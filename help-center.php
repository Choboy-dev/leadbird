<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/help-center/topics.php';

$appUrl = rtrim((string) app_config()['app_url'], '/');
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$helpUrl = app_route('help-center');
$contactUrl = app_route('contact');

$topics = help_center_topics();
$query = trim((string) ($_GET['q'] ?? ''));
$activeTopic = help_center_find_topic(trim((string) ($_GET['c'] ?? '')));
if ($activeTopic !== null) {
    $query = '';
}

$matches = static function (array $topic, string $q): bool {
    return $q === '' || str_contains(mb_strtolower($topic['name'] . ' ' . $topic['keywords']), mb_strtolower($q));
};
$results = array_values(array_filter($topics, static fn (array $t): bool => $matches($t, $query)));

$headerLinks = [
    ['Pricing', app_route('plans')],
    ['Sign in', app_route('login')],
    ['How to contact support', $contactUrl],
];

$footerLinks = [
    ['Terms of Use', app_route('terms')],
    ['Privacy Policy', app_route('privacy')],
    ['Delete My Data', app_route('data-deletion')],
];

$socials = [
    ['Facebook', '<path d="M1 8.03518C1 11.5176 3.52 14.402 6.845 15L6.95637 14.9084C6.93096 14.9041 6.90555 14.8996 6.88015 14.895V9.96002H5.13015V8.00002H6.88015V6.46002C6.88015 4.71002 8.00015 3.73002 9.61015 3.73002C10.1001 3.73002 10.6601 3.80002 11.1501 3.87002V5.65502H10.2401C9.40015 5.65502 9.19015 6.07502 9.19015 6.63503V8.00002H11.0451L10.7301 9.96002H9.19015V14.895C9.14523 14.9032 9.10031 14.9109 9.0554 14.9181L9.155 15C12.48 14.402 15 11.5176 15 8.03518C15 4.16583 11.85 1 8 1C4.15 1 1 4.16583 1 8.03518Z"/>'],
    ['X', '<path d="M9.332 6.925 14.544 1h-1.235L8.783 6.145 5.17 1H1l5.466 7.78L1 14.993h1.235l4.78-5.433 3.816 5.433H15L9.332 6.925ZM7.64 8.848l-.554-.775L2.68 1.91h1.897l3.556 4.975.554.775 4.622 6.466h-1.897L7.64 8.848Z"/>'],
    ['LinkedIn', '<path d="M2.24927 4.16968C3.34257 4.16968 4.01312 3.44081 4.01312 2.5516C3.99854 1.61865 3.34257 0.918945 2.26385 0.918945C1.19971 0.918945 0.5 1.61865 0.5 2.5516C0.5 3.45539 1.17055 4.16968 2.22012 4.16968H2.24927ZM3.61954 14.6067V5.47409H0.5V14.6067H3.61954ZM9.0855 7.01904V5.69275H5.96596C5.99185 6.45636 5.97177 13.2645 5.96694 14.7817H9.0855V9.83269C9.0855 9.55572 9.10007 9.26418 9.18754 9.07467C9.4062 8.50616 9.93098 7.93764 10.791 7.93764C11.9281 7.93764 12.38 8.7977 12.38 10.0659V14.7817H15.4995V9.70149C15.4995 6.8152 13.9543 5.47409 11.8989 5.47409C10.2462 5.47409 9.51254 6.37924 9.0855 7.01904Z"/>'],
    ['Instagram', '<path fill-rule="evenodd" d="M8.004 2.26c1.872 0 2.093.009 2.83.042.683.03 1.053.145 1.299.24.325.126.56.28.804.523.246.246.397.478.523.804.095.246.21.618.24 1.299.033.738.042.96.042 2.828 0 1.87-.009 2.092-.042 2.828-.03.684-.145 1.053-.24 1.299-.126.325-.28.56-.523.804a2.154 2.154 0 0 1-.804.522c-.246.096-.619.21-1.3.241-.739.033-.96.041-2.829.041-1.871 0-2.093-.008-2.829-.041-.684-.03-1.053-.145-1.3-.24a2.166 2.166 0 0 1-.804-.523 2.153 2.153 0 0 1-.522-.804c-.096-.246-.211-.618-.241-1.3-.033-.738-.041-.96-.041-2.827 0-1.87.008-2.092.04-2.828.03-.684.146-1.053.242-1.3.125-.325.279-.56.522-.803.246-.246.479-.397.805-.523.246-.095.618-.21 1.3-.24.735-.033.957-.041 2.828-.041Zm0-1.26c-1.901 0-2.14.008-2.886.041-.745.033-1.256.153-1.7.325a3.435 3.435 0 0 0-1.242.81 3.43 3.43 0 0 0-.81 1.239c-.172.446-.292.954-.325 1.698-.033.75-.041.988-.041 2.888 0 1.901.008 2.14.041 2.886.033.744.153 1.255.326 1.698.18.462.418.853.81 1.242.388.388.78.629 1.239.806.446.173.955.293 1.699.326C5.862 14.992 6.1 15 8 15c1.902 0 2.14-.008 2.887-.041.744-.033 1.256-.153 1.699-.325.46-.178.85-.419 1.24-.807a3.42 3.42 0 0 0 .806-1.24c.173-.445.293-.954.326-1.698.033-.746.041-.984.041-2.885 0-1.9-.008-2.139-.041-2.885-.033-.744-.153-1.256-.326-1.699a3.28 3.28 0 0 0-.801-1.244 3.423 3.423 0 0 0-1.24-.807c-.446-.172-.955-.292-1.699-.325C10.143 1.008 9.906 1 8.004 1Zm0 3.405a3.598 3.598 0 0 0-3.598 3.596 3.598 3.598 0 0 0 7.196 0 3.598 3.598 0 0 0-3.598-3.596Zm0 5.93a2.334 2.334 0 1 1 0-4.668 2.334 2.334 0 0 1 0 4.667Zm3.74-5.233a.84.84 0 1 0 0-1.68.84.84 0 0 0 0 1.68Z" clip-rule="evenodd"/>'],
    ['TikTok', '<path d="M8.36 1.01c.76-.01 1.52-.01 2.28-.01.05.89.37 1.8 1.02 2.43.65.65 1.58.95 2.47 1.05v2.35a6.2 6.2 0 0 1-2.45-.57 7.3 7.3 0 0 1-.95-.54c0 1.7.01 3.41-.01 5.1-.05.82-.31 1.63-.79 2.3a4.2 4.2 0 0 1-3.45 1.87 4.1 4.1 0 0 1-2.38-.6 4.3 4.3 0 0 1-2.13-3.33c-.01-.29-.02-.58 0-.87.11-1.11.65-2.17 1.5-2.9.97-.84 2.32-1.24 3.59-1 .01.86-.02 1.73-.02 2.59-.58-.19-1.25-.13-1.76.22-.37.24-.65.61-.79 1.02-.12.3-.09.62-.08.94.14.96 1.06 1.76 2.04 1.67.65 0 1.28-.38 1.62-.94.11-.19.23-.39.24-.62.06-1.04.03-2.08.04-3.13.01-2.35 0-4.7.01-7.04z"/>'],
];

