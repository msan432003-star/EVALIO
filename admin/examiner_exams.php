<?php
/* examiner_exams.php - STEP 2: exams created by one examiner + pass/fail donut */
require('../config/autoload.php');
require('auth.php');
require_once('report_helpers.php');

$dao = new DataAccess();
$examiner_id = isset($_GET['examiner_id']) ? (int)$_GET['examiner_id'] : 0;

$found = $dao->getData(array('id', 'name', 'email'), 'examiners', 'id = ' . $examiner_id);
if (empty($found)) {
    echo "<script>alert('Examiner not found.'); window.location.href='examiner_report.php';</script>";
    exit();
}
$examiner = $found[0];

// Every exam this examiner created, with attempt / pass / fail counts
$exams = $dao->query(
    "SELECT e.id, e.title, e.category, e.status, e.total_marks, e.created_at,
            COUNT(r.id) AS attempts,
            COALESCE(SUM(CASE WHEN " . rp_pass_sql() . " THEN 1 ELSE 0 END), 0) AS passed,
            COALESCE(SUM(CASE WHEN " . rp_fail_sql() . " THEN 1 ELSE 0 END), 0) AS failed
     FROM exams e
     LEFT JOIN exam_results r ON r.exam_id = e.id
     WHERE e.created_by = " . $examiner_id . "
     GROUP BY e.id, e.title, e.category, e.status, e.total_marks, e.created_at
     ORDER BY e.created_at DESC"
);
if (!is_array($exams)) { $exams = array(); }

$total_pass = 0;
$total_fail = 0;
foreach ($exams as $x) {
    $total_pass += (int)$x['passed'];
    $total_fail += (int)$x['failed'];
}

include('header.php');
echo rp_styles();
?>

<div class="container" style="margin-top:20px;">
    <a href="examiner_report.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Examiner Reports</a>
    <h2>Exams by <?= rp_h($examiner['name']); ?></h2>
    <p class="text-muted"><?= rp_h($examiner['email']); ?></p>
    <hr>

    <div class="rp-card">
        <h4>Overall Pass / Fail Rate</h4>
        <?= rp_donut($total_pass, $total_fail, 130); ?>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><i class="fa fa-list" style="margin-right:8px; color:var(--text-sidebar-icon);"></i><strong>Exams List (<?= count($exams); ?>)</strong></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover" style="margin-bottom:0;">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Exam</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Total Marks</th>
                        <th>Students Attempted</th>
                        <th>Passed</th>
                        <th>Failed</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($exams)) { $i = 1; foreach ($exams as $x) { ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><a class="rp-link" href="exam_students.php?exam_id=<?= (int)$x['id']; ?>"><strong><?= rp_h($x['title']); ?></strong></a></td>
                        <td><?= rp_h($x['category']); ?></td>
                        <td><span class="label label-<?= ($x['status'] === 'approved' || $x['status'] === 'active') ? 'success' : (($x['status'] === 'pending') ? 'warning' : 'default'); ?>"><?= rp_h(ucfirst($x['status'])); ?></span></td>
                        <td><?= rp_h(number_format((float)$x['total_marks'], 2)); ?></td>
                        <td><strong><?= (int)$x['attempts']; ?></strong></td>
                        <td><span style="color:#10b981; font-weight:700;"><?= (int)$x['passed']; ?></span></td>
                        <td><span style="color:#ef4444; font-weight:700;"><?= (int)$x['failed']; ?></span></td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding:24px;">This examiner has not created any exams yet.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
