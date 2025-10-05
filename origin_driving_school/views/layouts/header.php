<?php
/**
 * Header Layout - Origin Driving School Management System
 * 
 * Common header for all dashboard pages
 * 
 * File path: views/layouts/header.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

// Ensure user is logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}

$currentUser = $_SESSION['user_name'] ?? 'User';
$currentRole = $_SESSION['user_role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Origin Driving School Management System - Professional driving instruction management">
    <meta name="author" content="SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715 - DWIN309 Kent Institute Australia">
    
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Origin Driving School</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/dashboard.css'); ?>">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* Inline critical CSS for faster loading */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Loading spinner styles */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .page-loader.active {
            display: flex;
        }
        
        .loader-spinner {
            border: 4px solid #e5edf0;
            border-top: 4px solid #4e7e95;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Skip link for accessibility */
        .skip-link {
            position: absolute;
            left: -9999px;
            top: 0;
            background: var(--color-primary);
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            z-index: 10000;
        }
        
        .skip-link:focus {
            left: 0;
        }
    </style>
</head>
<body>
    
    <!-- Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-spinner"></div>
    </div>
    
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>