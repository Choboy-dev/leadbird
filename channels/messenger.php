<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = 'Messenger Automation | AI Replies to Facebook Page Chats & Ad Clicks | ' . $brandName;
$metaDescription = 'Reply to every Facebook Messenger chat and click-to-Messenger ad instantly, 24/7. ' . $brandName . '’s AI salesgirl shows your products, talks price, sends payment details and moves ready buyers to WhatsApp.';
$metaUrl = app_route('channels/messenger');
$extraStylesheets = ['solution.css?v=8', 'product-instagram.css?v=3', 'product-messenger.css?v=1'];
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
$msPhone = static function (string $class, string $page, array $msgs): string {
    $html = '<div class="igp ' . $class . '"><div class="igp-top"><i class="igp-back"></i><i class="igp-ava"></i><b>' . e($page) . '<small>Typically replies instantly</small></b></div><div class="igp-body">';
    foreach ($msgs as $m) {
        $html .= '<div class="igp-m ' . $m[0] . '">' . $m[1] . '</div>';
    }

    return $html . '</div><div class="igp-bar"><span>Aa</span><i><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 3 3 10.5l7 2.5 2.5 7z"/><path d="M21 3 10 13"/></svg></i></div></div>';
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
    $html = '<div class="igi' . ($multi ? ' multi' : '') . '"><div class="igi-bar"><span class="igr-brand">' . $mark . '<b>' . e($brandName) . '</b></span><span class="igi-tabs"><em class="on">All</em><em>Messenger</em><em>WhatsApp</em></span></div><div class="igi-main">';
    if ($multi) {
        $html .= '<ul class="igi-acc"><li class="h">Businesses</li><li class="on"><i></i>Ada’s Kitchen</li><li><i></i>Tolu’s Closet</li><li><i></i>Glow by Funmi</li></ul>';
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
    ['24/7', 'Every Messenger chat gets an answer, even while you sleep, cook or serve customers in your shop.'],
    ['Seconds', 'Hannah replies while the buyer is still on your Page, not hours later when they’ve ordered elsewhere.'],
    ['1 inbox', 'Messenger, WhatsApp and Instagram chats together, so no buyer slips through.'],
];

$steps = [
    ['Connect your Facebook Page', 'Log in with Meta and pick the Page you want Hannah to answer for. You need to be an admin of that Page.'],
    ['Teach Hannah your business', 'Add your products, prices, delivery details and FAQs, or let her learn from your website and documents.'],
    ['Go live and watch it work', 'Switch Hannah on. Every Messenger chat gets an instant, personal answer, 24/7.'],
    ['Close on WhatsApp', 'Connect WhatsApp too, so ready buyers can move over to finish the sale.'],
];

$unified = [
    ['One inbox for every channel', 'Messenger, WhatsApp and Instagram chats in one inbox. Read what Hannah said and reply yourself whenever you want.'],
    ['Every customer in one list', 'See each buyer’s name, channel and recent messages, and pick up where the last chat stopped. Vendor plan and up.'],
    ['Same knowledge on every channel', 'Teach Hannah once. She gives the same correct prices and answers on Messenger, WhatsApp and everywhere else.'],
    ['Start on Messenger, add channels any time', 'When you connect WhatsApp or Instagram, your catalog, knowledge and settings are already in place.'],
];

$types = [
    ['<path d="M6 22h28a14 14 0 0 1-28 0zM4 32h32M20 12v4M14 13l1 3M26 13l-1 3"/>', 'Food vendors: take orders straight from your Page', 'Customers ask what’s on the menu, how much a tray costs and whether you deliver to their area. Hannah answers and gets the order moving.', 'industries/food-vendors'],
    ['<path d="M14 6l6 3 6-3 6 5-4 4-2-1v18H14V14l-2 1-4-4z"/>', 'Fashion &amp; beauty: turn new arrivals into orders', 'Hannah answers “Do you have this in my size?”, shares prices and photos of your pieces, and sends payment details the moment a buyer is ready.', 'industries/fashion-beauty'],
    ['<rect x="7" y="9" width="26" height="23" rx="3"/><path d="M7 16h26M14 5v7M26 5v7M14 23l4 4 8-8"/>', 'Services &amp; bookings: fill your calendar', 'Hannah collects the customer’s name, phone and preferred time, checks your Google Calendar and books the slot, or shares your booking link.', 'industries/services-bookings'],
    ['<path d="M20 32c-6-3-9-8-9-14 4 1 7 4 9 8 2-4 5-7 9-8 0 6-3 11-9 14z"/><path d="M20 26c-2-5-2-11 0-17 2 6 2 12 0 17zM6 32h28"/>', 'Health &amp; wellness: answer before they book elsewhere', 'Share your services, prices and opening hours instantly, and book consultations right in the chat.', 'industries/health-wellness'],
    ['<path d="M4 15 20 8l16 7-16 7z"/><path d="M11 18.5V26c5 4 13 4 18 0v-7.5M34 16v9"/>', 'Schools &amp; training: convert course enquiries', 'Hannah explains your classes, fees and start dates, answers parents’ questions and sends the account details to secure a place.', 'industries/schools-training'],
    ['<path d="M6 13h28v5a3 3 0 0 0 0 6v5H6v-5a3 3 0 0 0 0-6z"/><path d="M24 13v16"/>', 'Events &amp; travel: sell tickets and trips on Messenger', 'Answer questions about dates, prices and what’s included, share your ticket link and follow up with people who go quiet.', 'industries/events-travel'],
];

$faqs = [
    ['What is Messenger automation with ' . $brandName . '?', 'It means Hannah, your AI salesgirl, replies to the Messenger chats on your Facebook Page for you. She uses Meta’s official messaging API, learns your products, prices and FAQs, and answers customers in your tone, day and night.'],
    ['Do I need a Facebook Page?', 'Yes. Messenger chats come in through a Facebook Page, and you need to be an admin of it. You connect it by logging in with Meta. No passwords are shared with ' . $brandName . '.'],
    ['Does ' . $brandName . ' reply to comments on my Facebook posts?', 'Not yet. ' . $brandName . ' replies to Messenger chats, including people who message you from your Page or from your click-to-Messenger ads. You can still schedule Facebook posts from the planner on the Vendor and Shop plans.'],
    ['Is it safe for my Facebook Page?', 'Yes. Everything runs through Meta’s official API, with no unofficial tools, passwords or scraping. Hannah only replies to people who message your Page first.'],
    ['Can I manage Messenger and WhatsApp in the same inbox?', 'Yes. Messenger, WhatsApp and Instagram chats all land in one ' . $brandName . ' inbox, where you can read Hannah’s replies and step in any time.'],
    ['What happens when a customer needs a real person?', 'Hannah sends you a WhatsApp alert for complaints, big orders and chats that need you. You can reply yourself from the inbox whenever you want.'],
    ['Do I need to code or build chatbot flows?', 'No. There are no flows to draw. You teach Hannah with your products, website, documents and FAQs, and she works out the right reply herself.'],
];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol ig ms flex-1">

        <section class="sol-hero ig-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <h1>Messenger automation<br>Turn chats into sales</h1>
                    <p class="sol-p">Hannah, your AI salesgirl, replies to every Messenger chat on your Facebook Page and every click-to-Messenger ad, instantly, 24/7. She shows your products, talks price, sends payment details and moves ready buyers to WhatsApp.</p>
                    <div class="sol-btns">
                        <a class="sol-btn" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e($pricingUrl) ?>">See pricing</a>
                    </div>
                    <div class="ig-chips">
                        <span><?= sol_icon('meta') ?>Official Meta APIs</span>
                        <span><?= sol_icon('messenger') ?>Messenger + WhatsApp in one inbox</span>
                        <span>No code needed</span>
                    </div>
                </div>

                <div class="ig-hv" aria-hidden="true">
                    <span class="ig-ring r1"></span><span class="ig-ring r2"></span><span class="ig-ring r3"></span>
                    <span class="ig-hv-ava"><img src="<?= e($photoBase . 'jollof-rice-tray.jpg') ?>" alt=""></span>
                    <span class="ig-hv-logo"><?= sol_icon('messenger') ?></span>
                    <span class="ig-hv-icon a"><svg viewBox="0 0 24 24"><path d="M4 12a8 8 0 1 1 4 6.9L4 20l1.2-3.8A8 8 0 0 1 4 12z"/></svg></span>
                    <span class="ig-hv-icon b"><svg viewBox="0 0 24 24"><path d="M3 11 21 3l-6 18-3-8z"/></svg></span>
                    <div class="ig-hv-card one">👋 Hi! How much is a tray of jollof rice for 20 people?</div>
                    <div class="ig-hv-card two"><b>Hannah</b> replied in 3 seconds ✓</div>
                    <?= $msPhone('ig-hv-ph', 'Ada’s Kitchen', [
                        ['out', 'Hi! How much is a tray of jollof rice for 20 people?'],
                        ['in', 'Hi Tunde 👋 A party tray for 20 is ₦45,000, with chicken included. When do you need it?<span class="igp-btn">See our menu</span>'],
                        ['out', 'Saturday afternoon. Do you deliver to Lekki?'],
                    ]) ?>
                </div>
            </div>
        </section>

        <?php
        $toolSections = ['clients'];
        $clientsHeading = '<strong>Businesses that sell on Facebook</strong> use ' . e($brandName) . ' to automate Messenger';
        require dirname(__DIR__) . '/includes/tools/sections-bottom.php';
        ?>

        <section class="sol-pad ig-gap-sec">
            <div class="lk-wrap">
                <div class="ig-gap">
                    <h2 class="sol-h2 center">Your Messenger inbox is a sales channel<br>Treat it like one</h2>
                    <p class="center">Every slow reply and missed chat is a buyer who could order from another seller.<br>Messenger automation closes those gaps for you, day and night.</p>
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
                <h2 class="sol-h2 center">Everything you need to automate<br>Facebook Messenger</h2>

                <article class="ig-feat pink">
                    <div class="sol-split">
                        <div class="ig-copy">
                            <h3>AI replies to every Messenger chat</h3>
                            <p>A buyer sends “How much?”, “Do you deliver?” or “Is this available?”. Hannah reads your catalog and FAQs and replies in seconds, in your tone. No copy and paste, no delays, no missed orders.</p>
                            <?= $list([
                                'Answers questions about products, prices, sizes and delivery',
                                'Shows the right items with photos and prices from your catalog',
                                'Understands photos customers send of what they want',
                                'Talks price within the discount limit you set (Vendor plan and up)',
                                'Sends your payment details when the buyer is ready',
                                'Runs 24/7, even when you’re offline',
                            ]) ?>
                        </div>
                        <div class="ig-vis comment" aria-hidden="true">
                            <div class="ig-comments">
                                <div class="ig-c-head">Chats <i>▾</i></div>
                                <div class="ig-c"><i class="a"></i><span><b>Tunde A.</b> Tray for 20 people?</span></div>
                                <div class="ig-c"><i class="b"></i><span><b>Ngozi E.</b> Delivery to Lekki?</span></div>
                                <div class="ig-c reply"><i class="c"></i><span><b>Ada’s Kitchen</b> ₦2,500, today 🛵</span></div>
                                <div class="ig-c-sent">✓ Hannah replied in 3 seconds</div>
                            </div>
                            <?= $ruleCard('ig-rule-a', 'How Hannah replies', [
                                ['pink', 'When', 'A Messenger chat arrives', ['Any customer']],
                                ['blue', 'She checks', 'What you taught her', ['Menu', 'Prices', 'FAQs']],
                                ['red', 'Then', 'Replies in your tone', ['Hi! A tray for 20 is ₦45,000 🍛']],
                            ]) ?>
                        </div>
                    </div>
                    <div class="ig-how">
                        <h4>How it works:</h4>
                        <ol>
                            <li><b>Buyer messages your Page</b>“How much is a tray for 20?”</li>
                            <li><b>Hannah checks your catalog</b>Prices, portions and photos you added</li>
                            <li><b>Reply in seconds</b>A clear answer and the next step</li>
                            <li><b>Saved to your inbox</b>Read it, label it or reply yourself</li>
                        </ol>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis story" aria-hidden="true">
                            <?= $ruleCard('ig-rule-b', 'Click-to-Messenger ads', [
                                ['pink', 'When', 'Someone taps “Send message” on your ad', []],
                                ['blue', 'She knows', 'Which ad they came from', ['Ad: Small chops tray']],
                                ['red', 'Then', 'Replies about that product', ['Hi! This tray is ₦18,000 😋']],
                            ]) ?>
                            <div class="ig-story-reply"><span class="ig-hv-ava sm"><img src="<?= e($photoBase . 'small-chops-tray.jpg') ?>" alt=""></span><em>😍 Saw your ad! Is the small chops tray available this weekend?</em></div>
                            <?= $msPhone('ig-ph-r', 'Ada’s Kitchen', [
                                ['out', 'I saw your ad. Is the small chops tray available on Sunday?'],
                                ['in', 'Hi Kemi! Yes, the small chops tray is ₦18,000 and serves 15. Shall I book one for Sunday?'],
                            ]) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>Ad clicks and Page visitors become sales chats</h3>
                            <p>Your Facebook ads and Page bring people in. Hannah makes sure every one of them gets an answer. When someone taps “Send message” on your click-to-Messenger ad, she also sees which ad they came from.</p>
                            <?= $list([
                                'Click-to-Messenger ads: Hannah sees the ad and talks about that product',
                                'Visitors who tap “Message” on your Page get an instant reply',
                                'Reply while interest is at its highest',
                                'Perfect for promos, weekend specials and new arrivals',
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
                            <?= $msPhone('ig-ph-c', 'Glow by Funmi', [
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
                                ['Tunde Adebayo', 'Saturday afternoon. Do you deliver to Lekki?', 'open', 'Ready to buy'],
                                ['Ngozi Eze', 'How much is delivery to Lekki?', 'pending', 'New'],
                                ['Kemi Ade', 'Can I pick up my order instead?', 'open', 'Ready to buy'],
                                ['Bisi Ola', 'I’ve paid, thank you!', 'solved', 'Paid'],
                                ['Femi B.', 'Do you do small chops too?', 'pending', 'New'],
                            ], 'Tunde Adebayo', [
                                ['in', 'Hi! How much is a tray of jollof rice for 20 people?'],
                                ['out', 'Hi Tunde 👋 A party tray for 20 is ₦45,000, with chicken included. When do you need it?'],
                                ['note', 'Owner alert sent: ready to buy'],
                                ['out', 'Delivery to Lekki is ₦2,500. Here are our payment details…'],
                            ]) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>One inbox for every chat</h3>
                            <p>Stop juggling Facebook, Messenger and your phone. Every conversation lands in one inbox, so you can see what Hannah said, label your buyers and step in whenever you want.</p>
                            <?= $list([
                                'Messenger, WhatsApp and Instagram chats in one place',
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
                            <p>Run a restaurant and a catering service? A salon and a beauty shop? Manage each business from one account, with its own Facebook Page, catalog and settings, so chats never get mixed up.</p>
                            <?= $list([
                                'Each business gets its own Page, catalog and settings',
                                'Separate inboxes, so buyers never get the wrong prices',
                                'Hannah answers with the right business’s knowledge',
                                'Switch between businesses without logging out',
                                'Vendor gives you 3 businesses, Shop gives you as many as you need',
                            ]) ?>
                        </div>
                        <div class="ig-vis wide" aria-hidden="true">
                            <?= $inbox(true, [
                                ['Tunde Adebayo', 'A tray for 20 people?', 'open', 'Ready to buy'],
                                ['Ngozi Eze', 'Do you deliver to Lekki?', 'pending', 'New'],
                                ['Bisi Ola', 'I’ve paid, thank you!', 'solved', 'Paid'],
                                ['Femi B.', 'Is the promo still on?', 'open', 'Ready to buy'],
                            ], 'Tunde Adebayo', [
                                ['in', 'Hi, how much is a tray of jollof for 20?'],
                                ['out', 'A party tray for 20 is ₦45,000. Want me to send our payment details?'],
                            ]) ?>
                        </div>
                    </div>
                    <div class="ig-how">
                        <h4>How it works:</h4>
                        <ol class="five">
                            <li><b>Add a business</b>Create Ada’s Kitchen next to Tolu’s Closet</li>
                            <li><b>Connect its Page</b>Link the right Facebook Page to each business</li>
                            <li><b>Chat arrives</b>A buyer messages Ada’s Kitchen about a tray</li>
                            <li><b>Right answers</b>Hannah replies with Ada’s Kitchen’s menu and prices</li>
                            <li><b>One account</b>Switch between businesses from the same login</li>
                        </ol>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis center" aria-hidden="true">
                            <?= $msPhone('ig-ph-c', 'Ada’s Kitchen', [
                                ['out', 'Let me check with my husband and get back to you.'],
                                ['in', 'No problem, Ngozi 😊'],
                                ['in', 'Hi Ngozi! Just checking in. We still have space for your Saturday order. Want me to hold it for you?<span class="igp-btn">Yes, hold it</span><span class="igp-btn">Continue on WhatsApp</span>'],
                            ]) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>Follow up and close on WhatsApp</h3>
                            <p>Not every buyer pays on the first chat. Hannah checks back with people who go quiet and moves ready buyers over to WhatsApp, so the sale doesn’t get lost in Messenger.</p>
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
                <h2 class="sol-h2 center">Set up Messenger automation in 4 steps</h2>
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
                <h2 class="sol-h2 center">Messenger automation that lets you<br>sell on WhatsApp too</h2>
                <p class="center ig-lead">Many automation tools keep Messenger and WhatsApp in separate apps. <?= e($brandName) ?> brings every channel into one inbox, so your chats, customers and knowledge stay in one place.</p>
                <div class="sol-split">
                    <div class="ig-unified-list">
                        <?php foreach ($unified as [$title, $copy]): ?>
                            <div><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ig-vis uni" aria-hidden="true">
                        <?= $inbox(false, [
                            ['Kemi Ade', 'Is the weekend promo still on?', 'open', 'Ready to buy'],
                            ['Ngozi Eze', 'Thanks for the update!', 'pending', 'New'],
                            ['Bisi Ola', 'Can I change my order?', 'open', 'Paid'],
                        ], 'Kemi Ade', [
                            ['in', 'Hi, I saw your post. Is the weekend promo still on?'],
                            ['out', 'Yes! It ends on Sunday. The small chops tray is ₦15,300 with the discount.'],
                            ['note', 'Ready to buy · moved to WhatsApp'],
                        ]) ?>
                        <div class="ig-profile">
                            <div class="ig-profile-head"><?= $mark ?><span><b>Ada’s Kitchen</b><small>Your business</small></span></div>
                            <small class="h">Channels</small>
                            <span class="ig-ch"><?= sol_icon('messenger') ?>Messenger<em>Connected</em></span>
                            <span class="ig-ch"><?= sol_icon('whatsapp') ?>WhatsApp<em>Connected</em></span>
                            <span class="ig-ch"><?= sol_icon('instagram') ?>Instagram<em class="off">Not linked</em></span>
                            <small class="h">Labels</small>
                            <span class="ig-tags"><em>Ready to buy</em><em>Paid</em></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad ig-types">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Messenger automation for<br>every type of business</h2>
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
                <h2 class="sol-h2 center">FAQ: Messenger automation</h2>
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
                    <h2>Start turning your Messenger chats into sales today</h2>
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
