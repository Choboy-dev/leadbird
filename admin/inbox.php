<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'inbox';
$pageTitle = 'Inbox viewer — Admin';
$pageHeading = 'Inbox viewer';
$success = flash_get('success');
$error = flash_get('error');
$threadId = trim((string) ($_GET['thread'] ?? ''));
$page = admin_page((int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['q'] ?? ''));

if ($threadId !== '') {
    $conversation = RepositoryFactory::conversations()->findById($threadId);
    if ($conversation === null) {
        flash_set('error', 'Conversation not found.');
        redirect(app_route('admin/inbox'));
    }
    $messages = RepositoryFactory::messages()->listByConversationId($threadId, 200);
    $business = RepositoryFactory::businesses()->findById((string) $conversation['business_id']);
    $customer = !empty($conversation['customer_id'])
        ? RepositoryFactory::customers()->findById((string) $conversation['customer_id'])
        : null;
}

$result = $threadId === ''
    ? $adminService->repository()->listConversations($page, 25, $search !== '' ? $search : null)
    : ['rows' => [], 'total' => 0];
$baseUrl = app_route('admin/inbox', array_filter(['q' => $search ?: null]));

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);

if ($threadId !== ''): ?>
    <p class="font-sans" style="margin:0 0 1rem"><a href="<?= e(app_route('admin/inbox')) ?>">← Back to conversations</a></p>
    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">
            <?= e((string) ($customer['customer_name'] ?? 'Customer')) ?>
            · <?= e((string) ($business['business_name'] ?? '')) ?>
        </h2>
        <p class="font-sans" style="font-size:0.875rem;color:rgb(0 0 0 / 0.58);margin:0 0 1rem">Read-only view for support and debugging.</p>
        <div class="admin-thread">
            <?php foreach ($messages as $message): ?>
                <?php
                $sender = (string) ($message['sender_type'] ?? 'customer');
                $bubbleClass = in_array($sender, ['ai', 'owner', 'business'], true) ? 'admin-thread-bubble--ai' : 'admin-thread-bubble--customer';
                ?>
                <div class="admin-thread-bubble <?= e($bubbleClass) ?>">
                    <strong style="display:block;font-size:0.75rem;margin-bottom:0.25rem"><?= e(ucfirst($sender)) ?></strong>
                    <?= nl2br(e((string) ($message['message_text'] ?? ''))) ?>
                    <span style="display:block;margin-top:0.35rem;font-size:0.72rem;opacity:0.65"><?= e(admin_format_datetime((string) ($message['created_at'] ?? ''))) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php else: ?>
    <div class="admin-toolbar"><?php admin_toolbar_search('Search customer or business…', $search); ?></div>
    <?php if ($result['rows'] === []): ?>
        <?php admin_render_empty('No conversations found.'); ?>
    <?php else: ?>
        <?php admin_render_table_start(['Customer', 'Business', 'Owner', 'Platform', 'Last message']); ?>
        <?php foreach ($result['rows'] as $row): ?>
            <tr>
                <td><a href="<?= e(app_route('admin/inbox', ['thread' => (string) $row['id']])) ?>"><?= e((string) ($row['customer_name'] ?? 'Unknown')) ?></a></td>
                <td><?= e((string) ($row['business_name'] ?? '')) ?></td>
                <td><?= e((string) ($row['owner_email'] ?? '')) ?></td>
                <td><?= e((string) ($row['platform'] ?? '')) ?></td>
                <td><?= e(admin_format_datetime((string) ($row['last_message'] ?? ''))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php admin_render_table_end(); ?>
        <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
    <?php endif; ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
