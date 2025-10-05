<?php
/**
 * Invoices List Page - Origin Driving School Management System
 * 
 * File path: invoices/index.php
 * 
 * Displays comprehensive invoice management with advanced filtering,
 * financial analytics, and payment tracking capabilities
 * 
 * @author Origin Driving School Development Team
 * @version 2.0
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

// Authentication and authorization
requireLogin();

// Include required models
require_once APP_PATH . '/models/User.php';
require_once APP_PATH . '/models/Invoice.php';
require_once APP_PATH . '/models/Student.php';
require_once APP_PATH . '/models/CourseAndOther.php';

// Initialize models
$invoiceModel = new Invoice();
$studentModel = new Student();
$courseModel = new Course();

// Get current user details
$userRole = getCurrentUserRole();
$userId = getCurrentUserId();

// Get filter and pagination parameters
$status = sanitize($_GET['status'] ?? '');
$searchTerm = sanitize($_GET['search'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');
$courseFilter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Initialize variables
$invoices = [];
$totalInvoices = 0;
$studentInfo = null;

// Fetch invoices based on user role
if ($userRole === 'admin' || $userRole === 'staff') {
    // Admin/Staff see all invoices with advanced filtering
    $conditions = [];
    $params = [];
    
    if ($status) {
        $conditions[] = "i.status = ?";
        $params[] = $status;
    }
    
    if ($searchTerm) {
        $conditions[] = "(i.invoice_number LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }
    
    if ($dateFrom) {
        $conditions[] = "i.issue_date >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $conditions[] = "i.issue_date <= ?";
        $params[] = $dateTo;
    }
    
    if ($courseFilter > 0) {
        $conditions[] = "i.course_id = ?";
        $params[] = $courseFilter;
    }
    
    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM invoices i
                 INNER JOIN students s ON i.student_id = s.student_id
                 INNER JOIN users u ON s.user_id = u.user_id
                 $whereClause";
    
    $countResult = $invoiceModel->customQueryOne($countSql, $params);
    $totalInvoices = $countResult['total'] ?? 0;
    
    // Get paginated invoices with details
    $sql = "SELECT i.*, 
            CONCAT(u.first_name, ' ', u.last_name) as student_name,
            u.email as student_email,
            c.course_name,
            s.student_id
            FROM invoices i
            INNER JOIN students s ON i.student_id = s.student_id
            INNER JOIN users u ON s.user_id = u.user_id
            LEFT JOIN courses c ON i.course_id = c.course_id
            $whereClause
            ORDER BY i.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $perPage;
    $params[] = $offset;
    
    $invoices = $invoiceModel->customQuery($sql, $params);
    
} elseif ($userRole === 'student') {
    // Students see only their invoices
    $student = $studentModel->getByUserId($userId);
    
    if ($student) {
        $studentInfo = $student;
        $studentId = $student['student_id'];
        
        $conditions = ["i.student_id = ?"];
        $params = [$studentId];
        
        if ($status) {
            $conditions[] = "i.status = ?";
            $params[] = $status;
        }
        
        if ($dateFrom) {
            $conditions[] = "i.issue_date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $conditions[] = "i.issue_date <= ?";
            $params[] = $dateTo;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM invoices i $whereClause";
        $countResult = $invoiceModel->customQueryOne($countSql, $params);
        $totalInvoices = $countResult['total'] ?? 0;
        
        // Get paginated invoices
        $sql = "SELECT i.*, c.course_name
                FROM invoices i
                LEFT JOIN courses c ON i.course_id = c.course_id
                $whereClause
                ORDER BY i.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $invoices = $invoiceModel->customQuery($sql, $params);
    }
}

// Calculate comprehensive statistics
$stats = [
    'total_revenue' => 0,
    'total_paid' => 0,
    'total_outstanding' => 0,
    'paid_count' => 0,
    'unpaid_count' => 0,
    'partially_paid_count' => 0,
    'overdue_count' => 0,
    'this_month_revenue' => 0,
    'this_month_paid' => 0
];

$currentMonth = date('Y-m');

foreach ($invoices as &$invoice) {
    // Calculate balance
    $invoice['balance_due'] = $invoice['total_amount'] - $invoice['amount_paid'];
    
    // Update statistics
    $stats['total_revenue'] += $invoice['total_amount'];
    $stats['total_paid'] += $invoice['amount_paid'];
    $stats['total_outstanding'] += $invoice['balance_due'];
    
    // Count by status
    switch ($invoice['status']) {
        case 'paid':
            $stats['paid_count']++;
            break;
        case 'unpaid':
            $stats['unpaid_count']++;
            break;
        case 'partially_paid':
            $stats['partially_paid_count']++;
            break;
        case 'overdue':
            $stats['overdue_count']++;
            break;
    }
    
    // This month's revenue
    if (strpos($invoice['issue_date'], $currentMonth) === 0) {
        $stats['this_month_revenue'] += $invoice['total_amount'];
        $stats['this_month_paid'] += $invoice['amount_paid'];
    }
    
    // Auto-update status based on due date
    if ($invoice['status'] !== 'paid' && strtotime($invoice['due_date']) < strtotime('today')) {
        $invoice['status'] = 'overdue';
        // Update in database if admin/staff
        if ($userRole === 'admin' || $userRole === 'staff') {
            $invoiceModel->update($invoice['invoice_id'], ['status' => 'overdue']);
        }
    }
}

// Get all courses for filter dropdown
$activeCourses = $courseModel->getActiveCourses();

// Calculate pagination
$totalPages = ceil($totalInvoices / $perPage);

// Page configuration
$pageTitle = 'Invoices Management';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h1><i class="fas fa-file-invoice-dollar"></i> Invoices Management</h1>
                <p style="color: #666; margin-top: 0.3rem;">
                    <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                        Comprehensive invoice and payment tracking system
                    <?php else: ?>
                        View your invoices and payment history
                    <?php endif; ?>
                </p>
            </div>
            <div class="user-menu">
                <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                    <a href="<?php echo APP_URL; ?>/invoices/create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Invoice
                    </a>
                    <a href="<?php echo APP_URL; ?>/invoices/export.php?<?php echo http_build_query($_GET); ?>" 
                       class="btn btn-success">
                        <i class="fas fa-file-export"></i> Export
                    </a>
                <?php endif; ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            
            <!-- Flash Messages -->
            <?php 
            $flashMessage = getFlashMessage();
            if ($flashMessage): 
            ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?>" style="animation: slideInDown 0.3s ease;">
                    <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($flashMessage['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Financial Statistics Dashboard -->
            <div class="stats-grid">
                <div class="stat-card gradient-purple">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo formatCurrency($stats['total_revenue']); ?></h3>
                        <p>Total Revenue</p>
                        <small style="opacity: 0.9;">
                            <i class="fas fa-calendar"></i> 
                            This Month: <?php echo formatCurrency($stats['this_month_revenue']); ?>
                        </small>
                    </div>
                </div>
                
                <div class="stat-card gradient-green">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo formatCurrency($stats['total_paid']); ?></h3>
                        <p>Total Collected</p>
                        <small style="opacity: 0.9;">
                            <?php echo $stats['paid_count']; ?> invoice(s) paid
                        </small>
                    </div>
                </div>
                
                <div class="stat-card gradient-red">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo formatCurrency($stats['total_outstanding']); ?></h3>
                        <p>Outstanding Amount</p>
                        <small style="opacity: 0.9;">
                            <?php echo ($stats['unpaid_count'] + $stats['partially_paid_count']); ?> pending
                        </small>
                    </div>
                </div>
                
                <div class="stat-card gradient-blue">
                    <div class="stat-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($totalInvoices); ?></h3>
                        <p>Total Invoices</p>
                        <small style="opacity: 0.9;">
                            <?php echo $stats['overdue_count']; ?> overdue
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Filters Section -->
            <div class="card">
                <div class="card-header" style="cursor: pointer;" onclick="toggleFilters()">
                    <h3>
                        <i class="fas fa-filter"></i> Advanced Filters
                        <i class="fas fa-chevron-down" id="filterToggle" style="float: right; transition: transform 0.3s;"></i>
                    </h3>
                </div>
                <div class="card-body" id="filterSection">
                    <form method="GET" action="" class="filter-form">
                        <div class="form-row">
                            
                            <div class="form-group">
                                <label for="status">
                                    <i class="fas fa-tag"></i> Status
                                </label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>
                                        Paid
                                    </option>
                                    <option value="unpaid" <?php echo $status === 'unpaid' ? 'selected' : ''; ?>>
                                        Unpaid
                                    </option>
                                    <option value="partially_paid" <?php echo $status === 'partially_paid' ? 'selected' : ''; ?>>
                                        Partially Paid
                                    </option>
                                    <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>
                                        Overdue
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_from">
                                    <i class="fas fa-calendar-alt"></i> Date From
                                </label>
                                <input type="date" name="date_from" id="date_from" class="form-control" 
                                       value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">
                                    <i class="fas fa-calendar-alt"></i> Date To
                                </label>
                                <input type="date" name="date_to" id="date_to" class="form-control" 
                                       value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            
                            <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                            <div class="form-group">
                                <label for="course_id">
                                    <i class="fas fa-book"></i> Course
                                </label>
                                <select name="course_id" id="course_id" class="form-control">
                                    <option value="">All Courses</option>
                                    <?php foreach ($activeCourses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>" 
                                                <?php echo $courseFilter == $course['course_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="search">
                                    <i class="fas fa-search"></i> Search
                                </label>
                                <input type="text" name="search" id="search" class="form-control" 
                                       placeholder="Invoice #, student name, email..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <?php endif; ?>
                            
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="<?php echo APP_URL; ?>/invoices/" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset All
                            </a>
                            <button type="button" class="btn btn-info" onclick="applyQuickFilter('overdue')">
                                <i class="fas fa-exclamation-triangle"></i> Show Overdue Only
                            </button>
                            <button type="button" class="btn btn-success" onclick="applyQuickFilter('unpaid')">
                                <i class="fas fa-clock"></i> Show Unpaid Only
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Invoices Table -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-list"></i> Invoice List 
                        <?php if ($totalInvoices > 0): ?>
                            <span class="badge badge-primary" style="font-size: 0.9rem;">
                                <?php echo number_format($totalInvoices); ?> Total
                            </span>
                        <?php endif; ?>
                    </h3>
                    <?php if (!empty($invoices) && ($userRole === 'admin' || $userRole === 'staff')): ?>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="printInvoiceList()" class="btn btn-sm btn-secondary">
                            <i class="fas fa-print"></i> Print List
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($invoices)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice" style="font-size: 4rem; color: #cbd5e0;"></i>
                            <h3>No Invoices Found</h3>
                            <p style="color: #718096;">
                                <?php if (!empty($searchTerm) || !empty($status)): ?>
                                    No invoices match your current filters. Try adjusting your search criteria.
                                <?php else: ?>
                                    There are no invoices in the system yet.
                                <?php endif; ?>
                            </p>
                            <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                <a href="<?php echo APP_URL; ?>/invoices/create.php" class="btn btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-plus"></i> Create First Invoice
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table" id="invoiceTable">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                        <th>Student</th>
                                        <?php endif; ?>
                                        <th>Course</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): ?>
                                    <tr class="invoice-row <?php echo $invoice['status'] === 'overdue' ? 'overdue-row' : ''; ?>">
                                        <td>
                                            <strong style="color: #4e7e95;">
                                                <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                                            </strong>
                                            <?php if ($invoice['status'] === 'overdue'): ?>
                                                <br><small class="text-danger">
                                                    <i class="fas fa-exclamation-circle"></i> Overdue
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                        <td>
                                            <div class="student-info">
                                                <strong><?php echo htmlspecialchars($invoice['student_name'] ?? 'N/A'); ?></strong>
                                                <br>
                                                <small style="color: #718096;">
                                                    <?php echo htmlspecialchars($invoice['student_email'] ?? ''); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                        
                                        <td>
                                            <i class="fas fa-book" style="color: #4e7e95;"></i>
                                            <?php echo htmlspecialchars($invoice['course_name'] ?? 'N/A'); ?>
                                        </td>
                                        
                                        <td>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?>
                                        </td>
                                        
                                        <td>
                                            <i class="fas fa-calendar-check"></i>
                                            <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                                            <?php if (strtotime($invoice['due_date']) < strtotime('today') && $invoice['status'] !== 'paid'): ?>
                                                <br><small class="text-danger">
                                                    (<?php echo abs(floor((strtotime('today') - strtotime($invoice['due_date'])) / 86400)); ?> days overdue)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <strong style="font-size: 1.1rem;">
                                                <?php echo formatCurrency($invoice['total_amount']); ?>
                                            </strong>
                                        </td>
                                        
                                        <td>
                                            <span style="color: #38a169;">
                                                <?php echo formatCurrency($invoice['amount_paid']); ?>
                                            </span>
                                            <?php if ($invoice['amount_paid'] > 0 && $invoice['status'] !== 'paid'): ?>
                                                <br><small style="color: #718096;">
                                                    <?php echo round(($invoice['amount_paid'] / $invoice['total_amount']) * 100, 1); ?>% paid
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <strong class="<?php echo $invoice['balance_due'] > 0 ? 'text-danger' : 'text-success'; ?>" 
                                                    style="font-size: 1.1rem;">
                                                <?php echo formatCurrency($invoice['balance_due']); ?>
                                            </strong>
                                        </td>
                                        
                                        <td>
                                            <?php
                                            $statusConfig = [
                                                'paid' => ['class' => 'success', 'icon' => 'check-circle', 'label' => 'Paid'],
                                                'unpaid' => ['class' => 'danger', 'icon' => 'times-circle', 'label' => 'Unpaid'],
                                                'partially_paid' => ['class' => 'warning', 'icon' => 'clock', 'label' => 'Partially Paid'],
                                                'overdue' => ['class' => 'danger', 'icon' => 'exclamation-triangle', 'label' => 'Overdue']
                                            ];
                                            $config = $statusConfig[$invoice['status']] ?? $statusConfig['unpaid'];
                                            ?>
                                            <span class="badge badge-<?php echo $config['class']; ?>" 
                                                  style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                                <i class="fas fa-<?php echo $config['icon']; ?>"></i>
                                                <?php echo $config['label']; ?>
                                            </span>
                                        </td>
                                        
                                        <td class="actions">
                                            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                                <a href="<?php echo APP_URL; ?>/invoices/view.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
                                                    <?php if ($invoice['status'] !== 'paid'): ?>
                                                    <a href="<?php echo APP_URL; ?>/invoices/add-payment.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       title="Record Payment">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="<?php echo APP_URL; ?>/invoices/edit.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="Edit Invoice">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($invoice['status'] !== 'paid'): ?>
                                                    <a href="<?php echo APP_URL; ?>/invoices/send-reminder.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Send Reminder"
                                                       onclick="return confirm('Send payment reminder to student?');">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="<?php echo APP_URL; ?>/invoices/delete.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this invoice? This action cannot be undone.');"
                                                       title="Delete Invoice">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="<?php echo APP_URL; ?>/invoices/print.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                                   class="btn btn-sm btn-secondary" 
                                                   target="_blank" 
                                                   title="Print/Download">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f7fafc; font-weight: 600;">
                                        <td colspan="<?php echo ($userRole === 'admin' || $userRole === 'staff') ? '5' : '4'; ?>" 
                                            style="text-align: right; padding: 1rem;">
                                            Page Total:
                                        </td>
                                        <td><?php echo formatCurrency(array_sum(array_column($invoices, 'total_amount'))); ?></td>
                                        <td><?php echo formatCurrency(array_sum(array_column($invoices, 'amount_paid'))); ?></td>
                                        <td><?php echo formatCurrency(array_sum(array_column($invoices, 'balance_due'))); ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination-wrapper">
                            <div class="pagination-info">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalInvoices); ?> 
                                of <?php echo number_format($totalInvoices); ?> invoices
                            </div>
                            <nav class="pagination">
                                <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="page-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="page-link">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span class="page-link disabled">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <span class="page-link disabled">...</span>
                                    <?php endif; ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" 
                                       class="page-link"><?php echo $totalPages; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    padding: 1.5rem;
    border-radius: 12px;
    color: white;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.gradient-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.gradient-green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.gradient-red { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.gradient-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.9;
}

.stat-details h3 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
}

.stat-details p {
    margin: 0.25rem 0 0 0;
    font-size: 1rem;
    opacity: 0.95;
}

.stat-details small {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.85rem;
}

.filter-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 0;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-state h3 {
    color: #2d3748;
    margin: 1rem 0 0.5rem 0;
}

.student-info {
    line-height: 1.5;
}

.invoice-row.overdue-row {
    background: #fff5f5;
}

.actions {
    white-space: nowrap;
}

.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid #e2e8f0;
}

.pagination-info {
    color: #718096;
    font-size: 0.9rem;
}

.pagination {
    display: flex;
    gap: 0.5rem;
}

.page-link {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #4e7e95;
    text-decoration: none;
    transition: all 0.2s ease;
    background: white;
}

.page-link:hover:not(.disabled):not(.active) {
    background: #4e7e95;
    color: white;
    border-color: #4e7e95;
}

.page-link.active {
    background: #4e7e95;
    color: white;
    border-color: #4e7e95;
    font-weight: 600;
}

.page-link.disabled {
    color: #cbd5e0;
    cursor: default;
    pointer-events: none;
}

@media print {
    .top-bar, .card-header, .actions, .pagination-wrapper {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .pagination-wrapper {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<script>
// Toggle filter section
function toggleFilters() {
    const section = document.getElementById('filterSection');
    const toggle = document.getElementById('filterToggle');
    
    if (section.style.display === 'none') {
        section.style.display = 'block';
        toggle.style.transform = 'rotate(180deg)';
    } else {
        section.style.display = 'none';
        toggle.style.transform = 'rotate(0deg)';
    }
}

// Quick filter functions
function applyQuickFilter(status) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('status', status);
    currentUrl.searchParams.delete('page'); // Reset to page 1
    window.location.href = currentUrl.toString();
}

// Print invoice list
function printInvoiceList() {
    window.print();
}

// Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

// Confirm before leaving page with unsaved filter changes
let formChanged = false;
const filterForm = document.querySelector('.filter-form');
if (filterForm) {
    filterForm.addEventListener('change', function() {
        formChanged = true;
    });
    
    filterForm.addEventListener('submit', function() {
        formChanged = false;
    });
}
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>