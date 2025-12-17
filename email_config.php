<?php
/**
 * EMAIL CONFIGURATION FOR TRAFFIC TICKET SYSTEM
 * Template: Clean & Professional
 * Location: config/email_config.php
 */

require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->mail->SMTPDebug = 0;
        $this->mail->Debugoutput = 'html';
        
        try {
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'trafficsystem523@gmail.com';
            $this->mail->Password = 'ztjrukvyekjhgtst';
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = 587;
            $this->mail->setFrom('trafficsystem523@gmail.com', 'Traffic Ticket System');
            $this->mail->isHTML(true);
        } catch (Exception $e) {
            error_log("PHPMailer Configuration Error: " . $e->getMessage());
        }
    }
    
    /**
     * Send Ticket Notification Email
     */
    public function sendTicketNotification($to_email, $to_name, $ticket_data) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to_email, $to_name);
            $this->mail->Subject = 'Traffic Violation Notice - ' . $ticket_data['ticket_number'];
            $this->mail->Body = $this->buildTicketEmail($ticket_data);
            $this->mail->AltBody = $this->buildTicketText($ticket_data);
            $this->mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            error_log("Email Error: " . $this->mail->ErrorInfo);
            return ['success' => false, 'message' => "Email could not be sent. Error: {$this->mail->ErrorInfo}"];
        }
    }
    
    /**
     * Send Payment Confirmation Email
     */
    public function sendPaymentConfirmation($to_email, $to_name, $payment_data) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to_email, $to_name);
            $this->mail->Subject = 'Payment Receipt - ' . $payment_data['receipt_number'];
            $this->mail->Body = $this->buildPaymentEmail($payment_data);
            $this->mail->AltBody = $this->buildPaymentText($payment_data);
            $this->mail->send();
            return ['success' => true, 'message' => 'Receipt sent successfully'];
        } catch (Exception $e) {
            error_log("Email Error: " . $this->mail->ErrorInfo);
            return ['success' => false, 'message' => "Receipt could not be sent. Error: {$this->mail->ErrorInfo}"];
        }
    }
    
    /**
     * Send Overdue Reminder Email
     */
    public function sendOverdueReminder($to_email, $to_name, $ticket_data) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to_email, $to_name);
            $this->mail->Subject = 'Overdue Payment Notice - ' . $ticket_data['ticket_number'];
            $this->mail->Body = $this->buildOverdueEmail($ticket_data);
            $this->mail->AltBody = $this->buildOverdueText($ticket_data);
            $this->mail->send();
            return ['success' => true, 'message' => 'Reminder sent successfully'];
        } catch (Exception $e) {
            error_log("Email Error: " . $this->mail->ErrorInfo);
            return ['success' => false, 'message' => "Reminder could not be sent. Error: {$this->mail->ErrorInfo}"];
        }
    }
    
    /**
     * BUILD TICKET EMAIL (Clean & Professional Template)
     */
    private function buildTicketEmail($data) {
        $issued_date = date('F d, Y', strtotime($data['date_issued']));
        $due_date = date('F d, Y', strtotime($data['due_date']));
        $amount = number_format($data['total_amount'], 2);
        $address = "City Hall, N.S Valderosa Street, Zamboanga City";
        
        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Traffic Violation Notice</title>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f5f5; padding: 20px 0;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    
                    <!-- Header -->
                    <tr>
                        <td style='background-color: #1a237e; color: #ffffff; padding: 20px; border-bottom: 4px solid #ff9800;'>
                            <h1 style='margin: 0; font-size: 24px; font-weight: bold;'>Traffic Violation Notice</h1>
                            <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Official Communication from City Traffic Management</p>
                        </td>
                    </tr>
                    
                    <!-- Body Content -->
                    <tr>
                        <td style='padding: 30px;'>
                            <!-- Greeting -->
                            <p style='margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #333;'>
                                Dear <strong>{$data['violator_name']}</strong>,
                            </p>
                            
                            <p style='margin: 0 0 25px 0; font-size: 15px; line-height: 1.8; color: #333;'>
                                This is an official notice that a traffic violation ticket has been issued to you. Please review the following information and take appropriate action before the due date.
                            </p>
                            
                            <!-- Ticket Details Box -->
                            <table width='100%' cellpadding='20' style='border: 2px solid #1a237e; background-color: #f8f9fa; margin: 25px 0;'>
                                <tr>
                                    <td>
                                        <h2 style='margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; color: #1a237e; font-size: 18px;'>Ticket Details</h2>
                                        
                                        <table width='100%' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold; width: 40%; color: #333;'>Ticket Number:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #333;'>{$data['ticket_number']}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold; color: #333;'>Date Issued:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #333;'>{$issued_date}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold; color: #333;'>Due Date:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #ff9800; font-weight: bold;'>{$due_date}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold; color: #333;'>Violation Type:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #333;'>{$data['violation_name']}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold; color: #333;'>Location:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #333;'>{$data['location']}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; font-weight: bold; color: #333;'>Vehicle Plate:</td>
                                                <td style='padding: 10px 0; color: #333;'>{$data['plate_number']}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Amount Due Box -->
                            <table width='100%' cellpadding='20' style='background-color: #fff3e0; border-left: 4px solid #ff9800; margin: 25px 0;'>
                                <tr>
                                    <td style='width: 70%;'>
                                        <p style='margin: 0; font-size: 15px; color: #333; font-weight: bold;'>Total Amount Due</p>
                                        <p style='margin: 5px 0 0 0; font-size: 12px; color: #666;'>Payment must be made by {$due_date}</p>
                                    </td>
                                    <td style='text-align: right;'>
                                        <p style='margin: 0; font-size: 32px; font-weight: bold; color: #ff9800;'>₱{$amount}</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Payment Instructions -->
                            <h3 style='margin: 25px 0 15px 0; color: #1a237e; font-size: 16px;'>Payment Instructions</h3>
                            <ol style='margin: 0; padding-left: 20px; line-height: 2; color: #333;'>
                                <li>Visit the Traffic Management Office at {$address}</li>
                                <li>Present this ticket number: <strong>{$data['ticket_number']}</strong></li>
                                <li>Bring valid identification and vehicle registration</li>
                                <li>Office hours: Monday to Friday, 8:00 AM to 5:00 PM</li>
                            </ol>
                            
                            <!-- Warning Box -->
                            <table width='100%' cellpadding='15' style='background-color: #ffebee; border: 1px solid #ef5350; border-radius: 4px; margin: 25px 0;'>
                                <tr>
                                    <td>
                                        <p style='margin: 0; font-weight: bold; color: #c62828;'>⚠️ Important Notice</p>
                                        <p style='margin: 5px 0 0 0; font-size: 14px; color: #666; line-height: 1.6;'>
                                            Late payments will incur a penalty of 3% per day after the due date. Please settle this ticket promptly to avoid additional charges.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style='margin: 25px 0 0 0; font-size: 15px; line-height: 1.8; color: #333;'>
                                If you have any questions or concerns regarding this ticket, please contact our office during business hours.
                            </p>
                            
                            <!-- Contact Info -->
                            <table width='100%' cellpadding='0' style='margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;'>
                                <tr>
                                    <td style='font-size: 13px; color: #666; line-height: 1.8;'>
                                        <p style='margin: 0; font-weight: bold; color: #333;'>Traffic Management Office</p>
                                        <p style='margin: 5px 0;'>{$address}</p>
                                        <p style='margin: 5px 0;'>Phone: (062) 123-4567</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd;'>
                            <p style='margin: 0 0 5px 0;'>Traffic Ticket System © " . date('Y') . " • City Traffic Management</p>
                            <p style='margin: 0;'>This is an automated notification. Please do not reply to this email.</p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        ";
    }
    
    /**
     * BUILD PAYMENT RECEIPT EMAIL
     */
    private function buildPaymentEmail($data) {
        $payment_date = date('F d, Y g:i A', strtotime($data['payment_date']));
        $amount = number_format($data['amount_paid'], 2);
        $address = "City Hall, N.S Valderosa Street, Zamboanga City";
        
        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f5f5; padding: 20px 0;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    
                    <tr>
                        <td style='background-color: #2e7d32; color: #ffffff; padding: 20px; border-bottom: 4px solid #4caf50;'>
                            <h1 style='margin: 0; font-size: 24px;'>Payment Confirmation</h1>
                            <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Official Receipt - City Traffic Management</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style='padding: 30px;'>
                            <p style='margin: 0 0 20px 0; font-size: 15px;'>Dear <strong>{$data['violator_name']}</strong>,</p>
                            
                            <p style='margin: 0 0 25px 0; font-size: 15px; line-height: 1.8;'>
                                Thank you for your payment. Your traffic violation ticket has been successfully settled. This email serves as your official receipt.
                            </p>
                            
                            <table width='100%' cellpadding='15' style='background-color: #e8f5e9; border: 2px solid #4caf50; margin: 25px 0;'>
                                <tr>
                                    <td style='text-align: center;'>
                                        <p style='margin: 0; font-size: 14px; color: #666;'>PAYMENT SUCCESSFUL</p>
                                        <p style='margin: 10px 0; font-size: 36px; font-weight: bold; color: #2e7d32;'>₱{$amount}</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <table width='100%' cellpadding='20' style='border: 2px solid #2e7d32; background-color: #f8f9fa; margin: 25px 0;'>
                                <tr>
                                    <td>
                                        <h2 style='margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; color: #2e7d32; font-size: 18px;'>Receipt Details</h2>
                                        
                                        <table width='100%' cellpadding='10' cellspacing='0'>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold; width: 40%;'>Receipt Number:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0;'>{$data['receipt_number']}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold;'>Ticket Number:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0;'>{$data['ticket_number']}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold;'>Payment Date:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0;'>{$payment_date}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold;'>Payment Method:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0;'>{$data['payment_method']}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold;'>Amount Paid:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold; color: #2e7d32;'>₱{$amount}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; font-weight: bold;'>Status:</td>
                                                <td style='padding: 10px 0; color: #2e7d32; font-weight: bold;'>PAID</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <table width='100%' cellpadding='15' style='background-color: #e3f2fd; border: 1px solid #2196f3; border-radius: 4px; margin: 25px 0;'>
                                <tr>
                                    <td>
                                        <p style='margin: 0; font-weight: bold; color: #1976d2;'>📄 Important</p>
                                        <p style='margin: 5px 0 0 0; font-size: 14px; color: #666;'>Please keep this receipt for your records. This serves as proof of payment for your traffic violation ticket.</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style='margin: 25px 0 0 0; font-size: 15px; line-height: 1.8;'>
                                Thank you for your prompt payment and for following traffic regulations.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd;'>
                            <p style='margin: 0 0 5px 0;'>Traffic Ticket System © " . date('Y') . " • City Traffic Management</p>
                            <p style='margin: 0;'>This is an automated receipt. Please do not reply to this email.</p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        ";
    }
    
    /**
     * BUILD OVERDUE REMINDER EMAIL
     */
    private function buildOverdueEmail($data) {
        $due_date = date('F d, Y', strtotime($data['due_date']));
        $days_overdue = $data['days_overdue'];
        $original_amount = number_format($data['total_amount'], 2);
        $late_fee = $data['total_amount'] * 0.03 * $days_overdue;
        $total_amount = number_format($data['total_amount'] + $late_fee, 2);
        $late_fee_formatted = number_format($late_fee, 2);
        $address = "City Hall, N.S Valderosa Street, Zamboanga City";
        
        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0;'>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f5f5; padding: 20px 0;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    
                    <tr>
                        <td style='background-color: #c62828; color: #ffffff; padding: 20px; border-bottom: 4px solid #e53935;'>
                            <h1 style='margin: 0; font-size: 24px;'>Overdue Payment Notice</h1>
                            <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Urgent: Immediate Action Required</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style='padding: 30px;'>
                            <p style='margin: 0 0 20px 0; font-size: 15px;'>Dear <strong>{$data['violator_name']}</strong>,</p>
                            
                            <p style='margin: 0 0 25px 0; font-size: 15px; line-height: 1.8;'>
                                This is an urgent reminder that your traffic violation ticket payment is now <strong style='color: #c62828;'>{$days_overdue} days overdue</strong>. Late fees have been applied to your account.
                            </p>
                            
                            <table width='100%' cellpadding='20' style='background-color: #ffebee; border: 2px solid #ef5350; margin: 25px 0;'>
                                <tr>
                                    <td>
                                        <table width='100%'>
                                            <tr>
                                                <td style='text-align: center; padding-bottom: 15px; border-bottom: 2px solid #ef5350;'>
                                                    <p style='margin: 0; font-size: 14px; color: #666; font-weight: bold;'>OVERDUE BY {$days_overdue} DAYS</p>
                                                    <p style='margin: 10px 0 0 0; font-size: 36px; font-weight: bold; color: #c62828;'>₱{$total_amount}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style='padding-top: 15px; text-align: center;'>
                                                    <p style='margin: 0; font-size: 13px; color: #666;'>
                                                        Original Amount: ₱{$original_amount} + Late Fee: ₱{$late_fee_formatted}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <table width='100%' cellpadding='20' style='border: 2px solid #c62828; background-color: #f8f9fa; margin: 25px 0;'>
                                <tr>
                                    <td>
                                        <h2 style='margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; color: #c62828; font-size: 18px;'>Ticket Information</h2>
                                        
                                        <table width='100%' cellpadding='10' cellspacing='0'>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold; width: 40%;'>Ticket Number:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0;'>{$data['ticket_number']}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold;'>Original Due Date:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0;'>{$due_date}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold;'>Days Overdue:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #c62828; font-weight: bold;'>{$days_overdue} days</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold;'>Original Amount:</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0;'>₱{$original_amount}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: bold;'>Late Fee (3% per day):</td>
                                                <td style='padding: 10px 0; border-bottom: 1px solid #e0e0e0; color: #c62828;'>₱{$late_fee_formatted}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 10px 0; font-weight: bold; font-size: 16px;'>Current Total:</td>
                                                <td style='padding: 10px 0; font-weight: bold; font-size: 16px; color: #c62828;'>₱{$total_amount}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <table width='100%' cellpadding='15' style='background-color: #fff3e0; border-left: 4px solid #ff9800; margin: 25px 0;'>
                                <tr>
                                    <td>
                                        <p style='margin: 0; font-weight: bold; color: #e65100;'>⚠️ Action Required</p>
                                        <p style='margin: 5px 0 0 0; font-size: 14px; color: #666; line-height: 1.6;'>
                                            Please settle this payment immediately to avoid additional penalties. Late fees continue to accrue at 3% per day until payment is received.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <h3 style='margin: 25px 0 15px 0; color: #1a237e; font-size: 16px;'>Payment Options</h3>
                            <ul style='margin: 0; padding-left: 20px; line-height: 2;'>
                                <li><strong>Visit Office:</strong> Traffic Management Office, {$address}</li>
                                <li><strong>Office Hours:</strong> Monday-Friday, 8:00 AM - 5:00 PM</li>
                                <li><strong>Contact:</strong> (062) 123-4567</li>
                                <li><strong>Bring:</strong> This ticket number and valid ID</li>
                            </ul>
                            
                            <table width='100%' cellpadding='15' style='background-color: #ffebee; border: 1px solid #ef5350; border-radius: 4px; margin: 25px 0;'>
                                <tr>
                                    <td>
                                        <p style='margin: 0; font-weight: bold; color: #c62828;'>⚠️ Warning</p>
                                        <p style='margin: 5px 0 0 0; font-size: 14px; color: #666; line-height: 1.6;'>
                                            Failure to pay may result in additional penalties, vehicle impoundment, or legal action.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd;'>
                            <p style='margin: 0 0 5px 0;'>Traffic Ticket System © " . date('Y') . " • City Traffic Management</p>
                            <p style='margin: 0;'>This is an automated overdue reminder. Please do not reply to this email.</p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        ";
    }
    
    /**
     * PLAIN TEXT VERSION - TICKET
     */
    private function buildTicketText($data) {
        $issued_date = date('F d, Y', strtotime($data['date_issued']));
        $due_date = date('F d, Y', strtotime($data['due_date']));
        $amount = number_format($data['total_amount'], 2);
        $address = "City Hall, N.S Valderosa Street, Zamboanga City";
        
        return "
TRAFFIC VIOLATION NOTICE
Official Communication from City Traffic Management

Dear {$data['violator_name']},

This is an official notice that a traffic violation ticket has been issued to you.

TICKET DETAILS:
Ticket Number: {$data['ticket_number']}
Date Issued: {$issued_date}
Due Date: {$due_date}
Violation Type: {$data['violation_name']}
Location: {$data['location']}
Vehicle Plate: {$data['plate_number']}

AMOUNT DUE: ₱{$amount}
Payment must be made by {$due_date}

PAYMENT INSTRUCTIONS:
1. Visit the Traffic Management Office at {$address}
2. Present this ticket number: {$data['ticket_number']}
3. Bring valid identification and vehicle registration
4. Office hours: Monday to Friday, 8:00 AM to 5:00 PM

IMPORTANT NOTICE:
Late payments will incur a penalty of 3% per day after the due date.
Please settle this ticket promptly to avoid additional charges.

Contact Information:
Traffic Management Office
{$address}
Phone: (062) 123-4567

Traffic Ticket System © " . date('Y') . "
This is an automated notification. Please do not reply to this email.
        ";
    }
    
    /**
     * PLAIN TEXT VERSION - PAYMENT RECEIPT
     */
    private function buildPaymentText($data) {
        $payment_date = date('F d, Y g:i A', strtotime($data['payment_date']));
        $amount = number_format($data['amount_paid'], 2);
        $address = "City Hall, N.S Valderosa Street, Zamboanga City";
        
        return "
PAYMENT CONFIRMATION
Official Receipt - City Traffic Management

Dear {$data['violator_name']},

Thank you for your payment. Your traffic violation ticket has been successfully settled.

RECEIPT DETAILS:
Receipt Number: {$data['receipt_number']}
Ticket Number: {$data['ticket_number']}
Payment Date: {$payment_date}
Payment Method: {$data['payment_method']}
Amount Paid: ₱{$amount}
Status: PAID

IMPORTANT:
Please keep this receipt for your records. This serves as proof of payment.

Thank you for your prompt payment and for following traffic regulations.

Traffic Ticket System © " . date('Y') . "
This is an automated receipt. Please do not reply to this email.
        ";
    }
    /**
     * PLAIN TEXT VERSION - OVERDUE REMINDER
     */
    private function buildOverdueText($data) {
        $due_date = date('F d, Y', strtotime($data['due_date']));
        $days_overdue = $data['days_overdue'];
        $original_amount = number_format($data['total_amount'], 2);
        $late_fee = $data['total_amount'] * 0.03 * $days_overdue;
        $total_amount = number_format($data['total_amount'] + $late_fee, 2);
        $late_fee_formatted = number_format($late_fee, 2);
        $address = "City Hall, N.S Valderosa Street, Zamboanga City";
        
        return "
OVERDUE PAYMENT NOTICE
Urgent: Immediate Action Required

Dear {$data['violator_name']},

This is an urgent reminder that your traffic violation ticket payment is now {$days_overdue} days overdue.

TICKET INFORMATION:
Ticket Number: {$data['ticket_number']}
Original Due Date: {$due_date}
Days Overdue: {$days_overdue} days

AMOUNT BREAKDOWN:
Original Amount: ₱{$original_amount}
Late Fee (3% per day): ₱{$late_fee_formatted}
CURRENT TOTAL: ₱{$total_amount}

ACTION REQUIRED:
Please settle this payment immediately to avoid additional penalties.
Late fees continue to accrue at 3% per day until payment is received.

PAYMENT OPTIONS:
Visit: Traffic Management Office, {$address}
Hours: Monday-Friday, 8:00 AM - 5:00 PM
Contact: (062) 123-4567
Bring: Ticket number and valid ID

WARNING:
Failure to pay may result in additional penalties, vehicle impoundment, or legal action.

Traffic Ticket System © " . date('Y') . "
This is an automated overdue reminder. Please do not reply to this email.
        ";
    }
}
?>