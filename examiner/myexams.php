<?php
require('../config/autoload.php');

// 🔒 Examiner Auth Check
if (!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
}

$examiner_id = (int)$_SESSION['examiner_id'];
$dao = new DataAccess();

// ==========================================
// POST ACTION HANDLERS (State-Changing)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action  = trim($_POST['action']);
    $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

    // Verify exam exists and belongs to logged-in examiner
    $exam_check = $dao->getData('*', 'exams', "id = $exam_id AND created_by = $examiner_id");
    if (empty($exam_check)) {
        echo "<script>alert('Error: Exam not found or permission denied.'); window.location.href='myexams.php';</script>";
        exit();
    }
    $target_exam = $exam_check[0];
    $category    = strtolower(trim($target_exam['category'] ?? ''));

    // ------------------------------------------
    // ACTION: SUBMIT / RESUBMIT
    // ------------------------------------------
    if ($action === 'submit' || $action === 'resubmit') {
        // Enforce rule: exam must have at least 1 question
        $q_cnt_res = $dao->query("SELECT COUNT(*) AS cnt FROM questions WHERE exam_id = $exam_id");
        $q_count   = (!empty($q_cnt_res) && isset($q_cnt_res[0]['cnt'])) ? (int)$q_cnt_res[0]['cnt'] : 0;

        if ($q_count < 1) {
            echo "<script>alert('Cannot submit exam: You must add at least 1 question to this exam before submitting.'); window.location.href='myexams.php';</script>";
            exit();
        }

        if ($category === 'certification') {
            // Certification: status becomes 'pending', submitted_at = NOW()
            $update_data = array(
                'status'           => 'pending',
                'submitted_at'     => date('Y-m-d H:i:s'),
                'rejection_reason' => null
            );
            $ok = $dao->update($update_data, 'exams', "id = $exam_id AND created_by = $examiner_id");
            if ($ok) {
                echo "<script>alert('Certification exam submitted for admin approval!'); window.location.href='myexams.php';</script>";
            } else {
                echo "<script>alert('Failed to submit exam. Please try again.'); window.location.href='myexams.php';</script>";
            }
            exit();
        } elseif ($category === 'mock') {
            // Mock: status becomes 'approved' (published immediately)
            $update_data = array(
                'status'       => 'approved',
                'submitted_at' => date('Y-m-d H:i:s')
            );
            $ok = $dao->update($update_data, 'exams', "id = $exam_id AND created_by = $examiner_id");
            if ($ok) {
                echo "<script>alert('Mock exam published successfully! It is now live to students.'); window.location.href='myexams.php';</script>";
            } else {
                echo "<script>alert('Failed to publish mock exam. Please try again.'); window.location.href='myexams.php';</script>";
            }
            exit();
        }
    }

    // ------------------------------------------
    // ACTION: DELETE
    // ------------------------------------------
    if ($action === 'delete') {
        if ($category === 'mock') {
            $current_status = strtolower(trim($target_exam['status'] ?? ''));
            if ($current_status !== 'draft') {
                echo "<script>alert('Published mock exams cannot be deleted while live.'); window.location.href='myexams.php';</script>";
                exit();
            }

            // MOCK DELETE RULE:
            // A mock exam that already has rows in exam_results or student_answers must NOT be deletable
            $res_check = $dao->query("SELECT (SELECT COUNT(*) FROM exam_results WHERE exam_id = $exam_id) + (SELECT COUNT(*) FROM student_answers WHERE exam_id = $exam_id) AS total_attempts");
            $attempts  = (!empty($res_check) && isset($res_check[0]['total_attempts'])) ? (int)$res_check[0]['total_attempts'] : 0;

            if ($attempts > 0) {
                echo "<script>alert('Students have already attempted this exam. It cannot be deleted.'); window.location.href='myexams.php';</script>";
                exit();
            }
        } elseif ($category === 'certification') {
            // CERTIFICATION DELETE RULE:
            // Only deletable in 'draft' or 'rejected' status, and only if it has no registrations
            $current_status = strtolower(trim($target_exam['status'] ?? ''));
            if (!in_array($current_status, array('draft', 'rejected'))) {
                echo "<script>alert('Certification exams cannot be deleted while awaiting review or approved.'); window.location.href='myexams.php';</script>";
                exit();
            }

            $reg_check = $dao->query("SELECT COUNT(*) AS cnt FROM registrations WHERE exam_id = $exam_id");
            $reg_count = (!empty($reg_check) && isset($reg_check[0]['cnt'])) ? (int)$reg_check[0]['cnt'] : 0;

            if ($reg_count > 0) {
                echo "<script>alert('This exam has student registrations and cannot be deleted.'); window.location.href='myexams.php';</script>";
                exit();
            }

            // Delete uploaded exam image from ../uploads/exams/
            if (!empty($target_exam['image'])) {
                $img_path = __DIR__ . '/../uploads/exams/' . $target_exam['image'];
                if (is_file($img_path)) {
                    @unlink($img_path);
                }
            }
        }

        // Transactional deletion of questions and exam
        $dao->query("START TRANSACTION");
        $del_q = $dao->query("DELETE FROM questions WHERE exam_id = $exam_id");
        $del_e = $dao->query("DELETE FROM exams WHERE id = $exam_id AND created_by = $examiner_id");

        if ($del_e) {
            $dao->query("COMMIT");
            echo "<script>alert('Exam deleted successfully.'); window.location.href='myexams.php';</script>";
            exit();
        } else {
            $dao->query("ROLLBACK");
            echo "<script>alert('Failed to delete exam. Operation rolled back.'); window.location.href='myexams.php';</script>";
            exit();
        }
    }
}

