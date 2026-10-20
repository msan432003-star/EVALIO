<?php
/* includes/header.php */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);

function isCurrentPage($pageName, $currentPage) {
    return ($currentPage === $pageName) ? 'class="active"' : '';
}

// Detect logged-in state (examiner / examinee only; admin is intentionally excluded)
$is_logged_in = false;
$dashboard_url = '';
$logout_url = '';
$user_role_label = '';

if (!empty($_SESSION['examiner_id'])) {
    $is_logged_in = true;
    $dashboard_url = '../examiner/dashboard.php';
    $logout_url = '../examiner/logout.php';
    $user_role_label = 'Examiner';
} elseif (!empty($_SESSION['student_id'])) {
    $is_logged_in = true;
    $dashboard_url = 'dashboard.php';
    $logout_url = 'logout.php';
    $user_role_label = 'Examinee';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Evalio - Online Exam & Certification Platform'; ?></title>
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="reg/css/main.css">
    <link rel="stylesheet" href="index/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="index.php" class="logo" aria-label="Evalio"><img src="index/image/Name.png" alt="Evalio" class="logo-img"></a>
        <ul class="nav-links">
            <li><a href="index.php" <?= isCurrentPage('index.php', $currentPage); ?>>Home</a></li>
            <li><a href="exams.php" <?= isCurrentPage('exams.php', $currentPage); ?>>Exams</a></li>
            <li><a href="features.php" <?= isCurrentPage('features.php', $currentPage); ?>>Features</a></li>
            <li><a href="about.php" <?= isCurrentPage('about.php', $currentPage); ?>>About</a></li>
            <li><a href="contact.php" <?= isCurrentPage('contact.php', $currentPage); ?>>Contact Us</a></li>
            <?php if ($is_logged_in): ?>
                <li><a href="<?= $dashboard_url; ?>" class="nav-btn nav-btn-secondary" title="<?= htmlspecialchars($user_role_label); ?> Dashboard"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
                <li><a href="<?= $logout_url; ?>" class="nav-btn nav-btn-primary" title="Logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="nav-btn nav-btn-primary <?= ($currentPage === 'login.php') ? 'active' : ''; ?>" data-auth="login">Login</a></li>
                <li><a href="registration1.php" class="nav-btn nav-btn-secondary <?= ($currentPage === 'registration1.php') ? 'active' : ''; ?>" data-auth="signup">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>