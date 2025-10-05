<?php
/**
 * file path: lessons/bulk_action.php
 * 
 * Bulk Action Handler - Origin Driving School Management System
 * 
 * Handles bulk operations on multiple lessons (complete, cancel, reschedule)
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../app/models/User.php';
require_once '../app/models/Lesson.php';

// Require login and admin/staff access
requireLogin();
$userRole = getCurrentUserRole();

if (!in_array($userRole, ['admin', 'staff'])) {
    setFlashMessage('You do not have permission to perform bulk actions', 'error');
    redirect('/lessons/');
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/lessons/');
}

// Get action and lesson IDs
$action = $_GET['action'] ?? '';
$lessonIds = $_POST['lesson_ids'] ?? [];

// Validate input
if (empty($action) || empty($lessonIds) || !is_array($lessonIds)) {
    setFlashMessage('Invalid bulk action request', 'error');
    redirect('/lessons/');
}

// Sanitize lesson IDs
$lessonIds = array_map('intval', $lessonIds);
$lessonIds = array_filter($lessonIds, function($id) {
    return $id > 0;
});

if (empty($lessonIds)) {
    setFlashMessage('No valid lessons selected', 'error');
    redirect('/lessons/');
}

$lessonModel = new Lesson();
$successCount = 0;
$failCount = 0;
$errors = [];

try {
    switch ($action) {
        case 'complete':
            // Mark lessons as completed
            foreach ($lessonIds as $lessonId) {
                $lesson = $lessonModel->find($lessonId);
                
                if (!$lesson) {
                    $errors[] = "Lesson ID {$lessonId} not found";
                    $failCount++;
                    continue;
                }
                
                if ($lesson['status'] !== 'scheduled') {
                    $errors[] = "Lesson ID {$lessonId} cannot be completed (status: {$lesson['status']})";
                    $failCount++;
                    continue;
                }
                
                $updated = $lessonModel->update($lessonId, [
                    'status' => 'completed',
                    'instructor_notes' => 'Bulk completed by ' . getCurrentUserName() . ' on ' . date('Y-m-d H:i:s')
                ]);
                
                if ($updated) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errors[] = "Failed to complete lesson ID {$lessonId}";
                }
            }
            
            if ($successCount > 0) {
                setFlashMessage("{$successCount} lesson(s) marked as completed successfully", 'success');
            }
            break;
            
        case 'cancel':
            // Cancel lessons
            $cancelReason = $_POST['cancel_reason'] ?? 'Bulk cancellation by administrator';
            
            foreach ($lessonIds as $lessonId) {
                $lesson = $lessonModel->find($lessonId);
                
                if (!$lesson) {
                    $errors[] = "Lesson ID {$lessonId} not found";
                    $failCount++;
                    continue;
                }
                
                if (in_array($lesson['status'], ['completed', 'cancelled'])) {
                    $errors[] = "Lesson ID {$lessonId} cannot be cancelled (status: {$lesson['status']})";
                    $failCount++;
                    continue;
                }
                
                $updated = $lessonModel->update($lessonId, [
                    'status' => 'cancelled',
                    'instructor_notes' => $cancelReason . ' - Cancelled by ' . getCurrentUserName() . ' on ' . date('Y-m-d H:i:s')
                ]);
                
                if ($updated) {
                    $successCount++;
                    
                    // TODO: Send cancellation notifications to student and instructor
                    // $this->sendCancellationNotification($lesson);
                    
                } else {
                    $failCount++;
                    $errors[] = "Failed to cancel lesson ID {$lessonId}";
                }
            }
            
            if ($successCount > 0) {
                setFlashMessage("{$successCount} lesson(s) cancelled successfully", 'success');
            }
            break;
            
        case 'reschedule':
            // Redirect to bulk reschedule page with selected lessons
            $lessonIdsStr = implode(',', $lessonIds);
            redirect('/lessons/bulk_reschedule.php?lesson_ids=' . $lessonIdsStr);
            break;
            
        case 'export':
            // Export selected lessons
            $lessons = [];
            foreach ($lessonIds as $lessonId) {
                $lesson = $lessonModel->getLessonWithFullDetails($lessonId);
                if ($lesson) {
                    $lessons[] = $lesson;
                }
            }
            
            if (empty($lessons)) {
                setFlashMessage('No lessons found for export', 'error');
                redirect('/lessons/');
            }
            
            // Generate CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="lessons_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($output, [
                'Lesson ID',
                'Date',
                'Start Time',
                'End Time',
                'Duration (min)',
                'Student Name',
                'Student Phone',
                'Instructor Name',
                'Instructor Phone',
                'Vehicle',
                'Lesson Type',
                'Status',
                'Pickup Location',
                'Dropoff Location',
                'Performance Rating',
                'Notes'
            ]);
            
            // CSV Data
            foreach ($lessons as $lesson) {
                fputcsv($output, [
                    $lesson['lesson_id'],
                    $lesson['lesson_date'],
                    $lesson['start_time'],
                    $lesson['end_time'],
                    $lesson['duration_minutes'] ?? '',
                    $lesson['student_name'],
                    $lesson['student_phone'],
                    $lesson['instructor_name'],
                    $lesson['instructor_phone'],
                    $lesson['registration_number'] ?? '',
                    $lesson['lesson_type'],
                    $lesson['status'],
                    $lesson['pickup_location'],
                    $lesson['dropoff_location'] ?? '',
                    $lesson['student_performance_rating'] ?? '',
                    $lesson['instructor_notes'] ?? ''
                ]);
            }
            
            fclose($output);
            exit;
            
        case 'delete':
            // Permanent deletion (admin only)
            if ($userRole !== 'admin') {
                setFlashMessage('Only administrators can permanently delete lessons', 'error');
                redirect('/lessons/');
            }
            
            foreach ($lessonIds as $lessonId) {
                $deleted = $lessonModel->delete($lessonId);
                
                if ($deleted) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errors[] = "Failed to delete lesson ID {$lessonId}";
                }
            }
            
            if ($successCount > 0) {
                setFlashMessage("{$successCount} lesson(s) permanently deleted", 'success');
            }
            break;
            
        case 'assign_instructor':
            // Reassign instructor for multiple lessons
            $newInstructorId = $_POST['instructor_id'] ?? 0;
            
            if (!$newInstructorId) {
                setFlashMessage('Please select an instructor', 'error');
                redirect('/lessons/');
            }
            
            foreach ($lessonIds as $lessonId) {
                $lesson = $lessonModel->find($lessonId);
                
                if (!$lesson) {
                    $errors[] = "Lesson ID {$lessonId} not found";
                    $failCount++;
                    continue;
                }
                
                if ($lesson['status'] !== 'scheduled') {
                    $errors[] = "Lesson ID {$lessonId} cannot be reassigned (status: {$lesson['status']})";
                    $failCount++;
                    continue;
                }
                
                // Check instructor availability
                $conflicts = $lessonModel->checkInstructorAvailability(
                    $newInstructorId,
                    $lesson['lesson_date'],
                    $lesson['start_time'],
                    $lesson['end_time'],
                    $lessonId
                );
                
                if (!empty($conflicts)) {
                    $errors[] = "Lesson ID {$lessonId} conflicts with instructor's schedule";
                    $failCount++;
                    continue;
                }
                
                $updated = $lessonModel->update($lessonId, [
                    'instructor_id' => $newInstructorId,
                    'instructor_notes' => 'Instructor reassigned by ' . getCurrentUserName() . ' on ' . date('Y-m-d H:i:s')
                ]);
                
                if ($updated) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errors[] = "Failed to reassign lesson ID {$lessonId}";
                }
            }
            
            if ($successCount > 0) {
                setFlashMessage("{$successCount} lesson(s) reassigned successfully", 'success');
            }
            break;
            
        case 'change_status':
            // Change status for multiple lessons
            $newStatus = $_POST['new_status'] ?? '';
            
            if (!in_array($newStatus, ['scheduled', 'completed', 'cancelled', 'in_progress', 'rescheduled'])) {
                setFlashMessage('Invalid status selected', 'error');
                redirect('/lessons/');
            }
            
            foreach ($lessonIds as $lessonId) {
                $updated = $lessonModel->update($lessonId, [
                    'status' => $newStatus,
                    'instructor_notes' => 'Status changed to ' . $newStatus . ' by ' . getCurrentUserName() . ' on ' . date('Y-m-d H:i:s')
                ]);
                
                if ($updated) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errors[] = "Failed to update lesson ID {$lessonId}";
                }
            }
            
            if ($successCount > 0) {
                setFlashMessage("{$successCount} lesson(s) status updated to {$newStatus}", 'success');
            }
            break;
            
        default:
            setFlashMessage('Invalid bulk action', 'error');
            redirect('/lessons/');
    }
    
    // Show errors if any
    if (!empty($errors) && $failCount > 0) {
        $errorMsg = "Failed to process {$failCount} lesson(s): " . implode('; ', array_slice($errors, 0, 3));
        if (count($errors) > 3) {
            $errorMsg .= '... and ' . (count($errors) - 3) . ' more';
        }
        setFlashMessage($errorMsg, 'error');
    }
    
} catch (Exception $e) {
    error_log("Bulk action error: " . $e->getMessage());
    setFlashMessage('An error occurred while processing bulk action: ' . $e->getMessage(), 'error');
}

// Redirect back to lessons page
redirect('/lessons/');
?>