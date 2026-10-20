<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();
date_default_timezone_set('Asia/Kolkata');

$student_id = (int)($_GET['student_id'] ?? 0);
$exam_id    = (int)($_GET['exam_id'] ?? 0);

// Validate student existence
$student = null;
if ($student_id > 0) {
    $res_student = $dao->getData('*', 'examinees', 'id = ' . $student_id);
    if (!empty($res_student) && is_array($res_student)) {
        $student = $res_student[0];
    }
}

// Validate exam existence
$exam = null;
if ($exam_id > 0) {
    $res_exam = $dao->getData('*', 'exams', 'id = ' . $exam_id);
    if (!empty($res_exam) && is_array($res_exam)) {
        $exam = $res_exam[0];
    }
}

// Fetch exam result record (if taken)
$result_row = null;
if ($student && $exam) {
    $r_res = $dao->query("
        SELECT r.*, e.title AS exam_title, e.category AS exam_category,
               COALESCE(e.passing_percentage, 50.00) AS passing_percentage,
               em.name AS student_name, em.email AS student_email, em.profile_photo AS student_photo
        FROM exam_results r
        JOIN exams e ON e.id = r.exam_id
        JOIN examinees em ON em.id = r.student_id
        WHERE r.student_id = $student_id AND r.exam_id = $exam_id
        ORDER BY r.submitted_at DESC, r.id DESC
        LIMIT 1
    ");
    if (!empty($r_res) && is_array($r_res)) {
        $result_row = $r_res[0];
    }
}

// Fetch registration record (if registered)
$registration = null;
if ($student && $exam) {
    $r_reg = $dao->query("
        SELECT reg.*, e.title AS exam_title, e.category AS exam_category
        FROM registrations reg
        LEFT JOIN exams e ON e.id = reg.exam_id
        WHERE reg.student_id = $student_id AND reg.exam_id = $exam_id
        LIMIT 1
    ");
    if (!empty($r_reg) && is_array($r_reg)) {
        $registration = $r_reg[0];
    }
}

// Check student answers count (for inconsistent data detection)
$answers_count = 0;
$answers_list  = array();
if ($student && $exam) {
    $r_answers = $dao->query("
        SELECT sa.*, q.question_text, q.question_type, q.marks AS q_marks,
               q.option_a, q.option_b, q.option_c, q.option_d,
               q.correct_option, q.correct_answer, q.options_data
        FROM student_answers sa
        LEFT JOIN questions q ON q.id = sa.question_id
        WHERE sa.student_id = $student_id AND sa.exam_id = $exam_id
        ORDER BY sa.id ASC
    ");
    if (!empty($r_answers) && is_array($r_answers)) {
        $answers_list  = $r_answers;
        $answers_count = count($r_answers);
    }
}

// Evaluation computation
$eval_status = null;
if ($result_row) {
    $eval_status = evalio_pass_fail_status(
        $result_row['marks_obtained'],
        $result_row['total_marks'],
        $result_row['passing_percentage'],
        $result_row['grading_status']
    );
}

include('header.php');
?>

<style>
    /* Examinee Result Page Styling */
    .result-nav-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 22px;
    }
    .btn-back-action {
        background: var(--card-body-bg, #ffffff);
        color: var(--text-primary, #0f172a) !important;
        border: 1px solid var(--border-color, #cbd5e1);
        border-radius: 8px;
        padding: 7px 14px;
        font-weight: 600;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }
    .btn-back-action:hover {
        background: rgba(99, 102, 241, 0.08);
        border-color: #6366f1;
        color: #4f46e5 !important;
        transform: translateX(-2px);
    }
    [data-theme="dark"] .btn-back-action {
        background: #111c35;
        border-color: rgba(255,255,255,0.12);
        color: #e2e8f0 !important;
    }
    [data-theme="dark"] .btn-back-action:hover {
        background: #1e293b;
        color: #a5b4fc !important;
    }

    /* Candidate & Exam Summary Top Card */
    .exam-header-card {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        padding: 20px 24px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        margin-bottom: 24px;
    }
    [data-theme="dark"] .exam-header-card {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .exam-header-title {
        font-size: 24px;
        font-weight: 800;
        margin: 0 0 6px 0;
        color: var(--text-primary, #0f172a);
    }
    .exam-header-subtitle {
        font-size: 14px;
        color: var(--text-muted, #64748b);
        margin: 0;
    }
    .exam-header-subtitle a {
        font-weight: 700;
        color: #4f46e5;
        text-decoration: none;
    }
    .exam-header-subtitle a:hover {
        text-decoration: underline;
    }
    [data-theme="dark"] .exam-header-subtitle a {
        color: #818cf8;
    }

    /* Big Glowing Result Status Banner */
    .banner-result {
        border-radius: 16px;
        padding: 28px 24px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 24px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease;
    }
    @media (max-width: 600px) {
        .banner-result {
            flex-direction: column;
            text-align: center;
            gap: 16px;
        }
    }
    .banner-icon-wrap {
        width: 80px;
        height: 80px;
        min-width: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
    }
    .banner-text-wrap {
        flex: 1;
    }
    .banner-badge-title {
        font-size: 32px;
        font-weight: 900;
        line-height: 1.1;
        letter-spacing: -0.5px;
        margin: 0 0 4px 0;
        text-transform: uppercase;
    }
    .banner-subtext {
        font-size: 15px;
        font-weight: 500;
        margin: 0;
    }

    /* Banner Status Variants */
    /* Pass (Green Neon) */
    .banner-pass {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(5, 150, 105, 0.18) 100%);
        border: 2px solid #10b981;
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.18);
    }
    .banner-pass .banner-icon-wrap {
        background: rgba(16, 185, 129, 0.2);
        color: #059669;
    }
    .banner-pass .banner-badge-title { color: #059669; }
    .banner-pass .banner-subtext { color: #065f46; }
    [data-theme="dark"] .banner-pass {
        background: #04251d;
        border-color: #10b981;
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.4);
    }
    [data-theme="dark"] .banner-pass .banner-icon-wrap {
        background: rgba(16, 185, 129, 0.3);
        color: #34d399;
    }
    [data-theme="dark"] .banner-pass .banner-badge-title { color: #34d399; }
    [data-theme="dark"] .banner-pass .banner-subtext { color: #a7f3d0; }

    /* Fail (Red Neon) */
    .banner-fail {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(220, 38, 38, 0.18) 100%);
        border: 2px solid #ef4444;
        box-shadow: 0 4px 20px rgba(239, 68, 68, 0.18);
    }
    .banner-fail .banner-icon-wrap {
        background: rgba(239, 68, 68, 0.2);
        color: #dc2626;
    }
    .banner-fail .banner-badge-title { color: #dc2626; }
    .banner-fail .banner-subtext { color: #991b1b; }
    [data-theme="dark"] .banner-fail {
        background: #2a0b10;
        border-color: #ef4444;
        box-shadow: 0 0 30px rgba(239, 68, 68, 0.4);
    }
    [data-theme="dark"] .banner-fail .banner-icon-wrap {
        background: rgba(239, 68, 68, 0.3);
        color: #f87171;
    }
    [data-theme="dark"] .banner-fail .banner-badge-title { color: #f87171; }
    [data-theme="dark"] .banner-fail .banner-subtext { color: #fecaca; }

    /* Awaiting Evaluation (Yellow/Amber Neon) */
    .banner-pending {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(217, 119, 6, 0.18) 100%);
        border: 2px solid #f59e0b;
        box-shadow: 0 4px 20px rgba(245, 158, 11, 0.18);
    }
    .banner-pending .banner-icon-wrap {
        background: rgba(245, 158, 11, 0.2);
        color: #d97706;
    }
    .banner-pending .banner-badge-title { color: #d97706; }
    .banner-pending .banner-subtext { color: #92400e; }
    [data-theme="dark"] .banner-pending {
        background: #2a1f05;
        border-color: #f59e0b;
        box-shadow: 0 0 30px rgba(245, 158, 11, 0.4);
    }
    [data-theme="dark"] .banner-pending .banner-icon-wrap {
        background: rgba(245, 158, 11, 0.3);
        color: #fbbf24;
    }
    [data-theme="dark"] .banner-pending .banner-badge-title { color: #fbbf24; }
    [data-theme="dark"] .banner-pending .banner-subtext { color: #fef3c7; }

    /* Score Metrics Grid (6 Highlights) */
    .metrics-summary-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 14px;
        margin-bottom: 28px;
    }
    @media (max-width: 1200px) {
        .metrics-summary-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 600px) {
        .metrics-summary-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .metric-pill {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    [data-theme="dark"] .metric-pill {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .metric-pill-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted, #64748b);
        margin-bottom: 4px;
    }
    .metric-pill-val {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-primary, #0f172a);
        line-height: 1.2;
    }

    /* Question-by-Question Section */
    .breakdown-header {
        font-size: 19px;
        font-weight: 800;
        margin: 0 0 16px 0;
        color: var(--text-primary, #0f172a);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .question-card {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    [data-theme="dark"] .question-card {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .question-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .question-title-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .question-num-tag {
        font-weight: 800;
        font-size: 14px;
        color: var(--text-primary, #0f172a);
    }
    .badge-qtype {
        background: rgba(99, 102, 241, 0.12);
        color: #4f46e5;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 4px;
    }
    [data-theme="dark"] .badge-qtype {
        background: rgba(99, 102, 241, 0.25);
        color: #a5b4fc;
    }
    .badge-marks-awarded {
        background: var(--table-header-bg, #f1f5f9);
        color: var(--text-primary, #1e293b);
        border: 1px solid var(--border-color, #cbd5e1);
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }
    [data-theme="dark"] .badge-marks-awarded {
        background: #111c35;
        border-color: rgba(255,255,255,0.12);
        color: #e2e8f0;
    }
    .question-text-content {
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-primary, #0f172a);
        line-height: 1.5;
        margin-bottom: 16px;
    }

    /* Answers Comparison Blocks */
    .answer-block {
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 10px;
        font-size: 13.5px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .ans-examinee {
        background: rgba(99, 102, 241, 0.06);
        border: 1px solid rgba(99, 102, 241, 0.2);
        color: var(--text-primary, #0f172a);
    }
    [data-theme="dark"] .ans-examinee {
        background: rgba(99, 102, 241, 0.12);
        border-color: rgba(99, 102, 241, 0.3);
        color: #e2e8f0;
    }
    .ans-correct {
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.25);
        color: #065f46;
    }
    [data-theme="dark"] .ans-correct {
        background: rgba(16, 185, 129, 0.15);
        border-color: rgba(16, 185, 129, 0.35);
        color: #a7f3d0;
    }
    .ans-label {
        font-weight: 700;
        min-width: 135px;
        display: inline-block;
    }

    /* MCQ Option List in Question Card */
    .mcq-options-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 14px;
    }
    @media (max-width: 650px) {
        .mcq-options-grid { grid-template-columns: 1fr; }
    }
    .mcq-opt-pill {
        padding: 9px 14px;
        border-radius: 8px;
        border: 1px solid var(--border-color, #e2e8f0);
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--table-header-bg, #f8fafc);
        color: var(--text-primary, #1e293b);
    }
    [data-theme="dark"] .mcq-opt-pill {
        background: #111c35;
        border-color: rgba(255,255,255,0.06);
        color: #cbd5e1;
    }
    .mcq-opt-pill.is-user-choice {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.1);
        font-weight: 600;
    }
    .mcq-opt-pill.is-correct-opt {
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.12);
        color: #065f46;
        font-weight: 600;
    }
    [data-theme="dark"] .mcq-opt-pill.is-correct-opt {
        color: #34d399;
    }

    /* Essay Examiner Feedback Box */
    .essay-feedback-box {
        background: rgba(245, 158, 11, 0.08);
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 10px;
        padding: 12px 16px;
        margin-top: 10px;
        color: #92400e;
        font-size: 13.5px;
    }
    [data-theme="dark"] .essay-feedback-box {
        background: rgba(245, 158, 11, 0.15);
        border-color: rgba(245, 158, 11, 0.35);
        color: #fde68a;
    }

    /* State Notices */
    .state-notice-card {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 16px;
        padding: 50px 30px;
        text-align: center;
        box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        margin-bottom: 25px;
    }
    [data-theme="dark"] .state-notice-card {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .state-notice-icon {
        font-size: 46px;
        display: block;
        margin-bottom: 14px;
        opacity: 0.8;
    }
    .state-notice-title {
        font-size: 22px;
        font-weight: 800;
        margin: 0 0 8px 0;
        color: var(--text-primary, #0f172a);
    }
    .state-notice-desc {
        color: var(--text-muted, #64748b);
        font-size: 14px;
        max-width: 520px;
        margin: 0 auto 24px auto;
        line-height: 1.5;
    }
    .reg-details-box {
        background: var(--table-header-bg, #f8fafc);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 18px 24px;
        max-width: 500px;
        margin: 0 auto 24px auto;
        text-align: left;
    }
    [data-theme="dark"] .reg-details-box {
        background: #111c35;
        border-color: rgba(255,255,255,0.08);
    }
    .reg-detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 13.5px;
    }
    .reg-detail-row:last-child {
        margin-bottom: 0;
    }
</style>

<div class="row">
    <div class="col-md-12">

        <!-- Navigation Bar -->
        <div class="result-nav-bar">
            <?php if ($student): ?>
                <a href="examinee_view.php?id=<?= $student_id; ?>" class="btn-back-action">
                    <i class="fa fa-arrow-left"></i> Back to Examinee Profile
                </a>
            <?php else: ?>
                <a href="examinees.php" class="btn-back-action">
                    <i class="fa fa-arrow-left"></i> Back to Examinees List
                </a>
            <?php endif; ?>

            <div style="font-size:13px; color:var(--text-muted);">
                Examinee Management / <strong>Examination Performance</strong>
            </div>
        </div>

        <?php if (!$student || !$exam): ?>
            <!-- CASE 1: NOT FOUND (Student or Exam Missing) -->
            <div class="state-notice-card">
                <i class="fa fa-exclamation-triangle state-notice-icon text-danger"></i>
                <h2 class="state-notice-title">Record Not Found</h2>
                <p class="state-notice-desc">
                    The requested student (#<?= $student_id; ?>) or exam (#<?= $exam_id; ?>) could not be located in the database.
                </p>
                <a href="examinees.php" class="btn btn-primary" style="border-radius:8px; font-weight:600;">
                    <i class="fa fa-arrow-left"></i> Return to Examinees List
                </a>
            </div>

        <?php else: ?>

            <!-- Header Info Card -->
            <div class="exam-header-card">
                <h1 class="exam-header-title"><?= htmlspecialchars((string)$exam['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="exam-header-subtitle">
                    Examinee: <a href="examinee_view.php?id=<?= $student_id; ?>"><?= htmlspecialchars((string)$student['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                    &bull; Email: <?= htmlspecialchars((string)$student['email'], ENT_QUOTES, 'UTF-8'); ?>
                    &bull; Type: <strong><?= htmlspecialchars((string)$exam['category'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </p>
            </div>

            <?php if (!empty($result_row)): ?>
                <!-- ======================================================== -->
                <!-- CASE 2: RESULT EXISTS (Display Banner, Metrics & Breakdown) -->
                <!-- ======================================================== -->

                <?php
                    $is_passed = $eval_status['is_passed'];
                    $banner_class = 'banner-pending';
                    $banner_icon  = 'fa-clock-o';
                    $banner_title = 'AWAITING EVALUATION';
                    $banner_sub   = 'This exam contains descriptive or essay questions awaiting examiner evaluation.';

                    if ($eval_status['status'] === 'pass') {
                        $banner_class = 'banner-pass';
                        $banner_icon  = 'fa-check-circle';
                        $banner_title = 'PASSED';
                        $banner_sub   = 'Examinee achieved ' . $eval_status['percentage'] . '% (Required: ' . (float)$result_row['passing_percentage'] . '%).';
                    } elseif ($eval_status['status'] === 'fail') {
                        $banner_class = 'banner-fail';
                        $banner_icon  = 'fa-times-circle';
                        $banner_title = 'FAILED';
                        $banner_sub   = 'Examinee achieved ' . $eval_status['percentage'] . '% (Required: ' . (float)$result_row['passing_percentage'] . '%).';
                    }
                ?>

                <!-- Big Glowing Result Status Banner -->
                <div class="banner-result <?= $banner_class; ?>">
                    <div class="banner-icon-wrap">
                        <i class="fa <?= $banner_icon; ?>"></i>
                    </div>
                    <div class="banner-text-wrap">
                        <h2 class="banner-badge-title"><?= $banner_title; ?></h2>
                        <p class="banner-subtext"><?= $banner_sub; ?></p>
                    </div>
                </div>

                <!-- 6 Highlights Grid -->
                <div class="metrics-summary-grid">
                    <!-- Marks Obtained -->
                    <div class="metric-pill">
                        <div class="metric-pill-label">Marks Obtained</div>
                        <div class="metric-pill-val">
                            <?= rtrim(rtrim(number_format((float)$result_row['marks_obtained'], 2), '0'), '.'); ?>
                            <span style="font-size:13px; color:var(--text-muted); font-weight:600;">/ <?= rtrim(rtrim(number_format((float)$result_row['total_marks'], 2), '0'), '.'); ?></span>
                        </div>
                    </div>

                    <!-- Percentage -->
                    <div class="metric-pill">
                        <div class="metric-pill-label">Percentage</div>
                        <div class="metric-pill-val" style="color:#6366f1;">
                            <?= $eval_status['percentage']; ?>%
                        </div>
                    </div>

                    <!-- Passing Required -->
                    <div class="metric-pill">
                        <div class="metric-pill-label">Passing Required</div>
                        <div class="metric-pill-val">
                            <?= (float)$result_row['passing_percentage']; ?>%
                        </div>
                    </div>

                    <!-- Total Questions -->
                    <div class="metric-pill">
                        <div class="metric-pill-label">Total Questions</div>
                        <div class="metric-pill-val">
                            <?= (int)$result_row['total_questions']; ?>
                        </div>
                    </div>

                    <!-- Exam Type -->
                    <div class="metric-pill">
                        <div class="metric-pill-label">Category</div>
                        <div class="metric-pill-val" style="font-size:16px;">
                            <?= htmlspecialchars((string)$result_row['exam_category'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>

                    <!-- Submitted Date -->
                    <div class="metric-pill">
                        <div class="metric-pill-label">Submitted On</div>
                        <div class="metric-pill-val" style="font-size:13.5px; font-weight:700;">
                            <?= !empty($result_row['submitted_at']) ? date('d M Y, h:i A', strtotime($result_row['submitted_at'])) : '-'; ?>
                        </div>
                    </div>
                </div>

                <!-- Question-by-Question Breakdown -->
                <div class="breakdown-header">
                    <span><i class="fa fa-list-ol" style="margin-right:6px; color:#6366f1;"></i> Question-by-Question Breakdown</span>
                    <span style="font-size:13px; font-weight:600; color:var(--text-muted);">
                        Showing <?= count($answers_list); ?> Questions
                    </span>
                </div>

                <?php if (!empty($answers_list)): ?>
                    <?php 
                    $q_idx = 1; 
                    foreach ($answers_list as $ans): 
                        $q_type = strtolower(trim((string)$ans['question_type']));
                        $is_mcq = ($q_type === 'mcq');
                        $user_ans = trim((string)$ans['answer_given']);
                        $corr_opt = trim((string)$ans['correct_option']);
                        $corr_ans = trim((string)$ans['correct_answer']);

                        $is_ans_correct = ($ans['is_correct'] === '1' || $ans['is_correct'] === 1);
                        $is_ans_pending = ($ans['status'] === 'pending_review' || $ans['is_correct'] === null);
                    ?>
                        <div class="question-card">
                            <!-- Card Header -->
                            <div class="question-card-top">
                                <div class="question-title-left">
                                    <span class="question-num-tag">Question #<?= $q_idx++; ?></span>
                                    <span class="badge-qtype"><?= htmlspecialchars((string)str_replace('_', ' ', $q_type), ENT_QUOTES, 'UTF-8'); ?></span>
                                    
                                    <?php if ($is_ans_pending): ?>
                                        <span class="label label-warning" style="font-size:11px; padding:3px 7px;">
                                            <i class="fa fa-clock-o"></i> Pending Review
                                        </span>
                                    <?php elseif ($is_ans_correct): ?>
                                        <span class="label label-success" style="font-size:11px; padding:3px 7px;">
                                            <i class="fa fa-check"></i> Correct
                                        </span>
                                    <?php else: ?>
                                        <span class="label label-danger" style="font-size:11px; padding:3px 7px;">
                                            <i class="fa fa-times"></i> Incorrect
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="badge-marks-awarded">
                                    Marks: <strong><?= rtrim(rtrim(number_format((float)$ans['marks_awarded'], 2), '0'), '.'); ?></strong>
                                    / <?= rtrim(rtrim(number_format((float)$ans['max_marks'], 2), '0'), '.'); ?>
                                </div>
                            </div>

                            <!-- Question Text -->
                            <div class="question-text-content">
                                <?= nl2br(htmlspecialchars((string)$ans['question_text'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>

                            <?php if ($is_mcq): ?>
                                <!-- MCQ Options Display -->
                                <div class="mcq-options-grid">
                                    <?php 
                                    $options = array(
                                        'A' => $ans['option_a'],
                                        'B' => $ans['option_b'],
                                        'C' => $ans['option_c'],
                                        'D' => $ans['option_d']
                                    );
                                    foreach ($options as $opt_key => $opt_val):
                                        if (empty($opt_val) && $opt_val !== '0') continue;
                                        $chosen   = (strcasecmp($user_ans, $opt_key) === 0 || strcasecmp($user_ans, $opt_val) === 0);
                                        $correct  = (strcasecmp($corr_opt, $opt_key) === 0 || strcasecmp($corr_ans, $opt_key) === 0 || strcasecmp($corr_ans, $opt_val) === 0);
                                        
                                        $cls = '';
                                        if ($correct) $cls .= ' is-correct-opt';
                                        if ($chosen)  $cls .= ' is-user-choice';
                                    ?>
                                        <div class="mcq-opt-pill <?= $cls; ?>">
                                            <strong>(<?= $opt_key; ?>)</strong>
                                            <span style="flex:1;"><?= htmlspecialchars((string)$opt_val, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if ($chosen): ?>
                                                <span class="label label-primary" style="font-size:10px;"><i class="fa fa-user"></i> Examinee's Choice</span>
                                            <?php endif; ?>
                                            <?php if ($correct): ?>
                                                <span class="label label-success" style="font-size:10px;"><i class="fa fa-check"></i> Correct</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <!-- Non-MCQ / Essay / Short Answer / Fill in Blank -->
                                <div class="answer-block ans-examinee">
                                    <span class="ans-label"><i class="fa fa-pencil"></i> Examinee Answer:</span>
                                    <div style="flex:1;">
                                        <?= !empty($user_ans) ? nl2br(htmlspecialchars((string)$user_ans, ENT_QUOTES, 'UTF-8')) : '<em class="text-muted">(No answer provided)</em>'; ?>
                                    </div>
                                </div>

                                <?php if (!empty($corr_ans) || !empty($corr_opt)): ?>
                                    <div class="answer-block ans-correct">
                                        <span class="ans-label"><i class="fa fa-check-circle"></i> Correct Answer:</span>
                                        <div style="flex:1;">
                                            <?= nl2br(htmlspecialchars((string)(!empty($corr_ans) ? $corr_ans : $corr_opt), ENT_QUOTES, 'UTF-8')); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Examiner Feedback for Essays or Manual Grading -->
                            <?php if (!empty($ans['feedback'])): ?>
                                <div class="essay-feedback-box">
                                    <i class="fa fa-commenting" style="margin-right:6px;"></i>
                                    <strong>Examiner Feedback:</strong>
                                    <?= nl2br(htmlspecialchars((string)$ans['feedback'], ENT_QUOTES, 'UTF-8')); ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="question-card" style="text-align:center; padding:35px; color:var(--text-muted);">
                        <i class="fa fa-info-circle" style="font-size:24px; display:block; margin-bottom:8px;"></i>
                        No individual student answer logs recorded for this attempt.
                    </div>
                <?php endif; ?>

            <?php elseif ($answers_count > 0): ?>
                <!-- ======================================================== -->
                <!-- CASE 3: INCONSISTENT DATA (Answers exist but no exam_results) -->
                <!-- ======================================================== -->
                <div class="state-notice-card">
                    <i class="fa fa-exclamation-circle state-notice-icon text-warning"></i>
                    <h2 class="state-notice-title">Result Not Available</h2>
                    <p class="state-notice-desc">
                        Recorded candidate answers exist for this exam, but the final evaluated results row has not yet been compiled or generated.
                    </p>
                    <a href="examinee_view.php?id=<?= $student_id; ?>" class="btn btn-default" style="border-radius:8px; font-weight:600;">
                        <i class="fa fa-arrow-left"></i> Back to Examinee Profile
                    </a>
                </div>

            <?php elseif ($registration): ?>
                <!-- ======================================================== -->
                <!-- CASE 4: REGISTERED BUT NOT TAKEN YET (No result, no scores) -->
                <!-- ======================================================== -->
                <div class="state-notice-card">
                    <i class="fa fa-hourglass-half state-notice-icon text-info"></i>
                    <h2 class="state-notice-title">No Result Yet</h2>
                    <p class="state-notice-desc" style="font-weight:600; color:var(--text-primary); font-size:15px;">
                        This examinee has registered for this exam but has not taken it.
                    </p>

                    <div class="reg-details-box">
                        <div class="reg-detail-row">
                            <span class="text-muted">Exam Name:</span>
                            <strong><?= htmlspecialchars((string)$exam['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div class="reg-detail-row">
                            <span class="text-muted">Registration Number:</span>
                            <code><?= htmlspecialchars((string)($registration['registration_no'] ?: 'Pending'), ENT_QUOTES, 'UTF-8'); ?></code>
                        </div>
                        <div class="reg-detail-row">
                            <span class="text-muted">Registered On:</span>
                            <span><?= !empty($registration['registration_date']) ? date('d M Y, h:i A', strtotime($registration['registration_date'])) : '-'; ?></span>
                        </div>
                        <div class="reg-detail-row">
                            <span class="text-muted">Payment Status:</span>
                            <span class="label label-success"><?= htmlspecialchars((string)($registration['payment_status'] ?: 'Paid'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <?php if (isset($registration['amount_paid'])): ?>
                            <div class="reg-detail-row">
                                <span class="text-muted">Fee Paid:</span>
                                <strong>₹<?= number_format((float)$registration['amount_paid'], 2); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="examinee_view.php?id=<?= $student_id; ?>" class="btn btn-default" style="border-radius:8px; font-weight:600;">
                        <i class="fa fa-arrow-left"></i> Back to Examinee Profile
                    </a>
                </div>

            <?php else: ?>
                <!-- ======================================================== -->
                <!-- CASE 5: NO PARTICIPATION RECORD AT ALL -->
                <!-- ======================================================== -->
                <div class="state-notice-card">
                    <i class="fa fa-info-circle state-notice-icon text-muted"></i>
                    <h2 class="state-notice-title">No Participation Record</h2>
                    <p class="state-notice-desc">
                        This examinee has neither registered for nor attempted this examination.
                    </p>
                    <a href="examinee_view.php?id=<?= $student_id; ?>" class="btn btn-default" style="border-radius:8px; font-weight:600;">
                        <i class="fa fa-arrow-left"></i> Back to Examinee Profile
                    </a>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php
include('footer.php');
?>
