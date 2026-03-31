<?php
/**
 * config/notification_handler.php
 * 
 * Handles all notification creation, sending, and tracking
 * Manages delivery to multiple channels with retry logic
 * 
 * FUNCTIONS:
 * - createNotification()        Create notification record
 * - sendNotification()          Send notification through channel
 * - getUnreadNotifications()    Fetch unread notifications
 * - markNotificationAsRead()    Mark notification as read
 * - retryFailedNotifications()  Retry failed delivery
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/complaint_helpers.php';

/**
 * createNotification($complaint_id, $type, $recipient_id, $channel, $message_id = null)
 * 
 * Create a new notification record
 * 
 * @param int $complaint_id - Complaint ID
 * @param string $type - 'new_complaint', 'response_received', 'status_change', 'sla_warning', etc
 * @param int $recipient_id - User receiving notification
 * @param string $channel - 'web', 'sms', 'email', 'gmail', 'in_app'
 * @param int|null $message_id - Associated message ID if applicable
 * @return int - Notification ID or 0 on failure
 */
function createNotification($complaint_id, $type, $recipient_id, $channel, $message_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO complaint_notifications 
            (complaint_id, message_id, recipient_id, notification_type, channel, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([$complaint_id, $message_id, $recipient_id, $type, $channel]);
        return (int) $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("createNotification error: " . $e->getMessage());
        return 0;
    }
}

/**
 * sendNotification($notification_id)
 * 
 * Send a notification through its configured channel
 * Handles retry logic and error tracking
 * 
 * @param int $notification_id - Notification ID
 * @return bool - Success
 */
