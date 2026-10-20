<?php
/* admin/revenue_report.php - Platform revenue report for the admin
   Level 1: every examiner's total revenue   (default view)
   Level 2: ?examiner_id=N  -> that examiner's revenue per exam (+ daily chart + transactions)
   Level 3: ?examiner_id=N&exam_id=M -> transactions of one exam
   Revenue = paid registrations (amount_paid > 0) for non-mock exams. */
require('../config/autoload.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
/* >>> adjust the session key if your admin login uses a different one <<< */
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$dao = new DataAccess();

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
function rv_date($s) {
    $d = DateTime::createFromFormat('!Y-m-d', (string)$s);
    return ($d && $d->format('Y-m-d') === (string)$s) ? $d : null;
}
function rv_money($n) { return '&#8377;' . number_format((float)$n, 2); }
function rv_csv($v) {   // stop spreadsheet formula injection
    $v = (string)$v;
    return (strlen($v) && strpos("=+-@", $v[0]) !== false) ? "'" . $v : $v;
}
function rv_like($s) {  // escape for use inside LIKE '%...%'
    return str_replace(array('%', '_'), array('\\%', '\\_'), addslashes($s));
}

/* ---------- which level are we on? ---------- */
$examinerId = (int)($_GET['examiner_id'] ?? 0);
$examinerName = '';
$examinerEmail = '';
$drill = false;
if ($examinerId) {
    $row = $dao->query("SELECT e.created_by, COALESCE(ex.name, CONCAT('Unassigned (ID ', e.created_by, ')')) AS name, ex.email
                        FROM exams e LEFT JOIN examiners ex ON ex.id = e.created_by
                        WHERE e.created_by = $examinerId AND LOWER(TRIM(e.category)) <> 'mock' LIMIT 1");
    if (is_array($row) && !empty($row)) {
        $drill = true; $examinerName = $row[0]['name']; $examinerEmail = (string)$row[0]['email'];
    } else {
        header('Location: revenue_report.php'); exit();
    }
}

/* ---------- dropdown data ---------- */
$examIds = array();
if ($drill) {
    $myExams = $dao->query("SELECT id, title FROM exams WHERE created_by = $examinerId AND LOWER(TRIM(category)) <> 'mock' ORDER BY title");
    if (is_array($myExams)) { foreach ($myExams as $e) { $examIds[(int)$e['id']] = $e['title']; } }
    $methodRows = $dao->query("SELECT DISTINCT r.payment_method FROM registrations r JOIN exams e ON e.id = r.exam_id
                               WHERE e.created_by = $examinerId AND r.payment_method IS NOT NULL AND r.payment_method <> '' ORDER BY r.payment_method");
} else {
    $methodRows = $dao->query("SELECT DISTINCT payment_method FROM registrations WHERE payment_method IS NOT NULL AND payment_method <> '' ORDER BY payment_method");
}
$methods = array();
if (is_array($methodRows)) { foreach ($methodRows as $m) { $methods[] = $m['payment_method']; } }

/* ---------- read & validate filters ---------- */
$periods = array(
    'all' => 'All time', 'today' => 'Today', 'yesterday' => 'Yesterday', 'last7' => 'Last 7 days',
    'last30' => 'Last 30 days', 'this_month' => 'This month', 'last_month' => 'Last month',
    'this_year' => 'This year', 'day' => 'Single day', 'custom' => 'Custom range'
);
$period = $_GET['period'] ?? 'all';
if (!isset($periods[$period])) { $period = 'all'; }

$examId = (int)($_GET['exam_id'] ?? 0);
if (!$drill || ($examId && !isset($examIds[$examId]))) { $examId = 0; }

$method = $_GET['method'] ?? '';
if ($method !== '' && !in_array($method, $methods, true)) { $method = ''; }

$q = trim((string)($_GET['q'] ?? ''));
if (strlen($q) > 60) { $q = substr($q, 0, 60); }

$earnOnly = (!$drill && ($_GET['earn'] ?? '') === '1') ? '1' : '';

$sortMapOverview = array(
    'revenue_desc' => 'revenue DESC, name ASC',  'revenue_asc' => 'revenue ASC, name ASC',
    'name_asc'     => 'name ASC',                'name_desc'   => 'name DESC',
    'regs_desc'    => 'regs DESC, name ASC',     'exams_desc'  => 'exams DESC, name ASC'
);
$sortLabelsOverview = array(
    'revenue_desc' => 'Revenue: high to low', 'revenue_asc' => 'Revenue: low to high',
    'name_asc' => 'Name: A to Z', 'name_desc' => 'Name: Z to A',
    'regs_desc' => 'Most registrations', 'exams_desc' => 'Most exams'
);
$sortMapExam = array(
    'revenue_desc' => 'revenue DESC, e.title ASC', 'revenue_asc' => 'revenue ASC, e.title ASC',
    'title_asc'    => 'e.title ASC',               'title_desc'  => 'e.title DESC',
    'regs_desc'    => 'regs DESC, e.title ASC',    'price_desc'  => 'e.price DESC, e.title ASC'
);
$sortLabelsExam = array(
    'revenue_desc' => 'Revenue: high to low', 'revenue_asc' => 'Revenue: low to high',
    'title_asc' => 'Title: A to Z', 'title_desc' => 'Title: Z to A',
    'regs_desc' => 'Most registrations', 'price_desc' => 'Price: high to low'
);
$sortMap = $drill ? $sortMapExam : $sortMapOverview;
$sortLabels = $drill ? $sortLabelsExam : $sortLabelsOverview;
$sort = $_GET['sort'] ?? 'revenue_desc';
if (!isset($sortMap[$sort])) { $sort = 'revenue_desc'; }

$today = new DateTime('today');
$from = null; $to = null;
$dayVal = ''; $fromVal = ''; $toVal = '';
switch ($period) {
    case 'today':      $from = clone $today; $to = clone $today; break;
    case 'yesterday':  $from = (clone $today)->modify('-1 day'); $to = clone $from; break;
    case 'last7':      $from = (clone $today)->modify('-6 days'); $to = clone $today; break;
    case 'last30':     $from = (clone $today)->modify('-29 days'); $to = clone $today; break;
    case 'this_month': $from = new DateTime('first day of this month midnight'); $to = clone $today; break;
    case 'last_month': $from = new DateTime('first day of last month midnight'); $to = new DateTime('last day of last month midnight'); break;
    case 'this_year':  $from = new DateTime($today->format('Y') . '-01-01'); $to = clone $today; break;
    case 'day':
        $from = rv_date($_GET['day'] ?? '') ?: clone $today;
        $to = clone $from; $dayVal = $from->format('Y-m-d'); break;
    case 'custom':
        $from = rv_date($_GET['from'] ?? ''); $to = rv_date($_GET['to'] ?? '');
        if ($from && $to && $from > $to) { $tmp = $from; $from = $to; $to = $tmp; }
        $fromVal = $from ? $from->format('Y-m-d') : ''; $toVal = $to ? $to->format('Y-m-d') : '';
        break;
}

/* ---------- WHERE pieces (every value is validated / cast / escaped above) ---------- */
$cond = " AND LOWER(TRIM(r.payment_status)) = 'paid' AND r.amount_paid > 0";
if ($from) { $cond .= " AND r.registration_date >= '" . $from->format('Y-m-d') . " 00:00:00'"; }
if ($to)   { $cond .= " AND r.registration_date < '" . (clone $to)->modify('+1 day')->format('Y-m-d') . " 00:00:00'"; }
if ($method !== '') { $cond .= " AND r.payment_method = '" . addslashes($method) . "'"; }

if ($drill) {
    $scope = " AND e.created_by = $examinerId" . ($examId ? " AND e.id = $examId" : '');
    if ($q !== '') { $scope .= " AND e.title LIKE '%" . rv_like($q) . "%'"; }
} else {
    $scope = '';
    if ($q !== '') { $scope .= " AND (ex.name LIKE '%" . rv_like($q) . "%' OR ex.email LIKE '%" . rv_like($q) . "%')"; }
}

/* ---------- main table ---------- */
if ($drill) {
    $rows = $dao->query(
        "SELECT e.id, e.title, e.price, COUNT(r.id) AS regs, COALESCE(SUM(r.amount_paid), 0) AS revenue
         FROM exams e
         LEFT JOIN registrations r ON r.exam_id = e.id $cond
         WHERE LOWER(TRIM(e.category)) <> 'mock' $scope
         GROUP BY e.id, e.title, e.price
         ORDER BY " . $sortMap[$sort]
    );
} else {
    $rows = $dao->query(
        "SELECT e.created_by AS id,
                COALESCE(ex.name, CONCAT('Unassigned (ID ', e.created_by, ')')) AS name,
                ex.email AS email,
                COUNT(DISTINCT e.id) AS exams,
                COUNT(DISTINCT CASE WHEN r.id IS NOT NULL THEN e.id END) AS exams_earning,
                COUNT(r.id) AS regs,
                COALESCE(SUM(r.amount_paid), 0) AS revenue
         FROM exams e
         LEFT JOIN examiners ex ON ex.id = e.created_by
         LEFT JOIN registrations r ON r.exam_id = e.id $cond
         WHERE LOWER(TRIM(e.category)) <> 'mock' $scope
         GROUP BY e.created_by, ex.name, ex.email
         " . ($earnOnly ? 'HAVING revenue > 0' : '') . "
         ORDER BY " . $sortMap[$sort]
    );
}
if (!is_array($rows)) { $rows = array(); }

$totalRevenue = 0; $totalRegs = 0; $earning = 0; $examsEarning = 0;
foreach ($rows as $p) {
    $totalRevenue += (float)$p['revenue']; $totalRegs += (int)$p['regs'];
    if ((float)$p['revenue'] > 0) { $earning++; }
    if (!$drill) { $examsEarning += (int)$p['exams_earning']; }
}
$avg = $totalRegs ? $totalRevenue / $totalRegs : 0;

/* ---------- transactions base ---------- */
$txnBase = "FROM registrations r JOIN exams e ON e.id = r.exam_id
            LEFT JOIN examiners ex ON ex.id = e.created_by
            LEFT JOIN examinees s ON s.id = r.student_id
            WHERE LOWER(TRIM(e.category)) <> 'mock' $scope $cond";

/* ---------- CSV export ---------- */
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="admin_revenue_' . ($drill ? 'examiner' . $examinerId : 'examiners') . '_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    if ($drill) {
        fputcsv($out, array('Date', 'Examiner', 'Exam', 'Student', 'Registration No', 'Payment Method', 'Amount (INR)', 'Transaction ID'));
        $all = $dao->query("SELECT r.registration_date, ex.name AS examiner, e.title, s.name AS student, r.registration_no, r.payment_method, r.amount_paid, r.transaction_id
                            $txnBase ORDER BY r.registration_date DESC, r.id DESC");
        if (is_array($all)) {
            foreach ($all as $r) {
                fputcsv($out, array($r['registration_date'], rv_csv($r['examiner'] ?: $examinerName), rv_csv($r['title']), rv_csv($r['student']),
                    $r['registration_no'], $r['payment_method'], $r['amount_paid'], $r['transaction_id']));
            }
        }
        fputcsv($out, array('', '', 'TOTAL', '', '', '', number_format($totalRevenue, 2, '.', ''), ''));
    } else {
        fputcsv($out, array('Examiner', 'Email', 'Exams', 'Paid Registrations', 'Revenue (INR)'));
        foreach ($rows as $p) {
            fputcsv($out, array(rv_csv($p['name']), rv_csv($p['email']), $p['exams'], $p['regs'], number_format((float)$p['revenue'], 2, '.', '')));
        }
        fputcsv($out, array('TOTAL', '', '', $totalRegs, number_format($totalRevenue, 2, '.', '')));
    }
    fclose($out);
    exit();
}

/* ---------- daily revenue (small bar chart) ---------- */
$daily = $dao->query("SELECT DATE(r.registration_date) AS d, SUM(r.amount_paid) AS rev, COUNT(*) AS n
                      $txnBase GROUP BY DATE(r.registration_date) ORDER BY d DESC LIMIT 31");
$daily = is_array($daily) ? array_reverse($daily) : array();
$dailyMax = 0;
foreach ($daily as $d) { $dailyMax = max($dailyMax, (float)$d['rev']); }

/* ---------- transactions (drill-down only, paginated) ---------- */
$txns = array(); $pages = 1; $page = 1;
if ($drill) {
    $perPage = 15;
    $pages = max(1, (int)ceil($totalRegs / $perPage));
    $page = max(1, min($pages, (int)($_GET['page'] ?? 1)));
    $offset = ($page - 1) * $perPage;
    $txns = $dao->query("SELECT r.registration_date, e.title, s.name AS student, r.registration_no, r.payment_method, r.amount_paid, r.transaction_id
                         $txnBase ORDER BY r.registration_date DESC, r.id DESC LIMIT $perPage OFFSET $offset");
    if (!is_array($txns)) { $txns = array(); }
}

/* ---------- labels / links ---------- */
$periodLabel = $periods[$period];
if ($period === 'day' && $from) { $periodLabel = $from->format('d M Y'); }
if (in_array($period, array('today', 'yesterday', 'last7', 'last30', 'this_month', 'last_month', 'this_year'), true) && $from && $to) {
    $periodLabel .= ' (' . $from->format('d M') . ($from == $to ? '' : ' - ' . $to->format('d M Y')) . ')';
}
if ($period === 'custom') {
    $periodLabel = ($from || $to) ? (($from ? $from->format('d M Y') : 'Start') . ' to ' . ($to ? $to->format('d M Y') : 'Today')) : 'Custom range';
}

/* common filters carried across links */
$common = array('period' => $period, 'method' => $method, 'day' => $dayVal, 'from' => $fromVal, 'to' => $toVal);
$qs = $common + array('q' => $q, 'sort' => $sort, 'earn' => $earnOnly);
if ($drill) { $qs += array('examiner_id' => $examinerId, 'exam_id' => $examId ?: ''); }
$exportUrl = '?' . http_build_query($qs + array('export' => 1));
$pageUrl = function ($p) use ($qs) { return '?' . http_build_query($qs + array('page' => $p)); };
// links between levels keep the date/method filters but reset search/sort
$examinerUrl = function ($id) use ($common) { return '?' . http_build_query($common + array('examiner_id' => $id)); };
$examUrl = function ($id) use ($common, $examinerId) { return '?' . http_build_query($common + array('examiner_id' => $examinerId, 'exam_id' => $id)); };
$backAllUrl = '?' . http_build_query($common);
$backExaminerUrl = '?' . http_build_query($common + array('examiner_id' => $examinerId));

$page_title = 'Revenue Report';
include("header.php");
?>
<style>
    .rv-card { 
        background: var(--card-bg, #ffffff); 
        color: var(--card-text, #111827); 
        border: 1px solid var(--table-border-color, #e5e7eb); 
        border-radius: 12px; 
        padding: 20px 24px; 
        margin-bottom: 24px; 
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        transition: background-color 0.25s ease, border-color 0.25s ease;
    }
    .rv-card h4 { 
        margin: 0 0 16px; 
        color: var(--text-primary, #111827); 
        font-weight: 700;
        font-size: 17px;
    }
    .rv-filters { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; }
    .rv-filters .rv-f { display: flex; flex-direction: column; min-width: 170px; }
    .rv-filters label { font-size: 12px; color: var(--text-muted, #64748b); font-weight: 500; margin: 0 0 6px; }
    .rv-filters .form-control { 
        height: 38px; 
        background-color: var(--input-bg, #ffffff);
        color: var(--input-text, #111827);
        border: 1px solid var(--input-border, #d1d5db);
        border-radius: 6px;
    }
    .rv-dates { display: none; gap: 14px; flex-wrap: wrap; }
    .rv-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .rv-stat { 
        background: var(--card-bg, #ffffff); 
        border: 1px solid var(--table-border-color, #e5e7eb); 
        border-radius: 12px; 
        padding: 18px 20px; 
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        transition: all 0.25s ease;
    }
    .rv-stat b { 
        display: block; 
        font-size: 26px; 
        font-weight: 700;
        color: var(--text-primary, #111827); 
        margin-bottom: 4px;
        line-height: 1.2;
    }
    .rv-stat span { 
        font-size: 13px; 
        color: var(--text-muted, #64748b); 
        font-weight: 500;
    }
    .rv-stat.rv-main { 
        border-color: #6366f1; 
        background: linear-gradient(145deg, var(--card-bg, #ffffff), rgba(99, 102, 241, 0.06));
    }
    [data-theme="dark"] .rv-stat.rv-main {
        border-color: #818cf8;
        background: linear-gradient(145deg, var(--card-bg, #18191f), rgba(99, 102, 241, 0.15));
        box-shadow: 0 0 18px rgba(99, 102, 241, 0.22);
    }
    .rv-table { width: 100%; border-collapse: collapse; }
    .rv-table th, .rv-table td { 
        padding: 12px 14px; 
        border-bottom: 1px solid var(--table-border-color, #e5e7eb); 
        text-align: left; 
        vertical-align: middle; 
        color: var(--text-primary, #111827);
    }
    .rv-table th { 
        font-size: 12px; 
        text-transform: uppercase; 
        letter-spacing: .5px; 
        color: var(--text-muted, #64748b); 
        font-weight: 600;
        background: var(--table-th-bg, #f9fafb);
    }
    .rv-table tbody tr:hover {
        background-color: var(--table-row-hover, #f9fafb);
    }
    .rv-table .rv-num { text-align: right; white-space: nowrap; }
    .rv-table tfoot td { 
        font-weight: 700; 
        border-bottom: 0; 
        background: var(--table-th-bg, #f9fafb);
        color: var(--text-primary, #111827);
    }
    .rv-table a.rv-link { 
        font-weight: 600; 
        color: #4f46e5; 
        text-decoration: none; 
    }
    [data-theme="dark"] .rv-table a.rv-link {
        color: #a5b4fc;
    }
    .rv-table a.rv-link:hover { text-decoration: underline; }
    .rv-bar { height: 8px; border-radius: 4px; background: rgba(99, 102, 241, 0.18); min-width: 90px; }
    .rv-bar i { display: block; height: 100%; border-radius: 4px; background: linear-gradient(90deg, #6366f1, #00c6ff); }
    .rv-chart { display: flex; align-items: flex-end; gap: 8px; height: 170px; overflow-x: auto; padding-top: 10px; }
    .rv-col { flex: 0 0 38px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; font-size: 10px; color: var(--text-muted, #64748b); }
    .rv-col i { display: block; width: 100%; border-radius: 4px 4px 0 0; background: linear-gradient(180deg, #00c6ff, #6366f1); min-height: 3px; }
    .rv-col em { font-style: normal; opacity: .8; margin-top: 4px; white-space: nowrap; }
    .rv-muted { color: var(--text-muted, #64748b); }
    .rv-pager { display: flex; gap: 14px; align-items: center; justify-content: center; margin-top: 14px; }
    .rv-empty { text-align: center; padding: 26px 0; color: var(--text-muted, #64748b); }
    .rv-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    .rv-crumb { margin-bottom: 12px; font-size: 13px; color: var(--text-muted, #64748b); }
    .rv-crumb a { color: #4f46e5; text-decoration: none; font-weight: 500; }
    [data-theme="dark"] .rv-crumb a { color: #a5b4fc; }
    .rv-crumb span { opacity: .6; margin: 0 6px; }
    .rv-crumb strong { color: var(--text-primary, #111827); }
</style>

<div class="row">
    <div class="col-md-12">
        <p class="rv-crumb">
            <a href="dashboard.php">&laquo; Dashboard</a><span>/</span>
            <?php if ($drill) { ?>
                <a href="<?= h($backAllUrl); ?>">All examiners</a><span>/</span>
                <?php if ($examId) { ?>
                    <a href="<?= h($backExaminerUrl); ?>"><?= h($examinerName); ?></a><span>/</span><strong><?= h($examIds[$examId]); ?></strong>
                <?php } else { ?><strong><?= h($examinerName); ?></strong><?php } ?>
            <?php } else { ?><strong>All examiners</strong><?php } ?>
        </p>

        <div class="rv-head">
            <div>
                <h2 style="margin-top:0;">Revenue Report<?= $drill ? ' &ndash; ' . h($examinerName) : ''; ?></h2>
                <p class="text-muted">
                    <?php if ($drill) { ?>Earnings per exam<?= $examinerEmail !== '' ? ' for ' . h($examinerEmail) : ''; ?>. Click an exam to see only its transactions.
                    <?php } else { ?>Total earnings of every examiner from paid registrations. Click a name to see their exams.<?php } ?>
                </p>
            </div>
            <a class="btn btn-default" href="<?= h($exportUrl); ?>"><i class="fa fa-download"></i> Export CSV</a>
        </div>
        <hr>

        <!-- Filters -->
        <form method="GET" class="rv-card" id="rvForm">
            <?php if ($drill) { ?><input type="hidden" name="examiner_id" value="<?= $examinerId; ?>"><?php } ?>
            <div class="rv-filters">
                <div class="rv-f">
                    <label for="q"><?= $drill ? 'Search exam' : 'Search examiner'; ?></label>
                    <input type="text" name="q" id="q" class="form-control" maxlength="60" value="<?= h($q); ?>" placeholder="<?= $drill ? 'Exam title' : 'Name or email'; ?>">
                </div>
                <?php if ($drill) { ?>
                <div class="rv-f">
                    <label for="exam_id">Exam</label>
                    <select name="exam_id" id="exam_id" class="form-control">
                        <option value="0">All exams</option>
                        <?php foreach ($examIds as $id => $title) { ?>
                            <option value="<?= $id; ?>" <?= $examId === $id ? 'selected' : ''; ?>><?= h($title); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>
                <div class="rv-f">
                    <label for="period">Period</label>
                    <select name="period" id="period" class="form-control">
                        <?php foreach ($periods as $k => $lbl) { ?>
                            <option value="<?= $k; ?>" <?= $period === $k ? 'selected' : ''; ?>><?= h($lbl); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="rv-dates" id="dayBox">
                    <div class="rv-f"><label for="day">Date</label>
                        <input type="date" name="day" id="day" class="form-control" max="<?= $today->format('Y-m-d'); ?>" value="<?= h($dayVal ?: $today->format('Y-m-d')); ?>"></div>
                </div>
                <div class="rv-dates" id="rangeBox">
                    <div class="rv-f"><label for="from">From</label>
                        <input type="date" name="from" id="from" class="form-control" max="<?= $today->format('Y-m-d'); ?>" value="<?= h($fromVal); ?>"></div>
                    <div class="rv-f"><label for="to">To</label>
                        <input type="date" name="to" id="to" class="form-control" max="<?= $today->format('Y-m-d'); ?>" value="<?= h($toVal); ?>"></div>
                </div>
                <?php if (!empty($methods)) { ?>
                <div class="rv-f">
                    <label for="method">Payment method</label>
                    <select name="method" id="method" class="form-control">
                        <option value="">All methods</option>
                        <?php foreach ($methods as $m) { ?>
                            <option value="<?= h($m); ?>" <?= $method === $m ? 'selected' : ''; ?>><?= h($m); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>
                <div class="rv-f">
                    <label for="sort">Sort by</label>
                    <select name="sort" id="sort" class="form-control">
                        <?php foreach ($sortLabels as $k => $lbl) { ?>
                            <option value="<?= $k; ?>" <?= $sort === $k ? 'selected' : ''; ?>><?= h($lbl); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php if (!$drill) { ?>
                <div class="rv-f">
                    <label for="earn">Show</label>
                    <select name="earn" id="earn" class="form-control">
                        <option value="">All examiners</option>
                        <option value="1" <?= $earnOnly ? 'selected' : ''; ?>>Only with revenue</option>
                    </select>
                </div>
                <?php } ?>
                <div>
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="revenue_report.php<?= $drill ? '?examiner_id=' . $examinerId : ''; ?>" class="btn btn-default">Reset</a>
                </div>
            </div>
        </form>

        <p class="rv-muted">Showing:
            <strong><?= $drill ? h($examinerName) : 'All examiners'; ?></strong>
            <?php if ($examId) { ?>&bull; <strong><?= h($examIds[$examId]); ?></strong><?php } ?>
            &bull; <strong><?= h($periodLabel); ?></strong>
            <?= $method !== '' ? ' &bull; <strong>' . h($method) . '</strong>' : ''; ?>
            <?= $q !== '' ? ' &bull; search &ldquo;<strong>' . h($q) . '</strong>&rdquo;' : ''; ?>
        </p>

        <!-- Summary -->
        <div class="rv-stats">
            <div class="rv-stat rv-main"><b><?= rv_money($totalRevenue); ?></b><span>Total Revenue</span></div>
            <div class="rv-stat"><b><?= $totalRegs; ?></b><span>Paid Registrations</span></div>
            <div class="rv-stat"><b><?= rv_money($avg); ?></b><span>Average per Registration</span></div>
            <?php if ($drill) { ?>
                <div class="rv-stat"><b><?= $earning; ?></b><span>Exams Earning Revenue</span></div>
            <?php } else { ?>
                <div class="rv-stat"><b><?= $earning; ?></b><span>Examiners Earning Revenue</span></div>
                <div class="rv-stat"><b><?= $examsEarning; ?></b><span>Exams Earning Revenue</span></div>
            <?php } ?>
        </div>

        <?php if (empty($rows)) { ?>
            <div class="rv-card"><div class="rv-empty">
                <?= ($q !== '' || $earnOnly || $examId) ? 'Nothing matches the current filters.' : 'No certification exams found, so there is no revenue to show.'; ?>
            </div></div>
        <?php } else { ?>

        <?php if (!$drill) { ?>
        <!-- Level 1: revenue by examiner -->
        <div class="rv-card">
            <h4>Revenue by Examiner</h4>
            <div class="table-responsive">
            <table class="rv-table">
                <thead><tr><th>Examiner</th><th>Email</th><th class="rv-num">Exams</th><th class="rv-num">Registrations</th><th class="rv-num">Revenue</th><th style="width:20%;">Share</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $p) { $pct = $totalRevenue > 0 ? round(((float)$p['revenue'] / $totalRevenue) * 100, 1) : 0; ?>
                    <tr>
                        <td><a class="rv-link" href="<?= h($examinerUrl((int)$p['id'])); ?>"><?= h($p['name']); ?> &rsaquo;</a></td>
                        <td class="rv-muted"><?= h($p['email'] ?: '-'); ?></td>
                        <td class="rv-num"><?= (int)$p['exams']; ?></td>
                        <td class="rv-num"><?= (int)$p['regs']; ?></td>
                        <td class="rv-num"><strong><?= rv_money($p['revenue']); ?></strong></td>
                        <td><div class="rv-bar" title="<?= $pct; ?>%"><i style="width:<?= $pct; ?>%"></i></div><small class="rv-muted"><?= $pct; ?>%</small></td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot><tr><td>Total</td><td></td><td></td><td class="rv-num"><?= $totalRegs; ?></td><td class="rv-num"><?= rv_money($totalRevenue); ?></td><td></td></tr></tfoot>
            </table>
            </div>
        </div>

        <?php } else { ?>
        <!-- Level 2: revenue by exam -->
        <div class="rv-card">
            <h4>Revenue by Exam</h4>
            <div class="table-responsive">
            <table class="rv-table">
                <thead><tr><th>Exam</th><th class="rv-num">Price</th><th class="rv-num">Registrations</th><th class="rv-num">Revenue</th><th style="width:22%;">Share</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $p) { $pct = $totalRevenue > 0 ? round(((float)$p['revenue'] / $totalRevenue) * 100, 1) : 0; ?>
                    <tr>
                        <td><a class="rv-link" href="<?= h($examUrl((int)$p['id'])); ?>"><?= h($p['title']); ?></a></td>
                        <td class="rv-num"><?= rv_money($p['price']); ?></td>
                        <td class="rv-num"><?= (int)$p['regs']; ?></td>
                        <td class="rv-num"><strong><?= rv_money($p['revenue']); ?></strong></td>
                        <td><div class="rv-bar" title="<?= $pct; ?>%"><i style="width:<?= $pct; ?>%"></i></div><small class="rv-muted"><?= $pct; ?>%</small></td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot><tr><td>Total</td><td></td><td class="rv-num"><?= $totalRegs; ?></td><td class="rv-num"><?= rv_money($totalRevenue); ?></td><td></td></tr></tfoot>
            </table>
            </div>
        </div>
        <?php } ?>

        <!-- Daily revenue -->
        <?php if (!empty($daily)) { ?>
        <div class="rv-card">
            <h4>Daily Revenue <small class="rv-muted">(last <?= count($daily); ?> day<?= count($daily) > 1 ? 's' : ''; ?> with sales)</small></h4>
            <div class="rv-chart">
                <?php foreach ($daily as $d) { $ht = $dailyMax > 0 ? max(2, round(((float)$d['rev'] / $dailyMax) * 100)) : 0; ?>
                    <div class="rv-col" title="<?= h(date('d M Y', strtotime($d['d']))); ?>: &#8377;<?= number_format((float)$d['rev'], 2); ?> (<?= (int)$d['n']; ?> reg.)">
                        <i style="height:<?= $ht; ?>%"></i>
                        <em><?= h(date('d M', strtotime($d['d']))); ?></em>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <?php if ($drill) { ?>
        <!-- Transactions -->
        <div class="rv-card">
            <h4>Transactions <small class="rv-muted">(<?= $totalRegs; ?>)</small></h4>
            <?php if (!empty($txns)) { ?>
            <div class="table-responsive">
            <table class="rv-table">
                <thead><tr><th>Date</th><th>Exam</th><th>Student</th><th>Registration No</th><th>Method</th><th>Transaction ID</th><th class="rv-num">Amount</th></tr></thead>
                <tbody>
                <?php foreach ($txns as $t) { ?>
                    <tr>
                        <td><?= !empty($t['registration_date']) ? h(date('d M Y, h:i A', strtotime($t['registration_date']))) : '-'; ?></td>
                        <td><?= h($t['title']); ?></td>
                        <td><?= h($t['student'] ?: '-'); ?></td>
                        <td><?= h($t['registration_no'] ?: '-'); ?></td>
                        <td><?= h($t['payment_method'] ?: '-'); ?></td>
                        <td><?= !empty($t['transaction_id']) ? '<code>' . h($t['transaction_id']) . '</code>' : '-'; ?></td>
                        <td class="rv-num"><strong><?= rv_money($t['amount_paid']); ?></strong></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            </div>
            <?php if ($pages > 1) { ?>
            <div class="rv-pager">
                <?php if ($page > 1) { ?><a class="btn btn-default btn-sm" href="<?= h($pageUrl($page - 1)); ?>">&laquo; Prev</a><?php } ?>
                <span class="rv-muted">Page <?= $page; ?> of <?= $pages; ?></span>
                <?php if ($page < $pages) { ?><a class="btn btn-default btn-sm" href="<?= h($pageUrl($page + 1)); ?>">Next &raquo;</a><?php } ?>
            </div>
            <?php } ?>
            <?php } else { ?>
                <div class="rv-empty">No paid registrations for this filter.</div>
            <?php } ?>
        </div>
        <?php } ?>

        <?php } ?>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('rvForm'),
        period = document.getElementById('period'),
        dayBox = document.getElementById('dayBox'),
        rangeBox = document.getElementById('rangeBox');

    function toggle() {
        dayBox.style.display = period.value === 'day' ? 'flex' : 'none';
        rangeBox.style.display = period.value === 'custom' ? 'flex' : 'none';
    }
    toggle();

    // Dropdowns apply instantly; date-based periods wait for a date / the Apply button
    period.addEventListener('change', function () {
        toggle();
        if (period.value !== 'day' && period.value !== 'custom') { form.submit(); }
    });
    ['exam_id', 'method', 'sort', 'earn'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('change', function () { form.submit(); }); }
    });
    document.getElementById('day').addEventListener('change', function () { form.submit(); });
})();
</script>

<?php include('footer.php'); ?>
