<?php
/* profile.php - edit own profile (name, phone, photo) and change password */
require('../config/autoload.php');
require('auth_helper.php');
require_once('ev_helpers.php');
$student_id = (int)require_login();   // identity comes ONLY from the session

$MAX_BYTES = 2 * 1024 * 1024;
$msgs = array('profile' => null, 'password' => null);   // array(type, text)
$old  = array();

// Load current user (fallback if profile_photo column has not been added yet)
$has_photo_col = true;
$u = ev_query('SELECT id, name, email, phone, password, profile_photo FROM examinees WHERE id = ?', 'i', array($student_id));
if ($u === false) {
    $has_photo_col = false;
    $u = ev_query('SELECT id, name, email, phone, password FROM examinees WHERE id = ?', 'i', array($student_id));
}
if (empty($u)) { header('Location: logout.php'); exit(); }
$user = $u[0];
$current_photo = ($has_photo_col && !empty($user['profile_photo'])) ? $user['profile_photo'] : '';

// Flash message from the previous request (post/redirect/get)
if (!empty($_SESSION['ev_flash'])) {
    $f = $_SESSION['ev_flash'];
    unset($_SESSION['ev_flash']);
    if (isset($f['target'], $f['type'], $f['text']) && array_key_exists($f['target'], $msgs)) {
        $msgs[$f['target']] = array($f['type'], $f['text']);
    }
}
function ev_flash_redirect($target, $type, $text) {
    $_SESSION['ev_flash'] = array('target' => $target, 'type' => $type, 'text' => $text);
    session_write_close();
    header('Location: profile.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---------------- Update profile ---------------- */
    if (isset($_POST['btn_save_profile'])) {
        $errors = array();
        if (!csrf_check()) { $errors[] = 'Session expired. Please try again.'; }

        $name  = isset($_POST['profile_name']) ? trim($_POST['profile_name']) : '';
        $phone = isset($_POST['profile_phone']) ? trim($_POST['profile_phone']) : '';
        $old = array('name' => $name, 'phone' => $phone);

        $len = mb_strlen($name, 'UTF-8');
        if ($len < 2 || $len > 100 || preg_match('/[\x00-\x1F\x7F]/u', $name)) { $errors[] = 'Full name must be 2 to 100 characters.'; }
        if (!preg_match(EV_PHONE_PATTERN, $phone)) { $errors[] = 'Enter a valid phone number.'; }

        // Optional photo
        $new_ext = '';
        $file = isset($_FILES['profile_photo']) ? $_FILES['profile_photo'] : null;
        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            if (!$has_photo_col) {
                $errors[] = 'Photo upload is not enabled yet (database column missing).';
            } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                $errors[] = 'Photo must be 2 MB or smaller.';
            } elseif ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                $errors[] = 'Photo upload failed. Please try again.';
            } elseif ($file['size'] > $MAX_BYTES) {
                $errors[] = 'Photo must be 2 MB or smaller.';
            } else {
                $fi = new finfo(FILEINFO_MIME_TYPE);
                $mime = $fi->file($file['tmp_name']);
                $map = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
                if (!isset($map[$mime]) || @getimagesize($file['tmp_name']) === false) {
                    $errors[] = 'Photo must be a JPG, PNG or WEBP image.';
                } else {
                    $new_ext = $map[$mime];
                }
            }
        }

        if (empty($errors)) {
            $new_name = '';
            if ($new_ext !== '') {
                $dir = ev_ensure_upload_dir();
                $new_name = bin2hex(random_bytes(16)) . '.' . $new_ext;
                if (!is_dir($dir) || !move_uploaded_file($file['tmp_name'], $dir . $new_name)) {
                    $errors[] = 'Could not save the photo. Please try again.';
                    $new_name = '';
                }
            }
        }

        if (empty($errors)) {
            if ($new_name !== '') {
                $ok = ev_exec('UPDATE examinees SET name = ?, phone = ?, profile_photo = ? WHERE id = ?', 'sssi', array($name, $phone, $new_name, $student_id));
            } else {
                $ok = ev_exec('UPDATE examinees SET name = ?, phone = ? WHERE id = ?', 'ssi', array($name, $phone, $student_id));
            }
            if ($ok === false) {
                if ($new_name !== '') { @unlink(__DIR__ . '/uploads/profile/' . $new_name); }
                $errors[] = 'Could not update your profile. Please try again.';
            } else {
                if ($new_name !== '' && $current_photo !== '' && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $current_photo)) {
                    @unlink(__DIR__ . '/uploads/profile/' . $current_photo);   // delete the replaced photo
                }
                session_regenerate_id(true);
                ev_flash_redirect('profile', 'success', 'Profile updated successfully.');
            }
        }
        $msgs['profile'] = array('error', implode(' ', $errors));
    }

    /* ---------------- Change password ---------------- */
    if (isset($_POST['btn_change_password'])) {
        $errors = array();
        if (!csrf_check()) { $errors[] = 'Session expired. Please try again.'; }

        if (!empty($_SESSION['ev_pw_lock'])) {
            if (time() < $_SESSION['ev_pw_lock']) {
                $rem = ceil(($_SESSION['ev_pw_lock'] - time()) / 60);
                $errors[] = 'Too many failed attempts. Please try again in ' . $rem . ' minute' . ($rem > 1 ? 's' : '') . '.';
            } else {
                unset($_SESSION['ev_pw_lock'], $_SESSION['ev_pw_fail']);
            }
        }

        $cur = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
        $new = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
        $cnf = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

        if (empty($errors)) {
            if ($cur === '') {
                $errors[] = 'Please enter your current password.';
            } elseif ($new === '') {
                $errors[] = 'Please enter a new password.';
            } elseif ($cnf === '') {
                $errors[] = 'Please confirm your new password.';
            } elseif (strlen($new) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif (!hash_equals($new, $cnf)) {
                $errors[] = 'New password and confirmation do not match.';
            } elseif (hash_equals($cur, $new)) {
                $errors[] = 'New password must be different from the current password.';
            } else {
                // Same check as login.php: bcrypt hash, or a legacy plain-text value
                $stored = (string)$user['password'];
                $cur_ok = ($cur !== '') && (password_verify($cur, $stored) || hash_equals($stored, $cur));
                if (!$cur_ok) {
                    $_SESSION['ev_pw_fail'] = (isset($_SESSION['ev_pw_fail']) ? $_SESSION['ev_pw_fail'] : 0) + 1;
                    if ($_SESSION['ev_pw_fail'] >= 5) {
                        $_SESSION['ev_pw_lock'] = time() + 300;
                        $_SESSION['ev_pw_fail'] = 0;
                    }
                    $errors[] = 'Current password is incorrect.';
                }
            }
        }

        if (empty($errors)) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $ok = ev_exec('UPDATE examinees SET password = ? WHERE id = ?', 'si', array($hash, $student_id));
            if ($ok === false) {
                $errors[] = 'Could not change the password. Please try again.';
            } else {
                unset($_SESSION['ev_pw_fail'], $_SESSION['ev_pw_lock']);
                session_regenerate_id(true);
                ev_flash_redirect('password', 'success', 'Password changed successfully.');
            }
        }
        $msgs['password'] = array('error', implode(' ', $errors));
    }
}

