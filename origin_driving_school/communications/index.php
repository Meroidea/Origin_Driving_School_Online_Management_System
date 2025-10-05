<?php
/**
 * Communications Management - Send & View Messages
 * 
 * File path: communications/index.php
 * 
 * @author Origin Driving School Development Team
 * @version 2.0 - FIXED
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

requireLogin();
requireRole(['admin', 'staff']);

require_once APP_PATH . '/models/Student.php';

$studentModel = new Student();
$pageTitle = 'Communications';

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipientType = sanitize($_POST['recipient_type']);
    $messageType = sanitize($_POST['message_type']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    if (empty($subject) || empty($message)) {
        setFlashMessage('error', 'Subject and message are required');
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Map form values to database ENUM values
        $recipientTypeMap = [
            'all' => 'all_users',
            'students' => 'all_students',
            'instructors' => 'all_instructors',
            'staff' => 'all_staff'
        ];
        
        $dbRecipientType = $recipientTypeMap[$recipientType] ?? 'all_users';
        
        // Get recipients based on type
        $recipients = [];
        switch ($recipientType) {
            case 'all':
                $recipients = $db->select("SELECT user_id, email, first_name, last_name FROM users WHERE is_active = 1");
                break;
            case 'students':
                $recipients = $db->select("SELECT u.user_id, u.email, u.first_name, u.last_name FROM users u INNER JOIN students s ON u.user_id = s.user_id WHERE u.is_active = 1");
                break;
            case 'instructors':
                $recipients = $db->select("SELECT u.user_id, u.email, u.first_name, u.last_name FROM users u INNER JOIN instructors i ON u.user_id = i.user_id WHERE u.is_active = 1");
                break;
            case 'staff':
                $recipients = $db->select("SELECT u.user_id, u.email, u.first_name, u.last_name FROM users u INNER JOIN staff st ON u.user_id = st.user_id WHERE u.is_active = 1");
                break;
        }
        
        // Insert into communications table - USE MAPPED VALUE
        $sql = "INSERT INTO communications (sent_by, recipient_type, method, subject, message, recipient_count, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'sent', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'],
            $dbRecipientType,  // Use the mapped value here
            $messageType,
            $subject,
            $message,
            count($recipients)
        ]);
        
        $communicationId = $conn->lastInsertId();
        
        // Create individual notifications for each recipient
        if (!empty($recipients)) {
            $notifSql = "INSERT INTO notifications (user_id, notification_type, title, message, link, created_at) 
                         VALUES (?, 'message', ?, ?, ?, NOW())";
            $notifStmt = $conn->prepare($notifSql);
            
            foreach ($recipients as $recipient) {
                // Don't send notification to the sender
                if ($recipient['user_id'] != $_SESSION['user_id']) {
                    $notifStmt->execute([
                        $recipient['user_id'],
                        $subject,
                        substr($message, 0, 200) . (strlen($message) > 200 ? '...' : ''),
                        '/communications/view.php?id=' . $communicationId
                    ]);
                }
            }
        }
        
        setFlashMessage('success', 'Message sent successfully to ' . count($recipients) . ' recipients');
        redirect('/communications/index.php');
    }
}

// Get message history
$searchTerm = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$searchQuery = '';
if (!empty($searchTerm)) {
    $searchQuery = "AND (c.subject LIKE '%$searchTerm%' OR c.message LIKE '%$searchTerm%')";
}

$sql = "SELECT c.*, 
        CONCAT(u.first_name, ' ', u.last_name) AS sent_by_name
        FROM communications c
        INNER JOIN users u ON c.sent_by = u.user_id
        WHERE 1=1 $searchQuery";

if (!empty($typeFilter)) {
    $sql .= " AND c.method = '" . $studentModel->db->escape($typeFilter) . "'";
}

$sql .= " ORDER BY c.created_at DESC LIMIT $perPage OFFSET $offset";

$messages = $studentModel->customQuery($sql);

// Get total count
$countSql = "SELECT COUNT(*) as total FROM communications c WHERE 1=1 $searchQuery";
if (!empty($typeFilter)) {
    $countSql .= " AND c.method = '" . $studentModel->db->escape($typeFilter) . "'";
}

$totalResult = $studentModel->customQueryOne($countSql);
$totalMessages = $totalResult['total'] ?? 0;
$totalPages = ceil($totalMessages / $perPage);

// Get statistics - FIXED with proper null handling
$statsToday = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(recipient_count), 0) as recipients FROM communications WHERE DATE(created_at) = CURDATE()");
$todayCount = isset($statsToday['count']) ? (int)$statsToday['count'] : 0;
$todayRecipients = isset($statsToday['recipients']) ? (int)$statsToday['recipients'] : 0;

$statsThisMonth = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(recipient_count), 0) as recipients FROM communications WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$monthCount = isset($statsThisMonth['count']) ? (int)$statsThisMonth['count'] : 0;
$monthRecipients = isset($statsThisMonth['recipients']) ? (int)$statsThisMonth['recipients'] : 0;

$statsTotal = $studentModel->customQueryOne("SELECT COUNT(*) as count, COALESCE(SUM(recipient_count), 0) as recipients FROM communications");
$totalCount = isset($statsTotal['count']) ? (int)$statsTotal['count'] : 0;
$totalRecipients = isset($statsTotal['recipients']) ? (int)$statsTotal['recipients'] : 0;

$totalUsersResult = $studentModel->customQueryOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$totalUsers = isset($totalUsersResult['count']) ? (int)$totalUsersResult['count'] : 0;

include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h1><i class="fas fa-envelope"></i> Communications</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
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
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $todayCount; ?></h3>
                        <p>Messages Sent Today</p>
                        <small>(<?php echo $todayRecipients; ?> recipients)</small>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $monthCount; ?></h3>
                        <p>This Month</p>
                        <small>(<?php echo $monthRecipients; ?> recipients)</small>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalCount; ?></h3>
                        <p>Total Messages</p>
                        <small>(<?php echo $totalRecipients; ?> recipients)</small>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $totalUsers; ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
            </div>
            
            <!-- Send New Message Section -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header" style="background-color: #4e7e95; color: white;">
                    <h3 style="margin: 0;"><i class="fas fa-plus-circle"></i> Send New Message</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>Recipients <span style="color: red;">*</span></label>
                                <select name="recipient_type" class="form-control" required>
                                    <option value="">Select Recipients</option>
                                    <option value="all">All Users</option>
                                    <option value="students">All Students</option>
                                    <option value="instructors">All Instructors</option>
                                    <option value="staff">All Staff</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Message Type <span style="color: red;">*</span></label>
                                <select name="message_type" class="form-control" required>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS (Coming Soon)</option>
                                    <option value="both">Both (Coming Soon)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Subject <span style="color: red;">*</span></label>
                            <input type="text" name="subject" class="form-control" 
                                   placeholder="Enter message subject" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Message <span style="color: red;">*</span></label>
                            <textarea name="message" class="form-control" rows="6" 
                                      placeholder="Enter your message here..." required></textarea>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <small style="color: #666;">
                                <i class="fas fa-info-circle"></i> 
                                Messages will be sent to all active users in the selected group
                            </small>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Message History -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Message History</h3>
                </div>
                <div class="card-body">
                    
                    <!-- Search and Filter -->
                    <form method="GET" action="" style="margin-bottom: 1.5rem;">
                        <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search subject or message..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0;">
                                <select name="type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="email" <?php echo $typeFilter === 'email' ? 'selected' : ''; ?>>Email</option>
                                    <option value="sms" <?php echo $typeFilter === 'sms' ? 'selected' : ''; ?>>SMS</option>
                                    <option value="both" <?php echo $typeFilter === 'both' ? 'selected' : ''; ?>>Both</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (empty($messages)): ?>
                        <div class="table-empty">
                            <i class="fas fa-envelope"></i>
                            <p>No messages found</p>
                            <?php if (!empty($searchTerm) || !empty($typeFilter)): ?>
                                <p><a href="index.php">Clear filters to view all messages</a></p>
                            <?php else: ?>
                                <p>Send your first message using the form above</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date & Time</th>
                                        <th>Subject</th>
                                        <th>Recipients</th>
                                        <th>Type</th>
                                        <th>Sent By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $msg): ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($msg['communication_id'] ?? ''); ?></strong></td>
                                            <td><?php echo isset($msg['created_at']) ? date('d/m/Y H:i', strtotime($msg['created_at'])) : 'N/A'; ?></td>
                                            <td><strong><?php echo htmlspecialchars($msg['subject'] ?? ''); ?></strong></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo ucfirst($msg['recipient_type'] ?? 'N/A'); ?> 
                                                    (<?php echo $msg['recipient_count'] ?? 0; ?>)
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo strtoupper($msg['method'] ?? 'EMAIL'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($msg['sent_by_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $msg['communication_id']; ?>" 
                                                   class="btn btn-info btn-sm" title="View Message">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&type=<?php echo $typeFilter; ?>" 
                                       class="btn btn-secondary btn-sm">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <span class="pagination-info">
                                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                                </span>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&type=<?php echo $typeFilter; ?>" 
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

<style>
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    display: flex;
    gap: 1rem;
    align-items: center;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.dashboard-card-icon {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.dashboard-card-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.dashboard-card-content p {
    margin: 0.25rem 0 0 0;
    color: #666;
    font-size: 0.9rem;
}

.dashboard-card-content small {
    color: #999;
    font-size: 0.8rem;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
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

.table-empty {
    text-align: center;
    padding: 3rem;
    color: #999;
}

.table-empty i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
    display: block;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
}

.pagination-info {
    color: #666;
    font-weight: 500;
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
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
    border-bottom: 2px solid #e0e0e0;
    background: #f8f9fa;
}

.data-table tbody tr {
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
}

.data-table tbody tr:hover {
    background: rgba(78, 126, 149, 0.03);
}

.data-table tbody td {
    padding: 1rem;
    color: #34495e;
}
</style>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>