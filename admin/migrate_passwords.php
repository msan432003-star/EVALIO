<?php
/**
 * One-Click Password Migration Tool
 * Safely converts any existing plain-text passwords into Bcrypt hashes.
 * Requires active Admin session.
 */
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();
$msg = [];
$error = '';

if (isset($_POST['btn_migrate'])) {
    $adminMigrated = 0;
    $examinerMigrated = 0;
    $examineeMigrated = 0;

    // 1. Migrate Admins
    $admins = $dao->getData(['id', 'password'], 'admins', '1');
    if (!empty($admins)) {
        foreach ($admins as $admin) {
            $pwd = $admin['password'];
            // Check if password is already a Bcrypt hash ($2y$, $2a$, $2b$)
            if (!password_get_info($pwd)['algo']) {
                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                $dao->update(['password' => $hash], 'admins', 'id=' . (int)$admin['id']);
                $adminMigrated++;
            }
        }
    }

    // 2. Migrate Examiners
    $examiners = $dao->getData(['id', 'password'], 'examiners', '1');
    if (!empty($examiners)) {
        foreach ($examiners as $ex) {
            $pwd = $ex['password'];
            if (!empty($pwd) && !password_get_info($pwd)['algo']) {
                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                $dao->update(['password' => $hash], 'examiners', 'id=' . (int)$ex['id']);
                $examinerMigrated++;
            }
        }
    }

    // 3. Migrate Examinees
    $examinees = $dao->getData(['id', 'password'], 'examinees', '1');
    if (!empty($examinees)) {
        foreach ($examinees as $em) {
            $pwd = $em['password'];
            if (!empty($pwd) && !password_get_info($pwd)['algo']) {
                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                $dao->update(['password' => $hash], 'examinees', 'id=' . (int)$em['id']);
                $examineeMigrated++;
            }
        }
    }

    $msg[] = "Migration complete: $adminMigrated admin(s), $examinerMigrated examiner(s), and $examineeMigrated examinee(s) upgraded to Bcrypt.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Password Migration - Admin</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body style="background:#f4f6f9; padding: 40px 20px;">
    <div class="container" style="max-width: 650px;">
        <div class="panel panel-default" style="padding: 25px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0;">Password Security Migration</h3>
            <p class="text-muted">This utility safely upgrades any legacy plain-text passwords across Admins, Examiners, and Examinees into secure, one-way Bcrypt hashes using standard PHP <code>password_hash()</code>. Passwords that are already hashed will not be modified.</p>
            
            <?php if (!empty($msg)) { ?>
                <?php foreach ($msg as $m) { ?>
                    <div class="alert alert-success"><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
            <?php } ?>

            <form method="POST">
                <button type="submit" name="btn_migrate" class="btn btn-primary btn-lg" onclick="return confirm('Upgrade all legacy plain-text passwords to Bcrypt?');">
                    Run Password Migration Now
                </button>
                <a href="index.php" class="btn btn-default btn-lg" style="margin-left: 10px;">Back to Dashboard</a>
            </form>
        </div>
    </div>
</body>
</html>
