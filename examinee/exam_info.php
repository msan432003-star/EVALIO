<?php 
/* exam_info.php - public details page for one certification exam (same look as index.php) */
require_once('../config/autoload.php'); 

if(!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$login_page = 'login.php'; // <-- change to your student login page name

$dao = new DataAccess(); 
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Only approved exams (both certification & published mock exams) can be viewed
$rows = $dao->getData('*', 'exams', "id = " . $exam_id . " AND status = 'approved'");
if(empty($rows)) {
    echo "<script>alert('Exam not found.'); window.location.href='exams.php';</script>";
    exit();
}
$ex = $rows[0];
$is_mock = (strtolower(trim($ex['category'] ?? '')) === 'mock' || strtolower(trim($ex['exam_type'] ?? '')) === 'mock');

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

$pageTitle = "Exam Details - ExamPortal";
include('includes/header.php');
?>

<style>
    .exam-public-card { text-align: left; max-width: 800px; margin: 0 auto; }
    .exam-public-img { width: 100%; height: 300px; object-fit: cover; display: block; border-radius: 8px; margin-bottom: 15px; }
    .exam-public-card h3 { margin: 0 0 8px 0; }
    .exam-public-desc { color: #ccc; font-size: 15px; margin: 8px 0 15px 0; }
    .exam-public-meta { font-size: 14px; margin: 4px 0; }
    .exam-public-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; color: #fff; background: #5124f1; }
    .exam-public-btn { display: block; text-align: center; margin-top: 15px; }
</style>

<section class="section">
    <h2 class="section-title">Exam Details</h2>
    <div class="card-custom exam-public-card">
        <img src="<?= exam_card_image($ex); ?>" class="exam-public-img" alt="<?= e($ex['title']); ?>">
        <h3><?= e($ex['title']); ?></h3>

        <p class="exam-public-desc">
            <?= !empty($ex['description']) ? nl2br(htmlspecialchars((string)$ex['description'], ENT_QUOTES, 'UTF-8')) : 'No description provided for this exam.'; ?>
        </p>

        <p class="exam-public-meta"><strong>Course:</strong> <?= e($course_name); ?></p>
        <?php if(!$is_mock) { ?>
            <p class="exam-public-meta"><strong>By:</strong> Prof <?= e($examiner_name); ?></p>
        <?php } ?>
        <p class="exam-public-meta"><strong>Category:</strong> <span class="exam-public-badge<?= $is_mock ? ' mock' : ''; ?>"><?= e($ex['category']); ?></span></p>
        <p class="exam-public-meta"><strong>Duration:</strong> <?= (int)$ex['duration_minutes']; ?> Minutes</p>
        <?php if(!$is_mock) { ?>
            <?php if(!empty($ex['registration_deadline'])) { ?>
                <p class="exam-public-meta"><strong>Registration Deadline:</strong> <?= date('d M Y, h:i A', strtotime($ex['registration_deadline'])); ?></p>
            <?php } ?>
            <?php if(!empty($ex['exam_start_datetime'])) { ?>
                <p class="exam-public-meta"><strong>Starts At:</strong> <?= date('d M Y, h:i A', strtotime($ex['exam_start_datetime'])); ?></p>
            <?php } ?>
            <p class="exam-public-meta"><strong>Fee:</strong> &#8377;<?= number_format($ex['price'], 2); ?></p>
        <?php } ?>

        <div style="margin-top: 24px; text-align: center;">
            <a href="exams.php" class="nav-btn nav-btn-primary" style="display: inline-block; padding: 10px 28px; text-decoration:none;"><i class="fa fa-arrow-left"></i> Back to Exams</a>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>