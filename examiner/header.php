<?php
// Determine current page for active menu highlighting
$active_page = basename($_SERVER['PHP_SELF'] ?? '');

// Determine examiner profile avatar
$examiner_avatar = 'assets/img/find_user.png';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['profile_photo']) && is_file(__DIR__ . '/../uploads/profile/' . $_SESSION['profile_photo'])) {
    $examiner_avatar = '../uploads/profile/' . htmlspecialchars($_SESSION['profile_photo'], ENT_QUOTES, 'UTF-8');
} elseif (!empty($_SESSION['examiner_id'])) {
    if (class_exists('DataAccess')) {
        $dao_av = new DataAccess();
        $ex_av = $dao_av->getData('profile_photo', 'examiners', 'id = ' . (int)$_SESSION['examiner_id']);
        if (!empty($ex_av) && !empty($ex_av[0]['profile_photo']) && is_file(__DIR__ . '/../uploads/profile/' . $ex_av[0]['profile_photo'])) {
            $_SESSION['profile_photo'] = $ex_av[0]['profile_photo'];
            $examiner_avatar = '../uploads/profile/' . htmlspecialchars($ex_av[0]['profile_photo'], ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Evalio | Examiner Panel</title>
    <!-- PRELOAD SAVED THEME (Prevents Flash of Unstyled Theme) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('examiner_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM THEME STYLES-->
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body>
    <div id="wrapper">
        <nav class="navbar navbar-default navbar-cls-top" role="navigation">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="../examinee/index.php" aria-label="Evalio" title="Evalio - Go to Home">
                    <img src="assets/img/Name.png" alt="Evalio" class="brand-logo" />
                </a> 
            </div>
            
            <div class="header-right-actions">
                <button type="button" id="theme-toggle-btn" class="theme-toggle-btn" onclick="toggleExaminerTheme()" title="Toggle Theme" aria-label="Toggle Light or Dark Theme">
                    <i class="fa fa-moon-o" id="theme-toggle-icon"></i>
                    <span class="theme-text" id="theme-toggle-label">Dark Mode</span>
                </button>
                <a href="profile.php" class="ev-header-avatar" title="My Profile" style="display:inline-flex; align-items:center; margin-right:12px;">
                    <img src="<?= $examiner_avatar ?>" alt="Profile" style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid #38bdf8;" onerror="this.src='assets/img/find_user.png'">
                </a>
                <a href="logout.php" class="header-logout-btn">Logout</a> 
            </div>
        </nav>   
        <!-- /. NAV TOP  -->

        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li class="text-center user-profile-header">
                        <div class="user-avatar-container">
                            <img src="<?= $examiner_avatar ?>" onerror="this.src='assets/img/find_user.png'" class="user-image img-responsive" alt="Examiner Avatar"/>
                        </div>
                    </li>

                    <li>
                        <a class="<?= ($active_page == 'dashboard.php' || $active_page == 'index.php') ? 'active-menu' : ''; ?>" href="dashboard.php">
                            <i class="fa fa-tachometer"></i> Dashboard
                        </a>
                    </li>
                     <li>
    <a class="<?= ($active_page == 'profile.php') ? 'active-menu' : ''; ?>" href="profile.php">
        <i class="fa fa-user"></i> My Profile
    </a>
</li>
                    <li>
                        <a class="<?= ($active_page == 'myexams.php' || $active_page == 'viewexam.php' || $active_page == 'editexam.php') ? 'active-menu' : ''; ?>" href="myexams.php">
                            <i class="fa fa-folder-open"></i> My Exams
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'addcertexam.php') ? 'active-menu' : ''; ?>" href="addcertexam.php">
                            <i class="fa fa-pencil-square-o"></i> Certification Exam
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'addmockexam.php') ? 'active-menu' : ''; ?>" href="addmockexam.php">
                            <i class="fa fa-desktop"></i> Mock Exam
                        </a>
                    </li>
                     <li>
                        <a class="<?= ($active_page == 'addreference.php') ? 'active-menu' : ''; ?>" href="addreference.php">
                            <i class="fa fa-table"></i> Study References
                        </a>
                    </li>
                    <li>
                        <a class="<?= ($active_page == 'grade_essays.php') ? 'active-menu' : ''; ?>" href="grade_essays.php">
                            <i class="fa fa-edit"></i> Grade Essays
                        </a>
                    </li>
                    <li>
    <a class="<?= ($active_page == 'feedback_view.php' || $active_page == 'feedback_exam.php') ? 'active-menu' : ''; ?>" href="feedback_view.php">
        <i class="fa fa-comments"></i> Exam Feedback
    </a>
</li>
                   
                    <li><a href="revenue_report.php"><i class="fa fa-money fa-3x"></i> Revenue Report</a></li>
                    <li>
                        <a class="<?= ($active_page == 'registered_students.php') ? 'active-menu' : ''; ?>" href="registered_students.php">
                            <i class="fa fa-th-large"></i> Registered Users for Exam
                        </a>
                    </li>
                   
                                        <li>
                        <a href="my_exams.php"><i class="fa fa-bar-chart"></i> My Exams &amp; Reports</a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fa fa-bolt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>  
        <!-- /. NAV SIDE  -->

        <div id="page-wrapper">
            <div id="page-inner">