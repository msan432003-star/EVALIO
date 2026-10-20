<?php
/* api.php - chatbot endpoint. POST: message, profile (public|examinee|examiner), csrf_token. Returns JSON. */
ob_start();

function cb_out($arr, $code = 200) {
    if (!is_array($arr)) {
        $arr = array('reply' => (string)$arr);
    }
    if (!isset($arr['reply']) || !is_string($arr['reply'])) {
        $arr['reply'] = 'Sorry, something went wrong. Please try again.';
    }
    if (!isset($arr['links']) || !is_array($arr['links'])) {
        $arr['links'] = array();
    }
    if (!isset($arr['suggestions']) || !is_array($arr['suggestions'])) {
        $arr['suggestions'] = array();
    }

    // Discard any previous buffer output (notices, stray whitespace, BOM)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    require_once __DIR__ . '/../config/autoload.php';   // starts the session, defines BASE_URL and csrf helpers
    require_once __DIR__ . '/engine.php';

    // Fallback CSRF verification in case csrf_check() is not defined in config/autoload.php
    if (!function_exists('csrf_check')) {
        function csrf_check($token = null) {
            if ($token === null) {
                $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '');
            }
            return !empty($_SESSION['csrf_token']) && hash_equals((string)$_SESSION['csrf_token'], (string)$token);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        cb_out(array('reply' => 'Invalid request.', 'links' => array(), 'suggestions' => array()), 405);
    }

    if (!csrf_check()) {
        cb_out(array('reply' => 'Session expired. Please refresh the page and try again.', 'links' => array(), 'suggestions' => array()), 403);
    }

    $profiles = array('public', 'examinee', 'examiner');
    $profile = isset($_POST['profile']) ? (string)$_POST['profile'] : 'public';
    if (!in_array($profile, $profiles, true)) {
        $profile = 'public';
    }

    // Student answers are only for logged-in examinees
    if ($profile === 'examinee' && empty($_SESSION['student_id'])) {
        $profile = 'public';
    }

    // Rate limit: 15 messages per minute per session
    $now = time();
    $win = (isset($_SESSION['cb_win']) && is_array($_SESSION['cb_win'])) ? $_SESSION['cb_win'] : array();
    $win = array_values(array_filter($win, function ($t) use ($now) { return $t > $now - 60; }));
    if (count($win) >= 15) {
        cb_out(array('reply' => 'You are sending messages too fast. Please wait a moment.', 'links' => array(), 'suggestions' => array()), 429);
    }
    $win[] = $now;
    $_SESSION['cb_win'] = $win;

    $message = mb_substr(trim(isset($_POST['message']) ? (string)$_POST['message'] : ''), 0, 200, 'UTF-8');
    if ($message === '') {
        cb_out(array('reply' => 'Please type your question.', 'links' => array(), 'suggestions' => array()));
    }

    $kb_file = __DIR__ . '/kb/' . $profile . '.php';
    if (!file_exists($kb_file)) {
        cb_out(array(
            'reply' => 'The knowledge base is temporarily unavailable. Please try again later or contact our team.',
            'links' => array(),
            'suggestions' => array()
        ), 200);
    }

    $kb = require $kb_file;
    if (!is_array($kb) || empty($kb['entries'])) {
        cb_out(array(
            'reply' => 'Knowledge base could not be loaded. Please try again later.',
            'links' => array(),
            'suggestions' => array()
        ), 200);
    }

    $base_url = defined('BASE_URL') ? BASE_URL : 'http://localhost/Projectmca25011/';
    $ans = cb_answer($kb, $message, $base_url);
    cb_out($ans);

} catch (Throwable $t) {
    error_log('Chatbot API Error: ' . $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine());
    cb_out(array(
        'reply' => 'Sorry, something went wrong. Please try again.',
        'links' => array(),
        'suggestions' => array()
    ), 500);
}