<?php 
/* exam_details.php */
require('../config/autoload.php'); 

// e() fallback in case it lives in auth_helper.php
if(!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Guest view (?guest=1) is read-only; otherwise the student must be logged in
$is_guest = isset($_GET['guest']);
if(!$is_guest) {
    require('auth_helper.php');
    $student_id = require_login();
} else {
    $student_id = 0;
}

$dao = new DataAccess(); 

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Only approved certification exams can be viewed here
$rows = $dao->getData('*', 'exams', "id = " . $exam_id . " AND status = 'approved' AND LOWER(category) NOT LIKE '%mock%'");
if(empty($rows)) {
    $back = $is_guest ? 'exams.php' : 'student_exams.php';
    echo "<script>alert('Exam not found.'); window.location.href='" . $back . "';</script>";
    exit();
}
$ex = $rows[0];

// Already registered?
$is_registered = false;
if(!$is_guest) {
    $registrations = $dao->getData('*', 'registrations', 'student_id = ' . $student_id . ' AND exam_id = ' . $exam_id);
    $is_registered = !empty($registrations);
}

// Course name
$course_name = 'N/A';
$all_courses = $dao->getData('*', 'courses', '1');
if(!empty($all_courses)) {
    foreach($all_courses as $c) {
        $cid = $c['id'] ?? ($c['course_id'] ?? 0);
        if((int)$cid === (int)$ex['course_id']) {
            $course_name = $c['course_name'] ?? ($c['name'] ?? 'N/A');
            break;
        }
    }
}

// Professor name
$examiner_name = 'N/A';
$all_examiners = $dao->getData('*', 'examiners', '1');
if(!empty($all_examiners)) {
    foreach($all_examiners as $ex2) {
        $eid = $ex2['id'] ?? ($ex2['examiner_id'] ?? 0);
        if((int)$eid === (int)$ex['created_by']) {
            $examiner_name = $ex2['name'] ?? ($ex2['examiner_name'] ?? ($ex2['fullname'] ?? 'N/A'));
            break;
        }
    }
}

function exam_card_image($ex) {
    if (!empty($ex['image'])) {
        return '../uploads/exams/' . htmlspecialchars((string)$ex['image'], ENT_QUOTES, 'UTF-8');
    }
    return 'login/images/img2.jpg';
}

$page_title = 'Exam Details';
include("header.php"); 
?>

<style>
    .exam-card {
        background: var(--card-bg) !important;
        border: 1px solid var(--card-border) !important;
        border-radius: 14px !important;
        overflow: hidden;
        box-shadow: var(--card-shadow) !important;
        margin-bottom: 25px;
        height: 100%;
        color: var(--text-primary);
    }
    .exam-card-img {
        width: 100%;
        height: 280px;
        object-fit: cover;
        display: block;
    }
    .exam-card-body {
        padding: 22px;
    }
    .exam-card-title {
        margin: 0 0 12px 0;
        font-weight: 700;
        font-size: 18px;
        color: var(--text-primary);
    }
    .exam-card-desc {
        color: var(--text-muted);
        font-size: 14px;
        line-height: 1.6;
        margin: 8px 0 16px 0;
    }
    .exam-card-meta {
        font-size: 13px;
        margin-bottom: 8px;
        color: var(--text-muted);
    }
    .exam-card-meta strong {
        color: var(--text-primary);
    }
    .exam-card-footer {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid var(--card-border);
    }
</style>

<div class="row">
    <div class="col-md-12">
        <h2>Exam Details</h2>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="exam-card">
            <img src="<?= exam_card_image($ex); ?>" class="exam-card-img" style="height:300px;" alt="<?= e($ex['title']); ?>">
            <div class="exam-card-body">
                <h4 class="exam-card-title"><?= e($ex['title']); ?></h4>

                <p class="exam-card-desc">
                    <?= !empty($ex['description']) ? nl2br(htmlspecialchars((string)$ex['description'], ENT_QUOTES, 'UTF-8')) : 'No description provided for this exam.'; ?>
                </p>

                <p class="exam-card-meta"><strong>Course:</strong> <?= e($course_name); ?></p>
                <p class="exam-card-meta"><strong>By:</strong> Prof <?= e($examiner_name); ?></p>
                <p class="exam-card-meta"><strong>Category:</strong> <span class="label label-info"><?= e($ex['category']); ?></span></p>
                <p class="exam-card-meta"><strong>Duration:</strong> <?= (int)$ex['duration_minutes']; ?> Minutes</p>
                <p class="exam-card-meta"><strong>Registration Deadline:</strong> <?= date('d M Y, h:i A', strtotime($ex['registration_deadline'])); ?></p>
                <p class="exam-card-meta"><strong>Starts At:</strong> <span class="text-danger"><?= date('d M Y, h:i A', strtotime($ex['exam_start_datetime'])); ?></span></p>
                <p class="exam-card-meta"><strong>Fee:</strong> &#8377;<?= number_format($ex['price'], 2); ?></p>

                <div class="exam-card-footer">
                    <?php if($is_guest) { ?>
                        <a href="<?= $login_page; ?>" class="btn btn-primary btn-block">Login to Register</a>
                    <?php } elseif($is_registered) { ?>
                        <a href="my_exams.php" class="btn btn-default btn-block">Registered - Go to My Exams</a>
                    <?php } else { ?>
                        <a href="register_exam.php?exam_id=<?= (int)$ex['id']; ?>" class="btn btn-primary btn-block">Register</a>
                    <?php } ?>
                    <a href="<?= $is_guest ? 'exams.php' : 'student_exams.php'; ?>" class="btn btn-default btn-block">Back to All Exams</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('footer.php'); ?>