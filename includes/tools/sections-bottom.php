<?php

declare(strict_types=1);

/**
 * Lower sections shared by the free WhatsApp tools.
 *
 * @var string $brandName
 * @var list<array{0: string, 1: string}> $faqs
 * @var list<string>|null $toolSections Which sections to render, in page order
 */

$toolSectionsShown = $toolSections ?? ['clients', 'advantages', 'testimonials', 'faq', 'cta'];

$channels = [
    ['WhatsApp', '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>'],
    ['Instagram', '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>'],
    ['Messenger', '<path d="M12 0C5.24 0 0 4.95 0 11.64c0 3.5 1.43 6.52 3.77 8.61.2.18.31.42.32.68l.07 2.14a.96.96 0 001.34.85l2.38-1.05c.2-.09.43-.1.64-.05 1.1.3 2.26.46 3.48.46 6.76 0 12-4.95 12-11.64S18.76 0 12 0zm7.2 8.95l-3.52 5.59a1.8 1.8 0 01-2.6.48l-2.8-2.1a.72.72 0 00-.87 0l-3.78 2.87c-.5.38-1.16-.22-.83-.76l3.53-5.59a1.8 1.8 0 012.6-.48l2.8 2.1c.26.2.61.2.87 0l3.78-2.87c.5-.38 1.16.22.82.76z"/>'],
    ['Facebook', '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>'],
    ['TikTok', '<path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>'],
];
$channelPaths = array_column($channels, 1, 0);

$clientLogoBase = rtrim((string) app_config()['app_url'], '/') . '/assets/images/clients/';
$clientLogos = [
    ['EsteBare', 'estebare', 154],
    ['Dangote', 'dangote', 178],
    ['GIGM', 'gigm', 96],
    ['Air Peace', 'air-peace', 320],
    ['This Present House', 'this-present-house', 162],
    ['Oríkì', 'oriki', 247],
    ['Nordica Fertility Centre', 'nordica', 209],
    ['Jaiz Bank', 'jaiz-bank', 320],
    ['Axon Integrated Services', 'axon', 320],
    ['StarTimes', 'startimes', 123],
    ['Mega Chicken', 'mega-chicken', 119],
    ['Dodo Pizza', 'dodo-pizza', 93],
    ["Panarotti's", 'panarottis', 269],
    ['Krispy Kreme', 'krispy-kreme', 280],
];

$advantages = [
    ['<path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/>', 'Never miss a DM', 'Your AI salesgirl replies in seconds, even at 2am, so buyers don’t move on to the next seller.'],
    ['<path d="M5 5h14v10H9l-4 4z"/><path d="M9 9h6M9 12h4"/>', 'Answers in your voice', 'She learns your products, prices and delivery rules, and talks to customers the way you would.'],
    ['<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/>', 'Gets you paid', 'She sends your payment details when a buyer is ready, so the sale closes right inside the chat.'],
    ['<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>', 'Every channel in one place', 'WhatsApp, Instagram and Messenger chats all land in one inbox you can check any time.'],
];

$reviews = [
    ['Now I wake up and see money in my account', 'I used to wake up and see customers already bought from someone else. Now I wake up and see money in my account. Lead Bird did that.', 'Adaobi', 'Instagram seller, Lagos', 'Instagram'],
    ['My phone sells even when I am not looking', 'I sell shoes. Before, I would miss messages because I was busy at the market. Now my phone sells even when I am not looking at it.', 'Chinedu', 'Shoe seller, Onitsha', 'Instagram'],
    ['The account number goes fast, every time', 'Customers would ask for my account number and I would forget to send it. They would get angry and leave. Now the account number goes fast, every time.', 'Nkechi', 'WhatsApp seller, Aba', 'WhatsApp'],
];
?>
        <?php if (in_array('clients', $toolSectionsShown, true)): ?>
        <section class="sol-clients lk-clients">
            <div class="lk-wrap">
                <h2 class="sol-clients-h"><?= $clientsHeading ?? 'Built for <strong>sellers who sell in the DMs</strong> on every channel' ?></h2>
                <div class="sol-marquee">
                    <div class="sol-marquee-track">
                        <?php for ($loop = 0; $loop < 2; $loop++): ?>
                            <?php foreach ($clientLogos as [$name, $file, $width]): ?>
                                <span class="lk-logo"<?= $loop > 0 ? ' aria-hidden="true"' : '' ?>><img src="<?= e($clientLogoBase . $file . '.webp') ?>" alt="<?= $loop > 0 ? '' : e($name) ?>" width="<?= (int) $width ?>" height="96" loading="lazy" decoding="async"></span>
                            <?php endforeach; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if (in_array('advantages', $toolSectionsShown, true)): ?>
        <section class="sol-pad lk-adv">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">Why businesses choose <?= e($brandName) ?></h2>
                <p class="lk-sub">Getting the first message is just the start. <?= e($brandName) ?>’s AI salesgirl answers every chat, quotes your prices and helps you close the sale, day and night.</p>
                <div class="lk-adv-grid">
                    <?php foreach ($advantages as [$icon, $title, $copy]): ?>
                        <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if (in_array('testimonials', $toolSectionsShown, true)): ?>
        <section class="sol-pad sol-testi lk-testi">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">What our customers say</h2>
                <p class="center lk-sub">How sellers use <?= e($brandName) ?> to talk to their customers on WhatsApp and Instagram.</p>
                <div class="ts" data-slider>
                    <div class="ts-track">
                        <?php foreach ($reviews as [$headline, $quote, $name, $role, $channel]): ?>
                            <div class="ts-slide">
                                <figure class="ts-card">
                                    <blockquote class="ts-quote"><b>“<?= e($headline) ?>”</b><br><?= e($quote) ?></blockquote>
                                    <figcaption class="ts-foot">
                                        <span class="ts-who">
                                            <span class="ts-channel ts-channel--<?= e(strtolower($channel)) ?>" role="img" aria-label="<?= e($channel) ?>"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $channelPaths[$channel] ?? '' ?></svg></span>
                                            <span><b><?= e($name) ?></b><small><?= e($role) ?></small></span>
                                        </span>
                                    </figcaption>
                                </figure>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ts-dots">
                        <?php foreach ($reviews as $i => $review): ?>
                            <button type="button"<?= $i === 0 ? ' class="on"' : '' ?> aria-label="Show review <?= $i + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if (in_array('faq', $toolSectionsShown, true)): ?>
        <section class="sol-pad lk-faq">
            <div class="lk-wrap">
                <h2 class="sol-h2">Frequently asked questions</h2>
                <?php foreach ($faqs as [$question, $answer]): ?>
                    <details>
                        <summary><?= e($question) ?></summary>
                        <p><?= e($answer) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (in_array('cta', $toolSectionsShown, true)): ?>
        <section class="sol-pad">
            <div class="lk-wrap">
                <div class="lk-cta">
                    <h2>Discover more</h2>
                    <p>WhatsApp is where your customers already are. Turn it into a sales channel with an AI salesgirl from <?= e($brandName) ?>.</p>
                    <div class="sol-btns">
                        <a class="sol-btn white" href="<?= e(app_route('signup')) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e(app_route('plans')) ?>">See pricing</a>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
