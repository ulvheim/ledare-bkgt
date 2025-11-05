<?php
/**
 * Notification handler
 */

class BKGT_Communication_Notification {
    
    /**
     * Get notifications for user
     */
    public static function get_notifications($user_id, $limit = 20, $unread_only = false) {
        global $wpdb;
        
        $query = "SELECT * FROM {$wpdb->prefix}bkgt_notifications WHERE user_id = %d";
        $params = array($user_id);
        
        if ($unread_only) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;
        
        $notifications = $wpdb->get_results(
            $wpdb->prepare($query, $params)
        );
        
        return $notifications ?: array();
    }
    
    /**
     * Get unread notification count
     */
    public static function get_unread_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_notifications 
             WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
        
        return (int)$count;
    }
    
    /**
     * Mark notification as read
     */
    public static function mark_read($notification_id) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'bkgt_notifications',
            array('is_read' => 1),
            array('id' => $notification_id),
            array('%d'),
            array('%d')
        );
    }
    
    /**
     * Mark all notifications as read for user
     */
    public static function mark_all_read($user_id) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'bkgt_notifications',
            array('is_read' => 1),
            array('user_id' => $user_id, 'is_read' => 0),
            array('%d'),
            array('%d', '%d')
        );
    }
    
    /**
     * Delete notification
     */
    public static function delete_notification($notification_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'bkgt_notifications',
            array('id' => $notification_id),
            array('%d')
        );
    }
    
    /**
     * Create custom notification
     */
    public static function create($user_id, $message, $type = 'info') {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'bkgt_notifications',
            array(
                'user_id' => $user_id,
                'message' => $message,
                'type' => $type,
                'is_read' => 0,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
    }
}