<?php
/**
 * Delete [Module Name]
 * File path: [module]/delete.php
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

requireLogin();
requireRole(['admin']);

require_once APP_PATH . '/models/Student.php';
$studentModel = new Student();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid ID');
    redirect('/instructors/index.php');
}

// Check if record exists
$sql = "SELECT * FROM table_name WHERE id_field = ?";
$record = $studentModel->customQueryOne($sql, [$id]);

if (!$record) {
    setFlashMessage('error', 'Record not found');
    redirect('/instructors/index.php');
}

// Delete the record
$deleteSql = "DELETE FROM table_name WHERE id_field = ?";
$result = $studentModel->db->delete($deleteSql, [$id]);

if ($result) {
    setFlashMessage('success', 'Record deleted successfully');
} else {
    setFlashMessage('error', 'Failed to delete record');
}

redirect('/instructors/index.php');