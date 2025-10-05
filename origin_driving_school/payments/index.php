<?php
/**
 * Payments Management - List All Payments
 * 
 * File path: payments/index.php
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
$pageTitle = 'Payments Management';

// Handle search and filters
$searchTerm = $_GET['search'] ?? '';
$methodFilter = $_GET['method'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query conditions
$searchQuery = '';
$conditions = [];

if (!empty($searchTerm)) {
    $searchQuery = "AND (CONCAT(u.first_name, ' ', u.last_name) LIKE '%$searchTerm%' OR i.invoice_number LIKE '%$searchTerm%' OR p.transaction_reference LIKE '%$searchTerm%')";
}

if (!empty($methodFilter)) {
    $conditions['payment_method'] = $methodFilter;
}

// Get payments with pagination
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*, 
        i.invoice_number,
        CONCAT(u.first_name, ' ', u.last_name) AS student_name,
        CONCAT(pu.first_name, ' ', pu.last_name) AS processed_by_name
        FROM payments p
        INNER JOIN invoices i ON p.invoice_id = i.invoice_id
        INNER JOIN students s ON i.student_id = s.student_id
        INNER JOIN users u ON s.user_id = u.user_id
        INNER JOIN users pu ON p.processed_by = pu.user_id
        WHERE 1=1 $searchQuery";

if (!empty($methodFilter)) {
    $sql .= " AND p.payment_method = '" . $studentModel->db->escape($methodFilter) . "'";
}

if (!empty($dateFrom)) {
    $sql .= " AND p.payment_date >= '" . $studentModel->db->escape($dateFrom) . "'";
}

if (!empty($dateTo)) {
    $sql .= " AND p.payment_date <= '" . $studentModel->db->escape($dateTo) . "'";
}

$sql .= " ORDER BY p.payment_date DESC, p.payment_id DESC LIMIT $perPage OFFSET $offset";

$payments = $studentModel->customQuery($sql);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM payments p 
             INNER JOIN invoices i ON p.invoice_id = i.invoice_id
             INNER JOIN students s ON i.student_id = s.student_id
             INNER JOIN users u ON s.user_id = u.user_id
             WHERE 1=1 $searchQuery";

if (!empty($methodFilter)) {
    $countSql .= " AND p.payment_method = '" . $studentModel->db->escape($methodFilter) . "'";
}

if (!empty($dateFrom)) {
    $countSql .= " AND p.payment_date >= '" . $studentModel->db->escape($dateFrom) . "'";
}

if (!empty($dateTo)) {
    $countSql .= " AND p.payment_date <= '" . $studentModel->db->escape($dateTo) . "'";
}

$totalResult = $studentModel->customQueryOne($countSql);
$totalPayments = $totalResult['total'] ?? 0;
$totalPages = ceil($totalPayments / $perPage);

// Get statistics
$statsToday = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE(payment_date) = CURDATE()");
$statsThisMonth = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
$statsTotal = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM payments");

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1><i class="fas fa-money-bill-wave"></i> Payments Management</h1>
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
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo CURRENCY_SYMBOL . number_format($statsToday['total'], 2); ?></h3>
                        <p>Today's Revenue (<?php echo $statsToday['count']; ?> payments)</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo CURRENCY_SYMBOL . number_format($statsThisMonth['total'], 2); ?></h3>
                        <p>This Month (<?php echo $statsThisMonth['count']; ?> payments)</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo CURRENCY_SYMBOL . number_format($statsTotal['total'], 2); ?></h3>
                        <p>Total Revenue (<?php echo $statsTotal['count']; ?> payments)</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <?php 
                        $avgPayment = $statsTotal['count'] > 0 ? $statsTotal['total'] / $statsTotal['count'] : 0;
                        ?>
                        <h3><?php echo CURRENCY_SYMBOL . number_format($avgPayment, 2); ?></h3>
                        <p>Average Payment</p>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter Section -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Search & Filter</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="filter-form">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Student, Invoice, Reference..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Payment Method</label>
                                <select name="method" class="form-control">
                                    <option value="">All Methods</option>
                                    <option value="cash" <?php echo $methodFilter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="credit_card" <?php echo $methodFilter === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="debit_card" <?php echo $methodFilter === 'debit_card' ? 'selected' : ''; ?>>Debit Card</option>
                                    <option value="bank_transfer" <?php echo $methodFilter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="paypal" <?php echo $methodFilter === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control" 
                                       value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control" 
                                       value="<?php echo htmlspecialchars($dateTo); ?>">
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
            
            <!-- Payments Table -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-list"></i> All Payments (<?php echo $totalPayments; ?>)</h3>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Record New Payment
                    </a>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($payments)): ?>
                        <div class="table-empty">
                            <i class="fas fa-money-bill-wave"></i>
                            <p>No payments found</p>
                            <?php if (!empty($searchTerm) || !empty($methodFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
                                <p><a href="index.php">Clear filters to view all payments</a></p>
                            <?php else: ?>
                                <p><a href="create.php" class="btn btn-primary">Record your first payment</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Date</th>
                                        <th>Invoice</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Processed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><strong>#<?php echo $payment['payment_id']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>/invoices/view.php?id=<?php echo $payment['invoice_id']; ?>">
                                                    <?php echo htmlspecialchars($payment['invoice_number']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                            <td>
                                                <strong style="color: #28a745;">
                                                    <?php echo CURRENCY_SYMBOL . number_format($payment['amount'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $payment['transaction_reference'] ? htmlspecialchars($payment['transaction_reference']) : '<em>N/A</em>'; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['processed_by_name']); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view.php?id=<?php echo $payment['payment_id']; ?>" 
                                                       class="btn btn-info btn-sm" title="View Receipt">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $payment['payment_id']; ?>" 
                                                       class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                                        <td colspan="4" style="text-align: right;">Total on this page:</td>
                                        <td colspan="5" style="color: #28a745;">
                                            <?php 
                                            $pageTotal = array_sum(array_column($payments, 'amount'));
                                            echo CURRENCY_SYMBOL . number_format($pageTotal, 2);
                                            ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&method=<?php echo $methodFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
                                       class="btn btn-secondary btn-sm">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <span class="pagination-info">
                                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                                </span>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&method=<?php echo $methodFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" 
                                       class="btn btn-secondary btn-sm">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>