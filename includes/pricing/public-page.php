<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var list<array<string, mixed>> $plans */
/** @var list<array{slug: string, name: string, tokens: int, price: float, badge?: string}> $topups */

// Names, prices, limits and token allowances come from config/plans.php and
// config/token-topups.php so this page and checkout never disagree.

$ppBrand = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$ppSupportEmail = (string) (app_config()['contact']['email'] ?? 'hello@leadbird.app');
$ppContactUrl = app_route('contact');

$ppTones = ['starter' => 'blue', 'vendor' => 'lime', 'shop' => 'ink'];

$ppPlanCopy = [
    'starter' => [
        'title' => 'Key features',
        'features' => [
            ['Replies day and night:', 'Answers questions about your products and prices, even while you sleep'],
            ['Payment details:', 'Sends your account details to buyers who are ready to pay'],
            ['Your own voice:', 'Answers in your tone, using what you teach her about your shop'],
            ['Top up any time:', 'Buy more tokens whenever you need them'],
        ],
        'usage' => ['1 Facebook Page, 1 Instagram account and 1 WhatsApp number'],
    ],
    'vendor' => [
        'title' => 'Everything in Starter, plus:',
        'features' => [
            ['Price talks:', 'Talks with customers until you both agree on a price'],
            ['WhatsApp handover:', 'Moves ready buyers over to WhatsApp'],
            ['Post writing:', 'Writes your social media posts for you'],
            ['Scheduling:', 'Plans your posts and publishes them with one click'],
            ['Bulk WhatsApp Messaging:', 'Message up to 5,000 contacts at once'],
        ],
        'usage' => ['Facebook, Instagram and WhatsApp for each business'],
    ],
    'shop' => [
        'title' => 'Everything in Vendor, plus:',
        'features' => [
            ['Follow-ups:', 'Follows up when a customer stops replying'],
            ['Receipts:', 'Sends a receipt after someone pays'],
            ['No account limits:', 'Connect as many Facebook, Instagram and WhatsApp accounts as you want'],
            ['Bulk WhatsApp Messaging:', 'Message up to 50,000 contacts at once'],
        ],
        'usage' => ['As many channels as you need for each business'],
    ],
];

// group => [[feature, starter, vendor, shop]]; true = tick, false = dash, text = shown as is
$ppCompare = [
    'What you get' => [
        ['Businesses you can run', '1', '3', 'As many as you need'],
        ['Facebook Pages', '1', '1 for each business', 'As many as you need'],
        ['Instagram accounts', '1', '1 for each business', 'As many as you need'],
        ['WhatsApp numbers', '1', '1 for each business', 'As many as you need'],
        ['Tokens each month', '500', '2,000', '8,000'],
        ['Buy extra tokens', true, true, true],
    ],
    'What your salesgirl does' => [
        ['Answers questions about your products', true, true, true],
        ['Tells customers your prices', true, true, true],
        ['Sends your payment details', true, true, true],
        ['Replies day and night', true, true, true],
        ['Answers in your own tone of voice', true, true, true],
        ['Agrees on a price with the customer', false, true, true],
        ['Moves ready buyers to WhatsApp', false, true, true],
        ['Follows up when a customer goes quiet', false, false, true],
        ['Sends a receipt after payment', false, false, true],
    ],
    'Social media posts' => [
        ['Writes post captions for you', false, true, true],
        ['Plans posts ahead on a calendar', false, true, true],
        ['Publishes to Facebook and Instagram', false, true, true],
    ],
    'Bulk WhatsApp Messaging' => [
        ['Message all your customers at once', false, true, true],
        ['Contacts you can add', false, '5,000', '50,000'],
        ['See the cost before you send', false, true, true],
        ['Customers who reply STOP are removed', false, true, true],
    ],
    'Paying and account' => [
        ['Pay with a card', true, true, true],
        ['Pay with a bank transfer', true, true, true],
        ['How often you pay', 'Monthly or yearly', 'Monthly or yearly', 'Monthly or yearly'],
        ['Cancel whenever you want', true, true, true],
        ['Move up or down a plan any time', true, true, true],
    ],
];

