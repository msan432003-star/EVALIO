<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$ex = $id > 0 ? $dao->query(
    "SELECT ex.*, c.name AS category_name,
            p.degree_name, p.designation, p.specialization, p.institution, p.year_passed,
            p.experience_years, p.city, p.linkedin_url, p.about
     FROM examiners ex
     LEFT JOIN examiner_profiles p ON p.examiner_id = ex.id
     LEFT JOIN categories c ON c.id = ex.category_id
     WHERE ex.id = " . $id
) : array();

if (empty($ex) || !is_array($ex)) {
    header('Location: approved_examiners.php');
    exit();
}
$ex = $ex[0];

$certs = $dao->query("SELECT title, issuer, file_path, uploaded_at FROM examiner_certificates WHERE examiner_id = $id ORDER BY id");
if (!is_array($certs)) { $certs = array(); }

$exams = $dao->query("SELECT id, title, exam_type, category, status, created_at FROM exams WHERE created_by = $id ORDER BY created_at DESC");
if (!is_array($exams)) { $exams = array(); }

include("header.php");
if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

function dash($v) { return ($v === null || $v === '') ? '-' : h($v); }

$photo = '';
if (!empty($ex['profile_photo'])) {
    if (is_file(__DIR__ . '/../uploads/profile/' . $ex['profile_photo'])) {
        $photo = '../uploads/profile/' . rawurlencode($ex['profile_photo']);
    } elseif (is_file(__DIR__ . '/../examinee/uploads/profile/' . $ex['profile_photo'])) {
        $photo = '../examinee/uploads/profile/' . rawurlencode($ex['profile_photo']);
    }
}

$statusClass = array('approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger');
$sc = isset($statusClass[$ex['status']]) ? $statusClass[$ex['status']] : 'default';
?>

