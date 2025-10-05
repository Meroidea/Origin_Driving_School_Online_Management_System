<?php
/**
 * Student Profile Management - Student Dashboard
 * 
 * File path: student/profile.php
 * 
 * Allows students to manage their profile, change password, and update settings
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

// Check authentication
requireLogin();
requireRole(['student']);

// Include required models
require_once APP_PATH . '/models/Student.php';
require_once APP_PATH . '/models/User.php';

// Initialize models
$studentModel = new Student();
$userModel = new User();

$pageTitle = 'My Profile';
$errors = [];
$success = false;
$activeTab = $_GET['tab'] ?? 'personal';

// Get student details
$userId = $_SESSION['user_id'];
$studentSql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.is_active, u.last_login,
               b.branch_name, b.branch_id
               FROM students s 
               INNER JOIN users u ON s.user_id = u.user_id 
               INNER JOIN branches b ON s.branch_id = b.branch_id
               WHERE u.user_id = ?";
$student = $studentModel->customQueryOne($studentSql, [$userId]);

if (!$student) {
    setFlashMessage('error', 'Student profile not found');
    redirect('/dashboard.php');
}

$studentId = $student['student_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Personal Information Update
    if (isset($_POST['update_personal'])) {
        $firstName = sanitize($_POST['first_name']);
        $lastName = sanitize($_POST['last_name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $suburb = sanitize($_POST['suburb']);
        $postcode = sanitize($_POST['postcode']);
        
        // Validation
        if (empty($firstName) || empty($lastName) || empty($phone)) {
            $errors[] = 'Name and phone are required';
        }
        
        if (empty($errors)) {
            $db = Database::getInstance();
            $db->getConnection()->beginTransaction();
            
            try {
                // Update user table
                $userSql = "UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?";
                $userModel->db->update($userSql, [$firstName, $lastName, $phone, $userId]);
                
                // Update student table
                $studentSql = "UPDATE students SET address = ?, suburb = ?, postcode = ? WHERE student_id = ?";
                $studentModel->db->update($studentSql, [$address, $suburb, $postcode, $studentId]);
                
                $db->getConnection()->commit();
                
                // Update session
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                
                $success = true;
                setFlashMessage('success', 'Personal information updated successfully');
                header("Refresh: 1; url=" . APP_URL . "/student/profile.php?tab=personal");
                
            } catch (Exception $e) {
                $db->getConnection()->rollBack();
                $errors[] = 'Error updating profile: ' . $e->getMessage();
            }
        }
        $activeTab = 'personal';
    }
    
    // Password Change
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Get current password hash
        $userInfo = $userModel->getById($userId);
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'All password fields are required';
        } elseif (!password_verify($currentPassword, $userInfo['password_hash'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        if (empty($errors)) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            
            if ($userModel->db->update($updateSql, [$passwordHash, $userId])) {
                $success = true;
                setFlashMessage('success', 'Password changed successfully');
                header("Refresh: 1; url=" . APP_URL . "/student/profile.php?tab=security");
            } else {
                $errors[] = 'Failed to update password';
            }
        }
        $activeTab = 'security';
    }
    
    // Emergency Contact Update
    if (isset($_POST['update_emergency'])) {
        $emergencyName = sanitize($_POST['emergency_contact_name']);
        $emergencyPhone = sanitize($_POST['emergency_contact_phone']);
        
        if (empty($emergencyName) || empty($emergencyPhone)) {
            $errors[] = 'Emergency contact information is required';
        }
        
        if (empty($errors)) {
            $updateSql = "UPDATE students SET emergency_contact_name = ?, emergency_contact_phone = ? WHERE student_id = ?";
            
            if ($studentModel->db->update($updateSql, [$emergencyName, $emergencyPhone, $studentId])) {
                $success = true;
                setFlashMessage('success', 'Emergency contact updated successfully');
                header("Refresh: 1; url=" . APP_URL . "/student/profile.php?tab=emergency");
            } else {
                $errors[] = 'Failed to update emergency contact';
            }
        }
        $activeTab = 'emergency';
    }
    
    // Medical Information Update
    if (isset($_POST['update_medical'])) {
        $medicalConditions = sanitize($_POST['medical_conditions']);
        
        $updateSql = "UPDATE students SET medical_conditions = ? WHERE student_id = ?";
        
        if ($studentModel->db->update($updateSql, [$medicalConditions, $studentId])) {
            $success = true;
            setFlashMessage('success', 'Medical information updated successfully');
            header("Refresh: 1; url=" . APP_URL . "/student/profile.php?tab=medical");
        } else {
            $errors[] = 'Failed to update medical information';
        }
        $activeTab = 'medical';
    }
    
    // Refresh student data after update
    $student = $studentModel->customQueryOne($studentSql, [$userId]);
}

include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <p style="color: #666; margin-top: 0.3rem;">Manage your account and personal information</p>
            </div>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success" style="animation: slideInDown 0.3s ease;">
                    <i class="fas fa-check-circle"></i>
                    <span>Changes saved successfully!</span>
                </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h4>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Profile Header Card -->
            <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, #4e7e95 0%, #3d6578 100%); color: white;">
                <div class="card-body" style="padding: 2rem;">
                    <div style="display: flex; align-items: center; gap: 2rem;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div style="flex: 1;">
                            <h2 style="margin: 0 0 0.5rem 0; color: white;">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                            </h2>
                            <div style="opacity: 0.9; margin-bottom: 0.5rem;">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?>
                            </div>
                            <div style="opacity: 0.9;">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($student['branch_name']); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.9rem; opacity: 0.9;">Member Since</div>
                            <div style="font-size: 1.2rem; font-weight: bold;">
                                <?php echo date('M Y', strtotime($student['enrollment_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="tabs-nav" style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="?tab=personal" class="tab-link <?php echo $activeTab === 'personal' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Personal Info
                    </a>
                    <a href="?tab=security" class="tab-link <?php echo $activeTab === 'security' ? 'active' : ''; ?>">
                        <i class="fas fa-lock"></i> Security
                    </a>
                    <a href="?tab=emergency" class="tab-link <?php echo $activeTab === 'emergency' ? 'active' : ''; ?>">
                        <i class="fas fa-phone-alt"></i> Emergency Contact
                    </a>
                    <a href="?tab=medical" class="tab-link <?php echo $activeTab === 'medical' ? 'active' : ''; ?>">
                        <i class="fas fa-notes-medical"></i> Medical Info
                    </a>
                    <a href="?tab=account" class="tab-link <?php echo $activeTab === 'account' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Account Settings
                    </a>
                </div>
            </div>
            
            <!-- Tab Content -->
            
            <!-- Personal Information Tab -->
            <?php if ($activeTab === 'personal'): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label for="first_name">First Name <span style="color: red;">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name <span style="color: red;">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label for="email">Email Address <span style="color: red;">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
                                <small style="color: #666;">Contact admin to change your email</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number <span style="color: red;">*</span></label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Street Address</label>
                            <input type="text" id="address" name="address" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['address']); ?>">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label for="suburb">Suburb</label>
                                <input type="text" id="suburb" name="suburb" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['suburb']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="postcode">Postcode</label>
                                <input type="text" id="postcode" name="postcode" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['postcode']); ?>" maxlength="4">
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e0e0e0;">
                            <button type="submit" name="update_personal" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Security Tab -->
            <?php if ($activeTab === 'security'): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-lock"></i> Change Password</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" style="margin-bottom: 2rem;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Password Requirements:</strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem;">
                            <li>Minimum 8 characters</li>
                            <li>Include letters and numbers for better security</li>
                            <li>Avoid using personal information</li>
                        </ul>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Current Password <span style="color: red;">*</span></label>
                            <input type="password" id="current_password" name="current_password" class="form-control" 
                                   minlength="8" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password <span style="color: red;">*</span></label>
                            <input type="password" id="new_password" name="new_password" class="form-control" 
                                   minlength="8" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password <span style="color: red;">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   minlength="8" required>
                        </div>
                        
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e0e0e0;">
                            <button type="submit" name="change_password" class="btn btn-primary btn-lg">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3><i class="fas fa-shield-alt"></i> Login History</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                        <i class="fas fa-clock" style="font-size: 2rem; color: #4e7e95;"></i>
                        <div>
                            <strong>Last Login:</strong>
                            <div style="color: #666;">
                                <?php echo $student['last_login'] ? date('F j, Y \a\t g:i A', strtotime($student['last_login'])) : 'Never'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Emergency Contact Tab -->
            <?php if ($activeTab === 'emergency'): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-phone-alt"></i> Emergency Contact Information</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning" style="margin-bottom: 2rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Important:</strong> This contact will be notified in case of emergencies during your lessons.
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="emergency_contact_name">Contact Name <span style="color: red;">*</span></label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['emergency_contact_name']); ?>" 
                                   placeholder="Full name of emergency contact" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_phone">Contact Phone Number <span style="color: red;">*</span></label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['emergency_contact_phone']); ?>" 
                                   placeholder="Mobile number" required>
                        </div>
                        
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e0e0e0;">
                            <button type="submit" name="update_emergency" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Update Emergency Contact
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
           <!-- Medical Information Tab -->
<?php if ($activeTab === 'medical'): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-notes-medical"></i> Medical Information</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info" style="margin-bottom: 2rem;">
            <i class="fas fa-info-circle"></i>
            <strong>Privacy Notice:</strong> This information is kept confidential and only shared with your instructor for safety purposes.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="medical_conditions">Medical Conditions or Special Requirements</label>
                <textarea id="medical_conditions" name="medical_conditions" class="form-control" rows="6" 
                          placeholder="List any medical conditions, allergies, or special requirements your instructor should be aware of (e.g., epilepsy, diabetes, physical limitations, etc.)"><?php echo htmlspecialchars($student['medical_conditions'] ?? ''); ?></textarea>
                <small style="color: #666;">Leave blank if you have no medical conditions to report</small>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e0e0e0;">
                <button type="submit" name="update_medical" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Update Medical Information
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
            
            <!-- Account Settings Tab -->
            <?php if ($activeTab === 'account'): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Account Settings</h3>
                </div>
                <div class="card-body">
                    <h4 style="margin-bottom: 1rem; color: #4e7e95;">Account Information</h4>
                    
                    <div style="display: grid; gap: 1rem; margin-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                            <div>
                                <strong>Account Status:</strong>
                                <div style="margin-top: 0.25rem;">
                                    <span class="badge badge-<?php echo $student['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                            <div>
                                <strong>Student ID:</strong>
                                <div style="color: #666; margin-top: 0.25rem;">
                                    #<?php echo str_pad($student['student_id'], 6, '0', STR_PAD_LEFT); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                            <div>
                                <strong>License Status:</strong>
                                <div style="margin-top: 0.25rem;">
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($student['license_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                            <div>
                                <strong>Branch Location:</strong>
                                <div style="color: #666; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($student['branch_name']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 1.5rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; margin-top: 2rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #856404;">
                            <i class="fas fa-exclamation-triangle"></i> Need Help?
                        </h4>
                        <p style="margin: 0; color: #856404;">
                            If you need to change your email, transfer branches, or have other account issues, 
                            please contact our support team at <strong>support@origindrivingschool.com.au</strong> 
                            or call <strong>1300-ORIGIN</strong>.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<style>
@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.tabs-nav {
    border: 1px solid #e0e0e0;
}

.tab-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    transition: all 0.3s ease;
}

.tab-link:hover {
    background: #f8f9fa;
    color: #4e7e95;
}

.tab-link.active {
    background: #4e7e95;
    color: white;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #4e7e95;
    box-shadow: 0 0 0 3px rgba(78, 126, 149, 0.1);
}

.form-control:disabled {
    background: #f8f9fa;
    cursor: not-allowed;
}

.btn-lg {
    padding: 1rem 2.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-success {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.badge-danger {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.2);
}

.badge-info {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border: 1px solid rgba(52, 152, 219, 0.2);
}
</style>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== newPassword.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        newPassword.addEventListener('input', function() {
            if (confirmPassword.value && confirmPassword.value !== this.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    }
});
</script>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>