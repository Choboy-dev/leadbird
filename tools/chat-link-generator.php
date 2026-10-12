<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/tools/countries.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = 'Free WhatsApp Chat Link Generator | wa.me Link & QR Code — ' . $brandName;
$metaDescription = 'Create a free WhatsApp chat link and QR code with an optional pre-filled message. Add it to Instagram, Facebook, TikTok, X, email or your website.';
$metaUrl = app_route('tools/chat-link-generator');
$extraStylesheets = ['dropdown.css?v=6', 'chat-link-generator.css?v=7'];
$activeNav = null;

$countries = tool_countries();
$defaultCountry = 'NG';

$linkIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>';

$cards = [
    ['<path d="M5 5h14v10H9l-4 4z"/><path d="M9 9h6M9 12h4"/>', 'Why use chat links?', 'Customers can message you on WhatsApp straight from your social profiles, emails or website, without having to save your number first.'],
    ['<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>', 'How chat links work', 'The tool creates a standard wa.me link for any WhatsApp or WhatsApp Business number. Tapping it opens a chat with you in WhatsApp.'],
    ['<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><path d="M14 14h2v2h-2zM18 18h2v2h-2zM18 14h2M14 18v2"/>', 'What you can create', 'A link with your number and an optional welcome message, plus a QR code for flyers, packaging, receipts, posters and your shop front.'],
];

$steps = [
    ['Enter your number', 'Choose your country code and type your WhatsApp Business number.'],
    ['Add a welcome message', 'Optionally pre-fill the first message customers will send you, so they know what to ask.'],
    ['Generate and share', 'Click “Generate link”, then copy it or download the QR code and add it wherever customers find you.'],
];

