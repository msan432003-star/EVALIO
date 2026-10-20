<?php 
require('../config/autoload.php');

// 🔒 Only logged-in examiners can access this page
if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
} 
include("header.php");

$dao = new DataAccess();

$elements = array(
    "title" => "",
    "course_id" => "",
    "exam_id" => "",
    "ref_type" => "",
    "reference_url" => ""
);

$form = new FormAssist($elements, $_POST);

$labels = array(
    'title' => "Reference Title",
    'course_id' => "Course",
    'exam_id' => "Exam",
    'ref_type' => "Reference Type",
    'reference_url' => "Reference URL"
);

$rules = array(
    "title" => array("required" => true, "minlength" => 3, "maxlength" => 150),
    "course_id" => array("required" => true),
    "exam_id" => array("required" => true),
    "ref_type" => array("required" => true)
);
    
$validator = new FormValidator($rules, $labels);
$msg = "";

if(isset($_POST["btn_insert"])) {
    if($validator->validate($_POST)) {

        // ✅ Examiner can add references only to non-Mock exams he/she created
        $exam_check = $dao->getData(
            '*',
            'exams',
            'id = ' . (int)$_POST['exam_id'] . ' AND category != "Mock" AND created_by = ' . (int)$_SESSION['examiner_id']
        );
        if(empty($exam_check)) {
            $msg = "Error: You can only add references to exams you created.";
        }
        
        $filename = "";
        
        // ✅ PDF is processed only if the exam check passed ($msg == "")
        if($msg == "" && $_POST['ref_type'] == 'PDF') {
            if(isset($_FILES['file_path']) && $_FILES['file_path']['error'] == 0) {
                $extension = strtolower(pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION));
                
                if($extension == 'pdf') {
                    // Automatically create uploads folder if it doesn't exist
                    if(!is_dir('../uploads/')) {
                        mkdir('../uploads/', 0755, true);
                    }
                    
                    $filename = time() . "_" . rand(1000, 9999) . "." . $extension;
                    $target = "../uploads/" . $filename;
                    
                    if(!move_uploaded_file($_FILES['file_path']['tmp_name'], $target)) {
                        $msg = "Error: Failed to move uploaded file. Check folder permissions.";
                    }
                } else {
                    $msg = "Error: Invalid file type. Only PDF files are allowed.";
                }
            } else {
                // Only throw an error if a file wasn't provided at all under PDF type
                if(!isset($_FILES['file_path']) || $_FILES['file_path']['error'] != UPLOAD_ERR_NO_FILE) {
                    $err = isset($_FILES['file_path']) ? $_FILES['file_path']['error'] : 'unknown';
                    $msg = "Error: File upload failed with error code: " . $err;
                } else {
                    $msg = "Error: Please choose a PDF file to upload.";
                }
            }
        }

        // URL type must have a URL
        if($msg == "" && $_POST['ref_type'] == 'URL' && trim($_POST['reference_url']) == '') {
            $msg = "Error: Please enter the reference URL.";
        }

        // Proceed with database insertion only if there are no errors
        if($msg == "") {
            $data = array(
                // ✅ course comes from the verified exam, so course and exam can't mismatch
                'course_id' => $exam_check[0]['course_id'],
                'exam_id' => (int)$_POST['exam_id'],
                'title' => $_POST['title'],
                'ref_type' => $_POST['ref_type'],
                'file_path' => $filename,
                'reference_url' => $_POST['reference_url']
            );
          
            if($dao->insert($data, "exam_references")) {
                echo "<script> alert('Study reference added successfully!'); window.location.href='addreference.php'; </script>";
            } else {
                $msg = "Failed to add reference to database.";
            }
        }
    }
}
?>
<script>
/*
 * FIX: header.php may load jQuery at the bottom of the page (footer.php),
 * so "$" does not exist yet when this script is parsed.
 * This wrapper waits until the page has fully loaded (after the footer scripts),
 * uses the layout's jQuery if present, and only loads jQuery from the CDN if it is missing.
 */
