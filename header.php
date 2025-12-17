<?php
/**
 * HEADER TEMPLATE
 * Location: includes/header.php
 * PURPOSE: Common header section for all pages
 */ 

// Get current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Load notification helper. NOTE: $pdo must be available (loaded by parent file)
// Using a check to ensure the file exists before including
$notification_helper_path = __DIR__ . '/../config/NotificationHelper.php';
if (file_exists($notification_helper_path)) {
    require_once $notification_helper_path;
    // Check if $pdo is available before initializing the helper
    if (isset($pdo)) {
        $notificationHelper = new NotificationHelper($pdo);
        $unread_count = $notificationHelper->getUnreadCount();
    } else {
        // Fallback if database connection is not set up correctly in the parent file
        $unread_count = 0;
    }
} else {
    // Critical error if helper file is missing
    $unread_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Traffic Ticket System</title>
    
    <link rel="stylesheet" href="css/style.css">
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚦</text></svg>">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                Traffic Ticket System
            </a>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                <ul class="navbar-menu">
                    <li>
                        <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="tickets.php" class="<?= in_array($current_page, ['tickets.php', 'add_ticket.php', 'view_ticket.php']) ? 'active' : '' ?>">
                            Tickets
                        </a>
                    </li>
                    <li>
                        <a href="violators.php" class="<?= in_array($current_page, ['violators.php', 'add_violator.php']) ? 'active' : '' ?>">
                            Violators
                        </a>
                    </li>
                    <li>
                        <a href="payments.php" class="<?= in_array($current_page, ['payments.php', 'add_payment.php']) ? 'active' : '' ?>">
                            Payments
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
                            Reports
                        </a>
                    </li>
                </ul>
                
                <div class="notification-container">
                    <button class="notification-bell <?= $unread_count > 0 ? 'has-notifications' : '' ?>" id="notificationBell">
                        <span style="font-size: 20px;">🔔</span>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge" id="notificationBadge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                        <div style="padding: 2rem; text-align: center; color: #999;">
                            Loading notifications...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <?php
    // Display success message if exists
    if (isset($_SESSION['success'])) {
        echo '<div class="container" style="margin-top: 2rem;">
                  <div class="alert alert-success">
                      ' . $_SESSION['success'] . '
                  </div>
              </div>';
        unset($_SESSION['success']);
    }

    // Display error message if exists
    if (isset($_SESSION['error'])) {
        echo '<div class="container" style="margin-top: 2rem;">
                  <div class="alert alert-error">
                      ' . $_SESSION['error'] . '
                  </div>
              </div>';
        unset($_SESSION['error']);
    }
    ?>
    
    <script>
    // Wait for page to load
    document.addEventListener('DOMContentLoaded', function() {
        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationBell && notificationDropdown) {
            // Toggle dropdown when clicking the bell
            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                
                if (notificationDropdown.style.display === 'block') {
                    notificationDropdown.style.display = 'none';
                } else {
                    // Load fresh notifications when opening dropdown
                    loadNotificationDropdown();
                    notificationDropdown.style.display = 'block';
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.style.display = 'none';
                }
            });
            
            // Prevent dropdown from closing when clicking inside it
            notificationDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // ✨ AUTO-REFRESH: Check for new notifications every 30 seconds
            setInterval(function() {
                updateNotificationCount();
            }, 30000); // 30 seconds
            
            // Initial update
            updateNotificationCount();
        }
    });
    
    // Load notification dropdown content
    function loadNotificationDropdown() {
        // Using relative path to the root (since header.php is in includes/)
        fetch('notification_dropdown.php') 
            .then(response => {
                if (!response.ok) {
                    // Correcting for when the file is not in the root
                    if (response.status === 404) {
                        return fetch('../notification_dropdown.php').then(res => res.text());
                    }
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                document.getElementById('notificationDropdown').innerHTML = html;
                attachMarkAllReadListener();
            })
            .catch(error => {
                console.error('Failed loading notifications:', error);
                document.getElementById('notificationDropdown').innerHTML = 
                    '<div style="padding: 2rem; text-align: center; color: #c62828;">Error loading notifications</div>';
            });
    }
    
    // Update notification badge count (called by auto-refresh)
    function updateNotificationCount() {
        // Using relative path to the root
        fetch('ajax/get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.count);
                } else {
                    console.error('Failed to get count:', data.error);
                }
            })
            .catch(error => {
                // Try alternate path if first one fails (e.g. if the page is in a subdirectory)
                fetch('../ajax/get_notification_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateNotificationBadge(data.count);
                        } else {
                            console.error('Failed to get count (Alt Path):', data.error);
                        }
                    })
                    .catch(altError => {
                        console.error('Error updating notification count:', altError);
                    });
            });
    }
    
    // Function to update notification badge (CRIT-1 FIX)
    function updateNotificationBadge(count) {
        const bell = document.getElementById('notificationBell');
        let badge = document.getElementById('notificationBadge');
        
        if (count > 0) {
            // Add 'has-notifications' class to the bell
            bell.classList.add('has-notifications');

            if (!badge) {
                // Create badge if it doesn't exist (if it was removed previously)
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                badge.id = 'notificationBadge';
                bell.appendChild(badge);
            }
            // Set/Update the content
            badge.textContent = count > 99 ? '99+' : count;

        } else {
            // Remove 'has-notifications' class from the bell
            bell.classList.remove('has-notifications');
            
            if (badge) {
                // Remove the badge element entirely
                badge.remove();
            }
        }
    }
    
    // Attach event listener to "Mark all read" button after dropdown loads
    function attachMarkAllReadListener() {
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Disable button and update text for user feedback
                markAllReadBtn.textContent = 'Marking...';
                markAllReadBtn.disabled = true;

                fetch('ajax/mark_all_read.php', {
                    method: 'POST',
                    // Using a path relative to the root, add an alternative fetch path for safety
                })
                .then(response => {
                    if (response.status === 404) {
                        // Try alternative path if first one fails
                        return fetch('../ajax/mark_all_read.php', { method: 'POST' });
                    }
                    return response;
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 🟢 CRITICAL FIX: Immediately update UI to zero
                        updateNotificationBadge(0);
                        
                        // Update dropdown content to show all as read
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                            item.classList.add('read');
                            // If you have a visual status indicator you want to change:
                            const statusSpan = item.querySelector('.notification-status');
                            if (statusSpan) {
                                statusSpan.textContent = 'Read';
                                statusSpan.style.color = '#27ae60';
                            }
                        });
                        
                        // Update "Mark all read" button text
                        markAllReadBtn.textContent = 'All notifications read';
                        markAllReadBtn.disabled = true;
                        markAllReadBtn.style.opacity = '0.7';

                    } else {
                        alert('Failed to mark all as read: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error marking all as read:', error);
                    alert('An unexpected network error occurred. Failed to mark all as read.');
                })
                .finally(() => {
                    // Re-enable button on error/completion if it's not already marked as read
                    if (markAllReadBtn.textContent !== 'All notifications read') {
                        markAllReadBtn.textContent = 'Mark all read';
                        markAllReadBtn.disabled = false;
                    }
                });
            });
        }
    }
    
    // Make functions globally available
    window.updateNotificationBadge = updateNotificationBadge;
    window.loadNotificationDropdown = loadNotificationDropdown;
    window.updateNotificationCount = updateNotificationCount;
    </script>
</body>
</html>