// ==========================================
// FETCH EXAMS BY EXAMINER
// ==========================================
$cert_exams = $dao->query(
    "SELECT e.*, c.course_name,
            (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count
     FROM exams e
     LEFT JOIN courses c ON c.id = e.course_id
     WHERE e.created_by = $examiner_id AND LOWER(TRIM(e.category)) = 'certification'
     ORDER BY e.id DESC"
);
if (!is_array($cert_exams)) { $cert_exams = array(); }

$mock_exams = $dao->query(
    "SELECT e.*, c.course_name,
            (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count
     FROM exams e
     LEFT JOIN courses c ON c.id = e.course_id
     WHERE e.created_by = $examiner_id AND LOWER(TRIM(e.category)) = 'mock'
     ORDER BY e.id DESC"
);
if (!is_array($mock_exams)) { $mock_exams = array(); }

include("header.php");
?>

<style>
    .editor-panel {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--table-border-color, #e5e7eb);
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        margin-bottom: 30px;
        overflow: hidden;
    }
    .editor-panel-heading {
        background: var(--table-header-bg, #f1f5f9);
        color: var(--text-primary, #111827);
        padding: 16px 22px;
        font-weight: 700;
        font-size: 17px;
        border-bottom: 1px solid var(--table-border-color, #e5e7eb);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .table-custom {
        margin-bottom: 0;
        width: 100%;
    }
    .table-custom > thead > tr > th {
        background: var(--table-th-bg, #f1f5f9);
        color: var(--table-th-text, #334155);
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--table-border-color, #e5e7eb);
        padding: 12px 14px;
        vertical-align: middle;
    }
    .table-custom > tbody > tr > td {
        color: var(--table-td-text, #1e293b);
        border-top: 1px solid var(--table-border-color, #e5e7eb);
        padding: 13px 14px;
        vertical-align: middle;
        font-size: 13.5px;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
        letter-spacing: 0.3px;
    }
    .status-draft    { background: #64748b; color: #ffffff; }
    .status-pending  { background: #f59e0b; color: #ffffff; }
    .status-approved { background: #10b981; color: #ffffff; }
    .status-rejected { background: #ef4444; color: #ffffff; }
    .actions-cell {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .rejection-box {
        margin-top: 6px;
        padding: 6px 10px;
        background: rgba(239, 68, 68, 0.1);
        border-left: 3px solid #ef4444;
        border-radius: 4px;
        color: var(--text-primary, #111827);
        font-size: 12px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:15px;">
            <div>
                <h2 style="margin:0 0 6px 0; font-weight:800; color:var(--text-primary);">
                    <i class="fa fa-folder-open" style="color:#0284c7; margin-right:8px;"></i> My Exams
                </h2>
                <p class="text-muted" style="margin:0;">Manage, review, publish, and track all your certification and mock exams.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="addcertexam.php" class="btn btn-primary" style="font-weight:600;">
                    <i class="fa fa-plus-circle"></i> New Certification Exam
                </a>
                <a href="addmockexam.php" class="btn btn-info" style="font-weight:600;">
                    <i class="fa fa-plus-circle"></i> New Mock Exam
                </a>
            </div>
        </div>
        <hr style="margin-top:10px; margin-bottom:25px;">
    </div>
</div>

<!-- ======================================================== -->
<!-- SECTION A: CERTIFICATION EXAMS -->
<!-- ======================================================== -->
<div class="row">
    <div class="col-md-12">
        <div class="editor-panel">
            <div class="editor-panel-heading">
                <div>
                    <i class="fa fa-graduation-cap" style="color:#6366f1; margin-right:8px;"></i>
                    Certification Exams (<?= count($cert_exams); ?>)
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th style="width:45px;">#</th>
                            <th>Title</th>
                            <th>Course</th>
                            <th style="width:90px; text-align:center;">Questions</th>
                            <th style="width:95px;">Total Marks</th>
                            <th>Start Date</th>
                            <th style="width:85px;">Fee</th>
                            <th style="width:110px;">Status</th>
                            <th style="min-width:240px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cert_exams)) { 
                            $i = 1;
                            foreach ($cert_exams as $ce) {
                                $st = strtolower(trim($ce['status'] ?? 'draft'));
                                $badge_cls = 'status-' . $st;
                                if (!in_array($st, array('draft', 'pending', 'approved', 'rejected'))) {
                                    $badge_cls = 'status-draft';
                                }
                        ?>
                            <tr>
                                <td><strong><?= $i++; ?></strong></td>
                                <td>
                                    <strong><?php e($ce['title']); ?></strong>
                                    <?php if (!empty($ce['rejection_reason']) && $st === 'rejected') { ?>
                                        <div class="rejection-box">
                                            <strong><i class="fa fa-exclamation-triangle"></i> Rejection Reason:</strong>
                                            <?php e($ce['rejection_reason']); ?>
                                            <?php if (!empty($ce['reviewed_at'])) { ?>
                                                <span class="text-muted" style="margin-left:6px;">(<?= date('d M Y, h:i A', strtotime($ce['reviewed_at'])); ?>)</span>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                </td>
                                <td><?php e($ce['course_name'] ?? 'N/A'); ?></td>
                                <td style="text-align:center;">
                                    <span class="badge" style="background:#0284c7; font-weight:700;">
                                        <?= (int)$ce['question_count']; ?>
                                    </span>
                                </td>
                                <td><strong><?= number_format((float)($ce['total_marks'] ?? 0), 2); ?></strong></td>
                                <td>
                                    <?= !empty($ce['exam_start_datetime']) ? date('d M Y, h:i A', strtotime($ce['exam_start_datetime'])) : '-'; ?>
                                </td>
                                <td>
                                    <?= ((float)($ce['price'] ?? 0) > 0) ? '&#8377;' . number_format((float)$ce['price'], 2) : '<span class="text-muted">Free</span>'; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $badge_cls; ?>">
                                        <?= ucfirst($st); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <!-- View is always available -->
                                        <a href="viewexam.php?exam_id=<?= (int)$ce['id']; ?>" class="btn btn-default btn-xs" title="View Exam Details">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <?php if ($st === 'draft') { ?>
                                            <!-- Draft Actions: View, Edit, Submit, Delete -->
                                            <a href="editexam.php?exam_id=<?= (int)$ce['id']; ?>" class="btn btn-primary btn-xs" title="Edit Exam">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>

                                            <form method="POST" action="myexams.php" style="display:inline;" onsubmit="return confirm('Submit this Certification Exam for admin approval? Once submitted, it will be locked until approved or rejected.');">
                                                <input type="hidden" name="action" value="submit">
                                                <input type="hidden" name="exam_id" value="<?= (int)$ce['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-xs" title="Submit to Admin">
                                                    <i class="fa fa-send"></i> Submit
                                                </button>
                                            </form>

                                            <form method="POST" action="myexams.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this draft exam and all its questions? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="exam_id" value="<?= (int)$ce['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-xs" title="Delete Exam">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>

                                        <?php } elseif ($st === 'pending') { ?>
                                            <!-- Pending: View Only + Awaiting note -->
                                            <span class="text-muted" style="font-size:12px; font-weight:600; padding:3px 6px;">
                                                <i class="fa fa-clock-o text-warning"></i> Awaiting admin approval
                                            </span>

                                        <?php } elseif ($st === 'approved') { ?>
                                            <!-- Approved: View Only + Live note -->
                                            <span class="text-success" style="font-size:12px; font-weight:700; padding:3px 6px;">
                                                <i class="fa fa-check-circle"></i> Live
                                            </span>

                                        <?php } elseif ($st === 'rejected') { ?>
                                            <!-- Rejected: View, Edit, Resubmit, Delete -->
                                            <a href="editexam.php?exam_id=<?= (int)$ce['id']; ?>" class="btn btn-primary btn-xs" title="Edit Exam to fix issues">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>

                                            <form method="POST" action="myexams.php" style="display:inline;" onsubmit="return confirm('Resubmit this corrected Certification Exam for admin approval?');">
                                                <input type="hidden" name="action" value="resubmit">
                                                <input type="hidden" name="exam_id" value="<?= (int)$ce['id']; ?>">
                                                <button type="submit" class="btn btn-warning btn-xs" title="Resubmit for Review">
                                                    <i class="fa fa-refresh"></i> Resubmit
                                                </button>
                                            </form>

                                            <form method="POST" action="myexams.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this rejected exam?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="exam_id" value="<?= (int)$ce['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-xs" title="Delete Exam">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } } else { ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted" style="padding: 24px;">
                                    No certification exams found. Click "New Certification Exam" to create one.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- SECTION B: MOCK EXAMS -->
<!-- ======================================================== -->
<div class="row">
    <div class="col-md-12">
        <div class="editor-panel">
            <div class="editor-panel-heading">
                <div>
                    <i class="fa fa-desktop" style="color:#0284c7; margin-right:8px;"></i>
                    Mock Exams (<?= count($mock_exams); ?>)
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th style="width:45px;">#</th>
                            <th>Title</th>
                            <th>Course</th>
                            <th style="width:90px; text-align:center;">Questions</th>
                            <th style="width:95px;">Total Marks</th>
                            <th style="width:110px;">Duration</th>
                            <th style="width:110px;">Status</th>
                            <th style="min-width:240px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mock_exams)) { 
                            $j = 1;
                            foreach ($mock_exams as $me) {
                                $mst = strtolower(trim($me['status'] ?? 'draft'));
                                $is_published = ($mst === 'approved');
                        ?>
                            <tr>
                                <td><strong><?= $j++; ?></strong></td>
                                <td><strong><?php e($me['title']); ?></strong></td>
                                <td><?php e($me['course_name'] ?? 'N/A'); ?></td>
                                <td style="text-align:center;">
                                    <span class="badge" style="background:#0284c7; font-weight:700;">
                                        <?= (int)$me['question_count']; ?>
                                    </span>
                                </td>
                                <td><strong><?= number_format((float)($me['total_marks'] ?? 0), 2); ?></strong></td>
                                <td><?= (int)$me['duration_minutes']; ?> mins</td>
                                <td>
                                    <?php if ($is_published) { ?>
                                        <span class="status-badge status-approved">Published</span>
                                    <?php } else { ?>
                                        <span class="status-badge status-draft">Draft</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <!-- View always available -->
                                        <a href="viewexam.php?exam_id=<?= (int)$me['id']; ?>" class="btn btn-default btn-xs" title="View Exam Details">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <?php if ($is_published) { ?>
                                            <!-- Live indicator when published (Edit & Delete hidden) -->
                                            <span class="text-success" style="font-size:12px; font-weight:700; padding:3px 6px;">
                                                <i class="fa fa-check-circle"></i> Live
                                            </span>
                                        <?php } else { ?>
                                            <!-- Edit only available in Draft -->
                                            <a href="editexam.php?exam_id=<?= (int)$me['id']; ?>" class="btn btn-primary btn-xs" title="Edit Mock Exam">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>

                                            <!-- Publish Mock Exam -->
                                            <form method="POST" action="myexams.php" style="display:inline;" onsubmit="return confirm('Publish this Mock Exam now? It will become live to all students immediately.');">
                                                <input type="hidden" name="action" value="submit">
                                                <input type="hidden" name="exam_id" value="<?= (int)$me['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-xs" title="Publish Mock Exam">
                                                    <i class="fa fa-check-circle"></i> Publish
                                                </button>
                                            </form>

                                            <!-- Delete Mock Exam -->
                                            <form method="POST" action="myexams.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this mock exam and all its questions?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="exam_id" value="<?= (int)$me['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-xs" title="Delete Exam">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } } else { ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted" style="padding: 24px;">
                                    No mock exams found. Click "New Mock Exam" to create one.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
