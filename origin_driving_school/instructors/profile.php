<?php
/**
 * Instructor Profile - View and Update Profile
 * 
 * File path: instructor/profile.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

// Require instructor login
requireLogin();
requireRole(['instructor']);

// Include required models
require_once APP_PATH . '/models/Student.php';
require_once APP_PATH . '/models/User.php';

// Initialize models
$studentModel = new Student();
$userModel = new User();

// Get instructor details
$userId = $_SESSION['user_id'];

// Get full instructor profile
$instructorSql = "SELECT i.*, 
                  u.first_name, u.last_name, u.email, u.phone, u.is_active, u.created_at,
                  b.branch_name, b.address as branch_address, b.phone as branch_phone
                  FROM instructors i
                  INNER JOIN users u ON i.user_id = u.user_id
                  LEFT JOIN branches b ON i.branch_id = b.branch_id
                  WHERE u.user_id = ?";
$instructor = $studentModel->customQueryOne($instructorSql, [$userId]);

if (!$instructor) {
    setFlashMessage('error', 'Instructor profile not found');
    redirect('/dashboard.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = sanitize($_POST['phone']);
    $bio = sanitize($_POST['bio']);
    $specialization = sanitize($_POST['specialization']);
    
    // Update user phone
    $updateUserSql = "UPDATE users SET phone = ? WHERE user_id = ?";
    $userModel->customQuery($updateUserSql, [$phone, $userId]);
    
    // Update instructor details
    $updateInstructorSql = "UPDATE instructors SET bio = ?, specialization = ? WHERE instructor_id = ?";
    $studentModel->customQuery($updateInstructorSql, [$bio, $specialization, $instructor['instructor_id']]);
    
    setFlashMessage('success', 'Profile updated successfully');
    redirect('/instructor/profile.php');
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Get current user
    $user = $userModel->getById($userId);
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        setFlashMessage('error', 'Current password is incorrect');
    } elseif ($newPassword !== $confirmPassword) {
        setFlashMessage('error', 'New passwords do not match');
    } elseif (strlen($newPassword) < 8) {
        setFlashMessage('error', 'Password must be at least 8 characters');
    } else {
        // Update password
        $userModel->updatePassword($userId, $newPassword);
        setFlashMessage('success', 'Password changed successfully');
    }
    redirect('/instructor/profile.php');
}

// Get instructor statistics
$stats = [];
$stats['total_lessons'] = $studentModel->customQueryOne(
    "SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ?", 
    [$instructor['instructor_id']]
)['count'] ?? 0;

$stats['completed_lessons'] = $studentModel->customQueryOne(
    "SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ? AND status = 'completed'", 
    [$instructor['instructor_id']]
)['count'] ?? 0;

$stats['total_students'] = $studentModel->customQueryOne(
    "SELECT COUNT(DISTINCT student_id) as count FROM students WHERE assigned_instructor_id = ?", 
    [$instructor['instructor_id']]
)['count'] ?? 0;

$stats['avg_rating'] = $studentModel->customQueryOne(
    "SELECT AVG(student_performance_rating) as avg FROM lessons 
     WHERE instructor_id = ? AND student_performance_rating IS NOT NULL", 
    [$instructor['instructor_id']]
)['avg'] ?? 0;

// Set page title
$pageTitle = 'My Profile';

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h2><i class="fas fa-user"></i> My Profile</h2>
                <p style="color: #666; margin-top: 0.3rem;">
                    View and manage your instructor profile
                </p>
            </div>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            
            <?php 
            $flashMessage = getFlashMessage();
            if ($flashMessage): 
            ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?>">
                    <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo $flashMessage['message']; ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Profile Header -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-body" style="padding: 2rem;">
                    <div style="display: grid; grid-template-columns: auto 1fr; gap: 2rem; align-items: center;">
                        
                        <!-- Profile Avatar -->
                        <div style="text-align: center;">
                            <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #4e7e95 0%, #e78759 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                <?php echo strtoupper(substr($instructor['first_name'], 0, 1) . substr($instructor['last_name'], 0, 1)); ?>
                            </div>
                            <div style="margin-top: 1rem;">
                                <span class="badge <?php echo $instructor['is_available'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $instructor['is_available'] ? 'Available' : 'Unavailable'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Profile Info -->
                        <div>
                            <h2 style="margin: 0 0 0.5rem 0; color: #4e7e95;">
                                <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                            </h2>
                            <p style="margin: 0 0 1rem 0; color: #666; font-size: 1.1rem;">
                                <i class="fas fa-chalkboard-teacher"></i> Driving Instructor
                            </p>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
                                <div style="padding: 1rem; background-color: rgba(78, 126, 149, 0.1); border-radius: 8px; text-align: center;">
                                    <div style="font-size: 1.8rem; font-weight: bold; color: #4e7e95;">
                                        <?php echo $stats['total_lessons']; ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #666;">Total Lessons</div>
                                </div>
                                <div style="padding: 1rem; background-color: rgba(40, 167, 69, 0.1); border-radius: 8px; text-align: center;">
                                    <div style="font-size: 1.8rem; font-weight: bold; color: #28a745;">
                                        <?php echo $stats['completed_lessons']; ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #666;">Completed</div>
                                </div>
                                <div style="padding: 1rem; background-color: rgba(231, 135, 89, 0.1); border-radius: 8px; text-align: center;">
                                    <div style="font-size: 1.8rem; font-weight: bold; color: #e78759;">
                                        <?php echo $stats['total_students']; ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #666;">Students</div>
                                </div>
                                <div style="padding: 1rem; background-color: rgba(255, 193, 7, 0.1); border-radius: 8px; text-align: center;">
                                    <div style="font-size: 1.8rem; font-weight: bold; color: #ffc107;">
                                        <?php echo number_format($stats['avg_rating'], 1); ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #666;">Avg Rating</div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                
                <!-- Left Column -->
                <div>
                    <!-- Personal Information -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-user-edit"></i> Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($instructor['first_name']); ?>" readonly>
                                    <small class="form-text">Contact admin to change your name</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($instructor['last_name']); ?>" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($instructor['email']); ?>" readonly>
                                    <small class="form-text">Contact admin to change your email</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Phone <span class="required">*</span></label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($instructor['phone']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Specialization</label>
                                    <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($instructor['specialization'] ?? ''); ?>" placeholder="e.g., Highway Driving, Parallel Parking">
                                </div>
                                
                                <div class="form-group">
                                    <label>Bio</label>
                                    <textarea name="bio" class="form-control" rows="4" placeholder="Tell students about yourself..."><?php echo htmlspecialchars($instructor['bio'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>Current Password <span class="required">*</span></label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>New Password <span class="required">*</span></label>
                                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                                    <small class="form-text">Minimum 8 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password <span class="required">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Instructor Details -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-id-card"></i> Instructor Details</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                
                                <?php if ($instructor['certificate_number']): ?>
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        <i class="fas fa-certificate" style="width: 20px;"></i> Certificate Number
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($instructor['certificate_number']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($instructor['adta_membership']): ?>
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        <i class="fas fa-id-badge" style="width: 20px;"></i> ADTA Membership
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($instructor['adta_membership']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($instructor['wwc_card_number']): ?>
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        <i class="fas fa-id-card" style="width: 20px;"></i> WWC Card Number
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($instructor['wwc_card_number']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($instructor['license_expiry']): ?>
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        <i class="fas fa-calendar-alt" style="width: 20px;"></i> License Expiry
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo date('d/m/Y', strtotime($instructor['license_expiry'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($instructor['date_joined']): ?>
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        <i class="fas fa-calendar-check" style="width: 20px;"></i> Date Joined
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo date('d/m/Y', strtotime($instructor['date_joined'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($instructor['hourly_rate']): ?>
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        <i class="fas fa-dollar-sign" style="width: 20px;"></i> Hourly Rate
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo formatCurrency($instructor['hourly_rate']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    </div>
                    
                    <!-- Branch Information -->
                    <?php if ($instructor['branch_name']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-building"></i> Branch Information</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        Branch Name
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($instructor['branch_name']); ?>
                                    </div>
                                </div>
                                
                                <?php if ($instructor['branch_address']): ?>
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        Address
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($instructor['branch_address']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($instructor['branch_phone']): ?>
                                <div>
                                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                                        Phone
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($instructor['branch_phone']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>