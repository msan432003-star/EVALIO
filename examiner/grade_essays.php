<?php 
require('../config/autoload.php');

if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
} 

$dao = new DataAccess(); 
$examiner_id = (int)$_SESSION['examiner_id'];
$grader = new Grader();

// Capture parameters for single submission review
$view_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$view_student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// Handle Form Grading Submission
if(isset($_POST['btn_save_grades'])) {
    $post_exam_id = (int)$_POST['exam_id'];
    $post_student_id = (int)$_POST['student_id'];

    // Verify examiner ownership
    $examCheck = $dao->getData(array('id', 'created_by'), 'exams', 'id=' . $post_exam_id);
    if(empty($examCheck) || (int)$examCheck[0]['created_by'] !== $examiner_id) {
        echo "<script>alert('Unauthorized: You can only grade exams you created.'); window.location.href='grade_essays.php';</script>";
        exit();
    }

    $essay_marks = isset($_POST['essay_marks']) && is_array($_POST['essay_marks']) ? $_POST['essay_marks'] : array();
    $feedbacks = isset($_POST['feedback']) && is_array($_POST['feedback']) ? $_POST['feedback'] : array();
    $overrides = isset($_POST['override_marks']) && is_array($_POST['override_marks']) ? $_POST['override_marks'] : array();

    // 1. Process Essay marks
    foreach($essay_marks as $answer_id => $marks_val) {
        $aid = (int)$answer_id;
        $fb = isset($feedbacks[$aid]) ? trim($feedbacks[$aid]) : '';
        $grader->gradeEssayAnswer($aid, (float)$marks_val, $fb, $examiner_id);
    }

    // 2. Process any overridden auto-graded marks
    foreach($overrides as $answer_id => $m_val) {
        if($m_val !== '') {
            $aid = (int)$answer_id;
            $fb = isset($feedbacks[$aid]) ? trim($feedbacks[$aid]) : '';
            $grader->overrideAnswerGrade($aid, (float)$m_val, $fb, $examiner_id);
        }
    }

    echo "<script>
        alert('Grades and feedback saved successfully! Student total score has been recalculated.');
        window.location.href='grade_essays.php?exam_id=" . $post_exam_id . "&student_id=" . $post_student_id . "';
    </script>";
    exit();
}

include("header.php"); 
?>