$pageTitle = ($query !== '' ? 'Search results for "' . $query . '" | ' : ($activeTopic !== null ? $activeTopic['name'] . ' | ' : '')) . $brandName . ' Help Center';
$metaDescription = 'Guides and answers for getting the most out of ' . $brandName . ' and Hannah, your AI salesgirl.';
$metaUrl = $helpUrl;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <?php require __DIR__ . '/includes/brand-meta.php'; ?>
    <?php require __DIR__ . '/includes/brand-fonts.php'; ?>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/brand.css">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/help-center.css?v=4">
</head>
<body class="help">
<a href="#main-content" class="hc-skip">Skip to main content</a>

<header class="hc-header">
    <div class="hc-header-bg">
        <div class="hc-meta">
            <div class="hc-meta-inner">
                <a class="hc-logo" href="<?= e($helpUrl) ?>" data-hc-home aria-label="<?= e($brandName) ?> Help Center">
                    <img src="<?= e($appUrl) ?>/assets/images/leadbird-logo.png" alt="" width="250" height="150">
                    <span>Help Center</span>
                </a>

                <nav class="hc-nav" aria-label="Help Center">
                    <?php foreach ($headerLinks as [$label, $url]): ?>
                        <a href="<?= e($url) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                    <span class="hc-lang" aria-label="Language: English">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 15C11.866 15 15 11.866 15 8C15 4.13401 11.866 1 8 1C4.13401 1 1 4.13401 1 8C1 11.866 4.13401 15 8 15Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 15C9.39949 15 10.534 11.866 10.534 8C10.534 4.13401 9.39949 1 8 1C6.60051 1 5.466 4.13401 5.466 8C5.466 11.866 6.60051 15 8 15Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M1.448 5.75989H14.524" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M1.448 10.2402H14.524" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        English
                    </span>
                </nav>

                <button class="hc-burger" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="hcMenu">
                    <svg width="24" height="24" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M1.86861 2C1.38889 2 1 2.3806 1 2.85008C1 3.31957 1.38889 3.70017 1.86861 3.70017H14.1314C14.6111 3.70017 15 3.31957 15 2.85008C15 2.3806 14.6111 2 14.1314 2H1.86861Z"/><path d="M1 8C1 7.53051 1.38889 7.14992 1.86861 7.14992H14.1314C14.6111 7.14992 15 7.53051 15 8C15 8.46949 14.6111 8.85008 14.1314 8.85008H1.86861C1.38889 8.85008 1 8.46949 1 8Z"/><path d="M1 13.1499C1 12.6804 1.38889 12.2998 1.86861 12.2998H14.1314C14.6111 12.2998 15 12.6804 15 13.1499C15 13.6194 14.6111 14 14.1314 14H1.86861C1.38889 14 1 13.6194 1 13.1499Z"/></svg>
                </button>
            </div>
        </div>

        <div class="hc-hero">
            <h1 id="hcTitle"><?= $query !== '' ? 'Search results' : 'Let’s get you the answers you need.' ?></h1>
            <form class="hc-search" id="hcSearch" role="search" action="<?= e($helpUrl) ?>" method="get" autocomplete="off">
                <label for="search-input" class="hc-sr">Search for articles...</label>
                <input id="search-input" type="text" name="q" value="<?= e($query) ?>" placeholder="Search for articles...">
                <svg width="22" height="21" viewBox="0 0 22 21" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.27485 8.7001C3.27485 5.42781 5.92757 2.7751 9.19985 2.7751C12.4721 2.7751 15.1249 5.42781 15.1249 8.7001C15.1249 11.9724 12.4721 14.6251 9.19985 14.6251C5.92757 14.6251 3.27485 11.9724 3.27485 8.7001ZM9.19985 0.225098C4.51924 0.225098 0.724854 4.01948 0.724854 8.7001C0.724854 13.3807 4.51924 17.1751 9.19985 17.1751C11.0802 17.1751 12.8176 16.5627 14.2234 15.5265L19.0981 20.4013C19.5961 20.8992 20.4033 20.8992 20.9013 20.4013C21.3992 19.9033 21.3992 19.0961 20.9013 18.5981L16.0264 13.7233C17.0625 12.3176 17.6749 10.5804 17.6749 8.7001C17.6749 4.01948 13.8805 0.225098 9.19985 0.225098Z"/></svg>
            </form>
        </div>
    </div>
