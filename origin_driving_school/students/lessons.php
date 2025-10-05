<?php
/**
 * Student Lessons - View My Lessons
 * 
 * File path: student/lessons.php
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

// Define APP_ACCESS constant
define('APP_ACCESS', true);

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is a student
requireLogin();
requireRole(['student']);

// Include required models
require_once APP_PATH . '/models/Student.php';

// Initialize models
$studentModel = new Student();

// Set page title
$pageTitle = 'My Lessons';

// Get student ID from user session
$userId = $_SESSION['user_id'];

// Get student details
$studentSql = "SELECT s.* FROM students s 
               INNER JOIN users u ON s.user_id = u.user_id 
               WHERE u.user_id = ?";
$student = $studentModel->customQueryOne($studentSql, [$userId]);

if (!$student) {
    setFlashMessage('error', 'Student profile not found');
    redirect('/dashboard.php');
}

$studentId = $student['student_id'];

// Get filter
$filterType = $_GET['filter'] ?? 'upcoming';

// Get lessons based on filter
$lessonsSql = "SELECT l.*, 
               CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
               iu.phone as instructor_phone,
               v.registration_number, v.make, v.model,
               c.course_name, c.lesson_duration
               FROM lessons l
               INNER JOIN instructors i ON l.instructor_id = i.instructor_id
               INNER JOIN users iu ON i.user_id = iu.user_id
               INNER JOIN vehicles v ON l.vehicle_id = v.vehicle_id
               INNER JOIN courses c ON l.course_id = c.course_id
               WHERE l.student_id = ?";

$params = [$studentId];

if ($filterType === 'upcoming') {
    $lessonsSql .= " AND l.lesson_date >= CURDATE() AND l.status = 'scheduled'";
    $lessonsSql .= " ORDER BY l.lesson_date ASC, l.start_time ASC";
} elseif ($filterType === 'past') {
    $lessonsSql .= " AND (l.lesson_date < CURDATE() OR l.status IN ('completed', 'cancelled'))";
    $lessonsSql .= " ORDER BY l.lesson_date DESC, l.start_time DESC";
} elseif ($filterType === 'today') {
    $lessonsSql .= " AND l.lesson_date = CURDATE()";
    $lessonsSql .= " ORDER BY l.start_time ASC";
} else {
    $lessonsSql .= " ORDER BY l.lesson_date DESC, l.start_time DESC";
}

$lessons = $studentModel->customQuery($lessonsSql, $params);

// Get statistics
$statsUpcoming = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE student_id = ? AND lesson_date >= CURDATE() AND status = 'scheduled'", [$studentId]);
$statsCompleted = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE student_id = ? AND status = 'completed'", [$studentId]);
$statsCancelled = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE student_id = ? AND status = 'cancelled'", [$studentId]);
$nextLesson = $studentModel->customQueryOne("SELECT * FROM lessons WHERE student_id = ? AND lesson_date >= CURDATE() AND status = 'scheduled' ORDER BY lesson_date ASC, start_time ASC LIMIT 1", [$studentId]);

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1><i class="fas fa-calendar-alt"></i> My Lessons</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            
            <!-- Next Lesson Alert -->
            <?php if ($nextLesson): ?>
                <div style="background: linear-gradient(135deg, #4e7e95 0%, #e78759 100%); color: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3 style="margin: 0 0 0.5rem 0;">
                                <i class="fas fa-bell"></i> Next Lesson Coming Up!
                            </h3>
                            <div style="font-size: 1.1rem;">
                                <strong><?php echo date('l, F j, Y', strtotime($nextLesson['lesson_date'])); ?></strong>
                                at <strong><?php echo date('g:i A', strtotime($nextLesson['start_time'])); ?></strong>
                            </div>
                            <div style="margin-top: 0.5rem; opacity: 0.9;">
                                Pickup: <?php echo htmlspecialchars($nextLesson['pickup_location']); ?>
                            </div>
                        </div>
                        <a href="<?php echo APP_URL; ?>/lessons/view.php?id=<?php echo $nextLesson['lesson_id']; ?>" 
                           class="btn btn-light">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsUpcoming['count'] ?? 0; ?></h3>
                        <p>Upcoming Lessons</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsCompleted['count'] ?? 0; ?></h3>
                        <p>Completed Lessons</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsCancelled['count'] ?? 0; ?></h3>
                        <p>Cancelled</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <?php 
                        $totalLessons = $statsCompleted['count'] + $statsCancelled['count'];
                        $completionRate = $totalLessons > 0 ? round(($statsCompleted['count'] / $totalLessons) * 100) : 0;
                        ?>
                        <h3><?php echo $completionRate; ?>%</h3>
                        <p>Completion Rate</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body" style="padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="?filter=upcoming" class="btn btn-<?php echo $filterType === 'upcoming' ? 'primary' : 'secondary'; ?> btn-sm">
                                <i class="fas fa-clock"></i> Upcoming (<?php echo $statsUpcoming['count']; ?>)
                            </a>
                            <a href="?filter=today" class="btn btn-<?php echo $filterType === 'today' ? 'primary' : 'secondary'; ?> btn-sm">
                                <i class="fas fa-calendar-day"></i> Today
                            </a>
                            <a href="?filter=past" class="btn btn-<?php echo $filterType === 'past' ? 'primary' : 'secondary'; ?> btn-sm">
                                <i class="fas fa-history"></i> Past Lessons
                            </a>
                            <a href="?filter=all" class="btn btn-<?php echo $filterType === 'all' ? 'primary' : 'secondary'; ?> btn-sm">
                                <i class="fas fa-list"></i> All
                            </a>
                        </div>
                        <a href="book-lesson.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Book New Lesson
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Lessons List -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-list"></i> 
                        <?php 
                        echo $filterType === 'upcoming' ? 'Upcoming Lessons' : 
                             ($filterType === 'past' ? 'Past Lessons' : 
                             ($filterType === 'today' ? "Today's Lessons" : 'All Lessons'));
                        ?>
                        (<?php echo count($lessons); ?>)
                    </h3>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($lessons)): ?>
                        <div class="table-empty">
                            <i class="fas fa-calendar-times"></i>
                            <p>No lessons found</p>
                            <?php if ($filterType === 'upcoming'): ?>
                                <p style="color: #666; margin-top: 1rem;">
                                    <a href="book-lesson.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Book Your First Lesson
                                    </a>
                                </p>
                            <?php else: ?>
                                <p><a href="?filter=all">View all lessons</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        
                        <div style="display: grid; gap: 1.5rem;">
                            <?php foreach ($lessons as $lesson): ?>
                                <?php
                                $isPast = (strtotime($lesson['lesson_date']) < strtotime('today'));
                                $isToday = ($lesson['lesson_date'] === date('Y-m-d'));
                                ?>
                                
                                <div style="border: 2px solid <?php 
                                    echo $lesson['status'] === 'completed' ? '#28a745' : 
                                        ($lesson['status'] === 'cancelled' ? '#dc3545' : 
                                        ($isToday ? '#e78759' : '#4e7e95')); 
                                ?>; border-radius: 8px; padding: 1.5rem; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                    
                                    <div style="display: grid; grid-template-columns: auto 1fr auto; gap: 1.5rem; align-items: start;">
                                        
                                        <!-- Date & Time -->
                                        <div style="text-align: center; min-width: 100px;">
                                            <div style="background-color: <?php echo $isToday ? '#e78759' : '#4e7e95'; ?>; color: white; padding: 0.5rem; border-radius: 8px 8px 0 0;">
                                                <div style="font-size: 0.85rem; opacity: 0.9;">
                                                    <?php echo date('M', strtotime($lesson['lesson_date'])); ?>
                                                </div>
                                                <div style="font-size: 2rem; font-weight: bold; line-height: 1;">
                                                    <?php echo date('d', strtotime($lesson['lesson_date'])); ?>
                                                </div>
                                                <div style="font-size: 0.85rem; opacity: 0.9;">
                                                    <?php echo date('Y', strtotime($lesson['lesson_date'])); ?>
                                                </div>
                                            </div>
                                            <div style="background-color: #f8f9fa; padding: 0.5rem; border-radius: 0 0 8px 8px;">
                                                <div style="font-weight: bold; color: #333;">
                                                    <?php echo date('g:i A', strtotime($lesson['start_time'])); ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: #666;">
                                                    <?php echo $lesson['lesson_duration']; ?> min
                                                </div>
                                            </div>
                                            <?php if ($isToday): ?>
                                                <div style="margin-top: 0.5rem;">
                                                    <span class="badge badge-warning">TODAY</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Lesson Details -->
                                        <div>
                                            <h4 style="margin: 0 0 1rem 0; color: #333;">
                                                <i class="fas fa-book"></i>
                                                <?php echo htmlspecialchars($lesson['course_name']); ?>
                                            </h4>
                                            
                                            <div style="display: grid; gap: 0.5rem; color: #666; font-size: 0.95rem;">
                                                <div>
                                                    <i class="fas fa-chalkboard-teacher" style="width: 22px; color: #4e7e95;"></i>
                                                    <strong>Instructor:</strong> <?php echo htmlspecialchars($lesson['instructor_name']); ?>
                                                </div>
                                                <div>
                                                    <i class="fas fa-phone" style="width: 22px; color: #4e7e95;"></i>
                                                    <strong>Contact:</strong> <?php echo htmlspecialchars($lesson['instructor_phone']); ?>
                                                </div>
                                                <div>
                                                    <i class="fas fa-car" style="width: 22px; color: #4e7e95;"></i>
                                                    <strong>Vehicle:</strong> <?php echo htmlspecialchars($lesson['make'] . ' ' . $lesson['model'] . ' (' . $lesson['registration_number'] . ')'); ?>
                                                </div>
                                                <div>
                                                    <i class="fas fa-map-marker-alt" style="width: 22px; color: #4e7e95;"></i>
                                                    <strong>Pickup:</strong> <?php echo htmlspecialchars($lesson['pickup_location']); ?>
                                                </div>
                                                <div>
                                                    <i class="fas fa-graduation-cap" style="width: 22px; color: #4e7e95;"></i>
                                                    <strong>Type:</strong> <?php echo ucwords(str_replace('_', ' ', $lesson['lesson_type'])); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($lesson['instructor_notes']) && $lesson['status'] === 'completed'): ?>
                                                <div style="margin-top: 1rem; padding: 0.75rem; background-color: #d4edda; border-left: 4px solid #28a745; border-radius: 3px;">
                                                    <div style="font-weight: bold; color: #155724; margin-bottom: 0.25rem;">
                                                        <i class="fas fa-comment"></i> Instructor Notes:
                                                    </div>
                                                    <div style="font-size: 0.9rem; color: #155724;">
                                                        <?php echo htmlspecialchars($lesson['instructor_notes']); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Status & Actions -->
                                        <div style="text-align: right;">
                                            <?php
                                            $badgeClass = 'secondary';
                                            $statusIcon = 'fa-info-circle';
                                            
                                            switch($lesson['status']) {
                                                case 'scheduled':
                                                    $badgeClass = 'primary';
                                                    $statusIcon = 'fa-clock';
                                                    break;
                                                case 'completed':
                                                    $badgeClass = 'success';
                                                    $statusIcon = 'fa-check-circle';
                                                    break;
                                                case 'cancelled':
                                                    $badgeClass = 'danger';
                                                    $statusIcon = 'fa-times-circle';
                                                    break;
                                                case 'no_show':
                                                    $badgeClass = 'warning';
                                                    $statusIcon = 'fa-exclamation-triangle';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge badge-<?php echo $badgeClass; ?>" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                                <i class="fas <?php echo $statusIcon; ?>"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $lesson['status'])); ?>
                                            </span>
                                            
                                            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                                <a href="<?php echo APP_URL; ?>/lessons/view.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                   class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                                
                                                <?php if ($lesson['status'] === 'scheduled' && !$isPast): ?>
                                                    <a href="<?php echo APP_URL; ?>/lessons/reschedule.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fas fa-calendar"></i> Reschedule
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
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