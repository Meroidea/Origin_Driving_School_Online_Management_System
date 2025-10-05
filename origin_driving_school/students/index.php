<?php
/**
 * FILE PATH: students/index.php
 * 
 * Students List Page - Origin Driving School Management System
 * PROFESSIONAL REDESIGN with Modern UI and Advanced Features
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 2.0 - ENHANCED
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/core/Database.php';
require_once '../app/core/Model.php';
require_once '../app/models/User.php';
require_once '../app/models/Student.php';
require_once '../app/models/CourseAndOther.php';
require_once '../app/models/Instructor.php';

// Require admin or staff role
requireRole(['admin', 'staff']);

$studentModel = new Student();
$branchModel = new Branch();
$instructorModel = new Instructor();

// Handle search
$searchTerm = $_GET['search'] ?? '';
$students = [];

if ($searchTerm) {
    $students = $studentModel->search($searchTerm);
} else {
    $students = $studentModel->getAllWithDetails();
}

$pageTitle = 'Students Management';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h2><i class="fas fa-user-graduate"></i> Students Management</h2>
                <p style="color: #666; margin-top: 0.3rem;">
                    Manage student enrollments and information
                </p>
            </div>
            <div class="user-menu">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Student
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
            
            <!-- Statistics Cards - Professional Design -->
            <div class="modern-stats-grid">
                <div class="modern-stat-card">
                    <div class="stat-icon-modern" style="background: rgba(78, 126, 149, 0.1);">
                        <i class="fas fa-users" style="color: #4e7e95;"></i>
                    </div>
                    <div class="stat-content-modern">
                        <h3 class="stat-number"><?php echo count($students); ?></h3>
                        <p class="stat-label">Total Students</p>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: 100%; background: #4e7e95;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-icon-modern" style="background: rgba(231, 135, 89, 0.1);">
                        <i class="fas fa-user-plus" style="color: #e78759;"></i>
                    </div>
                    <div class="stat-content-modern">
                        <?php 
                        $recentCount = 0;
                        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
                        foreach ($students as $student) {
                            if (isset($student['enrollment_date']) && $student['enrollment_date'] >= $thirtyDaysAgo) {
                                $recentCount++;
                            }
                        }
                        $recentPercentage = count($students) > 0 ? ($recentCount / count($students)) * 100 : 0;
                        ?>
                        <h3 class="stat-number"><?php echo $recentCount; ?></h3>
                        <p class="stat-label">New This Month</p>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo min($recentPercentage, 100); ?>%; background: #e78759;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-icon-modern" style="background: rgba(39, 174, 96, 0.1);">
                        <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                    </div>
                    <div class="stat-content-modern">
                        <?php 
                        $testReadyCount = 0;
                        foreach ($students as $student) {
                            if (isset($student['test_ready']) && $student['test_ready'] == 1) {
                                $testReadyCount++;
                            }
                        }
                        $testReadyPercentage = count($students) > 0 ? ($testReadyCount / count($students)) * 100 : 0;
                        ?>
                        <h3 class="stat-number"><?php echo $testReadyCount; ?></h3>
                        <p class="stat-label">Test Ready</p>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo $testReadyPercentage; ?>%; background: #27ae60;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-icon-modern" style="background: rgba(52, 152, 219, 0.1);">
                        <i class="fas fa-id-card" style="color: #3498db;"></i>
                    </div>
                    <div class="stat-content-modern">
                        <?php 
                        $learnerCount = 0;
                        foreach ($students as $student) {
                            if (isset($student['license_status']) && $student['license_status'] === 'learner') {
                                $learnerCount++;
                            }
                        }
                        $learnerPercentage = count($students) > 0 ? ($learnerCount / count($students)) * 100 : 0;
                        ?>
                        <h3 class="stat-number"><?php echo $learnerCount; ?></h3>
                        <p class="stat-label">Learner Permits</p>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo $learnerPercentage; ?>%; background: #3498db;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Bar -->
            <div class="quick-actions-bar">
                <div class="quick-action-item">
                    <i class="fas fa-filter"></i>
                    <select id="quickFilter" class="quick-select" onchange="filterTable(this.value)">
                        <option value="all">All Students</option>
                        <option value="active">Active Only</option>
                        <option value="test_ready">Test Ready</option>
                        <option value="learner">Learners</option>
                        <option value="no_instructor">No Instructor</option>
                    </select>
                </div>
                
                <div class="quick-action-item">
                    <i class="fas fa-sort"></i>
                    <select id="quickSort" class="quick-select" onchange="sortTable(this.value)">
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="date_new">Newest First</option>
                        <option value="date_old">Oldest First</option>
                    </select>
                </div>
                
                <div class="quick-action-item">
                    <button class="btn-action" onclick="exportToExcel()" title="Export to Excel">
                        <i class="fas fa-file-excel"></i> Export
                    </button>
                </div>
                
                <div class="quick-action-item">
                    <button class="btn-action" onclick="printTable()" title="Print Student List">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
                
                <div class="quick-action-item">
                    <button class="btn-action" onclick="toggleBulkMode()" title="Bulk Actions">
                        <i class="fas fa-tasks"></i> Bulk Actions
                    </button>
                </div>
            </div>
            
            <!-- Search and Filter -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Search Students</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="form-control" 
                                    placeholder="Search by name, email, or license number..." 
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
            
            <!-- Students Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Student List</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users" style="font-size: 4rem; color: #ccc;"></i>
                            <h3>No Students Found</h3>
                            <?php if ($searchTerm): ?>
                                <p>No students match your search criteria.</p>
                                <a href="index.php" class="btn btn-secondary">View All Students</a>
                            <?php else: ?>
                                <p>Get started by adding your first student.</p>
                                <a href="create.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add First Student
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
                                        <th>License Status</th>
                                        <th>Instructor</th>
                                        <th>Enrollment Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr data-test-ready="<?php echo isset($student['test_ready']) ? $student['test_ready'] : '0'; ?>">
                                        <td>
                                            <div class="user-info">
                                                <strong>
                                                    <?php 
                                                    echo htmlspecialchars($student['first_name'] ?? 'N/A') . ' ' . 
                                                         htmlspecialchars($student['last_name'] ?? ''); 
                                                    ?>
                                                </strong>
                                                <?php if (isset($student['license_number']) && !empty($student['license_number'])): ?>
                                                    <br><small>License: <?php echo htmlspecialchars($student['license_number']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="tel:<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['branch_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            $statusLabels = [
                                                'none' => 'No License',
                                                'learner' => 'Learner',
                                                'probationary' => 'Probationary',
                                                'full' => 'Full',
                                                'overseas' => 'Overseas'
                                            ];
                                            $statusColors = [
                                                'none' => 'secondary',
                                                'learner' => 'warning',
                                                'probationary' => 'info',
                                                'full' => 'success',
                                                'overseas' => 'primary'
                                            ];
                                            $licenseStatus = $student['license_status'] ?? 'none';
                                            ?>
                                            <span class="badge badge-<?php echo $statusColors[$licenseStatus]; ?>">
                                                <?php echo $statusLabels[$licenseStatus]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($student['instructor_name']) && !empty($student['instructor_name'])) {
                                                echo htmlspecialchars($student['instructor_name']);
                                            } else {
                                                echo '<span class="text-muted">Not assigned</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($student['enrollment_date'])) {
                                                echo date('d/m/Y', strtotime($student['enrollment_date']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($student['is_active']) && $student['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="view.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Edit Student">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.');"
                                               title="Delete Student">
                                                <i class="fas fa-trash"></i>
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
    position: relative;
    overflow: hidden;
}

.modern-stat-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #4e7e95, #e78759);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modern-stat-card:hover::before {
    opacity: 1;
}

.stat-icon-modern {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    transition: transform 0.3s ease;
}

.modern-stat-card:hover .stat-icon-modern {
    transform: scale(1.1);
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

/* Quick Actions Bar */
.quick-actions-bar {
    background: #ffffff;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.quick-action-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-action-item i {
    color: #4e7e95;
    font-size: 1.1rem;
}

