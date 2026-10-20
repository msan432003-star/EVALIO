<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();
date_default_timezone_set('Asia/Kolkata');

$admin_id = (int)($_SESSION['admin_id'] ?? 0);
$admin_name = $_SESSION['name'] ?? '';

// Retrieve admin name from database if not cached in session
if (empty($admin_name) && $admin_id > 0) {
    $adm = $dao->getData('name', 'admins', 'id = ' . $admin_id);
    if (!empty($adm) && !empty($adm[0]['name'])) {
        $admin_name = $adm[0]['name'];
        $_SESSION['name'] = $admin_name;
    } else {
        $admin_name = 'Administrator';
    }
}

// ========================================================
// 10 METRIC CALCULATIONS
// ========================================================

// 1. Certification Exams (Total, Approved, Pending)
$r_cert = $dao->query("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_cnt,
           SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_cnt
    FROM exams
    WHERE category = 'Certification'
");
$cert_total    = (int)($r_cert[0]['total'] ?? 0);
$cert_approved = (int)($r_cert[0]['approved_cnt'] ?? 0);
$cert_pending  = (int)($r_cert[0]['pending_cnt'] ?? 0);

// 2. Mock Exams (Total)
$r_mock = $dao->query("SELECT COUNT(*) as total FROM exams WHERE category = 'Mock'");
$mock_total = (int)($r_mock[0]['total'] ?? 0);

// 3. Active Examinees
$r_ee = $dao->query("SELECT COUNT(*) as total FROM examinees WHERE status = 'active'");
$active_examinees = (int)($r_ee[0]['total'] ?? 0);

// 4. Active Approved Examiners
$r_er = $dao->query("SELECT COUNT(*) as total FROM examiners WHERE status = 'approved' AND account_status = 'active'");
$active_examiners = (int)($r_er[0]['total'] ?? 0);

// 5. Total Revenue (SUM registrations.amount_paid WHERE payment_status='Paid')
$r_rev = $dao->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM registrations WHERE payment_status = 'Paid'");
$revenue = (float)($r_rev[0]['total'] ?? 0.00);

// 6. Overall Pass Rate
// Rule: Pass = (marks_obtained / total_marks * 100) >= exams.passing_percentage
// Only results with grading_status='completed' count. Guard against total_marks <= 0.
$r_results = $dao->query("
    SELECT r.id, r.marks_obtained, r.total_marks, r.grading_status,
           COALESCE(e.passing_percentage, 50.00) as passing_percentage
    FROM exam_results r
    LEFT JOIN exams e ON e.id = r.exam_id
    WHERE r.grading_status = 'completed'
");
$total_evaluated = 0;
$total_passed = 0;
if (!empty($r_results)) {
    foreach ($r_results as $res_row) {
        if ((float)$res_row['total_marks'] > 0) {
            $total_evaluated++;
            $st = evalio_pass_fail_status(
                $res_row['marks_obtained'],
                $res_row['total_marks'],
                $res_row['passing_percentage'],
                $res_row['grading_status']
            );
            if ($st['is_passed'] === true) {
                $total_passed++;
            }
        }
    }
}
$pass_rate = ($total_evaluated > 0) ? round(($total_passed / $total_evaluated) * 100) : 0;

// 7. Total Courses
$r_courses = $dao->query("SELECT COUNT(*) as total FROM courses");
$total_courses = (int)($r_courses[0]['total'] ?? 0);

// 8. Total Categories
$r_cats = $dao->query("SELECT COUNT(*) as total FROM categories");
$total_cats = (int)($r_cats[0]['total'] ?? 0);

// 9. Total Users (examinees + examiners)
$r_all_ee = $dao->query("SELECT COUNT(*) as total FROM examinees");
$all_ee = (int)($r_all_ee[0]['total'] ?? 0);
$r_all_er = $dao->query("SELECT COUNT(*) as total FROM examiners");
$all_er = (int)($r_all_er[0]['total'] ?? 0);
$total_users = $all_ee + $all_er;

// 10. Pending Approvals (pending cert exams + pending examiner signups)
$r_pending_er = $dao->query("SELECT COUNT(*) as total FROM examiners WHERE status = 'pending'");
$pending_examiners = (int)($r_pending_er[0]['total'] ?? 0);
$total_pending = $cert_pending + $pending_examiners;

// ========================================================
// RECENT DATA FOR OVERVIEW TABLES
// ========================================================

// Latest 5 Certification Exams Awaiting Approval
$latest_pending_exams = $dao->query("
    SELECT e.id, e.title, e.created_by, e.submitted_at, e.created_at, e.price,
           ex.name AS examiner_name
    FROM exams e
    LEFT JOIN examiners ex ON ex.id = e.created_by
    WHERE e.category = 'Certification' AND e.status = 'pending'
    ORDER BY COALESCE(e.submitted_at, e.created_at) DESC, e.id DESC
    LIMIT 5
");
if (!is_array($latest_pending_exams)) {
    $latest_pending_exams = array();
}

// Latest 5 Registrations
$latest_registrations = $dao->query("
    SELECT r.id, r.student_id, r.exam_id, r.amount_paid, r.registration_date, r.payment_status,
           em.name AS examinee_name, e.title AS exam_title
    FROM registrations r
    LEFT JOIN examinees em ON em.id = r.student_id
    LEFT JOIN exams e ON e.id = r.exam_id
    ORDER BY r.id DESC
    LIMIT 5
");
if (!is_array($latest_registrations)) {
    $latest_registrations = array();
}

include('header.php');
?>

<style>
    /* Background Constellation Graphic */
    .bg-constellation {
        position: absolute;
        top: 15px;
        right: 20px;
        width: 320px;
        height: 220px;
        pointer-events: none;
        z-index: 1;
        color: #6366f1;
        opacity: 0.25;
        transition: opacity 0.3s ease;
    }
    [data-theme="dark"] .bg-constellation {
        opacity: 0.45;
    }

    /* Dashboard Title & Welcome Banner */
    .dashboard-header {
        position: relative;
        z-index: 2;
        margin-bottom: 24px;
    }
    .dashboard-main-title {
        font-size: 32px;
        font-weight: 800;
        margin: 0 0 6px 0;
        letter-spacing: -0.5px;
        color: var(--text-primary, #0f172a);
    }
    [data-theme="dark"] .dashboard-main-title {
        color: #ffffff;
        display: inline-block;
    }
    .dashboard-welcome-title {
        font-size: 22px;
        font-weight: 400;
        margin: 0;
        color: var(--text-muted, #64748b);
    }
    .dashboard-welcome-title strong {
        font-weight: 700;
        color: var(--text-primary, #0f172a);
    }

    /* 10 Glowing Stat Cards Grid */
    .stat-cards-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 30px;
        position: relative;
        z-index: 2;
    }
    @media (max-width: 1200px) {
        .stat-cards-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 650px) {
        .stat-cards-grid { grid-template-columns: 1fr; }
    }

    .stat-card-link {
        text-decoration: none !important;
        display: block;
        color: inherit;
    }
    .stat-card {
        background: var(--card-body-bg, #ffffff);
        border-radius: 14px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        min-height: 102px;
        box-sizing: border-box;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        border: 1.5px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    .stat-card:hover {
        transform: translateY(-3px);
    }
    [data-theme="dark"] .stat-card {
        background: #0d1527;
    }

    .stat-icon-wrapper {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-right: 14px;
        transition: all 0.3s ease;
    }
    .stat-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .stat-number {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 2px;
        letter-spacing: -0.3px;
        color: var(--text-primary, #0f172a);
    }
    .stat-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary, #1e293b);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .stat-subtitle {
        font-size: 11.5px;
        font-weight: 500;
        color: var(--text-muted, #64748b);
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Individual Card Accents */
    /* Card 1: Cert (Cyan) */
    .card-cert { border-color: rgba(168, 85, 247, 0.45); }
    .card-cert .stat-icon-wrapper { background: rgba(6, 182, 212, 0.15); color: #0891b2; }
    [data-theme="dark"] .card-cert { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-cert .stat-number { color: #00e5ff; }
    [data-theme="dark"] .card-cert .stat-icon-wrapper { background: rgba(0, 210, 255, 0.2); color: #00e5ff; }

    /* Card 2: Mock (Pink) */
    .card-mock { border-color: rgba(168, 85, 247, 0.45); }
    .card-mock .stat-icon-wrapper { background: rgba(236, 72, 153, 0.15); color: #db2777; }
    [data-theme="dark"] .card-mock { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-mock .stat-number { color: #ff2a8d; }
    [data-theme="dark"] .card-mock .stat-icon-wrapper { background: rgba(255, 42, 141, 0.2); color: #ff2a8d; }

    /* Card 3: Active Examinees (Emerald Green) */
    .card-examinees { border-color: rgba(168, 85, 247, 0.45); }
    .card-examinees .stat-icon-wrapper { background: rgba(16, 185, 129, 0.15); color: #059669; }
    [data-theme="dark"] .card-examinees { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-examinees .stat-number { color: #34d399; }
    [data-theme="dark"] .card-examinees .stat-icon-wrapper { background: rgba(16, 185, 129, 0.2); color: #34d399; }

    /* Card 4: Active Examiners (Purple) */
    .card-examiners { border-color: rgba(168, 85, 247, 0.45); }
    .card-examiners .stat-icon-wrapper { background: rgba(168, 85, 247, 0.15); color: #7c3aed; }
    [data-theme="dark"] .card-examiners { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-examiners .stat-number { color: #c084fc; }
    [data-theme="dark"] .card-examiners .stat-icon-wrapper { background: rgba(178, 75, 243, 0.2); color: #c084fc; }

    /* Card 5: Revenue (Gold/Amber) */
    .card-revenue { border-color: rgba(168, 85, 247, 0.45); }
    .card-revenue .stat-icon-wrapper { background: rgba(245, 158, 11, 0.15); color: #d97706; }
    [data-theme="dark"] .card-revenue { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-revenue .stat-number { color: #fde047; }
    [data-theme="dark"] .card-revenue .stat-icon-wrapper { background: rgba(251, 191, 36, 0.2); color: #fde047; }

    /* Card 6: Pass Rate (Royal Blue) */
    .card-passrate { border-color: rgba(168, 85, 247, 0.45); }
    .card-passrate .stat-icon-wrapper { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
    [data-theme="dark"] .card-passrate { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-passrate .stat-number { color: #60a5fa; }
    [data-theme="dark"] .card-passrate .stat-icon-wrapper { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }

    /* Card 7: Total Courses (Teal) */
    .card-courses { border-color: rgba(168, 85, 247, 0.45); }
    .card-courses .stat-icon-wrapper { background: rgba(20, 184, 166, 0.15); color: #0d9488; }
    [data-theme="dark"] .card-courses { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-courses .stat-number { color: #2dd4bf; }
    [data-theme="dark"] .card-courses .stat-icon-wrapper { background: rgba(20, 184, 166, 0.2); color: #2dd4bf; }

    /* Card 8: Categories (Orange) */
    .card-categories { border-color: rgba(168, 85, 247, 0.45); }
    .card-categories .stat-icon-wrapper { background: rgba(249, 115, 22, 0.15); color: #ea580c; }
    [data-theme="dark"] .card-categories { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-categories .stat-number { color: #fb923c; }
    [data-theme="dark"] .card-categories .stat-icon-wrapper { background: rgba(249, 115, 22, 0.2); color: #fb923c; }

    /* Card 9: Total Users (Indigo) */
    .card-users { border-color: rgba(168, 85, 247, 0.45); }
    .card-users .stat-icon-wrapper { background: rgba(99, 102, 241, 0.15); color: #4f46e5; }
    [data-theme="dark"] .card-users { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-users .stat-number { color: #818cf8; }
    [data-theme="dark"] .card-users .stat-icon-wrapper { background: rgba(99, 102, 241, 0.2); color: #818cf8; }

    /* Card 10: Pending (Amber Red) */
    .card-pending { border-color: rgba(168, 85, 247, 0.45); }
    .card-pending .stat-icon-wrapper { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
    [data-theme="dark"] .card-pending { border-color: #b24bf3; box-shadow: 0 0 16px rgba(178, 75, 243, 0.45); }
    [data-theme="dark"] .card-pending .stat-number { color: #f87171; }
    [data-theme="dark"] .card-pending .stat-icon-wrapper { background: rgba(239, 68, 68, 0.2); color: #f87171; }

    /* Overview Panels & Tables */
    .dash-panel {
        background: var(--card-body-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        margin-bottom: 25px;
        overflow: hidden;
    }
    [data-theme="dark"] .dash-panel {
        background: #0d1527;
        border-color: rgba(255,255,255,0.08);
    }
    .dash-panel-heading {
        padding: 14px 20px;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        background: var(--table-header-bg, #f8fafc);
        color: var(--text-primary, #0f172a);
    }
    [data-theme="dark"] .dash-panel-heading {
        background: #111c35;
        border-color: rgba(255,255,255,0.08);
        color: #ffffff;
    }
    .table-dash {
        margin-bottom: 0;
        width: 100%;
    }
    .table-dash > thead > tr > th {
        background: var(--table-header-bg, #f8fafc);
        color: var(--text-muted, #64748b);
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        padding: 10px 14px;
    }
    [data-theme="dark"] .table-dash > thead > tr > th {
        background: #090e1d;
        color: #94a3b8;
        border-color: rgba(255,255,255,0.06);
    }
    .table-dash > tbody > tr > td {
        padding: 12px 14px;
        vertical-align: middle;
        font-size: 13.5px;
        color: var(--text-primary, #1e293b);
        border-top: 1px solid var(--border-color, #e2e8f0);
    }
    [data-theme="dark"] .table-dash > tbody > tr > td {
        color: #f1f5f9;
        border-color: rgba(255,255,255,0.06);
    }
</style>

<!-- Background Constellation Graphic -->
<svg class="bg-constellation" viewBox="0 0 350 250" fill="none" xmlns="http://www.w3.org/2000/svg">
    <line x1="80" y1="20" x2="160" y2="60" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <line x1="160" y1="60" x2="240" y2="30" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <line x1="160" y1="60" x2="210" y2="120" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <line x1="240" y1="30" x2="310" y2="70" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <line x1="210" y1="120" x2="310" y2="70" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <line x1="210" y1="120" x2="280" y2="170" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <line x1="100" y1="150" x2="210" y2="120" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <line x1="280" y1="170" x2="340" y2="140" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <line x1="310" y1="70" x2="340" y2="140" stroke="currentColor" stroke-opacity="0.3" stroke-width="1.2"/>
    <circle cx="80" cy="20" r="3.5" fill="#00e5ff"/>
    <circle cx="160" cy="60" r="4.5" fill="#00d2ff"/>
    <circle cx="240" cy="30" r="4" fill="#a855f7"/>
    <circle cx="210" cy="120" r="5" fill="#ff007f"/>
    <circle cx="310" cy="70" r="4.5" fill="#ec4899"/>
    <circle cx="280" cy="170" r="3.5" fill="#ff6b4a"/>
    <circle cx="100" cy="150" r="4" fill="#00e5ff"/>
    <circle cx="340" cy="140" r="4" fill="#10b981"/>
</svg>

<div class="container-fluid" style="padding: 20px 25px; position: relative;">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h2 class="dashboard-main-title">Admin Dashboard</h2>
        <h3 class="dashboard-welcome-title">Welcome, <strong><?php e($admin_name); ?></strong>!</h3>
    </div>

    <!-- 10 Glowing Stat Cards Grid in Rows of 4 -->
    <div class="stat-cards-grid">
        <!-- 1. Certification Exams -->
        <a href="pending_exams.php" class="stat-card-link">
            <div class="stat-card card-cert">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-certificate"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $cert_total; ?></div>
                    <div class="stat-title">Certification Exams</div>
                    <div class="stat-subtitle"><?= $cert_approved; ?> approved, <?= $cert_pending; ?> pending</div>
                </div>
            </div>
        </a>

        <!-- 2. Mock Exams -->
        <a href="../examinee/student_exams.php" target="_blank" class="stat-card-link">
            <div class="stat-card card-mock">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-pencil-square-o"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $mock_total; ?></div>
                    <div class="stat-title">Mock Exams</div>
                    <div class="stat-subtitle">Practice tests</div>
                </div>
            </div>
        </a>

        <!-- 3. Active Examinees -->
        <a href="examinees.php" class="stat-card-link">
            <div class="stat-card card-examinees">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-graduation-cap"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $active_examinees; ?></div>
                    <div class="stat-title">Active Examinees</div>
                    <div class="stat-subtitle">Registered candidates</div>
                </div>
            </div>
        </a>

        <!-- 4. Active Approved Examiners -->
        <a href="approved_examiners.php" class="stat-card-link">
            <div class="stat-card card-examiners">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $active_examiners; ?></div>
                    <div class="stat-title">Approved Examiners</div>
                    <div class="stat-subtitle">Active accounts</div>
                </div>
            </div>
        </a>

        <!-- 5. Total Revenue -->
        <a href="revenue_report.php" class="stat-card-link">
            <div class="stat-card card-revenue">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-inr"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">&#8377;<?= number_format($revenue, 2); ?></div>
                    <div class="stat-title">Total Revenue</div>
                    <div class="stat-subtitle">Paid registrations</div>
                </div>
            </div>
        </a>

        <!-- 6. Overall Pass Rate -->
        <a href="student_exams.php" class="stat-card-link">
            <div class="stat-card card-passrate">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-trophy"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $pass_rate; ?>%</div>
                    <div class="stat-title">Overall Pass Rate</div>
                    <div class="stat-subtitle"><?= $total_passed; ?> passed of <?= $total_evaluated; ?> evaluated</div>
                </div>
            </div>
        </a>

        <!-- 7. Total Courses -->
        <a href="course.php" class="stat-card-link">
            <div class="stat-card card-courses">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-book"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $total_courses; ?></div>
                    <div class="stat-title">Total Courses</div>
                    <div class="stat-subtitle">Curriculum subjects</div>
                </div>
            </div>
        </a>

        <!-- 8. Total Categories -->
        <a href="category.php" class="stat-card-link">
            <div class="stat-card card-categories">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-tags"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $total_cats; ?></div>
                    <div class="stat-title">Total Categories</div>
                    <div class="stat-subtitle">Academic branches</div>
                </div>
            </div>
        </a>

        <!-- 9. Total Users -->
        <a href="manage_users.php" class="stat-card-link">
            <div class="stat-card card-users">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-address-book-o"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $total_users; ?></div>
                    <div class="stat-title">Total Users</div>
                    <div class="stat-subtitle"><?= $all_ee; ?> examinees, <?= $all_er; ?> examiners</div>
                </div>
            </div>
        </a>

        <!-- 10. Pending Approvals -->
        <a href="pending_exams.php" class="stat-card-link">
            <div class="stat-card card-pending">
                <div class="stat-icon-wrapper">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= $total_pending; ?></div>
                    <div class="stat-title">Pending Approvals</div>
                    <div class="stat-subtitle"><?= $cert_pending; ?> exams, <?= $pending_examiners; ?> examiners</div>
                </div>
            </div>
        </a>
    </div>

    <!-- ======================================================== -->
    <!-- OVERVIEW TABLES: 2-COLUMN LAYOUT -->
    <!-- ======================================================== -->
    <div class="row">
        <!-- Table 1: Latest 5 Certification Exams Awaiting Approval -->
        <div class="col-md-6 col-sm-12">
            <div class="dash-panel">
                <div class="dash-panel-heading">
                    <div>
                        <i class="fa fa-clock-o text-warning" style="margin-right: 6px;"></i>
                        Awaiting Approval (Latest 5)
                    </div>
                    <div>
                        <a href="pending_exams.php" class="btn btn-xs btn-default" style="font-weight: 600;">View All</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-dash table-hover">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Examiner</th>
                                <th>Submitted</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($latest_pending_exams)) { ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted" style="padding: 24px;">
                                        <i class="fa fa-check-circle text-success" style="font-size: 20px; vertical-align: middle; margin-right: 6px;"></i>
                                        No certification exams awaiting approval.
                                    </td>
                                </tr>
                            <?php } else { 
                                foreach ($latest_pending_exams as $pe) {
                                    $sub_dt = !empty($pe['submitted_at']) ? $pe['submitted_at'] : $pe['created_at'];
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php e($pe['title']); ?></strong>
                                        <div style="font-size: 11px; color: var(--text-muted);">ID: #<?= (int)$pe['id']; ?></div>
                                    </td>
                                    <td><?php e($pe['examiner_name'] ?? 'Examiner #' . $pe['created_by']); ?></td>
                                    <td style="font-size: 12px; color: var(--text-muted);">
                                        <?= !empty($sub_dt) ? date('d M Y', strtotime($sub_dt)) : '-'; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="pending_exams.php" class="btn btn-info btn-xs" style="padding: 3px 8px; font-weight: 600;">
                                            <i class="fa fa-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Table 2: Latest 5 Exam Registrations -->
        <div class="col-md-6 col-sm-12">
            <div class="dash-panel">
                <div class="dash-panel-heading">
                    <div>
                        <i class="fa fa-shopping-cart text-success" style="margin-right: 6px;"></i>
                        Recent Registrations (Latest 5)
                    </div>
                    <div>
                        <a href="revenue_report.php" class="btn btn-xs btn-default" style="font-weight: 600;">View Report</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-dash table-hover">
                        <thead>
                            <tr>
                                <th>Examinee</th>
                                <th>Exam</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($latest_registrations)) { ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted" style="padding: 24px;">
                                        No recent registrations found.
                                    </td>
                                </tr>
                            <?php } else { 
                                foreach ($latest_registrations as $reg) {
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php e(!empty($reg['examinee_name']) ? $reg['examinee_name'] : 'Student #' . $reg['student_id']); ?></strong>
                                    </td>
                                    <td>
                                        <span><?php e($reg['exam_title'] ?? 'Exam #' . $reg['exam_id']); ?></span>
                                    </td>
                                    <td>
                                        <strong style="color: #059669;">&#8377;<?= number_format((float)($reg['amount_paid'] ?? 0), 2); ?></strong>
                                    </td>
                                    <td style="font-size: 12px; color: var(--text-muted);">
                                        <?= !empty($reg['registration_date']) ? date('d M Y', strtotime($reg['registration_date'])) : '-'; ?>
                                    </td>
                                </tr>
                            <?php } } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>