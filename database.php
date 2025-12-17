<?php
/**
 * DATABASE CONNECTION FILE
 * Location: config/database.php
 * PURPOSE: Handles MySQL database connection using PDO and starts the PHP session.
 */

// 🟢 FIX: session_start() MUST be the very first executable code (or near the top).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = 'localhost';
$dbname = 'trafficticketsystem';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password
    );
    
    // Set error mode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable emulated prepared statements
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

/**
 * HELPER FUNCTIONS
 */

// Generate unique ticket number
function generateTicketNumber($pdo) {
    $year = date('Y');
    $month = date('m');
    
    $stmt = $pdo->query("
        SELECT ticket_number 
        FROM tickets 
        WHERE ticket_number LIKE 'TCK-$year$month-%' 
        ORDER BY id DESC LIMIT 1
    ");
    $lastTicket = $stmt->fetch();
    
    if ($lastTicket) {
        $lastNum = intval(substr($lastTicket['ticket_number'], -4));
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }
    
    return sprintf("TCK-%s%s-%04d", $year, $month, $newNum);
}

// Generate unique receipt number
function generateReceiptNumber($pdo) {
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    
    $stmt = $pdo->query("
        SELECT receipt_number 
        FROM payments 
        WHERE receipt_number LIKE 'RCP-$year$month$day-%' 
        ORDER BY id DESC LIMIT 1
    ");
    $lastReceipt = $stmt->fetch();
    
    if ($lastReceipt) {
        $lastNum = intval(substr($lastReceipt['receipt_number'], -5));
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }
    
    return sprintf("RCP-%s%s%s-%05d", $year, $month, $day, $newNum);
}

// Sanitize input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format full name
function formatFullName($first, $middle, $last, $suffix = '') {
    $name = $first;
    if ($middle) {
        $name .= ' ' . $middle . '.';
    }
    $name .= ' ' . $last;
    if ($suffix) {
        $name .= ' ' . $suffix;
    }
    return $name;
}

// Calculate days overdue
function calculateDaysOverdue($due_date) {
    $due = new DateTime($due_date);
    $now = new DateTime();
    if ($now > $due) {
        return $now->diff($due)->days;
    }
    return 0;
}

// Calculate late penalty
function calculateLatePenalty($base_amount, $late_rate, $days_overdue) {
    if ($days_overdue > 0) {
        $late_fee = $base_amount * ($late_rate / 100) * $days_overdue;
        return $base_amount + $late_fee;
    }
    return $base_amount;
}
// ⚠️ No closing PHP tag (?>