<?php
/* examiner/profile.php - Examiner profile: photo, personal info, professional details, certificates, password */
require('../config/autoload.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['examiner_id'])) { header('Location: login.php'); exit(); }

$dao = new DataAccess();
$eid = (int)$_SESSION['examiner_id'];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$msgs = array(); $errs = array();
$maxCerts = 5; $maxSize = 2 * 1024 * 1024;

/* ---------- load current data ---------- */
function load_examiner($dao, $eid) {
    $a = $dao->getData('*', 'examiners', 'id = ' . $eid);
    $b = $dao->getData('*', 'examiner_profiles', 'examiner_id = ' . $eid);
    $c = $dao->getData('*', 'examiner_certificates', 'examiner_id = ' . $eid . ' ORDER BY uploaded_at DESC');
    return array(!empty($a) ? $a[0] : array(), !empty($b) ? $b[0] : array(), is_array($c) ? $c : array());
}
list($ex, $pr, $certs) = load_examiner($dao, $eid);
if (empty($ex)) { header('Location: login.php'); exit(); }

/* category name (read-only) */
$catRow = $dao->getData('*', 'categories', 'id = ' . (int)$ex['category_id']);
$categoryName = !empty($catRow) ? $catRow[0]['name'] : '-';

/* ---------- 1. Personal details + photo ---------- */
if (isset($_POST['save_personal'])) {
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city  = trim($_POST['city'] ?? '');
    if (strlen($name) < 3 || strlen($name) > 50)       $errs[] = 'Name must be 3-50 characters.';
    if ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) $errs[] = 'Phone must be 10 digits.';
    if (strlen($city) > 100)                           $errs[] = 'City is too long.';

    $photoName = !empty($ex['profile_photo']) ? $ex['profile_photo'] : null;
    $uploadedFile = !empty($_FILES['photo']['name']) ? $_FILES['photo'] : (!empty($_FILES['profile_photo']['name']) ? $_FILES['profile_photo'] : null);

    if ($uploadedFile && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE) {
        $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $errs[] = 'Photo upload failed. Please try again.';
        } elseif (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp'))) {
            $errs[] = 'Photo must be JPG, PNG or WEBP.';
        } elseif ($uploadedFile['size'] > $maxSize) {
            $errs[] = 'Photo must be under 2 MB.';
        } else {
            $dir = __DIR__ . '/../uploads/profile/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $new = 'examiner_' . $eid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($uploadedFile['tmp_name'], $dir . $new)) {
                if ($photoName && is_file($dir . $photoName)) {
                    @unlink($dir . $photoName);
                }
                $photoName = $new;
            } else {
                $errs[] = 'Could not save the photo to server.';
            }
        }
    }

    if (!$errs) {
        $updateData = array('name' => $name, 'phone' => $phone);
        if ($photoName !== null) {
            $updateData['profile_photo'] = $photoName;
            $_SESSION['profile_photo'] = $photoName;
        }
        $dao->update($updateData, 'examiners', 'id = ' . $eid);
        $dao->update(array('city' => $city), 'examiner_profiles', 'examiner_id = ' . $eid);
        $_SESSION['name'] = $name;
        $msgs[] = 'Personal details and profile photo updated successfully.';
    }
}

