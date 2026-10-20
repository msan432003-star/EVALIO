<?php 
require('../config/autoload.php');

if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
} 
include("header.php"); 

$dao = new DataAccess(); 

// Category rule: an examiner can only use courses of their own category.
// An examiner whose category is not set yet cannot create exams until the admin sets it.
$my_cat = 0;
$cat_row = $dao->getData(array('category_id'), 'examiners', 'id = ' . (int)$_SESSION['examiner_id']);
if (!empty($cat_row) && !empty($cat_row[0]['category_id'])) { $my_cat = (int)$cat_row[0]['category_id']; }
// A subject can belong to several categories (course_categories link table)
$course_cond = ($my_cat > 0)
    ? 'id IN (SELECT course_id FROM course_categories WHERE category_id = ' . $my_cat . ')'
    : '1=0';

// Fetch available courses for dropdown
$courses = $dao->getData('*', 'courses', $course_cond);


// Server-side check: the submitted course must be allowed for this examiner
$course_ok = true;
if (isset($_POST['btn_save_mock'])) {
    $chk = $dao->getData(array('id'), 'courses', 'id = ' . (int)$_POST['course_id'] . ' AND ' . $course_cond);
    $course_ok = !empty($chk);
    if (!$course_ok) { echo "<script>alert('" . ($my_cat > 0 ? 'You can only create exams for courses in your category.' : 'Your category is not set yet. Please ask the admin to set it.') . "');</script>"; }
}

if(isset($_POST['btn_save_mock']) && $course_ok) {$data = array(
        'title'            => trim($_POST['title']),
        'course_id'        => (int)$_POST['course_id'],
        'category'         => 'Mock',
        'duration_minutes' => (int)$_POST['duration_minutes'],
        'price'            => 0, // Mock exams are always free
        'created_by'       => (int)$_SESSION['examiner_id'],
        'status'           => 'draft'
    );

    // Server-side guard: the client-side min="1" on duration can be bypassed
    // (e.g. typing "00"), which would create an exam with a 0-second timer.
    if($data['duration_minutes'] < 1) {
        $data['duration_minutes'] = 30;
    }

    // Insert mock exam into `exams` table
    $insert_ok = $dao->insert($data, 'exams');

    if($insert_ok) {
        // BUGFIX: $dao->insert() only tells us whether the insert succeeded,
        // it is NOT the new row's id. The old code did
        //     $exam_id = $dao->insert($data, 'exams');
        // and then used $exam_id straight in the redirect URL below. When
        // insert() returns a plain boolean, `true` gets cast to the string
        // "1" in the URL, so every new mock exam redirected to
        // addquestion.php?exam_id=1 instead of the exam that was actually
        // just created — questions ended up attached to the wrong exam,
        // and the real new exam was left with none.
        //
        // Look the row back up by the data we just inserted so we get its
        // real id, regardless of what insert() returns.
        $new_exam = $dao->getData(
            '*',
            'exams',
            "title = '" . addslashes($data['title']) . "'"
            . " AND course_id = " . $data['course_id']
            . " AND created_by = " . $data['created_by']
            . " AND category = 'Mock'"
            . " ORDER BY id DESC LIMIT 1"
        );

        $exam_id = !empty($new_exam) ? (int)$new_exam[0]['id'] : 0;

        if($exam_id > 0) {
            echo "<script>
                alert('Mock Exam created successfully! Now select question format.');
                window.location.href = 'set_exam_type.php?exam_id=" . $exam_id . "';
            </script>";
            exit();
        } else {
            // Insert succeeded but we couldn't confirm the id - don't guess,
            // send the examiner to pick it from the exam list instead of
            // risking questions landing on the wrong exam again.
            echo "<script>
                alert('Mock Exam created, but its ID could not be confirmed. Please open it from your exam list to add questions.');
                window.location.href = 'manage_exams.php';
            </script>";
            exit();
        }
    } else {
        echo "<script>alert('Failed to create Mock Exam. Please try again.');</script>";
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Create New Mock Exam</h2>
        <?php if ($my_cat == 0) { ?>
            <div class="alert alert-warning">Your category has not been set yet, so you cannot create exams. Please ask the admin to set it.</div>
        <?php } ?>
        <hr>
        
        <form action="" method="POST" autocomplete="off">
            <div class="row">
                <div class="col-md-12 form-group">
                    <label>Mock Exam Title:</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Data Structures Mock Test 1" required>
                </div>

                <div class="col-md-6 form-group">
                    <label>Course:</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">-- Select Course --</option>
                        <?php if(!empty($courses)) { 
                            foreach($courses as $c) { ?>
                                <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['course_name'] ?? $c['title'] ?? 'Course '.$c['id']); ?></option>
                        <?php } } ?>
                    </select>
                </div>

                <div class="col-md-3 form-group">
                    <label>Duration (Minutes):</label>
                    <input type="number" name="duration_minutes" class="form-control" value="30" min="1" required>
                </div>
            </div>

            <button type="submit" name="btn_save_mock" class="btn btn-primary" style="margin-top:20px;">
                Create Mock &amp; Proceed to Add Questions
            </button>
        </form>
    </div>
</div>

<?php include('footer.php'); ?>