<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'webhooks';
$pageTitle = 'Webhook logs — Admin';
$pageHeading = 'Webhook logs';
$success = flash_get('success');
$error = flash_get('error');
$lines = admin_read_webhook_log(250);

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Meta webhook log</h2>
    <p class="font-sans" style="font-size:0.875rem;color:rgb(0 0 0 / 0.58);margin:0 0 0.85rem">Last <?= count($lines) ?> lines from <code>logs/meta-webhook.log</code></p>
    <?php if ($lines === []): ?>
        <?php admin_render_empty('Log file not found or empty.'); ?>
    <?php else: ?>
        <div class="admin-log-viewer"><?= e(implode("\n", $lines)) ?></div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
