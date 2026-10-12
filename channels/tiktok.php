<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = 'TikTok for Sellers | Plan, Publish & Caption TikToks with AI | ' . $brandName;
$metaDescription = 'Plan, edit and publish TikTok videos from one dashboard. ' . $brandName . ' writes your captions and hashtags, shows which videos perform, and its AI salesgirl replies to the buyers your TikToks send to WhatsApp and Instagram.';
$metaUrl = app_route('channels/tiktok');
$extraStylesheets = ['solution.css?v=8', 'product-instagram.css?v=3', 'product-tiktok.css?v=6'];
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

$heart = '<svg viewBox="0 0 24 24"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.7 4.5c2.1 0 3.5 1.1 5.3 3 1.8-1.9 3.2-3 5.3-3 3.7 0 5.8 3.9 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg>';
$bubble = '<svg viewBox="0 0 24 24"><path d="M12 3C6.5 3 2 6.8 2 11.5c0 2.6 1.4 4.9 3.6 6.5L5 21.5l4-2c.9.2 1.9.3 3 .3 5.5 0 10-3.8 10-8.3S17.5 3 12 3z"/></svg>';
$share = '<svg viewBox="0 0 24 24"><path d="M13 4v4C6 9 3 14 2 20c2.6-3.6 6-5.2 11-5.2V19l9-7.5z"/></svg>';
$ttVideo = static function (string $class, string $handle, string $photo, string $caption, array $counts, string $pill = '') use ($photoBase, $heart, $bubble, $share): string {
    $html = '<div class="ttv ' . $class . '"><img class="ttv-bg" src="' . e($photoBase . $photo) . '" alt="">'
        . '<div class="ttv-top"><span>Following</span><b>For You</b></div>'
        . '<div class="ttv-rail"><i class="ttv-ava"><img src="' . e($photoBase . $photo) . '" alt=""></i>'
        . '<span>' . $heart . e($counts[0]) . '</span><span>' . $bubble . e($counts[1]) . '</span><span>' . $share . e($counts[2]) . '</span></div>'
        . '<div class="ttv-cap"><b>@' . e($handle) . '</b><p>' . e($caption) . '</p>';
    if ($pill !== '') {
        $html .= '<span class="ttv-pill">' . $pill . '</span>';
    }

    return $html . '<small>♫ original sound · ' . e($handle) . '</small></div></div>';
};
$inbox = static function (array $chats, string $who, array $thread) use ($mark, $brandName): string {
    $html = '<div class="igi"><div class="igi-bar"><span class="igr-brand">' . $mark . '<b>' . e($brandName) . '</b></span><span class="igi-tabs"><em>All</em><em class="on">TikTok</em><em>WhatsApp</em></span></div><div class="igi-main">';
    $html .= '<ul class="igi-list"><li class="h">TikTok DMs</li>';
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
    ['1 place', 'Plan, edit and publish your TikToks next to your Instagram and Facebook posts, from one dashboard.'],
    ['Seconds', 'Hannah writes a caption and hashtags for your video, so you never stare at a blank box again.'],
    ['24/7', 'When a viewer messages you on WhatsApp or Instagram, Hannah answers right away, even at 2am.'],
];

$steps = [
    ['Connect your TikTok account', 'Log in with TikTok and approve ' . $brandName . '. No passwords are shared, and you can disconnect any time.'],
    ['Connect WhatsApp or Instagram', 'This is where your TikTok viewers will message you, and where Hannah replies and closes the sale.'],
    ['Upload your first video', 'Trim and crop it in the browser, then let Hannah write the caption and hashtags.'],
    ['Publish or schedule', 'Post now or pick a time. Then check Insights to see which videos people watch and share.'],
];

$unified = [
    ['TikTok next to Instagram and Facebook', 'Plan every post in one calendar. Publish the same video to TikTok, Instagram and Facebook without uploading it three times.'],
    ['Captions that know your products', 'Hannah writes captions from what you taught her, so prices, sizes and delivery details are right every time.'],
    ['Sales chats in one inbox', 'Viewers who message you on WhatsApp, Instagram or Messenger land in one inbox, where Hannah replies and you can step in.'],
    ['Start with TikTok, add channels any time', 'When you connect WhatsApp or Instagram, your catalog, knowledge and settings are already in place.'],
];

