<?php

declare(strict_types=1);

/** @var string $helpSearchQuery */
/** @var array<string, mixed> $helpData */

$visibleRecommended = [];
foreach ($helpData['recommended'] as $ref) {
    $article = help_find_article((string) $ref['topic'], (string) $ref['slug']);
    if ($article !== null) {
        $visibleRecommended[] = $article;
    }
}

$searchResults = [];
if ($helpSearchQuery !== '') {
    $needle = mb_strtolower($helpSearchQuery);
    foreach (help_all_articles() as $article) {
        $haystack = mb_strtolower($article['title'] . ' ' . strip_tags($article['body']) . ' ' . $article['topic_label']);
        if (str_contains($haystack, $needle)) {
            $searchResults[] = $article;
        }
    }
}
?>
<?php if ($helpSearchQuery !== ''): ?>
    <nav class="hc-breadcrumbs" aria-label="Breadcrumb">
        <a href="<?= e(help_home_url()) ?>">Home</a>
        <span class="hc-breadcrumb-sep" aria-hidden="true">/</span>
        <span>Search results</span>
    </nav>
    <header class="hc-section-head">
        <h2 class="hc-section-title font-heading">Search results</h2>
        <p class="hc-section-lead"><?= count($searchResults) ?> article<?= count($searchResults) === 1 ? '' : 's' ?> found</p>
    </header>
    <?php if ($searchResults === []): ?>
        <p class="hc-empty">No articles match “<?= e($helpSearchQuery) ?>”. Try different keywords or <a href="<?= e(help_contact_url()) ?>">contact support</a>.</p>
    <?php else: ?>
        <div class="hc-row-list">
            <?php foreach ($searchResults as $article): ?>
                <a href="<?= e(help_article_url($article['topic'], $article['slug'])) ?>" class="hc-row-link">
                    <span class="hc-row-link-text"><?= e($article['title']) ?></span>
                    <span class="hc-row-link-meta"><?= e($article['topic_label']) ?></span>
                    <svg class="hc-row-link-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <header class="hc-section-head">
        <h2 class="hc-section-title font-heading">Common questions</h2>
        <p class="hc-section-lead">Quick answers to what sellers ask most often.</p>
    </header>
    <div class="hc-row-list" data-help-recommended>
        <?php foreach ($visibleRecommended as $article): ?>
            <a href="<?= e(help_article_url($article['topic'], $article['slug'])) ?>" class="hc-row-link">
                <span class="hc-row-link-text"><?= e($article['title']) ?></span>
                <svg class="hc-row-link-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if (count($visibleRecommended) < count($helpData['recommended'])): ?>
        <button type="button" class="hc-show-more" data-help-show-more hidden>Show more questions</button>
    <?php endif; ?>
<?php endif; ?>
