<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = $brandName . ' for Support | Make WhatsApp Your #1 Support Channel';
$metaDescription = 'Answer customers on WhatsApp, Instagram and Messenger around the clock. Hannah, the ' . $brandName . ' AI salesgirl, answers routine questions and alerts you when a person is needed.';
$metaUrl = app_route('solutions/support');
$extraStylesheets = ['solution.css?v=8', 'solution-support.css?v=3'];
$activeNav = null;

$signupUrl = app_route('signup');
$markSrc = rtrim($appUrl, '/') . '/assets/images/leadbird-favicon.png';
$mark = '<img class="brand-mark" src="' . e($markSrc) . '" alt="" width="32" height="32">';
$hannahUrl = rtrim($appUrl, '/') . '/#journey';

$tick = '<svg viewBox="0 0 32 32" aria-hidden="true"><rect x="5" y="5" width="24" height="24" rx="5" class="t-ink"/><rect x="2" y="2" width="24" height="24" rx="5" class="t-white"/><path d="M8 14l4 4 8-8"/></svg>';

$phone = static function (array $items, string $class = ''): string {
    $html = '<div class="ph ' . $class . '" aria-hidden="true"><div class="ph-top"><i class="ph-back"></i><i class="ph-ava"></i><i class="ph-name"></i><i class="ph-tick"></i><i class="ph-ic"></i></div><div class="ph-body">';
    foreach ($items as $item) {
        $html .= '<div class="ph-msg ' . $item[0] . '"><div class="ph-b">' . $item[1] . '</div>';
        foreach ($item[2] ?? [] as $button) {
            $html .= '<span class="ph-btn">' . $button . '</span>';
        }
        $html .= '</div>';
    }

    return $html . '</div><div class="ph-bar"><i>+</i><span></span><i class="ph-cam"></i></div></div>';
};

$sources = [
    ['Social media', 'Free WhatsApp chat links', '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1"/>'],
    ['Website', 'Free WhatsApp chat button', '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>'],
    ['Click-to-WhatsApp ads', 'Chats straight from ads', '<path d="M9 11V5.5a1.5 1.5 0 0 1 3 0V11m0-1.5a1.5 1.5 0 0 1 3 0V11m0-.5a1.5 1.5 0 0 1 3 0V15a6 6 0 0 1-6 6h-.5a6 6 0 0 1-4.7-2.3L5 16.2a1.5 1.5 0 0 1 2.3-1.9L9 16"/>'],
    ['Packaging / flyers', 'Free WhatsApp QR codes', '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 20h1M20 14h1"/>'],
];

$balance = [
    'Answering product, price and delivery questions instantly',
    'Sending your payment details when a buyer is ready',
    'Alerting you on WhatsApp when a buyer needs a person',
    'Letting you step into any chat and reply yourself',
];

$salesSteps = [
    'Hannah answers questions about your products and prices',
    'She shares items from your catalog with photos',
    'Ready buyers get your payment details right away',
];
$supportSteps = [
    'Hannah answers delivery and return questions from what you teach her',
    'Complaints and tricky chats trigger a WhatsApp alert to you',
    'You step in and reply from the same inbox',
];

