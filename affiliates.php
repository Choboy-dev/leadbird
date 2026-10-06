<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Become a Partner — Lead Bird';
$metaTitle = 'Lead Bird Partners — Earn 10% for every referral';
$metaDescription = 'Share your Lead Bird partner link. Earn 10% when a business pays for their first plan, then withdraw to your bank.';
$appUrl = app_config()['app_url'];
$extraStylesheets = ['contact.css', 'affiliates.css?v=3'];
$activeNav = null;

$applyUrl = app_route('affiliates/apply');
$loginUrl = app_route('login');

$payouts = [
    ['plan' => 'Starter', 'amount' => '$2.90', 'note' => '10% of first plan payment'],
    ['plan' => 'Vendor', 'amount' => '$5.90', 'note' => '10% of first plan payment'],
    ['plan' => 'Shop', 'amount' => '$9.90', 'note' => '10% of first plan payment'],
];

$steps = [
    ['title' => 'Get your link', 'copy' => 'Apply in minutes. You get a unique referral URL to share anywhere.'],
    ['title' => 'Share it', 'copy' => 'Send it to founders who need an AI Salesgirl in WhatsApp, Instagram, or Messenger.'],
    ['title' => 'Earn 10%', 'copy' => 'When they pay for their first plan, your commission lands after a short hold — then withdraw.'],
];

require __DIR__ . '/includes/home/layout-start.php';
?>
<div class="partners-page min-h-screen flex flex-col font-sans">
    <?php require __DIR__ . '/includes/home/marketing-header.php'; ?>

    <main class="flex-1">
        <section class="partners-hero" aria-labelledby="partners-title">
            <div class="brand-grid-bg absolute inset-0 pointer-events-none opacity-70" aria-hidden="true"></div>
            <div class="partners-hero-inner">
                <p class="partners-brand" data-reveal style="--reveal-i: 0">
                    <span class="partners-brand-lead">LEAD</span>
                    <span class="partners-brand-bird">BIRD</span>
                </p>
                <h1 id="partners-title" class="partners-title" data-reveal style="--reveal-i: 1">
                    Earn <span class="partners-title-accent">10%</span> for every business you bring.
                </h1>
                <p class="partners-lead" data-reveal style="--reveal-i: 2">
                    Share your partner link. When they pay for their first Lead Bird plan, you earn. Withdraw to your bank anytime.
                </p>
                <div class="partners-cta-row" data-reveal style="--reveal-i: 3">
                    <a href="<?= e($applyUrl) ?>" class="partners-btn partners-btn--lime">Become a partner</a>
                    <a href="<?= e($loginUrl) ?>" class="partners-btn partners-btn--ghost">Sign in</a>
                </div>
            </div>
        </section>

        <div class="partners-section-full">
            <section class="partners-section" aria-labelledby="partners-payouts-title">
                <p class="partners-kicker" data-reveal>What you earn</p>
                <h2 id="partners-payouts-title" class="partners-h2" data-reveal>First paid plan. Ten percent. Paid to you.</h2>
                <p class="partners-section-lead" data-reveal>
                    Commission is on the first successful subscription only — not renewals, not token top-ups.
                </p>
                <div class="partners-payouts" role="list">
                    <?php foreach ($payouts as $i => $row): ?>
                        <article class="partners-payout" role="listitem" data-reveal style="--reveal-i: <?= (int) $i ?>">
                            <p class="partners-payout-plan"><?= e($row['plan']) ?></p>
                            <p class="partners-payout-amount"><?= e($row['amount']) ?></p>
                            <p class="partners-payout-note"><?= e($row['note']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <section class="partners-section" aria-labelledby="partners-steps-title">
            <p class="partners-kicker" data-reveal>How it works</p>
            <h2 id="partners-steps-title" class="partners-h2" data-reveal>Three steps. No complicated setup.</h2>
            <div class="partners-steps">
                <?php foreach ($steps as $i => $step): ?>
                    <article class="partners-step" data-reveal style="--reveal-i: <?= (int) $i ?>">
                        <span class="partners-step-num" aria-hidden="true"><?= (int) $i + 1 ?></span>
                        <h3 class="partners-step-title"><?= e($step['title']) ?></h3>
                        <p class="partners-step-copy"><?= e($step['copy']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="partners-closing" aria-labelledby="partners-closing-title">
            <div class="brand-grid-bg absolute inset-0 pointer-events-none opacity-50" aria-hidden="true"></div>
            <div class="partners-closing-inner">
                <div data-reveal>
                    <h2 id="partners-closing-title" class="partners-closing-title">Ready to refer?</h2>
                    <p class="partners-closing-copy">
                        Create your partner link now. Track earnings and withdraw from the dashboard after you sign in.
                    </p>
                </div>
                <div class="partners-cta-row" data-reveal style="--reveal-i: 1">
                    <a href="<?= e($applyUrl) ?>" class="partners-btn partners-btn--lime">Get my link</a>
                    <a href="<?= e($loginUrl) ?>" class="partners-btn partners-btn--ghost">Sign in</a>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/home/footer.php'; ?>
</div>
<script>
(function () {
    var page = document.querySelector('.partners-page');
    if (!page) return;
    var els = page.querySelectorAll('[data-reveal]');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) {
        els.forEach(function (el) { el.classList.add('is-visible'); });
        return;
    }
    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -24px 0px' });
    els.forEach(function (el) { obs.observe(el); });
})();
</script>
<?php require __DIR__ . '/includes/home/layout-end.php'; ?>
