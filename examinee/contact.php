<?php 
/* contact.php - Contact Form (messages are saved for the admin to read and reply to) */
require_once('../config/autoload.php'); 

// If logged in as an examinee, redirect to examinee-specific portal contact page
if (!empty($_SESSION['student_id'])) {
    header('Location: contact_us.php');
    exit;
}

$dao = new DataAccess();

// Values are stored HTML-encoded. This keeps quotes and backslashes out of the SQL
// (so a message like "I can't log in" never breaks the INSERT) and makes the text
// safe to show on the admin page.
function enc_text($s)
{
    return str_replace('\\', '&#92;', htmlspecialchars(trim((string)$s), ENT_QUOTES, 'UTF-8'));
}

$error   = '';
$name    = '';
$email   = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($_POST['website'])) {
        // Hidden field was filled in, so this is a bot. Pretend it worked.
        header('Location: contact.php?sent=1');
        exit;
    }

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $error = 'Please enter your name (2 to 100 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Please enter a valid email address.';
    } elseif (mb_strlen($message) < 5 || mb_strlen($message) > 2000) {
        $error = 'Your message must be 5 to 2000 characters.';
    } else {
        $ok = false;
        try {
            $ok = $dao->insert(array(
                'name'    => enc_text($name),
                'email'   => enc_text($email),
                'message' => enc_text($message),
                'status'  => 'new',
            ), 'contact_messages');
        } catch (Throwable $e) {
            error_log('contact.php insert failed: ' . $e->getMessage());
            $ok = false;
        }

        if ($ok) {
            // Redirect so refreshing the page does not send the message again
            header('Location: contact.php?sent=1');
            exit;
        }
        $error = 'Sorry, your message could not be sent. Please try again later.';
    }
}

$pageTitle = "Evalio - Contact Us";
include('includes/header.php');
?>

    <section class="section">
        <h2 class="section-title">Contact Us</h2>

        <?php if (isset($_GET['sent'])) { ?>
            <div style="max-width:600px; margin:0 auto 15px; padding:12px 16px; border-radius:6px; background:#e6f7ec; color:#17642f; border:1px solid #b7e4c7;">
                Thank you! Your message has been sent. We will get back to you by email.
            </div>
        <?php } ?>

        <?php if ($error != '') { ?>
            <div style="max-width:600px; margin:0 auto 15px; padding:12px 16px; border-radius:6px; background:#fdecea; color:#8a1f17; border:1px solid #f5c2bd;">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <form class="contact-form" action="contact.php" method="POST">
            <input type="text" name="name" placeholder="Your Name" maxlength="100" required value="<?= htmlspecialchars($name); ?>">
            <input type="email" name="email" placeholder="Your Email" maxlength="150" required value="<?= htmlspecialchars($email); ?>">
            <textarea name="message" rows="5" placeholder="Your Message" maxlength="2000" required><?= htmlspecialchars($message); ?></textarea>

            <!-- Spam trap: real visitors never see or fill this field -->
            <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px;" aria-hidden="true">

            <button type="submit" class="nav-btn nav-btn-primary">Send Message</button>
        </form>
    </section>

<?php include('includes/footer.php'); ?>
