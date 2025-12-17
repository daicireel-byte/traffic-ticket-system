<?php
/**
 * PAYMENTS PAGE
 * Location: payments.php
 * 
 * PURPOSE: Display all payment transactions
 * FLOW: Database Connection → Get Payments → Display Table
 */

$page_title = "Payment Records";
require_once 'config/database.php';
require_once 'includes/header.php';

$search = isset($_GET['search']) ? clean($_GET['search']) : '';

$sql = "SELECT * FROM vw_payments_detailed WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (receipt_number LIKE ? OR ticket_number LIKE ? OR violator_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$sql .= " ORDER BY payment_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$total_payments = count($payments);
$total_amount = array_sum(array_column($payments, 'amount_paid'));
?>

<div class="container">
    <div class="page-header">
        <h1>Payment Records</h1>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 2rem;">
        <div class="stat-card revenue">
            <h3>Total Payments</h3>
            <div class="number"><?= number_format($total_payments) ?></div>
        </div>
        <div class="stat-card paid">
            <h3>Total Amount Collected</h3>
            <div class="number">₱<?= number_format($total_amount, 2) ?></div>
        </div>
        <div class="stat-card">
            <h3>Average Payment</h3>
            <div class="number">
                ₱<?= $total_payments > 0 ? number_format($total_amount / $total_payments, 2) : '0.00' ?>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="card">
        <form method="GET" action="payments.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Search Payments</label>
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by receipt #, ticket #, or violator name..." 
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <a href="payments.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <h2>Payment Transactions (<?= count($payments) ?> found)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Ticket #</th>
                        <th>Violator Name</th>
                        <th>Violation</th>
                        <th>Amount Paid</th>
                        <th>Payment Method</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <h3>No payments found</h3>
                            <p>No payment records match your search</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($payments as $payment): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($payment['receipt_number']) ?></strong></td>
                            <td>
                                <a href="view_ticket.php?id=<?= $payment['id'] ?>" style="color: #3498db; text-decoration: none;">
                                    <?= htmlspecialchars($payment['ticket_number']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($payment['violator_name']) ?></td>
                            <td><?= htmlspecialchars($payment['violation_name']) ?></td>
                            <td><strong style="color: #27ae60;">₱<?= number_format($payment['amount_paid'], 2) ?></strong></td>
                            <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                            <td><?= date('M d, Y g:i A', strtotime($payment['payment_date'])) ?></td>
                            <td><span class="badge <?= strtolower($payment['payment_status']) ?>"><?= $payment['payment_status'] ?></span></td>
                            <td><?= htmlspecialchars($payment['processed_by']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>