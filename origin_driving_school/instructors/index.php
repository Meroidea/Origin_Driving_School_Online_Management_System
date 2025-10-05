<?php
/**
 * FILE PATH: instructors/index.php
 * 
 * Instructors List Page - Origin Driving School Management System
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 3.0 - FIXED USING STUDENTS PATTERN
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/core/Database.php';
require_once '../app/core/Model.php';
require_once '../app/models/User.php';
require_once '../app/models/Student.php';
require_once '../app/models/Instructor.php';
require_once '../app/models/CourseAndOther.php';

// Require admin or staff role
requireRole(['admin', 'staff']);

$instructorModel = new Instructor();
$branchModel = new Branch();

// Handle search
$searchTerm = $_GET['search'] ?? '';
$instructors = [];

if ($searchTerm) {
    // If search exists, you might need to implement search in Instructor model
    $instructors = $instructorModel->getAllWithDetails();
    // Filter by search term
    $instructors = array_filter($instructors, function($instructor) use ($searchTerm) {
        $searchLower = strtolower($searchTerm);
        return stripos($instructor['first_name'] ?? '', $searchTerm) !== false ||
               stripos($instructor['last_name'] ?? '', $searchTerm) !== false ||
               stripos($instructor['email'] ?? '', $searchTerm) !== false ||
               stripos($instructor['certificate_number'] ?? '', $searchTerm) !== false;
    });
} else {
    $instructors = $instructorModel->getAllWithDetails();
}

// Get branches for filter
$db = Database::getInstance();
$conn = $db->getConnection();
$stmtBranches = $conn->query("SELECT branch_id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name");
$branches = $stmtBranches->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Instructors Management';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h2><i class="fas fa-chalkboard-teacher"></i> Instructors Management</h2>
                <p style="color: #666; margin-top: 0.3rem;">
                    Manage instructor profiles and assignments
                </p>
            </div>
            <div class="user-menu">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Instructor
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
                    <span><?php echo htmlspecialchars($flashMessage['message']); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="modern-stats-grid">
                <div class="modern-stat-card">
                    <div class="stat-icon-modern" style="background: rgba(78, 126, 149, 0.1);">
                        <i class="fas fa-chalkboard-teacher" style="color: #4e7e95;"></i>
                    </div>
                    <div class="stat-content-modern">
                        <h3 class="stat-number"><?php echo count($instructors); ?></h3>
                        <p class="stat-label">Total Instructors</p>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: 100%; background: #4e7e95;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-icon-modern" style="background: rgba(39, 174, 96, 0.1);">
                        <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                    </div>
                    <div class="stat-content-modern">
                        <?php 
                        $availableCount = 0;
                        foreach ($instructors as $instructor) {
                            if (isset($instructor['is_available']) && $instructor['is_available'] == 1) {
                                $availableCount++;
                            }
                        }
                        ?>
                        <h3 class="stat-number"><?php echo $availableCount; ?></h3>
                        <p class="stat-label">Available Now</p>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo count($instructors) > 0 ? ($availableCount/count($instructors)*100) : 0; ?>%; background: #27ae60;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-icon-modern" style="background: rgba(231, 135, 89, 0.1);">
                        <i class="fas fa-users" style="color: #e78759;"></i>
                    </div>
                    <div class="stat-content-modern">
                        <?php 
                        $stmt = $conn->query("SELECT COUNT(*) FROM students WHERE assigned_instructor_id IS NOT NULL");
                        $assignedStudents = $stmt->fetchColumn();
                        ?>
                        <h3 class="stat-number"><?php echo $assignedStudents; ?></h3>
                        <p class="stat-label">Assigned Students</p>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: 75%; background: #e78759;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-icon-modern" style="background: rgba(52, 152, 219, 0.1);">
                        <i class="fas fa-calendar-check" style="color: #3498db;"></i>
                    </div>
                    <div class="stat-content-modern">
                        <?php 
                        $stmt = $conn->query("SELECT COUNT(*) FROM lessons WHERE DATE(lesson_date) = CURDATE() AND status = 'scheduled'");
                        $todayLessons = $stmt->fetchColumn();
                        ?>
                        <h3 class="stat-number"><?php echo $todayLessons; ?></h3>
                        <p class="stat-label">Lessons Today</p>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: 60%; background: #3498db;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Search Instructors</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="form-control" 
                                    placeholder="Search by name, email, or certificate number..." 
                                    value="<?php echo htmlspecialchars($searchTerm); ?>"
                                >
                            </div>
                            <div class="form-group" style="align-self: flex-end;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if ($searchTerm): ?>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Instructors Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Instructor List</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($instructors)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chalkboard-teacher" style="font-size: 4rem; color: #ccc;"></i>
                            <h3>No Instructors Found</h3>
                            <?php if ($searchTerm): ?>
                                <p>No instructors match your search criteria.</p>
                                <a href="index.php" class="btn btn-secondary">View All Instructors</a>
                            <?php else: ?>
                                <p>Get started by adding your first instructor.</p>
                                <a href="create.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add First Instructor
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Branch</th>
                                        <th>Certificate</th>
                                        <th>Hourly Rate</th>
                                        <th>Specialization</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($instructors as $instructor): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <strong>
                                                    <?php 
                                                    echo htmlspecialchars($instructor['first_name'] ?? 'N/A') . ' ' . 
                                                         htmlspecialchars($instructor['last_name'] ?? ''); 
                                                    ?>
                                                </strong>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($instructor['email'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($instructor['email'] ?? 'N/A'); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="tel:<?php echo htmlspecialchars($instructor['phone'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($instructor['phone'] ?? 'N/A'); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($instructor['branch_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($instructor['certificate_number'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo CURRENCY_SYMBOL . number_format($instructor['hourly_rate'] ?? 0, 2); ?>/hr</strong>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($instructor['specialization']) && !empty($instructor['specialization'])) {
                                                echo htmlspecialchars($instructor['specialization']);
                                            } else {
                                                echo '<span class="text-muted">None</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($instructor['is_available']) && $instructor['is_available']): ?>
                                                <span class="badge badge-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Unavailable</span>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($instructor['is_active']) && !$instructor['is_active']): ?>
                                                <br><span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="view.php?id=<?php echo $instructor['instructor_id']; ?>" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $instructor['instructor_id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Edit Instructor">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="schedule.php?id=<?php echo $instructor['instructor_id']; ?>" 
                                               class="btn btn-sm btn-primary" title="View Schedule">
                                                <i class="fas fa-calendar"></i>
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
    </div>
</div>

<style>
/* Modern Statistics Cards */
.modern-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.modern-stat-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.modern-stat-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
}

