<?php
require('../config/autoload.php');
require('auth_helper.php');
$student_id = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: feedback.php');
    exit();
}

$feedback_id = isset($_POST['feedback_id']) ? (int)$_POST['feedback_id'] : 0;
if ($feedback_id <= 0) {
    header('Location: feedback.php?msg=delete_err');
    exit();
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if (!$conn) {
    header('Location: feedback.php?msg=delete_err');
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE feedback SET is_deleted = 1 WHERE id = ? AND student_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $feedback_id, $student_id);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    if ($affected > 0) {
        header('Location: feedback.php?msg=deleted');
        exit();
    } else {
        header('Location: feedback.php?msg=delete_err');
        exit();
    }
} else {
    mysqli_close($conn);
    header('Location: feedback.php?msg=delete_err');
    exit();
}