$faqs = [
    ['What does a WhatsApp chat link look like?', 'It starts with https://wa.me/ followed by your full phone number with the country code, for example https://wa.me/2348012345678. If you add a welcome message, it is added to the end of the link as ?text= followed by the message.'],
    ['What happens when someone clicks the link?', 'It opens a chat with you in the WhatsApp app on their phone, or in WhatsApp Web or the desktop app on a computer. If you added a welcome message, it appears ready to send. They just tap send.'],
    ['Does it cost anything to create a WhatsApp link?', 'No. Creating WhatsApp chat links and QR codes with this tool is completely free.'],
    ['Does it work with the WhatsApp Business app?', 'Yes. Chat links work with any number registered on WhatsApp, whether you use WhatsApp, the WhatsApp Business app or the WhatsApp Business Platform through ' . $brandName . '.'],
    ['Do my customers need to save my number first?', 'No. That’s the main benefit: the chat opens straight away without anyone adding you to their contacts.'],
    ['What do you do with my email and number?', 'We save them so we can follow up about ' . $brandName . ' and understand how the tool is used. Your welcome message isn’t stored. See our Privacy Policy for details, including how to opt out.'],
];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol lk flex-1">

        <section class="lk-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <span class="lk-tag">Free WhatsApp Chat Link Generator</span>
                    <h1>WhatsApp chat links for your social media pages</h1>
                    <p class="sol-p">Let customers start a WhatsApp chat with you in one tap from your Instagram, Facebook, TikTok or X profile, your email signature or your website, using a link or a QR code.</p>
                    <a class="sol-btn" href="#generator">Generate now</a>
                </div>
                <div class="lk-visual" aria-hidden="true">
                    <div class="lk-profile">
                        <div class="lk-profile-top"><i></i><div><b>Ada’s Kitchen</b><small>Food vendor · Lagos</small></div></div>
                        <p>Hot jollof &amp; small chops across Lagos 🍲<br>Order or ask us anything on WhatsApp 👇</p>
                        <span class="lk-profile-link"><?= $linkIcon ?>wa.me/2348012345678</span>
                        <div class="lk-profile-grid"><i></i><i></i><i></i><i></i><i></i><i></i></div>
                    </div>
                    <div class="lk-tap"><span></span></div>
                    <div class="lk-chat">
                        <div class="lk-chat-top"><i></i><b>Ada’s Kitchen</b></div>
                        <div class="lk-chat-body"><span class="lk-chat-date">Today</span></div>
                        <div class="lk-chat-input"><span>Hi! I’d like to order a tray of jollof rice for Saturday 🍛</span><b><svg viewBox="0 0 24 24"><path d="M4 12l16-8-6 16-2-7z"/></svg></b></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad lk-boost">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Boost engagement with <span>WhatsApp chat links</span> on social media</h2>
                <div class="lk-cards">
                    <?php foreach ($cards as [$icon, $title, $copy]): ?>
                        <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad lk-steps-sec">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Create a WhatsApp chat link in 3 simple steps</h2>
                <ol class="lk-steps">
                    <?php foreach ($steps as $i => [$title, $copy]): ?>
                        <li><span>Step <?= $i + 1 ?></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <section class="sol-pad lk-gen" id="generator">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Create your WhatsApp chat link</h2>
                <div class="lk-tool">
                    <form
                        class="lk-form"
                        id="lk-form"
                        action="#generator"
                        novalidate
                        data-lead-endpoint="<?= e(app_route('tools/lead')) ?>"
                        data-csrf="<?= e(csrf_token()) ?>"
                        data-tool="chat-link-generator"
                    >
                        <div class="lk-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <p class="lk-alert" id="lk-alert" role="alert" hidden></p>

                        <div class="lk-field">
                            <label for="lk-phone">Your WhatsApp number</label>
                            <p class="lk-help" id="lk-phone-help">Choose your country code, then type your number. For example, for +234 801 234 5678 choose Nigeria and type 801 234 5678.</p>
                            <div class="lk-phone">
                                <select name="phone_country" id="lk-country" class="lk-country-select" aria-label="Country code" data-searchable>
                                    <?php foreach ($countries as $code => [$name, $dial]): ?>
                                        <option value="<?= e($code) ?>" data-dial="<?= e($dial) ?>"<?= $code === $defaultCountry ? ' selected' : '' ?>><?= e($name) ?> (+<?= e($dial) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="tel" id="lk-phone" name="phone" inputmode="tel" autocomplete="tel-national" maxlength="20" aria-describedby="lk-phone-help lk-phone-err" required>
                            </div>
                            <p class="lk-err" id="lk-phone-err"></p>
                        </div>

                        <div class="lk-field">
                            <label for="lk-email">Business email</label>
                            <input type="email" id="lk-email" name="email" autocomplete="email" maxlength="190" aria-describedby="lk-email-err" required>
                            <p class="lk-err" id="lk-email-err"></p>
                        </div>

                        <div class="lk-field">
                            <label for="lk-message">Welcome message <small>(optional)</small></label>
                            <p class="lk-help" id="lk-message-help">This text appears ready to send when someone opens your chat, making it easier to start the conversation.</p>
                            <textarea id="lk-message" name="message" rows="4" maxlength="1000" placeholder="Hi! I’d like to know more about your products." aria-describedby="lk-message-help lk-message-err"></textarea>
                            <p class="lk-err" id="lk-message-err"></p>
                        </div>

                        <button type="submit" class="sol-btn lk-submit">Generate link</button>
                        <p class="lk-note">We’ll save your email and number to follow up about <?= e($brandName) ?>. See our <a href="<?= e(app_route('privacy')) ?>">Privacy Policy</a>.</p>
                    </form>

                    <div class="lk-result" id="lk-result" aria-live="polite">
                        <div class="lk-empty" id="lk-empty">
                            <?= $linkIcon ?>
                            <p>Your chat link and QR code will appear here.</p>
                        </div>
                        <div class="lk-out" id="lk-out" hidden>
                            <div class="lk-out-link">
                                <h3>Your WhatsApp chat link</h3>
                                <div class="lk-linkbox"><input type="text" id="lk-link" readonly value="" aria-label="Your WhatsApp chat link"></div>
                                <div class="lk-btns">
                                    <button type="button" class="sol-btn blue" id="lk-copy">Copy link</button>
                                    <a class="sol-btn-line" id="lk-open" href="#" target="_blank" rel="noopener">Open in WhatsApp</a>
                                    <span class="lk-copied" id="lk-copied" role="status"></span>
                                </div>
                            </div>
                            <div class="lk-out-qr">
                                <h3>Your WhatsApp QR code</h3>
                                <div class="lk-qr">
                                    <canvas id="lk-qr" width="280" height="280" aria-label="QR code for your WhatsApp chat link" role="img"></canvas>
                                    <div>
                                        <p>Customers scan this with their phone camera to open a chat with you. Use it on your website, flyers, packaging, posters, receipts and in your shop.</p>
                                        <div class="lk-btns">
                                            <a class="sol-btn yellow" id="lk-png" href="#" download="whatsapp-qr-code.png">Download PNG</a>
                                            <a class="sol-btn-line" id="lk-svg" href="#" download="whatsapp-qr-code.svg">Download SVG</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <noscript><p class="lk-help">Turn on JavaScript to see and download your QR code.</p></noscript>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php require dirname(__DIR__) . '/includes/tools/sections-bottom.php'; ?>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<script src="<?= e($appUrl) ?>/assets/js/dropdown.js?v=6"></script>
<script src="<?= e($appUrl) ?>/assets/vendor/qrcode/qrcode.js?v=1"></script>
<script src="<?= e($appUrl) ?>/assets/js/chat-link-generator.js?v=2"></script>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
