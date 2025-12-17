<?php
/**
 * PRINT TICKET/REPORT VIEW
 * Location: print_ticket.php
 * PURPOSE: Renders a printable version of a single ticket receipt or a date-range report.
 */
require_once 'config/database.php';

// --- 1. SETUP & INPUT HANDLING ---

// Get URL parameters, using null coalescing (??) for safe access
$ticket_id = (int)($_GET['id'] ?? 0);
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$print_type = $ticket_id ? 'single' : ($_GET['report'] ?? null); // Check for ?report=1 for multiple view
$do_print = isset($_GET['print']); // New parameter to trigger immediate print

$page_title = "";

if ($print_type === 'single') {
    // --- Single Ticket Processing ---
    $stmt = $pdo->prepare("SELECT * FROM vw_tickets_detailed WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) die("Ticket not found");
    
    $payment = null;
    if ($ticket['status'] == 'PAID') {
        // Assuming vw_payments_detailed includes payment amount, date, and method_name
        $stmt_payment = $pdo->prepare("SELECT * FROM vw_payments_detailed WHERE ticket_number = ?");
        $stmt_payment->execute([$ticket['ticket_number']]);
        $payment = $stmt_payment->fetch();
    }
    
    // Calculate current amount with late fees
    $current_amount = $ticket['total_amount'];
    $late_fee = 0;
    
    // SAFE CHECK: Ensure days_overdue key exists before checking value
    if (($ticket['days_overdue'] ?? 0) > 0 && $ticket['status'] !== 'PAID') {
        // Assuming 3% per day calculation logic from add_payment.php
        $late_fee = $ticket['total_amount'] * 0.03 * $ticket['days_overdue']; 
        $current_amount = $ticket['total_amount'] + $late_fee;
    }
    
    $page_title = "Print - " . htmlspecialchars($ticket['ticket_number']);

} else {
    // --- Multiple Ticket Report Processing (Triggered by lack of ID or ?report=1) ---
    $stmt_tickets = $pdo->prepare("SELECT * FROM vw_tickets_detailed WHERE date_issued BETWEEN ? AND ? ORDER BY date_issued DESC");
    $stmt_tickets->execute([$start_date, $end_date]);
    $tickets = $stmt_tickets->fetchAll();

    $page_title = "Print Tickets - " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date));
    $print_type = 'multiple'; // Force type to multiple if ID is missing
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20mm; background: white; }
        
        /* Main Container for single ticket print */
        .ticket-container { 
            max-width: 210mm; 
            margin: 0 auto; 
            border: 3px solid #1a237e; 
            padding: 15mm; 
            margin-bottom: 10mm;
        }
        
        /* Specific Styles */
        .header { text-align: center; border-bottom: 2px solid #1a237e; padding-bottom: 10mm; margin-bottom: 10mm; }
        .header h1 { color: #1a237e; font-size: 28pt; margin-bottom: 5mm; }
        .ticket-number { background: #1a237e; color: white; padding: 5mm; text-align: center; font-size: 18pt; font-weight: bold; margin-bottom: 10mm; }
        .status-badge { display: inline-block; padding: 3mm 8mm; border-radius: 5mm; font-weight: bold; font-size: 12pt; margin-left: 5mm; }
        .status-unpaid { background: #ffebee; color: #c62828; border: 2px solid #c62828; }
        .status-paid { background: #e8f5e9; color: #2e7d32; border: 2px solid #2e7d32; }
        .status-overdue { background: #fff3e0; color: #e65100; border: 2px solid #e65100; }
        .section { margin-bottom: 8mm; }
        .section-title { background: #f5f5f5; padding: 3mm; font-weight: bold; color: #1a237e; margin-bottom: 3mm; }
        .info-grid { display: grid; grid-template-columns: 40% 60%; gap: 3mm; }
        .info-label { font-weight: bold; color: #666; }
        .amount-box { background: #fff3e0; border: 3px solid #e65100; padding: 5mm; margin: 5mm 0; text-align: center; }
        .amount-value { font-size: 24pt; font-weight: bold; color: #e65100; }
        
        /* Payment details box */
        .payment-info { 
            background: #e8f5e9; 
            border: 3px solid #2e7d32; 
            padding: 5mm; 
            margin: 5mm 0; 
        }
        
        .footer { margin-top: 10mm; padding-top: 5mm; border-top: 2px dashed #999; text-align: center; color: #666; font-size: 9pt; }
        
        /* Table styles for multiple ticket report */
        .tickets-table { width: 100%; border-collapse: collapse; margin-top: 5mm; }
        .tickets-table th { background: #1a237e; color: white; padding: 3mm; text-align: left; font-size: 10pt; }
        .tickets-table td { padding: 2mm; border-bottom: 1px solid #ddd; font-size: 9pt; }
        
        /* Print controls (for screen only) */
        .print-controls { background: #f8f9fa; padding: 10mm; margin-bottom: 10mm; border: 2px solid #1a237e; border-radius: 5mm; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 5mm; margin-bottom: 3mm; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 2mm; color: #1a237e; }
        .form-group input, .form-group select { padding: 3mm; border: 2px solid #ddd; border-radius: 3mm; font-size: 11pt; }
        
        /* --- PRINT MEDIA FIXES (The key to fixing the breaks) --- */
        @media print { 
            body { padding: 0; } 
            .no-print { display: none !important; } 
            
            /* FIX: Prevents the entire receipt from breaking across pages */
            .ticket-container { 
                border: none; 
                page-break-inside: avoid !important;
            } 
            
            /* FIX: Ensures the specific payment info box stays intact */
            .payment-info {
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>
    
    <div class="no-print print-controls">
        <h2 style="color: #1a237e; margin-bottom: 5mm; text-align: center;">📄 PRINT TICKETS</h2>
        
        <?php if ($print_type === 'single'): ?>
        <div style="text-align: center; margin-bottom: 5mm;">
            <p><strong>Printing Single Ticket:</strong> <?= htmlspecialchars($ticket['ticket_number']) ?></p>
        </div>
        <div style="text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #2e7d32; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1em;">
                🖨️ Print Receipt
            </button>
        </div>
        
        <?php else: // Report Printing Controls ?>
        <form method="GET" style="margin-bottom: 5mm;">
            <input type="hidden" name="report" value="1"> 
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required></div>
                <div class="form-group"><label>End Date</label><input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required></div>
                <div class="form-group"><label>Quick Select</label>
                    <select onchange="if(this.value) window.location.href='print_ticket.php?report=1&start_date='+this.value+'&end_date=<?= date('Y-m-d') ?>'">
                        <option value="">Select Month</option>
                        <option value="<?= date('Y-m-01') ?>">Current Month (<?= date('M Y') ?>)</option>
                        <option value="<?= date('Y-m-01', strtotime('-1 month')) ?>">Last Month (<?= date('M Y', strtotime('-1 month')) ?>)</option>
                    </select>
                </div>
            </div>
            <div style="text-align: center;">
                <button type="submit" style="padding: 10px 20px; background: #1a237e; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1em;">
                    Update Report
                </button>
                <button type="button" onclick="window.location.href='print_ticket.php?report=1&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>&print=1'" style="padding: 10px 20px; background: #2e7d32; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1em; margin-left: 10px;">
                    🖨️ Print Report
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    
    <?php if ($print_type === 'single' && $ticket): ?>
    
    <div class="ticket-container">
        <div class="header">
            <h1>Traffic Ticket Receipt</h1>
            <p style="color: #666; font-size: 10pt;">Generated on: <?= date('M d, Y h:i A') ?></p>
            
            <?php if ($ticket['status'] == 'PAID'): ?>
                <h2 style="color: #2e7d32; margin-top: 10mm; font-size: 18pt;">✅ PAYMENT RECEIVED</h2>
            <?php else: ?>
                <h2 style="color: #c62828; margin-top: 10mm; font-size: 18pt;">⚠️ BILLING STATEMENT</h2>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2 class="section-title">Ticket Information</h2>
            <div class="info-grid">
                <div class="info-label">Ticket No.:</div><div><?= htmlspecialchars($ticket['ticket_number']) ?></div>
                <div class="info-label">Date Issued:</div><div><?= date('M d, Y', strtotime($ticket['date_issued'])) ?></div>
                <div class="info-label">Due Date:</div><div><?= date('M d, Y', strtotime($ticket['due_date'])) ?></div>
                <div class="info-label">Status:</div><div><span class="status-badge status-<?= strtolower($ticket['status']) ?>"><?= htmlspecialchars($ticket['status']) ?></span></div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Violator & Vehicle</h2>
            <div class="info-grid">
                <div class="info-label">Name:</div><div><?= htmlspecialchars($ticket['violator_name']) ?></div>
                <div class="info-label">License #:</div><div><?= htmlspecialchars($ticket['license_number']) ?></div>
                <div class="info-label">Vehicle Plate:</div><div><?= htmlspecialchars($ticket['plate_number']) ?></div>
                <div class="info-label">Officer:</div><div><?= htmlspecialchars($ticket['officer_name'] ?? 'N/A') ?> (<?= htmlspecialchars($ticket['officer_badge'] ?? 'N/A') ?>)</div>
            </div>
        </div>
        
        <div class="section">
            <h2 class="section-title">Violation</h2>
            <div class="info-grid">
                <div class="info-label">Violation:</div><div><?= htmlspecialchars($ticket['violation_name'] ?? 'N/A') ?></div>
                <div class="info-label">Ordinance Code:</div><div><?= htmlspecialchars($ticket['ordinance_code'] ?? 'N/A') ?></div>
                <div class="info-label">Severity:</div><div><?= htmlspecialchars($ticket['severity'] ?? 'N/A') ?></div>
            </div>
        </div>
        
        <div class="section">
            <h2 class="section-title">Amount Due</h2>
            <div class="info-grid">
                <div class="info-label">Fine Amount:</div><div>₱<?= number_format($ticket['total_amount'], 2) ?></div>
                <?php if ($late_fee > 0): ?>
                    <div class="info-label" style="color: #e65100;">Late Fee (<?= $ticket['days_overdue'] ?? 0 ?> days):</div><div style="color: #e65100;">+ ₱<?= number_format($late_fee, 2) ?></div>
                <?php endif; ?>
            </div>
            <div class="amount-box">
                <p style="font-size: 14pt; margin-bottom: 3mm;">TOTAL PAYABLE</p>
                <p class="amount-value">₱<?= number_format($current_amount, 2) ?></p>
            </div>
        </div>
        
        <?php if ($payment): ?>
        <div class="section payment-info">
            <h2 class="section-title" style="background: none; color: #2e7d32;">Payment Received</h2>
            <div class="info-grid">
                <div class="info-label">Receipt Number:</div><div style="font-weight: bold;"><?= htmlspecialchars($payment['receipt_number'] ?? 'N/A') ?></div>
                <div class="info-label">Amount Paid:</div><div style="font-weight: bold;">₱<?= number_format($payment['amount_paid'] ?? 0, 2) ?></div>
                <div class="info-label">Payment Date:</div><div><?= date('M d, Y h:i A', strtotime($payment['payment_date'] ?? 'N/A')) ?></div>
                <div class="info-label">Method:</div><div><?= htmlspecialchars($payment['method_name'] ?? 'N/A') ?></div>
                <div class="info-label">Processed By:</div><div><?= htmlspecialchars($payment['processed_by'] ?? 'N/A') ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>This is an official document. Fraudulent alteration of this receipt is subject to legal action.</p>
            <p>Printed: <?= date('M d, Y h:i A') ?></p>
        </div>
    </div>
    
    <?php elseif ($print_type === 'multiple'): ?>
    
    <div style="max-width: 210mm; margin: 0 auto; padding: 10mm;">
        <div class="header" style="border-bottom: 2px solid #999; margin-bottom: 10mm;">
            <h1 style="font-size: 24pt;">Ticket Collection Report</h1>
            <p style="color: #666; font-size: 12pt;">
                Date Range: <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?>
            </p>
            <p style="color: #666; font-size: 10pt; margin-top: 5mm;">
                Report Generated: <?= date('M d, Y h:i A') ?>
            </p>
        </div>
        
        <?php if (!empty($tickets)): ?>
        <div class="table-container">
            <table class="tickets-table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Violator</th>
                        <th>Plate No.</th>
                        <th>Violation</th>
                        <th>Date Issued</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_fine = 0;
                    $total_paid_fine = 0; // Renamed for clarity
                    foreach ($tickets as $t): 
                        $total_fine += $t['total_amount'];
                        if ($t['status'] == 'PAID') {
                            $total_paid_fine += $t['total_amount']; 
                        }
                    ?>
                    <tr style="page-break-inside: avoid;">
                        <td><?= htmlspecialchars($t['ticket_number']) ?></td>
                        <td><?= htmlspecialchars($t['violator_name']) ?></td>
                        <td><?= htmlspecialchars($t['plate_number']) ?></td>
                        <td><?= htmlspecialchars($t['violation_name']) ?></td>
                        <td><?= date('Y-m-d', strtotime($t['date_issued'])) ?></td>
                        <td>₱<?= number_format($t['total_amount'], 2) ?></td>
                        <td><span class="status-badge status-<?= strtolower($t['status']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 15mm; border-top: 2px solid #333; padding-top: 10mm; text-align: right;">
            <p style="font-size: 14pt; font-weight: bold; color: #1a237e;">TOTAL FINES ISSUED: ₱<?= number_format($total_fine, 2) ?></p>
            <p style="font-size: 12pt; color: #2e7d32;">Total Fines for Paid Tickets: ₱<?= number_format($total_paid_fine, 2) ?></p>
        </div>
        
        <?php else: ?>
            <p style="text-align: center; color: #999; margin-top: 20mm;">No tickets found in the selected date range (<?= $start_date ?> to <?= $end_date ?>).</p>
        <?php endif; ?>

        <div class="footer" style="margin-top: 20mm; text-align: left;">
            <p>End of Report.</p>
        </div>
    </div>
    
    <?php endif; ?>

    <script>
    // Global function to trigger print if the 'print' parameter is present
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        // Only call window.print() if it's a single ticket or if the '?print=1' parameter is set for reports.
        if (urlParams.has('id') || urlParams.has('print')) {
            window.print();
        }
    });
    </script>

</body>
</html>