<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = (int)($_SESSION['admin_id'] ?? 0);

// 1. Handle Approval & Rejection Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id     = (int)$_POST['id'];
    $action = trim($_POST['action']);

    if ($id > 0 && $action === 'approve') {
        $now = date('Y-m-d H:i:s');
        $ok = $dao->update(
            array('status' => 'approved', 'reviewed_at' => $now),
            'exams',
            "id = $id AND status = 'pending'"
        );
        if ($ok) {
            logAction($admin_id, 'approve_exam', 'exams', $id);
            echo "<script>alert('Exam approved successfully! It is now active for candidates.'); window.location.href='pending_exams.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to approve exam or exam is no longer pending.'); window.location.href='pending_exams.php';</script>";
            exit();
        }
    } elseif ($id > 0 && $action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        if ($reason === '') {
            $reason = 'Revision requested by administrator.';
        }
        $now = date('Y-m-d H:i:s');
        $ok = $dao->update(
            array('status' => 'rejected', 'rejection_reason' => $reason, 'reviewed_at' => $now),
            'exams',
            "id = $id AND status = 'pending'"
        );
        if ($ok) {
            logAction($admin_id, 'reject_exam', 'exams', $id, $reason);
            echo "<script>alert('Exam rejected. Examiner has been notified with the reason.'); window.location.href='pending_exams.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to reject exam or exam is no longer pending.'); window.location.href='pending_exams.php';</script>";
            exit();
        }
    }
}

