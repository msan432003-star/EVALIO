<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();

$filter  = $_GET['status'] ?? '';
$allowed = array('new', 'read', 'replied');
if (!in_array($filter, $allowed, true)) { $filter = ''; }

// Load every message once; counts and filtering are done in PHP
$all = array();
try {
    $all = $dao->getData(array('id', 'name', 'email', 'message', 'status', 'created_at'), 'contact_messages', '1=1');
} catch (Throwable $e) {
    $all = array();
}
if (!is_array($all)) { $all = array(); }

$counts = array('new' => 0, 'read' => 0, 'replied' => 0);
foreach ($all as $m) {
    if (isset($counts[$m['status']])) { $counts[$m['status']]++; }
}

$rows = array();
foreach ($all as $m) {
    if ($filter === '' || $m['status'] === $filter) { $rows[] = $m; }
}
$rows = array_reverse($rows);   // newest first

$labelClass = array('new' => 'label-danger', 'read' => 'label-info', 'replied' => 'label-success');

include('header.php');
?>
<div class="container" style="margin-top:20px;">
    <h3>Contact Messages</h3>
    <p class="text-muted">Messages sent from the Contact Us page on the home site.</p>

    <ul class="nav nav-tabs" style="margin-bottom:15px;">
        <li class="<?= $filter === '' ? 'active' : ''; ?>"><a href="contact_messages.php">All (<?= count($all); ?>)</a></li>
        <li class="<?= $filter === 'new' ? 'active' : ''; ?>"><a href="contact_messages.php?status=new">New (<?= $counts['new']; ?>)</a></li>
        <li class="<?= $filter === 'read' ? 'active' : ''; ?>"><a href="contact_messages.php?status=read">Read (<?= $counts['read']; ?>)</a></li>
        <li class="<?= $filter === 'replied' ? 'active' : ''; ?>"><a href="contact_messages.php?status=replied">Replied (<?= $counts['replied']; ?>)</a></li>
    </ul>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date/Time</th><th>Name</th><th>Email</th><th>Message</th><th>Status</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6">No messages found.</td></tr>
            <?php else: foreach ($rows as $m):
                // Stored text is already HTML-encoded; decode only to shorten it, then encode again for display
                $plain   = html_entity_decode($m['message'], ENT_QUOTES, 'UTF-8');
                $preview = mb_strimwidth($plain, 0, 70, '...', 'UTF-8');
                $st      = $m['status'];
            ?>
                <tr>
                    <td><?= h($m['created_at']); ?></td>
                    <td><?= $m['name']; ?></td>
                    <td><?= $m['email']; ?></td>
                    <td><?= h($preview); ?></td>
                    <td><span class="label <?= isset($labelClass[$st]) ? $labelClass[$st] : 'label-default'; ?>"><?= h(ucfirst($st)); ?></span></td>
                    <td><a href="view_message.php?id=<?= (int)$m['id']; ?>" class="btn btn-primary btn-sm"><?= $st === 'replied' ? 'View' : 'View / Reply'; ?></a></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php include('footer.php'); ?>
