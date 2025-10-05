<?php
/**
 * file path: lessons/view.php
 * 
 * Professional Lesson Details Page - Origin Driving School
 * 
 * Comprehensive lesson information display with action buttons,
 * performance tracking, and interactive timeline
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 2.0
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/models/User.php';
require_once '../app/models/Lesson.php';
require_once '../app/models/Student.php';
require_once '../app/models/Instructor.php';

// Require login
requireLogin();

$lessonId = $_GET['id'] ?? 0;
$userRole = getCurrentUserRole();
$userId = getCurrentUserId();

if (!$lessonId) {
    setFlashMessage('Invalid lesson ID', 'error');
    redirect('/lessons/');
}

$lessonModel = new Lesson();
$lesson = $lessonModel->getLessonWithFullDetails($lessonId);

if (!$lesson) {
    setFlashMessage('Lesson not found', 'error');
    redirect('/lessons/');
}

// Check permissions
if ($userRole === 'student') {
    $studentModel = new Student();
    $student = $studentModel->getByUserId($userId);
    if (!$student || $student['student_id'] != $lesson['student_id']) {
        setFlashMessage('You do not have permission to view this lesson', 'error');
        redirect('/lessons/');
    }
} elseif ($userRole === 'instructor') {
    $instructorModel = new Instructor();
    $instructor = $instructorModel->getByUserId($userId);
    if (!$instructor || $instructor['instructor_id'] != $lesson['instructor_id']) {
        setFlashMessage('You do not have permission to view this lesson', 'error');
        redirect('/lessons/');
    }
}

// Calculate lesson status details
$lessonDate = new DateTime($lesson['lesson_date'] . ' ' . $lesson['start_time']);
$currentDate = new DateTime();
$isPast = $lessonDate < $currentDate;
$isToday = $lesson['lesson_date'] === date('Y-m-d');
$isTomorrow = $lesson['lesson_date'] === date('Y-m-d', strtotime('+1 day'));
$daysUntil = $lessonDate->diff($currentDate)->days;

// Get student's other lessons
$studentLessons = $lessonModel->getStudentProgress($lesson['student_id']);

$pageTitle = 'Lesson Details - ' . $lesson['student_name'];
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Enhanced Header -->
        <div class="lesson-detail-header">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="<?php echo APP_URL; ?>/lessons/"><i class="fas fa-calendar-alt"></i> Lessons</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Lesson #<?php echo $lesson['lesson_id']; ?></span>
                </div>
                <h2>
                    <i class="fas fa-graduation-cap"></i>
                    Lesson Details
                </h2>
                <div class="lesson-meta">
                    <span class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('l, F d, Y', strtotime($lesson['lesson_date'])); ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-clock"></i>
                        <?php echo date('h:i A', strtotime($lesson['start_time'])); ?> - 
                        <?php echo date('h:i A', strtotime($lesson['end_time'])); ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-hourglass-half"></i>
                        <?php echo $lesson['duration_minutes']; ?> minutes
                    </span>
                </div>
            </div>
            <div class="header-actions">
                <?php if (($userRole === 'admin' || $userRole === 'staff' || $userRole === 'instructor') && $lesson['status'] === 'scheduled'): ?>
                    <a href="edit.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Lesson
                    </a>
                    <?php if ($userRole === 'instructor'): ?>
                    <a href="complete.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Mark Complete
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
                <button class="btn btn-outline" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-secondary" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>
        </div>
        
        <div class="content-area">
            <?php 
            $flashMessage = getFlashMessage();
            if ($flashMessage): 
            ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?>">
                    <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($flashMessage['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Status Banner -->
            <div class="status-banner status-<?php echo $lesson['status']; ?>">
                <div class="status-content">
                    <?php
                    $statusIcons = [
                        'scheduled' => 'clock',
                        'completed' => 'check-circle',
                        'cancelled' => 'times-circle',
                        'in_progress' => 'spinner fa-spin',
                        'rescheduled' => 'redo'
                    ];
                    $icon = $statusIcons[$lesson['status']] ?? 'question-circle';
                    ?>
                    <div class="status-icon">
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                    </div>
                    <div class="status-info">
                        <h3>Lesson Status: <?php echo ucfirst($lesson['status']); ?></h3>
                        <p>
                            <?php
                            if ($isToday && $lesson['status'] === 'scheduled') {
                                echo '<span class="badge badge-today">Today\'s Lesson</span> - Starts in ' . $lessonDate->diff($currentDate)->format('%h hours %i minutes');
                            } elseif ($isTomorrow && $lesson['status'] === 'scheduled') {
                                echo '<span class="badge badge-tomorrow">Tomorrow</span> - ' . date('h:i A', strtotime($lesson['start_time']));
                            } elseif (!$isPast && $lesson['status'] === 'scheduled') {
                                echo "Scheduled for {$daysUntil} days from now";
                            } elseif ($lesson['status'] === 'completed') {
                                echo "Successfully completed on " . date('M d, Y', strtotime($lesson['lesson_date']));
                            } elseif ($lesson['status'] === 'cancelled') {
                                echo "This lesson has been cancelled";
                            }
                            ?>
                        </p>
                    </div>
                </div>
                <?php if ($lesson['status'] === 'scheduled' && !$isPast): ?>
                <div class="countdown-timer" id="countdownTimer" 
                     data-date="<?php echo $lesson['lesson_date']; ?>" 
                     data-time="<?php echo $lesson['start_time']; ?>">
                    <div class="countdown-item">
                        <span class="countdown-value" id="days">00</span>
                        <span class="countdown-label">Days</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="hours">00</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="minutes">00</span>
                        <span class="countdown-label">Minutes</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="seconds">00</span>
                        <span class="countdown-label">Seconds</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Main Content Grid -->
            <div class="lesson-detail-grid">
                <!-- Left Column -->
                <div class="detail-column">
                    <!-- Student Information Card -->
                    <div class="info-card">
                        <div class="card-header-modern">
                            <h3><i class="fas fa-user-graduate"></i> Student Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="profile-section">
                                <div class="profile-avatar">
                                    <?php echo strtoupper(substr($lesson['student_name'], 0, 2)); ?>
                                </div>
                                <div class="profile-details">
                                    <h4><?php echo htmlspecialchars($lesson['student_name']); ?></h4>
                                    <p class="profile-subtitle">Student</p>
                                </div>
                            </div>
                            <div class="info-list">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </div>
                                    <div class="info-value">
                                        <a href="mailto:<?php echo $lesson['student_email']; ?>">
                                            <?php echo htmlspecialchars($lesson['student_email']); ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-phone"></i> Phone
                                    </div>
                                    <div class="info-value">
                                        <a href="tel:<?php echo $lesson['student_phone']; ?>">
                                            <?php echo htmlspecialchars($lesson['student_phone']); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php if (!empty($lesson['student_license'])): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-id-card"></i> License
                                    </div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($lesson['student_license']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-check"></i> Enrolled
                                    </div>
                                    <div class="info-value">
                                        <?php echo date('M d, Y', strtotime($lesson['enrollment_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Student Progress -->
                            <?php if ($studentLessons): ?>
                            <div class="progress-widget">
                                <h4>Student Progress</h4>
                                <div class="progress-stats">
                                    <div class="progress-stat">
                                        <div class="stat-number"><?php echo $studentLessons['completed_lessons']; ?></div>
                                        <div class="stat-label">Completed</div>
                                    </div>
                                    <div class="progress-stat">
                                        <div class="stat-number"><?php echo $studentLessons['scheduled_lessons']; ?></div>
                                        <div class="stat-label">Scheduled</div>
                                    </div>
                                    <div class="progress-stat">
                                        <div class="stat-number">
                                            <?php echo $studentLessons['avg_rating'] ? number_format($studentLessons['avg_rating'], 1) : 'N/A'; ?>
                                        </div>
                                        <div class="stat-label">Avg Rating</div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Instructor Information Card -->
                    <div class="info-card">
                        <div class="card-header-modern">
                            <h3><i class="fas fa-chalkboard-teacher"></i> Instructor Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="profile-section">
                                <div class="profile-avatar instructor-avatar">
                                    <?php echo strtoupper(substr($lesson['instructor_name'], 0, 2)); ?>
                                </div>
                                <div class="profile-details">
                                    <h4><?php echo htmlspecialchars($lesson['instructor_name']); ?></h4>
                                    <p class="profile-subtitle">Driving Instructor</p>
                                </div>
                            </div>
                            <div class="info-list">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </div>
                                    <div class="info-value">
                                        <a href="mailto:<?php echo $lesson['instructor_email']; ?>">
                                            <?php echo htmlspecialchars($lesson['instructor_email']); ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-phone"></i> Phone
                                    </div>
                                    <div class="info-value">
                                        <a href="tel:<?php echo $lesson['instructor_phone']; ?>">
                                            <?php echo htmlspecialchars($lesson['instructor_phone']); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php if (!empty($lesson['instructor_license'])): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-id-card"></i> License
                                    </div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($lesson['instructor_license']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($lesson['specialization'])): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-star"></i> Specialization
                                    </div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($lesson['specialization']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vehicle Information Card -->
                    <?php if (!empty($lesson['registration_number'])): ?>
                    <div class="info-card">
                        <div class="card-header-modern">
                            <h3><i class="fas fa-car"></i> Vehicle Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="vehicle-display">
                                <div class="license-plate">
                                    <?php echo htmlspecialchars($lesson['registration_number']); ?>
                                </div>
                                <div class="vehicle-info-grid">
                                    <div class="vehicle-info-item">
                                        <span class="label">Make & Model</span>
                                        <span class="value">
                                            <?php echo htmlspecialchars($lesson['make'] . ' ' . $lesson['model']); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($lesson['year'])): ?>
                                    <div class="vehicle-info-item">
                                        <span class="label">Year</span>
                                        <span class="value"><?php echo htmlspecialchars($lesson['year']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($lesson['color'])): ?>
                                    <div class="vehicle-info-item">
                                        <span class="label">Color</span>
                                        <span class="value"><?php echo htmlspecialchars($lesson['color']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($lesson['transmission_type'])): ?>
                                    <div class="vehicle-info-item">
                                        <span class="label">Transmission</span>
                                        <span class="value">
                                            <?php echo htmlspecialchars(ucfirst($lesson['transmission_type'])); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right Column -->
                <div class="detail-column">
                    <!-- Lesson Details Card -->
                    <div class="info-card">
                        <div class="card-header-modern">
                            <h3><i class="fas fa-info-circle"></i> Lesson Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Lesson Type</div>
                                        <div class="detail-value">
                                            <span class="badge badge-type badge-<?php echo $lesson['lesson_type']; ?>">
                                                <?php echo ucfirst($lesson['lesson_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($lesson['course_name'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Course</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($lesson['course_name']); ?>
                                            <?php if (!empty($lesson['course_code'])): ?>
                                                <small>(<?php echo htmlspecialchars($lesson['course_code']); ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Pickup Location</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($lesson['pickup_location']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($lesson['dropoff_location'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-flag-checkered"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Dropoff Location</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($lesson['dropoff_location']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Branch</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($lesson['branch_name']); ?>
                                            <?php if (!empty($lesson['branch_phone'])): ?>
                                                <br><small><?php echo htmlspecialchars($lesson['branch_phone']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Performance & Notes -->
                    <?php if ($lesson['status'] === 'completed'): ?>
                    <div class="info-card">
                        <div class="card-header-modern">
                            <h3><i class="fas fa-chart-line"></i> Performance & Feedback</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($lesson['student_performance_rating'])): ?>
                            <div class="performance-rating">
                                <div class="rating-label">Student Performance</div>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $lesson['student_performance_rating'] ? 'active' : ''; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="rating-value"><?php echo $lesson['student_performance_rating']; ?>/5</span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($lesson['skills_practiced'])): ?>
                            <div class="skills-section">
                                <h4>Skills Practiced</h4>
                                <div class="skills-tags">
                                    <?php 
                                    $skills = explode(',', $lesson['skills_practiced']);
                                    foreach ($skills as $skill): 
                                    ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($lesson['instructor_notes'])): ?>
                            <div class="notes-section">
                                <h4>Instructor Notes</h4>
                                <div class="notes-content">
                                    <?php echo nl2br(htmlspecialchars($lesson['instructor_notes'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quick Actions Card -->
                    <div class="info-card">
                        <div class="card-header-modern">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="action-grid">
                                <?php if ($lesson['status'] === 'scheduled' && ($userRole === 'admin' || $userRole === 'staff')): ?>
                                <a href="edit.php?id=<?php echo $lesson['lesson_id']; ?>" class="action-card">
                                    <i class="fas fa-edit"></i>
                                    <span>Edit Lesson</span>
                                </a>
                                <a href="reschedule.php?id=<?php echo $lesson['lesson_id']; ?>" class="action-card">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Reschedule</span>
                                </a>
                                <a href="delete.php?id=<?php echo $lesson['lesson_id']; ?>" class="action-card danger"
                                   onclick="return confirm('Are you sure you want to cancel this lesson?');">
                                    <i class="fas fa-times-circle"></i>
                                    <span>Cancel</span>
                                </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo APP_URL; ?>/lessons/?student_id=<?php echo $lesson['student_id']; ?>" class="action-card">
                                    <i class="fas fa-history"></i>
                                    <span>Student History</span>
                                </a>
                                
                                <a href="<?php echo APP_URL; ?>/lessons/?instructor_id=<?php echo $lesson['instructor_id']; ?>" class="action-card">
                                    <i class="fas fa-user-tie"></i>
                                    <span>Instructor Schedule</span>
                                </a>
                                
                                <button onclick="shareLesson()" class="action-card">
                                    <i class="fas fa-share-alt"></i>
                                    <span>Share</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>

/* ============= LESSON VIEW PAGE STYLES ============= */

