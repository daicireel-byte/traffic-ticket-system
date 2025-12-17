<?php
require_once 'config/email_config.php';

function sendToActualViolator($violatorData) {
    try {
        $emailSender = new EmailSender();
        
        $ticket_data = [
            'violator_name' => $violatorData['name'],
            'ticket_number' => $violatorData['ticket_number'],
            'date_issued' => $violatorData['date_issued'],
            'due_date' => $violatorData['due_date'],
            'violation_name' => $violatorData['violation'],
            'location' => $violatorData['location'] ?? 'Recorded Location',
            'vehicle_type' => $violatorData['vehicle_type'] ?? 'Vehicle',
            'plate_number' => $violatorData['plate_number'],
            'license_number' => $violatorData['license_no'],
            'total_amount' => $violatorData['amount']
        ];
        
        $result = $emailSender->sendTicketNotification(
            $violatorData['email'] ?? 'daicireel@gmail.com',
            $violatorData['name'], // ACTUAL VIOLATOR NAME
            $ticket_data
        );
        
        return $result;
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// FIXED: Added missing try block and removed extra code
try {
    $emailSender = new EmailSender();
    
    // FIXED: Added missing $test_data array
    $test_data = [
        'violator_name' => 'John Doe',
        'ticket_number' => 'TKT-001',
        'date_issued' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+7 days')),
        'violation_name' => 'Speeding',
        'location' => 'Main Street',
        'vehicle_type' => 'Sedan',
        'plate_number' => 'ABC123',
        'total_amount' => 1500.00
    ]; 
    
    $result = $emailSender->sendTicketNotification(
        'daicireel@gmail.com', 
        'Test User',
        $test_data
    );
    
    if ($result['success']) {
        echo "✅ Test email sent successfully! Check your inbox and spam folder.\n";
    } else {
        echo "❌ Failed to send email: " . $result['message'] . "\n";
    }
    
    // Additional: Test with actual violator
    echo "\n--- Testing Actual Violator ---\n";
    
    $actualViolator = [
        'name' => 'DAICIREE LABADO',
        'ticket_number' => 'TCK-202511-0001',
        'license_no' => 'L12345',
        'plate_number' => '01234',
        'violation' => 'Expired Registration',
        'amount' => 1200.00, // FIXED: Removed comma in number
        'date_issued' => '2025-11-10',
        'due_date' => '2025-11-25',
        'email' => 'daicireel@gmail.com'
    ];
    
    $result2 = sendToActualViolator($actualViolator);
    
    if ($result2['success']) {
        echo "✅ Actual violator email sent to DAICIREE LABADO.!\n";
    } else {
        echo "❌ Actual violator email failed: " . $result2['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}