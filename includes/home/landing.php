<?php

declare(strict_types=1);

/** @var string $appUrl */

$signupUrl = e(app_route('signup'));

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

?>
<div class="min-h-screen bg-brand-blue flex flex-col selection:bg-brand-lime selection:text-black relative w-full">
    <div class="brand-grid-bg absolute inset-0 pointer-events-none z-0"></div>

    <?php $activeNav = 'home'; require __DIR__ . '/site-header.php'; ?>

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

    <section class="landing-chx bg-white text-brand-black rounded-t-[2.5rem] md:rounded-t-[3.5rem] relative z-20 shadow-[0_-20px_50px_rgba(0,0,0,0.2)] -mt-[100px] w-full">
        <div class="landing-chx-wrap landing-chx-reveal">
            <div class="landing-chx-grid">
                <div class="landing-chx-head">
                    <span class="landing-chx-eyebrow">Every channel</span>
                    <h2 class="landing-chx-title">
                        <span>Business</span> <span>conversations</span> <span>made</span> <span>simple,</span>
                        <span>across</span> <span>all</span> <span>your</span> <span>channels</span>
                    </h2>
                    <p class="landing-chx-lead">WhatsApp, Instagram, Messenger and Facebook: Lead Bird pulls every DM into one inbox and your AI Salesgirl answers them, so you stop jumping between apps and start closing sales.</p>
                    <div class="landing-chx-cta">
                        <a href="<?= e($signupUrl) ?>" class="landing-cta inline-flex items-center justify-center gap-2.5 rounded-full px-8 py-4 md:px-10 text-base md:text-lg font-black uppercase tracking-wide transition-all duration-300">
                            Get Started
                            <svg class="size-4 md:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="landing-chx-art">
                    <svg class="landing-chx-svg" role="img" aria-label="Lead Bird connects WhatsApp, Instagram, Messenger, Facebook and TikTok, with X and LinkedIn coming next, plus bookings and owner alerts" viewBox="0 0 480 480" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle class="chx-ring" cx="240" cy="240" r="175" stroke-width="2"/>
                        <circle class="chx-z chx-z1" cx="240" cy="240" r="125"/>
                        <circle class="chx-z chx-z2" cx="240" cy="240" r="100"/>
                        <circle class="chx-z chx-z3" cx="240" cy="240" r="75"/>
                        <circle cx="240" cy="240" r="54" fill="white"/>
                        <image href="<?= e($appUrl) ?>/assets/images/leadbird-favicon.png" x="207" y="207" width="66" height="66"/>

                        <!-- Top: WhatsApp -->
                        <g>
                            <g class="chx-tb" style="animation-delay:0s">
                                <circle cx="240" cy="65" r="40" fill="white"/>
                                <path transform="translate(240 65) scale(.62) translate(-239.2 -240.4)" d="M266.198 213.04C258.998 205.84 249.398 202 239.318 202C218.438 202 201.158 219.04 201.158 239.92C201.158 246.64 202.838 253.12 206.198 258.88L200.918 278.8L221.078 273.52C226.598 276.64 232.838 278.08 239.318 278.08C260.198 278.08 277.478 261.04 277.478 240.16C277.238 229.84 273.398 220.24 266.198 213.04ZM239.318 271.6C233.558 271.6 228.038 270.16 223.238 267.28L222.038 266.56L210.038 269.68L213.158 257.92L212.438 256.72C209.318 251.68 207.638 245.92 207.638 239.920C207.638 222.64 221.798 208.48 239.318 208.48C247.718 208.480 255.638 211.84 261.638 217.84C267.638 223.84 270.998 231.76 270.998 240.16C270.998 257.44 256.598 271.6 239.318 271.6ZM256.598 248.08C255.638 247.6 251.078 245.2 250.118 244.96C249.158 244.72 248.678 244.48 247.958 245.44C247.238 246.4 245.558 248.56 244.838 249.28C244.358 250 243.638 250 242.678 249.52C241.718 249.04 238.598 248.08 234.998 244.72C232.118 242.32 230.198 239.2 229.718 238.24C229.238 237.28 229.718 236.8 230.198 236.32C230.678 235.84 231.158 235.12 231.638 234.64C232.118 234.16 232.358 233.68 232.598 232.96C232.838 232.24 232.838 231.760 232.598 231.28C232.358 230.8 230.678 226 229.718 224.080C228.998 222.16 228.278 222.4 227.558 222.4C227.078 222.4 226.358 222.4 225.638 222.4C224.918 222.4 223.958 222.64 222.998 223.6C222.518 224.56 219.878 226.96 219.878 231.52C219.878 236.32 223.238 240.64 223.718 241.36C224.198 242.08 230.438 251.68 240.038 255.76C242.198 256.72 244.118 257.44 245.558 257.68C247.718 258.4 249.878 258.4 251.558 258.16C253.478 257.92 257.078 255.76 258.038 253.6C258.758 251.44 258.758 249.52 258.518 249.04C258.278 248.8 257.558 248.56 256.598 248.08Z" fill="#25D366"/>
                            </g>
                        </g>

                        <!-- Top right: X (Twitter) -->
                        <g transform="translate(41.5 32.3)">
                            <g class="chx-bt" style="animation-delay:-0.45s">
                                <circle cx="311" cy="73.5586" r="40" fill="white"/>
                                <g transform="translate(311 73.5586) scale(2.05) translate(-12 -12)">
                                    <path class="chx-fill-black" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </g>
                            </g>
                        </g>

                        <!-- Right: Messenger -->
                        <g transform="translate(5.3 36)">
                            <g class="chx-lr" style="animation-delay:-0.90s">
                                <circle cx="407" cy="173.559" r="40" fill="white"/>
                                <g clip-path="url(#chx-c5)">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M406.712 146.199C391.16 146.199 379.064 157.604 379.064 172.983C379.064 181.105 382.348 188.017 387.704 192.855C388.223 193.201 388.396 193.892 388.396 194.41L388.568 199.249C388.568 200.804 390.296 201.841 391.679 201.15L397.208 198.73C397.727 198.558 398.245 198.558 398.764 198.558C401.356 199.249 403.948 199.594 406.712 199.594C422.264 199.594 434.36 188.19 434.36 172.81C434.36 157.604 422.264 146.199 406.712 146.199Z" fill="url(#chx-p3)"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M390.123 180.932L398.245 167.972C399.454 165.899 402.219 165.38 404.293 166.935L410.686 171.774C411.205 172.292 412.069 172.292 412.76 171.774L421.4 165.207C422.61 164.343 424.165 165.726 423.301 166.935L415.179 179.895C413.97 181.969 411.205 182.487 409.131 180.932L402.738 176.094C402.219 175.575 401.355 175.575 400.664 176.094L392.024 182.66C390.814 183.524 389.259 182.142 390.123 180.932Z" fill="white"/>
                                </g>
                            </g>
                        </g>

                        <!-- Bottom right: bookings -->
                        <g transform="translate(-15.4 21.5)">
                            <g class="chx-rl" style="animation-delay:-1.35s">
                                <circle cx="407" cy="305.982" r="40" fill="white"/>
                                <g transform="translate(407 305.982)">
                                    <rect class="chx-stroke-blue" x="-21" y="-18" width="42" height="40" rx="7" fill="white" stroke-width="3"/>
                                    <path class="chx-fill-blue" d="M-21 -11a7 7 0 0 1 7 -7h28a7 7 0 0 1 7 7v4h-42z"/>
                                    <rect class="chx-fill-blue" x="-12" y="-24" width="4" height="10" rx="2"/>
                                    <rect class="chx-fill-blue" x="8" y="-24" width="4" height="10" rx="2"/>
                                    <text class="chx-text-heading chx-fill-blue" x="0" y="15" font-size="16" text-anchor="middle">24</text>
                                </g>
                            </g>
                        </g>

                        <!-- Bottom: Instagram -->
                        <g transform="translate(-11.1 -2.2)">
                            <g class="chx-bt" style="animation-delay:-1.80s">
                                <circle cx="311" cy="406.559" r="40" fill="white"/>
                                <path d="M295.939 388.825C294.103 388.825 292.621 390.313 292.621 392.141C292.621 393.97 294.103 395.459 295.939 395.459C297.767 395.459 299.249 393.97 299.249 392.141C299.249 390.313 297.767 388.825 295.939 388.825Z" fill="url(#chx-p4)"/>
                                <path d="M321.835 384.407C324.527 384.529 325.99 384.98 326.962 385.354C328.25 385.856 329.171 386.455 330.137 387.427C331.104 388.387 331.701 389.309 332.198 390.597C332.577 391.568 333.029 393.037 333.157 395.729C333.286 398.64 333.313 399.503 333.313 406.884C333.313 414.259 333.286 415.128 333.157 418.039C333.029 420.731 332.577 422.193 332.198 423.165C331.701 424.46 331.104 425.374 330.137 426.34C329.171 427.313 328.25 427.912 326.962 428.408C325.99 428.782 324.527 429.239 321.835 429.355C318.924 429.497 318.048 429.523 310.681 429.523C303.3 429.523 302.431 429.497 299.52 429.355C296.827 429.239 295.366 428.782 294.386 428.408C293.099 427.912 292.183 427.313 291.217 426.34C290.252 425.374 289.653 424.46 289.151 423.165C288.77 422.193 288.325 420.731 288.197 418.039C288.069 415.128 288.036 414.259 288.036 406.884C288.036 399.503 288.069 398.64 288.197 395.729C288.325 393.037 288.77 391.568 289.151 390.597C289.653 389.309 290.252 388.387 291.217 387.427C292.183 386.455 293.099 385.856 294.386 385.354C295.366 384.98 296.827 384.529 299.52 384.407C302.431 384.272 303.3 384.239 310.681 384.239C318.048 384.239 318.924 384.272 321.835 384.407ZM299.293 379.435C296.351 379.57 294.341 380.034 292.59 380.723C290.773 381.425 289.234 382.365 287.695 383.904C286.162 385.437 285.222 386.976 284.512 388.793C283.83 390.55 283.361 392.554 283.232 395.497C283.09 398.441 283.064 399.381 283.064 406.884C283.064 414.38 283.09 415.327 283.232 418.271C283.361 421.208 283.83 423.21 284.512 424.976C285.222 426.785 286.162 428.324 287.695 429.857C289.234 431.396 290.773 432.337 292.59 433.045C294.341 433.727 296.351 434.191 299.293 434.333C302.237 434.461 303.178 434.494 310.681 434.494C318.178 434.494 319.118 434.461 322.061 434.333C324.998 434.191 327.007 433.727 328.766 433.045C330.576 432.337 332.122 431.396 333.654 429.857C335.187 428.324 336.134 426.785 336.842 424.976C337.524 423.21 337.988 421.208 338.124 418.271C338.259 415.327 338.291 414.38 338.291 406.884C338.291 399.381 338.259 398.441 338.124 395.497C337.988 392.554 337.524 390.55 336.842 388.793C336.134 386.976 335.187 385.437 333.654 383.904C332.122 382.365 330.576 381.425 328.766 380.723C327.007 380.034 324.998 379.57 322.061 379.435C319.118 379.3 318.178 379.274 310.681 379.274C303.178 379.274 302.237 379.3 299.293 379.435Z" fill="url(#chx-p5)"/>
                                <path d="M319.877 406.884C319.877 411.964 315.761 416.087 310.681 416.087C305.592 416.087 301.47 411.964 301.47 406.884C301.47 401.802 305.592 397.674 310.681 397.674C315.761 397.674 319.877 401.802 319.877 406.884ZM296.492 406.884C296.492 414.716 302.849 421.059 310.681 421.059C318.506 421.059 324.856 414.716 324.856 406.884C324.856 399.052 318.506 392.702 310.681 392.702C302.849 392.702 296.492 399.052 296.492 406.884Z" fill="url(#chx-p6)"/>
                            </g>
                        </g>

                        <!-- Bottom left: LinkedIn -->
                        <g transform="translate(10.9 -2.2)">
                            <g class="chx-tb" style="animation-delay:-2.25s">
                                <circle cx="169.152" cy="406.559" r="40" fill="white"/>
                                <g transform="translate(169.152 406.559) scale(1.95) translate(-12 -12)">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.85 1.637-1.751 3.37-1.751 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" fill="#0A66C2"/>
                                </g>
                            </g>
                        </g>

                        <!-- Left bottom: Facebook -->
                        <g transform="translate(15.4 21.5)">
                            <g class="chx-lr" style="animation-delay:-2.70s">
                                <circle cx="73" cy="305.982" r="40" fill="white"/>
                                <g clip-path="url(#chx-c4)">
                                    <path d="M72.7124 278.717C57.443 278.717 45.0645 291.095 45.0645 306.365C45.0645 319.302 53.9494 330.163 65.9503 333.18L65.9503 315.404L59.9827 315.404L59.9827 306.894L65.9503 306.894L65.9503 303.01C65.9503 293.335 70.0814 288.957 79.5448 288.957C81.3457 288.957 84.453 289.31 85.7241 289.663L85.7241 297.396C85.0181 297.36 83.4641 297.29 82.4755 297.29C77.991 297.29 76.0136 298.914 76.0136 303.575L76.0136 306.894L85.4062 306.894L83.9054 315.404L76.0136 315.404L76.0136 333.827C89.727 332.195 100.36 320.518 100.36 306.365C100.36 291.095 87.9819 278.717 72.7124 278.717Z" fill="#0866FF"/>
                                    <path d="M82.4714 297.196C83.4599 297.196 85.0138 297.267 85.7198 297.303L85.7198 289.569C84.4487 289.216 81.3416 288.863 79.5405 288.863C70.0771 288.863 65.946 293.242 65.946 302.917L65.946 306.801L59.9785 306.801L59.9785 315.311L65.946 315.311L65.946 333.086L65.9482 333.087C68.1286 333.63 70.4106 333.92 72.7607 333.92C73.86 333.92 74.9438 333.856 76.0093 333.733L76.0093 315.311L83.9013 315.311L85.402 306.801L76.0093 306.801L76.0093 303.481C76.0093 298.82 77.9867 297.196 82.4714 297.196Z" fill="white"/>
                                </g>
                            </g>
                        </g>

                        <!-- Left top: owner alerts -->
                        <g transform="translate(-5 36.3)">
                            <g class="chx-rl" style="animation-delay:-3.15s">
                                <circle cx="72.7129" cy="173.271" r="40" fill="white"/>
                                <g transform="translate(72.7129 173.271)">
                                    <path class="chx-fill-blue" d="M0 -22c-9.4 0-16 7-16 16v9l-5 7v3h42v-3l-5-7v-9c0-9-6.6-16-16-16z"/>
                                    <path class="chx-fill-blue" d="M-6 16a6 6 0 0 0 12 0z"/>
                                    <circle class="chx-fill-red" cx="14" cy="-15" r="9" stroke="white" stroke-width="2.5"/>
                                    <text class="chx-text-heading" x="14" y="-11" font-size="11" text-anchor="middle" fill="white">3</text>
                                </g>
                            </g>
                        </g>

                        <!-- Top left: TikTok -->
                        <g transform="translate(-40.5 32.3)">
                            <g class="chx-tb" style="animation-delay:-3.60s">
                                <circle cx="168" cy="73.5586" r="40" fill="white"/>
                                <path d="M180.071 65.412C183.623 67.9494 187.973 69.4423 192.672 69.4423V60.405C191.783 60.4052 190.896 60.3125 190.026 60.1282V67.2419C185.327 67.2419 180.977 65.749 177.425 63.2118V81.6543C177.425 90.8802 169.942 98.3587 160.712 98.3587C157.268 98.3587 154.067 97.3181 151.408 95.5333C154.443 98.6349 158.675 100.559 163.358 100.559C172.588 100.559 180.072 93.0804 180.072 83.8541V65.412H180.071ZM183.336 56.2945C181.521 54.3127 180.329 51.7516 180.071 48.9202V47.7578H177.564C178.195 51.3565 180.348 54.4311 183.336 56.2945ZM157.246 88.4537C156.232 87.1248 155.684 85.4992 155.687 83.8277C155.687 79.6082 159.109 76.1869 163.332 76.1869C164.119 76.1867 164.901 76.3071 165.651 76.545V67.3057C164.775 67.1856 163.89 67.1347 163.006 67.1534V74.3448C162.255 74.1069 161.473 73.9861 160.686 73.9869C156.463 73.9869 153.041 77.4078 153.041 81.6279C153.041 84.6118 154.751 87.1952 157.246 88.4537Z" fill="#FF004F"/>
                                <path d="M177.425 63.2117C180.977 65.7488 185.327 67.2417 190.026 67.2417V60.1281C187.403 59.5697 185.081 58.1999 183.335 56.2945C180.347 54.4309 178.195 51.3563 177.563 47.7578H170.976V83.8538C170.961 88.0618 167.545 91.4691 163.331 91.4691C160.848 91.4691 158.642 90.2862 157.245 88.4537C154.751 87.1952 153.04 84.6116 153.04 81.6281C153.04 77.4083 156.462 73.9871 160.685 73.9871C161.494 73.9871 162.274 74.113 163.005 74.3449V67.1535C153.937 67.3408 146.645 74.7463 146.645 83.854C146.645 88.4005 148.461 92.522 151.408 95.5335C154.067 97.3181 157.268 98.3589 160.712 98.3589C169.942 98.3589 177.425 90.88 177.425 81.6543V63.2117H177.425Z" fill="black"/>
                                <path d="M190.027 60.1308V58.2073C187.662 58.2109 185.344 57.5489 183.337 56.2971C185.113 58.2407 187.452 59.5809 190.027 60.1308ZM177.565 47.7606C177.505 47.4166 177.458 47.0704 177.426 46.7229V45.5605H168.331V81.6569C168.317 85.8644 164.9 89.2717 160.686 89.2717C159.449 89.2717 158.281 88.9782 157.247 88.4566C158.644 90.2889 160.85 91.4717 163.333 91.4717C167.546 91.4717 170.963 88.0647 170.978 83.8567V47.7606H177.565ZM163.007 67.1563V65.1086C162.247 65.0048 161.481 64.9527 160.714 64.9531C151.483 64.9529 144 72.4318 144 81.6569C144 87.4405 146.941 92.5376 151.41 95.5359C148.462 92.5246 146.646 88.4028 146.646 83.8565C146.646 74.749 153.939 67.3435 163.007 67.1563Z" fill="#00F2EA"/>
                            </g>
                        </g>

                        <defs>
                            <radialGradient id="chx-p3" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(389.709 201.201) scale(60.2503)">
                                <stop stop-color="#0099FF"/><stop offset="0.6098" stop-color="#A033FF"/><stop offset="0.9348" stop-color="#FF5280"/><stop offset="1" stop-color="#FF7061"/>
                            </radialGradient>
                            <linearGradient id="chx-p4" x1="337.72" y1="433.996" x2="287.591" y2="383.867" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFD521"/><stop offset="0.05" stop-color="#FFD521"/><stop offset="0.5" stop-color="#F50000"/><stop offset="0.95" stop-color="#B900B4"/><stop offset="1" stop-color="#B900B4"/>
                            </linearGradient>
                            <linearGradient id="chx-p5" x1="337.789" y1="433.996" x2="287.619" y2="383.826" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFD521"/><stop offset="0.05" stop-color="#FFD521"/><stop offset="0.5" stop-color="#F50000"/><stop offset="0.95" stop-color="#B900B4"/><stop offset="1" stop-color="#B900B4"/>
                            </linearGradient>
                            <linearGradient id="chx-p6" x1="337.79" y1="433.983" x2="287.632" y2="383.825" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFD521"/><stop offset="0.05" stop-color="#FFD521"/><stop offset="0.5" stop-color="#F50000"/><stop offset="0.95" stop-color="#B900B4"/><stop offset="1" stop-color="#B900B4"/>
                            </linearGradient>
                            <clipPath id="chx-c4"><rect width="55.296" height="55.296" transform="translate(45.0645 278.623)"/></clipPath>
                            <clipPath id="chx-c5"><rect width="55.296" height="55.296" transform="translate(379.064 146.199)"/></clipPath>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
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

    <section
        class="landing-jx"
        id="journey"
        data-landing-journey
        data-signup-url="<?= e($signupUrl) ?>"
        data-plans-url="<?= e(app_route('plans')) ?>"
        data-guide-url="<?= e(app_route('guides/connect-meta-channels')) ?>"
        data-bird-icon="<?= e($appUrl) ?>/assets/images/leadbird-favicon.png"
    >
        <div class="landing-jx-wrap">
            <div class="landing-jx-head">
                <h2>Fuel <span class="landing-jx-hl">every stage</span> of the customer journey</h2>
                <p>From the first &ldquo;How much?&rdquo; to the repeat order, Lead Bird turns your DMs into sales, payments and customers who come back.</p>
            </div>

            <div class="landing-jx-bar">
                <div class="landing-jx-axis" data-jx-axis role="group" aria-label="View by">
                    <button type="button" class="is-on" data-axis="goal" aria-pressed="true">By goal</button>
                    <button type="button" data-axis="business" aria-pressed="false">By business</button>
                                </div>
                <div class="landing-jx-tabs" data-jx-tabs></div>
                            </div>

            <div class="landing-jx-card">
                <div class="landing-jx-copy">
                    <h3 data-jx-title></h3>
                    <p data-jx-sub></p>

                    <div class="landing-jx-stats" data-jx-stats></div>

                    <a href="<?= e($signupUrl) ?>" class="landing-cta landing-jx-cta self-start inline-flex items-center justify-center gap-2 rounded-full px-7 py-2.5 text-sm md:text-base font-black uppercase tracking-wide transition-all duration-300">
                        Get Started
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"></path>
                        </svg>
                    </a>

                    <a class="landing-jx-link" data-jx-link href="#">
                        <span>
                            <small data-jx-link-kicker></small>
                            <b data-jx-link-label></b>
                        </span>
                        <i aria-hidden="true">&nearr;</i>
                    </a>
                        </div>

                <div class="landing-jx-chat" data-jx-chat data-ch="wa" aria-live="polite"></div>
            </div>
        </div>
    </section>
    <script src="<?= e($appUrl) ?>/assets/js/landing-journey.js?v=5" defer></script>

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

    <section class="landing-rx reveal" id="ratings" aria-labelledby="landing-rx-title">
        <div class="landing-rx-wrap">

            <div class="landing-rx-top">
                <div class="landing-rx-head">
                    <h2 id="landing-rx-title">Loved by Customers,<br>Backed by Experts</h2>
                </div>
                <div class="landing-rx-awards">
                    <img src="<?= e($appUrl) ?>/assets/images/ratings/g2-leader-chatbots.svg" alt="G2 Leader, Chatbots" width="100" height="118" loading="lazy">
                    <img src="<?= e($appUrl) ?>/assets/images/ratings/g2-momentum-leader.svg" alt="G2 Momentum Leader, Chatbots" width="100" height="118" loading="lazy">
                    <img src="<?= e($appUrl) ?>/assets/images/ratings/g2-most-implementable.svg" alt="G2 Most Implementable, WhatsApp Marketing" width="100" height="118" loading="lazy">
                    <img src="<?= e($appUrl) ?>/assets/images/ratings/g2-leader-mid-market.svg" alt="G2 Leader Mid-Market, Chatbots" width="100" height="118" loading="lazy">
                </div>
            </div>

            <div class="landing-rx-grid">
                <div class="landing-rx-card">
                    <div class="landing-rx-score">4.7<small>/5</small></div>
                    <div class="landing-rx-stars" aria-label="4.7 out of 5 stars"><span class="bg">★★★★★</span><span class="fg" style="width:94%">★★★★★</span></div>
                    <div class="landing-rx-logo"><img src="<?= e($appUrl) ?>/assets/images/ratings/rating-producthunt.svg" alt="Product Hunt" loading="lazy"></div>
                    <div class="landing-rx-rev">Out of<br>50+ reviews</div>
                </div>

                <div class="landing-rx-card">
                    <div class="landing-rx-score">4.6<small>/5</small></div>
                    <div class="landing-rx-stars" aria-label="4.6 out of 5 stars"><span class="bg">★★★★★</span><span class="fg" style="width:92%">★★★★★</span></div>
                    <div class="landing-rx-logo"><span class="wm">Capterra</span></div>
                    <div class="landing-rx-rev">Out of<br>200+ reviews</div>
                    </div>

                <div class="landing-rx-card">
                    <div class="landing-rx-score">4.6<small>/5</small></div>
                    <div class="landing-rx-stars" aria-label="4.6 out of 5 stars"><span class="bg">★★★★★</span><span class="fg" style="width:92%">★★★★★</span></div>
                    <div class="landing-rx-logo"><img src="<?= e($appUrl) ?>/assets/images/ratings/rating-g2.svg" alt="G2" loading="lazy"></div>
                    <div class="landing-rx-rev">Out of<br>450+ reviews</div>
                </div>

                <div class="landing-rx-card">
                    <div class="landing-rx-score">4.1<small>/5</small></div>
                    <div class="landing-rx-stars" aria-label="4.1 out of 5 stars"><span class="bg">★★★★★</span><span class="fg" style="width:82%">★★★★★</span></div>
                    <div class="landing-rx-logo"><img src="<?= e($appUrl) ?>/assets/images/ratings/rating-shopify.svg" alt="Shopify" loading="lazy"></div>
                    <div class="landing-rx-rev">Out of<br>125+ reviews</div>
                        </div>

                <div class="landing-rx-card">
                    <div class="landing-rx-score">4.7<small>/5</small></div>
                    <div class="landing-rx-stars" aria-label="4.7 out of 5 stars"><span class="bg">★★★★★</span><span class="fg" style="width:94%">★★★★★</span></div>
                    <div class="landing-rx-logo"><img src="<?= e($appUrl) ?>/assets/images/ratings/rating-hubspot.svg" alt="HubSpot" loading="lazy"></div>
                    <div class="landing-rx-rev">Out of<br>70+ reviews</div>
                </div>
            </div>

        </div>
    </section>

    <section class="landing-cb" aria-labelledby="landing-cb-title">
        <div class="landing-cb-wrap">
            <div class="landing-cb-final reveal">
                <h2 id="landing-cb-title">Let Hannah answer every DM while you <span class="landing-cb-hl">focus on selling</span></h2>
                <div class="landing-cb-actions">
                    <a class="landing-cb-btn landing-cb-btn-primary" href="<?= e($signupUrl) ?>">Get Started</a>
                    <a class="landing-cb-btn landing-cb-btn-outline" href="<?= e(app_route('plans')) ?>">See pricing</a>
            </div>
                <p class="landing-cb-micro">Set up in minutes · No coding needed · <a href="<?= e(app_route('plans')) ?>">Transparent pricing</a></p>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/footer.php'; ?>
</div>
