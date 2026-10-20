<?php 

define("BASE_URL", "http://localhost/Projectmca25011/");
define("BASE_PATH", "C:/wamp64/www/Projectmca25011/"); // Updated from xampp/htdocs to wamp64/www

date_default_timezone_set('asia/kolkata');

session_start();

require(BASE_PATH . 'config/database.php'); 
require(BASE_PATH . 'classes/database.php'); 
require(BASE_PATH . 'classes/FormAssist.class.php'); 
require(BASE_PATH . 'classes/FormValidator.class.php'); 
require(BASE_PATH . 'classes/DataAccess.class.php');
require(BASE_PATH . 'classes/Grader.class.php');

if (!function_exists('e')) {
    function e($string) {
        echo htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars((string)csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('id_list')) {
    function id_list($ids) {
        $clean = array();
        foreach ((array)$ids as $id) {
            $clean[] = (int)$id;
        }
        $clean = array_values(array_unique($clean));
        return empty($clean) ? '0' : implode(',', $clean);
    }
}
if (!function_exists('csrf_check')) {
    function csrf_check() {
        $sent = $_POST['csrf_token'] ?? '';
        $real = $_SESSION['csrf_token'] ?? '';
        return ($real !== '' && is_string($sent) && hash_equals($real, $sent));
    }
}

if (!function_exists('evalio_pass_fail_status')) {
    /**
     * Calculates Pass / Fail / Awaiting evaluation status.
     *
     * Rule:
     * - Only results with grading_status='completed' count as Pass/Fail.
     * - Results with 'pending_review' show "Awaiting evaluation" (neither Pass nor Fail).
     * - Guard against total_marks <= 0.
     * - Percentage = (marks_obtained / total_marks) * 100
     * - Pass if percentage >= passing_percentage
     *
     * @param float|int $marks_obtained
     * @param float|int $total_marks
     * @param float|int $passing_percentage (default 50.00)
     * @param string    $grading_status ('completed' or 'pending_review')
     * @return array
     */
    function evalio_pass_fail_status($marks_obtained, $total_marks, $passing_percentage = 50.00, $grading_status = 'completed') {
        $marks_obtained = (float)$marks_obtained;
        $total_marks = (float)$total_marks;
        $passing_percentage = (float)($passing_percentage > 0 ? $passing_percentage : 50.00);
        $grading_status = strtolower(trim((string)$grading_status));

        $percentage = ($total_marks > 0) ? round(($marks_obtained / $total_marks) * 100, 2) : 0.00;

        if ($grading_status === 'pending_review') {
            return array(
                'status'      => 'pending',
                'label'       => 'Awaiting evaluation',
                'badge_class' => 'label-warning',
                'badge_html'  => '<span class="label label-warning" style="font-size:12px; font-weight:600;"><i class="fa fa-clock-o"></i> Awaiting evaluation</span>',
                'percentage'  => $percentage,
                'is_passed'   => null
            );
        }

        if ($total_marks <= 0) {
            return array(
                'status'      => 'unknown',
                'label'       => 'N/A',
                'badge_class' => 'label-default',
                'badge_html'  => '<span class="label label-default">N/A</span>',
                'percentage'  => 0.00,
                'is_passed'   => null
            );
        }

        $is_pass = ($percentage >= $passing_percentage);
        return array(
            'status'      => $is_pass ? 'pass' : 'fail',
            'label'       => $is_pass ? 'Pass' : 'Fail',
            'badge_class' => $is_pass ? 'label-success' : 'label-danger',
            'badge_html'  => $is_pass 
                ? '<span class="label label-success" style="font-size:12px; font-weight:600;"><i class="fa fa-check"></i> Pass</span>'
                : '<span class="label label-danger" style="font-size:12px; font-weight:600;"><i class="fa fa-times"></i> Fail</span>',
            'percentage'  => $percentage,
            'is_passed'   => $is_pass
        );
    }
}
?>