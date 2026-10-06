<?php

declare(strict_types=1);

/** @var string|null $helpActiveTopic */
/** @var string $helpViewMode home|topic|article|contact */
/** @var array<string, mixed>|null $settings */

$helpData = help_center_data();
$helpActiveTopic = $helpActiveTopic ?? null;
$helpViewMode = $helpViewMode ?? 'home';
$askAiLabel = dashboard_ai_assistant_label($settings ?? null);
$helpSearchQuery = trim((string) ($_GET['q'] ?? ''));
$showShortcuts = $helpViewMode === 'home' && $helpSearchQuery === '';
?>
<div class="hc-page" data-help-page>
    <div class="hc-wrap">
        <div class="hc-toolbar">
            <form class="hc-search" action="<?= e(help_home_url()) ?>" method="get" role="search">
                <label class="sr-only" for="hc-search-input">Search help</label>
                <svg class="hc-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.75"/>
                    <path d="M16 16l4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                <input
                    type="search"
                    id="hc-search-input"
                    name="q"
                    class="hc-search-input"
                    placeholder="Search questions, keywords, or topics"
                    value="<?= e($helpSearchQuery) ?>"
                    data-help-search
                    autocomplete="off"
                >
            </form>
        </div>

        <nav class="hc-topics" aria-label="Help topics">
            <a
                href="<?= e(help_home_url()) ?>"
                class="hc-topic-pill<?= $helpViewMode === 'home' ? ' is-active' : '' ?>"
                <?= $helpViewMode === 'home' ? 'aria-current="page"' : '' ?>
            >Home</a>
            <?php foreach ($helpData['topics'] as $topicSlug => $topic): ?>
                <a
                    href="<?= e(help_topic_url($topicSlug)) ?>"
                    class="hc-topic-pill<?= $helpActiveTopic === $topicSlug ? ' is-active' : '' ?>"
                    <?= $helpActiveTopic === $topicSlug ? 'aria-current="page"' : '' ?>
                ><?= e($topic['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="hc-panel">
            <main class="hc-content">
                <?php require $helpMainPartial; ?>
            </main>

            <?php if ($helpViewMode !== 'contact'): ?>
                <aside class="hc-support" aria-label="Get help">
                    <div class="hc-support-inner">
                        <div class="hc-support-copy">
                            <p class="hc-support-title">Still need help?</p>
                            <p class="hc-support-desc">Our team can help with billing, connections, or setup.</p>
                        </div>
                        <div class="hc-support-actions">
                            <a href="<?= e(help_contact_url()) ?>" class="hc-support-btn hc-support-btn--primary">Contact us</a>
                            <a href="<?= e(app_route('ai-salesgirl')) ?>" class="hc-support-btn hc-support-btn--soft"><?= e($askAiLabel) ?></a>
                        </div>
                    </div>
                </aside>
            <?php endif; ?>
        </div>

        <?php if ($showShortcuts): ?>
            <section class="hc-shortcuts" aria-labelledby="hc-shortcuts-title">
                <h2 id="hc-shortcuts-title" class="hc-shortcuts-title font-heading">More ways we can help</h2>
                <div class="hc-shortcuts-grid">
                    <?php foreach ($helpData['more_ways'] as $card): ?>
                        <a href="<?= e((string) $card['href']) ?>" class="hc-shortcut">
                            <span class="hc-shortcut-icon" aria-hidden="true"><?= help_icon_svg((string) $card['icon']) ?></span>
                            <span class="hc-shortcut-body">
                                <span class="hc-shortcut-title"><?= e((string) $card['title']) ?></span>
                                <span class="hc-shortcut-desc"><?= e((string) $card['description']) ?></span>
                            </span>
                            <svg class="hc-shortcut-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<script src="<?= e($appUrl) ?>/assets/js/dashboard-help.js?v=5"></script>
