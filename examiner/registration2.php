<?php
/* registration2.php - STEP 2 of examiner registration: qualifications (mini resume) + certificates */
require('../config/autoload.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$dao = new DataAccess();

// Step 1 must be completed first
if (empty($_SESSION['reg_step1']) || !is_array($_SESSION['reg_step1'])) {
    header('Location: registration1.php');
    exit();
}
$step1 = $_SESSION['reg_step1'];

function reg_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function reg_old($k) { return isset($_POST[$k]) ? reg_h($_POST[$k]) : ''; }

$categories = $dao->getData('*', 'categories', '1=1 ORDER BY name');
if (!is_array($categories)) { $categories = array(); }

$allDegrees = $dao->getData('*', 'degrees', '1=1 ORDER BY name');
if (!is_array($allDegrees)) { $allDegrees = array(); }
$degreesByCat = array();
foreach ($allDegrees as $d) { $degreesByCat[(int)$d['category_id']][] = array('id' => (int)$d['id'], 'name' => $d['name']); }
$oldDegree = isset($_POST['degree_id']) ? (string)$_POST['degree_id'] : '';

$errors      = array();
$maxCerts    = 5;
$maxSize     = 2 * 1024 * 1024; // 2 MB per certificate
$allowedExt  = array('pdf', 'jpg', 'jpeg', 'png');
$allowedMime = array('application/pdf', 'image/jpeg', 'image/png');
$thisYear    = (int)date('Y');

if (isset($_POST['register'])) {

    // ---------- Qualification fields ----------
    $degree_post  = isset($_POST['degree_id']) ? (string)$_POST['degree_id'] : '';
    $degree_other = trim(isset($_POST['degree_other']) ? $_POST['degree_other'] : '');
    $degree       = '';
    $degreeIdSave = null;
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $spec        = trim(isset($_POST['specialization']) ? $_POST['specialization'] : '');
    $institution = trim(isset($_POST['institution']) ? $_POST['institution'] : '');
    $year        = isset($_POST['year_passed']) ? (int)$_POST['year_passed'] : 0;
    $experience  = isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
    $about       = trim(isset($_POST['about']) ? $_POST['about'] : '');

    if (strlen($spec) > 100)                                    { $errors[] = 'Specialization is too long (max 100 characters).'; }
    if (strlen($institution) < 2 || strlen($institution) > 150) { $errors[] = 'Enter the college / university name.'; }
    if ($year < 1970 || $year > $thisYear)                      { $errors[] = 'Enter a valid year of passing (1970 - ' . $thisYear . ').'; }
    if ($experience < 0 || $experience > 60)                    { $errors[] = 'Experience must be between 0 and 60 years.'; }
    if (strlen($about) > 500)                                   { $errors[] = 'The short summary can be at most 500 characters.'; }

    $catOk = false;
    foreach ($categories as $c) { if ((int)$c['id'] === $category_id) { $catOk = true; break; } }
    if (!$catOk) {
        $errors[] = 'Select your category.';
    } elseif ($degree_post === 'other') {
        $degree = $degree_other;
        if (strlen($degree) < 2 || strlen($degree) > 100) { $errors[] = 'Enter your degree name.'; }
    } else {
        $did = (int)$degree_post;
        $found = false;
        foreach ($allDegrees as $d) {
            if ((int)$d['id'] === $did && (int)$d['category_id'] === $category_id) {
                $found = true; $degree = $d['name']; $degreeIdSave = $did; break;
            }
        }
        if (!$found) { $errors[] = 'Select your degree from the list.'; }
    }

    // ---------- Certificates (optional, but each one needs a title and a file) ----------
    $certs   = array();
    $titles  = (isset($_POST['cert_title']) && is_array($_POST['cert_title'])) ? $_POST['cert_title'] : array();
    $issuers = (isset($_POST['cert_issuer']) && is_array($_POST['cert_issuer'])) ? $_POST['cert_issuer'] : array();

    foreach ($titles as $i => $t) {
        $t   = trim($t);
        $iss = trim(isset($issuers[$i]) ? $issuers[$i] : '');
        $hasFile = isset($_FILES['cert_file']['error'][$i]) && $_FILES['cert_file']['error'][$i] !== UPLOAD_ERR_NO_FILE;
        $n = (int)$i + 1;

        if ($t === '' && !$hasFile) { continue; } // empty row
        if (count($certs) >= $maxCerts) { $errors[] = 'You can upload at most ' . $maxCerts . ' certificates.'; break; }
        if ($t === '')    { $errors[] = 'Certificate ' . $n . ': enter a title.'; continue; }
        if (!$hasFile)    { $errors[] = 'Certificate ' . $n . ': attach the file.'; continue; }
        if ($_FILES['cert_file']['error'][$i] !== UPLOAD_ERR_OK) { $errors[] = 'Certificate ' . $n . ': the upload failed, please try again.'; continue; }
        if (strlen($t) > 150 || strlen($iss) > 150) { $errors[] = 'Certificate ' . $n . ': title / issuer is too long.'; continue; }

        $tmp  = $_FILES['cert_file']['tmp_name'][$i];
        $ext  = strtolower(pathinfo($_FILES['cert_file']['name'][$i], PATHINFO_EXTENSION));
        $size = (int)$_FILES['cert_file']['size'][$i];

        if (!in_array($ext, $allowedExt)) { $errors[] = 'Certificate ' . $n . ': only PDF, JPG or PNG files are allowed.'; continue; }
        if ($size > $maxSize)             { $errors[] = 'Certificate ' . $n . ': the file is larger than 2 MB.'; continue; }
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $tmp);
            finfo_close($fi);
            if (!in_array($mime, $allowedMime)) { $errors[] = 'Certificate ' . $n . ': the file content is not a valid PDF / JPG / PNG.'; continue; }
        }
        $certs[] = array('title' => $t, 'issuer' => $iss, 'tmp' => $tmp, 'ext' => $ext);
    }

    // ---------- Save ----------
    if (empty($errors)) {
        $emailCond = 'email = "' . addslashes($step1['email']) . '"';

        $exists = $dao->getData(array('id'), 'examiners', $emailCond);
        if (!empty($exists)) {
            unset($_SESSION['reg_step1']);
            echo "<script>alert('This email was registered in the meantime. Please start again with another email.'); location.replace('registration1.php');</script>";
            exit();
        }

        $examiner = array(
            'name'        => $step1['name'],
            'email'       => $step1['email'],
            'phone'       => !empty($step1['phone']) ? $step1['phone'] : null,
            'password'    => $step1['password'],
            'status'      => 'pending',   // waits for admin approval
            'category_id' => $category_id
        );

        $saved = false;
        if ($dao->insert($examiner, 'examiners')) {
            $row = $dao->getData(array('id'), 'examiners', $emailCond);
            $new_id = !empty($row) ? (int)$row[0]['id'] : 0;

            if ($new_id > 0) {
                $profile = array(
                    'examiner_id'      => $new_id,
                    'degree_name'      => $degree,
                    'specialization'   => $spec,
                    'institution'      => $institution,
                    'year_passed'      => $year,
                    'experience_years' => $experience,
                    'about'            => $about
                );
                if ($degreeIdSave !== null) { $profile['degree_id'] = $degreeIdSave; }
                $dao->insert($profile, 'examiner_profiles');

                if (!empty($certs)) {
                    $dir = '../uploads/certificates/';
                    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
                    foreach ($certs as $c) {
                        $fname = 'cert_' . $new_id . '_' . time() . '_' . uniqid() . '.' . $c['ext'];
                        if (move_uploaded_file($c['tmp'], $dir . $fname)) {
                            $dao->insert(array(
                                'examiner_id' => $new_id,
                                'title'       => $c['title'],
                                'issuer'      => $c['issuer'],
                                'file_path'   => $fname
                            ), 'examiner_certificates');
                        }
                    }
                }
                $saved = true;
            }
        }

        if ($saved) {
            unset($_SESSION['reg_step1']);
            echo "<script> alert('Registration submitted successfully! Please wait for Admin approval.'); location.replace('login.php'); </script>";
            exit();
        }
        $errors[] = 'Registration failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Evalio | Examiner Registration - Qualifications</title>
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
    <style>
        .reg-section { margin: 18px 0 8px; font-size: 14px; font-weight: 600; color: var(--text-auth-muted); text-transform: uppercase; letter-spacing: .5px; }
        .reg-row { display: flex; gap: 12px; }
        .reg-row > div { flex: 1; min-width: 0; }
        .reg-plain { padding-left: 14px !important; }
        .reg-cert { border: 1px dashed rgba(148,163,184,.5); border-radius: 10px; padding: 12px; margin-bottom: 12px; position: relative; }
        .reg-cert .reg-remove { position: absolute; top: 6px; right: 10px; background: none; border: none; color: #ef4444; font-size: 18px; cursor: pointer; }
        .reg-hint { font-size: 12px; color: var(--text-auth-muted); margin: 2px 0 8px; }
        .reg-add { background: none; border: 1px solid rgba(148,163,184,.6); color: inherit; border-radius: 8px; padding: 6px 14px; cursor: pointer; font-size: 13px; }
        .reg-back { display: inline-block; margin-top: 14px; }
    </style>
</head>

<body>
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
            <div class="auth-card-media" style="background-image: url('login/images/img2.jpg');"></div>

            <div class="auth-card-form">
                <div class="auth-header">
                    <h1 class="auth-title">YOUR QUALIFICATIONS</h1>
                    <p style="text-align:center; font-size:13px; margin:6px 0 0; color: var(--text-auth-muted);">Step 2 of 2 &mdash; Hello <?= reg_h($step1['name']); ?>, tell us about your education</p>
                </div>

                <form method="POST" class="auth-form" id="qualForm" enctype="multipart/form-data" autocomplete="off">
                    <?php if (!empty($errors)) { ?>
                        <div class="auth-alert-box">
                            <i class="fa fa-exclamation-triangle"></i>
                            <?php foreach ($errors as $er) { echo '<div>' . reg_h($er) . '</div>'; } ?>
                        </div>
                    <?php } ?>

                    <div class="reg-section">Education</div>

                    <div class="auth-input-group">
                        <select name="category_id" id="categorySelect" class="auth-input reg-plain" required>
                            <option value="">Category (your subject area)</option>
                            <?php foreach ($categories as $c) { ?>
                                <option value="<?= (int)$c['id']; ?>" <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$c['id']) ? 'selected' : ''; ?>><?= reg_h($c['name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="reg-hint">You will only be able to create exams for courses in this category.</div>

                    <div class="auth-input-group">
                        <select name="degree_id" id="degreeSelect" class="auth-input reg-plain" required disabled>
                            <option value="">Select a category first</option>
                        </select>
                    </div>

                    <div class="auth-input-group" id="degreeOtherBox" style="display:none;">
                        <i class="fa fa-graduation-cap auth-input-icon"></i>
                        <input type="text" name="degree_other" id="degreeOther" class="auth-input" placeholder="Type your degree (e.g. MCA)" maxlength="100" value="<?= reg_old('degree_other'); ?>">
                    </div>

                    <div class="auth-input-group">
                        <i class="fa fa-book auth-input-icon"></i>
                        <input type="text" name="specialization" class="auth-input" placeholder="Specialization (optional)" maxlength="100" value="<?= reg_old('specialization'); ?>">
                    </div>

                    <div class="auth-input-group">
                        <i class="fa fa-university auth-input-icon"></i>
                        <input type="text" name="institution" class="auth-input" placeholder="College / University" maxlength="150" required value="<?= reg_old('institution'); ?>">
                    </div>

                    <div class="reg-row">
                        <div class="auth-input-group">
                            <input type="number" name="year_passed" class="auth-input reg-plain" placeholder="Year of passing" min="1970" max="<?= $thisYear; ?>" required value="<?= reg_old('year_passed'); ?>">
                        </div>
                        <div class="auth-input-group">
                            <input type="number" name="experience_years" class="auth-input reg-plain" placeholder="Experience (years)" min="0" max="60" required value="<?= isset($_POST['experience_years']) ? reg_old('experience_years') : '0'; ?>">
                        </div>
                    </div>

                    <div class="auth-input-group">
                        <textarea name="about" class="auth-input reg-plain" rows="3" maxlength="500" placeholder="Short summary: skills, other qualifications (optional)"><?= reg_old('about'); ?></textarea>
                    </div>

                    <div class="reg-section">Certificates (optional)</div>
                    <div class="reg-hint">PDF, JPG or PNG, max 2 MB each, up to <?= $maxCerts; ?> files.</div>
                    <div id="certList"></div>
                    <button type="button" class="reg-add" id="btnAddCert"><i class="fa fa-plus"></i> Add certificate</button>

                    <div style="margin-top: 24px;">
                        <button class="auth-btn auth-btn-primary" name="register" type="submit">
                            <i class="fa fa-user-plus"></i> Register
                        </button>
                    </div>

                    <a href="registration1.php" class="auth-link reg-back"><i class="fa fa-arrow-left"></i> Back to step 1</a>
                </form>
            </div>
        </div>
    </div>

    <script>
    var MAX_CERTS = <?= (int)$maxCerts; ?>;

    // ---- Category -> Degree dropdown ----
    var DEGREES    = <?= json_encode($degreesByCat, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var OLD_DEGREE = <?= json_encode($oldDegree, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var catSel   = document.getElementById('categorySelect');
    var degSel   = document.getElementById('degreeSelect');
    var otherBox = document.getElementById('degreeOtherBox');
    var otherInp = document.getElementById('degreeOther');

    function loadDegrees(selected) {
        var list = DEGREES[catSel.value] || [];
        degSel.innerHTML = '';
        otherBox.style.display = 'none';
        otherInp.required = false;

        if (!catSel.value) {
            degSel.add(new Option('Select a category first', ''));
            degSel.disabled = true;
            return;
        }
        degSel.disabled = false;
        degSel.add(new Option('Select your degree', ''));
        list.forEach(function (d) { degSel.add(new Option(d.name, d.id)); });
        degSel.add(new Option('Other (not listed)', 'other'));
        if (selected) { degSel.value = selected; }
        toggleOther();
    }
    function toggleOther() {
        var show = degSel.value === 'other';
        otherBox.style.display = show ? '' : 'none';
        otherInp.required = show;
    }
    catSel.addEventListener('change', function () { loadDegrees(''); });
    degSel.addEventListener('change', toggleOther);
    loadDegrees(OLD_DEGREE);

    function certCount() { return document.querySelectorAll('#certList .reg-cert').length; }

    function addCertRow() {
        if (certCount() >= MAX_CERTS) { alert('You can add at most ' + MAX_CERTS + ' certificates.'); return; }
        var div = document.createElement('div');
        div.className = 'reg-cert';
        div.innerHTML =
            '<button type="button" class="reg-remove" title="Remove" onclick="this.parentNode.remove()">&times;</button>' +
            '<div class="auth-input-group"><input type="text" name="cert_title[]" class="auth-input reg-plain" placeholder="Certificate title" maxlength="150"></div>' +
            '<div class="auth-input-group"><input type="text" name="cert_issuer[]" class="auth-input reg-plain" placeholder="Issued by (optional)" maxlength="150"></div>' +
            '<input type="file" name="cert_file[]" accept=".pdf,.jpg,.jpeg,.png" style="font-size:13px;">';
        document.getElementById('certList').appendChild(div);
    }
    document.getElementById('btnAddCert').addEventListener('click', addCertRow);
    addCertRow(); // start with one empty row

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
    }

    (function() {
        updateToggleUI(document.documentElement.getAttribute('data-theme') || 'dark');
    })();
    </script>
</body>
</html>
