<?php
/**
 * lang/en.php
 * All English interface text in one place
 * To add a new text string: add a new key => value pair here AND in fr.php
 */

return [
    // --- App Name & Navigation ---
    'app_name'              => 'PlaceParole',
    'app_tagline'           => 'Market Feedback & Communication Platform',
    'nav_home'              => 'Home',
    'nav_complaints'        => 'Complaints',
    'nav_suggestions'       => 'Suggestions',
    'nav_community'         => 'Community',
    'nav_announcements'     => 'Announcements',
    'nav_dashboard'         => 'Dashboard',
    'nav_profile'           => 'Profile',
    'nav_logout'            => 'Logout',
    'nav_language'          => 'Language',

    // --- Authentication ---
    'login'                 => 'Login',
    'register'              => 'Register',
    'email'                 => 'Email Address',
    'password'              => 'Password',
    'password_confirm'      => 'Confirm Password',
    'name'                  => 'Full Name',
    'phone'                 => 'Phone Number',
    'stall_number'          => 'Stall Number',
    'select_market'         => 'Select your Market',
    'select_category'       => 'Select a Category',
    'register_market'       => 'Register a New Market',
    'market_name'           => 'Market Name',
    'market_location'       => 'Market Location',
    'i_am_a'                => 'I am a',
    'seller'                => 'Seller',
    'manager'               => 'Manager',
    'already_have_account'  => 'Already have an account?',
    'login_here'            => 'Login here',
    'no_account'            => 'No account yet?',
    'register_now'          => 'Register now',
    'login_success'         => 'Login successful! Welcome back.',
    'register_success'      => 'Registration successful! You can now login.',
    'logout_success'        => 'You have been logged out.',

    // Registration page headings/intros
    'seller_registration'          => 'Seller Registration',
    'register_seller'              => 'Register as seller',
    'seller_registration_intro'    => 'Choose your market and create your seller profile.',

    'manager_registration'         => 'Market Manager Registration',
    'register_manager_intro'       => 'Create your market and manager account in one step.',

    // --- Complaints ---
    'submit_complaint'      => 'Submit a Complaint',
    'complaint_category'    => 'Category',
    'complaint_description' => 'Describe your complaint',
    'complaint_sent'        => 'Your complaint has been received.',
    'your_ref_code'         => 'Your reference code is',
    'keep_ref_code'         => 'Please save this code to track your complaint.',
    'track_complaint'       => 'Track a Complaint',
    'enter_ref_code'        => 'Enter your reference code',
    'view_status'           => 'View Status',
    'cat_infrastructure'    => 'Infrastructure',
    'cat_sanitation'        => 'Sanitation',
    'cat_stall_allocation'  => 'Stall Allocation',
    'cat_security'          => 'Security',
    'cat_other'             => 'Other',

    // --- Status Labels ---
    'status_pending'        => 'Pending',
    'status_in_review'      => 'In Review',
    'status_resolved'       => 'Resolved',

    // --- Suggestions ---
    'submit_suggestion'     => 'Submit a Suggestion',
    'suggestion_title'      => 'Title',
    'suggestion_description'=> 'Describe your idea',
    'suggestion_sent'       => 'Your suggestion has been submitted.',

    // --- Community ---
    'report_event'          => 'Report a Community Event',
    'event_type'            => 'Event Type',
    'event_death'           => 'Death',
    'event_illness'         => 'Illness',
    'event_emergency'       => 'Emergency',
    'event_other'           => 'Other',
    'person_name'           => 'Name of person affected',
    'event_description'     => 'Details',
    'report_sent'           => 'Your report has been shared with the community.',

    // --- Announcements ---
    'announcements'         => 'Announcements',
    'no_announcements'      => 'No announcements yet.',
    'new_announcement'      => 'New Announcement',
    'announcement_title'    => 'Title',
    'announcement_body'     => 'Message',
    'announcement_picture'  => 'Picture',
    'send_via'              => 'Send via',
    'broadcast_announcement'=> 'Broadcast Announcement',
    'announcement_channels' => 'Send via',
    'channel_web'           => 'Web/In-App',
    'channel_sms'           => 'SMS',
    'channel_email'         => 'Email',
    'channel_gmail'         => 'Gmail',
    'channel_whatsapp'      => 'WhatsApp',
    'error_select_channel'  => 'Please select at least one channel.',
    'announcement_sent'     => 'Announcement sent successfully to all channels.',

    // --- Community Feedback ---
    'submit_feedback'       => 'Share Your Feedback',
    'feedback_description'  => 'Share ideas, suggestions, or feedback about the market community',
    'feedback_title'        => 'Feedback Title',
    'feedback_message'      => 'Your Feedback',
    'feedback_title_placeholder' => 'Brief title of your feedback',
    'feedback_placeholder'  => 'Share your ideas or concerns in detail...',
    'feedback_sent'         => 'Thank you! Your feedback has been received and will be reviewed by our team.',
    'feedback_anonymous'    => 'Your feedback will remain anonymous to other market members.',

    // --- Pending Moderation ---
    'pending_suggestions'   => 'Pending Suggestions',
    'pending_feedback'      => 'Pending Feedback',
    'approve'               => 'Approve',
    'reject'                => 'Reject',
    'approved'              => 'Approved',
    'rejected'              => 'Rejected',
    'reason'                => 'Reason (optional)',
    'optional'              => 'optional',
    'approve_success'       => 'Successfully approved',
    'reject_success'        => 'Successfully rejected',

    // --- Notifications ---
    'notifications'         => 'Notifications',
    'no_notifications'      => 'No new notifications',
    'new_suggestion_notif'  => 'New suggestion pending approval',
    'new_feedback_notif'    => 'New feedback pending approval',
    'new_announcement_notif'=> 'New announcement from management',
    'suggestion_approved'   => 'Your suggestion was approved',
    'feedback_approved'     => 'Your feedback was approved',

    // --- Dashboard (Manager) ---
    'manager_dashboard'     => 'Manager Dashboard',
    'analytics_dashboard'   => 'Analytics Dashboard',
    'total_complaints'      => 'Total Complaints',
    'pending_complaints'    => 'Pending',
    'resolved_complaints'   => 'Resolved',
    'all_complaints'        => 'All Complaints',
    'filter_by_status'      => 'Filter by Status',
    'filter_by_category'    => 'Filter by Category',
    'all_categories'        => 'All Categories',
    'seller_name'           => 'Seller Name',
    'date'                  => 'Date',
    'actions'               => 'Actions',
    'view'                  => 'View',
    'respond'               => 'Respond',
    'mark_resolved'         => 'Mark as Resolved',

    // --- Analytics ---
    'by_category'           => 'Complaints by Category',
    'by_month'              => 'Complaints by Month',
    'recent_complaints'     => 'Recent Complaints',
    'avg_resolution_time'   => 'Average Resolution Time',
    'complaints_last_12_months' => 'Last 12 Months',
    'hours'                 => 'hrs',
    'no_resolved'           => 'No resolved yet',
    'nav_analytics'         => 'Analytics',

    // --- General ---
    'submit'                => 'Submit',
    'save'                  => 'Save',
    'cancel'                => 'Cancel',
    'back'                  => 'Back',
    'search'                => 'Search',
    'filter'                => 'Filter',
    'all'                   => 'All',
    'category'              => 'Category',
    'created_at'            => 'Submitted',
    'updated_at'            => 'Updated',
    'response'              => 'Response',
    'error_required'        => 'This field is required.',
    'error_invalid_email'   => 'Please enter a valid email address.',
    'error_password_match'  => 'Passwords do not match.',
    'error_email_exists'    => 'This email is already registered.',
    'error_phone_exists'    => 'This phone number is already registered.',
    'error_invalid_login'   => 'Invalid email or password.',
    'success'               => 'Success',
    'error'                 => 'Error',
    'close'                 => 'Close',
    'loading'               => 'Loading...',
];
?>
