<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $templates */
/** @var bool $hasNumber */
/** @var bool $preview */
/** @var array<string, string> $previewQuery */
/** @var string $templatesUrl */
/** @var string $sendersUrl */
/** @var string|null $createUrl */
/** @var string|null $managerUrl */
/** @var string|null $syncError */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */

$languageNames = [
    'en' => 'English',
    'en_US' => 'English (US)',
    'en_GB' => 'English (UK)',
    'fr' => 'French',
    'ha' => 'Hausa',
    'yo' => 'Yoruba',
    'ig' => 'Igbo',
    'ar' => 'Arabic',
    'pt_BR' => 'Portuguese (Brazil)',
    'pt_PT' => 'Portuguese',
    'es' => 'Spanish',
    'sw' => 'Swahili',
    'zh_CN' => 'Chinese',
    'de' => 'German',
];
$languageLabel = static fn(string $code): string => $languageNames[$code] ?? $code;

$typeIcons = [
    'text' => '<path d="M3 3.5h10M8 3.5v9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
    'media' => '<rect x="2" y="3" width="12" height="10" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><circle cx="5.75" cy="6.5" r="1.1" fill="currentColor"/><path d="m3 12 3.5-3.5 2.5 2.5 1.5-1.5L13.5 12" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>',
    'quick_reply' => '<path d="M2.5 13V7.5h2.5V13zM5 7.5 7.5 2.5c1.1 0 1.8.8 1.6 1.9L8.7 6.5h3.6c.9 0 1.5.8 1.3 1.6l-1 4c-.2.6-.7.9-1.3.9H5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>',
    'call_to_action' => '<rect x="2" y="2.5" width="12" height="8" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="m8.5 8 1.4 5.5 1-2 2 1.9 1-1-1.9-2 2-1z" fill="currentColor"/>',
    'list_picker' => '<path d="M6 4.5h7.5M6 8h7.5M6 11.5h7.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="3" cy="4.5" r="0.9" fill="currentColor"/><circle cx="3" cy="8" r="0.9" fill="currentColor"/><circle cx="3" cy="11.5" r="0.9" fill="currentColor"/>',
    'carousel' => '<rect x="4.5" y="3" width="7" height="10" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2 5v6M14 5v6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
    'card' => '<rect x="2.5" y="2" width="11" height="12" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2.5 7.5h11M5 10h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
    'whatsapp_card' => '<rect x="2.5" y="2" width="11" height="12" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M5 5h6M5 8h6M2.5 10.5h11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
    'catalog' => '<path d="M3.5 5.5h9l-.8 8h-7.4zM6 5.5V4.5a2 2 0 0 1 4 0v1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>',
    'flow' => '<rect x="2" y="2" width="5" height="4" rx="1" fill="none" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="10" width="5" height="4" rx="1" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M4.5 6v3.5a1 1 0 0 0 1 1H9" fill="none" stroke="currentColor" stroke-width="1.4"/>',
    'location' => '<path d="M8 14s4.5-4.2 4.5-7.5a4.5 4.5 0 0 0-9 0C3.5 9.8 8 14 8 14z" fill="none" stroke="currentColor" stroke-width="1.4"/><circle cx="8" cy="6.5" r="1.5" fill="currentColor"/>',
    'authentication' => '<rect x="3" y="7" width="10" height="7" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" fill="none" stroke="currentColor" stroke-width="1.4"/>',
];
$typeLabels = [
    'text' => 'Text',
    'media' => 'Media',
    'quick_reply' => 'Quick reply',
    'call_to_action' => 'Call to action',
    'list_picker' => 'List Picker',
    'card' => 'Card',
    'whatsapp_card' => 'WhatsApp Card',
    'carousel' => 'Carousel',
    'catalog' => 'Catalog',
    'flow' => 'Flows',
    'location' => 'Location',
    'authentication' => 'Authentication',
];
$categoryLabels = ['MARKETING' => 'Marketing', 'UTILITY' => 'Utility', 'AUTHENTICATION' => 'Login code'];

