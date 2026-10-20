<?php 
require('../config/autoload.php'); 

if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
}

$dao = new DataAccess(); 
date_default_timezone_set('Asia/Kolkata'); 
$now = time();
$examiner_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$examiner_name = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$examiner_id = (int)$_SESSION['examiner_id'];

// If name is not stored in session, retrieve it from the database for dynamic greeting
if(empty($examiner_name) && $examiner_id > 0) {
    $ex_record = $dao->getData('name', 'examiners', 'id = ' . $examiner_id);
    if(!empty($ex_record) && !empty($ex_record[0]['name'])) {
        $examiner_name = $ex_record[0]['name'];
        $_SESSION['name'] = $examiner_name;
    }
}

$exams         = $dao->getData('*', 'exams', 'created_by = ' . $examiner_id . ' ORDER BY id DESC');
$courses       = $dao->getData('*', 'courses', '1');
$questions     = $dao->getData('*', 'questions', '1');
$references    = $dao->getData('*', 'exam_references', '1');
$registrations = $dao->getData('*', 'registrations', '1');
$results       = $dao->getData('*', 'exam_results', '1');

$exams         = !empty($exams) ? $exams : array();
$courses       = !empty($courses) ? $courses : array();
$questions     = !empty($questions) ? $questions : array();
$references    = !empty($references) ? $references : array();
$registrations = !empty($registrations) ? $registrations : array();
$results       = !empty($results) ? $results : array();

$my_exam_ids = array();
foreach($exams as $e) { $my_exam_ids[$e['id']] = true; }

$questions = array_values(array_filter($questions, function($q) use ($my_exam_ids) {
    return isset($my_exam_ids[$q['exam_id']]);
}));
$references = array_values(array_filter($references, function($r) use ($my_exam_ids) {
    return isset($my_exam_ids[$r['exam_id']]);
}));
$registrations = array_values(array_filter($registrations, function($rg) use ($my_exam_ids) {
    return isset($my_exam_ids[$rg['exam_id']]);
}));
$results = array_values(array_filter($results, function($rs) use ($my_exam_ids) {
    return isset($my_exam_ids[$rs['exam_id']]);
}));

$course_names = array();
foreach($courses as $c) { $course_names[$c['id']] = $c['course_name']; }

$exam_price = array();
foreach($exams as $e) { $exam_price[$e['id']] = $e['price']; }

$q_count = array();
foreach($questions as $q) { $q_count[$q['exam_id']] = (isset($q_count[$q['exam_id']]) ? $q_count[$q['exam_id']] : 0) + 1; }

$reg_count = array();
$total_paid_regs = 0;
$revenue = 0;
foreach($registrations as $rg) {
    if($rg['payment_status'] == 'Paid') {
        $reg_count[$rg['exam_id']] = (isset($reg_count[$rg['exam_id']]) ? $reg_count[$rg['exam_id']] : 0) + 1;
        $total_paid_regs++;
        $revenue += isset($exam_price[$rg['exam_id']]) ? $exam_price[$rg['exam_id']] : 0;
    }
}

$attempt_count = array();
$pct_sum = array();
$total_attempts = 0;
$total_passed = 0;
foreach($results as $rs) {
    $pct = ($rs['total_questions'] > 0) ? ($rs['score'] / $rs['total_questions']) * 100 : 0;
    $attempt_count[$rs['exam_id']] = (isset($attempt_count[$rs['exam_id']]) ? $attempt_count[$rs['exam_id']] : 0) + 1;
    $pct_sum[$rs['exam_id']] = (isset($pct_sum[$rs['exam_id']]) ? $pct_sum[$rs['exam_id']] : 0) + $pct;
    $total_attempts++;
    if($pct >= 60) { $total_passed++; }
}
$pass_rate = ($total_attempts > 0) ? round(($total_passed / $total_attempts) * 100) : 0;

$total_cert = 0;
$total_mock = 0;
foreach($exams as $e) {
    if($e['category'] == 'Mock') { $total_mock++; } else { $total_cert++; }
}

$pending_essays_count = 0;
if(!empty($my_exam_ids)) {
    $pending_data = $dao->query("SELECT COUNT(*) AS cnt FROM student_answers WHERE exam_id IN (" . implode(',', array_keys($my_exam_ids)) . ") AND status = 'pending_review'");
    if(!empty($pending_data) && isset($pending_data[0]['cnt'])) {
        $pending_essays_count = (int)$pending_data[0]['cnt'];
    }
}

include("header.php"); 
?>

<!-- Background Constellation Graphic matching Reference Images -->
<svg class="bg-constellation" viewBox="0 0 350 250" fill="none" xmlns="http://www.w3.org/2000/svg">
    <line x1="80" y1="20" x2="160" y2="60" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="160" y1="60" x2="240" y2="30" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="160" y1="60" x2="210" y2="120" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="240" y1="30" x2="310" y2="70" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="210" y1="120" x2="310" y2="70" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="210" y1="120" x2="280" y2="170" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="100" y1="150" x2="210" y2="120" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="280" y1="170" x2="340" y2="140" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="310" y1="70" x2="340" y2="140" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <circle cx="80" cy="20" r="3.5" fill="#00e5ff"/>
    <circle cx="160" cy="60" r="4.5" fill="#00d2ff"/>
    <circle cx="240" cy="30" r="4" fill="#a855f7"/>
    <circle cx="210" cy="120" r="5" fill="#ff007f"/>
    <circle cx="310" cy="70" r="4.5" fill="#ec4899"/>
    <circle cx="280" cy="170" r="3.5" fill="#ff6b4a"/>
    <circle cx="100" cy="150" r="4" fill="#00e5ff"/>
    <circle cx="340" cy="140" r="4" fill="#10b981"/>
