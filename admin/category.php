<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;
$msg = '';

// ---------- Add category ----------
if (isset($_POST['btn_add_category'])) {
    $name = trim($_POST['category_name'] ?? '');

    if (strlen($name) < 2 || strlen($name) > 80) {
        $msg = 'Category name must be 2 to 80 characters';
    } else {
        $exists = $dao->getData(array('id'), 'categories', "name='" . addslashes($name) . "'");
        if (!empty($exists)) {
            $msg = 'This category already exists';
        } elseif ($dao->insert(array('name' => $name), 'categories')) {
            // insert() does not return the new id, so look it up by the (unique) name
            $row = $dao->getData(array('id'), 'categories', "name='" . addslashes($name) . "' ORDER BY id DESC LIMIT 1");
            $newId = !empty($row) ? (int)$row[0]['id'] : 0;

            // Audit log
            logAction($admin_id, 'add_category', 'categories', $newId, 'Added category: ' . $name);

            header('Location: category.php?added=1');
            exit;
        } else {
            $msg = 'Category could not be added';
        }
    }
}

// ---------- List with counts ----------
$categories = $dao->query(
    "SELECT c.id, c.name,
            (SELECT COUNT(*) FROM course_categories cc WHERE cc.category_id = c.id) AS subject_count,
            (SELECT COUNT(*) FROM degrees dg WHERE dg.category_id = c.id) AS degree_count,
            (SELECT COUNT(*) FROM examiners ex WHERE ex.category_id = c.id) AS examiner_count
     FROM categories c
     ORDER BY c.name"
);
if (!is_array($categories)) { $categories = array(); }

include("header.php");
?>

<?php if (isset($_GET['added'])) { ?>
<script>alert('Category added successfully'); window.history.replaceState(null, '', 'category.php');</script>
<?php } ?>

<h2>Categories</h2>
<p class="text-muted">Add categories (e.g. Computer Science &amp; IT, Chemistry). Each category has its own <a href="degree.php">degrees</a> (MCA, BCA...) and subjects. Examiners can only create exams in subjects under their own category.</p>
<hr>

<span style="color:red;"><?= h($msg); ?></span>

<form action="" method="POST" class="form-inline" style="margin-bottom:25px;">
    <div class="form-group">
        <label>Category:</label>
        <input type="text" name="category_name" class="form-control" maxlength="80" required placeholder="e.g. Computer Science &amp; IT">
    </div>
    <button type="submit" name="btn_add_category" class="btn btn-primary">Add Category</button>
</form>

<table class="table table-bordered table-striped">
    <thead>
        <tr><th>#</th><th>Category</th><th>Degrees</th><th>Subjects</th><th>Examiners</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php if (!empty($categories)) { $i = 1; foreach ($categories as $d) { ?>
        <tr>
            <td><?= $i++; ?></td>
            <td><?= h($d['name']); ?></td>
            <td><a href="degree.php?cat=<?= (int)$d['id']; ?>"><?= (int)$d['degree_count']; ?></a></td>
            <td><?= (int)$d['subject_count']; ?></td>
            <td><?= (int)$d['examiner_count']; ?></td>
            <td>
                <a href="editcategory.php?id=<?= (int)$d['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="deletecategory.php?id=<?= (int)$d['id']; ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete this category?');">Delete</a>
            </td>
        </tr>
    <?php } } else { ?>
        <tr><td colspan="6" class="text-center">No categories added yet.</td></tr>
    <?php } ?>
    </tbody>
</table>

<?php include('footer.php'); ?>
