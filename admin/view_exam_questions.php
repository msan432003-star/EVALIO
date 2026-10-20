<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();

$exam_id = (int)($_GET['exam_id'] ?? 0);

if ($exam_id <= 0) {
    header('Location: pending_exams.php');
    exit;
}

$exam = $dao->getData(
    array('id', 'title', 'course_id', 'category', 'exam_type', 'exam_start_datetime', 'duration_minutes', 'price', 'total_marks', 'status'),
    'exams',
    'id=' . $exam_id
);
$exam = $exam[0] ?? null;

if (!$exam) {
    header('Location: pending_exams.php');
    exit;
}

// Course name
$courseName = $exam['course_id'];
$course = $dao->getData(array('course_name'), 'courses', 'id=' . (int)$exam['course_id']);
if (!empty($course)) {
    $courseName = $course[0]['course_name'];
}

$questions = $dao->getData('*', 'questions', 'exam_id=' . $exam_id . ' ORDER BY id ASC');
$questions = is_array($questions) ? $questions : array();

include('header.php');
?>
<div class="container" style="margin-top:20px; margin-bottom:60px;">
    <h3>Review Exam: <?php echo h($exam['title']); ?></h3>

    <table class="table table-bordered" style="width:auto; margin-bottom:25px;">
        <tr><th>Course</th><td><?php echo h($courseName); ?></td></tr>
        <tr><th>Category</th><td><?php echo h($exam['category']); ?></td></tr>
        <tr><th>Format</th><td><span class="label label-primary"><?php echo strtoupper(str_replace('_', ' ', $exam['exam_type'] ?? 'hybrid')); ?></span></td></tr>
        <tr><th>Starts</th><td><?php echo h($exam['exam_start_datetime']); ?></td></tr>
        <tr><th>Duration</th><td><?php echo h($exam['duration_minutes']); ?> minutes</td></tr>
        <tr><th>Fee</th><td>&#8377;<?php echo h($exam['price']); ?></td></tr>
        <tr><th>Total Marks</th><td><strong><?php echo number_format($exam['total_marks'] ?? 0, 2); ?></strong></td></tr>
        <tr><th>Status</th><td><?php echo h($exam['status']); ?></td></tr>
    </table>

    <h5>Questions (<?php echo count($questions); ?>)</h5>

    <?php if (empty($questions)): ?>
        <p>No questions have been added to this exam yet.</p>
    <?php else: ?>
        <?php foreach ($questions as $i => $q): 
            $qt = !empty($q['question_type']) ? $q['question_type'] : 'mcq';
            $q_marks = (float)($q['marks'] ?? 1.00);
        ?>
            <div class="panel panel-default" style="margin-bottom:15px; border-radius: 6px;">
                <div class="panel-body">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <p style="font-size:16px; margin:0;">
                            <strong>Q<?php echo $i + 1; ?>. <?php echo h(stripslashes($q['question_text'])); ?></strong>
                        </p>
                        <div>
                            <span class="label label-info"><?php echo strtoupper(str_replace('_', ' ', $qt)); ?></span>
                            <span class="badge" style="background:#5029D6;"><?php echo number_format($q_marks, 1); ?> Marks</span>
                        </div>
                    </div>

                    <?php if ($qt === 'mcq'): ?>
                        <ul class="list-unstyled" style="margin-left: 15px;">
                            <?php foreach (array('a', 'b', 'c', 'd') as $opt): 
                                $opt_text = $q['option_' . $opt] ?? '';
                                if($opt_text === null || $opt_text === '') continue;
                                $isCorrect = (strtolower($q['correct_option']) === $opt);
                            ?>
                                <li<?php echo $isCorrect ? ' class="text-success" style="font-weight:bold;"' : ''; ?>>
                                    <?php echo strtoupper($opt); ?>. <?php echo h(stripslashes($opt_text)); ?>
                                    <?php if ($isCorrect): ?>
                                        <span class="label label-success">Correct</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif ($qt === 'true_false'): ?>
                        <p style="margin-left:15px;">Correct Statement: <strong><span class="label label-warning"><?php echo h($q['correct_answer'] ?? $q['correct_option']); ?></span></strong></p>
                    <?php elseif ($qt === 'essay'): ?>
                        <p style="margin-left:15px;" class="text-muted"><em>Open-ended essay prompt evaluated manually by examiner upon submission.</em></p>
                    <?php elseif ($qt === 'matching'): 
                        $pairs = Grader::parseMatchingPairs($q['options_data'] ?? '', $q['correct_answer'] ?? '');
                    ?>
                        <ul style="margin-left: 20px;">
                            <?php foreach($pairs as $p): ?>
                                <li><?php echo h($p['left']); ?> &rarr; <strong><?php echo h($p['right']); ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="margin-left:15px;">Expected Answer(s): <code><?php echo h($q['correct_answer'] ?? $q['option_a']); ?></code></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="pending_exams.php" class="btn btn-default">&larr; Back to Pending Exams</a>
</div>
<?php include('footer.php'); ?>