<?php
/**
 * View Communication Message
 * 
 * File path: communications/view.php
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

requireLogin();

$db = Database::getInstance();
$messageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$messageId) {
    setFlashMessage('error', 'Invalid message ID');
    redirect('/communications/index.php');
}

// Get message details
$sql = "SELECT c.*, 
        CONCAT(u.first_name, ' ', u.last_name) AS sent_by_name,
        u.email as sent_by_email
        FROM communications c
        INNER JOIN users u ON c.sent_by = u.user_id
        WHERE c.communication_id = ?";

$message = $db->selectOne($sql, [$messageId]);

if (!$message) {
    setFlashMessage('error', 'Message not found');
    redirect('/communications/index.php');
}

// Mark notification as read if viewing from notification
if (isset($_GET['notif'])) {
    $notifId = (int)$_GET['notif'];
    $db->query("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?", 
               [$notifId, $_SESSION['user_id']]);
}

$pageTitle = 'View Message';
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h1><i class="fas fa-envelope-open"></i> View Message</h1>
            <div class="user-menu">
                <a href="<?php echo APP_URL; ?>/communications/index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Communications
                </a>
            </div>
        </div>
        
        <div class="content-area">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h3 style="margin: 0; color: white;">
                        <i class="fas fa-envelope"></i> 
                        <?php echo htmlspecialchars($message['subject']); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="message-meta" style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <strong>From:</strong><br>
                                <?php echo htmlspecialchars($message['sent_by_name']); ?>
                            </div>
                            <div>
                                <strong>Sent To:</strong><br>
                                <span class="badge badge-info">
                                    <?php echo ucfirst($message['recipient_type']); ?> 
                                    (<?php echo $message['recipient_count']; ?> recipients)
                                </span>
                            </div>
                            <div>
                                <strong>Date:</strong><br>
                                <?php echo date('d M Y, H:i', strtotime($message['created_at'])); ?>
                            </div>
                            <div>
                                <strong>Method:</strong><br>
                                <span class="badge badge-primary">
                                    <?php echo strtoupper($message['method']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="message-content" style="padding: 1rem; line-height: 1.6; min-height: 200px;">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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

.card-body {
    padding: 1.5rem;
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

.message-content {
    font-size: 1rem;
    color: #2c3e50;
}
</style>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>