<?php
/**
 * Edit [Module Name]
 * File path: [module]/edit.php
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../config/config.php';

requireLogin();
requireRole(['admin', 'instructor', 'staff']);

require_once APP_PATH . '/models/Student.php';
$studentModel = new Student();

$pageTitle = 'Edit [Module Name]';

// Get record ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid ID');
    redirect('/module/index.php');
}

// Get existing record
$sql = "SELECT * FROM table_name WHERE id_field = ?";
$record = $studentModel->customQueryOne($sql, [$id]);

if (!$record) {
    setFlashMessage('error', 'Record not found');
    redirect('/module/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'field1' => sanitize($_POST['field1']),
        'field2' => sanitize($_POST['field2']),
    ];
    
    $errors = [];
    if (empty($data['field1'])) {
        $errors[] = 'Field1 is required';
    }
    
    if (empty($errors)) {
        $sql = "UPDATE table_name SET field1 = ?, field2 = ? WHERE id_field = ?";
        $result = $studentModel->db->update($sql, [$data['field1'], $data['field2'], $id]);
        
        if ($result !== false) {
            setFlashMessage('success', 'Record updated successfully');
            redirect('/module/index.php');
        } else {
            $errors[] = 'Failed to update record';
        }
    }
}

include_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-container">
    <?php include_once BASE_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h1><i class="fas fa-edit"></i> Edit [Module Name]</h1>
            <div class="user-menu">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="content-area">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Edit Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        
                        <div class="form-group">
                            <label>Field 1 <span style="color: red;">*</span></label>
                            <input type="text" name="field1" class="form-control" 
                                   value="<?php echo isset($_POST['field1']) ? htmlspecialchars($_POST['field1']) : htmlspecialchars($record['field1']); ?>" required>
                        </div>
                        
                        <!-- Pre-populate form with existing data -->
                        
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>