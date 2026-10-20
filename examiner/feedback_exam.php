<?php
/* feedback_exam.php (EXAMINER) - all feedback for one exam, with Back button */
require('../config/autoload.php');
require_once('exam_report_helpers.php');
$examiner_id = er_require_examiner();
$dao = new DataAccess();

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
// Only the examiner who created the exam can see its feedback
$ex = $dao->query("SELECT id, title, category FROM exams WHERE id = $exam_id AND created_by = " . (int)$examiner_id . " LIMIT 1");
if (empty($ex) || !is_array($ex)) { header('Location: feedback_view.php'); exit; }
$exam = $ex[0];

$list = $dao->query("SELECT f.rating, f.comments, f.created_at, s.name
    FROM feedback f LEFT JOIN examinees s ON s.id = f.student_id
    WHERE f.exam_id = $exam_id ORDER BY f.created_at DESC");
if (!is_array($list)) { $list = array(); }
$total = count($list); $sum = 0;
foreach ($list as $r) { $sum += (int)$r['rating']; }

include("header.php");
echo er_styles();
?>
<div class="row"><div class="col-md-12">
    <a href="feedback_view.php" class="btn btn-default" style="margin-bottom:15px;"><i class="fa fa-arrow-left"></i> Back</a>
    <h2><?= er_h($exam['title']); ?> <small><?= er_h($exam['category']); ?></small></h2>
    <p><strong>Feedback received:</strong> <?= $total; ?>
       <?php if ($total) { ?> &nbsp;|&nbsp; <strong>Average rating:</strong> <?= er_h(number_format($sum / $total, 1)); ?> / 5<?php } ?></p><hr>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>#</th><th>Student</th><th>Rating</th><th>Feedback</th><th>Date</th></tr></thead><tbody>
        <?php if ($list) { $i = 1; foreach ($list as $r) { ?>
            <tr>
                <td><?= $i++; ?></td>
                <td><?= er_h($r['name'] ? $r['name'] : 'Student'); ?></td>
                <td style="color:#f59e0b;white-space:nowrap;"><?= str_repeat('&#9733;', (int)$r['rating']) . str_repeat('&#9734;', 5 - (int)$r['rating']); ?></td>
                <td><?= nl2br(er_h($r['comments'])); ?></td>
                <td><?= er_h(date('d M Y, h:i A', strtotime($r['created_at']))); ?></td>
            </tr>
        <?php } } else { ?><tr><td colspan="5" class="text-center">No feedback for this exam yet.</td></tr><?php } ?>
    </tbody></table></div>
    <a href="feedback_view.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Feedback</a>
</div></div>
<?php include('footer.php'); ?>
