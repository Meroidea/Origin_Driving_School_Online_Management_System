<?php
/**
 * FILE PATH: students/create.php
 * 
 * Add New Student Page - Origin Driving School Management System
 * 
 * Form to create new student account with user credentials
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/core/Database.php';
require_once '../app/core/Model.php';
require_once '../app/models/User.php';
require_once '../app/models/Student.php';
require_once '../app/models/CourseAndOther.php';
require_once '../app/models/Instructor.php';

// Require admin or staff role
requireRole(['admin', 'staff']);

$studentModel = new Student();
$userModel = new User();
$branchModel = new Branch();
$instructorModel = new Instructor();

// Get branches and instructors for dropdowns
$branches = $branchModel->getActiveBranches();
$instructors = $instructorModel->getAvailableInstructors();

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
    
    // Student fields validation
    if (empty($_POST['date_of_birth'])) {
        $errors[] = 'Date of birth is required';
    } else {
        // Validate age (must be at least 15 years old)
        $dob = new DateTime($_POST['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        
        if ($age < 15) {
            $errors[] = 'Student must be at least 15 years old';
        }
        
        if ($dob > $today) {
            $errors[] = 'Date of birth cannot be in the future';
        }
    }
    
    if (empty($_POST['address'])) {
        $errors[] = 'Address is required';
    }
    
    if (empty($_POST['suburb'])) {
        $errors[] = 'Suburb is required';
    }
    
    if (empty($_POST['postcode'])) {
        $errors[] = 'Postcode is required';
    }
    
    if (empty($_POST['emergency_contact_name'])) {
        $errors[] = 'Emergency contact name is required';
    }
    
    if (empty($_POST['emergency_contact_phone'])) {
        $errors[] = 'Emergency contact phone is required';
    }
    
    if (empty($_POST['branch_id'])) {
        $errors[] = 'Branch is required';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $existingUser = $userModel->getByEmail($_POST['email']);
        if ($existingUser) {
            $errors[] = 'Email address is already registered';
        }
    }
    
    // If no errors, create the student
    if (empty($errors)) {
        
        // Start transaction using Database instance
        $db = Database::getInstance();
        $db->getConnection()->beginTransaction();
        
        try {
            // 1. Create user account
            $userData = [
                'email' => trim($_POST['email']),
                'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'user_role' => 'student',
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'phone' => trim($_POST['phone']),
                'is_active' => 1
            ];
            
            $userId = $userModel->create($userData);
            
            if (!$userId) {
                throw new Exception('Failed to create user account');
            }
            
            // 2. Create student record
            $studentData = [
                'user_id' => $userId,
                'license_number' => !empty($_POST['license_number']) ? trim($_POST['license_number']) : null,
                'license_status' => $_POST['license_status'] ?? 'none',
                'date_of_birth' => $_POST['date_of_birth'],
                'address' => trim($_POST['address']),
                'suburb' => trim($_POST['suburb']),
                'postcode' => trim($_POST['postcode']),
                'emergency_contact_name' => trim($_POST['emergency_contact_name']),
                'emergency_contact_phone' => trim($_POST['emergency_contact_phone']),
                'medical_conditions' => !empty($_POST['medical_conditions']) ? trim($_POST['medical_conditions']) : null,
                'enrollment_date' => date('Y-m-d'),
                'branch_id' => (int)$_POST['branch_id'],
                'assigned_instructor_id' => !empty($_POST['assigned_instructor_id']) ? (int)$_POST['assigned_instructor_id'] : null,
                'total_lessons_completed' => 0,
                'test_ready' => 0,
                'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null
            ];
            
            $studentId = $studentModel->create($studentData);
            
            if (!$studentId) {
                throw new Exception('Failed to create student record');
            }
            
            // Commit transaction
            $db->getConnection()->commit();
            
            // Success message
            setFlashMessage('success', 'Student account created successfully!');
            redirect('/students/view.php?id=' . $studentId);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->getConnection()->rollBack();
            $errors[] = 'Error creating student: ' . $e->getMessage();
            error_log('Student creation error: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Add New Student';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h2><i class="fas fa-user-plus"></i> Add New Student</h2>
                <p style="color: #666; margin-top: 0.3rem;">
                    Create a new student account
                </p>
            </div>
            <div class="user-menu">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
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
            
            <form method="POST" action="" class="student-form">
                
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
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" 
                                       max="<?php echo date('Y-m-d'); ?>"
                                       min="<?php echo date('Y-m-d', strtotime('-100 years')); ?>"
                                       required>
                                <small class="form-text text-muted">Student must be at least 15 years old</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="license_status">License Status *</label>
                                <select id="license_status" name="license_status" class="form-control" required>
                                    <option value="none" <?php echo ($_POST['license_status'] ?? '') === 'none' ? 'selected' : ''; ?>>No License</option>
                                    <option value="learner" <?php echo ($_POST['license_status'] ?? '') === 'learner' ? 'selected' : ''; ?>>Learner Permit</option>
                                    <option value="probationary" <?php echo ($_POST['license_status'] ?? '') === 'probationary' ? 'selected' : ''; ?>>Probationary</option>
                                    <option value="full" <?php echo ($_POST['license_status'] ?? '') === 'full' ? 'selected' : ''; ?>>Full License</option>
                                    <option value="overseas" <?php echo ($_POST['license_status'] ?? '') === 'overseas' ? 'selected' : ''; ?>>Overseas License</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="license_number">License Number</label>
                            <input type="text" id="license_number" name="license_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>"
                                   placeholder="Optional - if applicable">
                        </div>
                    </div>
                </div>
                
                <!-- Address Information -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="address">Street Address *</label>
                            <input type="text" id="address" name="address" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" 
                                   placeholder="123 Main Street" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="suburb">Suburb *</label>
                                <input type="text" id="suburb" name="suburb" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['suburb'] ?? ''); ?>" 
                                       placeholder="Melbourne" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="postcode">Postcode *</label>
                                <input type="text" id="postcode" name="postcode" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['postcode'] ?? ''); ?>" 
                                       placeholder="3000" maxlength="4" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergency_contact_name">Contact Name *</label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="emergency_contact_phone">Contact Phone *</label>
                                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>" 
                                       placeholder="0412-345-678" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Medical & Assignment -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-notes-medical"></i> Medical & Assignment</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="medical_conditions">Medical Conditions</label>
                            <textarea id="medical_conditions" name="medical_conditions" class="form-control" rows="3" 
                                      placeholder="Any medical conditions or special requirements (optional)"><?php echo htmlspecialchars($_POST['medical_conditions'] ?? ''); ?></textarea>
                        </div>
                        
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
                                <label for="assigned_instructor_id">Assigned Instructor</label>
                                <select id="assigned_instructor_id" name="assigned_instructor_id" class="form-control">
                                    <option value="">No instructor assigned yet</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?php echo $instructor['instructor_id']; ?>" 
                                                <?php echo ($_POST['assigned_instructor_id'] ?? '') == $instructor['instructor_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($instructor['instructor_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" 
                                      placeholder="Additional notes about the student (optional)"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
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
                            <strong>Note:</strong> Student will use their email address and this password to login to the system.
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Create Student Account
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
.student-form .card {
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
    color: var(--color-dark);
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
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(78, 126, 149, 0.1);
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

.form-text {
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.text-muted {
    color: #6c757d;
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