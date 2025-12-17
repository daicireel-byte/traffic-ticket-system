<?php
/**
 * ADD TICKET FORM
 * Location: add_ticket.php
 * 
 * PURPOSE: Form to add new traffic violation ticket
 * FLOW: Display Form → Submit to process_ticket.php
 */

$page_title = "Add New Ticket";
require_once 'config/database.php';
require_once 'includes/header.php';

// Get violators for dropdown
$violators = $pdo->query("SELECT * FROM violators ORDER BY last_name, first_name")->fetchAll();

// Get officers for dropdown
$officers = $pdo->query("SELECT * FROM officers WHERE status = 'ACTIVE' ORDER BY last_name, first_name")->fetchAll();

// Get violation types for dropdown
$violations = $pdo->query("SELECT * FROM violation_types WHERE status = 'ACTIVE' ORDER BY violation_name")->fetchAll();

// Generate next ticket number
$next_ticket_number = generateTicketNumber($pdo);

// Default dates
$today = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+15 days'));
?>

<div class="container">
    <div class="page-header">
        <h1>Add New Ticket</h1>
        <a href="tickets.php" class="btn btn-secondary">← Back to Tickets</a>
    </div>

    <div class="card">
        <div class="info-box">
            <strong> Instructions:</strong> Fill out all required fields marked with <span style="color: #e74c3c;">*</span>. 
            The ticket number is auto-generated. Due date is automatically set to 15 days from issue date.
        </div>

        <form method="POST" action="process/process_ticket.php">
            <!-- Ticket Information -->
            <div class="form-section">
                <h3> Ticket Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ticket Number <span class="required">*</span></label>
                        <input type="text" name="ticket_number" value="<?= $next_ticket_number ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Date Issued <span class="required">*</span></label>
                        <input type="date" name="date_issued" value="<?= $today ?>" required max="<?= $today ?>">
                    </div>
                    <div class="form-group">
                        <label>Due Date <span class="required">*</span></label>
                        <input type="date" name="due_date" value="<?= $due_date ?>" required min="<?= $today ?>">
                    </div>
                </div>
            </div>

            <!-- Violator Information -->
            <div class="form-section">
                <h3>👤 Violator Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Existing Violator</label>
                        <select id="violator_select" name="violator_id">
                            <option value="">-- Select Violator or Add New Below --</option>
                            <?php foreach($violators as $violator): ?>
                            <option value="<?= $violator['id'] ?>" 
                                    data-license="<?= $violator['license_number'] ?>"
                                    data-first="<?= $violator['first_name'] ?>"
                                    data-middle="<?= $violator['middle_initial'] ?>"
                                    data-last="<?= $violator['last_name'] ?>"
                                    data-suffix="<?= $violator['suffix'] ?>">
                                <?= formatFullName($violator['first_name'], $violator['middle_initial'], 
                                                   $violator['last_name'], $violator['suffix']) ?> 
                                (<?= $violator['license_number'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #7f8c8d;">Or add new violator below</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" id="middle_initial" name="middle_initial" maxlength="5" placeholder="e.g., D">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Suffix</label>
                        <select id="suffix" name="suffix">
                            <option value="">-- None --</option>
                            <option value="Jr.">Jr.</option>
                            <option value="Sr.">Sr.</option>
                            <option value="II">II</option>
                            <option value="III">III</option>
                            <option value="IV">IV</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>License Number <span class="required">*</span></label>
                        <input type="text" id="license_number" name="license_number" required placeholder="e.g., N01-23-456789">
                    </div>
                </div>
            </div>

            <!-- Vehicle & Violation Information -->
            <div class="form-section">
                <h3> Vehicle & Violation Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Plate Number <span class="required">*</span></label>
                        <input type="text" name="plate_number" required placeholder="e.g., ABC 1234">
                    </div>
                    <div class="form-group">
                        <label>Violation Type <span class="required">*</span></label>
                        <select name="violation_type" id="violation_type" required>
                            <option value="">-- Select Violation --</option>
                            <?php foreach($violations as $violation): ?>
                            <option value="<?= $violation['id'] ?>" 
                                    data-amount="<?= $violation['fine_amount'] ?>"
                                    data-severity="<?= $violation['severity_level'] ?>">
                                <?= $violation['violation_name'] ?> - ₱<?= number_format($violation['fine_amount'], 2) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Location <span class="required">*</span></label>
                        <input type="text" name="location" required placeholder="e.g., Rizal Street corner Luna St">
                    </div>
                    <div class="form-group">
                        <label>Total Amount <span class="required">*</span></label>
                        <input type="number" id="total_amount" name="total_amount" step="0.01" required readonly>
                    </div>
                </div>
            </div>

            <!-- Officer Information -->
            <div class="form-section">
                <h3> Officer Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Issuing Officer <span class="required">*</span></label>
                        <select name="officer_id" required>
                            <option value="">-- Select Officer --</option>
                            <?php foreach($officers as $officer): ?>
                            <option value="<?= $officer['id'] ?>">
                                <?= formatFullName($officer['first_name'], $officer['middle_initial'], 
                                                   $officer['last_name'], $officer['suffix']) ?>
                                (<?= $officer['badge_number'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Additional Notes -->
            <div class="form-section">
                <h3> Additional Notes</h3>
                <div class="form-group">
                    <label>Notes/Remarks</label>
                    <textarea name="notes" placeholder="Any additional information about the violation..."></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="tickets.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="add_ticket" class="btn btn-success">
                    ✓ Issue Ticket
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-fill violator information when selected from dropdown
document.getElementById('violator_select').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.getElementById('first_name').value = selected.dataset.first || '';
        document.getElementById('middle_initial').value = selected.dataset.middle || '';
        document.getElementById('last_name').value = selected.dataset.last || '';
        document.getElementById('suffix').value = selected.dataset.suffix || '';
        document.getElementById('license_number').value = selected.dataset.license || '';
        
        // Make fields readonly when selecting existing violator
        document.getElementById('first_name').readOnly = true;
        document.getElementById('middle_initial').readOnly = true;
        document.getElementById('last_name').readOnly = true;
        document.getElementById('suffix').disabled = true;
        document.getElementById('license_number').readOnly = true;
    } else {
        // Clear and enable fields for new violator
        document.getElementById('first_name').value = '';
        document.getElementById('middle_initial').value = '';
        document.getElementById('last_name').value = '';
        document.getElementById('suffix').value = '';
        document.getElementById('license_number').value = '';
        
        document.getElementById('first_name').readOnly = false;
        document.getElementById('middle_initial').readOnly = false;
        document.getElementById('last_name').readOnly = false;
        document.getElementById('suffix').disabled = false;
        document.getElementById('license_number').readOnly = false;
    }
});

// Auto-fill amount when violation is selected
document.getElementById('violation_type').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.getElementById('total_amount').value = selected.dataset.amount || '0.00';
    } else {
        document.getElementById('total_amount').value = '';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>