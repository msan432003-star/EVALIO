<?php
/* payment_history.php - current user's payments (table: registrations) */
require('../config/autoload.php');
require('auth_helper.php');
require_once('ev_helpers.php');
$student_id = (int)require_login();

$per_page = 10;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array('options' => array('default' => 1, 'min_range' => 1)));
if (!$page) { $page = 1; }

$sum = ev_query("SELECT COUNT(*) AS n_all,
                        SUM(CASE WHEN r.payment_status = 'Paid' THEN 1 ELSE 0 END) AS n_paid,
                        COALESCE(SUM(CASE WHEN r.payment_status = 'Paid' THEN r.amount_paid ELSE 0 END), 0) AS total_paid
                 FROM registrations r LEFT JOIN exams e ON e.id = r.exam_id
                 WHERE r.student_id = ? AND (e.id IS NULL OR LOWER(TRIM(e.category)) <> 'mock')", 'i', array($student_id));
$n_all  = !empty($sum) ? (int)$sum[0]['n_all'] : 0;
$n_paid = !empty($sum) ? (int)$sum[0]['n_paid'] : 0;
$total_paid = !empty($sum) ? (float)$sum[0]['total_paid'] : 0;

$pages = max(1, (int)ceil($n_all / $per_page));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $per_page;

$rows = ev_query("SELECT r.id, r.registration_date, r.payment_status, r.registration_no, r.payment_method,
                         r.amount_paid, r.transaction_id, e.title
                  FROM registrations r LEFT JOIN exams e ON e.id = r.exam_id
                  WHERE r.student_id = ? AND (e.id IS NULL OR LOWER(TRIM(e.category)) <> 'mock')
                  ORDER BY r.registration_date DESC, r.id DESC LIMIT ? OFFSET ?", 'iii', array($student_id, $per_page, $offset));
if (!is_array($rows)) { $rows = array(); }

$page_title = 'Payment History';
include('header.php');
?>
<div class="row">
    <div class="col-md-12">
        <h2>Payment History</h2>
        <p class="text-muted">All exam registration payments made from your account.</p>
        <hr>
    </div>
</div>

    <div class="ev-summary">
        <div class="ev-stat">
            <span class="ev-stat-icon"><i class="fa fa-inr" aria-hidden="true"></i></span>
            <span class="ev-stat-body"><span class="ev-stat-value">&#8377;<?= number_format($total_paid, 2); ?></span><span class="ev-stat-label">Total Paid</span></span>
        </div>
        <div class="ev-stat">
            <span class="ev-stat-icon"><i class="fa fa-credit-card" aria-hidden="true"></i></span>
            <span class="ev-stat-body"><span class="ev-stat-value"><?= $n_paid; ?></span><span class="ev-stat-label">Number of Payments</span></span>
        </div>
    </div>

    <?php if (!empty($rows)) { ?>
    <div class="ev-card ev-card-flush">
        <table class="ev-table">
            <thead><tr>
                <th>Date</th><th>Exam</th><th>Amount</th><th>Status</th><th>Transaction ID</th><th>Receipt</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r) { $is_paid = (strtolower(trim((string)$r['payment_status'])) === 'paid' && (float)$r['amount_paid'] > 0); // receipt only for real payments ?>
                <tr>
                    <td data-label="Date"><?= !empty($r['registration_date']) ? ev_h(date('d M Y, h:i A', strtotime($r['registration_date']))) : '&mdash;'; ?></td>
                    <td data-label="Exam"><strong><?= ev_h(!empty($r['title']) ? $r['title'] : 'Exam'); ?></strong>
                        <?php if (!empty($r['payment_method'])) { ?><span class="ev-muted ev-block"><?= ev_h($r['payment_method']); ?></span><?php } ?></td>
                    <td data-label="Amount"><?= ($r['amount_paid'] !== null) ? '&#8377;' . number_format((float)$r['amount_paid'], 2) : '&mdash;'; ?></td>
                    <td data-label="Status"><?= ev_status_badge($r['payment_status']); ?></td>
                    <td data-label="Transaction ID"><?= !empty($r['transaction_id']) ? '<code class="ev-code">' . ev_h($r['transaction_id']) . '</code>' : '&mdash;'; ?></td>
                    <td data-label="Receipt"><?php if ($is_paid) { ?>
                        <a class="ev-link" href="receipt.php?id=<?= (int)$r['id']; ?>" target="_blank" rel="noopener"><i class="fa fa-print" aria-hidden="true"></i> Print receipt</a>
                        <?php } else { ?>&mdash;<?php } ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1) { ?>
    <nav class="ev-pager" aria-label="Payment pages">
        <?php if ($page > 1) { ?><a class="ev-btn ev-btn-ghost" href="?page=<?= $page - 1; ?>">&laquo; Prev</a><?php } ?>
        <span class="ev-muted">Page <?= $page; ?> of <?= $pages; ?></span>
        <?php if ($page < $pages) { ?><a class="ev-btn ev-btn-ghost" href="?page=<?= $page + 1; ?>">Next &raquo;</a><?php } ?>
    </nav>
    <?php } ?>

    <?php } else { ?>
    <div class="ev-card"><div class="ev-empty"><i class="fa fa-credit-card" aria-hidden="true"></i>
        <p>No payments yet</p>
        <a class="ev-btn ev-btn-primary" href="student_exams.php">Browse All Exams</a></div></div>
    <?php } ?>
</div>
<?php include('footer.php'); ?>