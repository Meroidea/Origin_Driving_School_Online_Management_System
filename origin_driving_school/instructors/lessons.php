<?php
/**
 * Instructor Lessons - View Lesson History
 * 
 * File path: instructor/lessons.php
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
require_once APP_PATH . '/models/Lesson.php';

// Initialize models
$studentModel = new Student();
$lessonModel = new Lesson();

// Get instructor details
$userId = $_SESSION['user_id'];
$instructorSql = "SELECT i.* FROM instructors i 
                  INNER JOIN users u ON i.user_id = u.user_id 
                  WHERE u.user_id = ?";
$instructor = $studentModel->customQueryOne($instructorSql, [$userId]);

if (!$instructor) {
    setFlashMessage('error', 'Instructor profile not found');
    redirect('/dashboard.php');
}

$instructorId = $instructor['instructor_id'];

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$searchTerm = $_GET['search'] ?? '';

// Build query
$conditions = ["l.instructor_id = ?"];
$params = [$instructorId];

if ($status !== 'all') {
    $conditions[] = "l.status = ?";
    $params[] = $status;
}

if ($dateFrom) {
    $conditions[] = "l.lesson_date >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $conditions[] = "l.lesson_date <= ?";
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $conditions);

$lessonsSql = "SELECT l.*, 
               CONCAT(su.first_name, ' ', su.last_name) AS student_name,
               su.phone as student_phone,
               v.registration_number, v.make, v.model,
               c.course_name,
               TIMESTAMPDIFF(MINUTE, l.start_time, l.end_time) AS duration_minutes
               FROM lessons l
               INNER JOIN students s ON l.student_id = s.student_id
               INNER JOIN users su ON s.user_id = su.user_id
               LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
               LEFT JOIN courses c ON l.course_id = c.course_id
               WHERE $whereClause";

if (!empty($searchTerm)) {
    $lessonsSql .= " AND (su.first_name LIKE ? OR su.last_name LIKE ? OR c.course_name LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$lessonsSql .= " ORDER BY l.lesson_date DESC, l.start_time DESC";

$lessons = $studentModel->customQuery($lessonsSql, $params);

// Get statistics
$statsAll = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ?", [$instructorId]);
$statsCompleted = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ? AND status = 'completed'", [$instructorId]);
$statsCancelled = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ? AND status = 'cancelled'", [$instructorId]);
$statsScheduled = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM lessons WHERE instructor_id = ? AND status = 'scheduled' AND lesson_date >= CURDATE()", [$instructorId]);

// Set page title
$pageTitle = 'Lesson History';

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h2><i class="fas fa-clipboard-list"></i> Lesson History</h2>
                <p style="color: #666; margin-top: 0.3rem;">
                    View and filter your teaching history
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
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsAll['count'] ?? 0; ?></h3>
                        <p>Total Lessons</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsCompleted['count'] ?? 0; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsScheduled['count'] ?? 0; ?></h3>
                        <p>Upcoming</p>
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
            </div>
            
            <!-- Filters -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Filter Lessons</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                            
                            <div class="form-group" style="margin: 0;">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="no_show" <?php echo $status === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label>From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label>To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Student or course..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="lessons.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                            
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lessons Table -->
            <div class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0;">
                        <i class="fas fa-list"></i> Lessons (<?php echo count($lessons); ?>)
                    </h3>
                </div>
                
                <?php if (empty($lessons)): ?>
                    <div class="table-empty">
                        <i class="fas fa-calendar-times"></i>
                        <p>No lessons found</p>
                        <p style="color: #666;">Try adjusting your filters or date range.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">
                                            <?php echo date('d/m/Y', strtotime($lesson['lesson_date'])); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: #666;">
                                            <?php echo date('H:i', strtotime($lesson['start_time'])); ?> - 
                                            <?php echo date('H:i', strtotime($lesson['end_time'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($lesson['student_name']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: #666;">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($lesson['student_phone']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($lesson['course_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?php echo ucwords(str_replace('_', ' ', $lesson['lesson_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $lesson['duration_minutes']; ?> min</td>
                                    <td>
                                        <?php if ($lesson['registration_number']): ?>
                                            <div style="font-weight: 600;">
                                                <?php echo htmlspecialchars($lesson['registration_number']); ?>
                                            </div>
                                            <div style="font-size: 0.85rem; color: #666;">
                                                <?php echo htmlspecialchars($lesson['make'] . ' ' . $lesson['model']); ?>
                                            </div>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'secondary';
                                        if ($lesson['status'] === 'completed') $badgeClass = 'completed';
                                        elseif ($lesson['status'] === 'cancelled') $badgeClass = 'cancelled';
                                        elseif ($lesson['status'] === 'scheduled') $badgeClass = 'scheduled';
                                        elseif ($lesson['status'] === 'no_show') $badgeClass = 'no_show';
                                        ?>
                                        <span class="badge badge-<?php echo $badgeClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $lesson['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="<?php echo APP_URL; ?>/lessons/view.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($lesson['status'] === 'scheduled'): ?>
                                                <a href="<?php echo APP_URL; ?>/lessons/edit.php?id=<?php echo $lesson['lesson_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>