(function () {

    function initReferencePage($) {

        // 1. Toggle PDF vs URL fields
        function togglePdfUrl(val) {
            if (val === 'PDF') {
                $('#pdf_group').show();
                $('#url_group').hide();
            } else if (val === 'URL') {
                $('#url_group').show();
                $('#pdf_group').hide();
            } else {
                $('#pdf_group').hide();
                $('#url_group').hide();
            }
        }

        togglePdfUrl($('#ref_type').val());

        $('#ref_type').on('change', function () {
            togglePdfUrl($(this).val());
        });

        // 2. Load certification exams (excluding Mock tests) via AJAX
        function fetchExams(courseId, preSelectedExam) {
            if (courseId !== "") {
                $.ajax({
                    url: 'get_exams.php',
                    type: 'POST',
                    data: { course_id: courseId, selected_exam: preSelectedExam },
                    success: function (response) {
                        $('#exam_id').html(response);
                    }
                });
            } else {
                $('#exam_id').html('<option value="">Select Exam</option>');
            }
        }

        // Course already selected on page load (e.g. after a validation error or browser form restore)
        var currentCourse = $('#course_id').val();
        var savedExam = "<?= isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : '' ?>";
        if (currentCourse !== "") {
            fetchExams(currentCourse, savedExam);
        }

        // 3. Dependent dropdown: fetch exams when the course changes
        $('#course_id').on('change', function () {
            fetchExams($(this).val(), "");
        });
    }

    function boot() {
        if (window.jQuery) {
            initReferencePage(window.jQuery);
            return;
        }
        // Fallback: layout did not load jQuery, so load it here
        var s = document.createElement('script');
        s.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        s.onload = function () { initReferencePage(window.jQuery); };
        document.head.appendChild(s);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>

<div class="row">
    <div class="col-md-12">
        <h2>Examiner: Add Study Reference (Book / Material)</h2>
        <hr>
        
        <?php if($msg != "") { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
        <?php } ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row">
                
                <!-- Reference Title -->
                <div class="col-md-6 form-group">
    <label>Reference Title:</label> 
    <?= $form->textBox('title', array('class' => 'form-control', 'placeholder' => 'Enter the title')); ?>
    <?= $validator->error('title'); ?>
</div>


                <!-- Course Selection -->
                <div class="col-md-6 form-group">
                    <label>Course:</label> 
                    <select name="course_id" id="course_id" class="form-control">
                        <option value="">Select Course</option>
                        <?php
                        $courses = $dao->getData('*', 'courses', '1');
                        foreach($courses as $c) {
                            $selected = (isset($_POST['course_id']) && $_POST['course_id'] == $c['id']) ? 'selected' : '';
                            echo "<option value='".$c['id']."' ".$selected.">".htmlspecialchars($c['course_name']).'</option>';
                        }
                        ?>
                    </select>
                    <?= $validator->error('course_id'); ?>
                </div>

                <!-- Exam Selection (Dependent - only your own non-Mock exams) -->
                <div class="col-md-6 form-group">
                    <label>Exam:</label> 
                    <select name="exam_id" id="exam_id" class="form-control">
                        <option value="">Select Exam</option>
                    </select>
                    <?= $validator->error('exam_id'); ?>
                </div>

                <!-- Reference Type (PDF vs URL) -->
                <div class="col-md-6 form-group">
                    <label>Reference Type:</label>
                    <select name="ref_type" id="ref_type" class="form-control">
                        <option value="">Select Type</option>
                        <option value="PDF" <?= (isset($_POST['ref_type']) && $_POST['ref_type']=='PDF')?'selected':''; ?>>PDF Document Upload</option>
                        <option value="URL" <?= (isset($_POST['ref_type']) && $_POST['ref_type']=='URL')?'selected':''; ?>>External URL Link</option>
                    </select>
                    <?= $validator->error('ref_type'); ?>
                </div>

                <!-- File Upload Field (Shown if PDF) -->
                <div class="col-md-6 form-group" id="pdf_group" style="display:none;">
                    <label>Upload PDF File:</label>
                    <input type="file" name="file_path" class="form-control">
                </div>

                <!-- URL Input Field (Shown if URL) -->
                <div class="col-md-6 form-group" id="url_group" style="display:none;">
                    <label>Reference URL:</label>
                    <?= $form->textBox('reference_url', array('class' => 'form-control', 'placeholder' => 'https://example.com')); ?>
                </div>

            </div>

            <button type="submit" name="btn_insert" class="btn btn-primary" style="margin-top:20px;">Save Reference</button>
        </form>
    </div>
</div>

<?php include('footer.php'); ?>