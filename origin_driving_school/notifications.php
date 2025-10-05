<?php
/**
 * Notifications Page
 * 
 * File path: notifications.php
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

requireLogin();

$db = Database::getInstance();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Mark as read action
if (isset($_POST['mark_read'])) {
    $notifId = (int)$_POST['notif_id'];
    $db->query("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?", 
               [$notifId, $_SESSION['user_id']]);
    setFlashMessage('success', 'Notification marked as read');
    redirect('/notifications.php');
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", 
               [$_SESSION['user_id']]);
    setFlashMessage('success', 'All notifications marked as read');
    redirect('/notifications.php');
}

// Get notifications
$notifications = $db->select(
    "SELECT * FROM notifications 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT ? OFFSET ?", 
    [$_SESSION['user_id'], $perPage, $offset]
);

// Get total count
$totalResult = $db->selectOne(
    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?", 
    [$_SESSION['user_id']]
);
$totalNotifications = $totalResult['count'] ?? 0;
$totalPages = ceil($totalNotifications / $perPage);

// Get unread count
$unreadResult = $db->selectOne(
    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", 
    [$_SESSION['user_id']]
);
$unreadCount = $unreadResult['count'] ?? 0;

$pageTitle = 'Notifications';
include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <div class="user-menu">
                <?php if ($unreadCount > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-info btn-sm">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </form>
                <?php endif; ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
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
            
            <!-- Stats -->
            <div class="stats-bar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-around; text-align: center;">
                    <div>
                        <h2 style="margin: 0; color: white;"><?php echo $totalNotifications; ?></h2>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Total Notifications</p>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: white;"><?php echo $unreadCount; ?></h2>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Unread</p>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: white;"><?php echo $totalNotifications - $unreadCount; ?></h2>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Read</p>
                    </div>
                </div>
            </div>
            
            <!-- Notifications List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> All Notifications</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="table-empty" style="padding: 3rem; text-align: center; color: #999;">
                            <i class="fas fa-bell-slash" style="font-size: 4rem; display: block; margin-bottom: 1rem; color: #ddd;"></i>
                            <p>No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" 
                                     style="padding: 1.5rem; border-bottom: 1px solid #f0f0f0; display: flex; align-items: start; gap: 1rem; <?php echo $notif['is_read'] ? '' : 'background: rgba(102, 126, 234, 0.05); border-left: 4px solid #667eea;'; ?>">
                                    
                                    <div class="notif-icon" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(102, 126, 234, 0.1); color: #667eea; flex-shrink: 0; font-size: 1.3rem;">
                                        <i class="fas fa-<?php echo $notif['notification_type'] === 'message' ? 'envelope' : 'bell'; ?>"></i>
                                    </div>
                                    
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #2c3e50;">
                                            <?php echo htmlspecialchars($notif['title']); ?>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="badge" style="background: #667eea; color: white; font-size: 0.75rem; padding: 0.25rem 0.6rem; margin-left: 0.5rem;">NEW</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p style="margin: 0.5rem 0; color: #666; font-size: 0.95rem; line-height: 1.5;">
                                            <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                                        </p>
                                        <small style="color: #999;">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('d M Y, H:i', strtotime($notif['created_at'])); ?>
                                        </small>
                                    </div>
                                    
                                    <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                        <?php if ($notif['link']): ?>
                                            <a href="<?php echo htmlspecialchars($notif['link']) . '&notif=' . $notif['notification_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notif['is_read']): ?>
                                            <form method="POST" style="margin: 0;">
                                                <input type="hidden" name="notif_id" value="<?php echo $notif['notification_id']; ?>">
                                                <button type="submit" name="mark_read" class="btn btn-sm btn-secondary" style="width: 100%;">
                                                    <i class="fas fa-check"></i> Mark Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem;">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <span style="color: #666; font-weight: 500;">
                                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                                </span>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">
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
.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    background: #f8f9fa;
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
    padding: 0;
}

.notification-item {
    transition: all 0.2s;
}

.notification-item:hover {
    background: rgba(102, 126, 234, 0.03) !important;
}
</style>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>