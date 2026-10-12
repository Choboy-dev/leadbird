<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$appUrl = app_config()['app_url'];
$pageTitle = 'Become a Partner — Lead Bird';
$metaTitle = 'Apply to Lead Bird Partners — Get your referral link';
$metaDescription = 'Create your Lead Bird partner account in under a minute. Share your link and earn 10% on first plan payments.';
$error = flash_get('error');
$success = flash_get('success');
$extraStylesheets = ['contact.css', 'affiliates.css?v=4'];
$activeNav = null;

$nameValue = trim((string) ($_POST['name'] ?? ''));
$emailValue = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!rate_limit_allow('affiliate_apply', 10, 3600)) {
        flash_set('error', 'Too many applications. Please wait and try again.');
        redirect(app_route('affiliates/apply'));
    }

    $result = (new AffiliateService())->applyPublic(
        $nameValue,
        $emailValue,
        $phoneValue !== '' ? $phoneValue : null
    );

    if (!($result['ok'] ?? false)) {
        flash_set('error', (string) ($result['error'] ?? 'Could not create your partner account.'));
        redirect(app_route('affiliates/apply'));
    }

    $affiliate = $result['affiliate'];
    $_SESSION['affiliate_id'] = (string) ($affiliate['id'] ?? '');
    flash_set('success', 'You are in. Your link is ready — sign in to manage payouts in the dashboard.');
    redirect(app_route('affiliates/welcome') . '?code=' . rawurlencode((string) ($affiliate['code'] ?? '')));
}

$perks = [
    'Instant unique referral link',
    '10% on every first paid plan',
    'Withdraw to your Nigerian bank',
];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="partners-page min-h-screen flex flex-col font-sans">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="flex-1">
        <div class="partners-apply-shell">
            <section class="partners-apply-pitch" aria-labelledby="partners-apply-title">
                <div class="brand-grid-bg absolute inset-0 pointer-events-none opacity-60" aria-hidden="true"></div>
                <div class="partners-apply-pitch-inner">
                    <p class="partners-apply-kicker" data-reveal>Lead Bird Partners</p>
                    <h1 id="partners-apply-title" class="partners-apply-title" data-reveal style="--reveal-i: 1">
                        Get your link.<br>
                        Earn <span class="partners-apply-title-accent">10%</span>.
                    </h1>
                    <p class="partners-apply-lead" data-reveal style="--reveal-i: 2">
                        Three details. Instant partner link. Share it, earn when they subscribe.
                    </p>
                    <ul class="partners-apply-perks" data-reveal style="--reveal-i: 3">
                        <?php foreach ($perks as $perk): ?>
                            <li class="partners-apply-perk">
                                <span class="partners-apply-perk-mark" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6 9 17l-5-5"></path>
                                    </svg>
                                </span>
                                <span><?= e($perk) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section class="partners-apply-panel" aria-labelledby="partners-apply-form-title">
                <div class="partners-apply-panel-inner" data-reveal>
                    <h2 id="partners-apply-form-title" class="partners-apply-form-title">Create your partner account</h2>
                    <p class="partners-apply-form-lead">We’ll use this email when you sign in to track earnings.</p>

                    <?php if ($error): ?>
                        <p class="partners-apply-alert" role="alert"><?= e($error) ?></p>
                    <?php endif; ?>

                    <form method="post" class="partners-apply-form" data-partners-apply-form novalidate>
                        <?= csrf_field() ?>
                        <div class="partners-apply-field">
                            <label class="partners-apply-label" for="name">Full name</label>
                            <input
                                id="name"
                                name="name"
                                required
                                class="partners-apply-input"
                                autocomplete="name"
                                value="<?= e($nameValue) ?>"
                            >
                        </div>
                        <div class="partners-apply-field">
                            <label class="partners-apply-label" for="email">Email</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                required
                                class="partners-apply-input"
                                autocomplete="email"
                                value="<?= e($emailValue) ?>"
                            >
                        </div>
                        <div class="partners-apply-field">
                            <label class="partners-apply-label" for="phone">
                                Phone <span class="partners-apply-hint">(for wallet payouts)</span>
                        <a href="<?= e(app_route('partners/dashboard')) ?>">Sign in</a>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                class="partners-apply-input"
                                autocomplete="tel"
                                inputmode="tel"
                                placeholder="080…"
                                value="<?= e($phoneValue) ?>"
                            >
                        </div>
                        <button type="submit" class="partners-apply-submit" data-partners-apply-submit>
                            Create my link
                        </button>
                    </form>

                    <p class="partners-apply-foot">
                        Already a partner?
                        <a href="<?= e(app_route('login')) ?>">Sign in</a>
                        ·
                        <a href="<?= e(app_route('affiliates')) ?>">Program overview</a>
                    </p>
                </div>
            </section>
        </div>
    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<script>
(function () {
    var page = document.querySelector('.partners-page');
    if (page) {
        var els = page.querySelectorAll('[data-reveal]');
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || !('IntersectionObserver' in window)) {
            els.forEach(function (el) { el.classList.add('is-visible'); });
        } else {
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -24px 0px' });
            els.forEach(function (el) { obs.observe(el); });
        }
    }

    var form = document.querySelector('[data-partners-apply-form]');
    var btn = document.querySelector('[data-partners-apply-submit]');
    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.textContent = 'Creating…';
        });
    }
})();
</script>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
