<?php
/* feedback_form.php (STUDENT) - shown automatically after an exam; also opened from the Feedback section */
require('../config/autoload.php');
require('auth_helper.php');
$student_id = require_login();
$dao = new DataAccess();

$exam_id = isset($_REQUEST['exam_id']) ? (int)$_REQUEST['exam_id'] : 0;

// Student must have actually taken this exam
$taken = $exam_id ? $dao->query("SELECT e.id, e.title, e.category FROM exams e
          INNER JOIN exam_results r ON r.exam_id = e.id
          WHERE e.id = $exam_id AND r.student_id = $student_id LIMIT 1") : array();
if (empty($taken) || !is_array($taken)) { header('Location: feedback.php'); exit; }
$exam = $taken[0];

// Already submitted active feedback? go to the Feedback section
$done = $dao->query("SELECT id FROM feedback WHERE student_id = $student_id AND exam_id = $exam_id AND is_deleted = 0 LIMIT 1");
if (!empty($done) && is_array($done)) { header('Location: feedback.php?msg=already'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating   = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    if (function_exists('mb_substr')) { $comments = mb_substr($comments, 0, 1000); } else { $comments = substr($comments, 0, 1000); }

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a star rating.';
    } elseif ($comments === '') {
        $error = 'Please write a short comment.';
    } else {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($conn) {
            // Check if there is an existing soft-deleted feedback row for this student and exam
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM feedback WHERE student_id = ? AND exam_id = ? AND is_deleted = 1 LIMIT 1");
            mysqli_stmt_bind_param($check_stmt, "ii", $student_id, $exam_id);
            mysqli_stmt_execute($check_stmt);
            $res = mysqli_stmt_get_result($check_stmt);
            $existing = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($check_stmt);

            if ($existing && !empty($existing['id'])) {
                $existing_id = (int)$existing['id'];
                $up_stmt = mysqli_prepare($conn, "UPDATE feedback SET rating = ?, comments = ?, created_at = NOW(), is_deleted = 0 WHERE id = ? AND student_id = ?");
                mysqli_stmt_bind_param($up_stmt, "isii", $rating, $comments, $existing_id, $student_id);
                mysqli_stmt_execute($up_stmt);
                mysqli_stmt_close($up_stmt);
            } else {
                $in_stmt = mysqli_prepare($conn, "INSERT INTO feedback (student_id, exam_id, rating, comments, created_at, is_deleted) VALUES (?, ?, ?, ?, NOW(), 0)");
                mysqli_stmt_bind_param($in_stmt, "iiis", $student_id, $exam_id, $rating, $comments);
                mysqli_stmt_execute($in_stmt);
                mysqli_stmt_close($in_stmt);
            }
            mysqli_close($conn);
            header('Location: feedback.php?msg=thanks');
            exit;
        } else {
            $error = 'Database connection error. Please try again.';
        }
    }
}

$page_title = 'Exam Feedback';
include('header.php');
?>
<style>
.fb-stars{direction:rtl;display:inline-block;font-size:34px}
.fb-stars input{display:none}
.fb-stars label{color:#94a3b8;cursor:pointer;padding:0 2px;margin:0}
.fb-stars input:checked ~ label,.fb-stars label:hover,.fb-stars label:hover ~ label{color:#f59e0b}
</style>
<div class="row"><div class="col-md-8 col-md-offset-2">
    <h2>Exam Feedback</h2>
    <p class="text-muted">How was <strong><?= htmlspecialchars($exam['title']); ?></strong>? Your feedback goes to the examiner.</p><hr>
    <?php if ($error) { ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php } ?>
    <form method="POST">
        <input type="hidden" name="exam_id" value="<?= $exam_id; ?>">
        <div class="form-group">
            <label>Rating</label><br>
            <div class="fb-stars">
                <?php for ($s = 5; $s >= 1; $s--) { ?>
                    <input type="radio" name="rating" id="star<?= $s; ?>" value="<?= $s; ?>" <?= (isset($rating) && $rating == $s) ? 'checked' : ''; ?>>
                    <label for="star<?= $s; ?>" title="<?= $s; ?> star(s)">&#9733;</label>
                <?php } ?>
            </div>
        </div>
        <div class="form-group">
            <label for="comments">Your feedback</label>
            <textarea name="comments" id="comments" rows="5" maxlength="1000" class="form-control" placeholder="Questions, difficulty, timing, anything you want the examiner to know..."><?= isset($comments) ? htmlspecialchars($comments) : ''; ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Feedback</button>
        <a href="feedback.php" class="btn btn-default">Cancel</a>
        <p class="text-muted" style="margin-top:10px;">Not now? Cancel and fill it anytime from <strong>Feedback</strong> in the menu.</p>
    </form>
</div></div>
<?php include('footer.php'); ?>
