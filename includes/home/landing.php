<?php

declare(strict_types=1);

/** @var string $appUrl */
require_once __DIR__ . '/../pricing/cards.php';
require_once __DIR__ . '/../pricing/carousel.php';

$loginUrl = e(app_route('login'));
$signupUrl = e(app_route('signup'));
$pricingTiers = PlanService::list();

$products = [
    ['title' => 'Whatsapp salesgirl', 'url' => app_route('signup'), 'icon' => 'whatsapp'],
    ['title' => 'Instagram salesgirl', 'url' => app_route('signup'), 'icon' => 'instagram'],
    ['title' => 'Tiktok salesgirl', 'url' => app_route('signup'), 'icon' => 'tiktok'],
];

function landing_render_product_icon(string $icon): void
{
    match ($icon) {
        'whatsapp' => print '<svg viewBox="0 0 24 24" class="size-4" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
        'instagram' => print '<svg viewBox="0 0 24 24" class="size-4" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
        'tiktok' => print '<svg viewBox="0 0 24 24" class="size-4" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
        default => null,
    };
}

/** @param array{title: string, url: string, icon: string} $product */
function landing_render_product_link(array $product, bool $mobile = false): void
{
    $iconClass = match ($product['icon']) {
        'whatsapp' => 'landing-product-icon landing-product-icon-whatsapp',
        'instagram' => 'landing-product-icon landing-product-icon-instagram',
        'tiktok' => 'landing-product-icon landing-product-icon-tiktok',
        default => 'landing-product-icon',
    };

    if ($mobile) {
        ?>
        <a href="<?= e($product['url']) ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-900 border-b border-slate-100 last:border-b-0 hover:bg-slate-50">
            <span class="<?= e($iconClass) ?>">
                <?php landing_render_product_icon($product['icon']); ?>
            </span>
            <span><?= e($product['title']) ?></span>
        </a>
        <?php

        return;
    }
    ?>
    <a href="<?= e($product['url']) ?>" class="landing-product-link">
        <span class="<?= e($iconClass) ?>">
            <?php landing_render_product_icon($product['icon']); ?>
        </span>
        <span class="landing-product-link-label"><?= e($product['title']) ?></span>
        <svg class="landing-product-link-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"></path>
        </svg>
    </a>
    <?php
}

$features = [
    ['icon' => 'message-circle', 'title' => 'Answers Questions Instantly', 'description' => 'Size, price, location, delivery? Handled.'],
    ['icon' => 'wallet', 'title' => 'Sends Payment Details', 'description' => 'Your account number, wallet, or link. Automatically.'],
    ['icon' => 'receipt', 'title' => 'Delivers Receipts', 'description' => 'Customers get proof of payment request instantly.'],
    ['icon' => 'clock', 'title' => 'Closes Deals 24/7', 'description' => 'Even at 2 AM when you\'re asleep.'],
    ['icon' => 'audio-lines', 'title' => 'Speaks Your Customer\'s Tone', 'description' => 'Friendly, professional, or street-smart. You choose.'],
    ['icon' => 'brain', 'title' => 'Learns Your Business', 'description' => 'The more she chats, the smarter she gets about your products and customers.'],
];

function landing_render_feature_icon(string $icon): void
{
    $svgOpen = '<svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';

    match ($icon) {
        'message-circle' => print $svgOpen . '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path></svg>',
        'wallet' => print $svgOpen . '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path></svg>',
        'receipt' => print $svgOpen . '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-6"></path><path d="M16 12h-6"></path><path d="M16 16h-6"></path></svg>',
        'clock' => print $svgOpen . '<circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>',
        'audio-lines' => print $svgOpen . '<path d="M2 10v3"></path><path d="M6 6v11"></path><path d="M10 3v18"></path><path d="M14 8v7"></path><path d="M18 5v13"></path><path d="M22 10v3"></path></svg>',
        'brain' => print $svgOpen . '<path d="M12 5a3 3 0 1 0-5.997.125 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588A4 4 0 1 0 12 18Z"></path><path d="M12 5a3 3 0 1 1 5.997.125 4 4 0 0 1 2.526 5.77 4 4 0 0 1-.556 6.588A4 4 0 1 1 12 18Z"></path><path d="M15 13a4.5 4.5 0 0 1-3-4 4.5 4.5 0 0 1-3 4"></path><path d="M17.599 6.5a3 3 0 0 0 .399-1.375"></path><path d="M6.003 5.125A3 3 0 0 0 6.401 6.5"></path><path d="M3.477 10.896a4 4 0 0 1 .585-.396"></path><path d="M19.938 10.5a4 4 0 0 1 .585.396"></path><path d="M6 18a4 4 0 0 1-1.967-.516"></path><path d="M19.967 17.484A4 4 0 0 1 18 18"></path></svg>',
        default => null,
    };
}

