<?php
require('../config/autoload.php');

// 🔒 Examiner Auth Check
if (!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
}

$examiner_id = (int)$_SESSION['examiner_id'];
$exam_id     = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$dao         = new DataAccess();

if ($exam_id <= 0) {
    echo "<script>alert('Invalid Exam ID.'); window.location.href='myexams.php';</script>";
    exit();
}

// 1. Fetch Exam Details (Strict Ownership Check)
$exam_res = $dao->getData('*', 'exams', "id = $exam_id AND created_by = $examiner_id");
if (empty($exam_res)) {
    echo "<script>alert('Exam not found or access denied.'); window.location.href='myexams.php';</script>";
    exit();
}
$exam = $exam_res[0];

// 2. Fetch Course Information
$course_name = 'Unassigned';
if (!empty($exam['course_id'])) {
    $c_res = $dao->getData('course_name', 'courses', 'id = ' . (int)$exam['course_id']);
    if (!empty($c_res)) {
        $course_name = $c_res[0]['course_name'];
    }
}

// 3. Status & Lock Detection
$category  = strtolower(trim($exam['category'] ?? ''));
$is_mock   = ($category === 'mock');
$status    = strtolower(trim($exam['status'] ?? 'draft'));
$is_locked = in_array($status, array('pending', 'approved'));

// 4. Fetch Questions
$questions = $dao->getData('*', 'questions', "exam_id = $exam_id ORDER BY id ASC");
if (!is_array($questions)) {
    $questions = array();
}

$total_marks_calc = 0.00;
foreach ($questions as $q) {
    $total_marks_calc += (float)($q['marks'] ?? 0);
}

include("header.php");
?>

