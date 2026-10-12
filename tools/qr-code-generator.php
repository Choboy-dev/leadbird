<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/tools/countries.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = 'Free WhatsApp QR Code Generator | Click to Chat QR Codes — ' . $brandName;
$metaDescription = 'Create a free WhatsApp QR code for your website, landing pages, packaging or shop. Add a pre-filled welcome message and download it as PNG or SVG.';
$metaUrl = app_route('tools/qr-code-generator');
$extraStylesheets = ['dropdown.css?v=6', 'chat-link-generator.css?v=7'];
$activeNav = null;

$countries = tool_countries();
$defaultCountry = 'NG';
$sampleLink = 'https://wa.me/2348012345678';

$qrIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><path d="M14 14h2v2h-2zM18 18h2v2h-2zM18 14h2M14 18v2"/></svg>';

$cards = [
    ['<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M4 12h16"/>', 'Why use QR codes?', 'Visitors to your website, landing pages or shop scan the code and start a WhatsApp chat with you, without saving your number first.'],
    ['<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><path d="M14 14h2v2h-2zM18 18h2v2h-2zM18 14h2M14 18v2"/>', 'How these QR codes work', 'The tool turns a standard wa.me click-to-chat link for any WhatsApp or WhatsApp Business number into a QR code that any phone camera can read.'],
    ['<path d="M5 5h14v10H9l-4 4z"/><path d="M9 9h6M9 12h4"/>', 'Set the tone', 'Add a welcome message so every chat starts with context, like the product someone is looking at or the offer they saw.'],
];

$steps = [
    ['Enter your number', 'Choose your country code and type your WhatsApp Business number.'],
    ['Add a welcome message', 'Optionally pre-fill the first message customers will send you when they scan the code.'],
    ['Generate and download', 'Click “Generate QR code”, then download it as a PNG or SVG and add it to your website, print or packaging.'],
];

$faqs = [
    ['What is a WhatsApp QR code?', 'It’s a QR code that contains a WhatsApp click-to-chat link (https://wa.me/ followed by your number). When someone scans it with their phone camera, WhatsApp opens a chat with you.'],
    ['What happens when someone scans the code?', 'Their phone offers to open the link, which starts a chat with you in WhatsApp. If you added a welcome message, it appears ready to send. They just tap send.'],
    ['Does it cost anything to create a WhatsApp QR code?', 'No. Creating and downloading WhatsApp QR codes with this tool is completely free, and the codes don’t expire.'],
    ['Should I download PNG or SVG?', 'Use PNG for websites, social posts and documents. Use SVG for print, such as posters, menus and packaging, because it stays sharp at any size.'],
    ['Will the QR code stop working if I change my welcome message?', 'The message is part of the code, so existing codes keep their original message. To use a new message, generate and download a new code.'],
    ['Do you store my number or message?', 'No. The QR code is created in your browser, so your number and message aren’t sent to or stored on our servers.'],
];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol lk flex-1">

        <section class="lk-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <span class="lk-tag">Free WhatsApp QR Code Generator</span>
                    <h1>WhatsApp click-to-chat QR codes for your website</h1>
                    <p class="sol-p">Let visitors start a WhatsApp chat with you by scanning a QR code on your website, landing pages, packaging or in your shop. Free, with no sign-up.</p>
                    <a class="sol-btn" href="#generator">Generate now</a>
                </div>
                <div class="lk-visual lk-visual-qr" aria-hidden="true">
                    <div class="lk-web">
                        <div class="lk-web-bar"><i></i><i></i><i></i><span>adaskitchen.ng</span></div>
                        <div class="lk-web-body">
                            <div class="lk-web-copy"><b></b><b></b><b></b><span>Order jollof</span></div>
                            <div class="lk-web-qr"><canvas data-qr="<?= e($sampleLink) ?>" width="120" height="120"></canvas><small>Scan to chat with us on WhatsApp</small></div>
                        </div>
                    </div>
                    <div class="lk-scan">
                        <div class="lk-scan-view"><canvas data-qr="<?= e($sampleLink) ?>" width="120" height="120"></canvas><i></i></div>
                        <span class="lk-scan-pill"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v10H9l-4 4z"/><path d="M9 9h6M9 12h4"/></svg>Open in WhatsApp</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad lk-boost">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Boost engagement with <span>WhatsApp QR codes</span></h2>
                <div class="lk-cards">
                    <?php foreach ($cards as [$icon, $title, $copy]): ?>
                        <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad lk-steps-sec">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Create a WhatsApp QR code in 3 simple steps</h2>
                <ol class="lk-steps">
                    <?php foreach ($steps as $i => [$title, $copy]): ?>
                        <li><span>Step <?= $i + 1 ?></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <section class="sol-pad lk-gen" id="generator">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Create your WhatsApp QR code</h2>
                <div class="lk-tool">
                    <form class="lk-form" id="lk-form" action="#generator" novalidate>
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
                            <label for="lk-message">Welcome message <small>(optional)</small></label>
                            <p class="lk-help" id="lk-message-help">This text appears ready to send when someone opens your chat, making it easier to start the conversation.</p>
                            <textarea id="lk-message" name="message" rows="4" maxlength="1000" placeholder="Hi! I’d like to know more about your products." aria-describedby="lk-message-help lk-message-err"></textarea>
                            <p class="lk-err" id="lk-message-err"></p>
                        </div>

                        <button type="submit" class="sol-btn lk-submit" data-label="Generate QR code">Generate QR code</button>
                        <p class="lk-note">Your QR code is created in your browser. Your number and message aren’t sent to or stored on our servers.</p>
                    </form>

                    <div class="lk-result" id="lk-result" aria-live="polite">
                        <div class="lk-empty" id="lk-empty">
                            <?= $qrIcon ?>
                            <p>Your QR code and chat link will appear here.</p>
                        </div>
                        <div class="lk-out" id="lk-out" hidden>
                            <div class="lk-out-qr">
                                <h3>Your WhatsApp QR code</h3>
                                <div class="lk-qr big">
                                    <canvas id="lk-qr" width="280" height="280" aria-label="QR code for your WhatsApp chat link" role="img"></canvas>
                                    <div>
                                        <p>Customers scan this with their phone camera to open a chat with you. Use it on your website, menus, packaging, posters, receipts and in your shop.</p>
                                        <div class="lk-btns">
                                            <a class="sol-btn yellow" id="lk-png" href="#" download="whatsapp-qr-code.png">Download PNG</a>
                                            <a class="sol-btn-line" id="lk-svg" href="#" download="whatsapp-qr-code.svg">Download SVG</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="lk-out-link">
                                <h3>Your WhatsApp chat link</h3>
                                <div class="lk-linkbox"><input type="text" id="lk-link" readonly value="" aria-label="Your WhatsApp chat link"></div>
                                <div class="lk-btns">
                                    <button type="button" class="sol-btn blue" id="lk-copy">Copy link</button>
                                    <a class="sol-btn-line" id="lk-open" href="#" target="_blank" rel="noopener">Open in WhatsApp</a>
                                    <span class="lk-copied" id="lk-copied" role="status"></span>
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
