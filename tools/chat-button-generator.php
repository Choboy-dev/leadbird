<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/tools/countries.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = 'Free WhatsApp Chat Button & Live Chat Widget for Your Website — ' . $brandName;
$metaDescription = 'Create a free WhatsApp chat button for your website. Choose the style, colour and position, add a welcome message, preview it live and copy the code.';
$metaUrl = app_route('tools/chat-button-generator');
$extraStylesheets = ['dropdown.css?v=6', 'chat-link-generator.css?v=7'];
$activeNav = null;

$countries = tool_countries();
$defaultCountry = 'NG';
$widgetSrc = rtrim((string) $appUrl, '/') . '/assets/widget/chat-widget.js?v=1';

$heroWidget = [
    'phone' => '2348012345678',
    'brandName' => 'Ada’s Kitchen',
    'welcome' => 'Hi there! How can we help you?',
    'buttonStyle' => 'pill',
    'buttonText' => 'Chat with us',
    'color' => '#25D366',
    'position' => 'right',
    'openByDefault' => true,
    'branding' => false,
];

$cards = [
    ['<path d="M5 5h14v10H9l-4 4z"/><path d="M9 9h6M9 12h4"/>', 'Why add a chat widget?', 'Give visitors one of their favourite ways to talk to you: a WhatsApp chat button pinned to every page of your website and landing pages.'],
    ['<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>', 'How the widget works', 'Visitors click the button, read your welcome message and tap “Start chat” to open a WhatsApp conversation with you, on mobile or desktop.'],
    ['<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 0 0 18c1.1 0 1.6-.8 1.6-1.6 0-.9-.6-1.3-.6-2.1 0-.9.7-1.5 1.6-1.5H17a4 4 0 0 0 4-4c0-4.4-4-8.8-9-8.8z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7.5" r="1"/><circle cx="14.5" cy="7.5" r="1"/>', 'Make it yours', 'Choose the button style, colour, text and position, add your brand name and logo, and pre-fill the first message with the page they came from.'],
];

$steps = [
    ['Design your button', 'Pick a style and colour, write the call to action and choose which corner it sits in.'],
    ['Set up your widget', 'Add your brand name, WhatsApp number and welcome text. Pre-fill the first message with the page link or title so you know where the chat started.'],
    ['Copy and paste the code', 'Add the code just before the closing </body> tag on every page of your website. That’s it.'],
];

$buttonStyles = [
    ['pill', 'Pill', '<span class="cb-mini cb-mini-pill" aria-hidden="true"><i></i><b>Chat</b></span>'],
    ['round', 'Round', '<span class="cb-mini cb-mini-round" aria-hidden="true"><i></i></span>'],
    ['square', 'Square', '<span class="cb-mini cb-mini-square" aria-hidden="true"><i></i><b>Chat</b></span>'],
    ['bubble', 'Bubble', '<span class="cb-mini cb-mini-bubble" aria-hidden="true"><em>Chat</em><i></i></span>'],
];

