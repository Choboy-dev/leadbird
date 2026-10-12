<?php

declare(strict_types=1);

/**
 * Shared layout for industry pages (/industries/*).
 *
 * @var array{
 *     slug: string, name: string, title: string, description: string, h1: string, lede: string,
 *     clients: string, band: string, cta: string,
 *     heroImage?: array{0: string, 1: int, 2: int},
 *     heroVisual?: 'shop'|'booking',
 *     hero: array<string, string>,
 *     quotesLede?: string,
 *     features: list<array{0: string, 1: string, 2: string}>,
 *     rows: list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string, 5: list<array>}>
 * } $ind
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/reviews.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = $brandName . ' for ' . $ind['name'] . ' | ' . $ind['title'];
$metaDescription = $ind['description'];
$metaUrl = app_route('industries/' . $ind['slug']);
$extraStylesheets = ['solution.css?v=8', 'industry.css?v=12'];
$activeNav = null;

$signupUrl = app_route('signup');
$contactUrl = app_route('contact');
$photoBase = rtrim($appUrl, '/') . '/assets/images/solutions/';

$photo = static fn (string $file): string => '<img class="ind-ph-photo" src="' . e($photoBase . $file) . '" alt="" loading="lazy">';

$phone = static function (array $items): string {
    $html = '<div class="ph ind-ph" aria-hidden="true"><div class="ph-top"><i class="ph-back"></i><i class="ph-ava"></i><i class="ph-name"></i><i class="ph-tick"></i><i class="ph-ic"></i></div><div class="ph-body">';
    foreach ($items as $item) {
        $html .= '<div class="ph-msg ' . $item[0] . '"><div class="ph-b">' . $item[1] . '</div>';
        foreach ($item[2] ?? [] as $button) {
            $html .= '<span class="ph-btn">' . $button . '</span>';
        }
        $html .= '</div>';
    }

    return $html . '</div><div class="ph-bar"><i>+</i><span></span><i class="ph-cam"></i></div></div>';
};

$decos = [
    'arrows' => '<path d="M92 8 70 32M70 20v12h12M96 26 74 50M74 38v12h12M94 46 72 70M72 58v12h12"/>',
    'plus' => '<path d="M70 22v14M63 29h14M80 40v14M73 47h14M68 58v14M61 65h14M82 72v14M75 79h14"/>',
    'cross' => '<path d="M66 20l10 10M76 20 66 30M82 32l10 10M92 32 82 42M68 52l10 10M78 52 68 62M84 72l10 10M94 72 84 82"/>',
];

$quotes = lb_reviews();

$hero = $ind['hero'];

require dirname(__DIR__) . '/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/home/marketing-header.php'; ?>

    <main class="sol ind flex-1">

        <section class="ind-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <span class="ind-eyebrow"><?= e($brandName) ?> for <?= e($ind['name']) ?></span>
                    <h1><?= $ind['h1'] ?></h1>
                    <p class="sol-p"><?= e($ind['lede']) ?></p>
                    <div class="sol-btns">
                        <a class="sol-btn ind-hero-btn" href="<?= e($signupUrl) ?>">Get started</a>
                    </div>
                    <div class="ind-trust">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="var(--brand-lime)"/><path d="M7.5 12.5l3 3 6-6.5" stroke="var(--brand-black)" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>WhatsApp, Instagram &amp; Messenger</span>
                        <i></i>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="var(--brand-blue)"/><path d="M12 7v5l3 2" stroke="var(--brand-white)" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>AI replies day and night</span>
                    </div>
                </div>
                <?php if (!empty($ind['heroImage'])): ?>
                    <div aria-hidden="true">
                        <img class="ind-hero-img" src="<?= e(rtrim($appUrl, '/') . '/assets/images/' . $ind['heroImage'][0]) ?>" alt="" width="<?= (int) $ind['heroImage'][1] ?>" height="<?= (int) $ind['heroImage'][2] ?>">
                    </div>
                <?php else: ?>
                    <?php require __DIR__ . '/hero-' . ($ind['heroVisual'] ?? 'shop') . '.php'; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php
        $toolSections = ['clients'];
        $clientsHeading = $ind['clients'];
        require dirname(__DIR__) . '/tools/sections-bottom.php';
        ?>

        <section class="ind-feat-sec">
            <div class="lk-wrap">
                <div class="ind-feat-box">
                    <ul class="ind-feats">
                        <?php foreach ($ind['features'] as [$icon, $title, $copy]): ?>
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg>
                                <div><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section class="ind-band">
            <div class="lk-wrap"><h2><?= e($ind['band']) ?></h2></div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <?php foreach ($ind['rows'] as $i => [$title, $sub, $points, $tone, $deco, $chat]): ?>
                    <div class="sol-split ind-row<?= $i % 2 === 1 ? ' flip' : '' ?>">
                        <div class="ind-row-copy">
                            <h2><?= e($title) ?></h2>
                            <h3><?= e($sub) ?></h3>
                            <ul>
                                <?php foreach ($points as $point): ?>
                                    <li><?= e($point) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="ind-frame <?= e($tone) ?>" aria-hidden="true">
                            <div class="ind-frame-box"><svg class="ind-deco" viewBox="0 0 100 100"><?= $decos[$deco] ?></svg></div>
                            <div class="ind-frame-pop"><?= $phone(array_map(static function (array $m) use ($photo): array {
                                if (isset($m[3])) {
                                    $m[1] = $photo($m[3]) . $m[1];
                                }
                                return $m;
                            }, $chat)) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="sol-pad ind-testi">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">What our customers say</h2>
                <p class="sol-p center"><?= e($ind['quotesLede'] ?? 'How sellers use ' . $brandName . ' to answer customers on WhatsApp and Instagram.') ?></p>
                <div class="ind-quotes">
                    <?php foreach ($quotes as [$headline, $quote, $name, $role, $channel]): ?>
                        <figure class="ind-quote">
                            <h3>“<?= e($headline) ?>”</h3>
                            <blockquote><?= e($quote) ?></blockquote>
                            <figcaption><?= lb_review_channel_badge($channel) ?><span><b><?= e($name) ?></b><small><?= e($role) ?></small></span></figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <div class="ind-cta">
                    <p><strong>Discover more.</strong> <?= e($ind['cta']) ?></p>
                    <div class="sol-btns">
                        <a class="sol-btn white" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e($contactUrl) ?>">Talk to us</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php require dirname(__DIR__) . '/home/footer.php'; ?>
</div>
<?php require dirname(__DIR__) . '/home/layout-end.php'; ?>