/* Lesson Detail Header */
.lesson-detail-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.header-content {
    margin-bottom: 1rem;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
    transition: opacity 0.3s;
}

.breadcrumb a:hover {
    opacity: 0.8;
}

.lesson-detail-header h2 {
    margin: 0.5rem 0;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.lesson-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-top: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* Status Banner */
.status-banner {
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.status-banner.status-scheduled {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #000;
}

.status-banner.status-completed {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.status-banner.status-cancelled {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.status-banner.status-in_progress {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.status-content {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.status-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.status-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
}

.status-info p {
    margin: 0;
    opacity: 0.9;
}

/* Countdown Timer */
.countdown-timer {
    display: flex;
    gap: 1rem;
}

.countdown-item {
    text-align: center;
    background: rgba(255,255,255,0.2);
    padding: 0.75rem 1rem;
    border-radius: 12px;
    min-width: 70px;
}

.countdown-value {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
}

.countdown-label {
    display: block;
    font-size: 0.75rem;
    opacity: 0.9;
    margin-top: 0.25rem;
}

/* Detail Grid */
.lesson-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.detail-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Info Card */
.info-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header-modern {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.25rem 1.5rem;
    border-bottom: 2px solid #dee2e6;
}

.card-header-modern h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-card .card-body {
    padding: 1.5rem;
}

/* Profile Section */
.profile-section {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f0f0f0;
}

.profile-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 700;
}

.instructor-avatar {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.profile-details h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1.3rem;
    color: #333;
}

.profile-subtitle {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

/* Info List */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-weight: 500;
    font-size: 0.9rem;
}

.info-label i {
    color: #667eea;
    width: 18px;
}

.info-value {
    color: #333;
    font-weight: 500;
}

.info-value a {
    color: #667eea;
    text-decoration: none;
}

.info-value a:hover {
    text-decoration: underline;
}

/* Progress Widget */
.progress-widget {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid #f0f0f0;
}

.progress-widget h4 {
    margin: 0 0 1rem 0;
    color: #333;
}

.progress-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.progress-stat {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.progress-stat .stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    color: #667eea;
}

.progress-stat .stat-label {
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.25rem;
}

/* Vehicle Display */
.vehicle-display {
    text-align: center;
}

.license-plate {
    display: inline-block;
    background: #FFD700;
    color: #000;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 2px;
    margin-bottom: 1.5rem;
    border: 3px solid #000;
}

.vehicle-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.vehicle-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.vehicle-info-item .label {
    font-size: 0.8rem;
    color: #666;
    font-weight: 500;
}

.vehicle-info-item .value {
    font-size: 1rem;
    color: #333;
    font-weight: 600;
}

/* Detail Grid */
.detail-grid {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.detail-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.detail-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.detail-content {
    flex: 1;
}

.detail-label {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.detail-value {
    color: #333;
    font-weight: 600;
}

.detail-value small {
    display: block;
    font-size: 0.85rem;
    color: #888;
    font-weight: 400;
    margin-top: 0.25rem;
}

/* Badge Types */
.badge-type {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-block;
}

.badge-theory { background: #e3f2fd; color: #1976d2; }
.badge-practical { background: #f3e5f5; color: #7b1fa2; }
.badge-highway { background: #e8f5e9; color: #388e3c; }
.badge-parking { background: #fff3e0; color: #f57c00; }

.badge-today {
    background: #ffc107;
    color: #000;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-tomorrow {
    background: #17a2b8;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Performance Rating */
.performance-rating {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.rating-label {
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
    opacity: 0.9;
}

.rating-stars {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
}

.rating-stars i {
    color: rgba(255,255,255,0.3);
}

.rating-stars i.active {
    color: #FFD700;
}

.rating-value {
    margin-left: 0.5rem;
    font-size: 1.2rem;
    font-weight: 600;
}

/* Skills Section */
.skills-section {
    margin-bottom: 1.5rem;
}

.skills-section h4 {
    margin: 0 0 1rem 0;
    color: #333;
}

.skills-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.skill-tag {
    padding: 0.5rem 1rem;
    background: #e9ecef;
    color: #333;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Notes Section */
.notes-section h4 {
    margin: 0 0 1rem 0;
    color: #333;
}

.notes-content {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    color: #666;
    line-height: 1.6;
}

/* Action Grid */
.action-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    color: #333;
}

.action-card:hover {
    background: #667eea;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.action-card.danger:hover {
    background: #dc3545;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.action-card i {
    font-size: 1.5rem;
}

.action-card span {
    font-size: 0.9rem;
    font-weight: 500;
}

/* Alert Styling */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert i {
    font-size: 1.2rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .lesson-detail-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .lesson-detail-header {
        padding: 1.5rem;
    }
    
    .lesson-detail-header h2 {
        font-size: 1.5rem;
    }
    
    .lesson-meta {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .header-actions {
        flex-direction: column;
    }
    
    .header-actions .btn {
        width: 100%;
    }
    
    .status-banner {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .countdown-timer {
        width: 100%;
        justify-content: space-around;
    }
    
    .countdown-item {
        min-width: 60px;
        padding: 0.5rem;
    }
    
    .countdown-value {
        font-size: 1.4rem;
    }
    
    .profile-section {
        flex-direction: column;
        text-align: center;
    }
    
    .vehicle-info-grid {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .progress-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .countdown-timer {
        flex-wrap: wrap;
    }
    
    .countdown-item {
        flex: 1 1 40%;
    }
    
    .license-plate {
        font-size: 1.2rem;
        padding: 0.5rem 1.5rem;
    }
}

/* Print Styles */
@media print {
    .sidebar, 
    .header-actions, 
    .action-card,
    .countdown-timer {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
    }
    
    .lesson-detail-header {
        background: #667eea !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .status-banner {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .lesson-detail-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
// ============= LESSON VIEW PAGE JAVASCRIPT =============

// Countdown timer for scheduled lessons
function updateCountdown() {
    const timer = document.getElementById('countdownTimer');
    if (!timer) return;
    
    const lessonDate = timer.dataset.date;
    const lessonTime = timer.dataset.time;
    const lessonDateTime = new Date(lessonDate + ' ' + lessonTime);
    const now = new Date();
    const diff = lessonDateTime - now;
    
    if (diff <= 0) {
        timer.innerHTML = '<div class="countdown-expired" style="text-align: center; font-size: 1.2rem; font-weight: 600;">Lesson has started!</div>';
        return;
    }
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    const daysElement = document.getElementById('days');
    const hoursElement = document.getElementById('hours');
    const minutesElement = document.getElementById('minutes');
    const secondsElement = document.getElementById('seconds');
    
    if (daysElement) daysElement.textContent = String(days).padStart(2, '0');
    if (hoursElement) hoursElement.textContent = String(hours).padStart(2, '0');
    if (minutesElement) minutesElement.textContent = String(minutes).padStart(2, '0');
    if (secondsElement) secondsElement.textContent = String(seconds).padStart(2, '0');
}

// Initialize countdown timer
if (document.getElementById('countdownTimer')) {
    updateCountdown();
    setInterval(updateCountdown, 1000);
}

// Share lesson function
function shareLesson() {
    const lessonUrl = window.location.href;
    const lessonTitle = document.querySelector('.lesson-detail-header h2')?.textContent || 'Lesson Details';
    
    // Check if Web Share API is supported
    if (navigator.share) {
        navigator.share({
            title: lessonTitle + ' - Origin Driving School',
            text: 'Check out this driving lesson',
            url: lessonUrl
        })
        .then(() => console.log('Shared successfully'))
        .catch((error) => console.log('Error sharing:', error));
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(lessonUrl)
            .then(() => {
                showNotification('Lesson link copied to clipboard!', 'success');
            })
            .catch((error) => {
                console.error('Could not copy to clipboard:', error);
                // Show URL in a prompt as final fallback
                prompt('Copy this lesson URL:', lessonUrl);
            });
    }
}

// Show notification toast
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideIn 0.3s ease-out;
    `;
    
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Print lesson details
function printLesson() {
    window.print();
}

// Send email to student/instructor
function sendEmail(recipient, type) {
    const subject = encodeURIComponent(`Lesson Details - Origin Driving School`);
    const body = encodeURIComponent(`Hi,\n\nHere are the lesson details:\n${window.location.href}\n\nBest regards,\nOrigin Driving School`);
    window.location.href = `mailto:${recipient}?subject=${subject}&body=${body}`;
}

// Download lesson as PDF (placeholder - requires server-side implementation)
function downloadPDF() {
    showNotification('PDF download feature coming soon!', 'info');
    // In a real implementation, you would redirect to a PDF generation endpoint
    // window.location.href = `generate_pdf.php?lesson_id=${lessonId}`;
}

// Add to calendar (iCal format)
function addToCalendar() {
    const timer = document.getElementById('countdownTimer');
    if (!timer) {
        showNotification('Cannot add to calendar', 'error');
        return;
    }
    
    const lessonDate = timer.dataset.date;
    const lessonTime = timer.dataset.time;
    const lessonDateTime = new Date(lessonDate + ' ' + lessonTime);
    
    // Get lesson details from page
    const title = document.querySelector('.lesson-detail-header h2')?.textContent || 'Driving Lesson';
    const location = document.querySelector('.detail-value')?.textContent || '';
    
    // Format date for iCal (YYYYMMDDTHHMMSS)
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}${month}${day}T${hours}${minutes}00`;
    };
    
    // Calculate end time (add duration)
    const endDateTime = new Date(lessonDateTime.getTime() + (60 * 60 * 1000)); // 1 hour duration
    
    // Create iCal content
    const icalContent = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Origin Driving School//Lesson//EN',
        'BEGIN:VEVENT',
        `DTSTART:${formatDate(lessonDateTime)}`,
        `DTEND:${formatDate(endDateTime)}`,
        `SUMMARY:${title}`,
        `DESCRIPTION:Driving lesson at Origin Driving School`,
        `LOCATION:${location}`,
        'STATUS:CONFIRMED',
        'END:VEVENT',
        'END:VCALENDAR'
    ].join('\r\n');
    
    // Create blob and download
    const blob = new Blob([icalContent], { type: 'text/calendar' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'driving-lesson.ics';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showNotification('Calendar event downloaded!', 'success');
}

// Cancel lesson with confirmation
function cancelLesson(lessonId) {
    if (confirm('Are you sure you want to cancel this lesson? This action cannot be undone.')) {
        window.location.href = `delete.php?id=${lessonId}`;
    }
}

// Reschedule lesson
function rescheduleLesson(lessonId) {
    window.location.href = `edit.php?id=${lessonId}`;
}

// View student history
function viewStudentHistory(studentId) {
    window.location.href = `index.php?student_id=${studentId}`;
}

// View instructor schedule
function viewInstructorSchedule(instructorId) {
    window.location.href = `index.php?instructor_id=${instructorId}`;
}

// Smooth scroll to section
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Initialize tooltips (if using a tooltip library)
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers to action cards
    const actionCards = document.querySelectorAll('.action-card[data-action]');
    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            const action = this.dataset.action;
            switch(action) {
                case 'print':
                    printLesson();
                    break;
                case 'share':
                    shareLesson();
                    break;
                case 'calendar':
                    addToCalendar();
                    break;
                case 'pdf':
                    downloadPDF();
                    break;
            }
        });
    });
    
    // Highlight current time in countdown
    if (document.getElementById('countdownTimer')) {
        const countdownItems = document.querySelectorAll('.countdown-item');
        countdownItems.forEach(item => {
            item.style.transition = 'all 0.3s ease';
        });
    }
    
    // Add animation to cards on load
    const cards = document.querySelectorAll('.info-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Handle back button
window.addEventListener('popstate', function(event) {
    // Handle browser back button if needed
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + P: Print
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        printLesson();
    }
    
    // Ctrl/Cmd + S: Share
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        shareLesson();
    }
    
    // Escape: Go back
    if (e.key === 'Escape') {
        window.history.back();
    }
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Copy text to clipboard utility
function copyToClipboard(text) {
    navigator.clipboard.writeText(text)
        .then(() => {
            showNotification('Copied to clipboard!', 'success');
        })
        .catch(err => {
            console.error('Failed to copy:', err);
        });
}

// Add click-to-copy functionality to phone numbers and emails
document.addEventListener('DOMContentLoaded', function() {
    const copyableElements = document.querySaleAll('a[href^="tel:"], a[href^="mailto:"]');
    copyableElements.forEach(element => {
        element.style.cursor = 'pointer';
        element.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            const text = this.textContent.trim();
            copyToClipboard(text);
        });
    });
});

// Performance rating interaction (if editable)
function updateRating(rating) {
    const stars = document.querySelectorAll('.rating-stars i');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Status change animation
function animateStatusChange(newStatus) {
    const statusBanner = document.querySelector('.status-banner');
    if (statusBanner) {
        statusBanner.style.transition = 'all 0.5s ease';
        statusBanner.style.transform = 'scale(0.95)';
        setTimeout(() => {
            statusBanner.className = `status-banner status-${newStatus}`;
            statusBanner.style.transform = 'scale(1)';
        }, 250);
    }
}

// Refresh lesson data (AJAX - placeholder)
function refreshLessonData() {
    showNotification('Refreshing lesson data...', 'info');
    
    // In a real implementation, you would use AJAX to refresh the data
    // fetch(`get_lesson.php?id=${lessonId}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         // Update the page with new data
    //         showNotification('Lesson data updated!', 'success');
    //     })
    //     .catch(error => {
    //         showNotification('Failed to refresh data', 'error');
    //     });
    
    // For now, just reload the page
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Set reminder/notification (placeholder)
function setReminder(minutes) {
    if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                showNotification(`Reminder set for ${minutes} minutes before lesson`, 'success');
                
                // In a real implementation, you would use a service worker or backend
                // to handle the actual notification
            }
        });
    } else {
        showNotification('Notifications not supported in this browser', 'error');
    }
}

// Console log for debugging
console.log('Lesson View JavaScript loaded successfully');
console.log('Available functions:', {
    updateCountdown: 'Update countdown timer',
    shareLesson: 'Share lesson via Web Share API or clipboard',
    printLesson: 'Print lesson details',
    addToCalendar: 'Download iCal file',
    downloadPDF: 'Download lesson as PDF',
    cancelLesson: 'Cancel the lesson',
    rescheduleLesson: 'Reschedule the lesson',
    copyToClipboard: 'Copy text to clipboard',
    refreshLessonData: 'Refresh lesson information',
    setReminder: 'Set reminder notification'
});
</script>

<?php include '../views/layouts/footer.php'; ?>