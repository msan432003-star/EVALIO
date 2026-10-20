<?php
/* ev_helpers.php - shared helpers for the new examinee pages.
   Uses the connection settings already defined in config/database.php
   ($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME) and mysqli prepared statements.
   Does NOT touch DataAccess or any existing logic. */

// Phone rule. NOTE: set this to EXACTLY the pattern used in registration1.php.
if (!defined('EV_PHONE_PATTERN')) { define('EV_PHONE_PATTERN', '/^[6-9][0-9]{9}$/'); }
// Same pass mark as results.php
if (!defined('EV_PASS_PERCENT')) { define('EV_PASS_PERCENT', 60); }
if (!defined('EV_DEFAULT_AVATAR')) { define('EV_DEFAULT_AVATAR', 'assets/img/find_user.png'); }

function ev_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function ev_db() {
    static $conn = null;
    if ($conn !== null) { return $conn; }
    global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME;
    try {
        $c = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
        if ($c->connect_errno) { $conn = false; return false; }
        $c->set_charset('utf8mb4');
        $conn = $c;
    } catch (Throwable $e) {
        $conn = false;
    }
    return $conn;
}

/* SELECT with prepared statement. Returns array of rows, or false on failure. */
function ev_query($sql, $types = '', $params = array()) {
    $db = ev_db();
    if (!$db) { return false; }
    try {
        $st = $db->prepare($sql);
        if (!$st) { return false; }
        if ($types !== '') { $st->bind_param($types, ...$params); }
        if (!$st->execute()) { $st->close(); return false; }
        $res = $st->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
        $st->close();
        return $rows;
    } catch (Throwable $e) {
        return false;
    }
}

/* INSERT/UPDATE/DELETE with prepared statement. Returns affected rows, or false on failure. */
function ev_exec($sql, $types = '', $params = array()) {
    $db = ev_db();
    if (!$db) { return false; }
    try {
        $st = $db->prepare($sql);
        if (!$st) { return false; }
        if ($types !== '') { $st->bind_param($types, ...$params); }
        $ok = $st->execute();
        $n = $st->affected_rows;
        $st->close();
        return $ok ? $n : false;
    } catch (Throwable $e) {
        return false;
    }
}

/* Same rule as results.php: mock exams are hidden from results/certificates */
function ev_is_mock($category) {
    return strtolower(trim((string)$category)) === 'mock';
}

/* Same total / obtained / pass logic as results.php */
function ev_result_info($r) {
    $pending  = (isset($r['grading_status']) && $r['grading_status'] === 'pending_review');
    $total    = (float)(isset($r['total_marks']) && $r['total_marks'] > 0 ? $r['total_marks'] : $r['total_questions']);
    $obtained = (float)(isset($r['marks_obtained']) ? $r['marks_obtained'] : $r['score']);
    $pct      = ($total > 0) ? ($obtained / $total) * 100 : 0;
    $passed   = (!$pending && $pct >= EV_PASS_PERCENT);
    return array('pending' => $pending, 'total' => $total, 'obtained' => $obtained, 'pct' => $pct, 'passed' => $passed);
}

/* Logged-in user's photo URL (relative to examinee/), or the default avatar */
function ev_photo_url($sid = null) {
    static $cache = array();
    if ($sid === null) { $sid = isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : 0; }
    $sid = (int)$sid;
    if ($sid <= 0) { return EV_DEFAULT_AVATAR; }
    if (isset($cache[$sid])) { return $cache[$sid]; }
    $url = EV_DEFAULT_AVATAR;
    $rows = ev_query('SELECT profile_photo FROM examinees WHERE id = ?', 'i', array($sid)); // false if column not added yet
    if (!empty($rows) && !empty($rows[0]['profile_photo'])) {
        $f = $rows[0]['profile_photo'];
        if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $f) && is_file(__DIR__ . '/uploads/profile/' . $f)) {
            $url = 'uploads/profile/' . $f;
        }
    }
    return $cache[$sid] = $url;
}

/* Create uploads/profile with script-blocking files if missing */
function ev_ensure_upload_dir() {
    $dir = __DIR__ . '/uploads/profile/';
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    if (is_dir($dir)) {
        if (!is_file($dir . 'index.html')) { @file_put_contents($dir . 'index.html', '<!doctype html><title>403</title>'); }
        if (!is_file($dir . '.htaccess')) {
            @file_put_contents($dir . '.htaccess', "Options -Indexes -ExecCGI\n<FilesMatch \"\\.(php|phtml|php[0-9]|phar|pl|py|cgi|sh|html?)$\">\n    Require all denied\n</FilesMatch>\n");
        }
    }
    return $dir;
}

function ev_status_badge($status) {
    $s = strtolower(trim((string)$status));
    if ($s === 'paid' || $s === 'success' || $s === 'completed') { return '<span class="ev-badge ev-badge-ok">Paid</span>'; }
    if ($s === 'pending') { return '<span class="ev-badge ev-badge-warn">Pending</span>'; }
    if ($s === 'failed' || $s === 'fail') { return '<span class="ev-badge ev-badge-bad">Failed</span>'; }
    return '<span class="ev-badge ev-badge-muted">' . ev_h($status !== '' ? $status : 'Unknown') . '</span>';
}