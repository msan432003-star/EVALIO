<?php 
require('../config/autoload.php'); 
require('auth_helper.php');
$student_id = require_login();

$dao = new DataAccess(); 
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0; 

$exam_info = $dao->getData('*', 'exams', "id = " . $exam_id . " AND status = 'approved'");
if(empty($exam_info)) {
    echo "<script>alert('Invalid Exam or exam not open for registration.'); window.location.href='student_exams.php';</script>";
    exit();
}
$exam = $exam_info[0];

// Check if exam is a Mock Exam
$category = strtolower(trim($exam['category'] ?? ''));
$type = strtolower(trim($exam['exam_type'] ?? ''));
$is_mock = ($category === 'mock' || $type === 'mock' || strpos($category, 'mock') !== false);

// Safe calculation for exam closing window and registration deadline
$is_closed = false;
$deadline_passed = false;
if (!$is_mock) {
    if (!empty($exam['exam_start_datetime'])) {
        $exam_end = strtotime($exam['exam_start_datetime']) + ($exam['duration_minutes'] * 60);
        $is_closed = time() > $exam_end;
    }
    if (!empty($exam['registration_deadline'])) {
        $deadline_ts = strtotime($exam['registration_deadline']);
        if ($deadline_ts > 0 && time() > $deadline_ts) {
            $deadline_passed = true;
            $is_closed = true;
        }
    }
}

// Already registered?
$existing = $dao->getData('*', 'registrations', 'student_id = ' . $student_id . ' AND exam_id = ' . $exam_id);
$already_registered = !empty($existing);
$reg = $already_registered ? $existing[0] : array();

// ---------- Dummy payment gateway settings ----------
// Category => percentage of the exam fee the student pays (edit as you like)
$category_options = array(
    'General' => 100,
    'OBC'     => 100,
    'EWS'     => 100,
    'SC'      => 50,
    'ST'      => 50,
    'PwD'     => 50
);
$payment_methods = array(
    'upi'        => 'UPI',
    'netbanking' => 'Net Banking',
    'card'       => 'Credit / Debit Card',
    'wallet'     => 'Wallet'
);
$banks   = array('State Bank of India', 'HDFC Bank', 'ICICI Bank', 'Axis Bank', 'Federal Bank', 'Canara Bank');
$wallets = array('Paytm', 'PhonePe', 'Amazon Pay', 'MobiKwik');

$base_fee = (float)($exam['price'] ?? 0);

// Student details for the form
$student_info = $dao->getData('*', 'examinees', 'id = ' . $student_id);
$student_name = !empty($student_info) ? $student_info[0]['name'] : '';

// Exam code and registration number
$exam_code = 'EX' . str_pad($exam_id, 4, '0', STR_PAD_LEFT);
$registration_no = 'REG' . date('Y') . '-' . str_pad($student_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($exam_id, 4, '0', STR_PAD_LEFT);

$errors = array();

if(isset($_POST['btn_register'])) {
    if(!csrf_check()) {
        echo "<script>alert('Session expired. Please try again.'); window.location.href='register_exam.php?exam_id=" . $exam_id . "';</script>";
        exit();
    }
    if($deadline_passed) {
        echo "<script>alert('Registration for this exam is closed. The registration deadline has passed.'); window.location.href='student_exams.php';</script>";
        exit();
    }
    if($is_closed) {
        echo "<script>alert('Registration for this exam is closed.'); window.location.href='student_exams.php';</script>";
        exit();
    }
    if($already_registered) {
        echo "<script>alert('You are already registered for this exam.'); window.location.href='student_exams.php';</script>";
        exit();
    }

    // ----- Validate payment form (all checks are server-side) -----
    $sel_category = isset($_POST['category']) ? $_POST['category'] : '';
    $sel_method   = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';

    if(!array_key_exists($sel_category, $category_options)) {
        $errors[] = 'Please select your category.';
    }
    if(!array_key_exists($sel_method, $payment_methods)) {
        $errors[] = 'Please select a payment mode.';
    } else {
        if($sel_method === 'upi') {
            $upi = isset($_POST['upi_id']) ? trim($_POST['upi_id']) : '';
            if(!preg_match('/^[A-Za-z0-9._\-]{2,}@[A-Za-z]{2,}$/', $upi)) {
                $errors[] = 'Enter a valid UPI ID (example: name@upi).';
            }
        } elseif($sel_method === 'netbanking') {
            if(!in_array(isset($_POST['bank']) ? $_POST['bank'] : '', $banks)) {
                $errors[] = 'Please select your bank.';
            }
        } elseif($sel_method === 'card') {
            $card_no = preg_replace('/\s+/', '', isset($_POST['card_number']) ? $_POST['card_number'] : '');
            $card_ex = isset($_POST['card_expiry']) ? trim($_POST['card_expiry']) : '';
            $card_cv = isset($_POST['card_cvv']) ? trim($_POST['card_cvv']) : '';
            $card_nm = isset($_POST['card_name']) ? trim($_POST['card_name']) : '';
            if(!preg_match('/^\d{16}$/', $card_no)) { $errors[] = 'Card number must be 16 digits.'; }
            if(!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $card_ex)) { $errors[] = 'Expiry must be in MM/YY format.'; }
            if(!preg_match('/^\d{3}$/', $card_cv)) { $errors[] = 'CVV must be 3 digits.'; }
            if($card_nm === '') { $errors[] = 'Enter the name on the card.'; }
        } elseif($sel_method === 'wallet') {
            if(!in_array(isset($_POST['wallet']) ? $_POST['wallet'] : '', $wallets)) {
                $errors[] = 'Please select a wallet.';
            }
        }
    }

    if(empty($errors)) {
        // Fee is always calculated on the server, never taken from the form
        $amount_paid = round($base_fee * $category_options[$sel_category] / 100, 2);
        $transaction_id = 'TXN' . date('YmdHis') . random_int(1000, 9999);

        // Card details are validated only; they are NOT stored
        $reg_data = array(
            'student_id'     => $student_id,
            'exam_id'        => $exam_id,
            'payment_status' => 'Paid',
            'registration_no'=> $registration_no,
            'category'       => $sel_category,
            'payment_method' => $payment_methods[$sel_method],
            'amount_paid'    => $amount_paid,
            'transaction_id' => $transaction_id
        );

        if($dao->insert($reg_data, "registrations")) {
            echo "<script>alert('Payment successful! Transaction ID: " . $transaction_id . "'); window.location.href='register_exam.php?exam_id=" . $exam_id . "';</script>";
        } else {
            echo "<script>alert('Registration failed. Please try again.'); window.location.href='register_exam.php?exam_id=" . $exam_id . "';</script>";
        }
        exit();
    }
}

