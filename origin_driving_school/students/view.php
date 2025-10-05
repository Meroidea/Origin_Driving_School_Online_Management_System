<?php
/**
 * VIEW STUDENT DETAILS
 * File path: students/view.php
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once APP_PATH . '/models/Student.php';
$studentModel = new Student();
$pageTitle = 'Student Details';

$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($studentId <= 0) {
    setFlashMessage('error', 'Invalid student ID');
    redirect('/students/index.php');
}

// Get student with all related data
$sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.is_active, u.last_login,
        b.branch_name, b.address as branch_address,
        CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
        COUNT(DISTINCT l.lesson_id) as total_lessons,
        COUNT(DISTINCT CASE WHEN l.status = 'completed' THEN l.lesson_id END) as completed_lessons,
        COUNT(DISTINCT i.invoice_id) as total_invoices,
        COALESCE(SUM(i.balance_due), 0) as outstanding_balance
        FROM students s
        INNER JOIN users u ON s.user_id = u.user_id
        INNER JOIN branches b ON s.branch_id = b.branch_id
        LEFT JOIN instructors inst ON s.assigned_instructor_id = inst.instructor_id
        LEFT JOIN users iu ON inst.user_id = iu.user_id
        LEFT JOIN lessons l ON s.student_id = l.student_id
        LEFT JOIN invoices i ON s.student_id = i.student_id
        WHERE s.student_id = ?
        GROUP BY s.student_id";

$student = $studentModel->customQueryOne($sql, [$studentId]);

if (!$student) {
    setFlashMessage('error', 'Student not found');
    redirect('/students/index.php');
}

// Get recent lessons
$recentLessons = $studentModel->customQuery("
    SELECT l.*, CONCAT(iu.first_name, ' ', iu.last_name) as instructor_name
    FROM lessons l
    INNER JOIN instructors i ON l.instructor_id = i.instructor_id
    INNER JOIN users iu ON i.user_id = iu.user_id
    WHERE l.student_id = ?
    ORDER BY l.lesson_date DESC, l.start_time DESC
    LIMIT 5
", [$studentId]);

include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h1><i class="fas fa-user"></i> Student Profile</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
        
        <div class="content-area">
            
            <!-- Action Bar -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body" style="padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0;">
                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                        <?php if ($student['test_ready']): ?>
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Test Ready</span>
                        <?php endif; ?>
                        <?php if (!$student['is_active']): ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="edit.php?id=<?php echo $studentId; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $student['total_lessons']; ?></h3>
                        <p>Total Lessons</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $student['completed_lessons']; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $student['total_invoices']; ?></h3>
                        <p>Invoices</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: <?php echo $student['outstanding_balance'] > 0 ? 'rgba(220, 53, 69, 0.1)' : 'rgba(40, 167, 69, 0.1)'; ?>; color: <?php echo $student['outstanding_balance'] > 0 ? '#dc3545' : '#28a745'; ?>;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo CURRENCY_SYMBOL . number_format($student['outstanding_balance'], 2); ?></h3>
                        <p>Outstanding Balance</p>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                
                <!-- Main Details -->
                <div>
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div>
                                    <strong style="color: #666;">Email:</strong><br>
                                    <span><?php echo htmlspecialchars($student['email']); ?></span>
                                </div>
                                <div>
                                    <strong style="color: #666;">Phone:</strong><br>
                                    <span><?php echo htmlspecialchars($student['phone']); ?></span>
                                </div>
                                <div>
                                    <strong style="color: #666;">Date of Birth:</strong><br>
                                    <span><?php echo date('d/m/Y', strtotime($student['date_of_birth'])); ?></span>
                                </div>
                                <div>
                                    <strong style="color: #666;">Age:</strong><br>
                                    <span><?php echo date_diff(date_create($student['date_of_birth']), date_create('today'))->y; ?> years</span>
                                </div>
                                <div style="grid-column: span 2;">
                                    <strong style="color: #666;">Address:</strong><br>
                                    <span><?php echo htmlspecialchars($student['address'] . ', ' . $student['suburb'] . ', ' . $student['postcode']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-id-card"></i> License & Enrollment</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div>
                                    <strong style="color: #666;">License Number:</strong><br>
                                    <span><?php echo $student['license_number'] ? htmlspecialchars($student['license_number']) : '<em>Not provided</em>'; ?></span>
                                </div>
                                <div>
                                    <strong style="color: #666;">License Status:</strong><br>
                                    <span class="badge badge-info"><?php echo ucfirst($student['license_status']); ?></span>
                                </div>
                                <div>
                                    <strong style="color: #666;">Enrollment Date:</strong><br>
                                    <span><?php echo date('d/m/Y', strtotime($student['enrollment_date'])); ?></span>
                                </div>
                                <div>
                                    <strong style="color: #666;">Member Since:</strong><br>
                                    <span><?php echo date_diff(date_create($student['enrollment_date']), date_create('today'))->days; ?> days</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Lessons -->
                    <div class="card">
                        <div class="card-header" style="display: flex; justify-content: space-between;">
                            <h3><i class="fas fa-history"></i> Recent Lessons</h3>
                            <a href="<?php echo APP_URL; ?>/lessons/index.php?student=<?php echo $studentId; ?>" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentLessons)): ?>
                                <p style="text-align: center; color: #999; padding: 2rem;">No lessons recorded yet</p>
                            <?php else: ?>
                                <?php foreach ($recentLessons as $lesson): ?>
                                    <div style="padding: 1rem; border-bottom: 1px solid #e5edf0;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <div>
                                                <strong><?php echo date('d/m/Y', strtotime($lesson['lesson_date'])); ?></strong> at 
                                                <?php echo date('H:i', strtotime($lesson['start_time'])); ?>
                                            </div>
                                            <span class="badge badge-<?php echo $lesson['status'] === 'completed' ? 'success' : ($lesson['status'] === 'cancelled' ? 'danger' : 'primary'); ?>">
                                                <?php echo ucfirst($lesson['status']); ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 0.9rem; color: #666; margin-top: 0.25rem;">
                                            Instructor: <?php echo htmlspecialchars($lesson['instructor_name']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar Info -->
                <div>
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header" style="background-color: #4e7e95; color: white;">
                            <h3 style="margin: 0;"><i class="fas fa-building"></i> Branch & Instructor</h3>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 1.5rem;">
                                <strong style="color: #666;">Branch:</strong><br>
                                <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($student['branch_name']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($student['branch_address']); ?></small>
                            </div>
                            <div>
                                <strong style="color: #666;">Assigned Instructor:</strong><br>
                                <strong style="font-size: 1.1rem;">
                                    <?php echo $student['instructor_name'] ? htmlspecialchars($student['instructor_name']) : '<em>Not assigned yet</em>'; ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-phone-square"></i> Emergency Contact</h3>
                        </div>
                        <div class="card-body">
                            <strong><?php echo htmlspecialchars($student['emergency_contact_name']); ?></strong><br>
                            <span style="color: #666;"><?php echo htmlspecialchars($student['emergency_contact_phone']); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($student['medical_conditions'])): ?>
                        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid #ffc107;">
                            <div class="card-header" style="background-color: #fff3cd;">
                                <h3 style="margin: 0; color: #856404;"><i class="fas fa-notes-medical"></i> Medical Info</h3>
                            </div>
                            <div class="card-body" style="background-color: #fff3cd;">
                                <p style="margin: 0; color: #856404;"><?php echo nl2br(htmlspecialchars($student['medical_conditions'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($student['notes'])): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-sticky-note"></i> Notes</h3>
                            </div>
                            <div class="card-body">
                                <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($student['notes'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card" style="margin-top: 1.5rem;">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Account Status</h3>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 0.75rem;">
                                <strong>Status:</strong>
                                <span class="badge badge-<?php echo $student['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <div>
                                <strong>Last Login:</strong><br>
                                <?php echo $student['last_login'] ? date('d/m/Y H:i', strtotime($student['last_login'])) : '<em>Never</em>'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>