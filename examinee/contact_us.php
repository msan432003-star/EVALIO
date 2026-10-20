<?php
/* contact_us.php - Dedicated Contact & Support page for Examinees.
   Messages are stored in the contact_messages table for Admin to view and reply. */
require('../config/autoload.php');
require('auth_helper.php');
require_once('ev_helpers.php');
$student_id = (int)require_login();

$dao = new DataAccess();

// Retrieve examinee info for pre-filling
$me = ev_query('SELECT name, email, phone FROM examinees WHERE id = ?', 'i', array($student_id));
$student_name  = (!empty($me) && !empty($me[0]['name']))  ? $me[0]['name']  : '';
$student_email = (!empty($me) && !empty($me[0]['email'])) ? $me[0]['email'] : (isset($_SESSION['email']) ? $_SESSION['email'] : '');

function enc_text($s) {
    return str_replace('\\', '&#92;', htmlspecialchars(trim((string)$s), ENT_QUOTES, 'UTF-8'));
}

$error   = '';
$success = false;
$name    = $student_name;
$email   = $student_email;
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Anti-spam honeypot
    if (!empty($_POST['website'])) {
        header('Location: contact_us.php?sent=1');
        exit;
    }

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $error = 'Please enter your name (2 to 100 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Please enter a valid email address.';
    } elseif (mb_strlen($message) < 5 || mb_strlen($message) > 2000) {
        $error = 'Your message must be between 5 and 2000 characters.';
    } else {
        $full_message = $message;
        if (!empty($subject)) {
            $full_message = "[Subject: " . $subject . "]\n\n" . $message;
        }

        $ok = false;
        try {
            $ok = $dao->insert(array(
                'name'    => enc_text($name),
                'email'   => enc_text($email),
                'message' => enc_text($full_message),
                'status'  => 'new',
            ), 'contact_messages');
        } catch (Throwable $e) {
            error_log('contact_us.php insert failed: ' . $e->getMessage());
            $ok = false;
        }

        if ($ok) {
            header('Location: contact_us.php?sent=1');
            exit;
        }
        $error = 'Sorry, your message could not be sent. Please try again later.';
    }
}

$page_title = 'Help & Support';
include('header.php');
?>

<div class="row">
    <div class="col-md-12">
        <h2>Help & Support</h2>
        <p class="text-muted">Need help with an exam, registration, payment, or certificate? Send a message directly to the Admin.</p>
        <hr class="header-divider">
    </div>
</div>

<?php if (isset($_GET['sent'])) { ?>
    <div class="alert alert-success" role="alert" style="margin-bottom: 24px; padding: 16px 20px;">
        <i class="fa fa-check-circle fa-lg"></i> 
        <strong>Message Sent!</strong> Thank you! Your message has been sent directly to the Admin. Our support team will review it and get back to you via email.
    </div>
<?php } ?>

<?php if (!empty($error)) { ?>
    <div class="alert alert-danger" role="alert" style="margin-bottom: 24px; padding: 16px 20px;">
        <i class="fa fa-exclamation-triangle fa-lg"></i> 
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php } ?>

<div class="row">
    <!-- Contact Form Panel -->
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-envelope-o"></i> Send Message to Admin
            </div>
            <div class="panel-body" style="padding: 24px;">
                <form action="contact_us.php" method="POST" autocomplete="off">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="name">Your Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="Your Full Name" maxlength="100" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="email">Your Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="your.email@example.com" maxlength="150" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject / Topic (Optional)</label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="e.g. Exam Registration Issue, Payment Query, Grading Question" maxlength="150" value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="message">Message <span class="text-danger">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="6" placeholder="Describe your question or issue in detail..." maxlength="2000" required><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="text-muted" style="display:block; margin-top: 4px;">Maximum 2000 characters.</small>
                    </div>

                    <!-- Honeypot spam trap -->
                    <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px;" aria-hidden="true">

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 14px;">
                            <i class="fa fa-paper-plane"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info / FAQ Card -->
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-info-circle"></i> Support Information
            </div>
            <div class="panel-body" style="padding: 20px;">
                <div style="margin-bottom: 18px;">
                    <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: var(--text-primary);"><i class="fa fa-clock-o text-primary"></i> Response Time</h4>
                    <p style="margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.6;">Admin typically responds to inquiries within 24 to 48 hours on working days.</p>
                </div>
                <hr style="margin: 14px 0;">

                <div style="margin-bottom: 18px;">
                    <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: var(--text-primary);"><i class="fa fa-question-circle text-primary"></i> Common Topics</h4>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--text-muted); line-height: 1.7;">
                        <li>Registration &amp; fee payment status</li>
                        <li>Provisional essay evaluation</li>
                        <li>Exam timer &amp; connection queries</li>
                        <li>Certificate generation &amp; download</li>
                    </ul>
                </div>
                <hr style="margin: 14px 0;">

                <div>
                    <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: var(--text-primary);"><i class="fa fa-tachometer text-primary"></i> Quick Navigation</h4>
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                        <a href="dashboard.php" class="btn btn-default btn-sm" style="text-align: left;"><i class="fa fa-tachometer"></i> Back to Dashboard</a>
                        <a href="my_exams.php" class="btn btn-default btn-sm" style="text-align: left;"><i class="fa fa-calendar"></i> My Registered Exams</a>
                        <a href="results.php" class="btn btn-default btn-sm" style="text-align: left;"><i class="fa fa-bar-chart"></i> My Results</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