<div class="container mt-5">
    <a href="approved_examiners.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Approved Examiners</a>
    
    <h2>Examiner Details</h2>
    <p class="text-muted">Comprehensive profile, academic qualifications, and examination activity.</p>
    <hr>

    <!-- Examiner Summary Header -->
    <div class="panel panel-default">
        <div class="panel-body" style="padding: 24px;">
            <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                <div style="flex: 0 0 auto;">
                    <?php if ($photo) { ?>
                        <a href="<?= $photo; ?>" target="_blank" title="Click to view full photo">
                            <img src="<?= $photo; ?>" alt="Examiner Photo" style="width: 105px; height: 105px; border-radius: 50%; object-fit: cover; border: 3px solid #6366f1; box-shadow: 0 4px 14px rgba(0,0,0,0.25);">
                        </a>
                    <?php } else { ?>
                        <img src="assets/img/find_user.png" alt="No Photo" style="width: 105px; height: 105px; border-radius: 50%; object-fit: cover; border: 3px solid var(--panel-default-border); background: var(--panel-default-head-bg);">
                    <?php } ?>
                </div>
                <div style="flex: 1 1 300px;">
                    <h2 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: var(--text-primary);"><?= h($ex['name']); ?></h2>
                    <p style="margin: 0 0 12px 0; font-size: 14px; color: var(--text-muted);">
                        <?= !empty($ex['designation']) ? '<strong style="color: var(--text-primary);">' . h($ex['designation']) . '</strong> &bull; ' : ''; ?>
                        <span><?= h($ex['email']); ?></span>
                    </p>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span class="label label-<?= $sc; ?>"><i class="fa fa-check-circle"></i> <?= h(ucfirst($ex['status'])); ?></span>
                        <span class="label label-<?= $ex['account_status'] === 'disabled' ? 'danger' : 'success'; ?>"><i class="fa fa-user"></i> Account: <?= h(ucfirst($ex['account_status'])); ?></span>
                        <?php if (!empty($ex['category_name'])) { ?>
                            <span class="label label-info"><i class="fa fa-tag"></i> <?= h($ex['category_name']); ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Personal Details -->
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><i class="fa fa-user-circle-o" style="margin-right: 8px; color: var(--text-sidebar-icon);"></i><strong>Personal Details</strong></div>
                <div class="panel-body">
                    <div class="info-kv-table">
                        <div class="info-kv-row">
                            <span class="info-kv-label">Full Name</span>
                            <span class="info-kv-value"><?= dash($ex['name']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">Email Address</span>
                            <span class="info-kv-value"><?= dash($ex['email']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">Phone Number</span>
                            <span class="info-kv-value"><?= dash($ex['phone']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">City</span>
                            <span class="info-kv-value"><?= dash($ex['city']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">Registered On</span>
                            <span class="info-kv-value"><?= !empty($ex['created_at']) ? h(date('d M Y, h:i A', strtotime($ex['created_at']))) : '-'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Details -->
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><i class="fa fa-graduation-cap" style="margin-right: 8px; color: var(--text-sidebar-icon);"></i><strong>Professional Details</strong></div>
                <div class="panel-body">
                    <div class="info-kv-table">
                        <div class="info-kv-row">
                            <span class="info-kv-label">Assigned Category</span>
                            <span class="info-kv-value"><?= dash($ex['category_name']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">Highest Degree</span>
                            <span class="info-kv-value"><?= dash($ex['degree_name']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">Designation</span>
                            <span class="info-kv-value"><?= dash($ex['designation']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">Specialization</span>
                            <span class="info-kv-value"><?= dash($ex['specialization']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">College / University</span>
                            <span class="info-kv-value"><?= dash($ex['institution']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">Year of Passing</span>
                            <span class="info-kv-value"><?= dash($ex['year_passed']); ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">Experience</span>
                            <span class="info-kv-value"><?= $ex['experience_years'] !== null ? (int)$ex['experience_years'] . ' year(s)' : '-'; ?></span>
                        </div>
                        <div class="info-kv-row">
                            <span class="info-kv-label">LinkedIn Profile</span>
                            <span class="info-kv-value">
                                <?php if (!empty($ex['linkedin_url']) && preg_match('#^https?://#i', $ex['linkedin_url'])) { ?>
                                    <a href="<?= h($ex['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--text-active-menu); text-decoration: none;"><i class="fa fa-external-link"></i> View Profile</a>
                                <?php } else { echo '-'; } ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Bio -->
    <div class="panel panel-default">
        <div class="panel-heading"><i class="fa fa-info-circle" style="margin-right: 8px; color: var(--text-sidebar-icon);"></i><strong>About</strong></div>
        <div class="panel-body" style="font-size: 14.5px; line-height: 1.7;">
            <?= !empty($ex['about']) ? nl2br(h($ex['about'])) : '<span class="text-muted">No personal bio provided.</span>'; ?>
        </div>
    </div>

    <!-- Certificates -->
    <div class="panel panel-default">
        <div class="panel-heading"><i class="fa fa-certificate" style="margin-right: 8px; color: var(--text-sidebar-icon);"></i><strong>Certificates (<?= count($certs); ?>)</strong></div>
        <div class="panel-body">
            <?php if (!empty($certs)) { foreach ($certs as $cert) { ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--table-border);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa fa-file-pdf-o" style="color: #ef4444; font-size: 18px;"></i>
                        <span style="font-weight: 600; color: var(--text-primary);"><?= h($cert['title']); ?></span>
                        <?= !empty($cert['issuer']) ? '<span class="text-muted">&bull; ' . h($cert['issuer']) . '</span>' : ''; ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <span class="text-muted" style="font-size: 13px;"><?= h(date('d M Y', strtotime($cert['uploaded_at']))); ?></span>
                        <a href="../uploads/certificates/<?= rawurlencode($cert['file_path']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-xs" style="font-weight: 600;"><i class="fa fa-eye"></i> View</a>
                    </div>
                </div>
            <?php } } else { echo '<span class="text-muted">No certificates uploaded.</span>'; } ?>
        </div>
    </div>

    <!-- Exams created -->
    <div class="panel panel-default">
        <div class="panel-heading"><i class="fa fa-file-text-o" style="margin-right: 8px; color: var(--text-sidebar-icon);"></i><strong>Exams Created (<?= count($exams); ?>)</strong></div>
        <?php if (!empty($exams)) { ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                <?php $n = 1; foreach ($exams as $e) { ?>
                    <tr>
                        <td><?= $n++; ?></td>
                        <td><strong><?= h($e['title']); ?></strong></td>
                        <td><?= h($e['category']); ?></td>
                        <td><?= h($e['exam_type']); ?></td>
                        <td><span class="label label-<?= ($e['status'] === 'approved' || $e['status'] === 'active') ? 'success' : (($e['status'] === 'pending') ? 'warning' : 'default'); ?>"><?= h(ucfirst($e['status'])); ?></span></td>
                        <td><?= h(date('d M Y', strtotime($e['created_at']))); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } else { ?>
            <div class="panel-body"><span class="text-muted">No exams created yet.</span></div>
        <?php } ?>
    </div>
</div>

<?php include('footer.php'); ?>
