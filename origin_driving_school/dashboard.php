<?php
/**
 * Dashboard Page - Origin Driving School Management System
 * 
 * Main dashboard with role-based views
 * Created for DWIN309 Final Assessment at Kent Institute Australia
 * 
 * dashboard.php -> this is the main dashboard page
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once 'config/config.php';
require_once 'app/models/User.php';
require_once 'app/models/Student.php';
require_once 'app/models/Instructor.php';
require_once 'app/models/Lesson.php';
require_once 'app/models/Invoice.php';

// Require login
requireLogin();

// Initialize Database instance for notifications
$db = Database::getInstance();

$userModel = new User();
$userRole = getCurrentUserRole();
$userId = getCurrentUserId();
$userName = $_SESSION['user_name'];

// Get dashboard statistics based on role
$stats = [];
$upcomingLessons = [];
$recentStudents = [];
$pendingInvoices = [];

if ($userRole === 'admin' || $userRole === 'staff') {
    $studentModel = new Student();
    $instructorModel = new Instructor();
    $lessonModel = new Lesson();
    $invoiceModel = new Invoice();
    
    $stats = [
        'total_students' => $studentModel->count(['is_active' => 1]),
        'total_instructors' => $instructorModel->count(['is_available' => 1]),
        'upcoming_lessons' => $lessonModel->getUpcomingLessonsCount(),
        'pending_invoices' => $invoiceModel->count(['status' => 'unpaid'])
    ];
    
    $recentStudents = $studentModel->getRecentStudents(5);
    $upcomingLessons = $lessonModel->getUpcomingLessons(10);
    
} elseif ($userRole === 'instructor') {
    $instructorModel = new Instructor();
    $lessonModel = new Lesson();
    $instructor = $instructorModel->getByUserId($userId);
    
    if ($instructor && isset($instructor['instructor_id'])) {
        $stats = [
            'todays_lessons' => $lessonModel->getTodaysLessonsCountForInstructor($instructor['instructor_id']),
            'upcoming_lessons' => $lessonModel->getUpcomingLessonsCountForInstructor($instructor['instructor_id']),
            'total_students' => $lessonModel->getTotalStudentsForInstructor($instructor['instructor_id']),
            'completed_this_month' => $lessonModel->getCompletedLessonsThisMonthForInstructor($instructor['instructor_id'])
        ];
        
        $upcomingLessons = $lessonModel->getInstructorUpcomingLessons($instructor['instructor_id'], 10);
    } else {
        // Instructor record not found - set default values
        $stats = [
            'todays_lessons' => 0,
            'upcoming_lessons' => 0,
            'total_students' => 0,
            'completed_this_month' => 0
        ];
        
        setFlashMessage('warning', 'Your instructor profile is not set up yet. Please contact the administrator.');
    }
    
} elseif ($userRole === 'student') {
    $studentModel = new Student();
    $lessonModel = new Lesson();
    $invoiceModel = new Invoice();
    $student = $studentModel->getByUserId($userId);
    
    if ($student && isset($student['student_id'])) {
        $stats = [
            'upcoming_lessons' => $lessonModel->getUpcomingLessonsCountForStudent($student['student_id']),
            'completed_lessons' => $student['total_lessons_completed'] ?? 0,
            'pending_payments' => $invoiceModel->getTotalPendingForStudent($student['student_id']),
            'test_ready' => $student['test_ready'] ?? 0
        ];
        
        $upcomingLessons = $lessonModel->getStudentUpcomingLessons($student['student_id'], 5);
        $pendingInvoices = $invoiceModel->getStudentPendingInvoices($student['student_id']);
    } else {
        // Student record not found - set default values
        $stats = [
            'upcoming_lessons' => 0,
            'completed_lessons' => 0,
            'pending_payments' => 0,
            'test_ready' => 0
        ];
        
        setFlashMessage('warning', 'Your student profile is not set up yet. Please contact the administrator.');
    }
}

$pageTitle = 'Dashboard';
include 'views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include 'views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h2>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h2>
                <p style="color: #666; margin-top: 0.3rem;">
                    <?php echo date('l, F j, Y'); ?>
                </p>
            </div>
            <div class="user-menu">
                <span><?php echo ucfirst($userRole); ?></span>
                <a href="logout.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
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
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['total_students']; ?></h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon secondary">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['total_instructors']; ?></h3>
                            <p>Active Instructors</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon primary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['upcoming_lessons']; ?></h3>
                            <p>Upcoming Lessons</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon secondary">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['pending_invoices']; ?></h3>
                            <p>Pending Invoices</p>
                        </div>
                    </div>
                    
                <?php elseif ($userRole === 'instructor'): ?>
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon primary">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['todays_lessons']; ?></h3>
                            <p>Today's Lessons</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon secondary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['upcoming_lessons']; ?></h3>
                            <p>Upcoming Lessons</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['total_students']; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon secondary">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['completed_this_month']; ?></h3>
                            <p>Completed This Month</p>
                        </div>
                    </div>
                    
                <?php elseif ($userRole === 'student'): ?>
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon primary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['upcoming_lessons']; ?></h3>
                            <p>Upcoming Lessons</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon secondary">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['completed_lessons']; ?></h3>
                            <p>Completed Lessons</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon primary">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo formatCurrency($stats['pending_payments']); ?></h3>
                            <p>Pending Payments</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon <?php echo $stats['test_ready'] ? 'secondary' : 'primary'; ?>">
                            <i class="fas fa-<?php echo $stats['test_ready'] ? 'trophy' : 'road'; ?>"></i>
                        </div>
                        <div class="dashboard-card-info">
                            <h3><?php echo $stats['test_ready'] ? 'Yes' : 'No'; ?></h3>
                            <p>Test Ready</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Upcoming Lessons Table -->
            <?php if (!empty($upcomingLessons)): ?>
            <div class="table-container" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--color-primary);">
                    <i class="fas fa-calendar"></i> Upcoming Lessons
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <?php if ($userRole !== 'student'): ?>
                                <th>Student</th>
                            <?php endif; ?>
                            <?php if ($userRole !== 'instructor'): ?>
                                <th>Instructor</th>
                            <?php endif; ?>
                            <th>Pickup Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingLessons as $lesson): ?>
                        <tr>
                            <td><?php echo formatDate($lesson['lesson_date']); ?></td>
                            <td><?php echo date('H:i', strtotime($lesson['start_time'])); ?></td>
                            <?php if ($userRole !== 'student'): ?>
                                <td><?php echo htmlspecialchars($lesson['student_name']); ?></td>
                            <?php endif; ?>
                            <?php if ($userRole !== 'instructor'): ?>
                                <td><?php echo htmlspecialchars($lesson['instructor_name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($lesson['pickup_location']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $lesson['status']; ?>">
                                    <?php echo ucfirst($lesson['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="lessons/view.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
<!-- Notifications Section -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
    </div>
    <div class="card-body">
        <?php
        $notifications = $db->select(
            "SELECT * FROM notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT 10", 
            [$_SESSION['user_id']]
        );
        
        if (empty($notifications)): ?>
            <div class="table-empty" style="padding: 2rem; text-align: center; color: #999;">
                <i class="fas fa-bell-slash" style="font-size: 3rem; display: block; margin-bottom: 1rem; color: #ddd;"></i>
                <p>No notifications</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" 
                         style="padding: 1rem; border-bottom: 1px solid #f0f0f0; display: flex; align-items: start; gap: 1rem; <?php echo $notif['is_read'] ? '' : 'background: rgba(102, 126, 234, 0.05);'; ?>">
                        
                        <div class="notif-icon" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(102, 126, 234, 0.1); color: #667eea; flex-shrink: 0;">
                            <i class="fas fa-<?php echo $notif['notification_type'] === 'message' ? 'envelope' : 'bell'; ?>"></i>
                        </div>
                        
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: #2c3e50;">
                                <?php echo htmlspecialchars($notif['title']); ?>
                                <?php if (!$notif['is_read']): ?>
                                    <span class="badge" style="background: #667eea; color: white; font-size: 0.7rem; padding: 0.2rem 0.5rem; margin-left: 0.5rem;">NEW</span>
                                <?php endif; ?>
                            </h4>
                            <p style="margin: 0.25rem 0; color: #666; font-size: 0.9rem;">
                                <?php echo htmlspecialchars(substr($notif['message'], 0, 100)) . (strlen($notif['message']) > 100 ? '...' : ''); ?>
                            </p>
                            <small style="color: #999;">
                                <i class="fas fa-clock"></i> 
                                <?php echo date('d M Y, H:i', strtotime($notif['created_at'])); ?>
                            </small>
                        </div>
                        
                        <?php if ($notif['link']): ?>
                            <div>
                                <a href="<?php echo htmlspecialchars($notif['link']) . '&notif=' . $notif['notification_id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; padding: 1rem;">
                <a href="<?php echo APP_URL; ?>/notifications.php" class="btn btn-secondary">
                    View All Notifications
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>


            <!-- Student: Pending Invoices -->
            <?php if ($userRole === 'student' && !empty($pendingInvoices)): ?>
            <div class="table-container" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--color-primary);">
                    <i class="fas fa-file-invoice-dollar"></i> Pending Invoices
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Total Amount</th>
                            <th>Balance Due</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingInvoices as $invoice): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            <td><?php echo formatDate($invoice['issue_date']); ?></td>
                            <td><?php echo formatDate($invoice['due_date']); ?></td>
                            <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                            <td><?php echo formatCurrency($invoice['balance_due']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $invoice['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $invoice['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="invoices/view.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Admin/Staff: Recent Students -->
            <?php if (($userRole === 'admin' || $userRole === 'staff') && !empty($recentStudents)): ?>
            <div class="table-container" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--color-primary);">
                    <i class="fas fa-user-plus"></i> Recently Enrolled Students
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Enrollment Date</th>
                            <th>Branch</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentStudents as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td><?php echo formatDate($student['enrollment_date']); ?></td>
                            <td><?php echo htmlspecialchars($student['branch_name']); ?></td>
                            <td>
                                <a href="students/view.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>