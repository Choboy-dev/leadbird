<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var array<string, mixed>|null $deletionRequest */
/** @var string $requestedCode */

$statusLabels = [
    'pending' => 'In progress',
    'completed' => 'Completed',
    'failed' => 'Needs attention',
];
?>
<div class="legal-page bg-white">
    <div class="legal-shell min-h-screen flex flex-col">
        <?php require __DIR__ . '/../home/marketing-header.php'; ?>

        <main class="legal-main flex-1">
            <article class="legal-article">
                <header class="legal-article-header">
                    <h1 class="legal-title font-heading">Delete Your Data</h1>
                    <p class="legal-updated">Last updated: August 10, 2026</p>
                </header>

                <?php if ($requestedCode !== ''): ?>
                    <section class="legal-section">
                        <h2 class="legal-section-title font-heading">Deletion request status</h2>
                        <?php if ($deletionRequest === null): ?>
                            <p>We could not find a deletion request with the code <strong><?= e($requestedCode) ?></strong>. Check the code and try again, or email <a href="mailto:support@leadbird.app" class="legal-link">support@leadbird.app</a>.</p>
                        <?php else: ?>
                            <?php $status = (string) ($deletionRequest['status'] ?? 'pending'); ?>
                            <ul class="legal-list">
                                <li><strong>Confirmation code:</strong> <?= e((string) $deletionRequest['confirmation_code']) ?></li>
                                <li><strong>Status:</strong> <?= e($statusLabels[$status] ?? ucfirst($status)) ?></li>
                                <li><strong>Requested:</strong> <?= e(date('F j, Y', strtotime((string) ($deletionRequest['requested_at'] ?? 'now')))) ?></li>
                                <?php if (!empty($deletionRequest['completed_at'])): ?>
                                    <li><strong>Completed:</strong> <?= e(date('F j, Y', strtotime((string) $deletionRequest['completed_at']))) ?></li>
                                <?php endif; ?>
                            </ul>
                            <?php if ($status === 'completed'): ?>
                                <p>All Facebook, Instagram, and WhatsApp data linked to this request has been removed from Lead Bird.</p>
                            <?php elseif ($status === 'failed'): ?>
                                <p>Something went wrong while processing this request. Email <a href="mailto:support@leadbird.app" class="legal-link">support@leadbird.app</a> with the code above and we will finish it manually within 30 days.</p>
                            <?php else: ?>
                                <p>This request is being processed. Refresh this page in a few minutes.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <section class="legal-section">
                    <h2 class="legal-section-title font-heading">What this page is for</h2>
                    <p>Lead Bird connects to your Facebook Page, Instagram Business account, and WhatsApp Business number so an AI assistant can reply to your customers. This page explains how to remove everything we hold that came from Meta, and how to delete your Lead Bird account entirely.</p>
                </section>

                <section class="legal-section">
                    <h2 class="legal-section-title font-heading">Option 1 — Remove Lead Bird from Facebook</h2>
                    <p>This is the fastest route and it works even if you cannot sign in to Lead Bird:</p>
                    <ul class="legal-list">
                        <li>Go to <strong>Facebook → Settings &amp; Privacy → Settings</strong></li>
                        <li>Open <strong>Apps and Websites</strong></li>
                        <li>Find <strong>Lead Bird</strong> in the list</li>
                        <li>Click <strong>Remove</strong>, then tick the box to delete your activity</li>
                    </ul>
                    <p>Facebook notifies us automatically. We immediately delete your stored access tokens, disconnect your Pages, Instagram accounts, and WhatsApp numbers, and erase every message and customer record that came from Meta. You will receive a confirmation code you can enter on this page to check the status.</p>
                </section>

                <section class="legal-section">
                    <h2 class="legal-section-title font-heading">Option 2 — Disconnect inside Lead Bird</h2>
                    <ul class="legal-list">
                        <li>Sign in and open <strong>Connected Accounts</strong></li>
                        <li>Click <strong>Disconnect</strong> on any Facebook, Instagram, or WhatsApp channel</li>
                    </ul>
                    <p>This revokes the access token and stops us receiving new messages on that channel right away.</p>
                </section>

                <section class="legal-section">
                    <h2 class="legal-section-title font-heading">Option 3 — Delete your whole Lead Bird account</h2>
                    <ul class="legal-list">
                        <li>Sign in and open <strong><a href="<?= e(app_route('settings')) ?>" class="legal-link">Settings</a></strong></li>
                        <li>Scroll to <strong>Delete account</strong></li>
                        <li>Type <strong>Delete</strong> to confirm</li>
                    </ul>
                    <p>This permanently removes your account, your businesses, your products and offers, your customers, and every conversation. It cannot be undone.</p>
                </section>

                <section class="legal-section">
                    <h2 class="legal-section-title font-heading">Option 4 — Email us</h2>
                    <p>If you cannot use any option above, email <a href="mailto:support@leadbird.app" class="legal-link">support@leadbird.app</a> from the address on your account and ask us to delete your data. We complete these requests within 30 days.</p>
                </section>

                <section class="legal-section">
                    <h2 class="legal-section-title font-heading">What gets deleted</h2>
                    <ul class="legal-list">
                        <li>Access tokens for your Facebook, Instagram, and WhatsApp accounts</li>
                        <li>Your connected Pages, Instagram Business accounts, and WhatsApp numbers</li>
                        <li>Messages exchanged with your customers on those platforms</li>
                        <li>Customer names, profile photos, and platform IDs collected from those messages</li>
                        <li>Webhook subscriptions, so we stop receiving anything new</li>
                    </ul>
                    <p>Backups are rotated out within 90 days. We keep only what the law requires us to keep, such as payment records for tax purposes.</p>
                </section>

                <section class="legal-section">
                    <h2 class="legal-section-title font-heading">Questions?</h2>
                    <p>Email <a href="mailto:support@leadbird.app" class="legal-link">support@leadbird.app</a> and a person will reply.</p>
                </section>

                <p class="legal-closing">Your data is yours. Take it back whenever you want.</p>
            </article>
        </main>
    </div>

    <?php require __DIR__ . '/../home/footer.php'; ?>
</div>
