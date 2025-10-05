<?php
/**
 * Edit Student
 * 
 * File path: students/edit.php
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

requireLogin();
requireRole(['admin', 'staff']);

require_once APP_PATH . '/models/Student.php';
$studentModel = new Student();

$pageTitle = 'Edit Student';
$errors = [];

// Get student ID
$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($studentId <= 0) {
    setFlashMessage('error', 'Invalid student ID');
    redirect('/students/index.php');
}

// Get student data
$studentSql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.is_active
               FROM students s
               INNER JOIN users u ON s.user_id = u.user_id
               WHERE s.student_id = ?";
$student = $studentModel->customQueryOne($studentSql, [$studentId]);

if (!$student) {
    setFlashMessage('error', 'Student not found');
    redirect('/students/index.php');
}

// Get branches and instructors for dropdowns
$branches = $studentModel->customQuery("SELECT * FROM branches WHERE is_active = 1 ORDER BY branch_name");
$instructors = $studentModel->customQuery("
    SELECT i.instructor_id, CONCAT(u.first_name, ' ', u.last_name) as name 
    FROM instructors i 
    INNER JOIN users u ON i.user_id = u.user_id 
    WHERE u.is_active = 1 
    ORDER BY u.first_name, u.last_name
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    // Check if email already exists for another user
    $existingUser = $studentModel->customQueryOne(
        "SELECT user_id FROM users WHERE email = ? AND user_id != ?", 
        [$email, $student['user_id']]
    );
    
    if ($existingUser) {
        $errors[] = 'Email address already exists for another user';
    }
    
    if (empty($firstName) || empty($lastName) || empty($phone)) {
        $errors[] = 'All required fields must be filled';
    }
    
    if (empty($errors)) {
        $studentModel->db->query("START TRANSACTION");
        
        try {
            // Update user account
            $userSql = "UPDATE users SET 
                        first_name = ?, last_name = ?, email = ?, phone = ?, is_active = ?
                        WHERE user_id = ?";
            
            $userUpdate = $studentModel->db->update($userSql, [
                $firstName,
                $lastName,
                $email,
                $phone,
                isset($_POST['is_active']) ? 1 : 0,
                $student['user_id']
            ]);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) >= 8) {
                    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $studentModel->db->update(
                        "UPDATE users SET password_hash = ? WHERE user_id = ?",
                        [$passwordHash, $student['user_id']]
                    );
                } else {
                    throw new Exception('Password must be at least 8 characters');
                }
            }
            
            // Update student profile
            $studentSql = "UPDATE students SET 
                          license_number = ?, license_status = ?, date_of_birth = ?,
                          address = ?, suburb = ?, postcode = ?,
                          emergency_contact_name = ?, emergency_contact_phone = ?,
                          medical_conditions = ?, branch_id = ?, assigned_instructor_id = ?,
                          test_ready = ?, notes = ?
                          WHERE student_id = ?";
            
            $studentUpdate = $studentModel->db->update($studentSql, [
                sanitize($_POST['license_number']),
                sanitize($_POST['license_status']),
                sanitize($_POST['date_of_birth']),
                sanitize($_POST['address']),
                sanitize($_POST['suburb']),
                sanitize($_POST['postcode']),
                sanitize($_POST['emergency_contact_name']),
                sanitize($_POST['emergency_contact_phone']),
                sanitize($_POST['medical_conditions']),
                intval($_POST['branch_id']),
                !empty($_POST['assigned_instructor_id']) ? intval($_POST['assigned_instructor_id']) : null,
                isset($_POST['test_ready']) ? 1 : 0,
                sanitize($_POST['notes']),
                $studentId
            ]);
            
            $studentModel->db->query("COMMIT");
            
            setFlashMessage('success', 'Student updated successfully');
            redirect('/students/view.php?id=' . $studentId);
            
        } catch (Exception $e) {
            $studentModel->db->query("ROLLBACK");
            $errors[] = 'Error updating student: ' . $e->getMessage();
        }
    }
}

include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h1><i class="fas fa-user-edit"></i> Edit Student</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="content-area">
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
            
            <div class="card">
                <div class="card-header" style="background-color: #4e7e95; color: white; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;">
                        <i class="fas fa-user-edit"></i> 
                        Edit: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                    </h3>
                    <a href="view.php?id=<?php echo $studentId; ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-eye"></i> View Profile
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        
                        <h4 style="border-bottom: 2px solid #4e7e95; padding-bottom: 0.5rem; margin-bottom: 1rem; color: #4e7e95;">
                            <i class="fas fa-user"></i> Personal Details
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>First Name <span style="color: red;">*</span></label>
                                <input type="text" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Last Name <span style="color: red;">*</span></label>
                                <input type="text" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Email Address <span style="color: red;">*</span></label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number <span style="color: red;">*</span></label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Change Password</label>
                                <input type="password" name="password" class="form-control" minlength="8">
                                <small style="color: #666;">Leave blank to keep current password</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Date of Birth <span style="color: red;">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['date_of_birth']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?php echo $student['is_active'] ? 'checked' : ''; ?>>
                                <span>Account is Active</span>
                            </label>
                        </div>
                        
                        <h4 style="border-bottom: 2px solid #4e7e95; padding-bottom: 0.5rem; margin: 2rem 0 1rem 0; color: #4e7e95;">
                            <i class="fas fa-home"></i> Address Information
                        </h4>
                        
                        <div class="form-group">
                            <label>Street Address <span style="color: red;">*</span></label>
                            <input type="text" name="address" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['address']); ?>" required>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Suburb <span style="color: red;">*</span></label>
                                <input type="text" name="suburb" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['suburb']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Postcode <span style="color: red;">*</span></label>
                                <input type="text" name="postcode" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['postcode']); ?>" required>
                            </div>
                        </div>
                        
                        <h4 style="border-bottom: 2px solid #4e7e95; padding-bottom: 0.5rem; margin: 2rem 0 1rem 0; color: #4e7e95;">
                            <i class="fas fa-id-card"></i> License & Enrollment Details
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>License Number</label>
                                <input type="text" name="license_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['license_number']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>License Status <span style="color: red;">*</span></label>
                                <select name="license_status" class="form-control" required>
                                    <option value="none" <?php echo $student['license_status'] === 'none' ? 'selected' : ''; ?>>None</option>
                                    <option value="learner" <?php echo $student['license_status'] === 'learner' ? 'selected' : ''; ?>>Learner's Permit</option>
                                    <option value="probationary" <?php echo $student['license_status'] === 'probationary' ? 'selected' : ''; ?>>Probationary</option>
                                    <option value="full" <?php echo $student['license_status'] === 'full' ? 'selected' : ''; ?>>Full License</option>
                                    <option value="overseas" <?php echo $student['license_status'] === 'overseas' ? 'selected' : ''; ?>>Overseas License</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Branch <span style="color: red;">*</span></label>
                                <select name="branch_id" class="form-control" required>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>" 
                                                <?php echo $student['branch_id'] == $branch['branch_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Assigned Instructor</label>
                                <select name="assigned_instructor_id" class="form-control">
                                    <option value="">-- Not Assigned --</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?php echo $instructor['instructor_id']; ?>" 
                                                <?php echo $student['assigned_instructor_id'] == $instructor['instructor_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($instructor['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="test_ready" value="1" 
                                       <?php echo $student['test_ready'] ? 'checked' : ''; ?>>
                                <span><strong>Student is Test Ready</strong></span>
                            </label>
                        </div>
                        
                        <h4 style="border-bottom: 2px solid #4e7e95; padding-bottom: 0.5rem; margin: 2rem 0 1rem 0; color: #4e7e95;">
                            <i class="fas fa-phone-square"></i> Emergency Contact
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Emergency Contact Name <span style="color: red;">*</span></label>
                                <input type="text" name="emergency_contact_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['emergency_contact_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Emergency Contact Phone <span style="color: red;">*</span></label>
                                <input type="tel" name="emergency_contact_phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['emergency_contact_phone']); ?>" required>
                            </div>
                        </div>
                        
                        <h4 style="border-bottom: 2px solid #4e7e95; padding-bottom: 0.5rem; margin: 2rem 0 1rem 0; color: #4e7e95;">
                            <i class="fas fa-notes-medical"></i> Additional Information
                        </h4>
                        
                        <div class="form-group">
                            <label>Medical Conditions or Special Requirements</label>
                            <textarea name="medical_conditions" class="form-control" rows="3"><?php echo htmlspecialchars($student['medical_conditions']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($student['notes']); ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e5edf0;">
                            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="view.php?id=<?php echo $studentId; ?>" class="btn btn-secondary" style="padding: 0.75rem 2rem;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="delete.php?id=<?php echo $studentId; ?>" 
                               class="btn btn-danger" 
                               style="padding: 0.75rem 2rem; margin-left: auto;"
                               onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone and will delete all associated records.');">
                                <i class="fas fa-trash"></i> Delete Student
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>