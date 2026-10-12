<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = 'Instagram Automation | AI Replies to DMs, Story Replies & Ad Clicks | ' . $brandName;
$metaDescription = 'Reply to Instagram DMs, story replies and ad clicks instantly, 24/7. ' . $brandName . '’s AI salesgirl shows your products, talks price, sends payment details and moves ready buyers to WhatsApp.';
$metaUrl = app_route('channels/instagram');
$extraStylesheets = ['solution.css?v=8', 'product-instagram.css?v=3'];
$activeNav = null;

$signupUrl = app_route('signup');
$pricingUrl = app_route('pricing');
$markSrc = rtrim($appUrl, '/') . '/assets/images/leadbird-favicon.png';
$mark = '<img class="brand-mark" src="' . e($markSrc) . '" alt="" width="32" height="32">';
$photoBase = rtrim($appUrl, '/') . '/assets/images/solutions/';

$tick = '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="9"/><path d="M6 10.5l2.6 2.6L14 7.6"/></svg>';
$list = static function (array $items) use ($tick): string {
    $html = '<ul class="ig-list">';
    foreach ($items as $item) {
        $html .= '<li>' . $tick . e($item) . '</li>';
    }

    return $html . '</ul>';
};
$igPhone = static function (string $class, string $handle, array $msgs): string {
    $html = '<div class="igp ' . $class . '"><div class="igp-top"><i class="igp-back"></i><i class="igp-ava"></i><b>' . e($handle) . '</b></div><div class="igp-body">';
    foreach ($msgs as $m) {
        $html .= '<div class="igp-m ' . $m[0] . '">' . $m[1] . '</div>';
    }

    return $html . '</div><div class="igp-bar"><span>Message…</span><i><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 3 3 10.5l7 2.5 2.5 7z"/><path d="M21 3 10 13"/></svg></i></div></div>';
};
$ruleCard = static function (string $class, string $title, array $steps) use ($mark, $brandName): string {
    $html = '<div class="igr ' . $class . '"><div class="igr-bar"><span class="igr-brand">' . $mark . '<b>' . e($brandName) . '</b></span></div><div class="igr-body"><b class="igr-title">' . e($title) . '</b>';
    foreach ($steps as [$tone, $label, $text, $chips]) {
        $html .= '<div class="igr-step ' . $tone . '"><i></i><div><small>' . e($label) . '</small><span>' . e($text) . '</span>';
        if ($chips) {
            $html .= '<span class="igr-chips">';
            foreach ($chips as $chip) {
                $html .= '<em>' . e($chip) . '</em>';
            }
            $html .= '</span>';
        }
        $html .= '</div></div>';
    }

    return $html . '</div></div>';
};
$inbox = static function (bool $multi, array $chats, string $who, array $thread) use ($mark, $brandName): string {
    $html = '<div class="igi' . ($multi ? ' multi' : '') . '"><div class="igi-bar"><span class="igr-brand">' . $mark . '<b>' . e($brandName) . '</b></span><span class="igi-tabs"><em class="on">All</em><em>Instagram</em><em>WhatsApp</em></span></div><div class="igi-main">';
    if ($multi) {
        $html .= '<ul class="igi-acc"><li class="h">Businesses</li><li><i></i>Ada’s Kitchen</li><li class="on"><i></i>Tolu’s Closet</li><li><i></i>Glow by Funmi</li></ul>';
    }
    $html .= '<ul class="igi-list"><li class="h">Chats</li>';
    foreach ($chats as $i => [$name, $last, $tag, $label]) {
        $html .= '<li' . ($i === 0 ? ' class="on"' : '') . '><i></i><span><b>' . e($name) . '</b><small>' . e($last) . '</small><em class="' . $tag . '">' . e($label) . '</em></span></li>';
    }
    $html .= '</ul><div class="igi-thread"><div class="igi-th-head"><i></i><b>' . e($who) . '</b><span>Hannah is replying</span></div><div class="igi-th-body">';
    foreach ($thread as [$dir, $text]) {
        $html .= '<span class="igi-m ' . $dir . '">' . e($text) . '</span>';
    }

    return $html . '</div><div class="igi-compose"><span>Type to reply yourself</span><b>Send</b></div></div></div></div>';
};

