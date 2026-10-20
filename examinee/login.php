<?php
require('../config/autoload.php');
require('auth_helper.php');
$dao=new DataAccess();

// Already logged in? Go straight to the dashboard
if(!empty($_SESSION['student_id']))
{
    header('Location: dashboard.php');
    exit();
}

$elements=array("Email"=>"","Password"=>"");
$form=new FormAssist($elements,$_POST);
$rules=array(
	'Email'=>array('required'=>true),
	'Password'=>array('required'=>true),
);
$validator=new FormValidator($rules);

$alert_msg = '';
if(isset($_GET['registered']))
{
    $alert_msg = 'Registered Successfully! Please Login.';
}

if(isset($_POST['login']))
{
    // Simple brute-force protection: 5 wrong tries = 5 minute lock
    if(!empty($_SESSION['lock_until']) && time() < $_SESSION['lock_until'])
    {
        $alert_msg = 'Too many failed attempts. Please try again in a few minutes.';
    }
    elseif(!csrf_check())
    {
        $alert_msg = 'Session expired. Please try again.';
    }
    elseif($validator->validate($_POST))
    {
        $email = trim($_POST['Email']);
        $password = $_POST['Password'];
        $user = null;

        // Email is checked against a strict pattern before it is used in the query
        if(safe_email($email))
        {
            $rows = $dao->getData('*', 'examinees', "email = '" . $email . "'");
            if(!empty($rows)) $user = $rows[0];
        }

        $ok = false;
        if($user)
        {
            if(password_verify($password, $user['password'])) {
                $ok = true;
            } elseif(hash_equals((string)$user['password'], $password)) {
                $ok = true;
                // Auto-upgrade plain-text password to Bcrypt hash
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $userId = (int)($user['id'] ?? 0);
                if ($userId > 0) {
                    $dao->update(array('password' => $newHash), 'examinees', 'id = ' . $userId);
                }
            }
        }

        // Blocked/inactive accounts cannot log in.
        // $deactivated is set only when the password was correct and the admin has
        // disabled the account, so the message is not shown to someone guessing emails.
        $deactivated = false;
        if($ok && isset($user['status']) && $user['status'] !== 'active')
        {
            $ok = false;
            if(strcasecmp(trim($user['status']), 'disabled') == 0) $deactivated = true;
        }

        if($ok)
        {
            session_regenerate_id(true);
            $_SESSION['email']=$user['email'];
            $_SESSION['student_id']=$user['id']; // Store examinee ID for exams
            unset($_SESSION['login_attempts'], $_SESSION['lock_until']);

            header('Location: dashboard.php');
            exit();
        }
        elseif($deactivated)
        {
            // Correct password, but the admin has deactivated this account.
            // Not counted as a failed attempt.
            $alert_msg = 'Your account has been deactivated by the Admin. Please contact the Admin for further details.';
        }
        else{
            $_SESSION['login_attempts'] = (isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] : 0) + 1;
            if($_SESSION['login_attempts'] >= 5)
            {
                $_SESSION['lock_until'] = time() + 300;
                $_SESSION['login_attempts'] = 0;
            }
            $alert_msg = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Evalio | Examinee Login</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" type="image/png" href="index/image/logo.png"/>
    <!-- PRELOAD SAVED THEME (Prevents Flash) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('examiner_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../examiner/assets/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="../examiner/assets/css/auth.css">
</head>
<body>

    <!-- Header matching Evalio Landing Page -->
    <header class="auth-navbar">
        <a href="index.php" class="auth-brand-link" aria-label="Evalio">
            <img src="index/image/Name.png" alt="E" class="auth-brand-logo-img">
        </a>
        <button type="button" id="theme-toggle-btn" class="theme-toggle-btn" onclick="toggleExaminerTheme()" title="Toggle Theme" aria-label="Toggle Light or Dark Theme">
            <i class="fa fa-sun-o" id="theme-toggle-icon"></i>
            <span class="theme-text" id="theme-toggle-label">Light Mode</span>
        </button>
    </header>

    <div class="auth-page-container">
        <div class="auth-card">
            <!-- Left Media Banner (Library image with overlay) -->
            <div class="auth-card-media" style="background-image: url('login/images/img2.jpg');"></div>

            <!-- Right Form Container -->
            <div class="auth-card-form">
                <div class="auth-center-logo">
                    <img src="index/image/logo.png" alt="Evalio Emblem" class="evalio-emblem-img">
                </div>

                <div class="auth-header">
                    <h1 class="auth-title">EXAMINEE LOGIN</h1>
                </div>

                <form method="POST" class="auth-form" id="loginForm">
                    <?= csrf_field(); ?>
                    
                    <div class="auth-input-group">
                        <i class="fa fa-envelope auth-input-icon"></i>
                        <input id="Email" class="auth-input" type="email" name="Email" placeholder="Email" required value="<?= htmlspecialchars($_POST['Email'] ?? ''); ?>">
                    </div>

                    <div class="auth-input-group">
                        <i class="fa fa-lock auth-input-icon"></i>
                        <input id="Password" class="auth-input" type="password" name="Password" placeholder="Password" required>
                    </div>

                    <div style="margin-top: 24px;">
                        <button class="auth-btn auth-btn-primary" name="login" type="submit" id="btnLogin">
                            <i class="fa fa-sign-in"></i> Sign In
                        </button>
                    </div>

                    <div class="auth-links-row">
                        <div>
                            <span style="color: var(--text-auth-muted); font-size: 13px;">Forgot </span>
                            <a href="forgot_password.php" class="auth-link">User name / password?</a>
                        </div>
                        <div style="margin-top: 4px;">
                            <span style="color: var(--text-auth-muted); font-size: 13.5px;">Don't have an account? </span>
                            <a href="registration1.php" class="auth-link bold">Sign Up</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function toggleExaminerTheme() {
        var html = document.documentElement;
        var currentTheme = html.getAttribute('data-theme') || 'dark';
        var nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', nextTheme);
        localStorage.setItem('examiner_theme', nextTheme);
        updateToggleUI(nextTheme);
    }

    function updateToggleUI(theme) {
        var icon = document.getElementById('theme-toggle-icon');
        var label = document.getElementById('theme-toggle-label');
        var btn = document.getElementById('theme-toggle-btn');
        if(icon && label) {
            if(theme === 'dark') {
                icon.className = 'fa fa-sun-o';
                label.textContent = 'Light Mode';
                if(btn) btn.setAttribute('aria-label', 'Switch to Light Mode');
            } else {
                icon.className = 'fa fa-moon-o';
                label.textContent = 'Dark Mode';
                if(btn) btn.setAttribute('aria-label', 'Switch to Dark Mode');
            }
        }
    }

    // Sync button state on initial load
    (function() {
        var currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        updateToggleUI(currentTheme);
    })();
    </script>
    <?php if($alert_msg != '') { ?>
    <script> alert(<?= json_encode($alert_msg); ?>); </script>
    <?php } ?>
</body>
</html>