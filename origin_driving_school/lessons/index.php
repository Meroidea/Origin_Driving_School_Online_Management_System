<?php
/**
 * file path: lessons/index.php
 * 
 * Professional Lessons Management Dashboard - Origin Driving School
 * 
 * Advanced lesson scheduling and management system with analytics,
 * bulk operations, and intelligent filtering
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
require_once '../app/models/CourseAndOther.php';

// Require login
requireLogin();

$lessonModel = new Lesson();
$userRole = getCurrentUserRole();
$userId = getCurrentUserId();

// Get advanced filter parameters
$status = $_GET['status'] ?? '';
$lessonType = $_GET['lesson_type'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$searchTerm = $_GET['search'] ?? '';
$selectedInstructor = $_GET['instructor_id'] ?? '';
$view = $_GET['view'] ?? 'list'; // list or grid

// Build search criteria
$searchCriteria = [];
if ($searchTerm) $searchCriteria['search_term'] = $searchTerm;
if ($status) $searchCriteria['status'] = $status;
if ($lessonType) $searchCriteria['lesson_type'] = $lessonType;
if ($dateFrom) $searchCriteria['date_from'] = $dateFrom;
if ($dateTo) $searchCriteria['date_to'] = $dateTo;

// Fetch lessons based on user role
$lessons = [];
$userContext = null;

if ($userRole === 'admin' || $userRole === 'staff') {
    // Admin/Staff see all lessons with advanced filtering
    if ($selectedInstructor) {
        $searchCriteria['instructor_id'] = $selectedInstructor;
    }
    
    if (!empty($searchCriteria)) {
        $lessons = $lessonModel->advancedSearch($searchCriteria);
    } elseif ($dateFilter) {
        $lessons = $lessonModel->getByDate($dateFilter);
    } else {
        $lessons = $lessonModel->getAllWithDetails();
    }
} elseif ($userRole === 'instructor') {
    // Instructors see their lessons
    $instructorModel = new Instructor();
    $instructor = $instructorModel->getByUserId($userId);
    $userContext = $instructor;
    
    if ($instructor) {
        $searchCriteria['instructor_id'] = $instructor['instructor_id'];
        if (!empty($searchCriteria)) {
            $lessons = $lessonModel->advancedSearch($searchCriteria);
        } else {
            $lessons = $lessonModel->getInstructorLessons($instructor['instructor_id']);
        }
    }
} elseif ($userRole === 'student') {
    // Students see their lessons
    $studentModel = new Student();
    $student = $studentModel->getByUserId($userId);
    $userContext = $student;
    
    if ($student) {
        $searchCriteria['student_id'] = $student['student_id'];
        if (!empty($searchCriteria)) {
            $lessons = $lessonModel->advancedSearch($searchCriteria);
        } else {
            $lessons = $lessonModel->getStudentLessons($student['student_id']);
        }
    }
}

// Get comprehensive statistics
$overallStats = $lessonModel->getLessonStatistics();

// Calculate detailed statistics
$stats = [
    'total_lessons' => count($lessons),
    'scheduled' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'in_progress' => 0,
    'today' => 0,
    'tomorrow' => 0,
    'this_week' => 0,
    'this_month' => 0,
    'avg_duration' => 0,
    'total_hours' => 0
];

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$weekEnd = date('Y-m-d', strtotime('+7 days'));
$monthEnd = date('Y-m-d', strtotime('last day of this month'));

$totalMinutes = 0;
$lessonCount = 0;

foreach ($lessons as $lesson) {
    // Status counts
    if (isset($stats[$lesson['status']])) {
        $stats[$lesson['status']]++;
    }
    
    // Date-based counts
    if ($lesson['lesson_date'] === $today && $lesson['status'] === 'scheduled') {
        $stats['today']++;
    }
    if ($lesson['lesson_date'] === $tomorrow && $lesson['status'] === 'scheduled') {
        $stats['tomorrow']++;
    }
    if ($lesson['lesson_date'] >= $today && $lesson['lesson_date'] <= $weekEnd && $lesson['status'] === 'scheduled') {
        $stats['this_week']++;
    }
    if ($lesson['lesson_date'] >= $today && $lesson['lesson_date'] <= $monthEnd && $lesson['status'] === 'scheduled') {
        $stats['this_month']++;
    }
    
    // Duration calculations
    if (isset($lesson['duration_minutes'])) {
        $totalMinutes += $lesson['duration_minutes'];
        $lessonCount++;
    }
}

if ($lessonCount > 0) {
    $stats['avg_duration'] = round($totalMinutes / $lessonCount);
    $stats['total_hours'] = round($totalMinutes / 60, 1);
}

// Get instructors for filter (admin/staff only)
$instructors = [];
if ($userRole === 'admin' || $userRole === 'staff') {
    $instructorModel = new Instructor();
    $instructors = $instructorModel->getAllWithUserInfo();
}

$pageTitle = 'Professional Lesson Management';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Enhanced Top Bar -->
        <div class="top-bar lesson-top-bar">
            <div class="page-header-section">
                <div class="page-title-wrapper">
                    <div class="icon-wrapper">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h2>Lesson Management System</h2>
                        <p class="page-subtitle">
                            <i class="fas fa-info-circle"></i> 
                            Comprehensive lesson scheduling, tracking, and analytics
                        </p>
                    </div>
                </div>
            </div>
            <div class="action-buttons-group">
                <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                    <a href="create.php" class="btn btn-primary btn-with-icon">
                        <i class="fas fa-plus-circle"></i> Schedule Lesson
                    </a>
                    <a href="calendar.php" class="btn btn-info btn-with-icon">
                        <i class="fas fa-calendar"></i> Calendar
                    </a>
                    <div class="dropdown-wrapper">
                        <button class="btn btn-secondary dropdown-toggle" onclick="toggleDropdown('exportMenu')">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <div id="exportMenu" class="dropdown-menu">
                            <a href="export.php?format=pdf" class="dropdown-item">
                                <i class="fas fa-file-pdf"></i> Export to PDF
                            </a>
                            <a href="export.php?format=excel" class="dropdown-item">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </a>
                            <a href="export.php?format=csv" class="dropdown-item">
                                <i class="fas fa-file-csv"></i> Export to CSV
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <button class="btn btn-outline" onclick="toggleView()">
                    <i class="fas fa-th" id="viewIcon"></i> <span id="viewText">Grid</span>
                </button>
            </div>
        </div>
        
        <div class="content-area">
            <?php 
            $flashMessage = getFlashMessage();
            if ($flashMessage): 
            ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible">
                    <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flashMessage['message']); ?></span>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Enhanced Statistics Dashboard -->
            <div class="stats-section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-line"></i> Lesson Overview & Analytics</h3>
                    <div class="time-indicator">
                        <i class="fas fa-clock"></i> 
                        <span id="currentTime"></span>
                    </div>
                </div>
                
                <div class="stats-grid-enhanced">
                    <!-- Primary Stats -->
                    <div class="stat-card-enhanced primary-stat">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper gradient-purple">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="stat-trend">
                                <span class="trend-badge positive">
                                    <i class="fas fa-arrow-up"></i> Active
                                </span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number"><?php echo $stats['total_lessons']; ?></h3>
                            <p class="stat-label">Total Lessons</p>
                            <div class="stat-footer">
                                <small><i class="fas fa-calendar-check"></i> All time records</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper gradient-blue">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number"><?php echo $stats['scheduled']; ?></h3>
                            <p class="stat-label">Scheduled</p>
                            <div class="progress-bar-mini">
                                <div class="progress-fill" style="width: <?php echo $stats['total_lessons'] > 0 ? ($stats['scheduled'] / $stats['total_lessons'] * 100) : 0; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper gradient-green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number"><?php echo $stats['completed']; ?></h3>
                            <p class="stat-label">Completed</p>
                            <div class="progress-bar-mini">
                                <div class="progress-fill bg-success" style="width: <?php echo $stats['total_lessons'] > 0 ? ($stats['completed'] / $stats['total_lessons'] * 100) : 0; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper gradient-orange">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number"><?php echo $stats['today']; ?></h3>
                            <p class="stat-label">Today's Lessons</p>
                            <div class="stat-footer">
                                <small><i class="fas fa-hourglass-half"></i> In progress & scheduled</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper gradient-teal">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number"><?php echo $stats['this_week']; ?></h3>
                            <p class="stat-label">This Week</p>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper gradient-indigo">
                                <i class="fas fa-hourglass-end"></i>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number"><?php echo $stats['avg_duration']; ?></h3>
                            <p class="stat-label">Avg. Duration (min)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Panel -->
            <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
            <div class="quick-actions-panel">
                <div class="quick-action-item" onclick="window.location.href='create.php'">
                    <i class="fas fa-plus-circle"></i>
                    <span>Schedule New</span>
                </div>
                <div class="quick-action-item" onclick="window.location.href='?date=<?php echo $today; ?>'">
                    <i class="fas fa-calendar-day"></i>
                    <span>Today's Schedule</span>
                </div>
                <div class="quick-action-item" onclick="window.location.href='calendar.php'">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar View</span>
                </div>
                <div class="quick-action-item" onclick="window.location.href='reports.php'">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </div>
                <div class="quick-action-item" onclick="refreshLessons()">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Advanced Filter Section -->
            <div class="card filter-card">
                <div class="card-header-enhanced">
                    <div class="header-left">
                        <i class="fas fa-filter"></i>
                        <h3>Advanced Filters & Search</h3>
                    </div>
                    <button class="btn-collapse" onclick="toggleSection('filterSection')">
                        <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
                    </button>
                </div>
                <div class="card-body" id="filterSection">
                    <form method="GET" action="" class="advanced-filter-form">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label for="status">
                                    <i class="fas fa-flag"></i> Status
                                </label>
                                <select name="status" id="status" class="form-control-modern">
                                    <option value="">All Status</option>
                                    <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>
                                        ⏰ Scheduled
                                    </option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>
                                        ✅ Completed
                                    </option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>
                                        ❌ Cancelled
                                    </option>
                                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>
                                        🔄 In Progress
                                    </option>
                                    <option value="rescheduled" <?php echo $status === 'rescheduled' ? 'selected' : ''; ?>>
                                        📅 Rescheduled
                                    </option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="lesson_type">
                                    <i class="fas fa-car"></i> Lesson Type
                                </label>
                                <select name="lesson_type" id="lesson_type" class="form-control-modern">
                                    <option value="">All Types</option>
                                    <option value="theory" <?php echo $lessonType === 'theory' ? 'selected' : ''; ?>>
                                        📚 Theory
                                    </option>
                                    <option value="practical" <?php echo $lessonType === 'practical' ? 'selected' : ''; ?>>
                                        🚗 Practical
                                    </option>
                                    <option value="highway" <?php echo $lessonType === 'highway' ? 'selected' : ''; ?>>
                                        🛣️ Highway
                                    </option>
                                    <option value="parking" <?php echo $lessonType === 'parking' ? 'selected' : ''; ?>>
                                        🅿️ Parking
                                    </option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="date_from">
                                    <i class="fas fa-calendar-alt"></i> Date From
                                </label>
                                <input type="date" name="date_from" id="date_from" class="form-control-modern" 
                                       value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="date_to">
                                    <i class="fas fa-calendar-check"></i> Date To
                                </label>
                                <input type="date" name="date_to" id="date_to" class="form-control-modern" 
                                       value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            
                            <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                            <div class="filter-group">
                                <label for="instructor_id">
                                    <i class="fas fa-user-tie"></i> Instructor
                                </label>
                                <select name="instructor_id" id="instructor_id" class="form-control-modern">
                                    <option value="">All Instructors</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo $instructor['instructor_id']; ?>" 
                                            <?php echo $selectedInstructor == $instructor['instructor_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="search">
                                    <i class="fas fa-search"></i> Search
                                </label>
                                <input type="text" name="search" id="search" class="form-control-modern" 
                                       placeholder="Student, Instructor, License..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary btn-with-icon">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="<?php echo APP_URL; ?>/lessons/" class="btn btn-secondary btn-with-icon">
                                <i class="fas fa-redo"></i> Reset All
                            </a>
                            <button type="button" class="btn btn-outline" onclick="saveFilters()">
                                <i class="fas fa-save"></i> Save Filter Preset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bulk Actions Bar (Admin/Staff only) -->
            <?php if (($userRole === 'admin' || $userRole === 'staff') && !empty($lessons)): ?>
            <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
                <div class="bulk-info">
                    <i class="fas fa-check-square"></i>
                    <span id="selectedCount">0</span> lessons selected
                </div>
                <div class="bulk-buttons">
                    <button class="btn btn-sm btn-success" onclick="bulkAction('complete')">
                        <i class="fas fa-check"></i> Mark Complete
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkAction('reschedule')">
                        <i class="fas fa-calendar"></i> Reschedule
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('cancel')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        <i class="fas fa-times-circle"></i> Clear
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Lessons Display -->
            <div class="card lessons-card">
                <div class="card-header-enhanced">
                    <div class="header-left">
                        <i class="fas fa-list-alt"></i>
                        <h3>Lesson Schedule</h3>
                        <span class="badge badge-primary"><?php echo count($lessons); ?> Records</span>
                    </div>
                    <div class="header-right">
                        <div class="view-options">
                            <button class="view-btn active" data-view="list" onclick="switchView('list')">
                                <i class="fas fa-list"></i>
                            </button>
                            <button class="view-btn" data-view="grid" onclick="switchView('grid')">
                                <i class="fas fa-th-large"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($lessons)): ?>
                        <div class="empty-state-enhanced">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No Lessons Found</h3>
                            <p>No lessons match your current filter criteria.</p>
                            <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                <a href="create.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus-circle"></i> Schedule Your First Lesson
                                </a>
                            <?php else: ?>
                                <a href="<?php echo APP_URL; ?>/lessons/" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- List View -->
                        <div id="listView" class="lessons-list-view">
                            <div class="table-wrapper">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                            <th width="40">
                                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                            </th>
                                            <?php endif; ?>
                                            <th>
                                                <i class="fas fa-calendar"></i> Date & Time
                                            </th>
                                            <?php if ($userRole !== 'student'): ?>
                                            <th>
                                                <i class="fas fa-user-graduate"></i> Student
                                            </th>
                                            <?php endif; ?>
                                            <?php if ($userRole !== 'instructor'): ?>
                                            <th>
                                                <i class="fas fa-user-tie"></i> Instructor
                                            </th>
                                            <?php endif; ?>
                                            <th>
                                                <i class="fas fa-car"></i> Vehicle
                                            </th>
                                            <th>
                                                <i class="fas fa-book"></i> Type
                                            </th>
                                            <th>
                                                <i class="fas fa-map-marker-alt"></i> Location
                                            </th>
                                            <th>
                                                <i class="fas fa-flag"></i> Status
                                            </th>
                                            <th width="180">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lessons as $lesson): ?>
                                        <?php
                                        $isPast = strtotime($lesson['lesson_date']) < strtotime($today);
                                        $isToday = $lesson['lesson_date'] === $today;
                                        $isTomorrow = $lesson['lesson_date'] === $tomorrow;
                                        $rowClass = $isToday ? 'today-row' : ($isTomorrow ? 'tomorrow-row' : '');
                                        ?>
                                        <tr class="<?php echo $rowClass; ?> <?php echo $isPast ? 'past-lesson' : ''; ?>">
                                            <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                            <td>
                                                <input type="checkbox" class="lesson-checkbox" 
                                                       value="<?php echo $lesson['lesson_id']; ?>"
                                                       onchange="updateBulkActions()">
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="date-time-cell">
                                                    <div class="date-display">
                                                        <strong><?php echo date('d M Y', strtotime($lesson['lesson_date'])); ?></strong>
                                                        <?php if ($isToday): ?>
                                                            <span class="badge badge-today">Today</span>
                                                        <?php elseif ($isTomorrow): ?>
                                                            <span class="badge badge-tomorrow">Tomorrow</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="time-display">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo date('h:i A', strtotime($lesson['start_time'])); ?> - 
                                                        <?php echo date('h:i A', strtotime($lesson['end_time'])); ?>
                                                        <?php if (isset($lesson['duration_minutes'])): ?>
                                                            <small class="duration-badge"><?php echo $lesson['duration_minutes']; ?>min</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <?php if ($userRole !== 'student'): ?>
                                            <td>
                                                <div class="user-cell">
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($lesson['student_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="user-info">
                                                        <strong><?php echo htmlspecialchars($lesson['student_name']); ?></strong>
                                                        <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($lesson['student_phone'] ?? 'N/A'); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                            <?php if ($userRole !== 'instructor'): ?>
                                            <td>
                                                <div class="instructor-cell">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                    <strong><?php echo htmlspecialchars($lesson['instructor_name']); ?></strong>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="vehicle-cell">
                                                    <div class="vehicle-plate"><?php echo htmlspecialchars($lesson['registration_number'] ?? 'N/A'); ?></div>
                                                    <small class="vehicle-model">
                                                        <?php echo htmlspecialchars(($lesson['make'] ?? '') . ' ' . ($lesson['model'] ?? '')); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $typeIcons = [
                                                    'theory' => 'fa-book',
                                                    'practical' => 'fa-car',
                                                    'highway' => 'fa-road',
                                                    'parking' => 'fa-parking'
                                                ];
                                                $icon = $typeIcons[$lesson['lesson_type']] ?? 'fa-graduation-cap';
                                                ?>
                                                <span class="badge badge-type badge-<?php echo $lesson['lesson_type']; ?>">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                    <?php echo ucfirst($lesson['lesson_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="location-cell">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <small><?php echo htmlspecialchars($lesson['pickup_location']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $statusConfig = [
                                                    'scheduled' => ['class' => 'warning', 'icon' => 'clock'],
                                                    'completed' => ['class' => 'success', 'icon' => 'check-circle'],
                                                    'cancelled' => ['class' => 'danger', 'icon' => 'times-circle'],
                                                    'in_progress' => ['class' => 'info', 'icon' => 'spinner'],
                                                    'rescheduled' => ['class' => 'secondary', 'icon' => 'redo']
                                                ];
                                                $config = $statusConfig[$lesson['status']] ?? ['class' => 'secondary', 'icon' => 'question'];
                                                ?>
                                                <span class="status-badge status-<?php echo $config['class']; ?>">
                                                    <i class="fas fa-<?php echo $config['icon']; ?>"></i>
                                                    <?php echo ucfirst($lesson['status']); ?>
                                                </span>
                                            </td>
                                            <td class="actions-cell">
                                                <div class="action-buttons">
                                                    <a href="view.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                       class="btn-action btn-view" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if (($userRole === 'admin' || $userRole === 'staff' || $userRole === 'instructor') && $lesson['status'] === 'scheduled'): ?>
                                                        <a href="edit.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                           class="btn-action btn-edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        <?php if ($userRole === 'instructor'): ?>
                                                        <a href="complete.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                           class="btn-action btn-complete" title="Complete">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                                        <a href="delete.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                           class="btn-action btn-delete" 
                                                           onclick="return confirm('Are you sure you want to cancel this lesson?');"
                                                           title="Cancel">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Grid View (Hidden by default) -->
                        <div id="gridView" class="lessons-grid-view" style="display: none;">
                            <div class="lessons-grid">
                                <?php foreach ($lessons as $lesson): ?>
                                <?php
                                $isToday = $lesson['lesson_date'] === $today;
                                $isTomorrow = $lesson['lesson_date'] === $tomorrow;
                                ?>
                                <div class="lesson-card <?php echo $isToday ? 'today-card' : ($isTomorrow ? 'tomorrow-card' : ''); ?>">
                                    <div class="lesson-card-header">
                                        <div class="lesson-date">
                                            <div class="date-day"><?php echo date('d', strtotime($lesson['lesson_date'])); ?></div>
                                            <div class="date-month"><?php echo date('M', strtotime($lesson['lesson_date'])); ?></div>
                                        </div>
                                        <div class="lesson-status">
                                            <?php
                                            $statusConfig = [
                                                'scheduled' => ['class' => 'warning', 'icon' => 'clock'],
                                                'completed' => ['class' => 'success', 'icon' => 'check-circle'],
                                                'cancelled' => ['class' => 'danger', 'icon' => 'times-circle'],
                                                'in_progress' => ['class' => 'info', 'icon' => 'spinner']
                                            ];
                                            $config = $statusConfig[$lesson['status']] ?? ['class' => 'secondary', 'icon' => 'question'];
                                            ?>
                                            <span class="status-badge-grid status-<?php echo $config['class']; ?>">
                                                <i class="fas fa-<?php echo $config['icon']; ?>"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="lesson-card-body">
                                        <div class="lesson-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('h:i A', strtotime($lesson['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($lesson['end_time'])); ?>
                                        </div>
                                        <div class="lesson-type-badge">
                                            <?php echo ucfirst($lesson['lesson_type']); ?>
                                        </div>
                                        <?php if ($userRole !== 'student'): ?>
                                        <div class="lesson-student">
                                            <i class="fas fa-user-graduate"></i>
                                            <?php echo htmlspecialchars($lesson['student_name']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($userRole !== 'instructor'): ?>
                                        <div class="lesson-instructor">
                                            <i class="fas fa-user-tie"></i>
                                            <?php echo htmlspecialchars($lesson['instructor_name']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="lesson-vehicle">
                                            <i class="fas fa-car"></i>
                                            <?php echo htmlspecialchars($lesson['registration_number'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                    <div class="lesson-card-footer">
                                        <a href="view.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn-card-action">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if (($userRole === 'admin' || $userRole === 'staff') && $lesson['status'] === 'scheduled'): ?>
                                        <a href="edit.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn-card-action">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/layouts/footer.php'; ?>

<style>
/* ============= LESSON INDEX PAGE STYLES ============= */

