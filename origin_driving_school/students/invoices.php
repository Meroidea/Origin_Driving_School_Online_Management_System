<?php
/**
 * Student Invoices - View My Invoices
 * 
 * File path: student/invoices.php
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

// Define APP_ACCESS constant
define('APP_ACCESS', true);

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is a student
requireLogin();
requireRole(['student']);

// Include required models
require_once APP_PATH . '/models/Student.php';

// Initialize models
$studentModel = new Student();

// Set page title
$pageTitle = 'My Invoices';

// Get student ID from user session
$userId = $_SESSION['user_id'];

// Get student details
$studentSql = "SELECT s.* FROM students s 
               INNER JOIN users u ON s.user_id = u.user_id 
               WHERE u.user_id = ?";
$student = $studentModel->customQueryOne($studentSql, [$userId]);

if (!$student) {
    setFlashMessage('error', 'Student profile not found');
    redirect('/dashboard.php');
}

$studentId = $student['student_id'];

// Get filter
$filterType = $_GET['filter'] ?? 'all';

// Get invoices
$invoicesSql = "SELECT i.*, 
                c.course_name, c.course_code,
                COUNT(p.payment_id) as payment_count
                FROM invoices i
                INNER JOIN courses c ON i.course_id = c.course_id
                LEFT JOIN payments p ON i.invoice_id = p.invoice_id
                WHERE i.student_id = ?";

$params = [$studentId];

if ($filterType === 'unpaid') {
    $invoicesSql .= " AND i.status IN ('unpaid', 'partially_paid', 'overdue')";
} elseif ($filterType === 'paid') {
    $invoicesSql .= " AND i.status = 'paid'";
} elseif ($filterType === 'overdue') {
    $invoicesSql .= " AND i.status = 'overdue'";
}

$invoicesSql .= " GROUP BY i.invoice_id ORDER BY i.invoice_id DESC";

$invoices = $studentModel->customQuery($invoicesSql, $params);

// Get statistics
$statsTotal = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE student_id = ?", [$studentId]);
$statsUnpaid = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(balance_due), 0) as total FROM invoices WHERE student_id = ? AND status IN ('unpaid', 'partially_paid', 'overdue')", [$studentId]);
$statsPaid = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE student_id = ? AND status = 'paid'", [$studentId]);
$statsOverdue = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(balance_due), 0) as total FROM invoices WHERE student_id = ? AND status = 'overdue'", [$studentId]);

// Include header
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1><i class="fas fa-file-invoice-dollar"></i> My Invoices</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            
            <!-- Overdue Alert -->
            <?php if ($statsOverdue['count'] > 0): ?>
                <div class="alert alert-danger" style="margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0;">
                        <i class="fas fa-exclamation-triangle"></i> Payment Overdue!
                    </h4>
                    <p style="margin: 0;">
                        You have <strong><?php echo $statsOverdue['count']; ?> overdue invoice(s)</strong> with a total balance of 
                        <strong><?php echo CURRENCY_SYMBOL . number_format($statsOverdue['total'], 2); ?></strong>
                    </p>
                    <p style="margin: 0.5rem 0 0 0;">
                        Please contact us or make a payment as soon as possible to avoid service interruption.
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $statsTotal['count']; ?></h3>
                        <p>Total Invoices</p>
                        <small><?php echo CURRENCY_SYMBOL . number_format($statsTotal['total'], 2); ?></small>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo CURRENCY_SYMBOL . number_format($statsUnpaid['total'], 2); ?></h3>
                        <p>Outstanding Balance</p>
                        <small><?php echo $statsUnpaid['count']; ?> invoice(s)</small>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo CURRENCY_SYMBOL . number_format($statsPaid['total'], 2); ?></h3>
                        <p>Total Paid</p>
                        <small><?php echo $statsPaid['count']; ?> invoice(s)</small>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo CURRENCY_SYMBOL . number_format($statsOverdue['total'], 2); ?></h3>
                        <p>Overdue</p>
                        <small><?php echo $statsOverdue['count']; ?> invoice(s)</small>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body" style="padding: 1rem;">
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="?filter=all" class="btn btn-<?php echo $filterType === 'all' ? 'primary' : 'secondary'; ?> btn-sm">
                            <i class="fas fa-list"></i> All (<?php echo $statsTotal['count']; ?>)
                        </a>
                        <a href="?filter=unpaid" class="btn btn-<?php echo $filterType === 'unpaid' ? 'primary' : 'secondary'; ?> btn-sm">
                            <i class="fas fa-clock"></i> Unpaid (<?php echo $statsUnpaid['count']; ?>)
                        </a>
                        <a href="?filter=paid" class="btn btn-<?php echo $filterType === 'paid' ? 'primary' : 'secondary'; ?> btn-sm">
                            <i class="fas fa-check-circle"></i> Paid (<?php echo $statsPaid['count']; ?>)
                        </a>
                        <a href="?filter=overdue" class="btn btn-<?php echo $filterType === 'overdue' ? 'primary' : 'secondary'; ?> btn-sm">
                            <i class="fas fa-exclamation-triangle"></i> Overdue (<?php echo $statsOverdue['count']; ?>)
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Invoices List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> My Invoices (<?php echo count($invoices); ?>)</h3>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($invoices)): ?>
                        <div class="table-empty">
                            <i class="fas fa-file-invoice"></i>
                            <p>No invoices found</p>
                            <?php if ($filterType !== 'all'): ?>
                                <p><a href="?filter=all">View all invoices</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        
                        <div style="display: grid; gap: 1.5rem;">
                            <?php foreach ($invoices as $invoice): ?>
                                <?php
                                $isOverdue = ($invoice['status'] === 'overdue');
                                $isPaid = ($invoice['status'] === 'paid');
                                ?>
                                
                                <div style="border: 2px solid <?php 
                                    echo $isPaid ? '#28a745' : 
                                        ($isOverdue ? '#dc3545' : '#4e7e95'); 
                                ?>; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                    
                                    <!-- Invoice Header -->
                                    <div style="background-color: <?php 
                                        echo $isPaid ? 'rgba(40, 167, 69, 0.1)' : 
                                            ($isOverdue ? 'rgba(220, 53, 69, 0.1)' : 'rgba(78, 126, 149, 0.1)'); 
                                    ?>; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin: 0; color: #333;">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                                Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                                            </h4>
                                            <div style="font-size: 0.9rem; color: #666; margin-top: 0.25rem;">
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($invoice['course_code']); ?>
                                                </span>
                                                <span style="margin-left: 0.5rem;">
                                                    <?php echo htmlspecialchars($invoice['course_name']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php
                                        $badgeClass = 'secondary';
                                        $statusText = ucfirst($invoice['status']);
                                        
                                        switch($invoice['status']) {
                                            case 'paid':
                                                $badgeClass = 'success';
                                                break;
                                            case 'unpaid':
                                                $badgeClass = 'warning';
                                                break;
                                            case 'overdue':
                                                $badgeClass = 'danger';
                                                break;
                                            case 'partially_paid':
                                                $badgeClass = 'info';
                                                $statusText = 'Partial';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $badgeClass; ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Invoice Details -->
                                    <div style="padding: 1.5rem;">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                                            
                                            <div>
                                                <div style="color: #666; font-size: 0.85rem; margin-bottom: 0.25rem;">Issue Date</div>
                                                <div style="font-weight: bold; color: #333;">
                                                    <?php echo date('F j, Y', strtotime($invoice['issue_date'])); ?>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <div style="color: #666; font-size: 0.85rem; margin-bottom: 0.25rem;">Due Date</div>
                                                <div style="font-weight: bold; color: <?php echo $isOverdue ? '#dc3545' : '#333'; ?>;">
                                                    <?php echo date('F j, Y', strtotime($invoice['due_date'])); ?>
                                                    <?php if ($isOverdue): ?>
                                                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <div style="color: #666; font-size: 0.85rem; margin-bottom: 0.25rem;">Payments Made</div>
                                                <div style="font-weight: bold; color: #333;">
                                                    <?php echo $invoice['payment_count']; ?> payment(s)
                                                </div>
                                            </div>
                                            
                                        </div>
                                        
                                        <!-- Amount Breakdown -->
                                        <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 5px;">
                                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.5rem;">
                                                <div style="color: #666;">Subtotal:</div>
                                                <div style="font-weight: bold; text-align: right;">
                                                    <?php echo CURRENCY_SYMBOL . number_format($invoice['subtotal'], 2); ?>
                                                </div>
                                                
                                                <div style="color: #666;">Tax (GST):</div>
                                                <div style="font-weight: bold; text-align: right;">
                                                    <?php echo CURRENCY_SYMBOL . number_format($invoice['tax_amount'], 2); ?>
                                                </div>
                                                
                                                <div style="color: #666; padding-top: 0.5rem; border-top: 1px solid #ddd;">Total Amount:</div>
                                                <div style="font-weight: bold; font-size: 1.1rem; text-align: right; padding-top: 0.5rem; border-top: 1px solid #ddd;">
                                                    <?php echo CURRENCY_SYMBOL . number_format($invoice['total_amount'], 2); ?>
                                                </div>
                                                
                                                <div style="color: #28a745;">Amount Paid:</div>
                                                <div style="font-weight: bold; color: #28a745; text-align: right;">
                                                    <?php echo CURRENCY_SYMBOL . number_format($invoice['amount_paid'], 2); ?>
                                                </div>
                                                
                                                <div style="color: <?php echo $invoice['balance_due'] > 0 ? '#dc3545' : '#28a745'; ?>; font-size: 1.1rem; font-weight: bold;">
                                                    Balance Due:
                                                </div>
                                                <div style="font-weight: bold; font-size: 1.25rem; color: <?php echo $invoice['balance_due'] > 0 ? '#dc3545' : '#28a745'; ?>; text-align: right;">
                                                    <?php echo CURRENCY_SYMBOL . number_format($invoice['balance_due'], 2); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($invoice['notes'])): ?>
                                            <div style="margin-top: 1rem; padding: 0.75rem; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">
                                                <div style="font-weight: bold; color: #856404; margin-bottom: 0.25rem;">
                                                    <i class="fas fa-sticky-note"></i> Notes:
                                                </div>
                                                <div style="font-size: 0.9rem; color: #856404;">
                                                    <?php echo htmlspecialchars($invoice['notes']); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Action Buttons -->
                                        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                                            <a href="<?php echo APP_URL; ?>/invoices/view.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                               class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View Invoice
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/invoices/download.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                               class="btn btn-secondary btn-sm">
                                                <i class="fas fa-download"></i> Download PDF
                                            </a>
                                            <?php if ($invoice['balance_due'] > 0): ?>
                                                <a href="<?php echo APP_URL; ?>/payments/make-payment.php?invoice_id=<?php echo $invoice['invoice_id']; ?>" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-credit-card"></i> Make Payment
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                </div>
                                
                            <?php endforeach; ?>
                        </div>
                        
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header" style="background-color: #4e7e95; color: white;">
                    <h3 style="margin: 0;"><i class="fas fa-info-circle"></i> Payment Information</h3>
                </div>
                <div class="card-body">
                    <h4 style="margin-bottom: 1rem;">Accepted Payment Methods:</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="padding: 1rem; border: 2px solid #e5edf0; border-radius: 5px; text-align: center;">
                            <i class="fas fa-credit-card" style="font-size: 2rem; color: #4e7e95; margin-bottom: 0.5rem;"></i>
                            <div style="font-weight: bold;">Credit/Debit Card</div>
                        </div>
                        <div style="padding: 1rem; border: 2px solid #e5edf0; border-radius: 5px; text-align: center;">
                            <i class="fas fa-money-bill-wave" style="font-size: 2rem; color: #28a745; margin-bottom: 0.5rem;"></i>
                            <div style="font-weight: bold;">Cash</div>
                        </div>
                        <div style="padding: 1rem; border: 2px solid #e5edf0; border-radius: 5px; text-align: center;">
                            <i class="fas fa-university" style="font-size: 2rem; color: #e78759; margin-bottom: 0.5rem;"></i>
                            <div style="font-weight: bold;">Bank Transfer</div>
                        </div>
                    </div>
                    
                    <div style="background-color: #d1ecf1; padding: 1rem; border-left: 4px solid #17a2b8; border-radius: 3px;">
                        <h5 style="margin: 0 0 0.5rem 0; color: #0c5460;">
                            <i class="fas fa-question-circle"></i> Need Help?
                        </h5>
                        <p style="margin: 0; color: #0c5460;">
                            If you have questions about your invoice or need to arrange a payment plan, 
                            please contact us at <strong>info@origindrivingschool.com.au</strong> or call <strong>1300-ORIGIN</strong>.
                        </p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>