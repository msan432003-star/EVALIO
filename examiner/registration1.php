<?php
require('../config/autoload.php'); 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$dao = new DataAccess();

$elements = array("name" => "", "email" => "", "phone" => "", "password" => "", "cpassword" => "");

// Coming back from step 2? Pre-fill name and email (password must be typed again)
$prefill = $_POST;
if (empty($prefill) && !empty($_SESSION['reg_step1'])) {
    $prefill = array('name' => $_SESSION['reg_step1']['name'], 'email' => $_SESSION['reg_step1']['email'], 'phone' => $_SESSION['reg_step1']['phone'] ?? '');
}
$form = new FormAssist($elements, $prefill);
$labels = array('name' => "Full Name", "email" => "Email Id", "phone" => "Phone Number", "password" => "Password", "cpassword" => "Confirm Password");

$rules = array(
    "name" => array("required" => true, "minlength" => 3, "maxlength" => 50),
    "email" => array("required" => true, "email" => true, "unique" => array("field" => "email", "table" => "examiners")),
    "phone" => array("required" => true, "integeronly" => true, "minlength" => 10, "maxlength" => 10),
    "password" => array("required" => true),
    "cpassword" => array("required" => true, "compare" => array("comparewith" => "password", "operator" => "=")),
);
    
$validator = new FormValidator($rules, $labels);

if(isset($_POST['register']))
{
    if($validator->validate($_POST))
    {
        $escaped_email = addslashes(trim($_POST['email']));
        $existing_examinee = $dao->getData('*', 'examinees', "email = '$escaped_email'");

        if(!empty($existing_examinee))
        {
            $msg = "This email is already registered as an examinee. Please use a different email.";
        }
        else
        {
            // STEP 1 is only saved temporarily in the session; the examiner account is created in step 2
            $_SESSION['reg_step1'] = array(
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'phone' => trim($_POST['phone']),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
            );
            header('Location: registration2.php');
            exit();
        }
    }
}

if(isset($_POST['home']))
{
   echo "<script> location.replace('login.php'); </script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Evalio | Examiner Registration</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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

    <!-- Centered Card Container -->
    <div class="auth-page-container">
        <div class="auth-card">
            <!-- Left Media Banner (Clean Image Only with Overlay) -->
            <div class="auth-card-media" style="background-image: url('login/images/img2.jpg');"></div>

            <!-- Right Form Section -->
            <div class="auth-card-form">
                <div class="auth-center-logo">
                    <img src="../examinee/index/image/logo.png" alt="Evalio Emblem" class="evalio-emblem-img">
                </div>

                <div class="auth-header">
                    <h1 class="auth-title">EXAMINER REGISTRATION</h1>
                    <p style="text-align:center; font-size:13px; margin:6px 0 0; color: var(--text-auth-muted);">Step 1 of 2 &mdash; Account details</p>
                </div>

                <form method="POST" class="auth-form" id="registerForm">
                    <?php if(isset($msg)) { ?>
                        <div class="auth-alert-box">
                            <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($msg); ?>
                        </div>
                    <?php } ?>
                    
                    <div class="auth-input-group">
                        <i class="fa fa-user auth-input-icon"></i>
                        <?= $form->textBox('name', array("placeholder" => "Full Name", "class" => "auth-input", "required" => "required")); ?>
                        <span class="valErr"><?= $validator->error('name'); ?></span>
                    </div>

                    <div class="auth-input-group">
                        <i class="fa fa-envelope auth-input-icon"></i>
                        <?= $form->textBox('email', array("placeholder" => "Email Address", "class" => "auth-input", "type" => "email", "required" => "required")); ?>
                        <span class="valErr"><?= $validator->error('email'); ?></span>
                    </div>

                    <div class="auth-input-group">
                        <i class="fa fa-phone auth-input-icon"></i>
                        <?= $form->textBox('phone', array("placeholder" => "Phone Number (10 digits)", "class" => "auth-input", "type" => "tel", "required" => "required", "maxlength" => "10")); ?>
                        <span class="valErr"><?= $validator->error('phone'); ?></span>
                    </div>

                    <div class="auth-input-group">
                        <i class="fa fa-lock auth-input-icon"></i>
                        <?= $form->passwordbox('password', array("placeholder" => "Password", "class" => "auth-input", "required" => "required")); ?>
                        <span class="valErr"><?= $validator->error('password'); ?></span>
                    </div>

                    <div class="auth-input-group">
                        <i class="fa fa-lock auth-input-icon"></i>
                        <?= $form->passwordbox('cpassword', array("placeholder" => "Confirm Password", "class" => "auth-input", "required" => "required")); ?>
                        <span class="valErr"><?= $validator->error('cpassword'); ?></span>
                    </div>
                     
                    <div style="margin-top: 24px;">
                        <button class="auth-btn auth-btn-primary" name="register" type="submit">
                            Next <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>

                    <div class="auth-links-row" style="margin-top: 18px;">
                        <div>
                            <span style="color: var(--text-auth-muted); font-size: 13.5px;">Already have an examiner account? </span>
                            <a href="login.php" class="auth-link bold">Sign In</a>
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