.stat-icon-modern {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.stat-icon-modern i {
    font-size: 1.75rem;
}

.stat-content-modern {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    line-height: 1;
}

.stat-label {
    font-size: 0.95rem;
    color: #7f8c8d;
    margin: 0.5rem 0;
    font-weight: 500;
}

.stat-progress {
    height: 4px;
    background: #ecf0f1;
    border-radius: 2px;
    margin-top: 0.75rem;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    transition: width 0.8s ease;
    border-radius: 2px;
}

.card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.card-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
    border-bottom: 2px solid #e0e0e0;
    background: #f8f9fa;
}

.data-table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.data-table tbody tr:hover {
    background: rgba(78, 126, 149, 0.03);
}

.data-table tbody td {
    padding: 1rem;
    color: #34495e;
    font-size: 0.9rem;
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
}

.badge-success {
    background: rgba(39, 174, 96, 0.1);
    color: #27ae60;
    border: 1px solid rgba(39, 174, 96, 0.2);
}

.badge-danger {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.2);
}

.badge-warning {
    background: rgba(243, 156, 18, 0.1);
    color: #f39c12;
    border: 1px solid rgba(243, 156, 18, 0.2);
}

.actions {
    white-space: nowrap;
}

.actions .btn {
    margin-right: 0.25rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #7f8c8d;
}

.empty-state h3 {
    margin-bottom: 0.75rem;
    color: #34495e;
}

.user-info strong {
    color: #2c3e50;
    font-weight: 600;
}

.text-muted {
    color: #95a5a6;
    font-style: italic;
}

a {
    color: #4e7e95;
    text-decoration: none;
}

a:hover {
    color: #3d6478;
    text-decoration: underline;
}
</style>

<?php include '../views/layouts/footer.php'; ?>