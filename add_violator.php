<?php
/**
 * ADD VIOLATOR FORM
 * Location: add_violator.php
 * 
 * PURPOSE: Form to add new violator to the database
 * FLOW: Display Form → Submit to process_violator.php
 */

$page_title = "Add New Violator";
require_once 'config/database.php';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Add New Violator</h1>
        <a href="violators.php" class="btn btn-secondary">← Back to Violators</a>
    </div>

    <div class="card">
        <div class="info-box">
            <strong> Instructions:</strong> Register a new violator in the system. 
            All fields marked with <span style="color: #e74c3c;">*</span> are required.
        </div>

        <form method="POST" action="process/process_violator.php">
            <!-- Personal Information -->
            <div class="form-section">
                <h3>👤 Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" required placeholder="e.g., Juan">
                    </div>
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" name="middle_initial" maxlength="5" placeholder="e.g., D">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" required placeholder="e.g., Dela Cruz">
                    </div>
                    <div class="form-group">
                        <label>Suffix</label>
                        <select name="suffix">
                            <option value="">-- None --</option>
                            <option value="Jr.">Jr.</option>
                            <option value="Sr.">Sr.</option>
                            <option value="II">II</option>
                            <option value="III">III</option>
                            <option value="IV">IV</option>
                            <option value="V">V</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- License Information -->
            <div class="form-section">
                <h3> License Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>License Number <span class="required">*</span></label>
                        <input type="text" name="license_number" required placeholder="e.g., N01-23-456789">
                        <small style="color: #7f8c8d;">Format: N##-##-######</small>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="form-section">
                <h3> Contact Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number <span class="required">*</span></label>
                        <input type="tel" name="contact_number" required placeholder="e.g., 09171234567">
                        <small style="color: #7f8c8d;">11-digit mobile number</small>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="e.g., juan.delacruz@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Complete Address <span class="required">*</span></label>
                        <textarea name="address" required placeholder="e.g., 123 Rizal Street, Barangay Centro, Molave, Zamboanga del Sur"></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="violators.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="add_violator" class="btn btn-success">
                    ✓ Register Violator
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>