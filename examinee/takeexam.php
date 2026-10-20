<?php 
/*
 * takeexam.php - Student Exam Taking Interface
 * Supports all 6 question types with per-question scoring,
 * responsive rendering, countdown timer, auto-grading, and provisional essay handling.
 */
require('../config/autoload.php'); 
require('auth_helper.php');
$student_id = require_login();

$dao = new DataAccess(); 
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : (isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0);

// 1. Exam must exist and be approved
$exam_info = $dao->getData('*', 'exams', "id = " . $exam_id . " AND status = 'approved'");
if(empty($exam_info)) {
    echo "<script>alert('Invalid Exam or exam is not currently active.'); window.location.href='student_exams.php';</script>";
    exit();
}
$exam = $exam_info[0];

// Identify if this is a Mock Exam
$category = strtolower(trim($exam['category'] ?? ''));
$type = strtolower(trim($exam['exam_type'] ?? ''));
$is_mock = ($category === 'mock' || $type === 'mock' || strpos($category, 'mock') !== false);

// 2. Student must be registered AND paid (Certification exams only)
if(!$is_mock) {
    $reg = $dao->getData('*', 'registrations', 'student_id = ' . $student_id . ' AND exam_id = ' . $exam_id . ' AND payment_status = "Paid"');
    if(empty($reg)) {
        echo "<script>alert('You are not registered for this exam.'); window.location.href='student_exams.php';</script>";
        exit();
    }
}

// 3. Must not have written it already (Certification exams)
$done = $dao->getData('*', 'exam_results', 'student_id = ' . $student_id . ' AND exam_id = ' . $exam_id);
if(!empty($done) && !$is_mock) {
    echo "<script>alert('You have already submitted this exam.'); window.location.href='results.php';</script>";
    exit();
}

// 4. Time window check
$now = time();
if(!$is_mock) {
    $start_time = strtotime($exam['exam_start_datetime']);
    $end_time = $start_time + ($exam['duration_minutes'] * 60);

    if($now < $start_time) {
        echo "<script>alert('This exam has not started yet.'); window.location.href='my_exams.php';</script>";
        exit();
    }
    // Allow generous grace period during POST submission so answers are saved properly
    if(!isset($_POST['btn_submit'])) {
        if($now > $end_time + 45) { // 45 seconds grace for network latency when viewing
            echo "<script>alert('The exam time is over.'); window.location.href='my_exams.php';</script>";
            exit();
        }
    } else {
        if($now > $end_time + 180) { // 180 seconds grace for network/payload latency during submit
            echo "<script>alert('The exam submission deadline has passed.'); window.location.href='my_exams.php';</script>";
            exit();
        }
    }
    $seconds_left = max(0, $end_time - $now);
} else {
    $seconds_left = ((int)$exam['duration_minutes']) * 60;
}

// 5. Fetch Questions for this exam
$questions = $dao->getData('*', 'questions', 'exam_id = ' . $exam_id . ' ORDER BY id ASC');
if(empty($questions)) {
    echo "<script>alert('No questions have been added to this exam yet.'); window.location.href='student_exams.php';</script>";
    exit();
}

