<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';
$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = 'Bulk WhatsApp Messaging for Small Businesses | ' . $brandName;
$metaDescription = 'Send bulk WhatsApp messages to all your customers from your own business number, using Meta’s official API. ' . $brandName . '’s AI salesgirl answers every reply.';
$metaUrl = app_route('features/bulk-whatsapp');
$extraStylesheets = ['solution.css?v=8', 'product-whatsapp.css?v=9', 'product-bulk-whatsapp.css?v=6'];
$activeNav = null;

$signupUrl = app_route('signup');
$markSrc = rtrim($appUrl, '/') . '/assets/images/leadbird-favicon.png';
$mark = '<img class="brand-mark" src="' . e($markSrc) . '" alt="" width="32" height="32">';
$photoBase = rtrim($appUrl, '/') . '/assets/images/solutions/';
$footer = WhatsAppBroadcastService::FOOTER;

$phone = static function (array $msgs): string {
    $html = '<div class="ph api-ph" aria-hidden="true"><div class="ph-top"><i class="ph-back"></i><i class="ph-ava"></i><i class="ph-name"></i><i class="ph-tick"></i><i class="ph-ic"></i></div><div class="ph-body">';
    foreach ($msgs as $m) {
        $html .= '<div class="ph-msg ' . $m[0] . '"><div class="ph-b">' . $m[1] . '</div>' . (isset($m[2]) ? '<span class="ph-btn">' . $m[2] . '</span>' : '') . '</div>';
    }

    return $html . '</div><div class="ph-bar"><i>+</i><span></span><i class="ph-cam"></i></div></div>';
};
$broadcastBubble = static fn (string $text): string => '<span>' . $text . '</span><small class="bw-ph-foot">' . e($footer) . '</small>';

$helpCards = [
    ['pink', 'New stock', '<path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>', 'Tell everyone the moment new styles, fresh batches or restocks land, before they sell out. Customers reply to reserve, and Hannah takes it from there.'],
    ['yellow', 'Sales & promos', '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="8" cy="8" r="1.5"/>', 'Announce a weekend sale, a holiday deal or a discount code to every customer at once. Each message greets them by name, so it reads like you wrote it to them.'],
    ['blue', 'Updates', '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>', 'Share opening hours, delivery days, class schedules or event reminders. Useful updates keep customers coming back without you sending one message at a time.'],
];

$stats = [
    ['100%', 'Official WhatsApp Business API'],
    ['50,000', 'Contacts on the Shop plan'],
    ['24/7', 'Hannah answers every reply'],
    ['1', 'Token per person you message'],
];

$compare = [
    ['How it sends', 'Meta’s official WhatsApp Business API', 'Unofficial apps, browser add-ons or your own phone'],
    ['Your number', 'Safe. Meta allows this way of sending, so your number isn’t banned for using it', 'Unofficial senders break WhatsApp’s rules, and numbers often get banned'],
    ['Approved by Meta', 'Yes. We are a Meta Business Partner and an approved Meta Tech Provider', 'Most unofficial senders are not'],
    ['Who gets it', 'Everyone on your list who agreed to hear from you', 'Only people who saved your number, or bought lists'],
    ['List size', 'Up to 5,000 contacts on Vendor and 50,000 on Shop', 'Often 256 contacts per broadcast list'],
    ['Your message', 'A template Meta approves once, greeting each customer by name', 'The same text to everyone, sent from your phone'],
    ['Replies', 'Hannah answers every reply in your tone, day and night', 'You reply to each person yourself'],
    ['Tracking', 'Sent, delivered, read and failed counts for every broadcast', 'Little or no delivery tracking'],
    ['Opt-outs', 'Anyone who replies STOP is removed automatically', 'You remove people by hand'],
    ['Cost', 'Meta bills your card per message, plus 1 ' . e($brandName) . ' token per person', 'Free lists, or a monthly fee for unofficial senders'],
];

$steps = [
    ['pink', 'Connect your WhatsApp', '<strong>Log in with your Meta account</strong> and pick your WhatsApp Business number. Then add a card in Meta billing, since Meta charges a small fee per message.'],
    ['yellow', 'Create a template', '<strong>Write your message or let Hannah draft it,</strong> and send it to Meta for approval from ' . e($brandName) . '. Approval usually takes minutes.'],
    ['blue', 'Add contacts and send', '<strong>Import customers from your inbox or paste your list,</strong> pick your approved template and see the tokens it uses and Meta’s charge before you send it to everyone.'],
];

