<?php
/**
 * config/complaint_helpers.php
 * 
 * Core functions for complaint management, threading, and response routing
 * 
 * FUNCTIONS:
 * - getComplaintThread()          Get full conversation thread
 * - sendComplaintResponse()       Send response through original channel
 * - routeResponseByChannel()      Route to email/SMS/Gmail/web
 * - markMessageAsRead()           Update read receipt
 * - checkSLACompliance()          Check SLA status
 * - getThreadStats()              Get threading statistics
 * - getUnreadCount()              Count unread messages
 * - assignComplaintToManager()    Assign complaint to manager
 * - updateComplaintStatus()       Update and log status changes
 */

require_once __DIR__ . '/db.php';

/**
 * getComplaintThread($complaint_id, $limit = 100)
 * 
 * Fetch complete conversation thread for a complaint
 * Returns all messages (submission + responses) in chronological order
 * 
 * @param int $complaint_id - Complaint ID
 * @param int $limit - Max messages to return (pagination support)
 * @return array - Array of messages with sender details
 */
function getComplaintThread($complaint_id, $limit = 100) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            cm.id,
            cm.complaint_id,
            cm.sender_id,
            cm.sender_role,
            cm.message_type,
            cm.content,
            cm.channel_sent,
            cm.sent_at,
            cm.read_at,
            u.name AS sender_name,
            u.role,
            u.email,
            u.phone,
            COUNT(ca.id) AS attachment_count
        FROM complaint_messages cm
        LEFT JOIN users u ON cm.sender_id = u.id
        LEFT JOIN complaint_attachments ca ON cm.id = ca.message_id
        WHERE cm.complaint_id = ?
        GROUP BY cm.id
        ORDER BY cm.sent_at ASC
        LIMIT ?
    ");
    
    $stmt->execute([$complaint_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * getComplaintDetails($complaint_id)
 * 
 * Fetch full complaint details including metadata
 * 
 * @param int $complaint_id - Complaint ID
 * @return array - Complaint data
 */
function getComplaintDetails($complaint_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u_seller.name AS seller_name,
            u_seller.phone AS seller_phone,
            u_seller.email AS seller_email,
            u_seller.stall_no,
            u_manager.name AS manager_name,
            u_manager.email AS manager_email,
            m.name AS market_name,
            m.location AS market_location
        FROM complaints c
        LEFT JOIN users u_seller ON c.seller_id = u_seller.id
        LEFT JOIN users u_manager ON c.manager_id = u_manager.id
        LEFT JOIN markets m ON c.market_id = m.id
        WHERE c.id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$complaint_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * sendComplaintResponse($complaint_id, $manager_id, $message, $status, $channel = null)
 * 
 * Send a manager response to a complaint through the original submission channel
 * This is the main function that orchestrates the entire response flow
 * 
 * @param int $complaint_id - Complaint ID
 * @param int $manager_id - Manager sending response
 * @param string $message - Response message text
 * @param string $status - New status (pending/in_review/resolved)
 * @param string|null $channel - Override channel (if null, uses original)
 * @return array - ['success' => bool, 'message' => string, 'message_id' => int]
 */
function sendComplaintResponse($complaint_id, $manager_id, $message, $status, $channel = null) {
    global $pdo;
    
    // Validation
    if (empty($message) || strlen($message) < 5) {
        return [
            'success' => false,
            'message' => 'Response message must be at least 5 characters',
            'message_id' => null
        ];
    }
    
    if (!in_array($status, ['pending', 'in_review', 'resolved'])) {
        return ['success' => false, 'message' => 'Invalid status', 'message_id' => null];
    }
    
    try {
        // Get complaint details
        $complaint = getComplaintDetails($complaint_id);
        if (!$complaint) {
            return ['success' => false, 'message' => 'Complaint not found', 'message_id' => null];
        }
        
        // Verify manager's market
        if ($complaint['market_id'] != $_SESSION['market_id']) {
            return ['success' => false, 'message' => 'Access denied', 'message_id' => null];
        }
        
        // Use original channel if not overridden
        $responseChannel = $channel ?? $complaint['channel'];
        
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Insert message into complaint_messages
        $msg_stmt = $pdo->prepare("
            INSERT INTO complaint_messages 
            (complaint_id, sender_id, sender_role, message_type, content, channel_sent, sent_at)
            VALUES (?, ?, 'manager', 'response', ?, ?, NOW())
        ");
        $msg_stmt->execute([$complaint_id, $manager_id, $message, $responseChannel]);
        $message_id = $pdo->lastInsertId();
        
        // 2. Update complaint status and metadata
        $response_time = null;
        if ($complaint['status'] === 'pending' && $status !== 'pending') {
            // Calculate time to first response
            $response_time = (strtotime('now') - strtotime($complaint['created_at']));
        }
        
        $upd_stmt = $pdo->prepare("
            UPDATE complaints 
            SET 
                status = ?,
                response = ?,
                thread_count = thread_count + 1,
                last_message_at = NOW(),
                last_message_from = 'manager',
                manager_id = ?,
                assigned_at = COALESCE(assigned_at, NOW()),
                response_time_secs = COALESCE(response_time_secs, ?),
                updated_at = NOW()
            WHERE id = ?
        ");
        $upd_stmt->execute([$status, $message, $manager_id, $response_time, $complaint_id]);
        
        // 3. Route response through channel
        $route_result = routeResponseByChannel(
            $responseChannel,
            $complaint['seller_phone'],
            $complaint['seller_email'],
            $message,
            $complaint['ref_code'],
            $complaint['seller_name']
        );
        
        // 4. Create notification record
        $notif_stmt = $pdo->prepare("
            INSERT INTO complaint_notifications 
            (complaint_id, message_id, recipient_id, notification_type, channel, status, external_id, created_at)
            VALUES (?, ?, ?, 'response_received', ?, ?, ?, NOW())
        ");
        $notif_stmt->execute([
            $complaint_id,
            $message_id,
            $complaint['seller_id'],
            $responseChannel,
            $route_result['success'] ? 'sent' : 'failed',
            $route_result['external_id'] ?? null
        ]);
        
        // 5. If status changed, create status change notification
        if ($status !== $complaint['status']) {
            $status_notif = $pdo->prepare("
                INSERT INTO complaint_notifications 
                (complaint_id, recipient_id, notification_type, channel, status, created_at)
                VALUES (?, ?, 'status_change', ?, 'pending', NOW())
            ");
            $status_notif->execute([
                $complaint_id,
                $complaint['seller_id'],
                $responseChannel
            ]);
        }
        
        $pdo->commit();
        
        return [
            'success' => $route_result['success'],
            'message' => $route_result['message'],
            'message_id' => $message_id,
            'notification_status' => $route_result['success'] ? 'sent' : 'failed'
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("sendComplaintResponse error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
            'message_id' => null
        ];
    }
}

/**
 * routeResponseByChannel($channel, $seller_phone, $seller_email, $message, $ref_code, $seller_name)
 * 
 * Route the response message to the appropriate channel
 * This function determines HOW to send (SMS, Email, Gmail, Web)
 * 
 * @param string $channel - 'web', 'sms', 'email', 'gmail'
 * @param string $seller_phone - Seller's phone number
 * @param string $seller_email - Seller's email address
 * @param string $message - Response message
 * @param string $ref_code - Complaint reference code
 * @param string $seller_name - Seller's name
 * @return array - ['success' => bool, 'message' => string, 'external_id' => string|null]
 */
function routeResponseByChannel($channel, $seller_phone, $seller_email, $message, $ref_code, $seller_name) {
    
    switch ($channel) {
        case 'sms':
            return sendResponseViaSMS($seller_phone, $message, $ref_code);
            
        case 'email':
        case 'gmail':
            return sendResponseViaEmail($seller_email, $seller_name, $message, $ref_code);
            
        case 'web':
            return createInAppNotification($seller_email, $message, $ref_code);
            
        default:
            return [
                'success' => false,
                'message' => 'Unknown channel: ' . $channel,
                'external_id' => null
            ];
    }
}

/**
 * sendResponseViaSMS($phone, $message, $ref_code)
 * 
 * Send response via SMS using the configured provider
 * Message is truncated for SMS character limit
 * 
 * @param string $phone - Seller's phone number
 * @param string $message - Response message (will be truncated)
 * @param string $ref_code - Complaint reference code
 * @return array - ['success' => bool, 'message' => string, 'external_id' => string|null]
 */
function sendResponseViaSMS($phone, $message, $ref_code) {
    if (empty($phone)) {
        return [
            'success' => false,
            'message' => 'Seller phone number not available',
            'external_id' => null
        ];
    }
    
    // Load SMS integration
    require_once __DIR__ . '/../integrations/sms_send.php';
    
    // Truncate message for SMS (max ~150 chars including ref code)
    $sms_message = "Update on complaint {$ref_code}: ";
    $max_content = 160 - strlen($sms_message) - 10; // -10 for link text
    $truncated = substr($message, 0, $max_content);
    if (strlen($message) > $max_content) {
        $truncated .= "...";
    }
    $sms_message .= $truncated;
    
    // Send SMS
    $success = sendSMS($phone, $sms_message);
    
    return [
        'success' => $success,
        'message' => $success ? 'SMS sent successfully' : 'Failed to send SMS',
        'external_id' => $success ? 'sms_' . uniqid() : null
    ];
}

/**
 * sendResponseViaEmail($email, $name, $message, $ref_code)
 * 
 * Send response via email with formatted HTML
 * 
 * @param string $email - Seller's email
 * @param string $name - Seller's name
 * @param string $message - Response message
 * @param string $ref_code - Complaint reference code
 * @return array - ['success' => bool, 'message' => string, 'external_id' => string|null]
 */
function sendResponseViaEmail($email, $name, $message, $ref_code) {
    if (empty($email)) {
        return [
            'success' => false,
            'message' => 'Seller email not available',
            'external_id' => null
        ];
    }
    
    // Load email integration
    require_once __DIR__ . '/../integrations/email_notify.php';
    
    // Send email using PHPMailer
    $success = sendComplaintUpdateEmail($email, $name, $ref_code, 'in_review', $message);
    
    return [
        'success' => $success,
        'message' => $success ? 'Email sent successfully' : 'Failed to send email',
        'external_id' => $success ? 'email_' . uniqid() : null
    ];
}

/**
 * createInAppNotification($email, $message, $ref_code)
 * 
 * Create an in-app (web) notification
 * For web channel: notification is stored and shown in dashboard
 * 
 * @param string $email - Seller's email (for logging)
 * @param string $message - Notification message
 * @param string $ref_code - Complaint reference code
 * @return array - ['success' => true, 'message' => string, 'external_id' => string]
 */
function createInAppNotification($email, $message, $ref_code) {
    // In-app notifications are already created via complaint_notifications table
    // This function is here for consistency
    return [
        'success' => true,
        'message' => 'In-app notification created',
        'external_id' => 'in_app_' . uniqid()
    ];
}

/**
 * markMessageAsRead($message_id, $user_id)
 * 
 * Mark a message as read by the recipient
 * Updates both complaint_messages and complaint_notifications
 * 
 * @param int $message_id - Message ID
 * @param int $user_id - User reading the message
 * @return bool - Success
 */
function markMessageAsRead($message_id, $user_id) {
    global $pdo;
    
    try {
        // Update read_at timestamp
        $stmt = $pdo->prepare("
            UPDATE complaint_messages 
            SET read_at = NOW() 
            WHERE id = ? AND read_at IS NULL
        ");
        $stmt->execute([$message_id]);
        
        // Update notification status
        $notif_stmt = $pdo->prepare("
            UPDATE complaint_notifications 
            SET status = 'read' 
            WHERE message_id = ? AND recipient_id = ? AND status != 'read'
        ");
        $notif_stmt->execute([$message_id, $user_id]);
        
        return true;
    } catch (Exception $e) {
        error_log("markMessageAsRead error: " . $e->getMessage());
        return false;
    }
}

/**
 * checkSLACompliance($complaint_id)
 * 
 * Check if complaint is within SLA window
 * Returns time remaining and status
 * 
 * @param int $complaint_id - Complaint ID
 * @return array - ['compliant' => bool, 'time_remaining' => int, 'status' => string]
 */
function checkSLACompliance($complaint_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT sla_deadline, status FROM complaints WHERE id = ? LIMIT 1
    ");
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$complaint) {
        return ['compliant' => false, 'time_remaining' => 0, 'status' => 'not_found'];
    }
    
    if ($complaint['status'] === 'resolved') {
        return ['compliant' => true, 'time_remaining' => -1, 'status' => 'resolved'];
    }
    
    $now = time();
    $deadline = strtotime($complaint['sla_deadline']);
    $remaining = $deadline - $now;
    
    if ($remaining <= 0) {
        return ['compliant' => false, 'time_remaining' => 0, 'status' => 'breached'];
    } elseif ($remaining < 3600) {  // Less than 1 hour
        return ['compliant' => true, 'time_remaining' => $remaining, 'status' => 'warning'];
    } else {
        return ['compliant' => true, 'time_remaining' => $remaining, 'status' => 'ok'];
    }
}

/**
 * getThreadStats($complaint_id)
 * 
 * Get conversation thread statistics
 * 
 * @param int $complaint_id - Complaint ID
 * @return array - Thread statistics
 */
function getThreadStats($complaint_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_messages,
            SUM(CASE WHEN sender_role='manager' THEN 1 ELSE 0 END) as manager_messages,
            SUM(CASE WHEN sender_role='seller' THEN 1 ELSE 0 END) as seller_messages,
            SUM(CASE WHEN read_at IS NULL AND sender_role='manager' THEN 1 ELSE 0 END) as unread_by_seller,
            MIN(sent_at) as first_message,
            MAX(sent_at) as last_message
        FROM complaint_messages 
        WHERE complaint_id = ?
    ");
    $stmt->execute([$complaint_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * getUnreadCount($user_id, $market_id = null)
 * 
 * Get count of unread notifications for a user
 * 
 * @param int $user_id - User ID
 * @param int|null $market_id - Optional: filter by market
 * @return int - Count of unread
 */
function getUnreadCount($user_id, $market_id = null) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) FROM complaint_notifications WHERE recipient_id = ? AND status IN ('pending', 'sent')";
    $params = [$user_id];
    
    if ($market_id) {
        $sql .= " AND complaint_id IN (SELECT id FROM complaints WHERE market_id = ?)";
        $params[] = $market_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * assignComplaintToManager($complaint_id, $manager_id)
 * 
 * Assign a complaint to a manager
 * 
 * @param int $complaint_id - Complaint ID
 * @param int $manager_id - Manager ID
 * @return bool - Success
 */
function assignComplaintToManager($complaint_id, $manager_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE complaints 
            SET manager_id = ?, assigned_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$manager_id, $complaint_id]);
    } catch (Exception $e) {
        error_log("assignComplaintToManager error: " . $e->getMessage());
        return false;
    }
}

/**
 * updateComplaintStatus($complaint_id, $new_status, $notes = '')
 * 
 * Update complaint status with logging
 * 
 * @param int $complaint_id - Complaint ID
 * @param string $new_status - New status
 * @param string $notes - Optional internal notes
 * @return bool - Success
 */
function updateComplaintStatus($complaint_id, $new_status, $notes = '') {
    global $pdo;
    
    if (!in_array($new_status, ['pending', 'in_review', 'resolved'])) {
        return false;
    }
    
    try {
        $update_data = [
            'status' => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($new_status === 'resolved') {
            $update_data['resolved_at'] = date('Y-m-d H:i:s');
        }
        
        $set_clause = implode(', ', array_map(fn($k) => "$k = :{$k}", array_keys($update_data)));
        $sql = "UPDATE complaints SET {$set_clause} WHERE id = :id";
        
        $params = array_merge(['id' => $complaint_id], $update_data);
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log("updateComplaintStatus error: " . $e->getMessage());
        return false;
    }
}

?>
