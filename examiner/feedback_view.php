<?php
/* feedback_view.php (EXAMINER) - one row per exam with short details; click exam name to see its feedback */
require('../config/autoload.php');
require_once('exam_report_helpers.php');
$examiner_id = er_require_examiner();
$dao = new DataAccess();

$exams = $dao->query("SELECT e.id, e.title, e.category, e.created_at,
        COUNT(f.id) AS fb_count, AVG(f.rating) AS avg_rating, MAX(f.created_at) AS last_fb
    FROM exams e LEFT JOIN feedback f ON f.exam_id = e.id
    WHERE e.created_by = " . (int)$examiner_id . "
    GROUP BY e.id, e.title, e.category, e.created_at
    ORDER BY (COUNT(f.id) > 0) DESC, last_fb DESC, e.created_at DESC");
if (!is_array($exams)) { $exams = array(); }

include("header.php");
echo er_styles();
?>
<div class="row"><div class="col-md-12">
    <h2>Exam Feedback</h2>
    <p class="text-muted">Click an exam name to see all feedback students gave for it.</p><hr>
    <div class="table-responsive"><table class="table table-bordered table-striped table-hover">
        <thead><tr><th>#</th><th>Exam</th><th>Type</th><th>Feedback Received</th><th>Average Rating</th><th>Latest Feedback</th></tr></thead><tbody>
        <?php if ($exams) { $i = 1; foreach ($exams as $x) { ?>
            <tr>
                <td><?= $i++; ?></td>
                <td><a class="er-link" href="feedback_exam.php?exam_id=<?= (int)$x['id']; ?>"><?= er_h($x['title']); ?></a></td>
                <td><?= er_h($x['category']); ?></td>
                <td><?= (int)$x['fb_count']; ?></td>
                <td><?= (int)$x['fb_count'] > 0 ? er_h(number_format((float)$x['avg_rating'], 1)) . ' / 5' : '&mdash;'; ?></td>
                <td><?= !empty($x['last_fb']) ? er_h(date('d M Y, h:i A', strtotime($x['last_fb']))) : '&mdash;'; ?></td>
            </tr>
        <?php } } else { ?><tr><td colspan="6" class="text-center">No exams found.</td></tr><?php } ?>
    </tbody></table></div>
</div></div>
<?php include('footer.php'); ?>
