<?php
/**
 * Sidebar Layout - Origin Driving School Management System
 * 
 * Navigation sidebar for dashboard pages
 * 
 * FIle path: views/layouts/sidebar.php
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = getCurrentUserRole();
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-car" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
        <h3>Origin Driving</h3>
        <p style="font-size: 0.9rem; opacity: 0.8;">Management System</p>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo APP_URL; ?>/dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <?php if ($userRole === 'admin' || $userRole === 'staff'): ?>
        <!-- Admin & Staff Menu Items -->
        <li>
            <a href="<?php echo APP_URL; ?>/students/index.php" class="<?php echo strpos($currentPage, 'students') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/instructors/index.php" class="<?php echo strpos($currentPage, 'instructors') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructors</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/lessons/index.php" class="<?php echo strpos($currentPage, 'lessons') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Lessons</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/courses/index.php" class="<?php echo strpos($currentPage, 'courses') !== false ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/invoices/index.php" class="<?php echo strpos($currentPage, 'invoices') !== false ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Invoices</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/payments/index.php" class="<?php echo strpos($currentPage, 'payments') !== false ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Payments</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/vehicles/index.php" class="<?php echo strpos($currentPage, 'vehicles') !== false ? 'active' : ''; ?>">
                <i class="fas fa-car"></i>
                <span>Vechiles</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/branches/index.php" class="<?php echo strpos($currentPage, 'branches') !== false ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>Branches</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/communications/index.php" class="<?php echo strpos($currentPage, 'communications') !== false ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i>
                <span>Communications</span>
            </a>
        </li>
        
        <?php if ($userRole === 'admin'): ?>
        <li>
            <a href="<?php echo APP_URL; ?>/staff/index.php" class="<?php echo strpos($currentPage, 'staff') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span>Staff</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/reports/index.php" class="<?php echo strpos($currentPage, 'reports') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/settings/index.php" class="<?php echo strpos($currentPage, 'settings') !== false ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php elseif ($userRole === 'instructor'): ?>
        <!-- Instructor Menu Items -->
        <li>
            <a href="<?php echo APP_URL; ?>/instructors/schedule.php" class="<?php echo $currentPage === 'schedule.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar"></i>
                <span>My Schedule</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/instructors/students.php" class="<?php echo $currentPage === 'students.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>My Students</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/instructors/lessons.php" class="<?php echo strpos($currentPage, 'lessons') !== false ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Lesson History</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/instructors/profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
        </li>
        
        <?php elseif ($userRole === 'student'): ?>
        <!-- Student Menu Items -->
        <li>
            <a href="<?php echo APP_URL; ?>/students/lessons.php" class="<?php echo strpos($currentPage, 'lessons') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>My Lessons</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/students/book-lesson.php" class="<?php echo $currentPage === 'book-lesson.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-plus"></i>
                <span>Book Lesson</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/students/progress.php" class="<?php echo $currentPage === 'progress.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>My Progress</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/students/invoices.php" class="<?php echo strpos($currentPage, 'invoices') !== false ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i>
                <span>My Invoices</span>
            </a>
        </li>
        
        <li>
            <a href="<?php echo APP_URL; ?>/students/profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Common Menu Items -->
        <li>
            <a href="<?php echo APP_URL; ?>/notifications.php" class="<?php echo $currentPage === 'notifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
        
        <li style="margin-top: 2rem;">
            <a href="<?php echo APP_URL; ?>/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>