$testimonials = [
    ['text' => 'I used to wake up and see customers already bought from someone else. Now I wake up and see money in my account. Lead Bird did that.', 'name' => 'Adaobi', 'role' => 'Lagos', 'channel' => 'instagram'],
    ['text' => 'I sell shoes. Before, I would miss messages because I was busy at the market. Now my phone sells even when I am not looking at it.', 'name' => 'Chinedu', 'role' => 'Onitsha', 'channel' => 'instagram'],
    ['text' => 'Customers would ask for my account number and I would forget to send it. They would get angry and leave. Now the account number goes fast, every time.', 'name' => 'Nkechi', 'role' => 'Aba', 'channel' => 'whatsapp'],
    ['text' => 'I used to sleep with my phone under my pillow so I would not miss a sale. Now I sleep normal. The sales still come.', 'name' => 'Emeka', 'role' => 'Enugu', 'channel' => 'instagram'],
    ['text' => 'I have three children. I cannot hold my phone all day. Before, I lost many buyers. Now they get answers fast, even when I am cooking.', 'name' => 'Fatima', 'role' => 'Kano', 'channel' => 'instagram'],
    ['text' => 'I would reply to one person, then ten more would message me. I could not catch up. Now all of them get answers. I just pack my orders.', 'name' => 'Olumide', 'role' => 'Ibadan', 'channel' => 'tiktok'],
    ['text' => 'Someone wanted to buy bags worth a lot of money. I saw the message too late. She bought from my friend instead. That will never happen again.', 'name' => 'Chioma', 'role' => 'Port Harcourt', 'channel' => 'instagram'],
    ['text' => 'I sell the same thing every day. I got tired of typing prices over and over. Now I just check who paid and send their stuff.', 'name' => 'Yusuf', 'role' => 'Abuja', 'channel' => 'whatsapp'],
    ['text' => 'My customers used to say I take too long to reply. Now they say I am very fast. I do not tell them my secret.', 'name' => 'Amaka', 'role' => 'Lagos', 'channel' => 'instagram'],
];

function landing_render_testimonial_stars(): void
{
    ?>
    <div class="landing-testimonial-stars flex gap-1 mb-4" aria-label="5 out of 5 stars" role="img">
        <?php for ($i = 0; $i < 5; $i++): ?>
            <svg viewBox="0 0 24 24" class="size-4 text-amber-400" fill="currentColor" aria-hidden="true">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
            </svg>
        <?php endfor; ?>
    </div>
    <?php
}

function landing_render_testimonial_channel(string $channel): void
{
    $label = match ($channel) {
        'whatsapp' => 'WhatsApp',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        default => 'Social channel',
    };

    $wrapClass = match ($channel) {
        'whatsapp' => 'w-10 h-10 rounded-full bg-brand-whatsapp flex items-center justify-center flex-shrink-0 shadow-inner',
        'instagram' => 'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 bg-gradient-to-br from-brand-instagram-purple via-brand-instagram-red to-brand-instagram-orange shadow-inner',
        'tiktok' => 'w-10 h-10 rounded-full bg-black flex items-center justify-center flex-shrink-0 border border-white/30 shadow-inner',
        default => 'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
    };

    $iconClass = 'w-5 h-5 text-white';

    ?>
    <span class="<?= e($wrapClass) ?>" aria-label="<?= e($label) ?>">
        <?php
        match ($channel) {
            'whatsapp' => print '<svg viewBox="0 0 24 24" class="' . $iconClass . '" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path></svg>',
            'instagram' => print '<svg viewBox="0 0 24 24" class="' . $iconClass . '" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"></path></svg>',
            'tiktok' => print '<svg viewBox="0 0 24 24" class="' . $iconClass . '" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"></path></svg>',
            default => null,
        };
        ?>
    </span>
    <?php
}

