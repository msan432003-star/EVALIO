<?php
/* exam_report.php - STEP 2: certification exam report (graph, registered students, students who took the exam) */
require('../config/autoload.php');
require_once('exam_report_helpers.php');
$examiner_id = er_require_examiner();

$dao = new DataAccess();
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Only this examiner's own CERTIFICATION exams
$found = $dao->getData('*', 'exams', "id = " . $exam_id . " AND created_by = " . $examiner_id . " AND category = 'Certification'");
if (empty($found)) {
    echo "<script>alert('Exam not found or access denied.'); window.location.href='my_exams.php';</script>";
    exit();
}
$exam = $found[0];

// Students who registered and paid (and whether they have taken the exam yet)
$registered = $dao->query(
    "SELECT s.id, s.name, r.registration_date,
            (SELECT COUNT(*) FROM exam_results t WHERE t.exam_id = r.exam_id AND t.student_id = r.student_id) AS taken
     FROM registrations r
     JOIN examinees s ON s.id = r.student_id
     WHERE r.exam_id = " . $exam_id . " AND r.payment_status = 'Paid'
     ORDER BY r.registration_date DESC"
);
if (!is_array($registered)) { $registered = array(); }

// Students who took the exam
$results = $dao->query(
    "SELECT r.student_id, r.marks_obtained, r.total_marks, r.grading_status, r.submitted_at, s.name AS student_name
     FROM exam_results r
     LEFT JOIN examinees s ON s.id = r.student_id
     WHERE r.exam_id = " . $exam_id . "
     ORDER BY r.marks_obtained DESC, s.name"
);
if (!is_array($results)) { $results = array(); }

$pass = 0; $fail = 0; $pending = 0;
foreach ($results as $k => $r) {
    $results[$k]['status'] = er_status($r);
    if ($results[$k]['status'] === 'Pass') { $pass++; }
    elseif ($results[$k]['status'] === 'Fail') { $fail++; }
    else { $pending++; }
}

include("header.php");
echo er_styles();
?>

<div class="row">
    <div class="col-md-12">
        <p><a href="my_exams.php">&laquo; Back to my exams</a></p>
        <h2 style="margin-top:0;"><?= er_h($exam['title']); ?></h2>
        <p class="text-muted">
            Certification &bull; <?= er_h(ucfirst($exam['status'])); ?> &bull;
            Total marks: <?= er_h(number_format((float)$exam['total_marks'], 2)); ?> &bull;
            Pass mark: <?= (int)PASS_PERCENT; ?>%
        </p>
        <a href="exam_detail.php?exam_id=<?= $exam_id; ?>" class="btn btn-primary"><i class="fa fa-list-alt"></i> Exam Details (Questions &amp; Answers)</a>
        <hr>

        <div class="er-card">
            <h4>Pass / Fail Rate</h4>
            <?= er_donut($pass, $fail, 150); ?>
            <?php if ($pending > 0) { ?>
                <p class="text-muted" style="margin:12px 0 0;"><?= $pending; ?> result(s) waiting for your review are not counted yet.</p>
            <?php } ?>
        </div>

        <div class="er-card">
            <h4>Registered Students (<?= count($registered); ?>)</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" style="margin-bottom:0;">
                    <thead>
                        <tr><th>#</th><th>Student ID</th><th>Student Name</th><th>Registered On</th><th>Exam Taken</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($registered)) { $i = 1; foreach ($registered as $s) { ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= (int)$s['id']; ?></td>
                            <td><?= er_h($s['name']); ?></td>
                            <td><?= !empty($s['registration_date']) ? er_h(date('d M Y, h:i A', strtotime($s['registration_date']))) : '&mdash;'; ?></td>
                            <td><?= ((int)$s['taken'] > 0) ? '<span class="label label-info">Yes</span>' : '<span class="label label-default">Not yet</span>'; ?></td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="5" class="text-center">No student has registered for this exam yet.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="er-card">
            <h4>Students Who Took the Exam (<?= count($results); ?>)</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" style="margin-bottom:0;">
                    <thead>
                        <tr><th>#</th><th>Student ID</th><th>Student Name</th><th>Marks Obtained</th><th>Total Marks</th><th>Percentage</th><th>Status</th><th>Submitted On</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($results)) { $i = 1; foreach ($results as $r) {
                        $tm = (float)$r['total_marks'];
                        $pct = $tm > 0 ? round(((float)$r['marks_obtained'] / $tm) * 100, 1) . '%' : '&mdash;';
                        ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= (int)$r['student_id']; ?></td>
                            <td><?= er_h($r['student_name'] ?: 'Unknown'); ?></td>
                            <td><?= er_h(number_format((float)$r['marks_obtained'], 2)); ?></td>
                            <td><?= er_h(number_format($tm, 2)); ?></td>
                            <td><?= $pct; ?></td>
                            <td><?= er_badge($r['status']); ?></td>
                            <td><?= !empty($r['submitted_at']) ? er_h(date('d M Y, h:i A', strtotime($r['submitted_at']))) : '&mdash;'; ?></td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="8" class="text-center">No student has taken this exam yet.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
