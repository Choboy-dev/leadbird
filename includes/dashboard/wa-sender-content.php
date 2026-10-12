<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $sender */
/** @var array<string, string> $values */
/** @var array{ok: bool, profile: array<string, mixed>, error?: string} $profileResult */
/** @var string $photoUrl */
/** @var bool $canEdit */
/** @var string|null $error */
/** @var string|null $success */
/** @var string $accountId */
/** @var string $senderUrl */
/** @var string $sendersUrl */
/** @var string $reconnectUrl */

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
[$statusLabel, $statusTone] = $statusMeta((string) ($sender['status'] ?? 'unknown'));
$quality = $qualityMeta($sender['quality'] ?? null);
$phone = (string) ($sender['phone'] ?? '');
$displayName = (string) ($sender['display_name'] ?? '');
$nameChangeUrl = !empty($sender['waba_id'])
    ? 'https://business.facebook.com/wa/manage/phone-numbers/?waba_id=' . rawurlencode((string) $sender['waba_id'])
    : 'https://business.facebook.com/wa/manage/home/';
$disabled = $canEdit ? '' : ' disabled';
$initial = strtoupper(mb_substr($displayName !== '' ? $displayName : 'W', 0, 1));
?>
<div class="ws-page ws-page--detail">
    <a class="ws-back" href="<?= e($sendersUrl) ?>">
        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Senders
    </a>

    <h1 class="ws-title font-heading">WhatsApp Sender: <?= e($phone) ?></h1>

    <?php if ($success): ?>
        <div class="ws-alert ws-alert--ok" role="status"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <div class="ws-alert ws-alert--bad" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!$canEdit): ?>
        <div class="ws-alert ws-alert--bad" role="alert">
            Your WhatsApp connection has expired, so we can't load or change this profile.
            <a class="ws-link" href="<?= e($reconnectUrl) ?>">Reconnect WhatsApp</a>
        </div>
    <?php elseif (!$profileResult['ok'] && !empty($profileResult['error'])): ?>
        <div class="ws-alert ws-alert--bad" role="alert"><?= e((string) $profileResult['error']) ?></div>
    <?php endif; ?>

    <section class="ws-card ws-section">
        <h2 class="ws-section-title font-heading">General information</h2>
        <dl class="ws-facts">
            <div>
                <dt>Status</dt>
                <dd>
                    <span class="ws-status ws-status--<?= e($statusTone) ?>">
                        <?php if ($statusTone === 'ok'): ?>
                            <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="currentColor"/><path d="m5 8.2 2 2 4-4.2" fill="none" stroke="var(--brand-white)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?php endif; ?>
                        <?= e($statusLabel) ?>
                    </span>
                </dd>
            </div>
            <div>
                <dt>Business display name</dt>
                <dd><?= e($displayName !== '' ? $displayName : '—') ?></dd>
            </div>
            <div>
                <dt>Quality rating</dt>
                <dd>
                    <?php if ($quality !== null): ?>
                        <span class="ws-quality"><span class="ws-dot ws-dot--<?= e($quality[1]) ?>" aria-hidden="true"></span><?= e($quality[0]) ?></span>
                    <?php else: ?>
                        <span class="ws-muted">—</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Daily limit</dt>
                <dd>
                    <?php if (!$canEdit): ?>
                        <span class="ws-muted">—</span>
                    <?php elseif (($sender['daily_limit'] ?? null) === null): ?>
                        Unlimited
                    <?php else: ?>
                        <?= number_format((int) $sender['daily_limit']) ?> people a day
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Throughput</dt>
                <dd>
                    <?php if (($sender['throughput'] ?? null) !== null): ?>
                        <?= number_format((int) $sender['throughput']) ?> messages per second
                    <?php else: ?>
                        <span class="ws-muted">—</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </section>

    <form id="ws-profile-form" class="ws-card ws-section" method="post" action="<?= e($senderUrl) ?>" enctype="multipart/form-data" data-ws-profile<?= $error !== null && $canEdit ? ' data-ws-dirty' : '' ?>>
        <?= csrf_field() ?>
        <h2 class="ws-section-title font-heading">Business profile</h2>

        <div class="ws-note">
            <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="currentColor"/><path d="M8 7.2v4M8 4.8v.1" stroke="var(--brand-white)" stroke-width="1.6" stroke-linecap="round"/></svg>
            <p>To change the display name for this number, request it in <a class="ws-link" href="<?= e($nameChangeUrl) ?>" target="_blank" rel="noopener">Meta WhatsApp Manager</a>. Meta reviews every name change.</p>
        </div>

        <div class="ws-field ws-field--half">
            <label for="ws-display-name">Business display name</label>
            <input id="ws-display-name" type="text" value="<?= e($displayName) ?>" readonly disabled>
        </div>

        <p class="ws-section-copy">Customers see this when they tap your name in WhatsApp. A complete profile helps people trust your broadcasts and reply to them.</p>

        <div class="ws-form-grid">
            <div class="ws-field">
                <span class="ws-label">Profile photo <span class="ws-optional">- Optional</span></span>
                <span class="ws-hint">JPG or PNG, 640 × 640 pixels or larger, square</span>
                <div class="ws-photo">
                    <div class="ws-photo-frame" data-ws-photo-frame>
                        <?php if ($photoUrl !== ''): ?>
                            <img src="<?= e($photoUrl) ?>" alt="Current profile photo" data-ws-photo-img>
                        <?php else: ?>
                            <span class="ws-photo-initial" aria-hidden="true"><?= e($initial) ?></span>
                        <?php endif; ?>
                    </div>
                    <label class="ws-btn ws-btn--ghost ws-btn--small<?= $canEdit ? '' : ' is-disabled' ?>">
                        Select file
                        <input class="sr-only" type="file" name="photo" accept="image/jpeg,image/png" data-ws-photo-input<?= $disabled ?>>
                    </label>
                </div>
                <span class="ws-hint" data-ws-photo-name></span>
            </div>

            <div class="ws-field">
                <label for="ws-address">Business address <span class="ws-optional">- Optional</span></label>
                <input id="ws-address" name="address" type="text" maxlength="256" value="<?= e($values['address']) ?>" placeholder="e.g. 12 Admiralty Way, Lekki Phase 1, Lagos"<?= $disabled ?>>
            </div>

            <div class="ws-field">
                <label for="ws-website-1">Business website <span class="ws-optional">- Optional</span></label>
                <input id="ws-website-1" name="website_1" type="url" maxlength="256" value="<?= e($values['website_1']) ?>" placeholder="https://www.example.com"<?= $disabled ?>>
            </div>

            <div class="ws-field">
                <label for="ws-website-2">Additional website <span class="ws-optional">- Optional</span></label>
                <input id="ws-website-2" name="website_2" type="url" maxlength="256" value="<?= e($values['website_2']) ?>" placeholder="https://instagram.com/yourshop"<?= $disabled ?>>
            </div>

            <div class="ws-field">
                <label for="ws-email">Business email <span class="ws-optional">- Optional</span></label>
                <input id="ws-email" name="email" type="email" maxlength="128" value="<?= e($values['email']) ?>" placeholder="hello@yourshop.com"<?= $disabled ?>>
            </div>

            <div class="ws-field">
                <label for="ws-vertical">Category <span class="ws-optional">- Optional</span></label>
                <select id="ws-vertical" name="vertical" class="ws-select"<?= $disabled ?>>
                    <option value="">Choose a category</option>
                    <?php foreach (WhatsAppBroadcastService::PROFILE_VERTICALS as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= $values['vertical'] === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ws-field">
                <label for="ws-description">Business description <span class="ws-optional">- Optional</span></label>
                <textarea id="ws-description" name="description" rows="4" maxlength="<?= WhatsAppBroadcastService::PROFILE_DESCRIPTION_MAX ?>" data-ws-count placeholder="What you sell, where you deliver, and when you're open."<?= $disabled ?>><?= e($values['description']) ?></textarea>
                <span class="ws-hint">Up to <?= WhatsAppBroadcastService::PROFILE_DESCRIPTION_MAX ?> characters · currently <span data-ws-count-for="ws-description"><?= mb_strlen($values['description']) ?></span></span>
            </div>

            <div class="ws-field">
                <label for="ws-about"><span class="ws-required" aria-hidden="true">*</span> About</label>
                <input id="ws-about" name="about" type="text" required maxlength="<?= WhatsAppBroadcastService::PROFILE_ABOUT_MAX ?>" value="<?= e($values['about']) ?>" data-ws-count placeholder="e.g. Ankara gowns, made to fit. Delivery across Lagos."<?= $disabled ?>>
                <span class="ws-hint">Up to <?= WhatsAppBroadcastService::PROFILE_ABOUT_MAX ?> characters · currently <span data-ws-count-for="ws-about"><?= mb_strlen($values['about']) ?></span></span>
            </div>
        </div>
    </form>

    <div class="ws-form-actions">
        <button type="submit" form="ws-profile-form" class="ws-btn ws-btn--primary" data-ws-save disabled>Update sender</button>
        <a class="ws-btn ws-btn--ghost" href="<?= e($sendersUrl) ?>">Cancel</a>
        <form method="post" action="<?= e(app_route('connected-accounts/disconnect')) ?>" class="ws-disconnect">
            <?= csrf_field() ?>
            <input type="hidden" name="account_id" value="<?= e($accountId) ?>">
            <input type="hidden" name="return_to" value="<?= e($sendersUrl) ?>">
            <button
                type="submit"
                class="ws-danger-link"
                data-lb-confirm="Lead Bird will stop sending and replying from this number. Your number stays on WhatsApp, and you can connect it again any time."
                data-lb-confirm-title="Disconnect this sender?"
                data-lb-confirm-label="Disconnect"
                data-lb-confirm-tone="danger"
            >Disconnect sender</button>
        </form>
    </div>
</div>
