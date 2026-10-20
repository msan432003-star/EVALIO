<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;


$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: course.php');
    exit;
}

$fields = array('course_name');
$course = $dao->getData($fields, 'courses', 'id=' . $id);

if (empty($course)) {
    header('Location: course.php');
    exit;
}

$elements = array("cname" => $course[0]['course_name']);
$form = new FormAssist($elements, $_POST);

$labels = array('cname' => "Course Name");
$rules = array(
    "cname" => array("required" => true, "minlength" => 3, "maxlength" => 50),
);

$validator = new FormValidator($rules, $labels);

$msg = '';
$updated = false;

// All categories
$categories = $dao->getData('*', 'categories', '1=1 ORDER BY name');
if (!is_array($categories)) { $categories = array(); }

// Currently linked categories (or the ticked ones if the form was submitted with an error)
$selected = array();
if (isset($_POST['btn_update'])) {
    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
        foreach ($_POST['categories'] as $d) { $selected[] = (int)$d; }
    }
} else {
    $links = $dao->getData(array('category_id'), 'course_categories', 'course_id=' . $id);
    if (is_array($links)) {
        foreach ($links as $l) { $selected[] = (int)$l['category_id']; }
    }
}

if (isset($_POST["btn_update"])) {
    $_POST['cname'] = trim($_POST['cname'] ?? '');

    $validCats = array();
    foreach ($categories as $d) {
        if (in_array((int)$d['id'], $selected, true)) { $validCats[] = (int)$d['id']; }
    }

    if ($validator->validate($_POST)) {
        $name = $_POST['cname'];

        if (empty($validCats)) {
            $msg = "Please choose at least one category for this course";
        } else {
            $nameOk = true;
            $oldName = $course[0]['course_name'];

            if ($name !== $oldName) {
                // Block duplicate names (excluding this course itself)
                $exists = $dao->getData(array('id'), 'courses', "course_name='" . addslashes($name) . "' AND id<>" . $id);

                if (!empty($exists)) {
                    $msg = "A course with this name already exists";
                    $nameOk = false;
                } elseif (!$dao->update(array('course_name' => $name), "courses", 'id=' . $id)) {
                    $msg = "Update failed";
                    $nameOk = false;
                }
            }

            if ($nameOk) {
                // Replace the category links with the new selection
                $dao->delete('course_categories', 'course_id=' . $id);
                foreach ($validCats as $did) {
                    $dao->insert(array('course_id' => $id, 'category_id' => $did), 'course_categories');
                }

                // Audit log
                $notes = ($name !== $oldName)
                    ? "Renamed course '" . $oldName . "' to '" . $name . "'"
                    : "Updated categories of course: " . $name;
                logAction($admin_id, 'edit_course', 'courses', $id, $notes);

                $updated = true;
            }
        }
    }
}

include("header.php");
?>

<?php if ($updated) { ?>
<script> alert('Course updated successfully'); window.location.href='course.php';</script>
<?php } ?>

<h2>Edit Course</h2>
<hr>
<span style="color:red;"><?php echo h($msg); ?></span>

<form action="" method="POST">
    
    <div class="row">
        <div class="col-md-6 form-group">
            Course Name: <?= $form->textBox('cname', array('class' => 'form-control')); ?>
            <?= $validator->error('cname'); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Categories this course belongs to:</label>
            <?php foreach ($categories as $d) { ?>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= (int)$d['id']; ?>"
                            <?= in_array((int)$d['id'], $selected, true) ? 'checked' : ''; ?>>
                        <?= h($d['name']); ?>
                    </label>
                </div>
            <?php } ?>
        </div>
    </div>

    <button type="submit" name="btn_update" class="btn btn-primary" style="margin-top:20px;">Update</button>
    <a href="course.php" style="margin-left:10px;">Cancel</a>
</form>

<?php include('footer.php'); ?>
