<?php
/**
 * Message handler
 */

class BKGT_Communication_Message {
    
    /**
     * Send message to recipients
     */
    public static function send($subject, $content, $recipients, $sender_id = null) {
        global $wpdb;
        
        if (!$sender_id) {
            $sender_id = get_current_user_id();
        }
        
        // Validate sender is logged in
        if (!$sender_id) {
            bkgt_log('warning', 'Message send failed - no sender identified');
            return false;
        }
        
        // Check permissions
        if (!bkgt_can('send_messages', $sender_id)) {
            bkgt_log('warning', 'Message send denied - insufficient permissions', array(
                'user_id' => $sender_id,
            ));
            return false;
        }
        
        // Validate subject and content
        if (empty($subject) || empty($content)) {
            bkgt_log('warning', 'Message send failed - empty subject or content');
            return false;
        }
        
        // Serialize recipients
        $recipients_json = is_array($recipients) ? json_encode($recipients) : $recipients;
        
        // Insert message
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'bkgt_messages',
            array(
                'sender_id' => $sender_id,
                'subject' => sanitize_text_field($subject),
                'message' => wp_kses_post($content),
                'recipients' => $recipients_json,
                'sent_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if (!$inserted) {
            bkgt_log('error', 'Failed to insert message into database', array(
                'sender_id' => $sender_id,
                'recipients' => $recipients_json,
            ));
            return false;
        }
        
        $message_id = $wpdb->insert_id;
        
        // Create notifications for recipients
        self::create_notifications($message_id, $subject, $recipients);
        
        bkgt_log('info', 'Message sent successfully', array(
            'message_id' => $message_id,
            'sender_id' => $sender_id,
            'recipients' => $recipients_json,
        ));
        
        return $message_id;
    }
    
    /**
     * Create notifications for message recipients
     */
    private static function create_notifications($message_id, $subject, $recipients) {
        global $wpdb;
        
        // Resolve recipient user IDs
        $user_ids = self::resolve_recipients($recipients);
        
        if (empty($user_ids)) {
            return false;
        }
        
        // Prepare notification message
        $notification_message = sprintf(
            __('Nytt meddelande: %s', 'bkgt-communication'),
            $subject
        );
        
        // Insert notification for each recipient
        foreach ($user_ids as $user_id) {
            $wpdb->insert(
                $wpdb->prefix . 'bkgt_notifications',
                array(
                    'user_id' => $user_id,
                    'message' => $notification_message,
                    'type' => 'message',
                    'is_read' => 0,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%d', '%s')
            );
        }
        
        return true;
    }
    
    /**
     * Resolve recipient groups to user IDs
     */
    private static function resolve_recipients($recipients) {
        global $wpdb;
        
        $user_ids = array();
        $recipients = is_array($recipients) ? $recipients : array($recipients);
        
        foreach ($recipients as $recipient) {
            if ($recipient === 'all') {
                // Get all users
                $users = get_users(array(
                    'fields' => 'ID',
                    'number' => -1,
                ));
                $user_ids = array_merge($user_ids, $users);
            } elseif ($recipient === 'coaches') {
                // Get users with coach role
                $users = get_users(array(
                    'role' => 'bkgt_coach',
                    'fields' => 'ID',
                    'number' => -1,
                ));
                $user_ids = array_merge($user_ids, $users);
            } elseif ($recipient === 'managers') {
                // Get users with manager role
                $users = get_users(array(
                    'role' => 'bkgt_manager',
                    'fields' => 'ID',
                    'number' => -1,
                ));
                $user_ids = array_merge($user_ids, $users);
            } elseif (is_numeric($recipient)) {
                // Direct user ID
                $user_ids[] = (int)$recipient;
            }
        }
        
        return array_unique($user_ids);
    }
    
    /**
     * Get message by ID
     */
    public static function get_message($message_id) {
        global $wpdb;
        
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_messages WHERE id = %d",
            $message_id
        ));
        
        if ($message) {
            $message->recipients = json_decode($message->recipients, true);
        }
        
        return $message;
    }
    
    /**
     * Get all messages for user
     */
    public static function get_user_messages($user_id, $limit = 10, $offset = 0) {
        global $wpdb;
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_messages 
             WHERE sender_id = %d OR recipients LIKE %s
             ORDER BY sent_at DESC
             LIMIT %d OFFSET %d",
            $user_id,
            '%"' . $user_id . '"%',
            $limit,
            $offset
        ));
        
        foreach ($messages as $msg) {
            $msg->recipients = json_decode($msg->recipients, true);
        }
        
        return $messages;
    }
}