$page_title = $is_mock ? 'Mock Exam Details' : 'Exam Registration';
include("header.php"); 

$post = function($k) { return isset($_POST[$k]) ? $_POST[$k] : ''; };
$panel_col = ($is_mock || $is_closed || $already_registered) ? 'col-md-6 col-md-offset-3' : 'col-md-8 col-md-offset-2';
?>

<div class="row justify-content-center">
    <div class="<?= $panel_col; ?>">
        <div class="panel panel-default" style="padding: 20px;">
            <h3 class="text-center text-primary"><?= $is_mock ? 'Mock Exam Details' : 'Exam Registration & Payment'; ?></h3>
            <hr>
            <h4><?= e($exam['title']); ?></h4>
            <p><strong>Category:</strong> <span class="label label-primary"><?= e($exam['category']); ?></span></p>
            <p><strong>Duration:</strong> <?= (int)$exam['duration_minutes']; ?> Minutes</p>
            
            <?php if(!$is_mock) { ?>
                <p><strong>Starts At:</strong> <span class="text-danger"><?= !empty($exam['exam_start_datetime']) ? date('d M Y, h:i A', strtotime($exam['exam_start_datetime'])) : 'Always Available'; ?></span></p>
                <p><strong>Registration Fee:</strong> <span class="text-success font-weight-bold" style="font-size: 18px;">&#8377;<?= number_format($exam['price'], 2); ?></span></p>
            <?php } ?>
            
            <?php if($is_mock) { ?>
                <a href="takeexam.php?exam_id=<?= (int)$exam['id']; ?>" class="btn btn-success btn-block btn-lg">Take the Exam</a>
                <a href="student_exams.php" class="btn btn-default btn-block">Cancel</a>
            <?php } elseif($is_closed) { ?>
                <div class="alert alert-danger">Registration for this exam is closed.</div>
                <a href="student_exams.php" class="btn btn-default btn-block">Back</a>
            <?php } elseif($already_registered) { ?>
                <div class="alert alert-info">You are already registered for this exam.</div>
                <?php if(!empty($reg['transaction_id'])) { ?>
                    <table class="table table-bordered">
                        <tr><th>Registration ID</th><td><?= e($reg['registration_no']); ?></td></tr>
                        <tr><th>Transaction ID</th><td><?= e($reg['transaction_id']); ?></td></tr>
                        <tr><th>Category</th><td><?= e($reg['category']); ?></td></tr>
                        <tr><th>Payment Mode</th><td><?= e($reg['payment_method']); ?></td></tr>
                        <tr><th>Amount Paid</th><td>&#8377;<?= number_format((float)$reg['amount_paid'], 2); ?></td></tr>
                        <tr><th>Status</th><td><span class="label label-success"><?= e($reg['payment_status']); ?></span></td></tr>
                        <tr><th>Date</th><td><?= date('d M Y, h:i A', strtotime($reg['registration_date'])); ?></td></tr>
                    </table>
                <?php } ?>
                <a href="my_exams.php" class="btn btn-primary btn-block">Go to My Exams</a>
            <?php } else { ?>
               

                <?php if(!empty($errors)) { ?>
                    <div class="alert alert-danger">
                        <ul style="margin-bottom:0;">
                            <?php foreach($errors as $err) { echo '<li>' . e($err) . '</li>'; } ?>
                        </ul>
                    </div>
                <?php } ?>

                <form method="POST" autocomplete="off">
                    <?= csrf_field(); ?>

                    <h4>Candidate &amp; Exam Details</h4>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Registration ID</label>
                            <input type="text" class="form-control" value="<?= e($registration_no); ?>" readonly>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-control" value="<?= e($student_name); ?>" readonly>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Exam Name</label>
                            <input type="text" class="form-control" value="<?= e($exam['title']); ?>" readonly>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Exam Code</label>
                            <input type="text" class="form-control" value="<?= e($exam_code); ?>" readonly>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Category / Quota</label>
                            <select name="category" id="category" class="form-control" data-base="<?= $base_fee; ?>">
                                <option value="" data-percent="100">Select Category</option>
                                <?php foreach($category_options as $cat => $pct) { ?>
                                    <option value="<?= e($cat); ?>" data-percent="<?= (int)$pct; ?>" <?= $post('category') === $cat ? 'selected' : ''; ?>>
                                        <?= e($cat); ?><?= $pct < 100 ? ' (' . (100 - $pct) . '% concession)' : ''; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Fee Payable</label>
                            <div class="form-control" style="font-weight:bold; color:#10b981;">&#8377;<span id="fee_display"><?= number_format($base_fee, 2, '.', ''); ?></span></div>
                        </div>
                    </div>

                    <h4>Payment Mode</h4>
                    <div class="form-group">
                        <?php foreach($payment_methods as $key => $label) { ?>
                            <label class="radio-inline">
                                <input type="radio" name="payment_method" value="<?= $key; ?>" <?= $post('payment_method') === $key ? 'checked' : ''; ?>> <?= e($label); ?>
                            </label>
                        <?php } ?>
                    </div>

                    <div class="pm-panel" id="pm_upi" style="display:none;">
                        <div class="form-group">
                            <label>UPI ID</label>
                            <input type="text" name="upi_id" class="form-control" placeholder="name@upi" value="<?= e($post('upi_id')); ?>">
                        </div>
                    </div>

                    <div class="pm-panel" id="pm_netbanking" style="display:none;">
                        <div class="form-group">
                            <label>Select Bank</label>
                            <select name="bank" class="form-control">
                                <option value="">Select Bank</option>
                                <?php foreach($banks as $b) { ?>
                                    <option value="<?= e($b); ?>" <?= $post('bank') === $b ? 'selected' : ''; ?>><?= e($b); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="pm-panel" id="pm_card" style="display:none;">
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>Card Number</label>
                                <input type="text" name="card_number" class="form-control" placeholder="4111 1111 1111 1111" maxlength="19">
                            </div>
                            <div class="col-md-12 form-group">
                                <label>Name on Card</label>
                                <input type="text" name="card_name" class="form-control" value="<?= e($post('card_name')); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Expiry (MM/YY)</label>
                                <input type="text" name="card_expiry" class="form-control" placeholder="08/29" maxlength="5">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>CVV</label>
                                <input type="password" name="card_cvv" class="form-control" maxlength="3">
                            </div>
                        </div>
                    </div>

                    <div class="pm-panel" id="pm_wallet" style="display:none;">
                        <div class="form-group">
                            <label>Select Wallet</label>
                            <select name="wallet" class="form-control">
                                <option value="">Select Wallet</option>
                                <?php foreach($wallets as $w) { ?>
                                    <option value="<?= e($w); ?>" <?= $post('wallet') === $w ? 'selected' : ''; ?>><?= e($w); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="btn_register" class="btn btn-success btn-block btn-lg">Pay &amp; Register</button>
                    <a href="student_exams.php" class="btn btn-default btn-block">Cancel</a>
                </form>

                <script>
                (function() {
                    var cat = document.getElementById('category');
                    var fee = document.getElementById('fee_display');
                    var base = parseFloat(cat.getAttribute('data-base')) || 0;

                    function updateFee() {
                        var pct = parseFloat(cat.options[cat.selectedIndex].getAttribute('data-percent')) || 100;
                        fee.textContent = (base * pct / 100).toFixed(2);
                    }
                    cat.addEventListener('change', updateFee);
                    updateFee();

                    var radios = document.querySelectorAll('input[name="payment_method"]');
                    function showPanel() {
                        var panels = document.querySelectorAll('.pm-panel');
                        for (var i = 0; i < panels.length; i++) { panels[i].style.display = 'none'; }
                        for (var j = 0; j < radios.length; j++) {
                            if (radios[j].checked) {
                                document.getElementById('pm_' + radios[j].value).style.display = 'block';
                            }
                        }
                    }
                    for (var k = 0; k < radios.length; k++) { radios[k].addEventListener('change', showPanel); }
                    showPanel();
                })();
                </script>
            <?php } ?>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>