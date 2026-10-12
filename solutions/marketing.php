<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';
require_once dirname(__DIR__) . '/config/plans.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = $brandName . ' for Marketing | Turn Posts, Ads and DMs into Sales';
$metaDescription = 'Plan and publish posts with AI, turn ad clicks and DMs into sales chats, and keep customers coming back. ' . $brandName . ' runs your social selling on WhatsApp, Instagram, Messenger and TikTok.';
$metaUrl = app_route('solutions/marketing');
$extraStylesheets = ['solution.css?v=8', 'solution-marketing.css?v=19'];
$activeNav = null;

$signupUrl = app_route('signup');
$contactUrl = app_route('contact');
$markSrc = rtrim($appUrl, '/') . '/assets/images/leadbird-favicon.png';
$mark = '<img class="brand-mark" src="' . e($markSrc) . '" alt="" width="32" height="32">';
$ratingsBase = rtrim($appUrl, '/') . '/assets/images/ratings/';

$svgs = [
    'dress' => '<path class="t-orange" d="M48 14h24l4 14-6 6 18 54H32l18-54-6-6z"/><circle class="t-blue" cx="60" cy="50" r="5"/><circle class="t-blue" cx="50" cy="68" r="5"/><circle class="t-blue" cx="70" cy="70" r="5"/><circle class="t-lime" cx="60" cy="82" r="4"/>',
    'food' => '<ellipse class="t-white" cx="60" cy="64" rx="48" ry="20"/><ellipse class="t-orange" cx="60" cy="56" rx="38" ry="16"/><circle class="t-red" cx="46" cy="52" r="5"/><circle class="t-green" cx="70" cy="50" r="4"/><circle class="t-red" cx="76" cy="60" r="4"/><circle class="t-green" cx="52" cy="62" r="3"/>',
    'jar' => '<rect class="t-ink" x="42" y="20" width="36" height="12" rx="3"/><rect class="t-white" x="36" y="32" width="48" height="52" rx="8"/><rect class="t-lime" x="44" y="48" width="32" height="18" rx="2"/>',
    'sandal' => '<path class="t-orange" d="M16 70c12-2 22-10 28-22l14 4c6 8 20 12 34 12 8 0 12 6 12 12v4H16z"/><path class="t-ink" d="M16 80h88v6H16z"/><path class="t-white" d="M48 54l6 4M53 51l6 4M58 49l5 4" stroke-width="2"/>',
];
$photos = [
    'food' => rtrim($appUrl, '/') . '/assets/images/solutions/jollof-rice-tray.jpg',
    'jar' => rtrim($appUrl, '/') . '/assets/images/solutions/shea-body-butter.jpg',
    'sandal' => rtrim($appUrl, '/') . '/assets/images/solutions/gold-sandals.jpg',
];
$media = static function (string $kind, string $bg) use ($svgs, $photos): string {
    if (isset($photos[$kind])) {
        return '<img class="mk-media mk-photo" src="' . e($photos[$kind]) . '" alt="" loading="lazy">';
    }
    return '<span class="mk-media" style="background:' . $bg . '"><svg viewBox="0 0 120 96" aria-hidden="true">' . $svgs[$kind] . '</svg></span>';
};

$order = static function (string $kind, string $title, string $meta, string $price) use ($media): string {
    return '<span class="ph-order">' . $media($kind, 'var(--s-cream)') . '<span><b>' . $title . '</b><small>' . $meta . '</small></span><b>' . $price . '</b></span>';
};

// Each item: ['in' | 'out', html, buttons] or ['sys', text]. "out" is the seller.
$phone = static function (array $items, string $class = ''): string {
    $html = '<div class="ph ' . $class . '" aria-hidden="true"><div class="ph-top"><i class="ph-back"></i><i class="ph-ava"></i><i class="ph-name"></i><i class="ph-tick"></i><i class="ph-ic"></i></div><div class="ph-body">';
    foreach ($items as $item) {
        if ($item[0] === 'sys') {
            $html .= '<span class="ph-sys">' . $item[1] . '</span>';
            continue;
        }
        $html .= '<div class="ph-msg ' . $item[0] . '"><div class="ph-b">' . $item[1] . '</div>';
        foreach ($item[2] ?? [] as $button) {
            $html .= '<span class="ph-btn">' . $button . '</span>';
        }
        $html .= '</div>';
    }

    return $html . '</div><div class="ph-bar"><i>+</i><span></span><i class="ph-cam"></i></div></div>';
};

$adCard = '<div class="cd-ad"><div class="cd-ad-top"><i></i><span><b>Ada’s Kitchen</b><small>Sponsored</small></span></div>'
    . '<img class="cd-ad-photo" src="' . e(rtrim($appUrl, '/') . '/assets/images/solutions/small-chops-tray.jpg') . '" alt="" width="640" height="634" loading="lazy">'
    . '<small>Party trays &amp; small chops</small><b>Small chops box · ₦18,000</b>'
    . '<span class="cd-send">' . sol_icon('whatsapp') . 'Send Message</span></div>';

