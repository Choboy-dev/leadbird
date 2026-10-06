<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $authMode */
/** @var string $step */
/** @var string $otpEmail */
/** @var string|false $error */
/** @var string|false $success */
/** @var int $otpLength */
/** @var array{code: string, name: string, affiliate_id: string}|null $referral */
/** @var array{id: string, email: string, name?: ?string}|null $authUser */
/** @var bool $referralIsOwnLink */
/** @var bool $authAdminPortal */

require_once __DIR__ . '/helpers.php';

$authMode = auth_normalize_mode($authMode);
$authAdminPortal = !empty($authAdminPortal);
$isSignUp = $authMode === 'signup' && !$authAdminPortal;
$pageTitle = $authAdminPortal ? 'Admin sign in' : ($isSignUp ? 'Sign up' : 'Sign in');
$heading = $authAdminPortal
    ? 'Admin sign in'
    : ($isSignUp ? 'Create your account' : 'Welcome back');
$leadCopy = $authAdminPortal
    ? 'Sign in with your admin account to open the Lead Bird admin portal.'
    : ($isSignUp
    ? 'Start selling in the DMs with your AI salesgirl.'
    : 'Sign in to pick up where you left off.');
$referral = $referral ?? ReferralSelection::pendingReferral();
$sessionAuth = new SessionAuth();
$authUser = $authUser ?? ($sessionAuth->check() ? $sessionAuth->user() : null);
$referralIsOwnLink = $referralIsOwnLink ?? ($referral !== null && ReferralSelection::isOwnLink($referral, $authUser));
$referralBlocksSignup = $isSignUp && $authUser !== null && $referral !== null;
$loginUrl = e(app_route('login'));
$signupUrl = e(app_route('signup'));
$pendingPlan = PlanSelection::pendingSlug();
$authQuery = ReferralSelection::publicQueryParams();
if ($pendingPlan !== null) {
    $authQuery['plan'] = $pendingPlan;
}
if ($authQuery !== []) {
    $signupUrl = e(app_route('signup', $authQuery));
    if (!$isSignUp || $referral === null) {
        $planOnly = $pendingPlan !== null ? ['plan' => $pendingPlan] : [];
        if ($planOnly !== []) {
            $loginUrl = e(app_route('login', $planOnly));
        }
    }
}
$referralShareUrl = $referral !== null
    ? (new AffiliateService())->referralUrl($referral['code'])
    : '';

if ($referralBlocksSignup) {
    if ($referralIsOwnLink) {
        $heading = 'Your referral link';
        $leadCopy = 'Share it with friends. You earn 10% when they pay for their first Lead Bird plan.';
    } else {
        $heading = 'This invite is for someone new';
        $leadCopy = 'You are already signed in. Send this link to a friend who has not joined yet.';
    }
}

$selectedPlan = $pendingPlan !== null ? PlanService::find($pendingPlan) : null;
$homeUrl = e(app_route('', ['clear_plan' => '1']));
$googleUrl = e(app_route('auth/google', array_merge(['mode' => $authMode], ReferralSelection::publicQueryParams())));
$sendOtpUrl = e(app_route('auth/send-otp'));
$verifyOtpUrl = e(app_route('auth/verify-otp'));
$resendOtpUrl = e(app_route('auth/resend-otp'));
$emailStepUrl = $isSignUp ? $signupUrl : $loginUrl;
$verifyHeading = 'Check your email';
$verifyLead = $otpEmail !== ''
    ? 'Enter the verification code sent to ' . $otpEmail
    : 'Enter the verification code we sent to your email.';
