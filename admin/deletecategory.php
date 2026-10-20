<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: category.php'); exit; }

$cat = $dao->getData(array('id', 'name'), 'categories', 'id=' . $id);
if (empty($cat)) {
    echo "<script>alert('Category not found'); window.location.href='category.php';</script>";
    exit;
}
$catName = $cat[0]['name'];

// A category that is still in use is never deleted, so no subject, degree or examiner is ever lost
$checks = array(
    array('course_categories', 'course_id', 'subjects'),
    array('degrees',           'id',        'degrees'),
    array('examiners',         'id',        'examiners'),
);
foreach ($checks as $c) {
    try {
        $used = $dao->getData(array($c[1]), $c[0], 'category_id=' . $id);
    } catch (Throwable $e) {
        $used = array();
    }
    if (!empty($used)) {
        echo "<script>alert('This category still has " . $c[2] . " linked to it. Remove or move them first.'); window.location.href='category.php';</script>";
        exit;
    }
}

if ($dao->delete('categories', 'id=' . $id)) {
    // Audit log
    logAction($admin_id, 'delete_category', 'categories', $id, 'Deleted category: ' . $catName);

    echo "<script>alert('Category deleted successfully'); window.location.href='category.php';</script>";
} else {
    echo "<script>alert('Deletion failed'); window.location.href='category.php';</script>";
}
?>