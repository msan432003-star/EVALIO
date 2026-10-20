<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: category.php'); exit; }

$cat = $dao->getData(array('name'), 'categories', 'id=' . $id);
if (empty($cat)) { header('Location: category.php'); exit; }

$msg = '';
$updated = false;
$name = $cat[0]['name'];

if (isset($_POST['btn_update'])) {
    $oldName = $cat[0]['name'];
    $name = trim($_POST['category_name'] ?? '');

    if (strlen($name) < 2 || strlen($name) > 80) {
        $msg = 'Category name must be 2 to 80 characters';
    } else {
        $exists = $dao->getData(array('id'), 'categories', "name='" . addslashes($name) . "' AND id<>" . $id);
        if (!empty($exists)) {
            $msg = 'A category with this name already exists';
        } elseif ($name === $oldName) {
            // Nothing changed - nothing to log
            $updated = true;
        } elseif ($dao->update(array('name' => $name), 'categories', 'id=' . $id)) {
            // Audit log
            logAction($admin_id, 'edit_category', 'categories', $id, "Renamed category '" . $oldName . "' to '" . $name . "'");
            $updated = true;
        } else {
            $msg = 'Update failed';
        }
    }
}

include("header.php");
?>

<?php if ($updated) { ?>
<script>alert('Category updated successfully'); window.location.href='category.php';</script>
<?php } ?>

<h2>Edit Category</h2>
<hr>
<span style="color:red;"><?= h($msg); ?></span>

<form action="" method="POST">
    <div class="row">
        <div class="col-md-6 form-group">
            <label>Category name:</label>
            <input type="text" name="category_name" class="form-control" maxlength="80" required value="<?= h($name); ?>">
        </div>
    </div>
    <button type="submit" name="btn_update" class="btn btn-primary">Update</button>
    <a href="category.php" style="margin-left:10px;">Cancel</a>
</form>

<?php include('footer.php'); ?>
