<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
date_default_timezone_set('Asia/Kolkata');

$admin_id = (int)($_SESSION['admin_id'] ?? 0);
$student_id = (int)($_GET['id'] ?? 0);

$alert_msg = '';
$alert_type = '';

// Fetch Examinee Details
$examinee = null;
if ($student_id > 0) {
    $res = $dao->getData('*', 'examinees', 'id = ' . $student_id);
    if (!empty($res) && is_array($res)) {
        $examinee = $res[0];
    }
}

// Fetch all exam results (TAKEN; includes Mock exams which have no registration)
$results = array();
if ($examinee) {
    $r_res = $dao->query("
        SELECT res.id AS result_id, res.exam_id, res.score, res.total_marks, res.marks_obtained,
               res.grading_status, res.submitted_at, res.evaluated_at, res.total_questions,
               e.title AS exam_title, e.category AS exam_category,
               COALESCE(e.passing_percentage, 50.00) AS passing_percentage,
               reg.registration_date, reg.registration_no, reg.amount_paid,
               1 AS is_taken
        FROM exam_results res
        LEFT JOIN exams e ON e.id = res.exam_id
        LEFT JOIN registrations reg ON (reg.student_id = res.student_id AND reg.exam_id = res.exam_id)
        WHERE res.student_id = $student_id
        ORDER BY res.submitted_at DESC, res.id DESC
    ");
    if (is_array($r_res)) {
        $results = $r_res;
    }
}

// Fetch all registrations with NO matching exam_results row (REGISTERED, NOT TAKEN yet)
$not_taken = array();
if ($examinee) {
    $r_not = $dao->query("
        SELECT NULL AS result_id, reg.exam_id, NULL AS score, NULL AS total_marks, NULL AS marks_obtained,
               NULL AS grading_status, NULL AS submitted_at, NULL AS evaluated_at, NULL AS total_questions,
               e.title AS exam_title, COALESCE(e.category, reg.category) AS exam_category,
               COALESCE(e.passing_percentage, 50.00) AS passing_percentage,
               reg.registration_date, reg.registration_no, reg.amount_paid,
               0 AS is_taken
        FROM registrations reg
        LEFT JOIN exams e ON e.id = reg.exam_id
        WHERE reg.student_id = $student_id
          AND NOT EXISTS (
              SELECT 1 FROM exam_results res
              WHERE res.student_id = reg.student_id AND res.exam_id = reg.exam_id
          )
        ORDER BY reg.registration_date DESC, reg.id DESC
    ");
    if (is_array($r_not)) {
        $not_taken = $r_not;
    }
}

// Combine all exam records
$all_exams = array_merge($results, $not_taken);

// Calculate summary counters
$cnt_registered = 0;
if ($examinee) {
    $r_reg_cnt = $dao->query("SELECT COUNT(*) AS total FROM registrations WHERE student_id = $student_id");
    $cnt_registered = (int)($r_reg_cnt[0]['total'] ?? 0);
}

$cnt_taken    = count($results);
$cnt_passed   = 0;
$cnt_failed   = 0;
$cnt_awaiting = 0;

foreach ($results as $item) {
    $st = evalio_pass_fail_status(
        $item['marks_obtained'],
        $item['total_marks'],
        $item['passing_percentage'],
        $item['grading_status']
    );

    if ($st['status'] === 'pending') {
        $cnt_awaiting++;
    } elseif ($st['is_passed'] === true) {
        $cnt_passed++;
    } elseif ($st['is_passed'] === false) {
        $cnt_failed++;
    }
}

// Profile avatar resolution
$avatar_url = 'assets/img/find_user.png';
if (!empty($examinee['profile_photo'])) {
    $pname = $examinee['profile_photo'];
    if (is_file(__DIR__ . '/../uploads/profile/' . $pname)) {
        $avatar_url = '../uploads/profile/' . rawurlencode($pname);
    } elseif (is_file(__DIR__ . '/../examinee/uploads/profile/' . $pname)) {
        $avatar_url = '../examinee/uploads/profile/' . rawurlencode($pname);
    }
}

include('header.php');
?>

<style>
    /* Examinee Profile View Styling */
    .view-header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 22px;
    }
    .btn-back-link {
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
    .btn-back-link:hover {
        background: rgba(99, 102, 241, 0.08);
        border-color: #6366f1;
        color: #4f46e5 !important;
        transform: translateX(-2px);
    }
    [data-theme="dark"] .btn-back-link {
        background: #111c35;
        border-color: rgba(255,255,255,0.12);
        color: #e2e8f0 !important;
    }
    [data-theme="dark"] .btn-back-link:hover {
        background: #1e293b;
        color: #a5b4fc !important;
    }

    /* Candidate Profile Card */
    .profile-card {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        margin-bottom: 25px;
        position: relative;
        overflow: hidden;
    }
    [data-theme="dark"] .profile-card {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .profile-card-inner {
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
    }
    .profile-avatar-wrap {
        position: relative;
        width: 96px;
        height: 96px;
        min-width: 96px;
    }
    .profile-avatar-img {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #6366f1;
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.25);
    }
    [data-theme="dark"] .profile-avatar-img {
        box-shadow: 0 0 20px rgba(0, 242, 254, 0.4);
        border-color: #00f2fe;
    }
    .profile-info {
        flex: 1;
        min-width: 260px;
    }
    .profile-name {
        font-size: 26px;
        font-weight: 800;
        margin: 0 0 6px 0;
        color: var(--text-primary, #0f172a);
        letter-spacing: -0.4px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    [data-theme="dark"] .profile-name {
        color: #ffffff;
    }
    .profile-meta-list {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-top: 10px;
        color: var(--text-muted, #64748b);
        font-size: 13.5px;
    }
    .profile-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .profile-meta-item i {
        color: #6366f1;
        font-size: 14px;
    }
    [data-theme="dark"] .profile-meta-item i {
        color: #818cf8;
    }
    .profile-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }
    @media (max-width: 768px) {
        .profile-actions {
            align-items: flex-start;
            width: 100%;
        }
    }

    /* 5 Glowing Summary Counters */
    .counters-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 14px;
        margin-bottom: 26px;
    }
    @media (max-width: 1100px) {
        .counters-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 700px) {
        .counters-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 480px) {
        .counters-grid { grid-template-columns: 1fr; }
    }

    .counter-card {
        background: var(--card-body-bg, #ffffff);
        border: 1.5px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .counter-card:hover {
        transform: translateY(-2px);
    }
    [data-theme="dark"] .counter-card {
        background: #0d1527;
    }
    .counter-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .counter-num {
        font-size: 22px;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 2px;
    }
    .counter-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted, #64748b);
    }

    /* Counter Card Themes */
    .card-cnt-reg { border-color: rgba(99, 102, 241, 0.4); }
    .card-cnt-reg .counter-icon { background: rgba(99, 102, 241, 0.15); color: #4f46e5; }
    .card-cnt-reg .counter-num { color: #4f46e5; }
    [data-theme="dark"] .card-cnt-reg { border-color: #6366f1; box-shadow: 0 0 14px rgba(99, 102, 241, 0.35); }
    [data-theme="dark"] .card-cnt-reg .counter-num { color: #818cf8; }

    .card-cnt-taken { border-color: rgba(6, 182, 212, 0.4); }
    .card-cnt-taken .counter-icon { background: rgba(6, 182, 212, 0.15); color: #0891b2; }
    .card-cnt-taken .counter-num { color: #0891b2; }
    [data-theme="dark"] .card-cnt-taken { border-color: #00d2ff; box-shadow: 0 0 14px rgba(0, 210, 255, 0.35); }
    [data-theme="dark"] .card-cnt-taken .counter-num { color: #00e5ff; }

    .card-cnt-pass { border-color: rgba(16, 185, 129, 0.4); }
    .card-cnt-pass .counter-icon { background: rgba(16, 185, 129, 0.15); color: #059669; }
    .card-cnt-pass .counter-num { color: #059669; }
    [data-theme="dark"] .card-cnt-pass { border-color: #10b981; box-shadow: 0 0 14px rgba(16, 185, 129, 0.35); }
    [data-theme="dark"] .card-cnt-pass .counter-num { color: #34d399; }

    .card-cnt-fail { border-color: rgba(239, 68, 68, 0.4); }
    .card-cnt-fail .counter-icon { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
    .card-cnt-fail .counter-num { color: #dc2626; }
    [data-theme="dark"] .card-cnt-fail { border-color: #ef4444; box-shadow: 0 0 14px rgba(239, 68, 68, 0.35); }
    [data-theme="dark"] .card-cnt-fail .counter-num { color: #f87171; }

    .card-cnt-pending { border-color: rgba(245, 158, 11, 0.4); }
    .card-cnt-pending .counter-icon { background: rgba(245, 158, 11, 0.15); color: #d97706; }
    .card-cnt-pending .counter-num { color: #d97706; }
    [data-theme="dark"] .card-cnt-pending { border-color: #f59e0b; box-shadow: 0 0 14px rgba(245, 158, 11, 0.35); }
    [data-theme="dark"] .card-cnt-pending .counter-num { color: #fbbf24; }

    /* Exams History Table Card */
    .history-card {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        overflow: hidden;
        margin-bottom: 30px;
    }
    [data-theme="dark"] .history-card {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .history-card-header {
        padding: 16px 22px;
        font-weight: 700;
        font-size: 16px;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        background: var(--table-header-bg, #f8fafc);
        color: var(--text-primary, #0f172a);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    [data-theme="dark"] .history-card-header {
        background: #111c35;
        border-color: rgba(255,255,255,0.08);
        color: #ffffff;
    }

    .table-history {
        margin-bottom: 0;
        width: 100%;
    }
    .table-history > thead > tr > th {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 13px 18px;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        background: transparent;
        color: var(--text-muted, #64748b);
        vertical-align: middle;
    }
    [data-theme="dark"] .table-history > thead > tr > th {
        color: #94a3b8;
    }
    .table-history > tbody > tr > td {
        padding: 14px 18px;
        vertical-align: middle;
        font-size: 13px;
        border-top: 1px solid var(--border-color, #f1f5f9);
        color: var(--text-primary, #1e293b);
    }
    [data-theme="dark"] .table-history > tbody > tr > td {
        border-color: rgba(255,255,255,0.05);
        color: #cbd5e1;
    }
    .table-history > tbody > tr:hover {
        background: rgba(99, 102, 241, 0.04);
    }
    [data-theme="dark"] .table-history > tbody > tr:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .exam-title-link {
        font-weight: 700;
        color: #4f46e5;
        text-decoration: none !important;
        font-size: 14px;
        display: block;
        margin-bottom: 2px;
    }
    .exam-title-link:hover {
        text-decoration: underline !important;
        color: #4338ca;
    }
    [data-theme="dark"] .exam-title-link {
        color: #818cf8;
    }
    [data-theme="dark"] .exam-title-link:hover {
        color: #a5b4fc;
    }

    /* Badges */
    .badge-type-cert {
        background: rgba(6, 182, 212, 0.15);
        color: #0891b2;
        border: 1px solid rgba(6, 182, 212, 0.35);
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
    }
    [data-theme="dark"] .badge-type-cert {
        background: rgba(6, 182, 212, 0.25);
        color: #22d3ee;
        border-color: rgba(34, 211, 238, 0.4);
    }

    .badge-type-mock {
        background: rgba(236, 72, 153, 0.15);
        color: #db2777;
        border: 1px solid rgba(236, 72, 153, 0.35);
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
    }
    [data-theme="dark"] .badge-type-mock {
        background: rgba(236, 72, 153, 0.25);
        color: #f472b6;
        border-color: rgba(244, 114, 182, 0.4);
    }

    .badge-taken-yes {
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
        font-weight: 600;
        padding: 4px 9px;
        border-radius: 12px;
        font-size: 11.5px;
    }
    [data-theme="dark"] .badge-taken-yes {
        background: rgba(16, 185, 129, 0.25);
        color: #34d399;
    }

    .badge-taken-no {
        background: rgba(100, 116, 139, 0.15);
        color: #475569;
        font-weight: 600;
        padding: 4px 9px;
        border-radius: 12px;
        font-size: 11.5px;
    }
    [data-theme="dark"] .badge-taken-no {
        background: rgba(148, 163, 184, 0.2);
        color: #94a3b8;
    }
</style>

<div class="row">
    <div class="col-md-12">

        <!-- Top Navigation Bar -->
        <div class="view-header-bar">
            <a href="examinees.php" class="btn-back-link">
                <i class="fa fa-arrow-left"></i> Back to Examinees
            </a>
            <div style="font-size:13px; color:var(--text-muted);">
                Examinee Management / <strong>Profile Details</strong>
            </div>
        </div>

        <?php if (!empty($alert_msg)): ?>
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible" role="alert" style="border-radius:10px;">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <i class="fa <?= $alert_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <strong><?= e($alert_msg); ?></strong>
            </div>
        <?php endif; ?>

        <?php if (!$examinee): ?>
            <!-- Not Found State -->
            <div class="profile-card" style="text-align:center; padding:60px 20px;">
                <i class="fa fa-user-times" style="font-size:48px; color:#ef4444; opacity:0.6; display:block; margin-bottom:14px;"></i>
                <h3 style="font-weight:800; color:var(--text-primary); margin:0 0 8px 0;">Examinee Not Found</h3>
                <p style="color:var(--text-muted); font-size:14px; max-width:400px; margin:0 auto 20px auto;">
                    The examinee ID requested (#<?= $student_id; ?>) does not exist or has been removed from the platform.
                </p>
                <a href="examinees.php" class="btn btn-primary" style="border-radius:8px; font-weight:600;">
                    <i class="fa fa-arrow-left"></i> Return to Examinees List
                </a>
            </div>
        <?php else: ?>

            <?php 
                $is_active = (strtolower(trim((string)$examinee['status'])) === 'active');
            ?>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-card-inner">
                    <!-- Photo -->
                    <div class="profile-avatar-wrap">
                        <img src="<?= $avatar_url; ?>" alt="<?= htmlspecialchars((string)$examinee['name'], ENT_QUOTES, 'UTF-8'); ?>" class="profile-avatar-img" onerror="this.src='assets/img/find_user.png'">
                    </div>

                    <!-- Details -->
                    <div class="profile-info">
                        <div class="profile-name">
                            <span><?= htmlspecialchars((string)$examinee['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($is_active): ?>
                                <span class="label label-success" style="font-size:12px; font-weight:600; padding:4px 9px;">
                                    <i class="fa fa-check-circle"></i> Active
                                </span>
                            <?php else: ?>
                                <span class="label label-danger" style="font-size:12px; font-weight:600; padding:4px 9px;">
                                    <i class="fa fa-ban"></i> Disabled
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="profile-meta-list">
                            <div class="profile-meta-item">
                                <i class="fa fa-id-badge"></i> Candidate ID: <strong>#<?= (int)$examinee['id']; ?></strong>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fa fa-envelope-o"></i>
                                <a href="mailto:<?= htmlspecialchars((string)$examinee['email'], ENT_QUOTES, 'UTF-8'); ?>" style="color:inherit; text-decoration:none;">
                                    <?= htmlspecialchars((string)$examinee['email'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fa fa-phone"></i>
                                <span><?= !empty($examinee['phone']) ? htmlspecialchars((string)$examinee['phone'], ENT_QUOTES, 'UTF-8') : 'No phone provided'; ?></span>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fa fa-calendar"></i>
                                <span>Joined on <?= !empty($examinee['created_at']) ? date('d M Y, h:i A', strtotime($examinee['created_at'])) : '-'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5 Glowing Summary Counters -->
            <div class="counters-grid">
                
                <!-- 1. Registered Exams -->
                <div class="counter-card card-cnt-reg">
                    <div class="counter-icon">
                        <i class="fa fa-bookmark-o"></i>
                    </div>
                    <div>
                        <div class="counter-num"><?= $cnt_registered; ?></div>
                        <div class="counter-label">Registered</div>
                    </div>
                </div>

                <!-- 2. Exams Taken -->
                <div class="counter-card card-cnt-taken">
                    <div class="counter-icon">
                        <i class="fa fa-pencil-square-o"></i>
                    </div>
                    <div>
                        <div class="counter-num"><?= $cnt_taken; ?></div>
                        <div class="counter-label">Taken</div>
                    </div>
                </div>

                <!-- 3. Passed -->
                <div class="counter-card card-cnt-pass">
                    <div class="counter-icon">
                        <i class="fa fa-check-circle-o"></i>
                    </div>
                    <div>
                        <div class="counter-num"><?= $cnt_passed; ?></div>
                        <div class="counter-label">Passed</div>
                    </div>
                </div>

                <!-- 4. Failed -->
                <div class="counter-card card-cnt-fail">
                    <div class="counter-icon">
                        <i class="fa fa-times-circle-o"></i>
                    </div>
                    <div>
                        <div class="counter-num"><?= $cnt_failed; ?></div>
                        <div class="counter-label">Failed</div>
                    </div>
                </div>

                <!-- 5. Awaiting Evaluation -->
                <div class="counter-card card-cnt-pending">
                    <div class="counter-icon">
                        <i class="fa fa-clock-o"></i>
                    </div>
                    <div>
                        <div class="counter-num"><?= $cnt_awaiting; ?></div>
                        <div class="counter-label">Awaiting Eval</div>
                    </div>
                </div>

            </div>

            <!-- Combined Exams History Table Card -->
            <div class="history-card">
                <div class="history-card-header">
                    <span><i class="fa fa-graduation-cap text-primary" style="margin-right:8px;"></i> Exam Participation & Results</span>
                    <span style="font-size:12.5px; font-weight:600; color:var(--text-muted);">
                        Total Records: <?= count($all_exams); ?>
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-history">
                        <thead>
                            <tr>
                                <th style="width:50px; text-align:center;">#</th>
                                <th>Exam Name</th>
                                <th style="text-align:center;">Type</th>
                                <th>Registered On</th>
                                <th style="text-align:center;">Status</th>
                                <th>Score</th>
                                <th style="text-align:center;">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_exams)): ?>
                                <?php 
                                $idx = 1; 
                                foreach ($all_exams as $row): 
                                    $eid        = (int)$row['exam_id'];
                                    $is_taken   = !empty($row['is_taken']);
                                    $cat        = $row['exam_category'] ?? 'Certification';
                                    $is_mock    = (strcasecmp($cat, 'Mock') === 0);

                                    // Evaluation status if taken
                                    $status_info = null;
                                    if ($is_taken) {
                                        $status_info = evalio_pass_fail_status(
                                            $row['marks_obtained'],
                                            $row['total_marks'],
                                            $row['passing_percentage'],
                                            $row['grading_status']
                                        );
                                    }
                                ?>
                                    <tr>
                                        <!-- # -->
                                        <td style="text-align:center; font-weight:600; color:var(--text-muted);">
                                            <?= $idx++; ?>
                                        </td>

                                        <!-- Exam Name (Link to examinee_result.php) -->
                                        <td>
                                            <a href="examinee_result.php?student_id=<?= $student_id; ?>&exam_id=<?= $eid; ?>" class="exam-title-link">
                                                <?= htmlspecialchars((string)($row['exam_title'] ?: 'Exam #' . $eid), ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                            <?php if ($is_taken && !empty($row['submitted_at'])): ?>
                                                <div style="font-size:11.5px; color:var(--text-muted);">
                                                    <i class="fa fa-clock-o"></i> Attempted: <?= date('d M Y, h:i A', strtotime($row['submitted_at'])); ?>
                                                </div>
                                            <?php elseif (!empty($row['registration_no'])): ?>
                                                <div style="font-size:11.5px; color:var(--text-muted);">
                                                    <i class="fa fa-barcode"></i> Reg No: <?= htmlspecialchars((string)$row['registration_no'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Type (Certification / Mock badge) -->
                                        <td style="text-align:center;">
                                            <?php if ($is_mock): ?>
                                                <span class="badge-type-mock"><i class="fa fa-pencil"></i> Mock</span>
                                            <?php else: ?>
                                                <span class="badge-type-cert"><i class="fa fa-certificate"></i> Certification</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Registered On -->
                                        <td>
                                            <?php if ($is_mock): ?>
                                                <span style="color:var(--text-muted);">-</span>
                                            <?php elseif (!empty($row['registration_date'])): ?>
                                                <span style="font-size:12.5px;"><?= date('d M Y, h:i A', strtotime($row['registration_date'])); ?></span>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted);">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Status Badge (Taken / Not Taken Yet) -->
                                        <td style="text-align:center;">
                                            <?php if ($is_taken): ?>
                                                <span class="badge-taken-yes">
                                                    <i class="fa fa-check-circle"></i> Taken
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-taken-no">
                                                    <i class="fa fa-hourglass-start"></i> Not Taken Yet
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Score -->
                                        <td>
                                            <?php if ($is_taken): ?>
                                                <?php if (($row['grading_status'] ?? '') === 'pending_review'): ?>
                                                    <span class="text-warning" style="font-weight:600;">
                                                        <i class="fa fa-clock-o"></i> Pending Review
                                                    </span>
                                                <?php else: ?>
                                                    <strong><?= rtrim(rtrim(number_format((float)$row['marks_obtained'], 2), '0'), '.'); ?></strong>
                                                    <span style="color:var(--text-muted);">/ <?= rtrim(rtrim(number_format((float)$row['total_marks'], 2), '0'), '.'); ?></span>
                                                    <span style="font-size:11.5px; color:var(--text-muted); margin-left:4px;">
                                                        (<?= $status_info['percentage']; ?>%)
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted);">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Result Badge -->
                                        <td style="text-align:center;">
                                            <?php if ($is_taken && $status_info): ?>
                                                <?= $status_info['badge_html']; ?>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted);">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Empty state for candidates with no exams -->
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:55px 20px; color:var(--text-muted);">
                                        <i class="fa fa-graduation-cap" style="font-size:42px; display:block; margin-bottom:12px; opacity:0.35;"></i>
                                        <strong style="font-size:16px; color:var(--text-primary);">No exam activity recorded yet.</strong>
                                        <p style="margin:6px 0 0 0; font-size:13px; max-width:440px; margin-left:auto; margin-right:auto;">
                                            This candidate has not registered for any certification tests or attempted any mock exams on Evalio yet.
                                        </p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php
include('footer.php');
?>