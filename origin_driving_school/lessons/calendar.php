<?php
/**
 * file path: lessons/calendar.php
 * 
 * Interactive Calendar View - Origin Driving School Management System
 * 
 * Visual calendar display for lesson scheduling with drag-and-drop,
 * month/week/day views, and real-time updates
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 2.0
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/models/User.php';
require_once '../app/models/Lesson.php';
require_once '../app/models/Instructor.php';
require_once '../app/models/Student.php';

// Require login
requireLogin();

$lessonModel = new Lesson();
$userRole = getCurrentUserRole();
$userId = getCurrentUserId();

// Get view mode and date
$viewMode = $_GET['view'] ?? 'month'; // month, week, day
$currentDate = $_GET['date'] ?? date('Y-m-d');

// Parse current date
$dateObj = new DateTime($currentDate);
$year = $dateObj->format('Y');
$month = $dateObj->format('m');
$day = $dateObj->format('d');

// Calculate date ranges based on view
switch ($viewMode) {
    case 'week':
        $startOfWeek = clone $dateObj;
        $startOfWeek->modify('monday this week');
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days');
        
        $dateFrom = $startOfWeek->format('Y-m-d');
        $dateTo = $endOfWeek->format('Y-m-d');
        break;
        
    case 'day':
        $dateFrom = $currentDate;
        $dateTo = $currentDate;
        break;
        
    default: // month
        $firstDayOfMonth = new DateTime("{$year}-{$month}-01");
        $lastDayOfMonth = clone $firstDayOfMonth;
        $lastDayOfMonth->modify('last day of this month');
        
        $dateFrom = $firstDayOfMonth->format('Y-m-d');
        $dateTo = $lastDayOfMonth->format('Y-m-d');
        break;
}

// Build filters
$filters = [];
if ($userRole === 'instructor') {
    $instructorModel = new Instructor();
    $instructor = $instructorModel->getByUserId($userId);
    if ($instructor) {
        $filters['instructor_id'] = $instructor['instructor_id'];
    }
} elseif ($userRole === 'student') {
    $studentModel = new Student();
    $student = $studentModel->getByUserId($userId);
    if ($student) {
        $filters['student_id'] = $student['student_id'];
    }
}

// Get filter from query params
if (!empty($_GET['instructor_id'])) {
    $filters['instructor_id'] = $_GET['instructor_id'];
}
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

// Fetch lessons
$lessons = $lessonModel->getLessonsByDateRange($dateFrom, $dateTo, $filters);

// Get instructors for filter (admin/staff only)
$instructors = [];
if ($userRole === 'admin' || $userRole === 'staff') {
    $instructorModel = new Instructor();
    $instructors = $instructorModel->getAllWithUserInfo();
}

// Group lessons by date
$lessonsByDate = [];
foreach ($lessons as $lesson) {
    $lessonDate = $lesson['lesson_date'];
    if (!isset($lessonsByDate[$lessonDate])) {
        $lessonsByDate[$lessonDate] = [];
    }
    $lessonsByDate[$lessonDate][] = $lesson;
}

$pageTitle = 'Calendar - Lesson Schedule';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Calendar Header -->
        <div class="calendar-header">
            <div class="calendar-title">
                <h2><i class="fas fa-calendar-alt"></i> Lesson Calendar</h2>
                <p>Visual schedule and planning system</p>
            </div>
            <div class="calendar-controls">
                <div class="view-switcher">
                    <button class="view-btn <?php echo $viewMode === 'month' ? 'active' : ''; ?>" 
                            onclick="switchView('month')">
                        <i class="fas fa-calendar"></i> Month
                    </button>
                    <button class="view-btn <?php echo $viewMode === 'week' ? 'active' : ''; ?>" 
                            onclick="switchView('week')">
                        <i class="fas fa-calendar-week"></i> Week
                    </button>
                    <button class="view-btn <?php echo $viewMode === 'day' ? 'active' : ''; ?>" 
                            onclick="switchView('day')">
                        <i class="fas fa-calendar-day"></i> Day
                    </button>
                </div>
                <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Lesson
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="content-area">
            <!-- Navigation and Filters -->
            <div class="calendar-navigation">
                <div class="nav-controls">
                    <button class="nav-btn" onclick="navigateCalendar('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h3 id="currentPeriod">
                        <?php
                        switch ($viewMode) {
                            case 'week':
                                echo $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d, Y');
                                break;
                            case 'day':
                                echo $dateObj->format('l, F d, Y');
                                break;
                            default:
                                echo $dateObj->format('F Y');
                                break;
                        }
                        ?>
                    </h3>
                    <button class="nav-btn" onclick="navigateCalendar('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="nav-btn today-btn" onclick="goToToday()">
                        <i class="fas fa-calendar-check"></i> Today
                    </button>
                </div>
                
                <div class="calendar-filters">
                    <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                    <select id="instructorFilter" class="filter-select" onchange="applyFilter()">
                        <option value="">All Instructors</option>
                        <?php foreach ($instructors as $instructor): ?>
                        <option value="<?php echo $instructor['instructor_id']; ?>" 
                                <?php echo (isset($filters['instructor_id']) && $filters['instructor_id'] == $instructor['instructor_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    
                    <select id="statusFilter" class="filter-select" onchange="applyFilter()">
                        <option value="">All Status</option>
                        <option value="scheduled" <?php echo (isset($filters['status']) && $filters['status'] === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo (isset($filters['status']) && $filters['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo (isset($filters['status']) && $filters['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <span class="legend-color status-scheduled"></span>
                    <span>Scheduled</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color status-completed"></span>
                    <span>Completed</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color status-cancelled"></span>
                    <span>Cancelled</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color status-in_progress"></span>
                    <span>In Progress</span>
                </div>
            </div>
            
            <!-- Calendar Views -->
            <div class="calendar-container">
                <?php if ($viewMode === 'month'): ?>
                    <!-- Month View -->
                    <?php
                    $firstDayOfMonth = new DateTime("{$year}-{$month}-01");
                    $lastDayOfMonth = clone $firstDayOfMonth;
                    $lastDayOfMonth->modify('last day of this month');
                    
                    $startDay = clone $firstDayOfMonth;
                    $startDay->modify('monday this week');
                    
                    $endDay = clone $lastDayOfMonth;
                    $endDay->modify('sunday this week');
                    ?>
                    
                    <div class="month-calendar">
                        <!-- Day Headers -->
                        <div class="calendar-header-row">
                            <div class="day-header">Mon</div>
                            <div class="day-header">Tue</div>
                            <div class="day-header">Wed</div>
                            <div class="day-header">Thu</div>
                            <div class="day-header">Fri</div>
                            <div class="day-header">Sat</div>
                            <div class="day-header">Sun</div>
                        </div>
                        
                        <!-- Calendar Days -->
                        <div class="calendar-grid">
                            <?php
                            $current = clone $startDay;
                            while ($current <= $endDay) {
                                $currentDateStr = $current->format('Y-m-d');
                                $isCurrentMonth = $current->format('m') == $month;
                                $isToday = $currentDateStr === date('Y-m-d');
                                $dayLessons = $lessonsByDate[$currentDateStr] ?? [];
                                ?>
                                
                                <div class="calendar-day <?php echo !$isCurrentMonth ? 'other-month' : ''; ?> <?php echo $isToday ? 'today' : ''; ?>"
                                     data-date="<?php echo $currentDateStr; ?>"
                                     onclick="viewDayDetails('<?php echo $currentDateStr; ?>')">
                                    <div class="day-number"><?php echo $current->format('j'); ?></div>
                                    
                                    <?php if (!empty($dayLessons)): ?>
                                        <div class="day-lessons">
                                            <?php 
                                            $displayCount = min(3, count($dayLessons));
                                            for ($i = 0; $i < $displayCount; $i++): 
                                                $lesson = $dayLessons[$i];
                                            ?>
                                                <div class="lesson-item status-<?php echo $lesson['status']; ?>"
                                                     onclick="event.stopPropagation(); viewLesson(<?php echo $lesson['lesson_id']; ?>)">
                                                    <span class="lesson-time">
                                                        <?php echo date('g:ia', strtotime($lesson['start_time'])); ?>
                                                    </span>
                                                    <span class="lesson-name">
                                                        <?php 
                                                        if ($userRole !== 'student') {
                                                            echo htmlspecialchars(substr($lesson['student_name'], 0, 15));
                                                        } else {
                                                            echo htmlspecialchars(substr($lesson['instructor_name'], 0, 15));
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            <?php endfor; ?>
                                            
                                            <?php if (count($dayLessons) > 3): ?>
                                                <div class="more-lessons">
                                                    +<?php echo count($dayLessons) - 3; ?> more
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php
                                $current->modify('+1 day');
                            }
                            ?>
                        </div>
                    </div>
                    
                <?php elseif ($viewMode === 'week'): ?>
                    <!-- Week View -->
                    <div class="week-calendar">
                        <div class="week-header">
                            <div class="time-column"></div>
                            <?php
                            $current = clone $startOfWeek;
                            for ($i = 0; $i < 7; $i++) {
                                $isToday = $current->format('Y-m-d') === date('Y-m-d');
                                ?>
                                <div class="week-day-header <?php echo $isToday ? 'today' : ''; ?>">
                                    <div class="day-name"><?php echo $current->format('D'); ?></div>
                                    <div class="day-date"><?php echo $current->format('j'); ?></div>
                                </div>
                                <?php
                                $current->modify('+1 day');
                            }
                            ?>
                        </div>
                        
                        <div class="week-body">
                            <?php for ($hour = 8; $hour <= 18; $hour++): ?>
                                <div class="week-row">
                                    <div class="time-label">
                                        <?php echo sprintf('%02d:00', $hour); ?>
                                    </div>
                                    
                                    <?php
                                    $current = clone $startOfWeek;
                                    for ($i = 0; $i < 7; $i++) {
                                        $currentDateStr = $current->format('Y-m-d');
                                        $dayLessons = $lessonsByDate[$currentDateStr] ?? [];
                                        
                                        // Filter lessons for this hour
                                        $hourLessons = array_filter($dayLessons, function($lesson) use ($hour) {
                                            $lessonHour = (int)date('H', strtotime($lesson['start_time']));
                                            return $lessonHour === $hour;
                                        });
                                        ?>
                                        
                                        <div class="week-cell" data-date="<?php echo $currentDateStr; ?>" data-hour="<?php echo $hour; ?>">
                                            <?php foreach ($hourLessons as $lesson): ?>
                                                <div class="week-lesson status-<?php echo $lesson['status']; ?>"
                                                     onclick="viewLesson(<?php echo $lesson['lesson_id']; ?>)">
                                                    <div class="lesson-time-range">
                                                        <?php echo date('g:ia', strtotime($lesson['start_time'])); ?> - 
                                                        <?php echo date('g:ia', strtotime($lesson['end_time'])); ?>
                                                    </div>
                                                    <div class="lesson-details">
                                                        <?php 
                                                        if ($userRole !== 'student') {
                                                            echo htmlspecialchars($lesson['student_name']);
                                                        } else {
                                                            echo htmlspecialchars($lesson['instructor_name']);
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="lesson-type"><?php echo ucfirst($lesson['lesson_type']); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <?php
                                        $current->modify('+1 day');
                                    }
                                    ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Day View -->
                    <div class="day-calendar">
                        <div class="day-header-info">
                            <h3><?php echo $dateObj->format('l, F d, Y'); ?></h3>
                            <p><?php echo count($lessonsByDate[$currentDate] ?? []); ?> lesson(s) scheduled</p>
                        </div>
                        
                        <div class="day-timeline">
                            <?php 
                            $dayLessons = $lessonsByDate[$currentDate] ?? [];
                            if (empty($dayLessons)):
                            ?>
                                <div class="no-lessons">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No lessons scheduled for this day</p>
                                    <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                        <a href="create.php?date=<?php echo $currentDate; ?>" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Schedule Lesson
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($dayLessons as $lesson): ?>
                                    <div class="timeline-lesson status-<?php echo $lesson['status']; ?>"
                                         onclick="viewLesson(<?php echo $lesson['lesson_id']; ?>)">
                                        <div class="timeline-time">
                                            <div class="time-start"><?php echo date('g:i A', strtotime($lesson['start_time'])); ?></div>
                                            <div class="time-end"><?php echo date('g:i A', strtotime($lesson['end_time'])); ?></div>
                                            <div class="time-duration"><?php echo $lesson['duration_minutes']; ?>min</div>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="lesson-header">
                                                <h4>
                                                    <?php 
                                                    if ($userRole !== 'student') {
                                                        echo '<i class="fas fa-user-graduate"></i> ' . htmlspecialchars($lesson['student_name']);
                                                    } else {
                                                        echo '<i class="fas fa-user-tie"></i> ' . htmlspecialchars($lesson['instructor_name']);
                                                    }
                                                    ?>
                                                </h4>
                                                <span class="lesson-status"><?php echo ucfirst($lesson['status']); ?></span>
                                            </div>
                                            <div class="lesson-info">
                                                <div class="info-badge">
                                                    <i class="fas fa-book"></i>
                                                    <?php echo ucfirst($lesson['lesson_type']); ?>
                                                </div>
                                                <div class="info-badge">
                                                    <i class="fas fa-car"></i>
                                                    <?php echo htmlspecialchars($lesson['registration_number'] ?? 'N/A'); ?>
                                                </div>
                                                <div class="info-badge">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($lesson['pickup_location']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* ============= CALENDAR VIEW PAGE STYLES ============= */

