<?php
/**
 * Courses Management - List All Courses
 * 
 * File path: courses/index.php
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

// Define APP_ACCESS constant
define('APP_ACCESS', true);

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and has permission
requireLogin();
requireRole(['admin', 'staff']);

// Include required models
require_once APP_PATH . '/models/Student.php';

// Initialize models
$studentModel = new Student();

// Set page title
$pageTitle = 'Courses Management';

// Handle search and filters
$searchTerm = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$searchQuery = '';
if (!empty($searchTerm)) {
    $searchQuery = "AND (course_name LIKE '%$searchTerm%' OR course_code LIKE '%$searchTerm%' OR description LIKE '%$searchTerm%')";
}

$sql = "SELECT c.*,
        COUNT(DISTINCT i.invoice_id) as total_enrollments,
        COUNT(DISTINCT CASE WHEN i.status = 'paid' THEN i.invoice_id END) as completed_enrollments
        FROM courses c
        LEFT JOIN invoices i ON c.course_id = i.course_id
        WHERE 1=1 $searchQuery";

if (!empty($typeFilter)) {
    $sql .= " AND c.course_type = '" . $studentModel->db->escape($typeFilter) . "'";
}

if ($statusFilter !== '') {
    $sql .= " AND c.is_active = " . intval($statusFilter);
}

$sql .= " GROUP BY c.course_id ORDER BY c.course_id DESC";

$courses = $studentModel->customQuery($sql);

// Get statistics
$totalCourses = count($courses);
$activeCourses = count(array_filter($courses, function($c) { return $c['is_active'] == 1; }));
$totalEnrollments = array_sum(array_column($courses, 'total_enrollments'));

// Calculate total revenue from courses
$revenueStats = $studentModel->customQueryOne("
    SELECT COALESCE(SUM(i.total_amount), 0) as total_revenue
    FROM invoices i
    WHERE i.status = 'paid'
");

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1><i class="fas fa-book"></i> Courses Management</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?>">
                    <?php 
                    echo htmlspecialchars($_SESSION['flash_message']);
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalCourses; ?></h3>
                        <p>Total Courses</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $activeCourses; ?></h3>
                        <p>Active Courses</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalEnrollments; ?></h3>
                        <p>Total Enrollments</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo CURRENCY_SYMBOL . number_format($revenueStats['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter Section -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Search & Filter</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="filter-form">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Course name, code, description..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Course Type</label>
                                <select name="type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="beginner" <?php echo $typeFilter === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo $typeFilter === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo $typeFilter === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                    <option value="test_preparation" <?php echo $typeFilter === 'test_preparation' ? 'selected' : ''; ?>>Test Preparation</option>
                                    <option value="refresher" <?php echo $typeFilter === 'refresher' ? 'selected' : ''; ?>>Refresher</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end; gap: 0.5rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Courses Grid -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-list"></i> All Courses (<?php echo $totalCourses; ?>)</h3>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Course
                    </a>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($courses)): ?>
                        <div class="table-empty">
                            <i class="fas fa-book"></i>
                            <p>No courses found</p>
                            <?php if (!empty($searchTerm) || !empty($typeFilter) || $statusFilter !== ''): ?>
                                <p><a href="index.php">Clear filters to view all courses</a></p>
                            <?php else: ?>
                                <p><a href="create.php" class="btn btn-primary">Add your first course</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        
                        <!-- Grid View -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($courses as $course): ?>
                                <div class="card" style="margin: 0; border: 2px solid <?php echo $course['is_active'] ? '#28a745' : '#dc3545'; ?>;">
                                    <div class="card-header" style="background-color: <?php 
                                        $colors = [
                                            'beginner' => '#d1ecf1',
                                            'intermediate' => '#fff3cd',
                                            'advanced' => '#f8d7da',
                                            'test_preparation' => '#d4edda',
                                            'refresher' => '#e7f3ff'
                                        ];
                                        echo $colors[$course['course_type']] ?? '#e5edf0';
                                    ?>; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin: 0; color: #4e7e95;">
                                                <i class="fas fa-graduation-cap"></i>
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </h4>
                                            <small style="color: #666;">Code: <?php echo htmlspecialchars($course['course_code']); ?></small>
                                        </div>
                                        <?php if ($course['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <p style="color: #666; margin-bottom: 1rem; min-height: 60px;">
                                            <?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?>
                                        </p>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem;">
                                            <div style="background-color: #f8f9fa; padding: 0.75rem; border-radius: 5px; text-align: center;">
                                                <div style="font-size: 1.25rem; font-weight: bold; color: #4e7e95;">
                                                    <?php echo $course['number_of_lessons']; ?>
                                                </div>
                                                <div style="font-size: 0.85rem; color: #666;">Lessons</div>
                                            </div>
                                            <div style="background-color: #f8f9fa; padding: 0.75rem; border-radius: 5px; text-align: center;">
                                                <div style="font-size: 1.25rem; font-weight: bold; color: #4e7e95;">
                                                    <?php echo $course['lesson_duration']; ?> min
                                                </div>
                                                <div style="font-size: 0.85rem; color: #666;">Per Lesson</div>
                                            </div>
                                        </div>
                                        
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background-color: rgba(78, 126, 149, 0.05); border-radius: 5px; margin-bottom: 1rem;">
                                            <div>
                                                <div style="font-size: 0.85rem; color: #666;">Price</div>
                                                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                                                    <?php echo CURRENCY_SYMBOL . number_format($course['price'], 2); ?>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <span class="badge badge-info" style="font-size: 0.9rem;">
                                                    <?php echo ucfirst(str_replace('_', ' ', $course['course_type'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div style="display: flex; justify-content: space-between; padding-top: 1rem; border-top: 1px solid #e5edf0;">
                                            <div>
                                                <small style="color: #666;">
                                                    <i class="fas fa-users"></i>
                                                    <?php echo $course['total_enrollments']; ?> enrolled
                                                </small>
                                            </div>
                                            <div>
                                                <small style="color: #28a745;">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo $course['completed_enrollments']; ?> completed
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer" style="display: flex; gap: 0.5rem; padding: 1rem;">
                                        <a href="view.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-info btn-sm" style="flex: 1;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="edit.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-warning btn-sm" style="flex: 1;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this course?');"
                                           style="flex: 1;">
                                            <i class="fas fa-trash"></i> Delete
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