// [name, tone, what it is, tokens used]
$ppTokenUses = [
    ['Customer replies', 'lime', 'Every reply your salesgirl sends to a customer on WhatsApp, Instagram or Messenger.', TokenCosts::DM_REPLY . ' token per reply'],
    ['Post captions', 'blue', 'She writes a ready-to-post caption for your Facebook, Instagram or TikTok post.', TokenCosts::SOCIAL_CAPTION . ' tokens per caption'],
    ['Hashtags & publishing', 'ink', 'Hashtag ideas for a post, and publishing a post to your pages.', TokenCosts::SOCIAL_HASHTAGS . ' token each'],
    ['Knowledge refresh', 'lime', 'She re-reads your documents, links and FAQs after you update them.', TokenCosts::KNOWLEDGE_AI_REFRESH . ' tokens per refresh'],
    ['Bulk WhatsApp Messaging', 'blue', 'Each customer you send a bulk WhatsApp message to. Meta also charges a small fee per message to your own Meta account.', TokenCosts::BROADCAST_RECIPIENT . ' token per person'],
];

$ppTopupBlurbs = [
    'boost_500' => 'A quick top-up for a busy weekend or a big sale',
    'boost_1500' => 'Room to keep replying through the rest of the month',
    'boost_4000' => 'For shops with messages coming in all day',
];

// [title, label, items]; an item can be [text, [sub-items]]
$ppServices = [
    ['Help getting set up', 'with every plan', [
        'Step-by-step guides to connect WhatsApp, Instagram and Facebook',
        'Help linking your Instagram account to a Facebook Page',
        'Plan and publish to TikTok from the same dashboard',
        'Answers from Hannah, our AI helper, any time of day',
    ]],
    ['Training your salesgirl', 'with every plan', [
        'Add your products, prices and delivery rules',
        'Upload documents, links and FAQs for her to learn from',
        'Test chat to see how she answers before customers do',
        'Set the tone of voice she uses with buyers',
    ]],
    ['Support from our team', 'with every plan', [
        'Email us at ' . $ppSupportEmail,
        'A Help Center with guides on every topic',
        ['We can help you with', ['Choosing the right plan', 'Moving to a bigger plan', 'Questions about your bill']],
    ]],
];

$ppChannels = [
    ['WhatsApp', '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>'],
    ['Instagram', '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>'],
    ['Messenger', '<path d="M12 0C5.24 0 0 4.95 0 11.64c0 3.5 1.43 6.52 3.77 8.61.2.18.31.42.32.68l.07 2.14a.96.96 0 001.34.85l2.38-1.05c.2-.09.43-.1.64-.05 1.1.3 2.26.46 3.48.46 6.76 0 12-4.95 12-11.64S18.76 0 12 0zm7.2 8.95l-3.52 5.59a1.8 1.8 0 01-2.6.48l-2.8-2.1a.72.72 0 00-.87 0l-3.78 2.87c-.5.38-1.16-.22-.83-.76l3.53-5.59a1.8 1.8 0 012.6-.48l2.8 2.1c.26.2.61.2.87 0l3.78-2.87c.5-.38 1.16.22.82.76z"/>'],
    ['Facebook', '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>'],
    ['TikTok', '<path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>'],
];

$ppFaq = [
    ['What is a token?', 'Tokens power your salesgirl’s work. Each reply to a customer uses 1 token, a post caption uses 2, hashtags or publishing use 1 each, and each person you send a bulk WhatsApp message to uses 1. Starter includes 500 tokens each month, Vendor 2,000 and Shop 8,000. Unused tokens expire at renewal, and you can buy more any time from Billing.'],
    ['What happens when my tokens run out?', 'Your salesgirl stops answering until your plan renews or you buy more. You can buy a pack any time from your dashboard and it shows up straight away. Nothing else about your account changes.'],
    ['Which plan should I pick?', 'Pick Starter if you sell from one shop and you are still testing things out. Pick Vendor if you run two or three shops, or you want help writing and posting on social media. Pick Shop if messages come in all day and you do not want to think about limits.'],
    ['Does Meta charge for bulk WhatsApp messages?', 'Yes. Meta charges a small fee for each bulk message, about $0.05 per message to a Nigerian number, billed to the card on your own Meta account. Before you send, we show how many tokens the message uses and roughly what Meta will charge, and we warn you if you would be left without enough tokens for Hannah to answer the replies.'],
    ['Is there a setup fee?', 'No. There is no setup fee on any plan. You only pay for your plan and any extra token packs you choose to buy.'],
    ['Is it monthly or yearly?', 'You can pay monthly, or pay yearly and get two months free. Cancel whenever you want from your dashboard.'],
    ['How do I pay?', 'You pay with a card or a bank transfer through Flutterwave. We never see or keep your card details. Prices on this page are in US dollars. Nigerian customers are charged the naira equivalent at checkout.'],
    ['How do I move to a bigger plan?', 'Open Billing in your dashboard and pick the plan you want. The new plan starts right away and your shops, chats and settings stay exactly as they are.'],
    ['Do you have any cancellation fees?', 'No. Open Billing in your dashboard and press cancel. Your plan keeps working until the end of the period you already paid for, then it stops. We do not charge you again.'],
    ['Do you give refunds?', 'If something on our side stops your salesgirl from working and we cannot fix it, write to ' . $ppSupportEmail . ' and we will sort it out with you. We do not refund time you have already used.'],
    ['Do I need a Facebook Page to use Instagram?', 'Yes. Instagram asks you to link your account to a Facebook Page before another app can read and answer your messages. Your Instagram account also needs to be a business or creator account, which is free to switch to inside the Instagram app.'],
    ['What happens to my chats if I cancel?', 'Your chats, customers and products stay in your account. Your salesgirl stops answering new messages, and you can start her again later by picking a plan.'],
];