</svg>

<div class="dashboard-header">
    <h2 class="dashboard-main-title">Examiner Dashboard</h2>
    <h3 class="dashboard-welcome-title">Welcome, <strong><?= htmlspecialchars(!empty($examiner_name) ? $examiner_name : 'Prof. Gupta'); ?></strong>!</h3>
    <?php if($pending_essays_count > 0) { ?>
        <div class="alert alert-warning" style="margin-top: 16px; border-left: 5px solid #d97706;">
            <i class="fa fa-pencil-square-o fa-lg"></i> 
            <strong>Action Required:</strong> You have <strong><?= $pending_essays_count; ?></strong> essay <?= ($pending_essays_count == 1 ? 'answer' : 'answers'); ?> awaiting grading.
            <a href="grade_essays.php" class="btn btn-warning btn-xs" style="margin-left: 10px; font-weight: bold;">Grade Essays Now &rarr;</a>
        </div>
    <?php } ?>
</div>

<!-- 8 Stat Cards Grid (Equal Width & Height, Custom Neon/Pastel Glows) -->
<div class="stat-cards-grid">
    <!-- Card 1: Certification Exams -->
    <div class="stat-card card-cert">
        <div class="stat-icon-wrapper">
            <i class="fa fa-certificate"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $total_cert; ?></div>
            <div class="stat-title">Certification Exams</div>
        </div>
    </div>

    <!-- Card 2: Mock Exams -->
    <div class="stat-card card-mock">
        <div class="stat-icon-wrapper">
            <i class="fa fa-pencil"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $total_mock; ?></div>
            <div class="stat-title">Mock Exams</div>
        </div>
    </div>

    <!-- Card 3: Total Questions -->
    <div class="stat-card card-questions">
        <div class="stat-icon-wrapper">
            <i class="fa fa-question-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= count($questions); ?></div>
            <div class="stat-title">Total Questions</div>
        </div>
    </div>

    <!-- Card 4: Study References -->
    <div class="stat-card card-references">
        <div class="stat-icon-wrapper">
            <i class="fa fa-book"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= count($references); ?></div>
            <div class="stat-title">Study References</div>
        </div>
    </div>

    <!-- Card 5: Paid Registrations -->
    <div class="stat-card card-registrations">
        <div class="stat-icon-wrapper">
            <i class="fa fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $total_paid_regs; ?></div>
            <div class="stat-title">Paid Registrations</div>
        </div>
    </div>

    <!-- Card 6: Registration Revenue -->
    <div class="stat-card card-revenue">
        <div class="stat-icon-wrapper">
            <i class="fa fa-money"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number">$<?= number_format($revenue, 2); ?></div>
            <div class="stat-title">Registration Revenue</div>
        </div>
    </div>

    <!-- Card 7: Exam Attempts -->
    <div class="stat-card card-attempts">
        <div class="stat-icon-wrapper">
            <i class="fa fa-file-text-o"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $total_attempts; ?></div>
            <div class="stat-title">Exam Attempts</div>
        </div>
    </div>

    <!-- Card 8: Overall Pass Rate -->
    <div class="stat-card card-passrate">
        <div class="stat-icon-wrapper">
            <i class="fa fa-trophy"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $pass_rate; ?>%</div>
            <div class="stat-title">Overall Pass Rate</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Exam overview table -->
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">My Exams Overview</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Course</th>
                                <th>Type</th>
                                <th>Questions</th>
                                <th>Registered</th>
                                <th>Attempts</th>
                                <th>Avg Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($exams)) { 
                                $shown = 0;
                                foreach($exams as $e) { 
                                    if($shown >= 10) { break; }
                                    $shown++;
                                    $eid = $e['id'];
                                    $is_mock = ($e['category'] == 'Mock');
                                    $qc = isset($q_count[$eid]) ? $q_count[$eid] : 0;
                                    $rc = isset($reg_count[$eid]) ? $reg_count[$eid] : 0;
                                    $ac = isset($attempt_count[$eid]) ? $attempt_count[$eid] : 0;
                                    $avg = ($ac > 0) ? round($pct_sum[$eid] / $ac) . '%' : '-';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['title']); ?></td>
                                    <td><?= isset($course_names[$e['course_id']]) ? htmlspecialchars($course_names[$e['course_id']]) : '-'; ?></td>
                                    <td>
                                        <?php if($is_mock) { ?>
                                            <span class="label label-success">Mock</span>
                                        <?php } else { ?>
                                            <span class="label label-primary">Certification</span>
                                        <?php } ?>
                                    </td>
                                    <td><?= $qc; ?></td>
                                    <td><?= $is_mock ? '-' : $rc; ?></td>
                                    <td><?= $ac; ?></td>
                                    <td><?= $avg; ?></td>
                                </tr>
                            <?php } 
                            } else { ?>
                                <tr><td colspan="7" class="text-center" style="padding: 24px; color: var(--text-muted);">You have not created any exams yet.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php if(count($exams) > 10) { ?>
                    <div style="padding: 10px 16px;">
                        <small class="text-muted">Showing your 10 most recent exams of <?= count($exams); ?>.</small>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>