.quick-select {
    padding: 0.5rem 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #ffffff;
    color: #2c3e50;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    outline: none;
}

.quick-select:hover {
    border-color: #4e7e95;
    box-shadow: 0 2px 8px rgba(78, 126, 149, 0.1);
}

.quick-select:focus {
    border-color: #4e7e95;
    box-shadow: 0 0 0 3px rgba(78, 126, 149, 0.1);
}

.btn-action {
    padding: 0.5rem 1rem;
    border: 1px solid #4e7e95;
    border-radius: 8px;
    background: #ffffff;
    color: #4e7e95;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.btn-action:hover {
    background: #4e7e95;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(78, 126, 149, 0.2);
    transform: translateY(-2px);
}

.btn-action i {
    font-size: 1rem;
}

/* Enhanced Card Styling */
.card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    background: linear-gradient(to right, rgba(78, 126, 149, 0.02), transparent);
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

.card-header h3 i {
    color: #4e7e95;
}

.card-body {
    padding: 1.5rem;
}

/* Enhanced Table */
.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table thead {
    background: linear-gradient(to right, #f8f9fa, #ffffff);
}

.data-table thead th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
    border-bottom: 2px solid #e0e0e0;
    white-space: nowrap;
}

.data-table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.data-table tbody tr:hover {
    background: linear-gradient(to right, rgba(78, 126, 149, 0.03), transparent);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.data-table tbody td {
    padding: 1rem;
    color: #34495e;
    font-size: 0.9rem;
}

/* Badge Styling */
.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
    white-space: nowrap;
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

.badge-info {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border: 1px solid rgba(52, 152, 219, 0.2);
}

.badge-primary {
    background: rgba(78, 126, 149, 0.1);
    color: #4e7e95;
    border: 1px solid rgba(78, 126, 149, 0.2);
}

.badge-secondary {
    background: rgba(127, 140, 141, 0.1);
    color: #7f8c8d;
    border: 1px solid rgba(127, 140, 141, 0.2);
}

/* Action Buttons */
.actions {
    white-space: nowrap;
}

.actions .btn {
    margin-right: 0.25rem;
    transition: all 0.2s ease;
}

.actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #7f8c8d;
}

