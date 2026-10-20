<?php
require('../config/autoload.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$dao = new DataAccess();

$error_msg = '';
$success_msg = '';
$info_msg = '';

// Step control: 'verify' (default), 'verified' (show username & set new password), 'success' (completed)
$step = 'verify';
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'find_username' ? 'find_username' : 'verify_both';

// If user already verified in this session and hasn't expired (15 mins)
if (isset($_SESSION['forgot_examinee_verified']) && is_array($_SESSION['forgot_examinee_verified'])) {
    if (time() - $_SESSION['forgot_examinee_verified']['time'] < 900) {
        $step = 'verified';
    } else {
        unset($_SESSION['forgot_examinee_verified']);
    }
}

// -------------------------------------------------------------
// ACTION 1: Cancel / Reset Flow
// -------------------------------------------------------------
if (isset($_GET['cancel'])) {
    unset($_SESSION['forgot_examinee_verified']);
    header('Location: forgot_password.php');
    exit();
}

// -------------------------------------------------------------
// ACTION 2: Primary Verification (Email AND Phone together)
// -------------------------------------------------------------
if (isset($_POST['btn_verify'])) {
    $active_tab = 'verify_both';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $clean_phone = preg_replace('/\D/', '', $phone);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid registered email address.';
    } elseif (empty($phone) || strlen($clean_phone) < 7) {
        $error_msg = 'Please enter your valid registered phone number.';
    } else {
        // Query examinees by email
        $res = $dao->getData('*', 'examinees', 'email = "' . addslashes($email) . '"');

        if ($res && count($res) > 0) {
            $user = isset($res[0]) ? $res[0] : $res;
            $user_phone_clean = preg_replace('/\D/', '', $user['phone'] ?? '');

            if (empty($user_phone_clean)) {
                $error_msg = 'No phone number is registered on file for this examinee account. Please contact support.';
            } elseif ($user_phone_clean === $clean_phone) {
                // Verified: phone and email exist together for this person!
                $_SESSION['forgot_examinee_verified'] = array(
                    'id'    => (int)$user['id'],
                    'email' => $user['email'],
                    'name'  => $user['name'],
                    'phone' => $user['phone'],
                    'time'  => time()
                );
                $step = 'verified';
            } else {
                $error_msg = 'The email and phone number provided do not match our records for the same account.';
            }
        } else {
            $error_msg = 'No student/examinee account found matching this email address.';
        }
    }
}

// -------------------------------------------------------------
// ACTION 3: Helper - Find Username / Email by Phone Number
// -------------------------------------------------------------
if (isset($_POST['btn_find_username'])) {
    $active_tab = 'find_username';
    $phone = trim($_POST['phone_lookup'] ?? '');
    $clean_phone = preg_replace('/\D/', '', $phone);

    if (empty($clean_phone) || strlen($clean_phone) < 7) {
        $error_msg = 'Please enter a valid phone number with at least 7 digits.';
    } else {
        $all = $dao->getData(array('id', 'name', 'email', 'phone'), 'examinees', 'phone IS NOT NULL AND phone != ""');
        $found = null;
        if (is_array($all)) {
            foreach ($all as $ex) {
                if (preg_replace('/\D/', '', $ex['phone']) === $clean_phone) {
                    $found = $ex;
                    break;
                }
            }
        }

        if ($found) {
            $info_msg = 'Account found for <strong>' . htmlspecialchars($found['name']) . '</strong>! Registered Username / Login Email: <strong>' . htmlspecialchars($found['email']) . '</strong>.';
            $_POST['email'] = $found['email'];
            $_POST['phone'] = $found['phone'];
            $active_tab = 'verify_both';
        } else {
            $error_msg = 'No student/examinee account found associated with this phone number.';
        }
    }
}

