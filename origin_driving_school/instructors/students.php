<?php
/**
 * Instructor Students - View My Assigned Students
 * 
 * File path: instructor/students.php
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

// Define APP_ACCESS constant
define('APP_ACCESS', true);

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is an instructor
requireLogin();
requireRole(['instructor']);

// Include required models
require_once APP_PATH . '/models/Student.php';

// Initialize models
$studentModel = new Student();

// Set page title
$pageTitle = 'My Students';

// Get instructor ID from user session
$userId = $_SESSION['user_id'];

// Get instructor details
$instructorSql = "SELECT i.* FROM instructors i 
                  INNER JOIN users u ON i.user_id = u.user_id 
                  WHERE u.user_id = ?";
$instructor = $studentModel->customQueryOne($instructorSql, [$userId]);

if (!$instructor) {
    setFlashMessage('error', 'Instructor profile not found');
    redirect('/dashboard.php');
}

$instructorId = $instructor['instructor_id'];

// Get all students assigned to this instructor
$studentsSql = "SELECT s.*, 
                u.first_name, u.last_name, u.email, u.phone, u.is_active,
                COUNT(DISTINCT l.lesson_id) as total_lessons,
                COUNT(DISTINCT CASE WHEN l.status = 'completed' THEN l.lesson_id END) as completed_lessons,
                MAX(l.lesson_date) as last_lesson_date
                FROM students s
                INNER JOIN users u ON s.user_id = u.user_id
                LEFT JOIN lessons l ON s.student_id = l.student_id AND l.instructor_id = ?
                WHERE s.assigned_instructor_id = ?
                GROUP BY s.student_id
                ORDER BY u.first_name, u.last_name";

$students = $studentModel->customQuery($studentsSql, [$instructorId, $instructorId]);

// Get statistics
$totalStudents = count($students);
$activeStudents = count(array_filter($students, function($s) { return $s['is_active'] == 1; }));
$testReadyStudents = count(array_filter($students, function($s) { return $s['test_ready'] == 1; }));

// Calculate total lessons given
$totalLessons = array_sum(array_column($students, 'total_lessons'));

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1><i class="fas fa-users"></i> My Students</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalStudents; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $activeStudents; ?></h3>
                        <p>Active Students</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $testReadyStudents; ?></h3>
                        <p>Test Ready</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalLessons; ?></h3>
                        <p>Total Lessons Given</p>
                    </div>
                </div>
            </div>
            
            <!-- Students Grid -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> My Assigned Students (<?php echo $totalStudents; ?>)</h3>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($students)): ?>
                        <div class="table-empty">
                            <i class="fas fa-users"></i>
                            <p>No students assigned yet</p>
                            <p style="color: #666;">Students will appear here once they are assigned to you by the admin.</p>
                        </div>
                    <?php else: ?>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($students as $student): ?>
                                <div class="card" style="margin: 0; border: 2px solid <?php echo $student['is_active'] ? '#28a745' : '#dc3545'; ?>;">
                                    
                                    <!-- Student Header -->
                                    <div class="card-header" style="background-color: rgba(78, 126, 149, 0.1); display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin: 0; color: #4e7e95;">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                            </h4>
                                        </div>
                                        <?php if ($student['test_ready']): ?>
                                            <span class="badge badge-success" title="Test Ready">
                                                <i class="fas fa-check-circle"></i> Test Ready
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Student Details -->
                                    <div class="card-body">
                                        <div style="margin-bottom: 1rem;">
                                            <div style="display: grid; gap: 0.5rem; font-size: 0.9rem; color: #666;">
                                                <div>
                                                    <i class="fas fa-envelope" style="width: 20px; color: #4e7e95;"></i>
                                                    <?php echo htmlspecialchars($student['email']); ?>
                                                </div>
                                                <div>
                                                    <i class="fas fa-phone" style="width: 20px; color: #4e7e95;"></i>
                                                    <?php echo htmlspecialchars($student['phone']); ?>
                                                </div>
                                                <div>
                                                    <i class="fas fa-id-card" style="width: 20px; color: #4e7e95;"></i>
                                                    License: <strong><?php echo ucfirst($student['license_status']); ?></strong>
                                                </div>
                                                <div>
                                                    <i class="fas fa-calendar" style="width: 20px; color: #4e7e95;"></i>
                                                    Enrolled: <?php echo date('d/m/Y', strtotime($student['enrollment_date'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Stats -->
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5edf0;">
                                            <div style="text-align: center; padding: 0.75rem; background-color: rgba(78, 126, 149, 0.05); border-radius: 5px;">
                                                <div style="font-size: 1.5rem; font-weight: bold; color: #4e7e95;">
                                                    <?php echo $student['total_lessons']; ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: #666;">Total Lessons</div>
                                            </div>
                                            <div style="text-align: center; padding: 0.75rem; background-color: rgba(40, 167, 69, 0.05); border-radius: 5px;">
                                                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                                                    <?php echo $student['completed_lessons']; ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: #666;">Completed</div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($student['last_lesson_date']): ?>
                                            <div style="margin-top: 1rem; padding: 0.5rem; background-color: #f8f9fa; border-radius: 5px; text-align: center; font-size: 0.85rem; color: #666;">
                                                <i class="fas fa-clock"></i>
                                                Last lesson: <?php echo date('d/m/Y', strtotime($student['last_lesson_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Student Notes (if any) -->
                                        <?php if (!empty($student['notes'])): ?>
                                            <div style="margin-top: 1rem; padding: 0.75rem; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">
                                                <div style="font-weight: bold; color: #856404; margin-bottom: 0.25rem;">
                                                    <i class="fas fa-sticky-note"></i> Notes:
                                                </div>
                                                <div style="font-size: 0.85rem; color: #856404;">
                                                    <?php echo htmlspecialchars(substr($student['notes'], 0, 100)) . (strlen($student['notes']) > 100 ? '...' : ''); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="card-footer" style="display: flex; gap: 0.5rem; padding: 1rem;">
                                        <a href="<?php echo APP_URL; ?>/students/view.php?id=<?php echo $student['student_id']; ?>" 
                                           class="btn btn-info btn-sm" style="flex: 1;">
                                            <i class="fas fa-eye"></i> View Profile
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/lessons/index.php?student=<?php echo $student['student_id']; ?>" 
                                           class="btn btn-primary btn-sm" style="flex: 1;">
                                            <i class="fas fa-calendar"></i> Lessons
                                        </a>
                                    </div>
                                    
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                    <?php endif; ?>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>