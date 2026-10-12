<?php

declare(strict_types=1);

/** @var array<string, mixed> $spec */
/** @var array<string, mixed>|null $template */
/** @var string $source */
/** @var string|null $status */
/** @var string|null $lockedReason */
/** @var string|null $wabaId */
/** @var string|null $templateRef */
/** @var string $templatesUrl */
/** @var bool $preview */
/** @var string|null $editUrl */
/** @var array<string, string> $previewQuery */
/** @var list<array{key: string, label: string, keywords: list<string>, bodies: list<array{text: string, samples: array<string, string>}>}> $hannahLibrary */

$isExisting = $template !== null;
$locked = $lockedReason !== null;
$disabled = $locked ? ' disabled' : '';
$languages = WhatsAppBroadcastService::TEMPLATE_LANGUAGES;
if (!isset($languages[$spec['language']])) {
    $languages[$spec['language']] = $spec['language'];
}

$statusPill = match ($status) {
    'APPROVED' => ['ok', 'Approved'],
    'PENDING', 'IN_APPEAL' => ['wait', 'In review'],
    'REJECTED' => ['bad', 'Rejected'],
    'PAUSED' => ['bad', 'Paused'],
    'DISABLED' => ['bad', 'Disabled'],
    null, 'DRAFT' => ['muted', 'Not submitted'],
    default => ['muted', 'Not available'],
};
$nameEditable = $source === 'copy';
$categoryLocked = $status === 'APPROVED';
$typeLabels = [
    'text' => 'Text',
    'quick_reply' => 'Quick reply',
    'call_to_action' => 'Call to action',
    'media' => 'Media',
    'list_picker' => 'List Picker',
    'card' => 'Card',
    'whatsapp_card' => 'WhatsApp Card',
    'carousel' => 'Carousel',
    'catalog' => 'Catalog',
    'authentication' => 'Authentication',
    'flow' => 'Flows',
];
$type = (string) $spec['content_type'];
$replyOnly = in_array($type, WhatsAppBroadcastService::REPLY_ONLY_TYPES, true);
$businessOnly = in_array($type, ['carousel', 'flow'], true);
$mediaFirst = in_array($type, ['card', 'whatsapp_card'], true);
$headerField = in_array($type, ['whatsapp_card', 'catalog'], true);
$footerField = in_array($type, ['card', 'whatsapp_card', 'catalog'], true);
$footerLabel = $type === 'card' ? 'Subtitle' : 'Footer';
$bodyLabel = $type === 'card' ? 'Title' : 'Body';
$fixedCategory = WhatsAppBroadcastService::FIXED_CATEGORIES[$type] ?? null;
$auth = $spec['auth'] ?? ['security' => true, 'expires' => false, 'expiry' => 10, 'button' => ''];
$flow = $spec['flow'] ?? ['button' => '', 'title' => '', 'submit' => '', 'questions' => []];
$typeLabel = $typeLabels[$spec['content_type']] ?? ucwords(str_replace('_', ' ', (string) $spec['content_type']));
$checkIcon = '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="currentColor"/><path d="m5 8.2 2 2 4-4.2" fill="none" stroke="var(--brand-white)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$chevronIcon = '<svg class="wtb-chevron" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="m4 6 4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$statusIcon = match ($statusPill[0]) {
    'ok' => $checkIcon,
    'muted' => '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="8" cy="8" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="2.4 2"/></svg>',
    default => '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="8" cy="8" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 7.25v3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="8" cy="5.1" r="0.9" fill="currentColor"/></svg>',
};
$headingName = $spec['name'] !== '' ? $spec['name'] : 'New template';
$infoIcon = '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><circle cx="8" cy="8" r="6.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 7.25v3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="8" cy="5.1" r="0.9" fill="currentColor"/></svg>';
$addVar = static function (string $target, string $tipId = '') use ($infoIcon): string {
    $describedBy = $tipId !== '' ? ' aria-describedby="' . $tipId . '"' : '';
    $id = $tipId !== '' ? ' id="' . $tipId . '"' : '';

    return '<div class="wtb-add-var">'
        . '<button type="button" class="wtb-add-var-btn" data-wtb-add-var="' . $target . '"><svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="M8 3v10M3 8h10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>Add Variable</button>'
        . '<span class="wtb-tip"><button type="button" class="wtb-tip-btn" aria-label="About variables"' . $describedBy . '>' . $infoIcon . '</button>'
        . '<span class="wtb-tip-bubble" role="tooltip"' . $id . '>Click here to add content variables to this field. Variables allow you to personalize messages. With variables, you can customize a URL or input a customer\'s name at time of send.</span></span>'
        . '</div>';
};
$pencilIcon = '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="m10.5 2.5 3 3L5.5 13.5H2.5v-3z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>';
$hannahIcon = $appUrl . '/assets/images/leadbird-favicon.png';
$hannahLink = static fn(string $field): string => '<button type="button" class="wtb-hannah-link" data-wtb-hannah-open="' . $field . '"><img src="' . e($hannahIcon) . '" width="16" height="16" alt="">Write with Hannah</button>';
$editorSpec = [
    'buttons' => $spec['buttons'],
    'samples' => (object) $spec['samples'],
    'media_url' => $spec['media_url'],
    'header_format' => $spec['header_format'],
    'list_items' => $spec['list_items'] ?? [],
    'cards' => $spec['cards'] ?? [],
    'questions' => $flow['questions'],
];
$categoryOptions = [
    'MARKETING' => ['Marketing', 'Include promotions or offers, informational updates, or invitations for customers to respond / take action. Any conversation that does not qualify as utility or authentication is a marketing conversation.'],
    'UTILITY' => ['Utility', 'Facilitate a specific, agreed-upon request or transaction or update to a customer about an ongoing transaction, including post-purchase notifications and recurring billing statements'],
    'AUTHENTICATION' => ['Authentication', 'Send codes that let customers confirm who they are when they log in, sign up or pay.'],
];
$categoryOptions = $fixedCategory !== null
    ? [$fixedCategory => $categoryOptions[$fixedCategory]]
    : array_intersect_key($categoryOptions, ['MARKETING' => true, 'UTILITY' => true]);