$types = [
    ['<path d="M14 6l6 3 6-3 6 5-4 4-2-1v18H14V14l-2 1-4-4z"/>', 'Fashion &amp; beauty: show off new arrivals', 'Post try-ons and new drops on TikTok, then let Hannah answer “Do you have my size?” and send payment details on WhatsApp.', 'industries/fashion-beauty'],
    ['<path d="M6 22h28a14 14 0 0 1-28 0zM4 32h32M20 12v4M14 13l1 3M26 13l-1 3"/>', 'Food vendors: make people hungry, then take orders', 'Share cooking clips and trays of the day. When viewers ask what’s on the menu or how much delivery costs, Hannah answers.', 'industries/food-vendors'],
    ['<rect x="7" y="9" width="26" height="23" rx="3"/><path d="M7 16h26M14 5v7M26 5v7M14 23l4 4 8-8"/>', 'Services &amp; bookings: turn views into appointments', 'Show before-and-after videos, then let Hannah check your Google Calendar and book the slot in the chat.', 'industries/services-bookings'],
    ['<path d="M20 32c-6-3-9-8-9-14 4 1 7 4 9 8 2-4 5-7 9-8 0 6-3 11-9 14z"/><path d="M20 26c-2-5-2-11 0-17 2 6 2 12 0 17zM6 32h28"/>', 'Health &amp; wellness: build trust with every video', 'Share tips and treatments on TikTok. Hannah explains your services, prices and opening hours to people who reach out.', 'industries/health-wellness'],
    ['<path d="M4 15 20 8l16 7-16 7z"/><path d="M11 18.5V26c5 4 13 4 18 0v-7.5M34 16v9"/>', 'Schools &amp; training: fill your next class', 'Post class clips and student wins. Hannah answers parents’ questions about fees and start dates and sends the account details.', 'industries/schools-training'],
    ['<path d="M6 13h28v5a3 3 0 0 0 0 6v5H6v-5a3 3 0 0 0 0-6z"/><path d="M24 13v16"/>', 'Events &amp; travel: sell out with short videos', 'Tease your next event or trip on TikTok, then let Hannah share prices, dates and your ticket link in the DMs.', 'industries/events-travel'],
];