// 6. Handle Submission via Grader
if(isset($_POST['btn_submit'])) {
    if(!csrf_check()) {
        echo "<script>alert('Session expired. Please try again.'); window.location.href='student_exams.php';</script>";
        exit();
    }

    $submitted_answers = isset($_POST['ans']) && is_array($_POST['ans']) ? $_POST['ans'] : array();

    $grader = new Grader();
    $result_summary = $grader->gradeExamSubmission($exam_id, $student_id, $submitted_answers);

    if($result_summary['success']) {
        if($is_mock) {
            $score_pct = ($result_summary['total_marks'] > 0)
                ? round(($result_summary['marks_obtained'] / $result_summary['total_marks']) * 100, 1)
                : 0;

            if($result_summary['has_pending_essay']) {
                echo "<script>
                    alert('Mock Exam Submitted!\\n\\nAuto-graded Score: " . number_format($result_summary['auto_graded_marks'], 2) . " / " . number_format($result_summary['total_marks'], 2) . "\\nEssay Questions: Pending review.');
                    window.location.href = 'feedback_form.php?exam_id=" . (int)$exam_id . "';
                </script>";
            } else {
                echo "<script>
                    alert('Mock Exam Submitted!\\n\\nYour Score: " . number_format($result_summary['marks_obtained'], 2) . " / " . number_format($result_summary['total_marks'], 2) . " (" . $score_pct . "%)');
                    window.location.href = 'feedback_form.php?exam_id=" . (int)$exam_id . "';
                </script>";
            }
        } else {
            if($result_summary['has_pending_essay']) {
                echo "<script>
                    alert('Exam submitted successfully!\\n\\nYour auto-graded questions have been scored: " . number_format($result_summary['auto_graded_marks'], 2) . " marks.\\nYour descriptive/essay questions are currently pending examiner grading.\\nYour final result will be published once grading is completed.');
                    window.location.href = 'feedback_form.php?exam_id=" . (int)$exam_id . "';
                </script>";
            } else {
                echo "<script>
                    alert('Exam submitted successfully! Your score has been calculated.');
                    window.location.href = 'feedback_form.php?exam_id=" . (int)$exam_id . "';
                </script>";
            }
        }
    } else {
        echo "<script>
            alert('Submission error: " . addslashes($result_summary['error'] ?? 'Could not process submission') . "');
            window.location.href = 'student_exams.php';
        </script>";
    }
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Take Exam: <?= htmlspecialchars($exam['title'] ?? 'Exam'); ?></title>
    <link rel="icon" type="image/png" href="index/image/logo.png"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../examiner/assets/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    
    <style>
        :root {
            --bg-primary: #050811;
            --card-bg: #0d1527;
            --card-border: #1a233a;
            --accent-cyan: #38bdf8;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --border-color: #1a233a;
            --bg-hover: rgba(255, 255, 255, 0.03);
            --table-th-bg: #0f172a;
        }

        body {
            background-color: var(--bg-primary, #050811);
            color: var(--text-primary, #ffffff);
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Allow typing selection in input/textarea */
        input, textarea, select {
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            user-select: text !important;
        }

        .exam-header {
            background: var(--card-bg, #0d1527);
            padding: 22px 26px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--card-border, #1a233a);
            border-left: 5px solid var(--accent-cyan, #38bdf8);
        }
        .exam-title-box h2 {
            margin: 0 0 6px 0;
            font-weight: 700;
            font-size: 22px;
            color: var(--text-primary, #ffffff) !important;
            transform: none !important;
            animation: none !important;
        }
        .header-badges {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .violation-badge {
            background: rgba(234, 179, 8, 0.12);
            border: 1.5px solid #eab308;
            color: #eab308;
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 0 15px rgba(234, 179, 8, 0.15);
            transition: all 0.3s ease;
        }
        .timer-badge {
            background: rgba(239, 68, 68, 0.12);
            border: 1.5px solid #ef4444;
            color: #ef4444;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
        }
        .q-card {
            background: var(--card-bg, #0d1527);
            border: 1px solid var(--card-border, #1a233a);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.18);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .q-card:hover {
            border-color: rgba(56, 189, 248, 0.4);
        }
        .q-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color, #1a233a);
            padding-bottom: 12px;
        }
        .q-number {
            font-size: 16px;
            font-weight: 700;
            color: var(--accent-cyan, #38bdf8);
        }
        .q-marks-badge {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #ffffff;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(56, 189, 248, 0.25);
        }
        .q-type-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary, #94a3b8);
            margin-right: 8px;
            font-weight: 600;
        }
        .q-text {
            font-size: 16px;
            color: var(--text-primary, #ffffff);
            line-height: 1.6;
            margin-bottom: 18px;
            font-weight: 500;
        }
        
        /* Option choice styling */
        .option-label {
            display: block;
            background: var(--bg-hover, rgba(255, 255, 255, 0.03));
            border: 1px solid var(--card-border, #1a233a);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            color: var(--text-primary, #ffffff);
        }
        .option-label:hover {
            background: rgba(56, 189, 248, 0.08);
            border-color: var(--accent-cyan, #38bdf8);
        }
        .option-label input[type="radio"] {
            margin-right: 10px;
            accent-color: var(--accent-cyan, #38bdf8);
        }

        /* Matching grid */
        .matching-table {
            background: var(--card-bg, #0d1527);
            border: 1px solid var(--border-color, #1a233a);
            color: var(--text-primary, #ffffff);
        }
        .matching-table th {
            background: var(--table-th-bg, #0f172a);
            color: var(--text-primary, #ffffff);
            border-color: var(--border-color, #1a233a) !important;
        }
        .matching-table td {
            vertical-align: middle !important;
            padding: 12px !important;
            border-color: var(--border-color, #1a233a) !important;
            color: var(--text-primary, #ffffff);
        }

        /* Essay text area */
        .essay-textarea {
            background: var(--bg-primary, #050811) !important;
            border-radius: 8px;
            border: 1.5px solid var(--border-color, #1a233a) !important;
            color: var(--text-primary, #ffffff) !important;
            font-size: 15px;
            padding: 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .essay-textarea:focus {
            border-color: var(--accent-cyan, #38bdf8) !important;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2) !important;
            outline: none;
        }

        .word-count {
            font-size: 12px;
            color: var(--text-secondary, #94a3b8);
            margin-top: 6px;
            text-align: right;
        }

        .exam-submit-panel {
            background: var(--card-bg, #0d1527);
            border: 1px solid var(--card-border, #1a233a);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.18);
        }

        /* Fullscreen & Warning Overlays */
        .exam-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(5, 8, 17, 0.96);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .exam-modal-card {
            background: #0d1527;
            border: 1px solid #1a233a;
            border-radius: 16px;
            max-width: 580px;
            width: 100%;
            padding: 36px 32px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
            animation: fadeInScale 0.25s ease-out;
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .exam-modal-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
        }
        .icon-primary {
            background: rgba(56, 189, 248, 0.12);
            color: #38bdf8;
            border: 2px solid #38bdf8;
        }
        .icon-warning {
            background: rgba(234, 179, 8, 0.12);
            color: #eab308;
            border: 2px solid #eab308;
        }
        .icon-danger {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
            border: 2px solid #ef4444;
        }
        .exam-modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 12px 0;
        }
        .exam-modal-subtitle {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .exam-rules-list {
            text-align: left;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid #1a233a;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.8;
        }
        .exam-rules-list li {
            margin-bottom: 6px;
        }
        .exam-modal-btn {
            display: inline-block;
            width: 100%;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-start {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3);
        }
        .btn-start:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .btn-return {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .btn-acknowledge {
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #ffffff;
        }
    </style>
</head>

<body>

    <!-- 1. Start Exam Fullscreen Overlay -->
    <div class="exam-overlay" id="startOverlay">
        <div class="exam-modal-card">
            <div class="exam-modal-icon icon-primary">
                <i class="fa fa-arrows-alt"></i>
            </div>
            <h2 class="exam-modal-title"><?= htmlspecialchars($exam['title'] ?? 'Exam'); ?></h2>
            <p class="exam-modal-subtitle">
                <span class="label label-primary"><?= htmlspecialchars($exam['category'] ?? ''); ?></span> &bull; 
                <?= count($questions); ?> Questions &bull; 
                <?= (int)$exam['duration_minutes']; ?> Minutes
            </p>
            <ul class="exam-rules-list">
                <li><i class="fa fa-shield text-info"></i> <strong>Fullscreen Enforcement:</strong> This exam requires fullscreen mode throughout.</li>
                <li><i class="fa fa-ban text-warning"></i> <strong>Proctoring Lockdown:</strong> Tab switching, window minimization, and shortcuts are restricted.</li>
                <li><i class="fa fa-exclamation-circle text-danger"></i> <strong>2-Strike Rule:</strong> 2 recorded violations will trigger automatic submission.</li>
                <li><i class="fa fa-lock text-info"></i> Right-click, copy, paste, and text selection are disabled.</li>
            </ul>
            <button type="button" class="exam-modal-btn btn-start" id="btnStartExam" onclick="startExam()">
                <i class="fa fa-play-circle"></i> Start Exam in Fullscreen
            </button>
        </div>
    </div>

    <!-- 2. Fullscreen Exited Warning Overlay -->
    <div class="exam-overlay" id="fullscreenWarningOverlay" style="display: none;">
        <div class="exam-modal-card">
            <div class="exam-modal-icon icon-warning">
                <i class="fa fa-expand"></i>
            </div>
            <h2 class="exam-modal-title">Fullscreen Exited!</h2>
            <p class="exam-modal-subtitle">
                You have exited fullscreen mode. Leaving fullscreen is a proctoring violation. Please return to fullscreen immediately to continue your exam.
            </p>
            <button type="button" class="exam-modal-btn btn-return" onclick="returnToFullscreen()">
                <i class="fa fa-arrows-alt"></i> Return to Fullscreen
            </button>
        </div>
    </div>

    <!-- 3. Security Violation Modal -->
    <div class="exam-overlay" id="violationOverlay" style="display: none;">
        <div class="exam-modal-card">
            <div class="exam-modal-icon icon-danger">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <h2 class="exam-modal-title" id="violationModalTitle">Security Warning</h2>
            <p class="exam-modal-subtitle" id="violationModalDesc">
                Tab switching or leaving the exam screen is prohibited.
            </p>
            <button type="button" class="exam-modal-btn btn-acknowledge" id="btnAcknowledgeViolation" onclick="dismissViolationModal()">
                <i class="fa fa-check"></i> I Understand &amp; Continue
            </button>
        </div>
    </div>

    <!-- 4. Standalone Exam Container (Hidden until Start Exam is clicked) -->
    <div class="container" id="examContainer" style="display: none; max-width: 960px; margin-top: 25px; margin-bottom: 80px;">
        <!-- Top Bar -->
        <div class="exam-header">
            <div class="exam-title-box">
                <h2><?= htmlspecialchars($exam['title'] ?? 'Exam'); ?></h2>
                <span class="label label-primary"><?= htmlspecialchars($exam['category'] ?? ''); ?></span>
                <span class="text-muted" style="margin-left: 10px;">
                    <i class="fa fa-question-circle"></i> <?= count($questions); ?> Questions &bull; 
                    <i class="fa fa-clock-o"></i> <?= (int)$exam['duration_minutes']; ?> Minutes
                </span>
            </div>
            <div class="header-badges">
                <div class="violation-badge" id="violationBadge">
                    <i class="fa fa-shield"></i> <span id="violationText">Warning 0 of 2</span>
                </div>
                <div class="timer-badge">
                    <i class="fa fa-clock-o"></i> <span id="timer">--:--</span>
                </div>
            </div>
        </div>

        <!-- Exam Submission Form -->
        <form method="POST" id="examForm" action="">
            <?= csrf_field(); ?>
            <input type="hidden" name="exam_id" value="<?= $exam_id; ?>">

            <?php 
            $n = 1; 
            foreach($questions as $q) { 
                $qid = (int)$q['id'];
                $q_type = !empty($q['question_type']) ? strtolower(trim($q['question_type'])) : 'mcq';
                $marks = isset($q['marks']) ? (float)$q['marks'] : 1.00;
            ?>
                <div class="q-card" id="q_card_<?= $qid; ?>">
                    <div class="q-header">
                        <div class="q-number">
                            Question <?= $n++; ?>
                        </div>
                        <div>
                            <span class="q-type-label"><?= strtoupper(str_replace('_', ' ', $q_type)); ?></span>
                            <span class="q-marks-badge"><?= number_format($marks, 1); ?> <?= ($marks == 1 ? 'Mark' : 'Marks'); ?></span>
                        </div>
                    </div>

                    <div class="q-text">
                        <?= nl2br(htmlspecialchars((string)$q['question_text'])); ?>
                    </div>

                    <!-- 1. MCQ TYPE -->
                    <?php if($q_type === 'mcq') { ?>
                        <div class="options-wrap">
                            <?php 
                            $mcq_opts = array('A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']);
                            foreach($mcq_opts as $opt_key => $opt_val) { 
                                if($opt_val === null || trim((string)$opt_val) === '') continue;
                            ?>
                                <label class="option-label">
                                    <input type="radio" name="ans[<?= $qid; ?>]" value="<?= $opt_key; ?>">
                                    <strong><?= $opt_key; ?>.</strong> <?= htmlspecialchars($opt_val); ?>
                                </label>
                            <?php } ?>
                        </div>

                    <!-- 2. TRUE / FALSE TYPE -->
                    <?php } elseif($q_type === 'true_false') { ?>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="option-label text-center" style="font-size: 16px;">
                                    <input type="radio" name="ans[<?= $qid; ?>]" value="True">
                                    <i class="fa fa-check text-success"></i> <strong>True</strong>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="option-label text-center" style="font-size: 16px;">
                                    <input type="radio" name="ans[<?= $qid; ?>]" value="False">
                                    <i class="fa fa-times text-danger"></i> <strong>False</strong>
                                </label>
                            </div>
                        </div>

                    <!-- 3. SHORT ANSWER TYPE -->
                    <?php } elseif($q_type === 'short_answer') { ?>
                        <div class="form-group" style="margin-bottom: 5px;">
                            <input type="text" name="ans[<?= $qid; ?>]" class="form-control input-lg" placeholder="Type your short answer here..." autocomplete="off">
                        </div>
                        <small class="text-muted"><i class="fa fa-info-circle"></i> Keep answers concise. Auto-evaluation is case-insensitive.</small>

                    <!-- 4. FILL IN THE BLANKS TYPE -->
                    <?php } elseif($q_type === 'fill_blank') { ?>
                        <div class="form-group" style="margin-bottom: 5px;">
                            <div class="input-group">
                                <span class="input-group-addon" style="background: linear-gradient(135deg, #0284c7, #38bdf8); color:#fff; font-weight:700; border: none;"><i class="fa fa-pencil"></i> Answer</span>
                                <input type="text" name="ans[<?= $qid; ?>]" class="form-control input-lg" placeholder="Enter the missing word(s)..." autocomplete="off">
                            </div>
                        </div>

                    <!-- 5. MATCHING TYPE -->
                    <?php } elseif($q_type === 'matching') { 
                        $pairs = Grader::parseMatchingPairs($q['options_data'] ?? '', $q['correct_answer'] ?? '');
                        
                        $right_choices = array();
                        foreach($pairs as $p) {
                            $right_choices[] = $p['right'];
                        }
                        $shuffled_rights = $right_choices;
                        shuffle($shuffled_rights);
                    ?>
                        <table class="table table-bordered matching-table">
                            <thead>
                                <tr>
                                    <th style="width: 45%;">Column A (Item)</th>
                                    <th style="width: 10%; text-align: center;">Match</th>
                                    <th style="width: 45%;">Column B (Select Matching Choice)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pairs as $p_idx => $p_item) { ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($p_item['left']); ?></strong></td>
                                        <td style="text-align: center; color: var(--accent-cyan, #38bdf8); font-size: 16px;"><i class="fa fa-arrow-right"></i></td>
                                        <td>
                                            <select name="ans[<?= $qid; ?>][<?= $p_idx; ?>]" class="form-control">
                                                <option value="">-- Choose matching item --</option>
                                                <?php foreach($shuffled_rights as $r_opt) { ?>
                                                    <option value="<?= htmlspecialchars($r_opt); ?>"><?= htmlspecialchars($r_opt); ?></option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                    <!-- 6. DESCRIPTIVE / ESSAY TYPE -->
                    <?php } elseif($q_type === 'essay') { ?>
                        <div class="form-group" style="margin-bottom: 0;">
                            <textarea name="ans[<?= $qid; ?>]" class="form-control essay-textarea" rows="6" placeholder="Write your detailed essay / descriptive answer here..." oninput="updateWordCount(this, 'wc_<?= $qid; ?>')"></textarea>
                            <div class="word-count" id="wc_<?= $qid; ?>">0 words | 0 characters</div>
                        </div>
                    <?php } ?>

                </div>
            <?php } ?>

            <!-- Submit Button Panel -->
            <div class="exam-submit-panel">
                <p class="text-muted" style="margin-bottom: 15px;">
                    <i class="fa fa-exclamation-triangle text-warning"></i> 
                    Please ensure you have reviewed all your answers before submitting. Once submitted, your exam attempt cannot be undone.
                </p>
                <button type="submit" name="btn_submit" class="btn btn-success btn-lg" style="padding: 14px 40px; font-size: 18px; font-weight: 700; background-color: #28a745; border-color: #28a745;" onclick="return confirm('Are you ready to submit your exam now?');">
                    <i class="fa fa-check-circle"></i> Submit Exam
                </button>
            </div>
        </form>
    </div>

<script>
// Word counter for essay questions
function updateWordCount(textarea, targetId) {
    var text = textarea.value.trim();
    var words = text === '' ? 0 : text.split(/\s+/).length;
    var chars = textarea.value.length;
    var el = document.getElementById(targetId);
    if(el) {
        el.textContent = words + (words === 1 ? ' word' : ' words') + ' | ' + chars + ' characters';
    }
}

// State variables
var examStarted = false;
var isSubmitting = false;
var violations = 0;
var maxViolations = 2;
var lastViolationTime = 0;
var secondsLeft = <?= (int)$seconds_left; ?>;
var timerEl = document.getElementById('timer');
var timerInterval = null;

// Fullscreen helpers
function requestFullscreenMode() {
    var elem = document.documentElement;
    if (elem.requestFullscreen) {
        return elem.requestFullscreen();
    } else if (elem.webkitRequestFullscreen) {
        return elem.webkitRequestFullscreen();
    } else if (elem.mozRequestFullScreen) {
        return elem.mozRequestFullScreen();
    } else if (elem.msRequestFullscreen) {
        return elem.msRequestFullscreen();
    }
    return Promise.resolve();
}

function isFullscreenActive() {
    return !!(
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement
    );
}

function exitFullscreenSafely() {
    try {
        if (isFullscreenActive()) {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(function(){});
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    } catch(e) {}
}

// Start Exam Handler
function startExam() {
    requestFullscreenMode().then(function() {
        initExamSession();
    }).catch(function() {
        initExamSession();
    });
}

function initExamSession() {
    examStarted = true;
    document.getElementById('startOverlay').style.display = 'none';
    document.getElementById('examContainer').style.display = 'block';

    // Start timer
    startCountdown();

    // History protection
    setupHistoryProtection();
}

// Return to Fullscreen Handler
function returnToFullscreen() {
    requestFullscreenMode().then(function() {
        document.getElementById('fullscreenWarningOverlay').style.display = 'none';
    }).catch(function() {
        document.getElementById('fullscreenWarningOverlay').style.display = 'none';
    });
}

// Timer
function startCountdown() {
    updateTimerDisplay();
    timerInterval = setInterval(function() {
        if(secondsLeft <= 0) {
            clearInterval(timerInterval);
            timerEl.textContent = '00:00';
            if(!isSubmitting) {
                if(timerEl && timerEl.parentElement) {
                    timerEl.parentElement.innerHTML = '<span style="color:#e74c3c;font-weight:bold;font-size:14px;"><i class="fa fa-spinner fa-spin"></i> Time Up! Auto-submitting...</span>';
                }
                autoSubmitExam('timer');
            }
            return;
        }
        secondsLeft--;
        updateTimerDisplay();
    }, 1000);
}

function updateTimerDisplay() {
    var m = Math.floor(secondsLeft / 60);
    var s = secondsLeft % 60;
    timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    if(secondsLeft <= 120) {
        timerEl.parentElement.style.animation = 'pulse 1s infinite alternate';
    }
}

// Security Violation Handling
function triggerViolation(reason) {
    if (!examStarted || isSubmitting) return;

    var now = Date.now();
    // Debounce blur & visibilitychange events firing together within 1500ms
    if (now - lastViolationTime < 1500) {
        return;
    }
    lastViolationTime = now;

    violations++;
    var textEl = document.getElementById('violationText');
    if (textEl) {
        textEl.textContent = 'Warning ' + violations + ' of ' + maxViolations;
    }
    var badgeWrap = document.getElementById('violationBadge');
    if (badgeWrap) {
        if (violations >= 2) {
            badgeWrap.style.background = 'rgba(239, 68, 68, 0.2)';
            badgeWrap.style.borderColor = '#ef4444';
            badgeWrap.style.color = '#ef4444';
        }
    }

    if (violations >= maxViolations) {
        showViolationModal(violations, reason, true);
        setTimeout(function() {
            autoSubmitExam('violations');
        }, 1800);
    } else {
        showViolationModal(violations, reason, false);
    }
}

function showViolationModal(count, reason, isFinal) {
    var modal = document.getElementById('violationOverlay');
    var title = document.getElementById('violationModalTitle');
    var desc = document.getElementById('violationModalDesc');
    var btn = document.getElementById('btnAcknowledgeViolation');

    if (isFinal) {
        title.innerHTML = '<span style="color:#ef4444;"><i class="fa fa-times-circle"></i> Exam Terminated</span>';
        desc.innerHTML = '<strong>Violation ' + count + ' of ' + maxViolations + ':</strong> ' + reason + '.<br>You have reached the maximum allowed warnings. Your exam is being automatically submitted now...';
        btn.style.display = 'none';
    } else {
        title.innerHTML = '<span style="color:#eab308;"><i class="fa fa-exclamation-triangle"></i> Warning ' + count + ' of ' + maxViolations + '</span>';
        desc.innerHTML = '<strong>Detected Action:</strong> ' + reason + '.<br>Tab switching, leaving fullscreen, or unfocusing the window is strictly prohibited. Reaching ' + maxViolations + ' warnings will automatically submit your exam.';
        btn.style.display = 'inline-block';
    }

    modal.style.display = 'flex';
}

function dismissViolationModal() {
    document.getElementById('violationOverlay').style.display = 'none';
    if (!isFullscreenActive()) {
        requestFullscreenMode().catch(function(){});
    }
}

// Auto submit
function autoSubmitExam(trigger) {
    if (isSubmitting) return;
    isSubmitting = true;

    if (timerInterval) clearInterval(timerInterval);
    removeLockdownListeners();
    exitFullscreenSafely();

    var form = document.getElementById('examForm');
    var btnInput = document.createElement('input');
    btnInput.type = 'hidden';
    btnInput.name = 'btn_submit';
    btnInput.value = '1';
    form.appendChild(btnInput);

    form.submit();
}

// Remove lockdown listeners
function removeLockdownListeners() {
    window.removeEventListener('beforeunload', beforeUnloadHandler);
    window.removeEventListener('blur', onWindowBlur);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', onFullscreenChange);
    document.removeEventListener('mozfullscreenchange', onFullscreenChange);
    document.removeEventListener('msfullscreenchange', onFullscreenChange);
}

// Event Listeners for Lockdown
function onWindowBlur() {
    if (!examStarted || isSubmitting) return;
    triggerViolation('Window unfocused / application switched');
}

function onVisibilityChange() {
    if (!examStarted || isSubmitting) return;
    if (document.hidden || document.visibilityState === 'hidden') {
        triggerViolation('Tab switched or minimized');
    }
}

function onFullscreenChange() {
    if (!examStarted || isSubmitting) return;
    if (!isFullscreenActive()) {
        document.getElementById('fullscreenWarningOverlay').style.display = 'flex';
        triggerViolation('Fullscreen mode exited');
    } else {
        document.getElementById('fullscreenWarningOverlay').style.display = 'none';
    }
}

function beforeUnloadHandler(e) {
    if (examStarted && !isSubmitting) {
        var msg = 'Your exam attempt is in progress. Leaving or reloading will disrupt your submission.';
        e.preventDefault();
        e.returnValue = msg;
        return msg;
    }
}

function setupHistoryProtection() {
    history.pushState(null, document.title, location.href);
    window.addEventListener('popstate', function() {
        if (examStarted && !isSubmitting) {
            history.pushState(null, document.title, location.href);
        }
    });
}

// Attach lockdown listeners
window.addEventListener('beforeunload', beforeUnloadHandler);
window.addEventListener('blur', onWindowBlur);
document.addEventListener('visibilitychange', onVisibilityChange);
document.addEventListener('fullscreenchange', onFullscreenChange);
document.addEventListener('webkitfullscreenchange', onFullscreenChange);
document.addEventListener('mozfullscreenchange', onFullscreenChange);
document.addEventListener('msfullscreenchange', onFullscreenChange);

// Handle manual form submit
var form = document.getElementById('examForm');
form.addEventListener('submit', function() {
    isSubmitting = true;
    if (timerInterval) clearInterval(timerInterval);
    removeLockdownListeners();
    exitFullscreenSafely();
});

// Shortcuts & Key Blocking
document.addEventListener('keydown', function(e) {
    if (!examStarted || isSubmitting) return;

    // F12
    if (e.key === 'F12' || e.keyCode === 123) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }

    var ctrlOrMeta = e.ctrlKey || e.metaKey;

    // Ctrl+U (View Source)
    if (ctrlOrMeta && (e.key === 'u' || e.key === 'U' || e.keyCode === 85)) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }

    // Ctrl+Shift+I / J / C (DevTools)
    if (ctrlOrMeta && e.shiftKey && (
        e.key === 'I' || e.key === 'i' || e.keyCode === 73 ||
        e.key === 'J' || e.key === 'j' || e.keyCode === 74 ||
        e.key === 'C' || e.key === 'c' || e.keyCode === 67
    )) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }

    // Ctrl+S (Save)
    if (ctrlOrMeta && (e.key === 's' || e.key === 'S' || e.keyCode === 83)) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }

    // Ctrl+P (Print)
    if (ctrlOrMeta && (e.key === 'p' || e.key === 'P' || e.keyCode === 80)) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }

    // Ctrl+C (Copy)
    if (ctrlOrMeta && (e.key === 'c' || e.key === 'C' || e.keyCode === 67)) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }

    // Ctrl+V (Paste)
    if (ctrlOrMeta && (e.key === 'v' || e.key === 'V' || e.keyCode === 86)) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }
});

// Block context menu (right click)
document.addEventListener('contextmenu', function(e) {
    if (examStarted && !isSubmitting) {
        e.preventDefault();
        return false;
    }
});

// Block copy / cut / paste
document.addEventListener('copy', function(e) {
    if (examStarted && !isSubmitting) {
        e.preventDefault();
        return false;
    }
});
document.addEventListener('cut', function(e) {
    if (examStarted && !isSubmitting) {
        e.preventDefault();
        return false;
    }
});
document.addEventListener('paste', function(e) {
    if (examStarted && !isSubmitting) {
        e.preventDefault();
        return false;
    }
});

// Block text selection on question areas
document.addEventListener('selectstart', function(e) {
    if (!examStarted || isSubmitting) return;
    var tag = e.target && e.target.tagName ? e.target.tagName.toLowerCase() : '';
    if (tag !== 'input' && tag !== 'textarea') {
        e.preventDefault();
        return false;
    }
});
</script>
</body>
</html>