function sendNotification($notification_id) {
    global $pdo;
    
    try {
        // Fetch notification details
        $stmt = $pdo->prepare("
            SELECT 
                cn.*,
                u.email,
                u.phone,
                u.lang,
                c.ref_code,
                c.category,
                c.description,
                cm.content as message_content,
                cm.sent_at as message_sent_at
            FROM complaint_notifications cn
            JOIN users u ON cn.recipient_id = u.id
            JOIN complaints c ON cn.complaint_id = c.id
            LEFT JOIN complaint_messages cm ON cn.message_id = cm.id
            WHERE cn.id = ?
            LIMIT 1
        ");
        
        $stmt->execute([$notification_id]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$notification) {
            return false;
        }
        
        // Prepare notification content based on type
        $title = getNotificationTitle($notification['notification_type'], $notification['lang']);
        $body = getNotificationBody($notification, $title);
        
        // Send through appropriate channel
        $success = false;
        $external_id = null;
        
        switch ($notification['channel']) {
            case 'sms':
                $result = sendNotificationViaSMS(
                    $notification['phone'],
                    $notification['ref_code'],
                    $notification['notification_type'],
                    $notification['message_content']
                );
                $success = $result['success'];
                $external_id = $result['external_id'];
                break;
                
            case 'email':
                $result = sendNotificationViaEmail(
                    $notification['email'],
                    $notification['ref_code'],
                    $title,
                    $body
                );
                $success = $result['success'];
                $external_id = $result['external_id'];
                break;
                
            case 'in_app':
            case 'web':
                // In-app notifications are already stored, mark as sent
                $success = true;
                $external_id = 'in_app_' . $notification_id;
                break;
                
            case 'gmail':
                // Gmail integration (future)
                $success = true;
                $external_id = 'gmail_' . $notification_id;
                break;
        }
        
        // Update notification status
        $update_stmt = $pdo->prepare("
            UPDATE complaint_notifications 
            SET 
                status = ?,
                external_id = ?,
                attempt_count = attempt_count + 1,
                last_attempt_at = NOW()
            WHERE id = ?
        ");
        
        $update_stmt->execute([
            $success ? 'sent' : 'failed',
            $external_id,
            $notification_id
        ]);
        
        return $success;
        
    } catch (Exception $e) {
        error_log("sendNotification error: " . $e->getMessage());
        return false;
    }
}

/**
 * getNotificationTitle($type, $lang = 'en')
 * 
 * Get localized notification title
 * 
 * @param string $type - Notification type
 * @param string $lang - Language code
 * @return string - Title
 */
function getNotificationTitle($type, $lang = 'en') {
    $titles = [
        'en' => [
            'new_complaint' => 'New Complaint Submitted',
            'response_received' => 'Response to Your Complaint',
            'status_change' => 'Complaint Status Updated',
            'sla_warning' => 'SLA Deadline Approaching',
            'sla_breached' => 'SLA Deadline Exceeded',
            'acknowledged' => 'Complaint Acknowledged'
        ],
        'fr' => [
            'new_complaint' => 'Nouvelle Plainte Soumise',
            'response_received' => 'Réponse à Votre Plainte',
            'status_change' => 'Statut de la Plainte Mis à Jour',
            'sla_warning' => 'Délai SLA Approchant',
            'sla_breached' => 'Délai SLA Dépassé',
            'acknowledged' => 'Plainte Confirmée'
        ]
    ];
    
    return $titles[$lang][$type] ?? $titles['en'][$type] ?? 'Notification';
}

/**
 * getNotificationBody($notification, $title)
 * 
 * Generate notification body with relevant details
 * 
 * @param array $notification - Notification data
 * @param string $title - Notification title
 * @return string - Body text
 */
function getNotificationBody($notification, $title) {
    $ref = $notification['ref_code'];
    $category = $notification['category'] ?? 'General';
    
    switch ($notification['notification_type']) {
        case 'new_complaint':
            return "Your complaint {$ref} has been logged and assigned to our team for review.";
        case 'response_received':
            return "We have a response to your complaint {$ref}. Click to view the update.";
        case 'status_change':
            return "The status of your complaint {$ref} has been updated.";
        case 'sla_warning':
            return "Reminder: Your complaint {$ref} will reach the SLA deadline soon.";
        case 'sla_breached':
            return "URGENT: Your complaint {$ref} has exceeded the response deadline.";
        case 'acknowledged':
            return "Your {$category} complaint {$ref} has been acknowledged.";
        default:
            return $title;
    }
}

/**
 * sendNotificationViaSMS($phone, $ref_code, $type, $message)
 * 
 * Send notification via SMS
 * 
 * @param string $phone - Phone number
 * @param string $ref_code - Complaint reference code
 * @param string $type - Notification type
 * @param string|null $message - Optional message preview
 * @return array - ['success' => bool, 'external_id' => string|null]
 */
function sendNotificationViaSMS($phone, $ref_code, $type, $message = null) {
    if (empty($phone)) {
        return ['success' => false, 'external_id' => null];
    }
    
    require_once __DIR__ . '/../integrations/sms_send.php';
    
    $sms_text = "PlaceParole: ";
    
    switch ($type) {
        case 'response_received':
            $sms_text .= "Response to complaint {$ref_code}. " . substr($message, 0, 80) . "...";
            break;
        case 'status_change':
            $sms_text .= "Complaint {$ref_code} status updated. Check your account.";
            break;
        case 'sla_warning':
            $sms_text .= "Complaint {$ref_code}: SLA deadline approaching (24h left).";
            break;
        default:
            $sms_text .= "Update on complaint {$ref_code}. View details in your account.";
    }
    
    // Truncate to SMS limit
    if (strlen($sms_text) > 160) {
        $sms_text = substr($sms_text, 0, 157) . "...";
    }
    
    $success = sendSMS($phone, $sms_text);
    
    return [
        'success' => $success,
        'external_id' => $success ? 'sms_' . uniqid() : null
    ];
}

/**
 * sendNotificationViaEmail($email, $ref_code, $title, $body)
 * 
 * Send notification via email
 * 
 * @param string $email - Email address
 * @param string $ref_code - Complaint reference
 * @param string $title - Email title
 * @param string $body - Email body
 * @return array - ['success' => bool, 'external_id' => string|null]
 */
function sendNotificationViaEmail($email, $ref_code, $title, $body) {
    if (empty($email)) {
        return ['success' => false, 'external_id' => null];
    }
    
    require_once __DIR__ . '/../integrations/email_notify.php';
    
    // Generate email content
    $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #16a34a; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9fafb; padding: 20px; border-radius: 0 0 5px 5px; }
                .ref-code { color: #16a34a; font-weight: bold; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>{$title}</h2>
                </div>
                <div class='content'>
                    <p>Dear Seller,</p>
                    <p>{$body}</p>
                    <p>Complaint Reference: <span class='ref-code'>{$ref_code}</span></p>
                    <p><a href='" . getenv('APP_URL') . "/modules/complaints/track.php?ref={$ref_code}'>View Your Complaint</a></p>
                    <div class='footer'>
                        <p>PlaceParole Market Management Platform</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
    ";
    
    // Use PHPMailer
    $success = sendComplaintUpdateEmail($email, '', $ref_code, 'in_review', $body);
    
    return [
        'success' => $success,
        'external_id' => $success ? 'email_' . uniqid() : null
    ];
}

/**
 * getUnreadNotifications($user_id, $limit = 50)
 * 
 * Fetch unread notifications for a user
 * 
 * @param int $user_id - User ID
 * @param int $limit - Max results
 * @return array - Notification list
 */
function getUnreadNotifications($user_id, $limit = 50) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            cn.*,
            c.ref_code,
            c.category,
            c.description,
            u_sender.name as sender_name
        FROM complaint_notifications cn
        JOIN complaints c ON cn.complaint_id = c.id
        LEFT JOIN users u_sender ON cn.recipient_id = u_sender.id
        WHERE cn.recipient_id = ? 
          AND cn.status IN ('pending', 'sent')
        ORDER BY cn.created_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * markNotificationAsRead($notification_id)
 * 
 * Mark a notification as read
 * 
 * @param int $notification_id - Notification ID
 * @return bool - Success
 */
function markNotificationAsRead($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE complaint_notifications 
            SET status = 'read' 
            WHERE id = ?
        ");
        return $stmt->execute([$notification_id]);
    } catch (Exception $e) {
        error_log("markNotificationAsRead error: " . $e->getMessage());
        return false;
    }
}

/**
 * retryFailedNotifications($max_attempts = 3)
 * 
 * Retry sending failed notifications
 * Should be called by a scheduled task/cron job
 * 
 * @param int $max_attempts - Max retry attempts
 * @return array - ['retried' => int, 'successful' => int, 'failed' => int]
 */
function retryFailedNotifications($max_attempts = 3) {
    global $pdo;
    
    try {
        // Fetch failed notifications that haven't exceeded retry limit
        $stmt = $pdo->prepare("
            SELECT id FROM complaint_notifications 
            WHERE status = 'failed' 
              AND attempt_count < ?
              AND last_attempt_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            LIMIT 50
        ");
        
        $stmt->execute([$max_attempts]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [
            'retried' => count($notifications),
            'successful' => 0,
            'failed' => 0
        ];
        
        foreach ($notifications as $notif) {
            if (sendNotification($notif['id'])) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
        
    } catch (Exception $e) {
        error_log("retryFailedNotifications error: " . $e->getMessage());
        return ['retried' => 0, 'successful' => 0, 'failed' => 0];
    }
}

/**
 * ============================================================
 * GENERIC NOTIFICATION FUNCTIONS (for Suggestions, Announcements, Feedback)
 * ============================================================
 */

/**
 * createGenericNotification($market_id, $recipient_id, $notification_type, $subject_type, $subject_id, $channel = 'web')
 * 
 * Create a notification for suggestions, announcements, or feedback
 * 
 * @param int $market_id - Market ID
 * @param int $recipient_id - User receiving notification
 * @param string $notification_type - 'new_suggestion', 'suggestion_approved', 'new_announcement', etc
 * @param string $subject_type - 'suggestion', 'announcement', 'feedback'
 * @param int $subject_id - ID in the respective table
 * @param string $channel - 'web', 'sms', 'email', 'gmail', 'in_app'
 * @return int - Notification ID or 0 on failure
 */
function createGenericNotification($market_id, $recipient_id, $notification_type, $subject_type, $subject_id, $channel = 'web') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (market_id, recipient_id, notification_type, subject_type, subject_id, channel, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([$market_id, $recipient_id, $notification_type, $subject_type, $subject_id, $channel]);
        return (int) $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("createGenericNotification error: " . $e->getMessage());
        return 0;
    }
}

/**
 * notifyMarketUsersOfSubmission($market_id, $notification_type, $subject_type, $subject_id, $channels = ['web'])
 * 
 * Notify all users in a market of a new/approved submission
 * Used when suggestion/feedback is approved or announcement is posted
 * 
 * @param int $market_id - Market ID
 * @param string $notification_type - Type of notification
 * @param string $subject_type - 'suggestion', 'announcement', 'feedback'
 * @param int $subject_id - ID in respective table
 * @param array $channels - Channels to notify through
 * @return array - ['sent' => count, 'failed' => count]
 */
function notifyMarketUsersOfSubmission($market_id, $notification_type, $subject_type, $subject_id, $channels = ['web']) {
    global $pdo;
    
    try {
        // Get all users in the market (skip the submitter for certain notifications)
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE market_id = ? 
            ORDER BY id
        ");
        
        $stmt->execute([$market_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sent = 0;
        $failed = 0;
        
        foreach ($users as $user) {
            foreach ($channels as $channel) {
                $notif_id = createGenericNotification(
                    $market_id,
                    $user['id'],
                    $notification_type,
                    $subject_type,
                    $subject_id,
                    $channel
                );
                
                if ($notif_id > 0) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }
        
        return ['sent' => $sent, 'failed' => $failed];
        
    } catch (Exception $e) {
        error_log("notifyMarketUsersOfSubmission error: " . $e->getMessage());
        return ['sent' => 0, 'failed' => 0];
    }
}

/**
 * notifyManagersOfPendingSubmission($market_id, $notification_type, $subject_type, $subject_id)
 * 
 * Notify managers/admins of pending submission requiring review
 * 
 * @param int $market_id - Market ID
 * @param string $notification_type - 'new_suggestion', 'new_community_feedback'
 * @param string $subject_type - 'suggestion', 'feedback'
 * @param int $subject_id - ID in respective table
 * @return array - ['sent' => count, 'failed' => count]
 */
function notifyManagersOfPendingSubmission($market_id, $notification_type, $subject_type, $subject_id) {
    global $pdo;
    
    try {
        // Get managers and admins in the market
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE market_id = ? 
            AND role IN ('manager', 'admin')
            ORDER BY id
        ");
        
        $stmt->execute([$market_id]);
        $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sent = 0;
        $failed = 0;
        
        foreach ($managers as $manager) {
            $notif_id = createGenericNotification(
                $market_id,
                $manager['id'],
                $notification_type,
                $subject_type,
                $subject_id,
                'web'
            );
            
            if ($notif_id > 0) {
                $sent++;
            } else {
                $failed++;
            }
        }
        
        return ['sent' => $sent, 'failed' => $failed];
        
    } catch (Exception $e) {
        error_log("notifyManagersOfPendingSubmission error: " . $e->getMessage());
        return ['sent' => 0, 'failed' => 0];
    }
}

/**
 * getGenericNotifications($user_id, $market_id, $limit = 50)
 * 
 * Fetch notifications for suggestions/announcements/feedback
 * 
 * @param int $user_id - User ID
 * @param int $market_id - Market ID (for scoping)
 * @param int $limit - Max results
 * @return array - Notification list
 */
function getGenericNotifications($user_id, $market_id, $limit = 50) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                n.*,
                u_submitter.name AS submitter_name,
                s.title as suggestion_title,
                a.title as announcement_title,
                cf.title as feedback_title
            FROM notifications n
            LEFT JOIN users u_submitter ON n.subject_type = 'suggestion' AND n.subject_id = s.id
            LEFT JOIN suggestions s ON n.subject_type = 'suggestion' AND n.subject_id = s.id
            LEFT JOIN announcements a ON n.subject_type = 'announcement' AND n.subject_id = a.id
            LEFT JOIN community_feedback cf ON n.subject_type = 'feedback' AND n.subject_id = cf.id
            WHERE n.recipient_id = ? 
            AND n.market_id = ?
            AND n.status IN ('pending', 'sent')
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$user_id, $market_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getGenericNotifications error: " . $e->getMessage());
        return [];
    }
}

/**
 * markGenericNotificationAsRead($notification_id)
 * 
 * Mark a notification as read
 * 
 * @param int $notification_id - Notification ID
 * @return bool - Success
 */
function markGenericNotificationAsRead($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET status = 'read', read_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$notification_id]);
    } catch (Exception $e) {
        error_log("markGenericNotificationAsRead error: " . $e->getMessage());
        return false;
    }
}

?>
