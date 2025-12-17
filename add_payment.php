<?php
/**
 * ADD PAYMENT FORM
 * Location: add_payment.php
 * * PURPOSE: Process payment for a ticket
 */

// 🟢 FIX 1: Load database/session FIRST. This must be the very first line of code.
require_once 'config/database.php';

// Get ticket ID from URL - using 'id' is more standard, but using 'ticket_id' to match your form action.
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($ticket_id == 0) {
    $_SESSION['error'] = "Invalid ticket ID";
    // 🟢 FIX 2: Redirect works here because no output has occurred.
    header("Location: tickets.php");
    exit;
}

// Get ticket details
// Assuming vw_tickets_detailed provides all necessary columns
$stmt = $pdo->prepare("SELECT * FROM vw_tickets_detailed WHERE id = ? AND status != 'PAID'");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = "Ticket not found or already paid";
    // 🟢 FIX 2: Redirect works here because no output has occurred.
    header("Location: tickets.php");
    exit;
}

// -----------------------------------------------------------
// LOGIC BEFORE HTML OUTPUT
// -----------------------------------------------------------

// Get payment methods
$payment_methods = $pdo->query("SELECT * FROM payment_methods WHERE status = 'ACTIVE' ORDER BY method_name")->fetchAll();

// Calculate current amount with late fees
$current_amount = $ticket['total_amount'];
$late_fee = 0;

// You defined calculateDaysOverdue() in database.php, let's use it
$days_overdue = calculateDaysOverdue($ticket['due_date']); 

if ($days_overdue > 0) {
    // Assuming a 3% rate per day as per your original code comment
    $late_rate = 3; 
    $late_fee = $ticket['total_amount'] * ($late_rate / 100) * $days_overdue; 
    $current_amount = $ticket['total_amount'] + $late_fee;
}

// Generate receipt number (from database.php helper)
$receipt_number = generateReceiptNumber($pdo);

// 🟢 FIX 3: Define CASHIER NAME Safely (handles "Undefined array key" warning)
// ASSUMING your session stores the user's display name in $_SESSION['user_name']
$cashier_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'System User (Not Logged In)';

// If you use $_SESSION['user']['id'] for the ID, and need the name:
/*
$cashier_id = $_SESSION['user']['id'] ?? 0;
$cashier_name = 'N/A';
if ($cashier_id > 0) {
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE id = ?");
    $stmt->execute([$cashier_id]);
    $cashier_name = $stmt->fetchColumn() ?? 'User ID ' . $cashier_id;
}
*/

// 🟢 FIX 4: Include header AFTER all processing and checks
$page_title = "Process Payment";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Process Payment</h1>
        <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-secondary">← Back to Ticket</a>
    </div>

    <?php if ($days_overdue > 0): ?>
    <div class="alert alert-warning">
        This ticket is <?= $days_overdue ?> day(s) overdue. 
        Late fee of ₱<?= number_format($late_fee, 2) ?> has been added.
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="card">
            <h2>Ticket Summary</h2>
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">Ticket Number:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['ticket_number']) ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">Violator:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['violator_name']) ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">License #:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['license_number']) ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">Violation:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['violation_name']) ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">Due Date:</td>
                    <td style="border: none; padding: 0.75rem 0;"><?= htmlspecialchars($ticket['due_date']) ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold; color: #7f8c8d;">Original Amount:</td>
                    <td style="border: none; padding: 0.75rem 0;">₱<?= number_format($ticket['total_amount'], 2) ?></td>
                </tr>
                <?php if ($late_fee > 0): ?>
                <tr style="color: #e67e22; font-weight: bold;">
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold;">Late Fee:</td>
                    <td style="border: none; padding: 0.75rem 0;">+ ₱<?= number_format($late_fee, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr style="background-color: #ecf0f1; font-size: 1.1em; border-top: 2px solid #bdc3c7;">
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold;">TOTAL PAYABLE:</td>
                    <td style="border: none; padding: 0.75rem 0; font-weight: bold;">₱<?= number_format($current_amount, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>

<?php if ($ticket['status'] != 'PAID'): ?>
<div class="card" style="grid-column: span 2; padding: 1rem; margin-top: 2rem; border: 2px solid #1a237e; border-radius: 4px;">
    <h2>Process Payment</h2>
    <form method="POST" action="process/process_payment.php">
        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Receipt Number</label>
                <input type="text" name="receipt_number" value="<?= $receipt_number ?>" required>
            </div>
            <div class="form-group">
                <label>Amount Paid</label>
                <input type="number" step="0.01" name="amount_paid" value="<?= number_format($current_amount, 2, '.', '') ?>" required>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method_id" required>
                    <?php foreach($payment_methods as $method): ?>
                        <option value="<?= $method['id'] ?>" <?= $method['id'] == 1 ? 'selected' : '' ?>>
                            <?= htmlspecialchars($method['method_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Processed By</label>
                <input type="text" value="Cashier: <?= htmlspecialchars($cashier_name) ?>" readonly>
                
                <input type="hidden" name="processed_by_id" value="<?= $_SESSION['user']['id'] ?? 0 ?>">
            </div>
            <div class="form-group">
                <label>Notes (Optional)</label>
                <textarea name="notes"></textarea>
            </div>
        </div>

        <div style="text-align: right; margin-top: 1rem;">
            <button type="submit" name="process_payment" style="padding: 8px 16px; background: #2e7d32; color: white; border: none; border-radius: 4px; cursor: pointer;">
                💳 Process Payment
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>