<?php
/**
 * GET NOTIFICATIONS (AJAX)
 * Location: ajax/get_notifications.php
 * Returns unread notifications as JSON
 * Called when user clicks the bell icon
 */

// Start session
session_start();

// Include database and helper
require_once '../config/database.php';
require_once '../config/NotificationHelper.php';

// Set response as JSON
header('Content-Type: application/json');

try {
    // Create notification helper
    $notificationHelper = new NotificationHelper($pdo);
    
    // Get unread notifications (limit 10)
    $notifications = $notificationHelper->getUnreadNotifications(10);
    
    // Return as JSON
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);
    
} catch (Exception $e) {
    // If error, return error message
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}