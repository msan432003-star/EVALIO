<?php
require('../config/autoload.php'); 
require('auth_helper.php');
$dao=new DataAccess();

// Combined name field to match table structure (instead of separate fname/lname)
$elements=array("name"=>"","email"=>"","phone"=>"","password"=>"","cpassword"=>"");

$form=new FormAssist($elements,$_POST);
$labels=array('name'=>"Full Name","email"=>"Email Id","phone"=>"Phone Number","password"=>"Password","cpassword"=>"Confirm Password");

$rules=array(
    "name"=>array("required"=>true,"minlength"=>3,"maxlength"=>50),
    // Point unique check to 'examinees' table and 'email' column
    "email"=>array("required"=>true,"email"=>true,"unique"=>array("field"=>"email","table"=>"examinees")),
    "phone"=>array("required"=>true,"integeronly"=>true,"minlength"=>10,"maxlength"=>10),
    "password"=>array("required"=>true,"minlength"=>6),
    "cpassword"=>array("required"=>true,"compare"=>array("comparewith"=>"password","operator"=>"=")),
);
    
$validator = new FormValidator($rules,$labels);

if(isset($_POST['register']))
{
    if(!csrf_check())
    {
        $msg="Session expired. Please try again.";
    }
    elseif($validator->validate($_POST))
    {
        $escaped_email = addslashes(trim($_POST['email']));
        $existing_examiner = $dao->getData('*', 'examiners', "email = '$escaped_email'");

        if(!empty($existing_examiner))
        {
            $msg = "This email is already registered as an examiner. Please use a different email.";
        }
        else
        {
            // Data array mapped to 'examinees' table schema columns
            $data=array(
				'name'=>$_POST['name'],
				'email'=>$_POST['email'],
				'phone'=>$_POST['phone'],
				// Password is now stored as a secure hash, never as plain text
				'password'=>password_hash($_POST['password'], PASSWORD_DEFAULT),
				'status'=>'active'
			);
			
			// Insert into 'examinees' table
			if($dao->insert($data,'examinees'))
			{
				header('Location: login.php?registered=1');
				exit();
			}
			else
				$msg="Insertion failed";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Evalio | Examinee Registration</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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

    <!-- Centered Card Container -->
    <div class="auth-page-container">
        <div class="auth-card">
            <!-- Left Media Banner (Exam hall image with overlay) -->
            <div class="auth-card-media" style="background-image: url('reg/images/img2.jpg');"></div>

            <!-- Right Form Section -->
            <div class="auth-card-form" style="padding: 36px 44px;">
                <div class="auth-center-logo" style="margin-bottom: 10px;">
                    <img src="index/image/logo.png" alt="Evalio Emblem" class="evalio-emblem-img">
                </div>

                <div class="auth-header" style="margin-bottom: 20px;">
                    <h1 class="auth-title" style="white-space: nowrap; font-size: 24px;">EXAMINEE REGISTRATION</h1>
                </div>

                <form method="POST" class="auth-form" id="registerForm">
					<?= csrf_field(); ?>
					
                    <?php if(isset($msg)) { ?>
                        <div class="auth-alert-box">
                            <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($msg); ?>
                        </div>
                    <?php } ?>
                    
                    <div class="auth-input-group" style="margin-bottom: 13px;">
                        <i class="fa fa-user auth-input-icon"></i>
                        <?= $form->textBox('name', array("placeholder" => "Full Name", "class" => "auth-input", "required" => "required")); ?>
                        <span class="valErr"><?= $validator->error('name'); ?></span>
                    </div>

                    <div class="auth-input-group" style="margin-bottom: 13px;">
                        <i class="fa fa-envelope auth-input-icon"></i>
                        <?= $form->textBox('email', array("placeholder" => "Email", "class" => "auth-input", "type" => "email", "required" => "required")); ?>
                        <span class="valErr"><?= $validator->error('email'); ?></span>
                    </div>

                    <div class="auth-input-group" style="margin-bottom: 13px;">
                        <i class="fa fa-phone auth-input-icon"></i>
                        <?= $form->textBox('phone', array("placeholder" => "Phone Number", "class" => "auth-input", "type" => "tel", "required" => "required", "maxlength" => "10")); ?>
                        <span class="valErr"><?= $validator->error('phone'); ?></span>
                    </div>

                    <div class="auth-input-group" style="margin-bottom: 13px;">
                        <i class="fa fa-lock auth-input-icon"></i>
                        <?= $form->passwordbox('password', array("placeholder" => "Password", "class" => "auth-input", "required" => "required")); ?>
                        <span class="valErr"><?= $validator->error('password'); ?></span>
                    </div>

                    <div class="auth-input-group" style="margin-bottom: 13px;">
                        <i class="fa fa-lock auth-input-icon"></i>
                        <?= $form->passwordbox('cpassword', array("placeholder" => "Confirm Password", "class" => "auth-input", "required" => "required")); ?>
                        <span class="valErr"><?= $validator->error('cpassword'); ?></span>
                    </div>
                     
                    <div style="margin-top: 18px;">
                        <button class="auth-btn auth-btn-primary" name="register" type="submit">
                            <i class="fa fa-user-plus"></i> Register
                        </button>
                        <a class="auth-btn auth-btn-secondary" href="login.php" style="margin-top: 10px;">
                            <i class="fa fa-sign-in"></i> Login
                        </a>
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