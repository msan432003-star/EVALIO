<?php 
require('../config/autoload.php');

if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
} 
include("header.php"); 

$dao = new DataAccess(); 
$examiner_id = (int)$_SESSION['examiner_id'];

// 1. Capture exam_id
$exam_id = isset($_REQUEST['exam_id']) ? (int)$_REQUEST['exam_id'] : 0;

// 2. Fetch list of available exams created by this examiner (fallback selector)
$all_exams = $dao->getData('*', 'exams', 'created_by = ' . $examiner_id . ' ORDER BY id DESC');

// 3. Exam info and mode
$current_exam = null;
$exam_type_mode = 'hybrid'; // Default
$exam_title = "Select an Exam";

if($exam_id > 0) {
    $exam_res = $dao->getData('*', 'exams', 'id = ' . $exam_id . ' AND created_by = ' . $examiner_id);
    if(!empty($exam_res)) {
        $current_exam = $exam_res[0];
        $exam_type_mode = !empty($current_exam['exam_type']) ? $current_exam['exam_type'] : 'hybrid';
        $exam_title = $current_exam['title'];
    }
}
$is_mock = ($current_exam !== null && stripos($current_exam['category'] ?? '', 'mock') !== false);
$is_locked = ($current_exam !== null 
    && in_array(strtolower(trim($current_exam['status'] ?? '')), array('pending', 'approved')));

// 4. Handle Question Deletion
if(isset($_POST['btn_delete_question'])) {
    if($is_locked) {
        echo "<script>alert('Error: This exam is locked (pending approval or approved). Questions cannot be deleted.'); window.location.href='addquestion.php?exam_id=" . $exam_id . "';</script>";
        exit();
    }

    $delete_qid = (int)$_POST['delete_question_id'];
    if($delete_qid > 0 && $exam_id > 0) {
        $dao->delete('questions', 'id = ' . $delete_qid . ' AND exam_id = ' . $exam_id);
        
        // Recalculate exam total marks
        $remaining_q = $dao->getData('marks', 'questions', 'exam_id = ' . $exam_id);
        $total_m = 0.00;
        if(!empty($remaining_q)) {
            foreach($remaining_q as $rq) { $total_m += (float)$rq['marks']; }
        }
        $dao->update(array('total_marks' => $total_m), 'exams', 'id = ' . $exam_id);

        echo "<script>alert('Question removed.'); window.location.href='addquestion.php?exam_id=" . $exam_id . "';</script>";
        exit();
    }
}

