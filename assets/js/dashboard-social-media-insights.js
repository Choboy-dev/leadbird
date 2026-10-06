(function () {
    'use strict';

    var config = window.LeadBirdSocialInsights || {};
    var root = document.querySelector('[data-smm-insights-page]');
    if (!root) return;

    var bootEl = root.querySelector('[data-smm-insights-boot]');
    var errorEl = root.querySelector('[data-smm-insights-error]');
    var shellEl = root.querySelector('[data-smm-insights-shell]');
    var menuToggle = root.querySelector('[data-smm-insights-menu-toggle]');
    var menuEl = root.querySelector('[data-smm-insights-menu]');
    var viewsChart = null;
    var demoChart = null;
    var state = {
        accountId: config.accountId || '',
        platform: config.platform || '',
        platformPostId: config.platformPostId || '',
        data: null,
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatNumber(value) {
        if (value == null || value === '') return '—';
        var num = Number(value);
        if (!Number.isFinite(num)) return String(value);
        return new Intl.NumberFormat('en-US').format(Math.round(num));
    }

    var PLATFORM_LABELS = { instagram: 'Instagram', facebook: 'Facebook', tiktok: 'TikTok' };

    /** Never guess the network — a wrong name here misreads the whole page. */
    function platformLabel(platform) {
        return PLATFORM_LABELS[String(platform || '')] || 'the social network';
    }

    function activePlatform(account) {
        return String((account && account.platform) || state.platform || '');
    }

    /** Meta returns Reel watch times in milliseconds. */
    function formatWatchDuration(ms) {
        if (ms == null || ms === '' || !Number.isFinite(Number(ms))) return null;
        var totalSeconds = Math.max(0, Math.round(Number(ms) / 1000));
        if (totalSeconds < 60) return totalSeconds + 's';
        var hours = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = totalSeconds % 60;
        if (hours > 0) {
            return hours + 'h ' + minutes + 'm';
        }
        if (minutes > 0 && seconds > 0) return minutes + 'm ' + seconds + 's';
        if (minutes > 0) return minutes + 'm';
        return seconds + 's';
    }

    function truncateCaption(text, max) {
        var value = String(text || '').replace(/\s+/g, ' ').trim();
        if (!value) return 'Untitled post';
        if (value.length <= max) return value;
        return value.slice(0, max - 1) + '…';
    }

    function formatPublished(iso) {
        if (!iso) return '—';
        var date = new Date(iso);
        if (Number.isNaN(date.getTime())) return iso;
        return date.toLocaleString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        }).replace(', ', ' ').replace(' at ', ', ');
    }

    function brandColor(name, fallback) {
        var styles = getComputedStyle(document.documentElement);
        return (styles.getPropertyValue(name) || '').trim() || fallback;
    }

    function showError(message) {
        if (bootEl) {
            bootEl.classList.add('is-hidden');
            bootEl.hidden = true;
        }
        if (shellEl) {
            shellEl.classList.add('is-hidden');
            shellEl.hidden = true;
        }
        if (errorEl) {
            errorEl.textContent = message || 'Could not load insights for this post.';
            errorEl.classList.remove('is-hidden');
            errorEl.hidden = false;
        }
    }

    function showShell() {
        if (bootEl) {
            bootEl.classList.add('is-hidden');
            bootEl.hidden = true;
        }
        if (errorEl) {
            errorEl.classList.add('is-hidden');
            errorEl.hidden = true;
        }
        if (shellEl) {
            shellEl.classList.remove('is-hidden');
            shellEl.hidden = false;
        }
    }

    function setMenuOpen(open) {
        if (!menuEl || !menuToggle) return;
        menuEl.classList.toggle('is-hidden', !open);
        menuEl.hidden = !open;
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function renderViewsAudience(audience) {
        var split = root.querySelector('[data-smm-insights-views-audience]');
        var empty = root.querySelector('[data-smm-insights-views-audience-empty]');
        var onceEl = root.querySelector('[data-smm-insights-views-once]');
        var repeatEl = root.querySelector('[data-smm-insights-views-repeat]');
        var onceSeg = root.querySelector('[data-smm-insights-views-once-seg]');
        var repeatSeg = root.querySelector('[data-smm-insights-views-repeat-seg]');
        var bar = root.querySelector('[data-smm-insights-views-audience-bar]');

        var viewsAudience = (audience && audience.views) || {};
        var available = !!viewsAudience.available;
        var seenOnce = Number(viewsAudience.seen_once);
        var seenRepeat = Number(viewsAudience.seen_more_than_once);
        if (!Number.isFinite(seenOnce)) seenOnce = 0;
        if (!Number.isFinite(seenRepeat)) seenRepeat = 0;

        if (split) {
            split.classList.toggle('is-hidden', !available);
            split.hidden = !available;
        }
        if (empty) {
            empty.classList.toggle('is-hidden', available);
            empty.hidden = available;
        }

        if (!available) return;

        if (onceEl) onceEl.textContent = formatNumber(seenOnce);
        if (repeatEl) repeatEl.textContent = formatNumber(seenRepeat);

        var total = seenOnce + seenRepeat;
        var oncePct = total > 0 ? (seenOnce / total) * 100 : 0;
        var repeatPct = total > 0 ? (seenRepeat / total) * 100 : 0;

        if (seenOnce > 0 && oncePct < 4) oncePct = 4;
        if (seenRepeat > 0 && repeatPct < 4) repeatPct = 4;
        if (seenOnce > 0 && seenRepeat > 0) {
            var scale = 100 / (oncePct + repeatPct);
            oncePct *= scale;
            repeatPct *= scale;
        } else if (total === 0) {
            oncePct = 50;
            repeatPct = 50;
        }

        if (onceSeg) {
            onceSeg.style.flexGrow = String(oncePct);
            onceSeg.style.flexBasis = '0';
            onceSeg.style.display = seenOnce === 0 && seenRepeat > 0 ? 'none' : '';
        }
        if (repeatSeg) {
            repeatSeg.style.flexGrow = String(repeatPct);
            repeatSeg.style.flexBasis = '0';
            repeatSeg.style.display = seenRepeat === 0 && seenOnce > 0 ? 'none' : '';
        }
        if (bar) {
            bar.setAttribute(
                'aria-label',
                'Seen once ' + formatNumber(seenOnce) + ', seen more than once ' + formatNumber(seenRepeat)
            );
        }
    }

    function renderOverview(overview) {
        var host = root.querySelector('[data-smm-insights-overview]');
        if (!host) return;
        var items = [
            { key: 'views', label: 'Views' },
            { key: 'reach', label: 'Reach' },
            { key: 'interactions', label: 'Interactions' },
        ];
        var watchTotal = formatWatchDuration(overview.watch_time_total_ms);
        if (watchTotal) {
            items.push({ key: '_watch_total', label: 'Watch time', display: watchTotal });
        }
        if (overview.follows != null && overview.follows !== '') {
            items.push({ key: 'follows', label: 'Follows' });
        }
        host.innerHTML = items.map(function (item) {
            var value = item.display != null ? item.display : formatNumber(overview[item.key]);
            return (
                '<div class="smm-insights-kpi">' +
                '<p class="smm-insights-kpi-label font-sans">' + escapeHtml(item.label) + '</p>' +
                '<p class="smm-insights-kpi-value font-sans">' + escapeHtml(value) + '</p>' +
                '</div>'
            );
        }).join('');
    }

    function renderWatchCard(overview) {
        var card = root.querySelector('[data-smm-insights-watch-card]');
        var avgEl = root.querySelector('[data-smm-insights-watch-avg]');
        var totalEl = root.querySelector('[data-smm-insights-watch-total]');
        var avg = formatWatchDuration(overview.watch_time_avg_ms);
        var total = formatWatchDuration(overview.watch_time_total_ms);
        if (!card) return;
        if (!avg && !total) {
            card.classList.add('is-hidden');
            card.hidden = true;
            return;
        }
        card.classList.remove('is-hidden');
        card.hidden = false;
        if (avgEl) avgEl.textContent = avg || '—';
        if (totalEl) totalEl.textContent = total || '—';
    }

    function renderDemographics(demographics) {
        var wrap = root.querySelector('[data-smm-insights-demo-wrap]');
        var empty = root.querySelector('[data-smm-insights-demo-empty]');
        var legend = root.querySelector('[data-smm-insights-demo-legend]');
        var canvas = root.querySelector('[data-smm-insights-demo-chart]');
        var ages = ((demographics && demographics.ages) || []).filter(function (row) {
            // Match Meta Business Suite buckets (skip empty teen bucket).
            return row && row.age !== '13-17';
        });
        var available = !!(demographics && demographics.available && ages.length);

        if (!available) {
            if (wrap) {
                wrap.classList.add('is-hidden');
                wrap.hidden = true;
            }
            if (empty) {
                empty.classList.remove('is-hidden');
                empty.hidden = false;
            }
            var emptySub = root.querySelector('[data-smm-insights-demo-empty-sub]');
            if (emptySub && activePlatform((state.data && state.data.account) || {}) === 'tiktok') {
                emptySub.textContent = 'TikTok does not share age or gender breakdowns for a video through its API.';
            }
            if (demoChart) {
                demoChart.destroy();
                demoChart = null;
            }
            return;
        }

        if (empty) {
            empty.classList.add('is-hidden');
            empty.hidden = true;
        }
        if (wrap) {
            wrap.classList.remove('is-hidden');
            wrap.hidden = false;
        }

        var womenPct = demographics.women_pct != null ? demographics.women_pct : null;
        var menPct = demographics.men_pct != null ? demographics.men_pct : null;
        if (legend) {
            legend.innerHTML =
                '<div class="smm-insights-demo-legend-item">' +
                '<span class="smm-insights-demo-legend-top">' +
                '<i class="smm-insights-demo-swatch smm-insights-demo-swatch--women" aria-hidden="true"></i>' +
                '<span class="smm-insights-demo-legend-label">Women</span>' +
                '</span>' +
                '<span class="smm-insights-demo-legend-pct">' +
                (womenPct != null ? escapeHtml(String(womenPct)) + '%' : '—') +
                '</span>' +
                '</div>' +
                '<div class="smm-insights-demo-legend-item">' +
                '<span class="smm-insights-demo-legend-top">' +
                '<i class="smm-insights-demo-swatch smm-insights-demo-swatch--men" aria-hidden="true"></i>' +
                '<span class="smm-insights-demo-legend-label">Men</span>' +
                '</span>' +
                '<span class="smm-insights-demo-legend-pct">' +
                (menPct != null ? escapeHtml(String(menPct)) + '%' : '—') +
                '</span>' +
                '</div>';
        }

        function drawDemo() {
            if (!canvas || typeof window.Chart === 'undefined') return;
            if (demoChart) {
                demoChart.destroy();
                demoChart = null;
            }
            var womenBlue = 'rgba(0, 56, 255, 0.42)';
            var menBlue = brandColor('--brand-blue', '#0038FF');
            var yMax = 60;
            ages.forEach(function (row) {
                yMax = Math.max(yMax, Number(row.women) || 0, Number(row.men) || 0);
            });
            yMax = Math.ceil(yMax / 20) * 20;
            if (yMax < 60) yMax = 60;

            demoChart = new window.Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ages.map(function (row) { return row.age; }),
                    datasets: [
                        {
                            label: 'Women',
                            data: ages.map(function (row) { return row.women; }),
                            backgroundColor: womenBlue,
                            borderRadius: { topLeft: 3, topRight: 3, bottomLeft: 0, bottomRight: 0 },
                            borderSkipped: false,
                            categoryPercentage: 0.72,
                            barPercentage: 0.98,
                        },
                        {
                            label: 'Men',
                            data: ages.map(function (row) { return row.men; }),
                            backgroundColor: menBlue,
                            borderRadius: { topLeft: 3, topRight: 3, bottomLeft: 0, bottomRight: 0 },
                            borderSkipped: false,
                            categoryPercentage: 0.72,
                            barPercentage: 0.98,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 4, right: 4, bottom: 0, left: 0 },
                    },
                    datasets: {
                        bar: {
                            categoryPercentage: 0.72,
                            barPercentage: 0.98,
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ' + ctx.parsed.y + '%';
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                color: '#65676b',
                                font: { size: 12, family: 'inherit' },
                                maxRotation: 0,
                                autoSkip: false,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            max: yMax,
                            border: { display: false },
                            ticks: {
                                stepSize: 20,
                                color: '#65676b',
                                font: { size: 12, family: 'inherit' },
                                callback: function (value) { return value + '%'; },
                                padding: 8,
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.08)',
                                drawTicks: false,
                            },
                        },
                    },
                },
            });
        }

        if (typeof window.Chart !== 'undefined') {
            drawDemo();
        } else {
            var tries = 0;
            var wait = window.setInterval(function () {
                tries += 1;
                if (typeof window.Chart !== 'undefined') {
                    window.clearInterval(wait);
                    drawDemo();
                } else if (tries > 40) {
                    window.clearInterval(wait);
                }
            }, 50);
        }
    }

    function setCompareText(selector, comparisonItem) {
        var el = root.querySelector(selector);
        if (!el) return;
        var available = comparisonItem && comparisonItem.available && comparisonItem.html;
        if (available) {
            el.innerHTML = comparisonItem.html;
            el.hidden = false;
        } else {
            el.innerHTML = '';
            el.hidden = true;
        }
    }

    function renderInteractionsBreakdown(overview) {
        var host = root.querySelector('[data-smm-insights-interactions-breakdown]');
        if (!host) return;
        var items = [
            { key: 'likes', label: 'Likes and reactions' },
            { key: 'comments', label: 'Comments' },
            { key: 'shares', label: 'Shares' },
            { key: 'saves', label: 'Saves' },
        ];
        host.innerHTML = items.map(function (item) {
            return (
                '<div class="smm-insights-breakdown-item">' +
                '<p class="smm-insights-breakdown-label font-sans">' + escapeHtml(item.label) + '</p>' +
                '<p class="smm-insights-breakdown-value font-sans">' + escapeHtml(formatNumber(overview[item.key])) + '</p>' +
                '</div>'
            );
        }).join('');
    }

    function looksLikeImageUrl(url) {
        return /\.(jpe?g|png|gif|webp|bmp)(\?|$)/i.test(String(url || ''));
    }

    function looksLikeVideoUrl(url) {
        var raw = String(url || '');
        if (!raw) return false;
        if (/social-media\/media\.php/i.test(raw)) return true;
        if (/\.(mp4|mov|m4v|webm)(\?|$)/i.test(raw)) return true;
        // Instagram CDN video paths often omit a file extension.
        if (/cdninstagram\.com|fbcdn\.net|scontent\./i.test(raw) && !looksLikeImageUrl(raw)) return true;
        return false;
    }

    function instagramEmbedUrl(permalink) {
        var raw = String(permalink || '').trim();
        if (!raw) return '';
        try {
            var u = new URL(raw);
            if (!/(^|\.)instagram\.com$/i.test(u.hostname)) return '';
            var path = u.pathname.replace(/\/+$/, '');
            if (!/\/(reel|p|tv)\//i.test(path)) return '';
            return u.origin + path + '/embed';
        } catch (error) {
            return '';
        }
    }

    var FEED_ICON_HEART = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
    var FEED_ICON_COMMENT = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>';
    var FEED_ICON_SEND = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>';
    var FEED_ICON_SAVE = '<svg class="smm-insights-feed-save" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';

    /** TikTok's rail is like, comment, bookmark, share — not Instagram's send/save pair. */
    function feedActionsHtml(platform) {
        var icons = platform === 'tiktok'
            ? [FEED_ICON_HEART, FEED_ICON_COMMENT, FEED_ICON_SAVE, FEED_ICON_SEND]
            : [FEED_ICON_HEART, FEED_ICON_COMMENT, FEED_ICON_SEND, FEED_ICON_SAVE];

        return '<div class="smm-insights-feed-actions" aria-hidden="true">' + icons.join('') + '</div>';
    }

    function renderFeed(post, account) {
        var feed = root.querySelector('[data-smm-insights-feed]');
        if (!feed) return;

        var platform = activePlatform(account);
        var handle = (account.username || account.display_name || '').replace(/^@/, '');
        var avatar = account.avatar_src || '';
        var mediaUrl = String(post.media_url || '').trim();
        var thumbUrl = String(post.thumbnail_url || '').trim();
        var permalink = String(post.permalink || '').trim();
        var mediaType = String(post.media_type || '').toLowerCase();
        var isVideo = mediaType === 'video' || !!post.is_reel || post.post_format === 'Reel';
        // Play when we have a real video URL (proxy or CDN) — never put a still into <video>.
        var canPlayVideo = isVideo && mediaUrl !== '' && mediaUrl !== thumbUrl && !looksLikeImageUrl(mediaUrl)
            && (looksLikeVideoUrl(mediaUrl) || mediaUrl.indexOf('/social-media/media.php') !== -1);
        // Meta omits media_url when a Reel uses copyrighted audio — only official embed can play those.
        var embedUrl = (!canPlayVideo && isVideo && platform === 'instagram')
            ? instagramEmbedUrl(permalink)
            : '';
        var caption = String(post.caption || '').trim();
        var captionHtml = caption
            ? '<strong>' + escapeHtml(handle) + '</strong> ' + escapeHtml(truncateCaption(caption, 90)) +
                (caption.length > 90 ? ' <span class="smm-insights-feed-more">... more</span>' : '')
            : '<strong>' + escapeHtml(handle) + '</strong>';

        var brandTitle = platformLabel(platform);
        var mediaHtml;
        if (canPlayVideo) {
            mediaHtml =
                '<div class="smm-insights-feed-video-wrap">' +
                '<video class="smm-insights-feed-media smm-insights-feed-media--video" ' +
                'src="' + escapeHtml(mediaUrl) + '" ' +
                (thumbUrl ? 'poster="' + escapeHtml(thumbUrl) + '" ' : '') +
                'controls playsinline muted autoplay loop preload="auto" ' +
                'controlslist="nodownload"></video>' +
                '</div>';
        } else if (embedUrl) {
            mediaHtml =
                '<div class="smm-insights-feed-video-wrap smm-insights-feed-embed-wrap">' +
                '<iframe class="smm-insights-feed-embed" title="Reel preview" ' +
                'src="' + escapeHtml(embedUrl) + '" ' +
                'loading="lazy" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" ' +
                'allowfullscreen scrolling="no"></iframe>' +
                '</div>';
        } else if (thumbUrl || mediaUrl) {
            mediaHtml = '<img class="smm-insights-feed-media" src="' + escapeHtml(thumbUrl || mediaUrl) + '" alt="">';
        } else {
            mediaHtml = '<div class="smm-insights-feed-media smm-insights-feed-media--empty font-sans">No preview</div>';
        }

        feed.classList.toggle('smm-insights-feed--tiktok', platform === 'tiktok');

        var headerHtml =
            '<p class="smm-insights-feed-brand">' + escapeHtml(brandTitle) + '</p>' +
            '<div class="smm-insights-feed-user">' +
            (avatar ? '<img class="smm-insights-feed-avatar" src="' + escapeHtml(avatar) + '" alt="">' : '<span class="smm-insights-feed-avatar"></span>') +
            '<p class="smm-insights-feed-handle font-sans">' + escapeHtml(handle || brandTitle) + '</p>' +
            '</div>';

        if (platform === 'tiktok') {
            // Real TikTok overlays the action rail down the right edge of the video,
            // so wrap media + rail in a stage instead of dropping a row underneath.
            feed.innerHTML =
                headerHtml +
                '<div class="smm-insights-feed-stage">' +
                mediaHtml +
                feedActionsHtml(platform) +
                '</div>' +
                '<p class="smm-insights-feed-caption font-sans">' + captionHtml + '</p>';
        } else {
            feed.innerHTML =
                headerHtml +
                mediaHtml +
                feedActionsHtml(platform) +
                '<p class="smm-insights-feed-caption font-sans">' + captionHtml + '</p>';
        }

        if (canPlayVideo) {
            var video = feed.querySelector('video');
            if (video) {
                var tryPlay = function () {
                    var playPromise = video.play();
                    if (playPromise && typeof playPromise.catch === 'function') {
                        playPromise.catch(function () {
                            // Autoplay blocked — controls still work.
                        });
                    }
                };
                if (video.readyState >= 2) {
                    tryPlay();
                } else {
                    video.addEventListener('loadeddata', tryPlay, { once: true });
                    video.load();
                }
            }
        }
    }

    function formatChartPointTime(iso) {
        if (!iso) return '';
        var date = new Date(iso);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        }).replace(' AM', 'am').replace(' PM', 'pm');
    }

    /**
     * Some networks report only a lifetime total, so there is no honest line to draw.
     */
    function setChartTimelineVisible(visible, note) {
        var wrap = root.querySelector('[data-smm-insights-chart-wrap]');
        var legend = root.querySelector('[data-smm-insights-chart-legend]');
        var noteEl = root.querySelector('[data-smm-insights-chart-note]');

        [wrap, legend].forEach(function (el) {
            if (!el) return;
            el.classList.toggle('is-hidden', !visible);
            el.hidden = !visible;
        });

        if (noteEl) {
            noteEl.textContent = visible ? '' : (note || '');
            noteEl.classList.toggle('is-hidden', visible);
            noteEl.hidden = visible;
        }
    }

    function renderChart(series) {
        var canvas = root.querySelector('[data-smm-insights-views-chart]');
        if (!canvas) return;

        if (!series || !Array.isArray(series.labels) || series.labels.length < 2) {
            if (viewsChart) {
                viewsChart.destroy();
                viewsChart = null;
            }
            var label = platformLabel(activePlatform((state.data && state.data.account) || {}));
            setChartTimelineVisible(false, label + ' reports one lifetime total for this post, so there is no views-over-time graph.');
            return;
        }

        setChartTimelineVisible(true, '');

        if (typeof window.Chart === 'undefined') {
            var tries = 0;
            var wait = window.setInterval(function () {
                tries += 1;
                if (typeof window.Chart !== 'undefined') {
                    window.clearInterval(wait);
                    renderChart(series);
                    return;
                }
                if (tries > 40) {
                    window.clearInterval(wait);
                }
            }, 50);
            return;
        }

        var labels = (series && series.labels) || ['15m', '9h', '1d 6h', '7d'];
        var pointTimes = (series && series.point_times) || [];
        var thisPost = (series && series.this_post) || [0, 0, 0, 0];
        var blue = brandColor('--brand-blue', '#0038FF');

        if (viewsChart) {
            viewsChart.destroy();
            viewsChart = null;
        }

        var crosshairPlugin = {
            id: 'smmViewsCrosshair',
            afterDraw: function (chart) {
                var active = chart.tooltip && chart.tooltip.getActiveElements
                    ? chart.tooltip.getActiveElements()
                    : [];
                if (!active.length) return;
                var x = active[0].element.x;
                var yAxis = chart.scales.y;
                var ctx = chart.ctx;
                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([3, 3]);
                ctx.moveTo(x, yAxis.top);
                ctx.lineTo(x, yAxis.bottom);
                ctx.lineWidth = 1;
                ctx.strokeStyle = '#bcc0c4';
                ctx.stroke();
                ctx.restore();
            },
        };

        viewsChart = new window.Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Views',
                    data: thisPost,
                    borderColor: blue,
                    backgroundColor: blue,
                    borderWidth: 2.5,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBorderWidth: 2,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: blue,
                    fill: false,
                }],
            },
            plugins: [crosshairPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#65676b',
                        bodyColor: '#050505',
                        borderColor: '#dddfe2',
                        borderWidth: 1,
                        displayColors: true,
                        boxPadding: 4,
                        padding: 10,
                        titleFont: { size: 12, weight: '400' },
                        bodyFont: { size: 13, weight: '500' },
                        callbacks: {
                            title: function (items) {
                                if (!items || !items.length) return '';
                                var idx = items[0].dataIndex;
                                return formatChartPointTime(pointTimes[idx]) || (labels[idx] || '');
                            },
                            label: function (context) {
                                return 'Views: ' + formatNumber(context.parsed.y);
                            },
                            labelColor: function () {
                                return { borderColor: blue, backgroundColor: blue };
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#8a8d91', font: { size: 11 } },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.06)' },
                        ticks: {
                            color: '#8a8d91',
                            font: { size: 11 },
                            callback: function (value) {
                                return formatNumber(value);
                            },
                        },
                        border: { display: false },
                    },
                },
            },
        });
    }

    function bindTabs() {
        root.querySelectorAll('[data-smm-insights-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-smm-insights-tab');
                root.querySelectorAll('[data-smm-insights-tab]').forEach(function (el) {
                    var active = el === btn;
                    el.classList.toggle('is-active', active);
                    el.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                root.querySelectorAll('[data-smm-insights-panel]').forEach(function (panel) {
                    var match = panel.getAttribute('data-smm-insights-panel') === tab;
                    panel.classList.toggle('is-hidden', !match);
                    panel.hidden = !match;
                });
            });
        });
    }

    function openEditFlow() {
        var permissions = (state.data && state.data.permissions) || {};
        if (permissions.can_edit === false) {
            window.LeadBirdModal.alert({
                title: 'Edit unavailable',
                message: unsupportedActionMessage('edit', 'edited'),
            });
            return;
        }

        var post = (state.data && state.data.post) || {};
        var current = String(post.caption || '');

        window.LeadBirdModal.prompt({
            title: 'Edit post',
            message: 'Update the caption for this live post.',
            label: 'Caption',
            defaultValue: current,
            confirmLabel: 'Save',
            cancelLabel: 'Cancel',
        }).then(function (next) {
            if (next == null) return;
            next = String(next).trim();
            if (next === current) return;

            fetch(config.endpoints.update, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    type: 'live',
                    account_id: state.accountId,
                    platform_post_id: state.platformPostId,
                    caption: next,
                }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        window.LeadBirdModal.alert({
                            title: 'Could not edit post',
                            message: data.error || data.message || 'Meta did not accept this caption change.',
                            tone: 'error',
                        });
                        return;
                    }
                    loadInsights();
                })
                .catch(function () {
                    window.LeadBirdModal.alert({
                        title: 'Could not edit post',
                        message: 'Something went wrong. Try again in a moment.',
                        tone: 'error',
                    });
                });
        });
    }

    function runLiveAction(action, confirmOptions) {
        var endpoint = config.endpoints && config.endpoints.liveAction;
        if (!endpoint) {
            window.LeadBirdModal.alert({
                title: 'Action unavailable',
                message: 'This action is not configured yet.',
                tone: 'error',
            });
            return;
        }

        var proceed = function () {
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: action,
                    account_id: state.accountId,
                    platform_post_id: state.platformPostId,
                }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        window.LeadBirdModal.alert({
                            title: action === 'duplicate' ? 'Duplicate failed' : 'Reshare failed',
                            message: data.error || data.message || 'Could not complete this action.',
                            tone: 'error',
                        });
                        return;
                    }

                    var go = function () {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    };

                    if (data.message) {
                        window.LeadBirdModal.alert({
                            title: action === 'duplicate' ? 'Draft created' : 'Story published',
                            message: data.message,
                        }).then(go);
                        return;
                    }
                    go();
                })
                .catch(function () {
                    window.LeadBirdModal.alert({
                        title: action === 'duplicate' ? 'Duplicate failed' : 'Reshare failed',
                        message: 'Something went wrong. Try again in a moment.',
                        tone: 'error',
                    });
                });
        };

        if (confirmOptions) {
            window.LeadBirdModal.confirm(confirmOptions).then(function (ok) {
                if (ok) proceed();
            });
            return;
        }

        proceed();
    }

    function duplicatePost() {
        runLiveAction('duplicate');
    }

    function reshareToStory() {
        var permissions = (state.data && state.data.permissions) || {};
        if (permissions.can_reshare_story === false) {
            window.LeadBirdModal.alert({
                title: 'Reshare unavailable',
                message: 'This post has no photo or video to share as a story.',
            });
            return;
        }

        runLiveAction('reshare_story', {
            title: 'Reshare to story?',
            message: 'This publishes the post media as a story on the same account.',
            confirmLabel: 'Publish story',
            cancelLabel: 'Cancel',
        });
    }

    /**
     * TikTok publishes no edit or delete endpoint, so say so plainly instead of
     * letting the request fail against the network.
     */
    function unsupportedActionMessage(verb, pastTense) {
        var account = (state.data && state.data.account) || {};
        var label = platformLabel(activePlatform(account));

        if (activePlatform(account) === 'tiktok') {
            return 'TikTok does not let apps ' + verb + ' a published video. Use View post to '
                + verb + ' it in TikTok.';
        }

        return 'This post cannot be ' + pastTense + ' from Lead Bird. Use View post to open it on ' + label + '.';
    }

    function deletePost() {
        var permissions = (state.data && state.data.permissions) || {};
        if (permissions.can_delete === false) {
            window.LeadBirdModal.alert({
                title: 'Delete unavailable',
                message: unsupportedActionMessage('delete', 'deleted'),
            });
            return;
        }

        var account = (state.data && state.data.account) || {};
        window.LeadBirdModal.confirm({
            title: 'Delete this post?',
            message: 'This removes it from ' + platformLabel(activePlatform(account)) + '. You cannot undo this.',
            confirmLabel: 'Delete',
            cancelLabel: 'Cancel',
            tone: 'danger',
        }).then(function (ok) {
            if (!ok) return;
            fetch(config.endpoints.delete, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'delete_live',
                    account_id: state.accountId,
                    platform_post_id: state.platformPostId,
                }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        window.LeadBirdModal.alert({
                            title: 'Delete failed',
                            message: data.error || 'Could not delete this post.',
                            tone: 'error',
                        });
                        return;
                    }
                    window.location.href = config.backUrl || (config.appUrl + '/social-media?view=content');
                })
                .catch(function () {
                    window.LeadBirdModal.alert({
                        title: 'Delete failed',
                        message: 'Something went wrong. Try again in a moment.',
                        tone: 'error',
                    });
                });
        });
    }

    function copyPostId() {
        var id = state.platformPostId || '';
        if (!id) {
            window.LeadBirdModal.alert({
                title: 'Copy failed',
                message: 'No post ID is available.',
                tone: 'error',
            });
            return;
        }

        var showCopied = function () {
            window.LeadBirdModal.alert({
                title: 'Post ID copied',
                message: id,
            });
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(id).then(showCopied).catch(function () {
                window.LeadBirdModal.alert({
                    title: 'Post ID',
                    message: id,
                });
            });
            return;
        }

        window.LeadBirdModal.alert({
            title: 'Post ID',
            message: id,
        });
    }

    function handleMenuAction(action) {
        setMenuOpen(false);
        if (action === 'edit') {
            openEditFlow();
            return;
        }
        if (action === 'duplicate') {
            duplicatePost();
            return;
        }
        if (action === 'reshare') {
            reshareToStory();
            return;
        }
        if (action === 'delete') {
            deletePost();
            return;
        }
        if (action === 'copy-id') {
            copyPostId();
        }
    }

    function bindMenu() {
        if (menuToggle) {
            menuToggle.addEventListener('click', function (event) {
                event.stopPropagation();
                var open = menuEl && menuEl.hidden;
                setMenuOpen(!!open);
            });
        }
        root.querySelectorAll('[data-smm-insights-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                handleMenuAction(btn.getAttribute('data-smm-insights-action'));
            });
        });
        document.addEventListener('click', function () {
            setMenuOpen(false);
        });
        if (menuEl) {
            menuEl.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }
    }

    function bindComment() {
        var send = root.querySelector('[data-smm-insights-comment-send]');
        var input = root.querySelector('[data-smm-insights-comment-input]');
        if (!send || !input) return;

        function goToPost() {
            var permalink = ((state.data && state.data.post) || {}).permalink || '';
            if (permalink) {
                window.open(permalink, '_blank', 'noopener');
                return;
            }
            var account = (state.data && state.data.account) || {};
            window.LeadBirdModal.alert({
                title: 'Open the post to comment',
                message: 'Comments are posted on ' + platformLabel(activePlatform(account)) + '. Use View post to continue there.',
            });
        }

        send.addEventListener('click', goToPost);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                goToPost();
            }
        });
    }

    function render(data) {
        state.data = data;
        var post = data.post || {};
        var account = data.account || {};
        var overview = data.overview || {};
        var comparison = data.comparison || {};
        var series = data.series || {};

        var thumb = root.querySelector('[data-smm-insights-thumb]');
        if (thumb) {
            var thumbSrc = post.thumbnail_url || '';
            if (!thumbSrc && String(post.media_type || '').toLowerCase() !== 'video' && !post.is_reel) {
                thumbSrc = post.media_url || '';
            }
            if (thumbSrc && !/\.mp4(\?|$)/i.test(thumbSrc)) {
                thumb.src = thumbSrc;
                thumb.style.display = '';
                thumb.onerror = function () {
                    thumb.style.display = 'none';
                };
            } else {
                thumb.removeAttribute('src');
                thumb.style.display = 'none';
            }
        }

        var captionEl = root.querySelector('[data-smm-insights-caption]');
        if (captionEl) captionEl.textContent = truncateCaption(post.caption, 120);

        var formatEl = root.querySelector('[data-smm-insights-format]');
        if (formatEl) formatEl.textContent = post.post_format || 'Post';

        var publishedEl = root.querySelector('[data-smm-insights-published]');
        if (publishedEl) publishedEl.textContent = formatPublished(post.timestamp);

        var platformIcon = root.querySelector('[data-smm-insights-platform-icon]');
        if (platformIcon) {
            platformIcon.innerHTML = (config.platformIcons && config.platformIcons[account.platform]) || '';
        }

        setCompareText('[data-smm-insights-views-compare]', comparison.views);
        setCompareText('[data-smm-insights-reach-compare]', comparison.reach);
        setCompareText('[data-smm-insights-interactions-compare]', comparison.interactions);

        var viewsValue = root.querySelector('[data-smm-insights-views-value]');
        if (viewsValue) viewsValue.textContent = formatNumber(overview.views);

        var reachValue = root.querySelector('[data-smm-insights-reach-value]');
        if (reachValue) reachValue.textContent = formatNumber(overview.reach);

        var interactionsValue = root.querySelector('[data-smm-insights-interactions-value]');
        if (interactionsValue) interactionsValue.textContent = formatNumber(overview.interactions);

        var permalink = root.querySelector('[data-smm-insights-permalink]');
        var permalinkLabel = root.querySelector('[data-smm-insights-permalink-label]');
        if (permalink) {
            if (post.permalink) {
                permalink.href = post.permalink;
                permalink.style.display = '';
            } else {
                permalink.removeAttribute('href');
                permalink.style.display = 'none';
            }
        }
        if (permalinkLabel) {
            permalinkLabel.textContent = 'View post on ' + platformLabel(activePlatform(account));
        }

        var previewTitle = root.querySelector('[data-smm-insights-preview-title]');
        if (previewTitle) {
            previewTitle.textContent = platformLabel(activePlatform(account)) + ' preview';
        }

        var commentAvatar = root.querySelector('[data-smm-insights-comment-avatar]');
        if (commentAvatar) {
            if (account.avatar_src) {
                commentAvatar.src = account.avatar_src;
                commentAvatar.style.display = '';
            } else {
                commentAvatar.style.display = 'none';
            }
        }

        renderOverview(overview);
        renderWatchCard(overview);
        renderInteractionsBreakdown(overview);
        renderViewsAudience(data.audience || {});
        renderDemographics((data.audience && data.audience.demographics) || {});
        renderFeed(post, account);
        showShell();
        renderChart(series);
    }

    function loadInsights() {
        if (!state.accountId || !state.platform || !state.platformPostId) {
            showError('Missing account or post details.');
            return;
        }

        if (bootEl) {
            bootEl.classList.remove('is-hidden');
            bootEl.hidden = false;
            bootEl.textContent = 'Loading insights…';
        }

        var url = config.endpoints.insights +
            '?account_id=' + encodeURIComponent(state.accountId) +
            '&platform=' + encodeURIComponent(state.platform) +
            '&platform_post_id=' + encodeURIComponent(state.platformPostId);

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    showError(data.error || 'Could not load insights for this post.');
                    return;
                }
                render(data);
            })
            .catch(function () {
                showError('Could not load insights. Try again in a moment.');
            });
    }

    bindTabs();
    bindMenu();
    bindComment();
    // Fetch insights immediately — do not wait on Chart.js (that alone added up to ~2s).
    loadInsights();
})();
