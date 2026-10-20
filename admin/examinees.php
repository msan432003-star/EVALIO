<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
date_default_timezone_set('Asia/Kolkata');

$admin_id = (int)($_SESSION['admin_id'] ?? 0);

// Handle POST request to toggle examinee status (Enable / Disable)
$alert_msg = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $examinee_id = (int)($_POST['examinee_id'] ?? 0);
    $new_status  = ($_POST['new_status'] ?? '') === 'disabled' ? 'disabled' : 'active';

    if ($examinee_id > 0) {
        $updated = $dao->update(array('status' => $new_status), 'examinees', 'id = ' . $examinee_id);
        if ($updated) {
            logAction($admin_id, ($new_status === 'active' ? 'activate_examinee' : 'disable_examinee'), 'examinees', $examinee_id, 'Status changed to ' . $new_status);
            $alert_msg = 'Examinee status updated to "' . ucfirst($new_status) . '" successfully.';
            $alert_type = 'success';
        } else {
            $alert_msg = 'Failed to update examinee status. Please try again.';
            $alert_type = 'danger';
        }
    }
}

// Fetch all examinees with aggregate counts of exams registered and exams taken
$examinees = $dao->query("
    SELECT e.id, e.name, e.email, e.phone, e.status, e.profile_photo, e.created_at,
           (SELECT COUNT(*) FROM registrations r WHERE r.student_id = e.id) AS exams_registered,
           (SELECT COUNT(*) FROM exam_results res WHERE res.student_id = e.id) AS exams_taken
    FROM examinees e
    ORDER BY e.id DESC
");
if (!is_array($examinees)) {
    $examinees = array();
}

// Calculate summary stats
$total_count    = count($examinees);
$active_count   = 0;
$disabled_count = 0;
$total_reg      = 0;
$total_taken    = 0;

foreach ($examinees as $ex_item) {
    if (($ex_item['status'] ?? '') === 'active') {
        $active_count++;
    } else {
        $disabled_count++;
    }
    $total_reg   += (int)($ex_item['exams_registered'] ?? 0);
    $total_taken += (int)($ex_item['exams_taken'] ?? 0);
}

include('header.php');
?>

<style>
    /* Examinees Page Styling */
    .page-title-wrap {
        margin-bottom: 22px;
        position: relative;
    }
    .page-title {
        font-size: 28px;
        font-weight: 800;
        margin: 0 0 6px 0;
        letter-spacing: -0.4px;
        color: var(--text-primary, #0f172a);
    }
    [data-theme="dark"] .page-title {
        background: linear-gradient(90deg, #00f2fe 0%, #4facfe 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: inline-block;
    }
    .page-subtitle {
        font-size: 14px;
        color: var(--text-muted, #64748b);
        margin: 0;
    }

    /* Top Summary Badges */
    .summary-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 24px;
    }
    .summary-pill {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 10px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        transition: transform 0.2s ease;
    }
    .summary-pill:hover {
        transform: translateY(-2px);
    }
    [data-theme="dark"] .summary-pill {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .summary-pill-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
    }
    .summary-pill-info {
        display: flex;
        flex-direction: column;
    }
    .summary-pill-num {
        font-size: 19px;
        font-weight: 800;
        line-height: 1.1;
        color: var(--text-primary, #0f172a);
    }
    .summary-pill-label {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    /* Filter Toolbar */
    .filter-toolbar {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    }
    [data-theme="dark"] .filter-toolbar {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .filter-search-box {
        position: relative;
        flex: 1 1 300px;
        max-width: 440px;
    }
    .filter-search-box i.fa-search {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted, #94a3b8);
        pointer-events: none;
        font-size: 14px;
    }
    .filter-search-input {
        width: 100%;
        height: 40px;
        padding: 0 36px 0 38px;
        border: 1px solid var(--input-border, #cbd5e1);
        border-radius: 20px;
        background: var(--input-bg, #ffffff);
        color: var(--input-text, #0f172a);
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        font-size: 13.5px;
    }
    .filter-search-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }
    [data-theme="dark"] .filter-search-input {
        background: #111c35;
        border-color: rgba(255,255,255,0.12);
        color: #f8fafc;
    }
    .filter-clear-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--text-muted, #94a3b8);
        display: none;
        font-size: 14px;
    }
    .filter-clear-btn:hover {
        color: var(--text-primary, #0f172a);
    }
    .filter-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .filter-select {
        height: 40px;
        padding: 0 16px;
        border: 1px solid var(--input-border, #cbd5e1);
        border-radius: 20px;
        background: var(--input-bg, #ffffff);
        color: var(--input-text, #0f172a);
        outline: none;
        min-width: 160px;
        font-size: 13px;
        font-weight: 500;
        transition: border-color .2s;
    }
    [data-theme="dark"] .filter-select {
        background: #111c35;
        border-color: rgba(255,255,255,0.12);
        color: #f8fafc;
    }
    .filter-select:focus {
        border-color: #6366f1;
    }
    .filter-counter {
        color: var(--text-muted, #64748b);
        font-size: 13px;
        font-weight: 600;
    }

    /* Examinee Table */
    .examinees-card {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        overflow: hidden;
        margin-bottom: 30px;
    }
    [data-theme="dark"] .examinees-card {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .table-examinees {
        margin-bottom: 0;
        width: 100%;
    }
    .table-examinees > thead > tr > th {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 13px 16px;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        background: var(--table-header-bg, #f8fafc);
        color: var(--text-muted, #64748b);
        vertical-align: middle;
    }
    [data-theme="dark"] .table-examinees > thead > tr > th {
        background: #111c35;
        border-color: rgba(255,255,255,0.08);
        color: #94a3b8;
    }
    .table-examinees > tbody > tr > td {
        padding: 13px 16px;
        vertical-align: middle;
        font-size: 13px;
        border-top: 1px solid var(--border-color, #f1f5f9);
        color: var(--text-primary, #1e293b);
    }
    [data-theme="dark"] .table-examinees > tbody > tr > td {
        border-color: rgba(255,255,255,0.05);
        color: #cbd5e1;
    }
    .table-examinees > tbody > tr:hover {
        background: rgba(99, 102, 241, 0.04);
    }
    [data-theme="dark"] .table-examinees > tbody > tr:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    /* Candidate Profile Link Cell */
    .examinee-user-cell {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none !important;
        color: var(--text-primary, #0f172a);
    }
    .examinee-avatar-img {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 1.5px solid #cbd5e1;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    [data-theme="dark"] .examinee-avatar-img {
        border-color: rgba(255,255,255,0.2);
    }
    .examinee-user-cell:hover .examinee-avatar-img {
        transform: scale(1.08);
    }
    .examinee-user-name {
        font-weight: 700;
        color: #4f46e5;
        font-size: 13.5px;
        transition: color 0.2s;
    }
    [data-theme="dark"] .examinee-user-name {
        color: #818cf8;
    }
    .examinee-user-cell:hover .examinee-user-name {
        text-decoration: underline;
        color: #4338ca;
    }
    [data-theme="dark"] .examinee-user-cell:hover .examinee-user-name {
        color: #a5b4fc;
    }

    /* Action Buttons */
    .btn-action-view {
        background: rgba(99, 102, 241, 0.12);
        color: #4f46e5 !important;
        font-weight: 600;
        font-size: 11.5px;
        padding: 5px 12px;
        border-radius: 6px;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid rgba(99, 102, 241, 0.25);
        transition: all 0.2s ease;
    }
    .btn-action-view:hover {
        background: #4f46e5;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(79, 70, 229, 0.3);
    }
    [data-theme="dark"] .btn-action-view {
        background: rgba(99, 102, 241, 0.2);
        color: #a5b4fc !important;
        border-color: rgba(99, 102, 241, 0.4);
    }
    [data-theme="dark"] .btn-action-view:hover {
        background: #6366f1;
        color: #ffffff !important;
    }

    .btn-status-toggle {
        font-size: 11.5px;
        font-weight: 600;
        padding: 5px 11px;
        border-radius: 6px;
        border: 1px solid;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
        background: transparent;
        cursor: pointer;
    }
    .btn-status-toggle.btn-disable {
        border-color: rgba(239, 68, 68, 0.35);
        color: #dc2626;
        background: rgba(239, 68, 68, 0.08);
    }
    .btn-status-toggle.btn-disable:hover {
        background: #dc2626;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(220, 38, 38, 0.3);
    }
    [data-theme="dark"] .btn-status-toggle.btn-disable {
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.45);
        background: rgba(239, 68, 68, 0.15);
    }
    [data-theme="dark"] .btn-status-toggle.btn-disable:hover {
        background: #ef4444;
        color: #ffffff;
    }

    .btn-status-toggle.btn-enable {
        border-color: rgba(16, 185, 129, 0.35);
        color: #059669;
        background: rgba(16, 185, 129, 0.08);
    }
    .btn-status-toggle.btn-enable:hover {
        background: #059669;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(16, 185, 129, 0.3);
    }
    [data-theme="dark"] .btn-status-toggle.btn-enable {
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.45);
        background: rgba(16, 185, 129, 0.15);
    }
    [data-theme="dark"] .btn-status-toggle.btn-enable:hover {
        background: #10b981;
        color: #ffffff;
    }

    .stat-badge-reg {
        background: rgba(99, 102, 241, 0.12);
        color: #4f46e5;
        font-weight: 700;
        font-size: 11.5px;
        padding: 3px 8px;
        border-radius: 12px;
        display: inline-block;
    }
    [data-theme="dark"] .stat-badge-reg {
        background: rgba(99, 102, 241, 0.25);
        color: #a5b4fc;
    }
    .stat-badge-taken {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        font-weight: 700;
        font-size: 11.5px;
        padding: 3px 8px;
        border-radius: 12px;
        display: inline-block;
    }
    [data-theme="dark"] .stat-badge-taken {
        background: rgba(16, 185, 129, 0.25);
        color: #6ee7b7;
    }
</style>

<div class="row">
    <div class="col-md-12">
        
        <!-- Header Banner -->
        <div class="page-title-wrap">
            <h1 class="page-title"><i class="fa fa-users" style="margin-right:8px;"></i> Registered Examinees</h1>
            <p class="page-subtitle">View candidate profiles, exam histories, participation stats, and manage account statuses.</p>
        </div>

        <?php if (!empty($alert_msg)): ?>
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible" role="alert" style="border-radius:10px;">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <i class="fa <?= $alert_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <strong><?= e($alert_msg); ?></strong>
            </div>
        <?php endif; ?>

        <!-- Quick Summary Pills -->
        <div class="summary-strip">
            <div class="summary-pill">
                <div class="summary-pill-icon" style="background:rgba(99, 102, 241, 0.15); color:#6366f1;">
                    <i class="fa fa-users"></i>
                </div>
                <div class="summary-pill-info">
                    <span class="summary-pill-num"><?= $total_count; ?></span>
                    <span class="summary-pill-label">Total Examinees</span>
                </div>
            </div>

            <div class="summary-pill">
                <div class="summary-pill-icon" style="background:rgba(16, 185, 129, 0.15); color:#10b981;">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="summary-pill-info">
                    <span class="summary-pill-num"><?= $active_count; ?></span>
                    <span class="summary-pill-label">Active</span>
                </div>
            </div>

            <div class="summary-pill">
                <div class="summary-pill-icon" style="background:rgba(239, 68, 68, 0.15); color:#ef4444;">
                    <i class="fa fa-ban"></i>
                </div>
                <div class="summary-pill-info">
                    <span class="summary-pill-num"><?= $disabled_count; ?></span>
                    <span class="summary-pill-label">Disabled</span>
                </div>
            </div>

            <div class="summary-pill">
                <div class="summary-pill-icon" style="background:rgba(245, 158, 11, 0.15); color:#f59e0b;">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div class="summary-pill-info">
                    <span class="summary-pill-num"><?= $total_reg; ?></span>
                    <span class="summary-pill-label">Total Registrations</span>
                </div>
            </div>

            <div class="summary-pill">
                <div class="summary-pill-icon" style="background:rgba(6, 182, 212, 0.15); color:#06b6d4;">
                    <i class="fa fa-check-square-o"></i>
                </div>
                <div class="summary-pill-info">
                    <span class="summary-pill-num"><?= $total_taken; ?></span>
                    <span class="summary-pill-label">Exams Completed</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="filter-toolbar">
            <div class="filter-search-box">
                <i class="fa fa-search"></i>
                <input type="text" id="examineeSearchInput" class="filter-search-input" placeholder="Search by name, email, or phone..." autocomplete="off">
                <i class="fa fa-times-circle filter-clear-btn" id="examineeSearchClear" title="Clear Search"></i>
            </div>

            <div class="filter-controls">
                <label style="margin:0; font-size:12.5px; font-weight:600; color:var(--text-muted);">Status:</label>
                <select id="statusFilterSelect" class="filter-select">
                    <option value="">All Statuses (<?= $total_count; ?>)</option>
                    <option value="active">Active (<?= $active_count; ?>)</option>
                    <option value="disabled">Disabled (<?= $disabled_count; ?>)</option>
                </select>

                <span class="filter-counter" id="filterResultCount">Showing <?= $total_count; ?> examinees</span>
            </div>
        </div>

        <!-- Main Examinees Table Card -->
        <div class="examinees-card">
            <div class="table-responsive">
                <table class="table table-examinees" id="examineesTable">
                    <thead>
                        <tr>
                            <th style="width:50px; text-align:center;">#</th>
                            <th>Examinee</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:center;">Registered</th>
                            <th style="text-align:center;">Taken</th>
                            <th>Joined Date</th>
                            <th style="text-align:center; min-width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="examineesTableBody">
                        <?php if (!empty($examinees)): ?>
                            <?php 
                            $row_idx = 1; 
                            foreach ($examinees as $ex): 
                                $uid = (int)$ex['id'];
                                $raw_status = strtolower(trim((string)$ex['status']));
                                $is_active = ($raw_status === 'active');

                                // Profile photo resolution with fallback
                                $avatar_url = 'assets/img/find_user.png';
                                if (!empty($ex['profile_photo'])) {
                                    $pname = $ex['profile_photo'];
                                    if (is_file(__DIR__ . '/../uploads/profile/' . $pname)) {
                                        $avatar_url = '../uploads/profile/' . rawurlencode($pname);
                                    } elseif (is_file(__DIR__ . '/../examinee/uploads/profile/' . $pname)) {
                                        $avatar_url = '../examinee/uploads/profile/' . rawurlencode($pname);
                                    }
                                }
                            ?>
                                <tr class="examinee-row" 
                                    data-status="<?= $raw_status; ?>" 
                                    data-name="<?= strtolower(htmlspecialchars((string)$ex['name'], ENT_QUOTES, 'UTF-8')); ?>" 
                                    data-email="<?= strtolower(htmlspecialchars((string)$ex['email'], ENT_QUOTES, 'UTF-8')); ?>"
                                    data-phone="<?= strtolower(htmlspecialchars((string)($ex['phone'] ?? ''), ENT_QUOTES, 'UTF-8')); ?>">
                                    
                                    <!-- # -->
                                    <td style="text-align:center; font-weight:600; color:var(--text-muted);">
                                        <?= $row_idx++; ?>
                                    </td>

                                    <!-- Photo + Name (link to info page) -->
                                    <td>
                                        <a href="examinee_view.php?id=<?= $uid; ?>" class="examinee-user-cell" title="View examinee details">
                                            <img src="<?= $avatar_url; ?>" alt="<?= htmlspecialchars((string)$ex['name'], ENT_QUOTES, 'UTF-8'); ?>" class="examinee-avatar-img" onerror="this.src='assets/img/find_user.png'">
                                            <div>
                                                <div class="examinee-user-name"><?= htmlspecialchars((string)$ex['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <small style="color:var(--text-muted); font-size:11px;">ID #<?= $uid; ?></small>
                                            </div>
                                        </a>
                                    </td>

                                    <!-- Email -->
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars((string)$ex['email'], ENT_QUOTES, 'UTF-8'); ?>" style="color:inherit; text-decoration:none;">
                                            <i class="fa fa-envelope-o" style="color:var(--text-muted); margin-right:4px;"></i>
                                            <?= htmlspecialchars((string)$ex['email'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>

                                    <!-- Phone -->
                                    <td>
                                        <?php if (!empty($ex['phone'])): ?>
                                            <span><i class="fa fa-phone" style="color:var(--text-muted); margin-right:4px;"></i><?= htmlspecialchars((string)$ex['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status Badge -->
                                    <td style="text-align:center;">
                                        <?php if ($is_active): ?>
                                            <span class="label label-success" style="font-size:11.5px; font-weight:600; padding:4px 9px;">
                                                <i class="fa fa-check-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="label label-danger" style="font-size:11.5px; font-weight:600; padding:4px 9px;">
                                                <i class="fa fa-ban"></i> Disabled
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Exams Registered -->
                                    <td style="text-align:center;">
                                        <span class="stat-badge-reg"><?= (int)$ex['exams_registered']; ?></span>
                                    </td>

                                    <!-- Exams Taken -->
                                    <td style="text-align:center;">
                                        <span class="stat-badge-taken"><?= (int)$ex['exams_taken']; ?></span>
                                    </td>

                                    <!-- Joined Date -->
                                    <td style="font-size:12px; color:var(--text-muted);">
                                        <?php 
                                            e(!empty($ex['created_at']) ? date('d M Y, h:i A', strtotime($ex['created_at'])) : '-'); 
                                        ?>
                                    </td>

                                    <!-- Action Buttons -->
                                    <td style="text-align:center; white-space:nowrap;">
                                        <!-- View Info Link -->
                                        <a href="examinee_view.php?id=<?= $uid; ?>" class="btn-action-view" title="View Full Profile & Exams">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="emptyTableRow">
                                <td colspan="9" style="text-align:center; padding:45px 20px; color:var(--text-muted);">
                                    <i class="fa fa-users" style="font-size:36px; display:block; margin-bottom:12px; opacity:0.4;"></i>
                                    <strong style="font-size:15px; color:var(--text-primary);">No registered examinees found.</strong>
                                    <p style="margin:5px 0 0 0; font-size:13px;">Candidates who register on the portal will appear here automatically.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Zero Match Message for dynamic search/filter -->
            <div id="noMatchMessage" style="display:none; text-align:center; padding:45px 20px; color:var(--text-muted);">
                <i class="fa fa-search" style="font-size:32px; display:block; margin-bottom:10px; opacity:0.4;"></i>
                <strong style="font-size:15px; color:var(--text-primary);">No matching examinees found.</strong>
                <p style="margin:4px 0 0 0; font-size:13px;">Try clearing your search query or changing the status filter.</p>
            </div>
        </div>

    </div>
</div>

<script>
    // Real-time client side search & status filtering
    (function() {
        var searchInput = document.getElementById('examineeSearchInput');
        var clearBtn    = document.getElementById('examineeSearchClear');
        var statusSelect= document.getElementById('statusFilterSelect');
        var counterText = document.getElementById('filterResultCount');
        var rows        = document.querySelectorAll('.examinee-row');
        var noMatchMsg  = document.getElementById('noMatchMessage');
        var tableBody   = document.getElementById('examineesTableBody');

        function filterExaminees() {
            var query = (searchInput.value || '').trim().toLowerCase();
            var statusVal = (statusSelect.value || '').toLowerCase();
            var visibleCount = 0;

            if (query.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }

            rows.forEach(function(row) {
                var rowName   = row.getAttribute('data-name') || '';
                var rowEmail  = row.getAttribute('data-email') || '';
                var rowPhone  = row.getAttribute('data-phone') || '';
                var rowStatus = row.getAttribute('data-status') || '';

                var matchesQuery = (query === '' || rowName.indexOf(query) !== -1 || rowEmail.indexOf(query) !== -1 || rowPhone.indexOf(query) !== -1);
                var matchesStatus = (statusVal === '' || rowStatus === statusVal);

                if (matchesQuery && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (counterText) {
                counterText.textContent = 'Showing ' + visibleCount + ' of <?= $total_count; ?> examinees';
            }

            if (noMatchMsg) {
                if (visibleCount === 0 && rows.length > 0) {
                    noMatchMsg.style.display = 'block';
                } else {
                    noMatchMsg.style.display = 'none';
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterExaminees);
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterExaminees();
                searchInput.focus();
            });
        }

        if (statusSelect) {
            statusSelect.addEventListener('change', filterExaminees);
        }
    })();
</script>

<?php
include('footer.php');
?>