/** @param array<int, array{text: string, name: string, role: string, channel: string}> $items */
function landing_render_testimonial_card(array $item): void
{
    ?>
    <div class="landing-testimonial-card p-10 rounded-3xl border border-gray-200 shadow-lg shadow-brand-blue/10 max-w-xs w-full bg-white">
        <?php landing_render_testimonial_stars(); ?>
        <div><?= e($item['text']) ?></div>
        <div class="flex items-center gap-2 mt-5">
            <?php landing_render_testimonial_channel($item['channel']); ?>
            <div class="flex flex-col">
                <div class="font-medium tracking-tight leading-5 text-neutral-900"><?= e($item['name']) ?></div>
                <div class="leading-5 opacity-60 tracking-tight text-neutral-900"><?= e($item['role']) ?></div>
            </div>
        </div>
    </div>
    <?php
}

/** @param array<int, array{text: string, name: string, role: string, channel: string}> $items */
function landing_render_testimonials_column(array $items, int $duration = 10, string $className = ''): void
{
    $classes = trim('landing-testimonials-column ' . $className);
    ?>
    <div class="<?= e($classes) ?>">
        <div class="landing-testimonials-track flex flex-col gap-6 pb-6 bg-white" style="animation-duration: <?= (int) $duration ?>s">
            <?php for ($set = 0; $set < 2; $set++): ?>
                <?php foreach ($items as $item): ?>
                    <?php landing_render_testimonial_card($item); ?>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php
}

$faqItems = [
    [
        'question' => 'Will my customers know it\'s a robot?',
        'answer' => 'Only if you want them to. You pick how she talks — friendly, sharp, or calm. Most customers think you just type fast.',
    ],
    [
        'question' => 'What if she doesn\'t know the answer?',
        'answer' => 'She pauses and texts you privately. You reply once, she learns it, and never asks again. Your knowledge stays in her head.',
    ],
    [
        'question' => 'I sell on WhatsApp, Instagram, and TikTok. Do I need three plans?',
        'answer' => 'No. The Vendor plan gives you 3 businesses. The Shop plan gives you as many as you need. One dashboard for all of them.',
    ],
    [
        'question' => 'Can she send my real account number? Is that safe?',
        'answer' => 'Yes. You enter it once. She sends only what you gave her. We never touch your money — we just share the details faster than you can type.',
    ],
    [
        'question' => 'I get 50 DMs saying \'How much?\' every day. Will this help?',
        'answer' => 'That is exactly why we built this. She answers price, size, color, and location in seconds. You stop typing the same thing forever.',
    ],
    [
        'question' => 'What about people who say \'I will pay tomorrow\' and vanish?',
        'answer' => 'The Shop plan chases them. She sends polite reminders, checks if they still want it, and brings them back to pay. You do not lose sleep over forgotten orders.',
    ],
    [
        'question' => 'My phone dies at the market. What happens to my DMs?',
        'answer' => 'She keeps working. Your phone can be off, dead, or left at home. Customers still get replies, prices, and receipts. You check sales when you are ready.',
    ],
    [
        'question' => 'Do I need to learn coding or tech stuff?',
        'answer' => 'No. If you can use WhatsApp, you can use Lead Bird. Connect your account, upload your list, and she starts in minutes.',
    ],
];

$footerLinks = [
    ['label' => 'Home', 'url' => $appUrl . '/'],
    ['label' => 'Products', 'url' => '#'],
    ['label' => 'Pricing', 'url' => app_route('plans')],
    ['label' => 'Refer & Earn', 'url' => app_route('affiliates')],
    ['label' => 'Contact', 'url' => app_route('contact')],
    ['label' => 'Terms of Use', 'url' => app_route('terms')],
    ['label' => 'Privacy Policy', 'url' => app_route('privacy')],
];

function landing_render_get_started_cta(string $signupUrl): void
{
    ?>
    <div class="landing-section-cta">
        <a
            href="<?= e($signupUrl) ?>"
            class="landing-cta inline-flex items-center gap-2.5 rounded-full bg-brand-lime px-10 py-4 md:px-12 md:py-5 text-base md:text-lg font-black uppercase tracking-wide text-brand-black transition-all duration-300 hover:bg-brand-lime-hover"
        >
            Get Started
            <svg class="size-4 md:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6"></path>
            </svg>
        </a>
    </div>
    <?php
}

