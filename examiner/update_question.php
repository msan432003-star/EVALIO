<?php
require('../config/autoload.php');

// 🔒 Examiner Auth Check
if (!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: myexams.php');
    exit();
}

$examiner_id = (int)$_SESSION['examiner_id'];
$dao = new DataAccess();

$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$exam_id     = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

if ($question_id <= 0 || $exam_id <= 0) {
    echo "<script>alert('Error: Invalid question or exam ID.'); window.location.href='myexams.php';</script>";
    exit();
}

// 1. Verify exam ownership
$exam_res = $dao->getData('*', 'exams', "id = $exam_id AND created_by = $examiner_id");
if (empty($exam_res)) {
    echo "<script>alert('Error: Exam not found or permission denied.'); window.location.href='myexams.php';</script>";
    exit();
}
$current_exam = $exam_res[0];
$category     = strtolower(trim($current_exam['category'] ?? ''));
$status       = strtolower(trim($current_exam['status'] ?? 'draft'));

// 2. Lock check: Certification exam in 'pending' or 'approved' => refuse
if ($category === 'certification' && in_array($status, array('pending', 'approved'))) {
    echo "<script>alert('Error: This exam is locked (pending review or approved). Questions cannot be modified.'); window.location.href='addquestion.php?exam_id=$exam_id';</script>";
    exit();
}

// 3. Verify question belongs to this exam
$q_res = $dao->getData('*', 'questions', "id = $question_id AND exam_id = $exam_id");
if (empty($q_res)) {
    echo "<script>alert('Error: Question not found in this exam.'); window.location.href='addquestion.php?exam_id=$exam_id';</script>";
    exit();
}

// 4. Capture & validate submitted question data
$q_type        = isset($_POST['question_type']) ? trim($_POST['question_type']) : 'mcq';
$marks         = isset($_POST['marks']) && (float)$_POST['marks'] > 0 ? (float)$_POST['marks'] : 1.00;
$question_text = trim($_POST['question_text'] ?? '');

$is_mock = ($category === 'mock');
if ($is_mock && $q_type === 'essay') {
    echo "<script>alert('Descriptive / Essay questions are only allowed in certification exams.'); window.location.href='addquestion.php?exam_id=$exam_id';</script>";
    exit();
}

if (empty($question_text)) {
    echo "<script>alert('Error: Question text cannot be empty.'); window.location.href='addquestion.php?exam_id=$exam_id';</script>";
    exit();
}

$data = array(
    'question_type'  => $q_type,
    'marks'          => $marks,
    'question_text'  => $question_text,
    'option_a'       => '',
    'option_b'       => '',
    'option_c'       => '',
    'option_d'       => '',
    'correct_option' => '',
    'correct_answer' => '',
    'options_data'   => ''
);

switch ($q_type) {
    case 'mcq':
        $data['option_a'] = trim($_POST['option_a'] ?? '');
        $data['option_b'] = trim($_POST['option_b'] ?? '');
        $data['option_c'] = trim($_POST['option_c'] ?? '');
        $data['option_d'] = trim($_POST['option_d'] ?? '');
        $data['correct_option'] = strtoupper(trim($_POST['correct_option'] ?? ''));
        $data['correct_answer'] = $data['correct_option'];
        break;

    case 'essay':
        $data['correct_option'] = '';
        $data['correct_answer'] = '';
        break;

    case 'short_answer':
        $data['correct_answer'] = trim($_POST['short_correct_answer'] ?? '');
        break;

    case 'true_false':
        $tf_ans = trim($_POST['tf_correct_answer'] ?? 'True');
        $data['correct_answer'] = ($tf_ans === 'False') ? 'False' : 'True';
        $data['correct_option'] = $data['correct_answer'];
        break;

    case 'fill_blank':
        $data['correct_answer'] = trim($_POST['blank_correct_answer'] ?? '');
        break;

    case 'matching':
        $left_items  = isset($_POST['match_left']) && is_array($_POST['match_left']) ? $_POST['match_left'] : array();
        $right_items = isset($_POST['match_right']) && is_array($_POST['match_right']) ? $_POST['match_right'] : array();

        $pairs = array();
        foreach ($left_items as $idx => $l_text) {
            $l_clean = trim($l_text);
            $r_clean = isset($right_items[$idx]) ? trim($right_items[$idx]) : '';
            if ($l_clean !== '' && $r_clean !== '') {
                $pairs[] = array('left' => $l_clean, 'right' => $r_clean);
            }
        }
        if (empty($pairs)) {
            $pairs = array(
                array('left' => 'Item 1', 'right' => 'Match 1'),
                array('left' => 'Item 2', 'right' => 'Match 2')
            );
        }
        $data['options_data']   = json_encode(array('pairs' => $pairs), JSON_UNESCAPED_UNICODE);
        $data['correct_answer'] = $data['options_data'];
        break;
}

// 5. Update row in database
$update_ok = $dao->update($data, 'questions', "id = $question_id AND exam_id = $exam_id");

if ($update_ok) {
    // 6. Recalculate total marks for the exam
    $remaining_q = $dao->getData('marks', 'questions', "exam_id = $exam_id");
    $total_m = 0.00;
    if (!empty($remaining_q)) {
        foreach ($remaining_q as $rq) {
            $total_m += (float)($rq['marks'] ?? 0);
        }
    }
    $dao->update(array('total_marks' => $total_m), 'exams', "id = $exam_id");

    echo "<script>alert('Question updated successfully!'); window.location.href='addquestion.php?exam_id=$exam_id';</script>";
} else {
    echo "<script>alert('Failed to update question. Please try again.'); window.location.href='addquestion.php?exam_id=$exam_id';</script>";
}
exit();