// Fallback GET support for direct approve links if any
if (isset($_GET['action'], $_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = trim($_GET['action']);
    if ($id > 0 && $action === 'approve') {
        $now = date('Y-m-d H:i:s');
        $ok = $dao->update(
            array('status' => 'approved', 'reviewed_at' => $now),
            'exams',
            "id = $id AND status = 'pending'"
        );
        if ($ok) {
            logAction($admin_id, 'approve_exam', 'exams', $id);
            echo "<script>alert('Exam approved successfully.'); window.location.href='pending_exams.php';</script>";
            exit();
        }
    }
}

// 2. Fetch all Pending Certification Exams
$pendingExams = $dao->getData(
    '*',
    'exams',
    "category = 'Certification' AND status = 'pending' ORDER BY submitted_at DESC, id DESC"
);
if (!is_array($pendingExams)) {
    $pendingExams = array();
}

// 3. Build Course Name Lookup
$courseNames = array();
$courses = $dao->getData(array('id', 'course_name'), 'courses', '1=1');
if (is_array($courses)) {
    foreach ($courses as $c) {
        $courseNames[$c['id']] = $c['course_name'];
    }
}

// 4. Build Examiner Lookup
$examinerDetails = array();
$examiners = $dao->getData(array('id', 'name', 'email'), 'examiners', '1=1');
if (is_array($examiners)) {
    foreach ($examiners as $ex) {
        $examinerDetails[$ex['id']] = $ex;
    }
}

// 5. Pre-calculate Question Counts
$questionCounts = array();
$allQuestions = $dao->query("SELECT exam_id, COUNT(*) AS cnt FROM questions GROUP BY exam_id");
if (is_array($allQuestions)) {
    foreach ($allQuestions as $qc) {
        $questionCounts[$qc['exam_id']] = (int)$qc['cnt'];
    }
}

include('header.php');
?>

<style>
    .pending-header-banner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }
    .panel-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--table-border, var(--border-color, #e5e7eb));
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }
    .panel-card-heading {
        background: var(--panel-default-head-bg, var(--bg-sidebar, #0f172a));
        color: var(--panel-default-head-text, #ffffff);
        padding: 14px 20px;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--table-border, #e5e7eb);
    }
    .table-pending {
        margin-bottom: 0;
        width: 100%;
        background-color: var(--table-bg, transparent);
    }
    .table-pending > thead > tr > th {
        background: var(--table-th-bg, #f8fafc);
        color: var(--table-th-text, #0f172a);
        font-weight: 700;
        font-size: 12.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--table-border, #e5e7eb);
        padding: 12px 14px;
        vertical-align: middle;
    }
    .table-pending > tbody > tr > td {
        color: var(--table-td-text, var(--text-primary, #1e293b));
        border-top: 1px solid var(--table-border, #e5e7eb);
        padding: 13px 14px;
        vertical-align: middle;
        font-size: 13.5px;
    }
    .badge-pending {
        background: #f59e0b;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 12px;
    }
    .action-btn-group {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }
    .modal-content {
        background: var(--card-bg, #ffffff);
        color: var(--text-primary, #1e293b);
        border: 1px solid var(--table-border, #e2e8f0);
        border-radius: 12px;
    }
    .modal-header {
        border-bottom: 1px solid var(--table-border, #e2e8f0);
        background: var(--table-th-bg, #f8fafc);
        color: var(--table-th-text, var(--text-primary, #0f172a));
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    .modal-footer {
        border-top: 1px solid var(--table-border, #e2e8f0);
        background: var(--table-th-bg, #f8fafc);
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }
</style>

<div class="container-fluid" style="padding: 20px 25px;">
    <!-- Header Banner -->
    <div class="pending-header-banner">
        <div>
            <h2 style="margin: 0 0 6px 0; font-weight: 800; color: var(--text-primary);">
                <i class="fa fa-check-square-o" style="color: #6366f1; margin-right: 8px;"></i>
                Pending Certification Exams
            </h2>
            <p class="text-muted" style="margin: 0; font-size: 13.5px;">
                Review submitted certification exams, inspect questions, and approve or reject with feedback.
            </p>
        </div>
        <div>
            <span class="badge" style="background: #6366f1; font-size: 14px; padding: 7px 14px; border-radius: 20px;">
                <i class="fa fa-clock-o"></i> <?= count($pendingExams); ?> Awaiting Review
            </span>
        </div>
    </div>

    <!-- Main Table Panel -->
    <div class="panel-card">
        <div class="panel-card-heading">
            <div>
                <i class="fa fa-list" style="margin-right: 6px;"></i>
                Certification Exams Pending Approval (<?= count($pendingExams); ?>)
            </div>
            <div>
                <a href="approved_examiners.php" class="btn btn-xs btn-default" style="font-weight: 600;">
                    <i class="fa fa-users"></i> View Examiners
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-pending table-hover">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Exam Title</th>
                        <th>Course</th>
                        <th>Examiner</th>
                        <th style="width: 80px; text-align: center;">Questions</th>
                        <th style="width: 90px;">Total Marks</th>
                        <th>Schedule</th>
                        <th>Fee</th>
                        <th>Submitted</th>
                        <th style="min-width: 200px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingExams)) { ?>
                        <tr>
                            <td colspan="10" class="text-center" style="padding: 45px 20px;">
                                <div style="color: #10b981; font-size: 42px; margin-bottom: 12px;">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                                <h4 style="font-weight: 700; margin: 0 0 6px 0; color: var(--text-primary);">All Clear!</h4>
                                <p class="text-muted" style="margin: 0;">There are currently no certification exams waiting for admin review.</p>
                            </td>
                        </tr>
                    <?php } else { 
                        $i = 1;
                        foreach ($pendingExams as $exam) {
                            $eid      = (int)$exam['id'];
                            $cid      = (int)($exam['course_id'] ?? 0);
                            $cname    = $courseNames[$cid] ?? 'Unassigned Course';
                            $ex_id    = (int)($exam['created_by'] ?? 0);
                            $ex_name  = $examinerDetails[$ex_id]['name'] ?? 'Examiner #' . $ex_id;
                            $ex_email = $examinerDetails[$ex_id]['email'] ?? '';
                            $q_count  = $questionCounts[$eid] ?? 0;
                            $total_m  = (float)($exam['total_marks'] ?? 0);
                    ?>
                        <tr>
                            <td><strong><?= $i++; ?></strong></td>
                            <td>
                                <strong style="color: var(--text-primary);"><?php e($exam['title']); ?></strong>
                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                    ID: #<?= $eid; ?> &bull; Duration: <?= (int)$exam['duration_minutes']; ?> mins
                                </div>
                            </td>
                            <td><span class="label label-default" style="font-size: 12px;"><?php e($cname); ?></span></td>
                            <td>
                                <div><strong><?php e($ex_name); ?></strong></div>
                                <?php if ($ex_email !== '') { ?>
                                    <small class="text-muted"><?php e($ex_email); ?></small>
                                <?php } ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge" style="background: <?= $q_count > 0 ? '#0284c7' : '#ef4444'; ?>; font-size: 12px; padding: 4px 8px;">
                                    <?= $q_count; ?>
                                </span>
                            </td>
                            <td><strong><?= number_format($total_m, 1); ?></strong></td>
                            <td style="font-size: 12.5px;">
                                <div><strong class="text-primary">Starts:</strong> <?= !empty($exam['exam_start_datetime']) ? date('d M Y, h:i A', strtotime($exam['exam_start_datetime'])) : '-'; ?></div>
                                <div class="text-muted"><small>Deadline: <?= !empty($exam['registration_deadline']) ? date('d M Y, h:i A', strtotime($exam['registration_deadline'])) : '-'; ?></small></div>
                            </td>
                            <td>
                                <?= ((float)($exam['price'] ?? 0) > 0) ? '&#8377;' . number_format((float)$exam['price'], 2) : '<span class="text-muted">Free</span>'; ?>
                            </td>
                            <td style="font-size: 12px; color: var(--text-muted);">
                                <?= !empty($exam['submitted_at']) ? date('d M Y, h:i A', strtotime($exam['submitted_at'])) : date('d M Y', strtotime($exam['created_at'])); ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-btn-group" style="justify-content: center;">
                                    <!-- View Questions -->
                                    <a href="view_exam_questions.php?exam_id=<?= $eid; ?>" class="btn btn-info btn-xs" title="Review All Questions and Answers">
                                        <i class="fa fa-eye"></i> View Questions
                                    </a>

                                    <!-- Approve Form -->
                                    <form method="POST" action="pending_exams.php" style="display:inline;" onsubmit="return confirm('Approve this exam? It will immediately become visible and open for student registration.');">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= $eid; ?>">
                                        <button type="submit" class="btn btn-success btn-xs" title="Approve Exam">
                                            <i class="fa fa-check"></i> Approve
                                        </button>
                                    </form>

                                    <!-- Reject Button (Opens Modal) -->
                                    <button type="button" class="btn btn-danger btn-xs btn-open-reject" 
                                        data-id="<?= $eid; ?>" 
                                        data-title="<?= htmlspecialchars((string)$exam['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                        title="Reject with Feedback">
                                        <i class="fa fa-times"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- BOOTSTRAP REJECTION MODAL -->
<!-- ======================================================== -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="pending_exams.php" id="rejectForm">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" id="modal_reject_id" value="0">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: var(--text-primary);"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="rejectModalLabel" style="font-weight: 700; color: #ef4444;">
                        <i class="fa fa-exclamation-triangle"></i> Reject Certification Exam
                    </h4>
                </div>

                <div class="modal-body">
                    <p style="font-size: 14px;">
                        You are rejecting exam: <strong id="modal_reject_title" style="color: var(--text-primary);"></strong>
                    </p>
                    <div class="alert alert-warning" style="font-size: 13px; margin-bottom: 15px; border-radius: 8px;">
                        <i class="fa fa-info-circle"></i> This reason will be shown to the examiner on their dashboard and exam page so they can address issues and resubmit.
                    </div>

                    <div class="form-group">
                        <label style="color: var(--text-primary); font-weight: 600;">Rejection Reason &amp; Required Changes: <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="modal_rejection_reason" class="form-control" rows="4" required placeholder="Describe what needs to be changed (e.g. incorrect answer key on Q3, duration too short, ambiguous questions, etc.)..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="font-weight: 700;">
                        <i class="fa fa-times-circle"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    var rejectButtons = document.querySelectorAll('.btn-open-reject');
    var modalIdInput = document.getElementById('modal_reject_id');
    var modalTitleSpan = document.getElementById('modal_reject_title');
    var modalReason = document.getElementById('modal_rejection_reason');

    rejectButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var eid = this.getAttribute('data-id');
            var title = this.getAttribute('data-title');

            if (modalIdInput) modalIdInput.value = eid;
            if (modalTitleSpan) modalTitleSpan.textContent = title + ' (ID: #' + eid + ')';
            if (modalReason) modalReason.value = '';

            if (window.jQuery && typeof $('#rejectModal').modal === 'function') {
                $('#rejectModal').modal('show');
            } else {
                var modal = document.getElementById('rejectModal');
                if (modal) {
                    modal.classList.add('in');
                    modal.style.display = 'block';
                }
            }
        });
    });
});
</script>

<?php include('footer.php'); ?>
