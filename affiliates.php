<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Refer & Earn — Lead Bird Partners';
$metaTitle = 'Share Lead Bird, earn 10% — Lead Bird Partners';
$metaDescription = 'Share your Lead Bird partner link. Earn 10% when a business pays for its first plan, then withdraw to your Nigerian bank account.';
$appUrl = app_config()['app_url'];
$extraStylesheets = ['contact.css', 'affiliates.css?v=4'];
$activeNav = null;

$applyUrl = app_route('affiliates/apply');
$dashboardUrl = app_route('partners/dashboard');
$commissionPct = (int) round(AffiliateService::COMMISSION_RATE * 100);
$holdDays = AffiliateService::HOLD_DAYS;
$linkDays = AffiliateService::COOKIE_DAYS;
$minWithdraw = money_format(AffiliateService::MIN_WITHDRAW_USD, 'USD');

$steps = [
    [
        'title' => 'Get your link',
        'copy' => 'Create a free partner account with your name and email. Your referral link is ready straight away.',
        'image' => 'step-1.webp',
        'alt' => 'Woman sitting on the grass, smiling at her phone',
    ],
    [
        'title' => 'Share your link',
        'copy' => 'Send it to business owners who sell on WhatsApp, Instagram, Messenger or TikTok and need Hannah to reply for them.',
        'image' => 'step-2.webp',
        'alt' => 'Woman with headphones typing a message on her phone',
    ],
    [
        'title' => 'Get paid',
        'copy' => 'Earn ' . $commissionPct . '% of their first plan payment. Claim it from your partner dashboard and we send it to your bank.',
        'image' => 'step-3.webp',
        'alt' => 'Man smiling at his phone',
    ],
];

$faqs = [
    [
        'q' => 'How does the Lead Bird referral programme work?',
        'a' => [
            'You share your partner link. When a business signs up through it and pays for its first Lead Bird plan, you earn ' . $commissionPct . '% of that payment.',
            'Share it however you like: WhatsApp, Instagram, TikTok, X, email, or a chat with a friend who runs a business.',
        ],
    ],
    [
        'q' => 'How much can I earn?',
        'a' => [
            'You earn ' . $commissionPct . '% of the first payment. That is $2.90, $5.90 or $9.90 on the Starter, Vendor and Shop monthly plans, and $29, $59 or $99 on the yearly plans.',
            'There is no limit to how many businesses you can refer.',
        ],
    ],
    [
        'q' => 'What counts as a successful referral?',
        'a' => [
            'The business opens your link, creates a Lead Bird account within ' . $linkDays . ' days, and pays for its first plan.',
            'Only the first plan payment counts. Renewals and token top-ups do not earn a commission.',
        ],
    ],
    [
        'q' => 'How can I track my referrals?',
        'a' => [
            'Sign in to your partner dashboard with the email you used to join. You will see how many people opened your link, who signed up, who paid, and what you have earned.',
        ],
    ],
    [
        'q' => 'What stops a referral from earning?',
        'a' => [
            'You will not earn if the business never pays for a plan, or if it is refunded. Referring your own account does not count.',
        ],
    ],
    [
        'q' => 'How do I claim my reward?',
        'a' => [
            'Each reward is held for ' . $holdDays . ' days after the business pays. Once your balance reaches ' . $minWithdraw . ', tap Claim reward in your partner dashboard.',
            'We check the request, then send the money to your Nigerian bank account in naira at the day\'s rate.',
        ],
    ],
];

require __DIR__ . '/includes/home/layout-start.php';
?>
<div class="partners-page min-h-screen flex flex-col font-sans">
    <?php require __DIR__ . '/includes/home/marketing-header.php'; ?>

    <main class="flex-1">
        <section class="rf-hero" aria-labelledby="rf-title">
            <div class="rf-hero-inner">
                <div class="rf-hero-copy">
                    <p class="rf-eyebrow" data-reveal style="--reveal-i: 0">Referrals</p>
                    <h1 id="rf-title" class="rf-title" data-reveal style="--reveal-i: 1">Share Lead Bird, Earn <?= $commissionPct ?>%</h1>
                    <p class="rf-lead" data-reveal style="--reveal-i: 2">
                        Good tools are meant to be shared. Invite business owners to Lead Bird and earn <?= $commissionPct ?>% of their first plan payment, up to $99 per referral. Start sharing and earning today.
                    </p>
                    <div class="rf-cta-row" data-reveal style="--reveal-i: 3">
                        <a href="<?= e($applyUrl) ?>" class="rf-btn rf-btn--primary">Get your referral link</a>
                        <a href="<?= e($dashboardUrl) ?>" class="rf-btn rf-btn--soft">Sign in</a>
                    </div>
                </div>
                <div class="rf-hero-media" data-reveal style="--reveal-i: 2">
                    <img
                        src="<?= e($appUrl) ?>/assets/images/partners/hero.webp"
                        alt="Two friends smiling at a phone together"
                        width="864"
                        height="1152"
                        fetchpriority="high"
                    >
                </div>
            </div>
        </section>

        <section class="rf-steps" aria-labelledby="rf-steps-title">
            <div class="rf-steps-inner">
                <h2 id="rf-steps-title" class="rf-h2" data-reveal>How it works</h2>
                <div class="rf-step-grid">
                    <?php foreach ($steps as $i => $step): ?>
                        <article class="rf-step" data-reveal style="--reveal-i: <?= (int) $i ?>">
                            <div class="rf-step-text">
                                <p class="rf-step-num">Step <?= (int) $i + 1 ?></p>
                                <h3 class="rf-step-title"><?= e($step['title']) ?></h3>
                                <p class="rf-step-copy"><?= e($step['copy']) ?></p>
                            </div>
                            <div class="rf-step-media rf-step-media--<?= (int) $i + 1 ?>">
                                <img
                                    src="<?= e($appUrl) ?>/assets/images/partners/<?= e($step['image']) ?>"
                                    alt="<?= e($step['alt']) ?>"
                                    width="900"
                                    height="675"
                                    loading="lazy"
                                >
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="rf-faq" aria-labelledby="rf-faq-title">
            <div class="rf-faq-inner">
                <h2 id="rf-faq-title" class="rf-h2 rf-faq-title" data-reveal>Frequently asked questions</h2>
                <div class="rf-faq-list">
                    <?php foreach ($faqs as $i => $faq): ?>
                        <details class="rf-faq-item" <?= $i === 0 ? 'open' : '' ?>>
                            <summary class="rf-faq-q">
                                <span><?= e($faq['q']) ?></span>
                                <span class="rf-faq-icon" aria-hidden="true"></span>
                            </summary>
                            <div class="rf-faq-a">
                                <?php foreach ($faq['a'] as $para): ?>
                                    <p><?= e($para) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
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
