<?php

require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();

if (!function_exists('valid_date')) {
    function valid_date($d) {
        $x = DateTime::createFromFormat('Y-m-d', $d);
        return $x && $x->format('Y-m-d') === $d;
    }
}

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$filter_by = $_GET['filter_by'] ?? 'exam';              
if (!in_array($filter_by, array('exam', 'registered'), true)) { $filter_by = 'exam'; }
$from = trim($_GET['fdate'] ?? '');
$to   = trim($_GET['tdate'] ?? '');
$searched = isset($_GET['search']);

$errors = array();
$rows = array();
$total_paid = 0;

if ($searched) {
    if (!valid_date($from)) { $errors[] = 'Please choose a valid From Date.'; }
    if ($to === '') { $to = $from; }                     // only one date given = that single day
    elseif (!valid_date($to)) { $errors[] = 'Please choose a valid To Date.'; }
    elseif ($from !== '' && valid_date($from) && $to < $from) { $errors[] = 'To Date cannot be earlier than From Date.'; }

    if (empty($errors)) { 
        // lookups
        $exams_lookup = array();
        $all_exams = $dao->getData('*', 'exams', '1');
        if (!empty($all_exams)) { foreach ($all_exams as $e) { $exams_lookup[(int)$e['id']] = $e; } }

        $students_lookup = array();
        $all_students = $dao->getData('*', 'examinees', '1');
        if (!empty($all_students)) { foreach ($all_students as $s) { $students_lookup[(int)$s['id']] = $s; } }

        $cond = '1';
        if ($filter_by === 'registered') {
            $cond = "DATE(registration_date) BETWEEN '" . $from . "' AND '" . $to . "'";
        }
        $regs = $dao->getData('*', 'registrations', $cond . ' ORDER BY registration_date DESC');

        if (!empty($regs)) {
            foreach ($regs as $r) {
                $exam = $exams_lookup[(int)$r['exam_id']] ?? null;
                if (!$exam) { continue; }

                // skip mock exams (same test student_exams.php uses)
                $cat  = strtolower(trim($exam['category'] ?? ''));
                $type = strtolower(trim($exam['exam_type'] ?? ''));
                if (strpos($cat, 'mock') !== false || strpos($type, 'mock') !== false) { continue; }

                // skip registrations with no payment / no registration number
                if (strtolower(trim($r['payment_status'] ?? '')) !== 'paid'
                    || empty($r['registration_no'])
                    || (float)$r['amount_paid'] <= 0) { continue; }

                $exam_day = substr($exam['exam_start_datetime'], 0, 10);

                if ($filter_by === 'exam' && ($exam_day < $from || $exam_day > $to)) { continue; }

                $stu = $students_lookup[(int)$r['student_id']] ?? array();
                $rows[] = array(
                    'reg_no'   => $r['registration_no'] ?: ('#' . $r['id']),
                    'sid'      => $r['student_id'],
                    'name'     => $stu['name'] ?? 'N/A',
                    'email'    => $stu['email'] ?? '',
                    'phone'    => $stu['phone'] ?? '',
                    'exam'     => $exam['title'] ?? 'N/A',
                    'exam_dt'  => $exam['exam_start_datetime'],
                    'reg_dt'   => $r['registration_date'],
                    'status'   => $r['payment_status'],
                    'amount'   => $r['amount_paid'],
                );
                $total_paid += (float)$r['amount_paid'];
            }
            if ($filter_by === 'exam') {   // order by exam date/time
                usort($rows, function ($a, $b) { return strcmp($a['exam_dt'], $b['exam_dt']); });
            }
        }
    }
}

include("header.php");
?>

<div class="row">
    <div class="col-md-12">
        <h2 style="margin-top:0;">Exam Registrations Report</h2>
        <p class="text-muted">Filter and inspect verified student exam registrations and payments across dates.</p>
        <hr>
    </div>
</div>