$rejectReasons = [
    'ABUSIVE_CONTENT' => 'WhatsApp found content that breaks its rules.',
    'INVALID_FORMAT' => 'The layout isn’t allowed, for example a placeholder at the very start or end of the message.',
    'PROMOTIONAL' => 'WhatsApp says this message is promotional, so it has to be a Marketing template.',
    'TAG_CONTENT_MISMATCH' => 'The message doesn’t match the category it was submitted under.',
    'INCORRECT_CATEGORY' => 'The message doesn’t match the category it was submitted under.',
    'SCAM' => 'WhatsApp flagged this message as possibly misleading.',
];
$approval = static function (array $template) use ($rejectReasons): array {
    $status = strtoupper((string) $template['status']);

    return match ($status) {
        'APPROVED' => ['approved', 'ok', 'Approved', 'Approved by WhatsApp. You can send it to anyone who agreed to hear from you, even if they’ve never messaged you.'],
        'PENDING', 'IN_APPEAL' => ['review', 'wait', 'In review', 'WhatsApp is checking this template. It usually takes a few minutes and can take up to 24 hours.'],
        'REJECTED' => ['rejected', 'bad', 'Rejected', ($rejectReasons[strtoupper((string) ($template['rejected_reason'] ?? ''))] ?? 'WhatsApp rejected this template.') . ' You can’t send it.'],
        'PAUSED' => ['stopped', 'bad', 'Paused', 'WhatsApp paused this template after customers blocked or reported it. You can’t send it for now.'],
        'DISABLED' => ['stopped', 'bad', 'Disabled', 'WhatsApp turned this template off after repeated customer complaints. You can’t send it.'],
        'DRAFT' => in_array((string) ($template['content_type'] ?? ''), WhatsAppBroadcastService::REPLY_ONLY_TYPES, true)
            ? ['draft', 'muted', 'Not submitted', 'List pickers don’t need WhatsApp approval. You can send one as a reply within 24 hours of a customer’s message.']
            : ['draft', 'muted', 'Not submitted', 'Saved in Lead Bird but not sent to WhatsApp yet. Open it to finish it and submit it for approval.'],
        default => ['stopped', 'muted', 'Not available', 'You can’t send this template right now.'],
    };
};

$languages = [];
$types = [];
foreach ($templates as $template) {
    $languages[(string) $template['language']] = $languageLabel((string) $template['language']);
    $type = (string) ($template['content_type'] ?? 'text');
    $types[$type] = $typeLabels[$type] ?? 'Text';
}
asort($languages);
asort($types);

