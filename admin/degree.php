<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;
$msg = '';

$categories = $dao->getData('*', 'categories', '1=1 ORDER BY name');
if (!is_array($categories)) { $categories = array(); }

$filter = (int)($_GET['cat'] ?? 0);
$selCat = 0;
$degName = '';

// ---------- Add degree ----------
if (isset($_POST['btn_add_degree'])) {
    $degName = trim($_POST['degree_name'] ?? '');
    $selCat  = (int)($_POST['category_id'] ?? 0);

    $catOk = false;
    $catName = '';
    foreach ($categories as $c) {
        if ((int)$c['id'] === $selCat) { $catOk = true; $catName = $c['name']; break; }
    }

    if (!$catOk) {
        $msg = 'Please choose a category';
    } elseif (strlen($degName) < 2 || strlen($degName) > 100) {
        $msg = 'Degree name must be 2 to 100 characters';
    } else {
        $exists = $dao->getData(array('id'), 'degrees', "name='" . addslashes($degName) . "' AND category_id=" . $selCat);
        if (!empty($exists)) {
            $msg = 'This degree already exists in that category';
        } elseif ($dao->insert(array('name' => $degName, 'category_id' => $selCat), 'degrees')) {
            // insert() does not return the new id, so look it up by name + category
            $row = $dao->getData(array('id'), 'degrees', "name='" . addslashes($degName) . "' AND category_id=" . $selCat . " ORDER BY id DESC LIMIT 1");
            $newId = !empty($row) ? (int)$row[0]['id'] : 0;

            // Audit log
            logAction($admin_id, 'add_degree', 'degrees', $newId, 'Added degree: ' . $degName . ' (category: ' . $catName . ')');

            header('Location: degree.php?added=1&cat=' . $selCat);
            exit;
        } else {
            $msg = 'Degree could not be added';
        }
    }
} elseif ($filter > 0) {
    $selCat = $filter; // pre-select the category being viewed
}

// ---------- List ----------
$where = $filter > 0 ? 'WHERE d.category_id = ' . $filter : '';
$degrees = $dao->query(
    "SELECT d.id, d.name, c.name AS category_name,
            (SELECT COUNT(*) FROM examiner_profiles p WHERE p.degree_id = d.id) AS examiner_count
     FROM degrees d JOIN categories c ON c.id = d.category_id
     $where
     ORDER BY c.name, d.name"
);
if (!is_array($degrees)) { $degrees = array(); }

include("header.php");
?>

<?php if (isset($_GET['added'])) { ?>
<script>alert('Degree added successfully'); window.history.replaceState(null, '', 'degree.php<?= $filter > 0 ? '?cat=' . $filter : ''; ?>');</script>
<?php } ?>

<h2>Degrees</h2>
<p class="text-muted">Each degree belongs to one category. During registration, examiners first pick a category and then see only that category's degrees.</p>
<hr>

<span style="color:red;"><?= h($msg); ?></span>

<form action="" method="POST" class="form-inline" style="margin-bottom:25px;">
    <div class="form-group">
        <label>Category:</label>
        <select name="category_id" class="form-control" required>
            <option value="">-- Select category --</option>
            <?php foreach ($categories as $c) { ?>
                <option value="<?= (int)$c['id']; ?>" <?= $selCat === (int)$c['id'] ? 'selected' : ''; ?>><?= h($c['name']); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="form-group">
        <label>Degree:</label>
        <input type="text" name="degree_name" class="form-control" maxlength="100" required placeholder="e.g. MCA" value="<?= h($degName); ?>">
    </div>
    <button type="submit" name="btn_add_degree" class="btn btn-primary">Add Degree</button>
</form>

<form method="GET" class="form-inline" style="margin-bottom:15px;">
    <label>Show:</label>
    <select name="cat" class="form-control" onchange="this.form.submit()">
        <option value="0">All categories</option>
        <?php foreach ($categories as $c) { ?>
            <option value="<?= (int)$c['id']; ?>" <?= $filter === (int)$c['id'] ? 'selected' : ''; ?>><?= h($c['name']); ?></option>
        <?php } ?>
    </select>
</form>

<table class="table table-bordered table-striped">
    <thead>
        <tr><th>#</th><th>Degree</th><th>Category</th><th>Examiners</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php if (!empty($degrees)) { $i = 1; foreach ($degrees as $d) { ?>
        <tr>
            <td><?= $i++; ?></td>
            <td><?= h($d['name']); ?></td>
            <td><?= h($d['category_name']); ?></td>
            <td><?= (int)$d['examiner_count']; ?></td>
            <td>
                <a href="editdegree.php?id=<?= (int)$d['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="deletedegree.php?id=<?= (int)$d['id']; ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete this degree?');">Delete</a>
            </td>
        </tr>
    <?php } } else { ?>
        <tr><td colspan="5" class="text-center">No degrees added yet.</td></tr>
    <?php } ?>
    </tbody>
</table>

<?php include('footer.php'); ?>
