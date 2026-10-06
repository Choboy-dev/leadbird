<?php

declare(strict_types=1);

/** @var array{label: string, slug: string, sections: list<array<string, mixed>>} $helpTopic */
?>
<nav class="hc-breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= e(help_home_url()) ?>">Home</a>
    <span class="hc-breadcrumb-sep" aria-hidden="true">/</span>
    <span><?= e($helpTopic['label']) ?></span>
</nav>

<header class="hc-section-head">
    <h2 class="hc-section-title font-heading"><?= e($helpTopic['label']) ?></h2>
    <p class="hc-section-lead">Choose a topic below to read the full answer.</p>
</header>

<div class="hc-accordion" data-help-accordion>
    <?php foreach ($helpTopic['sections'] as $index => $section): ?>
        <?php $sectionId = 'hc-section-' . preg_replace('/[^a-z0-9-]/', '', (string) $helpTopic['slug']) . '-' . (int) $index; ?>
        <section class="hc-accordion-item<?= $index === 0 ? ' is-open' : '' ?>" data-help-accordion-item>
            <button
                type="button"
                class="hc-accordion-trigger"
                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                aria-controls="<?= $sectionId ?>"
                id="<?= $sectionId ?>-trigger"
            >
                <span><?= e((string) $section['title']) ?></span>
                <svg class="hc-accordion-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div
                class="hc-accordion-panel"
                id="<?= $sectionId ?>"
                role="region"
                aria-labelledby="<?= $sectionId ?>-trigger"
                <?= $index === 0 ? '' : 'hidden' ?>
            >
                <div class="hc-row-list hc-row-list--nested">
                    <?php foreach ($section['articles'] as $article): ?>
                        <a href="<?= e(help_article_url($helpTopic['slug'], (string) $article['slug'])) ?>" class="hc-row-link hc-row-link--compact">
                            <span class="hc-row-link-text"><?= e((string) $article['title']) ?></span>
                            <svg class="hc-row-link-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>
