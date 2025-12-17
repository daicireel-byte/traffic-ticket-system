<?php
/**
 * GET NOTIFICATION COUNT ONLY (for auto-refresh)
 * Location: ajax/get_notification_count.php
 */

session_start();

require_once '../config/database.php';
require_once '../config/NotificationHelper.php';

header('Content-Type: application/json');

try {
    $notificationHelper = new NotificationHelper($pdo);
    $count = $notificationHelper->getUnreadCount();
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>