<?php
/* exam_students.php - STEP 3: students who wrote one exam, with marks and pass/fail */
require('../config/autoload.php');
require('auth.php');
require_once('report_helpers.php');

$dao = new DataAccess();
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

$found = $dao->query(
    "SELECT e.id, e.title, e.category, e.total_marks, e.created_by, ex.name AS examiner_name
     FROM exams e
     LEFT JOIN examiners ex ON ex.id = e.created_by
     WHERE e.id = " . $exam_id
);
if (empty($found)) {
    echo "<script>alert('Exam not found.'); window.location.href='examiner_report.php';</script>";
    exit();
}
$exam = $found[0];

$results = $dao->query(
    "SELECT r.student_id, r.marks_obtained, r.total_marks, r.grading_status, r.submitted_at, s.name AS student_name
     FROM exam_results r
     LEFT JOIN examinees s ON s.id = r.student_id
     WHERE r.exam_id = " . $exam_id . "
     ORDER BY r.marks_obtained DESC, s.name"
);
if (!is_array($results)) { $results = array(); }

$pass = 0;
$fail = 0;
$pending = 0;
foreach ($results as $k => $r) {
    $results[$k]['status'] = rp_status($r);
    if ($results[$k]['status'] === 'Pass') { $pass++; }
    elseif ($results[$k]['status'] === 'Fail') { $fail++; }
    else { $pending++; }
}

$back = !empty($exam['created_by']) ? 'examiner_exams.php?examiner_id=' . (int)$exam['created_by'] : 'examiner_report.php';

include('header.php');
echo rp_styles();
?>

<div class="container" style="margin-top:20px;">
    <p><a href="<?= rp_h($back); ?>">&laquo; Back to exams</a></p>
    <h2><?= rp_h($exam['title']); ?></h2>
    <p class="text-muted">
        <?= rp_h($exam['category']); ?> &bull;
        Created by <?= rp_h($exam['examiner_name'] ?: 'Unknown'); ?> &bull;
        Pass mark: <?= (int)PASS_PERCENT; ?>%
    </p>
    <hr>

    <div class="rp-card">
        <h4>Pass / Fail Rate</h4>
        <?= rp_donut($pass, $fail, 130); ?>
        <?php if ($pending > 0) { ?>
            <p class="text-muted" style="margin:10px 0 0;"><?= $pending; ?> result(s) still pending review are not counted.</p>
        <?php } ?>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Marks Obtained</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Submitted On</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($results)) { $i = 1; foreach ($results as $r) {
                $tm = (float)$r['total_marks'];
                $pct = $tm > 0 ? round(((float)$r['marks_obtained'] / $tm) * 100, 1) . '%' : '&mdash;';
                ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= rp_h($r['student_name'] ?: ('Student #' . (int)$r['student_id'])); ?></td>
                    <td><?= rp_h(number_format((float)$r['marks_obtained'], 2)); ?></td>
                    <td><?= rp_h(number_format($tm, 2)); ?></td>
                    <td><?= $pct; ?></td>
                    <td><?= rp_badge($r['status']); ?></td>
                    <td><?= !empty($r['submitted_at']) ? rp_h(date('d M Y, h:i A', strtotime($r['submitted_at']))) : '&mdash;'; ?></td>
                </tr>
            <?php } } else { ?>
                <tr><td colspan="7" class="text-center">No student has written this exam yet.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('footer.php'); ?>
