<?php
/**
 * Staff Management - List All Staff
 * 
 * File path: staff/index.php
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

// Define APP_ACCESS constant
define('APP_ACCESS', true);

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and has admin permission
requireLogin();
requireRole(['admin']);

// Include required models
require_once APP_PATH . '/models/Student.php';

// Initialize models
$studentModel = new Student();

// Set page title
$pageTitle = 'Staff Management';

// Handle search and filters
$searchTerm = $_GET['search'] ?? '';
$branchFilter = $_GET['branch'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$searchQuery = '';
if (!empty($searchTerm)) {
    $searchQuery = "AND (u.first_name LIKE '%$searchTerm%' OR u.last_name LIKE '%$searchTerm%' OR u.email LIKE '%$searchTerm%' OR st.position LIKE '%$searchTerm%')";
}

$sql = "SELECT st.*, u.first_name, u.last_name, u.email, u.phone, u.is_active, u.last_login,
        b.branch_name
        FROM staff st
        INNER JOIN users u ON st.user_id = u.user_id
        INNER JOIN branches b ON st.branch_id = b.branch_id
        WHERE 1=1 $searchQuery";

if (!empty($branchFilter)) {
    $sql .= " AND st.branch_id = " . intval($branchFilter);
}

if ($statusFilter !== '') {
    $sql .= " AND u.is_active = " . intval($statusFilter);
}

$sql .= " ORDER BY st.staff_id DESC";

$staff = $studentModel->customQuery($sql);

// Get statistics
$totalStaff = count($staff);
$activeStaff = count(array_filter($staff, function($s) { return $s['is_active'] == 1; }));

// Get branches for filter
$branches = $studentModel->customQuery("SELECT branch_id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name");

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1><i class="fas fa-user-tie"></i> Staff Management</h1>
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
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalStaff; ?></h3>
                        <p>Total Staff Members</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $activeStaff; ?></h3>
                        <p>Active Staff</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <?php 
                        $branchCount = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM branches WHERE is_active = 1");
                        ?>
                        <h3><?php echo $branchCount['count'] ?? 0; ?></h3>
                        <p>Active Branches</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <?php 
                        $recentStaff = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM staff WHERE hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                        ?>
                        <h3><?php echo $recentStaff['count'] ?? 0; ?></h3>
                        <p>Hired This Month</p>
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
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Name, Email, Position..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Branch</label>
                                <select name="branch" class="form-control">
                                    <option value="">All Branches</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>" 
                                                <?php echo $branchFilter == $branch['branch_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
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
            
            <!-- Staff Table -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-list"></i> All Staff Members (<?php echo $totalStaff; ?>)</h3>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Staff
                    </a>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($staff)): ?>
                        <div class="table-empty">
                            <i class="fas fa-user-tie"></i>
                            <p>No staff members found</p>
                            <?php if (!empty($searchTerm) || !empty($branchFilter) || $statusFilter !== ''): ?>
                                <p><a href="index.php">Clear filters to view all staff</a></p>
                            <?php else: ?>
                                <p><a href="create.php" class="btn btn-primary">Add your first staff member</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Position</th>
                                        <th>Branch</th>
                                        <th>Hire Date</th>
                                        <th>Last Login</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff as $staffMember): ?>
                                        <tr>
                                            <td><?php echo $staffMember['staff_id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($staffMember['first_name'] . ' ' . $staffMember['last_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($staffMember['email']); ?></td>
                                            <td><?php echo htmlspecialchars($staffMember['phone']); ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($staffMember['position']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($staffMember['branch_name']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($staffMember['hire_date'])); ?></td>
                                            <td>
                                                <?php if ($staffMember['last_login']): ?>
                                                    <?php echo date('d/m/Y H:i', strtotime($staffMember['last_login'])); ?>
                                                <?php else: ?>
                                                    <em>Never</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($staffMember['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view.php?id=<?php echo $staffMember['staff_id']; ?>" 
                                                       class="btn btn-info btn-sm" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $staffMember['staff_id']; ?>" 
                                                       class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $staffMember['staff_id']; ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Are you sure you want to delete this staff member?');"
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
                    <?php endif; ?>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>