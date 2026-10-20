<?php
/* feedback.php (STUDENT) - Feedback section: pending (exam name + Give Feedback) and submitted */
require('../config/autoload.php');
require('auth_helper.php');
$student_id = require_login();
$dao = new DataAccess();

$pending = $dao->query("SELECT e.id, e.title, e.category, MAX(r.submitted_at) AS taken_on
    FROM exam_results r INNER JOIN exams e ON e.id = r.exam_id
    LEFT JOIN feedback f ON f.exam_id = r.exam_id AND f.student_id = r.student_id AND f.is_deleted = 0
    WHERE r.student_id = $student_id AND f.id IS NULL
    GROUP BY e.id, e.title, e.category ORDER BY taken_on DESC");
$given = $dao->query("SELECT f.id, f.rating, f.comments, f.created_at, e.title
    FROM feedback f INNER JOIN exams e ON e.id = f.exam_id
    WHERE f.student_id = $student_id AND f.is_deleted = 0 ORDER BY f.created_at DESC");
if (!is_array($pending)) { $pending = array(); }
if (!is_array($given))   { $given = array(); }
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

$page_title = 'Feedback';
include('header.php');
?>
<div class="row"><div class="col-md-12">
    <h2>Feedback</h2>
    <p class="text-muted">Share your feedback for the exams you have taken.</p><hr>
    <?php if ($msg === 'thanks') { ?><div class="alert alert-success">Thank you! Your feedback was submitted.</div><?php } ?>
    <?php if ($msg === 'already') { ?><div class="alert alert-info">You have already submitted feedback for that exam.</div><?php } ?>
    <?php if ($msg === 'deleted') { ?><div class="alert alert-success">Your feedback has been deleted.</div><?php } ?>
    <?php if ($msg === 'delete_err') { ?><div class="alert alert-danger">Could not delete feedback. It may have already been removed.</div><?php } ?>

    <h4>Pending Feedback (<?= count($pending); ?>)</h4>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>#</th><th>Exam</th><th>Type</th><th>Taken On</th><th>Feedback</th></tr></thead><tbody>
        <?php if ($pending) { $i = 1; foreach ($pending as $p) { ?>
            <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($p['title']); ?></td>
                <td><?= htmlspecialchars($p['category']); ?></td>
                <td><?= !empty($p['taken_on']) ? date('d M Y, h:i A', strtotime($p['taken_on'])) : '&mdash;'; ?></td>
                <td><a class="btn btn-primary btn-xs" href="feedback_form.php?exam_id=<?= (int)$p['id']; ?>">Give Feedback</a></td>
            </tr>
        <?php } } else { ?><tr><td colspan="5" class="text-center">No pending feedback.</td></tr><?php } ?>
    </tbody></table></div>

    <h4 style="margin-top:30px;">Submitted Feedback</h4>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>#</th><th>Exam</th><th>Rating</th><th>Your Feedback</th><th>Submitted</th><th>Action</th></tr></thead><tbody>
        <?php if ($given) { $i = 1; foreach ($given as $g) { ?>
            <tr>
                <td><?= $i++; ?></td>
                <td><?= htmlspecialchars($g['title']); ?></td>
                <td style="color:#f59e0b;white-space:nowrap;"><?= str_repeat('&#9733;', (int)$g['rating']) . str_repeat('&#9734;', 5 - (int)$g['rating']); ?></td>
                <td><?= nl2br(htmlspecialchars($g['comments'])); ?></td>
                <td><?= date('d M Y, h:i A', strtotime($g['created_at'])); ?></td>
                <td>
                    <form method="POST" action="delete_feedback.php" style="display:inline;" onsubmit="return confirm('Delete this feedback?');">
                        <input type="hidden" name="feedback_id" value="<?= (int)$g['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-xs" title="Delete Feedback">Delete</button>
                    </form>
                </td>
            </tr>
        <?php } } else { ?><tr><td colspan="6" class="text-center">No feedback submitted yet.</td></tr><?php } ?>
    </tbody></table></div>
</div></div>
<?php include('footer.php'); ?>