/* ---------- 2. Professional details ---------- */
if (isset($_POST['save_professional'])) {
    $designation = trim($_POST['designation'] ?? '');
    $spec        = trim($_POST['specialization'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $year        = (int)($_POST['year_passed'] ?? 0);
    $exp         = (int)($_POST['experience_years'] ?? 0);
    $linkedin    = trim($_POST['linkedin_url'] ?? '');
    $about       = trim($_POST['about'] ?? '');
    $thisYear    = (int)date('Y');

    if (strlen($designation) > 100)                         $errs[] = 'Designation is too long.';
    if (strlen($spec) > 100)                                $errs[] = 'Specialization is too long.';
    if (strlen($institution) < 2 || strlen($institution) > 150) $errs[] = 'Enter the college / university name.';
    if ($year < 1970 || $year > $thisYear)                  $errs[] = "Year of passing must be 1970 - $thisYear.";
    if ($exp < 0 || $exp > 60)                              $errs[] = 'Experience must be 0-60 years.';
    if (strlen($about) > 500)                               $errs[] = 'About can be at most 500 characters.';
    if ($linkedin !== '' && (!filter_var($linkedin, FILTER_VALIDATE_URL) || stripos($linkedin, 'linkedin.com') === false || strlen($linkedin) > 255))
                                                            $errs[] = 'Enter a valid LinkedIn URL.';
    if (!$errs) {
        $dao->update(array(
            'designation' => $designation, 'specialization' => $spec, 'institution' => $institution,
            'year_passed' => $year, 'experience_years' => $exp, 'linkedin_url' => $linkedin, 'about' => $about
        ), 'examiner_profiles', 'examiner_id = ' . $eid);
        $msgs[] = 'Professional details updated.';
    }
}

/* ---------- 3. Certificates: add / delete ---------- */
if (isset($_POST['add_cert'])) {
    $t = trim($_POST['cert_title'] ?? ''); $iss = trim($_POST['cert_issuer'] ?? '');
    $ext = strtolower(pathinfo($_FILES['cert_file']['name'] ?? '', PATHINFO_EXTENSION));
    if (count($certs) >= $maxCerts)                         $errs[] = "You can keep at most $maxCerts certificates.";
    elseif ($t === '' || strlen($t) > 150 || strlen($iss) > 150) $errs[] = 'Enter a certificate title (max 150 characters).';
    elseif (empty($_FILES['cert_file']['name']) || $_FILES['cert_file']['error'] !== UPLOAD_ERR_OK) $errs[] = 'Attach the certificate file.';
    elseif (!in_array($ext, array('pdf','jpg','jpeg','png'))) $errs[] = 'Certificate must be PDF, JPG or PNG.';
    elseif ($_FILES['cert_file']['size'] > $maxSize)        $errs[] = 'Certificate must be under 2 MB.';
    else {
        $ok = true;
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $ok = in_array(finfo_file($fi, $_FILES['cert_file']['tmp_name']), array('application/pdf','image/jpeg','image/png'));
        }
        if (!$ok) $errs[] = 'The file content is not a valid PDF / JPG / PNG.';
        else {
            $dir = '../uploads/certificates/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = 'cert_' . $eid . '_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['cert_file']['tmp_name'], $dir . $fname)) {
                $dao->insert(array('examiner_id' => $eid, 'title' => $t, 'issuer' => $iss, 'file_path' => $fname), 'examiner_certificates');
                $msgs[] = 'Certificate added.';
            } else $errs[] = 'Could not save the certificate.';
        }
    }
}
if (isset($_POST['delete_cert'])) {
    $cid = (int)$_POST['delete_cert'];
    foreach ($certs as $c) {
        if ((int)$c['id'] === $cid) {   // only the owner's own certificate
            @unlink('../uploads/certificates/' . $c['file_path']);
            $dao->delete('examiner_certificates', 'id = ' . $cid . ' AND examiner_id = ' . $eid);
            $msgs[] = 'Certificate removed.';
            break;
        }
    }
}

/* ---------- 4. Change password ---------- */
if (isset($_POST['change_password'])) {
    $cur = $_POST['current_password'] ?? ''; $new = $_POST['new_password'] ?? ''; $con = $_POST['confirm_password'] ?? '';
    if (!password_verify($cur, $ex['password']))  $errs[] = 'Current password is incorrect.';
    elseif (strlen($new) < 6)                     $errs[] = 'New password must be at least 6 characters.';
    elseif ($new !== $con)                        $errs[] = 'New passwords do not match.';
    elseif (password_verify($new, $ex['password'])) $errs[] = 'New password must be different from the current one.';
    else {
        $dao->update(array('password' => password_hash($new, PASSWORD_DEFAULT)), 'examiners', 'id = ' . $eid);
        $msgs[] = 'Password updated.';
    }
}

/* reload after any change */
list($ex, $pr, $certs) = load_examiner($dao, $eid);
$photo = (!empty($ex['profile_photo']) && is_file(__DIR__ . '/../uploads/profile/' . $ex['profile_photo']))
       ? '../uploads/profile/' . htmlspecialchars($ex['profile_photo'], ENT_QUOTES, 'UTF-8') : 'assets/img/find_user.png';

/* quick stats */
$examCount = $dao->getData(array('id'), 'exams', 'created_by = ' . $eid);
$examCount = is_array($examCount) ? count($examCount) : 0;

