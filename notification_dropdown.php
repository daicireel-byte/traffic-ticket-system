<?php
/**
 * BELL NOTIFICATION DROPDOWN CONTENT
 * Location: notification_dropdown.php
 */

// Load database
require_once 'config/database.php';
require_once 'config/NotificationHelper.php';

// Get notifications from database
$notificationHelper = new NotificationHelper($pdo);
$unreadNotifications = $notificationHelper->getUnreadNotifications(10);
$unreadCount = count($unreadNotifications);
?>

<div class="notification-header">
    <span><strong>Notifications</strong> <span id="notificationCount">(<?= $unreadCount ?> new)</span></span>
    <?php if ($unreadCount > 0): ?>
        <a href="#" onclick="markAllAsRead(); return false;">Mark all read</a>
    <?php endif; ?>
</div>

<div class="notification-list" id="notificationList">
    <?php if (empty($unreadNotifications)): ?>
        <div class="notification-empty">
            <div style="text-align: center; padding: 2rem; color: #999;">
                <div style="font-size: 48px; margin-bottom: 1rem;">🔔</div>
                <div>No new notifications</div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($unreadNotifications as $notification): 
            $link = $notificationHelper->getNotificationLink($notification['related_type'], $notification['related_id']);
            $icon = $notificationHelper->getNotificationIcon($notification['notification_type']);
        ?>
            <div class="notification-item unread" 
                 data-notification-id="<?= $notification['id'] ?>"
                 onclick="handleNotificationClick(<?= $notification['id'] ?>, '<?= htmlspecialchars($link) ?>')">
                <div class="notification-icon">
                    <?= $icon ?>
                </div>
                <div class="notification-content">
                    <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                    <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                    <div class="notification-time">
                        <?= $notificationHelper->getTimeAgo($notification['created_at']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="notification-footer">
    <a href="notifications.php">View All Notifications</a>
</div>

<script>
function handleNotificationClick(notificationId, link) {
    console.log('Notification clicked:', notificationId, 'Link:', link);
    
    // Mark as read via AJAX
    fetch('ajax/mark_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        console.log('Mark as read response:', data);
    })
    .catch(error => {
        console.error('Error marking as read:', error);
    });
    
    // Remove unread styling immediately
    const notificationItem = event.currentTarget;
    notificationItem.classList.remove('unread');
    
    // Update badge count
    const currentCount = parseInt(document.getElementById('notificationBadge')?.textContent || 0);
    if (currentCount > 0) {
        updateNotificationBadge(currentCount - 1);
    }
    
    // Redirect to the link
    if (link && link !== '' && link !== '#') {
        setTimeout(() => {
            window.location.href = link;
        }, 100);
    } else {
        // Close dropdown if no link
        document.getElementById('notificationDropdown').style.display = 'none';
    }
}

function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        // Show loading state
        const countElement = document.getElementById('notificationCount');
        if (countElement) {
            countElement.textContent = '(marking...)';
        }
        
        // Call AJAX to mark all as read
        fetch('ajax/mark_all_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Mark all as read response:', data);
            
            if (data.success) {
                // Update UI - remove unread styling
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Update count display in dropdown
                if (countElement) {
                    countElement.textContent = '(0 new)';
                }
                
                // CRITICAL FIX: Force badge removal
                const badge = document.getElementById('notificationBadge');
                const bell = document.getElementById('notificationBell');
                
                console.log('Badge element:', badge);
                console.log('Bell element:', bell);
                
                // Remove badge
                if (badge) {
                    console.log('Removing badge...');
                    badge.remove();
                }
                
                // Remove animation class
                if (bell) {
                    console.log('Removing has-notifications class...');
                    bell.classList.remove('has-notifications');
                }
                
                // Try to call global function if it exists
                if (typeof window.updateNotificationBadge === 'function') {
                    console.log('Calling updateNotificationBadge(0)...');
                    window.updateNotificationBadge(0);
                } else if (typeof updateNotificationBadge === 'function') {
                    console.log('Calling local updateNotificationBadge(0)...');
                    updateNotificationBadge(0);
                } else {
                    console.log('Badge removed manually (function not found)');
                }
                
                // Reload dropdown after delay
                setTimeout(() => {
                    loadNotificationDropdown();
                }, 800);
            } else {
                alert('Error marking notifications as read. Please try again.');
                loadNotificationDropdown();
            }
        })
        .catch(error => {
            console.error('Error marking all as read:', error);
            alert('Error marking notifications as read. Please try again.');
            loadNotificationDropdown();
        });
    }
}
</script>