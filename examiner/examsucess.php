<?php 
require('../config/autoload.php');

// 🔒 Only logged-in examiners can access this page
if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
} 
include("header.php"); 
?> 

<html> 
<head> </head> 
<body> 
<div class="container" style="margin-top:80px; text-align:center;">
    <div class="card shadow" style="padding: 40px; border-radius: 10px; background-color: #f9f9f9;">
        <h1 class="text-success" style="margin-bottom: 20px;">🎉 Exam Creation Successful!</h1>
        <p class="lead">Your exam and all its questions have been successfully saved to the system.</p>
        <hr style="margin: 30px 0;">
        
        <div style="margin-top: 20px;">
            <a href="addcertexam.php" class="btn btn-primary btn-lg" style="margin-right: 15px;">Create Another Certification Exam</a>
            <a href="addmockexam.php" class="btn btn-info btn-lg">Create Another Mock Exam</a>
        </div>
    </div>
</div>
</body> 
</html>