$swatches = ['#25D366', '#075E54', '#000000', '#0038FF', '#FF3B30', '#F59E0B'];

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol lk cb flex-1">

        <section class="lk-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <span class="lk-tag">Free WhatsApp Chat Button Generator</span>
                    <h1>WhatsApp live chat widget for your website</h1>
                    <p class="sol-p">Let visitors start a WhatsApp chat with you from any page of your website or landing pages, using a chat button that matches your brand. Free, with no sign-up.</p>
                    <a class="sol-btn" href="#generator">Generate now</a>
                </div>
                <div class="cb-site" aria-hidden="true" inert>
                    <div class="lk-web-bar"><i></i><i></i><i></i><span>adaskitchen.ng</span></div>
                    <div class="cb-site-body" data-widget="<?= e((string) json_encode($heroWidget, JSON_UNESCAPED_UNICODE)) ?>">
                        <div class="cb-site-hero"><b></b><b></b><b></b><span>Order jollof</span></div>
                        <div class="cb-site-grid"><i></i><i></i><i></i></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sol-pad lk-boost">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Boost engagement with a <span>WhatsApp live chat widget</span></h2>
                <div class="lk-cards">
                    <?php foreach ($cards as [$icon, $title, $copy]): ?>
                        <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php $toolSections = ['clients']; require dirname(__DIR__) . '/includes/tools/sections-bottom.php'; ?>

        <section class="sol-pad lk-steps-sec">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Create a WhatsApp live chat widget in 3 steps</h2>
                <ol class="lk-steps">
                    <?php foreach ($steps as $i => [$title, $copy]): ?>
                        <li><span>Step <?= $i + 1 ?></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <section class="sol-pad lk-gen" id="generator">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Create your WhatsApp live chat widget</h2>
                <div class="cb-tool">
                    <form
                        class="lk-form cb-form"
                        id="cb-form"
                        action="#generator"
                        novalidate
                        data-lead-endpoint="<?= e(app_route('tools/lead')) ?>"
                        data-csrf="<?= e(csrf_token()) ?>"
                        data-widget-src="<?= e($widgetSrc) ?>"
                    >
                        <div class="lk-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <p class="lk-alert" id="lk-alert" role="alert" hidden></p>

                        <fieldset>
                            <legend>Button settings <small>Choose how your chat button looks</small></legend>

                            <div class="lk-field">
                                <span class="cb-label" id="cb-style-label">Button style</span>
                                <div class="cb-styles" role="radiogroup" aria-labelledby="cb-style-label">
                                    <?php foreach ($buttonStyles as [$value, $label, $mini]): ?>
                                        <label class="cb-style">
                                            <input type="radio" name="button_style" value="<?= e($value) ?>"<?= $value === 'pill' ? ' checked' : '' ?>>
                                            <span class="cb-style-card"><?= $mini ?><?= e($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="lk-field">
                                <span class="cb-label" id="cb-color-label">Brand colour</span>
                                <div class="cb-colors" role="group" aria-labelledby="cb-color-label">
                                    <?php foreach ($swatches as $swatch): ?>
                                        <button type="button" class="cb-swatch" data-color="<?= e($swatch) ?>" style="--sw:<?= e($swatch) ?>" aria-label="Use colour <?= e($swatch) ?>"></button>
                                    <?php endforeach; ?>
                                    <label class="cb-custom"><input type="color" name="color" id="cb-color" value="#25D366"><span>Custom</span></label>
                                </div>
                            </div>

                            <div class="lk-field">
                                <label for="cb-button-text">Button text <small>(max 24 characters)</small></label>
                                <input type="text" id="cb-button-text" name="button_text" maxlength="24" value="Chat with us" aria-describedby="cb-button-text-err">
                                <p class="lk-err" id="cb-button-text-err"></p>
                            </div>

                            <div class="lk-field">
                                <span class="cb-label" id="cb-pos-label">Position</span>
                                <div class="cb-seg" role="radiogroup" aria-labelledby="cb-pos-label">
                                    <label><input type="radio" name="position" value="left"><span>Bottom left</span></label>
                                    <label><input type="radio" name="position" value="right" checked><span>Bottom right</span></label>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Widget settings <small>Your brand, number and welcome message</small></legend>

                            <div class="lk-field">
                                <label for="cb-brand">Brand name</label>
                                <input type="text" id="cb-brand" name="brand_name" maxlength="60" placeholder="Your business name" aria-describedby="cb-brand-err">
                                <p class="lk-err" id="cb-brand-err"></p>
                            </div>

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
                                <label for="cb-welcome">Welcome text <small>(max 40 characters)</small></label>
                                <input type="text" id="cb-welcome" name="welcome" maxlength="40" value="Hi there! How can I help you?" aria-describedby="cb-welcome-err">
                                <p class="lk-err" id="cb-welcome-err"></p>
                            </div>

                            <div class="lk-field">
                                <label for="lk-message">Pre-filled message <small>(optional)</small></label>
                                <p class="lk-help" id="lk-message-help">The first message visitors send you. Insert the page link or title so you know which page they were on.</p>
                                <textarea id="lk-message" name="message" rows="3" maxlength="500" aria-describedby="lk-message-help lk-message-err">Hello, I have a question about {{page_link}}</textarea>
                                <div class="cb-tokens">
                                    <button type="button" data-token="{{page_link}}">+ Page link</button>
                                    <button type="button" data-token="{{page_title}}">+ Page title</button>
                                </div>
                                <p class="lk-err" id="lk-message-err"></p>
                            </div>

                            <div class="lk-field">
                                <span class="cb-label" id="cb-open-label">Open the widget automatically</span>
                                <div class="cb-seg" role="radiogroup" aria-labelledby="cb-open-label">
                                    <label><input type="radio" name="open_default" value="1"><span>Yes</span></label>
                                    <label><input type="radio" name="open_default" value="0" checked><span>No</span></label>
                                </div>
                            </div>

                            <div class="lk-field">
                                <label for="cb-image">Brand image URL <small>(optional)</small></label>
                                <p class="lk-help" id="cb-image-help">A square logo works best. Use a full address starting with https://</p>
                                <input type="url" id="cb-image" name="brand_image" maxlength="300" placeholder="https://www.example.com/logo.png" aria-describedby="cb-image-help cb-image-err">
                                <p class="lk-err" id="cb-image-err"></p>
                            </div>

                            <div class="lk-field">
                                <label for="lk-email">Business email</label>
                                <input type="email" id="lk-email" name="email" autocomplete="email" maxlength="190" aria-describedby="lk-email-err" required>
                                <p class="lk-err" id="lk-email-err"></p>
                            </div>
                        </fieldset>

                        <button type="submit" class="sol-btn lk-submit" data-label="Get the code">Get the code</button>
                        <p class="lk-note">We’ll save your email, number and brand name to follow up about <?= e($brandName) ?>. See our <a href="<?= e(app_route('privacy')) ?>">Privacy Policy</a>.</p>
                        <noscript><p class="lk-alert">Please turn on JavaScript to design your widget and get the code.</p></noscript>

                        <div class="cb-code" id="cb-code" hidden>
                            <h3>Your widget code</h3>
                            <p>Copy this code and paste it just before the closing <code>&lt;/body&gt;</code> tag on every page of your website. On WordPress, Shopify, Wix or Squarespace, paste it into the theme’s footer or “custom code” setting.</p>
                            <pre><code id="cb-snippet" tabindex="0"></code></pre>
                            <div class="lk-btns">
                                <button type="button" class="sol-btn blue" id="cb-copy">Copy code</button>
                                <span class="lk-copied" id="cb-copied" role="status"></span>
                            </div>
                        </div>
                    </form>

                    <div class="cb-preview">
                        <div class="cb-preview-head"><span class="cb-dot"></span>Live preview</div>
                        <div class="cb-site cb-site-preview">
                            <div class="lk-web-bar"><i></i><i></i><i></i><span>your-website.com</span></div>
                            <div class="cb-site-body" id="cb-stage">
                                <div class="cb-site-hero"><b></b><b></b><b></b><span>Your page</span></div>
                                <div class="cb-site-grid"><i></i><i></i><i></i></div>
                            </div>
                        </div>
                        <p class="lk-help">Click the button in the preview to open and close the widget.</p>
                    </div>
                </div>
            </div>
        </section>

        <?php $toolSections = ['cta']; require dirname(__DIR__) . '/includes/tools/sections-bottom.php'; ?>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<script src="<?= e($appUrl) ?>/assets/js/dropdown.js?v=6"></script>
<script src="<?= e($widgetSrc) ?>"></script>
<script src="<?= e($appUrl) ?>/assets/js/chat-button-generator.js?v=1"></script>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
