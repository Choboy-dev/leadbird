<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string|null $businessId */
/** @var string $userFirstName */
/** @var string $timeGreeting */
/** @var string $aiName */
/** @var string $businessName */
/** @var string $aiTone */
/** @var string $aiGreeting */
/** @var list<string> $aiToneOptions */
/** @var string $businessUrl */
/** @var string $testMessage */
/** @var string $testResponse */
/** @var string $testError */
/** @var array<string, mixed>|null $knowledgeTraining */
/** @var bool $hasFullSettings */
/** @var list<array{id: string, name: string, platform: string, platform_label: string, avatar_src: string}> $connectedChannels */
/** @var array{id: string, name: string, platform: string, platform_label: string, avatar_src: string}|null $defaultChannel */
/** @var list<array{tag: string, text: string}> $promptSuggestions */
/** @var array{negotiation_enabled?: bool, max_discount_percent?: float|int|string, negotiation_rules?: string, upsell_enabled?: bool, upsell_notes?: string, followup_enabled?: bool, followup_delay_hours?: int, followup_max_attempts?: int, followup_interval_hours?: int, followup_notes?: string, receipt_enabled?: bool, receipt_message?: string} $sellingSettings */
/** @var bool $canNegotiate */
/** @var bool $canFollowup */
/** @var bool $canReceipt */
/** @var list<array{id: string, label: string, checked: bool, enabled: bool}> $ownerAlertRules */
/** @var array<string, mixed> $alertsContext */
?>
<div class="ai-wisdom" data-ai-wisdom>
    <aside class="ai-wisdom-sidebar" data-ai-wisdom-sidebar aria-label="AI Salesgirl navigation">
        <div class="ai-wisdom-sidebar-head">
            <h2 class="ai-wisdom-sidebar-title font-heading">AI Salesgirl</h2>
            <button type="button" class="ai-wisdom-sidebar-collapse" data-ai-wisdom-sidebar-close aria-label="Close sidebar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <a href="<?= e(app_route('ai-salesgirl')) ?>" class="ai-wisdom-new-chat">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            New chat
        </a>

        <nav class="ai-wisdom-nav" aria-label="Sections">
            <button type="button" class="ai-wisdom-nav-item is-active" data-ai-wisdom-view="chat">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                </svg>
                Chat
            </button>
            <button type="button" class="ai-wisdom-nav-item" data-ai-wisdom-view="knowledge">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.75"/>
                </svg>
                Knowledge
            </button>
            <button type="button" class="ai-wisdom-nav-item" data-ai-wisdom-view="selling">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                    <circle cx="12" cy="12" r="3.25" stroke="currentColor" stroke-width="1.75"/>
                </svg>
                Selling
            </button>
            <button type="button" class="ai-wisdom-nav-item" data-ai-wisdom-view="alerts">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                Owner alerts
            </button>
            <button type="button" class="ai-wisdom-nav-item" data-ai-profile-edit>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Brand voice
            </button>
        </nav>

        <div class="ai-wisdom-history">
            <p class="ai-wisdom-history-label">Chat history</p>
            <ul class="ai-wisdom-history-list" data-ai-chat-history>
                <li class="ai-wisdom-history-empty">No chats yet</li>
            </ul>
        </div>
    </aside>

    <div class="ai-wisdom-main">
        <section class="ai-wisdom-view is-active" data-ai-wisdom-panel="chat">
            <header class="ai-wisdom-hero">
                <h1 class="ai-wisdom-greeting font-heading">
                    <?= e($timeGreeting) ?>, <span class="ai-wisdom-greeting-name"><?= e($userFirstName) ?></span>
                </h1>
                <p class="ai-wisdom-lead">
                    <?= e($aiName) ?> is your social-first AI assistant for replying in DMs, drafting messages, and testing how customers hear your brand.
                </p>
            </header>

            <div class="ai-wisdom-thread" data-ai-playground-thread aria-live="polite"<?= ($testResponse !== '' || $testError !== '') ? '' : ' hidden' ?>>
                <?php if ($testResponse !== ''): ?>
                    <div class="ai-wisdom-msg ai-wisdom-msg--in">
                        <span class="ai-wisdom-msg-label">Customer</span>
                        <p><?= e($testMessage) ?></p>
                    </div>
                    <div class="ai-wisdom-msg ai-wisdom-msg--out">
                        <span class="ai-wisdom-msg-label" data-ai-playground-name><?= e($aiName) ?></span>
                        <p><?= chat_format_message_html($testResponse) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <p class="ai-wisdom-error<?= $testError !== '' ? '' : ' is-hidden' ?>" data-ai-playground-error role="alert"><?= e($testError) ?></p>

            <div
                class="ai-wisdom-compose-wrap"
                data-ai-profile-name="<?= e($aiName) ?>"
                data-ai-business-name="<?= e($businessName) ?>"
                data-ai-greeting="<?= e($aiGreeting) ?>"
                data-ai-tone="<?= e($aiTone) ?>"
            >
                <form class="ai-wisdom-compose" data-ai-test-form novalidate>
                    <label class="ai-sr-only" for="ai-test-input">Message</label>
                    <textarea
                        id="ai-test-input"
                        class="ai-wisdom-compose-input"
                        name="test"
                        rows="3"
                        placeholder="Ask about a product, draft a reply, or test how <?= e($aiName) ?> handles a customer DM…"
                        autocomplete="off"
                    ></textarea>

                    <div class="ai-wisdom-compose-bar">
                        <button type="submit" class="ai-wisdom-send" data-ai-test-send aria-label="Send message">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <div class="ai-wisdom-prompts" data-ai-prompts>
                <button type="button" class="ai-wisdom-prompts-nav ai-wisdom-prompts-prev" data-ai-prompts-prev aria-label="Previous suggestions" hidden>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="ai-wisdom-prompts-track" data-ai-prompts-track>
                    <?php foreach ($promptSuggestions as $prompt): ?>
                        <button type="button" class="ai-wisdom-prompt-card" data-ai-prompt="<?= e($prompt['text']) ?>">
                            <span class="ai-wisdom-prompt-tag"><?= e($prompt['tag']) ?></span>
                            <span class="ai-wisdom-prompt-text"><?= e($prompt['text']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="ai-wisdom-prompts-nav ai-wisdom-prompts-next" data-ai-prompts-next aria-label="More suggestions">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <p class="ai-wisdom-disclaimer">
                Like all AI tools, <?= e($aiName) ?> may not always be accurate. Verify important details before sending to customers.
            </p>
        </section>

        <section class="ai-wisdom-view" data-ai-wisdom-panel="knowledge" hidden>
            <?php require __DIR__ . '/knowledge-training.php'; ?>
        </section>

        <section class="ai-wisdom-view" data-ai-wisdom-panel="selling" hidden>
            <header class="ai-wisdom-panel-head">
                <h2 class="ai-wisdom-panel-title font-heading">Selling</h2>
            </header>
            <p class="ai-wisdom-panel-lead">Train how <?= e($aiName) ?> negotiates, follows up, and sends receipts.</p>

            <?php if (empty($canNegotiate) && empty($canFollowup) && empty($canReceipt)): ?>
                <div class="ai-sell-locked">
                    <p class="ai-wisdom-empty">Negotiation starts on Vendor. Follow-ups and receipts unlock on Shop.</p>
                    <a href="<?= e(app_route('billing')) ?>" class="ai-wisdom-primary-btn">Upgrade to unlock</a>
                </div>
            <?php else: ?>
                <form class="ai-sell-form" data-ai-selling-form>
                    <?php if (!empty($canNegotiate)): ?>
                        <section class="ai-sell-card" aria-labelledby="ai-sell-negotiate-title">
                            <div class="ai-sell-card-top">
                                <div>
                                    <p class="ai-sell-badge">Vendor+</p>
                                    <h3 id="ai-sell-negotiate-title" class="ai-sell-card-title font-heading">Negotiate &amp; close</h3>
                                    <p class="ai-sell-card-copy">Set the discount ceiling, objection style, and upsells.</p>
                                </div>
                                <label class="ai-sell-switch">
                                    <input type="checkbox" name="negotiation_enabled" value="1"<?= !empty($sellingSettings['negotiation_enabled']) ? ' checked' : '' ?>>
                                    <span class="ai-sell-switch-ui" aria-hidden="true"></span>
                                    <span class="ai-sr-only">Enable negotiation</span>
                                </label>
                            </div>

                            <div class="ai-sell-fields">
                                <label class="ai-field" for="max_discount_percent">
                                    <span class="ai-field-label">Max discount (%)</span>
                                    <input
                                        class="ai-field-input"
                                        type="number"
                                        inputmode="decimal"
                                        id="max_discount_percent"
                                        name="max_discount_percent"
                                        min="0"
                                        max="90"
                                        step="0.5"
                                        value="<?= e((string) ($sellingSettings['max_discount_percent'] ?? '10')) ?>"
                                    >
                                </label>
                                <label class="ai-field ai-field--full" for="negotiation_rules">
                                    <span class="ai-field-label">How to negotiate</span>
                                    <textarea class="ai-field-input ai-field-textarea" id="negotiation_rules" name="negotiation_rules" rows="4" maxlength="2000" placeholder="Example: Hold firm on custom cakes. Bundle free delivery only over ₦50,000."><?= e((string) ($sellingSettings['negotiation_rules'] ?? '')) ?></textarea>
                                </label>
                                <label class="ai-sell-inline-check">
                                    <input type="checkbox" name="upsell_enabled" value="1"<?= !empty($sellingSettings['upsell_enabled']) ? ' checked' : '' ?>>
                                    <span>Suggest upsells when it fits</span>
                                </label>
                                <label class="ai-field ai-field--full" for="upsell_notes">
                                    <span class="ai-field-label">Upsell notes</span>
                                    <textarea class="ai-field-input ai-field-textarea" id="upsell_notes" name="upsell_notes" rows="3" maxlength="1000" placeholder="Example: Offer matching accessories or express delivery."><?= e((string) ($sellingSettings['upsell_notes'] ?? '')) ?></textarea>
                                </label>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="ai-sell-card ai-sell-card--locked">
                            <p class="ai-sell-badge">Vendor+</p>
                            <h3 class="ai-sell-card-title font-heading">Negotiate &amp; close</h3>
                            <p class="ai-sell-card-copy">Unlock discount limits, objection handling, and upsells on Vendor.</p>
                            <a href="<?= e(app_route('billing')) ?>" class="ai-sell-upgrade">Upgrade</a>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($canFollowup)): ?>
                        <section class="ai-sell-card" aria-labelledby="ai-sell-followup-title">
                            <div class="ai-sell-card-top">
                                <div>
                                    <p class="ai-sell-badge ai-sell-badge--shop">Shop</p>
                                    <h3 id="ai-sell-followup-title" class="ai-sell-card-title font-heading">Cold lead follow-ups</h3>
                                    <p class="ai-sell-card-copy">Re-engage buyers who go quiet. Stays inside the ~24h messaging window.</p>
                                </div>
                                <label class="ai-sell-switch">
                                    <input type="checkbox" name="followup_enabled" value="1"<?= !empty($sellingSettings['followup_enabled']) ? ' checked' : '' ?>>
                                    <span class="ai-sell-switch-ui" aria-hidden="true"></span>
                                    <span class="ai-sr-only">Enable follow-ups</span>
                                </label>
                            </div>

                            <div class="ai-sell-fields ai-sell-fields--grid">
                                <label class="ai-field" for="followup_delay_hours">
                                    <span class="ai-field-label">Wait before first nudge (hours)</span>
                                    <input class="ai-field-input" type="number" inputmode="numeric" id="followup_delay_hours" name="followup_delay_hours" min="1" max="72" value="<?= e((string) ($sellingSettings['followup_delay_hours'] ?? '6')) ?>">
                                </label>
                                <label class="ai-field" for="followup_interval_hours">
                                    <span class="ai-field-label">Hours between nudges</span>
                                    <input class="ai-field-input" type="number" inputmode="numeric" id="followup_interval_hours" name="followup_interval_hours" min="1" max="72" value="<?= e((string) ($sellingSettings['followup_interval_hours'] ?? '12')) ?>">
                                </label>
                                <label class="ai-field" for="followup_max_attempts">
                                    <span class="ai-field-label">Max follow-ups</span>
                                    <input class="ai-field-input" type="number" inputmode="numeric" id="followup_max_attempts" name="followup_max_attempts" min="1" max="5" value="<?= e((string) ($sellingSettings['followup_max_attempts'] ?? '2')) ?>">
                                </label>
                                <label class="ai-field ai-field--full" for="followup_notes">
                                    <span class="ai-field-label">Follow-up style</span>
                                    <textarea class="ai-field-input ai-field-textarea" id="followup_notes" name="followup_notes" rows="3" maxlength="1000" placeholder="Example: Soft check-in. Mention we still have their size if it was discussed."><?= e((string) ($sellingSettings['followup_notes'] ?? '')) ?></textarea>
                                </label>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="ai-sell-card ai-sell-card--locked">
                            <p class="ai-sell-badge ai-sell-badge--shop">Shop</p>
                            <h3 class="ai-sell-card-title font-heading">Cold lead follow-ups</h3>
                            <p class="ai-sell-card-copy">Automatic re-engagement when buyers stop replying unlocks on Shop.</p>
                            <a href="<?= e(app_route('billing')) ?>" class="ai-sell-upgrade">Upgrade</a>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($canReceipt)): ?>
                        <section class="ai-sell-card" aria-labelledby="ai-sell-receipt-title">
                            <div class="ai-sell-card-top">
                                <div>
                                    <p class="ai-sell-badge ai-sell-badge--shop">Shop</p>
                                    <h3 id="ai-sell-receipt-title" class="ai-sell-card-title font-heading">Payment receipts</h3>
                                    <p class="ai-sell-card-copy">Send a receipt message after someone says they paid.</p>
                                </div>
                                <label class="ai-sell-switch">
                                    <input type="checkbox" name="receipt_enabled" value="1"<?= !empty($sellingSettings['receipt_enabled']) ? ' checked' : '' ?>>
                                    <span class="ai-sell-switch-ui" aria-hidden="true"></span>
                                    <span class="ai-sr-only">Enable receipts</span>
                                </label>
                            </div>

                            <div class="ai-sell-fields">
                                <label class="ai-field ai-field--full" for="receipt_message">
                                    <span class="ai-field-label">Receipt message</span>
                                    <textarea class="ai-field-input ai-field-textarea" id="receipt_message" name="receipt_message" rows="5" maxlength="2000" placeholder="<?= e(SalesAutomationService::DEFAULT_RECEIPT) ?>"><?= e((string) ($sellingSettings['receipt_message'] ?? '')) ?></textarea>
                                    <span class="ai-sell-hint">Tokens: {business_name}, {date}, {amount_line}, {amount}</span>
                                </label>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="ai-sell-card ai-sell-card--locked">
                            <p class="ai-sell-badge ai-sell-badge--shop">Shop</p>
                            <h3 class="ai-sell-card-title font-heading">Payment receipts</h3>
                            <p class="ai-sell-card-copy">Auto receipts after payment claims unlock on Shop.</p>
                            <a href="<?= e(app_route('billing')) ?>" class="ai-sell-upgrade">Upgrade</a>
                        </section>
                    <?php endif; ?>

                    <div class="ai-sell-actions">
                        <button type="submit" class="ai-wisdom-primary-btn" data-ai-selling-submit>Save selling rules</button>
                        <p class="ai-wisdom-alert-status" data-ai-selling-status aria-live="polite"></p>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <section class="ai-wisdom-view" data-ai-wisdom-panel="alerts" hidden>
            <header class="ai-wisdom-panel-head">
                <h2 class="ai-wisdom-panel-title font-heading">Owner alerts</h2>
            </header>

            <?php if ($hasFullSettings): ?>
                <p class="ai-wisdom-panel-lead">Choose when Lead Bird should notify you instead of auto-replying.</p>
                <form class="ai-wisdom-alert-form" data-ai-alerts-form>
                    <ul class="ai-wisdom-alert-list">
                        <?php foreach ($ownerAlertRules as $rule): ?>
                            <li class="ai-wisdom-alert-item">
                                <label class="ai-wisdom-alert-check">
                                    <input
                                        type="checkbox"
                                        name="<?= e((string) $rule['id']) ?>"
                                        value="1"
                                        <?= !empty($rule['checked']) ? ' checked' : '' ?>
                                        <?= empty($rule['enabled']) ? ' disabled' : '' ?>
                                    >
                                    <span class="ai-wisdom-alert-check-box" aria-hidden="true"></span>
                                    <span class="ai-wisdom-alert-check-label"><?= e((string) $rule['label']) ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="ai-wisdom-alert-status" data-ai-alerts-status aria-live="polite"></p>
                </form>
                <a href="<?= e(app_route('onboarding/alerts')) ?>" class="ai-wisdom-text-link">Contact &amp; notification settings</a>
            <?php else: ?>
                <p class="ai-wisdom-empty">Upgrade your plan to customize owner alert rules.</p>
                <a href="<?= e(app_route('billing')) ?>" class="ai-wisdom-primary-btn">Upgrade to unlock</a>
            <?php endif; ?>
        </section>
    </div>
