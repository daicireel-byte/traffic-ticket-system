<?php
/**
 * PROCESS TICKET
 * Location: process/process_ticket.php
 * * PURPOSE: Handle ticket creation and violator registration
 * FLOW: Receive Form Data → Validate → Save to Database → Redirect
 */

session_start();
// The database.php file must include helpers.php or functions.php 
// where the createNotification function is defined.
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_ticket'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get and sanitize form data
        $ticket_number = clean($_POST['ticket_number']);
        $date_issued = clean($_POST['date_issued']);
        $due_date = clean($_POST['due_date']);
        $first_name = clean($_POST['first_name']);
        $middle_initial = clean($_POST['middle_initial']);
        $last_name = clean($_POST['last_name']);
        $suffix = clean($_POST['suffix']);
        $license_number = clean($_POST['license_number']);
        $plate_number = clean($_POST['plate_number']);
        $violation_type = (int)$_POST['violation_type'];
        $location = clean($_POST['location']);
        $officer_id = (int)$_POST['officer_id'];
        $total_amount = (float)$_POST['total_amount'];
        $notes = clean($_POST['notes']);
        
        // Check if violator exists or create new
        $violator_id = null;
        
        if (isset($_POST['violator_id']) && !empty($_POST['violator_id'])) {
            // Use existing violator
            $violator_id = (int)$_POST['violator_id'];
        } else {
            // Check if violator with same license exists
            $stmt = $pdo->prepare("SELECT id FROM violators WHERE license_number = ?");
            $stmt->execute([$license_number]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $violator_id = $existing['id'];
            } else {
                // Create new violator
                // Get additional violator info from session or form
                $address = isset($_POST['address']) ? clean($_POST['address']) : 'Not provided';
                $contact_number = isset($_POST['contact_number']) ? clean($_POST['contact_number']) : 'Not provided';
                $email = isset($_POST['email']) ? clean($_POST['email']) : null;
                
                $stmt = $pdo->prepare("
                    INSERT INTO violators 
                    (license_number, first_name, middle_initial, last_name, suffix, address, contact_number, email) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $license_number,
                    $first_name,
                    $middle_initial,
                    $last_name,
                    $suffix,
                    $address,
                    $contact_number,
                    $email
                ]);
                
                $violator_id = $pdo->lastInsertId();
            }
        }
        
        // Insert ticket
        $stmt = $pdo->prepare("
            INSERT INTO tickets 
            (ticket_number, violator_id, officer_id, violation_type, plate_number, location, 
             date_issued, due_date, total_amount, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'UNPAID', ?)
        ");
        
        $stmt->execute([
            $ticket_number,
            $violator_id,
            $officer_id,
            $violation_type,
            $plate_number,
            $location,
            $date_issued,
            $due_date,
            $total_amount,
            $notes
        ]);
        
        $new_ticket_id = $pdo->lastInsertId();

        // Commit transaction
        $pdo->commit();

        // 🟢 FIX: Create a notification after a successful ticket insertion
        $notification_message = "New Ticket #{$ticket_number} (₱" . number_format($total_amount, 2) . ") issued to {$first_name} {$last_name}.";
        
        // Ensure createNotification is available (e.g., from config/database.php chain)
        if (function_exists('createNotification')) {
            createNotification(
                $pdo, 
                $notification_message, 
                'NEW_TICKET', // Type of notification
                'HIGH',       // Priority
                $new_ticket_id // Reference the new ticket ID
            );
        }
        // END FIX 🟢
        
        $_SESSION['success'] = "Ticket {$ticket_number} has been issued successfully!";
        header("Location: ../tickets.php");
        exit;
        
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        $_SESSION['error'] = "Error creating ticket: " . $e->getMessage();
        header("Location: ../add_ticket.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: ../tickets.php");
    exit;
}
?>