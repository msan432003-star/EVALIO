<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;
$alertMsg = '';

// Handle approve / reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    $statuses = array('approve' => 'approved', 'reject' => 'rejected');

    if ($id > 0 && isset($statuses[$action])) {
        $updateData = array('status' => $statuses[$action]);

        // On approval the admin confirms the category (this is what limits the examiner's courses)
        if ($action == 'approve') {
            // The category the examiner chose at registration is final; the admin can only pick one
            // for old accounts that have none.
            $cur = $dao->getData(array('category_id'), 'examiners', 'id=' . $id);
            if (!empty($cur) && (int)$cur[0]['category_id'] > 0) {
                $cid = (int)$cur[0]['category_id'];
            } else {
                $cid = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
            }
            $okCat = ($cid > 0) ? $dao->getData(array('id'), 'categories', 'id = ' . $cid) : array();
            if (empty($okCat)) {
                $alertMsg = 'Please choose a category before approving';
                $updateData = null;
            } else {
                $updateData['category_id'] = $cid;
            }
        }
        // only pending examiners can be changed
        $condition = "id=" . $id . " AND status='pending'";

        if ($updateData === null) {
            // category missing - message already set above
        } elseif ($dao->update($updateData, 'examiners', $condition)) {
            $alertMsg = ($action == 'approve') ? 'Examiner Approved Successfully' : 'Examiner Rejected';

            // Audit log: approve_examiner / reject_examiner
            $ex = $dao->getData(array('name', 'email'), 'examiners', 'id=' . $id);
            $who = !empty($ex) ? $ex[0]['name'] . ' (' . $ex[0]['email'] . ')' : '';
            logAction($admin_id, $action . '_examiner', 'examiners', $id, $who);
        } else {
            $alertMsg = 'Action failed or examiner already processed';
        }
    }
}

include("header.php");

if ($alertMsg != '') {
    echo "<script> alert('" . $alertMsg . "'); window.location.href='approve_examiner.php'; </script>";
}

// Pending examiners together with their qualification details and category
$rows = $dao->query(
    "SELECT ex.id, ex.name, ex.email, ex.status, ex.created_at, ex.category_id,
            c.name AS category_name,
            p.degree_name, p.specialization, p.institution, p.year_passed, p.experience_years, p.about
     FROM examiners ex
     LEFT JOIN examiner_profiles p ON p.examiner_id = ex.id
     LEFT JOIN categories c ON c.id = ex.category_id
     WHERE ex.status = 'pending'
     ORDER BY ex.created_at ASC"
);
if (!is_array($rows)) { $rows = array(); }

$categories = $dao->getData('*', 'categories', '1=1 ORDER BY name');
if (!is_array($categories)) { $categories = array(); }

// Certificates of those examiners
$certs = array();
if (!empty($rows)) {
    $ids = array();
    foreach ($rows as $r) { $ids[] = (int)$r['id']; }
    $certRows = $dao->query("SELECT examiner_id, title, issuer, file_path FROM examiner_certificates WHERE examiner_id IN (" . implode(',', $ids) . ") ORDER BY id");
    if (is_array($certRows)) {
        foreach ($certRows as $cr) { $certs[(int)$cr['examiner_id']][] = $cr; }
    }
}
?>

<div class="container mt-5">
    <h2>Examiner Approvals</h2>
    <p class="text-muted">Check each examiner's qualifications and certificates, then approve or reject.</p>
    <hr>

    <?php if (!empty($rows)) { foreach ($rows as $row) {
        $id = (int)$row['id'];
        $hasProfile = !empty($row['degree_name']);
        ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><?= h($row['name']); ?></strong>
                &nbsp;&bull;&nbsp; <?= h($row['email']); ?>
                <span class="label label-warning" style="margin-left:8px;"><?= h($row['status']); ?></span>
                <span class="pull-right text-muted">Registered: <?= !empty($row['created_at']) ? h(date('d M Y', strtotime($row['created_at']))) : '-'; ?></span>
            </div>
            <div class="panel-body">
                <?php if ($hasProfile) { ?>
                    <dl class="dl-horizontal" style="margin-bottom:0;">
                        <dt>Highest degree</dt>
                        <dd><?= h($row['degree_name']); ?></dd>
                        <dt>Category</dt>
                        <dd><?= !empty($row['category_name']) ? '<span class="label label-info">' . h($row['category_name']) . '</span>' : '-'; ?></dd>
                        <dt>Specialization</dt>
                        <dd><?= !empty($row['specialization']) ? h($row['specialization']) : '-'; ?></dd>
                        <dt>College / University</dt>
                        <dd><?= h($row['institution']); ?></dd>
                        <dt>Year of passing</dt>
                        <dd><?= (int)$row['year_passed']; ?></dd>
                        <dt>Experience</dt>
                        <dd><?= (int)$row['experience_years']; ?> year(s)</dd>
                        <dt>About</dt>
                        <dd><?= !empty($row['about']) ? nl2br(h($row['about'])) : '-'; ?></dd>
                        <dt>Certificates</dt>
                        <dd>
                            <?php if (!empty($certs[$id])) { foreach ($certs[$id] as $cert) { ?>
                                <div>
                                    <i class="fa fa-file-text-o"></i>
                                    <a href="../uploads/certificates/<?= rawurlencode($cert['file_path']); ?>" target="_blank" rel="noopener"><?= h($cert['title']); ?></a>
                                    <?= !empty($cert['issuer']) ? '<span class="text-muted">&mdash; ' . h($cert['issuer']) . '</span>' : ''; ?>
                                </div>
                            <?php } } else { echo '<span class="text-muted">None uploaded</span>'; } ?>
                        </dd>
                    </dl>
                <?php } else { ?>
                    <p class="text-muted" style="margin:0;">No qualification details were submitted (this examiner registered before the qualification step existed).</p>
                <?php } ?>
            </div>
            <div class="panel-footer">
                <form method="GET" action="approve_examiner.php" class="form-inline" style="display:inline;" onsubmit="return confirm('Approve this examiner?');">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="id" value="<?= $id; ?>">
                    <?php if (!empty($row['category_id'])) { ?>
                        <label style="margin-right:6px;">Category:</label>
                        <span class="label label-info" style="margin-right:12px; font-size:12px;"><?= h($row['category_name']); ?></span>
                    <?php } else { ?>
                        <label style="margin-right:6px;">Category:</label>
                        <select name="category_id" class="form-control input-sm" required style="margin-right:8px;">
                            <option value="">-- choose --</option>
                            <?php foreach ($categories as $cat) { ?>
                                <option value="<?= (int)$cat['id']; ?>"><?= h($cat['name']); ?></option>
                            <?php } ?>
                        </select>
                    <?php } ?>
                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                </form>
                <a href="view_examiner.php?id=<?= $id; ?>" class="btn btn-primary btn-sm">View</a>
                <a href="approve_examiner.php?action=reject&id=<?= $id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this examiner?');">Reject</a>
            </div>
        </div>
    <?php } } else { ?>
        <div class="alert alert-info">No pending examiner requests found.</div>
    <?php } ?>
</div>

<?php include('footer.php'); ?>
