<?php
/* widget.php - include this on a page, just before </body> (or in the shared header):
      $chatbot_profile = 'public';   // 'public' | 'examinee' | 'examiner'
      include BASE_PATH . 'chatbot/widget.php';
   Needs ../config/autoload.php to be loaded already (BASE_URL, session, csrf_token). */
if (!defined('BASE_URL')) { return; }
$cb_profile = isset($chatbot_profile) ? $chatbot_profile : 'public';
if (!in_array($cb_profile, array('public', 'examinee', 'examiner'), true)) { $cb_profile = 'public'; }
$cb_url = BASE_URL . 'chatbot/';
$cb_cfg = array('api' => $cb_url . 'api.php', 'profile' => $cb_profile, 'token' => function_exists('csrf_token') ? csrf_token() : '');
?>
<link rel="stylesheet" href="<?= htmlspecialchars($cb_url . 'chatbot.css', ENT_QUOTES, 'UTF-8'); ?>">
<div id="cb-root"></div>
<script>window.EVALIO_CHATBOT = <?= json_encode($cb_cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="<?= htmlspecialchars($cb_url . 'chatbot.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>