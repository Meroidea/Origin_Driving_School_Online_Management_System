<?php
/**
 * Vehicles/Fleet Management - List All Vehicles
 * 
 * File path: vehicles/index.php
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

requireLogin();
requireRole(['admin', 'staff']);

require_once APP_PATH . '/models/Student.php';
$studentModel = new Student();

$pageTitle = 'Fleet Management';

// Handle search and filters
$searchTerm = $_GET['search'] ?? '';
$branchFilter = $_GET['branch'] ?? '';
$transmissionFilter = $_GET['transmission'] ?? '';
$availabilityFilter = $_GET['availability'] ?? '';

// Build query
$searchQuery = '';
if (!empty($searchTerm)) {
    $searchQuery = "AND (v.registration_number LIKE '%$searchTerm%' OR v.make LIKE '%$searchTerm%' OR v.model LIKE '%$searchTerm%')";
}

$sql = "SELECT v.*, b.branch_name,
        COUNT(DISTINCT l.lesson_id) as total_lessons,
        COUNT(DISTINCT CASE WHEN l.lesson_date = CURDATE() THEN l.lesson_id END) as lessons_today,
        MAX(l.lesson_date) as last_used_date
        FROM vehicles v
        INNER JOIN branches b ON v.branch_id = b.branch_id
        LEFT JOIN lessons l ON v.vehicle_id = l.vehicle_id
        WHERE 1=1 $searchQuery";

if (!empty($branchFilter)) {
    $sql .= " AND v.branch_id = " . intval($branchFilter);
}

if (!empty($transmissionFilter)) {
    $sql .= " AND v.transmission = '" . $studentModel->db->escape($transmissionFilter) . "'";
}

if ($availabilityFilter !== '') {
    $sql .= " AND v.is_available = " . intval($availabilityFilter);
}

$sql .= " GROUP BY v.vehicle_id ORDER BY v.registration_number ASC";

$vehicles = $studentModel->customQuery($sql);

// Get statistics
$totalVehicles = count($vehicles);
$availableVehicles = count(array_filter($vehicles, function($v) { return $v['is_available'] == 1; }));
$autoVehicles = count(array_filter($vehicles, function($v) { return $v['transmission'] === 'automatic'; }));
$manualVehicles = count(array_filter($vehicles, function($v) { return $v['transmission'] === 'manual'; }));

// Get branches for filter
$branches = $studentModel->customQuery("SELECT branch_id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name");

// Check for expiring items
$expiringItems = $studentModel->customQuery("
    SELECT vehicle_id, registration_number, 
           CASE 
               WHEN registration_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Registration'
               WHEN insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Insurance'
               WHEN next_service_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Service'
           END as expiring_item,
           CASE 
               WHEN registration_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN registration_expiry
               WHEN insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN insurance_expiry
               WHEN next_service_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN next_service_due
           END as expiry_date
    FROM vehicles
    WHERE registration_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
       OR insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
       OR next_service_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY expiry_date ASC
");

include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h1><i class="fas fa-car"></i> Fleet Management</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
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
            
            <!-- Expiring Items Alert -->
            <?php if (!empty($expiringItems)): ?>
                <div class="alert alert-warning" style="margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0;"><i class="fas fa-exclamation-triangle"></i> Attention Required!</h4>
                    <p style="margin: 0;">The following vehicles need attention:</p>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($expiringItems as $item): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($item['registration_number']); ?></strong> - 
                                <?php echo $item['expiring_item']; ?> expires on 
                                <strong><?php echo date('d/m/Y', strtotime($item['expiry_date'])); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalVehicles; ?></h3>
                        <p>Total Vehicles</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $availableVehicles; ?></h3>
                        <p>Available Now</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $autoVehicles; ?></h3>
                        <p>Automatic</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $manualVehicles; ?></h3>
                        <p>Manual</p>
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
                                       placeholder="Rego, Make, Model..." 
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
                                <label>Transmission</label>
                                <select name="transmission" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="automatic" <?php echo $transmissionFilter === 'automatic' ? 'selected' : ''; ?>>Automatic</option>
                                    <option value="manual" <?php echo $transmissionFilter === 'manual' ? 'selected' : ''; ?>>Manual</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Availability</label>
                                <select name="availability" class="form-control">
                                    <option value="">All</option>
                                    <option value="1" <?php echo $availabilityFilter === '1' ? 'selected' : ''; ?>>Available</option>
                                    <option value="0" <?php echo $availabilityFilter === '0' ? 'selected' : ''; ?>>Unavailable</option>
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
            
            <!-- Vehicles Grid/Cards -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-list"></i> Fleet Overview (<?php echo $totalVehicles; ?>)</h3>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Vehicle
                    </a>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($vehicles)): ?>
                        <div class="table-empty">
                            <i class="fas fa-car"></i>
                            <p>No vehicles found</p>
                            <?php if (!empty($searchTerm) || !empty($branchFilter) || !empty($transmissionFilter) || $availabilityFilter !== ''): ?>
                                <p><a href="index.php">Clear filters to view all vehicles</a></p>
                            <?php else: ?>
                                <p><a href="create.php" class="btn btn-primary">Add your first vehicle</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        
                        <!-- Grid View -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                            <?php foreach ($vehicles as $vehicle): ?>
                                <div class="card" style="margin: 0; border: 2px solid <?php echo $vehicle['is_available'] ? '#28a745' : '#dc3545'; ?>;">
                                    
                                    <!-- Vehicle Header -->
                                    <div class="card-header" style="background: linear-gradient(135deg, #4e7e95 0%, #e78759 100%); color: white;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <h3 style="margin: 0; font-size: 1.5rem;">
                                                    <?php echo htmlspecialchars($vehicle['registration_number']); ?>
                                                </h3>
                                                <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 0.25rem;">
                                                    <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?> (<?php echo $vehicle['year']; ?>)
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <?php if ($vehicle['is_available']): ?>
                                                    <span class="badge badge-success" style="font-size: 0.9rem;">Available</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger" style="font-size: 0.9rem;">In Use</span>
                                                <?php endif; ?>
                                                <div style="margin-top: 0.25rem;">
                                                    <span class="badge badge-light" style="font-size: 0.85rem;">
                                                        <?php echo ucfirst($vehicle['transmission']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Vehicle Details -->
                                    <div class="card-body">
                                        <div style="display: grid; gap: 0.75rem; margin-bottom: 1rem;">
                                            <div>
                                                <i class="fas fa-building" style="width: 20px; color: #4e7e95;"></i>
                                                <strong>Branch:</strong> <?php echo htmlspecialchars($vehicle['branch_name']); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-palette" style="width: 20px; color: #4e7e95;"></i>
                                                <strong>Color:</strong> <?php echo htmlspecialchars($vehicle['color']); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Usage Stats -->
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0; padding: 1rem; background-color: #f8f9fa; border-radius: 5px;">
                                            <div style="text-align: center;">
                                                <div style="font-size: 1.5rem; font-weight: bold; color: #4e7e95;">
                                                    <?php echo $vehicle['total_lessons']; ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: #666;">Total Lessons</div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div style="font-size: 1.5rem; font-weight: bold; color: #e78759;">
                                                    <?php echo $vehicle['lessons_today']; ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: #666;">Today</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Maintenance Info -->
                                        <div style="border-top: 1px solid #e5edf0; padding-top: 1rem;">
                                            <div style="display: grid; gap: 0.5rem; font-size: 0.85rem;">
                                                <?php
                                                $daysToRego = ceil((strtotime($vehicle['registration_expiry']) - time()) / 86400);
                                                $daysToInsurance = ceil((strtotime($vehicle['insurance_expiry']) - time()) / 86400);
                                                $daysToService = ceil((strtotime($vehicle['next_service_due']) - time()) / 86400);
                                                ?>
                                                
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span><i class="fas fa-id-card" style="color: <?php echo $daysToRego <= 30 ? '#dc3545' : '#28a745'; ?>;"></i> Registration:</span>
                                                    <strong style="color: <?php echo $daysToRego <= 30 ? '#dc3545' : '#333'; ?>;">
                                                        <?php echo date('d/m/Y', strtotime($vehicle['registration_expiry'])); ?>
                                                        <?php if ($daysToRego <= 30): ?>
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        <?php endif; ?>
                                                    </strong>
                                                </div>
                                                
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span><i class="fas fa-shield-alt" style="color: <?php echo $daysToInsurance <= 30 ? '#dc3545' : '#28a745'; ?>;"></i> Insurance:</span>
                                                    <strong style="color: <?php echo $daysToInsurance <= 30 ? '#dc3545' : '#333'; ?>;">
                                                        <?php echo date('d/m/Y', strtotime($vehicle['insurance_expiry'])); ?>
                                                        <?php if ($daysToInsurance <= 30): ?>
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        <?php endif; ?>
                                                    </strong>
                                                </div>
                                                
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span><i class="fas fa-wrench" style="color: <?php echo $daysToService <= 7 ? '#dc3545' : '#28a745'; ?>;"></i> Next Service:</span>
                                                    <strong style="color: <?php echo $daysToService <= 7 ? '#dc3545' : '#333'; ?>;">
                                                        <?php echo date('d/m/Y', strtotime($vehicle['next_service_due'])); ?>
                                                        <?php if ($daysToService <= 7): ?>
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        <?php endif; ?>
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($vehicle['last_used_date']): ?>
                                            <div style="margin-top: 1rem; padding: 0.5rem; background-color: #e5edf0; border-radius: 3px; text-align: center; font-size: 0.85rem;">
                                                <i class="fas fa-clock"></i> Last used: <?php echo date('d/m/Y', strtotime($vehicle['last_used_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="card-footer" style="display: flex; gap: 0.5rem; padding: 1rem;">
                                        <a href="view.php?id=<?php echo $vehicle['vehicle_id']; ?>" 
                                           class="btn btn-info btn-sm" style="flex: 1;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="edit.php?id=<?php echo $vehicle['vehicle_id']; ?>" 
                                           class="btn btn-warning btn-sm" style="flex: 1;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="schedule.php?id=<?php echo $vehicle['vehicle_id']; ?>" 
                                           class="btn btn-primary btn-sm" style="flex: 1;">
                                            <i class="fas fa-calendar"></i> Schedule
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