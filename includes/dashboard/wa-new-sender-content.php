<?php

declare(strict_types=1);

/** @var string $sendersUrl */
/** @var bool $signupReady */
/** @var array{app_id: string, config_id: string, graph_version: string, endpoint: string} $signup */

$tickIcon = '<svg class="ws-tick" viewBox="0 0 16 16" width="18" height="18" aria-hidden="true"><circle cx="8" cy="8" r="7.5" fill="currentColor"/><path d="m5 8.2 2 2 4-4.2" fill="none" stroke="var(--brand-white)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$infoIcon = '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="currentColor"/><path d="M8 7.2v4M8 4.8v.1" stroke="var(--brand-white)" stroke-width="1.6" stroke-linecap="round"/></svg>';
?>
<div
    class="ws-page ws-page--detail ws-page--full"
    data-ws-es
    data-app-id="<?= e($signup['app_id']) ?>"
    data-config-id="<?= e($signup['config_id']) ?>"
    data-graph-version="<?= e($signup['graph_version']) ?>"
    data-endpoint="<?= e($signup['endpoint']) ?>"
>
    <a class="ws-back" href="<?= e($sendersUrl) ?>">
        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Senders
    </a>

    <h1 class="ws-title font-heading">New sender</h1>

    <?php if (!$signupReady): ?>
        <div class="ws-alert ws-alert--bad" role="alert">Connecting new numbers is not available right now. Please try again later or contact support.</div>
    <?php endif; ?>

    <section class="ws-card ws-acc">
        <h2 class="ws-acc-title font-heading">
            <button type="button" class="ws-acc-head" aria-expanded="true" aria-controls="ws-step-1" data-ws-acc><svg class="ws-acc-chev" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="m6 3 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>1. Get your number ready</button>
        </h2>
        <div id="ws-step-1" class="ws-acc-body">

            <p class="ws-section-copy">Make sure your number is ready before you link it:</p>

            <ul class="ws-checklist">
                <li><?= $tickIcon ?><span><b>It isn’t on WhatsApp.</b> Use a new number, or delete WhatsApp from it first: in the app, go to Settings, then Account, then Delete account. Back up your chats before you do, as deleting removes them from your phone.</span></li>
                <li><?= $tickIcon ?><span><b>It can receive a text or call.</b> Meta sends a code to this number to confirm it’s yours.</span></li>
                <li><?= $tickIcon ?><span><b>No other provider is using it.</b> If it’s connected to Wati, Twilio or another WhatsApp Business API provider, ask them to release it.</span></li>
            </ul>

        <div class="ws-form-actions">
            <button type="button" class="ws-btn ws-btn--primary" data-ws-acc-open="ws-step-2">Continue</button>
            <a class="ws-btn ws-btn--ghost" href="<?= e($sendersUrl) ?>">Cancel</a>
        </div>
        </div>
    </section>

    <section class="ws-card ws-acc">
        <h2 class="ws-acc-title font-heading">
            <button type="button" class="ws-acc-head" aria-expanded="false" aria-controls="ws-step-2" data-ws-acc><svg class="ws-acc-chev" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="m6 3 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>2. Link your WhatsApp Business account</button>
        </h2>
        <div id="ws-step-2" class="ws-acc-body" hidden>

        <div class="ws-note">
            <?= $infoIcon ?>
            <p><strong>Keep this page open.</strong> A Meta window opens where you log in with Facebook, choose or create your business, and confirm your number.</p>
        </div>

        <p class="ws-section-copy">When it’s done, your number appears on the Senders page. Hannah can reply to customers on it, and you can send bulk messages once you add a payment card in Meta, since Meta charges a small fee per message.</p>

        <div class="ws-form-actions">
            <button type="button" class="ws-btn ws-btn--primary" data-ws-es-start disabled>Continue with Facebook</button>
            <a class="ws-btn ws-btn--ghost" href="<?= e($sendersUrl) ?>">Cancel</a>
        </div>
        <p class="ws-hint" data-ws-es-status role="status" aria-live="polite"></p>
        </div>
    </section>
</div>