$contacts = [
    ['Ada Obi', '+234 803 123 4567', true],
    ['Tunde Bello', '+234 805 765 4321', true],
    ['Chioma Eze', '+234 802 555 0192', true],
    ['Musa Ibrahim', '+234 813 440 2218', false],
    ['Funmi Ade', '+234 816 900 3371', true],
];

$guides = [
    ['blue', 'whatsapp', 'Guide', 'How to connect your WhatsApp Business number', app_route('help-center') . '?c=whatsapp'],
    ['pink', 'whatsapp', 'Free tool', 'Make a WhatsApp chat link so more people join your list', app_route('tools/chat-link-generator')],
    ['yellow', 'qr', 'Free tool', 'Print a QR code customers scan to message you', app_route('tools/qr-code-generator')],
];
$qrIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM18 18h3v3h-3zM14 19h2M19 14h2"/></svg>';
require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol api bw flex-1">

        <section class="sol-hero api-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <h1>Send bulk WhatsApp messages to all your customers</h1>
                    <p class="sol-p">Announce new stock, sales and updates to everyone who agreed to hear from you, from your own WhatsApp Business number. When they reply, Hannah, your AI salesgirl, answers and closes the sale.</p>
                    <div class="sol-btns">
                        <a class="sol-btn" href="<?= e($signupUrl) ?>">Start sending</a>
                    </div>
                    <p class="api-note">*Available on the Vendor and Shop plans</p>
                    <div class="api-trust">
                        <span><?= sol_icon('whatsapp') ?>Official WhatsApp Business API</span>
                        <i></i>
                        <span><?= sol_icon('meta') ?>Approved by Meta</span>
                    </div>
                </div>

                <div class="api-hv" aria-hidden="true">
                    <span class="api-hv-badge"><?= sol_icon('whatsapp') ?></span>
                    <div class="api-dash">
                        <div class="api-dash-bar"><?= $mark ?><b><?= e($brandName) ?></b></div>
                        <div class="api-dash-body bw-dash-body">
                            <div class="api-dash-title"><b>Bulk WhatsApp Messaging</b><em>Sending</em></div>
                            <div class="bw-hv-name"><b>Weekend ankara sale</b><small>1,240 contacts</small></div>
                            <div class="bw-hv-bar"><i style="width: 72%"></i></div>
                            <div class="bw-hv-stats">
                                <span><b>1,240</b>Sent</span>
                                <span><b>1,198</b>Delivered</span>
                                <span><b>893</b>Read</span>
                            </div>
                        </div>
                    </div>
                    <?= str_replace('api-ph', 'api-hv-ph', $phone([
                        ['in', $broadcastBubble('Hi Ada 👋 Our weekend ankara sale starts now. 20% off every gown until Sunday!')],
                        ['out', '<span>Do you have the green one in size 12?</span>'],
                        ['in', '<span>Yes! Size 12 is in stock for ₦52,000 after the discount. Shall I reserve it?</span>'],
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
                <h2 class="sol-h2 center">What is bulk WhatsApp messaging?</h2>
                <p class="api-sub center">And how is it different from other bulk WhatsApp messaging tools that are flagged as spam and get your number blocked?</p>
                <div class="sol-split">
                    <div class="api-chat" aria-hidden="true">
                        <?= $phone([
                            ['in', '<img class="ph-photo" src="' . e($photoBase . 'red-gown.jpg') . '" alt="" loading="lazy">' . $broadcastBubble('Hi Ada 👋 New gowns just landed, and every gown is 20% off this weekend only!')],
                            ['out', '<span>Is the red one available in size 14?</span>'],
                            ['in', '<span>Yes it is! It’s ₦52,000 with the discount. Shall I reserve it for you?</span>', 'Yes, reserve it'],
                        ]) ?>
                    </div>
                    <div>
                        <h3>Bulk WhatsApp messaging sent to thousands of people at once without getting blocked</h3>
                        <p class="sol-p">Bulk messages go out through Meta’s official WhatsApp Business Platform, from the same number your customers already chat with. Every message uses a template Meta has approved, goes only to people who agreed to receive it, and lets them reply STOP at any time. That keeps your number in good standing and your messages welcome.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad api-help">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">What can you send?</h2>
                <div class="api-help-grid">
                    <?php foreach ($helpCards as [$tone, $title, $icon, $copy]): ?>
                        <article class="api-help-card <?= $tone ?>">
                            <h3><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg><?= e($title) ?></h3>
                            <p><?= e($copy) ?></p>
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
                    <h2 class="sol-h2 center">Why is <?= e($brandName) ?> better than other WhatsApp broadcast tools?</h2>
                    <div class="api-cmp">
                        <div class="api-cmp-col labels">
                            <div class="api-cmp-head"></div>
                            <?php foreach ($compare as [$label]): ?><div class="api-cmp-cell"><?= $label ?></div><?php endforeach; ?>
                        </div>
                        <div class="api-cmp-col ours">
                            <div class="api-cmp-head">Bulk WhatsApp Messaging<small>by <span><?= $mark ?><?= e($brandName) ?></span></small></div>
                            <?php foreach ($compare as [$label, $ours]): ?><div class="api-cmp-cell" data-label="<?= $label ?>"><?= $ours ?></div><?php endforeach; ?>
                        </div>
                        <div class="api-cmp-col app">
                            <div class="api-cmp-head">Other WhatsApp broadcast tools</div>
                            <?php foreach ($compare as [$label, , $app]): ?><div class="api-cmp-cell" data-label="<?= $label ?>"><?= $app ?></div><?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad api-start">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">How to send your first<br>bulk WhatsApp message</h2>
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
                    <h2 class="sol-h2 center">Everything you need to message customers in bulk</h2>

                    <div class="sol-split api-feat">
                        <div class="api-vis bw-tpl" aria-hidden="true">
                            <div class="api-win">
                                <div class="api-win-bar"><?= $mark ?><b><?= e($brandName) ?></b></div>
                                <div class="api-form">
                                    <b>New template</b>
                                    <label>Template name<span>Weekend promo</span></label>
                                    <label>Type of message<span>Promotion: offers, new stock, sales</span></label>
                                    <label>Photo<span>red-gown.jpg</span></label>
                                    <label>Ask Hannah to write it<span class="bw-brief">20% off all gowns this weekend <em>Write it</em></span></label>
                                    <label>Message<span class="bw-body">Hi <mark>{{name}}</mark> 👋 Our weekend ankara sale starts now. 20% off every gown until Sunday!</span></label>
                                    <span class="bw-approved">✓ Approved by Meta</span>
                                </div>
                            </div>
                            <?= $phone([
                                ['in', '<img class="ph-photo" src="' . e($photoBase . 'red-gown.jpg') . '" alt="" loading="lazy">' . $broadcastBubble('Hi Ada 👋 Our weekend ankara sale starts now. 20% off every gown until Sunday!')],
                            ]) ?>
                        </div>
                        <div class="api-feat-copy">
                            <h3>Templates Meta approves, written with Hannah</h3>
                            <p>Describe your offer in a line and Hannah drafts the message for you. Add each customer’s first name, see exactly how it looks on a phone, then send it to Meta for approval straight from <?= e($brandName) ?>. Approval usually takes minutes, and you can reuse the template as often as you like.</p>
                        </div>
                    </div>

                    <div class="sol-split api-feat flip">
                        <div class="api-vis bw-contacts" aria-hidden="true">
                            <div class="api-win">
                                <div class="api-win-bar"><?= $mark ?><b><?= e($brandName) ?></b></div>
                                <div class="api-form">
                                    <b>Contacts</b>
                                    <small>1,240 subscribed · 18 unsubscribed</small>
                                    <div class="api-rcpt head bw-row"><span>Name</span><span>Phone</span><span>Status</span></div>
                                    <?php foreach ($contacts as [$name, $number, $subscribed]): ?>
                                        <div class="api-rcpt bw-row"><span><?= e($name) ?></span><span><?= e($number) ?></span><span class="bw-tag<?= $subscribed ? '' : ' off' ?>"><?= $subscribed ? 'Subscribed' : 'Unsubscribed' ?></span></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?= $phone([
                                ['in', $broadcastBubble('Hi Musa 👋 Fresh small chops boxes are back for the weekend. Order before Friday!')],
                                ['out', '<span>STOP</span>'],
                                ['in', '<span>No problem, Musa. You won’t get these messages any more.</span>'],
                            ]) ?>
                        </div>
                        <div class="api-feat-copy">
                            <h3>Contacts who want to hear from you</h3>
                            <p>Add customers who have already messaged you on WhatsApp with one click, or paste a list of names and numbers. Numbers starting with 0 are fine. You confirm that everyone agreed to receive your messages, and anyone who replies STOP is taken off your list automatically.</p>
                        </div>
                    </div>

                    <div class="sol-split api-feat">
                        <div class="api-vis bw-track" aria-hidden="true">
                            <div class="api-win">
                                <div class="api-win-bar"><?= $mark ?><b><?= e($brandName) ?></b></div>
                                <div class="api-form">
                                    <b>Weekend ankara sale</b>
                                    <small>Sent Saturday, 9:00am · 1,240 contacts</small>
                                    <div class="bw-tiles">
                                        <span><b>1,240</b>Sent</span>
                                        <span><b>1,198</b>Delivered</span>
                                        <span><b>893</b>Read</span>
                                        <span class="bad"><b>42</b>Failed</span>
                                    </div>
                                    <div class="bw-history">
                                        <div><span>Weekend ankara sale</span><em class="ok">Sent</em></div>
                                        <div><span>New stock: adire sets</span><em class="ok">Sent</em></div>
                                        <div><span>December opening hours</span><em class="wait">Sending</em></div>
                                    </div>
                                </div>
                            </div>
                            <div class="api-thanks bw-pause"><b>Broadcast paused</b>Meta declined your card. Update it in Meta billing, then resume. Nobody gets the message twice.</div>
                        </div>
                        <div class="api-feat-copy">
                            <h3>See who got it, who read it and what failed</h3>
                            <p>Every broadcast shows how many messages were sent, delivered, read and failed. If something stops it, like a declined card or a template Meta paused, you see the reason in plain English and resume when it’s fixed. You’re only charged tokens for messages that go out.</p>
                        </div>
                    </div>

                    <div class="sol-split api-feat flip">
                        <div class="api-vis bw-replies" aria-hidden="true">
                            <div class="api-promo">
                                <img class="api-promo-img" src="<?= e($photoBase . 'red-gown.jpg') ?>" alt="" loading="lazy">
                                <p><b>Weekend ankara sale</b></p>
                                <p>20% off every gown until Sunday.</p>
                                <span>Sent to 1,240 customers</span>
                            </div>
                            <?= $phone([
                                ['out', '<span>Is the red gown still available? I wear size 14.</span>'],
                                ['in', '<span>Yes! The red gown in size 14 is ₦52,000 with the sale. Shall I send our payment details?</span>'],
                                ['out', '<span>Yes please</span>'],
                                ['in', '<span>Here you go:<br><b>GTBank · 0123 456 789</b><br>Tolu’s Closet</span>'],
                            ]) ?>
                            <div class="api-thanks"><b>New order</b>Bisi · Red gown, size 14<br>₦52,000 · Payment details sent</div>
                        </div>
                        <div class="api-feat-copy">
                            <h3>Hannah answers every reply</h3>
                            <p>A good broadcast brings a wave of replies. Hannah answers each one in seconds, using your products, prices and FAQs: checks sizes, shares photos, sends your payment details and alerts you when a chat needs you. No reply goes unanswered, even when hundreds come in at once.</p>
                            <a class="api-more" href="<?= e($signupUrl) ?>">Start sending bulk messages →</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php $toolSections = ['testimonials']; require dirname(__DIR__) . '/includes/tools/sections-bottom.php'; ?>

        <section class="sol-pad api-guides">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Grow the list you send to</h2>
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
                    <h2>Ready to message all your customers at once?</h2>
                    <p>Sign up, connect your WhatsApp number and send your first bulk message today.</p>
                    <div class="sol-btns">
                        <a class="sol-btn white" href="<?= e($signupUrl) ?>">Get started</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<script src="<?= e($appUrl) ?>/assets/js/testimonial-slider.js?v=1"></script>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
