<?php
require('../config/autoload.php');
require('auth.php');
require_once('audit_log.php');

$dao = new DataAccess();
$admin_id = $_SESSION['admin_id'] ?? 0;

// Same encoding as contact.php: keeps quotes/backslashes out of the SQL
function enc_text($s)
{
    return str_replace('\\', '&#92;', htmlspecialchars(trim((string)$s), ENT_QUOTES, 'UTF-8'));
}
function dec_text($s)
{
    return html_entity_decode((string)$s, ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: contact_messages.php'); exit; }

$rows = $dao->getData('*', 'contact_messages', 'id=' . $id);
if (empty($rows)) {
    echo "<script>alert('Message not found'); window.location.href='contact_messages.php';</script>";
    exit;
}
$msg = $rows[0];

$error = '';

// ---------- Send a reply ----------
if (isset($_POST['btn_reply'])) {
    $replyText = trim($_POST['reply'] ?? '');

    if (mb_strlen($replyText) < 2 || mb_strlen($replyText) > 3000) {
        $error = 'Reply must be 2 to 3000 characters.';
    } else {
        $saved = $dao->update(array(
            'reply'      => enc_text($replyText),
            'status'     => 'replied',
            'replied_at' => date('Y-m-d H:i:s'),
            'replied_by' => (int)$admin_id,
        ), 'contact_messages', 'id=' . $id);

        if ($saved) {
            // Try to email the reply to the visitor (needs a working mail setup on the server)
            $toEmail = dec_text($msg['email']);
            $toName  = dec_text($msg['name']);
            $mailed  = 0;

            if (filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $subject = 'Re: your message to Evalio';
                $body    = "Hello " . $toName . ",\r\n\r\n"
                         . $replyText . "\r\n\r\n"
                         . "-----\r\nYour original message:\r\n" . dec_text($msg['message']) . "\r\n\r\n"
                         . "Regards,\r\nEvalio Admin";
                $headers = "From: Evalio <no-reply@evalio.local>\r\n"
                         . "Content-Type: text/plain; charset=UTF-8\r\n";
                $mailed  = @mail($toEmail, $subject, $body, $headers) ? 1 : 0;
            }

            // Audit log
            logAction($admin_id, 'reply_message', 'contact_messages', $id, 'Replied to message from ' . $toName);

            header('Location: view_message.php?id=' . $id . '&replied=1&mailed=' . $mailed);
            exit;
        } else {
            $error = 'Reply could not be saved.';
        }
    }
}

// ---------- Mark as read when first opened ----------
if ($msg['status'] === 'new') {
    $dao->update(array('status' => 'read'), 'contact_messages', 'id=' . $id);
    $msg['status'] = 'read';
}

include('header.php');
?>
<div class="container" style="margin-top:20px;">
    <h3>Message from <?= $msg['name']; ?></h3>
    <p><a href="contact_messages.php">&laquo; Back to messages</a></p>

    <?php if (isset($_GET['replied'])): ?>
        <?php if (!empty($_GET['mailed'])): ?>
            <div class="alert alert-success">Reply saved and emailed to the visitor.</div>
        <?php else: ?>
            <div class="alert alert-warning">Reply saved, but the email could not be sent from this server (mail is not set up). Copy the reply and send it from your own email if needed.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($error != ''): ?>
        <div class="alert alert-danger"><?= h($error); ?></div>
    <?php endif; ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <strong><?= $msg['name']; ?></strong> &bull; <?= $msg['email']; ?>
            <span class="pull-right text-muted"><?= h($msg['created_at']); ?></span>
        </div>
        <div class="panel-body">
            <?= nl2br($msg['message']); ?>
        </div>
    </div>

    <?php if (!empty($msg['reply'])): ?>
        <div class="panel panel-success">
            <div class="panel-heading">
                Your reply
                <span class="pull-right text-muted"><?= !empty($msg['replied_at']) ? h($msg['replied_at']) : ''; ?></span>
            </div>
            <div class="panel-body">
                <?= nl2br($msg['reply']); ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="view_message.php?id=<?= $id; ?>">
        <div class="form-group">
            <label><?= !empty($msg['reply']) ? 'Send a new reply (replaces the saved reply)' : 'Reply'; ?></label>
            <textarea name="reply" rows="5" class="form-control" maxlength="3000" required placeholder="Type your reply here"></textarea>
        </div>
        <button type="submit" name="btn_reply" class="btn btn-primary">Send Reply</button>
    </form>
</div>
<?php include('footer.php'); ?>
