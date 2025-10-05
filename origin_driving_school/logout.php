<?php
/**
 * Logout Page - Origin Driving School Management System
 * 
 * Handles user logout and session destruction
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once 'config/config.php';

// Destroy session
session_destroy();

// Clear all session variables
$_SESSION = array();

// Delete remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to homepage with message
header('Location: ' . APP_URL . '/index.php');
exit();
?>