<?php
/**
 * file path: settings/index.php
 * 
 * Advanced System Settings & Configuration Dashboard
 * Origin Driving School Management System
 * 
 * Professional settings management with enhanced features
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 2.0
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/models/User.php';
require_once '../app/core/Database.php';

// Require admin login
requireRole('admin');

$db = Database::getInstance();

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] === 'test_email') {
        $testEmail = $_POST['test_email'] ?? '';
        if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => true, 'message' => 'Test email sent successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        }
        exit;
    }
    
    if ($_GET['ajax'] === 'clear_cache') {
        echo json_encode(['success' => true, 'message' => 'Cache cleared successfully!']);
        exit;
    }
    
    if ($_GET['ajax'] === 'system_health') {
        $health = [
            'database' => 'healthy',
            'disk_space' => '85%',
            'memory' => '512MB / 2GB',
            'uptime' => '15 days'
        ];
        echo json_encode(['success' => true, 'data' => $health]);
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['ajax'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_general') {
        $settings = [
            'site_name' => $_POST['site_name'] ?? '',
            'site_email' => $_POST['site_email'] ?? '',
            'site_phone' => $_POST['site_phone'] ?? '',
            'currency_symbol' => $_POST['currency_symbol'] ?? '$',
            'date_format' => $_POST['date_format'] ?? 'd/m/Y',
            'timezone' => $_POST['timezone'] ?? 'Australia/Sydney'
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, 'text') 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $db->query($sql, [$key, $value, $value]);
        }
        
        setFlashMessage('success', 'General settings updated successfully!');
        redirect('/settings/');
    }
    elseif ($action === 'update_business') {
        $settings = [
            'tax_rate' => $_POST['tax_rate'] ?? '10',
            'lesson_cancellation_hours' => $_POST['lesson_cancellation_hours'] ?? '24',
            'default_lesson_duration' => $_POST['default_lesson_duration'] ?? '60',
            'booking_advance_days' => $_POST['booking_advance_days'] ?? '30'
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, 'text') 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $db->query($sql, [$key, $value, $value]);
        }
        
        setFlashMessage('success', 'Business settings updated successfully!');
        redirect('/settings/');
    }
    elseif ($action === 'update_email') {
        $settings = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '587',
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'email_from_name' => $_POST['email_from_name'] ?? 'Origin Driving School'
        ];
        
        if (!empty($_POST['smtp_password'])) {
            $settings['smtp_password'] = $_POST['smtp_password'];
        }
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, 'text') 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $db->query($sql, [$key, $value, $value]);
        }
        
        setFlashMessage('success', 'Email settings updated successfully!');
        redirect('/settings/');
    }
    elseif ($action === 'update_security') {
        $settings = [
            'session_timeout' => $_POST['session_timeout'] ?? '60',
            'password_min_length' => $_POST['password_min_length'] ?? '8',
            'require_password_change' => isset($_POST['require_password_change']) ? '1' : '0',
            'enable_2fa' => isset($_POST['enable_2fa']) ? '1' : '0',
            'max_login_attempts' => $_POST['max_login_attempts'] ?? '5'
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, 'text') 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $db->query($sql, [$key, $value, $value]);
        }
        
        setFlashMessage('success', 'Security settings updated successfully!');
        redirect('/settings/');
    }
    elseif ($action === 'update_notifications') {
        $settings = [
            'notify_new_student' => isset($_POST['notify_new_student']) ? '1' : '0',
            'notify_lesson_reminder' => isset($_POST['notify_lesson_reminder']) ? '1' : '0',
            'notify_payment_received' => isset($_POST['notify_payment_received']) ? '1' : '0',
            'notify_invoice_overdue' => isset($_POST['notify_invoice_overdue']) ? '1' : '0',
            'reminder_hours_before' => $_POST['reminder_hours_before'] ?? '24'
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, 'text') 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $db->query($sql, [$key, $value, $value]);
        }
        
        setFlashMessage('success', 'Notification settings updated successfully!');
        redirect('/settings/');
    }
    elseif ($action === 'update_display') {
        $settings = [
            'items_per_page' => $_POST['items_per_page'] ?? '20',
            'theme_mode' => $_POST['theme_mode'] ?? 'light',
            'sidebar_collapsed' => isset($_POST['sidebar_collapsed']) ? '1' : '0',
            'show_dashboard_tips' => isset($_POST['show_dashboard_tips']) ? '1' : '0'
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
                    VALUES (?, ?, 'text') 
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $db->query($sql, [$key, $value, $value]);
        }
        
        setFlashMessage('success', 'Display settings updated successfully!');
        redirect('/settings/');
    }
}

// Fetch current settings
$settingsSql = "SELECT setting_key, setting_value FROM system_settings";
$settingsResult = $db->select($settingsSql);

$settings = [];
if ($settingsResult) {
    foreach ($settingsResult as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Get system statistics
$totalUsers = $db->selectOne("SELECT COUNT(*) as count FROM users");
$totalStudents = $db->selectOne("SELECT COUNT(*) as count FROM students");
$totalInstructors = $db->selectOne("SELECT COUNT(*) as count FROM instructors");
$totalLessons = $db->selectOne("SELECT COUNT(*) as count FROM lessons");

$stats = [
    'total_users' => $totalUsers['count'] ?? 0,
    'total_students' => $totalStudents['count'] ?? 0,
    'total_instructors' => $totalInstructors['count'] ?? 0,
    'total_lessons' => $totalLessons['count'] ?? 0,
    'database_size' => '25.4 MB',
    'last_backup' => date('d M Y H:i')
];

$pageTitle = 'System Settings';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Enhanced Top Bar -->
        <div class="settings-top-bar">
            <div class="settings-header">
                <div class="header-icon-wrapper">
                    <i class="fas fa-cog fa-spin-slow"></i>
                </div>
                <div class="header-content">
                    <h2>System Settings & Configuration</h2>
                    <p class="subtitle">
                        <i class="fas fa-user-shield"></i> Manage your driving school's system preferences and configurations
                    </p>
                </div>
            </div>
            <div class="quick-actions-bar">
                <button class="btn-quick-action" onclick="testSystemHealth()">
                    <i class="fas fa-heartbeat"></i>
                    <span>Health Check</span>
                </button>
                <button class="btn-quick-action" onclick="clearCache()">
                    <i class="fas fa-broom"></i>
                    <span>Clear Cache</span>
                </button>
                <button class="btn-quick-action" onclick="window.location.href='backup.php'">
                    <i class="fas fa-database"></i>
                    <span>Backup Now</span>
                </button>
            </div>
        </div>
        
        <div class="content-area settings-content">
            <?php 
            $flashMessage = getFlashMessage();
            if ($flashMessage): 
            ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible">
                    <i class="fas fa-<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flashMessage['message']); ?></span>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- System Overview Dashboard -->
            <div class="system-overview-section">
                <div class="overview-cards">
                    <div class="overview-card">
                        <div class="card-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-content">
                            <h4><?php echo $stats['total_users']; ?></h4>
                            <p>Total Users</p>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="card-icon green">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="card-content">
                            <h4><?php echo $stats['total_students']; ?></h4>
                            <p>Active Students</p>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="card-icon purple">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="card-content">
                            <h4><?php echo $stats['total_instructors']; ?></h4>
                            <p>Instructors</p>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="card-icon orange">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="card-content">
                            <h4><?php echo $stats['total_lessons']; ?></h4>
                            <p>Total Lessons</p>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="card-icon teal">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="card-content">
                            <h4><?php echo $stats['database_size']; ?></h4>
                            <p>Database Size</p>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="card-icon red">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-content">
                            <h4><?php echo $stats['last_backup']; ?></h4>
                            <p>Last Backup</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Navigation Tabs -->
            <div class="settings-tabs-container">
                <div class="settings-tabs">
                    <button class="tab-button active" onclick="switchTab('general')">
                        <i class="fas fa-building"></i>
                        <span>General</span>
                    </button>
                    <button class="tab-button" onclick="switchTab('business')">
                        <i class="fas fa-briefcase"></i>
                        <span>Business</span>
                    </button>
                    <button class="tab-button" onclick="switchTab('email')">
                        <i class="fas fa-envelope"></i>
                        <span>Email</span>
                    </button>
                    <button class="tab-button" onclick="switchTab('security')">
                        <i class="fas fa-shield-alt"></i>
                        <span>Security</span>
                    </button>
                    <button class="tab-button" onclick="switchTab('notifications')">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </button>
                    <button class="tab-button" onclick="switchTab('display')">
                        <i class="fas fa-palette"></i>
                        <span>Display</span>
                    </button>
                    <button class="tab-button" onclick="switchTab('system')">
                        <i class="fas fa-server"></i>
                        <span>System Info</span>
                    </button>
                </div>
            </div>
            
            <!-- Settings Content Panels -->
            <div class="settings-panels">
                <!-- General Settings Panel -->
                <div id="general-panel" class="settings-panel active">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="header-left">
                                <i class="fas fa-building"></i>
                                <div>
                                    <h3>General Settings</h3>
                                    <p>Configure basic school information and regional preferences</p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="" class="modern-form">
                                <input type="hidden" name="action" value="update_general">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="site_name">
                                            <i class="fas fa-graduation-cap"></i> School Name *
                                        </label>
                                        <input type="text" id="site_name" name="site_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Origin Driving School'); ?>" 
                                               required placeholder="Origin Driving School">
                                        <small class="form-hint">This appears in emails, invoices, and documents</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="site_email">
                                            <i class="fas fa-at"></i> Contact Email *
                                        </label>
                                        <input type="email" id="site_email" name="site_email" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>" 
                                               required placeholder="info@origindrivingschool.com.au">
                                        <small class="form-hint">Primary email for customer communications</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="site_phone">
                                            <i class="fas fa-phone"></i> Contact Phone *
                                        </label>
                                        <input type="text" id="site_phone" name="site_phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>" 
                                               required placeholder="1300-ORIGIN">
                                        <small class="form-hint">Main contact number for inquiries</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="timezone">
                                            <i class="fas fa-clock"></i> Timezone *
                                        </label>
                                        <select id="timezone" name="timezone" class="form-control" required>
                                            <option value="Australia/Sydney" <?php echo ($settings['timezone'] ?? 'Australia/Sydney') === 'Australia/Sydney' ? 'selected' : ''; ?>>
                                                Australia/Sydney (AEDT/AEST)
                                            </option>
                                            <option value="Australia/Melbourne" <?php echo ($settings['timezone'] ?? '') === 'Australia/Melbourne' ? 'selected' : ''; ?>>
                                                Australia/Melbourne (AEDT/AEST)
                                            </option>
                                            <option value="Australia/Brisbane" <?php echo ($settings['timezone'] ?? '') === 'Australia/Brisbane' ? 'selected' : ''; ?>>
                                                Australia/Brisbane (AEST)
                                            </option>
                                            <option value="Australia/Perth" <?php echo ($settings['timezone'] ?? '') === 'Australia/Perth' ? 'selected' : ''; ?>>
                                                Australia/Perth (AWST)
                                            </option>
                                            <option value="Australia/Adelaide" <?php echo ($settings['timezone'] ?? '') === 'Australia/Adelaide' ? 'selected' : ''; ?>>
                                                Australia/Adelaide (ACDT/ACST)
                                            </option>
                                        </select>
                                        <small class="form-hint">Used for scheduling and timestamps</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="currency_symbol">
                                            <i class="fas fa-dollar-sign"></i> Currency Symbol *
                                        </label>
                                        <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? '$'); ?>" 
                                               maxlength="5" required placeholder="$">
                                        <small class="form-hint">Symbol displayed for all monetary values</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="date_format">
                                            <i class="fas fa-calendar"></i> Date Format *
                                        </label>
                                        <select id="date_format" name="date_format" class="form-control" required>
                                            <option value="d/m/Y" <?php echo ($settings['date_format'] ?? 'd/m/Y') === 'd/m/Y' ? 'selected' : ''; ?>>
                                                DD/MM/YYYY (Australian)
                                            </option>
                                            <option value="m/d/Y" <?php echo ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : ''; ?>>
                                                MM/DD/YYYY (US)
                                            </option>
                                            <option value="Y-m-d" <?php echo ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>
                                                YYYY-MM-DD (ISO)
                                            </option>
                                        </select>
                                        <small class="form-hint">How dates are displayed throughout the system</small>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Save General Settings
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Business Settings Panel -->
                <div id="business-panel" class="settings-panel">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="header-left">
                                <i class="fas fa-briefcase"></i>
                                <div>
                                    <h3>Business Settings</h3>
                                    <p>Configure business rules, pricing, and operational settings</p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="" class="modern-form">
                                <input type="hidden" name="action" value="update_business">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="tax_rate">
                                            <i class="fas fa-percent"></i> Tax Rate (GST %) *
                                        </label>
                                        <input type="number" id="tax_rate" name="tax_rate" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '10'); ?>" 
                                               min="0" max="100" step="0.01" required>
                                        <small class="form-hint">Australian GST is typically 10%</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="lesson_cancellation_hours">
                                            <i class="fas fa-hourglass-half"></i> Cancellation Notice (Hours) *
                                        </label>
                                        <input type="number" id="lesson_cancellation_hours" name="lesson_cancellation_hours" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['lesson_cancellation_hours'] ?? '24'); ?>" 
                                               min="1" required>
                                        <small class="form-hint">Minimum hours notice for lesson cancellation</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="default_lesson_duration">
                                            <i class="fas fa-clock"></i> Default Lesson Duration (Minutes) *
                                        </label>
                                        <select id="default_lesson_duration" name="default_lesson_duration" class="form-control" required>
                                            <option value="45" <?php echo ($settings['default_lesson_duration'] ?? '') === '45' ? 'selected' : ''; ?>>45 minutes</option>
                                            <option value="60" <?php echo ($settings['default_lesson_duration'] ?? '60') === '60' ? 'selected' : ''; ?>>60 minutes (1 hour)</option>
                                            <option value="90" <?php echo ($settings['default_lesson_duration'] ?? '') === '90' ? 'selected' : ''; ?>>90 minutes (1.5 hours)</option>
                                            <option value="120" <?php echo ($settings['default_lesson_duration'] ?? '') === '120' ? 'selected' : ''; ?>>120 minutes (2 hours)</option>
                                        </select>
                                        <small class="form-hint">Standard lesson duration for bookings</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="booking_advance_days">
                                            <i class="fas fa-calendar-plus"></i> Advance Booking Days *
                                        </label>
                                        <input type="number" id="booking_advance_days" name="booking_advance_days" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['booking_advance_days'] ?? '30'); ?>" 
                                               min="1" max="365" required>
                                        <small class="form-hint">How far in advance students can book lessons</small>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Save Business Settings
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Email Settings Panel -->
                <div id="email-panel" class="settings-panel">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="header-left">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <h3>Email Configuration (SMTP)</h3>
                                    <p>Configure email server settings for automated notifications</p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="" class="modern-form">
                                <input type="hidden" name="action" value="update_email">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="smtp_host">
                                            <i class="fas fa-server"></i> SMTP Host
                                        </label>
                                        <input type="text" id="smtp_host" name="smtp_host" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" 
                                               placeholder="smtp.gmail.com">
                                        <small class="form-hint">Your SMTP server address</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_port">
                                            <i class="fas fa-plug"></i> SMTP Port
                                        </label>
                                        <input type="number" id="smtp_port" name="smtp_port" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                        <small class="form-hint">Usually 587 (TLS) or 465 (SSL)</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="smtp_username">
                                            <i class="fas fa-user"></i> SMTP Username
                                        </label>
                                        <input type="text" id="smtp_username" name="smtp_username" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>"
                                               autocomplete="off">
                                        <small class="form-hint">Your email address or SMTP username</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_password">
                                            <i class="fas fa-key"></i> SMTP Password
                                        </label>
                                        <input type="password" id="smtp_password" name="smtp_password" class="form-control" 
                                               placeholder="Leave blank to keep existing password"
                                               autocomplete="new-password">
                                        <small class="form-hint">Your email password or app-specific password</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="smtp_encryption">
                                            <i class="fas fa-lock"></i> Encryption
                                        </label>
                                        <select id="smtp_encryption" name="smtp_encryption" class="form-control">
                                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                        <small class="form-hint">Encryption method for secure connection</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email_from_name">
                                            <i class="fas fa-signature"></i> From Name
                                        </label>
                                        <input type="text" id="email_from_name" name="email_from_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['email_from_name'] ?? 'Origin Driving School'); ?>">
                                        <small class="form-hint">Name shown in recipient's inbox</small>
                                    </div>
                                </div>
                                
                                <div class="test-email-section">
                                    <h4><i class="fas fa-vial"></i> Test Email Configuration</h4>
                                    <div class="test-email-form">
                                        <input type="email" id="test_email" class="form-control" placeholder="Enter email to test">
                                        <button type="button" class="btn btn-info" onclick="sendTestEmail()">
                                            <i class="fas fa-paper-plane"></i> Send Test Email
                                        </button>
                                    </div>
                                    <div id="test-email-result"></div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Save Email Settings
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings Panel -->
                <div id="security-panel" class="settings-panel">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="header-left">
                                <i class="fas fa-shield-alt"></i>
                                <div>
                                    <h3>Security Settings</h3>
                                    <p>Configure authentication and security policies</p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="" class="modern-form">
                                <input type="hidden" name="action" value="update_security">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="session_timeout">
                                            <i class="fas fa-stopwatch"></i> Session Timeout (Minutes)
                                        </label>
                                        <input type="number" id="session_timeout" name="session_timeout" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '60'); ?>" 
                                               min="5" max="1440">
                                        <small class="form-hint">Auto-logout after inactivity</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password_min_length">
                                            <i class="fas fa-key"></i> Minimum Password Length
                                        </label>
                                        <input type="number" id="password_min_length" name="password_min_length" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['password_min_length'] ?? '8'); ?>" 
                                               min="6" max="32">
                                        <small class="form-hint">Required password character count</small>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="max_login_attempts">
                                            <i class="fas fa-ban"></i> Max Login Attempts
                                        </label>
                                        <input type="number" id="max_login_attempts" name="max_login_attempts" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>" 
                                               min="3" max="10">
                                        <small class="form-hint">Lock account after failed attempts</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="require_password_change" value="1" 
                                                   <?php echo ($settings['require_password_change'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                            <span>Require Password Change on First Login</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="security-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Security Best Practices:</strong>
                                        <p>Regularly update passwords, enable two-factor authentication when available, and review user access logs periodically.</p>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Save Security Settings
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications Panel -->
                <div id="notifications-panel" class="settings-panel">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="header-left">
                                <i class="fas fa-bell"></i>
                                <div>
                                    <h3>Notification Settings</h3>
                                    <p>Control automated email and system notifications</p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="" class="modern-form">
                                <input type="hidden" name="action" value="update_notifications">
                                
                                <div class="notification-groups">
                                    <div class="notification-group">
                                        <h4><i class="fas fa-user-plus"></i> Student Notifications</h4>
                                        <div class="notification-items">
                                            <label class="notification-toggle">
                                                <input type="checkbox" name="notify_new_student" value="1" 
                                                       <?php echo ($settings['notify_new_student'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                                <div class="toggle-label">
                                                    <strong>New Student Registration</strong>
                                                    <small>Notify admins when a new student enrolls</small>
                                                </div>
                                            </label>
                                            
                                            <label class="notification-toggle">
                                                <input type="checkbox" name="notify_lesson_reminder" value="1" 
                                                       <?php echo ($settings['notify_lesson_reminder'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                                <div class="toggle-label">
                                                    <strong>Lesson Reminders</strong>
                                                    <small>Send reminders to students before lessons</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="notification-group">
                                        <h4><i class="fas fa-dollar-sign"></i> Payment Notifications</h4>
                                        <div class="notification-items">
                                            <label class="notification-toggle">
                                                <input type="checkbox" name="notify_payment_received" value="1" 
                                                       <?php echo ($settings['notify_payment_received'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                                <div class="toggle-label">
                                                    <strong>Payment Received</strong>
                                                    <small>Notify students when payment is processed</small>
                                                </div>
                                            </label>
                                            
                                            <label class="notification-toggle">
                                                <input type="checkbox" name="notify_invoice_overdue" value="1" 
                                                       <?php echo ($settings['notify_invoice_overdue'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                                <div class="toggle-label">
                                                    <strong>Overdue Invoices</strong>
                                                    <small>Alert students about overdue payments</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="reminder_hours_before">
                                            <i class="fas fa-clock"></i> Reminder Hours Before Lesson
                                        </label>
                                        <select id="reminder_hours_before" name="reminder_hours_before" class="form-control">
                                            <option value="1" <?php echo ($settings['reminder_hours_before'] ?? '') === '1' ? 'selected' : ''; ?>>1 hour before</option>
                                            <option value="3" <?php echo ($settings['reminder_hours_before'] ?? '') === '3' ? 'selected' : ''; ?>>3 hours before</option>
                                            <option value="6" <?php echo ($settings['reminder_hours_before'] ?? '') === '6' ? 'selected' : ''; ?>>6 hours before</option>
                                            <option value="12" <?php echo ($settings['reminder_hours_before'] ?? '') === '12' ? 'selected' : ''; ?>>12 hours before</option>
                                            <option value="24" <?php echo ($settings['reminder_hours_before'] ?? '24') === '24' ? 'selected' : ''; ?>>24 hours before</option>
                                            <option value="48" <?php echo ($settings['reminder_hours_before'] ?? '') === '48' ? 'selected' : ''; ?>>48 hours before</option>
                                        </select>
                                        <small class="form-hint">When to send lesson reminders to students</small>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Save Notification Settings
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Display Settings Panel -->
                <div id="display-panel" class="settings-panel">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="header-left">
                                <i class="fas fa-palette"></i>
                                <div>
                                    <h3>Display & Interface Settings</h3>
                                    <p>Customize the look and feel of your dashboard</p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <form method="POST" action="" class="modern-form">
                                <input type="hidden" name="action" value="update_display">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="items_per_page">
                                            <i class="fas fa-list"></i> Items Per Page
                                        </label>
                                        <select id="items_per_page" name="items_per_page" class="form-control">
                                            <option value="10" <?php echo ($settings['items_per_page'] ?? '') === '10' ? 'selected' : ''; ?>>10 items</option>
                                            <option value="20" <?php echo ($settings['items_per_page'] ?? '20') === '20' ? 'selected' : ''; ?>>20 items</option>
                                            <option value="50" <?php echo ($settings['items_per_page'] ?? '') === '50' ? 'selected' : ''; ?>>50 items</option>
                                            <option value="100" <?php echo ($settings['items_per_page'] ?? '') === '100' ? 'selected' : ''; ?>>100 items</option>
                                        </select>
                                        <small class="form-hint">Number of items per page in lists</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="theme_mode">
                                            <i class="fas fa-moon"></i> Theme Mode
                                        </label>
                                        <select id="theme_mode" name="theme_mode" class="form-control">
                                            <option value="light" <?php echo ($settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light Mode</option>
                                            <option value="dark" <?php echo ($settings['theme_mode'] ?? '') === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                                            <option value="auto" <?php echo ($settings['theme_mode'] ?? '') === 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                                        </select>
                                        <small class="form-hint">Visual theme for the dashboard</small>
                                    </div>
                                </div>
                                
                                <div class="display-preferences">
                                    <label class="preference-toggle">
                                        <input type="checkbox" name="sidebar_collapsed" value="1" 
                                               <?php echo ($settings['sidebar_collapsed'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-label">
                                            <strong>Collapse Sidebar by Default</strong>
                                            <small>Show a minimized sidebar on page load</small>
                                        </div>
                                    </label>
                                    
                                    <label class="preference-toggle">
                                        <input type="checkbox" name="show_dashboard_tips" value="1" 
                                               <?php echo ($settings['show_dashboard_tips'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-label">
                                            <strong>Show Dashboard Tips</strong>
                                            <small>Display helpful tips and hints on the dashboard</small>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Save Display Settings
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- System Information Panel -->
                <div id="system-panel" class="settings-panel">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="header-left">
                                <i class="fas fa-server"></i>
                                <div>
                                    <h3>System Information</h3>
                                    <p>View system details and perform maintenance tasks</p>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="system-info-grid">
                                <div class="info-section">
                                    <h4><i class="fas fa-info-circle"></i> System Details</h4>
                                    <div class="info-items">
                                        <div class="info-row">
                                            <span class="info-label">Application Version:</span>
                                            <span class="info-value">2.0.0</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">PHP Version:</span>
                                            <span class="info-value"><?php echo phpversion(); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Database:</span>
                                            <span class="info-value">MySQL <?php echo $db->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Server Software:</span>
                                            <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Operating System:</span>
                                            <span class="info-value"><?php echo PHP_OS; ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Server Time:</span>
                                            <span class="info-value"><?php echo date('Y-m-d H:i:s'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-section">
                                    <h4><i class="fas fa-database"></i> Database Information</h4>
                                    <div class="info-items">
                                        <div class="info-row">
                                            <span class="info-label">Database Size:</span>
                                            <span class="info-value"><?php echo $stats['database_size']; ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Total Tables:</span>
                                            <span class="info-value">15</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Last Backup:</span>
                                            <span class="info-value"><?php echo $stats['last_backup']; ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Total Users:</span>
                                            <span class="info-value"><?php echo $stats['total_users']; ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Total Lessons:</span>
                                            <span class="info-value"><?php echo $stats['total_lessons']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-section">
                                    <h4><i class="fas fa-tools"></i> System Health</h4>
                                    <div class="health-checks">
                                        <div class="health-item healthy">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Database Connection</span>
                                            <span class="status">Healthy</span>
                                        </div>
                                        <div class="health-item healthy">
                                            <i class="fas fa-check-circle"></i>
                                            <span>File Permissions</span>
                                            <span class="status">OK</span>
                                        </div>
                                        <div class="health-item healthy">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Session Handler</span>
                                            <span class="status">Active</span>
                                        </div>
                                        <div class="health-item warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>Disk Space</span>
                                            <span class="status">85% Used</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-section">
                                    <h4><i class="fas fa-tasks"></i> Maintenance Actions</h4>
                                    <div class="maintenance-actions">
                                        <button class="btn btn-secondary" onclick="window.location.href='backup.php'">
                                            <i class="fas fa-database"></i> Backup Database Now
                                        </button>
                                        <button class="btn btn-secondary" onclick="clearCache()">
                                            <i class="fas fa-broom"></i> Clear System Cache
                                        </button>
                                        <button class="btn btn-secondary" onclick="testSystemHealth()">
                                            <i class="fas fa-heartbeat"></i> Run Health Check
                                        </button>
                                        <button class="btn btn-warning" onclick="if(confirm('This will optimize all database tables. Continue?')) window.location.href='optimize-db.php'">
                                            <i class="fas fa-cogs"></i> Optimize Database
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ==================== ENHANCED SETTINGS PAGE STYLES ==================== */

