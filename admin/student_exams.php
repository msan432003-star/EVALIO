<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();
$alertMsg = '';

// Toggle an exam's approval status (approved / rejected)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    $statuses = array('enable' => 'approved', 'disable' => 'rejected');

    if ($id > 0 && isset($statuses[$action])) {
        $updateData = array('status' => $statuses[$action]);
        $condition = 'id=' . $id;

        if ($dao->update($updateData, 'exams', $condition)) {
            $alertMsg = ($action == 'enable') ? 'Exam approved and enabled.' : 'Exam rejected and disabled.';
        } else {
            $alertMsg = 'Action failed.';
        }
    }
}

include('header.php');

if ($alertMsg != '') {
    echo "<script> alert('" . addslashes($alertMsg) . "'); window.location.href='student_exams.php'; </script>";
}

// Fetch courses to display course_name instead of raw integer ID
$coursesRaw = $dao->getData(array('id', 'course_name'), 'courses', '1=1');
$coursesMap = array();
if (!empty($coursesRaw)) {
    foreach ($coursesRaw as $c) {
        $coursesMap[$c['id']] = $c['course_name'];
    }
}

// Fetch exams using real schema columns: id, title, course_id, status, exam_type, duration_minutes
$fields = array('id', 'title', 'course_id', 'status', 'exam_type', 'duration_minutes');
$rows = $dao->getData($fields, 'exams', '1=1 ORDER BY id DESC');
?>

<div class="container" style="margin-top:20px;">
    <h2>Exams Management</h2>
    <hr>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Exam Title</th>
                <th>Course</th>
                <th>Type</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if (!empty($rows)) {
            $i = 1;
            foreach ($rows as $row) {
                $id = (int)$row['id'];
                $status = strtolower((string)$row['status']);
                $isApproved = ($status === 'approved');
                $courseName = $coursesMap[$row['course_id']] ?? 'General (#' . $row['course_id'] . ')';
                
                $badgeClass = 'secondary';
                if ($status === 'approved') $badgeClass = 'success';
                elseif ($status === 'rejected') $badgeClass = 'danger';
                elseif ($status === 'pending') $badgeClass = 'warning';

                echo "<tr>";
                echo "<td>" . $i++ . "</td>";
                echo "<td><strong>" . h($row['title']) . "</strong></td>";
                echo "<td>" . h($courseName) . "</td>";
                echo "<td>" . h(ucfirst($row['exam_type'] ?? 'Standard')) . "</td>";
                echo "<td>" . (int)$row['duration_minutes'] . " mins</td>";
                echo "<td><span class='label label-" . $badgeClass . "'>" . h(ucfirst($status)) . "</span></td>";
                echo "<td>";
                if ($isApproved) {
                    echo "<a href='student_exams.php?action=disable&id=" . $id . "' class='btn btn-warning btn-sm' onclick=\"return confirm('Disable/Reject this exam?');\">Disable</a>";
                } else {
                    echo "<a href='student_exams.php?action=enable&id=" . $id . "' class='btn btn-success btn-sm' onclick=\"return confirm('Approve/Enable this exam?');\">Approve</a>";
                }
                echo " <a href='view_exam_questions.php?exam_id=" . $id . "' class='btn btn-info btn-sm'>Questions</a>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7' class='text-center'>No exams found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<?php include('footer.php'); ?>