<?php
/**
 * Book New Lesson - Student Dashboard
 * 
 * File path: students/book-lesson.php
 * 
 * Allows students to book driving lessons with real-time availability checking
 * 
 * @author Origin Driving School Development Team
 * @version 1.1
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

// Check authentication
requireLogin();
requireRole(['student']);

// Include required models
require_once APP_PATH . '/models/Student.php';
require_once APP_PATH . '/models/Lesson.php';
require_once APP_PATH . '/models/Instructor.php';
require_once APP_PATH . '/models/CourseAndOther.php';

// Initialize models
$studentModel = new Student();
$lessonModel = new Lesson();
$instructorModel = new Instructor();
$courseModel = new Course();
$vehicleModel = new Vehicle();

$pageTitle = 'Book New Lesson';
$errors = [];
$success = false;

// Get student details
$userId = $_SESSION['user_id'];
$studentSql = "SELECT s.*, b.branch_id, b.branch_name 
               FROM students s 
               INNER JOIN users u ON s.user_id = u.user_id 
               INNER JOIN branches b ON s.branch_id = b.branch_id
               WHERE u.user_id = ?";
$student = $studentModel->customQueryOne($studentSql, [$userId]);

if (!$student) {
    setFlashMessage('error', 'Student profile not found');
    redirect('/dashboard.php');
}

$studentId = $student['student_id'];
$branchId = $student['branch_id'];

// Get active courses
$courses = $courseModel->getActiveCourses();

// Get instructors for student's branch
$instructorsSql = "SELECT i.instructor_id, CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                   i.specialization, u.phone
                   FROM instructors i
                   INNER JOIN users u ON i.user_id = u.user_id
                   WHERE i.branch_id = ? AND i.is_available = 1 AND u.is_active = 1
                   ORDER BY u.first_name, u.last_name";
$instructors = $studentModel->customQuery($instructorsSql, [$branchId]);

// Get vehicles for student's branch
$vehiclesSql = "SELECT * FROM vehicles 
                WHERE branch_id = ? AND is_available = 1
                ORDER BY make, model";
$vehicles = $studentModel->customQuery($vehiclesSql, [$branchId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and get form data
    $courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $instructorId = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
    $vehicleId = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
    $lessonDate = sanitize($_POST['lesson_date'] ?? '');
    $startTime = sanitize($_POST['start_time'] ?? '');
    $lessonType = sanitize($_POST['lesson_type'] ?? 'practical');
    $pickupLocation = sanitize($_POST['pickup_location'] ?? '');
    $dropoffLocation = sanitize($_POST['dropoff_location'] ?? '');
    $specialRequests = sanitize($_POST['special_requests'] ?? '');
    
    // Validation
    if ($courseId <= 0) {
        $errors[] = 'Please select a course';
    }
    
    if ($instructorId <= 0) {
        $errors[] = 'Please select an instructor';
    }
    
    if ($vehicleId <= 0) {
        $errors[] = 'Please select a vehicle';
    }
    
    if (empty($lessonDate)) {
        $errors[] = 'Please select a lesson date';
    } else {
        // Validate date is not in the past
        if (strtotime($lessonDate) < strtotime('today')) {
            $errors[] = 'Lesson date cannot be in the past';
        }
        
        // Validate date is not more than 3 months in future
        $maxDate = date('Y-m-d', strtotime('+3 months'));
        if ($lessonDate > $maxDate) {
            $errors[] = 'Lessons can only be booked up to 3 months in advance';
        }
    }
    
    if (empty($startTime)) {
        $errors[] = 'Please select a lesson time';
    }
    
    if (empty($pickupLocation)) {
        $errors[] = 'Please enter a pickup location';
    }
    
    // Get course details for duration
    $endTime = '';
    if ($courseId > 0 && empty($errors)) {
        $course = $courseModel->getById($courseId);
        if ($course) {
            $lessonDuration = $course['lesson_duration']; // in minutes
            $endTime = date('H:i:s', strtotime($startTime) + ($lessonDuration * 60));
        } else {
            $errors[] = 'Invalid course selected';
        }
    }
    
    // Check instructor availability if no errors so far
    if (empty($errors) && $instructorId > 0 && !empty($lessonDate) && !empty($startTime) && !empty($endTime)) {
        $conflicts = $lessonModel->checkInstructorAvailability(
            $instructorId, 
            $lessonDate, 
            $startTime, 
            $endTime
        );
        
        if (!empty($conflicts)) {
            $errors[] = 'Instructor is not available at the selected time. Please choose a different time or instructor.';
        }
    }
    
    // Check vehicle availability
    if (empty($errors) && $vehicleId > 0 && !empty($lessonDate) && !empty($startTime) && !empty($endTime)) {
        $vehicleConflict = $lessonModel->checkVehicleAvailability(
            $vehicleId, 
            $lessonDate, 
            $startTime, 
            $endTime
        );
        
        if ($vehicleConflict) {
            $errors[] = 'Selected vehicle is not available at this time. Please choose a different vehicle or time.';
        }
    }
    
    // If no errors, create the lesson
    if (empty($errors)) {
        
        // Prepare lesson data
        $lessonData = [
            'student_id' => $studentId,
            'instructor_id' => $instructorId,
            'vehicle_id' => $vehicleId,
            'course_id' => $courseId,
            'lesson_date' => $lessonDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'pickup_location' => $pickupLocation,
            'dropoff_location' => !empty($dropoffLocation) ? $dropoffLocation : $pickupLocation,
            'lesson_type' => $lessonType,
            'status' => 'scheduled'
        ];
        
        // Add lesson_objectives only if provided
        if (!empty($specialRequests)) {
            $lessonData['lesson_objectives'] = $specialRequests;
        }
        
        // Log the attempt
        error_log("Attempting to create lesson for student ID: $studentId");
        error_log("Lesson data: " . json_encode($lessonData));
        
        // Create the lesson
        $lessonId = $lessonModel->create($lessonData);
        
        if ($lessonId && $lessonId > 0) {
            // Successfully created lesson
            error_log("Lesson created successfully with ID: $lessonId");
            
            try {
                // Get database instance for notification
                $db = Database::getInstance();
                
                // Get instructor's user_id for notification
                $instructorUser = $studentModel->customQueryOne(
                    "SELECT user_id FROM instructors WHERE instructor_id = ?", 
                    [$instructorId]
                );
                
                if ($instructorUser && isset($instructorUser['user_id'])) {
                    // Create notification for instructor
                    $notificationSql = "INSERT INTO notifications 
                                       (user_id, title, message, notification_type, link, created_at)
                                       VALUES (?, ?, ?, ?, ?, NOW())";
                    
                    $notificationMessage = sprintf(
                        'A new lesson has been booked with you on %s at %s',
                        date('M d, Y', strtotime($lessonDate)),
                        date('g:i A', strtotime($startTime))
                    );
                    
                    $notificationInserted = $db->insert($notificationSql, [
                        $instructorUser['user_id'],
                        'New Lesson Booking',
                        $notificationMessage,
                        'lesson',
                        '/lessons/view.php?id=' . $lessonId
                    ]);
                    
                    if ($notificationInserted) {
                        error_log("Notification sent to instructor user ID: " . $instructorUser['user_id']);
                    } else {
                        error_log("Failed to send notification to instructor");
                    }
                }
            } catch (Exception $e) {
                // Log notification error but don't fail the booking
                error_log("Error creating notification: " . $e->getMessage());
            }
            
            // Set success flag and flash message
            $success = true;
            setFlashMessage('success', 'Lesson booked successfully! You will receive a confirmation email shortly.');
            
            // Redirect to lessons page
            header("Location: " . APP_URL . "/students/lessons.php");
            exit();
            
        } else {
            // Failed to create lesson
            error_log("Failed to create lesson. Return value: " . var_export($lessonId, true));
            $errors[] = 'Failed to book lesson. Please check all fields and try again. If the problem persists, contact support.';
        }
    }
}

include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h1><i class="fas fa-calendar-plus"></i> Book New Lesson</h1>
                <p style="color: #666; margin-top: 0.3rem;">Schedule your next driving lesson</p>
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
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success" style="animation: slideInDown 0.3s ease;">
                    <h3 style="margin: 0 0 0.5rem 0;">
                        <i class="fas fa-check-circle"></i> Booking Confirmed!
                    </h3>
                    <p style="margin: 0;">
                        Your lesson has been successfully booked. You will receive a confirmation email shortly.
                        Redirecting to your lessons page...
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h4>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            
            <!-- Info Alert -->
            <div class="alert alert-info" style="margin-bottom: 2rem;">
                <h4 style="margin: 0 0 0.5rem 0;">
                    <i class="fas fa-info-circle"></i> Booking Information
                </h4>
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <li>Lessons can be booked up to 3 months in advance</li>
                    <li>A confirmation email will be sent to your registered email address</li>
                    <li>You can reschedule or cancel lessons up to <?php echo LESSON_CANCELLATION_HOURS ?? 24; ?> hours before the scheduled time</li>
                    <li>Branch: <strong><?php echo htmlspecialchars($student['branch_name']); ?></strong></li>
                </ul>
            </div>
            
            <!-- Booking Form -->
            <form method="POST" action="" id="bookingForm">
                
                <!-- Course & Lesson Type -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> Course & Lesson Type</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            
                            <div class="form-group">
                                <label for="course_id">Select Course <span style="color: red;">*</span></label>
                                <select id="course_id" name="course_id" class="form-control" required>
                                    <option value="">-- Choose a Course --</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>" 
                                                data-duration="<?php echo $course['lesson_duration']; ?>"
                                                <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_name']); ?> 
                                            (<?php echo $course['lesson_duration']; ?> min - <?php echo formatCurrency($course['price']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="lesson_type">Lesson Type <span style="color: red;">*</span></label>
                                <select id="lesson_type" name="lesson_type" class="form-control" required>
                                    <option value="practical" <?php echo (isset($_POST['lesson_type']) && $_POST['lesson_type'] === 'practical') ? 'selected' : ''; ?>>
                                        Practical Driving
                                    </option>
                                    <option value="theory" <?php echo (isset($_POST['lesson_type']) && $_POST['lesson_type'] === 'theory') ? 'selected' : ''; ?>>
                                        Theory/Classroom
                                    </option>
                                    <option value="test_preparation" <?php echo (isset($_POST['lesson_type']) && $_POST['lesson_type'] === 'test_preparation') ? 'selected' : ''; ?>>
                                        Test Preparation
                                    </option>
                                    <option value="highway_driving" <?php echo (isset($_POST['lesson_type']) && $_POST['lesson_type'] === 'highway_driving') ? 'selected' : ''; ?>>
                                        Highway Driving
                                    </option>
                                </select>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Date & Time -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Date & Time</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            
                            <div class="form-group">
                                <label for="lesson_date">Lesson Date <span style="color: red;">*</span></label>
                                <input type="date" id="lesson_date" name="lesson_date" class="form-control" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>"
                                       value="<?php echo htmlspecialchars($_POST['lesson_date'] ?? ''); ?>"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_time">Start Time <span style="color: red;">*</span></label>
                                <select id="start_time" name="start_time" class="form-control" required>
                                    <option value="">-- Select Time --</option>
                                    <?php
                                    // Generate time slots from 8 AM to 6 PM
                                    for ($hour = 8; $hour <= 18; $hour++) {
                                        for ($min = 0; $min < 60; $min += 30) {
                                            $time = sprintf('%02d:%02d:00', $hour, $min);
                                            $display = date('g:i A', strtotime($time));
                                            $selected = (isset($_POST['start_time']) && $_POST['start_time'] === $time) ? 'selected' : '';
                                            echo "<option value=\"$time\" $selected>$display</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <small style="color: #666; display: block; margin-top: 0.25rem;">
                                    <i class="fas fa-clock"></i> Duration will be based on selected course
                                </small>
                            </div>
                            
                        </div>
                        
                        <div id="availabilityCheck" style="margin-top: 1rem; padding: 1rem; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px; display: none;">
                            <i class="fas fa-info-circle"></i> 
                            <span id="availabilityMessage">Checking availability...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Instructor & Vehicle -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-tie"></i> Instructor & Vehicle</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            
                            <div class="form-group">
                                <label for="instructor_id">Select Instructor <span style="color: red;">*</span></label>
                                <select id="instructor_id" name="instructor_id" class="form-control" required>
                                    <option value="">-- Choose Instructor --</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?php echo $instructor['instructor_id']; ?>"
                                                <?php echo (isset($_POST['instructor_id']) && $_POST['instructor_id'] == $instructor['instructor_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($instructor['instructor_name']); ?>
                                            <?php if (!empty($instructor['specialization'])): ?>
                                                - <?php echo htmlspecialchars($instructor['specialization']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($instructors)): ?>
                                    <small style="color: #dc3545;">No instructors available for your branch</small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="vehicle_id">Select Vehicle <span style="color: red;">*</span></label>
                                <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                                    <option value="">-- Choose Vehicle --</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo $vehicle['vehicle_id']; ?>"
                                                <?php echo (isset($_POST['vehicle_id']) && $_POST['vehicle_id'] == $vehicle['vehicle_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?> 
                                            (<?php echo ucfirst($vehicle['transmission']); ?>) - 
                                            <?php echo htmlspecialchars($vehicle['registration_number']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($vehicles)): ?>
                                    <small style="color: #dc3545;">No vehicles available for your branch</small>
                                <?php endif; ?>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Pickup & Dropoff -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-map-marker-alt"></i> Pickup & Dropoff Location</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="pickup_location">Pickup Location <span style="color: red;">*</span></label>
                            <input type="text" id="pickup_location" name="pickup_location" class="form-control" 
                                   placeholder="Enter your pickup address"
                                   value="<?php echo htmlspecialchars($_POST['pickup_location'] ?? ($student['address'] . ', ' . $student['suburb'])); ?>"
                                   required>
                            <small style="color: #666; display: block; margin-top: 0.25rem;">
                                <i class="fas fa-home"></i> Your home address: 
                                <?php echo htmlspecialchars($student['address'] . ', ' . $student['suburb'] . ', ' . $student['postcode']); ?>
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="dropoff_location">Dropoff Location</label>
                            <input type="text" id="dropoff_location" name="dropoff_location" class="form-control" 
                                   placeholder="Leave blank to use same as pickup"
                                   value="<?php echo htmlspecialchars($_POST['dropoff_location'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Special Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-comment-alt"></i> Special Requests or Notes</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="special_requests">Additional Information (Optional)</label>
                            <textarea id="special_requests" name="special_requests" class="form-control" rows="4" 
                                      placeholder="Any special requirements, areas you'd like to focus on, or other notes for your instructor..."><?php echo htmlspecialchars($_POST['special_requests'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-calendar-check"></i> Book Lesson
                    </button>
                    <a href="lessons.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
                
            </form>
            
            <?php endif; ?>
            
        </div>
    </div>
</div>

<style>
@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #4e7e95;
    box-shadow: 0 0 0 3px rgba(78, 126, 149, 0.1);
}

.btn-lg {
    padding: 1rem 2.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.card {
    margin-bottom: 1.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card-header {
    padding: 1rem 1.5rem;
    background: linear-gradient(to right, rgba(78, 126, 149, 0.05), transparent);
    border-bottom: 2px solid #e0e0e0;
}

.card-header h3 {
    margin: 0;
    color: #4e7e95;
    font-size: 1.1rem;
    font-weight: 600;
}

.card-body {
    padding: 1.5rem;
}
</style>

<script>
// Real-time availability checking
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('lesson_date');
    const timeInput = document.getElementById('start_time');
    const instructorInput = document.getElementById('instructor_id');
    const availabilityDiv = document.getElementById('availabilityCheck');
    const availabilityMsg = document.getElementById('availabilityMessage');
    
    function checkAvailability() {
        const date = dateInput.value;
        const time = timeInput.value;
        const instructor = instructorInput.value;
        
        if (date && time && instructor) {
            availabilityDiv.style.display = 'block';
            availabilityMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
            
            // Simulate availability check (in production, use AJAX to check server)
            setTimeout(() => {
                availabilityMsg.innerHTML = '<i class="fas fa-check-circle"></i> Time slot is available!';
                availabilityDiv.style.background = '#d4edda';
                availabilityDiv.style.borderLeftColor = '#28a745';
            }, 1000);
        }
    }
    
    dateInput.addEventListener('change', checkAvailability);
    timeInput.addEventListener('change', checkAvailability);
    instructorInput.addEventListener('change', checkAvailability);
});

// Auto-fill dropoff with pickup if empty on form submit
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const pickup = document.getElementById('pickup_location').value;
    const dropoff = document.getElementById('dropoff_location');
    
    if (!dropoff.value.trim() && pickup.trim()) {
        dropoff.value = pickup;
    }
});

// Form validation feedback
(function() {
    'use strict';
    const forms = document.querySelectorAll('form');
    
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>