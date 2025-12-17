<?php
$page_title = "Dashboard";

// 🟢 Load database/session FIRST.
require_once 'config/database.php';

// GET ALL DATA IN SINGLE QUERIES
$kpi_data = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE payment_status = 'COMPLETED') as total_revenue,
        (SELECT COALESCE(SUM(total_amount), 0) FROM tickets WHERE status IN ('UNPAID', 'OVERDUE')) as outstanding_balance,
        (SELECT COUNT(*) FROM tickets) as total_tickets,
        (SELECT COUNT(*) FROM tickets WHERE MONTH(date_issued) = MONTH(CURRENT_DATE) AND YEAR(date_issued) = YEAR(CURRENT_DATE)) as monthly_tickets,
        (SELECT COUNT(*) FROM tickets WHERE DATE(date_issued) = CURRENT_DATE) as today_tickets,
        (SELECT AVG(DATEDIFF(p.payment_date, t.date_issued)) FROM payments p JOIN tickets t ON p.ticket_id = t.id WHERE p.payment_status = 'COMPLETED') as avg_payment_days
")->fetch();

$status_data = $pdo->query("
    SELECT status, COUNT(*) as count, SUM(total_amount) as total_amount 
    FROM tickets GROUP BY status
")->fetchAll();

$status_counts = array_column($status_data, 'count', 'status');

// 🟢 Use null coalescing operator (??) to safely access keys, preventing "Undefined array key" warnings
$paid_count = $status_counts['PAID'] ?? 0;
$total_tickets = $kpi_data['total_tickets'];
$overdue_count = $status_counts['OVERDUE'] ?? 0;
$unpaid_count = $status_counts['UNPAID'] ?? 0;

$collection_rate = $total_tickets > 0 ? ($paid_count / $total_tickets * 100) : 0;

$top_violations = $pdo->query("
    SELECT vt.violation_name, COUNT(t.id) as violation_count, SUM(t.total_amount) as total_revenue
    FROM violation_types vt LEFT JOIN tickets t ON vt.id = t.violation_type
    GROUP BY vt.id ORDER BY violation_count DESC LIMIT 5
")->fetchAll();

$recent_tickets = $pdo->query("SELECT * FROM vw_tickets_detailed ORDER BY date_issued DESC LIMIT 10")->fetchAll();
$payment_trends = $pdo->query("
    SELECT DATE(payment_date) as payment_day, COUNT(*) as payment_count, SUM(amount_paid) as daily_revenue
    FROM payments WHERE payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) AND payment_status = 'COMPLETED'
    GROUP BY DATE(payment_date) ORDER BY payment_day DESC
")->fetchAll();

// 🟢 Include header AFTER all processing
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Dashboard Overview</h1>
            <p style="color: var(--text-light); margin-top: 0.5rem;"><?= date('l, F d, Y') ?> | <?= date('g:i A') ?></p>
        </div>
    </div>

    <?php if ($overdue_count > 0): ?>
    <div class="alert alert-warning"> <strong>Attention:</strong> You have <?= $overdue_count ?> overdue ticket(s)!</div>
    <?php endif; ?>

    <div class="card">
        <h2> Financial Performance</h2>
        <div class="stats-grid">
            <div class="stat-card revenue"><h3>Total Revenue</h3><div class="number">₱<?= number_format($kpi_data['total_revenue'], 2) ?></div><small><?= $paid_count ?> paid tickets</small></div>
            <div class="stat-card unpaid"><h3>Outstanding Balance</h3><div class="number">₱<?= number_format($kpi_data['outstanding_balance'], 2) ?></div><small><?= ($unpaid_count + $overdue_count) ?> tickets</small></div>
            <div class="stat-card paid"><h3>Collection Rate</h3><div class="number"><?= number_format($collection_rate, 1) ?>%</div><small><?= $paid_count ?> of <?= $total_tickets ?> paid</small></div>
            <div class="stat-card"><h3>Avg. Payment Time</h3><div class="number"><?= round($kpi_data['avg_payment_days']) ?> days</div><small>From issue to payment</small></div>
        </div>
    </div>

    <div class="card">
        <h2> Operational Metrics</h2>
        <div class="stats-grid">
            <div class="stat-card"><h3>Total Tickets</h3><div class="number"><?= number_format($total_tickets) ?></div><small>All time</small></div>
            <div class="stat-card"><h3>This Month</h3><div class="number"><?= number_format($kpi_data['monthly_tickets']) ?></div><small><?= date('F Y') ?></small></div>
            <div class="stat-card"><h3>Today</h3><div class="number"><?= number_format($kpi_data['today_tickets']) ?></div><small><?= date('F d') ?></small></div>
            <div class="stat-card unpaid"><h3>Overdue Tickets</h3><div class="number"><?= number_format($overdue_count) ?></div><small>Requires attention</small></div>
        </div>
    </div>

    <div class="card">
        <h2> Ticket Status Distribution</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <?php 
            // 🟢 Ensure all possible statuses are present for display (if missing from DB results)
            $default_statuses = ['PAID' => 0, 'UNPAID' => 0, 'OVERDUE' => 0, 'PENDING' => 0];
            $display_status_data = [];
            foreach ($default_statuses as $status_name => $default_count) {
                // Find existing data or use default
                $found = false;
                foreach ($status_data as $data) {
                    if ($data['status'] === $status_name) {
                        $display_status_data[] = $data;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $display_status_data[] = ['status' => $status_name, 'count' => 0, 'total_amount' => 0.00];
                }
            }
            
            foreach ($display_status_data as $status): ?>
            <div style="padding: 1.5rem; background: var(--bg-light); border-radius: 8px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: var(--status-<?= strtolower($status['status']) ?>);"><?= number_format($status['count']) ?></div>
                <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 0.5rem; text-transform: uppercase;"><?= $status['status'] ?></div>
                <div style="font-size: 1rem; color: var(--text-medium); margin-top: 0.5rem; font-weight: 600;">₱<?= number_format($status['total_amount'], 2) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2 style="display: flex; align-items: center; justify-content: space-between;"><span> Top 5 Violations</span><a href="reports.php" class="btn btn-secondary btn-small">View Report</a></h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Violation Type</th><th>Count</th><th>Total Revenue</th><th>Percentage</th></tr></thead>
                <tbody>
                    <?php if (empty($top_violations)): ?>
                    <tr><td colspan="4" class="empty-state">No violation data available.</td></tr>
                    <?php else: foreach ($top_violations as $violation): 
                    $percentage = $total_tickets > 0 ? ($violation['violation_count'] / $total_tickets * 100) : 0;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($violation['violation_name']) ?></strong></td>
                        <td><?= number_format($violation['violation_count']) ?></td>
                        <td><strong style="color: var(--status-paid);">₱<?= number_format($violation['total_revenue'], 2) ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="flex: 1; background: var(--bg-light); height: 8px; border-radius: 4px;"><div style="background: var(--primary-main); height: 100%; width: <?= $percentage ?>%;"></div></div>
                                <span style="font-weight: 600; min-width: 45px;"><?= number_format($percentage, 1) ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 style="display: flex; align-items: center; justify-content: space-between;"><span> Recent Tickets</span><a href="tickets.php" class="btn btn-secondary btn-small">View All</a></h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Ticket #</th><th>Violator</th><th>Violation</th><th>Amount</th><th>Date Issued</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($recent_tickets)): ?>
                    <tr><td colspan="6" class="empty-state"><h3>No tickets yet</h3><p>Start by adding your first ticket</p></td></tr>
                    <?php else: foreach ($recent_tickets as $ticket): ?>
                    <tr>
                        <td><a href="view_ticket.php?id=<?= $ticket['id'] ?>" style="color: var(--primary-main); text-decoration: none; font-weight: bold;"><?= htmlspecialchars($ticket['ticket_number']) ?></a></td>
                        <td><?= htmlspecialchars($ticket['violator_name']) ?></td>
                        <td><?= htmlspecialchars($ticket['violation_name']) ?></td>
                        <td><strong>₱<?= number_format($ticket['total_amount'], 2) ?></strong></td>
                        <td><?= date('M d, Y g:i A', strtotime($ticket['date_issued'])) ?></td>
                        <td><span class="badge <?= strtolower($ticket['status']) ?>"><?= $ticket['status'] ?></span></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($payment_trends)): ?>
    <div class="card">
        <h2> Payment Trends (Last 7 Days)</h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Date</th><th>Payments</th><th>Revenue</th></tr></thead>
                <tbody>
                    <?php foreach ($payment_trends as $trend): ?>
                    <tr>
                        <td><strong><?= date('l, M d', strtotime($trend['payment_day'])) ?></strong></td>
                        <td><?= $trend['payment_count'] ?> payment(s)</td>
                        <td><strong style="color: var(--status-paid);">₱<?= number_format($trend['daily_revenue'], 2) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>setTimeout(() => location.reload(), 300000);</script>

<?php require_once 'includes/footer.php'; ?>