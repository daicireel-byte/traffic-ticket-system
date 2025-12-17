<?php
/**
 * NOTIFICATION HELPER
 * Location: config/NotificationHelper.php
 */
class NotificationHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get unread notifications from database
     */
    public function getUnreadNotifications($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE is_read = 0 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
            return $stmt->execute([$notificationId]);
        } catch (PDOException $e) {
            error_log("Error marking as read: " . $e->getMessage());
            return false;
        }
    }
    
    public function markAllAsRead() {
        try {
            return $this->pdo->exec("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
        } catch (PDOException $e) {
            error_log("Error marking all as read: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Create new notification
     */
    public function createNotification($notification_type, $title, $message, $related_id = null, $related_type = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (notification_type, title, message, related_id, related_type, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            return $stmt->execute([$notification_type, $title, $message, $related_id, $related_type]);
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get link based on related_type and related_id
     */
    public function getNotificationLink($related_type, $related_id) {
        if (!$related_id) return '#';
        
        switch($related_type) {
            case 'TICKET':
                return 'view_ticket.php?id=' . $related_id;
            case 'PAYMENT':
                return 'payments.php';
            case 'VIOLATOR':
                return 'violators.php';
            default:
                return '#';
        }
    }
    
    /**
     * Get icon based on notification type
     */
    public function getNotificationIcon($notification_type) {
        $icons = [
            'NEW_TICKET' => '📝',
            'PAYMENT_RECEIVED' => '💰',
            'OVERDUE_TICKET' => '⚠️',
            'PAYMENT_REMINDER' => '📅',
            'SYSTEM' => '🔔'
        ];
        return $icons[$notification_type] ?? '🔔';
    }
    
    /**
     * Format time ago
     */
    public function getTimeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        
        return date('M d, Y', $timestamp);
    }
}

?>
