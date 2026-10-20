<?php
/**
 * Examinee Authentication Helper
 * Manages student session verification, safe input validation, and access control.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Enforce active examinee login session.
 * If student is not authenticated, redirects to the examinee login page.
 * Returns the active student_id integer.
 */
if (!function_exists('require_login')) {
    function require_login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['student_id'])) {
            header('Location: login.php');
            exit();
        }
        return (int)$_SESSION['student_id'];
    }
}

/**
 * Validates whether an email string is strictly valid and safe.
 */
if (!function_exists('safe_email')) {
    function safe_email($email) {
        return filter_var(trim((string)$email), FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Check if the student is currently authenticated.
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['student_id']);
    }
}

/**
 * Get the current student's ID, or 0 if not logged in.
 */
if (!function_exists('current_student_id')) {
    function current_student_id() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : 0;
    }
}

/**
 * Get the current student's email.
 */
if (!function_exists('current_student_email')) {
    function current_student_email() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';
    }
}

/**
 * Get the current student's display name.
 */
if (!function_exists('current_student_name')) {
    function current_student_name() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['name']) ? (string)$_SESSION['name'] : (isset($_SESSION['student_name']) ? (string)$_SESSION['student_name'] : 'Examinee');
    }
}