<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;

// Tables that reference a course. A course used by any of these cannot be deleted.
// EDIT to match your real database columns.
$linked = array(
    'exams' => 'course_id',
);

function go($key) {
    header('Location: course.php?done=' . $key);
    exit;
}

// ---------- Categories ----------
$categories = $dao->getData('*', 'categories', '1=1 ORDER BY name');
if (!is_array($categories)) { $categories = array(); }
$catNames = array();
foreach ($categories as $c) { $catNames[(int)$c['id']] = $c['name']; }

// ---------- DELETE ----------
if (isset($_POST['btn_delete'])) {
    $delId = (int)($_POST['delete_id'] ?? 0);
    $row = $delId > 0 ? $dao->getData(array('id', 'course_name'), 'courses', 'id=' . $delId) : array();
    if (empty($row)) { go('notfound'); }

    foreach ($linked as $table => $column) {
        try {
            $used = $dao->getData(array('id'), $table, $column . '=' . $delId);
        } catch (Throwable $e) {
            $used = array();
        }
        if (!empty($used)) { go('inuse'); }
    }

    $dao->delete('course_categories', 'course_id=' . $delId);
    if ($dao->delete('courses', 'id=' . $delId)) {
        logAction($admin_id, 'delete_course', 'courses', $delId, 'Deleted course: ' . $row[0]['course_name']);
        go('deleted');
    }
    go('deletefail');
}

// ---------- Edit mode? ----------
$editId = (int)($_GET['edit'] ?? $_POST['edit_id'] ?? 0);
$editCourse = null;
if ($editId > 0) {
    $r = $dao->getData(array('id', 'course_name'), 'courses', 'id=' . $editId);
    if (empty($r)) { header('Location: course.php'); exit; }
    $editCourse = $r[0];
}

// ---------- Form setup ----------
$elements = array("cname" => $editCourse ? $editCourse['course_name'] : "");
$form = new FormAssist($elements, $_POST);
$labels = array('cname' => "Course Name");
$rules = array(
    "cname" => array("required" => true, "minlength" => 3, "maxlength" => 50),
);
$validator = new FormValidator($rules, $labels);
$msg = '';

// Ticked categories
$selected = array();
if (isset($_POST['btn_save'])) {
    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
        foreach ($_POST['categories'] as $d) { $selected[] = (int)$d; }
    }
} elseif ($editId > 0) {
    $links = $dao->getData(array('category_id'), 'course_categories', 'course_id=' . $editId);
    if (is_array($links)) {
        foreach ($links as $l) { $selected[] = (int)$l['category_id']; }
    }
}

// ---------- SAVE (add or update) ----------
if (isset($_POST['btn_save'])) {
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
            // Duplicate name check (ignore this course itself when editing)
            $cond = "course_name='" . addslashes($name) . "'";
            if ($editId > 0) { $cond .= " AND id<>" . $editId; }
            $exists = $dao->getData(array('id'), 'courses', $cond);

            if (!empty($exists)) {
                $msg = "A course with this name already exists";
            } elseif ($editId > 0) {
                // ----- UPDATE -----
                $oldName = $editCourse['course_name'];
                $ok = true;
                if ($name !== $oldName) {
                    $ok = $dao->update(array('course_name' => $name), "courses", 'id=' . $editId);
                }
                if ($ok) {
                    $dao->delete('course_categories', 'course_id=' . $editId);
                    foreach ($validCats as $did) {
                        $dao->insert(array('course_id' => $editId, 'category_id' => $did), 'course_categories');
                    }
                    $notes = ($name !== $oldName)
                        ? "Renamed course '" . $oldName . "' to '" . $name . "'"
                        : "Updated categories of course: " . $name;
                    logAction($admin_id, 'edit_course', 'courses', $editId, $notes);
                    go('updated');
                } else {
                    $msg = "Update failed";
                }
            } else {
                // ----- INSERT -----
                if ($dao->insert(array('course_name' => $name), "courses")) {
                    $row = $dao->getData(array('id'), 'courses', "course_name='" . addslashes($name) . "' ORDER BY id DESC LIMIT 1");
                    $newId = !empty($row) ? (int)$row[0]['id'] : 0;
                    if ($newId > 0) {
                        foreach ($validCats as $did) {
                            $dao->insert(array('course_id' => $newId, 'category_id' => $did), 'course_categories');
                        }
                    }
                    logAction($admin_id, 'add_course', 'courses', $newId, 'Added course: ' . $name);
                    go('added');
                } else {
                    $msg = "Course could not be added";
                }
            }
        }
    }
}

