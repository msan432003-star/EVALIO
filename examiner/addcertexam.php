<?php 
require('../config/autoload.php');

// Only logged-in examiners can access this page
if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
} 
include("header.php"); 

$dao = new DataAccess(); 

// Category rule: an examiner can only use courses of their own category.
// An examiner whose category is not set yet cannot create exams until the admin sets it.
date_default_timezone_set('Asia/Kolkata');
$min_dt = date('Y-m-d\TH:i'); // earliest date/time that can be picked in the calendar
$my_cat = 0;
$cat_row = $dao->getData(array('category_id'), 'examiners', 'id = ' . (int)$_SESSION['examiner_id']);
if (!empty($cat_row) && !empty($cat_row[0]['category_id'])) { $my_cat = (int)$cat_row[0]['category_id']; }
// A subject can belong to several categories (course_categories link table)
$course_cond = ($my_cat > 0)
    ? 'id IN (SELECT course_id FROM course_categories WHERE category_id = ' . $my_cat . ')'
    : '1=0';

$elements = array(
    "title" => "", 
    "description" => "",
    "course_id" => "", 
    "registration_deadline" => "",
    "exam_start_datetime" => "",
    "duration" => "", 
    "price" => ""
); 

$form = new FormAssist($elements, $_POST); 

$labels = array(
    'title' => "Certification Exam Title", 
    'description' => "Exam Description",
    'course_id' => "Course", 
    'registration_deadline' => "Registration Deadline",
    'exam_start_datetime' => "Exam Start Date & Time",
    'duration' => "Duration (Minutes)", 
    'price' => "Exam Fee"
); 

$rules = array( 
    "title" => array("required" => true, "minlength" => 3, "maxlength" => 150), 
    "description" => array("required" => true, "minlength" => 10),
    "course_id" => array("required" => true), 
    "registration_deadline" => array("required" => true),
    "exam_start_datetime" => array("required" => true),
    "duration" => array("required" => true), 
    "price" => array("required" => true)
); 

$validator = new FormValidator($rules, $labels); 


// Server-side check: the submitted course must be allowed for this examiner
$course_ok = true;
if (isset($_POST['btn_insert'])) {
    $chk = $dao->getData(array('id'), 'courses', 'id = ' . (int)$_POST['course_id'] . ' AND ' . $course_cond);
    $course_ok = !empty($chk);
    if (!$course_ok) { echo "<script>alert('" . ($my_cat > 0 ? 'You can only create exams for courses in your category.' : 'Your category is not set yet. Please ask the admin to set it.') . "');</script>"; }
}
// Date rules: deadline cannot be in the past, and the exam must start after the registration deadline
$date_ok = true;
if (isset($_POST['btn_insert']) && !empty($_POST['registration_deadline']) && !empty($_POST['exam_start_datetime'])) {
    $dl = strtotime($_POST['registration_deadline']);
    $st = strtotime($_POST['exam_start_datetime']);
    if ($dl === false || $st === false) {
        $date_ok = false;
        echo "<script>alert('Please enter valid dates.');</script>";
    } elseif ($dl < strtotime(date('Y-m-d H:i'))) {
        $date_ok = false;
        echo "<script>alert('The registration deadline cannot be in the past.');</script>";
    } elseif ($st <= $dl) {
        $date_ok = false;
        echo "<script>alert('The exam start time must be after the registration deadline.');</script>";
    }
}

if(isset($_POST["btn_insert"])) { 
    if($course_ok && $date_ok && $validator->validate($_POST)) { 

        // Handle exam image upload
        $image_filename = '';
        $image_error = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = array('jpg', 'jpeg', 'png', 'webp');
            $max_size = 2 * 1024 * 1024; // 2MB

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
                $image_filename = 'exam_' . time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($tmp_name, $upload_dir . $image_filename);
            }
        }

        if ($image_error !== '') {
            echo "<script> alert('" . addslashes($image_error) . "'); </script>";
        } else {
            $data = array( 
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'image' => $image_filename,
                'course_id' => (int)$_POST['course_id'],
                'category' => 'Certification', 
                'registration_deadline' => $_POST['registration_deadline'],
                'exam_start_datetime' => $_POST['exam_start_datetime'],
                'duration_minutes' => (int)$_POST['duration'],
                'price' => $_POST['price'],

                'status' => 'draft',

                // REQUIRED: links to examiner account
                'created_by' => (int)$_SESSION['examiner_id']
            ); 
            
            if($dao->insert($data, "exams")) { 
                $latest = $dao->getData('*', 'exams',
                    'created_by = ' . (int)$_SESSION['examiner_id'] . ' ORDER BY id DESC LIMIT 1');
                $exam_id = !empty($latest) ? (int)$latest[0]['id'] : 0;
                
                if ($exam_id > 0) {
                    echo "<script> 
                        alert('Exam details saved successfully! Now select question format.'); 
                        window.location.href='set_exam_type.php?exam_id=" . $exam_id . "';
                    </script>"; 
                } else {
                    echo "<script> alert('Exam saved but could not be reopened. Check the exams table.'); </script>";
                }
            } else {
                echo "<script> alert('Exam creation failed'); </script>";
            } 
        }
    } 
} 
?> 

