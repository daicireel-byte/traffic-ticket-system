<?php
/**
 * FOOTER TEMPLATE
 * Location: includes/footer.php
 * 
 * PURPOSE: Common footer section for all pages
 * USAGE: Include this at the bottom of every page
 */
?>

    <!-- Footer -->
    <footer style="
        background: #2c3e50;
        color: white;
        text-align: center;
        padding: 1.5rem;
        margin-top: 3rem;
    ">
        <p>&copy; <?= date('Y') ?> City Ordinance and Traffic Violation Payment System</p>
        <p style="font-size: 0.9rem; opacity: 0.8; margin-top: 0.5rem;">
            Developed for CC104 - Database Management System Project
        </p>
    </footer>

    <!-- JavaScript (if needed) -->
    <script>
        // Confirmation for delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>

</body>
</html>