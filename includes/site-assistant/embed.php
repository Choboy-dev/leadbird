<?php

declare(strict_types=1);

/** @var string $appUrl */

if (!empty($skipSiteAssistant)) {
    return;
}

require_once dirname(__DIR__, 2) . '/config/plans.php';
require_once dirname(__DIR__, 2) . '/lib/VanceVoice.php';
require_once dirname(__DIR__, 2) . '/lib/LbPublicGuideService.php';

$faviconUrl = $appUrl . '/assets/images/leadbird-favicon.png';
$assistant = new SiteAssistantService();
$suggested = $assistant->suggestedQuestions();
?>
<link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/brand.css">
<link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/site-assistant.css?v=6">

<div class="lb-assistant" data-lb-assistant aria-live="polite">
    <div class="lb-assistant-panel is-hidden" data-lb-assistant-panel hidden aria-hidden="true">
        <header class="lb-assistant-header">
            <div class="lb-assistant-header-brand">
                <span class="lb-assistant-header-icon">
                    <img src="<?= e($faviconUrl) ?>" width="28" height="28" alt="">
                </span>
                <div>
                    <h2 class="lb-assistant-title font-heading" id="lb-assistant-title">Chat with Hannah</h2>
                </div>
            </div>
            <button type="button" class="lb-assistant-close" data-lb-assistant-close aria-label="Close chat">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <div class="lb-assistant-messages" data-lb-assistant-messages role="log" aria-labelledby="lb-assistant-title" aria-relevant="additions">
            <article class="lb-assistant-msg lb-assistant-msg--bot">
                <span class="lb-assistant-msg-avatar" aria-hidden="true">
                    <img src="<?= e($faviconUrl) ?>" width="22" height="22" alt="">
                </span>
                <div class="lb-assistant-msg-bubble">
                    <p><?= e($assistant->greeting()) ?></p>
                </div>
            </article>
        </div>

        <div class="lb-assistant-suggestions" data-lb-assistant-suggestions>
            <?php foreach ($suggested as $question): ?>
                <button type="button" class="lb-assistant-chip" data-lb-assistant-suggest="<?= e($question) ?>">
                    <?= e($question) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <form class="lb-assistant-compose" data-lb-assistant-form>
            <label class="lb-assistant-compose-label" for="lb-assistant-input">Your question</label>
            <div class="lb-assistant-compose-row">
                <input
                    id="lb-assistant-input"
                    class="lb-assistant-input"
                    type="text"
                    name="message"
                    placeholder="Ask Hannah anything about Lead Bird…"
                    autocomplete="off"
                    maxlength="800"
                    data-lb-assistant-input
                >
                <button type="submit" class="lb-assistant-send" data-lb-assistant-send aria-label="Send message">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m22 2-7 20-4-9-9-4Z"/>
                        <path d="M22 2 11 13"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <button
        type="button"
        class="lb-assistant-launcher"
        data-lb-assistant-launcher
        aria-expanded="false"
        aria-controls="lb-assistant-title"
        aria-label="Open chat with Hannah"
    >
        <span class="lb-assistant-launcher-ring" aria-hidden="true"></span>
        <span class="lb-assistant-launcher-bounce" aria-hidden="true">
            <img src="<?= e($faviconUrl) ?>" width="34" height="34" alt="">
        </span>
        <span class="lb-assistant-launcher-label font-heading">Ask Hannah</span>
    </button>
</div>

<script>
    window.LeadBirdSiteAssistant = {
        chatUrl: <?= json_encode(app_route('site-assistant/chat'), JSON_THROW_ON_ERROR) ?>,
        faviconUrl: <?= json_encode($faviconUrl, JSON_THROW_ON_ERROR) ?>,
    };
</script>
<script src="<?= e($appUrl) ?>/assets/js/chat-message-format.js?v=1"></script>
<script src="<?= e($appUrl) ?>/assets/js/site-assistant.js?v=4"></script>
