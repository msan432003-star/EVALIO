<?php 
require('../config/autoload.php');

// Only logged-in examiners can access this page
if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
}

$examiner_id = (int)$_SESSION['examiner_id'];
$dao = new DataAccess();

// This examiner's own exams (for the filter dropdown)
$my_exams = $dao->getData(array('id', 'title'), 'exams', "created_by = " . $examiner_id . " AND category = 'Certification' ORDER BY title");

// Optional filter: one exam only (0 = all exams)
$filter_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$filter_sql = '';
if($filter_exam_id > 0) {
    $filter_sql = ' AND r.exam_id = ' . $filter_exam_id;
}

// Every student who registered AND paid for an exam created by this examiner
$students = $dao->query(
    "SELECT ex.id, ex.name, e.title AS exam_title, r.registration_date
     FROM registrations r
     JOIN examinees ex ON ex.id = r.student_id
     JOIN exams e ON e.id = r.exam_id
     WHERE e.created_by = " . $examiner_id . "
       AND e.category = 'Certification'
       AND r.payment_status = 'Paid'" . $filter_sql . "
     ORDER BY r.registration_date DESC"
);

include("header.php");
?>

<div class="row">
    <div class="col-md-12">
        <h2>Registered Students</h2>
        <hr>

        <form method="GET" class="form-inline" style="margin-bottom:20px;">
            <label for="exam_id" style="margin-right: 8px;">Exam: </label>
            <select name="exam_id" id="exam_id" class="form-control" style="margin-right: 8px; display: inline-block; width: auto;">
                <option value="0">All My Exams</option>
                <?php if(!empty($my_exams)) { foreach($my_exams as $m) { ?>
                    <option value="<?= (int)$m['id']; ?>" <?= $filter_exam_id == $m['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php } } ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <?php if(empty($students)) { ?>
            <div class="alert alert-info">No students have registered and paid for your exams yet.</div>
        <?php } else { ?>
            <p><strong>Total:</strong> <?= count($students); ?></p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Exam Registered</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach($students as $s) { ?>
                            <tr>
                                <td><?= $i++; ?></td>
                                <td><?= (int)$s['id']; ?></td>
                                <td><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($s['exam_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($s['registration_date'])); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</div>

<?php include('footer.php'); ?>