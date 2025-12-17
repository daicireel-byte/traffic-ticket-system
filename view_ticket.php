<?php
require_once 'config/database.php';

if (!$pdo) {
    die("Database connection failed");
}

$ticket_id = (int)($_GET['id'] ?? 0);
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$print_type = $ticket_id ? 'single' : 'multiple';

if ($print_type === 'single') {
    // Added proper error handling for prepare
    $stmt = $pdo->prepare("SELECT * FROM vw_tickets_detailed WHERE id = ?");
    if (!$stmt) {
        die("Database error: " . implode(", ", $pdo->errorInfo()));
    }
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) die("Ticket not found");
    
    $payment = null;
    if ($ticket['status'] == 'PAID') {
        // FIXED: Added proper error handling for prepare
        $stmt_payment = $pdo->prepare("SELECT * FROM vw_payments_detailed WHERE ticket_number = ?");
        if ($stmt_payment) {
            $stmt_payment->execute([$ticket['ticket_number']]);
            $payment = $stmt_payment->fetch();
        }
    }
    
    $current_amount = $ticket['total_amount'];
    if ($ticket['days_overdue'] > 0) {
        $current_amount += $ticket['total_amount'] * 0.03 * $ticket['days_overdue'];
    }
} else {
    // FIXED: Added proper error handling for prepare
    $stmt_tickets = $pdo->prepare("SELECT * FROM vw_tickets_detailed WHERE date_issued BETWEEN ? AND ? ORDER BY date_issued DESC");
    if ($stmt_tickets) {
        $stmt_tickets->execute([$start_date, $end_date]);
        $tickets = $stmt_tickets->fetchAll();
    } else {
        $tickets = [];
        die("Database error: Unable to prepare tickets query");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $print_type === 'single' ? "Print - {$ticket['ticket_number']}" : "Print Tickets - " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date)) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; padding: 10mm; background: white; font-size: 11pt; }
        .ticket-container { width: 210mm; min-height: 297mm; margin: 0 auto; border: 3px solid #1a237e; padding: 15mm; margin-bottom: 10mm; position: relative; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 8mm; margin-bottom: 8mm; }
        .header h1 { color: #1a237e; font-size: 24pt; margin-bottom: 3mm; }
        .header h2 { font-size: 14pt; color: #666; }
        .ticket-number { background: #1a237e; color: white; padding: 4mm; text-align: center; font-size: 16pt; font-weight: bold; margin-bottom: 8mm; }
        .status-badge { display: inline-block; padding: 2mm 6mm; border-radius: 4mm; font-weight: bold; font-size: 10pt; margin-left: 4mm; }
        .status-unpaid { background: #ffebee; color: #c62828; border: 2px solid #c62828; }
        .status-paid { background: #e8f5e9; color: #2e7d32; border: 2px solid #2e7d32; }
        .status-overdue { background: #fff3e0; color: #e65100; border: 2px solid #e65100; }
        .section { margin-bottom: 6mm; page-break-inside: avoid; }
        .section-title { background: #f5f5f5; padding: 2mm 3mm; font-weight: bold; color: #1a237e; margin-bottom: 2mm; font-size: 12pt; }
        .info-grid { display: grid; grid-template-columns: 35% 65%; gap: 2mm; margin: 2mm 0; }
        .info-label { font-weight: bold; color: #555; padding: 1mm 0; }
        .info-value { padding: 1mm 0; }
        .amount-box { background: #fff3e0; border: 3px solid #e65100; padding: 4mm; margin: 4mm 0; text-align: center; }
        .amount-value { font-size: 20pt; font-weight: bold; color: #e65100; }
        .payment-info { background: #e8f5e9; border: 3px solid #2e7d32; padding: 4mm; margin: 4mm 0; }
        .footer { margin-top: 8mm; padding-top: 4mm; border-top: 2px dashed #999; text-align: center; color: #666; font-size: 9pt; position: absolute; bottom: 15mm; left: 15mm; right: 15mm; }
        .tickets-table { width: 100%; border-collapse: collapse; margin-top: 4mm; }
        .tickets-table th { background: #1a237e; color: white; padding: 2mm; text-align: left; font-size: 9pt; }
        .tickets-table td { padding: 1.5mm; border-bottom: 1px solid #ddd; font-size: 9pt; }
        .summary-box { background: #f5f5f5; padding: 4mm; margin: 4mm 0; border-radius: 4mm; }
        .print-controls { background: #f8f9fa; padding: 8mm; margin-bottom: 8mm; border: 2px solid #1a237e; border-radius: 4mm; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4mm; margin-bottom: 2mm; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 1mm; color: #1a237e; }
        .form-group input, .form-group select { padding: 2mm; border: 2px solid #ddd; border-radius: 2mm; font-size: 10pt; }
        
        /* FIXED: Better print styles */
        @media print { 
            body { padding: 0; margin: 0; } 
            .no-print { display: none !important; } 
            .ticket-container { border: 3px solid #1a237e; margin: 0; page-break-after: always; }
            .ticket-container:last-child { page-break-after: auto; }
        }
        
        /* FIXED: Ensure proper page breaks */
        .page-break { page-break-before: always; }
        
        /* FIXED: Location and Date section styling */
        .location-date-section { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 5mm; 
            margin-bottom: 6mm;
            padding: 3mm;
            background: #f8f9fa;
            border-radius: 2mm;
        }
        .location-date-group { display: flex; flex-direction: column; }
        .location-date-label { font-weight: bold; color: #1a237e; margin-bottom: 1mm; }
        .location-date-value { font-size: 11pt; }
    </style>
</head>
<body>
    <div class="no-print print-controls">
        <h2 style="color: #1a237e; margin-bottom: 4mm; text-align: center;">📄 PRINT TICKETS</h2>
        
        <?php if ($print_type === 'single'): ?>
        <div style="text-align: center; margin-bottom: 4mm;">
            <p><strong>Printing Single Ticket:</strong> <?= $ticket['ticket_number'] ?></p>
        </div>
        <?php else: ?>
        <form method="GET" style="margin-bottom: 4mm;">
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?= $start_date ?>" required></div>
                <div class="form-group"><label>End Date</label><input type="date" name="end_date" value="<?= $end_date ?>" required></div>
                <div class="form-group"><label>Quick Select</label>
                    <select onchange="if(this.value) window.location.href='print_ticket.php?start_date='+this.value+'&end_date=<?= date('Y-m-d') ?>'">
                        <option value="">Select Month</option>
                        <option value="<?= date('Y-m-01') ?>">Current Month (<?= date('M Y') ?>)</option>
                        <option value="<?= date('Y-m-01', strtotime('-1 month')) ?>">Last Month (<?= date('M Y', strtotime('-1 month')) ?>)</option>
                        <option value="2025-11-01">November 2025</option>
                    </select>
                </div>
            </div>
            <div style="text-align: center; margin-top: 2mm;">
                <button type="submit" style="padding: 6mm 12mm; font-size: 11pt; background: #1a237e; color: white; border: none; border-radius: 2mm; cursor: pointer; margin-right: 2mm;">🔍 Load Tickets</button>
            </div>
        </form>
        <?php endif; ?>
        
        <div style="text-align: center;">
            <button onclick="window.print()" style="padding: 6mm 12mm; font-size: 11pt; background: #2e7d32; color: white; border: none; border-radius: 2mm; cursor: pointer; margin-right: 2mm;">🖨️ Print Now</button>
            <button onclick="window.close()" style="padding: 6mm 12mm; font-size: 11pt; background: #666; color: white; border: none; border-radius: 2mm; cursor: pointer;">✕ Close</button>
        </div>
    </div>

    <?php if ($print_type === 'single'): ?>
    <!-- SINGLE TICKET - FIXED LAYOUT -->
    <div class="ticket-container">
        <div class="header">
            <h1>TRAFFIC VIOLATION TICKET</h1>
            <h2>City Ordinance and Traffic Violation Payment System</h2>
        </div>

        <div class="ticket-number">
            <?= $ticket['ticket_number'] ?>
            <span class="status-badge status-<?= strtolower($ticket['status']) ?>"><?= $ticket['status'] ?></span>
        </div>

        <!-- FIXED: Location and Date Section -->
        <div class="location-date-section">
            <div class="location-date-group">
                <div class="location-date-label">Location:</div>
                <div class="location-date-value"><?= !empty($ticket['location']) ? $ticket['location'] : 'Not specified' ?></div>
            </div>
            <div class="location-date-group">
                <div class="location-date-label">Date Issued:</div>
                <div class="location-date-value"><?= date('F d, Y g:i A', strtotime($ticket['date_issued'])) ?></div>
            </div>
            <div class="location-date-group">
                <div class="location-date-label">Due Date:</div>
                <div class="location-date-value"><?= date('F d, Y', strtotime($ticket['due_date'])) ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">VIOLATOR INFORMATION</div>
            <div class="info-grid">
                <div class="info-label">Full Name:</div><div class="info-value"><?= $ticket['violator_name'] ?></div>
                <div class="info-label">License Number:</div><div class="info-value"><?= $ticket['license_number'] ?></div>
                <div class="info-label">Contact Number:</div><div class="info-value"><?= !empty($ticket['violator_contact']) ? $ticket['violator_contact'] : 'Not provided' ?></div>
                <div class="info-label">Vehicle Plate:</div><div class="info-value"><?= $ticket['plate_number'] ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">VIOLATION DETAILS</div>
            <div class="info-grid">
                <div class="info-label">Ordinance Code:</div><div class="info-value"><?= $ticket['ordinance_code'] ?></div>
                <div class="info-label">Violation Type:</div><div class="info-value"><?= $ticket['violation_name'] ?></div>
                <div class="info-label">Severity Level:</div><div class="info-value"><?= $ticket['severity_level'] ?></div>
                <div class="info-label">Fine Amount:</div><div class="info-value">₱<?= number_format($ticket['total_amount'], 2) ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">ISSUING OFFICER</div>
            <div class="info-grid">
                <div class="info-label">Officer Name:</div><div class="info-value"><?= $ticket['officer_name'] ?></div>
                <div class="info-label">Badge Number:</div><div class="info-value"><?= $ticket['badge_number'] ?></div>
            </div>
        </div>

        <?php if ($ticket['status'] != 'PAID'): ?>
        <div class="amount-box">
            <div style="font-size: 10pt; color: #666; margin-bottom: 2mm;">TOTAL AMOUNT DUE</div>
            <div class="amount-value">₱<?= number_format($current_amount, 2) ?></div>
            <?php if ($ticket['days_overdue'] > 0): ?>
            <p style="color: #e65100; font-weight: bold; margin-top: 2mm;">(Includes late fee: <?= $ticket['days_overdue'] ?> days overdue)</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($payment): ?>
        <div class="payment-info">
            <div style="font-weight: bold; font-size: 12pt; color: #2e7d32; margin-bottom: 2mm;">✓ PAYMENT RECEIVED</div>
            <div class="info-grid">
                <div class="info-label">Receipt Number:</div><div class="info-value"><?= $payment['receipt_number'] ?></div>
                <div class="info-label">Amount Paid:</div><div class="info-value">₱<?= number_format($payment['amount_paid'], 2) ?></div>
                <div class="info-label">Payment Date:</div><div class="info-value"><?= date('F d, Y g:i A', strtotime($payment['payment_date'])) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Printed:</strong> <?= date('F d, Y g:i A') ?></p>
            <p>localhost/mytrafficdeetsystem/print_ticket.php?id=<?= $ticket_id ?></p>
        </div>
    </div>

    <?php else: ?>
    <!-- MULTIPLE TICKETS -->
    <?php if (!empty($tickets)): ?>
        <?php
        $total_tickets = count($tickets);
        $total_amount = array_sum(array_column($tickets, 'total_amount'));
        $paid = count(array_filter($tickets, fn($t) => $t['status'] == 'PAID'));
        $unpaid = count(array_filter($tickets, fn($t) => $t['status'] == 'UNPAID'));
        $overdue = count(array_filter($tickets, fn($t) => $t['status'] == 'OVERDUE'));
        ?>
        
        <div class="ticket-container">
            <div class="header">
                <h1>TICKETS SUMMARY REPORT</h1>
                <h2>Date Range: <?= date('F d, Y', strtotime($start_date)) ?> to <?= date('F d, Y', strtotime($end_date)) ?></h2>
            </div>

            <div class="summary-box">
                <h3 style="color: #1a237e; margin-bottom: 2mm;">QUICK STATISTICS</h3>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2mm; text-align: center;">
                    <div><div style="font-size: 14pt; font-weight: bold; color: #1a237e;"><?= $total_tickets ?></div><div style="font-size: 8pt; color: #666;">Total</div></div>
                    <div><div style="font-size: 14pt; font-weight: bold; color: #2e7d32;"><?= $paid ?></div><div style="font-size: 8pt; color: #666;">Paid</div></div>
                    <div><div style="font-size: 14pt; font-weight: bold; color: #c62828;"><?= $unpaid ?></div><div style="font-size: 8pt; color: #666;">Unpaid</div></div>
                    <div><div style="font-size: 14pt; font-weight: bold; color: #e65100;"><?= $overdue ?></div><div style="font-size: 8pt; color: #666;">Overdue</div></div>
                </div>
                <div style="text-align: center; margin-top: 2mm; padding-top: 2mm; border-top: 1px dashed #ddd;">
                    <strong>Total Amount: ₱<?= number_format($total_amount, 2) ?></strong>
                </div>
            </div>

            <table class="tickets-table">
                <thead>
                    <tr><th>Ticket #</th><th>Violator Name</th><th>Violation</th><th>Date</th><th>Amount</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach($tickets as $t): ?>
                    <tr>
                        <td><strong><?= $t['ticket_number'] ?></strong></td>
                        <td><?= $t['violator_name'] ?></td>
                        <td><?= $t['violation_name'] ?></td>
                        <td><?= date('M d, Y', strtotime($t['date_issued'])) ?></td>
                        <td><strong>₱<?= number_format($t['total_amount'], 2) ?></strong></td>
                        <td><span class="status-badge status-<?= strtolower($t['status']) ?>" style="padding: 1mm 2mm; font-size: 8pt;"><?= $t['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="footer">
                <p>Generated for: <?= date('F d, Y', strtotime($start_date)) ?> to <?= date('F d, Y', strtotime($end_date)) ?></p>
                <p>Total records: <?= $total_tickets ?> tickets</p>
                <p><strong>Printed:</strong> <?= date('F d, Y g:i A') ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="ticket-container">
            <div class="header"><h1>NO TICKETS FOUND</h1></div>
            <div style="text-align: center; padding: 15mm; color: #666;">
                <p>No tickets found for the selected date range.</p>
            </div>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</body>
</html>