$faqs = [
    ['What does ' . $brandName . ' do on TikTok?', 'You can connect your TikTok account, edit videos, let Hannah write your captions and hashtags, publish or schedule posts, and see how each video performs. Buyers who reach out on WhatsApp or Instagram get instant replies from Hannah.'],
    ['Will Hannah reply to my TikTok DMs?', 'Yes, TikTok DM replies are coming soon. Hannah will answer your TikTok DMs the same way she does on WhatsApp and Instagram, from what you taught her. Until then, add a WhatsApp chat link to your TikTok bio, and she will answer every buyer who taps it. Comment replies are not supported.'],
    ['Which plans include TikTok publishing?', 'TikTok publishing is part of the social media planner on the Vendor and Shop plans. Every plan can connect WhatsApp, Instagram and Messenger for Hannah’s replies.'],
    ['Is it safe for my TikTok account?', 'Yes. You log in with TikTok and approve access through TikTok’s official API. ' . $brandName . ' never sees your password, and you can disconnect from your dashboard any time.'],
    ['Can I post photos or carousels to TikTok?', 'Not yet. ' . $brandName . ' publishes videos to TikTok. You can still post photos and carousels to Instagram and Facebook from the same planner.'],
    ['Do AI captions cost extra?', 'They come out of your plan’s monthly tokens. A caption uses ' . TokenCosts::SOCIAL_CAPTION . ' tokens, and hashtag ideas and publishing a post use ' . TokenCosts::SOCIAL_HASHTAGS . ' token each. You can top up any time.'],
    ['Can I choose who sees my video?', 'Yes. Pick Everyone, Friends or Only me, and turn comments, Duet and Stitch on or off, just like in the TikTok app.'],
];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol ig tt flex-1">

        <section class="sol-hero ig-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <h1>TikTok for sellers<br>Turn views into sales</h1>
                    <p class="sol-p">Plan, edit and publish your TikToks from one dashboard. Hannah, your AI salesgirl, writes your captions and hashtags, and replies to every viewer who messages you on WhatsApp or Instagram.</p>
                    <div class="sol-btns">
                        <a class="sol-btn" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e($pricingUrl) ?>">See pricing</a>
                    </div>
                    <div class="ig-chips">
                        <span><?= sol_icon('tiktok') ?>Official TikTok API</span>
                        <span><?= sol_icon('whatsapp') ?>Viewers reply on WhatsApp</span>
                        <span>No code needed</span>
                    </div>
                </div>

                <div class="ig-hv" aria-hidden="true">
                    <span class="ig-ring r1"></span><span class="ig-ring r2"></span><span class="ig-ring r3"></span>
                    <span class="ig-hv-ava"><img src="<?= e($photoBase . 'gold-sandals.jpg') ?>" alt=""></span>
                    <span class="ig-hv-logo"><?= sol_icon('tiktok') ?></span>
                    <span class="ig-hv-icon a"><svg viewBox="0 0 24 24"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.7 4.5c2.1 0 3.5 1.1 5.3 3 1.8-1.9 3.2-3 5.3-3 3.7 0 5.8 3.9 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg></span>
                    <span class="ig-hv-icon b"><svg viewBox="0 0 24 24"><path d="M4 12a8 8 0 1 1 4 6.9L4 20l1.2-3.8A8 8 0 0 1 4 12z"/></svg></span>
                    <div class="ig-hv-card one">✍️ <b>Hannah</b> wrote your caption and 5 hashtags</div>
                    <div class="ig-hv-card two">📅 Posted to TikTok at 6:00pm ✓</div>
                    <?= $ttVideo('tt-hv-ph', 'tolus.closet', 'gold-sandals.jpg', 'New gold sandals just landed ✨ Sizes 37–42, ₦28,000. Tap the link in our bio to order 💛 #lagosfashion #naijashoes', ['12.4K', '386', '1.1K'], sol_icon('whatsapp') . 'Chat on WhatsApp') ?>
                </div>
            </div>
        </section>

        <?php
        $toolSections = ['clients'];
        $clientsHeading = '<strong>Sellers who sell on social</strong> use ' . e($brandName) . ' for TikTok';
        require dirname(__DIR__) . '/includes/tools/sections-bottom.php';
        ?>

        <section class="sol-pad ig-gap-sec">
            <div class="lk-wrap">
                <div class="ig-gap">
                    <h2 class="sol-h2 center">Your TikToks get views<br>Make them bring in sales</h2>
                    <p class="center">Posting every day is hard, and views don’t pay the bills on their own.<br><?= e($brandName) ?> helps you post more often and answers the buyers your videos send your way.</p>
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
                <h2 class="sol-h2 center">Everything you need to sell<br>with TikTok</h2>

                <article class="ig-feat pink">
                    <div class="sol-split">
                        <div class="ig-copy">
                            <h3>Plan and publish your TikToks</h3>
                            <p>Upload your video once, choose where it goes and when. Post to TikTok now, or schedule it for the time your buyers are scrolling, and send the same video to Instagram and Facebook too.</p>
                            <?= $list([
                                'Publish to TikTok now or schedule for later',
                                'Send the same video to Instagram and Facebook',
                                'Save drafts and finish them when you’re ready',
                                'See every scheduled and published post in one list',
                                'On the Vendor and Shop plans',
                            ]) ?>
                        </div>
                        <div class="ig-vis tt-plan" aria-hidden="true">
                            <div class="ttc">
                                <div class="igr-bar"><span class="igr-brand"><?= $mark ?><b><?= e($brandName) ?></b></span><small>New post</small></div>
                                <div class="ttc-body">
                                    <div class="ttc-media"><img src="<?= e($photoBase . 'red-gown.jpg') ?>" alt=""><span>0:18</span></div>
                                    <div class="ttc-text">
                                        <small>Caption</small>
                                        <p>Our red evening gown is back in sizes 8–16 ❤️ ₦65,000. Link in bio to order.</p>
                                        <span class="ttc-ai">✨ Write with AI</span>
                                    </div>
                                </div>
                                <div class="ttc-row"><small>Post to</small><span class="ttc-ch on"><?= sol_icon('tiktok') ?>TikTok</span><span class="ttc-ch on"><?= sol_icon('instagram') ?>Instagram</span><span class="ttc-ch"><?= sol_icon('facebook') ?>Facebook</span></div>
                                <div class="ttc-row"><small>When</small><span class="ttc-when">📅 Friday, 6:00pm</span><b class="ttc-go">Schedule</b></div>
                            </div>
                            <div class="ttq">
                                <div class="ig-c-head">This week <i>▾</i></div>
                                <div class="ttq-i"><?= sol_icon('tiktok') ?><span><b>Red gown restock</b><small>Fri, 6:00pm</small></span><em>Scheduled</em></div>
                                <div class="ttq-i"><?= sol_icon('tiktok') ?><span><b>Gold sandals try-on</b><small>Wed, 7:30pm</small></span><em class="done">Published</em></div>
                                <div class="ttq-i"><?= sol_icon('tiktok') ?><span><b>Packing orders</b><small>No date yet</small></span><em class="draft">Draft</em></div>
                            </div>
                        </div>
                    </div>
                    <div class="ig-how">
                        <h4>How it works:</h4>
                        <ol>
                            <li><b>Upload your video</b>From your phone or computer</li>
                            <li><b>Pick your channels</b>TikTok, Instagram, Facebook</li>
                            <li><b>Choose a time</b>Post now or schedule it</li>
                            <li><b>It goes live</b>Track it in your post list</li>
                        </ol>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis story" aria-hidden="true">
                            <?= $ruleCard('ig-rule-b', 'Hannah writes your caption', [
                                ['pink', 'You upload', 'A video of your product', ['Gold sandals']],
                                ['blue', 'She checks', 'What you taught her', ['Price', 'Sizes', 'Delivery']],
                                ['red', 'Then writes', 'A caption and hashtags', ['#lagosfashion', '#naijashoes']],
                            ]) ?>
                            <?= $ttVideo('tt-ph-r', 'tolus.closet', 'gold-sandals.jpg', 'Gold sandals in sizes 37–42 ✨ ₦28,000 with delivery across Lagos. Tap the link in bio 💛 #lagosfashion #naijashoes', ['8.2K', '214', '640']) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>AI writes your captions and hashtags</h3>
                            <p>Stuck on what to say? Hannah writes a caption that sounds like you, using the products, prices and delivery details you taught her. Add hashtags in one tap, then edit anything before you post.</p>
                            <?= $list([
                                'Captions in your tone, with the right prices and sizes',
                                'Hashtags picked for your video in one tap',
                                'Ask her to make it more engaging, try new tones or fix the spelling',
                                'Edit everything before it goes live',
                                'Uses ' . TokenCosts::SOCIAL_CAPTION . ' tokens per caption from your monthly allowance',
                            ]) ?>
                        </div>
                    </div>
                </article>

                <article class="ig-feat pink">
                    <div class="sol-split">
                        <div class="ig-copy">
                            <h3>Trim and crop in your browser</h3>
                            <p>No extra editing app needed. Cut your video to the best part and crop it to TikTok’s tall 9:16 size right before you post.</p>
                            <?= $list([
                                'Trim the start and end of your video',
                                'Crop to 9:16, 4:5, 1:1 or 16:9',
                                'Preview it before you publish',
                                'Works on your phone and computer',
                            ]) ?>
                        </div>
                        <div class="ig-vis center" aria-hidden="true">
                            <div class="tte">
                                <div class="igr-bar"><span class="igr-brand"><?= $mark ?><b>Edit video</b></span><b class="ttc-go">Done</b></div>
                                <div class="tte-stage">
                                    <img src="<?= e($photoBase . 'shea-body-butter.jpg') ?>" alt="">
                                    <span class="tte-crop"></span>
                                </div>
                                <div class="tte-ratios"><em class="on">9:16</em><em>4:5</em><em>1:1</em><em>16:9</em></div>
                                <div class="tte-trim"><span class="tte-sel"><i></i><i></i></span></div>
                                <small class="tte-time">0:03 – 0:21 · 18 seconds</small>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis center" aria-hidden="true">
                            <div class="tts">
                                <div class="igr-bar"><span class="igr-brand"><?= sol_icon('tiktok') ?><b>TikTok settings</b></span></div>
                                <div class="tts-body">
                                    <small>Who can watch this video</small>
                                    <span class="tts-pick">Everyone<i>▾</i></span>
                                    <span class="tts-t on">Allow comments<i></i></span>
                                    <span class="tts-t on">Allow Duet<i></i></span>
                                    <span class="tts-t">Allow Stitch<i></i></span>
                                    <p>✓ Matches the settings on your TikTok account</p>
                                </div>
                            </div>
                        </div>
                        <div class="ig-copy">
                            <h3>You choose who sees it</h3>
                            <p>Every TikTok you publish follows your rules. Pick who can watch, and turn comments, Duet and Stitch on or off, just like you would in the TikTok app.</p>
                            <?= $list([
                                'Post to Everyone, Friends or Only me',
                                'Turn comments on or off per video',
                                'Allow or block Duet and Stitch',
                                'Uses the options your TikTok account allows',
                            ]) ?>
                        </div>
                    </div>
                </article>

                <article class="ig-feat pink">
                    <div class="sol-split">
                        <div class="ig-copy">
                            <h3>See which videos work</h3>
                            <p>Know what your buyers like to watch. See views, likes, comments and shares for every TikTok you post, next to your Instagram and Facebook posts.</p>
                            <?= $list([
                                'Views, likes, comments and shares for each video',
                                'Your TikToks next to your Instagram and Facebook posts',
                                'Spot the products people engage with most',
                                'Post more of what works',
                            ]) ?>
                        </div>
                        <div class="ig-vis center" aria-hidden="true">
                            <div class="tti">
                                <div class="igr-bar"><span class="igr-brand"><?= $mark ?><b>Insights</b></span><small>Last 30 days</small></div>
                                <div class="tti-head"><span></span><small>Views</small><small>Likes</small><small>Shares</small></div>
                                <?php foreach ([['gold-sandals.jpg', 'Gold sandals try-on', '12.4K', '1.8K', '1.1K', 100], ['red-gown.jpg', 'Red gown restock', '8.9K', '1.2K', '520', 72], ['shea-body-butter.jpg', 'Shea butter routine', '5.1K', '640', '210', 41]] as [$img, $title, $views, $likes, $shares, $bar]): ?>
                                    <div class="tti-row">
                                        <span class="tti-v"><img src="<?= e($photoBase . $img) ?>" alt=""><b><?= e($title) ?><i style="--w: <?= (int) $bar ?>%"></i></b></span>
                                        <b><?= e($views) ?></b><span><?= e($likes) ?></span><span><?= e($shares) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis story" aria-hidden="true">
                            <div class="ttp">
                                <div class="ttp-head"><i class="ttv-ava"><img src="<?= e($photoBase . 'red-gown.jpg') ?>" alt=""></i><b>@tolus.closet</b></div>
                                <div class="ttp-stats"><span><b>212</b>Following</span><span><b>48.6K</b>Followers</span><span><b>390K</b>Likes</span></div>
                                <p>Gowns &amp; sandals in Lagos 🇳🇬 Delivery nationwide</p>
                                <span class="ttp-link"><?= sol_icon('whatsapp') ?>wa.me/2348031234567</span>
                                <div class="ttp-grid">
                                    <?php foreach (['red-gown.jpg', 'gold-sandals.jpg', 'shea-body-butter.jpg'] as $img): ?>
                                        <img src="<?= e($photoBase . $img) ?>" alt="">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?= $igPhone('ig-ph-r tt-wa', 'Tolu’s Closet', [
                                ['out', 'Hi! I saw your TikTok. Is the red gown available in size 12?'],
                                ['in', 'Hi Amaka 👋 Yes, size 12 is in stock for ₦65,000. Want me to send our payment details?<span class="igp-btn">Send payment details</span>'],
                            ]) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>Turn viewers into buyers in the DMs</h3>
                            <p>Put a WhatsApp chat link in your TikTok bio. When a viewer taps it, Hannah replies in seconds, answers their questions, sends your payment details and alerts you when a buyer needs you.</p>
                            <?= $list([
                                'Make a free WhatsApp chat link for your bio',
                                'Hannah replies on WhatsApp and Instagram, 24/7',
                                'Shows products, talks price within your limits and sends payment details',
                                'Follows up with buyers who go quiet (Shop plan)',
                            ]) ?>
                            <a class="tt-tool" href="<?= e(app_route('tools/chat-link-generator')) ?>"><?= sol_icon('whatsapp') ?>Make your free WhatsApp link</a>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="sol-pad tt-dm">
            <div class="lk-wrap">
                <div class="tt-soon-wrap"><span class="tt-soon">Coming soon</span></div>
                <h2 class="sol-h2 center">Hannah in your TikTok DMs</h2>
                <p class="center ig-lead">Soon Hannah will answer the people who message you on TikTok, right inside TikTok. She replies in seconds, for any type of business, and sells for you while you sleep.</p>

                <article class="ig-feat pink">
                    <div class="sol-split">
                        <div class="ig-copy">
                            <h3>Instant replies for any business</h3>
                            <p>Whether you sell clothes, food, beauty services or classes, every TikTok DM gets a fast, friendly answer. Hannah uses what you taught her, so prices, sizes, menus and opening hours are always right.</p>
                            <?= $list([
                                'Replies to every TikTok DM in seconds, 24/7',
                                'Answers from your products, prices, FAQs and policies',
                                'Works for fashion, food, services, health, schools and events',
                                'Sounds like you, in the tone you pick',
                                'Hands the chat to you when a customer needs a person',
                            ]) ?>
                        </div>
                        <div class="ig-vis story" aria-hidden="true">
                            <?= $ruleCard('ig-rule-b', 'Hannah answers any business', [
                                ['pink', 'Fashion', '“Is this in size 12?”', ['Sizes', 'Price']],
                                ['blue', 'Food', '“Do you deliver to Lekki?”', ['Menu', 'Delivery fee']],
                                ['red', 'Services', '“Are you free on Saturday?”', ['Booking']],
                            ]) ?>
                            <?= $igPhone('ig-ph-r', 'adas.kitchen', [
                                ['out', 'Hi! Saw your TikTok 😍 Do you deliver to Lekki?'],
                                ['in', 'Hi Bisi 👋 Yes! Delivery to Lekki is ₦3,000, and today’s jollof rice tray is ₦45,000. Want to order?<span class="igp-btn">See today’s menu</span>'],
                            ]) ?>
                        </div>
                    </div>
                </article>

                <article class="ig-feat blue">
                    <div class="sol-split flip">
                        <div class="ig-vis center" aria-hidden="true">
                            <?= $inbox([
                                ['Amaka O.', 'Size 40 please', 'open', 'Buying'],
                                ['Chidi E.', 'Do you have size 42?', 'pending', 'Asked'],
                                ['Funke A.', 'Thank you! Received 🙏', 'solved', 'Paid'],
                            ], 'Amaka O.', [
                                ['in', 'Saw your TikTok! How much are the gold sandals?'],
                                ['out', 'They’re ₦28,000, sizes 37–42 ✨ Which size?'],
                                ['in', 'Size 40 please'],
                                ['out', 'Great choice! Here are our payment details. Send your receipt here once you pay 💛'],
                                ['note', '✓ Receipt received · You’ve been alerted'],
                            ]) ?>
                        </div>
                        <div class="ig-copy">
                            <h3>Sell right inside the DM</h3>
                            <p>Your viewers won’t need to leave TikTok to buy. Hannah shows the product, answers questions, talks price within your limits, sends your payment details and lets you know when an order comes in.</p>
                            <?= $list([
                                'Shares products and prices from your catalog',
                                'Talks price only within the limits you set',
                                'Sends your bank or payment details when the buyer is ready',
                                'Checks payment receipts and alerts you about new orders',
                                'Follows up with buyers who go quiet (Shop plan)',
                            ]) ?>
                        </div>
                    </div>
                    <div class="ig-how">
                        <h4>How it will work:</h4>
                        <ol>
                            <li><b>A viewer DMs you</b>After watching your TikTok</li>
                            <li><b>Hannah answers</b>Price, sizes, delivery and more</li>
                            <li><b>She closes the sale</b>Sends your payment details</li>
                            <li><b>You get the order</b>With an alert in your inbox</li>
                        </ol>
                    </div>
                </article>
            </div>
        </section>

        <section class="sol-pad ig-setup">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Get started with TikTok in 4 steps</h2>
                <p class="center ig-lead">No developer needed. Go from sign-up to your first scheduled TikTok fast.</p>
                <ol class="ig-steps">
                    <?php foreach ($steps as $i => [$title, $copy]): ?>
                        <li><span><?= $i + 1 ?></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <section class="sol-pad ig-unified">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Post on TikTok, sell on WhatsApp<br>all from one place</h2>
                <p class="center ig-lead">Most tools stop at posting. <?= e($brandName) ?> also answers the buyers your videos bring in, so your content, chats and customers stay together.</p>
                <div class="sol-split">
                    <div class="ig-unified-list">
                        <?php foreach ($unified as [$title, $copy]): ?>
                            <div><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ig-vis uni" aria-hidden="true">
                        <div class="ttk">
                            <div class="igr-bar"><span class="igr-brand"><?= $mark ?><b>Planner</b></span><span class="igi-tabs"><em>Week</em><em class="on">Month</em></span></div>
                            <div class="ttk-grid">
                                <?php
                                $planned = [3 => 'tiktok', 5 => 'instagram', 8 => 'tiktok', 10 => 'facebook', 12 => 'tiktok', 15 => 'instagram', 17 => 'tiktok', 19 => 'tiktok', 22 => 'facebook', 24 => 'tiktok', 26 => 'instagram'];
                                foreach (['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $d): ?>
                                    <small><?= $d ?></small>
                                <?php endforeach;
                                for ($day = 1; $day <= 28; $day++): ?>
                                    <span<?= $day === 12 ? ' class="on"' : '' ?>><?= $day ?><?= isset($planned[$day]) ? sol_icon($planned[$day]) : '' ?></span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="ig-profile">
                            <div class="ig-profile-head"><?= $mark ?><span><b>Tolu’s Closet</b><small>Your business</small></span></div>
                            <small class="h">Channels</small>
                            <span class="ig-ch"><?= sol_icon('tiktok') ?>TikTok<em>Connected</em></span>
                            <span class="ig-ch"><?= sol_icon('whatsapp') ?>WhatsApp<em>Connected</em></span>
                            <span class="ig-ch"><?= sol_icon('instagram') ?>Instagram<em>Connected</em></span>
                            <small class="h">This month</small>
                            <span class="ig-tags"><em>11 posts planned</em></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad ig-types">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">TikTok for every type<br>of business</h2>
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
                <h2 class="sol-h2 center">FAQ: TikTok with <?= e($brandName) ?></h2>
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
                    <h2>Start turning your TikTok views into sales today</h2>
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
