<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $backUrl */
/** @var string $accountId */
/** @var string $platform */
/** @var string $platformPostId */
?>
<div class="smm-insights-page" data-smm-insights-page>
    <div class="smm-insights-loading-state font-sans" data-smm-insights-boot>
        Loading insights…
    </div>
    <div class="smm-insights-error-state is-hidden font-sans" data-smm-insights-error hidden></div>
    <div class="smm-insights-shell is-hidden" data-smm-insights-shell hidden>
        <header class="smm-insights-topbar">
            <div class="smm-insights-topbar-nav">
                <a class="smm-insights-back" href="<?= e($backUrl) ?>" aria-label="Back to My Posts">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </a>
                <p class="smm-insights-topbar-heading font-sans">Post insights</p>
            </div>

            <div class="smm-insights-topbar-main">
                <div class="smm-insights-thumb-wrap" aria-hidden="true">
                    <img class="smm-insights-thumb" data-smm-insights-thumb src="" alt="">
                </div>
                <div class="smm-insights-topbar-copy">
                    <p class="smm-insights-caption font-sans" data-smm-insights-caption></p>
                    <p class="smm-insights-meta font-sans">
                        <span class="smm-insights-platform-icon" data-smm-insights-platform-icon></span>
                        <span data-smm-insights-format>Post</span>
                        <span class="smm-insights-meta-dot" aria-hidden="true">·</span>
                        <span class="smm-insights-published-wrap">
                            <span class="smm-insights-published-label">Published on: </span>
                            <span data-smm-insights-published></span>
                        </span>
                    </p>
                </div>
            </div>

            <div class="smm-insights-topbar-actions">
                <div class="smm-insights-menu-wrap">
                    <button type="button" class="smm-insights-icon-btn" data-smm-insights-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="More actions">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/>
                        </svg>
                    </button>
                    <div class="smm-insights-menu is-hidden" data-smm-insights-menu hidden role="menu">
                        <button type="button" class="smm-insights-menu-item font-sans" role="menuitem" data-smm-insights-action="edit">Edit post</button>
                        <button type="button" class="smm-insights-menu-item font-sans" role="menuitem" data-smm-insights-action="duplicate">Duplicate post</button>
                        <button type="button" class="smm-insights-menu-item font-sans" role="menuitem" data-smm-insights-action="reshare">Reshare to story</button>
                        <button type="button" class="smm-insights-menu-item smm-insights-menu-item--danger font-sans" role="menuitem" data-smm-insights-action="delete">Delete post</button>
                        <button type="button" class="smm-insights-menu-item font-sans" role="menuitem" data-smm-insights-action="copy-id">Copy post ID</button>
                    </div>
                </div>
                <button type="button" class="smm-insights-boost font-sans" disabled title="Boost is not available for this post">Boost unavailable</button>
            </div>
        </header>

        <div class="smm-insights-layout">
            <div class="smm-insights-main">
                <section class="smm-insights-card">
                    <h2 class="smm-insights-card-title font-sans">Overview</h2>
                    <div class="smm-insights-overview" data-smm-insights-overview></div>
                </section>

                <section class="smm-insights-card" data-smm-insights-views-card>
                    <p class="smm-insights-compare font-sans" data-smm-insights-views-compare hidden></p>
                    <div class="smm-insights-metric-head">
                        <div>
                            <p class="smm-insights-metric-label font-sans">Views</p>
                            <p class="smm-insights-metric-value font-sans" data-smm-insights-views-value>0</p>
                        </div>
                    </div>
                    <div class="smm-insights-tabs" role="tablist" aria-label="Views breakdown">
                        <button type="button" class="smm-insights-tab is-active font-sans" role="tab" aria-selected="true" data-smm-insights-tab="views-total">Total</button>
                        <button type="button" class="smm-insights-tab font-sans" role="tab" aria-selected="false" data-smm-insights-tab="views-audience">Audience</button>
                    </div>
                    <div class="smm-insights-tab-panel" data-smm-insights-panel="views-total">
                        <div class="smm-insights-chart-wrap" data-smm-insights-chart-wrap>
                            <canvas data-smm-insights-views-chart aria-label="Views over time"></canvas>
                        </div>
                        <div class="smm-insights-legend font-sans" data-smm-insights-chart-legend>
                            <span><i class="smm-insights-legend-line smm-insights-legend-line--this"></i> This post's views</span>
                        </div>
                        <p class="smm-insights-chart-note font-sans is-hidden" data-smm-insights-chart-note hidden></p>
                    </div>
                    <div class="smm-insights-tab-panel is-hidden" data-smm-insights-panel="views-audience" hidden>
                        <div class="smm-insights-audience-split" data-smm-insights-views-audience>
                            <div class="smm-insights-audience-split-labels font-sans">
                                <span>Seen once</span>
                                <span>Seen more than once</span>
                            </div>
                            <div class="smm-insights-audience-bar" role="img" aria-label="Views versus reach" data-smm-insights-views-audience-bar>
                                <span class="smm-insights-audience-bar-follower" data-smm-insights-views-once-seg></span>
                                <span class="smm-insights-audience-bar-nonfollower" data-smm-insights-views-repeat-seg></span>
                            </div>
                            <div class="smm-insights-audience-split-values font-sans">
                                <span data-smm-insights-views-once>0</span>
                                <span data-smm-insights-views-repeat>0</span>
                            </div>
                        </div>
                        <div class="smm-insights-audience-empty is-hidden" data-smm-insights-views-audience-empty hidden>
                            <p class="smm-insights-audience-label font-sans">Views vs reach</p>
                            <div class="smm-insights-audience-art" aria-hidden="true">
                                <img class="smm-insights-audience-icon" src="<?= e($appUrl) ?>/assets/images/insights-audience-pulse.png" alt="" width="120" height="120" loading="lazy" decoding="async">
                            </div>
                            <p class="smm-insights-empty-copy font-sans">Views or reach isn't available yet</p>
                            <p class="smm-insights-empty-sub font-sans">These metrics can take a while to appear. Check back after the post has more activity.</p>
                        </div>
                    </div>
                </section>

                <section class="smm-insights-card" data-smm-insights-reach-card>
                    <p class="smm-insights-compare font-sans" data-smm-insights-reach-compare hidden></p>
                    <div class="smm-insights-metric-head">
                        <div>
                            <p class="smm-insights-metric-label font-sans">Reach</p>
                            <p class="smm-insights-metric-value font-sans" data-smm-insights-reach-value>0</p>
                        </div>
                    </div>
                    <p class="smm-insights-audience-label font-sans">Audience</p>
                    <div class="smm-insights-demo is-hidden" data-smm-insights-demo-wrap hidden>
                        <div class="smm-insights-demo-chart">
                            <canvas data-smm-insights-demo-chart aria-label="Audience age and gender"></canvas>
                        </div>
                        <div class="smm-insights-demo-legend font-sans" data-smm-insights-demo-legend></div>
                    </div>
                    <div class="smm-insights-demo-empty" data-smm-insights-demo-empty>
                        <div class="smm-insights-audience-art" aria-hidden="true">
                            <img class="smm-insights-audience-icon" src="<?= e($appUrl) ?>/assets/images/insights-audience-pulse.png" alt="" width="120" height="120" loading="lazy" decoding="async">
                        </div>
                        <p class="smm-insights-empty-copy font-sans">Audience demographics aren’t available yet</p>
                        <p class="smm-insights-empty-sub font-sans" data-smm-insights-demo-empty-sub>Meta only returns age and gender when your account meets their insights rules (usually 100+ followers / reached accounts).</p>
                    </div>
                </section>

                <section class="smm-insights-card is-hidden" data-smm-insights-watch-card hidden>
                    <div class="smm-insights-watch-grid">
                        <div>
                            <p class="smm-insights-metric-label font-sans">Average watch time</p>
                            <p class="smm-insights-metric-value font-sans" data-smm-insights-watch-avg>—</p>
                        </div>
                        <div>
                            <p class="smm-insights-metric-label font-sans">Watch time</p>
                            <p class="smm-insights-metric-value font-sans" data-smm-insights-watch-total>—</p>
                        </div>
                    </div>
                </section>

                <section class="smm-insights-card" data-smm-insights-interactions-card>
                    <p class="smm-insights-compare font-sans" data-smm-insights-interactions-compare hidden></p>
                    <div class="smm-insights-metric-head">
                        <div>
                            <p class="smm-insights-metric-label font-sans">Interactions</p>
                            <p class="smm-insights-metric-value font-sans" data-smm-insights-interactions-value>0</p>
                        </div>
                    </div>
                    <div class="smm-insights-breakdown" data-smm-insights-interactions-breakdown></div>
                </section>
            </div>

            <aside class="smm-insights-side">
                <section class="smm-insights-card smm-insights-preview-card">
                    <h2 class="smm-insights-card-title font-sans" data-smm-insights-preview-title>Feed preview</h2>
                    <div class="smm-insights-feed" data-smm-insights-feed></div>
                    <a class="smm-insights-view-post font-sans" data-smm-insights-permalink href="#" target="_blank" rel="noopener">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M14 3h7v7"/><path d="M10 14L21 3"/><path d="M21 14v7H3V3h7"/>
                        </svg>
                        <span data-smm-insights-permalink-label>View post</span>
                    </a>
                </section>

                <section class="smm-insights-card smm-insights-comment-card">
                    <div class="smm-insights-comment-row">
                        <img class="smm-insights-comment-avatar" data-smm-insights-comment-avatar src="" alt="">
                        <label class="smm-insights-comment-field font-sans">
                            <span class="sr-only">Add a comment</span>
                            <input type="text" placeholder="Add a comment..." data-smm-insights-comment-input autocomplete="off">
                            <button type="button" class="smm-insights-comment-emoji" aria-label="Emoji" tabindex="-1">☺</button>
                        </label>
                        <button type="button" class="smm-insights-comment-send" data-smm-insights-comment-send aria-label="Send comment">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>

