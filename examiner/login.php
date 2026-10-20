<?php require('../config/autoload.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Evalio | Examiner Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="../examinee/index/image/logo.png"/>
    <!-- PRELOAD SAVED THEME -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('examiner_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="assets/css/auth.css">
</head>
<body>
<?php
$dao = new DataAccess();

$elements = array("Email" => "", "Password" => "");
$form = new FormAssist($elements, $_POST);
$rules = array(
    'Email' => array('required' => true),
    'Password' => array('required' => true),
);
$validator = new FormValidator($rules);

if(isset($_POST['login']))
{
    if($validator->validate($_POST))
    {
        $email = trim($_POST['Email']);
        $password = $_POST['Password'];
        
        // Fetch user by email to properly handle password hashing verification
        $info = $dao->getData('*', 'examiners', 'email = "' . addslashes($email) . '"');
        
        // If getData returns an array containing rows
        if($info && count($info) > 0)
        {
            // Extract the user record (handles single or multi-row returns depending on DAO implementation)
            $user = isset($info[0]) ? $info[0] : $info;
            
            $stored_password = $user['password'] ?? $user['Password'] ?? '';
            
            // Verify password using password_verify, with auto-upgrade for legacy plain text
            $password_matched = false;
            if (password_verify($password, $stored_password)) {
                $password_matched = true;
            } elseif ($password === $stored_password) {
                $password_matched = true;
                // Auto-upgrade plain-text password to Bcrypt hash
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $userId = (int)($user['id'] ?? $user['ID'] ?? 0);
                if ($userId > 0) {
                    $dao->update(array('password' => $newHash), 'examiners', 'id = ' . $userId);
                }
            }
            
            if($password_matched)
            {
                // Block deactivated accounts (set by admin in Manage Users)
                $account_status = trim($user['account_status'] ?? $user['Account_status'] ?? '');

                if(strcasecmp($account_status, 'disabled') == 0)
                {
                    echo "<script> alert('Your account has been deactivated by the Admin. Please contact the Admin to reactivate it.');</script>";
                }
                else
                {
                    // Normalize status retrieval
                    $status = trim($user['status'] ?? $user['Status'] ?? '');

                    // Check status conditions
                    if(strcasecmp($status, 'approved') == 0)
                    {
                        $_SESSION['email'] = $user['email'] ?? $user['Email'] ?? $email;
                        $_SESSION['examiner_id'] = $user['id'] ?? $user['ID'] ?? '';
                        $_SESSION['name'] = $user['name'] ?? $user['Name'] ?? '';
                        $_SESSION['profile_photo'] = !empty($user['profile_photo']) ? $user['profile_photo'] : '';

                        echo "<script> location.replace('dashboard.php'); </script>";
                        exit();
                    }
                    elseif(strcasecmp($status, 'rejected') == 0)
                    {
                        echo "<script> alert('Your account has been rejected by the Admin.');</script>";
                    }
                    else {
                        echo "<script> alert('Your account is pending Admin approval.');</script>";
                    }
                }
            }
            else {
                echo "<script> alert('Invalid username or password');</script>";
            }
        }
        else{
            echo "<script> alert('Invalid username or password');</script>";
        }
    }
}
?>

    <!-- Header matching Evalio Landing Page -->
    <header class="auth-navbar">
        <a href="../examinee/index.php" class="auth-brand-link" aria-label="Evalio">
            <img src="../examinee/index/image/Name.png" alt="E" class="auth-brand-logo-img">
        </a>
        <button type="button" id="theme-toggle-btn" class="theme-toggle-btn" onclick="toggleExaminerTheme()" title="Toggle Theme" aria-label="Toggle Light or Dark Theme">
            <i class="fa fa-sun-o" id="theme-toggle-icon"></i>
            <span class="theme-text" id="theme-toggle-label">Light Mode</span>
        </button>
    </header>

    <div class="auth-page-container">
        <div class="auth-card">
            <!-- Left Media Banner (Clean Image Only with Overlay) -->
            <div class="auth-card-media" style="background-image: url('login/images/img2.jpg');"></div>

            <!-- Right Form Container -->
            <div class="auth-card-form">
                <div class="auth-center-logo">
                    <img src="../examinee/index/image/logo.png" alt="Evalio Emblem" class="evalio-emblem-img">
                </div>

                <div class="auth-header">
                    <h1 class="auth-title">EXAMINER LOGIN</h1>
                </div>

                <form method="POST" class="auth-form" id="loginForm">
                    <div class="auth-input-group">
                        <i class="fa fa-envelope auth-input-icon"></i>
                        <input id="Email" class="auth-input" type="email" name="Email" placeholder="Email Address" required value="<?= htmlspecialchars($_POST['Email'] ?? ''); ?>">
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
                            <span style="color: var(--text-auth-muted); font-size: 13.5px;">Don't have an examiner account? </span>
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
</body>
</html>