<?php
/**
 * FILE PATH: instructors/view.php
 * 
 * View Instructor Profile - Origin Driving School Management System
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/core/Database.php';
require_once '../app/core/Model.php';
require_once '../app/models/User.php';
require_once '../app/models/Instructor.php';
require_once '../app/models/Student.php';
require_once '../app/models/CourseAndOther.php';

// Require admin or staff role
requireRole(['admin', 'staff']);

$instructorModel = new Instructor();
$db = Database::getInstance();
$conn = $db->getConnection();

// Get instructor ID
$instructorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$instructorId) {
    setFlashMessage('error', 'Invalid instructor ID');
    redirect('/instructors/index.php');
}

// Get instructor details
$instructor = $instructorModel->getInstructorWithDetails($instructorId);

if (!$instructor) {
    setFlashMessage('error', 'Instructor not found');
    redirect('/instructors/index.php');
}

// Get assigned students count
$stmtStudents = $conn->prepare("SELECT COUNT(*) FROM students WHERE assigned_instructor_id = ?");
$stmtStudents->execute([$instructorId]);
$studentCount = (int)$stmtStudents->fetchColumn();

// Get lessons statistics
$stmtLessons = $conn->prepare("
    SELECT 
        COUNT(*) as total_lessons,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_lessons,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_lessons,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_lessons
    FROM lessons 
    WHERE instructor_id = ?
");
$stmtLessons->execute([$instructorId]);
$lessonStats = $stmtLessons->fetch(PDO::FETCH_ASSOC);

// Get recent lessons
$stmtRecentLessons = $conn->prepare("
    SELECT 
        l.*,
        CONCAT(u.first_name, ' ', u.last_name) as student_name,
        c.course_name
    FROM lessons l
    INNER JOIN students s ON l.student_id = s.student_id
    INNER JOIN users u ON s.user_id = u.user_id
    INNER JOIN courses c ON l.course_id = c.course_id
    WHERE l.instructor_id = ?
    ORDER BY l.lesson_date DESC, l.start_time DESC
    LIMIT 10
");
$stmtRecentLessons->execute([$instructorId]);
$recentLessons = $stmtRecentLessons->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'View Instructor Profile';
include '../views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include '../views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h2><i class="fas fa-user-circle"></i> Instructor Profile</h2>
                <p style="color: #666; margin-top: 0.3rem;">
                    View and manage instructor information
                </p>
            </div>
            <div class="user-menu">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="edit.php?id=<?php echo $instructorId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Profile
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
            
            <!-- Profile Header -->
            <div class="profile-header-card">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($instructor['first_name'] ?? '') . ' ' . htmlspecialchars($instructor['last_name'] ?? ''); ?></h2>
                    <p class="profile-role"><i class="fas fa-chalkboard-teacher"></i> Driving Instructor</p>
                    <div class="profile-badges">
                        <?php if (isset($instructor['is_available']) && $instructor['is_available']): ?>
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Available</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><i class="fas fa-clock"></i> Unavailable</span>
                        <?php endif; ?>
                        
                        <?php if (isset($instructor['is_active']) && $instructor['is_active']): ?>
                            <span class="badge badge-info"><i class="fas fa-user-check"></i> Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fas fa-user-times"></i> Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $studentCount; ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $lessonStats['total_lessons'] ?? 0; ?></div>
                        <div class="stat-label">Total Lessons</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($instructor['hourly_rate'] ?? 0, 2); ?></div>
                        <div class="stat-label">Hourly Rate</div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="profile-content-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- Contact Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                                <div class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($instructor['email'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($instructor['email'] ?? 'N/A'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-phone"></i> Phone</div>
                                <div class="info-value">
                                    <a href="tel:<?php echo htmlspecialchars($instructor['phone'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($instructor['phone'] ?? 'N/A'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-building"></i> Branch</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($instructor['branch_name'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Qualifications -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-certificate"></i> Qualifications & Certifications</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-id-card"></i> Certificate Number</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($instructor['certificate_number'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-shield-alt"></i> ADTA Membership</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($instructor['adta_membership'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-child"></i> WWC Check</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($instructor['wwc_card_number'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <?php if (isset($instructor['specialization']) && !empty($instructor['specialization'])): ?>
                                <div class="info-row">
                                    <div class="info-label"><i class="fas fa-star"></i> Specialization</div>
                                    <div class="info-value">
                                        <span class="badge badge-primary">
                                            <?php echo htmlspecialchars($instructor['specialization']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Bio -->
                    <?php if (isset($instructor['bio']) && !empty($instructor['bio'])): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> About</h3>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($instructor['bio'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right Column -->
                <div class="right-column">
                    <!-- Lesson Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-bar"></i> Lesson Statistics</h3>
                        </div>
                        <div class="card-body">
                            <div class="stats-grid">
                                <div class="stat-box">
                                    <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-number"><?php echo $lessonStats['total_lessons'] ?? 0; ?></div>
                                        <div class="stat-text">Total Lessons</div>
                                    </div>
                                </div>
                                
                                <div class="stat-box">
                                    <div class="stat-icon" style="background: rgba(39, 174, 96, 0.1); color: #27ae60;">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-number"><?php echo $lessonStats['completed_lessons'] ?? 0; ?></div>
                                        <div class="stat-text">Completed</div>
                                    </div>
                                </div>
                                
                                <div class="stat-box">
                                    <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-number"><?php echo $lessonStats['scheduled_lessons'] ?? 0; ?></div>
                                        <div class="stat-text">Scheduled</div>
                                    </div>
                                </div>
                                
                                <div class="stat-box">
                                    <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <div class="stat-details">
                                        <div class="stat-number"><?php echo $lessonStats['cancelled_lessons'] ?? 0; ?></div>
                                        <div class="stat-text">Cancelled</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Lessons -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Lessons</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentLessons)): ?>
                                <p class="text-muted">No lessons found</p>
                            <?php else: ?>
                                <div class="lessons-list">
                                    <?php foreach ($recentLessons as $lesson): ?>
                                        <div class="lesson-item">
                                            <div class="lesson-date">
                                                <div class="date-day"><?php echo date('d', strtotime($lesson['lesson_date'])); ?></div>
                                                <div class="date-month"><?php echo date('M', strtotime($lesson['lesson_date'])); ?></div>
                                            </div>
                                            <div class="lesson-details">
                                                <div class="lesson-student"><?php echo htmlspecialchars($lesson['student_name'] ?? 'N/A'); ?></div>
                                                <div class="lesson-info">
                                                    <span><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($lesson['start_time'])); ?></span>
                                                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($lesson['course_name'] ?? 'N/A'); ?></span>
                                                </div>
                                            </div>
                                            <div class="lesson-status">
                                                <?php
                                                $statusColors = [
                                                    'scheduled' => 'warning',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'no_show' => 'secondary'
                                                ];
                                                $statusColor = $statusColors[$lesson['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?php echo $statusColor; ?>">
                                                    <?php echo ucfirst($lesson['status']); ?>
                                                </span>
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
    </div>
</div>

<style>
.profile-header-card {
    background: linear-gradient(135deg, #4e7e95 0%, #3d6478 100%);
    border-radius: 12px;
    padding: 2rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.profile-avatar {
    width: 120px;
    height: 120px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5rem;
    color: white;
}

.profile-info {
    flex: 1;
}

.profile-info h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.profile-role {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    opacity: 0.9;
}

.profile-badges {
    display: flex;
    gap: 0.5rem;
}

.profile-stats {
    display: flex;
    gap: 2rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    min-width: 100px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.profile-content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
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

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-value {
    color: #2c3e50;
    font-weight: 500;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-box {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-details {
    flex: 1;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-text {
    font-size: 0.85rem;
    color: #7f8c8d;
}

.lessons-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.lesson-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.lesson-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.lesson-date {
    text-align: center;
    padding: 0.5rem;
    background: white;
    border-radius: 8px;
    min-width: 60px;
}

.date-day {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
}

.date-month {
    font-size: 0.85rem;
    color: #7f8c8d;
    text-transform: uppercase;
}

.lesson-details {
    flex: 1;
}

.lesson-student {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.lesson-info {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: #7f8c8d;
}

.lesson-status {
    min-width: 100px;
    text-align: right;
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-success {
    background: rgba(39, 174, 96, 0.1);
    color: #27ae60;
    border: 1px solid rgba(39, 174, 96, 0.2);
}

.badge-warning {
    background: rgba(243, 156, 18, 0.1);
    color: #f39c12;
    border: 1px solid rgba(243, 156, 18, 0.2);
}

.badge-danger {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.2);
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

.text-muted {
    color: #95a5a6;
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

@media (max-width: 768px) {
    .profile-header-card {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-stats {
        width: 100%;
        justify-content: space-around;
    }
    
    .profile-content-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../views/layouts/footer.php'; ?>