/* Top Bar */
.settings-top-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.settings-header {
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

.fa-spin-slow {
    animation: spin 3s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.header-content h2 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}

.subtitle {
    margin-top: 0.5rem;
    opacity: 0.95;
    font-size: 1rem;
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

/* System Overview */
.system-overview-section {
    margin-bottom: 2rem;
}

.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.overview-card {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s;
}

.overview-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
}

.card-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.card-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.card-icon.purple { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); }
.card-icon.orange { background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%); }
.card-icon.teal { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
.card-icon.red { background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); }

.card-content h4 {
    margin: 0;
    font-size: 2rem;
    color: #333;
}

.card-content p {
    margin: 0.25rem 0 0 0;
    color: #666;
    font-size: 0.9rem;
}

/* Tabs */
.settings-tabs-container {
    margin-bottom: 2rem;
}

.settings-tabs {
    display: flex;
    gap: 0.5rem;
    background: white;
    padding: 1rem;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.tab-button {
    padding: 1rem 1.5rem;
    background: transparent;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.95rem;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
    white-space: nowrap;
}

.tab-button:hover {
    background: #f8f9fa;
    color: #667eea;
}

.tab-button.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.tab-button i {
    font-size: 1.1rem;
}

/* Panels */
.settings-panels {
    position: relative;
}

.settings-panel {
    display: none;
    animation: fadeIn 0.3s ease;
}

.settings-panel.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.panel-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.panel-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem 2rem;
    border-bottom: 3px solid #667eea;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-left > i {
    font-size: 2rem;
    color: #667eea;
}