$ppQuotes = [
    ['Now I wake up and see money in my account', 'I used to wake up and see customers already bought from someone else. Now I wake up and see money in my account. Lead Bird did that.', 'Adaobi', 'Instagram seller, Lagos', 'Instagram'],
    ['My phone sells even when I am not looking', 'I sell shoes. Before, I would miss messages because I was busy at the market. Now my phone sells even when I am not looking at it.', 'Chinedu', 'Shoe seller, Onitsha', 'Instagram'],
];

$ppChannelPaths = array_column($ppChannels, 1, 0);

$ppMoney = static function (float $amount): string {
    $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

    return '$' . number_format($amount, $decimals);
};

$ppMaxSaving = 0;
foreach ($plans as $plan) {
    $monthlyYear = (float) $plan['price'] * 12;
    if ($monthlyYear > 0) {
        $ppMaxSaving = max($ppMaxSaving, (int) round(100 * ($monthlyYear - (float) $plan['annual_price']) / $monthlyYear));
    }
}

$ppSpark = '<svg class="pp-spark" viewBox="0 0 40 60" aria-hidden="true"><path d="M6 6l22 14M4 30h26M6 54l22-14"/></svg>';
$ppSparkAlt = '<svg class="pp-spark pp-spark--blue" viewBox="0 0 40 60" aria-hidden="true"><path d="M6 6l22 14M4 30h26M6 54l22-14"/></svg>';
?>
<div class="pp-page bg-white min-h-screen flex flex-col">
    <?php require __DIR__ . '/../home/marketing-header.php'; ?>

    <main class="pp flex-1">

        <section class="pp-hero">
            <div class="pp-wrap">
                <h1><?= $ppSparkAlt ?>Get great features at a price that makes sense</h1>
                <p class="pp-sub">No setup fees and simple pricing</p>
                <div class="pp-switch">
                    <p>Save <?= e((string) $ppMaxSaving) ?>% &amp; get <a href="#pp-annual-note">two months free</a> with annual billing</p>
                    <div class="pp-toggle" role="radiogroup" aria-label="Billing period">
                        <button type="button" role="radio" class="on" data-period="annual" aria-checked="true">Annual</button>
                        <button type="button" role="radio" data-period="monthly" aria-checked="false">Monthly</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="pp-plans">
            <div class="pp-wrap pp-grid">
                <?php foreach ($plans as $plan): ?>
                    <?php
                    $slug = (string) $plan['slug'];
                    $copy = $ppPlanCopy[$slug] ?? ['title' => 'Key features', 'features' => [], 'usage' => []];
                    $monthly = (float) $plan['price'];
                    $annual = (float) $plan['annual_price'];
                    $annualPerMonth = round($annual / 12, 2);
                    $limit = $plan['business_limit'] ?? null;
                    $seats = $limit === null ? 'As many businesses as you need' : ($limit === 1 ? '1 business included' : $limit . ' businesses included');
                    $usage = array_merge($copy['usage'], [number_format((int) $plan['monthly_tokens']) . ' tokens each month', 'Buy extra token packs any time']);
                    ?>
                    <article class="pp-card t-<?= e($ppTones[$slug] ?? 'blue') ?>">
                        <?php if (!empty($plan['popular'])): ?>
                            <span class="pp-badge">Most picked</span>
                        <?php endif; ?>
                        <h2><?= e((string) $plan['name']) ?></h2>
                        <p class="pp-blurb"><?= e((string) $plan['description']) ?></p>
                        <div class="pp-price">
                            <b data-annual="<?= e($ppMoney($annualPerMonth)) ?>" data-monthly="<?= e($ppMoney($monthly)) ?>"><?= e($ppMoney($annualPerMonth)) ?></b>
                            <span>/month<small data-annual="<?= e($ppMoney($annual)) ?> billed yearly" data-monthly="billed monthly"><?= e($ppMoney($annual)) ?> billed yearly</small></span>
                        </div>
                        <p class="pp-seats"><?= e($seats) ?></p>
                        <p class="pp-fees">Uses tokens for AI work
                            <button type="button" class="pp-info" aria-label="About tokens" aria-expanded="false">i</button>
                            <span class="pp-tip" role="tooltip" hidden>Each reply to a customer uses 1 token. Captions, hashtags and publishing use a few more. <a href="#token-pricing">See how tokens work</a></span>
                        </p>
                        <a class="pp-select" href="<?= e(app_route('signup', ['plan' => $slug])) ?>">Select plan</a>
                        <h3><?= e($copy['title']) ?></h3>
                        <ul class="pp-list">
                            <?php foreach ($copy['features'] as [$label, $text]): ?>
                                <li><b><?= e($label) ?></b> <?= e($text) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <h3 class="pp-usage-h">Usage</h3>
                        <ul class="pp-list plain">
                            <?php foreach ($usage as $line): ?>
                                <li><?= e($line) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="pp-wrap">
                <p class="pp-annual-note" id="pp-annual-note">Prices are in US dollars. Pay yearly and you get 12 months for the price of 10. Nigerian customers are charged the naira equivalent at checkout.</p>

                <div class="pp-center"><button type="button" class="pp-compare-btn" aria-expanded="false" aria-controls="pp-compare">Compare plans in detail</button></div>
                <div class="pp-compare" id="pp-compare" hidden>
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Features</th>
                                <?php foreach ($plans as $plan): ?>
                                    <th scope="col" class="t-<?= e($ppTones[(string) $plan['slug']] ?? 'blue') ?>"><?= e((string) $plan['name']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <?php foreach ($ppCompare as $group => $rows): ?>
                            <tbody>
                                <tr class="pp-group"><th colspan="<?= count($plans) + 1 ?>" scope="colgroup"><?= e($group) ?></th></tr>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <th scope="row"><?= e((string) $row[0]) ?></th>
                                        <?php foreach (array_slice($row, 1) as $i => $value): ?>
                                            <td data-label="<?= e((string) ($plans[$i]['name'] ?? '')) ?>">
                                                <?php if ($value === true): ?>
                                                    <span class="pp-yes" aria-label="Included">&#10003;</span>
                                                <?php elseif ($value === false): ?>
                                                    <span class="pp-no" aria-label="Not included">&ndash;</span>
                                                <?php else: ?>
                                                    <?= e((string) $value) ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endforeach; ?>
                    </table>
                </div>

                <div class="pp-custom">
                    <div class="pp-custom-row">
                        <p>Have custom needs? Let's explore how we can tailor <b><?= e($ppBrand) ?> to power your business.</b></p>
                        <a class="pp-btn pp-btn--alt" href="<?= e($ppContactUrl) ?>">Talk to us</a>
                    </div>
                    <div class="pp-custom-row inner">
                        <p>Need more than <b>8,000 tokens a month?</b> Ask us about bigger token packs.</p>
                        <a class="pp-btn" href="<?= e($ppContactUrl) ?>">Contact us</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="pp-msg" id="token-pricing">
            <div class="pp-wrap">
                <h2><?= $ppSpark ?>Pay with tokens, get more control</h2>
                <p class="pp-msg-sub">Every plan comes with tokens each month. Each thing your salesgirl does uses a few, so your costs are easy to predict.</p>
                <button type="button" class="pp-btn pp-msg-btn" aria-expanded="false" aria-controls="pp-msg-more">Learn more</button>
                <div class="pp-msg-more" id="pp-msg-more" hidden>
                    <p>Tokens are shared by every business on your account. Your plan tops them up each time it renews, and unused tokens expire at renewal. Here is what uses them:</p>
                    <div class="pp-msg-grid">
                        <?php foreach ($ppTokenUses as [$name, $tone, $desc, $cost]): ?>
                            <div class="pp-msg-card t-<?= e($tone) ?>">
                                <h3><?= e($name) ?></h3>
                                <p><?= e($desc) ?></p>
                                <small><?= e($cost) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="pp-msg-note">Running low? <a href="#token-packs">Buy a token pack</a> any time from Billing in your dashboard.</p>
                </div>
            </div>
        </section>

        <section class="pp-addons-sec" id="token-packs">
            <div class="pp-wrap">
                <div class="pp-addons">
                    <h2>Keep selling with extra tokens</h2>
                    <div class="pp-addon-grid">
                        <?php foreach ($topups as $topup): ?>
                            <div class="pp-addon">
                                <h3><?= e(number_format((int) $topup['tokens'])) ?> tokens</h3>
                                <p><?= e($ppTopupBlurbs[(string) $topup['slug']] ?? 'Extra tokens on top of your monthly plan') ?></p>
                                <div class="pp-addon-foot">
                                    <?php if (!empty($topup['badge'])): ?>
                                        <span class="pp-addon-tag"><?= e((string) $topup['badge']) ?></span>
                                    <?php endif; ?>
                                    <b class="pp-addon-price"><?= e($ppMoney((float) $topup['price'])) ?><small> one-time</small></b>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="pp-addons-note">Packs sit on top of your monthly tokens and your plan stays the same.</p>
                </div>
            </div>
        </section>

        <section class="pp-experts-sec" id="experts">
            <div class="pp-wrap">
                <div class="pp-experts">
                    <h2>Grow faster with the <?= e($ppBrand) ?> team</h2>
                    <p class="pp-experts-sub">Get set up, train your salesgirl and keep selling with help when you need it.</p>
                    <div class="pp-exp-grid">
                        <?php foreach ($ppServices as [$title, $label, $items]): ?>
                            <div class="pp-exp">
                                <h3><?= e($title) ?></h3>
                                <p class="pp-exp-fee"><b>Included</b> <?= e($label) ?></p>
                                <h4>Includes:</h4>
                                <ul>
                                    <?php foreach ($items as $item): ?>
                                        <?php if (is_array($item)): ?>
                                            <li><?= e($item[0]) ?>
                                                <ul>
                                                    <?php foreach ($item[1] as $sub): ?>
                                                        <li><?= e($sub) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </li>
                                        <?php else: ?>
                                            <li><?= e($item) ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="pp-center"><a class="pp-btn pp-btn--blue" href="<?= e($ppContactUrl) ?>">Contact us</a></div>
                </div>
            </div>
        </section>

        <section class="pp-logos">
            <div class="pp-wrap">
                <h2>Works where your customers already are</h2>
                <div class="pp-logo-row">
                    <?php foreach ($ppChannels as [$name, $path]): ?>
                        <span class="pp-logo">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $path ?></svg>
                            <?= e($name) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="pp-faq">
            <div class="pp-wrap">
                <h2><?= $ppSparkAlt ?>Frequently asked questions</h2>
                <div class="pp-faq-list">
                    <?php foreach ($ppFaq as [$question, $answer]): ?>
                        <details>
                            <summary>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.3-1 .9-1 1.6v.6M12 17h.01"/></svg>
                                <?= e($question) ?>
                            </summary>
                            <p><?= e($answer) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="pp-love">
            <div class="pp-wrap">
                <h2>Loved by growing businesses</h2>
                <p>What sellers say about <?= e($ppBrand) ?>.</p>
                <div class="pp-love-grid">
                    <?php foreach ($ppQuotes as [$headline, $quote, $name, $role, $channel]): ?>
                        <figure class="pp-quote">
                            <blockquote><b>“<?= e($headline) ?>”</b><span><?= e($quote) ?></span></blockquote>
                            <figcaption>
                                <span class="pp-quote-channel pp-quote-channel--<?= e(strtolower($channel)) ?>" role="img" aria-label="<?= e($channel) ?>">
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $ppChannelPaths[$channel] ?? '' ?></svg>
                                </span>
                                <span><b><?= e($name) ?></b><small><?= e($role) ?></small></span>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    </main>

    <?php require __DIR__ . '/../home/footer.php'; ?>
</div>
<script src="<?= e($appUrl) ?>/assets/js/pricing-page.js?v=1"></script>
