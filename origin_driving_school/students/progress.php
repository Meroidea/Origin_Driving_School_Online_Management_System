<?php
/**
 * Student Progress Tracking - Student Dashboard
 * 
 * File path: student/progress.php
 * 
 * Visual progress tracking with course completion, skills assessment, and performance metrics
 * 
 * @author Origin Driving School Development Team
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

// Check authentication
requireLogin();
requireRole(['student']);

// Include required models
require_once APP_PATH . '/models/Student.php';
require_once APP_PATH . '/models/Lesson.php';

// Initialize models
$studentModel = new Student();
$lessonModel = new Lesson();

$pageTitle = 'My Progress';

// Get student details
$userId = $_SESSION['user_id'];
$studentSql = "SELECT s.*, 
               CONCAT(u.first_name, ' ', u.last_name) as student_name,
               b.branch_name,
               CONCAT(iu.first_name, ' ', iu.last_name) as instructor_name
               FROM students s 
               INNER JOIN users u ON s.user_id = u.user_id 
               INNER JOIN branches b ON s.branch_id = b.branch_id
               LEFT JOIN instructors i ON s.assigned_instructor_id = i.instructor_id
               LEFT JOIN users iu ON i.user_id = iu.user_id
               WHERE u.user_id = ?";
$student = $studentModel->customQueryOne($studentSql, [$userId]);

if (!$student) {
    setFlashMessage('error', 'Student profile not found');
    redirect('/dashboard.php');
}

$studentId = $student['student_id'];

// Get comprehensive progress data
$progressData = $lessonModel->getStudentProgress($studentId);

// Get lesson statistics by type
$lessonTypesSql = "SELECT 
                   lesson_type,
                   COUNT(*) as count,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                   FROM lessons 
                   WHERE student_id = ?
                   GROUP BY lesson_type";
$lessonTypes = $studentModel->customQuery($lessonTypesSql, [$studentId]);

// Get monthly progress (last 6 months)
$monthlyProgressSql = "SELECT 
                       DATE_FORMAT(lesson_date, '%Y-%m') as month,
                       DATE_FORMAT(lesson_date, '%b %Y') as month_name,
                       COUNT(*) as total_lessons,
                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_lessons
                       FROM lessons
                       WHERE student_id = ?
                       AND lesson_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                       GROUP BY DATE_FORMAT(lesson_date, '%Y-%m')
                       ORDER BY month ASC";
$monthlyProgress = $studentModel->customQuery($monthlyProgressSql, [$studentId]);

// Get skills assessment data (from completed lessons)
$skillsSql = "SELECT 
              skills_practiced,
              student_performance_rating
              FROM lessons
              WHERE student_id = ?
              AND status = 'completed'
              AND skills_practiced IS NOT NULL
              AND student_performance_rating IS NOT NULL
              ORDER BY lesson_date DESC
              LIMIT 10";
$skillsData = $studentModel->customQuery($skillsSql, [$studentId]);

// Process skills data
$skillsAssessment = [];
foreach ($skillsData as $skill) {
    if (!empty($skill['skills_practiced'])) {
        $skills = explode(',', $skill['skills_practiced']);
        foreach ($skills as $s) {
            $skillName = trim($s);
            if (!isset($skillsAssessment[$skillName])) {
                $skillsAssessment[$skillName] = [
                    'count' => 0,
                    'total_rating' => 0
                ];
            }
            $skillsAssessment[$skillName]['count']++;
            $skillsAssessment[$skillName]['total_rating'] += $skill['student_performance_rating'];
        }
    }
}

// Calculate average ratings
foreach ($skillsAssessment as $skill => &$data) {
    $data['average'] = round($data['total_rating'] / $data['count'], 1);
}
unset($data);

// Calculate overall progress percentage
$totalLessons = $progressData['total_lessons'] ?? 0;
$completedLessons = $progressData['completed_lessons'] ?? 0;
$scheduledLessons = $progressData['scheduled_lessons'] ?? 0;
$averageRating = $progressData['avg_rating'] ?? 0;

// Assume typical course is 10-20 lessons
$expectedTotalLessons = 15;
$progressPercentage = $totalLessons > 0 ? min(round(($completedLessons / $expectedTotalLessons) * 100), 100) : 0;

// Calculate test readiness score (0-100)
$testReadinessScore = 0;
if ($completedLessons >= 10) $testReadinessScore += 40;
elseif ($completedLessons >= 5) $testReadinessScore += 20;

if ($averageRating >= 4) $testReadinessScore += 40;
elseif ($averageRating >= 3) $testReadinessScore += 20;

if (count($skillsAssessment) >= 5) $testReadinessScore += 20;

include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h1><i class="fas fa-chart-line"></i> My Progress</h1>
                <p style="color: #666; margin-top: 0.3rem;">Track your learning journey and achievements</p>
            </div>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            
            <!-- Overall Progress Card -->
            <div class="card" style="background: linear-gradient(135deg, #4e7e95 0%, #3d6578 100%); color: white; margin-bottom: 2rem;">
                <div class="card-body" style="padding: 2rem;">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: center;">
                        <div>
                            <h2 style="margin: 0 0 1rem 0; color: white;">Overall Course Progress</h2>
                            <div style="background: rgba(255,255,255,0.2); border-radius: 20px; height: 40px; overflow: hidden; margin-bottom: 1rem;">
                                <div style="background: #e78759; height: 100%; width: <?php echo $progressPercentage; ?>%; display: flex; align-items: center; justify-content: flex-end; padding-right: 1rem; transition: width 1s ease;">
                                    <strong style="font-size: 1.1rem;"><?php echo $progressPercentage; ?>%</strong>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.5rem;">
                                <div>
                                    <div style="font-size: 2rem; font-weight: bold;"><?php echo $completedLessons; ?></div>
                                    <div style="opacity: 0.9;">Completed Lessons</div>
                                </div>
                                <div>
                                    <div style="font-size: 2rem; font-weight: bold;"><?php echo $scheduledLessons; ?></div>
                                    <div style="opacity: 0.9;">Upcoming Lessons</div>
                                </div>
                                <div>
                                    <div style="font-size: 2rem; font-weight: bold;">
                                        <?php echo $averageRating > 0 ? number_format($averageRating, 1) : 'N/A'; ?>
                                    </div>
                                    <div style="opacity: 0.9;">Average Rating</div>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 150px; height: 150px; border-radius: 50%; border: 8px solid rgba(255,255,255,0.3); margin: 0 auto; display: flex; align-items: center; justify-content: center; position: relative;">
                                <div style="font-size: 3rem; font-weight: bold;"><?php echo $progressPercentage; ?>%</div>
                            </div>
                            <div style="margin-top: 1rem; font-size: 1.1rem; opacity: 0.9;">
                                Course Completion
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(78, 126, 149, 0.1); color: #4e7e95;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $progressData['total_hours_learned'] ? round($progressData['total_hours_learned'] / 60, 1) : 0; ?> hrs</h3>
                        <p>Total Hours Learned</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(231, 135, 89, 0.1); color: #e78759;">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $testReadinessScore; ?>%</h3>
                        <p>Test Readiness</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo count($skillsAssessment); ?></h3>
                        <p>Skills Mastered</p>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: <?php echo $student['test_ready'] ? 'rgba(40, 167, 69, 0.1)' : 'rgba(255, 193, 7, 0.1)'; ?>; color: <?php echo $student['test_ready'] ? '#28a745' : '#ffc107'; ?>;">
                        <i class="fas fa-<?php echo $student['test_ready'] ? 'check-circle' : 'hourglass-half'; ?>"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3><?php echo $student['test_ready'] ? 'Yes' : 'Not Yet'; ?></h3>
                        <p>Test Ready Status</p>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                
                <!-- Lesson Types Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> Lessons by Type</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lessonTypes)): ?>
                            <p style="text-align: center; padding: 2rem; color: #999;">
                                No lesson data available yet
                            </p>
                        <?php else: ?>
                            <?php foreach ($lessonTypes as $type): ?>
                                <?php 
                                $typePercentage = $type['count'] > 0 ? round(($type['completed'] / $type['count']) * 100) : 0;
                                $typeLabel = ucwords(str_replace('_', ' ', $type['lesson_type']));
                                ?>
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <strong><?php echo $typeLabel; ?></strong>
                                        <span><?php echo $type['completed']; ?>/<?php echo $type['count']; ?> lessons</span>
                                    </div>
                                    <div style="background: #e5edf0; border-radius: 10px; height: 24px; overflow: hidden;">
                                        <div style="background: #4e7e95; height: 100%; width: <?php echo $typePercentage; ?>%; display: flex; align-items: center; justify-content: flex-end; padding-right: 0.5rem; color: white; font-weight: bold; transition: width 0.5s ease;">
                                            <?php if ($typePercentage > 20): ?>
                                                <?php echo $typePercentage; ?>%
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Skills Assessment -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-tasks"></i> Skills Assessment</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($skillsAssessment)): ?>
                            <p style="text-align: center; padding: 2rem; color: #999;">
                                Complete lessons to see your skills assessment
                            </p>
                        <?php else: ?>
                            <?php 
                            $sortedSkills = $skillsAssessment;
                            arsort($sortedSkills);
                            $displayCount = 0;
                            ?>
                            <?php foreach ($sortedSkills as $skill => $data): ?>
                                <?php if ($displayCount++ >= 6) break; ?>
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <strong><?php echo htmlspecialchars($skill); ?></strong>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star" style="color: <?php echo $i <= $data['average'] ? '#ffc107' : '#e0e0e0'; ?>; font-size: 0.9rem;"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div style="background: #e5edf0; border-radius: 10px; height: 20px; overflow: hidden;">
                                        <div style="background: linear-gradient(to right, #4e7e95, #e78759); height: 100%; width: <?php echo ($data['average'] / 5) * 100; ?>%; transition: width 0.5s ease;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
            
            <!-- Monthly Progress Chart -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Monthly Progress (Last 6 Months)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($monthlyProgress)): ?>
                        <p style="text-align: center; padding: 2rem; color: #999;">
                            No lesson history available for the past 6 months
                        </p>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem;">
                            <?php 
                            $maxLessons = max(array_column($monthlyProgress, 'total_lessons'));
                            ?>
                            <?php foreach ($monthlyProgress as $month): ?>
                                <div style="text-align: center;">
                                    <div style="height: 200px; display: flex; align-items: flex-end; justify-content: center; margin-bottom: 0.5rem;">
                                        <div style="width: 60px; position: relative;">
                                            <div style="background: #e5edf0; width: 100%; height: <?php echo $maxLessons > 0 ? ($month['total_lessons'] / $maxLessons) * 200 : 0; ?>px; border-radius: 4px 4px 0 0; transition: height 0.5s ease;">
                                                <div style="background: #4e7e95; width: 100%; height: <?php echo $month['total_lessons'] > 0 ? ($month['completed_lessons'] / $month['total_lessons']) * 100 : 0; ?>%; border-radius: 4px 4px 0 0;"></div>
                                            </div>
                                            <div style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-weight: bold; color: #4e7e95;">
                                                <?php echo $month['total_lessons']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.85rem; font-weight: 600; color: #666;">
                                        <?php echo $month['month_name']; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #999;">
                                        <?php echo $month['completed_lessons']; ?> completed
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Test Readiness Assessment -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header" style="background: <?php echo $student['test_ready'] ? 'linear-gradient(to right, rgba(40, 167, 69, 0.1), transparent)' : 'linear-gradient(to right, rgba(255, 193, 7, 0.1), transparent)'; ?>;">
                    <h3>
                        <i class="fas fa-<?php echo $student['test_ready'] ? 'check-circle' : 'exclamation-triangle'; ?>" style="color: <?php echo $student['test_ready'] ? '#28a745' : '#ffc107'; ?>;"></i>
                        Test Readiness Assessment
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($student['test_ready']): ?>
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fas fa-trophy" style="font-size: 4rem; color: #28a745; margin-bottom: 1rem;"></i>
                            <h3 style="color: #28a745; margin-bottom: 0.5rem;">You're Test Ready!</h3>
                            <p style="color: #666; margin-bottom: 1.5rem;">
                                Congratulations! Your instructor has confirmed you're ready for your driving test.
                            </p>
                            <a href="<?php echo APP_URL; ?>/student/book-test.php" class="btn btn-success btn-lg">
                                <i class="fas fa-calendar-check"></i> Book Your Driving Test
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="padding: 1.5rem;">
                            <h4 style="margin-bottom: 1rem; color: #666;">Requirements for Test Readiness:</h4>
                            
                            <div style="display: grid; gap: 1rem;">
                                <!-- Requirement 1 -->
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $completedLessons >= 10 ? '#28a745' : '#e5edf0'; ?>; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-<?php echo $completedLessons >= 10 ? 'check' : 'times'; ?>"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <strong>Complete Minimum 10 Lessons</strong>
                                        <div style="color: #666; margin-top: 0.25rem;">
                                            Progress: <?php echo $completedLessons; ?>/10 lessons completed
                                        </div>
                                        <div style="background: #e5edf0; border-radius: 10px; height: 8px; margin-top: 0.5rem; overflow: hidden;">
                                            <div style="background: #4e7e95; height: 100%; width: <?php echo min(($completedLessons / 10) * 100, 100); ?>%; transition: width 0.5s ease;"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Requirement 2 -->
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $averageRating >= 3.5 ? '#28a745' : '#e5edf0'; ?>; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-<?php echo $averageRating >= 3.5 ? 'check' : 'times'; ?>"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <strong>Achieve Average Rating of 3.5+</strong>
                                        <div style="color: #666; margin-top: 0.25rem;">
                                            Current average: <?php echo $averageRating > 0 ? number_format($averageRating, 1) : '0'; ?>/5.0
                                        </div>
                                        <div style="background: #e5edf0; border-radius: 10px; height: 8px; margin-top: 0.5rem; overflow: hidden;">
                                            <div style="background: #4e7e95; height: 100%; width: <?php echo $averageRating > 0 ? ($averageRating / 5) * 100 : 0; ?>%; transition: width 0.5s ease;"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Requirement 3 -->
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo count($skillsAssessment) >= 5 ? '#28a745' : '#e5edf0'; ?>; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-<?php echo count($skillsAssessment) >= 5 ? 'check' : 'times'; ?>"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <strong>Master At Least 5 Key Skills</strong>
                                        <div style="color: #666; margin-top: 0.25rem;">
                                            Skills mastered: <?php echo count($skillsAssessment); ?>/5
                                        </div>
                                        <div style="background: #e5edf0; border-radius: 10px; height: 8px; margin-top: 0.5rem; overflow: hidden;">
                                            <div style="background: #4e7e95; height: 100%; width: <?php echo min((count($skillsAssessment) / 5) * 100, 100); ?>%; transition: width 0.5s ease;"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Requirement 4 -->
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #e5edf0; color: #666; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <strong>Instructor Approval Required</strong>
                                        <div style="color: #666; margin-top: 0.25rem;">
                                            Your instructor will mark you as test ready when you're prepared
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 1.5rem; padding: 1rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                                <strong style="color: #856404;">
                                    <i class="fas fa-lightbulb"></i> Keep Learning!
                                </strong>
                                <p style="margin: 0.5rem 0 0 0; color: #856404;">
                                    Continue your lessons and practice the skills taught by your instructor. 
                                    You're <?php echo $testReadinessScore; ?>% of the way to being test ready!
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Instructor Info -->
            <?php if (!empty($student['instructor_name'])): ?>
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Your Instructor</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #4e7e95, #e78759); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #2c3e50;">
                                <?php echo htmlspecialchars($student['instructor_name']); ?>
                            </h4>
                            <p style="margin: 0; color: #666;">
                                Your assigned driving instructor at <?php echo htmlspecialchars($student['branch_name']); ?>
                            </p>
                        </div>
                        <div>
                            <a href="<?php echo APP_URL; ?>/student/book-lesson.php" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> Book Lesson
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>