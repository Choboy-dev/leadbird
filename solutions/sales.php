<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = $brandName . ' for Sales | Sell at Scale in Your DMs';
$metaDescription = 'Reply to buyers instantly, talk price within your limits and follow up on time. ' . $brandName . '’s AI salesgirl helps you close more sales on WhatsApp, Instagram, Messenger and TikTok.';
$metaUrl = app_route('solutions/sales');
$extraStylesheets = ['solution.css?v=8', 'solution-sales.css?v=8'];
$activeNav = null;

$signupUrl = app_route('signup');
$markSrc = rtrim($appUrl, '/') . '/assets/images/leadbird-favicon.png';
$mark = '<img class="brand-mark" src="' . e($markSrc) . '" alt="" width="32" height="32">';

$photoBase = rtrim($appUrl, '/') . '/assets/images/solutions/';
$catalog = [
    ['Jollof rice tray', 'Serves 15 · smoky party jollof', '₦45,000', 'jollof-rice-tray.jpg'],
    ['Small chops box', '50 pieces · puff-puff, samosa, spring rolls', '₦18,000', 'small-chops-tray.jpg'],
    ['Fried rice tray', 'Serves 15 · with chicken', '₦42,000', 'fried-rice-tray.jpg'],
    ['Asun platter', 'Spicy grilled goat meat', '₦25,000', 'asun-platter.jpg'],
];

$speed = [
    ['Reply instantly, even at 2am', 'Hannah, your AI salesgirl, replies to buyers the moment they message, answers questions about your products, prices and delivery, and keeps the chat moving while you sleep.'],
    ['Talk price without losing your profit', 'Set the most discount she can give. Hannah handles “last price?” and objections inside your limit, and suggests add-ons to grow the order.'],
    ['Hand over to you when it matters', 'Get a WhatsApp alert when a buyer needs you, like a complaint or a big order, so you step in at the right moment.'],
    ['Follow up with buyers who go quiet', 'Hannah checks back with buyers who stopped replying, on your schedule, so a slow “I’ll get back to you” still turns into a sale.'],
];

$funnel = [
    'Buyer sends<br>a DM',
    'Instant reply<br>from Hannah',
    'Prices, questions<br>&amp; negotiation',
    'Follow-ups for<br>quiet buyers',
    'Payment details<br>&amp; receipt',
];

$visible = [
    ['Every conversation in one inbox', 'WhatsApp, Instagram, Messenger and TikTok chats land in one inbox, so you can read what Hannah said and reply yourself any time.', 'Every plan'],
    ['Know your customers', 'See every buyer in one list, with their name, channel and recent messages, and pick up where the last chat stopped.', 'Vendor plan and up'],
    ['See which channel sells', 'Compare message volume across Instagram, Messenger and WhatsApp so you know where to focus your time and posts.', 'Vendor plan and up'],
];

$flex = [
    ['Run more than one business', 'Sell food and fashion? Manage each business with its own channels, catalog and settings from one account. Vendor gives you 3, Shop gives you as many as you need.'],
    ['Train her once, sell everywhere', 'Teach Hannah from your website, documents and FAQs. She uses the same knowledge on every channel, so buyers get the same answer wherever they ask.'],
    ['Pay for what you use', 'Every plan comes with monthly tokens for Hannah’s replies. Busy month? Top up any time instead of jumping to a bigger plan.'],
];

$tags = [
    ['Close sales faster', 'Reply in seconds', 'Share your catalog', 'Answer “How much?”'],
    ['Negotiate within your limits', 'Suggest add-ons', 'Book consultations', 'Send payment details'],
    ['Follow up quiet buyers', 'Send payment receipts', 'Alert you on WhatsApp', 'Increase conversion rate'],
    ['Track every conversation', 'Compare your channels', 'Run more than one business'],
];

$fbArrow = static function (string $box, string $viewBox, string $d): string {
    static $n = 0;
    $id = 'fbHead' . (++$n);

    return '<svg class="fb-arr" style="' . $box . '" viewBox="' . $viewBox . '" aria-hidden="true"><defs><marker id="' . $id . '" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="4" markerHeight="4" orient="auto"><path d="M1 1L8 5L1 9"/></marker></defs><path d="' . $d . '" marker-end="url(#' . $id . ')"/></svg>';
};
$fbNode = static function (string $tone, string $class, string $title, string $body): string {
    return '<div class="fb-node ' . $tone . ' ' . $class . '"><div class="fb-head"><i class="fb-ic"></i>' . $title . '<span class="fb-dots"></span></div>' . $body . '</div>';
};

