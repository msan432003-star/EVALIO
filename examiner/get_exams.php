<?php
require('../config/autoload.php');


if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    exit();
}
$dao = new DataAccess();

if(isset($_POST['course_id'])) {
    $course_id = (int)$_POST['course_id']; // SQL Injection fix via casting
    $examiner_id = (int)$_SESSION['examiner_id'];
    $selected_exam = isset($_POST['selected_exam']) ? (int)$_POST['selected_exam'] : '';
    
    // Fetch only exams that are NOT Mock tests AND were created by the logged-in examiner
    $exams = $dao->getData('*', 'exams', 'course_id = ' . $course_id . ' AND category != "Mock" AND created_by = ' . $examiner_id);
    
    echo '<option value="">Select Exam</option>';
    if(!empty($exams)) {
        foreach($exams as $e) {
            $sel = ($selected_exam == $e['id']) ? 'selected' : '';
            echo '<option value="'.$e['id'].'" '.$sel.'>'.htmlspecialchars($e['title']).' ('.htmlspecialchars($e['category']).')</option>';
        }
    } else {
        echo '<option value="">You have no certification exams for this course</option>';
    }
}
?>