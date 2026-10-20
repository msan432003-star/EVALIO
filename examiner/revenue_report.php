<?php
/* examiner/revenue_report.php - Revenue report for the logged-in examiner
   Revenue = paid registrations (amount_paid > 0) for exams created by this examiner. */
require('../config/autoload.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['examiner_id'])) { header('Location: login.php'); exit(); }

$dao = new DataAccess();
$eid = (int)$_SESSION['examiner_id'];

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

/* ---------- this examiner's (non-mock) exams + payment methods, for the dropdowns ---------- */
$myExams = $dao->query("SELECT id, title FROM exams WHERE created_by = $eid AND LOWER(TRIM(category)) <> 'mock' ORDER BY title");
if (!is_array($myExams)) { $myExams = array(); }
$examIds = array();
foreach ($myExams as $e) { $examIds[(int)$e['id']] = $e['title']; }

$methodRows = $dao->query("SELECT DISTINCT r.payment_method FROM registrations r JOIN exams e ON e.id = r.exam_id
                           WHERE e.created_by = $eid AND r.payment_method IS NOT NULL AND r.payment_method <> '' ORDER BY r.payment_method");
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
if ($examId && !isset($examIds[$examId])) { $examId = 0; }          // not this examiner's exam -> ignore

$method = $_GET['method'] ?? '';
if ($method !== '' && !in_array($method, $methods, true)) { $method = ''; }

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

/* ---------- WHERE pieces (all values are validated / cast above) ---------- */
$cond = " AND LOWER(TRIM(r.payment_status)) = 'paid' AND r.amount_paid > 0";
if ($from) { $cond .= " AND r.registration_date >= '" . $from->format('Y-m-d') . " 00:00:00'"; }
if ($to)   { $cond .= " AND r.registration_date < '" . (clone $to)->modify('+1 day')->format('Y-m-d') . " 00:00:00'"; }
if ($method !== '') { $cond .= " AND r.payment_method = '" . addslashes($method) . "'"; }
$examCond = $examId ? " AND e.id = $examId" : '';

/* ---------- per-exam revenue ---------- */
$perExam = $dao->query(
    "SELECT e.id, e.title, e.price, COUNT(r.id) AS regs, COALESCE(SUM(r.amount_paid), 0) AS revenue
     FROM exams e
     LEFT JOIN registrations r ON r.exam_id = e.id $cond
     WHERE e.created_by = $eid AND LOWER(TRIM(e.category)) <> 'mock' $examCond
     GROUP BY e.id, e.title, e.price
     ORDER BY revenue DESC, e.title ASC"
);
if (!is_array($perExam)) { $perExam = array(); }

$totalRevenue = 0; $totalRegs = 0; $earning = 0;
foreach ($perExam as $p) {
    $totalRevenue += (float)$p['revenue']; $totalRegs += (int)$p['regs'];
    if ((float)$p['revenue'] > 0) { $earning++; }
}
$avg = $totalRegs ? $totalRevenue / $totalRegs : 0;

/* ---------- CSV export ---------- */
$txnBase = "FROM registrations r JOIN exams e ON e.id = r.exam_id
            WHERE e.created_by = $eid AND LOWER(TRIM(e.category)) <> 'mock' $examCond $cond";
if (isset($_GET['export'])) {
    $all = $dao->query("SELECT r.registration_date, e.title, r.registration_no, r.payment_method, r.amount_paid, r.transaction_id
                        $txnBase ORDER BY r.registration_date DESC, r.id DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="revenue_report_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Date', 'Exam', 'Registration No', 'Payment Method', 'Amount (INR)', 'Transaction ID'));
    if (is_array($all)) {
        foreach ($all as $r) {
            fputcsv($out, array($r['registration_date'], rv_csv($r['title']), $r['registration_no'], $r['payment_method'], $r['amount_paid'], $r['transaction_id']));
        }
    }
    fputcsv($out, array('', 'TOTAL', '', '', number_format($totalRevenue, 2, '.', ''), ''));
    fclose($out);
    exit();
}

/* ---------- daily revenue (for the small bar chart) ---------- */
$daily = $dao->query("SELECT DATE(r.registration_date) AS d, SUM(r.amount_paid) AS rev, COUNT(*) AS n
                      $txnBase GROUP BY DATE(r.registration_date) ORDER BY d DESC LIMIT 31");
$daily = is_array($daily) ? array_reverse($daily) : array();
$dailyMax = 0;
foreach ($daily as $d) { $dailyMax = max($dailyMax, (float)$d['rev']); }

/* ---------- transactions (paginated) ---------- */
$perPage = 15;
$pages = max(1, (int)ceil($totalRegs / $perPage));
$page = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;
$txns = $dao->query("SELECT r.registration_date, e.title, r.registration_no, r.payment_method, r.amount_paid, r.transaction_id
                     $txnBase ORDER BY r.registration_date DESC, r.id DESC LIMIT $perPage OFFSET $offset");
if (!is_array($txns)) { $txns = array(); }

/* ---------- labels / links ---------- */
$periodLabel = $periods[$period];
if ($period === 'day' && $from) { $periodLabel = $from->format('d M Y'); }
if (in_array($period, array('today', 'yesterday', 'last7', 'last30', 'this_month', 'last_month', 'this_year'), true) && $from && $to) {
    $periodLabel .= ' (' . $from->format('d M') . ($from == $to ? '' : ' - ' . $to->format('d M Y')) . ')';
}
if ($period === 'custom') {
    $periodLabel = ($from || $to) ? (($from ? $from->format('d M Y') : 'Start') . ' to ' . ($to ? $to->format('d M Y') : 'Today')) : 'Custom range';
}
$examLabel = $examId ? $examIds[$examId] : 'All exams';

$qs = array('exam_id' => $examId ?: '', 'period' => $period, 'method' => $method, 'day' => $dayVal, 'from' => $fromVal, 'to' => $toVal);
$exportUrl = '?' . http_build_query($qs + array('export' => 1));
$pageUrl = function ($p) use ($qs) { return '?' . http_build_query($qs + array('page' => $p)); };

include("header.php");
?>
<style>
    .rv-card { background:var(--card-bg,#fff); color:var(--card-text,#333); border:1px solid var(--table-border-color,#ddd); border-radius:10px; padding:18px 22px; margin-bottom:22px; }
    .rv-card h4 { margin:0 0 14px; color:var(--text-primary,#222); }
    .rv-filters { display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; }
    .rv-filters .rv-f { display:flex; flex-direction:column; min-width:170px; }
    .rv-filters label { font-size:12px; opacity:.75; margin:0 0 4px; }
    .rv-filters .form-control { height:38px; }
    .rv-dates { display:none; gap:14px; flex-wrap:wrap; }
    .rv-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:22px; }
    .rv-stat { background:var(--card-bg,#fff); border:1px solid var(--table-border-color,#ddd); border-radius:10px; padding:16px 20px; }
    .rv-stat b { display:block; font-size:24px; color:var(--text-primary,#222); }
    .rv-stat span { font-size:12px; opacity:.75; }
    .rv-stat.rv-main { border-color:#6d5dfc; }
    .rv-table { width:100%; border-collapse:collapse; }
    .rv-table th, .rv-table td { padding:10px 12px; border-bottom:1px solid var(--table-border-color,#ddd); text-align:left; vertical-align:middle; }
    .rv-table th { font-size:12px; text-transform:uppercase; letter-spacing:.4px; opacity:.8; }
    .rv-table .rv-num { text-align:right; white-space:nowrap; }
    .rv-table tfoot td { font-weight:700; border-bottom:0; }
    .rv-bar { height:8px; border-radius:4px; background:rgba(109,93,252,.18); min-width:90px; }
    .rv-bar i { display:block; height:100%; border-radius:4px; background:linear-gradient(90deg,#6d5dfc,#00c6ff); }
    .rv-chart { display:flex; align-items:flex-end; gap:8px; height:170px; overflow-x:auto; padding-top:10px; }
    .rv-col { flex:0 0 38px; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%; font-size:10px; }
    .rv-col i { display:block; width:100%; border-radius:4px 4px 0 0; background:linear-gradient(180deg,#00c6ff,#6d5dfc); min-height:3px; }
    .rv-col em { font-style:normal; opacity:.7; margin-top:4px; white-space:nowrap; }
    .rv-muted { opacity:.7; }
    .rv-pager { display:flex; gap:14px; align-items:center; justify-content:center; margin-top:14px; }
    .rv-empty { text-align:center; padding:26px 0; opacity:.7; }
    .rv-head { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
</style>

<div class="row">
    <div class="col-md-12">
        <p><a href="dashboard.php">&laquo; Back to dashboard</a></p>
        <div class="rv-head">
            <div>
                <h2 style="margin-top:0;">Revenue Report</h2>
                <p class="text-muted">Earnings from paid registrations on your exams.</p>
            </div>
            <a class="btn btn-default" href="<?= h($exportUrl); ?>"><i class="fa fa-download"></i> Export CSV</a>
        </div>
        <hr>

        <!-- Filters -->
        <form method="GET" class="rv-card" id="rvForm">
            <div class="rv-filters">
                <div class="rv-f">
                    <label for="exam_id">Exam</label>
                    <select name="exam_id" id="exam_id" class="form-control">
                        <option value="0">All exams</option>
                        <?php foreach ($examIds as $id => $title) { ?>
                            <option value="<?= $id; ?>" <?= $examId === $id ? 'selected' : ''; ?>><?= h($title); ?></option>
                        <?php } ?>
                    </select>
                </div>
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
                <div>
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="revenue_report.php" class="btn btn-default">Reset</a>
                </div>
            </div>
        </form>

        <p class="rv-muted">Showing: <strong><?= h($examLabel); ?></strong> &bull; <strong><?= h($periodLabel); ?></strong><?= $method !== '' ? ' &bull; <strong>' . h($method) . '</strong>' : ''; ?></p>

        <!-- Summary -->
        <div class="rv-stats">
            <div class="rv-stat rv-main"><b><?= rv_money($totalRevenue); ?></b><span>Total Revenue</span></div>
            <div class="rv-stat"><b><?= $totalRegs; ?></b><span>Paid Registrations</span></div>
            <div class="rv-stat"><b><?= rv_money($avg); ?></b><span>Average per Registration</span></div>
            <div class="rv-stat"><b><?= $earning; ?></b><span>Exams Earning Revenue</span></div>
        </div>

        <?php if (empty($examIds)) { ?>
            <div class="rv-card"><div class="rv-empty">You haven't created any certification exams yet, so there is no revenue to show.</div></div>
        <?php } else { ?>

        <!-- Revenue by exam -->
        <div class="rv-card">
            <h4>Revenue by Exam</h4>
            <div class="table-responsive">
            <table class="rv-table">
                <thead><tr><th>Exam</th><th class="rv-num">Price</th><th class="rv-num">Registrations</th><th class="rv-num">Revenue</th><th style="width:22%;">Share</th></tr></thead>
                <tbody>
                <?php foreach ($perExam as $p) { $pct = $totalRevenue > 0 ? round(((float)$p['revenue'] / $totalRevenue) * 100, 1) : 0; ?>
                    <tr>
                        <td><?= h($p['title']); ?></td>
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

        <!-- Transactions -->
        <div class="rv-card">
            <h4>Transactions <small class="rv-muted">(<?= $totalRegs; ?>)</small></h4>
            <?php if (!empty($txns)) { ?>
            <div class="table-responsive">
            <table class="rv-table">
                <thead><tr><th>Date</th><th>Exam</th><th>Registration No</th><th>Method</th><th>Transaction ID</th><th class="rv-num">Amount</th></tr></thead>
                <tbody>
                <?php foreach ($txns as $t) { ?>
                    <tr>
                        <td><?= !empty($t['registration_date']) ? h(date('d M Y, h:i A', strtotime($t['registration_date']))) : '-'; ?></td>
                        <td><?= h($t['title']); ?></td>
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
    ['exam_id', 'method'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('change', function () { form.submit(); }); }
    });
    document.getElementById('day').addEventListener('change', function () { form.submit(); });
})();
</script>

<?php include('footer.php'); ?>