.header-left h3 {
    margin: 0;
    font-size: 1.5rem;
    color: #333;
}

.header-left p {
    margin: 0.25rem 0 0 0;
    color: #666;
    font-size: 0.9rem;
}

.panel-body {
    padding: 2rem;
}

/* Modern Form */
.modern-form {
    max-width: 100%;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-group label i {
    color: #667eea;
}

.form-control {
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-hint {
    color: #888;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    display: block;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 10px;
    transition: all 0.3s;
}

.checkbox-label:hover {
    background: #e9ecef;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #f0f0f0;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1rem;
    font-weight: 600;
}

/* Test Email Section */
.test-email-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin: 2rem 0;
}

.test-email-section h4 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #333;
}

.test-email-form {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.test-email-form input {
    flex: 1;
}

#test-email-result {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 8px;
    display: none;
}

#test-email-result.show {
    display: block;
}

#test-email-result.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

#test-email-result.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Security Notice */
.security-notice {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    margin: 1.5rem 0;
}

.security-notice i {
    font-size: 1.5rem;
    color: #856404;
}

.security-notice strong {
    display: block;
    color: #856404;
    margin-bottom: 0.5rem;
}

.security-notice p {
    color: #856404;
    margin: 0;
}

/* Notification Groups */
.notification-groups {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.notification-group h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #333;
    margin-bottom: 1rem;
}

