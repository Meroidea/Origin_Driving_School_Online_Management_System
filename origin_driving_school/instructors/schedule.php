<?php
/**
 * Instructor Schedule - View My Schedule
 * 
 * File path: instructor/schedule.php
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
$pageTitle = 'My Schedule';

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

// Get current date and week
$currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($currentDate)));
$weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($currentDate)));

// Get lessons for the week
$lessonsSql = "SELECT l.*, 
               CONCAT(su.first_name, ' ', su.last_name) AS student_name,
               su.phone as student_phone,
               v.registration_number, v.make, v.model,
               c.course_name
               FROM lessons l
               INNER JOIN students s ON l.student_id = s.student_id
               INNER JOIN users su ON s.user_id = su.user_id
               INNER JOIN vehicles v ON l.vehicle_id = v.vehicle_id
               INNER JOIN courses c ON l.course_id = c.course_id
               WHERE l.instructor_id = ? 
               AND l.lesson_date BETWEEN ? AND ?
               ORDER BY l.lesson_date, l.start_time";

$lessons = $studentModel->customQuery($lessonsSql, [$instructorId, $weekStart, $weekEnd]);

// Organize lessons by day
$lessonsByDay = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime($weekStart . " +$i days"));
    $lessonsByDay[$date] = [];
}

foreach ($lessons as $lesson) {
    $lessonsByDay[$lesson['lesson_date']][] = $lesson;
}

// Get statistics for instructor
$statsToday = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ? AND DATE(lesson_date) = CURDATE()", [$instructorId]);
$statsWeek = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ? AND lesson_date BETWEEN ? AND ?", [$instructorId, $weekStart, $weekEnd]);
$statsCompleted = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ? AND status = 'completed'", [$instructorId]);
$totalStudents = $studentModel->customQueryOne("SELECT COUNT(DISTINCT student_id) as count FROM students WHERE assigned_instructor_id = ?", [$instructorId]);

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1><i class="fas fa-calendar"></i> My Schedule</h1>
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
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsToday['count'] ?? 0; ?></h3>
                        <p>Lessons Today</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsWeek['count'] ?? 0; ?></h3>
                        <p>This Week</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsCompleted['count'] ?? 0; ?></h3>
                        <p>Total Completed</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalStudents['count'] ?? 0; ?></h3>
                        <p>My Students</p>
                    </div>
                </div>
            </div>
            
            <!-- Week Navigation -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body" style="padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;">
                            Week of <?php echo date('F j', strtotime($weekStart)); ?> - <?php echo date('F j, Y', strtotime($weekEnd)); ?>
                        </h3>
                        <div style="display: flex; gap: 0.5rem;">
                            <?php 
                            $prevWeek = date('Y-m-d', strtotime($weekStart . ' -7 days'));
                            $nextWeek = date('Y-m-d', strtotime($weekStart . ' +7 days'));
                            ?>
                            <a href="?date=<?php echo $prevWeek; ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i> Previous Week
                            </a>
                            <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-calendar"></i> This Week
                            </a>
                            <a href="?date=<?php echo $nextWeek; ?>" class="btn btn-secondary btn-sm">
                                Next Week <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Weekly Schedule -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Weekly Schedule</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    
                    <?php foreach ($lessonsByDay as $date => $dayLessons): ?>
                        <?php
                        $dayName = date('l', strtotime($date));
                        $dayDate = date('F j, Y', strtotime($date));
                        $isToday = ($date === date('Y-m-d'));
                        ?>
                        
                        <div style="border-bottom: 1px solid #e5edf0;">
                            <div style="background-color: <?php echo $isToday ? 'rgba(231, 135, 89, 0.1)' : '#f8f9fa'; ?>; padding: 1rem; border-left: 4px solid <?php echo $isToday ? '#e78759' : '#4e7e95'; ?>;">
                                <h4 style="margin: 0; color: <?php echo $isToday ? '#e78759' : '#4e7e95'; ?>;">
                                    <?php echo $dayName; ?> - <?php echo $dayDate; ?>
                                    <?php if ($isToday): ?>
                                        <span class="badge badge-warning" style="margin-left: 0.5rem;">TODAY</span>
                                    <?php endif; ?>
                                    <span style="float: right; color: #666; font-size: 0.9rem;">
                                        <?php echo count($dayLessons); ?> lesson(s)
                                    </span>
                                </h4>
                            </div>
                            
                            <div style="padding: 1rem;">
                                <?php if (empty($dayLessons)): ?>
                                    <p style="color: #999; text-align: center; padding: 2rem;">
                                        <i class="fas fa-calendar-times"></i> No lessons scheduled for this day
                                    </p>
                                <?php else: ?>
                                    <div style="display: grid; gap: 1rem;">
                                        <?php foreach ($dayLessons as $lesson): ?>
                                            <div style="border: 2px solid <?php 
                                                echo $lesson['status'] === 'completed' ? '#28a745' : 
                                                    ($lesson['status'] === 'cancelled' ? '#dc3545' : '#4e7e95'); 
                                            ?>; border-radius: 8px; padding: 1rem; background-color: white;">
                                                
                                                <div style="display: grid; grid-template-columns: auto 1fr auto; gap: 1rem; align-items: start;">
                                                    
                                                    <!-- Time -->
                                                    <div style="text-align: center; min-width: 80px;">
                                                        <div style="font-size: 1.5rem; font-weight: bold; color: #4e7e95;">
                                                            <?php echo date('H:i', strtotime($lesson['start_time'])); ?>
                                                        </div>
                                                        <div style="font-size: 0.85rem; color: #666;">
                                                            <?php echo date('H:i', strtotime($lesson['end_time'])); ?>
                                                        </div>
                                                        <div style="margin-top: 0.5rem;">
                                                            <?php
                                                            $badgeClass = 'secondary';
                                                            if ($lesson['status'] === 'completed') $badgeClass = 'success';
                                                            elseif ($lesson['status'] === 'cancelled') $badgeClass = 'danger';
                                                            elseif ($lesson['status'] === 'scheduled') $badgeClass = 'primary';
                                                            ?>
                                                            <span class="badge badge-<?php echo $badgeClass; ?>" style="font-size: 0.75rem;">
                                                                <?php echo ucfirst($lesson['status']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Lesson Details -->
                                                    <div>
                                                        <h4 style="margin: 0 0 0.5rem 0; color: #333;">
                                                            <i class="fas fa-user"></i>
                                                            <?php echo htmlspecialchars($lesson['student_name']); ?>
                                                        </h4>
                                                        
                                                        <div style="display: grid; gap: 0.25rem; color: #666; font-size: 0.9rem;">
                                                            <div>
                                                                <i class="fas fa-book" style="width: 20px;"></i>
                                                                <strong>Course:</strong> <?php echo htmlspecialchars($lesson['course_name']); ?>
                                                            </div>
                                                            <div>
                                                                <i class="fas fa-graduation-cap" style="width: 20px;"></i>
                                                                <strong>Type:</strong> <?php echo ucwords(str_replace('_', ' ', $lesson['lesson_type'])); ?>
                                                            </div>
                                                            <div>
                                                                <i class="fas fa-car" style="width: 20px;"></i>
                                                                <strong>Vehicle:</strong> <?php echo htmlspecialchars($lesson['make'] . ' ' . $lesson['model'] . ' (' . $lesson['registration_number'] . ')'); ?>
                                                            </div>
                                                            <div>
                                                                <i class="fas fa-map-marker-alt" style="width: 20px;"></i>
                                                                <strong>Pickup:</strong> <?php echo htmlspecialchars($lesson['pickup_location']); ?>
                                                            </div>
                                                            <div>
                                                                <i class="fas fa-phone" style="width: 20px;"></i>
                                                                <strong>Contact:</strong> <?php echo htmlspecialchars($lesson['student_phone']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Actions -->
                                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                        <a href="<?php echo APP_URL; ?>/lessons/view.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <?php if ($lesson['status'] === 'scheduled'): ?>
                                                            <a href="<?php echo APP_URL; ?>/lessons/edit.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                               class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>