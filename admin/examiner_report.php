<?php
/* examiner_report.php - STEP 1: list of approved, active examiners (with search, date filter, sorting) */
require('../config/autoload.php');
require('auth.php');
require_once('report_helpers.php');

$dao = new DataAccess();

// ---------- Filters from the form (GET) ----------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$from   = isset($_GET['from']) ? trim($_GET['from']) : '';
$to     = isset($_GET['to']) ? trim($_GET['to']) : '';
$sort   = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order  = (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'desc' : 'asc';

$validSorts = array('name', 'registered', 'exams', 'passrate');
if (!in_array($sort, $validSorts)) { $sort = 'name'; }

// Only accept real dates (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = ''; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $to = ''; }

// Only approved examiners whose account is active (rejected / deactivated / pending are hidden)
$where = "ex.status = 'approved' AND ex.account_status = 'active'";
if ($search !== '') {
    $s = addslashes($search);
    $where .= " AND (ex.name LIKE '%" . $s . "%' OR ex.email LIKE '%" . $s . "%')";
}
if ($from !== '') { $where .= " AND DATE(ex.created_at) >= '" . $from . "'"; }
if ($to !== '')   { $where .= " AND DATE(ex.created_at) <= '" . $to . "'"; }

$examiners = $dao->query(
    "SELECT ex.id, ex.name, ex.email, ex.created_at, COUNT(e.id) AS exam_count
     FROM examiners ex
     LEFT JOIN exams e ON e.created_by = ex.id
     WHERE " . $where . "
     GROUP BY ex.id, ex.name, ex.email, ex.created_at"
);
if (!is_array($examiners)) { $examiners = array(); }

// Pass / fail totals per examiner (all their exams together)
$stats = array();
$statRows = $dao->query(
    "SELECT e.created_by AS examiner_id,
            SUM(CASE WHEN " . rp_pass_sql() . " THEN 1 ELSE 0 END) AS passed,
            SUM(CASE WHEN " . rp_fail_sql() . " THEN 1 ELSE 0 END) AS failed
     FROM exam_results r
     JOIN exams e ON e.id = r.exam_id
     WHERE e.created_by IS NOT NULL
     GROUP BY e.created_by"
);
if (is_array($statRows)) {
    foreach ($statRows as $s2) {
        $stats[(int)$s2['examiner_id']] = array((int)$s2['passed'], (int)$s2['failed']);
    }
}

// Attach pass/fail/rate to each examiner, then sort
foreach ($examiners as $k => $ex) {
    $id = (int)$ex['id'];
    $p = isset($stats[$id]) ? $stats[$id][0] : 0;
    $f = isset($stats[$id]) ? $stats[$id][1] : 0;
    $examiners[$k]['passed'] = $p;
    $examiners[$k]['failed'] = $f;
    $examiners[$k]['rate'] = ($p + $f) > 0 ? ($p / ($p + $f)) * 100 : -1;
}

usort($examiners, function ($a, $b) use ($sort, $order) {
    switch ($sort) {
        case 'registered': $cmp = strtotime($a['created_at']) - strtotime($b['created_at']); break;
        case 'exams':      $cmp = (int)$a['exam_count'] - (int)$b['exam_count']; break;
        case 'passrate':   $cmp = $a['rate'] <=> $b['rate']; break;
        default:           $cmp = strcasecmp($a['name'], $b['name']);
    }
    return $order === 'desc' ? -$cmp : $cmp;
});

include('header.php');
echo rp_styles();
?>

<div class="container" style="margin-top:20px;">
    <h2>Examiner Report</h2>
    <p class="text-muted">Approved, active examiners only. Click an examiner to see the exams they created. Pass mark: <?= (int)PASS_PERCENT; ?>%.</p>
    <hr>

    <div class="panel panel-default" style="margin-bottom:24px;">
        <div class="panel-body" style="padding:16px 20px;">
            <form method="GET" class="form-inline" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="Search name or email" value="<?= rp_h($search); ?>">
                </div>
                <div class="form-group" style="display:flex; align-items:center; gap:6px;">
                    <label for="from" style="font-size:13px; color:var(--text-muted); margin:0;">From:</label>
                    <input type="date" name="from" id="from" class="form-control" value="<?= rp_h($from); ?>">
                </div>
                <div class="form-group" style="display:flex; align-items:center; gap:6px;">
                    <label for="to" style="font-size:13px; color:var(--text-muted); margin:0;">To:</label>
                    <input type="date" name="to" id="to" class="form-control" value="<?= rp_h($to); ?>">
                </div>
                <div class="form-group" style="display:flex; align-items:center; gap:6px;">
                    <label for="sort" style="font-size:13px; color:var(--text-muted); margin:0;">Sort:</label>
                    <select name="sort" id="sort" class="form-control">
                        <option value="name" <?= $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="registered" <?= $sort === 'registered' ? 'selected' : ''; ?>>Registered date</option>
                        <option value="exams" <?= $sort === 'exams' ? 'selected' : ''; ?>>Exams created</option>
                        <option value="passrate" <?= $sort === 'passrate' ? 'selected' : ''; ?>>Pass rate</option>
                    </select>
                    <select name="order" class="form-control">
                        <option value="asc" <?= $order === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="desc" <?= $order === 'desc' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filter</button>
                <a href="examiner_report.php" class="btn btn-default btn-sm">Reset</a>
            </form>
        </div>
    </div>

    <p><strong>Showing:</strong> <?= count($examiners); ?> examiner(s)</p>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Examiner</th>
                    <th>Email</th>
                    <th>Registered On</th>
                    <th>Exams Created</th>
                    <th>Passed</th>
                    <th>Failed</th>
                    <th>Pass Rate</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($examiners)) { $i = 1; foreach ($examiners as $ex) {
                $id = (int)$ex['id'];
                $p = $ex['passed'];
                $f = $ex['failed'];
                ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><a class="rp-link" href="examiner_exams.php?examiner_id=<?= $id; ?>"><?= rp_h($ex['name']); ?></a></td>
                    <td><?= rp_h($ex['email']); ?></td>
                    <td><?= !empty($ex['created_at']) ? rp_h(date('d M Y', strtotime($ex['created_at']))) : '&mdash;'; ?></td>
                    <td><?= (int)$ex['exam_count']; ?></td>
                    <td><?= $p; ?></td>
                    <td><?= $f; ?></td>
                    <td><?= ($p + $f) > 0 ? rp_rate($p, $p + $f) . '%' : '&mdash;'; ?></td>
                </tr>
            <?php } } else { ?>
                <tr><td colspan="8" class="text-center">No examiners match your search.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('footer.php'); ?>