// -------------------------------------------------------------
// ACTION 4: Reset Password (Only allowed when verified)
// -------------------------------------------------------------
if (isset($_POST['btn_reset_password'])) {
    if (!isset($_SESSION['forgot_examinee_verified'])) {
        $error_msg = 'Verification session expired. Please verify your email and phone number again.';
        $step = 'verify';
    } else {
        $verified_user = $_SESSION['forgot_examinee_verified'];
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 6) {
            $error_msg = 'Password must be at least 6 characters long.';
            $step = 'verified';
        } elseif ($new_password !== $confirm_password) {
            $error_msg = 'The new passwords do not match. Please re-enter them.';
            $step = 'verified';
        } else {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $update_ok = $dao->update(array('password' => $hash), 'examinees', 'id = ' . (int)$verified_user['id']);

            if ($update_ok) {
                unset($_SESSION['forgot_examinee_verified']);
                $step = 'success';
            } else {
                $error_msg = 'Database update failed. Please try again or contact administrator.';
                $step = 'verified';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Evalio | Examinee Account Recovery</title>
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
            <img src="index/image/Name.png" alt="Evalio" class="auth-brand-logo-img">
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
                    <img src="index/image/logo.png" alt="Evalio Emblem" class="evalio-emblem-img">
                </div>

                <div class="auth-header">
                    <h1 class="auth-title">
                        <?php if ($step === 'success'): ?>
                            SUCCESS!
                        <?php elseif ($step === 'verified'): ?>
                            RESET PASSWORD
                        <?php else: ?>
                            EXAMINEE RECOVERY
                        <?php endif; ?>
                    </h1>
                    <p style="text-align: center; font-size: 13px; margin: 6px 0 0; color: var(--text-auth-muted);">
                        <?php if ($step === 'success'): ?>
                            Your password has been successfully updated
                        <?php elseif ($step === 'verified'): ?>
                            Identity confirmed! View your username and set a new password
                        <?php else: ?>
                            Verify your email and phone number to recover your credentials
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($error_msg)): ?>
                    <div class="auth-alert-box">
                        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($info_msg)): ?>
                    <div class="auth-alert-box info">
                        <i class="fa fa-info-circle"></i> <?= $info_msg; ?>
                    </div>
                <?php endif; ?>

                <!-- STEP 1: VERIFICATION FORM -->
                <?php if ($step === 'verify'): ?>

                    <!-- Tab Switcher: Primary Verification vs Find Username -->
                    <div class="auth-mode-tabs">
                        <button type="button" class="auth-mode-tab <?= $active_tab === 'verify_both' ? 'active' : ''; ?>" onclick="switchTab('verify_both')">
                            <i class="fa fa-shield"></i> Verify Email &amp; Phone
                        </button>
                        <button type="button" class="auth-mode-tab <?= $active_tab === 'find_username' ? 'active' : ''; ?>" onclick="switchTab('find_username')">
                            <i class="fa fa-search"></i> Forgot Username?
                        </button>
                    </div>

                    <!-- Panel 1: Primary Email + Phone verification -->
                    <div id="panel_verify_both" style="<?= $active_tab === 'verify_both' ? 'display: block;' : 'display: none;'; ?>">
                        <form method="POST" class="auth-form" id="verifyForm">
                            <div class="auth-input-group">
                                <i class="fa fa-envelope auth-input-icon"></i>
                                <input id="email" class="auth-input" type="email" name="email" placeholder="Registered Email Address" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>

                            <div class="auth-input-group">
                                <i class="fa fa-phone auth-input-icon"></i>
                                <input id="phone" class="auth-input" type="text" name="phone" placeholder="Registered Phone Number" required value="<?= htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>

                            <div style="margin-top: 22px;">
                                <button class="auth-btn auth-btn-primary" name="btn_verify" type="submit">
                                    <i class="fa fa-check-circle"></i> Verify Credentials
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Panel 2: Forgot Username Lookup by Phone -->
                    <div id="panel_find_username" style="<?= $active_tab === 'find_username' ? 'display: block;' : 'display: none;'; ?>">
                        <form method="POST" class="auth-form" id="findUsernameForm">
                            <div style="margin-bottom: 14px; font-size: 12.5px; color: var(--text-auth-muted); text-align: center;">
                                Enter your registered phone number to look up your examinee account username (login email).
                            </div>
                            <div class="auth-input-group">
                                <i class="fa fa-phone auth-input-icon"></i>
                                <input id="phone_lookup" class="auth-input" type="text" name="phone_lookup" placeholder="Your Registered Phone Number" required value="<?= htmlspecialchars($_POST['phone_lookup'] ?? ''); ?>">
                            </div>

                            <div style="margin-top: 22px;">
                                <button class="auth-btn auth-btn-primary" name="btn_find_username" type="submit">
                                    <i class="fa fa-search"></i> Find My Username
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="auth-links-row" style="margin-top: 24px;">
                        <div>
                            <a href="login.php" class="auth-link">
                                <i class="fa fa-arrow-left"></i> Back to Examinee Login
                            </a>
                        </div>
                    </div>

                <!-- STEP 2: VERIFIED - SHOW USERNAME & RESET PASSWORD FORM -->
                <?php elseif ($step === 'verified'): ?>
                    <?php $vUser = $_SESSION['forgot_examinee_verified']; ?>

                    <div style="text-align: center;">
                        <span class="auth-badge-success">
                            <i class="fa fa-check-circle"></i> Credentials Verified
                        </span>
                    </div>

                    <!-- Verified Account Info Box (Answers "Forgot Username") -->
                    <div class="auth-verified-card">
                        <div class="auth-verified-row">
                            <span class="auth-verified-label"><i class="fa fa-user"></i> Full Name</span>
                            <span class="auth-verified-val"><?= htmlspecialchars($vUser['name']); ?></span>
                        </div>
                        <div class="auth-verified-row">
                            <span class="auth-verified-label"><i class="fa fa-envelope"></i> Login Username (Email)</span>
                            <span class="auth-verified-val" style="color: var(--link-auth-color);"><?= htmlspecialchars($vUser['email']); ?></span>
                        </div>
                        <div class="auth-verified-row">
                            <span class="auth-verified-label"><i class="fa fa-phone"></i> Verified Phone</span>
                            <span class="auth-verified-val"><?= htmlspecialchars($vUser['phone']); ?></span>
                        </div>
                    </div>

                    <!-- Reset Password Form (Answers "Forgot Password") -->
                    <form method="POST" class="auth-form" id="resetPwdForm">
                        <div class="auth-input-group">
                            <i class="fa fa-lock auth-input-icon"></i>
                            <input id="new_password" class="auth-input" type="password" name="new_password" placeholder="New Password (min 6 characters)" required minlength="6">
                            <i class="fa fa-eye auth-pwd-toggle" onclick="togglePasswordVisibility('new_password', this)" title="Show/Hide Password"></i>
                        </div>

                        <div class="auth-input-group">
                            <i class="fa fa-lock auth-input-icon"></i>
                            <input id="confirm_password" class="auth-input" type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
                            <i class="fa fa-eye auth-pwd-toggle" onclick="togglePasswordVisibility('confirm_password', this)" title="Show/Hide Password"></i>
                        </div>

                        <div style="margin-top: 22px;">
                            <button class="auth-btn auth-btn-primary" name="btn_reset_password" type="submit">
                                <i class="fa fa-key"></i> Update Password
                            </button>
                        </div>
                    </form>

                    <div class="auth-links-row" style="margin-top: 18px;">
                        <div>
                            <a href="forgot_password.php?cancel=1" class="auth-link" style="color: var(--text-auth-muted); font-size: 13px;">
                                <i class="fa fa-refresh"></i> Verify a different account
                            </a>
                        </div>
                        <div>
                            <a href="login.php" class="auth-link">
                                <i class="fa fa-arrow-left"></i> Back to Login
                            </a>
                        </div>
                    </div>

                <!-- STEP 3: SUCCESS CONFIRMATION -->
                <?php elseif ($step === 'success'): ?>

                    <div style="text-align: center; padding: 10px 0 20px;">
                        <div style="width: 76px; height: 76px; line-height: 76px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); border: 2px solid #10b981; margin: 0 auto 18px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-check" style="font-size: 34px; color: #10b981;"></i>
                        </div>

                        <div class="auth-alert-box success" style="margin-bottom: 20px;">
                            Your password has been successfully updated!
                        </div>

                        <p style="font-size: 13.5px; color: var(--text-auth-muted); margin-bottom: 24px; line-height: 1.6;">
                            You can now log in to the Examinee portal using your verified email address and new password.
                        </p>

                        <a href="login.php" class="auth-btn auth-btn-primary">
                            <i class="fa fa-sign-in"></i> Sign In to Account
                        </a>
                    </div>

                <?php endif; ?>

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

    function switchTab(tab) {
        var pVerify = document.getElementById('panel_verify_both');
        var pUser   = document.getElementById('panel_find_username');
        var tabs    = document.querySelectorAll('.auth-mode-tab');
        if (tabs.length >= 2) {
            tabs[0].classList.toggle('active', tab === 'verify_both');
            tabs[1].classList.toggle('active', tab === 'find_username');
        }
        if (pVerify) pVerify.style.display = (tab === 'verify_both') ? 'block' : 'none';
        if (pUser)   pUser.style.display   = (tab === 'find_username') ? 'block' : 'none';
    }

    function togglePasswordVisibility(fieldId, iconElem) {
        var input = document.getElementById(fieldId);
        if (input) {
            if (input.type === 'password') {
                input.type = 'text';
                iconElem.className = 'fa fa-eye-slash auth-pwd-toggle';
            } else {
                input.type = 'password';
                iconElem.className = 'fa fa-eye auth-pwd-toggle';
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
