<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: degree.php'); exit; }

$deg = $dao->getData(array('name', 'category_id'), 'degrees', 'id=' . $id);
if (empty($deg)) { header('Location: degree.php'); exit; }

$categories = $dao->getData('*', 'categories', '1=1 ORDER BY name');
if (!is_array($categories)) { $categories = array(); }

$inUse = $dao->getData(array('id'), 'examiner_profiles', 'degree_id=' . $id);
$inUse = !empty($inUse);

$msg = '';
$updated = false;
$name  = $deg[0]['name'];
$catId = (int)$deg[0]['category_id'];

if (isset($_POST['btn_update'])) {
    $oldName  = $deg[0]['name'];
    $oldCatId = (int)$deg[0]['category_id'];

    $name  = trim($_POST['degree_name'] ?? '');
    $catId = (int)($_POST['category_id'] ?? 0);

    $catOk = false;
    $newCatName = '';
    foreach ($categories as $c) {
        if ((int)$c['id'] === $catId) { $catOk = true; $newCatName = $c['name']; break; }
    }

    if (!$catOk) {
        $msg = 'Please choose a category';
    } elseif ($inUse && $catId !== $oldCatId) {
        $msg = 'Examiners already use this degree, so its category cannot be changed';
        $catId = $oldCatId;
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $msg = 'Degree name must be 2 to 100 characters';
    } else {
        $exists = $dao->getData(array('id'), 'degrees', "name='" . addslashes($name) . "' AND category_id=" . $catId . " AND id<>" . $id);
        if (!empty($exists)) {
            $msg = 'This degree already exists in that category';
        } elseif ($dao->update(array('name' => $name, 'category_id' => $catId), 'degrees', 'id=' . $id)) {
            // Audit log
            $notes = "Degree '" . $oldName . "' updated to '" . $name . "'";
            if ($catId !== $oldCatId) {
                $notes .= " (moved to category: " . $newCatName . ")";
            }
            logAction($admin_id, 'edit_degree', 'degrees', $id, $notes);

            $updated = true;
        } else {
            $msg = 'Update failed';
        }
    }
}

include("header.php");
?>

<?php if ($updated) { ?>
<script>alert('Degree updated successfully'); window.location.href='degree.php?cat=<?= (int)$catId; ?>';</script>
<?php } ?>

<h2>Edit Degree</h2>
<hr>
<span style="color:red;"><?= h($msg); ?></span>

<form action="" method="POST">
    <div class="row">
        <div class="col-md-6 form-group">
            <label>Category:</label>
            <select name="category_id" class="form-control" required <?= $inUse ? 'style="pointer-events:none;background:#eee;"' : ''; ?>>
                <?php foreach ($categories as $c) { ?>
                    <option value="<?= (int)$c['id']; ?>" <?= $catId === (int)$c['id'] ? 'selected' : ''; ?>><?= h($c['name']); ?></option>
                <?php } ?>
            </select>
            <?php if ($inUse) { ?><small class="text-muted">Locked: examiners already use this degree.</small><?php } ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 form-group">
            <label>Degree name:</label>
            <input type="text" name="degree_name" class="form-control" maxlength="100" required value="<?= h($name); ?>">
        </div>
    </div>
    <button type="submit" name="btn_update" class="btn btn-primary">Update</button>
    <a href="degree.php" style="margin-left:10px;">Cancel</a>
</form>

<?php include('footer.php'); ?>