// ---------- List data ----------
$courses = $dao->getData('*', 'courses', '1=1 ORDER BY course_name');
if (!is_array($courses)) { $courses = array(); }

$courseCats = array(); // course_id => [category names]
$allLinks = $dao->getData('*', 'course_categories', '1=1');
if (is_array($allLinks)) {
    foreach ($allLinks as $l) {
        $cid = (int)$l['category_id'];
        if (isset($catNames[$cid])) { $courseCats[(int)$l['course_id']][] = $catNames[$cid]; }
    }
}

$flashMsgs = array(
    'added'      => array('success', 'Course added successfully'),
    'updated'    => array('success', 'Course updated successfully'),
    'deleted'    => array('success', 'Course deleted successfully'),
    'deletefail' => array('danger',  'Deletion failed'),
    'notfound'   => array('danger',  'Course not found'),
    'inuse'      => array('danger',  'This course is used by existing exams and cannot be deleted'),
);
$flash = $flashMsgs[$_GET['done'] ?? ''] ?? null;

include("header.php");
?>

<h2>Courses / Subjects</h2>
<hr>

<?php if ($flash) { ?>
    <div class="alert alert-<?= $flash[0]; ?>"><?= h($flash[1]); ?></div>
<?php } ?>

<h4><?= $editCourse ? 'Edit Course' : 'Add Course'; ?></h4>
<span style="color:red;"><?= h($msg); ?></span>

<form action="course.php<?= $editCourse ? '?edit=' . (int)$editId : ''; ?>" method="POST">
    <?php if ($editCourse) { ?>
        <input type="hidden" name="edit_id" value="<?= (int)$editId; ?>">
    <?php } ?>

    <div class="row">
        <div class="col-md-6 form-group">
            Course Name: <?= $form->textBox('cname', array('class' => 'form-control')); ?>
            <?= $validator->error('cname'); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Categories this course belongs to (tick one or more):</label>
            <?php if (!empty($categories)) { foreach ($categories as $d) { ?>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= (int)$d['id']; ?>"
                            <?= in_array((int)$d['id'], $selected, true) ? 'checked' : ''; ?>>
                        <?= h($d['name']); ?>
                    </label>
                </div>
            <?php } } else { ?>
                <p class="text-danger">No categories found. <a href="category.php">Add a category first</a>.</p>
            <?php } ?>
        </div>
    </div>

    <button type="submit" name="btn_save" class="btn btn-primary">
        <?= $editCourse ? 'Update' : 'Submit'; ?>
    </button>
    <?php if ($editCourse) { ?>
        <a href="course.php" style="margin-left:10px;">Cancel</a>
    <?php } ?>
</form>

<hr>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Course Name</th>
            <th>Categories</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($courses)) { ?>
        <tr><td colspan="4" class="text-center">No courses added yet.</td></tr>
    <?php } else { $i = 1; foreach ($courses as $c) { ?>
        <tr<?= ($editId === (int)$c['id']) ? ' class="info"' : ''; ?>>
            <td><?= $i++; ?></td>
            <td><?= h($c['course_name']); ?></td>
            <td><?= h(implode(', ', $courseCats[(int)$c['id']] ?? array())); ?></td>
            <td>
                <a href="course.php?edit=<?= (int)$c['id']; ?>" class="btn btn-success btn-sm">Edit</a>
                <form action="course.php" method="POST" style="display:inline;"
                      onsubmit="return confirm('Delete this course?');">
                    <input type="hidden" name="delete_id" value="<?= (int)$c['id']; ?>">
                    <button type="submit" name="btn_delete" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
    <?php } } ?>
    </tbody>
</table>

<?php include('footer.php'); ?>
