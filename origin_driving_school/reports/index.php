<?php
/**
 * file path: reports/index.php
 * 
 * Advanced Reports & Analytics Dashboard
 * Origin Driving School Management System
 * 
 * Professional reporting with visual analytics and export capabilities
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 2.0
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/models/User.php';
require_once '../app/core/Database.php';

// Require admin or staff login
requireRole(['admin', 'staff']);

$db = Database::getInstance();

// Get date range and report type from query parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'overview';

// Quick date presets
if (isset($_GET['preset'])) {
    switch ($_GET['preset']) {
        case 'today':
            $startDate = $endDate = date('Y-m-d');
            break;
        case 'week':
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate = date('Y-m-d');
            break;
        case 'month':
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');
            break;
        case 'quarter':
            $startDate = date('Y-m-d', strtotime('-3 months'));
            $endDate = date('Y-m-d');
            break;
        case 'year':
            $startDate = date('Y-01-01');
            $endDate = date('Y-m-d');
            break;
    }
}

// Financial Summary
$financialSql = "SELECT 
    COUNT(DISTINCT i.invoice_id) as total_invoices,
    COALESCE(SUM(i.total_amount), 0) as total_revenue,
    COALESCE(SUM(i.amount_paid), 0) as total_paid,
    COALESCE(SUM(i.balance_due), 0) as total_outstanding,
    COUNT(CASE WHEN i.status = 'paid' THEN 1 END) as paid_invoices,
    COUNT(CASE WHEN i.status = 'unpaid' THEN 1 END) as unpaid_invoices,
    COUNT(CASE WHEN i.status = 'overdue' THEN 1 END) as overdue_invoices,
    COUNT(CASE WHEN i.status = 'partially_paid' THEN 1 END) as partial_invoices
    FROM invoices i
    WHERE i.issue_date BETWEEN ? AND ?";
$financialStats = $db->selectOne($financialSql, [$startDate, $endDate]) ?? [];

// Calculate collection rate
$collectionRate = ($financialStats['total_revenue'] ?? 0) > 0 
    ? (($financialStats['total_paid'] ?? 0) / ($financialStats['total_revenue'] ?? 1)) * 100 
    : 0;

// Student Summary
$studentSql = "SELECT 
    COUNT(*) as total_students,
    COUNT(CASE WHEN enrollment_date BETWEEN ? AND ? THEN 1 END) as new_students,
    COUNT(CASE WHEN test_ready = 1 THEN 1 END) as test_ready,
    COALESCE(AVG(total_lessons_completed), 0) as avg_lessons
    FROM students";
$studentStats = $db->selectOne($studentSql, [$startDate, $endDate]) ?? [];

// Lesson Summary
$lessonSql = "SELECT 
    COUNT(*) as total_lessons,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_lessons,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_lessons,
    COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled_lessons,
    COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_lessons
    FROM lessons
    WHERE lesson_date BETWEEN ? AND ?";
$lessonStats = $db->selectOne($lessonSql, [$startDate, $endDate]) ?? [];

// Calculate completion rate
$completionRate = ($lessonStats['total_lessons'] ?? 0) > 0 
    ? (($lessonStats['completed_lessons'] ?? 0) / ($lessonStats['total_lessons'] ?? 1)) * 100 
    : 0;

// Top Courses by Enrollment
$topCoursesSql = "SELECT 
    c.course_name,
    c.course_code,
    c.price,
    COUNT(i.invoice_id) as enrollments,
    COALESCE(SUM(i.total_amount), 0) as revenue
    FROM courses c
    LEFT JOIN invoices i ON c.course_id = i.course_id AND i.issue_date BETWEEN ? AND ?
    GROUP BY c.course_id
    ORDER BY enrollments DESC
    LIMIT 5";
$topCourses = $db->select($topCoursesSql, [$startDate, $endDate]) ?? [];

// Instructor Performance
$instructorSql = "SELECT 
    i.instructor_id,
    CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
    COUNT(l.lesson_id) as total_lessons,
    COUNT(CASE WHEN l.status = 'completed' THEN 1 END) as completed_lessons,
    COUNT(CASE WHEN l.status = 'cancelled' THEN 1 END) as cancelled_lessons,
    COALESCE(AVG(l.student_performance_rating), 0) as avg_rating,
    i.hourly_rate
    FROM instructors i
    INNER JOIN users u ON i.user_id = u.user_id
    LEFT JOIN lessons l ON i.instructor_id = l.instructor_id AND l.lesson_date BETWEEN ? AND ?
    WHERE u.is_active = 1
    GROUP BY i.instructor_id
    ORDER BY completed_lessons DESC
    LIMIT 10";
$instructorPerformance = $db->select($instructorSql, [$startDate, $endDate]) ?? [];

// Branch Performance
$branchSql = "SELECT 
    b.branch_id,
    b.branch_name,
    b.suburb,
    COUNT(DISTINCT s.student_id) as student_count,
    COUNT(DISTINCT i.instructor_id) as instructor_count,
    COUNT(DISTINCT l.lesson_id) as lesson_count,
    COALESCE(SUM(inv.total_amount), 0) as revenue
    FROM branches b
    LEFT JOIN students s ON b.branch_id = s.branch_id
    LEFT JOIN instructors i ON b.branch_id = i.branch_id
    LEFT JOIN lessons l ON s.student_id = l.student_id AND l.lesson_date BETWEEN ? AND ?
    LEFT JOIN invoices inv ON s.student_id = inv.student_id AND inv.issue_date BETWEEN ? AND ?
    WHERE b.is_active = 1
    GROUP BY b.branch_id
    ORDER BY revenue DESC";
$branchPerformance = $db->select($branchSql, [$startDate, $endDate, $startDate, $endDate]) ?? [];

// Monthly Revenue Trend (Last 12 months)
$monthlyRevenueSql = "SELECT 
    DATE_FORMAT(issue_date, '%Y-%m') as month,
    DATE_FORMAT(issue_date, '%b %Y') as month_label,
    COALESCE(SUM(total_amount), 0) as revenue,
    COALESCE(SUM(amount_paid), 0) as collected,
    COUNT(*) as invoice_count
    FROM invoices
    WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
    ORDER BY month ASC";
$monthlyRevenue = $db->select($monthlyRevenueSql) ?? [];

// Daily lessons for current month (for chart)
$dailyLessonsSql = "SELECT 
    DATE(lesson_date) as date,
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
    FROM lessons
    WHERE lesson_date BETWEEN ? AND ?
    GROUP BY DATE(lesson_date)
    ORDER BY date ASC";
$dailyLessons = $db->select($dailyLessonsSql, [$startDate, $endDate]) ?? [];

// Invoice status distribution
$invoiceDistSql = "SELECT 
    status,
    COUNT(*) as count,
    COALESCE(SUM(total_amount), 0) as amount
    FROM invoices
    WHERE issue_date BETWEEN ? AND ?
    GROUP BY status";
$invoiceDistribution = $db->select($invoiceDistSql, [$startDate, $endDate]) ?? [];

// Prepare chart data
$revenueLabels = array_column($monthlyRevenue, 'month_label');
$revenueData = array_column($monthlyRevenue, 'revenue');
$collectedData = array_column($monthlyRevenue, 'collected');

$dailyLabels = array_column($dailyLessons, 'date');
$dailyTotals = array_column($dailyLessons, 'total');
$dailyCompleted = array_column($dailyLessons, 'completed');

$pageTitle = 'Reports & Analytics';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Enhanced Top Bar -->
        <div class="reports-top-bar">
            <div class="reports-header">
                <div class="header-icon-wrapper">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="header-content">
                    <h2>Reports & Analytics</h2>
                    <p class="subtitle">
                        <i class="fas fa-calendar-alt"></i> 
                        <?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?>
                    </p>
                </div>
            </div>
            <div class="quick-actions-bar">
                <button class="btn-quick-action" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
                <button class="btn-quick-action" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i>
                    <span>Export PDF</span>
                </button>
                <button class="btn-quick-action" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i>
                    <span>Export Excel</span>
                </button>
            </div>
        </div>
        
        <div class="content-area reports-content">
            <!-- Date Range Filter with Presets -->
            <div class="filter-section">
                <div class="preset-buttons">
                    <button class="preset-btn <?php echo !isset($_GET['preset']) ? 'active' : ''; ?>" 
                            onclick="window.location.href='?preset=today'">Today</button>
                    <button class="preset-btn" onclick="window.location.href='?preset=week'">This Week</button>
                    <button class="preset-btn" onclick="window.location.href='?preset=month'">This Month</button>
                    <button class="preset-btn" onclick="window.location.href='?preset=quarter'">This Quarter</button>
                    <button class="preset-btn" onclick="window.location.href='?preset=year'">This Year</button>
                </div>
                
                <form method="GET" action="" class="custom-date-filter">
                    <div class="date-inputs">
                        <div class="input-group">
                            <label><i class="fas fa-calendar"></i> From</label>
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-calendar"></i> To</label>
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Key Performance Indicators -->
            <div class="kpi-section">
                <h3 class="section-title"><i class="fas fa-tachometer-alt"></i> Key Performance Indicators</h3>
                
                <div class="kpi-grid">
                    <div class="kpi-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="kpi-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="kpi-content">
                            <h4><?php echo formatCurrency($financialStats['total_revenue'] ?? 0); ?></h4>
                            <p>Total Revenue</p>
                            <div class="kpi-badge">
                                <i class="fas fa-chart-line"></i>
                                <?php echo number_format($collectionRate, 1); ?>% collected
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpi-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <div class="kpi-icon">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="kpi-content">
                            <h4><?php echo $lessonStats['completed_lessons'] ?? 0; ?></h4>
                            <p>Lessons Completed</p>
                            <div class="kpi-badge">
                                <i class="fas fa-percentage"></i>
                                <?php echo number_format($completionRate, 1); ?>% completion rate
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="kpi-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="kpi-content">
                            <h4><?php echo $studentStats['new_students'] ?? 0; ?></h4>
                            <p>New Students</p>
                            <div class="kpi-badge">
                                <i class="fas fa-users"></i>
                                <?php echo $studentStats['total_students'] ?? 0; ?> total
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="kpi-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="kpi-content">
                            <h4><?php echo $financialStats['total_invoices'] ?? 0; ?></h4>
                            <p>Total Invoices</p>
                            <div class="kpi-badge">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $financialStats['unpaid_invoices'] ?? 0; ?> unpaid
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="charts-grid">
                <!-- Revenue Trend Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-area"></i> Revenue Trend (Last 12 Months)</h3>
                        <div class="chart-legend">
                            <span class="legend-item"><span class="legend-color" style="background: #667eea;"></span> Invoiced</span>
                            <span class="legend-item"><span class="legend-color" style="background: #11998e;"></span> Collected</span>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="revenueChart" height="80"></canvas>
                    </div>
                </div>
                
                <!-- Lesson Activity Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-bar"></i> Daily Lesson Activity</h3>
                        <div class="chart-legend">
                            <span class="legend-item"><span class="legend-color" style="background: #4facfe;"></span> Total</span>
                            <span class="legend-item"><span class="legend-color" style="background: #11998e;"></span> Completed</span>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="lessonsChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Second Row Charts -->
            <div class="charts-grid">
                <!-- Invoice Status Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Invoice Status Distribution</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="invoiceStatusChart" height="100"></canvas>
                    </div>
                </div>
                
                <!-- Branch Performance Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-bar"></i> Branch Revenue Comparison</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="branchChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Tables Section -->
            <div class="tables-grid">
                <!-- Top Courses -->
                <div class="report-card">
                    <div class="report-card-header">
                        <h3><i class="fas fa-trophy"></i> Top Performing Courses</h3>
                    </div>
                    <div class="report-card-body">
                        <?php if (empty($topCourses)): ?>
                            <div class="empty-state">
                                <i class="fas fa-chart-line"></i>
                                <p>No course data available for this period</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Course</th>
                                            <th>Enrollments</th>
                                            <th>Revenue</th>
                                            <th>Avg. Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rank = 1; foreach ($topCourses as $course): ?>
                                        <tr>
                                            <td>
                                                <span class="rank-badge rank-<?php echo $rank; ?>">
                                                    <?php echo $rank; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($course['course_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($course['course_code']); ?></small>
                                            </td>
                                            <td><?php echo $course['enrollments']; ?></td>
                                            <td><strong><?php echo formatCurrency($course['revenue']); ?></strong></td>
                                            <td><?php echo $course['enrollments'] > 0 ? formatCurrency($course['revenue'] / $course['enrollments']) : '$0.00'; ?></td>
                                        </tr>
                                        <?php $rank++; endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Instructor Performance -->
                <div class="report-card">
                    <div class="report-card-header">
                        <h3><i class="fas fa-chalkboard-teacher"></i> Instructor Performance</h3>
                    </div>
                    <div class="report-card-body">
                        <?php if (empty($instructorPerformance)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-tie"></i>
                                <p>No instructor data available for this period</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Instructor</th>
                                            <th>Total</th>
                                            <th>Completed</th>
                                            <th>Cancelled</th>
                                            <th>Rating</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($instructorPerformance as $instructor): 
                                            $rate = $instructor['total_lessons'] > 0 ? ($instructor['completed_lessons'] / $instructor['total_lessons']) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($instructor['instructor_name']); ?></strong></td>
                                            <td><?php echo $instructor['total_lessons']; ?></td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php echo $instructor['completed_lessons']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    <?php echo $instructor['cancelled_lessons']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($instructor['avg_rating'] > 0): ?>
                                                    <div class="rating-display">
                                                        <?php echo number_format($instructor['avg_rating'], 1); ?>
                                                        <i class="fas fa-star"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $rate; ?>%; background: <?php echo $rate >= 80 ? '#11998e' : ($rate >= 60 ? '#f5af19' : '#f5576c'); ?>;"></div>
                                                </div>
                                                <small class="text-muted"><?php echo number_format($rate, 1); ?>%</small>
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
            
            <!-- Branch Performance Table -->
            <div class="report-card full-width">
                <div class="report-card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Branch Performance Overview</h3>
                </div>
                <div class="report-card-body">
                    <?php if (empty($branchPerformance)): ?>
                        <div class="empty-state">
                            <i class="fas fa-building"></i>
                            <p>No branch data available</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Branch</th>
                                        <th>Location</th>
                                        <th>Students</th>
                                        <th>Instructors</th>
                                        <th>Lessons</th>
                                        <th>Revenue</th>
                                        <th>Avg. Revenue/Student</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($branchPerformance as $branch): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($branch['branch_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($branch['suburb']); ?></td>
                                        <td>
                                            <span class="stat-bubble">
                                                <i class="fas fa-user-graduate"></i>
                                                <?php echo $branch['student_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="stat-bubble">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                                <?php echo $branch['instructor_count']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $branch['lesson_count']; ?></td>
                                        <td><strong><?php echo formatCurrency($branch['revenue']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $avgRev = $branch['student_count'] > 0 ? $branch['revenue'] / $branch['student_count'] : 0;
                                            echo formatCurrency($avgRev);
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td colspan="2"><strong>TOTAL</strong></td>
                                        <td><strong><?php echo array_sum(array_column($branchPerformance, 'student_count')); ?></strong></td>
                                        <td><strong><?php echo array_sum(array_column($branchPerformance, 'instructor_count')); ?></strong></td>
                                        <td><strong><?php echo array_sum(array_column($branchPerformance, 'lesson_count')); ?></strong></td>
                                        <td><strong><?php echo formatCurrency(array_sum(array_column($branchPerformance, 'revenue'))); ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Summary Statistics Cards -->
            <div class="summary-section">
                <div class="summary-card">
                    <h4><i class="fas fa-coins"></i> Financial Summary</h4>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="label">Total Invoiced:</span>
                            <span class="value"><?php echo formatCurrency($financialStats['total_revenue'] ?? 0); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Amount Collected:</span>
                            <span class="value success"><?php echo formatCurrency($financialStats['total_paid'] ?? 0); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Outstanding:</span>
                            <span class="value danger"><?php echo formatCurrency($financialStats['total_outstanding'] ?? 0); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Collection Rate:</span>
                            <span class="value"><?php echo number_format($collectionRate, 1); ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <h4><i class="fas fa-graduation-cap"></i> Student Summary</h4>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="label">Total Students:</span>
                            <span class="value"><?php echo $studentStats['total_students'] ?? 0; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">New Enrollments:</span>
                            <span class="value success"><?php echo $studentStats['new_students'] ?? 0; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Test Ready:</span>
                            <span class="value"><?php echo $studentStats['test_ready'] ?? 0; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Avg. Lessons:</span>
                            <span class="value"><?php echo number_format($studentStats['avg_lessons'] ?? 0, 1); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <h4><i class="fas fa-calendar-check"></i> Lesson Summary</h4>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="label">Total Lessons:</span>
                            <span class="value"><?php echo $lessonStats['total_lessons'] ?? 0; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Completed:</span>
                            <span class="value success"><?php echo $lessonStats['completed_lessons'] ?? 0; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Cancelled:</span>
                            <span class="value danger"><?php echo $lessonStats['cancelled_lessons'] ?? 0; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Completion Rate:</span>
                            <span class="value"><?php echo number_format($completionRate, 1); ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ==================== ENHANCED REPORTS STYLING ==================== */

