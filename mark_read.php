<?php
/**
 * MARK NOTIFICATION AS READ (AJAX)
 * Location: ajax/mark_read.php
 * Marks a single notification as read
 */

session_start();

require_once '../config/database.php';
require_once '../config/NotificationHelper.php';

header('Content-Type: application/json');

try {
    // notification ID from request
    $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
    
    if ($notification_id <= 0) {
        throw new Exception('Invalid notification ID');
    }
    $notificationHelper = new NotificationHelper($pdo);
    
    $success = $notificationHelper->markAsRead($notification_id);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        throw new Exception('Failed to mark as read');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}