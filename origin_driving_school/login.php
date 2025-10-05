<?php
/**
 * Login Page - Origin Driving School Management System
 * 
 * User authentication page
 * Created for DWIN309 Final Assessment at Kent Institute Australia
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once 'config/config.php';
require_once 'app/models/User.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $userModel = new User();
        $user = $userModel->authenticate($email, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Set remember me cookie if checked
            if ($rememberMe) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
            }
            
            // Redirect to appropriate dashboard
            setFlashMessage('success', 'Welcome back, ' . $user['first_name'] . '!');
            redirect('/dashboard.php');
        } else {
            $error = 'Invalid email or password';
        }
    }
}

$pageTitle = 'Login';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Origin Driving School</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-car"></i>
                <h2>Origin Driving School</h2>
                <p>Management System Login</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="Enter your email"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                    >
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Remember me</label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="login-footer">
                <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
                <p style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5edf0;">
                    <strong>Demo Credentials:</strong><br>
                    <small>Admin: admin@origindrivingschool.com.au / pw is 'password'</small><br>
                    <small>Instructor: david.smith@origindrivingschool.com.au / pw is 'password'</small><br>
                    <small>Student: olivia.taylor@email.com / pw is 'password'</small>
                </p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo asset('js/script.js'); ?>"></script>
</body>
</html>