$journeys = [
    [
        'tone' => 'blue',
        'tag' => 'ACQUISITION',
        'title' => 'Grow your audience and start more conversations',
        'copy' => 'Post more often, send ad clicks straight into a chat, and put your WhatsApp link everywhere buyers find you.',
        'items' => [
            [
                'AI Post Planner',
                '<div class="acc-side plan"><div class="bc-brand">' . $mark . '<b>' . e($brandName) . '</b></div><b>New post</b>'
                    . '<small>Caption, written by AI</small><span>Fresh Ankara sets just landed 🌺 Sizes 8 to 18, delivery across Lagos. DM to order!</span>'
                    . '<small>Publish to</small><span class="plan-ch">' . sol_icon('instagram') . sol_icon('facebook') . sol_icon('tiktok') . '</span>'
                    . '<small>Schedule</small><span>Saturday, 10:00 AM</span><span class="flow-btn">Schedule post</span></div>',
                $phone([
                    ['in', '<span>Saw your post! Is the blue set available in size 12?</span>'],
                    ['out', '<span>Yes it is! It’s ₦28,000 with free delivery in Lagos. Want me to reserve it for you?</span>', ['Reserve it']],
                    ['in', '<span>Yes please!</span>'],
                ]),
                'Post every week without the stress',
                'Let AI write your captions and hashtags, then schedule and publish to Instagram, Facebook and TikTok from one place. On Vendor and Shop plans.',
            ],
            [
                'Click-to-WhatsApp Ads',
                '<div class="acc-side adc">' . $adCard . '<span class="cd-badge meta">' . sol_icon('meta') . '</span></div>',
                $phone([
                    ['in', '<span>Hi! I saw your ad for the small chops box. Do you deliver to Ikeja?</span>'],
                    ['out', '<span>Hi, welcome to Ada’s Kitchen! Yes, we deliver across Lagos. A 50-piece box is ₦18,000. Want one this weekend?</span>', ['Yes please']],
                ]),
                'Turn ad clicks into sales chats',
                'Point your Facebook and Instagram ads at WhatsApp. Hannah replies in seconds, answers questions and sends your payment details.',
            ],
            [
                'Chat Links & QR Codes',
                '<div class="acc-side flow"><b>Your chat link</b><small>Link</small><span>wa.me/2348012345678</span>'
                    . '<small>Welcome message</small><span>Hi! I’d like to place an order</span>'
                    . '<canvas class="flow-qr" data-qr="https://wa.me/2348012345678" width="80" height="80"></canvas><span class="flow-btn">Copy link</span></div>',
                $phone([
                    ['in', '<span>Hi! I’d like to place an order</span>'],
                    ['out', '<span>Hi! Happy to help. What would you like today?</span>', ['See the menu']],
                ]),
                'Start chats from anywhere you post',
                'Make free WhatsApp links, QR codes and website chat buttons with ' . e($brandName) . '’s tools, then add them to your bio, flyers, packaging and website.',
            ],
            [
                'Instagram to WhatsApp',
                '<div class="acc-side igdm"><div class="igdm-top">' . sol_icon('instagram') . '<b>@adaobi.styles</b></div>'
                    . '<span class="igdm-in">When does the new gown collection drop?</span>'
                    . '<span class="igdm-out">They’re out on Friday! Tap below to continue on WhatsApp and I’ll send you the full collection there 🌺</span>'
                    . '<span class="igdm-in">Okay!</span><span class="igdm-arrow">→</span></div>',
                $phone([
                    ['out', '<img class="wa-photo" src="' . e(rtrim($appUrl, '/') . '/assets/images/solutions/red-gown.jpg') . '" alt="" width="447" height="447" loading="lazy">' . '<span>Hi Amaka 👋 here’s the new collection you asked about.</span>'],
                    ['in', '<span>Love this one! How much?</span>'],
                ]),
                'Move ready buyers to WhatsApp',
                'When an Instagram or Messenger buyer is ready, Hannah invites them to continue on WhatsApp, where it’s easier to share details and get paid. On Vendor and Shop plans.',
            ],
        ],
    ],
    [
        'tone' => 'pink',
        'tag' => 'CONVERSION',
        'title' => 'Let customers buy without leaving the chat',
        'copy' => 'Help shoppers find the right product, answer their questions and take the order, all in one conversation.',
        'items' => [
            [
                'Catalog & Payment Details',
                '',
                $phone([
                    ['out', $media('food', 'var(--s-blue-bg)') . '<span>Welcome to Ada’s Kitchen! Here’s this weekend’s menu.</span>', ['See menu']],
                    ['in', $order('food', 'Jollof rice tray', 'Serves 15', '₦45,000')],
                    ['out', '<span>Great choice! Pay ₦45,000 to Ada’s Kitchen, GTBank 0123456789, then send your receipt here.</span>'],
                ]),
                'Cut checkout friction',
                'Hannah shares products from your catalog with prices and photos, then sends your payment details the moment a buyer is ready.',
            ],
            [
                'Guided Selling',
                '',
                $phone([
                    ['in', '<span>I need a body butter for very dry skin, under ₦15,000.</span>'],
                    ['out', '<span>Here’s one that suits dry skin:</span>'],
                    ['out', $media('jar', 'var(--s-green-bg)') . '<b>Shea Glow Body Butter · ₦9,500</b>', ['Order now']],
                ]),
                'Recommend the right product',
                'Hannah asks a few quick questions and suggests products from your catalog that fit, like your best sales assistant.',
            ],
            [
                'Cold-Lead Follow-ups',
                '',
                $phone([
                    ['in', '<span>Let me think about it and get back to you.</span>'],
                    ['sys', 'Next day'],
                    ['out', '<span>Hi Sara! Still thinking about the gold sandals? Your size 39 is still available.</span>'],
                    ['in', '<span>Okay, I’ll take them!</span>'],
                ]),
                'Bring back buyers who went quiet',
                'Hannah follows up with buyers who stopped replying, on your schedule, with a friendly nudge. On the Shop plan.',
            ],
        ],
    ],
    [
        'tone' => 'green',
        'tag' => 'RETENTION',
        'title' => 'Turn first-time buyers into regulars',
        'copy' => 'Look after customers after they pay, and answer their questions fast so they come back.',
        'items' => [
            [
                'Payment Receipts',
                '',
                $phone([
                    ['in', '<span>I’ve paid ₦45,000. Here’s my transfer receipt.</span>'],
                    ['out', '<span>Thank you, Tolu! We’ve noted your payment ✅ Here’s your receipt:</span>'],
                    ['out', $order('food', 'Jollof rice tray', 'Delivery on Saturday', '₦45,000')],
                ]),
                'Make every buyer feel looked after',
                'Send a receipt message as soon as a buyer says they’ve paid, so they know their order is in safe hands. On the Shop plan.',
            ],
            [
                'Delivery Questions',
                '',
                $phone([
                    ['in', '<span>Do you deliver to Abuja? How long does it take?</span>'],
                    ['out', '<span>Yes! Delivery to Abuja takes 2 to 3 days and costs ₦4,000.</span>'],
                    ['in', '<span>Perfect, thanks!</span>'],
                ]),
                'Answer delivery questions instantly',
                'Teach Hannah your delivery areas, fees and return rules, and she answers the questions you get every day.',
            ],
            [
                'Owner Alerts',
                '',
                $phone([
                    ['in', '<span>My order came in the wrong size 😞</span>'],
                    ['out', '<span>I’m so sorry about that! I’ve told the owner, and she’ll message you shortly to fix it.</span>'],
                    ['sys', 'Owner alerted on WhatsApp'],
                ]),
                'Step in when it matters',
                'Get a WhatsApp alert when a buyer complains or needs a person, so you can step in and keep them happy. On Vendor and Shop plans.',
            ],
            [
                'AI Salesgirl',
                '',
                $phone([
                    ['in', '<span>Do you have this bag in black?</span>'],
                    ['out', '<span>Yes, we do! The black one is ₦22,000. Want me to send a photo?</span>'],
                    ['in', '<span>Yes please</span>'],
                ]),
                'Answer customers day and night',
                'Let Hannah, your AI salesgirl, answer questions around the clock and hand the chat to you when a person is needed.',
            ],
        ],
    ],
];