$ttlMinutes = app_config()['otp']['ttl_minutes'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Lead Bird</title>
    <?php
    $metaRobots = 'noindex, nofollow';
    require dirname(__DIR__) . '/brand-meta.php';
    ?>
    <?php require dirname(__DIR__) . '/brand-fonts.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Google Sans Flex"', '"Google Sans"', 'sans-serif'],
                        heading: ['"Arial Black"', 'Impact', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            blue: 'var(--brand-blue)',
                            'blue-dark': 'var(--brand-blue-dark)',
                            lime: 'var(--brand-lime)',
                            'lime-hover': 'var(--brand-lime-hover)',
                            red: 'var(--brand-red)',
                            surface: 'var(--brand-surface)',
                            instagram: {
                                purple: 'var(--brand-instagram-purple)',
                                red: 'var(--brand-instagram-red)',
                                orange: 'var(--brand-instagram-orange)',
                            },
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/auth.css?v=3">
</head>
<body class="font-sans antialiased auth-body">
<a href="<?= $homeUrl ?>" class="auth-btn auth-btn--primary auth-btn--back">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m12 19-7-7 7-7"></path>
        <path d="M19 12H5"></path>
    </svg>
    Back to home
</a>

<div class="auth-page min-h-screen bg-brand-blue relative flex flex-col items-center justify-center px-4 py-10 overflow-hidden">
    <div class="brand-grid-bg absolute inset-0 pointer-events-none"></div>

    <?php require __DIR__ . '/float-cards.php'; ?>

    <div class="auth-logo relative z-10 mb-8">
        <?php $logoSize = 'lg'; require dirname(__DIR__) . '/brand-logo.php'; ?>
    </div>

    <div class="auth-card relative z-10 w-full max-w-md rounded-2xl bg-white p-6 md:p-8 shadow-2xl shadow-brand-blue-dark/30">
        <div class="mb-6 text-center">
            <?php if ($step === 'verify'): ?>
                <h1 class="font-heading text-2xl font-black text-black md:text-3xl"><?= e($verifyHeading) ?></h1>
                <p class="mt-2 text-sm text-neutral-600 md:text-base"><?= e($verifyLead) ?></p>
            <?php else: ?>
                <h1 class="font-heading text-2xl font-black text-black md:text-3xl"><?= e($heading) ?></h1>
                <p class="mt-2 text-sm text-neutral-600 md:text-base"><?= e($leadCopy) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="auth-alert auth-alert--error mb-4" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="auth-alert auth-alert--success mb-4" role="status"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($isSignUp && $referral !== null && $step !== 'verify' && !$referralBlocksSignup): ?>
            <?php if ($authUser !== null): ?>
                <div class="auth-alert mb-4 border-2 border-black bg-brand-surface text-sm text-black" role="status">
                    You are already signed in. This link is for new accounts — send it to someone who has not joined Lead Bird yet.
                </div>
            <?php else: ?>
                <div class="auth-referral-note" role="status">
                    <span class="auth-referral-note-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 11v6"/><path d="M19 14h6"/>
                        </svg>
                    </span>
                    <p class="auth-referral-note-copy">
                        Referred by <strong><?= e($referral['name']) ?></strong>. Create your account below to get started.
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($selectedPlan !== null && $step !== 'verify'): ?>
            <div class="auth-alert mb-4 border-2 border-black bg-brand-lime/30 text-sm text-black" role="status">
                You selected the <strong><?= e((string) $selectedPlan['name']) ?></strong> plan (<?= e(money_format((float) $selectedPlan['price'])) ?>/mo).
                Create an account to continue to payment.
            </div>
        <?php endif; ?>

        <?php if ($step !== 'verify' && !$referralBlocksSignup): ?>
            <a class="auth-btn auth-btn--google" href="<?= $googleUrl ?>">
                <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"></path>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"></path>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"></path>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"></path>
                </svg>
                Continue with Google
            </a>

            <div class="auth-divider my-6"><span>or email</span></div>

            <form class="auth-form" method="post" action="<?= $sendOtpUrl ?>">
                <input type="hidden" name="auth_mode" value="<?= e($authMode) ?>">
                <label class="auth-label" for="email">Email</label>
                <input
                    id="email"
                    class="auth-input"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    placeholder="you@company.com"
                    value="<?= e($otpEmail) ?>"
                >
                <button class="auth-btn auth-btn--primary" type="submit">
                    <?= $isSignUp ? 'Send sign-up code' : 'Send sign-in code' ?>
                </button>
            </form>

            <p class="auth-foot mt-4 text-center text-sm text-neutral-600">
                <?php if ($authAdminPortal): ?>
                    <a class="font-semibold text-brand-blue hover:text-brand-blue-dark" href="<?= e(app_route('login')) ?>">Back to user sign in</a>
                <?php elseif ($isSignUp): ?>
                    Already have an account? <a class="font-semibold text-brand-blue hover:text-brand-blue-dark" href="<?= $loginUrl ?>">Sign in</a>
                <?php else: ?>
                    New here? <a class="font-semibold text-brand-blue hover:text-brand-blue-dark" href="<?= $signupUrl ?>">Create an account</a>
                <?php endif; ?>
            </p>
        <?php elseif ($referralBlocksSignup): ?>
            <div class="auth-referral-panel">
                <?php if ($referralIsOwnLink && $referralShareUrl !== ''): ?>
                    <div class="auth-referral-link-wrap">
                        <label class="auth-referral-link-label" for="auth-referral-link">Share link</label>
                        <div class="auth-referral-link-row">
                            <input
                                id="auth-referral-link"
                                type="text"
                                readonly
                                class="auth-referral-link-input"
                                value="<?= e($referralShareUrl) ?>"
                            >
                            <button type="button" class="auth-referral-link-copy" data-auth-referral-copy>Copy</button>
                        </div>
                    </div>
                    <ul class="auth-referral-steps" aria-label="How referrals work">
                        <li>Send your link to a friend</li>
                        <li>They sign up and choose a plan</li>
                        <li>You earn 10% on their first payment</li>
                    </ul>
                <?php else: ?>
                    <div class="auth-referral-link-wrap">
                        <label class="auth-referral-link-label" for="auth-referral-link">Invite link</label>
                        <div class="auth-referral-link-row">
                            <input
                                id="auth-referral-link"
                                type="text"
                                readonly
                                class="auth-referral-link-input"
                                value="<?= e($referralShareUrl) ?>"
                            >
                            <button type="button" class="auth-referral-link-copy" data-auth-referral-copy>Copy</button>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="auth-referral-actions">
                    <?php if ($referralIsOwnLink): ?>
                        <a class="auth-btn auth-btn--referral-primary" href="<?= e(app_route('refer')) ?>">Open Refer &amp; Earn</a>
                        <a class="auth-btn auth-btn--referral-secondary" href="<?= e(app_route('dashboard')) ?>">Go to dashboard</a>
                    <?php else: ?>
                        <a class="auth-btn auth-btn--referral-primary" href="<?= e(app_route('dashboard')) ?>">Go to dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <form class="auth-form auth-form--verify" method="post" action="<?= $verifyOtpUrl ?>" data-otp-form>
                <input type="hidden" name="auth_mode" value="<?= e($authMode) ?>">
                <input type="hidden" name="email" value="<?= e($otpEmail) ?>">
                <input type="hidden" name="code" id="auth-code" value="">

                <div class="auth-otp-grid" data-otp-grid aria-label="Verification code">
                    <?php for ($i = 0; $i < $otpLength; $i++): ?>
                        <input
                            type="text"
                            class="auth-otp-cell"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="1"
                            autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
                            aria-label="Digit <?= $i + 1 ?> of <?= (int) $otpLength ?>"
                            data-otp-cell
                            <?= $i === 0 ? 'autofocus' : '' ?>
                        >
                    <?php endfor; ?>
                </div>

                <p class="auth-otp-hint">This code expires in <?= (int) $ttlMinutes ?> minutes.</p>

                <button class="auth-btn auth-btn--primary" type="submit">
                    <?= $isSignUp ? 'Create account' : 'Sign in' ?>
                </button>
            </form>

            <form class="auth-resend-form" method="post" action="<?= $resendOtpUrl ?>">
                <input type="hidden" name="auth_mode" value="<?= e($authMode) ?>">
                <input type="hidden" name="email" value="<?= e($otpEmail) ?>">
                <button type="submit" class="auth-resend-btn">Resend code</button>
            </form>

            <p class="auth-foot mt-4 text-center text-sm text-neutral-600">
                <a class="font-semibold text-brand-blue hover:text-brand-blue-dark" href="<?= e($emailStepUrl) ?>">Use a different email</a>
            </p>
        <?php endif; ?>
    </div>
</div>
<script src="<?= e($appUrl) ?>/assets/js/auth.js?v=2"></script>
<?php require dirname(__DIR__) . '/site-assistant/embed.php'; ?>
</body>
</html>