<div class="row">
    <div class="col-md-12">
        <h2>Certification Exam</h2>
        <?php if ($my_cat == 0) { ?>
            <div class="alert alert-warning">Your category has not been set yet, so you cannot create exams. Please ask the admin to set it.</div>
        <?php } ?>
        <hr>
        <form action="" method="POST" enctype="multipart/form-data" autocomplete="off"> 
            <div class="row"> 
                <div class="col-md-6 form-group"> 
                    <label>Exam Title:</label>
                    <?= $form->textBox('title', array('class' => 'form-control')); ?> 
                    <?= $validator->error('title'); ?> 
                </div> 

                <div class="col-md-6 form-group"> 
                    <label>Course:</label>
                    <select name="course_id" class="form-control">
                        <option value="">Select Course</option>
                        <?php
                        $courses = $dao->getData('*', 'courses', $course_cond);
                        if (!empty($courses)) {
                            foreach($courses as $c) {
                                $selected = (isset($_POST['course_id']) && $_POST['course_id'] == $c['id']) ? 'selected' : '';
                                echo "<option value='".(int)$c['id']."' ".$selected.">"
                                   . htmlspecialchars($c['course_name'], ENT_QUOTES, 'UTF-8') . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <?= $validator->error('course_id'); ?> 
                </div> 

                <div class="col-md-12 form-group"> 
                    <label>Exam Description:</label>
                    <textarea name="description" class="form-control" rows="3"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : '' ?></textarea> 
                    <?= $validator->error('description'); ?> 
                </div> 

                <div class="col-md-6 form-group"> 
                    <label>Exam Image:</label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp"> 
                </div> 

                <div class="col-md-6 form-group"> 
                    <label>Registration Deadline (Date &amp; Time):</label>
                    <?= $form->textBox('registration_deadline', array('class' => 'form-control', 'type' => 'datetime-local', 'min' => $min_dt)); ?> 
                    <?= $validator->error('registration_deadline'); ?> 
                </div> 

                <div class="col-md-6 form-group"> 
                    <label>Exam Start Date &amp; Time:</label>
                    <?= $form->textBox('exam_start_datetime', array('class' => 'form-control', 'type' => 'datetime-local', 'min' => $min_dt)); ?> 
                    <?= $validator->error('exam_start_datetime'); ?> 
                </div> 

                <div class="col-md-6 form-group"> 
                    <label>Duration (Minutes):</label>
                    <?= $form->textBox('duration', array('class' => 'form-control', 'type' => 'number', 'min' => '1')); ?> 
                    <?= $validator->error('duration'); ?> 
                </div> 

                <div class="col-md-6 form-group"> 
                    <label>Exam Fee:</label>
                    <?= $form->textBox('price', array('class' => 'form-control', 'type' => 'number', 'step' => '0.01', 'min' => '1')); ?> 
                    <?= $validator->error('price'); ?> 
                </div> 
            </div> 

            <button type="submit" name="btn_insert" class="btn btn-primary" style="margin-top:20px;">Next</button> 
        </form> 
    </div>
</div>

<script>
(function () {
    var dl = document.getElementsByName('registration_deadline')[0];
    var st = document.getElementsByName('exam_start_datetime')[0];
    if (!dl || !st) { return; }
    function sync() {
        if (dl.value) {
            st.min = dl.value;                 // start cannot be before the deadline
            if (st.value && st.value <= dl.value) { st.value = ''; }
        } else {
            st.min = dl.min;
        }
    }
    dl.addEventListener('change', sync);
    sync();
})();
</script>

<?php include('footer.php'); ?>