$externalIcon = '<svg class="ws-ext" viewBox="0 0 16 16" width="13" height="13" aria-hidden="true"><path d="M9 2.5h4.5V7M13.5 2.5 7.5 8.5M11.5 9.5v3a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1h3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>
<div class="ws-page ws-page--full" data-wt-root data-endpoint="<?= e(app_route('broadcasts/action')) ?>" data-preview="<?= $preview ? '1' : '0' ?>">
    <header class="ws-head">
        <h1 class="ws-title font-heading">WhatsApp Templates</h1>
        <?php if ($createUrl !== null): ?>
            <a class="ws-btn ws-btn--primary" href="<?= e($createUrl) ?>">Create new</a>
        <?php endif; ?>
    </header>

    <?php if ($preview): ?>
        <div class="ws-alert ws-alert--preview" role="status">
            <strong>Preview mode.</strong> Nothing here is saved or sent to WhatsApp.
            <a class="ws-link" href="<?= e(app_route('broadcasts/templates')) ?>">Leave preview</a>
        </div>
    <?php endif; ?>

    <?php if ($flashSuccess): ?>
        <div class="ws-alert ws-alert--ok" role="status"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="ws-alert ws-alert--bad" role="alert"><?= e($flashError) ?></div>
    <?php endif; ?>

    <?php if ($syncError): ?>
        <div class="ws-alert ws-alert--bad" role="alert"><?= e($syncError) ?></div>
    <?php endif; ?>

    <div class="ws-intro">
        <p>Templates are messages WhatsApp has checked and approved. You need an approved template to message people who haven’t messaged you in the last 24 hours, like when you send a bulk message to your customer list.</p>
    </div>

    <?php if (!$hasNumber): ?>
        <section class="ws-card ws-empty">
            <div class="ws-empty-icon" aria-hidden="true"><?= brand_whatsapp_icon_html('ws-empty-logo', 40) ?></div>
            <h2 class="ws-empty-title">Connect a WhatsApp number first</h2>
            <p class="ws-empty-copy">Templates belong to your WhatsApp Business number. Connect one as a sender, then come back here.</p>
            <a class="ws-btn ws-btn--primary" href="<?= e($sendersUrl) ?>">Go to Senders</a>
        </section>
    <?php elseif ($templates === []): ?>
        <section class="ws-card ws-empty">
            <div class="ws-empty-icon" aria-hidden="true"><?= brand_whatsapp_icon_html('ws-empty-logo', 40) ?></div>
            <h2 class="ws-empty-title">No templates yet</h2>
            <p class="ws-empty-copy">Create your first template. Once WhatsApp approves it, you can use it to send bulk messages.</p>
            <?php if ($createUrl !== null): ?>
                <a class="ws-btn ws-btn--primary" href="<?= e($createUrl) ?>">Create new</a>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="wt-filters" aria-label="Filter templates">
            <div class="ws-field">
                <label for="wt-language">Language</label>
                <select id="wt-language" class="wt-select" data-wt-filter="lang">
                    <option value="">All languages</option>
                    <?php foreach ($languages as $code => $label): ?>
                        <option value="<?= e((string) $code) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ws-field">
                <label for="wt-type">Content type</label>
                <select id="wt-type" class="wt-select" data-wt-filter="type">
                    <option value="">All types</option>
                    <?php foreach ($types as $type => $label): ?>
                        <option value="<?= e((string) $type) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ws-field">
                <label for="wt-approval">WhatsApp approval</label>
                <select id="wt-approval" class="wt-select" data-wt-filter="approval">
                    <option value="">Any status</option>
                    <option value="approved">Approved</option>
                    <option value="review">In review</option>
                    <option value="rejected">Rejected</option>
                    <option value="stopped">Paused or disabled</option>
                    <option value="draft">Not submitted</option>
                </select>
            </div>
            <div class="ws-field">
                <label for="wt-updated">Last updated</label>
                <select id="wt-updated" class="wt-select" data-wt-filter="updated">
                    <option value="">Any time</option>
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                </select>
            </div>
        </section>

        <div class="wt-toolbar">
            <div class="wt-search">
                <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><circle cx="7" cy="7" r="4.75" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m10.5 10.5 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                <label class="sr-only" for="wt-search">Search by name or message</label>
                <input id="wt-search" type="search" placeholder="Search by name or message" autocomplete="off" data-wt-search>
            </div>
            <div class="wt-paging">
                <label class="wt-page-size" for="wt-size">
                    <span>Page size</span>
                    <select id="wt-size" class="wt-select wt-select--size" data-wt-size>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50" selected>50</option>
                    </select>
                </label>
                <p class="wt-count" data-wt-count aria-live="polite"></p>
            </div>
        </div>

        <section class="ws-card ws-card--flush">
            <div class="ws-table-wrap">
                <table class="ws-table wt-table">
                    <thead>
                        <tr>
                            <th scope="col">Name / Template ID</th>
                            <th scope="col">Language</th>
                            <th scope="col">Content type</th>
                            <th scope="col">Category</th>
                            <th scope="col">WhatsApp approval</th>
                            <th scope="col">Last updated</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $index => $template): ?>
                            <?php
                            $type = (string) ($template['content_type'] ?? 'text');
                            $type = isset($typeLabels[$type]) ? $type : 'text';
                            [$approvalKey, $tone, $approvalLabel, $approvalHelp] = $approval($template);
                            $updated = strtotime((string) ($template['updated_at'] ?? $template['created_at'] ?? 'now')) ?: time();
                            $ref = WhatsAppBroadcastService::templateRef($businessId, (string) $template['name'], (string) $template['language']);
                            $tipId = 'wt-tip-' . $index;
                            $openUrl = app_route('broadcasts/template', ['id' => (string) $template['id']] + $previewQuery);
                            ?>
                            <tr
                                data-wt-row
                                data-id="<?= e((string) $template['id']) ?>"
                                data-name="<?= e((string) $template['name']) ?>"
                                data-url="<?= e($openUrl) ?>"
                                data-ref="<?= e($ref) ?>"
                                data-search="<?= e(mb_strtolower($ref . ' ' . (string) $template['name'] . ' ' . (string) $template['title'] . ' ' . (string) $template['body'])) ?>"
                                data-lang="<?= e((string) $template['language']) ?>"
                                data-type="<?= e($type) ?>"
                                data-approval="<?= e($approvalKey) ?>"
                                data-updated="<?= $updated ?>"
                            >
                                <td class="wt-name">
                                    <b><a href="<?= e($openUrl) ?>"><?= e((string) $template['name']) ?></a></b>
                                    <span class="wt-id"><?= e($ref) ?></span>
                                </td>
                                <td><?= e($languageLabel((string) $template['language'])) ?></td>
                                <td>
                                    <span class="wt-type"><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><?= $typeIcons[$type] ?></svg><?= e($typeLabels[$type]) ?></span>
                                </td>
                                <td><?= in_array($type, WhatsAppBroadcastService::REPLY_ONLY_TYPES, true) ? '<span class="ws-muted">—</span>' : e($categoryLabels[strtoupper((string) $template['category'])] ?? ucfirst(strtolower((string) $template['category']))) ?></td>
                                <td>
                                    <span class="wt-pill-wrap">
                                        <span class="ws-status ws-status--<?= e($tone) ?>" tabindex="0" aria-describedby="<?= e($tipId) ?>">
                                            <?php if ($tone === 'ok'): ?>
                                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="currentColor"/><path d="m5 8.2 2 2 4-4.2" fill="none" stroke="var(--brand-white)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <?php elseif ($approvalKey === 'draft'): ?>
                                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="8" cy="8" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="2.4 2"/></svg>
                                            <?php else: ?>
                                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="8" cy="8" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 7.25v3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="8" cy="5.1" r="0.9" fill="currentColor"/></svg>
                                            <?php endif; ?>
                                            <?= e($approvalLabel) ?>
                                        </span>
                                        <span class="wt-tip" role="tooltip" id="<?= e($tipId) ?>"><?= e($approvalHelp) ?></span>
                                    </span>
                                </td>
                                <td><?= e(date('F j, Y', $updated)) ?></td>
                                <td class="wt-more">
                                    <button type="button" class="wt-more-btn" aria-haspopup="menu" aria-expanded="false" aria-label="More actions for <?= e((string) $template['name']) ?>" data-wt-menu>
                                        <svg viewBox="0 0 16 16" width="18" height="18" aria-hidden="true"><circle cx="8" cy="3.5" r="1.4" fill="currentColor"/><circle cx="8" cy="8" r="1.4" fill="currentColor"/><circle cx="8" cy="12.5" r="1.4" fill="currentColor"/></svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="wt-none" data-wt-none hidden>
                            <td colspan="7">
                                No templates match these filters.
                                <button type="button" class="ws-link wt-clear" data-wt-clear>Clear filters</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <nav class="wt-pager" aria-label="Template pages" data-wt-pager hidden>
            <button type="button" class="ws-btn ws-btn--ghost ws-btn--small" data-wt-prev>Previous</button>
            <button type="button" class="ws-btn ws-btn--ghost ws-btn--small" data-wt-next>Next</button>
        </nav>

        <?php if (!$preview): ?>
            <p class="ws-foot">
                Approval updates come from WhatsApp every few minutes.
                <a class="ws-link" href="<?= e($templatesUrl . '?refresh=1') ?>">Refresh now</a>
            </p>
        <?php endif; ?>

        <div class="wt-menu" role="menu" hidden data-wt-menu-pop>
            <button type="button" role="menuitem" data-wt-act="open">Open</button>
            <button type="button" role="menuitem" data-wt-act="copy-name">Copy template name</button>
            <button type="button" role="menuitem" data-wt-act="copy-id">Copy template ID</button>
            <?php if ($managerUrl !== null): ?>
                <a role="menuitem" href="<?= e($managerUrl) ?>" target="_blank" rel="noopener" data-wt-real-only>Open in WhatsApp Manager<?= $externalIcon ?></a>
            <?php endif; ?>
            <button type="button" role="menuitem" class="wt-menu-danger" data-wt-act="delete" data-wt-real-only>Delete</button>
        </div>
    <?php endif; ?>
</div>
