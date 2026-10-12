<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';
$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = 'WhatsApp Business API with an AI Salesgirl | ' . $brandName;
$metaDescription = 'Connect your WhatsApp Business number through Meta’s official Cloud API. ' . $brandName . '’s AI salesgirl replies to every customer, shares your catalog, sends payment details and books consultations, day and night.';
$metaUrl = app_route('channels/whatsapp');
$extraStylesheets = ['solution.css?v=8', 'product-whatsapp.css?v=9'];
$activeNav = null;

$signupUrl = app_route('signup');
$pricingUrl = app_route('pricing');
$contactUrl = app_route('contact');
$markSrc = rtrim($appUrl, '/') . '/assets/images/leadbird-favicon.png';
$mark = '<img class="brand-mark" src="' . e($markSrc) . '" alt="" width="32" height="32">';
$photoBase = rtrim($appUrl, '/') . '/assets/images/solutions/';

$phone = static function (array $msgs): string {
    $html = '<div class="ph api-ph" aria-hidden="true"><div class="ph-top"><i class="ph-back"></i><i class="ph-ava"></i><i class="ph-name"></i><i class="ph-tick"></i><i class="ph-ic"></i></div><div class="ph-body">';
    foreach ($msgs as $m) {
        $html .= '<div class="ph-msg ' . $m[0] . '"><div class="ph-b">' . $m[1] . '</div>' . (isset($m[2]) ? '<span class="ph-btn">' . $m[2] . '</span>' : '') . '</div>';
    }

    return $html . '</div><div class="ph-bar"><i>+</i><span></span><i class="ph-cam"></i></div></div>';
};

$helpCards = [
    ['pink', 'Support', '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2.5" y="13" width="4" height="6" rx="1.5"/><rect x="17.5" y="13" width="4" height="6" rx="1.5"/><path d="M20 19a3 3 0 0 1-3 3h-3"/>', 'Hannah answers questions about delivery, sizes, prices and orders in seconds, using what you taught her. When a customer needs a person, you get a WhatsApp alert and take over from the inbox.', 'Explore support', app_route('solutions/support')],
    ['yellow', 'Sales', '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>', 'Answer every “How much?” instantly, share your catalog with real photos, talk price within your limits and send your payment details so buyers can pay right away.', 'Explore sales', app_route('solutions/sales')],
    ['blue', 'Marketing', '<path d="M3 11v2a1 1 0 0 0 1 1h3l5 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M16 9a4 4 0 0 1 0 6"/><path d="M19 6a8 8 0 0 1 0 12"/>', 'Turn ad clicks into conversations. Hannah knows which ad a customer came from, follows up with buyers who go quiet, and free chat links and QR codes bring more people into WhatsApp.', 'Explore marketing', app_route('solutions/marketing')],
];

$stats = [
    ['100%', 'Official WhatsApp Business API'],
    ['24/7', 'AI replies, even at 2am'],
    ['0', 'Lines of code needed'],
    ['1', 'Inbox for WhatsApp, Instagram and Messenger'],
];

$compare = [
    ['Who it’s for', 'Businesses that get more chats than they can answer by hand', 'Solo owners who reply to every chat themselves'],
    ['Setup', 'Sign up, then connect your number with your Meta account in a few clicks', 'Install the free app and verify your number'],
    ['Replies', 'Hannah, your AI salesgirl, replies 24/7 in your tone, from your products, prices and FAQs', 'Greeting, away and quick replies you set up and send yourself'],
    ['Catalog &amp; payments', 'Shares your catalog with photos and prices, and sends your payment details in the chat', 'You share products and copy and paste your account details by hand'],
    ['Bookings', 'Collects the customer’s details, checks your Google Calendar and books the slot', 'You agree on a time in the chat and note it down yourself'],
    ['Follow-ups &amp; alerts', 'Follows up with buyers who go quiet and alerts you on WhatsApp when a chat needs you', 'You remember who to chase and when'],
    ['Channels', 'WhatsApp, Instagram and Messenger in one inbox', 'WhatsApp only'],
    ['Pricing model', 'Your ' . e($brandName) . ' plan, with monthly tokens for Hannah’s replies. Top up any time', 'Free (data charges only)'],
    ['Reliability &amp; scale', 'Runs in the cloud on Meta’s infrastructure, with no phone to keep online', 'Depends on your phone’s battery and connection'],
];