/* Calendar Header */
.calendar-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.calendar-title h2 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.8rem;
}

.calendar-title p {
    margin: 0;
    opacity: 0.9;
}

.calendar-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.view-switcher {
    display: flex;
    gap: 0.5rem;
    background: rgba(255,255,255,0.2);
    padding: 0.25rem;
    border-radius: 8px;
}

.view-btn {
    padding: 0.5rem 1rem;
    background: none;
    border: none;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.view-btn.active,
.view-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* Navigation */
.calendar-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.nav-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: none;
    background: #f8f9fa;
    color: #333;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-btn:hover {
    background: #667eea;
    color: white;
}

.today-btn {
    width: auto;
    padding: 0 1rem;
    gap: 0.5rem;
}

#currentPeriod {
    margin: 0;
    font-size: 1.3rem;
    color: #333;
    min-width: 250px;
    text-align: center;
}

.calendar-filters {
    display: flex;
    gap: 1rem;
}

.filter-select {
    padding: 0.5rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
}

/* Legend */
.calendar-legend {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding: 1rem 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

.legend-color.status-scheduled { background: #ffc107; }
.legend-color.status-completed { background: #28a745; }
.legend-color.status-cancelled { background: #dc3545; }
.legend-color.status-in_progress { background: #17a2b8; }

/* Calendar Container */
.calendar-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

/* Month Calendar */
.month-calendar {
    background: white;
}

.calendar-header-row {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.day-header {
    padding: 1rem;
    text-align: center;
    font-weight: 600;
    color: #666;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
    min-height: 120px;
    padding: 0.75rem;
    border: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.calendar-day:hover {
    background: #f8f9fa;
}

.calendar-day.other-month {
    background: #fafafa;
    opacity: 0.6;
}

.calendar-day.today {
    background: #fff3cd;
    border-color: #ffc107;
}

.day-number {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

.calendar-day.today .day-number {
    background: #ffc107;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.day-lessons {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.lesson-item {
    padding: 0.4rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.25rem;
}

.lesson-item.status-scheduled { background: #fff3cd; color: #856404; }
.lesson-item.status-completed { background: #d4edda; color: #155724; }
.lesson-item.status-cancelled { background: #f8d7da; color: #721c24; }
.lesson-item.status-in_progress { background: #d1ecf1; color: #0c5460; }

.lesson-item:hover {
    transform: scale(1.05);
}

.lesson-time {
    font-weight: 600;
}

.lesson-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.more-lessons {
    text-align: center;
    padding: 0.25rem;
    font-size: 0.7rem;
    color: #667eea;
    font-weight: 600;
}

/* Week Calendar */
.week-calendar {
    background: white;
}

.week-header {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.week-day-header {
    padding: 1rem;
    text-align: center;
}

.week-day-header.today {
    background: #fff3cd;
}

.day-name {
    font-weight: 600;
    color: #666;
    margin-bottom: 0.25rem;
}

.day-date {
    font-size: 1.2rem;
    font-weight: 700;
    color: #333;
}

.week-body {
    display: flex;
    flex-direction: column;
}

.week-row {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    border-bottom: 1px solid #e9ecef;
}

.time-label {
    padding: 1rem;
    font-weight: 600;
    color: #666;
    border-right: 2px solid #e9ecef;
    text-align: center;
}

.week-cell {
    padding: 0.5rem;
    border-right: 1px solid #e9ecef;
    min-height: 60px;
    position: relative;
}

.week-lesson {
    padding: 0.5rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.3s;
}

.week-lesson.status-scheduled { background: #fff3cd; }
.week-lesson.status-completed { background: #d4edda; }
.week-lesson.status-cancelled { background: #f8d7da; }
.week-lesson.status-in_progress { background: #d1ecf1; }

.week-lesson:hover {
    transform: scale(1.05);
}

.lesson-time-range {
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.lesson-details {
    font-size: 0.85rem;
    font-weight: 500;
}

.lesson-type {
    font-size: 0.7rem;
    opacity: 0.8;
}

/* Day Calendar */
.day-calendar {
    background: white;
    padding: 2rem;
}

.day-header-info {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
}

.day-header-info h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.day-header-info p {
    margin: 0;
    color: #666;
}

.day-timeline {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.timeline-lesson {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    border-left: 4px solid;
}

.timeline-lesson.status-scheduled {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.timeline-lesson.status-completed {
    background: #d4edda;
    border-left-color: #28a745;
}

.timeline-lesson.status-cancelled {
    background: #f8d7da;
    border-left-color: #dc3545;
}

.timeline-lesson.status-in_progress {
    background: #d1ecf1;
    border-left-color: #17a2b8;
}

.timeline-lesson:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.timeline-time {
    text-align: center;
    min-width: 100px;
}

.time-start {
    font-size: 1.2rem;
    font-weight: 700;
    color: #333;
}

.time-end {
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.25rem;
}

.time-duration {
    font-size: 0.8rem;
    color: #888;
    margin-top: 0.5rem;
}

.timeline-content {
    flex: 1;
}

.lesson-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.lesson-header h4 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #333;
}

.lesson-status {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    background: rgba(0,0,0,0.1);
}

.lesson-info {
    display: flex;
    gap: 1rem;
}

.info-badge {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    color: #666;
}

.info-badge i {
    color: #667eea;
}

.no-lessons {
    text-align: center;
    padding: 3rem;
    color: #999;
}

.no-lessons i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.no-lessons p {
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 1200px) {
    .calendar-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .calendar-navigation {
        flex-direction: column;
        gap: 1rem;
    }
    
    .calendar-legend {
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .calendar-grid {
        grid-template-columns: repeat(7, minmax(100px, 1fr));
        overflow-x: auto;
    }
    
    .week-row {
        grid-template-columns: 60px repeat(7, minmax(100px, 1fr));
        overflow-x: auto;
    }
    
    .calendar-day {
        min-height: 100px;
    }
    
    .view-switcher {
        width: 100%;
    }
    
    .view-btn {
        flex: 1;
        justify-content: center;
    }
    
    .calendar-filters {
        flex-direction: column;
        width: 100%;
    }
    
    .filter-select {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .calendar-header {
        padding: 1.5rem;
    }
    
    .calendar-title h2 {
        font-size: 1.4rem;
    }
    
    .nav-controls {
        flex-wrap: wrap;
    }
    
    #currentPeriod {
        font-size: 1.1rem;
        min-width: 200px;
    }
    
    .calendar-legend {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .timeline-lesson {
        flex-direction: column;
    }
    
    .timeline-time {
        text-align: left;
    }
}

/* Print Styles */
@media print {
    .sidebar,
    .calendar-header .calendar-controls,
    .calendar-navigation,
    .calendar-legend {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
    }
    
    .calendar-container {
        box-shadow: none;
    }
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.loading .calendar-day,
.loading .week-cell,
.loading .timeline-lesson {
    animation: pulse 1.5s ease-in-out infinite;
}

/* Tooltip Styles */
.tooltip {
    position: absolute;
    background: #333;
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    z-index: 1000;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s;
}

.tooltip.show {
    opacity: 1;
}
</style>

<script>
const currentView = '<?php echo $viewMode; ?>';
const currentDate = '<?php echo $currentDate; ?>';

function switchView(view) {
    const url = new URL(window.location.href);
    url.searchParams.set('view', view);
    window.location.href = url.toString();
}

function navigateCalendar(direction) {
    const url = new URL(window.location.href);
    const date = new Date(currentDate);
    
    if (currentView === 'month') {
        date.setMonth(date.getMonth() + (direction === 'next' ? 1 : -1));
    } else if (currentView === 'week') {
        date.setDate(date.getDate() + (direction === 'next' ? 7 : -7));
    } else {
        date.setDate(date.getDate() + (direction === 'next' ? 1 : -1));
    }
    
    url.searchParams.set('date', date.toISOString().split('T')[0]);
    window.location.href = url.toString();
}

function goToToday() {
    const url = new URL(window.location.href);
    const today = new Date().toISOString().split('T')[0];
    url.searchParams.set('date', today);
    window.location.href = url.toString();
}

function viewDayDetails(date) {
    const url = new URL(window.location.href);
    url.searchParams.set('view', 'day');
    url.searchParams.set('date', date);
    window.location.href = url.toString();
}

function viewLesson(lessonId) {
    window.location.href = 'view.php?id=' + lessonId;
}

function applyFilter() {
    const url = new URL(window.location.href);
    
    const instructorFilter = document.getElementById('instructorFilter');
    if (instructorFilter) {
        const instructorId = instructorFilter.value;
        if (instructorId) {
            url.searchParams.set('instructor_id', instructorId);
        } else {
            url.searchParams.delete('instructor_id');
        }
    }
    
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        const status = statusFilter.value;
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
    }
    
    window.location.href = url.toString();
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) return;
    
    switch(e.key) {
        case 'ArrowLeft':
            navigateCalendar('prev');
            break;
        case 'ArrowRight':
            navigateCalendar('next');
            break;
        case 't':
        case 'T':
            goToToday();
            break;
        case 'm':
        case 'M':
            switchView('month');
            break;
        case 'w':
        case 'W':
            switchView('week');
            break;
        case 'd':
        case 'D':
            switchView('day');
            break;
    }
});
</script>

<?php include '../views/layouts/footer.php'; ?>