/* Top Bar Styling */
.lesson-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.page-header-section {
    flex: 1;
}

.page-title-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.icon-wrapper {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.page-title-wrapper h2 {
    margin: 0;
    font-size: 1.8rem;
}

.page-subtitle {
    margin-top: 0.5rem;
    opacity: 0.9;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.95);
}

.action-buttons-group {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.btn-with-icon {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Dropdown Styling */
.dropdown-wrapper {
    position: relative;
}

.dropdown-toggle::after {
    content: '▼';
    margin-left: 0.5rem;
    font-size: 0.7rem;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    min-width: 200px;
    margin-top: 0.5rem;
    display: none;
    z-index: 1000;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
}

.dropdown-item:hover {
    background: #f8f9fa;
    color: #667eea;
}

/* Statistics Section */
.stats-section {
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #333;
    font-size: 1.3rem;
    margin: 0;
}

.time-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.stats-grid-enhanced {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card-enhanced {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.stat-card-enhanced:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card-enhanced.primary-stat {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-icon-wrapper {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.gradient-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.gradient-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.gradient-green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.gradient-orange { background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%); }
.gradient-teal { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
.gradient-indigo { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); }

.stat-body .stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-body .stat-label {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}

.primary-stat .stat-label {
    color: rgba(255,255,255,0.9);
}

.stat-footer {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.primary-stat .stat-footer {
    border-top-color: rgba(255,255,255,0.2);
}

.stat-footer small {
    color: #888;
    font-size: 0.85rem;
}

.primary-stat .stat-footer small {
    color: rgba(255,255,255,0.8);
}

.trend-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.trend-badge.positive {
    background: rgba(56, 239, 125, 0.2);
    color: #11998e;
}

.progress-bar-mini {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

.progress-fill.bg-success {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
}

/* Quick Actions Panel */
.quick-actions-panel {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.quick-action-item {
    flex: 1;
    min-width: 150px;
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.quick-action-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.quick-action-item i {
    font-size: 1.8rem;
}

.quick-action-item span {
    font-size: 0.9rem;
    font-weight: 500;
}

/* Filter Card */
.filter-card {
    margin-bottom: 2rem;
}

.card-header-enhanced {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid #dee2e6;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-left h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
}

.btn-collapse {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #666;
    cursor: pointer;
    transition: transform 0.3s;
}

.btn-collapse:hover {
    color: #667eea;
}

.advanced-filter-form {
    padding: 1.5rem;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.filter-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-control-modern {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-control-modern:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    align-items: center;
}

/* Bulk Actions Bar */
.bulk-actions-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.bulk-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
}

.bulk-buttons {
    display: flex;
    gap: 0.75rem;
}

/* Lessons Card */
.lessons-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.view-options {
    display: flex;
    gap: 0.5rem;
    background: white;
    padding: 0.25rem;
    border-radius: 8px;
}

.view-btn {
    padding: 0.5rem 1rem;
    background: none;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    color: #666;
    transition: all 0.3s;
}

.view-btn.active,
.view-btn:hover {
    background: #667eea;
    color: white;
}

/* Modern Table */
.table-wrapper {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modern-table thead th {
    padding: 1rem;
    font-weight: 600;
    text-align: left;
    font-size: 0.9rem;
    white-space: nowrap;
}

.modern-table thead th i {
    margin-right: 0.5rem;
    opacity: 0.9;
}

.modern-table tbody tr {
    background: white;
    transition: all 0.3s;
}

.modern-table tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.modern-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.9rem;
}

.today-row {
    background: #fff3cd !important;
    border-left: 4px solid #ffc107;
}

.tomorrow-row {
    background: #d1ecf1 !important;
    border-left: 4px solid #17a2b8;
}

.past-lesson {
    opacity: 0.7;
}

/* Table Cell Styles */
.date-time-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.date-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.time-display {
    color: #666;
    font-size: 0.85rem;
}

.duration-badge {
    background: #e9ecef;
    padding: 0.15rem 0.5rem;
    border-radius: 12px;
    margin-left: 0.5rem;
    font-size: 0.75rem;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.user-info small {
    color: #888;
    font-size: 0.8rem;
}

.instructor-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vehicle-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.vehicle-plate {
    font-weight: 600;
    color: #333;
}

.vehicle-model {
    color: #888;
    font-size: 0.8rem;
}

.location-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
}

.location-cell i {
    color: #dc3545;
}

/* Badges */
.badge-type {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.badge-theory { background: #e3f2fd; color: #1976d2; }
.badge-practical { background: #f3e5f5; color: #7b1fa2; }
.badge-highway { background: #e8f5e9; color: #388e3c; }
.badge-parking { background: #fff3e0; color: #f57c00; }

.badge-today {
    background: #ffc107;
    color: #000;
    padding: 0.15rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-tomorrow {
    background: #17a2b8;
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.status-warning { background: #fff3cd; color: #856404; }
.status-success { background: #d4edda; color: #155724; }
.status-danger { background: #f8d7da; color: #721c24; }
.status-info { background: #d1ecf1; color: #0c5460; }
.status-secondary { background: #e2e3e5; color: #383d41; }

/* Action Buttons */
.actions-cell {
    padding: 0.75rem 1rem !important;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-view { background: #17a2b8; color: white; }
.btn-edit { background: #ffc107; color: #000; }
.btn-complete { background: #28a745; color: white; }
.btn-delete { background: #dc3545; color: white; }

.btn-action:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Grid View */
.lessons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}

.lesson-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.lesson-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.today-card {
    border: 2px solid #ffc107;
}

.tomorrow-card {
    border: 2px solid #17a2b8;
}

.lesson-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.lesson-date {
    text-align: center;
}

.date-day {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.date-month {
    font-size: 0.9rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.status-badge-grid {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.lesson-card-body {
    padding: 1.25rem;
}

.lesson-card-body > div {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #666;
}

.lesson-card-body > div:last-child {
    border-bottom: none;
}

.lesson-card-body i {
    color: #667eea;
    width: 20px;
}

.lesson-type-badge {
    display: inline-block;
    background: #f0f0f0;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    margin: 0.5rem 0;
}

.lesson-card-footer {
    padding: 1rem 1.25rem;
    background: #f8f9fa;
    display: flex;
    gap: 0.75rem;
}

.btn-card-action {
    flex: 1;
    padding: 0.5rem;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    color: #333;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-card-action:hover {
    border-color: #667eea;
    color: #667eea;
    background: #f8f9fa;
}

/* Empty State */
.empty-state-enhanced {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    font-size: 5rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

.empty-state-enhanced h3 {
    color: #333;
    margin-bottom: 0.75rem;
}

.empty-state-enhanced p {
    color: #666;
    margin-bottom: 1.5rem;
    font-size: 1.05rem;
}

/* Alert Styling */
.alert-dismissible {
    position: relative;
    padding-right: 3rem;
}

.alert-close {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: inherit;
    opacity: 0.7;
    cursor: pointer;
    font-size: 1.2rem;
    transition: opacity 0.3s;
}

.alert-close:hover {
    opacity: 1;
}

/* Responsive Design */
@media (max-width: 768px) {
    .lesson-top-bar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .action-buttons-group {
        width: 100%;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    
    .stats-grid-enhanced {
        grid-template-columns: 1fr;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-panel {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
    }
    
    .modern-table {
        font-size: 0.85rem;
    }
    
    .modern-table thead th,
    .modern-table tbody td {
        padding: 0.75rem 0.5rem;
    }
    
    .lessons-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .page-title-wrapper {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .quick-actions-panel {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-buttons-group {
        flex-direction: column;
        width: 100%;
    }
    
    .action-buttons-group .btn,
    .action-buttons-group .dropdown-wrapper {
        width: 100%;
    }
}
</style>

<script>
// ============= LESSON INDEX PAGE JAVASCRIPT =============

// Real-time clock update
function updateTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
    const dateStr = now.toLocaleDateString('en-US', { 
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        timeElement.textContent = `${dateStr} ${timeStr}`;
    }
}

// Initialize clock
setInterval(updateTime, 1000);
updateTime();

// Toggle section visibility (for collapsible sections)
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const icon = document.getElementById('filterToggleIcon');
    
    if (section.style.display === 'none') {
        section.style.display = 'block';
        if (icon) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
    } else {
        section.style.display = 'none';
        if (icon) {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
}

// Dropdown toggle function
function toggleDropdown(menuId) {
    const menu = document.getElementById(menuId);
    if (!menu) return;
    
    menu.classList.toggle('show');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!e.target.closest('.dropdown-wrapper')) {
            menu.classList.remove('show');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

// Switch between list and grid view
function switchView(view) {
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');
    const viewBtns = document.querySelectorAll('.view-btn');
    
    if (!listView || !gridView) return;
    
    viewBtns.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.view === view) {
            btn.classList.add('active');
        }
    });
    
    if (view === 'list') {
        listView.style.display = 'block';
        gridView.style.display = 'none';
    } else {
        listView.style.display = 'none';
        gridView.style.display = 'block';
    }
}

// Toggle view button (for top bar button)
function toggleView() {
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');
    const viewIcon = document.getElementById('viewIcon');
    const viewText = document.getElementById('viewText');
    
    if (!listView || !gridView) return;
    
    if (listView.style.display === 'none') {
        listView.style.display = 'block';
        gridView.style.display = 'none';
        if (viewIcon) viewIcon.className = 'fas fa-th';
        if (viewText) viewText.textContent = 'Grid';
    } else {
        listView.style.display = 'none';
        gridView.style.display = 'block';
        if (viewIcon) viewIcon.className = 'fas fa-list';
        if (viewText) viewText.textContent = 'List';
    }
}

// Select all checkboxes
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.lesson-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

// Update bulk actions bar visibility
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.lesson-checkbox:checked');
    const bulkBar = document.getElementById('bulkActionsBar');
    const countSpan = document.getElementById('selectedCount');
    
    if (bulkBar) {
        if (checkboxes.length > 0) {
            bulkBar.style.display = 'flex';
            if (countSpan) {
                countSpan.textContent = checkboxes.length;
            }
        } else {
            bulkBar.style.display = 'none';
        }
    }
}

// Clear selection
function clearSelection() {
    const checkboxes = document.querySelectorAll('.lesson-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.checked = false;
    }
    
    updateBulkActions();
}

// Bulk action handler
function bulkAction(action) {
    const checkboxes = document.querySelectorAll('.lesson-checkbox:checked');
    const lessonIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (lessonIds.length === 0) {
        alert('Please select lessons first');
        return;
    }
    
    const confirmMsg = {
        'complete': 'Mark selected lessons as complete?',
        'cancel': 'Cancel selected lessons?',
        'reschedule': 'Reschedule selected lessons?'
    };
    
    if (confirm(confirmMsg[action] || 'Perform this action on selected lessons?')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `bulk_action.php?action=${action}`;
        
        lessonIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'lesson_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Refresh lessons page
function refreshLessons() {
    location.reload();
}

// Save filter preset (placeholder function)
function saveFilters() {
    // Get current filter values
    const status = document.getElementById('status')?.value || '';
    const lessonType = document.getElementById('lesson_type')?.value || '';
    const dateFrom = document.getElementById('date_from')?.value || '';
    const dateTo = document.getElementById('date_to')?.value || '';
    const instructorId = document.getElementById('instructor_id')?.value || '';
    const search = document.getElementById('search')?.value || '';
    
    // Create filter object
    const filterPreset = {
        status: status,
        lesson_type: lessonType,
        date_from: dateFrom,
        date_to: dateTo,
        instructor_id: instructorId,
        search: search,
        saved_at: new Date().toISOString()
    };
    
    // In a real implementation, you would save this to localStorage or server
    // For now, just show a confirmation
    console.log('Filter preset saved:', filterPreset);
    alert('Filter preset saved successfully!\n(This is a demo feature)');
    
    // Optional: Save to localStorage for future use
    try {
        const presets = JSON.parse(localStorage.getItem('lessonFilterPresets') || '[]');
        presets.push(filterPreset);
        localStorage.setItem('lessonFilterPresets', JSON.stringify(presets));
    } catch (e) {
        console.error('Could not save filter preset:', e);
    }
}

// Load saved filter preset (optional function)
function loadFilterPreset(presetIndex) {
    try {
        const presets = JSON.parse(localStorage.getItem('lessonFilterPresets') || '[]');
        if (presets[presetIndex]) {
            const preset = presets[presetIndex];
            
            if (document.getElementById('status')) {
                document.getElementById('status').value = preset.status || '';
            }
            if (document.getElementById('lesson_type')) {
                document.getElementById('lesson_type').value = preset.lesson_type || '';
            }
            if (document.getElementById('date_from')) {
                document.getElementById('date_from').value = preset.date_from || '';
            }
            if (document.getElementById('date_to')) {
                document.getElementById('date_to').value = preset.date_to || '';
            }
            if (document.getElementById('instructor_id')) {
                document.getElementById('instructor_id').value = preset.instructor_id || '';
            }
            if (document.getElementById('search')) {
                document.getElementById('search').value = preset.search || '';
            }
        }
    } catch (e) {
        console.error('Could not load filter preset:', e);
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Keyboard shortcuts for better UX
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K: Focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Ctrl/Cmd + N: New lesson (admin/staff only)
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const createLink = document.querySelector('a[href="create.php"]');
        if (createLink) {
            window.location.href = 'create.php';
        }
    }
    
    // Escape: Clear selection
    if (e.key === 'Escape') {
        clearSelection();
    }
});

// Export function (placeholder)
function exportLessons(format) {
    const url = `export.php?format=${format}`;
    
    // Add current filters to export
    const params = new URLSearchParams(window.location.search);
    window.location.href = url + '&' + params.toString();
}

// Tooltip initialization (if you're using a tooltip library)
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips if available
    initTooltips();
    
    // Set up any additional event listeners
    const filterForm = document.querySelector('.advanced-filter-form');
    if (filterForm) {
        // Auto-submit on filter change (optional)
        const autoSubmitElements = filterForm.querySelectorAll('select, input[type="date"]');
        autoSubmitElements.forEach(element => {
            // Uncomment the line below to enable auto-submit on filter change
            // element.addEventListener('change', () => filterForm.submit());
        });
    }
    
    // Highlight current date in any date inputs
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.setAttribute('placeholder', today);
        }
    });
});

// Table row click handler (optional - navigate to details on row click)
function initTableRowClicks() {
    const tableRows = document.querySelectorAll('.modern-table tbody tr');
    tableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on a button or checkbox
            if (e.target.closest('button') || 
                e.target.closest('a') || 
                e.target.closest('input[type="checkbox"]')) {
                return;
            }
            
            const viewLink = this.querySelector('a[href*="view.php"]');
            if (viewLink) {
                window.location.href = viewLink.href;
            }
        });
    });
}

// Optional: Initialize table row clicks
// Uncomment the line below to enable row click navigation
// document.addEventListener('DOMContentLoaded', initTableRowClicks);

// Print function
function printLessons() {
    window.print();
}

// Confirmation dialogs for delete actions
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('a[href*="delete.php"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to cancel this lesson? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});

// Filter reset with animation
function resetFilters() {
    const filterForm = document.querySelector('.advanced-filter-form');
    if (filterForm) {
        filterForm.reset();
        setTimeout(() => {
            window.location.href = window.location.pathname;
        }, 200);
    }
}

// Smooth scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Add scroll to top button if page is long
document.addEventListener('DOMContentLoaded', function() {
    if (document.body.scrollHeight > window.innerHeight * 2) {
        const scrollBtn = document.createElement('button');
        scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollBtn.className = 'scroll-to-top';
        scrollBtn.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            z-index: 1000;
            transition: all 0.3s;
        `;
        
        scrollBtn.addEventListener('click', scrollToTop);
        document.body.appendChild(scrollBtn);
        
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                scrollBtn.style.display = 'flex';
            } else {
                scrollBtn.style.display = 'none';
            }
        });
    }
});

// Console log for debugging
console.log('Lesson Index JavaScript loaded successfully');
console.log('Available functions:', {
    updateTime: 'Updates real-time clock',
    toggleSection: 'Toggle collapsible sections',
    toggleDropdown: 'Toggle dropdown menus',
    switchView: 'Switch between list/grid view',
    toggleView: 'Toggle view button handler',
    toggleSelectAll: 'Select/deselect all checkboxes',
    updateBulkActions: 'Update bulk actions bar',
    clearSelection: 'Clear all selections',
    bulkAction: 'Perform bulk actions',
    refreshLessons: 'Reload the page',
    saveFilters: 'Save current filter preset',
    loadFilterPreset: 'Load saved filter preset',
    exportLessons: 'Export lessons to file',
    printLessons: 'Print lessons table',
    scrollToTop: 'Scroll to page top'
});
</script>