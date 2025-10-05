<?php
/**
 * System Test Script - Origin Driving School Management System
 * 
 * This script tests all the fixed components to ensure they work correctly
 * 
 * file path: test_system.php (root directory)
 * 
 * Usage: Access via http://localhost/origin_driving_school/test_system.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once 'config/config.php';
require_once 'app/core/Database.php';
require_once 'app/core/Model.php';
require_once 'app/models/User.php';
require_once 'app/models/Student.php';
require_once 'app/models/Instructor.php';
require_once 'app/models/Lesson.php';
require_once 'app/models/Invoice.php';
require_once 'app/models/CourseAndOther.php';

// Disable error display for production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test results array
$tests = [];
$passed = 0;
$failed = 0;

/**
 * Run a test
 */
function runTest($name, $callback) {
    global $tests, $passed, $failed;
    
    try {
        $result = $callback();
        $tests[] = [
            'name' => $name,
            'status' => $result ? 'PASS' : 'FAIL',
            'message' => $result ? 'Test passed' : 'Test failed'
        ];
        
        if ($result) {
            $passed++;
        } else {
            $failed++;
        }
    } catch (Exception $e) {
        $tests[] = [
            'name' => $name,
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
        $failed++;
    }
}

// Start testing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test Results - Origin Driving School</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #4e7e95 0%, #3d6478 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            padding: 2rem;
            background: #f8f9fa;
        }
        
        .stat-card {
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            color: white;
        }
        
        .stat-card.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card.passed {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .stat-card.failed {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }
        
        .stat-card h3 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .tests {
            padding: 2rem;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid #ddd;
        }
        
        .test-item.pass {
            border-left-color: #38ef7d;
            background: #e8f8f0;
        }
        
        .test-item.fail {
            border-left-color: #f45c43;
            background: #ffe8e8;
        }
        
        .test-item.error {
            border-left-color: #ffc107;
            background: #fff8e1;
        }
        
        .test-status {
            width: 80px;
            text-align: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .test-status.pass {
            color: #11998e;
        }
        
        .test-status.fail {
            color: #eb3349;
        }
        
        .test-status.error {
            color: #f39c12;
        }
        
        .test-name {
            flex: 1;
            font-weight: 500;
            margin-left: 1rem;
        }
        
        .test-message {
            color: #666;
            font-size: 0.9rem;
        }
        
        .actions {
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #4e7e95;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }
        
        .btn:hover {
            background: #3d6478;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 System Test Results</h1>
            <p>Origin Driving School Management System - Component Testing</p>
        </div>
        
        <?php
        // TEST 1: Database Connection
        runTest('Database Connection', function() {
            $db = Database::getInstance();
            return $db->getConnection() instanceof PDO;
        });
        
        // TEST 2: Student Model
        runTest('Student Model - Basic Operations', function() {
            $studentModel = new Student();
            return $studentModel instanceof Student && method_exists($studentModel, 'getAllWithDetails');
        });
        
        // TEST 3: Student Model - getByUserId method
        runTest('Student Model - getByUserId Method', function() {
            $studentModel = new Student();
            return method_exists($studentModel, 'getByUserId');
        });
        
        // TEST 4: Instructor Model
        runTest('Instructor Model - Basic Operations', function() {
            $instructorModel = new Instructor();
            return $instructorModel instanceof Instructor && method_exists($instructorModel, 'getByUserId');
        });
        
        // TEST 5: Lesson Model
        runTest('Lesson Model - Basic Operations', function() {
            $lessonModel = new Lesson();
            return $lessonModel instanceof Lesson && method_exists($lessonModel, 'getAllWithDetails');
        });
        
        // TEST 6: Lesson Model - Search Method
        runTest('Lesson Model - Search Method', function() {
            $lessonModel = new Lesson();
            return method_exists($lessonModel, 'search');
        });
        
        // TEST 7: Invoice Model
        runTest('Invoice Model - Basic Operations', function() {
            $invoiceModel = new Invoice();
            return $invoiceModel instanceof Invoice && method_exists($invoiceModel, 'getAllWithDetails');
        });
        
        // TEST 8: Invoice Model - Search Method
        runTest('Invoice Model - Search Method', function() {
            $invoiceModel = new Invoice();
            return method_exists($invoiceModel, 'search');
        });
        
        // TEST 9: Course Model
        runTest('Course Model - Basic Operations', function() {
            $courseModel = new Course();
            return $courseModel instanceof Course;
        });
        
        // TEST 10: Branch Model
        runTest('Branch Model - Basic Operations', function() {
            $branchModel = new Branch();
            return $branchModel instanceof Branch;
        });
        
        // TEST 11: Protected Property Access (Should NOT be possible)
        runTest('OOP Encapsulation - Protected Property', function() {
            $studentModel = new Student();
            // This should NOT work - we're testing that it properly throws an error
            try {
                $db = $studentModel->db; // Trying to access protected property
                return false; // If we reach here, encapsulation is broken
            } catch (Error $e) {
                return true; // Good! Protected property cannot be accessed
            }
        });
        
        // TEST 12: System Settings Table
        runTest('System Settings Table Exists', function() {
            $db = Database::getInstance();
            $result = $db->selectOne("SHOW TABLES LIKE 'system_settings'");
            return $result !== null;
        });
        
        // TEST 13: Invoices Folder
        runTest('Invoices Folder Exists', function() {
            return is_dir(__DIR__ . '/invoices');
        });
        
        // TEST 14: Lessons Folder
        runTest('Lessons Folder Exists', function() {
            return is_dir(__DIR__ . '/lessons');
        });
        
        // TEST 15: Settings Folder
        runTest('Settings Folder Exists', function() {
            return is_dir(__DIR__ . '/settings');
        });
        
        // TEST 16: Reports Folder
        runTest('Reports Folder Exists', function() {
            return is_dir(__DIR__ . '/reports');
        });
        
        // TEST 17: Invoices Index File
        runTest('Invoices Index File Exists', function() {
            return file_exists(__DIR__ . '/invoices/index.php');
        });
        
        // TEST 18: Lessons Index File
        runTest('Lessons Index File Exists', function() {
            return file_exists(__DIR__ . '/lessons/index.php');
        });
        
        // TEST 19: Settings Index File
        runTest('Settings Index File Exists', function() {
            return file_exists(__DIR__ . '/settings/index.php');
        });
        
        // TEST 20: Reports Index File
        runTest('Reports Index File Exists', function() {
            return file_exists(__DIR__ . '/reports/index.php');
        });
        
        // TEST 21: Sample Data - Students
        runTest('Sample Data - Students Exist', function() {
            $db = Database::getInstance();
            $result = $db->selectOne("SELECT COUNT(*) as count FROM students");
            return $result && $result['count'] > 0;
        });
        
        // TEST 22: Sample Data - Instructors
        runTest('Sample Data - Instructors Exist', function() {
            $db = Database::getInstance();
            $result = $db->selectOne("SELECT COUNT(*) as count FROM instructors");
            return $result && $result['count'] > 0;
        });
        
        // TEST 23: Sample Data - Courses
        runTest('Sample Data - Courses Exist', function() {
            $db = Database::getInstance();
            $result = $db->selectOne("SELECT COUNT(*) as count FROM courses");
            return $result && $result['count'] > 0;
        });
        
        // TEST 24: Helper Functions
        runTest('Helper Functions Available', function() {
            return function_exists('redirect') && 
                   function_exists('isLoggedIn') && 
                   function_exists('getCurrentUserRole');
        });
        
        // TEST 25: Config Constants
        runTest('Configuration Constants Defined', function() {
            return defined('DB_HOST') && 
                   defined('DB_NAME') && 
                   defined('APP_URL');
        });
        ?>
        
        <div class="stats">
            <div class="stat-card total">
                <h3><?php echo count($tests); ?></h3>
                <p>Total Tests</p>
            </div>
            <div class="stat-card passed">
                <h3><?php echo $passed; ?></h3>
                <p>Passed</p>
            </div>
            <div class="stat-card failed">
                <h3><?php echo $failed; ?></h3>
                <p>Failed</p>
            </div>
        </div>
        
        <div class="tests">
            <h2 style="margin-bottom: 1.5rem; color: #4e7e95;">Test Details</h2>
            <?php foreach ($tests as $test): ?>
                <div class="test-item <?php echo strtolower($test['status']); ?>">
                    <div class="test-status <?php echo strtolower($test['status']); ?>">
                        <?php echo $test['status']; ?>
                    </div>
                    <div>
                        <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                        <?php if ($test['message']): ?>
                            <div class="test-message"><?php echo htmlspecialchars($test['message']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="actions">
            <h3 style="margin-bottom: 1rem; color: #4e7e95;">
                <?php if ($failed === 0): ?>
                    ✅ All Tests Passed! System is Ready
                <?php else: ?>
                    ⚠️ Some Tests Failed - Please Review
                <?php endif; ?>
            </h3>
            <a href="<?php echo APP_URL; ?>/login.php" class="btn">Go to Login</a>
            <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn">Go to Dashboard</a>
            <a href="?refresh=1" class="btn">Refresh Tests</a>
        </div>
    </div>
</body>
</html>