$steps = [
    ['pink', 'Sign up for ' . e($brandName), '<strong>Pick the plan that fits your business,</strong> from Starter to Shop. Every plan includes a WhatsApp number.'],
    ['yellow', 'Connect WhatsApp', '<strong>Log in with your Meta account</strong> and choose your WhatsApp Business number. Have your Meta Business account and phone number ready.'],
    ['blue', 'Teach Hannah and go live', '<strong>Add your products, prices and FAQs,</strong> and Hannah starts replying to your WhatsApp customers right away.'],
];

$catalog = [
    ['Gold sandals', 'Sizes 37–42', '₦28,000', 'gold-sandals.jpg'],
    ['Red evening gown', 'Sizes 8–16', '₦65,000', 'red-gown.jpg'],
    ['Shea body butter', '250ml jar', '₦7,500', 'shea-body-butter.jpg'],
    ['Small chops box', '50 pieces', '₦18,000', 'small-chops-tray.jpg'],
];

$guides = [
    ['blue', 'whatsapp', 'Guide', 'How to connect your WhatsApp Business number', app_route('help-center') . '?c=whatsapp'],
    ['green', 'mark', 'Guide', 'Train Hannah with your website, files and FAQs', app_route('help-center') . '?c=training'],
    ['pink', 'whatsapp', 'Free tool', 'Make a WhatsApp chat link for your bio', app_route('tools/chat-link-generator')],
    ['yellow', 'qr', 'Free tool', 'Print a QR code customers scan to message you', app_route('tools/qr-code-generator')],
    ['blue', 'mark', 'Guide', 'Set up follow-ups for buyers who go quiet', app_route('help-center') . '?c=follow-ups'],
    ['green', 'whatsapp', 'Free tool', 'Add a WhatsApp chat button to your website', app_route('tools/chat-button-generator')],
];
$qrIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM18 18h3v3h-3zM14 19h2M19 14h2"/></svg>';
require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol api flex-1">

        <section class="sol-hero api-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <h1>The WhatsApp Business API platform that turns chats into sales</h1>
                    <p class="sol-p">No coding needed. Connect your number through Meta’s official API, and Hannah, your AI salesgirl, replies to every customer, shares your catalog, sends payment details and books consultations, day and night.</p>
                    <div class="sol-btns">
                        <a class="sol-btn" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e($pricingUrl) ?>">See pricing</a>
                    </div>
                    <p class="api-note">*Every plan includes a WhatsApp number</p>
                    <div class="api-trust">
                        <span><?= sol_icon('whatsapp') ?>Official WhatsApp Business API</span>
                        <i></i>
                        <span><?= sol_icon('meta') ?>Built on Meta’s Cloud API</span>
                    </div>
                </div>

                <div class="api-hv" aria-hidden="true">
                    <span class="api-hv-badge"><?= sol_icon('whatsapp') ?></span>
                    <div class="api-dash">
                        <div class="api-dash-bar"><?= $mark ?><b><?= e($brandName) ?></b></div>
                        <div class="api-dash-body">
                            <div class="api-dash-title"><b>Inbox</b><em>AI on</em><span class="api-select"><i></i>WhatsApp ▾</span></div>
                            <div class="api-table"><span>Customer</span><span>Label</span></div>
                            <div class="api-table row"><span>Tolu A. · gold sandals</span><span class="api-btn">Add label →</span></div>
                            <ul class="api-pop">
                                <li><i class="lime"></i>Paid</li>
                                <li><i class="blue"></i>Ready to buy</li>
                                <li><i class="red"></i>Needs you</li>
                            </ul>
                        </div>
                    </div>
                    <?= str_replace('api-ph', 'api-hv-ph', $phone([
                        ['out', '<span>Hi! I saw your ad. Do you have the gold sandals in size 39?</span>'],
                        ['in', '<span>Yes! Size 39 is in stock for ₦28,000. Shall I send our payment details?</span>', 'Send payment details'],
                    ])) ?>
                </div>
            </div>
        </section>

        <?php
        $toolSections = ['clients'];
        $clientsHeading = '<strong>Growing sellers</strong> use ' . e($brandName) . ' to sell on WhatsApp';
        require dirname(__DIR__) . '/includes/tools/sections-bottom.php';
        ?>

        <section class="sol-pad api-what">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">What is the WhatsApp Business API?</h2>
                <p class="api-sub center">And why do you need it?</p>
                <div class="sol-split">
                    <div class="api-chat" aria-hidden="true">
                        <?= $phone([
                            ['out', '<span>Hi! Do you have the small chops box for Saturday?</span>'],
                            ['in', '<img class="ph-photo" src="' . e($photoBase . 'small-chops-tray.jpg') . '" alt="" loading="lazy"><span>Yes! A 50-piece box is ₦18,000, and delivery in Lagos is ₦2,500. Want one?</span>'],
                            ['out', '<span>Yes please, deliver to Yaba</span>'],
                            ['in', '<span>Done ✅ Pay ₦20,500 to Ada’s Kitchen, GTBank 0123456789, then send your receipt here.</span>'],
                        ]) ?>
                    </div>
                    <div>
                        <h3>The WhatsApp Business API unlocks the full power of WhatsApp for your business.</h3>
                        <p class="sol-p">Unlike the free WhatsApp Business app, the API runs in the cloud, so you don’t need a phone kept online. It lets <?= e($brandName) ?> reply to customers automatically, keep every chat in one inbox and handle many conversations at the same time.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad api-help">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">What can <?= e($brandName) ?> help you with?</h2>
                <div class="api-help-grid">
                    <?php foreach ($helpCards as [$tone, $title, $icon, $copy, $cta, $url]): ?>
                        <article class="api-help-card <?= $tone ?>">
                            <h3><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg><?= e($title) ?></h3>
                            <p><?= e($copy) ?></p>
                            <a class="api-help-btn" href="<?= e($url) ?>"><?= e($cta) ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="api-stats">
            <div class="lk-wrap">
                <?php foreach ($stats as [$num, $label]): ?>
                    <div><b><?= e($num) ?></b><span><?= e($label) ?></span></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="sol-pad api-diff-sec">
            <div class="lk-wrap">
                <div class="api-diff">
                    <h2 class="sol-h2 center">How is the WhatsApp Business API different?</h2>
                    <div class="api-cmp">
                        <div class="api-cmp-col labels">
                            <div class="api-cmp-head"></div>
                            <?php foreach ($compare as [$label]): ?><div class="api-cmp-cell"><?= $label ?></div><?php endforeach; ?>
                        </div>
                        <div class="api-cmp-col ours">
                            <div class="api-cmp-head">WhatsApp Business API<small>by <span><?= $mark ?><?= e($brandName) ?></span></small></div>
                            <?php foreach ($compare as [$label, $ours]): ?><div class="api-cmp-cell" data-label="<?= $label ?>"><?= $ours ?></div><?php endforeach; ?>
                        </div>
                        <div class="api-cmp-col app">
                            <div class="api-cmp-head">WhatsApp Business App</div>
                            <?php foreach ($compare as [$label, , $app]): ?><div class="api-cmp-cell" data-label="<?= $label ?>"><?= $app ?></div><?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad api-start">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">How to get started with <?= e($brandName) ?>’s<br>WhatsApp Business API platform</h2>
                <ol class="api-steps">
                    <?php foreach ($steps as $i => [$tone, $title, $copy]): ?>
                        <li class="<?= $tone ?>">
                            <h3><span><?= $i + 1 ?></span><?= $title ?></h3>
                            <p><?= $copy ?></p>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <section class="sol-pad api-feat-sec">
            <div class="lk-wrap">
                <div class="api-feat-box">
                    <h2 class="sol-h2 center">All the features you need, all in one place</h2>

                    <div class="sol-split api-feat">
                        <div class="api-vis ctwa" aria-hidden="true">
                            <div class="api-ad">
                                <div class="api-ad-head"><i></i><span><b>Ada’s Kitchen</b><small>Sponsored</small></span></div>
                                <img class="api-ad-img" src="<?= e($photoBase . 'jollof-rice-tray.jpg') ?>" alt="" loading="lazy">
                                <div class="api-ad-foot"><small>Lagos · same-week delivery</small><b>Party jollof trays</b></div>
                                <span class="api-ad-cta"><?= sol_icon('whatsapp') ?>Send message</span>
                            </div>
                            <svg class="api-swoosh" viewBox="0 0 120 80"><path d="M6 70 C30 10 80 6 112 30"/><path d="M100 18 L113 30 L97 36"/></svg>
                            <?= $phone([
                                ['out', '<span>I saw your ad. Is the party jollof tray available for Saturday?</span>'],
                                ['in', '<span>Hi, welcome to Ada’s Kitchen! Yes, our party tray serves 15 for ₦45,000.<br><br>Where should we deliver it?</span>', 'See the menu'],
                            ]) ?>
                        </div>
                        <div class="api-feat-copy">
                            <h3>Click-to-WhatsApp ads</h3>
                            <p>Turn ad clicks into sales. When someone taps “Send message” on your Facebook or Instagram ad, Hannah sees which ad they came from and replies about that exact product, instantly.</p>
                        </div>
                    </div>

                    <div class="sol-split api-feat flip">
                        <div class="api-vis catalog" aria-hidden="true">
                            <div class="api-win">
                                <div class="api-win-bar"><?= $mark ?><b><?= e($brandName) ?></b></div>
                                <div class="api-form">
                                    <b>Products &amp; offers</b>
                                    <label>Business<span>Tolu’s Closet</span></label>
                                    <small>What Hannah can show your customers</small>
                                    <div class="api-rcpt head"><i></i><span>Product</span><span>Price</span></div>
                                    <?php foreach ($catalog as [$name, $detail, $price, $img]): ?>
                                        <div class="api-rcpt"><img src="<?= e($photoBase . $img) ?>" alt="" loading="lazy"><span><?= e($name) ?><small><?= e($detail) ?></small></span><span class="ok"><?= e($price) ?></span></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?= $phone([
                                ['out', '<span>What shoes do you have for a wedding?</span>'],
                                ['in', '<img class="ph-photo" src="' . e($photoBase . 'gold-sandals.jpg') . '" alt=""><span>These gold sandals are a favourite for weddings, ₦28,000 in sizes 37–42 ✨</span>', 'See more styles'],
                            ]) ?>
                        </div>
                        <div class="api-feat-copy">
                            <h3>Your catalog, right in the chat</h3>
                            <p>Add your products and services once, with photos and prices. When a customer asks what you have, Hannah shows the right items and keeps the chat going until they’re ready to buy.</p>
                        </div>
                    </div>

                    <div class="sol-split api-feat">
                        <div class="api-vis book" aria-hidden="true">
                            <div class="api-promo">
                                <img class="api-promo-img" src="<?= e($photoBase . 'red-gown.jpg') ?>" alt="" loading="lazy">
                                <p><b>Gown fitting consultation</b></p>
                                <p>One hour to take your measurements and pick your fabric.</p>
                                <span>Book in WhatsApp</span>
                            </div>
                            <?= $phone([
                                ['out', '<span>I’d like to book a fitting for an evening gown.</span>'],
                                ['in', '<span>Lovely! Tuesday has 10am, 11am and 2pm free. Which works for you?</span>'],
                                ['out', '<span>11am please. I’m Funmi, 0803 123 4567</span>'],
                                ['in', '<span>You’re booked for Tuesday at 11am, Funmi. See you then!</span>'],
                            ]) ?>
                            <div class="api-thanks"><b>New booking</b>Funmi · Gown fitting<br>Tue, 11:00 · Added to Google Calendar</div>
                        </div>
                        <div class="api-feat-copy">
                            <h3>Bookings without the back-and-forth</h3>
                            <p>Hannah asks for the customer’s name, phone number and preferred time, checks your Google Calendar for free slots and books it, or sends your own booking link. You get an alert for every new booking.</p>
                        </div>
                    </div>

                    <div class="sol-split api-feat flip">
                        <div class="api-vis pay" aria-hidden="true">
                            <div class="api-win">
                                <div class="api-win-bar"><?= $mark ?><b><?= e($brandName) ?></b></div>
                                <div class="api-cat">
                                    <ul><li class="on">Payment details</li><li>Payment receipts</li><li>Owner alerts</li><li>Follow-ups</li></ul>
                                    <div>
                                        <b>Payment details</b>
                                        <em></em><em class="s"></em>
                                        <span class="api-cat-tag">✓ Saved</span>
                                        <p>Hannah sends these details when a buyer is ready to pay.</p>
                                        <span class="api-cat-btn">Save details</span>
                                    </div>
                                </div>
                            </div>
                            <?= $phone([
                                ['in', '<span>Here are our payment details:<br><b>GTBank · 0123 456 789</b><br>Ada’s Kitchen</span>'],
                                ['out', '<span>Done, I’ve paid ✅</span>'],
                                ['in', '<span class="ph-order"><img src="' . e($photoBase . 'jollof-rice-tray.jpg') . '" alt=""><span><b>Jollof rice tray</b><small>Quantity 1</small></span><b>₦45,000</b></span><span>Thank you! Here’s your receipt.</span>'],
                            ]) ?>
                        </div>
                        <div class="api-feat-copy">
                            <h3>Payments, receipts and owner alerts</h3>
                            <p>Save your account details once and Hannah sends them when a buyer is ready to pay. When they say they’ve paid, she sends a receipt, and you get a WhatsApp alert for big orders and chats that need you.</p>
                        </div>
                    </div>

                    <div class="sol-split api-feat">
                        <div class="api-vis bot" aria-hidden="true">
                            <div class="cbh">
                                <div class="cbh-win">
                                    <div class="cbh-bar"><span class="cbh-brand"><?= $mark ?><b><?= e($brandName) ?></b></span><i class="cbh-user"></i></div>
                                    <svg class="cbh-links" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M46 36 V46"/><path d="M58 30 H64"/><path d="M58 56 H64"/><path d="M80 42 V48"/></svg>
                                    <span class="cbh-spark a"></span><span class="cbh-spark b"></span>
                                    <div class="cbh-node ask"><b><i></i>Answer a question</b><em></em></div>
                                    <div class="cbh-node hook"><b><i></i>Check catalog</b><em></em><em></em><em></em></div>
                                    <div class="cbh-node send"><b><i></i>Send payment details</b><em class="w"></em><span class="cbh-cells"><em></em><em></em><em></em><em></em><em></em><em></em></span></div>
                                    <div class="cbh-node cond"><b><i></i>Alert the owner</b></div>
                                </div>
                                <div class="cbh-pal">
                                    <span class="green">Your products<i></i></span>
                                    <span class="yellow">Your FAQs<i></i></span>
                                    <span class="blue">Your tone<i></i></span>
                                </div>
                                <div class="cbh-phone">
                                    <div class="cbh-ph-head"><i></i><span><b>Brightpath Academy</b><small>Business account</small></span></div>
                                    <div class="cbh-ph-body">
                                        <span class="cbh-m in">Hi, I want to know more about your Grade 10 maths classes.</span>
                                        <span class="cbh-m out">Hi! Classes run on Saturdays, 10am to 12pm, for ₦35,000 a term.</span>
                                        <span class="cbh-m in">Can I pay in two parts?</span>
                                        <span class="cbh-m out">Yes, half now and half by week 4. Shall I send our account details?</span>
                                    </div>
                                    <div class="cbh-ph-input"><i></i>Message</div>
                                </div>
                            </div>
                        </div>
                        <div class="api-feat-copy">
                            <h3>An AI salesgirl, not a chatbot builder</h3>
                            <p>No flows to draw. Teach Hannah from your website, documents and FAQs once, and she answers in your tone, any time of day, on every channel.</p>
                            <a class="api-more" href="<?= e(app_route('help-center') . '?c=hannah') ?>">Meet Hannah →</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad api-int">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Connected to everything you sell with</h2>
                <p class="api-sub center">Your channels and tools, working together</p>
                <?php require dirname(__DIR__) . '/includes/solutions/hub.php'; ?>
            </div>
        </section>

        <?php $toolSections = ['testimonials']; require dirname(__DIR__) . '/includes/tools/sections-bottom.php'; ?>

        <section class="sol-pad api-guides">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Guides to help you get more from WhatsApp</h2>
                <div class="api-guide-grid">
                    <?php foreach ($guides as [$tone, $icon, $tag, $title, $url]): ?>
                        <a class="api-guide" href="<?= e($url) ?>">
                            <span class="api-guide-img <?= $tone ?>" aria-hidden="true"><?= $icon === 'mark' ? $mark : ($icon === 'qr' ? $qrIcon : sol_icon($icon)) ?></span>
                            <small><?= e($tag) ?></small>
                            <h3><?= e($title) ?></h3>
                            <span class="api-guide-more"><?= $tag === 'Free tool' ? 'Try it free' : 'Read the guide' ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad api-cta-sec">
            <div class="lk-wrap">
                <div class="api-cta">
                    <h2>Ready to sell on WhatsApp with <?= e($brandName) ?>?</h2>
                    <p>Get set up on your own, or talk to our team first.</p>
                    <div class="sol-btns">
                        <a class="sol-btn white" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e($contactUrl) ?>">Talk to us</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<script src="<?= e($appUrl) ?>/assets/js/testimonial-slider.js?v=1"></script>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
