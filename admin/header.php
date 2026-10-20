<?php
// Determine current page for active menu highlighting
$active_page = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Online Exam Management System</title>

    <!-- PRELOAD SAVED THEME (Prevents Flash of Unstyled Theme) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('admin_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <!-- GOOGLE FONTS: Plus Jakarta Sans (Formal Executive Typography) & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM DUAL THEME STYLES (Light & Dark Mode) -->
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body>
    <div id="wrapper">
        <!-- TOP NAV BAR -->
        <nav class="navbar navbar-default navbar-cls-top" role="navigation">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand-top" href="index.php">Admin</a> 
            </div>

            <div class="header-right-actions">
                <!-- Theme Toggle Button (Light & Dark Theme Switcher) -->
                <button type="button" id="theme-toggle-btn" class="theme-toggle-btn" onclick="toggleAdminTheme()" title="Toggle Theme" aria-label="Toggle Light or Dark Theme">
                    <i class="fa fa-moon-o" id="theme-toggle-icon"></i>
                    <span class="theme-text" id="theme-toggle-label">Dark</span>
                </button>

                <!-- Logout Button matching Image 1 & 2 -->
                <a href="logout.php" class="btn-admin-logout">Logout</a> 
            </div>
        </nav>   
        
        <!-- SIDEBAR NAV (Matches Image 1 & 2) -->
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <!-- Admin Title in Sidebar -->
                <div class="sidebar-admin-title">Admin</div>

                <!-- Logo Crest Centered -->
                <div class="sidebar-logo-container">
                    <a href="index.php">
                        <img src="assets/img/evalio_crest.png" alt="Admin Crest" class="sidebar-logo" onerror="this.src='assets/img/find_user.png'" />
                    </a>
                </div>

                <ul class="nav" id="main-menu">
                    <li>
                        <a class="<?= ($active_page == 'dashboard.php' || $active_page == 'index.php') ? 'active-menu' : ''; ?>" href="dashboard.php">
                            <i class="fa fa-dashboard"></i> Dashboard
                        </a>
                    </li>
                    
                    <li>
                        <a class="<?= ($active_page == 'category.php' || $active_page == 'editcategory.php') ? 'active-menu' : ''; ?>" href="category.php">
                            <i class="fa fa-tags"></i> Categories
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'degree.php' || $active_page == 'editdegree.php') ? 'active-menu' : ''; ?>" href="degree.php">
                            <i class="fa fa-graduation-cap"></i> Degrees
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'course.php' || $active_page == 'editcourse.php' || $active_page == 'viewcourse.php') ? 'active-menu' : ''; ?>" href="course.php">
                            <i class="fa fa-book"></i> Courses
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'approve_examiner.php') ? 'active-menu' : ''; ?>" href="approve_examiner.php">
                            <i class="fa fa-users"></i> Approve Examiners
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'approved_examiners.php' || $active_page == 'examiner_details.php' || $active_page == 'view_examiner.php' || $active_page == 'examiner_exams.php') ? 'active-menu' : ''; ?>" href="approved_examiners.php">
                            <i class="fa fa-user"></i> ApprovedExaminers
                        </a>
                    </li>
                    
                    <li>
                        <a class="<?= ($active_page == 'pending_exams.php' || $active_page == 'approve_exam.php' || $active_page == 'view_exam_questions.php') ? 'active-menu' : ''; ?>" href="pending_exams.php">
                            <i class="fa fa-check-square"></i> Approve Exams
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'examinees.php' || $active_page == 'examinee_view.php' || $active_page == 'examinee_result.php') ? 'active-menu' : ''; ?>" href="examinees.php">
                            <i class="fa fa-users"></i> Examinees
                        </a>
                    </li>
                     <li>
                        <a class="<?= ($active_page == 'manage_users.php' || $active_page == 'student_exams.php' || $active_page == 'exam_students.php') ? 'active-menu' : ''; ?>" href="manage_users.php">
                            <i class="fa fa-user"></i> Users
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'contact_messages.php' || $active_page == 'view_message.php') ? 'active-menu' : ''; ?>" href="contact_messages.php">
                            <i class="fa fa-envelope"></i> Messages
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'feedback.php') ? 'active-menu' : ''; ?>" href="feedback.php">
                            <i class="fa fa-comments"></i> Exam Feedback
                        </a>
                    </li>
                   
                    <li>
                        <a class="<?= ($active_page == 'revenue_report.php') ? 'active-menu' : ''; ?>" href="revenue_report.php">
                            <i class="fa fa-history"></i> Revenue Report
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'view_audit_log.php') ? 'active-menu' : ''; ?>" href="view_audit_log.php">
                            <i class="fa fa-history"></i> Audit Log
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'examiner_report.php') ? 'active-menu' : ''; ?>" href="examiner_report.php">
                            <i class="fa fa-bar-chart"></i> Examiner Report
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'rpt_exam_registrations.php') ? 'active-menu' : ''; ?>" href="rpt_exam_registrations.php">
                            <i class="fa fa-calendar"></i> Registrations Report
                        </a>
                    </li>
                </ul>
            </div>
        </nav>  
        
        <!-- PAGE WRAPPER START -->
        <div id="page-wrapper">
            <div id="page-inner">