$fixedCategoryNote = match ($type) {
    'carousel' => 'WhatsApp only accepts carousels as Marketing templates.',
    'catalog' => 'WhatsApp only accepts catalog messages as Marketing templates.',
    'authentication' => 'Login code templates are always Authentication templates.',
    default => null,
};
$mediaFields = static function () use ($spec, $disabled, $locked, $hannahLink, $type): void {
    $optional = $type === 'card';
    ?>
                <div class="ws-field" data-wtb-media-field hidden>
                    <div class="wtb-label-row">
                        <label for="wtb-media-url">Media URL<?php if (!$optional): ?> <span class="ws-required" aria-hidden="true">*</span><?php endif; ?></label>
                        <?php if (!$locked): ?><?= $hannahLink('media') ?><?php endif; ?>
                    </div>
                    <div class="wtb-input">
                        <input id="wtb-media-url" type="url" maxlength="1600" value="<?= e($spec['media_url']) ?>" placeholder="https://www" autocomplete="off" data-wtb-media-url data-wtb-count<?= $disabled ?>>
                        <span class="wtb-counter" aria-hidden="true"></span>
                    </div>
                    <span class="ws-hint"><?= $optional ? 'Optional. A photo, video or PDF shown above the title.' : 'Supports images, documents, and video.' ?></span>
                </div>

                <div class="ws-field" data-wtb-media-sample-field hidden>
                    <label for="wtb-media-sample">Sample content for variable <span data-wtb-media-var></span></label>
                    <div class="wtb-input">
                        <input id="wtb-media-sample" type="url" maxlength="1600" placeholder="https://www" autocomplete="off" data-wtb-media-sample data-wtb-count<?= $disabled ?>>
                        <span class="wtb-counter" aria-hidden="true"></span>
                    </div>
                    <span class="ws-hint">A link to an example photo, video or PDF. WhatsApp only uses it to review the template. You choose the real file each time you send.</span>
                </div>
    <?php
};
$cardLayout = array_column($spec['cards'][0]['buttons'] ?? [], 'type');
$buttonTypeOptions = [
    'QUICK_REPLY' => 'Quick Reply',
    'URL' => 'Visit Website',
    'PHONE_NUMBER' => 'Call Phone Number',
    'COPY_CODE' => 'Copy Offer Code',
];
?>
<div
    class="ws-page ws-page--detail ws-page--full wtb-page"
    data-wtb-root
    data-endpoint="<?= e(app_route('broadcasts/action')) ?>"
    data-templates-url="<?= e($templatesUrl) ?>"
    data-id="<?= $isExisting ? e((string) $template['id']) : '' ?>"
    data-locked="<?= $locked ? '1' : '0' ?>"
    data-name-locked="<?= $isExisting ? '1' : '0' ?>"
    data-draft="<?= $status === 'DRAFT' ? '1' : '0' ?>"
    data-preview="<?= $preview ? '1' : '0' ?>"