// 5. Process Question Creation
if(isset($_POST["btn_save_question"])) {
    $submitted_exam_id = (int)$_POST['exam_id'];

    if($submitted_exam_id > 0) {
        $submitted_exam = $dao->getData('*', 'exams', 'id = ' . $submitted_exam_id . ' AND created_by = ' . $examiner_id);
        if(empty($submitted_exam)) {
            echo "<script> alert('Error: Please select a valid Exam.'); </script>";
        } else {
            $sub_cat = strtolower(trim($submitted_exam[0]['category'] ?? ''));
            $sub_stat = strtolower(trim($submitted_exam[0]['status'] ?? 'draft'));

            if(in_array($sub_stat, array('pending', 'approved'))) {
                echo "<script>alert('Error: This exam is locked (published or pending review). Questions cannot be added.'); window.location.href='addquestion.php?exam_id=" . $submitted_exam_id . "';</script>";
                exit();
            }

            $q_type = isset($_POST['question_type']) ? trim($_POST['question_type']) : 'mcq';
            $marks = isset($_POST['marks']) && (float)$_POST['marks'] > 0 ? (float)$_POST['marks'] : 1.00;
            $question_text = trim($_POST['question_text']);

            $submitted_is_mock = (stripos($sub_cat, 'mock') !== false);

            if($submitted_is_mock && $q_type === 'essay') {
                echo "<script> alert('Descriptive / Essay questions are only allowed in certification exams.'); </script>";
            } elseif(empty($question_text)) {
                echo "<script> alert('Error: Question text / prompt cannot be empty.'); </script>";
            } else {
                $data = array(
                    'exam_id'        => $submitted_exam_id,
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

                switch($q_type) {
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
                        $left_items = isset($_POST['match_left']) && is_array($_POST['match_left']) ? $_POST['match_left'] : array();
                        $right_items = isset($_POST['match_right']) && is_array($_POST['match_right']) ? $_POST['match_right'] : array();
                        
                        $pairs = array();
                        foreach($left_items as $idx => $l_text) {
                            $l_clean = trim($l_text);
                            $r_clean = isset($right_items[$idx]) ? trim($right_items[$idx]) : '';
                            if($l_clean !== '' && $r_clean !== '') {
                                $pairs[] = array('left' => $l_clean, 'right' => $r_clean);
                            }
                        }

                        if(empty($pairs)) {
                            $pairs = array(
                                array('left' => 'Item 1', 'right' => 'Match 1'),
                                array('left' => 'Item 2', 'right' => 'Match 2')
                            );
                        }

                        $data['options_data'] = json_encode(array('pairs' => $pairs), JSON_UNESCAPED_UNICODE);
                        $data['correct_answer'] = $data['options_data'];
                        break;
                }

                if($dao->insert($data, "questions")) {
                    // Update total marks for this exam
                    $all_q = $dao->getData('marks', 'questions', 'exam_id = ' . $submitted_exam_id);
                    $total_m = 0.00;
                    if(!empty($all_q)) {
                        foreach($all_q as $rq) { $total_m += (float)$rq['marks']; }
                    }
                    $dao->update(array('total_marks' => $total_m), 'exams', 'id = ' . $submitted_exam_id);

                    echo "<script> alert('Question added successfully!'); window.location.href='addquestion.php?exam_id=" . $submitted_exam_id . "'; </script>";
                    exit();
                } else {
                    echo "<script> alert('Failed to save question. Please check the inputs.'); </script>";
                }
            }
        }
    } else {
        echo "<script> alert('Error: Please select a valid Exam.'); </script>";
    }
}

// 6. Fetch existing questions for this exam
$existing_questions = array();
$total_exam_marks = 0.00;
if($exam_id > 0) {
    $existing_questions = $dao->getData('*', 'questions', 'exam_id = ' . $exam_id . ' ORDER BY id ASC');
    if(!empty($existing_questions)) {
        foreach($existing_questions as $eq) {
            $total_exam_marks += (float)($eq['marks'] ?? 1.00);
        }
    }
}
?> 

<style>
    .q-type-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-mcq { background: rgba(56, 189, 248, 0.15); color: #0284c7; border: 1px solid rgba(56, 189, 248, 0.35); }
    [data-theme="dark"] .badge-mcq { color: #38bdf8; border-color: rgba(56, 189, 248, 0.4); }
    
    .badge-essay { background: rgba(244, 114, 182, 0.15); color: #db2777; border: 1px solid rgba(244, 114, 182, 0.35); }
    [data-theme="dark"] .badge-essay { color: #f472b6; border-color: rgba(244, 114, 182, 0.4); }
    
    .badge-short_answer { background: rgba(52, 211, 153, 0.15); color: #059669; border: 1px solid rgba(52, 211, 153, 0.35); }
    [data-theme="dark"] .badge-short_answer { color: #34d399; border-color: rgba(52, 211, 153, 0.4); }
    
    .badge-true_false { background: rgba(251, 191, 36, 0.15); color: #d97706; border: 1px solid rgba(251, 191, 36, 0.35); }
    [data-theme="dark"] .badge-true_false { color: #fbbf24; border-color: rgba(251, 191, 36, 0.4); }
    
    .badge-fill_blank { background: rgba(192, 132, 252, 0.15); color: #9333ea; border: 1px solid rgba(192, 132, 252, 0.35); }
    [data-theme="dark"] .badge-fill_blank { color: #c084fc; border-color: rgba(192, 132, 252, 0.4); }
    
    .badge-matching { background: rgba(45, 212, 191, 0.15); color: #0d9488; border: 1px solid rgba(45, 212, 191, 0.35); }
    [data-theme="dark"] .badge-matching { color: #2dd4bf; border-color: rgba(45, 212, 191, 0.4); }

    .badge-hybrid {
        background-color: #0284c7;
        color: #fff;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-single {
        background-color: rgba(148, 163, 184, 0.25);
        color: var(--text-muted);
        border: 1px solid var(--table-border-color);
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }

    .type-container, .modal-type-container {
        display: none;
        background: var(--input-bg);
        padding: 22px;
        border-radius: 10px;
        border: 1.5px solid var(--table-border-color);
        margin-top: 18px;
        margin-bottom: 20px;
    }
    .type-container.active, .modal-type-container.active {
        display: block;
    }
    .pair-row {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
        align-items: center;
    }
    .pair-row input {
        flex: 1;
    }
    .editor-panel {
        background: var(--card-bg);
        border: 1px solid var(--table-border-color);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.06);
        margin-bottom: 30px;
        overflow: hidden;
    }
    .editor-panel-heading {
        background: var(--table-header-bg);
        border-bottom: 1px solid var(--table-border-color);
        color: var(--text-primary);
        font-weight: 700;
        font-size: 16px;
        padding: 16px 22px;
    }
    .editor-panel-body {
        padding: 24px;
    }
    .editor-stats-well {
        background: var(--card-bg);
        border: 1px solid var(--table-border-color);
        border-radius: 10px;
        padding: 10px 18px;
        display: inline-flex;
        gap: 20px;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }

    /* Modal styling for Dark & Light Themes */
    .modal-content {
        background: var(--card-bg, #ffffff);
        color: var(--text-primary, #1e293b);
        border: 1px solid var(--table-border-color, #e2e8f0);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .modal-header {
        border-bottom: 1px solid var(--table-border-color, #e2e8f0);
        background: var(--table-header-bg, #f8fafc);
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    .modal-footer {
        border-top: 1px solid var(--table-border-color, #e2e8f0);
        background: var(--table-header-bg, #f8fafc);
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }
</style>

<div class="row" style="margin-bottom: 20px;">
    <!-- Header Banner -->
    <div class="col-md-7 col-sm-12">
        <h2 style="margin-top: 0; color: var(--text-primary); font-weight: 700;">Add Questions</h2>
        <?php if($current_exam) { ?>
            <div style="font-size: 15px; margin-top: 8px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; color: var(--text-muted);">
                <span><strong style="color: var(--text-primary);">Exam:</strong> <?= e($current_exam['title']); ?></span>
                <span class="label label-info" style="font-size: 12px; padding: 4px 8px;"><?= e($current_exam['category']); ?></span>
                <?php if($is_locked) { ?>
                    <span class="label label-warning" style="font-size: 12px; padding: 4px 8px;"><i class="fa fa-lock"></i> Locked (<?= htmlspecialchars(strtoupper((string)($current_exam['status'] ?? ''))); ?>)</span>
                <?php } else { ?>
                    <span class="label label-default" style="font-size: 12px; padding: 4px 8px;"><?= htmlspecialchars(strtoupper((string)($current_exam['status'] ?? 'DRAFT'))); ?></span>
                <?php } ?>
                <span>&bull; Mode:</span>
                <?php if($exam_type_mode === 'hybrid') { ?>
                    <span class="badge-hybrid"><i class="fa fa-cubes"></i> Hybrid / Mixed</span>
                <?php } else { ?>
                    <span class="badge-single"><i class="fa fa-lock"></i> Single Type (<?= strtoupper(str_replace('_', ' ', $exam_type_mode)); ?>)</span>
                <?php } ?>
                <a href="myexams.php" class="btn btn-xs btn-default" style="margin-left: 6px; font-weight: 600; padding: 4px 10px; border-radius: 6px; background: var(--card-bg); border: 1px solid var(--table-border-color); color: var(--text-primary);" title="Return to My Exams">
                    <i class="fa fa-arrow-left"></i> My Exams
                </a>
            </div>
        <?php } ?>
    </div>
    <div class="col-md-5 col-sm-12 text-right" style="padding-top: 5px;">
        <div class="editor-stats-well">
            <div>
                <span class="text-muted" style="font-size: 13px; font-weight: 500;">Total Questions:</span> 
                <span class="badge" style="background:#0284c7; font-size: 13px; padding: 4px 9px; margin-left: 4px;"><?= count($existing_questions); ?></span>
            </div>
            <div>
                <span class="text-muted" style="font-size: 13px; font-weight: 500;">Total Marks:</span> 
                <span class="badge" style="background:#059669; font-size: 13px; padding: 4px 9px; margin-left: 4px;"><?= number_format($total_exam_marks, 2); ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-12"><hr style="margin-top: 18px; margin-bottom: 24px; border-color: var(--hr-color);"></div>
</div>

<?php if($is_locked) { ?>
    <!-- Locked Exam Notice -->
    <div class="alert alert-warning" style="border-radius: 12px; font-size: 14px; padding: 18px 24px; margin-bottom: 30px; border-left: 5px solid #f59e0b;">
        <h4 style="margin-top: 0; font-weight: 700; color: #b45309;"><i class="fa fa-lock"></i> This Exam is Locked</h4>
        <p style="margin-bottom: 12px;">This certification exam is currently <strong><?= htmlspecialchars(strtoupper($current_exam['status'])); ?></strong>. Questions cannot be added, edited, or removed while awaiting admin review or after being approved.</p>
        <a href="myexams.php" class="btn btn-default btn-sm" style="font-weight: 600; background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--table-border-color);"><i class="fa fa-arrow-left"></i> Back to My Exams</a>
    </div>
<?php } else { ?>
    <!-- Question Creation Form -->
    <div class="editor-panel">
        <div class="editor-panel-heading">
            <i class="fa fa-plus-circle" style="color: #38bdf8; margin-right: 6px;"></i> Create New Question
        </div>
        <div class="editor-panel-body">
            <form action="" method="POST" id="questionForm"> 
                <div class="row">
                    <!-- Exam Selection -->
                    <div class="col-md-12 form-group">
                        <label style="color: var(--text-primary); font-weight: 600;">Exam:</label>
                        <?php if($exam_id > 0) { ?>
                            <input type="hidden" name="exam_id" value="<?= $exam_id; ?>">
                            <input type="text" class="form-control" value="<?= e($exam_title); ?> (ID: <?= $exam_id; ?>)" readonly style="background: var(--input-bg); color: var(--input-color); border: 1px solid var(--input-border); font-weight: 500;">
                        <?php } else { ?>
                            <select name="exam_id" class="form-control" required onchange="window.location.href='addquestion.php?exam_id='+this.value">
                                <option value="">-- Choose an Exam --</option>
                                <?php if(!empty($all_exams)) {
                                    foreach($all_exams as $ex) { ?>
                                        <option value="<?= (int)$ex['id']; ?>" <?= ($exam_id == $ex['id'] ? 'selected' : ''); ?>>
                                            <?= e($ex['title']); ?> (ID: <?= (int)$ex['id']; ?> - <?= e($ex['category']); ?>)
                                        </option>
                                <?php } 
                                } ?>
                            </select>
                        <?php } ?>
                    </div>

                    <!-- Question Type Selection -->
                    <div class="col-md-6 form-group">
                        <label style="color: var(--text-primary); font-weight: 600;">Question Type: <span class="text-danger">*</span></label>
                        <?php if($exam_type_mode === 'hybrid') { ?>
                            <select name="question_type" id="questionTypeSelect" class="form-control" required style="font-weight: 600;">
                                <option value="mcq">Multiple Choice (MCQ)</option>
                                <?php if(!$is_mock) { ?>
                                <option value="essay">Descriptive / Essay (Manual Grading)</option>
                                <?php } ?>
                                <option value="short_answer">Short Answer</option>
                                <option value="true_false">True / False</option>
                                <option value="fill_blank">Fill in the Blanks</option>
                                <option value="matching">Matching Type</option>
                            </select>
                        <?php } else { ?>
                            <!-- Single Type Mode: Locked -->
                            <input type="hidden" name="question_type" id="questionTypeSelect" value="<?= e($exam_type_mode); ?>">
                            <div class="form-control" style="background: var(--input-bg); border: 1px solid var(--input-border); font-weight:700; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                                <i class="fa fa-lock text-muted"></i> 
                                <?= strtoupper(str_replace('_', ' ', $exam_type_mode)); ?> (Locked by Exam Format)
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Marks / Score -->
                    <div class="col-md-6 form-group">
                        <label style="color: var(--text-primary); font-weight: 600;">Marks / Score: <span class="text-danger">*</span></label>
                        <input type="number" name="marks" id="marksInput" class="form-control" value="1.00" min="0.5" step="0.5" required>
                    </div>

                    <!-- Question Prompt / Text -->
                    <div class="col-md-12 form-group"> 
                        <label id="qTextLabel" style="color: var(--text-primary); font-weight: 600;">Question Text / Statement: <span class="text-danger">*</span></label>
                        <textarea name="question_text" id="questionTextInput" class="form-control" rows="3" required placeholder="Enter the question or statement here..."></textarea>
                    </div> 
                </div>

                <!-- 1. DYNAMIC TYPE: MCQ -->
                <div id="type_mcq" class="type-container">
                    <h5 style="font-weight: 700; color: #38bdf8; margin-top: 0; margin-bottom: 16px;"><i class="fa fa-check-square-o"></i> Multiple Choice Options</h5>
                    <div class="row">
                        <div class="col-md-6 form-group"> 
                            <label style="color: var(--text-primary); font-weight: 500;">Option A: <span class="text-danger">*</span></label>
                            <input type="text" name="option_a" class="form-control mcq-opt" placeholder="First choice"> 
                        </div> 
                        <div class="col-md-6 form-group"> 
                            <label style="color: var(--text-primary); font-weight: 500;">Option B: <span class="text-danger">*</span></label>
                            <input type="text" name="option_b" class="form-control mcq-opt" placeholder="Second choice"> 
                        </div> 
                        <div class="col-md-6 form-group"> 
                            <label style="color: var(--text-primary); font-weight: 500;">Option C: <span class="text-danger">*</span></label>
                            <input type="text" name="option_c" class="form-control mcq-opt" placeholder="Third choice"> 
                        </div> 
                        <div class="col-md-6 form-group"> 
                            <label style="color: var(--text-primary); font-weight: 500;">Option D: <span class="text-danger">*</span></label>
                            <input type="text" name="option_d" class="form-control mcq-opt" placeholder="Fourth choice"> 
                        </div> 
                        <div class="col-md-6 form-group"> 
                            <label style="color: var(--text-primary); font-weight: 500;">Correct Option: <span class="text-danger">*</span></label>
                            <select name="correct_option" class="form-control mcq-correct">
                                <option value="">-- Select Correct Option --</option>
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group" style="padding-top: 25px;">
                            <p class="text-muted" style="margin: 0; font-size: 13px;">
                                <i class="fa fa-info-circle"></i> Provide 4 choices and choose the corresponding answer key.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2. DYNAMIC TYPE: ESSAY -->
                <div id="type_essay" class="type-container">
                    <div class="alert alert-info" style="margin-bottom: 0;">
                        <i class="fa fa-info-circle"></i> <strong>Descriptive / Essay Question</strong><br>
                        Students will be provided an open-ended multi-line response area. Essay answers are marked as <em>Pending Examiner Review</em> upon student submission and can be evaluated on the <strong>Grade Essays</strong> page.
                    </div>
                </div>

                <!-- 3. DYNAMIC TYPE: SHORT ANSWER -->
                <div id="type_short_answer" class="type-container">
                    <h5 style="font-weight: 700; color: #34d399; margin-top: 0; margin-bottom: 14px;"><i class="fa fa-align-left"></i> Expected Answer</h5>
                    <div class="form-group">
                        <label style="color: var(--text-primary); font-weight: 500;">Correct Answer(s): <span class="text-danger">*</span></label>
                        <input type="text" name="short_correct_answer" class="form-control" placeholder="e.g. CPU or Central Processing Unit">
                        <p class="text-muted" style="font-size: 12px; margin-top: 6px; margin-bottom: 0;">
                            Tip: You can specify multiple acceptable variants separated by commas (e.g. <code>CPU, Central Processing Unit</code>). Auto-grading is case-insensitive.
                        </p>
                    </div>
                </div>

                <!-- 4. DYNAMIC TYPE: TRUE / FALSE -->
                <div id="type_true_false" class="type-container">
                    <h5 style="font-weight: 700; color: #fbbf24; margin-top: 0; margin-bottom: 14px;"><i class="fa fa-toggle-on"></i> Correct Boolean Choice</h5>
                    <div class="form-group">
                        <label style="color: var(--text-primary); font-weight: 500;">Select the correct statement truth value: <span class="text-danger">*</span></label>
                        <div style="margin-top: 8px;">
                            <label class="radio-inline" style="font-size: 16px; margin-right: 25px; color: var(--text-primary);">
                                <input type="radio" name="tf_correct_answer" value="True" checked> <strong><i class="fa fa-check text-success"></i> True</strong>
                            </label>
                            <label class="radio-inline" style="font-size: 16px; color: var(--text-primary);">
                                <input type="radio" name="tf_correct_answer" value="False"> <strong><i class="fa fa-times text-danger"></i> False</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 5. DYNAMIC TYPE: FILL IN THE BLANK -->
                <div id="type_fill_blank" class="type-container">
                    <h5 style="font-weight: 700; color: #c084fc; margin-top: 0; margin-bottom: 14px;"><i class="fa fa-pencil-square-o"></i> Blank Answer Value</h5>
                    <div class="form-group">
                        <label style="color: var(--text-primary); font-weight: 500;">Correct Blank Value(s): <span class="text-danger">*</span></label>
                        <input type="text" name="blank_correct_answer" class="form-control" placeholder="e.g. Nitrogen or N2">
                        <p class="text-muted" style="font-size: 12px; margin-top: 6px; margin-bottom: 0;">
                            Tip: Format your question text with <code>[blank]</code> or <code>___</code>. Put the correct missing text above. Multiple acceptable variants can be separated by commas.
                        </p>
                    </div>
                </div>

                <!-- 6. DYNAMIC TYPE: MATCHING -->
                <div id="type_matching" class="type-container">
                    <h5 style="font-weight: 700; color: #2dd4bf; margin-top: 0; margin-bottom: 10px;"><i class="fa fa-random"></i> Column Matching Pairs</h5>
                    <p class="text-muted" style="font-size: 13px; margin-bottom: 16px;">Define the corresponding Left &amp; Right matching pairs. Right-hand choices will be randomly shuffled when presented to students.</p>
                    
                    <div id="pairsContainer">
                        <div class="pair-row">
                            <input type="text" name="match_left[]" class="form-control" placeholder="Column A Item (e.g. France)">
                            <span style="font-weight: bold; color: #38bdf8; font-size: 18px;">&harr;</span>
                            <input type="text" name="match_right[]" class="form-control" placeholder="Matching Column B Item (e.g. Paris)">
                            <button type="button" class="btn btn-default btn-sm remove-pair" style="display:none;"><i class="fa fa-times"></i></button>
                        </div>
                        <div class="pair-row">
                            <input type="text" name="match_left[]" class="form-control" placeholder="Column A Item (e.g. Japan)">
                            <span style="font-weight: bold; color: #38bdf8; font-size: 18px;">&harr;</span>
                            <input type="text" name="match_right[]" class="form-control" placeholder="Matching Column B Item (e.g. Tokyo)">
                            <button type="button" class="btn btn-danger btn-sm remove-pair"><i class="fa fa-times"></i></button>
                        </div>
                        <div class="pair-row">
                            <input type="text" name="match_left[]" class="form-control" placeholder="Column A Item (e.g. Germany)">
                            <span style="font-weight: bold; color: #38bdf8; font-size: 18px;">&harr;</span>
                            <input type="text" name="match_right[]" class="form-control" placeholder="Matching Column B Item (e.g. Berlin)">
                            <button type="button" class="btn btn-danger btn-sm remove-pair"><i class="fa fa-times"></i></button>
                        </div>
                    </div>

                    <button type="button" id="btnAddPair" class="btn btn-default btn-sm" style="margin-top: 10px; background: var(--card-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                        <i class="fa fa-plus"></i> Add Another Pair
                    </button>
                </div>

                <!-- Form Action Buttons -->
                <div style="margin-top: 25px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                    <button type="submit" name="btn_save_question" class="btn btn-primary btn-lg" style="padding: 10px 24px; font-weight: 700;">
                        <i class="fa fa-save"></i> Save Question &amp; Add Another
                    </button>
                    <a href="myexams.php" class="btn btn-success btn-lg" style="padding: 10px 24px; font-weight: 700;">
                        <i class="fa fa-check"></i> Finish &amp; Complete Exam
                    </a> 
                    <?php if($exam_id > 0) { ?>
                        <button type="button" class="btn btn-default btn-lg" onclick="confirmLeaveEditor('set_exam_type.php?exam_id=<?= $exam_id; ?>')" style="background: var(--card-bg); color: var(--text-muted); border: 1px solid var(--table-border-color);">
                            <i class="fa fa-sign-out"></i> Leave
                        </button>
                    <?php } ?>
                </div>
            </form> 
        </div>
    </div>
<?php } ?>

<!-- Existing Questions List Table -->
<?php if(!empty($existing_questions)) { ?>
    <div class="editor-panel" style="margin-top: 35px;">
        <div class="editor-panel-heading">
            <i class="fa fa-list" style="color: #38bdf8; margin-right: 6px;"></i> Questions in this Exam (<?= count($existing_questions); ?>)
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 140px;">Type</th>
                        <th>Question / Prompt</th>
                        <th style="width: 240px;">Correct Answer / Details</th>
                        <th style="width: 80px;">Marks</th>
                        <th style="width: 120px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $num = 1;
                    foreach($existing_questions as $q) { 
                        $qt = !empty($q['question_type']) ? $q['question_type'] : 'mcq';
                    ?>
                        <tr>
                            <td><strong><?= $num++; ?></strong></td>
                            <td>
                                <span class="q-type-badge badge-<?= e($qt); ?>">
                                    <?= strtoupper(str_replace('_', ' ', $qt)); ?>
                                </span>
                            </td>
                            <td><?= e($q['question_text']); ?></td>
                            <td>
                                <?php if($qt === 'mcq') { ?>
                                    <strong>Correct: Option <?= e($q['correct_option']); ?></strong>
                                <?php } elseif($qt === 'true_false') { ?>
                                    <span class="label label-warning"><?= e($q['correct_answer'] ?? $q['correct_option']); ?></span>
                                <?php } elseif($qt === 'essay') { ?>
                                    <span class="text-muted"><i class="fa fa-user-circle"></i> Manual Examiner Grading</span>
                                <?php } elseif($qt === 'matching') { 
                                    $pairs = Grader::parseMatchingPairs($q['options_data'] ?? '', $q['correct_answer'] ?? '');
                                    echo '<small>' . count($pairs) . ' item pairs</small>';
                                } else { ?>
                                    <code><?= e($q['correct_answer'] ?? $q['option_a']); ?></code>
                                <?php } ?>
                            </td>
                            <td><span class="badge" style="background: #0284c7;"><?= number_format($q['marks'] ?? 1, 1); ?></span></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <?php if($is_locked) { ?>
                                    <span class="text-muted" title="Exam is locked"><i class="fa fa-lock"></i> Locked</span>
                                <?php } else { ?>
                                    <button type="button" class="btn btn-primary btn-xs btn-edit-question"
                                        data-id="<?= (int)$q['id']; ?>"
                                        data-type="<?= htmlspecialchars((string)$qt, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-marks="<?= htmlspecialchars((string)($q['marks'] ?? 1), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-text="<?= htmlspecialchars((string)($q['question_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-opta="<?= htmlspecialchars((string)($q['option_a'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-optb="<?= htmlspecialchars((string)($q['option_b'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-optc="<?= htmlspecialchars((string)($q['option_c'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-optd="<?= htmlspecialchars((string)($q['option_d'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-correct-opt="<?= htmlspecialchars((string)($q['correct_option'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-correct-ans="<?= htmlspecialchars((string)($q['correct_answer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-options='<?= htmlspecialchars((string)($q['options_data'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'
                                        title="Edit Question" style="padding: 4px 8px; border-radius: 4px; margin-right: 4px;">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this question?');" style="display:inline;">
                                        <input type="hidden" name="delete_question_id" value="<?= (int)$q['id']; ?>">
                                        <button type="submit" name="btn_delete_question" class="btn btn-danger btn-xs" title="Delete Question" style="padding: 4px 8px; border-radius: 4px;">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

<!-- Edit Question Modal -->
<div class="modal fade" id="editQuestionModal" tabindex="-1" role="dialog" aria-labelledby="editQuestionModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="update_question.php" method="POST" id="editQuestionForm">
                <input type="hidden" name="question_id" id="modal_question_id" value="0">
                <input type="hidden" name="exam_id" value="<?= $exam_id; ?>">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: var(--text-primary);"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="editQuestionModalLabel" style="font-weight: 700; color: var(--text-primary);">
                        <i class="fa fa-pencil-square-o text-primary"></i> Edit Question
                    </h4>
                </div>

                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <div class="row">
                        <!-- Question Type -->
                        <div class="col-md-6 form-group">
                            <label style="color: var(--text-primary); font-weight: 600;">Question Type: <span class="text-danger">*</span></label>
                            <?php if($exam_type_mode === 'hybrid') { ?>
                                <select name="question_type" id="modal_question_type" class="form-control" required style="font-weight: 600;">
                                    <option value="mcq">Multiple Choice (MCQ)</option>
                                    <?php if(!$is_mock) { ?>
                                    <option value="essay">Descriptive / Essay (Manual Grading)</option>
                                    <?php } ?>
                                    <option value="short_answer">Short Answer</option>
                                    <option value="true_false">True / False</option>
                                    <option value="fill_blank">Fill in the Blanks</option>
                                    <option value="matching">Matching Type</option>
                                </select>
                            <?php } else { ?>
                                <input type="hidden" name="question_type" id="modal_question_type" value="<?= e($exam_type_mode); ?>">
                                <div class="form-control" style="background: var(--input-bg); border: 1px solid var(--input-border); font-weight:700; color: var(--text-primary);">
                                    <i class="fa fa-lock text-muted"></i> <?= strtoupper(str_replace('_', ' ', $exam_type_mode)); ?>
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Marks -->
                        <div class="col-md-6 form-group">
                            <label style="color: var(--text-primary); font-weight: 600;">Marks: <span class="text-danger">*</span></label>
                            <input type="number" name="marks" id="modal_marks" class="form-control" min="0.5" step="0.5" required>
                        </div>

                        <!-- Prompt -->
                        <div class="col-md-12 form-group">
                            <label style="color: var(--text-primary); font-weight: 600;">Question Text / Statement: <span class="text-danger">*</span></label>
                            <textarea name="question_text" id="modal_question_text" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>

                    <!-- Modal Type: MCQ -->
                    <div id="modal_type_mcq" class="modal-type-container">
                        <h5 style="font-weight: 700; color: #38bdf8; margin-top: 0; margin-bottom: 14px;"><i class="fa fa-check-square-o"></i> MCQ Choices</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label style="color: var(--text-primary); font-weight: 500;">Option A:</label>
                                <input type="text" name="option_a" id="modal_option_a" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label style="color: var(--text-primary); font-weight: 500;">Option B:</label>
                                <input type="text" name="option_b" id="modal_option_b" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label style="color: var(--text-primary); font-weight: 500;">Option C:</label>
                                <input type="text" name="option_c" id="modal_option_c" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label style="color: var(--text-primary); font-weight: 500;">Option D:</label>
                                <input type="text" name="option_d" id="modal_option_d" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label style="color: var(--text-primary); font-weight: 500;">Correct Option:</label>
                                <select name="correct_option" id="modal_correct_option" class="form-control">
                                    <option value="">-- Select Correct Option --</option>
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Type: Essay -->
                    <div id="modal_type_essay" class="modal-type-container">
                        <div class="alert alert-info" style="margin-bottom: 0;">
                            <i class="fa fa-info-circle"></i> <strong>Descriptive / Essay Question</strong><br>
                            Open-ended essay prompt. Answers are graded manually by the examiner on the Grade Essays page.
                        </div>
                    </div>

                    <!-- Modal Type: Short Answer -->
                    <div id="modal_type_short_answer" class="modal-type-container">
                        <div class="form-group">
                            <label style="color: var(--text-primary); font-weight: 500;">Expected Correct Answer(s):</label>
                            <input type="text" name="short_correct_answer" id="modal_short_correct_answer" class="form-control" placeholder="e.g. CPU or Central Processing Unit">
                            <p class="text-muted" style="font-size: 12px; margin-top: 5px;">Multiple acceptable variants separated by commas.</p>
                        </div>
                    </div>

                    <!-- Modal Type: True / False -->
                    <div id="modal_type_true_false" class="modal-type-container">
                        <div class="form-group">
                            <label style="color: var(--text-primary); font-weight: 500;">Correct Truth Value:</label>
                            <div style="margin-top: 6px;">
                                <label class="radio-inline" style="font-size: 15px; margin-right: 20px; color: var(--text-primary);">
                                    <input type="radio" name="tf_correct_answer" id="modal_tf_true" value="True"> <strong><i class="fa fa-check text-success"></i> True</strong>
                                </label>
                                <label class="radio-inline" style="font-size: 15px; color: var(--text-primary);">
                                    <input type="radio" name="tf_correct_answer" id="modal_tf_false" value="False"> <strong><i class="fa fa-times text-danger"></i> False</strong>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Type: Fill in Blank -->
                    <div id="modal_type_fill_blank" class="modal-type-container">
                        <div class="form-group">
                            <label style="color: var(--text-primary); font-weight: 500;">Correct Blank Value(s):</label>
                            <input type="text" name="blank_correct_answer" id="modal_blank_correct_answer" class="form-control" placeholder="e.g. Nitrogen or N2">
                            <p class="text-muted" style="font-size: 12px; margin-top: 5px;">Multiple acceptable variants separated by commas.</p>
                        </div>
                    </div>

                    <!-- Modal Type: Matching -->
                    <div id="modal_type_matching" class="modal-type-container">
                        <h5 style="font-weight: 700; color: #2dd4bf; margin-top: 0; margin-bottom: 8px;"><i class="fa fa-random"></i> Column Matching Pairs</h5>
                        <p class="text-muted" style="font-size: 12px; margin-bottom: 12px;">Define Column A &amp; Column B pairs.</p>
                        <div id="modalPairsContainer">
                            <!-- Populated dynamically via JS -->
                        </div>
                        <button type="button" id="btnModalAddPair" class="btn btn-default btn-xs" style="margin-top: 8px; background: var(--card-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                            <i class="fa fa-plus"></i> Add Another Pair
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;"><i class="fa fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmLeaveEditor(targetUrl) {
    if (confirm("Are you sure you want to leave? All the changes you have made will be gone.")) {
        window.location.href = targetUrl;
    }
}

function escapeModalHtml(str) {
    if(!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

(function() {
    // Primary Add Question Form Switcher
    var typeSelect = document.getElementById('questionTypeSelect');
    var containers = document.querySelectorAll('.type-container');
    var mcqInputs = document.querySelectorAll('.mcq-opt, .mcq-correct');
    var marksInput = document.getElementById('marksInput');

    function updateQuestionTypeUI() {
        if(!typeSelect) return;
        var selectedType = typeSelect.value;
        containers.forEach(function(el) {
            el.classList.remove('active');
        });

        var targetContainer = document.getElementById('type_' + selectedType);
        if(targetContainer) {
            targetContainer.classList.add('active');
        }

        // Adjust default marks based on type
        if(marksInput) {
            if(selectedType === 'essay') {
                if(parseFloat(marksInput.value) <= 1) { marksInput.value = '5.00'; }
            } else if(selectedType === 'matching') {
                if(parseFloat(marksInput.value) <= 1) { marksInput.value = '4.00'; }
            }
        }

        // Toggle required attributes for MCQ
        if(selectedType === 'mcq') {
            mcqInputs.forEach(function(i) { i.setAttribute('required', 'required'); });
        } else {
            mcqInputs.forEach(function(i) { i.removeAttribute('required'); });
        }
    }

    if(typeSelect) {
        typeSelect.addEventListener('change', updateQuestionTypeUI);
        updateQuestionTypeUI();
    }

    // Dynamic Matching Pair Builder (Add Form)
    var pairsContainer = document.getElementById('pairsContainer');
    var btnAddPair = document.getElementById('btnAddPair');

    if(btnAddPair && pairsContainer) {
        btnAddPair.addEventListener('click', function() {
            var div = document.createElement('div');
            div.className = 'pair-row';
            div.innerHTML = '<input type="text" name="match_left[]" class="form-control" placeholder="Column A Item">' +
                            '<span style="font-weight: bold; color: #38bdf8; font-size: 18px;">&harr;</span>' +
                            '<input type="text" name="match_right[]" class="form-control" placeholder="Matching Column B Item">' +
                            '<button type="button" class="btn btn-danger btn-sm remove-pair"><i class="fa fa-times"></i></button>';
            pairsContainer.appendChild(div);
            bindRemoveButtons();
        });

        function bindRemoveButtons() {
            document.querySelectorAll('.remove-pair').forEach(function(btn) {
                btn.onclick = function() {
                    var rows = document.querySelectorAll('#pairsContainer .pair-row');
                    if(rows.length > 2) {
                        this.closest('.pair-row').remove();
                    } else {
                        alert('Matching question must have at least 2 pairs.');
                    }
                };
            });
        }
        bindRemoveButtons();
    }

    // Modal Question Type Switcher
    var modalTypeSelect = document.getElementById('modal_question_type');
    function updateModalTypeUI() {
        var selectedType = modalTypeSelect ? modalTypeSelect.value : 'mcq';
        document.querySelectorAll('.modal-type-container').forEach(function(el) {
            el.classList.remove('active');
        });
        var target = document.getElementById('modal_type_' + selectedType);
        if(target) target.classList.add('active');
    }
    if(modalTypeSelect) {
        modalTypeSelect.addEventListener('change', updateModalTypeUI);
    }

    // Modal Matching Pairs Helpers
    function addModalPairRow(leftVal, rightVal) {
        var container = document.getElementById('modalPairsContainer');
        if(!container) return;
        var div = document.createElement('div');
        div.className = 'pair-row';
        div.style.marginBottom = '8px';
        div.innerHTML = '<input type="text" name="match_left[]" class="form-control" value="' + escapeModalHtml(leftVal) + '" placeholder="Column A Item">' +
                        '<span style="font-weight: bold; color: #38bdf8; font-size: 16px;">&harr;</span>' +
                        '<input type="text" name="match_right[]" class="form-control" value="' + escapeModalHtml(rightVal) + '" placeholder="Matching Column B Item">' +
                        '<button type="button" class="btn btn-danger btn-xs remove-modal-pair"><i class="fa fa-times"></i></button>';
        container.appendChild(div);
        bindModalRemoveButtons();
    }

    function bindModalRemoveButtons() {
        document.querySelectorAll('.remove-modal-pair').forEach(function(btn) {
            btn.onclick = function() {
                var rows = document.querySelectorAll('#modalPairsContainer .pair-row');
                if(rows.length > 2) {
                    this.closest('.pair-row').remove();
                } else {
                    alert('Matching question must have at least 2 pairs.');
                }
            };
        });
    }

    var btnModalAddPair = document.getElementById('btnModalAddPair');
    if(btnModalAddPair) {
        btnModalAddPair.addEventListener('click', function() {
            addModalPairRow('', '');
        });
    }

    // Edit Question Button Click Handlers
    document.querySelectorAll('.btn-edit-question').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var qid = this.getAttribute('data-id');
            var qtype = this.getAttribute('data-type');
            var marks = this.getAttribute('data-marks');
            var qtext = this.getAttribute('data-text');
            var opta = this.getAttribute('data-opta') || '';
            var optb = this.getAttribute('data-optb') || '';
            var optc = this.getAttribute('data-optc') || '';
            var optd = this.getAttribute('data-optd') || '';
            var correctOpt = this.getAttribute('data-correct-opt') || '';
            var correctAns = this.getAttribute('data-correct-ans') || '';
            var optionsRaw = this.getAttribute('data-options') || '';

            document.getElementById('modal_question_id').value = qid;
            document.getElementById('modal_marks').value = marks;
            document.getElementById('modal_question_text').value = qtext;

            if(modalTypeSelect) {
                modalTypeSelect.value = qtype;
                updateModalTypeUI();
            }

            if(qtype === 'mcq') {
                document.getElementById('modal_option_a').value = opta;
                document.getElementById('modal_option_b').value = optb;
                document.getElementById('modal_option_c').value = optc;
                document.getElementById('modal_option_d').value = optd;
                document.getElementById('modal_correct_option').value = correctOpt.toUpperCase();
            } else if(qtype === 'true_false') {
                var isFalse = (correctAns.toLowerCase() === 'false' || correctOpt.toLowerCase() === 'false');
                var tfFalse = document.getElementById('modal_tf_false');
                var tfTrue = document.getElementById('modal_tf_true');
                if(tfFalse) tfFalse.checked = isFalse;
                if(tfTrue) tfTrue.checked = !isFalse;
            } else if(qtype === 'short_answer') {
                var saInput = document.getElementById('modal_short_correct_answer');
                if(saInput) saInput.value = correctAns;
            } else if(qtype === 'fill_blank') {
                var fbInput = document.getElementById('modal_blank_correct_answer');
                if(fbInput) fbInput.value = correctAns;
            } else if(qtype === 'matching') {
                var container = document.getElementById('modalPairsContainer');
                if(container) {
                    container.innerHTML = '';
                    var pairs = [];
                    try {
                        var parsed = JSON.parse(optionsRaw || correctAns);
                        if(parsed && parsed.pairs && Array.isArray(parsed.pairs)) {
                            pairs = parsed.pairs;
                        }
                    } catch(e) {}
                    if(!pairs || pairs.length === 0) {
                        pairs = [{left: '', right: ''}, {left: '', right: ''}];
                    }
                    pairs.forEach(function(p) {
                        addModalPairRow(p.left || '', p.right || '');
                    });
                }
            }

            // Open Bootstrap Modal
            if(window.jQuery && typeof $('#editQuestionModal').modal === 'function') {
                $('#editQuestionModal').modal('show');
            } else {
                // Fallback for modal
                var modalEl = document.getElementById('editQuestionModal');
                if(modalEl) {
                    modalEl.classList.add('in');
                    modalEl.style.display = 'block';
                }
            }
        });
    });
})();
</script>

<?php include('footer.php'); ?>