</div>

<div class="ai-modal-backdrop is-hidden" data-ai-profile-overlay aria-hidden="true">
    <div class="ai-modal" role="dialog" aria-modal="true" aria-labelledby="ai-profile-modal-title">
        <button type="button" class="ai-modal-close" data-ai-profile-close aria-label="Close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <h2 id="ai-profile-modal-title" class="ai-modal-title font-heading">Brand voice</h2>

        <form class="ai-modal-form" data-ai-profile-form novalidate>
            <div class="ai-modal-grid">
                <label class="ai-field">
                    <span class="ai-field-label">Name</span>
                    <input class="ai-field-input" type="text" name="ai_name" maxlength="100" value="<?= e($aiName) ?>" required>
                </label>
                <label class="ai-field">
                    <span class="ai-field-label">Business</span>
                    <input class="ai-field-input" type="text" name="business_name" maxlength="255" value="<?= e($businessName) ?>" required>
                </label>
                <label class="ai-field ai-field--full" id="ai-tone-label">
                    <span class="ai-field-label" id="ai_tone-label">Tone</span>
                    <select class="ai-field-input ai-field-select" id="ai_tone" name="ai_tone" required aria-labelledby="ai-tone-label">
                        <?php foreach ($aiToneOptions as $toneOption): ?>
                            <option value="<?= e($toneOption) ?>"<?= $aiTone === $toneOption ? ' selected' : '' ?>><?= e($toneOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="ai-field ai-field--full">
                    <span class="ai-field-label">Greeting</span>
                    <textarea class="ai-field-input ai-field-textarea" name="ai_greeting" maxlength="500" rows="3" required><?= e($aiGreeting) ?></textarea>
                </label>
            </div>

            <p class="ai-form-error is-hidden" data-ai-profile-error role="alert"></p>
            <button type="submit" class="ai-primary-btn ai-primary-btn--block" data-ai-profile-submit>Save</button>
        </form>
    </div>
</div>

<script>
    window.LeadBirdKnowledgeTraining = {
        saveTextUrl: <?= json_encode(app_route('ai-salesgirl/save-knowledge-text'), JSON_THROW_ON_ERROR) ?>,
        saveLinkUrl: <?= json_encode(app_route('ai-salesgirl/save-knowledge-link'), JSON_THROW_ON_ERROR) ?>,
        crawlLinkUrl: <?= json_encode(app_route('ai-salesgirl/crawl-knowledge-link'), JSON_THROW_ON_ERROR) ?>,
        processLinkUrl: <?= json_encode(app_route('ai-salesgirl/process-knowledge-link'), JSON_THROW_ON_ERROR) ?>,
        linkStatusUrl: <?= json_encode(app_route('ai-salesgirl/link-crawl-status'), JSON_THROW_ON_ERROR) ?>,
        uploadDocumentUrl: <?= json_encode(app_route('ai-salesgirl/upload-knowledge-document'), JSON_THROW_ON_ERROR) ?>,
        saveFaqUrl: <?= json_encode(app_route('ai-salesgirl/save-knowledge-faq'), JSON_THROW_ON_ERROR) ?>,
        uploadFaqAssetUrl: <?= json_encode(app_route('ai-salesgirl/upload-knowledge-faq-asset'), JSON_THROW_ON_ERROR) ?>,
        deleteItemUrl: <?= json_encode(app_route('ai-salesgirl/delete-knowledge-item'), JSON_THROW_ON_ERROR) ?>,
        documentIconUrl: <?= json_encode(brand_upload_icon_url(), JSON_THROW_ON_ERROR) ?>,
    };
    window.LeadBirdAiProfile = {
        saveUrl: <?= json_encode(app_route('ai-salesgirl/save-profile'), JSON_THROW_ON_ERROR) ?>,
        saveAlertsUrl: <?= json_encode(app_route('ai-salesgirl/save-alerts'), JSON_THROW_ON_ERROR) ?>,
        saveSellingUrl: <?= json_encode(app_route('ai-salesgirl/save-selling'), JSON_THROW_ON_ERROR) ?>,
        syncKnowledgeUrl: <?= json_encode(app_route('ai-salesgirl/sync-knowledge'), JSON_THROW_ON_ERROR) ?>,
        dismissMemoryUrl: <?= json_encode(app_route('ai-salesgirl/dismiss-memory'), JSON_THROW_ON_ERROR) ?>,
    };
    window.LeadBirdAiPlayground = {
        businessId: <?= json_encode($businessId ?? '', JSON_THROW_ON_ERROR) ?>,
        baseUrl: <?= json_encode(app_route('ai-salesgirl'), JSON_THROW_ON_ERROR) ?>,
        currentTest: <?= json_encode($testMessage, JSON_THROW_ON_ERROR) ?>,
        hasResponse: <?= json_encode($testResponse !== '', JSON_THROW_ON_ERROR) ?>,
        testChatUrl: <?= json_encode(app_route('ai-salesgirl/test-chat'), JSON_THROW_ON_ERROR) ?>,
        aiName: <?= json_encode($aiName, JSON_THROW_ON_ERROR) ?>,
    };
</script>
<script src="<?= e($appUrl) ?>/assets/js/chat-message-format.js?v=1"></script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-ai-salesgirl.js?v=19"></script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-knowledge-training.js?v=22"></script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-knowledge-qa.js?v=6"></script>
