<?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 Global Security Check: Kick them back to login if not logged in
if (!isset($_SESSION['student_id'])) {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='../login.php';</script>";
    exit();
}

// Automatically make $student_id and $student_email available to any file that includes this!
$student_id = $_SESSION['student_id'];
$student_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <!-- Clean Student Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <a class="navbar-brand" href="student_exams.php">Examinee Portal</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link" href="student_exams.php">Available Exams</a></li>
                <li class="nav-item"><a class="nav-link" href="results.php">My Results</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="container">