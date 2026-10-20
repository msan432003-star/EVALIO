<?php
require('../config/autoload.php');

// 🔒 Examiner Auth Check
if (!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
}

$examiner_id = (int)$_SESSION['examiner_id'];
$exam_id     = isset($_REQUEST['exam_id']) ? (int)$_REQUEST['exam_id'] : 0;
$dao         = new DataAccess();

if ($exam_id <= 0) {
    echo "<script>alert('Invalid Exam ID.'); window.location.href='myexams.php';</script>";
    exit();
}

// 1. Fetch Exam Details (Ownership Check)
$exam_res = $dao->getData('*', 'exams', "id = $exam_id AND created_by = $examiner_id");
if (empty($exam_res)) {
    echo "<script>alert('Exam not found or access denied.'); window.location.href='myexams.php';</script>";
    exit();
}
$exam = $exam_res[0];

$category  = strtolower(trim($exam['category'] ?? ''));
$is_mock   = ($category === 'mock');
$status    = strtolower(trim($exam['status'] ?? 'draft'));
$is_locked = in_array($status, array('pending', 'approved'));

// 2. Lock Check: Refuse editing if exam is published / locked
if ($is_locked) {
    echo "<script>alert('This exam has already been published/submitted and is locked. It cannot be edited.'); window.location.href='myexams.php';</script>";
    exit();
}

// 3. Category & Course Restrictions
date_default_timezone_set('Asia/Kolkata');
$my_cat = 0;
$cat_row = $dao->getData(array('category_id'), 'examiners', 'id = ' . $examiner_id);
if (!empty($cat_row) && !empty($cat_row[0]['category_id'])) {
    $my_cat = (int)$cat_row[0]['category_id'];
}
$course_cond = ($my_cat > 0)
    ? 'id IN (SELECT course_id FROM course_categories WHERE category_id = ' . $my_cat . ')'
    : '1=0';

$allowed_courses = $dao->getData('*', 'courses', $course_cond);
if (!is_array($allowed_courses)) {
    $allowed_courses = array();
}

