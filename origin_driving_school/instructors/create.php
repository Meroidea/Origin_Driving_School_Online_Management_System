<?php
/**
 * FILE PATH: instructors/create.php
 * 
 * Add New Instructor Page - Origin Driving School Management System
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 3.0 - FULLY CORRECTED
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/core/Database.php';
require_once '../app/core/Model.php';
require_once '../app/models/User.php';
require_once '../app/models/Instructor.php';
require_once '../app/models/CourseAndOther.php';

// Require admin or staff role
requireRole(['admin', 'staff']);

$instructorModel = new Instructor();
$userModel = new User();
$branchModel = new Branch();

// Get branches for dropdown
$branches = $branchModel->getActiveBranches();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate required fields
    $errors = [];
    
    // User fields validation
    if (empty($_POST['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($_POST['first_name'])) {
        $errors[] = 'First name is required';
    }
    
    if (empty($_POST['last_name'])) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($_POST['phone'])) {
        $errors[] = 'Phone is required';
    }
    
    if (empty($_POST['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($_POST['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    // Instructor fields validation
    if (empty($_POST['branch_id'])) {
        $errors[] = 'Branch is required';
    }
    
    if (empty($_POST['certificate_number'])) {
        $errors[] = 'Certificate number is required';
    }
    
    if (empty($_POST['hourly_rate'])) {
        $errors[] = 'Hourly rate is required';
    } elseif (!is_numeric($_POST['hourly_rate']) || $_POST['hourly_rate'] <= 0) {
        $errors[] = 'Hourly rate must be a positive number';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $existingUser = $userModel->getByEmail($_POST['email']);
        if ($existingUser) {
            $errors[] = 'Email address is already registered';
        }
    }
    
    // If no errors, create the instructor
    if (empty($errors)) {
        
        // Start transaction
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // 1. Create user account
            $userData = [
                'email' => trim($_POST['email']),
                'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'user_role' => 'instructor',
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'phone' => trim($_POST['phone']),
                'is_active' => 1
            ];
            
            $userId = $userModel->create($userData);
            
            if (!$userId) {
                throw new Exception('Failed to create user account');
            }
            
            // 2. Create instructor record - MATCHES UPDATED SCHEMA
            $instructorData = [
                'user_id' => $userId,
                'branch_id' => (int)$_POST['branch_id'],
                'certificate_number' => trim($_POST['certificate_number']),
                'adta_membership' => !empty($_POST['adta_membership']) ? trim($_POST['adta_membership']) : null,
                'wwc_card_number' => !empty($_POST['wwc_card_number']) ? trim($_POST['wwc_card_number']) : null,
                'license_expiry' => !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null,
                'certification_expiry' => !empty($_POST['certification_expiry']) ? $_POST['certification_expiry'] : null,
                'police_check_date' => !empty($_POST['police_check_date']) ? $_POST['police_check_date'] : null,
                'medical_check_date' => !empty($_POST['medical_check_date']) ? $_POST['medical_check_date'] : null,
                'date_joined' => date('Y-m-d'),
                'hourly_rate' => (float)$_POST['hourly_rate'],
                'specialization' => !empty($_POST['specialization']) ? trim($_POST['specialization']) : null,
                'bio' => !empty($_POST['bio']) ? trim($_POST['bio']) : null,
                'is_available' => isset($_POST['is_available']) ? 1 : 0
            ];
            
            // Debug: Log the data being inserted
            error_log('Instructor Data: ' . print_r($instructorData, true));
            
            $instructorId = $instructorModel->create($instructorData);
            
            if (!$instructorId) {
                throw new Exception('Failed to create instructor record. Check error logs for details.');
            }
            
            // Commit transaction
            $conn->commit();
            
            // Success message
            setFlashMessage('success', 'Instructor account created successfully!');
            redirect('/instructors/view.php?id=' . $instructorId);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            $errors[] = 'Error creating instructor: ' . $e->getMessage();
            error_log('Instructor creation error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
}

$pageTitle = 'Add New Instructor';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h2><i class="fas fa-user-plus"></i> Add New Instructor</h2>
                <p style="color: #666; margin-top: 0.3rem;">
                    Create a new instructor account with qualifications
                </p>
            </div>
            <div class="user-menu">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Instructors
                </a>
            </div>
        </div>
        
        <div class="content-area">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="instructor-form">
                
                <!-- Personal Information -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                       placeholder="0412-345-678" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Qualifications & Certifications -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-certificate"></i> Qualifications & Certifications</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="certificate_number">Certificate Number (Cert IV) *</label>
                                <input type="text" id="certificate_number" name="certificate_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['certificate_number'] ?? ''); ?>" 
                                       placeholder="CERT-IV-YYYY-XXXX" required>
                                <small class="form-text text-muted">Certificate IV in Training and Assessment</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="adta_membership">ADTA Membership Number</label>
                                <input type="text" id="adta_membership" name="adta_membership" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['adta_membership'] ?? ''); ?>" 
                                       placeholder="ADTA-VIC-XXXXX">
                                <small class="form-text text-muted">Australian Driver Trainers Association</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="wwc_card_number">Working with Children Check</label>
                                <input type="text" id="wwc_card_number" name="wwc_card_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['wwc_card_number'] ?? ''); ?>" 
                                       placeholder="WWC-XXXXXX-XX">
                            </div>
                            
                            <div class="form-group">
                                <label for="license_expiry">Driver's License Expiry</label>
                                <input type="date" id="license_expiry" name="license_expiry" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['license_expiry'] ?? ''); ?>"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="certification_expiry">Certification Expiry Date</label>
                                <input type="date" id="certification_expiry" name="certification_expiry" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['certification_expiry'] ?? ''); ?>"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="police_check_date">Police Check Date</label>
                                <input type="date" id="police_check_date" name="police_check_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['police_check_date'] ?? ''); ?>"
                                       max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="medical_check_date">Medical Check Date</label>
                            <input type="date" id="medical_check_date" name="medical_check_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['medical_check_date'] ?? ''); ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Employment Details -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-briefcase"></i> Employment Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="branch_id">Branch *</label>
                                <select id="branch_id" name="branch_id" class="form-control" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>" 
                                                <?php echo ($_POST['branch_id'] ?? '') == $branch['branch_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="hourly_rate">Hourly Rate ($) *</label>
                                <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['hourly_rate'] ?? ''); ?>" 
                                       min="0" step="0.01" placeholder="75.00" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="specialization">Specialization</label>
                            <select id="specialization" name="specialization" class="form-control">
                                <option value="">Select Specialization (Optional)</option>
                                <option value="Nervous Learners" <?php echo ($_POST['specialization'] ?? '') === 'Nervous Learners' ? 'selected' : ''; ?>>Nervous Learners</option>
                                <option value="Test Preparation" <?php echo ($_POST['specialization'] ?? '') === 'Test Preparation' ? 'selected' : ''; ?>>Test Preparation</option>
                                <option value="Beginner Focused" <?php echo ($_POST['specialization'] ?? '') === 'Beginner Focused' ? 'selected' : ''; ?>>Beginner Focused</option>
                                <option value="Advanced Skills" <?php echo ($_POST['specialization'] ?? '') === 'Advanced Skills' ? 'selected' : ''; ?>>Advanced Skills</option>
                                <option value="Defensive Driving" <?php echo ($_POST['specialization'] ?? '') === 'Defensive Driving' ? 'selected' : ''; ?>>Defensive Driving</option>
                                <option value="International Students" <?php echo ($_POST['specialization'] ?? '') === 'International Students' ? 'selected' : ''; ?>>International Students</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Bio / About</label>
                            <textarea id="bio" name="bio" class="form-control" rows="4" 
                                      placeholder="Brief description about the instructor's experience, teaching style, and qualifications..."><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">This will be shown to students</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_available" value="1" 
                                       <?php echo isset($_POST['is_available']) || !isset($_POST['submit']) ? 'checked' : ''; ?>>
                                <span>Available for new students</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Login Credentials -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-key"></i> Login Credentials</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" class="form-control" 
                                       minlength="6" required
                                       placeholder="Minimum 6 characters">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                       minlength="6" required
                                       placeholder="Re-enter password">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Instructor will use their email address and this password to login to the system to view their schedule and manage lessons.
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" name="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Create Instructor Account
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.instructor-form .card {
    margin-bottom: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.form-control {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #4e7e95;
    box-shadow: 0 0 0 3px rgba(78, 126, 149, 0.1);
}

.form-text {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    color: #6c757d;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.alert-info {
    background: rgba(78, 126, 149, 0.1);
    border: 1px solid rgba(78, 126, 149, 0.2);
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-info i {
    color: #4e7e95;
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<?php include '../views/layouts/footer.php'; ?>