include("header.php");
?>
<style>
    .pf-card { background:var(--card-bg,#fff); color:var(--card-text,#333); border:1px solid var(--table-border-color,#ddd); border-radius:10px; padding:18px 22px; margin-bottom:22px; }
    .pf-card h4 { margin-top:0; margin-bottom:14px; color:var(--text-primary,#222); }
    .pf-hero { display:flex; gap:20px; align-items:center; flex-wrap:wrap; }
    .pf-hero img { width:96px; height:96px; border-radius:50%; object-fit:cover; border:3px solid #6d5dfc; }
    .pf-stats { display:flex; gap:28px; margin-top:10px; flex-wrap:wrap; }
    .pf-stats b { display:block; font-size:18px; color:var(--text-primary,#222); }
    .pf-stats span { font-size:12px; opacity:.75; }
    .pf-cert { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--table-border-color,#ddd); margin:0; }
    .pf-cert small { display:block; opacity:.7; }
    .pf-card .form-control[readonly] { opacity:.7; cursor:not-allowed; }
    .pf-card label { margin-top:8px; }
</style>

<div class="row">
    <div class="col-md-12">
        <p><a href="dashboard.php">&laquo; Back to dashboard</a></p>
        <h2 style="margin-top:0;">My Profile</h2>
        <p class="text-muted">Keep your details up to date. Admin and examinees see your professional information.</p>
        <hr>

        <?php foreach ($msgs as $m) { echo '<div class="alert alert-success">' . h($m) . '</div>'; } ?>
        <?php foreach ($errs as $e) { echo '<div class="alert alert-danger">' . h($e) . '</div>'; } ?>

        <!-- Summary -->
        <div class="pf-card pf-hero">
            <img src="<?= h($photo) ?>" alt="Profile photo" onerror="this.src='assets/img/find_user.png'">
            <div>
                <h3 style="margin:0;"><?= h($ex['name']) ?></h3>
                <div class="text-muted"><?= h($pr['designation'] ?? '') ?: 'Examiner' ?> &bull; <?= h($ex['email']) ?></div>
                <div style="margin-top:8px;">
                    <span class="label label-primary"><?= h($categoryName) ?></span>
                    <span class="label label-info"><?= h($pr['degree_name'] ?? '') ?></span>
                    <span class="label label-success"><?= h(ucfirst($ex['status'])) ?></span>
                </div>
                <div class="pf-stats">
                    <div><b><?= $examCount ?></b><span>Exams created</span></div>
                    <div><b><?= (int)($pr['experience_years'] ?? 0) ?> yrs</b><span>Experience</span></div>
                    <div><b><?= count($certs) ?></b><span>Certificates</span></div>
                    <div><b><?= date('M Y', strtotime($ex['created_at'])) ?></b><span>Member since</span></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <!-- Personal -->
                <form method="POST" enctype="multipart/form-data" class="pf-card">
                    <h4><i class="fa fa-user"></i> Personal Details</h4>
                    <div class="form-group" style="display:flex; gap:16px; align-items:center; margin-bottom:18px;">
                        <img id="avatarPreview" src="<?= h($photo) ?>" alt="Current Avatar" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:3px solid var(--accent-cyan, #38bdf8); box-shadow:0 0 10px rgba(56,189,248,0.3); flex-shrink:0;" onerror="this.src='assets/img/find_user.png'">
                        <div style="flex:1;">
                            <label style="margin-top:0;">Profile Photo</label>
                            <input type="file" name="photo" id="profile_photo" accept="image/jpeg,image/png,image/webp" class="form-control">
                            <p class="help-block" style="margin-bottom:0; font-size:12px;">JPG, PNG or WEBP, max 2 MB.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input class="form-control" name="name" maxlength="50" required value="<?= h($ex['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" value="<?= h($ex['email']) ?>" readonly>
                        <p class="help-block">Email cannot be changed.</p>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 form-group">
                            <label>Phone Number</label>
                            <input class="form-control" name="phone" maxlength="10" pattern="[0-9]{10}" placeholder="10 digit number" value="<?= h($ex['phone'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6 form-group">
                            <label>City</label>
                            <input class="form-control" name="city" maxlength="100" value="<?= h($pr['city'] ?? '') ?>">
                        </div>
                    </div>
                    <button class="btn btn-primary btn-block" name="save_personal" type="submit">Save Changes</button>
                </form>

                <!-- Professional -->
                <form method="POST" class="pf-card">
                    <h4><i class="fa fa-briefcase"></i> Professional Details</h4>
                    <div class="form-group">
                        <label>Category</label>
                        <input class="form-control" value="<?= h($categoryName) ?>" readonly>
                        <p class="help-block">You can create exams only for courses in this category. Contact Admin to change it.</p>
                    </div>
                    <div class="form-group">
                        <label>Degree</label>
                        <input class="form-control" value="<?= h($pr['degree_name'] ?? '') ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Designation</label>
                        <input class="form-control" name="designation" maxlength="100" placeholder="e.g. Assistant Professor" value="<?= h($pr['designation'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Specialization</label>
                        <input class="form-control" name="specialization" maxlength="100" value="<?= h($pr['specialization'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>College / University</label>
                        <input class="form-control" name="institution" maxlength="150" required value="<?= h($pr['institution'] ?? '') ?>">
                    </div>
                    <div class="row">
                        <div class="col-sm-6 form-group">
                            <label>Year of passing</label>
                            <input class="form-control" type="number" name="year_passed" min="1970" max="<?= date('Y') ?>" required value="<?= h($pr['year_passed'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6 form-group">
                            <label>Experience (years)</label>
                            <input class="form-control" type="number" name="experience_years" min="0" max="60" required value="<?= h($pr['experience_years'] ?? 0) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>LinkedIn</label>
                        <input class="form-control" name="linkedin_url" maxlength="255" placeholder="https://www.linkedin.com/in/..." value="<?= h($pr['linkedin_url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>About</label>
                        <textarea class="form-control" name="about" rows="4" maxlength="500"><?= h($pr['about'] ?? '') ?></textarea>
                    </div>
                    <button class="btn btn-primary btn-block" name="save_professional" type="submit">Save Professional Details</button>
                </form>
            </div>

            <div class="col-md-6">
                <!-- Certificates -->
                <div class="pf-card">
                    <h4><i class="fa fa-certificate"></i> Certificates (<?= count($certs) ?>/<?= $maxCerts ?>)</h4>
                    <?php if (!$certs) { echo '<p class="text-muted">No certificates uploaded yet.</p>'; } ?>
                    <?php foreach ($certs as $c) { ?>
                        <form method="POST" class="pf-cert" onsubmit="return confirm('Remove this certificate?');">
                            <div>
                                <a href="../uploads/certificates/<?= h($c['file_path']) ?>" target="_blank" rel="noopener"><strong><?= h($c['title']) ?></strong></a>
                                <small><?= h($c['issuer']) ?: 'Issuer not given' ?> &bull; <?= date('d M Y', strtotime($c['uploaded_at'])) ?></small>
                            </div>
                            <button class="btn btn-danger btn-xs" name="delete_cert" value="<?= (int)$c['id'] ?>" type="submit">Remove</button>
                        </form>
                    <?php } ?>

                    <?php if (count($certs) < $maxCerts) { ?>
                    <form method="POST" enctype="multipart/form-data" style="margin-top:16px;">
                        <label>Add certificate</label>
                        <input class="form-control" name="cert_title" maxlength="150" placeholder="Title" required>
                        <input class="form-control" name="cert_issuer" maxlength="150" placeholder="Issued by (optional)" style="margin-top:8px;">
                        <input class="form-control" type="file" name="cert_file" accept=".pdf,.jpg,.jpeg,.png" required style="margin-top:8px;">
                        <p class="help-block">PDF, JPG or PNG, max 2 MB.</p>
                        <button class="btn btn-primary btn-block" name="add_cert" type="submit">Upload Certificate</button>
                    </form>
                    <?php } ?>
                </div>

                <!-- Password -->
                <form method="POST" class="pf-card">
                    <h4><i class="fa fa-lock"></i> Change Password</h4>
                    <div class="form-group">
                        <label>Current Password</label>
                        <input class="form-control" type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input class="form-control" type="password" name="new_password" minlength="6" required>
                        <p class="help-block">At least 6 characters.</p>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input class="form-control" type="password" name="confirm_password" minlength="6" required>
                    </div>
                    <button class="btn btn-primary btn-block" name="change_password" type="submit">Update Password</button>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var photoInput = document.getElementById('profile_photo');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            var file = e.target.files && e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(evt) {
                    var previews = document.querySelectorAll('.pf-hero img, #avatarPreview');
                    previews.forEach(function(img) { img.src = evt.target.result; });
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php include('footer.php'); ?>