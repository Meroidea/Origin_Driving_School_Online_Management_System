<?php
/**
 * DELETE STUDENT
 * File path: students/delete.php
 * 
 * IMPORTANT: This is a handler file only - no HTML output
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

requireLogin();
requireRole(['admin']); // Only admins can delete

require_once APP_PATH . '/models/Student.php';
$studentModel = new Student();

$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($studentId <= 0) {
    setFlashMessage('error', 'Invalid student ID');
    redirect('/students/index.php');
}

// Get student to verify exists
$student = $studentModel->customQueryOne("SELECT s.*, u.user_id FROM students s INNER JOIN users u ON s.user_id = u.user_id WHERE s.student_id = ?", [$studentId]);

if (!$student) {
    setFlashMessage('error', 'Student not found');
    redirect('/students/index.php');
}

// Delete user (CASCADE will delete student and related records)
$deleteSql = "DELETE FROM users WHERE user_id = ?";
$result = $studentModel->db->delete($deleteSql, [$student['user_id']]);

if ($result) {
    setFlashMessage('success', 'Student deleted successfully');
} else {
    setFlashMessage('error', 'Failed to delete student');
}

redirect('/students/index.php');
?>