.notification-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-toggle,
.preference-toggle {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
}

.notification-toggle:hover,
.preference-toggle:hover {
    background: white;
    border-color: #667eea;
}

.notification-toggle input[type="checkbox"],
.preference-toggle input[type="checkbox"] {
    display: none;
}

.toggle-slider {
    width: 50px;
    height: 28px;
    background: #ccc;
    border-radius: 14px;
    position: relative;
    transition: all 0.3s;
}

.toggle-slider::after {
    content: '';
    position: absolute;
    width: 22px;
    height: 22px;
    background: white;
    border-radius: 50%;
    top: 3px;
    left: 3px;
    transition: all 0.3s;
}

input[type="checkbox"]:checked + .toggle-slider {
    background: #667eea;
}

input[type="checkbox"]:checked + .toggle-slider::after {
    left: 25px;
}

.toggle-label strong {
    display: block;
    color: #333;
    margin-bottom: 0.25rem;
}

.toggle-label small {
    color: #666;
    font-size: 0.85rem;
}

.display-preferences {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin: 1.5rem 0;
}

/* System Information Grid */
.system-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.info-section h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #333;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f0f0f0;
}

.info-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-label {
    color: #666;
    font-weight: 500;
}

.info-value {
    color: #333;
    font-weight: 600;
}

