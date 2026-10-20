<?php
/* dashboard.php - Examinee dashboard (read-only, current user's data only) */
require('../config/autoload.php');
require('auth_helper.php');
require_once('ev_helpers.php');
$student_id = (int)require_login();

$me = ev_query('SELECT name FROM examinees WHERE id = ?', 'i', array($student_id));
$display_name = (!empty($me) && trim($me[0]['name']) !== '') ? $me[0]['name'] : 'Examinee';

// Paid registrations (same Paid filter as my_exams.php)
$regs = ev_query("SELECT e.id, e.title, e.category, e.duration_minutes, e.exam_start_datetime
                  FROM registrations r INNER JOIN exams e ON e.id = r.exam_id
                  WHERE r.student_id = ? AND r.payment_status = 'Paid'", 'i', array($student_id));
if (!is_array($regs)) { $regs = array(); }

// All results of this student, newest first
$all_results = ev_query("SELECT r.*, e.title AS exam_title, e.category AS exam_category
                         FROM exam_results r LEFT JOIN exams e ON e.id = r.exam_id
                         WHERE r.student_id = ? ORDER BY r.submitted_at DESC, r.id DESC", 'i', array($student_id));
if (!is_array($all_results)) { $all_results = array(); }

$submitted = array();   // exam ids with any result (used for "upcoming", same as my_exams.php)
$visible   = array();   // results shown on My Results (mock exams excluded)
foreach ($all_results as $r) {
    $submitted[(int)$r['exam_id']] = true;
    if (!ev_is_mock(isset($r['exam_category']) ? $r['exam_category'] : '')) { $visible[] = $r; }
}

$completed = count($visible);
$passed_count = 0; $pct_sum = 0; $pct_n = 0;
foreach ($visible as $r) {
    $i = ev_result_info($r);
    if ($i['passed']) { $passed_count++; }
    if (!$i['pending']) { $pct_sum += $i['pct']; $pct_n++; }
}
$avg_score = ($pct_n > 0) ? number_format($pct_sum / $pct_n, 1) . '%' : '-';
$certificates = $passed_count; // My Results offers a certificate for every passed (non-mock) result

// Upcoming exams (same rule as my_exams.php)
$now = time();
$upcoming = array();
foreach ($regs as $ex) {
    $start = !empty($ex['exam_start_datetime']) ? strtotime($ex['exam_start_datetime']) : 0;
    $end   = $start + ((int)$ex['duration_minutes'] * 60);
    if (isset($submitted[(int)$ex['id']]) || $now > $end) { continue; }
    $ex['_start'] = $start;
    $upcoming[] = $ex;
}
usort($upcoming, function($a, $b) { return $a['_start'] <=> $b['_start']; });

$recent = array_slice($visible, 0, 5);

$cards = array(
    array('fa-desktop',       'Registered Exams',    (string)count($regs),      'card-cert'),
    array('fa-check-square-o','Completed Exams',     (string)$completed,        'card-passrate'),
    array('fa-trophy',        'Passed Exams',        (string)$passed_count,     'card-registrations'),
    array('fa-line-chart',    'Average Score',       $avg_score,                'card-mock'),
    array('fa-certificate',   'Certificates Earned', (string)$certificates,     'card-revenue'),
);

$page_title = 'Dashboard';
include('header.php');
?>

<!-- Background Constellation Graphic matching Examiner Panel -->
<svg class="bg-constellation" viewBox="0 0 350 250" fill="none" xmlns="http://www.w3.org/2000/svg">
    <line x1="80" y1="20" x2="160" y2="60" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="160" y1="60" x2="240" y2="30" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="160" y1="60" x2="210" y2="120" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="240" y1="30" x2="310" y2="70" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="210" y1="120" x2="310" y2="70" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="210" y1="120" x2="280" y2="170" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="100" y1="150" x2="210" y2="120" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="280" y1="170" x2="340" y2="140" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <line x1="310" y1="70" x2="340" y2="140" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
    <circle cx="80" cy="20" r="3.5" fill="#00e5ff"/>
    <circle cx="160" cy="60" r="4.5" fill="#00d2ff"/>
    <circle cx="240" cy="30" r="4" fill="#a855f7"/>
    <circle cx="210" cy="120" r="5" fill="#ff007f"/>
    <circle cx="310" cy="70" r="4.5" fill="#ec4899"/>
    <circle cx="280" cy="170" r="3.5" fill="#ff6b4a"/>
    <circle cx="100" cy="150" r="4" fill="#00e5ff"/>
    <circle cx="340" cy="140" r="4" fill="#10b981"/>
</svg>

<div class="dashboard-header">
    <h2 class="dashboard-main-title">Examinee Dashboard</h2>
    <h3 class="dashboard-welcome-title">Welcome, <strong><?= ev_h($display_name); ?></strong>!</h3>
    <hr class="header-divider">
</div>

<div class="stat-cards-grid">
    <?php foreach ($cards as $c) { ?>
        <div class="stat-card <?= $c[3]; ?>">
            <div class="stat-icon-wrapper">
                <i class="fa <?= $c[0]; ?>" aria-hidden="true"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= ev_h($c[2]); ?></div>
                <div class="stat-title"><?= ev_h($c[1]); ?></div>
            </div>
        </div>
    <?php } ?>
</div>

    <div class="ev-grid-2">
        <section class="ev-card">
            <h3 class="ev-card-title"><i class="fa fa-clock-o" aria-hidden="true"></i> Upcoming Exams</h3>
            <?php if (!empty($upcoming)) { ?>
                <ul class="ev-list">
                <?php foreach (array_slice($upcoming, 0, 6) as $ex) { $ready = ($now >= $ex['_start']); ?>
                    <li class="ev-list-item">
                        <div class="ev-list-main">
                            <strong><?= ev_h($ex['title']); ?></strong>
                            <span class="ev-muted"><?= $ex['_start'] ? ev_h(date('d M Y, h:i A', $ex['_start'])) : 'Always available'; ?></span>
                        </div>
                        <?php if ($ready) { ?>
                            <a class="ev-btn ev-btn-success" href="takeexam.php?exam_id=<?= (int)$ex['id']; ?>">Take Exam</a>
                        <?php } else { ?>
                            <span class="ev-btn ev-btn-disabled" aria-disabled="true">Starts Soon</span>
                        <?php } ?>
                    </li>
                <?php } ?>
                </ul>
            <?php } else { ?>
                <div class="ev-empty"><i class="fa fa-calendar-o" aria-hidden="true"></i>
                    <p>No upcoming exams</p>
                    <a class="ev-btn ev-btn-primary" href="student_exams.php">Browse All Exams</a>
                </div>
            <?php } ?>
        </section>

        <section class="ev-card">
            <h3 class="ev-card-title"><i class="fa fa-bar-chart-o" aria-hidden="true"></i> Recent Results</h3>
            <?php if (!empty($recent)) { ?>
                <ul class="ev-list">
                <?php foreach ($recent as $r) { $i = ev_result_info($r); ?>
                    <li class="ev-list-item">
                        <div class="ev-list-main">
                            <strong><?= ev_h(!empty($r['exam_title']) ? $r['exam_title'] : 'Exam #' . (int)$r['exam_id']); ?></strong>
                            <span class="ev-muted"><?= ev_h(number_format($i['obtained'], 2) . ' / ' . number_format($i['total'], 2)); ?>
                                &middot; <?= ev_h(date('d M Y', strtotime($r['submitted_at']))); ?></span>
                        </div>
                        <?php if ($i['pending']) { ?><span class="ev-badge ev-badge-warn">Pending evaluation</span>
                        <?php } elseif ($i['passed']) { ?><span class="ev-badge ev-badge-ok">Passed</span>
                        <?php } else { ?><span class="ev-badge ev-badge-bad">Failed</span><?php } ?>
                    </li>
                <?php } ?>
                </ul>
                <a class="ev-link" href="results.php">View all results &rarr;</a>
            <?php } else { ?>
                <div class="ev-empty"><i class="fa fa-bar-chart-o" aria-hidden="true"></i>
                    <p>No results yet</p>
                    <a class="ev-link" href="results.php">Go to My Results &rarr;</a>
                </div>
            <?php } ?>
        </section>
    </div>

    <div class="ev-quick">
        <a class="ev-btn ev-btn-primary" href="student_exams.php"><i class="fa fa-desktop" aria-hidden="true"></i> Browse Exams</a>
        <a class="ev-btn ev-btn-ghost" href="results.php"><i class="fa fa-bar-chart-o" aria-hidden="true"></i> My Results</a>
        <a class="ev-btn ev-btn-ghost" href="references.php"><i class="fa fa-book" aria-hidden="true"></i> Study References</a>
    </div>
</div>
<?php include('footer.php'); ?>