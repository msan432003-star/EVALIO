<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();

// Every action the system can log, so the dropdown is always complete
// even before an action has happened for the first time.
$knownActions = array(
    // Exams
    'approve_exam',
    // Examiners
    'approve_examiner', 'reject_examiner', 'activate_examiner', 'deactivate_examiner',
    // Students (examinees)
    'activate_examinee', 'deactivate_examinee',
    // Categories
    'add_category', 'edit_category', 'delete_category',
    // Degrees
    'add_degree', 'edit_degree', 'delete_degree',
    // Courses
    'add_course', 'edit_course', 'delete_course',
);

// Also include any other action already present in the log table
// (e.g. one added later that is not in the list above).
$allActions = $dao->getData(array('action'), 'audit_logs', '1=1');
$actionOptions = array_flip($knownActions);
if (is_array($allActions)) {
    foreach ($allActions as $a) {
        if (!empty($a['action'])) {
            $actionOptions[$a['action']] = true;
        }
    }
}
$actionOptions = array_keys($actionOptions);
sort($actionOptions);

// Optional filters. addslashes is used here to match the rest of the codebase
// (course.php / editcourse.php / login.php all escape the same way).
$where = array('1=1');

if (!empty($_GET['action_filter'])) {
    // Exact match, since the value now comes from the dropdown
    $where[] = "action = '" . addslashes($_GET['action_filter']) . "'";
}
if (!empty($_GET['date_filter'])) {
    $where[] = "DATE(created_at) = '" . addslashes($_GET['date_filter']) . "'";
}

$condition = implode(' AND ', $where);

$logs = $dao->getData(
    array('id', 'admin_id', 'action', 'target_table', 'target_id', 'notes', 'created_at'),
    'audit_logs',
    $condition
);

$logs = is_array($logs) ? array_reverse($logs) : array();  // newest first

// Resolve admin names separately - getData() has no JOIN support,
// that only exists in selectAsTable().
$adminNames = array();
try {
    $admins = $dao->getData(array('id', 'name'), 'admins', '1=1');
    if (is_array($admins)) {
        foreach ($admins as $a) {
            $adminNames[$a['id']] = $a['name'];
        }
    }
} catch (Throwable $e) {
    // admins table has no 'name' column - fall back to showing the id
}

$selectedAction = isset($_GET['action_filter']) ? $_GET['action_filter'] : '';

include('header.php');
?>
<div class="container" style="margin-top:20px;">
    <h3>Audit Log</h3>

    <form method="GET" class="form-inline" style="margin-bottom:15px;">
        <select name="action_filter" class="form-control">
            <option value="">All actions</option>
            <?php foreach ($actionOptions as $act): ?>
                <option value="<?php echo h($act); ?>" <?php echo ($selectedAction === $act) ? 'selected' : ''; ?>>
                    <?php echo h(ucwords(str_replace('_', ' ', $act))); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_filter" class="form-control"
               value="<?php echo isset($_GET['date_filter']) ? h($_GET['date_filter']) : ''; ?>">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="view_audit_log.php" class="btn btn-default">Reset</a>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date/Time</th><th>Admin</th><th>Action</th>
                <th>Target Table</th><th>Target ID</th><th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6">No audit log entries found.</td></tr>
            <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo h($log['created_at']); ?></td>
                    <td><?php
                        $aid = $log['admin_id'];
                        echo h(isset($adminNames[$aid]) ? $adminNames[$aid] : $aid);
                    ?></td>
                    <td><?php echo h($log['action']); ?></td>
                    <td><?php echo h($log['target_table']); ?></td>
                    <td><?php echo h($log['target_id']); ?></td>
                    <td><?php echo h($log['notes']); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php include('footer.php'); ?>