$useCases = [
    'fashion' => [
        'Fashion & beauty',
        '',
        'Help shoppers find their size, agree on a price and come back for more.',
        [
            ['Help Shoppers Discover', $phone([
                ['in', '<span>I need a body butter for dry skin. Any ideas?</span>'],
                ['out', '<span>Based on your skin type, this is a perfect match:</span>'],
                ['out', $media('jar', 'var(--s-green-bg)') . '<b>Shea Glow Body Butter · ₦9,500</b>', ['Order now']],
            ])],
            ['Close the Sale', $phone([
                ['in', '<span>Last price for the gold sandals?</span>'],
                ['out', $media('sandal', 'var(--s-cream)') . '<span>For you, ₦17,000 instead of ₦18,500 🙌 Shall I send the account details?</span>', ['Yes, send them']],
                ['in', '<span>Deal!</span>'],
            ])],
            ['Win Repeat Orders', $phone([
                ['in', '<span>My shea butter is almost finished. Can I reorder?</span>'],
                ['out', '<span>Perfect timing, Emily! Same size as last time?</span>'],
                ['out', $media('jar', 'var(--s-green-bg)') . '<b>Shea Glow Body Butter · ₦9,500</b>', ['Reorder now']],
            ])],
        ],
    ],
    'food' => [
        'Food & small vendors',
        'pink',
        'Take orders, answer delivery questions and book party orders while you cook.',
        [
            ['Take Orders Faster', $phone([
                ['in', '<span>Two small chops boxes for Saturday, please.</span>'],
                ['out', '<span>Done! 2 × ₦18,000 = ₦36,000. Where should we deliver?</span>'],
                ['in', '<span>Yaba, close to the market.</span>'],
                ['out', '<span>Delivery to Yaba is ₦2,500. I’ll send our account details now.</span>'],
            ])],
            ['Answer Delivery Questions', $phone([
                ['in', '<span>What time do you deliver on Sundays?</span>'],
                ['out', '<span>We deliver from 10am to 6pm on Sundays. Delivery to Yaba is ₦2,500.</span>'],
                ['in', '<span>Great, thank you!</span>'],
            ])],
            ['Book Party Orders', $phone([
                ['in', '<span>Can you cater a party for 100 guests on 12 December?</span>'],
                ['out', '<span>We’d love to! Let’s book a quick call to plan your menu. Which time works for you?</span>', ['Tomorrow, 11am', 'Friday, 3pm']],
                ['in', '<span>Tomorrow, 11am</span>'],
            ])],
        ],
    ],
];