$show_name  = isset($old['name']) ? $old['name'] : $user['name'];
$show_phone = isset($old['phone']) ? $old['phone'] : $user['phone'];
$avatar = ev_photo_url($student_id);
if ($avatar === EV_DEFAULT_AVATAR && $current_photo !== '') { $avatar = EV_DEFAULT_AVATAR; }

$page_title = 'My Profile';
include('header.php');

function ev_alert($m) {
    if (!$m) { return ''; }
    return '<div class="ev-alert ev-alert-' . ($m[0] === 'success' ? 'ok' : 'bad') . '" role="alert">' . ev_h($m[1]) . '</div>';
}
?>
<div class="row">
    <div class="col-md-12">
        <h2>My Profile</h2>
        <p class="text-muted">Update your details and keep your account secure.</p>
        <hr>
    </div>
</div>

    <div class="ev-grid-2">
        <section class="ev-card">
            <h3 class="ev-card-title"><i class="fa fa-user" aria-hidden="true"></i> Personal Details</h3>
            <?= ev_alert($msgs['profile']); ?>
            <form method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                <?= csrf_field(); ?>
                <div class="ev-avatar-row">
                    <img class="ev-avatar" src="<?= ev_h($avatar); ?>" alt="Your profile photo">
                    <div class="ev-field">
                        <label for="profile_photo">Profile Photo</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="ev-input">
                        <small class="ev-muted">JPG, PNG or WEBP, max 2 MB.<?= $has_photo_col ? '' : ' (Photo upload needs the database column to be added.)'; ?></small>
                    </div>
                </div>
                <div class="ev-field">
                    <label for="profile_name">Full Name</label>
                    <input type="text" id="profile_name" name="profile_name" class="ev-input" maxlength="100" required value="<?= ev_h($show_name); ?>">
                </div>
                <div class="ev-field">
                    <label for="profile_email">Email</label>
                    <input type="email" id="profile_email" class="ev-input" value="<?= ev_h($user['email']); ?>" readonly disabled>
                    <small class="ev-muted">Email cannot be changed.</small>
                </div>
                <div class="ev-field">
                    <label for="profile_phone">Phone Number</label>
                    <input type="tel" id="profile_phone" name="profile_phone" class="ev-input" maxlength="15" required value="<?= ev_h($show_phone); ?>">
                </div>
                <button type="submit" name="btn_save_profile" class="ev-btn ev-btn-primary ev-btn-block">Save Changes</button>
            </form>
        </section>

        <section class="ev-card">
            <h3 class="ev-card-title"><i class="fa fa-lock" aria-hidden="true"></i> Change Password</h3>
            <?= ev_alert($msgs['password']); ?>
            <form method="POST" autocomplete="off" novalidate>
                <?= csrf_field(); ?>
                <div class="ev-field">
                    <label for="current_password">Current Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="current_password" name="current_password" class="ev-input" autocomplete="current-password" required>
                        <button type="button" id="toggle_current_password" class="toggle-btn" aria-label="Show password" aria-pressed="false"><i class="fa fa-eye" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="ev-field">
                    <label for="new_password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" class="ev-input" autocomplete="new-password" minlength="6" required>
                        <button type="button" id="toggle_new_password" class="toggle-btn" aria-label="Show password" aria-pressed="false"><i class="fa fa-eye" aria-hidden="true"></i></button>
                    </div>
                    <small class="ev-muted">At least 6 characters.</small>
                </div>
                <div class="ev-field">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" class="ev-input" autocomplete="new-password" minlength="6" required>
                        <button type="button" id="toggle_confirm_password" class="toggle-btn" aria-label="Show password" aria-pressed="false"><i class="fa fa-eye" aria-hidden="true"></i></button>
                    </div>
                </div>
                <button type="submit" name="btn_change_password" class="ev-btn ev-btn-primary ev-btn-block">Update Password</button>
            </form>
        </section>
    </div>
</div>

<script>
(function () {
    function setupPasswordToggle(fieldId) {
        var btn = document.getElementById('toggle_' + fieldId);
        var input = document.getElementById(fieldId);
        if (!btn || !input) { return; }
        btn.addEventListener('click', function () {
            var show = input.getAttribute('type') === 'password';
            input.setAttribute('type', show ? 'text' : 'password');
            btn.innerHTML = show ? '<i class="fa fa-eye-slash" aria-hidden="true"></i>' : '<i class="fa fa-eye" aria-hidden="true"></i>';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    }
    setupPasswordToggle('current_password');
    setupPasswordToggle('new_password');
    setupPasswordToggle('confirm_password');
})();
</script>
<?php include('footer.php'); ?>