// 4. Handle Form Submission
if (isset($_POST['btn_update_exam'])) {
    $title     = trim($_POST['title'] ?? '');
    $course_id = (int)($_POST['course_id'] ?? 0);
    $duration  = (int)($_POST['duration_minutes'] ?? 30);
    if ($duration < 1) { $duration = 30; }

    // Verify course permission
    $course_chk = $dao->getData(array('id'), 'courses', "id = $course_id AND ($course_cond OR id = " . (int)$exam['course_id'] . ")");
    if (empty($course_chk)) {
        echo "<script>alert('Error: You can only select courses in your assigned category.');</script>";
    } elseif (strlen($title) < 3) {
        echo "<script>alert('Error: Exam title must be at least 3 characters long.');</script>";
    } else {
        if ($is_mock) {
            // Update Mock Exam
            $update_data = array(
                'title'            => $title,
                'course_id'        => $course_id,
                'duration_minutes' => $duration
            );

            $ok = $dao->update($update_data, 'exams', "id = $exam_id AND created_by = $examiner_id");
            if ($ok) {
                echo "<script>alert('Mock exam updated successfully!'); window.location.href='viewexam.php?exam_id=$exam_id';</script>";
                exit();
            } else {
                echo "<script>alert('No changes were made or update failed.');</script>";
            }
        } else {
            // Update Certification Exam
            $description = trim($_POST['description'] ?? '');
            $price       = (float)($_POST['price'] ?? 0.00);
            $reg_dl      = trim($_POST['registration_deadline'] ?? '');
            $start_dt    = trim($_POST['exam_start_datetime'] ?? '');

            $dates_valid = true;
            $dl_time = strtotime($reg_dl);
            $st_time = strtotime($start_dt);

            if ($dl_time === false || $st_time === false) {
                $dates_valid = false;
                echo "<script>alert('Please enter valid dates for registration deadline and exam start time.');</script>";
            } elseif ($st_time <= $dl_time) {
                $dates_valid = false;
                echo "<script>alert('The exam start time must be strictly after the registration deadline.');</script>";
            }

            if ($dates_valid) {
                $image_filename = $exam['image'] ?? '';
                $image_error    = '';

                // Handle Image Replacement
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_ext = array('jpg', 'jpeg', 'png', 'webp');
                    $max_size    = 2 * 1024 * 1024; // 2MB

                    $tmp_name  = $_FILES['image']['tmp_name'];
                    $orig_name = $_FILES['image']['name'];
                    $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowed_ext)) {
                        $image_error = 'Invalid image type. Only JPG, PNG, and WEBP are allowed.';
                    } elseif ($_FILES['image']['size'] > $max_size) {
                        $image_error = 'Image is too large. Maximum size is 2MB.';
                    } else {
                        $upload_dir = '../uploads/exams/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $new_image = 'exam_' . time() . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($tmp_name, $upload_dir . $new_image)) {
                            // Delete old image if it exists
                            if (!empty($image_filename) && file_exists($upload_dir . $image_filename)) {
                                @unlink($upload_dir . $image_filename);
                            }
                            $image_filename = $new_image;
                        } else {
                            $image_error = 'Failed to upload new image.';
                        }
                    }
                }

                if ($image_error !== '') {
                    echo "<script>alert('" . addslashes($image_error) . "');</script>";
                } else {
                    $update_data = array(
                        'title'                 => $title,
                        'description'           => $description,
                        'course_id'             => $course_id,
                        'duration_minutes'      => $duration,
                        'price'                 => $price,
                        'registration_deadline' => $reg_dl,
                        'exam_start_datetime'   => $start_dt,
                        'image'                 => $image_filename
                    );

                    $ok = $dao->update($update_data, 'exams', "id = $exam_id AND created_by = $examiner_id");
                    if ($ok) {
                        echo "<script>alert('Certification exam details updated successfully!'); window.location.href='viewexam.php?exam_id=$exam_id';</script>";
                        exit();
                    } else {
                        echo "<script>alert('No changes were made or update failed.');</script>";
                    }
                }
            }
        }
    }

    // Refresh exam record after potential update attempt
    $refreshed = $dao->getData('*', 'exams', "id = $exam_id AND created_by = $examiner_id");
    if (!empty($refreshed)) {
        $exam = $refreshed[0];
    }
}

include("header.php");
?>