$points = [
    'See every customer conversation from WhatsApp, Facebook Messenger and Instagram in one inbox.',
    'Stay organised with labels and channel filters, so no question is left waiting.',
    'Read what Hannah said in every chat, and jump in to reply yourself whenever you like.',
];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol sup flex-1">

        <section class="sol-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <span class="sol-shout"><?= e($brandName) ?> for Support</span>
                    <h1>Make WhatsApp your #1 support channel, with AI that scales with you</h1>
                    <p class="sol-p">Hannah, the <?= e($brandName) ?> AI salesgirl, takes care of the questions you answer every day, so you can give the human touch where it matters.</p>
                    <div class="sol-btns">
                        <a class="sol-btn" href="<?= e($signupUrl) ?>">Get started</a>
                    </div>
                </div>
                <div class="sup-hero-img">
                    <svg viewBox="0 0 1013 759" fill="none" role="img" aria-label="A seller answering WhatsApp chats alongside Hannah, the AI salesgirl">
                        <defs>
                            <g id="supCloud"><rect x="40" y="560" width="760" height="190" rx="95"/><rect x="150" y="470" width="420" height="200" rx="100"/><rect x="560" y="150" width="420" height="600" rx="140"/><rect x="700" y="95" width="200" height="140" rx="70"/><rect x="720" y="420" width="270" height="250" rx="120"/></g>
                            <clipPath id="supBot"><circle cx="602" cy="128" r="114"/></clipPath>
                        </defs>
                        <use href="#supCloud" class="h-cloud-s" transform="translate(12 10)"/>
                        <use href="#supCloud" class="h-cloud"/>
                        <circle cx="895" cy="118" r="22" class="h-cloud h-cloud-line" stroke-width="6"/>
                        <rect x="205" y="112" width="420" height="636" rx="70" class="h-surface h-ink-line" stroke-width="10"/>
                        <circle cx="352" cy="146" r="10" class="h-ink"/>
                        <line x1="384" y1="146" x2="452" y2="146" class="h-ink-line" stroke-width="12" stroke-linecap="round"/>
                        <path d="M440 302h210l8 92-18-10h-200z" class="h-blue"/>
                        <path d="M466 328h158M466 346h158M480 364h112" class="h-white-line" stroke-width="4" stroke-linecap="round"/>
                        <path d="M208 525h210l4 98h-196l-14 12z" class="h-lime"/>
                        <path d="M232 553h162M232 573h162M290 595h104" class="h-ink-line" stroke-width="4" stroke-linecap="round"/>
                        <rect x="84" y="294" width="262" height="205" rx="22" class="h-ink"/>
                        <rect x="72" y="282" width="262" height="205" rx="22" class="h-wa h-ink-line" stroke-width="5"/>
                        <g transform="translate(171 318) scale(2.7)" class="h-white"><?= preg_replace('~</?svg[^>]*>~', '', sol_icon('whatsapp')) ?></g>
                        <text x="203" y="442" text-anchor="middle" class="h-white h-text">WhatsApp</text>
                        <circle cx="602" cy="128" r="118" class="h-white h-ink-line" stroke-width="7"/>
                        <g clip-path="url(#supBot)">
                            <circle cx="602" cy="128" r="114" class="h-lime-soft"/>
                            <image href="<?= e($markSrc) ?>" x="532" y="58" width="140" height="140"/>
                        </g>
                        <image href="<?= e(rtrim($appUrl, '/') . '/assets/images/solutions/support-seller.webp') ?>" x="505" y="232" width="460" height="528" preserveAspectRatio="xMidYMax meet"/>
                    </svg>
                </div>
            </div>
        </section>

        <?php
        $clientsHeading = 'Sellers <strong>trust ' . e($brandName) . '</strong> to answer every customer without hiring more hands';
        $toolSections = ['clients'];
        require dirname(__DIR__) . '/includes/tools/sections-bottom.php';
        ?>

        <section class="sup-box-sec">
            <div class="lk-wrap">
                <div class="sup-box">
                    <h2 class="sol-h2 center">Make WhatsApp your go-to support channel and stay connected with customers 24/7</h2>
                    <p class="sup-sub center">Bring customers from every channel into WhatsApp and answer them all, without adding staff.</p>

                    <div class="qmap">
                        <svg class="qmap-lines" viewBox="0 0 1000 400" preserveAspectRatio="none" aria-hidden="true">
                            <g>
                                <path d="M210 18C320 18 300 175 395 175"/>
                                <path d="M210 133C300 133 320 190 395 190"/>
                                <path d="M210 247C300 247 320 210 395 210"/>
                                <path d="M210 362C320 362 300 225 395 225"/>
                                <path d="M565 175C660 175 640 18 745 18"/>
                                <path d="M565 225C660 225 640 348 745 348"/>
                            </g>
                        </svg>

                        <div class="qmap-col">
                            <?php foreach ($sources as [$label, $note, $svg]): ?>
                                <div class="qmap-src">
                                    <span class="qmap-node"><?= e($label) ?><svg viewBox="0 0 24 24" aria-hidden="true"><?= $svg ?></svg></span>
                                    <small><?= e($note) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="qmap-hub">
                            <span>Customer questions on</span>
                            <b><?= sol_icon('whatsapp') ?>WhatsApp</b>
                        </div>

                        <div class="qmap-col right">
                            <div class="qmap-src">
                                <span class="qmap-node ai">Hannah, your AI salesgirl<svg class="qmap-spark" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2 6.5L20.5 11 14 13l-2 7-2-7-6.5-2L10 8.5zM19.5 1.5l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/></svg></span>
                                <small>Answers common questions<br>&amp; sends payment details</small>
                            </div>
                            <div class="qmap-stat"><b>24/7</b><span>instant answers to<br>common questions</span></div>
                            <div class="qmap-src">
                                <span class="qmap-node center">Owner alerts</span>
                                <small>Hands tricky chats<br>to you on WhatsApp</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad sup-balance">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Balancing AI with the human touch</h2>
                <div class="sup-balance-grid">
                    <div>
                        <p>AI should make you faster, not replace the person your customers want to talk to.</p>
                        <p>Hannah learns <strong>your products, prices, delivery rules and tone of voice</strong>, so her replies still sound like your shop.</p>
                    </div>
                    <div>
                        <p>Hannah <strong>balances automation and the human touch by:</strong></p>
                        <ul>
                            <?php foreach ($balance as $line): ?>
                                <li><?= e($line) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="center">
                    <a class="sol-btn-line sup-learn" href="<?= e($hannahUrl) ?>">Learn more about Hannah, your AI salesgirl</a>
                </div>
            </div>
        </section>

        <section class="sup-box-sec">
            <div class="lk-wrap">
                <div class="sup-box">
                    <h2 class="sol-h2 center">Get more done with Hannah answering sales and support questions</h2>

                    <div class="qtree">
                        <div class="qtree-root">Customer Questions</div>
                        <svg class="qtree-lines" viewBox="0 0 1000 120" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M500 0v30M500 30q0 14-14 14H390v40M500 30q0 14 14 14h96v40M390 84H155v36M610 84h235v36"/>
                        </svg>

                        <div class="qtree-grid">
                            <div class="qtree-col">
                                <?php foreach ($salesSteps as $step): ?>
                                    <div class="qtree-box"><?= e($step) ?></div>
                                <?php endforeach; ?>
                            </div>

                            <div class="qtree-branch">
                                <div class="qtree-tag">Sales<br>questions</div>
                                <?= $phone([
                                    ['in', '<span>Hi, do you have the Shea Glow body butter in the big size?</span>'],
                                    ['out', '<span>Yes, we do! The 500g jar is ₦9,500. Would you like our account details to order?</span>', ['Yes, send them']],
                                ], 'qtree-phone') ?>
                            </div>

                            <div class="qtree-branch">
                                <div class="qtree-tag">Support<br>questions</div>
                                <?= $phone([
                                    ['in', '<span>My order hasn’t arrived yet. I paid on Monday.</span>'],
                                    ['out', '<span>Sorry about the wait! Lagos orders arrive within 1 to 2 working days. Was this helpful?</span>', ['Yes', 'No, I’d like to talk to the owner']],
                                ], 'qtree-phone') ?>
                            </div>

                            <div class="qtree-col">
                                <?php foreach ($supportSteps as $step): ?>
                                    <div class="qtree-box"><?= e($step) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <h2 class="sol-h2 center w-900">One inbox to manage WhatsApp, Facebook &amp; Instagram conversations</h2>
                <div class="sol-split sup-inbox">
                    <div>
                        <?php foreach ($points as $point): ?>
                            <div class="sup-point"><?= $tick ?><p><?= e($point) ?></p></div>
                        <?php endforeach; ?>
                    </div>

                    <div class="ibx" aria-hidden="true">
                        <div class="ibx-app">
                            <div class="ibx-head"><span class="ibx-logo"><?= $mark ?><b><?= e($brandName) ?></b></span><i class="ibx-user"></i></div>
                            <div class="ibx-chat">
                                <span class="ibx-bub g w60"></span>
                                <span class="ibx-bub w55"></span>
                                <span class="ibx-bub g w80"></span>
                                <span class="ibx-bub w12"></span>
                            </div>
                        </div>

                        <div class="ibx-tabs ibx-pop">
                            <b>All</b>
                            <span><?= sol_icon('whatsapp') ?>WhatsApp</span>
                            <span><?= sol_icon('instagram') ?>Instagram</span>
                            <span><?= sol_icon('messenger') ?>Messenger</span>
                        </div>

                        <div class="ibx-assign ibx-pop">
                            <small>Labels</small>
                            <div><em>Order</em><b>Paid</b></div>
                            <div><em>Stage</em><b>Delivery</b></div>
                        </div>

                        <div class="ibx-compose ibx-pop">
                            <i></i><i class="ic-bolt"></i><i class="ic-clip"></i><i class="ic-emoji"></i>
                            <span>Send</span>
                        </div>

                        <div class="ibx-list ibx-pop">
                            <?php foreach (['green' => 'whatsapp', 'purple' => 'instagram', 'blue' => 'messenger'] as $tone => $icon): ?>
                                <div class="ibx-row <?= $tone ?>">
                                    <span class="ibx-ava"><?= sol_icon($icon) ?></span>
                                    <span class="ibx-lines"><i></i><i></i><i></i></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="ibx-hours ibx-pop">
                            <b>Hannah settings</b>
                            <div class="ibx-hrow">AI replies<span><i class="ibx-tg on"></i>On</span></div>
                            <small>What Hannah does for you</small>
                            <div class="ibx-hrow">Owner alerts<span><i class="ibx-tg on"></i>On</span></div>
                            <div class="ibx-hrow">Follow-ups<span><i class="ibx-tg on"></i>On</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <div class="sol-cta sup-cta">
                    <h3><strong>Ready to meet today’s customers on the messaging apps they already use?</strong></h3>
                    <a class="sol-btn blue" href="<?= e($signupUrl) ?>">Get started</a>
                </div>
            </div>
        </section>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
