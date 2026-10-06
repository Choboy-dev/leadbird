<?php

declare(strict_types=1);

/** @var array{slug: string, title: string, body: string, topic: string, topic_label: string, section: string} $helpArticle */
?>
<nav class="hc-breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= e(help_home_url()) ?>">Home</a>
    <span class="hc-breadcrumb-sep" aria-hidden="true">/</span>
    <a href="<?= e(help_topic_url($helpArticle['topic'])) ?>"><?= e($helpArticle['topic_label']) ?></a>
    <span class="hc-breadcrumb-sep" aria-hidden="true">/</span>
    <span class="hc-breadcrumb-current"><?= e($helpArticle['title']) ?></span>
</nav>

<article class="hc-article">
    <header class="hc-section-head hc-section-head--article">
        <h2 class="hc-section-title font-heading"><?= e($helpArticle['title']) ?></h2>
    </header>
    <div class="hc-article-body">
        <?= $helpArticle['body'] ?>
    </div>
</article>

<footer class="hc-article-footer">
    <p class="hc-article-footer-label">Was this helpful?</p>
    <div class="hc-article-footer-actions">
        <a href="<?= e(help_contact_url()) ?>" class="hc-article-footer-link">Contact support</a>
        <a href="<?= e(help_topic_url($helpArticle['topic'])) ?>" class="hc-article-footer-link">Back to <?= e($helpArticle['topic_label']) ?></a>
    </div>
</footer>
