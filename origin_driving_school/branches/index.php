<?php
/**
 * Branches Management - List All Branches
 * 
 * File path: branches/index.php
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
$pageTitle = 'Branches Management';

// Handle search
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$searchQuery = '';
if (!empty($searchTerm)) {
    $searchQuery = "AND (branch_name LIKE '%$searchTerm%' OR address LIKE '%$searchTerm%' OR suburb LIKE '%$searchTerm%')";
}

$sql = "SELECT * FROM branches WHERE 1=1 $searchQuery";

if ($statusFilter !== '') {
    $sql .= " AND is_active = " . intval($statusFilter);
}

$sql .= " ORDER BY branch_name ASC";

$branches = $studentModel->customQuery($sql);

// Get statistics
$totalBranches = count($branches);
$activeBranches = count(array_filter($branches, function($b) { return $b['is_active'] == 1; }));

// Get additional stats
$studentCount = $studentModel->customQuery("
    SELECT b.branch_id, b.branch_name, COUNT(s.student_id) as student_count
    FROM branches b
    LEFT JOIN students s ON b.branch_id = s.branch_id
    GROUP BY b.branch_id
");

$studentCountByBranch = [];
foreach ($studentCount as $sc) {
    $studentCountByBranch[$sc['branch_id']] = $sc['student_count'];
}

$instructorCount = $studentModel->customQuery("
    SELECT b.branch_id, COUNT(i.instructor_id) as instructor_count
    FROM branches b
    LEFT JOIN instructors i ON b.branch_id = i.branch_id
    GROUP BY b.branch_id
");

$instructorCountByBranch = [];
foreach ($instructorCount as $ic) {
    $instructorCountByBranch[$ic['branch_id']] = $ic['instructor_count'];
}

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1><i class="fas fa-building"></i> Branches Management</h1>
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
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalBranches; ?></h3>
                        <p>Total Branches</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $activeBranches; ?></h3>
                        <p>Active Branches</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <?php 
                        $totalStudents = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM students");
                        ?>
                        <h3><?php echo $totalStudents['count'] ?? 0; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <?php 
                        $totalInstructors = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM instructors");
                        ?>
                        <h3><?php echo $totalInstructors['count'] ?? 0; ?></h3>
                        <p>Total Instructors</p>
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
                                       placeholder="Branch name, address, suburb..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
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
            
            <!-- Branches Grid -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-list"></i> All Branches (<?php echo $totalBranches; ?>)</h3>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Branch
                    </a>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($branches)): ?>
                        <div class="table-empty">
                            <i class="fas fa-building"></i>
                            <p>No branches found</p>
                            <?php if (!empty($searchTerm) || $statusFilter !== ''): ?>
                                <p><a href="index.php">Clear filters to view all branches</a></p>
                            <?php else: ?>
                                <p><a href="create.php" class="btn btn-primary">Add your first branch</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        
                        <!-- Grid View -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                            <?php foreach ($branches as $branch): ?>
                                <div class="card" style="border: 2px solid <?php echo $branch['is_active'] ? '#28a745' : '#dc3545'; ?>; margin: 0;">
                                    <div class="card-header" style="background-color: <?php echo $branch['is_active'] ? 'rgba(40, 167, 69, 0.1)' : 'rgba(220, 53, 69, 0.1)'; ?>; display: flex; justify-content: space-between; align-items: center;">
                                        <h4 style="margin: 0; color: #4e7e95;">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </h4>
                                        <?php if ($branch['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <p style="margin-bottom: 0.5rem;">
                                            <i class="fas fa-home" style="width: 20px; color: #4e7e95;"></i>
                                            <strong>Address:</strong><br>
                                            <span style="margin-left: 28px;">
                                                <?php echo htmlspecialchars($branch['address']); ?><br>
                                                <?php echo htmlspecialchars($branch['suburb'] . ', ' . $branch['state'] . ' ' . $branch['postcode']); ?>
                                            </span>
                                        </p>
                                        
                                        <p style="margin-bottom: 0.5rem; margin-top: 1rem;">
                                            <i class="fas fa-phone" style="width: 20px; color: #4e7e95;"></i>
                                            <strong>Phone:</strong>
                                            <span style="margin-left: 10px;"><?php echo htmlspecialchars($branch['phone']); ?></span>
                                        </p>
                                        
                                        <p style="margin-bottom: 0.5rem;">
                                            <i class="fas fa-envelope" style="width: 20px; color: #4e7e95;"></i>
                                            <strong>Email:</strong>
                                            <span style="margin-left: 10px;"><?php echo htmlspecialchars($branch['email']); ?></span>
                                        </p>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5edf0;">
                                            <div style="text-align: center; padding: 0.5rem; background-color: rgba(78, 126, 149, 0.1); border-radius: 5px;">
                                                <div style="font-size: 1.5rem; font-weight: bold; color: #4e7e95;">
                                                    <?php echo $studentCountByBranch[$branch['branch_id']] ?? 0; ?>
                                                </div>
                                                <div style="font-size: 0.85rem; color: #666;">Students</div>
                                            </div>
                                            <div style="text-align: center; padding: 0.5rem; background-color: rgba(231, 135, 89, 0.1); border-radius: 5px;">
                                                <div style="font-size: 1.5rem; font-weight: bold; color: #e78759;">
                                                    <?php echo $instructorCountByBranch[$branch['branch_id']] ?? 0; ?>
                                                </div>
                                                <div style="font-size: 0.85rem; color: #666;">Instructors</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer" style="display: flex; justify-content: space-between; gap: 0.5rem; padding: 1rem;">
                                        <a href="view.php?id=<?php echo $branch['branch_id']; ?>" 
                                           class="btn btn-info btn-sm" style="flex: 1;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="edit.php?id=<?php echo $branch['branch_id']; ?>" 
                                           class="btn btn-warning btn-sm" style="flex: 1;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete.php?id=<?php echo $branch['branch_id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this branch? This may affect associated students and instructors.');"
                                           style="flex: 1;">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Table View (Alternative) -->
                        <div style="display: none;" id="tableView">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Branch Name</th>
                                            <th>Address</th>
                                            <th>Contact</th>
                                            <th>Students</th>
                                            <th>Instructors</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($branches as $branch): ?>
                                            <tr>
                                                <td><?php echo $branch['branch_id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($branch['branch_name']); ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($branch['address'] . ', ' . $branch['suburb']); ?>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($branch['phone']); ?></div>
                                                    <div style="font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($branch['email']); ?></div>
                                                </td>
                                                <td><?php echo $studentCountByBranch[$branch['branch_id']] ?? 0; ?></td>
                                                <td><?php echo $instructorCountByBranch[$branch['branch_id']] ?? 0; ?></td>
                                                <td>
                                                    <?php if ($branch['is_active']): ?>
                                                        <span class="badge badge-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="view.php?id=<?php echo $branch['branch_id']; ?>" 
                                                           class="btn btn-info btn-sm" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?php echo $branch['branch_id']; ?>" 
                                                           class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete.php?id=<?php echo $branch['branch_id']; ?>" 
                                                           class="btn btn-danger btn-sm" 
                                                           onclick="return confirm('Are you sure?');"
                                                           title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                    <?php endif; ?>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>