<style>
    .editor-panel {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--table-border-color, #e2e8f0);
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.06);
        margin-bottom: 25px;
        overflow: hidden;
    }
    .editor-panel-heading {
        background: var(--table-header-bg, #f8fafc);
        border-bottom: 1px solid var(--table-border-color, #e2e8f0);
        color: var(--text-primary, #1e293b);
        font-weight: 700;
        font-size: 15px;
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .editor-panel-body {
        padding: 22px;
    }
    .meta-box {
        background: var(--input-bg, #f8fafc);
        border: 1px solid var(--table-border-color, #e2e8f0);
        border-radius: 10px;
        padding: 14px 18px;
        height: 100%;
    }
    .meta-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted, #64748b);
        font-weight: 600;
        margin-bottom: 4px;
    }
    .meta-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary, #1e293b);
    }
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
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

    .q-card {
        background: var(--input-bg, #f8fafc);
        border: 1px solid var(--table-border-color, #e2e8f0);
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 18px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .q-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .q-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
        gap: 12px;
    }
    .q-num {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary, #1e293b);
    }
    .q-type-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
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

    .opt-box {
        padding: 9px 14px;
        margin-bottom: 8px;
        border-radius: 8px;
        border: 1px solid var(--table-border-color, #e2e8f0);
        background: var(--card-bg, #ffffff);
        color: var(--text-primary, #1e293b);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .opt-box.is-correct {
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.08);
        font-weight: 600;
    }
    .opt-letter {
        font-weight: 700;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(148, 163, 184, 0.2);
        font-size: 12px;
    }
    .opt-box.is-correct .opt-letter {
        background: #10b981;
        color: #ffffff;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <!-- Top Action & Navigation Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
            <div>
                <a href="myexams.php" class="btn btn-default btn-sm" style="font-weight:600; margin-bottom:8px;">
                    <i class="fa fa-arrow-left"></i> Back to My Exams
                </a>
                <h2 style="margin:4px 0 6px 0; font-weight:800; color:var(--text-primary);">
                    <?php e($exam['title']); ?>
                </h2>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span class="label label-info" style="font-size:12px; padding:4px 8px;">
                        <?= e($exam['category']); ?> Exam
                    </span>
                    <span class="status-badge status-<?= $status; ?>">
                        <?= ucfirst($status); ?>
                    </span>
                    <?php if ($is_locked) { ?>
                        <span class="label label-warning" style="font-size:12px; padding:4px 8px;">
                            <i class="fa fa-lock"></i> Locked
                        </span>
                    <?php } ?>
                </div>
            </div>

            <!-- Contextual Action Buttons -->
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php if (!$is_locked) { ?>
                    <a href="editexam.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-primary" style="font-weight:600;">
                        <i class="fa fa-pencil"></i> Edit Exam Info
                    </a>
                    <a href="addquestion.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-info" style="font-weight:600;">
                        <i class="fa fa-plus-circle"></i> Manage Questions
                    </a>
                <?php } else { ?>
                    <button type="button" class="btn btn-default" disabled title="Locked exams cannot be edited">
                        <i class="fa fa-lock"></i> Locked
                    </button>
                    <a href="addquestion.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-default" style="font-weight:600;">
                        <i class="fa fa-list"></i> View Questions Editor
                    </a>
                <?php } ?>
            </div>
        </div>
        <hr style="margin-top:10px; margin-bottom:24px; border-color:var(--hr-color);">
    </div>
</div>

<!-- Rejection Notice Banner -->
<?php if ($status === 'rejected' && !empty($exam['rejection_reason'])) { ?>
    <div class="alert alert-danger" style="border-radius:10px; font-size:14px; padding:16px 20px; margin-bottom:25px; border-left:5px solid #ef4444;">
        <h4 style="margin-top:0; font-weight:700;"><i class="fa fa-exclamation-triangle"></i> Rejection Reason from Admin</h4>
        <p style="margin:0; font-size:14px;"><?php e($exam['rejection_reason']); ?></p>
        <div style="margin-top:10px;">
            <a href="editexam.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-danger btn-sm" style="font-weight:600;">
                <i class="fa fa-pencil"></i> Edit Exam to Resolve Issues
            </a>
        </div>
    </div>
<?php } ?>

<!-- Exam Information Overview -->
<div class="editor-panel">
    <div class="editor-panel-heading">
        <div><i class="fa fa-info-circle text-primary"></i> Exam Overview</div>
        <div>
            <span class="text-muted" style="font-size:12px;">ID: #<?= (int)$exam['id']; ?></span>
        </div>
    </div>
    <div class="editor-panel-body">
        <div class="row" style="margin-bottom:18px;">
            <div class="col-md-3 col-sm-6" style="margin-bottom:14px;">
                <div class="meta-box">
                    <div class="meta-label">Course / Subject</div>
                    <div class="meta-value"><?php e($course_name); ?></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom:14px;">
                <div class="meta-box">
                    <div class="meta-label">Questions &amp; Marks</div>
                    <div class="meta-value"><?= count($questions); ?> Qs &bull; <?= number_format($total_marks_calc, 1); ?> pts</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom:14px;">
                <div class="meta-box">
                    <div class="meta-label">Duration</div>
                    <div class="meta-value"><?= (int)$exam['duration_minutes']; ?> Minutes</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom:14px;">
                <div class="meta-box">
                    <div class="meta-label">Fee / Price</div>
                    <div class="meta-value">
                        <?= ((float)($exam['price'] ?? 0) > 0) ? '&#8377;' . number_format((float)$exam['price'], 2) : '<span class="text-success">Free</span>'; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$is_mock) { ?>
            <!-- Certification-specific scheduling -->
            <div class="row" style="margin-bottom:18px;">
                <div class="col-md-4 col-sm-6" style="margin-bottom:14px;">
                    <div class="meta-box">
                        <div class="meta-label">Registration Deadline</div>
                        <div class="meta-value" style="font-size:14px;">
                            <?= !empty($exam['registration_deadline']) ? date('d M Y, h:i A', strtotime($exam['registration_deadline'])) : '-'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom:14px;">
                    <div class="meta-box">
                        <div class="meta-label">Exam Start Date &amp; Time</div>
                        <div class="meta-value" style="font-size:14px;">
                            <?= !empty($exam['exam_start_datetime']) ? date('d M Y, h:i A', strtotime($exam['exam_start_datetime'])) : '-'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12" style="margin-bottom:14px;">
                    <div class="meta-box">
                        <div class="meta-label">Format / Question Mode</div>
                        <div class="meta-value" style="font-size:14px;">
                            <?= strtoupper(str_replace('_', ' ', $exam['exam_type'] ?? 'hybrid')); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Description -->
        <?php if (!empty($exam['description'])) { ?>
            <div style="background:var(--input-bg, #f8fafc); border:1px solid var(--table-border-color, #e2e8f0); border-radius:10px; padding:14px 18px; margin-bottom:10px;">
                <div class="meta-label">Exam Description</div>
                <div style="color:var(--text-primary); line-height:1.6; margin-top:6px; white-space:pre-line;"><?php e($exam['description']); ?></div>
            </div>
        <?php } ?>

        <!-- Exam Cover Image (if present) -->
        <?php if (!empty($exam['image']) && file_exists('../uploads/exams/' . $exam['image'])) { ?>
            <div style="margin-top:14px;">
                <div class="meta-label" style="margin-bottom:8px;">Cover Image</div>
                <img src="../uploads/exams/<?= htmlspecialchars($exam['image']); ?>" alt="Exam Banner" style="max-height:160px; border-radius:8px; border:1px solid var(--table-border-color);">
            </div>
        <?php } ?>
    </div>
</div>

<!-- ======================================================== -->
<!-- QUESTIONS LIST -->
<!-- ======================================================== -->
<div class="editor-panel">
    <div class="editor-panel-heading">
        <div>
            <i class="fa fa-list text-primary"></i> Questions Included (<?= count($questions); ?>)
        </div>
        <div>
            <span class="badge" style="background:#0284c7; padding:5px 10px; font-size:12px;">
                Total: <?= number_format($total_marks_calc, 1); ?> Marks
            </span>
        </div>
    </div>
    <div class="editor-panel-body">
        <?php if (empty($questions)) { ?>
            <div class="text-center" style="padding:40px 20px; color:var(--text-muted);">
                <i class="fa fa-folder-open-o" style="font-size:42px; margin-bottom:12px;"></i>
                <h4>No questions have been added yet</h4>
                <p>This exam cannot be published or submitted to the admin until at least 1 question is created.</p>
                <?php if (!$is_locked) { ?>
                    <a href="addquestion.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-primary" style="margin-top:10px; font-weight:600;">
                        <i class="fa fa-plus-circle"></i> Add Questions Now
                    </a>
                <?php } ?>
            </div>
        <?php } else { 
            $qnum = 1;
            foreach ($questions as $q) {
                $qt = !empty($q['question_type']) ? $q['question_type'] : 'mcq';
        ?>
            <div class="q-card">
                <div class="q-header">
                    <div style="display:flex; align-items:baseline; gap:10px; flex-wrap:wrap;">
                        <span class="q-num">Q<?= $qnum++; ?>.</span>
                        <span class="q-type-badge badge-<?= e($qt); ?>">
                            <?= strtoupper(str_replace('_', ' ', $qt)); ?>
                        </span>
                        <span class="badge" style="background:#059669; font-size:11px; padding:3px 8px;">
                            <?= number_format((float)($q['marks'] ?? 1), 1); ?> Mark<?= (float)($q['marks'] ?? 1) != 1 ? 's' : ''; ?>
                        </span>
                    </div>
                    <?php if (!$is_locked) { ?>
                        <a href="addquestion.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-default btn-xs" title="Edit in Question Manager">
                            <i class="fa fa-pencil"></i> Edit
                        </a>
                    <?php } ?>
                </div>

                <!-- Question Prompt -->
                <div style="font-size:15px; font-weight:600; color:var(--text-primary); margin-bottom:14px; line-height:1.5;">
                    <?php e($q['question_text']); ?>
                </div>

                <!-- Question Details based on Type -->
                <?php if ($qt === 'mcq') { 
                    $corr = strtoupper(trim((string)($q['correct_option'] ?? '')));
                    $options = array(
                        'A' => $q['option_a'] ?? '',
                        'B' => $q['option_b'] ?? '',
                        'C' => $q['option_c'] ?? '',
                        'D' => $q['option_d'] ?? ''
                    );
                ?>
                    <div class="row">
                        <?php foreach ($options as $opt_key => $opt_val) { 
                            $is_c = ($corr === $opt_key);
                        ?>
                            <div class="col-md-6 col-sm-12">
                                <div class="opt-box <?= $is_c ? 'is-correct' : ''; ?>">
                                    <span class="opt-letter"><?= $opt_key; ?></span>
                                    <span style="flex:1;"><?php e($opt_val); ?></span>
                                    <?php if ($is_c) { ?>
                                        <i class="fa fa-check-circle text-success" style="font-size:16px;" title="Correct Answer"></i>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                <?php } elseif ($qt === 'true_false') { 
                    $ans = trim((string)($q['correct_answer'] ?? $q['correct_option'] ?? 'True'));
                    $is_t = (strcasecmp($ans, 'True') === 0);
                ?>
                    <div style="display:flex; gap:14px;">
                        <span class="label <?= $is_t ? 'label-success' : 'label-default'; ?>" style="font-size:14px; padding:6px 14px;">
                            <i class="fa <?= $is_t ? 'fa-check-circle' : 'fa-circle-o'; ?>"></i> True <?= $is_t ? ' (Correct Answer)' : ''; ?>
                        </span>
                        <span class="label <?= !$is_t ? 'label-success' : 'label-default'; ?>" style="font-size:14px; padding:6px 14px;">
                            <i class="fa <?= !$is_t ? 'fa-check-circle' : 'fa-circle-o'; ?>"></i> False <?= !$is_t ? ' (Correct Answer)' : ''; ?>
                        </span>
                    </div>

                <?php } elseif ($qt === 'essay') { ?>
                    <div class="alert alert-info" style="margin-bottom:0; font-size:13px; padding:10px 14px;">
                        <i class="fa fa-user-circle"></i> <strong>Descriptive / Essay Question</strong><br>
                        Students write open-ended text answers. Responses are saved as pending manual examiner grading.
                    </div>

                <?php } elseif ($qt === 'short_answer' || $qt === 'fill_blank') { ?>
                    <div style="background:var(--card-bg); padding:10px 14px; border-radius:8px; border:1px solid var(--table-border-color); display:inline-block;">
                        <span class="text-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; margin-right:8px;">
                            <?= ($qt === 'fill_blank') ? 'Missing Word(s):' : 'Expected Answer(s):'; ?>
                        </span>
                        <code style="font-size:14px;"><?php e($q['correct_answer'] ?? ''); ?></code>
                    </div>

                <?php } elseif ($qt === 'matching') { 
                    $pairs = Grader::parseMatchingPairs($q['options_data'] ?? '', $q['correct_answer'] ?? '');
                ?>
                    <?php if (!empty($pairs)) { ?>
                        <div class="table-responsive" style="max-width:600px; margin-bottom:0;">
                            <table class="table table-bordered table-condensed" style="margin-bottom:0; background:var(--card-bg);">
                                <thead>
                                    <tr style="background:var(--table-header-bg);">
                                        <th style="width:50%;">Column A (Item)</th>
                                        <th style="width:50%;">Column B (Matching Target)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pairs as $p) { ?>
                                        <tr>
                                            <td><strong><?php e($p['left'] ?? ''); ?></strong></td>
                                            <td><span class="text-success"><i class="fa fa-arrow-right"></i> <?php e($p['right'] ?? ''); ?></span></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <span class="text-muted">No matching pairs configured.</span>
                    <?php } ?>
                <?php } ?>
            </div>
        <?php } } ?>
    </div>
</div>

<?php include('footer.php'); ?>