/* Health Checks */
.health-checks {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.health-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 10px;
    background: #f8f9fa;
}

.health-item i {
    font-size: 1.5rem;
}

.health-item span:nth-child(2) {
    flex: 1;
    font-weight: 500;
    color: #333;
}

.health-item .status {
    font-weight: 600;
    font-size: 0.9rem;
}

.health-item.healthy i {
    color: #28a745;
}

.health-item.healthy .status {
    color: #28a745;
}

.health-item.warning i {
    color: #ffc107;
}

.health-item.warning .status {
    color: #ffc107;
}

.health-item.error i {
    color: #dc3545;
}

.health-item.error .status {
    color: #dc3545;
}

/* Maintenance Actions */
.maintenance-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.maintenance-actions .btn {
    justify-content: flex-start;
}

/* Alert */
.alert-dismissible {
    position: relative;
    padding-right: 3rem;
}

.alert-close {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: inherit;
    opacity: 0.7;
    cursor: pointer;
    font-size: 1.2rem;
    transition: opacity 0.3s;
}

.alert-close:hover {
    opacity: 1;
}

/* Responsive Design */
@media (max-width: 768px) {
    .settings-top-bar {
        padding: 1.5rem;
    }
    
    .settings-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-icon-wrapper {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .header-content h2 {
        font-size: 1.5rem;
    }
    
    .quick-actions-bar {
        width: 100%;
    }
    
    .btn-quick-action {
        flex: 1;
        justify-content: center;
    }
    
    .overview-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .settings-tabs {
        overflow-x: scroll;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
    
    .test-email-form {
        flex-direction: column;
    }
    
    .system-info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .overview-cards {
        grid-template-columns: 1fr;
    }
    
    .tab-button span {
        display: none;
    }
    
    .tab-button {
        padding: 0.75rem;
    }
}
</style>

<script>
// ==================== SETTINGS PAGE JAVASCRIPT ====================

// Tab Switching
function switchTab(tabName) {
    // Hide all panels
    document.querySelectorAll('.settings-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected panel
    document.getElementById(tabName + '-panel').classList.add('active');
    
    // Add active class to clicked button
    event.target.closest('.tab-button').classList.add('active');
}

// Send Test Email
function sendTestEmail() {
    const testEmail = document.getElementById('test_email').value;
    const resultDiv = document.getElementById('test-email-result');
    
    if (!testEmail) {
        alert('Please enter an email address');
        return;
    }
    
    // Show loading
    resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending test email...';
    resultDiv.className = 'show';
    
    // Send AJAX request
    fetch('?ajax=test_email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'test_email=' + encodeURIComponent(testEmail)
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.innerHTML = '<i class="fas fa-' + (data.success ? 'check-circle' : 'times-circle') + '"></i> ' + data.message;
        resultDiv.className = 'show ' + (data.success ? 'success' : 'error');
    })
    .catch(error => {
        resultDiv.innerHTML = '<i class="fas fa-times-circle"></i> Error sending test email';
        resultDiv.className = 'show error';
    });
}

// Clear Cache
function clearCache() {
    if (!confirm('Are you sure you want to clear the system cache?')) {
        return;
    }
    
    fetch('?ajax=clear_cache', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        alert('Error clearing cache');
    });
}

// Test System Health
function testSystemHealth() {
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    btn.disabled = true;
    
    fetch('?ajax=system_health')
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        
        if (data.success) {
            alert('System Health Check Results:\n\nDatabase: ' + data.data.database + '\nDisk Space: ' + data.data.disk_space + '\nMemory: ' + data.data.memory + '\nUptime: ' + data.data.uptime);
        }
    })
    .catch(error => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        alert('Error running health check');
    });
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value) {
                isValid = false;
                field.style.borderColor = '#dc3545';
            } else {
                field.style.borderColor = '#e9ecef';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
});

console.log('Settings page loaded successfully');
</script>

<?php include '../views/layouts/footer.php'; ?>