<style>
    .editor-panel {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--table-border-color, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.06);
        margin-bottom: 30px;
        overflow: hidden;
    }
    .editor-panel-heading {
        background: var(--table-header-bg, #f8fafc);
        border-bottom: 1px solid var(--table-border-color, #e2e8f0);
        color: var(--text-primary, #1e293b);
        font-weight: 700;
        font-size: 16px;
        padding: 16px 22px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .editor-panel-body {
        padding: 24px;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: capitalize;
    }
    .status-draft    { background: #64748b; color: #ffffff; }
    .status-pending  { background: #f59e0b; color: #ffffff; }
    .status-approved { background: #10b981; color: #ffffff; }
    .status-rejected { background: #ef4444; color: #ffffff; }
</style>

<div class="row">
    <div class="col-md-12">
        <!-- Top Nav & Actions Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
            <div>
                <a href="myexams.php" class="btn btn-default btn-sm" style="font-weight:600; margin-bottom:8px;">
                    <i class="fa fa-arrow-left"></i> Back to My Exams
                </a>
                <h2 style="margin:4px 0 6px 0; font-weight:800; color:var(--text-primary);">
                    Edit Exam: <?php e($exam['title']); ?>
                </h2>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span class="label label-info" style="font-size:12px; padding:4px 8px;">
                        <?= e($exam['category']); ?> Exam
                    </span>
                    <span class="status-badge status-<?= $status; ?>">
                        Status: <?= ucfirst($status); ?>
                    </span>
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="viewexam.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-default" style="font-weight:600;">
                    <i class="fa fa-eye"></i> View Exam Details
                </a>
                <a href="addquestion.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-info" style="font-weight:600;">
                    <i class="fa fa-list"></i> Manage Questions
                </a>
            </div>
        </div>
        <hr style="margin-top:10px; margin-bottom:24px; border-color:var(--hr-color);">
    </div>
</div>

<?php if ($status === 'rejected' && !empty($exam['rejection_reason'])) { ?>
    <div class="alert alert-danger" style="border-radius:10px; padding:16px 20px; margin-bottom:24px; border-left:5px solid #ef4444;">
        <h4 style="margin-top:0; font-weight:700;"><i class="fa fa-exclamation-circle"></i> Needs Revision: Admin Feedback</h4>
        <p style="margin:0; font-size:14px;"><?php e($exam['rejection_reason']); ?></p>
        <p style="margin-top:8px; margin-bottom:0; font-size:12.5px; color:var(--text-muted);">
            Please correct the fields below and review the questions. After saving, you can resubmit the exam for review from <strong>My Exams</strong>.
        </p>
    </div>
<?php } ?>

<div class="editor-panel">
    <div class="editor-panel-heading">
        <div>
            <i class="fa fa-pencil-square-o text-primary"></i> 
            Edit <?= $is_mock ? 'Mock Exam' : 'Certification Exam'; ?> Configuration
        </div>
        <div>
            <span class="text-muted" style="font-size:12px;">Exam ID #<?= (int)$exam['id']; ?></span>
        </div>
    </div>
    <div class="editor-panel-body">
        <form action="editexam.php?exam_id=<?= (int)$exam['id']; ?>" method="POST" enctype="multipart/form-data">
            <div class="row">
                <!-- Exam Title -->
                <div class="col-md-8 col-sm-12 form-group">
                    <label style="color:var(--text-primary); font-weight:600;">Exam Title: <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars((string)$exam['title'], ENT_QUOTES, 'UTF-8'); ?>" required minlength="3" maxlength="150" style="font-weight:600;">
                </div>

                <!-- Course Dropdown -->
                <div class="col-md-4 col-sm-12 form-group">
                    <label style="color:var(--text-primary); font-weight:600;">Course / Subject: <span class="text-danger">*</span></label>
                    <select name="course_id" class="form-control" required style="font-weight:500;">
                        <option value="">-- Select Course --</option>
                        <?php foreach ($allowed_courses as $c) { 
                            $sel = ((int)$exam['course_id'] === (int)$c['id']) ? 'selected' : '';
                        ?>
                            <option value="<?= (int)$c['id']; ?>" <?= $sel; ?>>
                                <?= htmlspecialchars((string)$c['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <?php if (!$is_mock) { ?>
                <!-- Description for Certification Exam -->
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label style="color:var(--text-primary); font-weight:600;">Exam Description: <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" required placeholder="Describe topics covered, instructions, and prerequisites..."><?= htmlspecialchars((string)($exam['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>

                <!-- Scheduling & Fee -->
                <div class="row">
                    <div class="col-md-4 col-sm-12 form-group">
                        <label style="color:var(--text-primary); font-weight:600;">Registration Deadline: <span class="text-danger">*</span></label>
                        <?php 
                        $dl_val = !empty($exam['registration_deadline']) ? date('Y-m-d\TH:i', strtotime($exam['registration_deadline'])) : '';
                        ?>
                        <input type="datetime-local" name="registration_deadline" class="form-control" value="<?= $dl_val; ?>" required>
                        <p class="text-muted" style="font-size:11.5px; margin-top:4px; margin-bottom:0;">Date and time when registrations close.</p>
                    </div>

                    <div class="col-md-4 col-sm-12 form-group">
                        <label style="color:var(--text-primary); font-weight:600;">Exam Start Date &amp; Time: <span class="text-danger">*</span></label>
                        <?php 
                        $st_val = !empty($exam['exam_start_datetime']) ? date('Y-m-d\TH:i', strtotime($exam['exam_start_datetime'])) : '';
                        ?>
                        <input type="datetime-local" name="exam_start_datetime" class="form-control" value="<?= $st_val; ?>" required>
                        <p class="text-muted" style="font-size:11.5px; margin-top:4px; margin-bottom:0;">Must be strictly after the registration deadline.</p>
                    </div>

                    <div class="col-md-2 col-sm-6 form-group">
                        <label style="color:var(--text-primary); font-weight:600;">Duration (Mins): <span class="text-danger">*</span></label>
                        <input type="number" name="duration_minutes" class="form-control" value="<?= (int)($exam['duration_minutes'] ?? 30); ?>" min="5" max="360" required>
                    </div>

                    <div class="col-md-2 col-sm-6 form-group">
                        <label style="color:var(--text-primary); font-weight:600;">Exam Fee (&#8377;): <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" value="<?= number_format((float)($exam['price'] ?? 0), 2, '.', ''); ?>" min="0" step="0.5" required>
                    </div>
                </div>

                <!-- Cover Image Upload & Current Preview -->
                <div class="row" style="margin-top:10px;">
                    <div class="col-md-12 form-group">
                        <label style="color:var(--text-primary); font-weight:600;">Exam Cover Image:</label>
                        <?php if (!empty($exam['image']) && file_exists('../uploads/exams/' . $exam['image'])) { ?>
                            <div style="display:flex; align-items:center; gap:16px; margin-bottom:12px; padding:10px 14px; background:var(--input-bg); border:1px solid var(--table-border-color); border-radius:8px;">
                                <img src="../uploads/exams/<?= htmlspecialchars($exam['image']); ?>" alt="Current Banner" style="height:65px; border-radius:6px; border:1px solid var(--table-border-color);">
                                <div>
                                    <span style="font-size:13px; font-weight:600; color:var(--text-primary);">Current image file: <?= htmlspecialchars($exam['image']); ?></span>
                                    <p class="text-muted" style="margin:2px 0 0 0; font-size:12px;">To replace, choose a new file below (JPG, PNG, WEBP, max 2MB).</p>
                                </div>
                            </div>
                        <?php } ?>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>

            <?php } else { ?>
                <!-- Mock Exam Specifics -->
                <div class="row">
                    <div class="col-md-6 col-sm-12 form-group">
                        <label style="color:var(--text-primary); font-weight:600;">Duration (Minutes): <span class="text-danger">*</span></label>
                        <input type="number" name="duration_minutes" class="form-control" value="<?= (int)($exam['duration_minutes'] ?? 30); ?>" min="1" max="360" required>
                        <p class="text-muted" style="font-size:11.5px; margin-top:4px;">Students will receive a countdown timer set to this duration.</p>
                    </div>
                    <div class="col-md-6 col-sm-12 form-group">
                        <label style="color:var(--text-primary); font-weight:600;">Pricing &amp; Access:</label>
                        <div class="form-control" style="background:var(--input-bg); color:var(--text-muted); font-weight:600;">
                            <i class="fa fa-check-circle text-success"></i> Free Practice Exam (Always accessible to examinees)
                        </div>
                    </div>
                </div>
            <?php } ?>

            <!-- Submit and Cancel Buttons -->
            <div style="margin-top:25px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <button type="submit" name="btn_update_exam" class="btn btn-primary btn-lg" style="padding:10px 24px; font-weight:700;">
                    <i class="fa fa-save"></i> Save Changes
                </button>
                <a href="viewexam.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-default btn-lg" style="padding:10px 20px;">
                    Cancel
                </a>
                <a href="addquestion.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-info btn-lg" style="padding:10px 20px; font-weight:600;">
                    <i class="fa fa-plus-circle"></i> Manage Questions
                </a>
            </div>
        </form>
    </div>
</div>

<?php include('footer.php'); ?>
