<?php
/* receipt.php?id=<registrations.id> - print-friendly receipt, owner only */
require('../config/autoload.php');
require('auth_helper.php');
require_once('ev_helpers.php');
$student_id = (int)require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
$row = null;
if ($id) {
    $res = ev_query("SELECT r.id, r.registration_date, r.payment_status, r.registration_no, r.category, r.payment_method,
                            r.amount_paid, r.transaction_id, e.title, u.name, u.email
                     FROM registrations r
                     INNER JOIN examinees u ON u.id = r.student_id
                     LEFT JOIN exams e ON e.id = r.exam_id
                     WHERE r.id = ? AND r.student_id = ?
                       AND (e.id IS NULL OR LOWER(TRIM(e.category)) <> 'mock') LIMIT 1", 'ii', array($id, $student_id));
    if (!empty($res)) { $row = $res[0]; }
}
if (!$row) { http_response_code(404); echo 'Receipt not found.'; exit(); }
if (strtolower(trim((string)$row['payment_status'])) !== 'paid' || !((float)$row['amount_paid'] > 0)) { http_response_code(403); echo 'Receipt is available only for paid registrations with a payment amount.'; exit(); }

$line = function($label, $value) { return '<tr><th>' . ev_h($label) . '</th><td>' . $value . '</td></tr>'; };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Receipt - Evalio</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; color: #111; background: #f3f3f6; margin: 0; padding: 20px; }
    .rc { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 28px; }
    h1 { margin: 0 0 4px; font-size: 22px; color: #0284c7; }
    .sub { color: #555; margin: 0 0 20px; font-size: 13px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 10px 6px; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: top; }
    th { width: 40%; color: #444; font-weight: 600; }
    .total td { font-weight: 700; font-size: 16px; }
    .actions { margin-top: 20px; text-align: center; }
    button, a.b { min-height: 44px; padding: 10px 20px; border-radius: 8px; border: 1px solid #0284c7; background: #0284c7; color: #fff; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-block; }
    a.b { background: #fff; color: #0284c7; margin-left: 8px; }
    button:focus-visible, a.b:focus-visible { outline: 3px solid #111; outline-offset: 2px; }
    @media print { body { background: #fff; padding: 0; } .rc { border: 0; } .actions { display: none; } }
</style>
</head>
<body>
<div class="rc">
    <h1>Evalio &mdash; Payment Receipt</h1>
    <p class="sub">Receipt for exam registration</p>
    <table>
        <?= $line('Name', ev_h($row['name'])); ?>
        <?= $line('Email', ev_h($row['email'])); ?>
        <?= $line('Exam', ev_h($row['title'])); ?>
        <?= $line('Registration No.', ev_h($row['registration_no'] !== null ? $row['registration_no'] : '-')); ?>
        <?= $line('Category', ev_h($row['category'] !== null ? $row['category'] : '-')); ?>
        <?= $line('Payment Method', ev_h($row['payment_method'] !== null ? $row['payment_method'] : '-')); ?>
        <?= $line('Transaction ID', ev_h($row['transaction_id'] !== null ? $row['transaction_id'] : '-')); ?>
        <?= $line('Date', ev_h(date('d M Y, h:i A', strtotime($row['registration_date'])))); ?>
        <?= $line('Status', 'Paid'); ?>
        <tr class="total"><th>Amount Paid</th><td><?= ($row['amount_paid'] !== null) ? '&#8377;' . number_format((float)$row['amount_paid'], 2) : '-'; ?></td></tr>
    </table>
    <div class="actions"><button type="button" onclick="window.print()">Print</button><a class="b" href="payment_history.php">Back</a></div>
</div>
</body>
</html>