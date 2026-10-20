<?php
require('../config/autoload.php');
require('auth.php'); // only a logged-in admin can delete
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;

// Tables that reference a course: 'table' => 'column holding the course id'.
// EDIT THESE to match your real database columns. A course used by any of these
// cannot be deleted (protects exams/results from being orphaned).
$linked = array(
    'exams' => 'course_id',
);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: course.php');
    exit;
}

// Make sure the course exists (also grab its name for the audit log)
$course = $dao->getData(array('id', 'course_name'), 'courses', 'id=' . $id);
if (empty($course)) {
    echo "<script> alert('Course not found'); window.location.href='course.php';</script>";
    exit;
}
$courseName = $course[0]['course_name'];

// Block delete if the course is in use
foreach ($linked as $table => $column) {
    try {
        $used = $dao->getData(array('id'), $table, $column . '=' . $id);
    } catch (Throwable $e) {
        $used = array(); // table/column not found - skip this check
    }
    if (!empty($used)) {
        echo "<script> alert('This course is used by existing " . $table . " and cannot be deleted'); window.location.href='course.php';</script>";
        exit;
    }
}

$condition = 'id=' . $id;

// Remove this course's category links first
$dao->delete('course_categories', 'course_id=' . $id);

if ($dao->delete('courses', $condition)) {
    // Audit log
    logAction($admin_id, 'delete_course', 'courses', $id, 'Deleted course: ' . $courseName);

    echo "<script> alert('Course deleted successfully'); window.location.href='course.php';</script>";
} else {
    echo "<script> alert('Deletion failed'); window.location.href='course.php';</script>";
}
?>
