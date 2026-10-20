<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_guest = !empty($is_guest);                 // set to true by exams.php / guest view of exam_details.php
$login_page = isset($login_page) ? $login_page : 'login.php'; // <-- change to your student login page name
require_once __DIR__ . '/ev_helpers.php';
$ev_avatar = $is_guest ? EV_DEFAULT_AVATAR : ev_photo_url();
// Public Contact page used by Help / Support. Confirm this path (set $contact_url before including header.php to override).
$contact_url = isset($contact_url) ? $contact_url : 'contact_us.php';
$ev_active = function($pages) use ($current_page) { 
    $pages_arr = array_map('strtolower', (array)$pages);
    return in_array(strtolower($current_page), $pages_arr, true) ? 'active-menu' : ''; 
};
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Student Portal'; ?> - Evalio</title>
    <!-- PRELOAD SAVED THEME (Prevents Flash) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('examiner_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <!-- GOOGLE FONTS (Outfit & Roboto matching Examiner side) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" />
    <!-- BOOTSTRAP STYLES-->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES (Examiner theme system and portal styles) -->
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/evalio_pages.css" rel="stylesheet" />
    <!-- JQUERY + BOOTSTRAP SCRIPTS -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</head>
<body>
    <div id="wrapper">
        <!-- TOP NAV BAR -->
        <nav class="navbar navbar-default navbar-cls-top" role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
                <?php if (!$is_guest) { ?>
                <button type="button" class="navbar-toggle" id="sidebarToggleBtn">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <?php } ?>
                <a class="navbar-brand" href="index.php" aria-label="Evalio" title="Evalio - Go to Home">
                    <img src="assets/img/Name.png" alt="Evalio" class="brand-logo" />
                </a>
            </div>
            <div class="header-right-actions navbar-top-actions">
                <button type="button" id="theme-toggle-btn" class="theme-toggle-btn" onclick="toggleExaminerTheme()" title="Toggle Theme" aria-label="Toggle Light or Dark Theme">
                    <i class="fa fa-sun-o" id="theme-toggle-icon"></i>
                    <span class="theme-text" id="theme-toggle-label">Light Mode</span>
                </button>
                <?php if (!$is_guest) { ?>
                    <a href="profile.php" class="ev-header-avatar" aria-label="My Profile"><img src="<?= htmlspecialchars($ev_avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="" /></a>
                <?php } ?>
                <?php if ($is_guest) { ?>
                    <a href="<?= $login_page; ?>" class="btn navbar-login-btn">Login</a>
                    <a href="registration1.php" class="btn navbar-register-btn">Register</a>
                <?php } else { ?>
                    <a href="logout.php" class="header-logout-btn">Logout</a>
                <?php } ?>
            </div>
        </nav>
        <!-- /. NAV TOP -->

        <!-- SIDEBAR NAV (logged-in students only) -->
        <?php if (!$is_guest) { ?>
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <nav class="navbar-default navbar-side" role="navigation" id="sidebarNav">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <li class="text-center user-profile-header">
                        <div class="user-avatar-container">
                            <img src="<?= htmlspecialchars($ev_avatar, ENT_QUOTES, 'UTF-8'); ?>" class="user-image ev-avatar-img img-responsive" alt="Examinee Avatar"/>
                        </div>
                    </li>
                    <li><a class="<?= $ev_active('dashboard.php'); ?>" href="dashboard.php"><i class="fa fa-tachometer"></i> Dashboard</a></li>
                    <li><a class="<?= $ev_active(array('student_exams.php', 'exams.php', 'exam_details.php')); ?>" href="student_exams.php"><i class="fa fa-desktop"></i> All Exams</a></li>
                    <li><a class="<?= $ev_active('my_exams.php'); ?>" href="my_exams.php"><i class="fa fa-calendar"></i> My Exams</a></li>
                    <li><a class="<?= $ev_active('results.php'); ?>" href="results.php"><i class="fa fa-bar-chart"></i> My Results</a></li>
                    <li><a class="<?= $ev_active(array('feedback.php', 'feedback_form.php')); ?>" href="feedback.php"><i class="fa fa-comments"></i> Feedback</a></li>
                    <li><a class="<?= $ev_active(array('references.php', 'References.php')); ?>" href="references.php"><i class="fa fa-book"></i> Study References</a></li>
                    <li><a class="<?= $ev_active(array('payment_history.php', 'receipt.php')); ?>" href="payment_history.php"><i class="fa fa-credit-card"></i> Payment History</a></li>
                    <li><a class="<?= $ev_active('profile.php'); ?>" href="profile.php"><i class="fa fa-user"></i> My Profile</a></li>
                    <li class="sidebar-bottom sidebar-divider" role="separator"></li>
                    <li><a class="<?= $ev_active(array('contact_us.php', 'contact.php')); ?>" href="<?= htmlspecialchars($contact_url, ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-life-ring"></i> Help / Support</a></li>
                    <li><a href="logout.php"><i class="fa fa-bolt"></i> Logout</a></li>
                </ul>
            </div>
        </nav>
        <?php } ?>
        <!-- /. NAV SIDE -->

        <?php if (!$is_guest) { ?>
        <script>
        (function () {
            function initSidebar() {
                try {
                    var toggleBtn = document.getElementById('sidebarToggleBtn');
                    var sidebar   = document.getElementById('sidebarNav');
                    var backdrop  = document.getElementById('sidebarBackdrop');

                    function closeSidebar() {
                        if (sidebar) sidebar.classList.remove('sidebar-open');
                        if (backdrop) backdrop.classList.remove('sidebar-open');
                    }
                    function toggleSidebar() {
                        if (sidebar) sidebar.classList.toggle('sidebar-open');
                        if (backdrop) backdrop.classList.toggle('sidebar-open');
                    }

                    if (toggleBtn) {
                        toggleBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            toggleSidebar();
                        });
                    }
                    if (backdrop) {
                        backdrop.addEventListener('click', closeSidebar);
                    }

                    if (sidebar) {
                        var menuLinks = sidebar.querySelectorAll('#main-menu a');
                        for (var i = 0; i < menuLinks.length; i++) {
                            menuLinks[i].addEventListener('click', function () {
                                closeSidebar();
                            });
                        }
                    }
                } catch (err) {
                    console.error('Sidebar init error:', err);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSidebar);
            } else {
                initSidebar();
            }
        })();
        </script>
        <?php } ?>

        <script>
        (function () {
            function updateToggleUI(theme) {
                try {
                    var icon = document.getElementById('theme-toggle-icon');
                    var label = document.getElementById('theme-toggle-label');
                    var btn = document.getElementById('theme-toggle-btn');
                    if (icon && label) {
                        if (theme === 'dark') {
                            icon.className = 'fa fa-sun-o';
                            label.textContent = 'Light Mode';
                            if (btn) btn.setAttribute('aria-label', 'Switch to Light Mode');
                        } else {
                            icon.className = 'fa fa-moon-o';
                            label.textContent = 'Dark Mode';
                            if (btn) btn.setAttribute('aria-label', 'Switch to Dark Mode');
                        }
                    }
                } catch (e) {
                    console.error('Theme UI error:', e);
                }
            }

            window.toggleExaminerTheme = function () {
                try {
                    var html = document.documentElement;
                    var currentTheme = html.getAttribute('data-theme') || 'dark';
                    var nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    html.setAttribute('data-theme', nextTheme);
                    localStorage.setItem('examiner_theme', nextTheme);
                    updateToggleUI(nextTheme);
                } catch (e) {
                    console.error('Toggle theme error:', e);
                }
            };

            function syncTheme() {
                try {
                    var currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                    updateToggleUI(currentTheme);
                } catch (e) {
                    console.error('Sync theme error:', e);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', syncTheme);
            } else {
                syncTheme();
            }
        })();
        </script>

        <div id="page-wrapper">
            <div id="page-inner">