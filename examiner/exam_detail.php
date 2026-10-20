<?php
/* exam_detail.php - questions and answers of one exam (certification or mock). Essay answers are left blank. */
require('../config/autoload.php');
require_once('exam_report_helpers.php');
$examiner_id = er_require_examiner();

$dao = new DataAccess();
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Only this examiner's own exams
$found = $dao->getData('*', 'exams', "id = " . $exam_id . " AND created_by = " . $examiner_id);
if (empty($found)) {
    echo "<script>alert('Exam not found or access denied.'); window.location.href='my_exams.php';</script>";
    exit();
}
$exam = $found[0];
$isMock = (strcasecmp($exam['category'], 'Mock') === 0);
$back = $isMock ? 'my_exams.php' : 'exam_report.php?exam_id=' . $exam_id;

$questions = $dao->getData('*', 'questions', 'exam_id = ' . $exam_id . ' ORDER BY id');
if (!is_array($questions)) { $questions = array(); }

// Matching questions keep their pairs as JSON (options_data, or correct_answer as fallback)
function er_pairs($q)
{
    foreach (array('options_data', 'correct_answer') as $col) {
        if (!empty($q[$col])) {
            $d = json_decode($q[$col], true);
            if (is_array($d) && !empty($d['pairs']) && is_array($d['pairs'])) {
                return $d['pairs'];
            }
        }
    }
    return array();
}

include("header.php");
echo er_styles();
?>

<div class="row">
    <div class="col-md-12">
        <p><a href="<?= er_h($back); ?>">&laquo; Back</a></p>
        <h2 style="margin-top:0;"><?= er_h($exam['title']); ?> &mdash; Questions &amp; Answers</h2>
        <p class="text-muted">
            <?= er_h($exam['category']); ?> &bull; <?= count($questions); ?> question(s) &bull;
            Total marks: <?= er_h(number_format((float)$exam['total_marks'], 2)); ?>
        </p>
        <hr>

        <?php if (empty($questions)) { ?>
            <div class="alert alert-info">No questions have been added to this exam yet.</div>
        <?php } else { $n = 1; foreach ($questions as $q) {
            $type = !empty($q['question_type']) ? $q['question_type'] : 'mcq';
            ?>
            <div class="er-q">
                <div class="er-q-head">
                    <strong>Q<?= $n++; ?>. <?= er_h($q['question_text']); ?></strong>
                    <span>
                        <span class="label label-default"><?= er_h(strtoupper(str_replace('_', ' ', $type))); ?></span>
                        <span class="label label-info"><?= er_h(number_format((float)$q['marks'], 1)); ?> mark(s)</span>
                    </span>
                </div>

                <?php if ($type === 'mcq') {
                    $correct = strtoupper(trim((string)$q['correct_option']));
                    foreach (array('A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d') as $letter => $col) { ?>
                        <div class="er-opt <?= $correct === $letter ? 'correct' : ''; ?>">
                            <?= $letter; ?>. <?= er_h($q[$col]); ?>
                            <?= $correct === $letter ? ' <i class="fa fa-check text-success"></i> Correct answer' : ''; ?>
                        </div>
                    <?php }
                } elseif ($type === 'true_false') { ?>
                    <p style="margin:0;">Answer: <span class="label label-success"><?= er_h($q['correct_answer'] ?: $q['correct_option']); ?></span></p>
                <?php } elseif ($type === 'essay') { ?>
                    <p class="text-muted" style="margin:0;">Descriptive answer (graded manually):</p>
                    <div class="er-blank"></div>
                <?php } elseif ($type === 'matching') {
                    $pairs = er_pairs($q);
                    if (!empty($pairs)) { ?>
                        <table class="table table-bordered" style="margin:0; max-width:520px;">
                            <thead><tr><th>Left</th><th>Matches with</th></tr></thead>
                            <tbody>
                            <?php foreach ($pairs as $p) { ?>
                                <tr><td><?= er_h(isset($p['left']) ? $p['left'] : ''); ?></td><td><?= er_h(isset($p['right']) ? $p['right'] : ''); ?></td></tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    <?php } else { ?>
                        <p class="text-muted" style="margin:0;">No pairs saved.</p>
                    <?php }
                } else { /* short_answer, fill_blank */ ?>
                    <p style="margin:0;">Answer: <code><?= er_h($q['correct_answer']); ?></code></p>
                <?php } ?>
            </div>
        <?php } } ?>
    </div>
</div>

<?php include('footer.php'); ?>
