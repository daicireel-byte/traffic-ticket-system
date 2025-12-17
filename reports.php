<?php
$page_title = "Reports & Analytics";
require_once 'config/database.php';
require_once 'includes/header.php';

// Date Range Filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Financial Summary
$stmt_financial = $pdo->prepare("
    SELECT COUNT(*) as total_tickets,
    SUM(CASE WHEN status = 'PAID' THEN total_amount ELSE 0 END) as total_collected,
    SUM(CASE WHEN status IN ('UNPAID', 'OVERDUE') THEN total_amount ELSE 0 END) as outstanding_balance
    FROM tickets WHERE date_issued BETWEEN ? AND ?
");
$stmt_financial->execute([$start_date, $end_date]);
$financial = $stmt_financial->fetch();

$paid_count = $pdo->query("SELECT COUNT(*) as paid FROM tickets WHERE status = 'PAID' AND date_issued BETWEEN '$start_date' AND '$end_date'")->fetch()['paid'];
$collection_rate = $financial['total_tickets'] > 0 ? ($paid_count / $financial['total_tickets'] * 100) : 0;

// Status Report
$stmt_status = $pdo->prepare("
    SELECT status, COUNT(*) as count, SUM(total_amount) as total_amount,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tickets WHERE date_issued BETWEEN ? AND ?), 2) as percentage
    FROM tickets WHERE date_issued BETWEEN ? AND ? GROUP BY status ORDER BY count DESC
");
$stmt_status->execute([$start_date, $end_date, $start_date, $end_date]);
$status_report = $stmt_status->fetchAll();