.empty-state i {
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.75rem;
    color: #34495e;
    font-weight: 600;
}

.empty-state p {
    margin-bottom: 2rem;
    font-size: 1rem;
}

/* User Info */
.user-info {
    display: flex;
    flex-direction: column;
}

.user-info strong {
    color: #2c3e50;
    font-weight: 600;
}

.user-info small {
    color: #7f8c8d;
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.text-muted {
    color: #95a5a6;
    font-style: italic;
}

/* Links */
a {
    color: #4e7e95;
    text-decoration: none;
    transition: color 0.2s ease;
}

a:hover {
    color: #3d6478;
    text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .quick-action-item {
        width: 100%;
        justify-content: space-between;
    }
    
    .quick-select,
    .btn-action {
        width: 100%;
        justify-content: center;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .data-table thead th,
    .data-table tbody td {
        padding: 0.75rem 0.5rem;
    }
    
    .actions .btn {
        padding: 0.35rem 0.6rem;
        font-size: 0.85rem;
    }
}

/* Print Styles */
@media print {
    .quick-actions-bar,
    .actions,
    .top-bar {
        display: none !important;
    }
    
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<script>
// Filter Table
function filterTable(filter) {
    const table = document.querySelector('.data-table tbody');
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        let show = true;
        
        if (filter === 'active') {
            const statusBadge = row.querySelector('td:nth-last-child(2)');
            show = statusBadge && statusBadge.textContent.includes('Active');
        } else if (filter === 'test_ready') {
            show = row.dataset.testReady === '1';
        } else if (filter === 'learner') {
            const licenseBadge = row.querySelector('td:nth-child(5)');
            show = licenseBadge && licenseBadge.textContent.includes('Learner');
        } else if (filter === 'no_instructor') {
            const instructorCell = row.querySelector('td:nth-child(6)');
            show = instructorCell && instructorCell.textContent.includes('Not assigned');
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// Sort Table
function sortTable(sortBy) {
    const table = document.querySelector('.data-table tbody');
    const rows = Array.from(table.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aVal, bVal;
        
        switch(sortBy) {
            case 'name_asc':
                aVal = a.querySelector('td:first-child').textContent.trim();
                bVal = b.querySelector('td:first-child').textContent.trim();
                return aVal.localeCompare(bVal);
            case 'name_desc':
                aVal = a.querySelector('td:first-child').textContent.trim();
                bVal = b.querySelector('td:first-child').textContent.trim();
                return bVal.localeCompare(aVal);
            case 'date_new':
                aVal = a.querySelector('td:nth-child(7)').textContent.trim();
                bVal = b.querySelector('td:nth-child(7)').textContent.trim();
                return new Date(bVal) - new Date(aVal);
            case 'date_old':
                aVal = a.querySelector('td:nth-child(7)').textContent.trim();
                bVal = b.querySelector('td:nth-child(7)').textContent.trim();
                return new Date(aVal) - new Date(bVal);
        }
        return 0;
    });
    
    rows.forEach(row => table.appendChild(row));
}

// Export to Excel
function exportToExcel() {
    const table = document.querySelector('.data-table');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        let csvRow = [];
        cols.forEach((col, index) => {
            if (index !== cols.length - 1) {
                csvRow.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
            }
        });
        csv.push(csvRow.join(','));
    });
    
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'students_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Print Table
function printTable() {
    window.print();
}

// Toggle Bulk Mode
function toggleBulkMode() {
    alert('Bulk actions feature coming soon! This will allow you to:\n- Select multiple students\n- Send bulk emails\n- Assign instructors to multiple students\n- Update status in bulk');
}
</script>

<?php include '../views/layouts/footer.php'; ?>