<div class="container-fluid" style="padding: 0; margin-top: 10px; margin-bottom: 60px;">

    <?php if($view_exam_id > 0 && $view_student_id > 0) { 
        // -------------------------------------------------------------
        // DETAIL VIEW: GRADE INDIVIDUAL STUDENT SUBMISSION
        // -------------------------------------------------------------
        $sub = $grader->getSubmissionDetails($view_exam_id, $view_student_id);
        $exam = $sub['exam'];
        $student = $sub['student'];
        $result = $sub['result'];
        $questions = $sub['questions'];
        $answers = $sub['answers'];

        if(!$exam || !$student || !$result || (int)($exam['created_by'] ?? 0) !== $examiner_id || ($exam['category'] ?? '') !== 'Certification') {
            echo "<div class='alert alert-danger'>Submission not found or you do not have permission to review it. <a href='grade_essays.php'>Back to list</a></div>";
        } else {
            $is_pending = ($result['grading_status'] === 'pending_review');
    ?>
            <div class="row">
                <div class="col-md-12">
                    <a href="grade_essays.php" class="btn btn-default btn-sm" style="margin-bottom: 15px;">
                        &larr; Back to All Submissions
                    </a>
                </div>
            </div>

            <!-- Submission Header Card -->
            <div class="panel panel-default" style="border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <div class="panel-body" style="padding: 22px;">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 style="margin-top: 0; font-weight: 700; color: var(--text-primary);">
                                Grading Submission: <?= e($student['name'] ?? 'Student'); ?>
                            </h3>
                            <p class="text-muted" style="font-size: 15px; margin-bottom: 5px;">
                                <strong>Exam:</strong> <?= e($exam['title']); ?> &bull; 
                                <strong>Category:</strong> <?= e($exam['category']); ?> &bull; 
                                <strong>Student Email:</strong> <?= e($student['email'] ?? 'N/A'); ?>
                            </p>
                            <p class="text-muted" style="font-size: 14px; margin-bottom: 0;">
                                <strong>Submitted On:</strong> <?= date('d M Y, h:i A', strtotime($result['submitted_at'])); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <div style="font-size: 24px; font-weight: 700; color: var(--active-menu-color);">
                                <?= number_format($result['marks_obtained'], 2); ?> / <?= number_format($result['total_marks'], 2); ?>
                            </div>
                            <div style="margin-top: 6px;">
                                <?php if($is_pending) { ?>
                                    <span class="status-badge-pending"><i class="fa fa-clock-o"></i> Pending Review (Provisional)</span>
                                <?php } else { ?>
                                    <span class="status-badge-completed"><i class="fa fa-check-circle"></i> Evaluation Completed</span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions & Student Answers Form -->
            <form method="POST" action="">
                <input type="hidden" name="exam_id" value="<?= $view_exam_id; ?>">
                <input type="hidden" name="student_id" value="<?= $view_student_id; ?>">

                <?php 
                $q_num = 1;
                foreach($questions as $q) { 
                    $qid = (int)$q['id'];
                    $qt = !empty($q['question_type']) ? $q['question_type'] : 'mcq';
                    $q_marks = (float)($q['marks'] ?? 1.00);
                    $ans_row = isset($answers[$qid]) ? $answers[$qid] : null;
                    $student_ans_text = $ans_row['answer_given'] ?? '';
                    $marks_awarded = $ans_row['marks_awarded'] ?? null;
                    $ans_status = $ans_row['status'] ?? 'pending_review';
                    $feedback_text = $ans_row['feedback'] ?? '';
                    $is_essay = ($qt === 'essay');
                ?>
                    <div class="answer-card <?= ($is_essay ? 'essay-card' : 'auto-card'); ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <span style="font-size: 17px; font-weight: 700; color: var(--text-primary);">
                                    Q<?= $q_num++; ?>. <?= e($q['question_text']); ?>
                                </span>
                            </div>
                            <div>
                                <span class="label label-default" style="text-transform: uppercase;"><?= strtoupper(str_replace('_', ' ', $qt)); ?></span>
                                <span class="badge" style="background:#0284c7; color:#fff;"><?= number_format($q_marks, 1); ?> <?= ($q_marks == 1 ? 'Mark' : 'Marks'); ?></span>
                            </div>
                        </div>

                        <!-- 1. AUTO-GRADED QUESTION DETAILS -->
                        <?php if(!$is_essay) { ?>
                            <div style="margin-top: 12px;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="text-muted" style="font-size: 12px; text-transform: uppercase;">Student's Answer:</label>
                                        <div style="font-size: 15px; font-weight: 600; color: <?= ($marks_awarded > 0 ? '#28a745' : '#dc3545'); ?>;">
                                            <?php 
                                            if($qt === 'mcq') {
                                                $opt_key = strtoupper(trim($student_ans_text));
                                                $opt_content = $q['option_' . strtolower($opt_key)] ?? '';
                                                echo 'Option ' . e($opt_key) . (!empty($opt_content) ? ' (' . e($opt_content) . ')' : '');
                                            } elseif($qt === 'matching') {
                                                $decoded_ans = json_decode($student_ans_text, true);
                                                $pairs = Grader::parseMatchingPairs($q['options_data'] ?? '', $q['correct_answer'] ?? '');
                                                if(is_array($decoded_ans) && !empty($pairs)) {
                                                    echo '<ul style="padding-left: 20px; margin-bottom: 0;">';
                                                    foreach($pairs as $pidx => $p) {
                                                        $chosen = $decoded_ans[$pidx] ?? ($decoded_ans[$p['left']] ?? 'Not answered');
                                                        $is_pair_correct = (Grader::normalizeText($chosen) === Grader::normalizeText($p['right']));
                                                        echo '<li>' . e($p['left']) . ' &rarr; <strong>' . e($chosen) . '</strong> ' . 
                                                             ($is_pair_correct ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>') . '</li>';
                                                    }
                                                    echo '</ul>';
                                                } else {
                                                    echo '<em>No matching pairs answered</em>';
                                                }
                                            } else {
                                                echo e($student_ans_text !== '' ? $student_ans_text : '(No answer given)');
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted" style="font-size: 12px; text-transform: uppercase;">Expected Correct Answer:</label>
                                        <div style="font-size: 15px; color: #28a745; font-weight: 600;">
                                            <?php 
                                            if($qt === 'mcq') {
                                                $c_opt = strtoupper(trim($q['correct_option']));
                                                $c_content = $q['option_' . strtolower($c_opt)] ?? '';
                                                echo 'Option ' . e($c_opt) . (!empty($c_content) ? ' (' . e($c_content) . ')' : '');
                                            } elseif($qt === 'matching') {
                                                $pairs = Grader::parseMatchingPairs($q['options_data'] ?? '', $q['correct_answer'] ?? '');
                                                echo '<small>' . count($pairs) . ' correct pair mappings</small>';
                                            } else {
                                                echo e($q['correct_answer'] ?? $q['option_a']);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="grade-bar-summary">
                                    <div>
                                        <span class="label label-success"><i class="fa fa-magic"></i> Auto-Graded</span>
                                        <span style="font-weight: 700; margin-left: 10px; color: var(--text-primary);">
                                            Awarded: <?= number_format($marks_awarded !== null ? $marks_awarded : 0, 2); ?> / <?= number_format($q_marks, 2); ?> Marks
                                        </span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-default btn-xs" onclick="document.getElementById('override_box_<?= $ans_row['id']; ?>').style.display = (document.getElementById('override_box_<?= $ans_row['id']; ?>').style.display === 'none' ? 'block' : 'none');">
                                            <i class="fa fa-pencil"></i> Override Mark / Feedback
                                        </button>
                                    </div>
                                </div>

                                <!-- Optional Examiner Override Box -->
                                <?php if($ans_row) { ?>
                                    <div id="override_box_<?= $ans_row['id']; ?>" class="override-expand-box" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-3 form-group">
                                                <label>Adjust Marks (0 to <?= $q_marks; ?>):</label>
                                                <input type="number" name="override_marks[<?= $ans_row['id']; ?>]" class="form-control input-sm" value="<?= ($marks_awarded !== null ? $marks_awarded : ''); ?>" min="0" max="<?= $q_marks; ?>" step="0.5">
                                            </div>
                                            <div class="col-md-9 form-group">
                                                <label>Examiner Feedback / Audit Note:</label>
                                                <input type="text" name="feedback[<?= $ans_row['id']; ?>]" class="form-control input-sm" value="<?= e($feedback_text); ?>" placeholder="Optional explanation for adjustment">
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                        <!-- 2. DESCRIPTIVE / ESSAY QUESTION GRADING -->
                        <?php } else { ?>
                            <div style="margin-top: 12px;">
                                <label class="text-muted" style="font-size: 13px; text-transform: uppercase;">Student Essay Response:</label>
                                <div class="student-response-box">
                                    <?= (!empty($student_ans_text) ? nl2br(htmlspecialchars((string)$student_ans_text, ENT_QUOTES, 'UTF-8')) : '<em class="text-muted">No essay written by student.</em>'); ?>
                                </div>

                                <?php if($ans_row) { ?>
                                    <div class="grading-input-box">
                                        <h5 style="margin-top: 0; font-weight: 700; color: var(--active-menu-color);">
                                            <i class="fa fa-gavel"></i> Manual Essay Evaluation
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-3 form-group">
                                                <label>Marks Awarded (Max <?= $q_marks; ?>): <span class="text-danger">*</span></label>
                                                <input type="number" name="essay_marks[<?= $ans_row['id']; ?>]" class="form-control input-lg" value="<?= ($marks_awarded !== null ? $marks_awarded : ''); ?>" min="0" max="<?= $q_marks; ?>" step="0.5" required placeholder="0.0">
                                            </div>
                                            <div class="col-md-9 form-group">
                                                <label>Examiner Feedback / Rubric Notes:</label>
                                                <textarea name="feedback[<?= $ans_row['id']; ?>]" class="form-control" rows="2" placeholder="Constructive feedback for the student..."><?= e($feedback_text); ?></textarea>
                                            </div>
                                        </div>
                                        <?php if($ans_row['status'] === 'manually_graded' && !empty($ans_row['graded_at'])) { ?>
                                            <small class="text-muted">
                                                <i class="fa fa-check"></i> Previously graded on <?= date('d M Y, h:i A', strtotime($ans_row['graded_at'])); ?>
                                            </small>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>

                    </div>
                <?php } ?>

                <!-- Bottom Save Button -->
                <div class="panel panel-default" style="padding: 20px; text-align: center; border-radius: 8px;">
                    <button type="submit" name="btn_save_grades" class="btn btn-primary btn-lg" style="font-weight: 700; padding: 12px 35px;">
                        <i class="fa fa-save"></i> Save &amp; Finalize Grades
                    </button>
                    <a href="grade_essays.php" class="btn btn-default btn-lg" style="margin-left: 12px;">Back to Submissions</a>
                </div>
            </form>

    <?php 
        } // end valid detail view
    } else { 
        // -------------------------------------------------------------
        // LIST VIEW: ALL EXAMS & SUBMISSIONS REQUIRING GRADING
        // -------------------------------------------------------------
        
        // 1. Process GET Filters
        $filter_exam_id = 0;
        if (isset($_GET['exam_id']) && $_GET['exam_id'] !== '') {
            $filter_exam_id = (int)$_GET['exam_id'];
        } elseif (isset($_GET['exam']) && $_GET['exam'] !== '') {
            $filter_exam_id = (int)$_GET['exam'];
        }

        $filter_course_id = 0;
        if (isset($_GET['course_id']) && $_GET['course_id'] !== '') {
            $filter_course_id = (int)$_GET['course_id'];
        } elseif (isset($_GET['course']) && $_GET['course'] !== '') {
            $filter_course_id = (int)$_GET['course'];
        }

        $from_date_raw = '';
        if (!empty($_GET['from_date'])) {
            $from_date_raw = trim($_GET['from_date']);
        } elseif (!empty($_GET['from'])) {
            $from_date_raw = trim($_GET['from']);
        }

        $to_date_raw = '';
        if (!empty($_GET['to_date'])) {
            $to_date_raw = trim($_GET['to_date']);
        } elseif (!empty($_GET['to'])) {
            $to_date_raw = trim($_GET['to']);
        }

        // Validate dates (Y-m-d)
        $valid_from = null;
        if ($from_date_raw !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $from_date_raw);
            if ($d && $d->format('Y-m-d') === $from_date_raw) {
                $valid_from = $from_date_raw;
            }
        }

        $valid_to = null;
        if ($to_date_raw !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $to_date_raw);
            if ($d && $d->format('Y-m-d') === $to_date_raw) {
                $valid_to = $to_date_raw;
            }
        }

        // 2. Fetch dropdown data
        // Fetch certification exams created by this examiner (mock exams are auto-graded, never listed here)
        $examiner_exams = $dao->getData('*', 'exams', 'created_by = ' . $examiner_id . " AND category = 'Certification' ORDER BY title ASC, id DESC");
        if (!is_array($examiner_exams)) {
            $examiner_exams = array();
        }
        $exam_ids = !empty($examiner_exams) ? array_map('intval', array_column($examiner_exams, 'id')) : array();

        $exam_titles = array();
        foreach($examiner_exams as $ex) {
            $exam_titles[$ex['id']] = $ex['title'];
        }

        // Fetch available courses
        $courses_list = $dao->getData(array('id', 'course_name'), 'courses', '1 ORDER BY course_name ASC');
        if (!is_array($courses_list)) {
            $courses_list = array();
        }

        // 3. Build filtered submissions query with prepared statement
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        $sql = "SELECT r.*, e.title AS exam_title, e.course_id 
                FROM exam_results r 
                INNER JOIN exams e ON e.id = r.exam_id 
                WHERE e.created_by = ? AND e.category = 'Certification'";

        $params = array($examiner_id);
        $types = "i";

        if ($filter_exam_id > 0) {
            $sql .= " AND r.exam_id = ?";
            $params[] = $filter_exam_id;
            $types .= "i";
        }

        if ($filter_course_id > 0) {
            $sql .= " AND e.course_id = ?";
            $params[] = $filter_course_id;
            $types .= "i";
        }

        if ($valid_from !== null) {
            $sql .= " AND r.submitted_at >= ?";
            $params[] = $valid_from . ' 00:00:00';
            $types .= "s";
        }

        if ($valid_to !== null) {
            $sql .= " AND r.submitted_at <= ?";
            $params[] = $valid_to . ' 23:59:59';
            $types .= "s";
        }

        $sql .= " ORDER BY r.submitted_at DESC";

        $all_submissions = array();
        if ($conn) {
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $all_submissions[] = $row;
                    }
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_close($conn);
        }

        // Student details lookup
        $student_lookup = array();
        if(!empty($all_submissions)) {
            $student_ids = array_unique(array_map('intval', array_column($all_submissions, 'student_id')));
            if(!empty($student_ids)) {
                $st_list = implode(',', $student_ids);
                $st_records = $dao->getData('*', 'examinees', 'id IN (' . $st_list . ')');
                if(!empty($st_records)) {
                    foreach($st_records as $st) {
                        $student_lookup[$st['id']] = $st;
                    }
                }
            }
        }

        // Calculate pending essays count per (exam_id, student_id)
        $pending_counts = array();
        if(!empty($exam_ids)) {
            $pending_rows = $dao->query(
                "SELECT exam_id, student_id, COUNT(*) AS p_count
                 FROM student_answers
                 WHERE exam_id IN (" . implode(',', $exam_ids) . ")
                   AND status = 'pending_review'
                 GROUP BY exam_id, student_id"
            );
            if(!empty($pending_rows)) {
                foreach($pending_rows as $pr) {
                    $pending_counts[$pr['exam_id'] . '_' . $pr['student_id']] = (int)$pr['p_count'];
                }
            }
        }
    ?>

        <div class="row">
            <div class="col-md-12">
                <h2>Grade Essays &amp; Submissions</h2>
                <p class="text-muted" style="font-size: 15px;">
                    Review student submissions, grade descriptive/essay questions, leave feedback, and optionally adjust auto-graded scores.
                </p>
                <hr>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="panel panel-default" style="border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px;">
            <div class="panel-body" style="padding: 16px 20px;">
                <form method="GET" action="grade_essays.php" class="form-inline" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="exam_id" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Exam</label>
                        <select name="exam_id" id="exam_id" class="form-control input-sm" style="min-width: 180px;">
                            <option value="0">All Exams</option>
                            <?php foreach ($examiner_exams as $ex) { ?>
                                <option value="<?= (int)$ex['id']; ?>" <?= ($filter_exam_id === (int)$ex['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string)$ex['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="course_id" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">Course</label>
                        <select name="course_id" id="course_id" class="form-control input-sm" style="min-width: 160px;">
                            <option value="0">All Courses</option>
                            <?php foreach ($courses_list as $c) { ?>
                                <option value="<?= (int)$c['id']; ?>" <?= ($filter_course_id === (int)$c['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string)$c['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="from_date" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">From Date</label>
                        <input type="date" name="from_date" id="from_date" class="form-control input-sm" value="<?= htmlspecialchars((string)$from_date_raw, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="to_date" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">To Date</label>
                        <input type="date" name="to_date" id="to_date" class="form-control input-sm" value="<?= htmlspecialchars((string)$to_date_raw, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary btn-sm" style="font-weight: 600;">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <a href="grade_essays.php" class="btn btn-default btn-sm" style="margin-left: 5px;">
                            <i class="fa fa-refresh"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="panel panel-default" style="box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-radius: 8px;">
            <div class="panel-heading" style="font-weight:700; font-size:16px;">
                <i class="fa fa-users"></i> Student Exam Submissions (<?= count($all_submissions); ?>)
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Exam Title</th>
                            <th>Submitted On</th>
                            <th>Score / Total</th>
                            <th>Pending Essays</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($all_submissions)) { 
                            foreach($all_submissions as $sub_item) {
                                $eid = (int)$sub_item['exam_id'];
                                $sid = (int)$sub_item['student_id'];
                                $st_info = $student_lookup[$sid] ?? null;
                                $st_name = $st_info['name'] ?? ('Student #' . $sid);
                                $st_email = $st_info['email'] ?? '';
                                $ex_title = $sub_item['exam_title'] ?? $exam_titles[$eid] ?? ('Exam #' . $eid);
                                $pending_essays = $pending_counts[$eid . '_' . $sid] ?? 0;
                                $g_status = $sub_item['grading_status'] ?? 'completed';
                                $is_pending = ($g_status === 'pending_review' || $pending_essays > 0);
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars((string)$st_name, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars((string)$st_email, ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td><?= htmlspecialchars((string)$ex_title, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($sub_item['submitted_at'])); ?></td>
                                <td>
                                    <strong><?= number_format($sub_item['marks_obtained'] ?? $sub_item['score'], 2); ?></strong> / <?= number_format($sub_item['total_marks'] ?? $sub_item['total_questions'], 2); ?>
                                </td>
                                <td>
                                    <?php if($pending_essays > 0) { ?>
                                        <span class="badge" style="background: #e65100;"><?= $pending_essays; ?> Pending</span>
                                    <?php } else { ?>
                                        <span class="text-muted"><i class="fa fa-check text-success"></i> All Graded</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if($is_pending) { ?>
                                        <span class="status-badge-pending"><i class="fa fa-clock-o"></i> Pending Review</span>
                                    <?php } else { ?>
                                        <span class="status-badge-completed"><i class="fa fa-check-circle"></i> Completed</span>
                                    <?php } ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="grade_essays.php?exam_id=<?= $eid; ?>&student_id=<?= $sid; ?>" class="btn <?= ($is_pending ? 'btn-primary' : 'btn-default'); ?> btn-sm">
                                        <?= ($is_pending ? '<i class="fa fa-pencil"></i> Grade Submission' : '<i class="fa fa-eye"></i> View / Edit'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } 
                        } else { ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 30px;">
                                    <p class="text-muted" style="margin-bottom: 0;">No student submissions found.</p>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } ?>

</div>

<?php include('footer.php'); ?>