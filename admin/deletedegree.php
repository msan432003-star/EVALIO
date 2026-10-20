<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: degree.php'); exit; }

$deg = $dao->getData(array('id', 'name', 'category_id'), 'degrees', 'id=' . $id);
if (empty($deg)) {
    echo "<script>alert('Degree not found'); window.location.href='degree.php';</script>";
    exit;
}
$back = 'degree.php?cat=' . (int)$deg[0]['category_id'];
$degName = $deg[0]['name'];

// Block delete if any examiner registered with this degree
$used = $dao->getData(array('id'), 'examiner_profiles', 'degree_id=' . $id);
if (!empty($used)) {
    echo "<script>alert('Examiners are registered with this degree, so it cannot be deleted'); window.location.href='" . $back . "';</script>";
    exit;
}

if ($dao->delete('degrees', 'id=' . $id)) {
    // Audit log
    logAction($admin_id, 'delete_degree', 'degrees', $id, 'Deleted degree: ' . $degName);

    echo "<script>alert('Degree deleted successfully'); window.location.href='" . $back . "';</script>";
} else {
    echo "<script>alert('Deletion failed'); window.location.href='" . $back . "';</script>";
}
?>