$gapStats = [
    ['24/7', 'Every DM and story reply gets an answer, even while you sleep or serve customers in your shop.'],
    ['Seconds', 'Hannah replies while the buyer is still excited, not hours later when they’ve bought elsewhere.'],
    ['1 inbox', 'Instagram, WhatsApp and Messenger chats together, so no buyer slips through.'],
];

$steps = [
    ['Connect your Instagram business account', 'Log in with Meta. You need an Instagram professional account linked to a Facebook Page you manage.'],
    ['Teach Hannah your business', 'Add your products, prices, delivery details and FAQs, or let her learn from your website and documents.'],
    ['Go live and watch it work', 'Switch Hannah on. Every DM and story reply gets an instant, personal answer, 24/7.'],
    ['Close on WhatsApp', 'Connect WhatsApp too, so ready buyers can move over to finish the sale.'],
];

$unified = [
    ['One inbox for every channel', 'Instagram DMs, WhatsApp and Messenger chats in one inbox. Read what Hannah said and reply yourself whenever you want.'],
    ['Every customer in one list', 'See each buyer’s name, channel and recent messages, and pick up where the last chat stopped. Vendor plan and up.'],
    ['Same knowledge on every channel', 'Teach Hannah once. She gives the same correct prices and answers on Instagram, WhatsApp and everywhere else.'],
    ['Start on Instagram, add channels any time', 'When you connect WhatsApp or Messenger, your catalog, knowledge and settings are already in place.'],
];

$types = [
    ['<path d="M14 6l6 3 6-3 6 5-4 4-2-1v18H14V14l-2 1-4-4z"/>', 'Fashion &amp; beauty: turn new arrivals into orders', 'Hannah answers “Do you have this in my size?”, shares prices and photos of your pieces, and sends payment details the moment a buyer is ready.', 'industries/fashion-beauty'],
    ['<path d="M6 22h28a14 14 0 0 1-28 0zM4 32h32M20 12v4M14 13l1 3M26 13l-1 3"/>', 'Food vendors: take orders straight from DMs', 'Customers ask what’s on the menu, how much a tray costs and whether you deliver to their area. Hannah answers and gets the order moving.', 'industries/food-vendors'],
    ['<rect x="7" y="9" width="26" height="23" rx="3"/><path d="M7 16h26M14 5v7M26 5v7M14 23l4 4 8-8"/>', 'Services &amp; bookings: fill your calendar', 'Hannah collects the customer’s name, phone and preferred time, checks your Google Calendar and books the slot, or shares your booking link.', 'industries/services-bookings'],
    ['<path d="M20 32c-6-3-9-8-9-14 4 1 7 4 9 8 2-4 5-7 9-8 0 6-3 11-9 14z"/><path d="M20 26c-2-5-2-11 0-17 2 6 2 12 0 17zM6 32h28"/>', 'Health &amp; wellness: answer before they book elsewhere', 'Share your services, prices and opening hours instantly, and book consultations right in the chat.', 'industries/health-wellness'],
    ['<path d="M4 15 20 8l16 7-16 7z"/><path d="M11 18.5V26c5 4 13 4 18 0v-7.5M34 16v9"/>', 'Schools &amp; training: convert course enquiries', 'Hannah explains your classes, fees and start dates, answers parents’ questions and sends the account details to secure a place.', 'industries/schools-training'],
    ['<path d="M6 13h28v5a3 3 0 0 0 0 6v5H6v-5a3 3 0 0 0 0-6z"/><path d="M24 13v16"/>', 'Events &amp; travel: sell tickets and trips in the DMs', 'Answer questions about dates, prices and what’s included, share your ticket link and follow up with people who go quiet.', 'industries/events-travel'],
];

