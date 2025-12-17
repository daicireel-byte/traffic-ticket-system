<?php
/**
 * MARK ALL NOTIFICATIONS AS READ (AJAX)
 * Location: ajax/mark_all_read.php
 * Marks all unread notifications as read
 */

session_start();

require_once '../config/database.php';
require_once '../config/NotificationHelper.php';

header('Content-Type: application/json');

try {
    // Create helper
    $notificationHelper = new NotificationHelper($pdo);
    
    // Mark all as read
    $affectedRows = $notificationHelper->markAllAsRead();
    
    echo json_encode([
        'success' => true,
        'message' => "$affectedRows notifications marked as read",
        'count' => $affectedRows
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}