// Violations Report
$stmt_violations = $pdo->prepare("
    SELECT vt.ordinance_code, vt.violation_name, vt.severity_level, COUNT(t.id) as ticket_count,
    SUM(CASE WHEN t.status = 'PAID' THEN t.total_amount ELSE 0 END) as revenue_collected,
    SUM(t.total_amount) as total_potential
    FROM violation_types vt LEFT JOIN tickets t ON vt.id = t.violation_type AND t.date_issued BETWEEN ? AND ?
    GROUP BY vt.id ORDER BY ticket_count DESC
");
$stmt_violations->execute([$start_date, $end_date]);
$violations_report = $stmt_violations->fetchAll();

// Officer Report
$stmt_officer = $pdo->prepare("
    SELECT o.badge_number, o.full_name as officer_name, o.department, COUNT(t.id) as tickets_issued,
    SUM(CASE WHEN t.status = 'PAID' THEN t.total_amount ELSE 0 END) as amount_collected,
    ROUND(SUM(CASE WHEN t.status = 'PAID' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(t.id), 0), 2) as collection_rate
    FROM officers o LEFT JOIN tickets t ON o.id = t.officer_id AND t.date_issued BETWEEN ? AND ?
    GROUP BY o.id HAVING tickets_issued > 0 ORDER BY tickets_issued DESC
");
$stmt_officer->execute([$start_date, $end_date]);
$officer_report = $stmt_officer->fetchAll();

// Top Violators
$stmt_violators = $pdo->prepare("
    SELECT v.license_number, v.full_name as violator_name, COUNT(t.id) as total_violations,
    SUM(CASE WHEN t.status IN ('UNPAID', 'OVERDUE') THEN t.total_amount ELSE 0 END) as outstanding_balance,
    MAX(t.date_issued) as last_violation_date FROM violators v
    JOIN tickets t ON v.id = t.violator_id AND t.date_issued BETWEEN ? AND ?
    GROUP BY v.id HAVING total_violations >= 2 ORDER BY total_violations DESC LIMIT 10
");
$stmt_violators->execute([$start_date, $end_date]);
$top_violators = $stmt_violators->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <h1>Reports & Analytics</h1>
        <div>
            <a href="print_ticket.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="btn btn-success">🖨️ Print Report</a>
            <button onclick="window.print()" class="btn btn-primary">📄 Print Page</button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card">
        <form method="GET">
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?= $start_date ?>" required></div>
                <div class="form-group"><label>End Date</label><input type="date" name="end_date" value="<?= $end_date ?>" required max="<?= date('Y-m-d') ?>"></div>
                <div class="form-group" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary"> Generate</button>
                    <a href="print_ticket.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" target="_blank" class="btn btn-success">🖨️ Print Report</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Financial Summary -->
    <div class="card">
        <h2> Financial Summary</h2>
        <p style="color: var(--text-light); margin-bottom: 1.5rem;"><?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?></p>
        <div class="stats-grid">
            <div class="stat-card"><h3>Total Tickets</h3><div class="number"><?= number_format($financial['total_tickets']) ?></div></div>
            <div class="stat-card paid"><h3>Total Collected</h3><div class="number">₱<?= number_format($financial['total_collected'], 2) ?></div></div>
            <div class="stat-card unpaid"><h3>Outstanding</h3><div class="number">₱<?= number_format($financial['outstanding_balance'], 2) ?></div></div>
            <div class="stat-card revenue"><h3>Collection Rate</h3><div class="number"><?= number_format($collection_rate, 1) ?>%</div></div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="card">
        <h2> Tickets by Status</h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Status</th><th>Count</th><th>Amount</th><th>%</th></tr></thead>
                <tbody>
                    <?php foreach ($status_report as $status): ?>
                    <tr>
                        <td><span class="badge <?= strtolower($status['status']) ?>"><?= $status['status'] ?></span></td>
                        <td><strong><?= number_format($status['count']) ?></strong></td>
                        <td><strong>₱<?= number_format($status['total_amount'], 2) ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="flex: 1; background: var(--bg-light); height: 8px; border-radius: 4px;">
                                    <div style="background: var(--primary-main); height: 100%; width: <?= $status['percentage'] ?>%;"></div>
                                </div>
                                <span style="font-weight: 600;"><?= $status['percentage'] ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Violations Report -->
    <div class="card">
        <h2> Violations Report</h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Code</th><th>Violation</th><th>Severity</th><th>Count</th><th>Collected</th><th>Potential</th><th>%</th></tr></thead>
                <tbody>
                    <?php foreach ($violations_report as $v): 
                    $coll_pct = $v['total_potential'] > 0 ? ($v['revenue_collected'] / $v['total_potential'] * 100) : 0;
                    ?>
                    <tr>
                        <td><strong><?= $v['ordinance_code'] ?></strong></td>
                        <td><?= $v['violation_name'] ?></td>
                        <td><span class="badge severity-<?= strtolower($v['severity_level']) ?>"><?= $v['severity_level'] ?></span></td>
                        <td><?= number_format($v['ticket_count']) ?></td>
                        <td><strong style="color: var(--status-paid);">₱<?= number_format($v['revenue_collected'], 2) ?></strong></td>
                        <td>₱<?= number_format($v['total_potential'], 2) ?></td>
                        <td><?= number_format($coll_pct, 1) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Officer Performance -->
    <div class="card">
        <h2> Officer Performance</h2>
        <div class="table-container">
            <table>
                <thead><tr><th>Badge</th><th>Officer</th><th>Dept</th><th>Tickets</th><th>Collected</th><th>Rate</th></tr></thead>
                <tbody>
                    <?php if (empty($officer_report)): ?>
                    <tr><td colspan="6" class="empty-state">No tickets in selected period</td></tr>
                    <?php else: foreach ($officer_report as $o): ?>
                    <tr>
                        <td><strong><?= $o['badge_number'] ?></strong></td>
                        <td><?= $o['officer_name'] ?></td>
                        <td><?= $o['department'] ?></td>
                        <td><?= number_format($o['tickets_issued']) ?></td>
                        <td><strong style="color: var(--status-paid);">₱<?= number_format($o['amount_collected'], 2) ?></strong></td>
                        <td><?= number_format($o['collection_rate'], 1) ?>%</td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Violators -->
    <?php if (!empty($top_violators)): ?>
    <div class="card">
        <h2> Top Violators</h2>
        <div class="table-container">
            <table>
                <thead><tr><th>License</th><th>Name</th><th>Violations</th><th>Outstanding</th><th>Last</th></tr></thead>
                <tbody>
                    <?php foreach ($top_violators as $v): ?>
                    <tr>
                        <td><strong><?= $v['license_number'] ?></strong></td>
                        <td><?= $v['violator_name'] ?></td>
                        <td><span class="badge unpaid"><?= $v['total_violations'] ?></span></td>
                        <td><strong style="color: var(--status-unpaid);">₱<?= number_format($v['outstanding_balance'], 2) ?></strong></td>
                        <td><?= date('M d, Y', strtotime($v['last_violation_date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="card" style="text-align: center; color: var(--text-light);">
        <p><strong>Generated:</strong> <?= date('F d, Y g:i A') ?></p>
        <p style="margin-top: 0.5rem;">City Ordinance Violation System</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>