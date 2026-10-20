<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: approve_examiner.php'); exit; }

$rows = $dao->query(
    "SELECT ex.id, ex.name, ex.email, ex.status, ex.account_status, ex.created_at, ex.category_id,
            c.name AS category_name,
            p.degree_id, p.degree_name, p.specialization, p.institution, p.year_passed, p.experience_years, p.about
     FROM examiners ex
     LEFT JOIN examiner_profiles p ON p.examiner_id = ex.id
     LEFT JOIN categories c ON c.id = ex.category_id
     WHERE ex.id = " . $id . " LIMIT 1"
);
if (!is_array($rows) || empty($rows)) {
    echo "<script>alert('Examiner not found'); window.location.href='approve_examiner.php';</script>";
    exit;
}
$e = $rows[0];

$certs = $dao->query("SELECT title, issuer, file_path FROM examiner_certificates WHERE examiner_id = " . $id . " ORDER BY id");
if (!is_array($certs)) { $certs = array(); }

$hasProfile = !empty($e['degree_name']);
$statusClass = array('pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger');
$sc = isset($statusClass[$e['status']]) ? $statusClass[$e['status']] : 'default';

include("header.php");
?>

<div class="container mt-5">
    <a href="approve_examiner.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Approvals</a>

    <h2>Examiner Details</h2>
    <hr>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Account</strong></div>
        <div class="panel-body">
            <dl class="dl-horizontal" style="margin-bottom:0;">
                <dt>Name</dt>
                <dd><?= h($e['name']); ?></dd>
                <dt>Email</dt>
                <dd><?= h($e['email']); ?></dd>
                <dt>Approval status</dt>
                <dd><span class="label label-<?= $sc; ?>"><?= h($e['status']); ?></span></dd>
                <dt>Account</dt>
                <dd><?= h($e['account_status'] ?? '-'); ?></dd>
                <dt>Registered on</dt>
                <dd><?= !empty($e['created_at']) ? h(date('d M Y, h:i A', strtotime($e['created_at']))) : '-'; ?></dd>
                <dt>Category</dt>
                <dd><?= !empty($e['category_name']) ? '<span class="label label-info">' . h($e['category_name']) . '</span>' : '-'; ?></dd>
            </dl>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Qualifications</strong></div>
        <div class="panel-body">
            <?php if ($hasProfile) { ?>
                <dl class="dl-horizontal" style="margin-bottom:0;">
                    <dt>Highest degree</dt>
                    <dd><?= h($e['degree_name']); ?><?= empty($e['degree_id']) ? ' <span class="text-muted">(typed by examiner, not in the degree list)</span>' : ''; ?></dd>
                    <dt>Specialization</dt>
                    <dd><?= !empty($e['specialization']) ? h($e['specialization']) : '-'; ?></dd>
                    <dt>College / University</dt>
                    <dd><?= h($e['institution']); ?></dd>
                    <dt>Year of passing</dt>
                    <dd><?= (int)$e['year_passed']; ?></dd>
                    <dt>Experience</dt>
                    <dd><?= (int)$e['experience_years']; ?> year(s)</dd>
                    <dt>About</dt>
                    <dd><?= !empty($e['about']) ? nl2br(h($e['about'])) : '-'; ?></dd>
                </dl>
            <?php } else { ?>
                <p class="text-muted" style="margin:0;">No qualification details were submitted (this examiner registered before the qualification step existed).</p>
            <?php } ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Certificates</strong></div>
        <div class="panel-body">
            <?php if (!empty($certs)) { ?>
                <table class="table table-bordered table-striped" style="margin-bottom:0;">
                    <thead><tr><th>#</th><th>Title</th><th>Issued by</th><th>File</th></tr></thead>
                    <tbody>
                    <?php $i = 1; foreach ($certs as $c) { ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= h($c['title']); ?></td>
                            <td><?= !empty($c['issuer']) ? h($c['issuer']) : '-'; ?></td>
                            <td><a href="../uploads/certificates/<?= rawurlencode($c['file_path']); ?>" target="_blank" rel="noopener"><i class="fa fa-file-text-o"></i> Open</a></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <span class="text-muted">None uploaded</span>
            <?php } ?>
        </div>
    </div>

    <?php if ($e['status'] === 'pending') { ?>
    <div class="panel panel-default">
        <div class="panel-footer">
            <form method="GET" action="approve_examiner.php" class="form-inline" style="display:inline;" onsubmit="return confirm('Approve this examiner?');">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" value="<?= $id; ?>">
                <?php if (empty($e['category_id'])) {
                    $allCats = $dao->getData('*', 'categories', '1=1 ORDER BY name');
                    if (!is_array($allCats)) { $allCats = array(); } ?>
                    <label style="margin-right:6px;">Category:</label>
                    <select name="category_id" class="form-control input-sm" required style="margin-right:8px;">
                        <option value="">-- choose --</option>
                        <?php foreach ($allCats as $cat) { ?>
                            <option value="<?= (int)$cat['id']; ?>"><?= h($cat['name']); ?></option>
                        <?php } ?>
                    </select>
                <?php } ?>
                <button type="submit" class="btn btn-success">Approve</button>
            </form>
            <a href="approve_examiner.php?action=reject&id=<?= $id; ?>" class="btn btn-danger" onclick="return confirm('Reject this examiner?');">Reject</a>
        </div>
    </div>
    <?php } ?>

    <a href="approve_examiner.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Go Back</a>
</div>

<?php include('footer.php'); ?>
