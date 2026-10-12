<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$affiliate = $code !== '' ? RepositoryFactory::affiliates()->findByCode($code) : null;
if ($affiliate !== null && (string) ($affiliate['id'] ?? '') !== (string) ($_SESSION['affiliate_id'] ?? '')) {
    $affiliate = null;
}
$appUrl = app_config()['app_url'];
$pageTitle = 'Your partner link — Lead Bird';
$link = $affiliate ? (new AffiliateService())->referralUrl((string) $affiliate['code']) : app_route('affiliates');

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<main class="bg-brand-surface min-h-screen font-sans">
    <div class="max-w-xl mx-auto px-4 py-14">
        <p class="text-brand-lime font-heading"><?= $affiliate ? 'YOU ARE IN' : 'PARTNERS' ?></p>
        <h1 class="font-heading text-3xl text-brand-blue mt-2">Your referral link</h1>
        <?php if ($affiliate): ?>
            <div class="mt-6 rounded-2xl bg-brand-white border border-black/5 p-6">
                <p class="text-sm text-zinc-500">Share this anywhere</p>
                <p class="mt-2 font-mono text-sm break-all text-brand-blue" id="partner-link"><?= e($link) ?></p>
                <button type="button" class="mt-4 rounded-full bg-brand-lime text-brand-black font-semibold px-5 py-2.5" data-copy-link data-link="<?= e($link) ?>">
                    Copy link
                </button>
            </div>
            <p class="mt-6 text-zinc-600">
                Sign in with <strong><?= e((string) $affiliate['email']) ?></strong> to track earnings and withdraw in the dashboard.
            </p>
            <a href="<?= e(app_route('login')) ?>" class="inline-flex mt-4 text-brand-blue font-semibold">Sign in →</a>
        <?php else: ?>
            <p class="mt-4">Partner account not found. <a class="text-brand-blue underline" href="<?= e(app_route('affiliates/apply')) ?>">Apply again</a>.</p>
        <?php endif; ?>
    </div>
</main>
<script>
document.querySelector('[data-copy-link]')?.addEventListener('click', function () {
    var link = this.getAttribute('data-link') || '';
    var btn = this;
    navigator.clipboard.writeText(link).then(function () {
        btn.textContent = 'Copied';
        setTimeout(function () { btn.textContent = 'Copy link'; }, 1600);
    });
});
</script>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
