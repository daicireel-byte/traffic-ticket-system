<?php
/**
 * NOTIFICATIONS PAGE
 * Location: notifications.php  
 */
session_start();
require_once 'config/database.php';

$page_title = "All Notifications";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>All Notifications</h1>
    </div>

    <div class="card">
        <h2>Notification History</h2>
        
        <div class="notification-list">
            <div class="notification-item">
                <div class="notification-icon new-ticket">🎫</div>
                <div class="notification-content">
                    <div class="notification-title">New Ticket Created</div>
                    <div class="notification-message">Ticket #TCK-202411-0001 has been created</div>
                    <div class="notification-time">Today, 10:30 AM</div>
                </div>
            </div>
            
            <div class="notification-item">
                <div class="notification-icon payment-received">💰</div>
                <div class="notification-content">
                    <div class="notification-title">Payment Received</div>
                    <div class="notification-message">Payment of ₱1,500.00 received for Ticket #TCK-202410-n000</div>
                    <div class="notification-time">Yesterday, 2:15 PM</div>
                </div>
            </div>
            
            <div class="notification-item">
                <div class="notification-icon overdue-ticket">⚠️</div>
                <div class="notification-content">
                    <div class="notification-title">Overdue Ticket</div>
                    <div class="notification-message">Ticket #TCK-202410-n002 is overdue - please follow up</div>
                    <div class="notification-time">Nov 23, 2024</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>