</header>

<div class="hc-menu" id="hcMenu" hidden>
    <div class="hc-menu-panel">
        <button class="hc-menu-close" type="button" aria-label="Close menu">
            <svg width="24" height="24" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M3.5097 3.5097C3.84165 3.17776 4.37984 3.17776 4.71178 3.5097L7.99983 6.79775L11.2879 3.5097C11.6198 3.17776 12.158 3.17776 12.49 3.5097C12.8219 3.84165 12.8219 4.37984 12.49 4.71178L9.20191 7.99983L12.49 11.2879C12.8219 11.6198 12.8219 12.158 12.49 12.49C12.158 12.8219 11.6198 12.8219 11.2879 12.49L7.99983 9.20191L4.71178 12.49C4.37984 12.8219 3.84165 12.8219 3.5097 12.49C3.17776 12.158 3.17776 11.6198 3.5097 11.2879L6.79775 7.99983L3.5097 4.71178C3.17776 4.37984 3.17776 3.84165 3.5097 3.5097Z"/></svg>
        </button>
        <nav aria-label="Help Center menu">
            <?php foreach ($headerLinks as [$label, $url]): ?>
                <a href="<?= e($url) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <span class="hc-menu-lang">English</span>
        </nav>
    </div>
</div>

<main class="hc-main" id="main-content">
<?php if ($activeTopic !== null): ?>
    <section class="hc-topic">
        <a class="hc-topic-back" href="<?= e($helpUrl) ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
            All topics
        </a>
        <div class="hc-topic-head">
            <span class="hc-card-icon"><?= help_center_icon($activeTopic['icon']) ?></span>
            <h2><?= e($activeTopic['name']) ?></h2>
        </div>
        <div class="hc-topic-empty">
            <p>We are writing the guides for this topic. They will be here soon.</p>
            <p>In the meantime, Hannah can answer most questions right away, or our team can help.</p>
            <div class="hc-topic-actions">
                <a class="hc-btn hc-btn-primary" href="<?= e($contactUrl) ?>" data-hc-ask-hannah>Ask Hannah</a>
                <a class="hc-btn hc-btn-outline" href="<?= e($contactUrl) ?>">Contact support</a>
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="hc-banner" id="hcBanner"<?= $query !== '' ? ' hidden' : '' ?>>
        <div class="hc-banner-event" aria-hidden="true">
            <div class="hc-banner-brand">
                <img src="<?= e($appUrl) ?>/assets/images/leadbird-favicon.png" alt="" width="30" height="30">
                <b><?= e($brandName) ?></b>
                <span>24/7</span>
            </div>
            <div class="hc-banner-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>Knows every plan, price and token pack</div>
            <div class="hc-banner-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>Helps you connect WhatsApp, Instagram and Facebook</div>
            <div class="hc-banner-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1v-6h3zM3 19a2 2 0 0 0 2 2h1v-6H3z"/></svg>Sends you to our team when she’s not sure</div>
        </div>
        <div class="hc-banner-copy">
            <h2>Ask Hannah anything</h2>
            <p>Get instant answers about setup, channels, tokens and billing</p>
            <a href="<?= e($contactUrl) ?>" data-hc-ask-hannah>Chat with Hannah</a>
        </div>
    </section>

    <p class="hc-empty" id="hcEmpty"<?= $query !== '' && $results === [] ? '' : ' hidden' ?>>
        We couldn’t find anything for “<span data-hc-q><?= e($query) ?></span>”. Try another word, or <a href="<?= e($contactUrl) ?>">contact support</a>.
    </p>
    <p class="hc-count" id="hcCount"<?= $query !== '' && $results !== [] ? '' : ' hidden' ?>>
        <span data-hc-n><?= count($results) ?></span> <span data-hc-unit><?= count($results) === 1 ? 'topic' : 'topics' ?></span> matching “<span data-hc-q><?= e($query) ?></span>”
    </p>

    <div class="hc-grid" id="hcGrid" role="list"<?= $results === [] ? ' hidden' : '' ?>>
        <?php foreach ($topics as $topic): ?>
            <?php $n = (int) $topic['articles']; ?>
            <a
                class="hc-card"
                role="listitem"
                href="<?= e(app_route('help-center', ['c' => $topic['slug']])) ?>"
                data-hc-search="<?= e(mb_strtolower($topic['name'] . ' ' . $topic['keywords'])) ?>"
                <?= $matches($topic, $query) ? '' : 'hidden' ?>
            >
                <span class="hc-card-icon"><?= help_center_icon($topic['icon']) ?></span>
                <span class="hc-card-text">
                    <span class="hc-card-name"><?= e($topic['name']) ?></span>
                    <span class="hc-card-count"><?= $n > 0 ? $n . ' ' . ($n === 1 ? 'article' : 'articles') : 'Articles coming soon' ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</main>

<footer class="hc-footer">
    <a class="hc-footer-name" href="<?= e($helpUrl) ?>" data-hc-home><?= e($brandName) ?> Help Center</a>
    <ul class="hc-footer-links">
        <?php foreach ($footerLinks as [$label, $url]): ?>
            <li><a href="<?= e($url) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <ul class="hc-footer-social">
        <?php foreach ($socials as [$label, $path]): ?>
            <li><a href="#" aria-label="<?= e($label) ?>"><svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor" aria-hidden="true"><?= $path ?></svg></a></li>
        <?php endforeach; ?>
    </ul>
</footer>

<?php require __DIR__ . '/includes/site-assistant/embed.php'; ?>
<script src="<?= e($appUrl) ?>/assets/js/help-center.js?v=1" defer></script>
</body>
</html>