$faqs = [
    ['What is Instagram automation with ' . $brandName . '?', 'It means Hannah, your AI salesgirl, replies to your Instagram DMs for you. She uses Meta’s official messaging API, learns your products, prices and FAQs, and answers customers in your tone, day and night.'],
    ['Does ' . $brandName . ' reply to Instagram comments?', 'Not yet. ' . $brandName . ' replies to Instagram DMs, including story replies and messages from your Instagram ads. You can still see how your posts perform in Insights and schedule new posts from the planner.'],
    ['Is it safe for my Instagram account?', 'Yes. Everything runs through Meta’s official API, with no unofficial tools, passwords or scraping. Hannah only replies to people who message you first.'],
    ['Can I manage Instagram DMs and WhatsApp in the same inbox?', 'Yes. Instagram, WhatsApp and Messenger chats all land in one ' . $brandName . ' inbox, where you can read Hannah’s replies and step in any time.'],
    ['What happens when a customer needs a real person?', 'Hannah sends you a WhatsApp alert for complaints, big orders and chats that need you. You can reply yourself from the inbox whenever you want.'],
    ['Do I need to code or build chatbot flows?', 'No. There are no flows to draw. You teach Hannah with your products, website, documents and FAQs, and she works out the right reply herself.'],
    ['How is ' . $brandName . ' different from Instagram-only automation tools?', 'Hannah is an AI salesgirl, not a keyword bot. She answers real questions, talks price within your limits, books consultations, sends payment details and moves ready buyers to WhatsApp, across every channel you connect.'],
];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol ig flex-1">

        <section class="sol-hero ig-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <h1>Instagram automation<br>Turn DMs into sales</h1>
                    <p class="sol-p">Hannah, your AI salesgirl, replies to Instagram DMs, story replies and ad clicks instantly, 24/7. She shows your products, talks price, sends payment details and moves ready buyers to WhatsApp.</p>
                    <div class="sol-btns">
                        <a class="sol-btn" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e($pricingUrl) ?>">See pricing</a>
                    </div>
                    <div class="ig-chips">
                        <span><?= sol_icon('meta') ?>Official Meta APIs</span>
                        <span><?= sol_icon('instagram') ?>Instagram + WhatsApp in one inbox</span>
                        <span>No code needed</span>
                    </div>
                </div>

                <div class="ig-hv" aria-hidden="true">
                    <span class="ig-ring r1"></span><span class="ig-ring r2"></span><span class="ig-ring r3"></span>
                    <span class="ig-hv-ava"><img src="<?= e($photoBase . 'red-gown.jpg') ?>" alt=""></span>
                    <span class="ig-hv-logo"><?= sol_icon('instagram') ?></span>
                    <span class="ig-hv-icon a"><svg viewBox="0 0 24 24"><path d="M4 12a8 8 0 1 1 4 6.9L4 20l1.2-3.8A8 8 0 0 1 4 12z"/></svg></span>
                    <span class="ig-hv-icon b"><svg viewBox="0 0 24 24"><path d="M3 11 21 3l-6 18-3-8z"/></svg></span>
                    <div class="ig-hv-card one">👋 Hi! Do you still have the red evening gown in size 12?</div>
                    <div class="ig-hv-card two"><b>Hannah</b> replied in 4 seconds ✓</div>
                    <?= $igPhone('ig-hv-ph', 'tolus.closet', [
                        ['out', 'Hi! Do you still have the red evening gown in size 12?'],
                        ['in', 'Hi Amaka 👋 Yes, size 12 is in stock for ₦65,000. Want me to send our payment details?<span class="igp-btn">Send payment details</span>'],
                        ['out', 'Yes please! Can you deliver to Ikeja?'],
                    ]) ?>
                </div>
            </div>
        </section>

        <?php
        $toolSections = ['clients'];
        $clientsHeading = '<strong>Sellers who sell in the DMs</strong> use ' . e($brandName) . ' to automate Instagram';
        require dirname(__DIR__) . '/includes/tools/sections-bottom.php';
        ?>

        <section class="sol-pad ig-gap-sec">
            <div class="lk-wrap">
                <div class="ig-gap">
                    <h2 class="sol-h2 center">Your Instagram DMs are a sales channel<br>Treat them like one</h2>
                    <p class="center">Every slow reply and missed DM is a buyer who could go to another seller.<br>Instagram automation closes those gaps for you, day and night.</p>
                    <div class="ig-stats">
                        <?php foreach ($gapStats as [$num, $copy]): ?>
                            <div><b><?= e($num) ?></b><p><?= e($copy) ?></p></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad ig-feats">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Everything you need to automate<br>Instagram DMs</h2>

                <article class="ig-feat pink">
                    <div class="sol-split">
                        <div class="ig-copy">
                            <h3>AI replies to every DM</h3>
                            <p>A buyer sends “How much?”, “Do you deliver?” or “Is this available?”. Hannah reads your catalog and FAQs and replies in seconds, in your tone. No copy and paste, no delays, no missed sales.</p>
                            <?= $list([
                                'Answers questions about products, prices, sizes and delivery',
                                'Shows the right items with photos and prices from your catalog',
                                'Talks price within the discount limit you set (Vendor plan and up)',
                                'Sends your payment details when the buyer is ready',
                                'Runs 24/7, even when you’re offline',
                            ]) ?>
                        </div>
                        <div class="ig-vis comment" aria-hidden="true">
                            <div class="ig-comments">
                                <div class="ig-c-head">Messages <i>▾</i></div>
                                <div class="ig-c"><i class="a"></i><span><b>amaka.obi</b> Red gown in size 12?</span></div>
                                <div class="ig-c"><i class="b"></i><span><b>jide_o</b> Delivery to Ikeja?</span></div>
                                <div class="ig-c reply"><i class="c"></i><span><b>tolus.closet</b> ₦3,000, 1–2 days 🚚</span></div>
                                <div class="ig-c-sent">✓ Hannah replied in 4 seconds</div>
                            </div>
                            <?= $ruleCard('ig-rule-a', 'How Hannah replies', [
                                ['pink', 'When', 'An Instagram DM arrives', ['Any customer']],
                                ['blue', 'She checks', 'What you taught her', ['Catalog', 'Prices', 'FAQs']],
                                ['red', 'Then', 'Replies in your tone', ['Hi! Yes, size 12 is in stock 💃']],
                            ]) ?>
                        </div>
                    </div>
                    <div class="ig-how">
                        <h4>How it works:</h4>
                        <ol>
                            <li><b>Buyer sends a DM</b>“Do you have this in size 12?”</li>
                            <li><b>Hannah checks your catalog</b>Sizes, prices and photos you added</li>
                            <li><b>Reply in seconds</b>A clear answer and the next step</li>
                            <li><b>Saved to your inbox</b>Read it, label it or reply yourself</li>
                        </ol>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis story" aria-hidden="true">
                            <?= $ruleCard('ig-rule-b', 'Story replies & ad clicks', [
                                ['pink', 'When', 'A story reply or ad click arrives', []],
                                ['blue', 'She knows', 'Which ad they came from', ['Ad: Gold sandals']],
                                ['red', 'Then', 'Replies about that product', ['Hi! These are ₦28,000 ✨']],
                            ]) ?>
                            <div class="ig-story-reply"><span class="ig-hv-ava sm"><img src="<?= e($photoBase . 'gold-sandals.jpg') ?>" alt=""></span><em>😍 I love these! Are they still available?</em></div>
                            <?= $igPhone('ig-ph-r', 'tolus.closet', [
                                ['out', 'I saw your ad. Do the gold sandals come in size 39?'],
                                ['in', 'Hi Bisi! Yes, the gold sandals come in sizes 37–42 for ₦28,000. Shall I reserve a size 39 for you?'],
                            ]) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>Story replies and ad clicks become sales chats</h3>
                            <p>Turn every story and ad into a conversation. When a follower replies to your story, it lands in your DMs and Hannah answers right away. When someone taps “Send message” on your Instagram ad, she also sees which ad they came from.</p>
                            <?= $list([
                                'Story replies arrive as DMs, and Hannah answers them like any other chat',
                                'Click-to-Instagram ads: Hannah sees the ad and talks about that product',
                                'Reply while interest is at its highest',
                                'Perfect for new arrivals, flash sales and product drops',
                            ]) ?>
                        </div>
                    </div>
                </article>

                <article class="ig-feat pink">
                    <div class="sol-split">
                        <div class="ig-copy">
                            <h3>An AI salesgirl, not a chatbot builder</h3>
                            <p>No flows to draw and no keywords to guess. Teach Hannah once from your website, documents and FAQs, and she handles real conversations from hello to payment.</p>
                            <?= $list([
                                'Learns from your website, documents, links and FAQs',
                                'Understands real questions, not just keywords',
                                'Books consultations and checks your Google Calendar',
                                'Sends payment details, and a receipt once they’ve paid (Shop plan)',
                                'Alerts you on WhatsApp when a chat needs you',
                                'You can read every reply and take over any time',
                            ]) ?>
                        </div>
                        <div class="ig-vis center" aria-hidden="true">
                            <?= $igPhone('ig-ph-c', 'glowbyfunmi', [
                                ['out', 'Hi, I’d like to book a bridal makeup trial for Saturday.'],
                                ['in', 'Lovely! Saturday has 11am and 2pm free. Which works for you?'],
                                ['out', '11am. I’m Jumoke, 0803 555 0199'],
                                ['in', 'You’re booked for Saturday at 11am, Jumoke ✅<span class="igp-btn">See our prices</span>'],
                            ]) ?>
                        </div>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis wide" aria-hidden="true">
                            <?= $inbox(false, [
                                ['Amaka Obi', 'Yes please! Can you deliver to Ikeja?', 'open', 'Ready to buy'],
                                ['Jide Ola', 'How much is delivery to Ikeja?', 'pending', 'New'],
                                ['Bisi Adeyemi', 'Can I pick up my order instead?', 'open', 'Ready to buy'],
                                ['Kemi Ade', 'I’ve paid, thank you!', 'solved', 'Paid'],
                                ['Femi B.', 'Do you have it in black?', 'pending', 'New'],
                            ], 'Amaka Obi', [
                                ['in', 'Hi! Do you still have the red evening gown in size 12?'],
                                ['out', 'Hi Amaka 👋 Yes, size 12 is in stock for ₦65,000. Want me to send our payment details?'],
                                ['note', 'Owner alert sent: ready to buy'],
                                ['out', 'Delivery to Ikeja is ₦3,000. Here are our payment details…'],
                            ]) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>One inbox for every chat</h3>
                            <p>Stop juggling the Instagram app and your phone. Every conversation lands in one inbox, so you can see what Hannah said, label your buyers and step in whenever you want.</p>
                            <?= $list([
                                'Instagram, WhatsApp and Messenger chats in one place',
                                'Read every reply Hannah sends',
                                'Pause Hannah on a chat and reply yourself',
                                'Label chats so you know who’s ready to buy',
                                'WhatsApp alerts for complaints and big orders',
                            ]) ?>
                        </div>
                    </div>
                </article>

                <article class="ig-feat pink">
                    <div class="sol-split">
                        <div class="ig-copy">
                            <h3>Run more than one business</h3>
                            <p>Sell fashion and food? Run a salon and a beauty shop? Manage each business from one account, with its own Instagram, catalog and settings, so chats never get mixed up.</p>
                            <?= $list([
                                'Each business gets its own channels, catalog and settings',
                                'Separate inboxes, so buyers never get the wrong prices',
                                'Hannah answers with the right business’s knowledge',
                                'Switch between businesses without logging out',
                                'Vendor gives you 3 businesses, Shop gives you as many as you need',
                            ]) ?>
                        </div>
                        <div class="ig-vis wide" aria-hidden="true">
                            <?= $inbox(true, [
                                ['Amaka Obi', 'Do you have it in size 12?', 'open', 'Ready to buy'],
                                ['Jide Ola', 'Do you deliver to Ikeja?', 'pending', 'New'],
                                ['Kemi Ade', 'I’ve paid, thank you!', 'solved', 'Paid'],
                                ['Femi B.', 'Is the sale still on?', 'open', 'Ready to buy'],
                            ], 'Amaka Obi', [
                                ['in', 'Hi, do you have the red gown in size 12?'],
                                ['out', 'Yes! Size 12 is ₦65,000. Want me to send our payment details?'],
                            ]) ?>
                        </div>
                    </div>
                    <div class="ig-how">
                        <h4>How it works:</h4>
                        <ol class="five">
                            <li><b>Add a business</b>Create Tolu’s Closet next to Ada’s Kitchen</li>
                            <li><b>Connect its Instagram</b>Link the right account to each business</li>
                            <li><b>DM arrives</b>A buyer messages @tolus.closet about a gown</li>
                            <li><b>Right answers</b>Hannah replies with Tolu’s Closet’s catalog and prices</li>
                            <li><b>One account</b>Switch between businesses from the same login</li>
                        </ol>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis center" aria-hidden="true">
                            <?= $igPhone('ig-ph-c', 'emma_ibe', [
                                ['out', 'Let me think about it and get back to you.'],
                                ['in', 'No problem, Emma 😊'],
                                ['in', 'Hi Emma! Just checking in. The red gown in size 12 is still available. Want me to hold it for you?<span class="igp-btn">Yes, hold it</span><span class="igp-btn">Continue on WhatsApp</span>'],
                            ]) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>Follow up and close on WhatsApp</h3>
                            <p>Not every buyer pays on the first chat. Hannah checks back with people who go quiet and moves ready buyers over to WhatsApp, so the sale doesn’t get lost in your DMs.</p>
                            <?= $list([
                                'Follows up with buyers who stop replying (Shop plan)',
                                'Moves ready buyers over to WhatsApp (Vendor plan and up)',
                                'Sends a receipt once the buyer says they’ve paid (Shop plan)',
                                'Alerts you on WhatsApp when a buyer needs you',
                            ]) ?>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="sol-pad ig-setup">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Set up Instagram automation in 4 steps</h2>
                <p class="center ig-lead">No developer needed. Go from sign-up to your first AI reply fast.</p>
                <ol class="ig-steps">
                    <?php foreach ($steps as $i => [$title, $copy]): ?>
                        <li><span><?= $i + 1 ?></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <section class="sol-pad ig-unified">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Instagram automation that lets you<br>sell on WhatsApp too</h2>
                <p class="center ig-lead">Many automation tools keep Instagram and WhatsApp in separate apps. <?= e($brandName) ?> brings every channel into one inbox, so your chats, customers and knowledge stay in one place.</p>
                <div class="sol-split">
                    <div class="ig-unified-list">
                        <?php foreach ($unified as [$title, $copy]): ?>
                            <div><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ig-vis uni" aria-hidden="true">
                        <?= $inbox(false, [
                            ['Bisi Adeyemi', 'Is the 20% off still on?', 'open', 'Ready to buy'],
                            ['Jide Ola', 'Thanks for the update!', 'pending', 'New'],
                            ['Kemi Ade', 'Can I change my order?', 'open', 'Paid'],
                        ], 'Bisi Adeyemi', [
                            ['in', 'Hi, I saw your Reel. Is the 20% off still on?'],
                            ['out', 'Yes! It ends on Sunday. The gold sandals are ₦22,400 with the discount.'],
                            ['note', 'Ready to buy · moved to WhatsApp'],
                        ]) ?>
                        <div class="ig-profile">
                            <div class="ig-profile-head"><?= $mark ?><span><b>Tolu’s Closet</b><small>Your business</small></span></div>
                            <small class="h">Channels</small>
                            <span class="ig-ch"><?= sol_icon('instagram') ?>Instagram<em>Connected</em></span>
                            <span class="ig-ch"><?= sol_icon('whatsapp') ?>WhatsApp<em>Connected</em></span>
                            <span class="ig-ch"><?= sol_icon('messenger') ?>Messenger<em class="off">Not linked</em></span>
                            <small class="h">Labels</small>
                            <span class="ig-tags"><em>Ready to buy</em><em>Paid</em></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad ig-types">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Instagram DM automation for<br>every type of business</h2>
                <div class="ig-type-grid">
                    <?php foreach ($types as [$icon, $title, $copy, $route]): ?>
                        <a class="ig-type" href="<?= e(app_route($route)) ?>">
                            <svg viewBox="0 0 40 40" aria-hidden="true"><?= $icon ?></svg>
                            <h3><?= $title ?></h3>
                            <p><?= e($copy) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad ig-faq">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">FAQ: Instagram automation</h2>
                <div class="ig-faq-list">
                    <?php foreach ($faqs as $i => [$q, $a]): ?>
                        <details<?= $i === 0 ? ' open' : '' ?>>
                            <summary><?= e($q) ?></summary>
                            <p><?= e($a) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad ig-cta-sec">
            <div class="lk-wrap">
                <div class="ig-cta">
                    <h2>Start turning your Instagram DMs into sales today</h2>
                    <div class="sol-btns">
                        <a class="sol-btn white" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e(app_route('contact')) ?>">Talk to us</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