>
    <h1 class="ws-title font-heading" data-wtb-heading><?= e($headingName) ?></h1>

    <?php if ($preview): ?>
        <div class="ws-alert ws-alert--preview" role="status">
            <?php if ($replyOnly): ?>
                <strong>Preview mode.</strong> Nothing is saved or sent.
            <?php else: ?>
                <strong>Preview mode.</strong> Submit runs all our checks and shows what we would send to WhatsApp. Nothing is saved or sent.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashSuccess)): ?>
        <div class="ws-alert ws-alert--ok" role="status"><?= e($flashSuccess) ?></div>
    <?php endif; ?>

    <?php if ($lockedReason !== null): ?>
        <div class="ws-note"><?= $infoIcon ?><p><?= e($lockedReason) ?></p></div>
    <?php elseif ($status === 'REJECTED' && !empty($template['rejected_reason'])): ?>
        <div class="ws-note ws-note--bad"><?= $infoIcon ?><p><strong>WhatsApp rejected this template.</strong> Change what you need and submit it again.</p></div>
    <?php endif; ?>

    <details class="ws-card wtb-section" open>
        <summary class="wtb-section-head"><?= $chevronIcon ?><h2 class="wtb-section-title font-heading" id="wtb-general-title">General information</h2><?php if ($editUrl !== null): ?><a class="wtb-edit" href="<?= e($editUrl) ?>"><?= $pencilIcon ?>Edit</a><?php endif; ?></summary>
        <div class="wtb-section-body">
            <div class="wtb-general">
                <div class="ws-field">
                    <?php if ($nameEditable): ?>
                        <div class="wtb-label-row">
                            <label for="wtb-name">Template name</label>
                            <?= $hannahLink('name') ?>
                        </div>
                        <input id="wtb-name" type="text" maxlength="512" value="<?= e($spec['name']) ?>" placeholder="summer_sale" autocomplete="off" data-wtb-name>
                        <span class="ws-hint">Small letters, numbers and underscores only.</span>
                    <?php else: ?>
                        <span class="ws-label">Template name</span>
                        <p class="wtb-static"><?= e($spec['name']) ?></p>
                        <input type="hidden" value="<?= e($spec['name']) ?>" data-wtb-name>
                    <?php endif; ?>
                </div>
                <div class="ws-field">
                    <span class="ws-label">Template ID</span>
                    <p class="wtb-static wtb-static--id"><?= $templateRef !== null ? e($templateRef) : '<span class="ws-muted">Given when you ' . ($replyOnly ? 'save' : 'submit') . '</span>' ?></p>
                </div>
                <div class="ws-field">
                    <span class="ws-label">Template language</span>
                    <p class="wtb-static"><?= e($languages[$spec['language']]) ?></p>
                    <input type="hidden" value="<?= e($spec['language']) ?>" data-wtb-language>
                </div>
                <div class="ws-field">
                    <span class="ws-label">WhatsApp approval status</span>
                    <p class="wtb-static wtb-approval wtb-approval--<?= e($statusPill[0]) ?>"><?= $statusIcon ?><?= e($statusPill[1]) ?></p>
                </div>
                <div class="ws-field">
                    <span class="ws-label">Content type</span>
                    <p class="wtb-static"><?= e($typeLabel) ?></p>
                    <input type="hidden" value="<?= e((string) $spec['content_type']) ?>" data-wtb-type>
                </div>
                <div class="ws-field">
                    <span class="ws-label">Last updated at</span>
                    <p class="wtb-static"><?= $isExisting ? e(date('F j, Y', strtotime((string) $template['updated_at']) ?: time())) : '<span class="ws-muted">—</span>' ?></p>
                </div>
                <?php if ($isExisting && $status !== 'DRAFT'): ?>
                    <div class="ws-field">
                        <span class="ws-label">WhatsApp Category</span>
                        <p class="wtb-static"><?= e(['MARKETING' => 'Marketing', 'UTILITY' => 'Utility', 'AUTHENTICATION' => 'Authentication'][$spec['category']] ?? ucfirst(strtolower((string) $spec['category']))) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="wtb-channels">
                <span class="ws-label">Supported Channels:</span>
                <?php if (!$replyOnly): ?>
                    <span class="wtb-channel<?= $status === 'APPROVED' ? ' is-on' : '' ?>"><?= $status === 'APPROVED' ? $checkIcon : $infoIcon ?>WhatsApp business initiated</span>
                <?php endif; ?>
                <?php if (!$businessOnly): ?>
                    <span class="wtb-channel is-on"><?= $checkIcon ?>WhatsApp user initiated</span>
                <?php endif; ?>
            </div>
        </div>
    </details>

    <details class="ws-card wtb-section" open>
        <summary class="wtb-section-head"><?= $chevronIcon ?><h2 class="wtb-section-title font-heading" id="wtb-content-title">Content</h2></summary>
        <div class="wtb-section-body">
        <div class="wtb-tabs" role="presentation"><span class="wtb-tab" data-wtb-type-label><?= e($typeLabel) ?></span></div>
        <h3 class="wtb-configure">Configure content - <span data-wtb-type-label><?= e($typeLabel) ?></span></h3>
        <div class="wtb-content">
            <div class="wtb-form">
                <?php if ($type === 'whatsapp_card'): ?>
                    <div class="ws-field">
                        <label for="wtb-header-type">Header</label>
                        <select id="wtb-header-type" class="wtb-select" data-wtb-header-type<?= $disabled ?>>
                            <?php foreach (['none' => 'None', 'text' => 'Text', 'media' => 'Media'] as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= ($spec['header_type'] ?? 'none') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="ws-hint">Optional. A short line of text, or a photo, video or PDF above the message.</span>
                    </div>
                <?php endif; ?>

                <?php if ($type === 'catalog'): ?>
                    <fieldset class="wtb-category wtb-choice">
                        <legend>Catalog format</legend>
                        <?php foreach ([
                            'catalog' => ['Full catalog', 'Shows a product photo and a View catalog button. Customers can browse everything in your catalog.'],
                            'mpm' => ['Multiple products', 'Shows a header and a View items button. You pick up to 30 products each time you send.'],
                        ] as $value => [$label, $help]): ?>
                            <label class="wtb-category-option">
                                <input type="radio" name="wtb-catalog-format" value="<?= e($value) ?>" data-wtb-catalog-format<?= ($spec['catalog_format'] ?? 'catalog') === $value ? ' checked' : '' ?><?= $disabled ?>>
                                <span><b><?= e($label) ?></b><small><?= e($help) ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <div class="wtb-samples-note"><?= $infoIcon ?><p>Products come from the Meta catalog connected to your WhatsApp number. <strong>Set it up in Commerce Manager</strong> before you send this template.</p></div>
                <?php endif; ?>

                <?php if ($headerField): ?>
                    <div class="ws-field" data-wtb-header-field>
                        <label for="wtb-header">Header text <span class="ws-required" aria-hidden="true">*</span></label>
                        <div class="wtb-input">
                            <input id="wtb-header" type="text" maxlength="60" value="<?= e($spec['header']) ?>" placeholder="Type header" autocomplete="off" data-wtb-header data-wtb-count<?= $disabled ?>>
                            <span class="wtb-counter" aria-hidden="true"></span>
                        </div>
                        <?php if (!$locked): ?><?= $addVar('header') ?><?php endif; ?>
                    </div>
                <?php else: ?>
                    <input type="hidden" value="<?= e($spec['header']) ?>" data-wtb-header>
                <?php endif; ?>

                <?php if ($mediaFirst) {
                    $mediaFields();
                } ?>

                <div class="ws-field"<?= $type === 'authentication' ? ' hidden' : '' ?>>
                    <div class="wtb-label-row">
                        <label for="wtb-body"><?= e($bodyLabel) ?> <span class="ws-required" aria-hidden="true">*</span></label>
                        <?php if (!$locked): ?><?= $hannahLink('body') ?><?php endif; ?>
                    </div>
                    <div class="wtb-input">
                        <textarea id="wtb-body" rows="2" maxlength="1024" placeholder="Type <?= e(strtolower($bodyLabel)) ?>" data-wtb-body data-wtb-count<?= $disabled ?>><?= e($spec['body']) ?></textarea>
                        <span class="wtb-counter" aria-hidden="true"></span>
                    </div>
                    <?php if (!$locked): ?><?= $addVar('body', 'wtb-var-tip') ?><?php endif; ?>
                </div>

                <div class="wtb-samples" data-wtb-samples></div>

                <?php if (!$mediaFirst) {
                    $mediaFields();
                } ?>

                <?php if ($footerField): ?>
                    <div class="ws-field">
                        <label for="wtb-footer"><?= e($footerLabel) ?></label>
                        <div class="wtb-input">
                            <input id="wtb-footer" type="text" maxlength="60" value="<?= e($spec['footer']) ?>" placeholder="Type <?= e(strtolower($footerLabel)) ?>" autocomplete="off" data-wtb-footer data-wtb-count<?= $disabled ?>>
                            <span class="wtb-counter" aria-hidden="true"></span>
                        </div>
                        <span class="ws-hint">Optional. Variables can’t be used here.</span>
                    </div>
                <?php else: ?>
                    <input type="hidden" value="<?= e($spec['footer']) ?>" data-wtb-footer>
                <?php endif; ?>

                <?php if ($type === 'authentication'): ?>
                    <div class="wtb-auth" data-wtb-auth>
                        <div class="wtb-samples-note"><?= $infoIcon ?><p>WhatsApp writes this message for you, so login codes look the same for every business. <strong>Links, media and emojis aren’t allowed.</strong> You choose the extras below.</p></div>
                        <label class="wtb-category-option">
                            <input type="checkbox" data-wtb-auth-security<?= !empty($auth['security']) ? ' checked' : '' ?><?= $disabled ?>>
                            <span><b>Add security recommendation</b><small>Adds “<?= e(WhatsAppBroadcastService::AUTH_SECURITY) ?>”</small></span>
                        </label>
                        <label class="wtb-category-option">
                            <input type="checkbox" data-wtb-auth-expires<?= !empty($auth['expires']) ? ' checked' : '' ?><?= $disabled ?>>
                            <span><b>Add expiry time for the code</b><small>Shows how long the code works, under the message.</small></span>
                        </label>
                        <div class="ws-field wtb-auth-expiry" data-wtb-auth-expiry-field<?= empty($auth['expires']) ? ' hidden' : '' ?>>
                            <label for="wtb-auth-expiry">Code expires in (minutes) <span class="ws-required" aria-hidden="true">*</span></label>
                            <input id="wtb-auth-expiry" type="number" min="1" max="90" step="1" inputmode="numeric" value="<?= (int) (($auth['expiry'] ?? 0) ?: 10) ?>" data-wtb-auth-expiry<?= $disabled ?>>
                            <span class="ws-hint">Between 1 and 90 minutes.</span>
                        </div>
                        <div class="ws-field">
                            <label for="wtb-auth-button">Copy code button text</label>
                            <div class="wtb-input">
                                <input id="wtb-auth-button" type="text" maxlength="25" value="<?= e((string) ($auth['button'] ?? '')) ?>" placeholder="Copy code" autocomplete="off" data-wtb-auth-button data-wtb-count<?= $disabled ?>>
                                <span class="wtb-counter" aria-hidden="true"></span>
                            </div>
                            <span class="ws-hint">Customers tap this to copy the code. Leave it empty to use “Copy code”.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($type === 'carousel'): ?>
                    <div class="wtb-carousel" data-wtb-carousel>
                        <div>
                            <h4 class="wtb-sub-title">Cards</h4>
                            <p class="ws-hint">Customers swipe through 2 to 10 cards. Every card needs a photo or video and the same buttons.</p>
                        </div>
                        <div class="wtb-carousel-layout">
                            <?php foreach ([0, 1] as $slot): ?>
                                <div class="ws-field">
                                    <label for="wtb-card-btype-<?= $slot ?>">Button <?= $slot + 1 ?> on every card</label>
                                    <select id="wtb-card-btype-<?= $slot ?>" class="wtb-select" data-wtb-card-btype="<?= $slot ?>"<?= $disabled ?>>
                                        <?php if ($slot === 1): ?><option value="">None</option><?php endif; ?>
                                        <?php foreach (['QUICK_REPLY', 'URL', 'PHONE_NUMBER'] as $value): ?>
                                            <option value="<?= e($value) ?>"<?= ($cardLayout[$slot] ?? '') === $value ? ' selected' : '' ?>><?= e($buttonTypeOptions[$value]) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="wtb-button-list" data-wtb-cards></div>
                        <?php if (!$locked): ?>
                            <button type="button" class="ws-btn ws-btn--ghost ws-btn--small wtb-add" data-wtb-card-add>Add card</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($type === 'flow'): ?>
                    <div class="wtb-flow" data-wtb-flow>
                        <div class="ws-field">
                            <label for="wtb-flow-button">Button text <span class="ws-required" aria-hidden="true">*</span></label>
                            <div class="wtb-input">
                                <input id="wtb-flow-button" type="text" maxlength="25" value="<?= e($flow['button']) ?>" placeholder="Fill the form" autocomplete="off" data-wtb-flow-button data-wtb-count<?= $disabled ?>>
                                <span class="wtb-counter" aria-hidden="true"></span>
                            </div>
                            <span class="ws-hint">Customers tap this to open the form.</span>
                        </div>
                        <div>
                            <h4 class="wtb-sub-title">Form</h4>
                            <p class="ws-hint">The form opens inside WhatsApp. Answers come back to your inbox.</p>
                        </div>
                        <div class="ws-field">
                            <label for="wtb-flow-title">Form title <span class="ws-required" aria-hidden="true">*</span></label>
                            <div class="wtb-input">
                                <input id="wtb-flow-title" type="text" maxlength="30" value="<?= e($flow['title']) ?>" placeholder="Book a visit" autocomplete="off" data-wtb-flow-title data-wtb-count<?= $disabled ?>>
                                <span class="wtb-counter" aria-hidden="true"></span>
                            </div>
                        </div>
                        <div class="wtb-button-list" data-wtb-questions></div>
                        <?php if (!$locked): ?>
                            <button type="button" class="ws-btn ws-btn--ghost ws-btn--small wtb-add" data-wtb-question-add>Add question</button>
                        <?php endif; ?>
                        <div class="ws-field">
                            <label for="wtb-flow-submit">Submit button text <span class="ws-required" aria-hidden="true">*</span></label>
                            <div class="wtb-input">
                                <input id="wtb-flow-submit" type="text" maxlength="30" value="<?= e($flow['submit']) ?>" placeholder="Submit" autocomplete="off" data-wtb-flow-submit data-wtb-count<?= $disabled ?>>
                                <span class="wtb-counter" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="wtb-list" data-wtb-list-wrap hidden>
                    <div class="ws-field">
                        <label for="wtb-list-button">List button text <span class="ws-required" aria-hidden="true">*</span></label>
                        <div class="wtb-input">
                            <input id="wtb-list-button" type="text" maxlength="20" value="<?= e((string) ($spec['list_button'] ?? '')) ?>" placeholder="Type list button text" autocomplete="off" data-wtb-list-button data-wtb-count<?= $disabled ?>>
                            <span class="wtb-counter" aria-hidden="true"></span>
                        </div>
                        <?php if (!$locked): ?><?= $addVar('field', 'wtb-list-var-tip') ?><?php endif; ?>
                    </div>
                    <div class="wtb-button-list" data-wtb-list-items></div>
                    <?php if (!$locked): ?>
                        <button type="button" class="ws-btn ws-btn--ghost ws-btn--small wtb-add" data-wtb-list-add>Add list item</button>
                    <?php endif; ?>
                </div>

                <div class="wtb-buttons" data-wtb-buttons-wrap hidden>
                    <div class="wtb-button-list" data-wtb-buttons></div>
                    <?php if (!$locked): ?>
                        <button type="button" class="ws-btn ws-btn--ghost ws-btn--small wtb-add" data-wtb-add>Add button</button>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="wtb-preview" aria-label="Message preview">
                <h3 class="wtb-sub-title">Message preview</h3>
                <div class="wtb-phone">
                    <div class="wtb-phone-top">
                        <span class="wtb-phone-avatar" aria-hidden="true"><?= brand_whatsapp_icon_html('wtb-phone-logo', 22) ?></span>
                        <span>Your WhatsApp number</span>
                    </div>
                    <div class="wtb-chat">
                        <div class="wtb-bubble">
                            <p class="wtb-bubble-header" data-wtb-p-header hidden></p>
                            <p class="wtb-bubble-body" data-wtb-p-body></p>
                            <div class="wtb-bubble-media" data-wtb-p-media hidden></div>
                            <p class="wtb-bubble-footer" data-wtb-p-footer hidden></p>
                        </div>
                        <div class="wtb-bubble-buttons" data-wtb-p-buttons></div>
                        <?php if ($type === 'carousel'): ?>
                            <div class="wtb-p-cards" data-wtb-p-cards tabindex="0" aria-label="Cards"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($type === 'flow'): ?>
                    <h3 class="wtb-sub-title">Form preview</h3>
                    <div class="wtb-phone wtb-phone--form">
                        <div class="wtb-form-top">
                            <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            <span data-wtb-p-form-title></span>
                        </div>
                        <div class="wtb-form-fields" data-wtb-p-form></div>
                        <div class="wtb-form-foot">
                            <span class="wtb-form-submit" data-wtb-p-form-submit></span>
                            <small>Managed by the business. Learn more</small>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
        </div>
    </details>

    <?php if ($preview): ?>
        <section class="ws-card wtb-card" aria-labelledby="wtb-payload-title" data-wtb-payload hidden>
            <h2 class="wtb-card-title font-heading" id="wtb-payload-title">What we would send to WhatsApp</h2>
            <p class="ws-hint">This passed all our checks. WhatsApp still makes the final decision when a real number submits it.</p>
            <pre class="wtb-payload-code" tabindex="0" data-wtb-payload-code></pre>
        </section>
    <?php endif; ?>

    <div class="wtb-bar">
        <?php if (!$locked && ($status === null || $status === 'DRAFT')): ?>
            <button type="button" class="ws-btn ws-btn--primary" data-wtb-save>Save</button>
            <?php if (!$replyOnly): ?>
                <button type="button" class="ws-btn ws-btn--ghost" data-wtb-submit>Save and submit for WhatsApp approval</button>
            <?php endif; ?>
        <?php elseif (!$locked): ?>
            <button type="button" class="ws-btn ws-btn--primary" data-wtb-submit>Submit for WhatsApp approval</button>
        <?php endif; ?>
        <?php if ($isExisting && $spec['editable']): ?>
            <a class="ws-btn ws-btn--ghost" href="<?= e(app_route('broadcasts/template', ['copy' => (string) $template['id']] + $previewQuery)) ?>">Duplicate</a>
        <?php endif; ?>
        <?php if ($isExisting && $wabaId !== null && !$spec['editable']): ?>
            <a class="ws-btn ws-btn--ghost" href="https://business.facebook.com/wa/manage/message-templates/?waba_id=<?= e(rawurlencode($wabaId)) ?>" target="_blank" rel="noopener">Open in WhatsApp Manager</a>
        <?php endif; ?>
        <a class="ws-btn ws-btn--ghost" href="<?= e($templatesUrl) ?>">Cancel</a>
        <?php if ($isExisting): ?>
            <button type="button" class="ws-danger-link" data-wtb-delete>Delete</button>
        <?php endif; ?>
    </div>

    <?php if (!$locked): ?>
        <div class="lb-modal-overlay is-hidden" aria-hidden="true" data-wtb-dialog>
            <div class="lb-modal wtb-dialog" role="dialog" aria-modal="true" aria-labelledby="wtb-dialog-title">
                <button type="button" class="wtb-dialog-close" aria-label="Close" data-wtb-dialog-cancel><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
                <h2 class="lb-modal-title font-heading" id="wtb-dialog-title">Submit for WhatsApp review</h2>
                <p class="lb-modal-message font-sans">This entire template will be submitted for WhatsApp approval. WhatsApp usually approves or rejects template submissions in 48 hours or less.</p>
                <fieldset class="wtb-category">
                    <legend><span class="ws-required" aria-hidden="true">*</span> Select template category</legend>
                    <?php foreach ($categoryOptions as $value => [$label, $help]): ?>
                        <label class="wtb-category-option">
                            <input type="radio" name="wtb-category" value="<?= e($value) ?>" data-wtb-category<?= $fixedCategory !== null || ($status !== null && $status !== 'DRAFT' && $spec['category'] === $value) ? ' checked' : '' ?><?= $categoryLocked ? ' disabled' : '' ?>>
                            <span><b><?= e($label) ?></b><small><?= e($help) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if ($fixedCategoryNote !== null): ?><p class="ws-hint"><?= e($fixedCategoryNote) ?></p><?php endif; ?>
                </fieldset>
                <div class="lb-modal-actions">
                    <button type="button" class="lb-modal-btn lb-modal-btn--cancel" data-wtb-dialog-cancel>Cancel</button>
                    <button type="button" class="lb-modal-btn lb-modal-btn--primary" data-wtb-dialog-submit<?= ($status === null || $status === 'DRAFT') && $fixedCategory === null ? ' disabled' : '' ?>>Submit</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$locked): ?>
        <script type="application/json" data-wtb-hannah-library><?= json_encode($hannahLibrary, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
        <script type="application/json" data-wtb-hannah-samples><?= json_encode(WhatsAppStarterTemplates::SAMPLE_VALUES, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>

    <script type="application/json" data-wtb-spec><?= json_encode($editorSpec, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

    <template data-wtb-list-item-tpl>
        <div class="wtb-button" data-wtb-list-item>
            <div class="wtb-button-head">
                <h4 class="wtb-button-title">List item</h4>
                <button type="button" class="wtb-remove" data-wtb-list-remove>Remove</button>
            </div>
            <div class="ws-field">
                <label>Item name <span class="ws-required" aria-hidden="true">*</span></label>
                <div class="wtb-input">
                    <input type="text" maxlength="24" placeholder="Type item name" autocomplete="off" data-wtb-item-name data-wtb-count>
                    <span class="wtb-counter" aria-hidden="true"></span>
                </div>
                <?php if (!$locked): ?><?= $addVar('field') ?><?php endif; ?>
            </div>
            <div class="ws-field">
                <label>Item ID <span class="ws-required" aria-hidden="true">*</span></label>
                <div class="wtb-input">
                    <input type="text" maxlength="200" placeholder="Type item ID" autocomplete="off" data-wtb-item-id data-wtb-count>
                    <span class="wtb-counter" aria-hidden="true"></span>
                </div>
                <?php if (!$locked): ?><?= $addVar('field') ?><?php endif; ?>
            </div>
            <div class="ws-field">
                <label>Item description</label>
                <div class="wtb-input">
                    <input type="text" maxlength="72" placeholder="Type item description" autocomplete="off" data-wtb-item-desc data-wtb-count>
                    <span class="wtb-counter" aria-hidden="true"></span>
                </div>
                <?php if (!$locked): ?><?= $addVar('field') ?><?php endif; ?>
            </div>
        </div>
    </template>

    <template data-wtb-button-tpl>
        <div class="wtb-button" data-wtb-button>
            <div class="wtb-button-head">
                <h4 class="wtb-button-title">Button</h4>
                <button type="button" class="wtb-remove" data-wtb-remove>Remove</button>
            </div>
            <div class="ws-field" data-wtb-cta-only>
                <label>Type of action</label>
                <select class="wtb-select" data-wtb-btype>
                    <?php foreach ($buttonTypeOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ws-field" data-wtb-text-field>
                <div class="wtb-label-row">
                    <label>Button Text</label>
                    <?php if (!$locked): ?><?= $hannahLink('button_text') ?><?php endif; ?>
                </div>
                <div class="wtb-input">
                    <input type="text" maxlength="25" data-wtb-btext data-wtb-count>
                    <span class="wtb-counter" aria-hidden="true"></span>
                </div>
                <span class="ws-hint" data-wtb-cta-only>For use in WhatsApp without template approval, button text should be 20 characters max.</span>
            </div>
            <div class="ws-field" data-wtb-url-field>
                <label>Website URL</label>
                <div class="wtb-input">
                    <input type="text" inputmode="url" maxlength="2000" placeholder="https://yourwebsite.com" data-wtb-burl data-wtb-count>
                    <span class="wtb-counter" aria-hidden="true"></span>
                </div>
                <span class="ws-hint">Only one variable can be added to the end of a URL.</span>
            </div>
            <div class="ws-field wtb-url-sample" data-wtb-url-sample-field hidden>
                <label>Sample content for variable <span data-wtb-url-var></span></label>
                <input type="text" maxlength="200" data-wtb-url-sample>
            </div>
            <div class="ws-field" data-wtb-phone-field>
                <label>Phone Number</label>
                <input type="text" inputmode="tel" placeholder="+2348012345678" data-wtb-bphone>
            </div>
            <div class="ws-field" data-wtb-code-field>
                <label>Sample offer code</label>
                <div class="wtb-input">
                    <input type="text" maxlength="15" placeholder="SAVE20" autocomplete="off" data-wtb-bcode data-wtb-count>
                    <span class="wtb-counter" aria-hidden="true"></span>
                </div>
                <span class="ws-hint">The button always says “Copy offer code”. You set the real code each time you send.</span>
            </div>
        </div>
    </template>

    <?php if ($type === 'carousel'): ?>
        <template data-wtb-card-tpl>
            <div class="wtb-button" data-wtb-card>
                <div class="wtb-button-head">
                    <h4 class="wtb-button-title" data-wtb-card-title>Card</h4>
                    <button type="button" class="wtb-remove" data-wtb-card-remove>Remove</button>
                </div>
                <div class="ws-field">
                    <label>Media URL <span class="ws-required" aria-hidden="true">*</span></label>
                    <div class="wtb-input">
                        <input type="url" maxlength="1600" placeholder="https://www" autocomplete="off" data-wtb-card-media data-wtb-count>
                        <span class="wtb-counter" aria-hidden="true"></span>
                    </div>
                    <span class="ws-hint">A JPG or PNG photo, or an MP4 video. Use the same kind on every card.</span>
                </div>
                <div class="ws-field">
                    <label>Card text</label>
                    <div class="wtb-input wtb-input--area">
                        <textarea rows="2" maxlength="160" placeholder="Type card text" data-wtb-card-body data-wtb-count></textarea>
                        <span class="wtb-counter" aria-hidden="true"></span>
                    </div>
                    <span class="ws-hint">Optional. If one card has text, every card needs it.</span>
                </div>
                <?php foreach ([0, 1] as $slot): ?>
                    <div class="wtb-card-slot" data-wtb-slot="<?= $slot ?>">
                        <div class="ws-field">
                            <label>Button <?= $slot + 1 ?> text <span class="ws-required" aria-hidden="true">*</span></label>
                            <div class="wtb-input">
                                <input type="text" maxlength="25" autocomplete="off" data-wtb-slot-text data-wtb-count>
                                <span class="wtb-counter" aria-hidden="true"></span>
                            </div>
                        </div>
                        <div class="ws-field" data-wtb-slot-url-field>
                            <label>Website URL <span class="ws-required" aria-hidden="true">*</span></label>
                            <input type="text" inputmode="url" maxlength="2000" placeholder="https://yourwebsite.com" data-wtb-slot-url>
                        </div>
                        <div class="ws-field" data-wtb-slot-phone-field>
                            <label>Phone Number <span class="ws-required" aria-hidden="true">*</span></label>
                            <input type="text" inputmode="tel" placeholder="+2348012345678" data-wtb-slot-phone>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </template>
    <?php endif; ?>

    <?php if ($type === 'flow'): ?>
        <template data-wtb-question-tpl>
            <div class="wtb-button" data-wtb-question>
                <div class="wtb-button-head">
                    <h4 class="wtb-button-title" data-wtb-question-title>Question</h4>
                    <button type="button" class="wtb-remove" data-wtb-question-remove>Remove</button>
                </div>
                <div class="ws-field">
                    <label>Answer type</label>
                    <select class="wtb-select" data-wtb-q-type>
                        <?php foreach (WhatsAppBroadcastService::FLOW_QUESTION_TYPES as $value => $kind): ?>
                            <option value="<?= e($value) ?>" data-max="<?= (int) $kind['max'] ?>" data-choices="<?= $kind['choices'] ? '1' : '0' ?>"><?= e($kind['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ws-field">
                    <label>Question <span class="ws-required" aria-hidden="true">*</span></label>
                    <div class="wtb-input">
                        <input type="text" maxlength="20" placeholder="Type question" autocomplete="off" data-wtb-q-label data-wtb-count>
                        <span class="wtb-counter" aria-hidden="true"></span>
                    </div>
                </div>
                <div class="ws-field" data-wtb-q-options-field hidden>
                    <label>Options <span class="ws-required" aria-hidden="true">*</span></label>
                    <textarea rows="3" placeholder="One option per line" data-wtb-q-options></textarea>
                    <span class="ws-hint">One option per line. 2 to 10 options, up to 30 characters each.</span>
                </div>
                <label class="wtb-category-option wtb-required-toggle">
                    <input type="checkbox" data-wtb-q-required checked>
                    <span><b>Required</b></span>
                </label>
            </div>
        </template>
    <?php endif; ?>
</div>
