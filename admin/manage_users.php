<?php
require('../config/autoload.php');
require('auth.php');          // starts session, redirects if not logged in, defines h()
require_once('audit_log.php');

$dao = new DataAccess();

$admin_id = $_SESSION['admin_id'];

// Handle activate / deactivate toggle
if (isset($_GET['action'], $_GET['id'], $_GET['type'])) {
    $id     = (int) $_GET['id'];
    $type   = $_GET['type'];   // 'examinee' or 'examiner'
    $action = $_GET['action']; // 'activate' or 'deactivate'

    $newStatus = ($action === 'activate') ? 'active' : 'disabled';

    if ($id > 0 && ($action === 'activate' || $action === 'deactivate')) {
        if ($type === 'examinee') {
            $dao->update(array('status' => $newStatus), 'examinees', 'id=' . $id);
            logAction($admin_id, $action . '_examinee', 'examinees', $id);
        } elseif ($type === 'examiner') {
            $dao->update(array('account_status' => $newStatus), 'examiners', 'id=' . $id);
            logAction($admin_id, $action . '_examiner', 'examiners', $id);
        }
    }

    $tab = ($type === 'examiner') ? 'examiner' : 'examinee';
    echo "<script>alert('User status updated.'); window.location='manage_users.php?tab=" . $tab . "';</script>";
    exit;
}

// Newest first is done in PHP so we don't assume the DAO supports ORDER BY
$examinees = $dao->getData(array('id', 'name', 'email', 'status'), 'examinees', '1=1');
$examiners = $dao->getData(array('id', 'name', 'email', 'status', 'account_status'), 'examiners', '1=1');

$examinees = is_array($examinees) ? array_reverse($examinees) : array();
$examiners = is_array($examiners) ? array_reverse($examiners) : array();

$activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'examiner') ? 'examiner' : 'examinee';

include('header.php');
?>
<style>
    .panel-heading{
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: rgba(74, 0, 245, 0.87);
    border: rgba(86, 0, 245, 0.87);
    font-size: 35px;
   
}</style>
<div class="container" style="margin-top:20px;">
<div class="panel-heading">Manage Users</div>
    <ul class="nav nav-tabs" style="margin-bottom:15px;">
        <li class="<?php echo $activeTab === 'examinee' ? 'active' : ''; ?>">
            <a href="manage_users.php?tab=examinee">Students (Examinees)</a>
        </li>
        <li class="<?php echo $activeTab === 'examiner' ? 'active' : ''; ?>">
            <a href="manage_users.php?tab=examiner">Examiners</a>
        </li>
    </ul>

    <?php if ($activeTab === 'examinee'): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if (empty($examinees)): ?>
                    <tr><td colspan="5">No students found.</td></tr>
                <?php else: foreach ($examinees as $u): ?>
                    <tr>
                        <td><?php echo h($u['id']); ?></td>
                        <td><?php echo h($u['name']); ?></td>
                        <td><?php echo h($u['email']); ?></td>
                        <td><?php echo h($u['status']); ?></td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <a href="manage_users.php?action=deactivate&type=examinee&id=<?php echo (int)$u['id']; ?>"
                                   class="btn btn-sm btn-warning"
                                   onclick="return confirm('Deactivate this student?');">Deactivate</a>
                            <?php else: ?>
                                <a href="manage_users.php?action=activate&type=examinee&id=<?php echo (int)$u['id']; ?>"
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Reactivate this student?');">Activate</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Approval Status</th><th>Account Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if (empty($examiners)): ?>
                    <tr><td colspan="6">No examiners found.</td></tr>
                <?php else: foreach ($examiners as $u): ?>
                    <tr>
                        <td><?php echo h($u['id']); ?></td>
                        <td><?php echo h($u['name']); ?></td>
                        <td><?php echo h($u['email']); ?></td>
                        <td><?php echo h($u['status']); ?></td>
                        <td><?php echo h($u['account_status']); ?></td>
                        <td>
                            <?php if ($u['account_status'] === 'active'): ?>
                                <a href="manage_users.php?action=deactivate&type=examiner&id=<?php echo (int)$u['id']; ?>"
                                   class="btn btn-sm btn-warning"
                                   onclick="return confirm('Deactivate this examiner?');">Deactivate</a>
                            <?php else: ?>
                                <a href="manage_users.php?action=activate&type=examiner&id=<?php echo (int)$u['id']; ?>"
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Reactivate this examiner?');">Activate</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php include('footer.php'); ?>