<div class="panel panel-default" style="padding: 20px; margin-bottom: 25px;">
    <form method="GET" action="" class="form-inline">
        <div class="form-group" style="margin-right: 15px; margin-bottom: 10px;">
            <label style="margin-right: 6px;">Filter by:</label>
            <select name="filter_by" class="form-control">
                <option value="exam"       <?= $filter_by === 'exam' ? 'selected' : ''; ?>>Exam date</option>
                <option value="registered" <?= $filter_by === 'registered' ? 'selected' : ''; ?>>Registration date</option>
            </select>
        </div>

        <div class="form-group" style="margin-right: 15px; margin-bottom: 10px;">
            <label style="margin-right: 6px;">From Date:</label>
            <input type="date" name="fdate" class="form-control" value="<?= h($from); ?>" required>
        </div>

        <div class="form-group" style="margin-right: 15px; margin-bottom: 10px;">
            <label style="margin-right: 6px;">To Date:</label>
            <input type="date" name="tdate" class="form-control" value="<?= h($to); ?>">
            <small class="text-muted" style="display:inline-block; margin-left: 4px;">(Optional, defaults to From Date)</small>
        </div>

        <div class="form-group" style="margin-bottom: 10px;">
            <button type="submit" name="search" value="1" class="btn btn-primary" style="margin-right: 8px;">
                <i class="fa fa-search"></i> View Report
            </button>
            <?php if ($searched) { ?>
                <a href="rpt_exam_registrations.php" class="btn btn-default">Reset</a>
            <?php } ?>
        </div>
    </form>
</div>

<?php if (!empty($errors)) { ?>
    <div class="alert alert-danger" style="border-radius: 8px;">
        <?php foreach ($errors as $er) { echo '<i class="fa fa-exclamation-triangle"></i> ' . h($er) . '<br>'; } ?>
    </div>
<?php } ?>

<?php if ($searched && empty($errors)) { ?>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:15px;">
        <h4 style="margin: 0;">
            <?= $filter_by === 'exam' ? 'Students taking an exam' : 'Students who registered'; ?>
            <?= $from === $to ? 'on <strong>' . h($from) . '</strong>' : 'from <strong>' . h($from) . '</strong> to <strong>' . h($to) . '</strong>'; ?>
            <span class="label label-info" style="font-size: 13px; margin-left: 8px;"><?= count($rows); ?> found</span>
        </h4>
        <?php if (!empty($rows)) { ?>
            <button type="button" class="btn btn-default" onclick="window.print()">
                <i class="fa fa-print"></i> Print
            </button>
        <?php } ?>
    </div>

    <?php if (empty($rows)) { ?>
        <div class="panel panel-default" style="padding: 30px; text-align: center;">
            <p class="text-muted" style="margin: 0; font-size: 15px;">No paid registrations found for this date selection.</p>
        </div>
    <?php } else { ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Reg. No</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Exam</th>
                        <th>Exam Date &amp; Time</th>
                        <th>Registered On</th>
                        <th>Payment</th>
                        <th style="text-align: right;">Amount (&#8377;)</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($rows as $r) { ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><strong><?= h($r['reg_no']); ?></strong></td>
                        <td><?= (int)$r['sid']; ?></td>
                        <td><?= h($r['name']); ?></td>
                        <td><?= h($r['email'] ?: '-'); ?></td>
                        <td><?= h($r['phone'] ?: '-'); ?></td>
                        <td><?= h($r['exam']); ?></td>
                        <td><?= h(date('d M Y, h:i A', strtotime($r['exam_dt']))); ?></td>
                        <td><?= h(date('d M Y, h:i A', strtotime($r['reg_dt']))); ?></td>
                        <td><span class="label label-success"><?= h(strtoupper($r['status'])); ?></span></td>
                        <td style="text-align: right; font-weight: 600;">&#8377;<?= number_format((float)$r['amount'], 2); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="10" style="text-align:right; font-size: 15px;"><strong>Total Revenue:</strong></td>
                        <td style="text-align: right; font-size: 15px; color: #16a34a;"><strong>&#8377;<?= number_format($total_paid, 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php } ?>
<?php } ?>

<?php include('footer.php'); ?>