function landing_render_faq_item(array $item, int $index): void
{
    $id = 'landing-faq-panel-' . $index;
    ?>
    <div class="landing-faq-item group rounded-2xl bg-white transition-all duration-200 ease-in-out" data-faq-item>
        <button
            type="button"
            class="landing-faq-trigger flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
            data-faq-trigger
            aria-expanded="false"
            aria-controls="<?= e($id) ?>"
        >
            <h3 class="font-heading text-lg md:text-xl font-semibold text-black">
                <?= e($item['question']) ?>
            </h3>
            <span class="landing-faq-chevron flex-shrink-0 rounded-full p-0.5 text-black transition-all duration-200 group-[.is-open]:scale-110" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </button>
        <div id="<?= e($id) ?>" class="landing-faq-panel" data-faq-panel aria-hidden="true">
            <div class="landing-faq-panel-inner px-6 pb-5 pt-0">
                <p class="text-base md:text-lg leading-relaxed text-black">
                    <?= e($item['answer']) ?>
                </p>
            </div>
        </div>
    </div>
    <?php
}

?>
<div class="min-h-screen bg-brand-blue flex flex-col selection:bg-brand-lime selection:text-black relative w-full">
    <div class="brand-grid-bg absolute inset-0 pointer-events-none z-0"></div>

    <nav class="landing-nav sticky top-0 z-50 w-full">
        <div class="px-6 py-6 md:px-10 md:py-8 max-w-[1440px] mx-auto w-full">
        <div class="flex items-center justify-between gap-4">
            <?php $logoSize = 'md'; $logoHref = e($appUrl) . '/'; require dirname(__DIR__) . '/brand-logo.php'; ?>

            <div class="hidden md:flex items-center justify-center gap-2 flex-1">
                <a href="<?= e($appUrl) ?>/" class="landing-nav-link px-4 py-1.5 rounded-full border border-white/30 text-white text-xs font-semibold hover:bg-white/10 transition-colors">Home</a>

                <div class="landing-nav-dropdown relative">
                    <button
                        type="button"
                        class="landing-nav-link inline-flex items-center gap-1 px-4 py-1.5 rounded-full border border-white/30 text-white text-xs font-semibold hover:bg-white/10 transition-colors"
                        aria-expanded="false"
                        aria-haspopup="true"
                        data-nav-dropdown-trigger
                    >
                        Products
                        <svg class="landing-nav-chevron size-3 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>
                    <div class="landing-nav-dropdown-panel absolute left-1/2 top-full z-50 -translate-x-1/2 pt-2" data-nav-dropdown-panel>
                        <div class="landing-nav-menu">
                            <span class="landing-nav-menu-arrow" aria-hidden="true"></span>
                            <?php foreach ($products as $product): ?>
                                <?php landing_render_product_link($product); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <a href="<?= e(app_route('plans')) ?>" class="landing-nav-link px-4 py-1.5 rounded-full border border-white/30 text-white text-xs font-semibold hover:bg-white/10 transition-colors">Pricing</a>
                <a href="<?= e(app_route('contact')) ?>" class="landing-nav-link px-4 py-1.5 rounded-full border border-white/30 text-white text-xs font-semibold hover:bg-white/10 transition-colors">Contact</a>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    id="landing-mobile-menu-trigger"
                    class="landing-menu-toggle md:hidden"
                    aria-expanded="false"
                    aria-controls="landing-mobile-drawer"
                    aria-label="Open menu"
                >
                    <span class="landing-menu-toggle-box" aria-hidden="true">
                        <span class="landing-menu-toggle-line"></span>
                        <span class="landing-menu-toggle-line"></span>
                        <span class="landing-menu-toggle-line"></span>
                    </span>
                </button>
                <a href="<?= $loginUrl ?>" class="landing-nav-auth landing-nav-auth--ghost hidden md:inline-flex px-4 md:px-5 py-2 rounded-full border border-white text-white text-xs md:text-sm font-semibold hover:bg-white hover:text-brand-blue transition-colors">Sign in</a>
                <a href="<?= $signupUrl ?>" class="landing-nav-auth landing-nav-auth--solid hidden md:inline-flex px-4 md:px-5 py-2 rounded-full bg-white text-brand-blue text-xs md:text-sm font-semibold hover:bg-white/90 transition-colors">Sign up</a>
            </div>
        </div>
        </div>
    </nav>

    <div id="landing-drawer-backdrop" class="landing-drawer-backdrop md:hidden" aria-hidden="true" data-landing-drawer-backdrop></div>

    <aside
        id="landing-mobile-drawer"
        class="landing-drawer md:hidden"
        aria-hidden="true"
        aria-label="Mobile menu"
        data-landing-drawer
    >
        <div class="landing-drawer-header">
            <div class="landing-drawer-logo">
                <?php
                $logoSize = 'sm';
                $logoHref = e($appUrl) . '/';
                require dirname(__DIR__) . '/brand-logo.php';
                ?>
            </div>
            <button
                type="button"
                class="landing-drawer-close"
                aria-label="Close menu"
                data-landing-drawer-close
            >
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>

        <nav class="landing-drawer-nav" aria-label="Mobile">
            <a href="<?= e($appUrl) ?>/" class="landing-drawer-link" data-landing-drawer-link>Home</a>

            <div class="landing-drawer-products" data-landing-drawer-products>
                <button
                    type="button"
                    class="landing-drawer-products-trigger"
                    data-mobile-products-trigger
                    aria-expanded="false"
                >
                    <span>Products</span>
                    <svg class="landing-mobile-chevron size-4 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </button>
                <div class="landing-drawer-products-panel hidden" data-mobile-products-panel>
                    <?php foreach ($products as $product): ?>
                        <?php landing_render_product_link($product, true); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="<?= e(app_route('plans')) ?>" class="landing-drawer-link" data-landing-drawer-link>Pricing</a>
            <a href="<?= e(app_route('contact')) ?>" class="landing-drawer-link" data-landing-drawer-link>Contact</a>
        </nav>

        <div class="landing-drawer-footer">
            <p class="landing-drawer-footer-copy">Start selling in the DMs with your AI Salesgirl.</p>
            <a href="<?= $signupUrl ?>" class="landing-drawer-cta landing-drawer-cta--primary font-heading" data-landing-drawer-link>Sign up</a>
            <a href="<?= $loginUrl ?>" class="landing-drawer-cta landing-drawer-cta--ghost font-heading" data-landing-drawer-link>Sign in</a>
        </div>
    </aside>

    <main class="flex-1 relative z-10 pt-8 pb-32 md:pt-12 md:pb-48 px-4 flex flex-col items-center justify-center w-full max-w-[1440px] mx-auto">
        <div class="relative w-full max-w-5xl mx-auto flex flex-col items-center justify-center text-center z-10 mt-4 mb-16">
            <div class="relative w-full">
                <div class="w-full flex flex-col items-center relative z-10 gap-2 md:gap-4">
                    <div class="w-full flex justify-start pl-[10%] md:pl-[25%] relative z-20">
                        <h1 class="landing-hero-title text-[clamp(4.5rem,12vw,160px)] font-black leading-[0.85] tracking-tighter text-brand-lime m-0 p-0 uppercase">
                            NEVER
                        </h1>
                    </div>

                    <div class="w-full flex justify-center relative z-20">
                        <h1 class="landing-hero-title whitespace-nowrap text-[clamp(4.5rem,12vw,160px)] font-black leading-[0.85] tracking-tighter text-white m-0 p-0 uppercase">
                            MISS DM's
                        </h1>
                    </div>

                    <div class="w-full flex justify-start pl-[15%] md:pl-[30%] relative z-10">
                        <h1 class="landing-hero-title text-[clamp(4.5rem,12vw,160px)] font-black leading-[0.85] tracking-tighter text-white m-0 p-0 uppercase">
                            AGAIN
                        </h1>
                    </div>
                </div>

                <div class="absolute inset-0 w-full h-full pointer-events-none">
                    <div class="landing-float-1 hidden md:block absolute top-[-8%] left-[2%] md:top-[-10%] md:left-[6%] z-40 pointer-events-auto">
                        <div class="w-40 md:w-52 aspect-[3/3.5] bg-white/20 backdrop-blur-md border border-white/40 rounded-[2rem] p-5 flex flex-col items-center justify-center rotate-[-12deg] shadow-2xl hover:rotate-0 transition-transform duration-500">
                            <div class="relative mb-4">
                                <div class="w-16 h-16 md:w-24 md:h-24 bg-black rounded-full flex items-center justify-center shadow-inner border-[3px] border-white/50">
                                    <svg viewBox="0 0 24 24" class="w-8 h-8 md:w-12 md:h-12 text-white" fill="currentColor" aria-hidden="true">
                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                    </svg>
                                </div>
                                <div class="landing-float-badge absolute -top-2 -right-3 md:-top-3 md:-right-4 min-w-[2rem] md:min-w-[2.5rem] rounded-2xl bg-brand-red px-2 py-1 text-center text-[10px] md:text-xs font-black leading-none text-white shadow-lg" aria-label="58 unanswered messages">
                                    58
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <p class="font-bold text-sm md:text-lg text-white">Tiktok DM's</p>
                                <p class="text-[10px] md:text-xs text-white/80 mt-1">58 unanswered messages</p>
                            </div>
                        </div>
                    </div>

                    <div class="landing-float-2 hidden md:block absolute top-[45%] left-[54%] md:top-[63%] md:left-[84%] z-40 pointer-events-auto">
                        <div class="w-40 md:w-52 aspect-[3/3.5] bg-white/20 backdrop-blur-md border border-white/40 rounded-[2rem] p-5 flex flex-col items-center justify-center rotate-[12deg] shadow-2xl hover:rotate-0 transition-transform duration-500">
                            <div class="relative mb-4">
                                <div class="w-16 h-16 md:w-24 md:h-24 rounded-full flex items-center justify-center shadow-inner border-[3px] border-white/50 bg-gradient-to-br from-brand-instagram-purple via-brand-instagram-red to-brand-instagram-orange">
                                    <svg viewBox="0 0 24 24" class="w-8 h-8 md:w-12 md:h-12 text-white" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                    </svg>
                                </div>
                                <div class="landing-float-badge absolute -top-2 -right-3 md:-top-3 md:-right-4 min-w-[2rem] md:min-w-[2.5rem] rounded-2xl bg-brand-red px-2 py-1 text-center text-[10px] md:text-xs font-black leading-none text-white shadow-lg" aria-label="35 unanswered messages">
                                    35
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <p class="font-bold text-sm md:text-lg text-white">Instagram DM's</p>
                                <p class="text-[10px] md:text-xs text-white/80 mt-1">35 unanswered messages</p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute bottom-[0%] left-[0%] md:left-[10%] w-24 h-24 md:w-32 md:h-32 z-20">
                        <svg viewBox="0 0 100 100" class="w-full h-full text-brand-lime stroke-current overflow-visible" fill="none" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10,90 C 10,40 40,20 60,50 C 70,65 80,75 95,70"></path>
                            <path d="M80,55 L95,70 L85,85"></path>
                        </svg>
                    </div>

                    <div class="absolute top-[5%] right-[0%] md:right-[10%] w-24 h-24 md:w-32 md:h-32 z-20">
                        <svg viewBox="0 0 100 100" class="w-full h-full text-brand-lime stroke-current overflow-visible" fill="none" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M90,10 C 80,60 60,80 40,60 C 20,40 40,20 60,30 C 80,40 70,70 50,80"></path>
                            <path d="M65,75 L50,80 L55,65"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="w-full relative z-50">
                <?php landing_render_get_started_cta($signupUrl); ?>
            </div>
        </div>
    </main>

    <section class="bg-white text-black rounded-t-[2.5rem] md:rounded-t-[3.5rem] px-6 py-[50px] md:px-10 relative z-20 shadow-[0_-20px_50px_rgba(0,0,0,0.2)] -mt-[100px] w-full">
        <div class="landing-section-content grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
            <div class="bg-brand-surface rounded-[2rem] p-8 flex flex-col items-center text-center relative h-64 border border-gray-100">
                <h3 class="text-xl md:text-2xl uppercase leading-tight mb-2 font-black">
                    CONNECT YOUR<br>SOCIAL ACCOUNTS
                </h3>
                <p class="text-[10px] md:text-xs text-black/60 font-bold mb-auto">
                    Link WhatsApp, Instagram, Messenger, or TikTok
                </p>

                <div class="relative w-full flex justify-center mt-6">
                    <div class="flex items-center bg-brand-blue rounded-2xl p-2.5 text-white shadow-lg relative z-10">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-brand-whatsapp flex items-center justify-center flex-shrink-0 shadow-inner">
                                <svg viewBox="0 0 24 24" class="w-4 h-4 text-white" fill="currentColor" aria-hidden="true">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-gradient-to-br from-brand-instagram-purple via-brand-instagram-red to-brand-instagram-orange shadow-inner">
                                <svg viewBox="0 0 24 24" class="w-4 h-4 text-white" fill="currentColor" aria-hidden="true">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                </svg>
                            </div>
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden shadow-inner" aria-hidden="true">
                                <img
                                    src="<?= e($appUrl) ?>/assets/images/facebook-messenger-icon.png"
                                    alt=""
                                    class="w-full h-full object-cover"
                                    width="32"
                                    height="32"
                                    loading="lazy"
                                >
                            </div>
                            <div class="w-8 h-8 rounded-full bg-black flex items-center justify-center flex-shrink-0 border border-white/30 shadow-inner">
                                <svg viewBox="0 0 24 24" class="w-4 h-4 text-white" fill="currentColor" aria-hidden="true">
                                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden md:block absolute -right-12 bottom-8 w-16 h-16 z-30">
                    <svg viewBox="0 0 100 100" class="w-full h-full text-black stroke-current overflow-visible" fill="none" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20,80 Q 40,20 80,40"></path>
                        <path d="M60,20 L80,40 L50,60"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-brand-surface rounded-[2rem] p-8 flex flex-col items-center text-center relative h-64 border border-gray-100">
                <h3 class="text-xl md:text-2xl uppercase leading-tight mb-2 font-black">
                    TRAIN YOUR<br>AI SALESGIRL
                </h3>
                <p class="text-[10px] md:text-xs text-black/60 font-bold mb-auto">
                    Upload your products, business info, prices, and bank details — then let it sell while you sleep.
                </p>

                <div class="relative w-full flex justify-center mt-6">
                    <div class="flex items-center bg-brand-blue rounded-full p-1.5 text-white shadow-lg">
                        <div class="relative bg-white/20 rounded-full px-4 py-2 mr-2">
                            <svg viewBox="0 0 24 24" class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <div class="landing-badge-circle absolute -top-2 -right-3 size-5 rounded-full bg-brand-red text-[10px] font-black text-white shadow-lg" aria-label="5 items in cart">
                                5
                            </div>
                        </div>
                        <div class="font-bold text-xs px-4">
                            ₦500K
                        </div>
                    </div>
                </div>

                <div class="hidden md:block absolute -right-12 bottom-8 w-16 h-16 z-30">
                    <svg viewBox="0 0 100 100" class="w-full h-full text-black stroke-current overflow-visible" fill="none" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20,80 Q 40,20 80,40"></path>
                        <path d="M60,20 L80,40 L50,60"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-brand-surface rounded-[2rem] p-8 flex flex-col items-center text-center relative h-64 border border-gray-100">
                <h3 class="text-xl md:text-2xl uppercase leading-tight mb-2 font-black">
                    NEVER MISS<br>ANOTHER DM
                </h3>
                <p class="text-[10px] md:text-xs text-black/60 font-bold mb-auto">
                    Lead Bird replies instantly, closes deals, sends receipts, or moves discussion to Whatsapp to continue conversations
                </p>

                <div class="flex flex-col items-center bg-brand-lime rounded-2xl px-4 py-2.5 text-black shadow-lg mt-6 relative max-w-[9.5rem]">
                    <p class="text-sm md:text-base font-black leading-tight">40 New Sales</p>
                    <div class="absolute -bottom-1.5 left-6 w-3.5 h-3.5 bg-brand-lime transform rotate-45"></div>
                </div>
            </div>
        </div>
        <?php landing_render_get_started_cta($signupUrl); ?>
    </section>

    <section class="bg-brand-surface w-full py-[150px] px-4 md:px-8 lg:px-16 relative">
        <div class="landing-section-content">
            <div class="flex justify-center mb-8">
                <div class="bg-[var(--brand-badge-muted)] text-black px-5 py-2 rounded-full text-sm md:text-base uppercase tracking-wider font-heading font-semibold">
                    You're Losing Sales in the DMs
                </div>
            </div>

            <div class="text-center relative text-neutral-900">
                <p class="text-2xl md:text-3xl lg:text-4xl font-semibold leading-normal md:leading-snug">
                    Every unanswered DM is a lost customer. Every &ldquo;How much?&rdquo; you miss is money gone.
                    <span class="landing-highlight text-[1.65rem] md:text-[2.15rem] lg:text-[2.85rem] font-bold">Many business owners lose their customers to the next vendor who replies faster.</span>
                    You didn't start selling online to spend 24 hours a day typing replies.
                </p>
            </div>
            <?php landing_render_get_started_cta($signupUrl); ?>
        </div>
    </section>

    <section class="relative bg-brand-blue w-full py-[150px] overflow-hidden">
        <div class="brand-grid-bg absolute inset-0 pointer-events-none z-0"></div>

        <div class="landing-section-content relative z-10 space-y-8 md:space-y-16">
            <div class="relative mx-auto max-w-xl space-y-6 text-center md:space-y-12">
                <h2 class="text-balance text-4xl font-medium lg:text-5xl text-white">
                    What she does
                </h2>
                <p class="text-xl md:text-2xl lg:text-3xl text-white leading-relaxed">
                    Lead Bird replies instantly, closes deals, sends receipts, or moves discussion to Whatsapp to continue conversations
                </p>
            </div>

            <div class="landing-features-3d-grid relative mx-auto grid w-full gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                <?php foreach ($features as $feature): ?>
                    <article class="landing-feature-card" data-feature-card tabindex="0">
                        <div class="landing-feature-card-inner">
                            <div class="landing-feature-card-shine" aria-hidden="true"></div>
                            <div class="landing-feature-card-glare" aria-hidden="true"></div>
                            <div class="landing-feature-card-content space-y-4">
                                <div class="flex items-center gap-3">
                                    <span class="landing-feature-card-icon-wrap">
                                        <?php landing_render_feature_icon($feature['icon']); ?>
                                    </span>
                                    <h3 class="text-base md:text-lg font-semibold text-white"><?= e($feature['title']) ?></h3>
                                </div>
                                <p class="text-base md:text-lg text-white leading-relaxed"><?= e($feature['description']) ?></p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php landing_render_get_started_cta($signupUrl); ?>
        </div>
    </section>

    <section class="bg-white w-full py-[150px] overflow-hidden">
        <div class="landing-section-content">
            <div class="flex flex-col items-center justify-center max-w-[540px] mx-auto mb-16">
                <div class="flex justify-center">
                    <div class="border border-gray-200 py-1 px-4 rounded-lg text-sm font-heading text-neutral-900">Testimonials</div>
                </div>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight mt-5 text-center text-neutral-900">
                    What our users say
                </h2>
                <p class="text-center mt-5 text-lg md:text-xl lg:text-2xl opacity-75 text-neutral-600 leading-relaxed">
                    Discover how thousands of teams streamline their operations with our platform.
                </p>
            </div>

            <div class="landing-testimonials-columns flex justify-center gap-6">
                <?php landing_render_testimonials_column(array_slice($testimonials, 0, 3), 15); ?>
                <?php landing_render_testimonials_column(array_slice($testimonials, 3, 3), 19, 'hidden md:block'); ?>
                <?php landing_render_testimonials_column(array_slice($testimonials, 6, 3), 17, 'hidden lg:block'); ?>
            </div>
            <?php landing_render_get_started_cta($signupUrl); ?>
        </div>
    </section>

    <section id="pricing" class="bg-white w-full py-[150px] overflow-hidden">
        <div class="landing-section-content">
            <div class="relative w-full max-w-6xl mx-auto px-4">
                <div class="text-center space-y-6 mb-16">
                    <div class="relative">
                        <h2 class="text-4xl md:text-5xl font-bold text-zinc-900 -rotate-1">
                            Pricing
                        </h2>
                        <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-44 h-3 bg-blue-500/20 -rotate-1 rounded-full blur-sm" aria-hidden="true"></div>
                    </div>
                </div>

                <?php pricing_carousel_open(); ?>
                    <?php foreach ($pricingTiers as $index => $tier): ?>
                        <div class="landing-pricing-tier pricing-carousel-slide">
                            <?php pricing_render_tier($tier, $index, 'signup'); ?>
                        </div>
                    <?php endforeach; ?>
                <?php pricing_carousel_close(); ?>

                <div class="absolute -z-10 inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                    <div class="absolute top-40 left-20 text-4xl rotate-12">✎</div>
                    <div class="absolute bottom-40 right-20 text-4xl -rotate-12">✏️</div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-faq-section relative z-10 w-full py-[150px]">
        <div class="landing-section-content">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <h2 class="mb-5 text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight text-black">
                    Frequently asked questions
                </h2>
                <p class="text-lg md:text-xl lg:text-2xl leading-relaxed text-black/70">
                    Everything you need to know about Lead Bird and how she helps you sell in the DMs.
                </p>
            </div>

            <div class="mx-auto max-w-2xl space-y-3">
                <?php foreach ($faqItems as $index => $item): ?>
                    <?php landing_render_faq_item($item, $index); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/footer.php'; ?>
</div>