$socialIcons = ['instagram', 'facebook', 'tiktok', 'youtube', 'x', 'messenger', 'whatsapp'];
$tick = '<svg viewBox="0 0 40 40" aria-hidden="true"><rect x="5" y="7" width="30" height="30" rx="6" class="t-ink"/><rect x="3" y="4" width="30" height="30" rx="6" class="t-lime" stroke-width="2.5"/><path d="M10 19l6 6 11-12" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';

$planCopy = [
    'starter' => ['Key features', [
        ['Replies day and night:', 'Answers questions about your products and prices, even while you sleep'],
        ['Payment details:', 'Sends your account details to buyers who are ready to pay'],
        ['Your own voice:', 'Answers in your tone, using what you teach her about your shop'],
        ['Top up any time:', 'Buy more tokens whenever you need them'],
    ], ['1 Facebook Page, 1 Instagram account and 1 WhatsApp number']],
    'vendor' => ['Everything in Starter, plus:', [
        ['Price talks:', 'Talks with customers until you both agree on a price'],
        ['WhatsApp handover:', 'Moves ready buyers over to WhatsApp'],
        ['Post writing:', 'Writes your social media posts for you'],
        ['Scheduling:', 'Plans your posts and publishes them with one click'],
        ['Bulk WhatsApp Messaging:', 'Message up to 5,000 contacts at once'],
    ], ['Facebook, Instagram and WhatsApp for each business']],
    'shop' => ['Everything in Vendor, plus:', [
        ['Follow-ups:', 'Follows up when a customer stops replying'],
        ['Receipts:', 'Sends a receipt after someone pays'],
        ['No account limits:', 'Connect as many Facebook, Instagram and WhatsApp accounts as you want'],
        ['Bulk WhatsApp Messaging:', 'Message up to 50,000 contacts at once'],
    ], ['As many channels as you need for each business']],
];
$planTones = ['starter' => 'blue', 'vendor' => 'green', 'shop' => 'pink'];
$money = static function (float $amount): string {
    return '$' . number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
};
$maxSaving = 0;
foreach (plans_list() as $plan) {
    $year = (float) $plan['price'] * 12;
    $maxSaving = max($maxSaving, (int) round(100 * ($year - (float) $plan['annual_price']) / $year));
}

