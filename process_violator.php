<?php
/**
 * PROCESS VIOLATOR
 * Location: process/process_violator.php
 * 
 * PURPOSE: Handle violator registration
 * FLOW: Receive Form Data → Validate → Save to Database → Redirect
 */

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_violator'])) {
    try {
        // Get and sanitize form data
        $license_number = clean($_POST['license_number']);
        $first_name = clean($_POST['first_name']);
        $middle_initial = clean($_POST['middle_initial']);
        $last_name = clean($_POST['last_name']);
        $suffix = clean($_POST['suffix']);
        $address = clean($_POST['address']);
        $contact_number = clean($_POST['contact_number']);
        $email = clean($_POST['email']);
        
        $stmt = $pdo->prepare("SELECT id FROM violators WHERE license_number = ?");
        $stmt->execute([$license_number]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "A violator with license number {$license_number} already exists!";
            header("Location: ../add_violator.php");
            exit;
        }
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
        
        $full_name = formatFullName($first_name, $middle_initial, $last_name, $suffix);
        $_SESSION['success'] = "Violator {$full_name} has been registered successfully!";
        header("Location: ../violators.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error registering violator: " . $e->getMessage();
        header("Location: ../add_violator.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: ../violators.php");
    exit;
}
?>