.reports-top-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.reports-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.header-icon-wrapper {
    width: 70px;
    height: 70px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.header-content h2 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}

.subtitle {
    margin-top: 0.5rem;
    opacity: 0.95;
}

.quick-actions-bar {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-quick-action {
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.btn-quick-action:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

/* Filter Section */
.filter-section {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.preset-buttons {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.preset-btn {
    padding: 0.75rem 1.5rem;
    border: 2px solid #e9ecef;
    background: white;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.preset-btn:hover,
.preset-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}

.custom-date-filter {
    border-top: 2px solid #f0f0f0;
    padding-top: 1.5rem;
}

.date-inputs {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
}

.input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.input-group label {
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
}

.input-group input {
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.95rem;
}

/* KPI Section */
.kpi-section {
    margin-bottom: 2rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    color: #2c3e50;
    margin-bottom: 1.5rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.kpi-card {
    padding: 2rem;
    border-radius: 16px;
    color: white;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    display: flex;
    gap: 1.5rem;
    align-items: center;
    transition: transform 0.3s;
}

.kpi-card:hover {
    transform: translateY(-5px);
}

.kpi-icon {
    width: 70px;
    height: 70px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.kpi-content h4 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.kpi-content p {
    margin: 0.5rem 0;
    opacity: 0.9;
    font-size: 1rem;
}

.kpi-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Charts */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.chart-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.chart-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.25rem 1.5rem;
    border-bottom: 3px solid #667eea;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-header h3 {
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chart-legend {
    display: flex;
    gap: 1rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.chart-body {
    padding: 1.5rem;
}

/* Tables */
.tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.report-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.report-card.full-width {
    grid-column: 1 / -1;
}

.report-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.25rem 1.5rem;
}

.report-card-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.report-card-body {
    padding: 1.5rem;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table thead th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid #e9ecef;
    background: #f8f9fa;
}

.modern-table tbody tr {
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
}

.modern-table tbody tr:hover {
    background: rgba(102, 126, 234, 0.03);
}

.modern-table td {
    padding: 1rem;
}

.modern-table tfoot .total-row {
    background: #f8f9fa;
    font-weight: 700;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-weight: 700;
    color: white;
}

.rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
.rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%); }
.rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); }
.rank-4, .rank-5 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

.rating-display {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-weight: 600;
    color: #ffc107;
}

.progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.progress-fill {
    height: 100%;
    transition: width 0.3s;
}

.stat-bubble {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    border-radius: 20px;
    font-weight: 500;
}

/* Summary Cards */
.summary-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.summary-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.summary-card h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.summary-grid {
    display: grid;
    gap: 1rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.summary-item .label {
    color: #666;
    font-weight: 500;
}

.summary-item .value {
    font-weight: 700;
    color: #2c3e50;
    font-size: 1.1rem;
}

.summary-item .value.success {
    color: #11998e;
}

.summary-item .value.danger {
    color: #f5576c;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #999;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.text-muted {
    color: #999;
}

/* Print Styles */
@media print {
    .reports-top-bar,
    .filter-section,
    .quick-actions-bar,
    .sidebar {
        display: none !important;
    }
    
    .report-card,
    .chart-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .charts-grid,
    .tables-grid {
        grid-template-columns: 1fr;
    }
    
    .kpi-grid {
        grid-template-columns: 1fr;
    }
    
    .date-inputs {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Chart Configuration
const chartColors = {
    primary: '#667eea',
    secondary: '#764ba2',
    success: '#11998e',
    danger: '#f5576c',
    warning: '#f5af19',
    info: '#4facfe'
};

// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($revenueLabels); ?>,
            datasets: [{
                label: 'Invoiced',
                data: <?php echo json_encode($revenueData); ?>,
                borderColor: chartColors.primary,
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Collected',
                data: <?php echo json_encode($collectedData); ?>,
                borderColor: chartColors.success,
                backgroundColor: 'rgba(17, 153, 142, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Daily Lessons Chart
const lessonsCtx = document.getElementById('lessonsChart');
if (lessonsCtx) {
    new Chart(lessonsCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dailyLabels); ?>,
            datasets: [{
                label: 'Total',
                data: <?php echo json_encode($dailyTotals); ?>,
                backgroundColor: 'rgba(79, 172, 254, 0.8)',
            }, {
                label: 'Completed',
                data: <?php echo json_encode($dailyCompleted); ?>,
                backgroundColor: 'rgba(17, 153, 142, 0.8)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Invoice Status Pie Chart
const invoiceStatusCtx = document.getElementById('invoiceStatusChart');
if (invoiceStatusCtx) {
    const statusData = <?php echo json_encode($invoiceDistribution); ?>;
    const statusLabels = statusData.map(item => item.status.replace('_', ' ').toUpperCase());
    const statusCounts = statusData.map(item => parseInt(item.count));
    
    new Chart(invoiceStatusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: [
                    chartColors.success,
                    chartColors.warning,
                    chartColors.danger,
                    chartColors.info,
                    chartColors.primary
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Branch Revenue Chart
const branchCtx = document.getElementById('branchChart');
if (branchCtx) {
    const branchData = <?php echo json_encode($branchPerformance); ?>;
    const branchLabels = branchData.map(item => item.branch_name);
    const branchRevenues = branchData.map(item => parseFloat(item.revenue));
    
    new Chart(branchCtx, {
        type: 'bar',
        data: {
            labels: branchLabels,
            datasets: [{
                label: 'Revenue',
                data: branchRevenues,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Export Functions
function exportToPDF() {
    alert('PDF export functionality would be implemented here using libraries like jsPDF or server-side PDF generation.');
}

function exportToExcel() {
    alert('Excel export functionality would be implemented here using libraries like SheetJS or server-side Excel generation.');
}

console.log('Reports dashboard loaded successfully');
</script>

<?php include '../views/layouts/footer.php'; ?>