$ratings = [
    ['4.7', 94, 'rating-producthunt.svg', 'Product Hunt', '50+'],
    ['4.6', 92, null, 'Capterra', '200+'],
    ['4.6', 92, 'rating-g2.svg', 'G2', '450+'],
    ['4.1', 82, 'rating-shopify.svg', 'Shopify', '125+'],
    ['4.7', 94, 'rating-hubspot.svg', 'HubSpot', '70+'],
];
$awards = [
    ['g2-leader-chatbots.svg', 'G2 Leader, Chatbots'],
    ['g2-momentum-leader.svg', 'G2 Momentum Leader, Chatbots'],
    ['g2-most-implementable.svg', 'G2 Most Implementable, WhatsApp Marketing'],
    ['g2-leader-mid-market.svg', 'G2 Leader Mid-Market, Chatbots'],
];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol smk flex-1">

        <section class="sol-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <span class="sol-shout"><?= e($brandName) ?> for Marketing</span>
                    <h1>Grow sales with marketing that talks back</h1>
                    <p class="sol-p">Plan and publish posts with AI, turn ad clicks and DMs into sales chats, and keep customers coming back. <?= e($brandName) ?> answers every buyer on WhatsApp, Instagram, Messenger and TikTok.</p>
                    <div class="sol-btns">
                        <a class="sol-btn" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="#pricing">See pricing</a>
                    </div>
                </div>
                <div class="hv" aria-hidden="true">
                    <div class="hv-circle"></div>
                    <div class="hv-board"></div>
                    <?php foreach ([
                        ['blue', 'instagram', 'Views', '+2,480', 'M0 10 C30 10 50 40 80 44 S120 34 150 48'],
                        ['pink', 'facebook', 'Reach', '+1,130', 'M0 50 C40 52 60 50 80 44 S120 14 150 12'],
                        ['green', 'whatsapp', 'Chats', '+124', 'M0 50 C30 46 60 40 80 42 S120 10 150 8'],
                    ] as $i => [$tone, $icon, $label, $value, $line]): ?>
                        <div class="hv-card hv-card-<?= $i ?> <?= $tone ?>">
                            <div class="hv-card-head"><?= sol_icon($icon) ?><b><?= e($label) ?></b><span><?= e($value) ?></span></div>
                            <svg viewBox="0 0 150 56" preserveAspectRatio="none"><path class="fill" d="<?= $line ?> V56 H0z"/><path class="line" d="<?= $line ?>"/></svg>
                        </div>
                    <?php endforeach; ?>
                    <div class="hv-phone"><?= $phone([
                        ['in', '<span>Is the Ankara two-piece still available in size 12?</span>'],
                        ['out', '<span>Yes! It’s back in stock 🙌 ₦28,000 with free delivery in Lagos.</span>', ['Send payment details']],
                        ['in', '<span>Yay! Ordering now</span>'],
                    ]) ?></div>
                    <span class="hv-app wa"><?= sol_icon('whatsapp') ?></span>
                    <span class="hv-app ig"><?= sol_icon('instagram') ?></span>
                    <span class="hv-app gg"><?= sol_icon('tiktok') ?></span>
                </div>
            </div>
        </section>

        <?php
        $clientsHeading = 'Sellers in <span id="industryText" data-words="' . e(json_encode(['fashion', 'food', 'beauty', 'gadgets', 'events'])) . '">fashion</span><br>use ' . e($brandName) . ' to turn their DMs into a sales channel';
        $toolSections = ['clients'];
        require dirname(__DIR__) . '/includes/tools/sections-bottom.php';
        ?>

        <section class="sol-pad">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Bring shoppers from every channel into one<br>conversation</h2>
                <p class="sol-p center w-900">Ads, QR codes, social profiles and your website all lead to the same place: a chat where Hannah answers straight away and turns interest into a sale.</p>

                <div class="cd" aria-hidden="true">
                    <div class="cd-box">
                        <div class="cd-head">SOCIAL MEDIA</div>
                        <div class="cd-body cd-social">
                            <?php foreach ($socialIcons as $i => $key): ?>
                                <span class="cd-ic" style="--a:<?= round($i * 360 / count($socialIcons), 2) ?>deg"><?= sol_icon($key) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="cd-box">
                        <div class="cd-head">PRINT &amp;<br>PACKAGING</div>
                        <div class="cd-body">
                            <canvas class="cd-qr" data-qr="https://wa.me/2348012345678" width="110" height="110"></canvas>
                            <span class="cd-qr-wa"><?= sol_icon('whatsapp') ?></span>
                        </div>
                    </div>
                    <div class="cd-box tall">
                        <div class="cd-head">ADS ON FACEBOOK<br>&amp; INSTAGRAM</div>
                        <div class="cd-body">
                            <?= $adCard ?>
                            <span class="cd-badge meta"><?= sol_icon('meta') ?></span>
                            <span class="cd-badge google"><?= sol_icon('instagram') ?></span>
                        </div>
                    </div>
                    <div class="cd-box">
                        <div class="cd-head">YOUR<br>WEBSITE</div>
                        <div class="cd-body cd-web">
                            <div class="cd-chat">
                            <span class="cd-pill">Chat with us</span><span class="cd-x">×</span>
                            <span class="cd-bubble"><svg viewBox="0 0 24 24"><path d="M4 5h16v11H9l-5 4z" fill="none" stroke-width="2" stroke-linejoin="round"/></svg></span>
                            </div>
                        </div>
                    </div>
                    <svg class="cd-lines" viewBox="0 0 1000 220" preserveAspectRatio="none">
                        <path d="M120 0 C140 120 420 120 480 210"/>
                        <path d="M370 0 C380 110 460 130 492 210"/>
                        <path d="M630 0 C600 110 540 140 508 210"/>
                        <path d="M880 0 C860 120 580 120 520 210"/>
                    </svg>
                    <div class="cd-logo"><?= $mark ?><span><?= e($brandName) ?></span></div>
                </div>

                <div class="sol-ticks">
                    <?php foreach (['Every chat answered <br>in seconds', 'Real, two-way <br>conversations', 'Free link, QR code and <br>chat button tools'] as $label): ?>
                        <div class="sol-tick"><?= $tick ?><p><?= $label ?></p></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad sol-yellow">
            <div class="lk-wrap sol-facts">
                <h3>Your customers are already chatting, every day, on…</h3>
                <div><span class="sol-fact-n">2B+</span><p>WhatsApp monthly users</p></div>
                <div><span class="sol-fact-n">2B+</span><p>Instagram monthly users</p></div>
                <div><span class="sol-fact-n">1B+</span><p>TikTok monthly users</p></div>
            </div>
        </section>

        <?php foreach ($journeys as $j => $journey): ?>
            <section class="acc <?= $journey['tone'] ?>" data-acc>
                <div class="lk-wrap sol-split">
                    <div>
                        <span class="acc-tag"><?= e($journey['tag']) ?></span>
                        <h2 class="sol-h2"><?= e($journey['title']) ?></h2>
                        <p class="sol-p"><?= e($journey['copy']) ?></p>
                        <div class="acc-list" role="tablist">
                            <?php foreach ($journey['items'] as $i => [$label]): ?>
                                <button type="button" role="tab" class="acc-item<?= $i === 0 ? ' open' : '' ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= e($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="acc-card">
                        <div class="acc-visuals">
                            <?php foreach ($journey['items'] as $i => [, $side, $ph]): ?>
                                <div class="acc-visual<?= $i === 0 ? ' on' : '' ?><?= $side !== '' ? ' has-side' : '' ?>"><?= $side ?><?= $ph ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="acc-copy">
                            <?php foreach ($journey['items'] as $i => [, , , $h4, $p]): ?>
                                <div class="acc-text<?= $i === 0 ? ' on' : '' ?>"><h4><?= e($h4) ?></h4><p><?= $p ?></p></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php if ($j === 0): ?>
                <section class="sol-pad">
                    <div class="lk-wrap">
                        <div class="sol-partner">
                            <span class="sol-partner-tag">OFFICIAL META APIS</span>
                            <div class="sol-partner-row">
                                <?= sol_icon('meta') ?><?= sol_icon('whatsapp') ?><?= sol_icon('instagram') ?>
                                <p><b>Built on Meta’s official WhatsApp, Instagram and Messenger APIs.</b><br><?= e($brandName) ?> connects through the same official APIs Meta gives every business, so your accounts and customers stay within Meta’s rules.</p>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>

        <section class="bk">
            <div class="lk-wrap sol-split">
                    <div>
                        <span class="bk-tag">BULK WHATSAPP MESSAGING</span>
                        <h2 class="sol-h2">Send bulk WhatsApp messages to all your customers</h2>
                        <p class="sol-p">Announce new stock, a weekend sale or your holiday hours to everyone who agreed to hear from you, from your own WhatsApp Business number. When they reply, Hannah answers and closes the sale.</p>
                        <ul class="bk-list">
                            <?php foreach ([
                                'Write the message with Hannah and send it to Meta for approval',
                                'Reaches only customers who opted in, and anyone who replies STOP is removed',
                                'See how many were sent, delivered and read',
                            ] as $point): ?>
                                <li><?= $tick ?><span><?= e($point) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="bk-note">On Vendor and Shop plans. Meta charges a small fee per message, billed to your own Meta account.</p>
                        <div class="sol-btns">
                            <a class="sol-btn" href="<?= e(app_route('features/bulk-whatsapp')) ?>">See how it works</a>
                        </div>
                    </div>
                    <div class="bk-card" aria-hidden="true">
                        <div class="acc-side">
                            <div class="bc-brand"><?= $mark ?><b><?= e($brandName) ?></b></div>
                            <b>New broadcast</b>
                            <small>Template</small><span>Weekend sale · Approved by Meta</span>
                            <small>Send to</small><span>1,240 customers who opted in</span>
                            <small>When</small><span>Saturday, 9:00 AM</span>
                            <span class="flow-btn">Send broadcast</span>
                        </div>
                        <?= $phone([
                            ['out', '<span>Hi Ada 👋 Our weekend sale starts now. 20% off every gown until Sunday!</span>'],
                            ['in', '<span>Is the red gown available in size 14?</span>'],
                            ['out', '<span>Yes! It’s ₦52,000 with the sale. Shall I send our payment details?</span>', ['Yes please']],
                        ]) ?>
                    </div>
            </div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Marketing for the way your business sells</h2>
                <p class="sol-p center w-900">See how sellers use <?= e($brandName) ?> from the first post to the repeat order.</p>
                <div class="uc-tabs" role="tablist">
                    <?php $first = true; foreach ($useCases as $key => [$label]): ?>
                        <button type="button" role="tab" class="uc-tab<?= $first ? ' active' : '' ?>" data-tab="<?= $key ?>" aria-selected="<?= $first ? 'true' : 'false' ?>"><?= e($label) ?></button>
                    <?php $first = false; endforeach; ?>
                </div>
                <?php $first = true; foreach ($useCases as $key => [, $tone, $intro, $cards]): ?>
                    <div class="uc-panel<?= $first ? ' active' : '' ?>" id="uc-<?= $key ?>" role="tabpanel">
                        <p class="sol-p center w-900 uc-text"><?= e($intro) ?></p>
                        <div class="uc-cards <?= $tone ?>">
                            <?php foreach ($cards as [$head, $ph]): ?>
                                <div class="uc-card"><div class="uc-head"><?= e($head) ?></div><div class="uc-body"><?= $ph ?></div></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php $first = false; endforeach; ?>
                <div class="center sol-mt40"><a class="sol-btn" href="<?= e($signupUrl) ?>">Get started</a></div>
            </div>
        </section>

        <?php $toolSections = ['testimonials']; require dirname(__DIR__) . '/includes/tools/sections-bottom.php'; ?>

        <section class="sol-pad">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Plug <?= e($brandName) ?> into the channels you already use</h2>
                <p class="sol-p center w-900">Connect your accounts once. Hannah answers on every one of them, using the same catalog, knowledge and payment details.</p>
                <?php require dirname(__DIR__) . '/includes/solutions/hub.php'; ?>
            </div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Simple to run, built to grow, measured end to end</h2>
                <p class="sol-p center w-900">AI posting, every chat in one inbox and post insights, together in <?= e($brandName) ?>.</p>

                <div class="fx-card wide">
                    <div>
                        <h3>See how every post performs</h3>
                        <p>Track views, reach, interactions and watch time for the posts you publish, so you know what to post more of.</p>
                    </div>
                    <div class="an" aria-hidden="true">
                        <div class="an-main">
                            <div class="an-brand"><?= $mark ?><b><?= e($brandName) ?></b></div>
                            <b class="an-title">Post insights</b>
                            <div class="an-stats">
                                <?php foreach ([['12.4k', 'Views'], ['8.1k', 'Reach'], ['932', 'Interactions'], ['41h', 'Watch time'], ['12s', 'Avg. watch'], ['18', 'Posts']] as [$n, $l]): ?>
                                    <span><b><?= $n ?></b><small><?= $l ?></small></span>
                                <?php endforeach; ?>
                            </div>
                            <table>
                                <tr><th>Post</th><th>Platform</th><th>Views</th><th>Reach</th><th>Interactions</th></tr>
                                <?php foreach ([
                                    ['Ankara drop', 'Instagram', '4.8k', 88, '3.2k', 72, '412', 44],
                                    ['Party trays', 'Facebook', '2.1k', 40, '1.6k', 36, '188', 20],
                                    ['Behind the scenes', 'TikTok', '5.5k', 98, '3.3k', 74, '332', 36],
                                ] as [$post, $platform, $v, $vw, $r, $rw, $in, $iw]): ?>
                                    <tr><td><b><?= $post ?></b></td><td><?= $platform ?></td><td><i style="--w:<?= $vw ?>%"></i><?= $v ?></td><td><i style="--w:<?= $rw ?>%"></i><?= $r ?></td><td><i class="y" style="--w:<?= $iw ?>%"></i><?= $in ?></td></tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <div class="an-pie">
                            <small>Views this week</small><b>12.4k <em>(+18%)</em></b>
                            <svg viewBox="0 0 42 42"><circle r="15.9" cx="21" cy="21" fill="none" class="p-a" stroke-width="10" stroke-dasharray="44 56" stroke-dashoffset="25"/><circle r="15.9" cx="21" cy="21" fill="none" class="p-b" stroke-width="10" stroke-dasharray="17 83" stroke-dashoffset="81"/><circle r="15.9" cx="21" cy="21" fill="none" class="p-c" stroke-width="10" stroke-dasharray="39 61" stroke-dashoffset="64"/></svg>
                            <ul><li class="a">Instagram</li><li class="b">Facebook</li><li class="c">TikTok</li></ul>
                        </div>
                        <div class="an-bars">
                            <small>Chats by channel</small>
                            <div><span style="--h:80%"><em>192</em></span><span style="--h:60%"><em>145</em></span></div>
                            <div class="an-bar-l"><small>Instagram</small><small>WhatsApp</small></div>
                        </div>
                    </div>
                </div>

                <div class="fx-row">
                    <div class="fx-card">
                        <div>
                            <h3>Run your shop from your phone</h3>
                            <p>Read every chat, see what Hannah said and reply to customers yourself, right from your phone.</p>
                        </div>
                        <div class="mb" aria-hidden="true">
                            <div class="mb-tabs"><span>All</span><span class="on">WhatsApp</span><span>Instagram</span></div>
                            <div class="mb-search">Search</div>
                            <b>Active chats</b>
                            <?php foreach ([['C', 'Chioma', 'Is the blue set still available?', '10:41'], ['T', 'Tunde', 'Sent the payment receipt', '10:32'], ['A', 'Amaka', 'Thanks for your help!', '09:58']] as [$initial, $name, $last, $time]): ?>
                                <div class="mb-row"><i><?= $initial ?></i><span><b><?= $name ?></b><small><?= $last ?></small></span><em><?= $time ?></em></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="fx-card">
                        <div>
                            <h3>Never run out mid-month</h3>
                            <p>Every plan includes monthly tokens. Top up whenever you need more, so Hannah keeps replying in your busiest weeks.</p>
                        </div>
                        <div class="dl" aria-hidden="true">
                            <div class="dl-card one"><i>✓</i><small>Every plan includes</small><b>Monthly tokens</b></div>
                            <div class="dl-card two"><i>+</i><small>Running low?</small><b>Top up</b><small>any time</small></div>
                        </div>
                    </div>
                </div>

                <div class="pts">
                    <?php foreach ([['24/7', 'AI REPLIES ON<br>EVERY CHANNEL'], ['1', 'INBOX FOR<br>ALL YOUR CHATS'], ['4', 'CHANNELS IN<br>ONE PLACE']] as [$n, $l]): ?>
                        <div class="pts-card"><h3><?= $n ?></h3><span><?= $l ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <div class="sol-cta smk-cta">
                    <h3>Do more than post. Run your whole social selling on <?= e($brandName) ?>.</h3>
                    <span>Get more sales from every post, ad and DM, starting today.</span>
                    <div class="sol-btns">
                        <a class="sol-btn blue" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e($contactUrl) ?>">Talk to us</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="pr" id="pricing">
            <div class="lk-wrap">
                <div class="pr-head">
                    <h2 class="sol-h2"><span class="pr-shout">Plans that grow with your business</span></h2>
                    <p class="pr-sub">No setup fees, simple pricing, cancel any time</p>
                </div>
                <div class="pr-switch">
                    <p>Save <?= $maxSaving ?>% with yearly billing</p>
                    <div class="pr-toggle" role="radiogroup" aria-label="Billing period">
                        <button type="button" role="radio" class="on" data-period="annual" aria-checked="true">Annual</button>
                        <button type="button" role="radio" data-period="monthly" aria-checked="false">Monthly</button>
                    </div>
                </div>
                <div class="pr-grid">
                    <?php foreach (plans_list() as $plan): ?>
                        <?php
                        $slug = (string) $plan['slug'];
                        [$title, $features, $usageExtra] = $planCopy[$slug];
                        $monthly = (float) $plan['price'];
                        $annual = (float) $plan['annual_price'];
                        $limit = $plan['business_limit'] ?? null;
                        $seats = $limit === null ? 'As many businesses as you need' : ($limit === 1 ? '1 business included' : $limit . ' businesses included');
                        $usage = array_merge($usageExtra, [number_format((int) $plan['monthly_tokens']) . ' tokens each month', 'Buy extra token packs any time']);
                        $selectUrl = app_route('signup', ['plan' => $slug]);
                        ?>
                        <article class="pr-card <?= $planTones[$slug] ?? 'blue' ?>">
                            <?php if (!empty($plan['popular'])): ?><span class="pr-badge">Most picked</span><?php endif; ?>
                            <h3><?= e((string) $plan['name']) ?></h3>
                            <p class="pr-blurb"><?= e((string) $plan['description']) ?></p>
                            <div class="pr-price">
                                <b data-annual="<?= e($money(round($annual / 12, 2))) ?>" data-monthly="<?= e($money($monthly)) ?>"><?= e($money(round($annual / 12, 2))) ?></b>
                                <span>/month<small data-annual="<?= e($money($annual)) ?> billed yearly" data-monthly="billed monthly"><?= e($money($annual)) ?> billed yearly</small></span>
                            </div>
                            <p class="pr-seats"><?= e($seats) ?><br>Uses tokens for AI replies and posts</p>
                            <a class="pr-btn" href="<?= e($selectUrl) ?>">Select plan</a>
                            <h4><?= e($title) ?></h4>
                            <ul class="pr-list">
                                <?php foreach ($features as [$label, $text]): ?>
                                    <li><b><?= e($label) ?></b> <?= e($text) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <h4 class="pr-usage-h">Usage</h4>
                            <ul class="pr-list plain">
                                <?php foreach ($usage as $line): ?>
                                    <li><?= e($line) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <a class="pr-btn" href="<?= e($selectUrl) ?>">Select plan</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad sol-reviews">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Rated by our customers</h2>
                <div class="rv">
                    <div class="rv-left">
                        <?php foreach ($ratings as [$score, $width, $logo, $name, $count]): ?>
                            <div class="rv-card">
                                <div class="rv-score"><?= $score ?><small>/5</small></div>
                                <div class="rv-stars" aria-label="<?= $score ?> out of 5 stars"><span class="bg">★★★★★</span><span class="fg" style="width:<?= $width ?>%">★★★★★</span></div>
                                <div class="rv-logo"><?php if ($logo): ?><img src="<?= e($ratingsBase . $logo) ?>" alt="<?= e($name) ?>" loading="lazy"><?php else: ?><span class="wm"><?= e($name) ?></span><?php endif; ?></div>
                                <small class="rv-count">Out of <?= $count ?> reviews</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="rv-right">
                        <?php foreach ($awards as [$file, $alt]): ?>
                            <img src="<?= e($ratingsBase . $file) ?>" alt="<?= e($alt) ?>" width="96" height="113" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<script src="<?= e($appUrl) ?>/assets/vendor/qrcode/qrcode.js?v=1"></script>
<script src="<?= e($appUrl) ?>/assets/js/solution-marketing.js?v=1"></script>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
