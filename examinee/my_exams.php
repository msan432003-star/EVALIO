<?php 
require('../config/autoload.php'); 
require('auth_helper.php');
$student_id = require_login();

$dao = new DataAccess(); 
$current_time = time();

// Fetch registrations for this student
$registrations = $dao->getData('*', 'registrations', 'student_id = ' . $student_id . ' AND payment_status = "Paid"');

// Fetch all results of this student once (instead of one query per exam)
$results_by_exam = array();
$all_results = $dao->getData('*', 'exam_results', 'student_id = ' . $student_id);
if(!empty($all_results)) {
    foreach($all_results as $r) {
        $results_by_exam[$r['exam_id']] = $r;
    }
}

// Fetch exams
$upcoming_exams = array();
$past_exams = array();

if(!empty($registrations)) {
    $exam_ids_string = id_list(array_column($registrations, 'exam_id'));
    $all_registered_exams = $dao->getData('*', 'exams', 'id IN (' . $exam_ids_string . ')');
    
    if(!empty($all_registered_exams)) {
        foreach($all_registered_exams as $ex) {
            $start_time = strtotime($ex['exam_start_datetime']);
            $duration_seconds = $ex['duration_minutes'] * 60;
            $end_time = $start_time + $duration_seconds;

            // Already submitted, or the exam window is over
            if(isset($results_by_exam[$ex['id']]) || $current_time > $end_time) {
                $past_exams[] = $ex;
            } else {
                $upcoming_exams[] = $ex;
            }
        }
    }
}

$page_title = 'My Exams';
include("header.php"); 
?>

<div class="row">
    <div class="col-md-12">
        <h2 style="margin-top: 0; font-weight: 700;">My Registered Exams</h2>
        <p class="text-muted">Manage your upcoming schedules and review your completed exam attempts.</p>
        <hr>
    </div>
</div>

<!-- SECTION 1: UPCOMING EXAMS -->
<div class="row" style="margin-bottom: 30px;">
    <div class="col-md-12">
        <h3 style="font-size: 18px; font-weight: 600; color: var(--accent-cyan, #38bdf8); margin-bottom: 15px;">
            <i class="fa fa-clock-o"></i> Upcoming Registered Exams
        </h3>
    </div>
    <?php if(!empty($upcoming_exams)) { 
        foreach($upcoming_exams as $ex) { 
            $start_time = strtotime($ex['exam_start_datetime']);
            $is_ready = $current_time >= $start_time;
    ?>
        <div class="col-md-4 col-sm-6" style="margin-bottom: 20px;">
            <div class="panel panel-default" style="padding: 20px; border-radius: 12px; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h4 style="font-weight: 700; color: var(--text-primary); margin-top: 0;"><?= e($ex['title']); ?></h4>
                    <p style="margin-bottom: 8px;">
                        <span class="label label-info" style="background: rgba(56, 189, 248, 0.15); color: var(--accent-cyan, #38bdf8); border: 1px solid rgba(56, 189, 248, 0.3);">
                            <?= e($ex['category']); ?>
                        </span>
                    </p>
                    <p class="text-muted" style="margin-bottom: 5px;">
                        <i class="fa fa-hourglass-half"></i> <strong>Duration:</strong> <?= (int)$ex['duration_minutes']; ?> Mins
                    </p>
                    <p class="text-muted" style="margin-bottom: 15px;">
                        <i class="fa fa-calendar"></i> <strong>Starts At:</strong><br>
                        <span style="color: #f59e0b; font-weight: 600;"><?= date('d M Y, h:i A', $start_time); ?></span>
                    </p>
                </div>
                <div>
                    <?php if($is_ready) { ?>
                        <a href="takeexam.php?exam_id=<?= (int)$ex['id']; ?>" class="btn btn-success btn-block" style="font-weight: 600; padding: 10px;">
                            <i class="fa fa-play-circle"></i> Take Exam Now
                        </a>
                    <?php } else { ?>
                        <button class="btn btn-warning btn-block" style="opacity: 0.8; font-weight: 600; padding: 10px;" disabled>
                            <i class="fa fa-lock"></i> Starts Soon (Locked)
                        </button>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } } else { ?>
        <div class="col-md-12">
            <div class="alert alert-info" style="background: var(--card-bg); border-color: var(--card-border); color: var(--text-secondary);">
                <i class="fa fa-info-circle"></i> No upcoming registered exams right now. Browse <a href="student_exams.php" style="color: var(--accent-cyan); font-weight: 600;">All Exams</a> to enroll.
            </div>
        </div>
    <?php } ?>
</div>

<!-- SECTION 2: PAST / COMPLETED EXAMS -->
<div class="row" style="margin-bottom: 30px;">
    <div class="col-md-12">
        <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 15px;">
            <i class="fa fa-check-square-o"></i> Completed &amp; Past Exams
        </h3>
    </div>
    <?php if(!empty($past_exams)) { 
        foreach($past_exams as $ex) { 
            $has_result = isset($results_by_exam[$ex['id']]);
            $res = $has_result ? $results_by_exam[$ex['id']] : null;
    ?>
        <div class="col-md-4 col-sm-6" style="margin-bottom: 20px;">
            <div class="panel panel-default" style="padding: 20px; border-radius: 12px; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h4 style="font-weight: 700; color: var(--text-primary); margin-top: 0;"><?= e($ex['title']); ?></h4>
                    <p style="margin-bottom: 8px;">
                        <span class="label label-default" style="background: rgba(255, 255, 255, 0.08); color: var(--text-secondary); border: 1px solid var(--border-color);">
                            <?= e($ex['category']); ?>
                        </span>
                    </p>
                    <p class="text-muted" style="margin-bottom: 5px;">
                        <i class="fa fa-hourglass-half"></i> <strong>Duration:</strong> <?= (int)$ex['duration_minutes']; ?> Mins
                    </p>
                    <?php if($has_result) { ?>
                        <p style="margin-bottom: 15px; color: #10b981; font-weight: 600;">
                            <i class="fa fa-check-circle"></i> Completed on <?= date('d M Y', strtotime($res['submitted_at'])); ?>
                        </p>
                    <?php } else { ?>
                        <p class="text-muted" style="margin-bottom: 15px;">
                            <i class="fa fa-clock-o"></i> Exam window concluded
                        </p>
                    <?php } ?>
                </div>
                <div>
                    <?php if($has_result) { ?>
                        <a href="results.php" class="btn btn-primary btn-block" style="font-weight: 600; padding: 10px;">
                            <i class="fa fa-bar-chart"></i> View Results
                        </a>
                    <?php } else { ?>
                        <button class="btn btn-default btn-block" style="opacity: 0.6; padding: 10px;" disabled>
                            Concluded
                        </button>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } } else { ?>
        <div class="col-md-12">
            <p class="text-muted">No completed or past exams recorded yet.</p>
        </div>
    <?php } ?>
</div>

<?php include('footer.php'); ?>