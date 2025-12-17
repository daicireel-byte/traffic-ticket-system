<?php
/**
 * PROCESS PAYMENT
 * Location: process/process_payment.php
 * 
 * PURPOSE: Handle payment processing
 * FLOW: Receive Form Data → Validate → Save Payment → Update Ticket Status → Redirect
 */

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get and sanitize form data
$ticket_id = (int)$_POST['ticket_id'];
$payment_method_id = (int)$_POST['payment_method_id']; // Now reads selected method from form
$receipt_number = clean($_POST['receipt_number']);
$amount_paid = (float)$_POST['amount_paid'];
$processed_by = clean($_POST['processed_by']);
$notes = clean($_POST['notes']);


        // Verify ticket exists and is not paid
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND status != 'PAID'");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            throw new Exception("Ticket not found or already paid");
        }
        
        // Insert payment
        $stmt = $pdo->prepare("
            INSERT INTO payments 
            (ticket_id, payment_method_id, receipt_number, amount_paid, payment_status, processed_by, notes) 
            VALUES (?, ?, ?, ?, 'COMPLETED', ?, ?)
        ");
        
        $stmt->execute([
            $ticket_id,
            $payment_method_id,
            $receipt_number,
            $amount_paid,
            $processed_by,
            $notes
        ]);
        
        // Update ticket status to PAID
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'PAID' WHERE id = ?");
        $stmt->execute([$ticket_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Payment processed successfully! Receipt #: {$receipt_number}";
        header("Location: ../view_ticket.php?id={$ticket_id}");
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Error processing payment: " . $e->getMessage();
        header("Location: ../add_payment.php?ticket_id=" . (isset($ticket_id) ? $ticket_id : ''));
        exit;
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: ../payments.php");
    exit;
}
?>
