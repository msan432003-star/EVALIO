<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Exam System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container" style="margin-top:100px;">
    <div class="text-center mb-5">
        <h1>Online Exam System</h1>
        <p class="lead">Certification exams, mock tests and study material in one place.</p>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow p-4 text-center">
                <h4>Examinee</h4>
                <p>Take mock tests and certification exams.</p>
                <a href="login.php" class="btn btn-primary btn-block">Examinee Login</a>
                <a href="registration1.php" class="btn btn-outline-primary btn-block">Examinee Register</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow p-4 text-center">
                <h4>Examiner</h4>
                <p>Create exams, questions and study references.</p>
                <a href="examiner/login.php" class="btn btn-success btn-block">Examiner Login</a>
                <a href="examiner/examiner_registration.php" class="btn btn-outline-success btn-block">Examiner Register</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>