<script>
    window.LeadBirdSocialInsights = {
        appUrl: <?= json_encode($appUrl, JSON_THROW_ON_ERROR) ?>,
        backUrl: <?= json_encode($backUrl, JSON_THROW_ON_ERROR) ?>,
        accountId: <?= json_encode($accountId, JSON_THROW_ON_ERROR) ?>,
        platform: <?= json_encode($platform, JSON_THROW_ON_ERROR) ?>,
        platformPostId: <?= json_encode($platformPostId, JSON_THROW_ON_ERROR) ?>,
        platformIcons: <?= json_encode([
            'instagram' => dash_platform_badge_icon('instagram', 14),
            'facebook' => dash_platform_badge_icon('facebook', 14),
            'tiktok' => dash_platform_badge_icon('tiktok', 14),
        ], JSON_THROW_ON_ERROR) ?>,
        endpoints: {
            insights: <?= json_encode(app_route('social-media/insights'), JSON_THROW_ON_ERROR) ?>,
            delete: <?= json_encode(app_route('social-media/delete'), JSON_THROW_ON_ERROR) ?>,
            update: <?= json_encode(app_route('social-media/update'), JSON_THROW_ON_ERROR) ?>,
            liveAction: <?= json_encode(app_route('social-media/live-action'), JSON_THROW_ON_ERROR) ?>,
        },
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-social-media-insights.js?v=15" defer></script>