$phoneTop = '<div class="ph-top"><i class="ph-back"></i><i class="ph-ava"></i><i class="ph-name"></i><i class="ph-tick"></i><i class="ph-ic"></i></div>';

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol sls flex-1">

        <section class="sol-hero sls-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <h1>From one sale to thousands: sell at scale in your DMs</h1>
                    <p class="sol-p">Stop losing sales to slow replies, forgotten follow-ups and DMs you never saw. <?= e($brandName) ?> gives you an AI salesgirl who talks to every buyer on WhatsApp, Instagram, Messenger and TikTok and helps you close more sales.</p>
                    <div class="sol-btns">
                        <a class="sol-btn sls-hero-btn" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="#how-sales">See use cases</a>
                    </div>
                </div>

                <div class="shv" aria-hidden="true">
                    <span class="shv-glow"></span>
                    <div class="shv-stat a"><b>24/7</b><span>INSTANT<br>FIRST<br>REPLIES</span></div>
                    <div class="shv-stat b"><b>AUTO</b><span>PRICE TALKS<br>&amp; COLD-LEAD<br>FOLLOW-UPS</span></div>
                    <div class="shv-stat c"><b>1</b><span>INBOX FOR<br>EVERY<br>CHANNEL</span></div>
                    <div class="fb shv-app">
                        <div class="fb-top"><?= $mark ?><b><?= e($brandName) ?></b></div>
                        <div class="fb-canvas">
                            <?= $fbNode('blue', 'n1', 'Show catalog', '<span class="fb-bar"></span><span class="fb-bar"></span>') ?>
                            <?= $fbNode('pink', 'n2', 'Negotiate', '<span class="fb-bar"></span><span class="fb-bar"></span>') ?>
                            <?= $fbNode('yellow', 'n3', 'Ask a question', '<span class="fb-bar"></span><span class="fb-bar"></span>') ?>
                            <?= $fbNode('green', 'n4', 'Get paid', '<span class="fb-bar"></span><span class="fb-grid"><i></i><i></i><i></i><i></i><i></i></span>') ?>
                            <?= $fbArrow('left:0.5%;top:36%;width:9%', '0 0 30 50', 'M8 2 C0 18 2 40 26 46') ?><?= $fbArrow('left:44.5%;top:34%;width:9%', '0 0 30 57', 'M1 50 C18 50 10 6 28 6') ?><?= $fbArrow('left:70%;top:44%;width:9%', '0 0 30 25', 'M3 2 C12 4 22 10 22 23') ?>
                        </div>
                    </div>
                    <div class="shv-chat">
                        <div class="shv-chat-top"><i></i><span><b>Ada’s Kitchen</b><small>@adaskitchen</small></span></div>
                        <div class="shv-chat-body">
                            <span class="cp out">Hi, how much is the jollof rice tray for a party this Saturday?</span>
                            <span class="cp in">Hi! Our party tray serves 15 and costs ₦45,000.<br><br>Where should we deliver it?</span>
                            <span class="cp out">Lekki Phase 1</span>
                            <span class="cp in">Great, delivery to Lekki is ₦3,500. Shall I send our account details to confirm your order?</span>
                            <span class="cp out">Yes please</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php $toolSections = ['clients']; require dirname(__DIR__) . '/includes/tools/sections-bottom.php'; ?>

        <section class="sol-pad sls-winning">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Designed for sellers who want to grow</h2>
                <p class="sol-p center w-900">Answer every buyer, follow up on every lead and move every sale along, right inside the chat. <?= e($brandName) ?> learns your products and prices so you don’t have to repeat yourself.</p>

                <div class="sol-split sls-row">
                    <div>
                        <h3>Show your catalog right in the chat</h3>
                        <p>When a buyer asks what you have, Hannah answers with your real products, prices and photos from your <?= e($brandName) ?> catalog.</p>
                        <p>She points each buyer to what fits them and keeps the conversation going until they’re ready to pay.</p>
                    </div>
                    <div class="sls-visual outreach" aria-hidden="true">
                        <div class="ph sls-ph-a">
                            <?= $phoneTop ?>
                            <div class="ph-body"></div>
                        </div>
                        <div class="sls-offer">
                            <span>Hi Sandra! We have party trays for this weekend, and small chops boxes are ready same day. Want to see the menu?</span>
                            <b><svg viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>Yes, show me!</b>
                        </div>
                        <div class="sls-cat">
                            <div class="sls-cat-top"><span>Close</span><b>Catalog</b><i></i></div>
                            <div class="sls-cat-hero"><img src="<?= e($photoBase . 'jollof-rice-tray.jpg') ?>" alt="" loading="lazy"></div>
                            <b class="sls-cat-h">Party packs</b>
                            <?php foreach ($catalog as [$name, $detail, $price, $photo]): ?>
                                <div class="sls-cat-item"><img src="<?= e($photoBase . $photo) ?>" alt="" loading="lazy"><span><b><?= e($name) ?></b><small><?= e($detail) ?></small><small><?= e($price) ?></small></span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="sol-split sls-row reverse">
                    <div class="sls-visual booking" aria-hidden="true">
                        <div class="fb sls-book-app">
                            <div class="fb-top"><?= $mark ?><b><?= e($brandName) ?></b></div>
                            <div class="fb-canvas">
                                <?= $fbNode('indigo', 'b1', 'Buyer asks to book', '<span class="fb-row">Offer: 1-hour bridal makeup consultation</span><span class="fb-row">Hannah checks the offer details</span>') ?>
                                <?= $fbNode('orange', 'b2', 'Ask for a time', '<span class="fb-row">Which day and time works best for you?</span><span class="fb-row">[Buyer’s preferred slot]</span>') ?>
                                <?= $fbNode('indigo', 'b3', 'Check the details', '<span class="fb-row">Name, phone and preferred time collected</span><span class="fb-row">Hannah reads the time back to confirm</span>') ?>
                                <?= $fbNode('red', 'b4', 'Confirm booking', '<span class="fb-row">[Booking confirmed]</span><span class="fb-row">Saved for you to see</span>') ?>
                                <?= $fbArrow('left:0%;top:24%;width:8%', '0 0 30 90', 'M8 2 C0 30 2 70 26 86') ?><?= $fbArrow('left:44.5%;top:28%;width:11%', '0 0 35 97', 'M2 92 C22 92 12 6 33 6') ?><?= $fbArrow('left:74%;top:42%;width:9%', '0 0 29 43', 'M3 2 C16 4 24 16 22 41') ?>
                            </div>
                        </div>
                        <div class="ph sls-book-ph">
                            <?= $phoneTop ?>
                            <div class="ph-body">
                                <div class="ph-msg out"><div class="ph-b"><span>Hi! I want to book a makeup consultation.</span></div></div>
                                <div class="ph-msg in"><div class="ph-b"><span>Hi! Happy to help you book. Here’s how it works:<br>- Pick a day and time<br>- I’ll confirm it right here<br>- Come with your inspiration photos</span></div><span class="ph-btn">Book now</span><span class="ph-btn">See prices</span></div>
                                <div class="ph-msg out"><div class="ph-b"><span>Saturday at 11am works for me.</span></div></div>
                            </div>
                            <div class="ph-bar"><i>+</i><span></span><i class="ph-cam"></i></div>
                        </div>
                    </div>
                    <div>
                        <h3>Book consultations without the back-and-forth</h3>
                        <p>Let customers book a consultation right in the chat. Hannah asks for their preferred day and time, confirms the booking and saves it, so you just show up.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="sls-speed">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Speed up every step from DM to payment</h2>
                <div class="sol-split sls-speed-row">
                    <div class="sls-speed-copy">
                        <?php foreach ($speed as [$title, $copy]): ?>
                            <h3><?= e($title) ?></h3>
                            <p><?= e($copy) ?></p>
                        <?php endforeach; ?>
                    </div>
                    <div class="sls-funnel" aria-label="Sales funnel">
                        <?php foreach ($funnel as $i => $label): ?>
                            <div class="sls-fstep" style="--i:<?= $i ?>">
                                <svg viewBox="0 0 100 40" preserveAspectRatio="none" aria-hidden="true">
                                    <polygon class="sh" points="4,4 99,4 93,39 9,39"/>
                                    <polygon class="fg" points="1,1 96,1 90,36 6,36"/>
                                </svg>
                                <span><?= $label ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad sls-cards-sec">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Every sale, fully visible</h2>
                <p class="sol-p center">See what’s happening, from the first message to the payment</p>
                <div class="sls-cards">
                    <?php foreach ($visible as [$title, $copy, $badge]): ?>
                        <div class="sls-card pink">
                            <h3><?= e($title) ?></h3>
                            <p><?= e($copy) ?></p>
                            <span class="sls-soon"><?= e($badge) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad sls-crm">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">One AI salesgirl, connected to everything you sell with</h2>
                <?php require dirname(__DIR__) . '/includes/solutions/hub.php'; ?>
            </div>
        </section>

        <section class="sol-pad sls-flex">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Flexibility that helps you win</h2>
                <div class="sls-cards">
                    <?php foreach ($flex as [$title, $copy]): ?>
                        <div class="sls-card white">
                            <h3><?= e($title) ?></h3>
                            <p><?= e($copy) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad sls-how" id="how-sales">
            <div class="lk-wrap">
                <h2 class="sol-h2 center w-900">How can <?= e($brandName) ?> help your sales?</h2>
                <p class="sol-p center w-900">Build stronger relationships with buyers, reply to everyone personally and make more money from your DMs.</p>
                <div class="sls-tags">
                    <?php foreach ($tags as $row): ?>
                        <div class="sls-tag-row">
                            <?php foreach ($row as $tag): ?>
                                <span class="sls-tag"><?= e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <div class="sol-cta sls-cta">
                    <h3>Move sales forward and make more money from your DMs</h3>
                    <a class="sol-btn blue" href="<?= e($signupUrl) ?>">Get started</a>
                </div>
            </div>
        </section>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
