<?php

declare(strict_types=1);

/** @var string $templatesUrl */
/** @var bool $preview */
/** @var array{name: string, language: string, type: string} $prefill */

$gearIcon = brand_icon_html(brand_dashboard_nav_icon_urls()['settings'], '', 30);
$typeIcon = brand_icon_html($appUrl . '/assets/images/icons/content-type.png', '', 30);
$types = [
    'text' => ['Text', '<path d="M5 6h14M12 6v13" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>'],
    'media' => ['Media', '<rect x="4" y="5" width="16" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="9" cy="10" r="1.6" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="m4.5 17 4.5-4.5 3.5 3.5 2.5-2.5 4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>'],
    'list_picker' => ['List Picker', '<path d="M9 7h11M9 12h11M9 17h11" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="5" cy="7" r="1.1" fill="currentColor"/><circle cx="5" cy="12" r="1.1" fill="currentColor"/><circle cx="5" cy="17" r="1.1" fill="currentColor"/>'],
    'call_to_action' => ['Call to action', '<rect x="3.5" y="4" width="17" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12.5 11.5v8.2l2-1.8 1.4 3 1.6-.7-1.4-3h2.7z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>'],
    'quick_reply' => ['Quick Reply', '<path d="M7 10v10H4.5a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1H7zm0 0 4-6.5a1.8 1.8 0 0 1 3.3 1.3L13.5 9H19a2 2 0 0 1 2 2.3l-1.1 6.5A2.5 2.5 0 0 1 17.4 20H7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>'],
    'card' => ['Card', null],
    'catalog' => ['Catalog', '<path d="M3.5 5h2.2l2 10.5h10.3l1.8-7.5H7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/><circle cx="9" cy="19" r="1.3" fill="currentColor"/><circle cx="16.5" cy="19" r="1.3" fill="currentColor"/>'],
    'carousel' => ['Carousel', null],
    'whatsapp_card' => ['WhatsApp Card', null],
    'authentication' => ['Authentication', '<path d="M12 3.5 5 6.2v5.3c0 4.3 3 7.7 7 9 4-1.3 7-4.7 7-9V6.2z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m9 12 2.2 2.2L15.5 10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>'],
    'flow' => ['Flows', '<path d="M7 3.5h7l4 4V20a.5.5 0 0 1-.5.5h-10.5a.5.5 0 0 1-.5-.5V4a.5.5 0 0 1 .5-.5z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9.5 11h5M9.5 14.5h5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>'],
];
$editorReady = WhatsAppBroadcastService::EDITABLE_TYPES;
$channelsIcon = brand_icon_html(brand_dashboard_nav_icon_urls()['inbox'], '', 30);
$channelText = [
    'user' => ['WhatsApp user initiated', 'WhatsApp approved. This template can be used'],
    'business' => ['WhatsApp business initiated', 'WhatsApp approval required for this template to be used for WhatsApp business initiated messages.'],
];
$both = [['', ['user', 'business']]];
$typeChannels = [
    'text' => $both,
    'media' => $both,
    'list_picker' => [['', ['user']]],
    'call_to_action' => $both,
    'quick_reply' => $both,
    'card' => $both,
    'catalog' => [['Single Product, and Multi-Product Message', ['user', 'business']], ['Catalog Message', ['business']]],
    'carousel' => [['', ['business']]],
    'whatsapp_card' => $both,
    'authentication' => $both,
    'flow' => [['', ['business']]],
];
?>
<div class="ws-page ws-page--detail ws-page--full wtc-page">
    <form id="wtc-form" class="ws-card wtc-card" method="get" action="<?= e(app_route('broadcasts/template')) ?>" novalidate data-wtc-form>
        <?php if ($preview): ?><input type="hidden" name="preview" value="1"><?php endif; ?>

        <section class="wtc-section" aria-labelledby="wtc-general-title">
            <span class="wtc-section-icon"><?= $gearIcon ?></span>
            <div class="wtc-section-main">
                <h2 class="wtc-section-title font-heading" id="wtc-general-title">General Information</h2>
                <p class="wtc-section-sub">Give your template a unique name and a language</p>
                <div class="wtc-fields">
                    <div class="ws-field" data-wtc-field="name">
                        <label for="wtc-name"><span class="ws-required" aria-hidden="true">*</span> Template Name</label>
                        <div class="wtb-input">
                            <input id="wtc-name" name="name" type="text" maxlength="512" value="<?= e($prefill['name']) ?>" placeholder="Type template name" autocomplete="off" required aria-describedby="wtc-name-hint wtc-name-error" data-wtb-count data-wtc-name>
                            <span class="wtb-counter" aria-hidden="true"><?= mb_strlen($prefill['name']) ?>/512</span>
                        </div>
                        <span class="ws-hint" id="wtc-name-hint" data-wtc-hint>Template names can only contain lowercase alphanumeric characters and underscores. No other characters or spaces are allowed.</span>
                        <span class="wtc-error" id="wtc-name-error" role="alert" data-wtc-error="name"></span>
                    </div>
                    <div class="ws-field" data-wtc-field="language">
                        <label for="wtc-language"><span class="ws-required" aria-hidden="true">*</span> Template Language</label>
                        <select id="wtc-language" name="language" class="wtb-select" required data-search-inline data-wtc-language>
                            <option value="" hidden<?= isset(WhatsAppBroadcastService::TEMPLATE_LANGUAGES[$prefill['language']]) ? '' : ' selected' ?>>Select template language</option>
                            <?php foreach (WhatsAppBroadcastService::TEMPLATE_LANGUAGES as $code => $label): ?>
                                <option value="<?= e($code) ?>"<?= $code === $prefill['language'] ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="wtc-error" role="alert" data-wtc-error="language"></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="wtc-section" aria-labelledby="wtc-type-title">
            <span class="wtc-section-icon"><?= $typeIcon ?></span>
            <div class="wtc-section-main">
                <h2 class="wtc-section-title font-heading" id="wtc-type-title">Content Type</h2>
                <p class="wtc-section-sub">Select one of the message types below</p>
                <fieldset class="wtc-types" aria-labelledby="wtc-type-title" data-wtc-field="type">
                    <?php foreach ($types as $value => [$label, $icon]): ?>
                        <label class="wtc-type">
                            <input type="radio" name="type" value="<?= e($value) ?>"<?= $value === $prefill['type'] ? ' checked' : '' ?> data-wtc-type<?= in_array($value, $editorReady, true) ? '' : ' data-wtc-soon' ?>>
                            <?php if ($icon === null): ?>
                                <span class="wtc-type-icon wtc-type-icon--carousel" aria-hidden="true"></span>
                            <?php else: ?>
                                <svg class="wtc-type-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><?= $icon ?></svg>
                            <?php endif; ?>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <span class="wtc-error" role="alert" data-wtc-error="type"></span>
            </div>
        </section>

        <section class="wtc-section" aria-labelledby="wtc-channels-title" data-wtc-channels<?= isset($typeChannels[$prefill['type']]) ? '' : ' hidden' ?>>
            <span class="wtc-section-icon"><?= $channelsIcon ?></span>
            <div class="wtc-section-main">
                <h2 class="wtc-section-title wtc-section-title--ruled font-heading" id="wtc-channels-title">Supported Channels</h2>
                <?php foreach ($typeChannels as $value => $groups): ?>
                    <div class="wtc-channel-set" data-wtc-channels-for="<?= e($value) ?>"<?= $value === $prefill['type'] ? '' : ' hidden' ?>>
                        <?php foreach ($groups as [$groupTitle, $keys]): ?>
                            <?php if ($groupTitle !== ''): ?><h3 class="wtc-channel-group"><?= e($groupTitle) ?></h3><?php endif; ?>
                            <ul class="wtc-channel-list">
                                <?php foreach ($keys as $key): ?>
                                    <li class="wtc-channel">
                                        <span class="wtc-channel-icon"><?= brand_whatsapp_icon_html('', 20) ?></span>
                                        <span><strong><?= e($channelText[$key][0]) ?></strong><?= e($channelText[$key][1]) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </form>

    <div class="wtb-bar">
        <button type="submit" class="ws-btn ws-btn--primary" form="wtc-form">Create</button>
        <a class="ws-btn ws-btn--ghost" href="<?= e($templatesUrl) ?>">Cancel</a>
    </div>
</div>
