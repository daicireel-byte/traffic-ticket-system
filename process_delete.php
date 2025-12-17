<?php
/**
 * PROCESS DELETE
 * Location: process/process_delete.php
 * 
 * PURPOSE: Handle deletion of tickets and violators
 * FLOW: Receive Delete Request → Validate → Delete from Database → Redirect
 */

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Delete Ticket
    if (isset($_POST['delete_ticket'])) {
        try {
            $ticket_id = (int)$_POST['delete_ticket'];
            
            // Get ticket number before deleting
            $stmt = $pdo->prepare("SELECT ticket_number FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                throw new Exception("Ticket not found");
            }
            
            // Delete ticket (payments will cascade)
            $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);