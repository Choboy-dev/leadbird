<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $senders */
/** @var array<string, array<string, mixed>> $wabas */
/** @var string $connectUrl */
/** @var string $reconnectUrl */
/** @var string $sendersUrl */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */

$statusMeta = static fn(string $status): array => match ($status) {
    'online' => ['Online', 'ok'],
    'expired' => ['Disconnected', 'bad'],
    'restricted' => ['Restricted', 'bad'],
    'pending' => ['Pending', 'wait'],
    'offline' => ['Offline', 'muted'],
    default => ['Unknown', 'muted'],
};
$qualityMeta = static fn(?string $quality): ?array => match ($quality) {
    'GREEN' => ['High', 'ok'],
    'YELLOW' => ['Medium', 'wait'],
    'RED' => ['Low', 'bad'],
    default => null,
};
$externalIcon = '<svg class="ws-ext" viewBox="0 0 16 16" width="13" height="13" aria-hidden="true"><path d="M9 2.5h4.5V7M13.5 2.5 7.5 8.5M11.5 9.5v3a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1h3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>
<div class="ws-page">
    <header class="ws-head">
        <h1 class="ws-title font-heading">WhatsApp Senders</h1>
        <a class="ws-btn ws-btn--primary" href="<?= e($connectUrl) ?>">Create new sender</a>
    </header>

    <?php if ($flashSuccess): ?>
        <div class="ws-alert ws-alert--ok" role="status"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="ws-alert ws-alert--bad" role="alert"><?= e($flashError) ?></div>
    <?php endif; ?>

    <div class="ws-intro">
        <p>To send bulk messages, connect your WhatsApp Business number as a sender.</p>
        <p>Each bulk message must use a template WhatsApp has approved. Open the <a class="ws-link" href="<?= e(app_route('broadcasts/templates')) ?>">Template builder</a> to get started.</p>
    </div>

    <?php if ($senders === []): ?>
        <section class="ws-card ws-empty">
            <div class="ws-empty-icon" aria-hidden="true"><?= brand_whatsapp_icon_html('ws-empty-logo', 40) ?></div>
            <h2 class="ws-empty-title">No sender yet</h2>
            <p class="ws-empty-copy">Connect your WhatsApp Business number through Meta to start sending bulk messages.</p>
            <a class="ws-btn ws-btn--primary" href="<?= e($connectUrl) ?>">Create new sender</a>
        </section>
    <?php else: ?>
        <?php foreach ($wabas as $waba): ?>
            <section class="ws-card ws-ids" aria-label="Meta account details">
                <div class="ws-id">
                    <h2 class="ws-id-label">WhatsApp Business Account ID</h2>
                    <a class="ws-link ws-id-value" href="https://business.facebook.com/wa/manage/home/?waba_id=<?= e(rawurlencode((string) $waba['waba_id'])) ?>" target="_blank" rel="noopener"><?= e((string) $waba['waba_id']) ?><?= $externalIcon ?></a>
                    <?php if (!empty($waba['waba_name'])): ?>
                        <p class="ws-id-sub"><?= e((string) $waba['waba_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ws-id">
                    <h2 class="ws-id-label">Meta Business Manager ID</h2>
                    <?php if (!empty($waba['business_manager_id'])): ?>
                        <a class="ws-link ws-id-value" href="https://business.facebook.com/settings/info?business_id=<?= e(rawurlencode((string) $waba['business_manager_id'])) ?>" target="_blank" rel="noopener"><?= e((string) $waba['business_manager_id']) ?><?= $externalIcon ?></a>
                        <?php if (!empty($waba['business_manager_name'])): ?>
                            <p class="ws-id-sub"><?= e((string) $waba['business_manager_name']) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="ws-id-sub">Shows once your connection is active.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <section class="ws-card ws-card--flush">
            <div class="ws-table-wrap">
                <table class="ws-table">
                    <thead>
                        <tr>
                            <th scope="col">WhatsApp number</th>
                            <th scope="col">Business display name</th>
                            <th scope="col">Sender status</th>
                            <th scope="col">Quality rating</th>
                            <th scope="col">Daily limit</th>
                            <th scope="col">Throughput</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($senders as $sender): ?>
                            <?php
                            [$statusLabel, $statusTone] = $statusMeta((string) $sender['status']);
                            $quality = $qualityMeta($sender['quality']);
                            ?>
                            <tr>
                                <td class="ws-number"><?= e((string) $sender['phone']) ?></td>
                                <td><?= e((string) $sender['display_name']) ?></td>
                                <td>
                                    <span class="ws-status ws-status--<?= e($statusTone) ?>">
                                        <?php if ($statusTone === 'ok'): ?>
                                            <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="currentColor"/><path d="m5 8.2 2 2 4-4.2" fill="none" stroke="var(--brand-white)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <?php endif; ?>
                                        <?= e($statusLabel) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($quality !== null): ?>
                                        <span class="ws-quality"><span class="ws-dot ws-dot--<?= e($quality[1]) ?>" aria-hidden="true"></span><?= e($quality[0]) ?></span>
                                    <?php else: ?>
                                        <span class="ws-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sender['status'] === 'expired'): ?>
                                        <span class="ws-muted">—</span>
                                    <?php elseif ($sender['daily_limit'] === null): ?>
                                        Unlimited
                                    <?php else: ?>
                                        <?= number_format((int) $sender['daily_limit']) ?> people
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sender['throughput'] !== null): ?>
                                        <?= number_format((int) $sender['throughput']) ?> MPS
                                    <?php else: ?>
                                        <span class="ws-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="ws-actions">
                                    <?php if ($sender['status'] === 'expired'): ?>
                                        <a class="ws-action" href="<?= e($reconnectUrl) ?>">Reconnect</a>
                                    <?php else: ?>
                                        <a class="ws-action" href="<?= e(app_route('broadcasts/sender', ['id' => (string) $sender['account_id']])) ?>">Edit sender</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <p class="ws-foot">
            Details come from Meta and update every few minutes.
            <a class="ws-link" href="<?= e($sendersUrl . '?refresh=1') ?>">Refresh now</a>
        </p>
    <?php endif; ?>
</div>
