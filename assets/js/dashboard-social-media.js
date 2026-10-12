(function () {
    var config = window.LeadBirdSocialMedia || {};
    var root = document.querySelector('[data-smm-root]');
    if (!root || !config.endpoints) return;

    var queuePosts = Array.isArray(config.initialPosts) ? config.initialPosts.slice() : [];
    var livePosts = [];
    var activeFilter = 'all';
    var activeSource = 'all';
    var activeAccount = '';
    var calendarFilters = { status: [], platform: [], account: [] };
    var liveLoadedFor = '';
    var liveFeedWarnings = [];
    var liveSyncedPlatforms = [];

    var form = root.querySelector('[data-smm-form]');
    var captionEl = root.querySelector('[data-smm-caption]');
    var hashtagsEl = root.querySelector('[data-smm-hashtags]');
    var aiPromptEl = root.querySelector('[data-smm-ai-prompt]');
    var mediaInput = root.querySelector('[data-smm-media-input]');
    var mediaGrid = root.querySelector('[data-smm-media-grid]');
    var selectedFiles = [];
    var selectedFileMetadata = [];
    var selectedOriginalFiles = [];
    var selectedOriginalFileMetadata = [];
    var previewObjectUrls = [];
    var instagramUserTags = [];
    var pendingIgTagPosition = null;
    var instagramPostCarouselIndex = 0;
    var instagramCarouselState = { signature: '', files: [], urls: [] };
    var previewEmpty = root.querySelector('[data-smm-preview-empty]');
    var previewStack = root.querySelector('[data-smm-preview-stack]');
    var platformExtras = root.querySelector('[data-smm-platform-extras]');
    var firstCommentWrap = root.querySelector('[data-smm-first-comment-wrap]');
    var schedulePicker = root.querySelector('[data-smm-schedule-picker]');
    var formNote = root.querySelector('[data-smm-form-note]');
    var publishOverlay = root.querySelector('[data-smm-publish-overlay]');
    var publishOverlayTitle = root.querySelector('[data-smm-publish-title]');
    var publishOverlayCopy = root.querySelector('[data-smm-publish-copy]');
    var publishOverlayTrack = root.querySelector('[data-smm-publish-track]');
    var publishOverlayFill = root.querySelector('[data-smm-publish-fill]');
    var publishOverlayPct = root.querySelector('[data-smm-publish-pct]');
    var submitBtn = root.querySelector('[data-smm-submit]');
    var postsLists = root.querySelectorAll('[data-smm-posts-list]');
    var calendarEl = root.querySelector('[data-smm-calendar]');
    var modal = root.querySelector('[data-smm-modal]');
    var insightsModal = root.querySelector('[data-smm-insights-modal]');
    var insightsBody = root.querySelector('[data-smm-insights-body]');
    var insightsTitle = root.querySelector('[data-smm-insights-title]');
    var editForm = root.querySelector('[data-smm-edit-form]');
    var statusWrap = root.querySelector('[data-smm-panel="content"] [data-smm-status-wrap]');

    var activeView = 'calendar';
    var calendarWeekStart = startOfWeek(new Date());
    var calendarViewMode = 'month';
    var calendarMonth = startOfMonth(new Date());
    var selectedDay = null;
    var composeModal = root.querySelector('[data-smm-compose-modal]');
    var dayPanel = root.querySelector('[data-smm-day-panel]');
    var calendarLayout = root.querySelector('[data-smm-cal-layout]');
    var volumeEl = root.querySelector('[data-smm-volume]');
    var scheduledAtEl = root.querySelector('[data-smm-scheduled-at]');
    var composeDialog = root.querySelector('[data-smm-compose-dialog]');
    var composeBody = composeModal ? composeModal.querySelector('.smm-compose-modal-body') : null;
    var wisdomPanel = root.querySelector('[data-smm-wisdom-panel]');
    var accountPicker = root.querySelector('[data-smm-account-picker]');
    var accountTrigger = root.querySelector('[data-smm-account-trigger]');
    var accountPlaceholder = root.querySelector('[data-smm-account-placeholder]');
    var accountPills = root.querySelector('[data-smm-account-pills]');
    var clearAccountsBtn = root.querySelector('[data-smm-clear-accounts]');
    var platformTabs = root.querySelector('[data-smm-platform-tabs]');
    var editorShell = root.querySelector('.smm-compose-editor-shell');
    var postTypeTrigger = root.querySelector('[data-smm-post-type-trigger]');
    var postTypeMenu = root.querySelector('[data-smm-post-type-menu]');
    var postTypeLabel = root.querySelector('[data-smm-post-type-label]');
    var accountMenu = root.querySelector('[data-smm-account-menu]');
    var platformIcons = config.platformIcons || {};
    var composePlatformOrder = ['tiktok', 'instagram', 'facebook'];
    var activeComposePlatform = '';
    var activeComposePostType = 'post';
    var composePlatformContent = {
        facebook: { caption: '', hashtags: '', postType: 'post', files: [], fileMetadata: [], originalFiles: [], originalFileMetadata: [] },
        instagram: { caption: '', hashtags: '', postType: 'post', files: [], fileMetadata: [], originalFiles: [], originalFileMetadata: [] },
        tiktok: { caption: '', hashtags: '', postType: 'post', files: [], fileMetadata: [], originalFiles: [], originalFileMetadata: [] },
    };
    var postTypeLabels = { post: 'Post', story: 'Story', reel: 'Reel' };
    var composePostTypesByPlatform = {
        instagram: ['post', 'story', 'reel'],
        facebook: ['post', 'story', 'reel'],
        tiktok: ['post'],
    };
    var composePlatformLabels = { instagram: 'Instagram', facebook: 'Facebook', tiktok: 'TikTok' };
    var TIKTOK_CAPTION_LIMIT = 2200;
    var TIKTOK_MEDIA_ACCEPT = 'video/mp4,video/quicktime';
    var DEFAULT_MEDIA_ACCEPT = 'image/jpeg,image/png,image/webp,video/mp4,video/quicktime';
    var TIKTOK_PRIVACY_LEVELS = ['PUBLIC_TO_EVERYONE', 'MUTUAL_FOLLOW_FRIENDS', 'FOLLOWER_OF_CREATOR', 'SELF_ONLY'];
    /*
     * TikTok's Direct Post guidelines require the creator to pick the audience and
     * to switch interactions on themselves, so nothing here may start selected.
     */
    var tiktokSettings = {
        privacyLevel: '',
        allowComment: false,
        allowDuet: false,
        allowStitch: false,
    };
    var tiktokCreatorInfo = null;
    var tiktokCreatorInfoAccountId = '';
    var tiktokCreatorInfoPending = false;
    var STORY_CAPTION_CHAR_LIMIT = 220;
    var storyPreviewImageCache = {};
    var storyPreviewBurnTimer = null;
    var COMPOSE_EMOJIS = [
        '😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '😉', '😍', '🥰', '😘', '😋', '😎',
        '🤩', '🥳', '😢', '😭', '😤', '😡', '🤯', '😱', '🙄', '😴', '🤔', '🤗', '🤝', '👍', '👎', '👏',
        '🙌', '💪', '🙏', '✨', '🔥', '💯', '⭐', '🎉', '🎊', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤',
        '🤍', '💕', '💖', '💝', '💡', '🚀', '📣', '📸', '🛍️', '💰', '🎯', '✅', '❌', '⚡', '🌟', '🌈',
        '☀️', '🌙', '🍕', '☕', '🎁', '🏆', '📍', '🛒', '💬', '📱', '🎵', '🎬', '📈', '🤳', '👀', '✍️',
    ];
    var scheduleModal = root.querySelector('[data-smm-schedule-modal]');
    var scheduleLaterBtn = root.querySelector('[data-smm-schedule-later]');
    var scheduleLaterLabel = root.querySelector('[data-smm-schedule-later-label]');
    var scheduleDateInput = root.querySelector('[data-smm-schedule-date]');
    var scheduleDateTrigger = root.querySelector('[data-smm-schedule-date-trigger]');
    var scheduleDateLabel = root.querySelector('[data-smm-schedule-date-label]');
    var scheduleTimezoneEl = root.querySelector('[data-smm-schedule-timezone]');
    var scheduleHourSelect = root.querySelector('[data-smm-schedule-hour]');
    var scheduleMinuteSelect = root.querySelector('[data-smm-schedule-minute]');
    var schedulePeriodSelect = root.querySelector('[data-smm-schedule-period]');
    var scheduleRecommendedToggle = root.querySelector('[data-smm-schedule-recommended-toggle]');
    var scheduleRecommendedPanel = root.querySelector('[data-smm-schedule-recommended]');
    var scheduleDoneBtn = root.querySelector('[data-smm-schedule-done]');
    var scheduleFieldsInitialized = false;
    var wisdomAskInput = root.querySelector('[data-smm-wisdom-ask]');
    var submitMenu = root.querySelector('[data-smm-submit-menu]');
    var submitSplit = root.querySelector('[data-smm-submit-split]');
    var submitMenuBypass = false;

    var CALENDAR_HOLIDAYS = [
        // Nigeria — public & widely observed
        { type: 'annual', month: 0, day: 1, name: "New Year's Day" },
        { type: 'fixed', date: '2026-04-03', name: 'Good Friday' },
        { type: 'fixed', date: '2026-04-06', name: 'Easter Monday' },
        { type: 'fixed', date: '2027-03-26', name: 'Good Friday' },
        { type: 'fixed', date: '2027-03-29', name: 'Easter Monday' },
        { type: 'annual', month: 4, day: 1, name: "Workers' Day" },
        { type: 'annual', month: 4, day: 27, name: "Children's Day" },
        { type: 'annual', month: 5, day: 12, name: 'Democracy Day' },
        { type: 'annual', month: 9, day: 1, name: 'Independence Day' },
        { type: 'annual', month: 11, day: 25, name: 'Christmas Day' },
        { type: 'annual', month: 11, day: 26, name: 'Boxing Day' },
        // Islamic (approximate — update yearly)
        { type: 'fixed', date: '2026-03-20', name: 'Eid al-Fitr' },
        { type: 'fixed', date: '2026-03-21', name: 'Eid al-Fitr holiday' },
        { type: 'fixed', date: '2026-05-27', name: 'Eid al-Adha' },
        { type: 'fixed', date: '2026-05-28', name: 'Eid al-Adha holiday' },
        { type: 'fixed', date: '2026-09-05', name: 'Mawlid' },
        { type: 'fixed', date: '2027-03-10', name: 'Eid al-Fitr' },
        { type: 'fixed', date: '2027-05-17', name: 'Eid al-Adha' },
        // Retail & social calendar
        { type: 'annual', month: 1, day: 14, name: "Valentine's Day" },
        { type: 'annual', month: 2, day: 8, name: "International Women's Day" },
        { type: 'fixed', date: '2026-05-10', name: "Mother's Day" },
        { type: 'fixed', date: '2026-06-21', name: "Father's Day" },
        { type: 'annual', month: 9, day: 31, name: 'Halloween' },
        { type: 'fixed', date: '2026-11-27', name: 'Black Friday' },
        { type: 'annual', month: 11, day: 31, name: "New Year's Eve" },
    ];

    function getActivePostsList() {
        if (activeView === 'drafts') {
            return root.querySelector('[data-smm-posts-list][data-smm-posts-context="drafts"]');
        }
        if (activeView === 'content') {
            return root.querySelector('[data-smm-posts-list][data-smm-posts-context="content"]');
        }
        return postsLists[0] || null;
    }

    function startOfWeek(date) {
        var d = new Date(date.getTime());
        d.setDate(d.getDate() - d.getDay());
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function addDays(date, days) {
        var d = new Date(date.getTime());
        d.setDate(d.getDate() + days);
        return d;
    }

    function formatIsoDate(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function formatShortDate(date) {
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function formatRangeLabel(weekStart) {
        var weekEnd = addDays(weekStart, 6);
        var sameMonth = weekStart.getMonth() === weekEnd.getMonth();
        if (sameMonth) {
            return formatShortDate(weekStart) + ' – ' + weekEnd.getDate() + ', ' + weekEnd.getFullYear();
        }
        return formatShortDate(weekStart) + ' – ' + formatShortDate(weekEnd) + ', ' + weekEnd.getFullYear();
    }

    function formatHourLabel(hour) {
        var suffix = hour >= 12 ? 'PM' : 'AM';
        var h = hour % 12;
        if (h === 0) h = 12;
        return h + ':00 ' + suffix;
    }

    function holidaysForDate(date) {
        var month = date.getMonth();
        var day = date.getDate();
        var iso = formatIsoDate(date);
        return CALENDAR_HOLIDAYS.filter(function (holiday) {
            if (holiday.type === 'annual') {
                return holiday.month === month && holiday.day === day;
            }
            if (holiday.type === 'fixed') {
                return holiday.date === iso;
            }
            return false;
        }).map(function (holiday) {
            return holiday.name;
        });
    }

    function isSameDay(a, b) {
        return a.getFullYear() === b.getFullYear()
            && a.getMonth() === b.getMonth()
            && a.getDate() === b.getDate();
    }

    function isFutureOrToday(date) {
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var compare = new Date(date.getTime());
        compare.setHours(0, 0, 0, 0);
        return compare >= today;
    }

    var RECOMMENDED_TIME_HOURS = [17, 21];

    function isWeekendDay(date) {
        var weekday = date.getDay();
        return weekday === 0 || weekday === 5 || weekday === 6;
    }

    function shouldShowRecommendedTimes(day, dayPosts) {
        if (!isWeekendDay(day) || !isFutureOrToday(day)) return false;
        return (dayPosts || []).length === 0;
    }

    function renderRecommendedTimeSuggestHtml(day, options) {
        options = options || {};
        var dayPosts = options.posts || postsOnDay(day);
        if (!shouldShowRecommendedTimes(day, dayPosts)) return '';

        var iso = formatIsoDate(day);
        var extraClass = options.extraClass ? ' ' + options.extraClass : '';
        var html = '';
        RECOMMENDED_TIME_HOURS.forEach(function (hour) {
            if (options.dayPanel) {
                html += '<button type="button" class="smm-cal-suggest smm-day-slot-suggest" data-smm-day-slot-hour="' + hour + '">' +
                    '<span class="smm-cal-suggest-icon" aria-hidden="true">' +
                    '<svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M12 2 3 7v6c0 5 4 9 9 9s9-4 9-9V7l-9-5Z" fill="currentColor"/></svg>' +
                    '</span><span>Recommended time ' + escapeHtml(formatHourLabel(hour)) + '</span></button>';
                return;
            }

            html += '<button type="button" class="smm-cal-suggest' + extraClass + '" data-smm-open-compose data-smm-day="' + iso + '" data-smm-hour="' + hour + '">' +
                '<span class="smm-cal-suggest-icon" aria-hidden="true"><svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M12 2 3 7v6c0 5 4 9 9 9s9-4 9-9V7l-9-5Z" fill="currentColor"/></svg></span>' +
                '<span>Recommended time ' + escapeHtml(formatHourLabel(hour)) + '</span></button>';
        });
        return html;
    }

    function startOfMonth(date) {
        return new Date(date.getFullYear(), date.getMonth(), 1);
    }

    function formatMonthLabel(date) {
        return date.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
    }

    function formatMonthLong(date) {
        return date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    }

    function formatDayPanelDate(date) {
        return date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
    }

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function toDateInputValue(date) {
        return date.getFullYear() + '-' + pad2(date.getMonth() + 1) + '-' + pad2(date.getDate());
    }

    function formatTimezoneLabel() {
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local time';
        var offsetMinutes = -new Date().getTimezoneOffset();
        var sign = offsetMinutes >= 0 ? '+' : '-';
        var abs = Math.abs(offsetMinutes);
        var hours = Math.floor(abs / 60);
        var minutes = abs % 60;
        var gmt = 'GMT' + sign + hours + (minutes ? ':' + pad2(minutes) : '');
        return '(' + gmt + ') ' + tz.replace(/_/g, ' ');
    }

    function formatScheduleDateLabel(date) {
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var compare = new Date(date.getTime());
        compare.setHours(0, 0, 0, 0);
        var tomorrow = new Date(today.getTime());
        tomorrow.setDate(tomorrow.getDate() + 1);
        var formatted = date.toLocaleDateString(undefined, {
            month: 'long',
            day: '2-digit',
            year: 'numeric',
        });
        if (compare.getTime() === today.getTime()) {
            return formatted + ' (today)';
        }
        if (compare.getTime() === tomorrow.getTime()) {
            return formatted + ' (tomorrow)';
        }
        return formatted;
    }

    function formatScheduleChipLabel(date) {
        return date.toLocaleString(undefined, {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    function parseScheduleValue(value) {
        if (!value) return null;
        var parsed = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function get12HourParts(date) {
        var hour24 = date.getHours();
        var period = hour24 >= 12 ? 'PM' : 'AM';
        var hour12 = hour24 % 12;
        if (hour12 === 0) hour12 = 12;
        return {
            hour: String(hour12),
            minute: pad2(date.getMinutes()),
            period: period,
        };
    }

    function buildScheduleDateFromFields() {
        if (!scheduleDateInput || !scheduleHourSelect || !scheduleMinuteSelect || !schedulePeriodSelect) {
            return null;
        }
        var dateValue = scheduleDateInput.value;
        if (!dateValue) return null;

        var hour = parseInt(scheduleHourSelect.value, 10);
        var minute = parseInt(scheduleMinuteSelect.value, 10);
        var period = schedulePeriodSelect.value;
        if (Number.isNaN(hour) || Number.isNaN(minute)) return null;

        if (period === 'PM' && hour < 12) hour += 12;
        if (period === 'AM' && hour === 12) hour = 0;

        var parts = dateValue.split('-');
        if (parts.length !== 3) return null;

        return new Date(
            parseInt(parts[0], 10),
            parseInt(parts[1], 10) - 1,
            parseInt(parts[2], 10),
            hour,
            minute,
            0,
            0
        );
    }

    function syncScheduleFieldsFromValue(value) {
        var parsed = parseScheduleValue(value) || getDefaultScheduleDate();
        if (!scheduleDateInput || !scheduleHourSelect || !scheduleMinuteSelect || !schedulePeriodSelect) {
            return parsed;
        }

        scheduleDateInput.value = toDateInputValue(parsed);
        if (scheduleDateLabel) {
            scheduleDateLabel.textContent = formatScheduleDateLabel(parsed);
        }

        var parts = get12HourParts(parsed);
        scheduleHourSelect.value = parts.hour;
        scheduleMinuteSelect.value = parts.minute;
        schedulePeriodSelect.value = parts.period;

        if (window.LeadBirdDropdown) {
            [scheduleHourSelect, scheduleMinuteSelect, schedulePeriodSelect].forEach(function (select) {
                if (!select) return;
                var wrapper = select.closest('.lb-dropdown');
                if (wrapper) {
                    window.LeadBirdDropdown.refresh(wrapper, select);
                }
            });
        }

        return parsed;
    }

    function syncScheduleValueFromFields() {
        var built = buildScheduleDateFromFields();
        if (!built || !scheduledAtEl) return null;
        scheduledAtEl.value = toDatetimeLocal(built);
        return built;
    }

    function getDefaultScheduleDate() {
        var next = new Date();
        next.setMinutes(next.getMinutes() + 60 - (next.getMinutes() % 5));
        next.setSeconds(0, 0);
        if (next.getTime() <= Date.now()) {
            next.setMinutes(next.getMinutes() + 5);
        }
        return next;
    }

    function renderRecommendedTimes() {
        if (!scheduleRecommendedPanel) return;

        var suggestions = [];
        var base = getDefaultScheduleDate();
        suggestions.push(new Date(base.getTime()));

        var tomorrowMorning = new Date();
        tomorrowMorning.setDate(tomorrowMorning.getDate() + 1);
        tomorrowMorning.setHours(9, 0, 0, 0);
        suggestions.push(tomorrowMorning);

        var tomorrowNoon = new Date(tomorrowMorning.getTime());
        tomorrowNoon.setHours(12, 0, 0, 0);
        suggestions.push(tomorrowNoon);

        var tomorrowEvening = new Date(tomorrowMorning.getTime());
        tomorrowEvening.setHours(17, 0, 0, 0);
        suggestions.push(tomorrowEvening);

        scheduleRecommendedPanel.innerHTML = suggestions.map(function (date, index) {
            return '<button type="button" class="smm-schedule-recommended-option font-sans" data-smm-schedule-recommended-option="' + index + '">' +
                escapeHtml(formatScheduleChipLabel(date)) +
                '</button>';
        }).join('');

        scheduleRecommendedPanel.querySelectorAll('[data-smm-schedule-recommended-option]').forEach(function (btn, index) {
            btn.addEventListener('click', function () {
                var picked = suggestions[index];
                if (!picked) return;
                syncScheduleFieldsFromValue(toDatetimeLocal(picked));
                if (scheduleRecommendedPanel) {
                    scheduleRecommendedPanel.classList.add('is-hidden');
                    scheduleRecommendedPanel.hidden = true;
                }
                if (scheduleRecommendedToggle) {
                    scheduleRecommendedToggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    function initScheduleFields() {
        if (!scheduleHourSelect || !scheduleMinuteSelect || scheduleFieldsInitialized) return;

        var hourHtml = '';
        for (var h = 1; h <= 12; h++) {
            hourHtml += '<option value="' + h + '">' + h + '</option>';
        }
        scheduleHourSelect.innerHTML = hourHtml;

        var minuteHtml = '';
        for (var m = 0; m < 60; m++) {
            minuteHtml += '<option value="' + pad2(m) + '">' + pad2(m) + '</option>';
        }
        scheduleMinuteSelect.innerHTML = minuteHtml;

        if (scheduleTimezoneEl) {
            scheduleTimezoneEl.textContent = formatTimezoneLabel();
        }

        if (scheduleDateInput) {
            var today = toDateInputValue(new Date());
            scheduleDateInput.min = today;
        }

        renderRecommendedTimes();
        scheduleFieldsInitialized = true;

        if (window.LeadBirdDropdown && scheduleModal) {
            window.LeadBirdDropdown.init(scheduleModal);
        }
    }

    function setScheduleLaterBtnOpen(isOpen) {
        if (!scheduleLaterBtn) return;
        scheduleLaterBtn.classList.toggle('is-open', isOpen);
        scheduleLaterBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function openScheduleModal() {
        if (!scheduleModal) return;
        closeSubmitMenu();
        initScheduleFields();
        syncScheduleFieldsFromValue(scheduledAtEl ? scheduledAtEl.value : '');
        scheduleModal.classList.remove('is-hidden');
        scheduleModal.hidden = false;
        scheduleModal.setAttribute('aria-hidden', 'false');
        setScheduleLaterBtnOpen(true);
    }

    function closeScheduleModal() {
        if (!scheduleModal) return;
        scheduleModal.classList.add('is-hidden');
        scheduleModal.hidden = true;
        scheduleModal.setAttribute('aria-hidden', 'true');
        setScheduleLaterBtnOpen(false);
        if (scheduleRecommendedPanel) {
            scheduleRecommendedPanel.classList.add('is-hidden');
            scheduleRecommendedPanel.hidden = true;
        }
        if (scheduleRecommendedToggle) {
            scheduleRecommendedToggle.setAttribute('aria-expanded', 'false');
        }
    }

    function clearScheduleSelection() {
        if (scheduledAtEl) scheduledAtEl.value = '';
        closeScheduleModal();
        setPublishMode('now');
        updateScheduleUi();
    }

    function toDatetimeLocal(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        var h = String(date.getHours()).padStart(2, '0');
        var min = String(date.getMinutes()).padStart(2, '0');
        return y + '-' + m + '-' + d + 'T' + h + ':' + min;
    }

    function setPublishMode(mode) {
        root.querySelectorAll('[data-smm-mode]').forEach(function (el) {
            el.checked = el.value === mode;
        });
        updateScheduleUi();
    }

    function updateScheduleUi() {
        var submitLabel = root.querySelector('[data-smm-submit-label]');
        var val = scheduledAtEl ? scheduledAtEl.value : '';
        var parsed = parseScheduleValue(val);

        if (scheduleLaterLabel) {
            if (parsed) {
                scheduleLaterLabel.textContent = formatScheduleChipLabel(parsed);
                if (scheduleLaterBtn) scheduleLaterBtn.classList.add('has-schedule');
            } else {
                scheduleLaterLabel.textContent = 'Schedule for later';
                if (scheduleLaterBtn) scheduleLaterBtn.classList.remove('has-schedule');
            }
        }

        if (submitLabel) {
            var mode = currentPublishMode();
            if (mode === 'draft') submitLabel.textContent = 'Save draft';
            else if (mode === 'now') submitLabel.textContent = 'Post now';
            else submitLabel.textContent = 'Schedule';
        }

        if (submitBtn) {
            // A picked schedule publishes straight from the button, so drop the menu chevron.
            submitBtn.classList.toggle('is-direct-submit', currentPublishMode() === 'schedule');
        }
        syncSubmitMenuAffordance();
    }

    function syncSubmitMenuAffordance() {
        if (!submitBtn) return;

        if (submitMenu && submitActsAsMenu() && currentPublishMode() !== 'schedule') {
            submitBtn.setAttribute('aria-haspopup', 'menu');
            if (!submitBtn.hasAttribute('aria-expanded')) {
                submitBtn.setAttribute('aria-expanded', 'false');
            }
            return;
        }

        submitBtn.removeAttribute('aria-haspopup');
        submitBtn.removeAttribute('aria-expanded');
    }

    function applyScheduleSelection() {
        var built = syncScheduleValueFromFields();
        if (!built) {
            setNote('Choose a valid date and time.', 'error');
            return false;
        }
        if (built.getTime() <= Date.now()) {
            setNote('Choose a date and time in the future.', 'error');
            return false;
        }
        setPublishMode('schedule');
        updateScheduleUi();
        closeScheduleModal();
        setNote('');
        return true;
    }

    function syncScheduleDatetimeInput() {
        applyScheduleSelection();
    }

    function getSelectedComposeAccounts() {
        return Array.prototype.slice.call(root.querySelectorAll('[data-smm-compose-account]:checked'));
    }

    function getSelectedComposePlatforms() {
        var platforms = [];
        getSelectedComposeAccounts().forEach(function (input) {
            var platform = input.getAttribute('data-platform') || '';
            if (platform && platforms.indexOf(platform) === -1) {
                platforms.push(platform);
            }
        });
        return composePlatformOrder.filter(function (platform) {
            return platforms.indexOf(platform) !== -1;
        });
    }

    function getPrimaryAccountForPlatform(platform) {
        var selected = getSelectedComposeAccounts().filter(function (input) {
            return (input.getAttribute('data-platform') || '') === platform;
        });
        if (selected.length === 0) {
            return null;
        }
        var input = selected[0];
        var displayName = input.getAttribute('data-display-name') || 'Your account';
        return {
            id: input.value,
            displayName: displayName,
            avatarSrc: input.getAttribute('data-avatar-src') || '',
            platform: platform,
            initial: displayName.trim().charAt(0).toUpperCase() || 'Y',
        };
    }

    function renderPreviewAvatar(platform, account, format) {
        format = format || 'post';
        var avatarKey = format === 'post' ? platform : platform + '-' + format;
        var avatarWrap = root.querySelector('[data-smm-preview-avatar="' + avatarKey + '"]');
        if (!avatarWrap && format !== 'post') {
            avatarWrap = root.querySelector('[data-smm-preview-avatar="' + platform + '"]');
        }
        if (!avatarWrap) return;

        var initial = account ? account.initial : 'Y';
        var avatarSrc = account ? account.avatarSrc : '';
        var initialClass = 'smm-compose-preview-ig-initial';
        if (platform === 'facebook') {
            initialClass = 'smm-compose-preview-fb-initial';
        } else if (platform === 'tiktok') {
            initialClass = 'smm-compose-preview-tiktok-initial';
        }

        if (avatarSrc) {
            avatarWrap.innerHTML = '<img src="' + escapeHtml(avatarSrc) + '" alt="" data-smm-preview-avatar-img><span class="' + initialClass + ' font-heading is-hidden" aria-hidden="true">' + escapeHtml(initial) + '</span>';
            var img = avatarWrap.querySelector('[data-smm-preview-avatar-img]');
            if (img instanceof HTMLImageElement) {
                img.addEventListener('error', function () {
                    img.classList.add('is-hidden');
                    var fallback = avatarWrap.querySelector('.' + initialClass);
                    if (fallback instanceof HTMLElement) {
                        fallback.classList.remove('is-hidden');
                    }
                });
            }
            return;
        }

        avatarWrap.innerHTML = '<span class="' + initialClass + ' font-heading">' + escapeHtml(initial) + '</span>';
    }

    function ensurePlatformContent(platform) {
        if (!platform) return;
        if (!composePlatformContent[platform]) {
            composePlatformContent[platform] = { caption: '', hashtags: '', postType: 'post', files: [], fileMetadata: [], originalFiles: [], originalFileMetadata: [] };
        }
        if (!Array.isArray(composePlatformContent[platform].files)) {
            composePlatformContent[platform].files = [];
        }
        if (!Array.isArray(composePlatformContent[platform].originalFiles)) {
            composePlatformContent[platform].originalFiles = [];
        }
        if (!Array.isArray(composePlatformContent[platform].originalFileMetadata)) {
            composePlatformContent[platform].originalFileMetadata = [];
        }
    }

    function getFilesForPlatform(platform) {
        ensurePlatformContent(platform);
        if (platform === activeComposePlatform) {
            return selectedFiles.slice();
        }
        return (composePlatformContent[platform].files || []).slice();
    }

    function getFileMetadataForPlatform(platform) {
        ensurePlatformContent(platform);
        if (platform === activeComposePlatform) {
            return selectedFileMetadata.slice();
        }
        return (composePlatformContent[platform].fileMetadata || []).slice();
    }

    function getOriginalFilesForPlatform(platform) {
        ensurePlatformContent(platform);
        if (platform === activeComposePlatform) {
            return selectedOriginalFiles.slice();
        }
        return (composePlatformContent[platform].originalFiles || []).slice();
    }

    function getOriginalFileMetadataForPlatform(platform) {
        ensurePlatformContent(platform);
        if (platform === activeComposePlatform) {
            return selectedOriginalFileMetadata.slice();
        }
        return (composePlatformContent[platform].originalFileMetadata || []).slice();
    }

    function persistActivePlatformMedia() {
        if (!activeComposePlatform) return;
        ensurePlatformContent(activeComposePlatform);
        composePlatformContent[activeComposePlatform].files = selectedFiles.slice();
        composePlatformContent[activeComposePlatform].fileMetadata = selectedFileMetadata.slice();
        composePlatformContent[activeComposePlatform].originalFiles = selectedOriginalFiles.slice();
        composePlatformContent[activeComposePlatform].originalFileMetadata = selectedOriginalFileMetadata.slice();
    }

    function loadActivePlatformMedia() {
        if (!activeComposePlatform) {
            selectedFiles = [];
            selectedFileMetadata = [];
            selectedOriginalFiles = [];
            selectedOriginalFileMetadata = [];
            syncMediaInput();
            renderMediaGrid();
            return;
        }
        ensurePlatformContent(activeComposePlatform);
        selectedFiles = (composePlatformContent[activeComposePlatform].files || []).slice();
        selectedFileMetadata = (composePlatformContent[activeComposePlatform].fileMetadata || []).slice();
        selectedOriginalFiles = (composePlatformContent[activeComposePlatform].originalFiles || []).slice();
        selectedOriginalFileMetadata = (composePlatformContent[activeComposePlatform].originalFileMetadata || []).slice();
        syncOriginalFileArrays();
        syncMediaInput();
        renderMediaGrid();
    }

    function syncOriginalFileArrays() {
        while (selectedOriginalFiles.length < selectedFiles.length) {
            var index = selectedOriginalFiles.length;
            var file = selectedFiles[index];
            var isVideo = file && (file.type || '').indexOf('video/') === 0;
            selectedOriginalFiles.push(isVideo ? file : null);
            selectedOriginalFileMetadata.push(null);
        }
        if (selectedOriginalFiles.length > selectedFiles.length) {
            selectedOriginalFiles = selectedOriginalFiles.slice(0, selectedFiles.length);
            selectedOriginalFileMetadata = selectedOriginalFileMetadata.slice(0, selectedFiles.length);
        }
    }

    function loadVideoFileMetadata(file) {
        return new Promise(function (resolve, reject) {
            if (!file || (file.type || '').indexOf('video/') !== 0) {
                resolve(null);
                return;
            }
            var video = document.createElement('video');
            video.preload = 'metadata';
            var url = URL.createObjectURL(file);
            video.addEventListener('loadedmetadata', function () {
                URL.revokeObjectURL(url);
                resolve({
                    duration: video.duration,
                    width: video.videoWidth,
                    height: video.videoHeight,
                    aspectRatio: video.videoWidth / video.videoHeight,
                    aspectRatioLabel: formatAspectRatioLabel(video.videoWidth, video.videoHeight),
                    loaded: true,
                });
            });
            video.addEventListener('error', function () {
                URL.revokeObjectURL(url);
                reject(new Error('Could not read video metadata.'));
            });
            video.src = url;
        });
    }

    function ensureOriginalVideoMetadataForIndex(index) {
        var file = selectedOriginalFiles[index];
        if (!file) {
            return Promise.resolve(null);
        }
        if (selectedOriginalFileMetadata[index] && selectedOriginalFileMetadata[index].loaded) {
            return Promise.resolve(selectedOriginalFileMetadata[index]);
        }
        return loadVideoFileMetadata(file).then(function (meta) {
            selectedOriginalFileMetadata[index] = meta;
            persistActivePlatformMedia();
            return meta;
        });
    }

    function platformFilesIncludeVideo(platform) {
        var files = getFilesForPlatform(platform);
        for (var i = 0; i < files.length; i++) {
            if ((files[i].type || '').indexOf('video/') === 0) {
                return true;
            }
        }
        return false;
    }

    function getFirstVideoIndexForPlatform(platform) {
        var files = getFilesForPlatform(platform);
        for (var i = 0; i < files.length; i++) {
            if ((files[i].type || '').indexOf('video/') === 0) {
                return i;
            }
        }
        return -1;
    }

    function isInstagramPostCarousel(platform) {
        if (platform !== 'instagram') return false;
        if (getComposePostTypeForPlatform(platform) !== 'post') return false;
        return getFilesForPlatform(platform).length > 1;
    }

    function allowsInstagramPostCarouselMedia() {
        return activeComposePlatform === 'instagram'
            && getComposePostTypeForPlatform('instagram') === 'post';
    }

    function platformFilesIncludeImage(platform) {
        var files = getFilesForPlatform(platform);
        for (var i = 0; i < files.length; i++) {
            if ((files[i].type || '').indexOf('video/') !== 0) {
                return true;
            }
        }
        return false;
    }

    function getVideoMetadataForPlatform(platform) {
        if (!platformFilesIncludeVideo(platform)) return null;
        var videoIndex = getFirstVideoIndexForPlatform(platform);
        if (videoIndex < 0) return null;
        var meta = getFileMetadataForPlatform(platform)[videoIndex];
        if (!meta || !meta.loaded) return null;
        return meta;
    }

    function resetComposePlatformContent() {
        composePlatformContent = {
            facebook: { caption: '', hashtags: '', postType: 'post', files: [], fileMetadata: [], originalFiles: [], originalFileMetadata: [] },
            instagram: { caption: '', hashtags: '', postType: 'post', files: [], fileMetadata: [], originalFiles: [], originalFileMetadata: [] },
            tiktok: { caption: '', hashtags: '', postType: 'post', files: [], fileMetadata: [], originalFiles: [], originalFileMetadata: [] },
        };
        resetTikTokSettings();
    }

    function persistActivePlatformContent() {
        if (!activeComposePlatform) return;
        ensurePlatformContent(activeComposePlatform);
        if (captionEl) {
            composePlatformContent[activeComposePlatform].caption = captionEl.value;
        }
        if (hashtagsEl) {
            composePlatformContent[activeComposePlatform].hashtags = hashtagsEl.value;
        }
        composePlatformContent[activeComposePlatform].postType = activeComposePostType;
    }

    function loadActivePlatformContent() {
        if (!activeComposePlatform) return;
        ensurePlatformContent(activeComposePlatform);
        var content = composePlatformContent[activeComposePlatform];
        if (captionEl) captionEl.value = content.caption || '';
        if (hashtagsEl) hashtagsEl.value = content.hashtags || '';
        activeComposePostType = content.postType || 'post';
    }

    function getComposePostTypeForPlatform(platform) {
        ensurePlatformContent(platform);
        return composePlatformContent[platform].postType || 'post';
    }

    function getPlatformFullCaption(platform) {
        ensurePlatformContent(platform);
        var content = composePlatformContent[platform];
        var captionText = (content.caption || '').trim();
        var hashtags = (content.hashtags || '').trim();
        return hashtags ? captionText + (captionText ? '\n\n' : '') + hashtags : captionText;
    }

    function buildPlatformContentPayload() {
        persistActivePlatformContent();
        var payload = {};
        getSelectedComposePlatforms().forEach(function (platform) {
            ensurePlatformContent(platform);
            var content = composePlatformContent[platform];
            payload[platform] = {
                caption: content.caption || '',
                hashtags: content.hashtags || '',
                post_type: content.postType || 'post',
            };
            if (platform === 'tiktok') {
                payload[platform].options = buildTikTokOptionsPayload();
            }
        });
        return payload;
    }

    function validatePlatformComposeContent() {
        persistActivePlatformContent();
        persistActivePlatformMedia();
        var platforms = getSelectedComposePlatforms();

        for (var i = 0; i < platforms.length; i++) {
            var platform = platforms[i];
            ensurePlatformContent(platform);
            var files = getFilesForPlatform(platform);

            if (platform === 'tiktok') {
                if (files.length === 0) {
                    return 'Add a video for TikTok.';
                }
                // Drafts are still work in progress, so only require this to leave.
                if (tiktokSettings.privacyLevel === '' && currentPublishMode() !== 'draft') {
                    return 'Choose who can watch this video on TikTok.';
                }
                continue;
            }

            if (files.length > 0) continue;
            if ((composePlatformContent[platform].caption || '').trim() !== '') continue;
            return 'Write a caption or add media for ' + composePlatformLabel(platform) + '.';
        }

        return '';
    }

    function getAllowedPostTypesForPlatform(platform) {
        return composePostTypesByPlatform[platform] || composePostTypesByPlatform.facebook;
    }

    function composePlatformLabel(platform) {
        return composePlatformLabels[platform] || platform;
    }

    function isPostTypeAllowedForPlatform(type, platform) {
        return getAllowedPostTypesForPlatform(platform).indexOf(type) !== -1;
    }

    function updatePostTypeUi() {
        var allowedTypes = getAllowedPostTypesForPlatform(activeComposePlatform || 'facebook');
        var currentType = getComposePostTypeForPlatform(activeComposePlatform || 'facebook');

        if (allowedTypes.indexOf(currentType) === -1) {
            setActiveComposePostType('post');
            return;
        }

        activeComposePostType = currentType;

        if (postTypeLabel) {
            postTypeLabel.textContent = postTypeLabels[activeComposePostType] || 'Post';
        }
        if (postTypeMenu) {
            postTypeMenu.querySelectorAll('[data-smm-post-type]').forEach(function (btn) {
                var type = btn.getAttribute('data-smm-post-type') || '';
                var allowed = allowedTypes.indexOf(type) !== -1;
                var isSelected = type === activeComposePostType;
                btn.classList.toggle('is-selected', isSelected);
                btn.classList.toggle('is-hidden', !allowed);
                btn.hidden = !allowed;
                btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
        }
        var postTypeWrap = root.querySelector('[data-smm-post-type-wrap]');
        if (postTypeWrap) {
            // TikTok has a single publish surface, so the picker would only ever show "Post".
            var hideWrap = allowedTypes.length < 2;
            postTypeWrap.classList.toggle('is-hidden', hideWrap);
            postTypeWrap.hidden = hideWrap;
        }
        if (captionEl) {
            captionEl.placeholder = activeComposePostType === 'story'
                ? 'Write the text to show on your story'
                : 'Write your caption, then customize it for each social network';
        }
        updateComposeMediaAccept();
    }

    function updateComposeMediaAccept() {
        if (!mediaInput) return;
        mediaInput.accept = activeComposePlatform === 'tiktok' ? TIKTOK_MEDIA_ACCEPT : DEFAULT_MEDIA_ACCEPT;
    }

    function setActiveComposePostType(type) {
        if (!postTypeLabels[type]) {
            type = 'post';
        }
        if (!isPostTypeAllowedForPlatform(type, activeComposePlatform || 'facebook')) {
            type = 'post';
        }
        activeComposePostType = type;
        if (activeComposePlatform) {
            ensurePlatformContent(activeComposePlatform);
            composePlatformContent[activeComposePlatform].postType = type;
        }
        updatePostTypeUi();
        updateComposePreviewPanels();
        updatePreview();
        updateComposeErrors();
        if (postTypeMenu) {
            postTypeMenu.classList.add('is-hidden');
            postTypeMenu.hidden = true;
        }
        if (postTypeTrigger) {
            postTypeTrigger.setAttribute('aria-expanded', 'false');
        }
    }

    function selectedFilesIncludeVideo() {
        return platformFilesIncludeVideo(activeComposePlatform || 'facebook');
    }

    function getEffectivePreviewFormat(platform) {
        var postType = getComposePostTypeForPlatform(platform);
        if (platform === 'instagram' && postType === 'post' && platformFilesIncludeVideo(platform)) {
            var files = getFilesForPlatform(platform);
            if (files.length === 1 && (files[0].type || '').indexOf('video/') === 0) {
                return 'reel';
            }
            return 'post';
        }
        return postType;
    }

    function gcd(a, b) {
        var x = Math.abs(Math.round(a));
        var y = Math.abs(Math.round(b));
        while (y) {
            var temp = y;
            y = x % y;
            x = temp;
        }
        return x || 1;
    }

    function formatAspectRatioLabel(width, height) {
        if (!width || !height) return '';
        var divisor = gcd(width, height);
        return Math.round(width / divisor) + ':' + Math.round(height / divisor);
    }

    function formatDurationLong(seconds) {
        var value = Number(seconds);
        if (!isFinite(value) || value <= 0) return '0 seconds';
        var mins = Math.floor(value / 60);
        var secs = value % 60;
        var secsRounded = Math.round(secs * 100) / 100;
        var parts = [];
        if (mins > 0) {
            parts.push(mins + ' minute' + (mins === 1 ? '' : 's'));
        }
        if (secsRounded > 0 || mins === 0) {
            var secsLabel = secsRounded % 1 === 0 ? String(Math.round(secsRounded)) : String(secsRounded);
            parts.push(secsLabel + ' second' + (secsRounded === 1 ? '' : 's'));
        }
        if (parts.length === 2) return parts[0] + ' and ' + parts[1];
        return parts[0] || '0 seconds';
    }

    function truncateFileName(name, maxLength) {
        var fileName = String(name || 'video.mp4');
        if (fileName.length <= maxLength) return fileName;
        return fileName.slice(0, maxLength - 3) + '...';
    }

    function getPrimaryVideoMetadata() {
        if (!activeComposePlatform) return null;
        return getVideoMetadataForPlatform(activeComposePlatform);
    }

    function isAspectRatioInRange(ratio, minRatio, maxRatio, tolerance) {
        var tol = typeof tolerance === 'number' ? tolerance : 0.02;
        return ratio >= minRatio - tol && ratio <= maxRatio + tol;
    }

    function isVideoAspectWithinBounds(width, height, minRatio, maxRatio) {
        if (!width || !height || !minRatio || !maxRatio) return true;
        var w = Math.round(Number(width));
        var h = Math.round(Number(height));
        if (w < 1 || h < 1) return true;

        // Integer cross-multiplication avoids float tolerance hiding off-by-one-pixel ratios (e.g. 427:240 vs 16:9).
        var minScale = Math.round(minRatio * 10000);
        var maxScale = Math.round(maxRatio * 10000);
        if (w * 10000 < h * minScale) return false;
        if (w * 10000 > h * maxScale) return false;
        return true;
    }

    function isFacebookPostAspectValid(width, height) {
        return isVideoAspectWithinBounds(width, height, 9 / 16, 16 / 9);
    }

    function isVertical916Ratio(ratio) {
        var target = 9 / 16;
        return Math.abs(ratio - target) <= 0.04;
    }

    function ensureVideoMetadataLoaded() {
        selectedFiles.forEach(function (file, index) {
            if ((file.type || '').indexOf('video/') !== 0) {
                selectedFileMetadata[index] = null;
                return;
            }
            if (selectedFileMetadata[index] && selectedFileMetadata[index].loaded) return;
            selectedFileMetadata[index] = { loaded: false };
            var video = document.createElement('video');
            video.preload = 'metadata';
            var url = URL.createObjectURL(file);
            video.addEventListener('loadedmetadata', function () {
                URL.revokeObjectURL(url);
                selectedFileMetadata[index] = {
                    duration: video.duration,
                    width: video.videoWidth,
                    height: video.videoHeight,
                    aspectRatio: video.videoWidth / video.videoHeight,
                    aspectRatioLabel: formatAspectRatioLabel(video.videoWidth, video.videoHeight),
                    loaded: true,
                };
                persistActivePlatformMedia();
                updateComposeErrors();
                renderMediaGrid();
            });
            video.addEventListener('error', function () {
                URL.revokeObjectURL(url);
            });
            video.src = url;
        });
    }

    function composeErrorActionLabel(type, fileName) {
        var label = 'Crop video ';
        if (type === 'trim') label = 'Trim video ';
        return label + truncateFileName(fileName, 18);
    }

    function pushVerticalVideoErrors(errors, contextLabel, meta, fileName, maxDurationSeconds) {
        if (!isVertical916Ratio(meta.aspectRatio)) {
            errors.push({
                id: contextLabel + '-aspect',
                title: 'Video has an unsupported aspect ratio',
                message: 'When publishing directly, ' + contextLabel + ' supports aspect ratios between 9:16 and 9:16. Your video is ' + meta.aspectRatioLabel + '.',
                actionLabel: composeErrorActionLabel('crop', fileName),
                editorMode: 'crop',
                editorTargetRatio: 9 / 16,
            });
        }
        if (meta.duration > maxDurationSeconds) {
            errors.push({
                id: contextLabel + '-duration',
                title: 'Video is too long',
                message: 'When publishing directly, ' + contextLabel + ' supports videos up to ' + formatDurationLong(maxDurationSeconds) + ' long. Your video is ' + formatDurationLong(meta.duration) + ' long.',
                actionLabel: composeErrorActionLabel('trim', fileName),
                editorMode: 'trim',
                editorMaxDuration: maxDurationSeconds,
            });
        }
        if (meta.height < 960) {
            errors.push({
                id: contextLabel + '-height',
                title: 'Video height is too small',
                message: 'When publishing directly, ' + contextLabel + ' requires videos be at least 960 pixels tall. Your video height is ' + meta.height + ' pixels.',
            });
        }
    }

    function selectedFilesIncludeImage() {
        return platformFilesIncludeImage(activeComposePlatform || 'facebook');
    }

    /**
     * TikTok's Content Posting API only publishes one video, so photos and extra
     * files are surfaced as blocking errors the user clears from the media strip.
     */
    function getTikTokComposeErrors(platformFiles, videoMeta, fileName) {
        var errors = [];

        if (platformFilesIncludeImage('tiktok')) {
            errors.push({
                id: 'tiktok-image',
                title: 'This file type isn\'t supported by TikTok',
                message: 'You can only publish videos to TikTok. Remove the photo to continue.',
            });
        }

        if (platformFiles.length > 1) {
            errors.push({
                id: 'tiktok-attachments',
                title: 'Too many attachments added',
                message: 'TikTok publishes one video at a time. You\'ve added ' + platformFiles.length + '. Please remove any extra files.',
            });
        }

        var maxDuration = tiktokMaxVideoDuration();
        if (videoMeta && maxDuration > 0 && videoMeta.duration > maxDuration) {
            errors.push({
                id: 'tiktok-duration',
                title: 'Video is too long',
                message: 'This TikTok account supports videos up to ' + formatDurationLong(maxDuration) + ' long. Your video is ' + formatDurationLong(videoMeta.duration) + ' long.',
                actionLabel: composeErrorActionLabel('trim', fileName),
                editorMode: 'trim',
                editorMaxDuration: maxDuration,
            });
        }

        if (getPlatformFullCaption('tiktok').length > TIKTOK_CAPTION_LIMIT) {
            errors.push({
                id: 'tiktok-caption',
                title: 'Caption is too long',
                message: 'TikTok captions are limited to ' + TIKTOK_CAPTION_LIMIT.toLocaleString() + ' characters.',
            });
        }

        return errors;
    }

    function tiktokMaxVideoDuration() {
        if (!tiktokCreatorInfo) return 0;
        var max = Number(tiktokCreatorInfo.max_video_post_duration_sec || 0);
        return isFinite(max) && max > 0 ? max : 0;
    }

    function getComposeErrorsForPlatform(platform) {
        var errors = [];
        if (getSelectedComposePlatforms().indexOf(platform) === -1) {
            return errors;
        }

        var postType = getComposePostTypeForPlatform(platform);
        var platformFiles = getFilesForPlatform(platform);
        var videoMeta = getVideoMetadataForPlatform(platform);
        var videoIndex = getFirstVideoIndexForPlatform(platform);
        var videoFile = videoIndex >= 0 ? platformFiles[videoIndex] : null;
        var fileName = videoFile ? videoFile.name : 'video.mp4';

        if (platform === 'tiktok') {
            return getTikTokComposeErrors(platformFiles, videoMeta, fileName);
        }

        if (platform === 'instagram' && postType === 'story' && platformFiles.length > 1) {
            errors.push({
                id: 'instagram-story-attachments',
                title: 'Too many attachments added',
                message: 'Instagram stories supports up to 1 attachment for direct publishing. You\'ve added ' + platformFiles.length + '. Please remove any extra files.',
            });
        }

        if (postType === 'post' && platformFiles.length > 1) {
            var hasVideo = platformFilesIncludeVideo(platform);
            var hasImage = platformFilesIncludeImage(platform);
            if (hasVideo && hasImage) {
                errors.push({
                    id: platform + '-post-mixed-media',
                    title: 'Too many attachments added',
                    message: 'Use one video or multiple photos — not both.',
                });
            }
        }

        if (platform === 'instagram' && postType === 'post') {
            var videoCount = 0;
            platformFiles.forEach(function (file) {
                if ((file.type || '').indexOf('video/') === 0) {
                    videoCount += 1;
                }
            });
            if (videoCount > 1) {
                errors.push({
                    id: 'instagram-post-multiple-videos',
                    title: 'Too many attachments added',
                    message: 'Instagram posts support one video in a carousel.',
                });
            }
        }

        if (platform === 'instagram' && postType === 'reel' && platformFilesIncludeImage(platform)) {
            errors.push({
                id: 'instagram-reel-image',
                title: 'This file type isn\'t supported by Instagram reels',
                message: 'You can only publish videos to Instagram reels.',
            });
        }

        if (platform === 'facebook' && postType === 'reel' && platformFilesIncludeImage(platform)) {
            errors.push({
                id: 'facebook-reel-image',
                title: 'This file type isn\'t supported by Facebook reels',
                message: 'You can only publish videos to Facebook reels.',
            });
        }

        if (platform === 'instagram' && postType === 'story' && videoMeta && videoMeta.duration > 60) {
            errors.push({
                id: 'instagram-story-duration',
                title: 'Video is too long',
                message: 'When publishing directly, Instagram stories supports videos up to 60 seconds long. Your video is ' + formatDurationLong(videoMeta.duration) + ' long.',
                actionLabel: composeErrorActionLabel('trim', fileName),
                editorMode: 'trim',
                editorMaxDuration: 60,
            });
        }

        if (platform === 'instagram' && postType === 'post' && isInstagramPostCarousel(platform) && videoMeta && videoMeta.duration > 60) {
            errors.push({
                id: 'instagram-post-duration',
                title: 'Video is too long',
                message: 'When publishing directly, Instagram posts supports videos up to 60 seconds long. Your video is ' + formatDurationLong(videoMeta.duration) + ' long.',
                actionLabel: composeErrorActionLabel('trim', fileName),
                editorMode: 'trim',
                editorMaxDuration: 60,
            });
        }

        if (videoMeta) {
            if (platform === 'facebook' && postType === 'post') {
                var minRatio = 9 / 16;
                var maxRatio = 16 / 9;
                if (!isFacebookPostAspectValid(videoMeta.width, videoMeta.height)) {
                    errors.push({
                        id: 'facebook-post-aspect',
                        title: 'Video has an unsupported aspect ratio',
                        message: 'When publishing directly, Facebook posts supports aspect ratios between 9:16 and 16:9. Your video is ' + videoMeta.aspectRatioLabel + '.',
                        actionLabel: composeErrorActionLabel('crop', fileName),
                        editorMode: 'crop',
                        editorAspectMin: minRatio,
                        editorAspectMax: maxRatio,
                    });
                }
            }

            if (platform === 'facebook' && (postType === 'story' || postType === 'reel')) {
                var fbContext = postType === 'story' ? 'Facebook stories' : 'Facebook reels';
                pushVerticalVideoErrors(errors, fbContext, videoMeta, fileName, 90);
            }
        }

        return errors;
    }

    function hasBlockingComposeErrors() {
        var platforms = getSelectedComposePlatforms();
        for (var i = 0; i < platforms.length; i++) {
            if (getComposeErrorsForPlatform(platforms[i]).length > 0) {
                return true;
            }
        }
        return false;
    }

    function syncComposePlatformTabActiveState() {
        if (!platformTabs) return;
        platformTabs.querySelectorAll('[data-smm-platform-tab]').forEach(function (btn) {
            var tabPlatform = btn.getAttribute('data-smm-platform-tab') || '';
            var isActive = tabPlatform === activeComposePlatform;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            var group = btn.closest('[data-smm-platform-tab-group]');
            if (group) {
                group.classList.toggle('is-active', isActive);
            }
        });
    }

    function composePlatformErrorBadgeHtml() {
        return '<span class="smm-compose-platform-error-badge" data-smm-platform-error-badge aria-hidden="true">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
            '<path d="M12 3.5 3 20h18L12 3.5Z" fill="currentColor"/>' +
            '<path d="M12 9v5M12 16.5h.01" stroke="#fff" stroke-width="2" stroke-linecap="round"/>' +
            '</svg></span>';
    }

    function updateComposePlatformTabErrors() {
        if (!platformTabs) return;

        platformTabs.querySelectorAll('[data-smm-platform-error-badge]').forEach(function (badge) {
            badge.remove();
        });

        getSelectedComposePlatforms().forEach(function (platform) {
            if (getComposeErrorsForPlatform(platform).length === 0) return;
            var tab = platformTabs.querySelector('[data-smm-platform-tab="' + platform + '"]');
            if (!tab) return;
            var row = tab.querySelector('[data-smm-platform-tab-row]');
            if (!row) return;
            row.insertAdjacentHTML('beforeend', composePlatformErrorBadgeHtml());
        });
    }

    function composeErrorActionAttributes(error) {
        var attrs = ' data-smm-video-editor-open data-smm-video-editor-mode="' + escapeHtml(error.editorMode) + '"';
        if (error.editorMaxDuration) {
            attrs += ' data-smm-video-editor-max-duration="' + escapeHtml(String(error.editorMaxDuration)) + '"';
        }
        if (error.editorTargetRatio) {
            attrs += ' data-smm-video-editor-target-ratio="' + escapeHtml(String(error.editorTargetRatio)) + '"';
        }
        if (error.editorAspectMin) {
            attrs += ' data-smm-video-editor-aspect-min="' + escapeHtml(String(error.editorAspectMin)) + '"';
        }
        if (error.editorAspectMax) {
            attrs += ' data-smm-video-editor-aspect-max="' + escapeHtml(String(error.editorAspectMax)) + '"';
        }
        return attrs;
    }

    function composeErrorAlertHtml(error) {
        var actionHtml = error.actionLabel && error.editorMode
            ? '<button type="button" class="smm-compose-error-alert-action font-sans"' + composeErrorActionAttributes(error) + '>' + escapeHtml(error.actionLabel) + '</button>'
            : '';
        return (
            '<div class="smm-compose-error-alert" role="alert">' +
            '<span class="smm-compose-error-alert-icon" aria-hidden="true">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M12 8v5M12 16h.01" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>' +
            '</span>' +
            '<div class="smm-compose-error-alert-copy">' +
            '<p class="smm-compose-error-alert-title font-sans">' + escapeHtml(error.title) + '</p>' +
            '<p class="smm-compose-error-alert-message font-sans">' + escapeHtml(error.message) + '</p>' +
            actionHtml +
            '</div>' +
            '</div>'
        );
    }

    function updateComposeErrorAlert() {
        var container = root.querySelector('[data-smm-compose-error-alerts]');
        if (!container) return;

        var errors = getComposeErrorsForPlatform(activeComposePlatform);
        if (errors.length === 0) {
            container.classList.add('is-hidden');
            container.hidden = true;
            container.innerHTML = '';
            return;
        }

        container.innerHTML = errors.map(composeErrorAlertHtml).join('');
        container.classList.remove('is-hidden');
        container.hidden = false;
    }

    function getMaxTrimDurationForPlatform(platform) {
        var postType = getComposePostTypeForPlatform(platform);
        if (platform === 'instagram' && postType === 'story') return 60;
        if (platform === 'instagram' && postType === 'post' && isInstagramPostCarousel(platform)) return 60;
        if (platform === 'facebook' && (postType === 'story' || postType === 'reel')) return 90;
        if (platform === 'tiktok') return tiktokMaxVideoDuration() || null;
        return null;
    }

    function getVideoEditorConfig(platform) {
        var postType = getComposePostTypeForPlatform(platform);
        var config = {
            trimAvailable: true,
            cropAvailable: true,
            maxDuration: getMaxTrimDurationForPlatform(platform),
            targetRatio: null,
            aspectMin: null,
            aspectMax: null,
        };

        if (platform === 'facebook' && postType === 'post') {
            config.aspectMin = 9 / 16;
            config.aspectMax = 16 / 9;
        } else if (platform === 'facebook' && (postType === 'story' || postType === 'reel')) {
            config.targetRatio = 9 / 16;
            config.aspectMin = null;
            config.aspectMax = null;
        } else if (platform === 'instagram' && (postType === 'story' || postType === 'reel')) {
            config.targetRatio = 9 / 16;
            config.aspectMin = null;
            config.aspectMax = null;
        } else if (platform === 'instagram' && postType === 'post') {
            config.aspectMin = 9 / 16;
            config.aspectMax = 16 / 9;
        }

        return config;
    }

    function getVideoEditChooserFlags(platform) {
        var errors = getComposeErrorsForPlatform(platform);
        var needsTrim = false;
        var needsCrop = false;
        errors.forEach(function (error) {
            if (error.editorMode === 'trim') needsTrim = true;
            if (error.editorMode === 'crop') needsCrop = true;
        });
        return { needsTrim: needsTrim, needsCrop: needsCrop };
    }

    function launchVideoEditor(mode, videoIndex, editorOverrides) {
        if (!window.LeadBirdVideoEditor || videoIndex < 0) return;

        var platform = activeComposePlatform || 'facebook';
        var editorConfig = getVideoEditorConfig(platform);
        var sourceFile = selectedOriginalFiles[videoIndex] || selectedFiles[videoIndex];
        if (!sourceFile) return;

        ensureOriginalVideoMetadataForIndex(videoIndex).then(function (meta) {
            if (!meta) {
                if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                    window.LeadBirdModal.alert({
                        title: 'Could not open editor',
                        message: 'We could not read this video file.',
                        tone: 'error',
                    });
                }
                return;
            }

            var openOptions = {
                mode: mode,
                file: sourceFile,
                meta: meta,
                maxDuration: editorOverrides && editorOverrides.maxDuration != null
                    ? editorOverrides.maxDuration
                    : editorConfig.maxDuration,
                targetRatio: editorOverrides && editorOverrides.targetRatio != null
                    ? editorOverrides.targetRatio
                    : editorConfig.targetRatio,
                aspectMin: editorOverrides && editorOverrides.aspectMin != null
                    ? editorOverrides.aspectMin
                    : editorConfig.aspectMin,
                aspectMax: editorOverrides && editorOverrides.aspectMax != null
                    ? editorOverrides.aspectMax
                    : editorConfig.aspectMax,
                onSave: function (editedFile) {
                    selectedFiles[videoIndex] = editedFile;
                    selectedFileMetadata[videoIndex] = null;
                    persistActivePlatformMedia();
                    syncMediaInput();
                    renderMediaGrid();
                    updateComposeErrors();
                },
            };

            window.LeadBirdVideoEditor.open(openOptions);
        }).catch(function () {
            if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                window.LeadBirdModal.alert({
                    title: 'Could not open editor',
                    message: 'We could not read this video file.',
                    tone: 'error',
                });
            }
        });
    }

    function closeVideoEditChooser() {
        var chooser = document.querySelector('[data-smm-video-edit-chooser]');
        if (!chooser) return;
        chooser.classList.add('is-hidden');
        chooser.hidden = true;
        chooser.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('smm-video-edit-chooser-open');
        chooser.removeAttribute('data-smm-video-edit-index');
    }

    function openVideoEditChooser(videoIndex) {
        var chooser = document.querySelector('[data-smm-video-edit-chooser]');
        if (!chooser || videoIndex < 0) return;

        var platform = activeComposePlatform || 'facebook';
        var flags = getVideoEditChooserFlags(platform);
        var trimBadge = chooser.querySelector('[data-smm-video-edit-trim-badge]');
        var cropBadge = chooser.querySelector('[data-smm-video-edit-crop-badge]');

        if (trimBadge) {
            trimBadge.classList.toggle('is-hidden', !flags.needsTrim);
            trimBadge.hidden = !flags.needsTrim;
        }
        if (cropBadge) {
            cropBadge.classList.toggle('is-hidden', !flags.needsCrop);
            cropBadge.hidden = !flags.needsCrop;
        }

        chooser.setAttribute('data-smm-video-edit-index', String(videoIndex));
        chooser.classList.remove('is-hidden');
        chooser.hidden = false;
        chooser.setAttribute('aria-hidden', 'false');
        document.body.classList.add('smm-video-edit-chooser-open');

        var firstAction = chooser.querySelector('[data-smm-video-edit-action]');
        if (firstAction) firstAction.focus();
    }

    function openVideoEditorFromButton(btn) {
        if (!selectedFilesIncludeVideo() || !window.LeadBirdVideoEditor) return;

        var videoIndex = getFirstVideoIndexForPlatform(activeComposePlatform || 'facebook');
        if (videoIndex < 0) return;

        launchVideoEditor(btn.getAttribute('data-smm-video-editor-mode') || 'trim', videoIndex, {
            maxDuration: parseFloat(btn.getAttribute('data-smm-video-editor-max-duration') || '') || null,
            targetRatio: parseFloat(btn.getAttribute('data-smm-video-editor-target-ratio') || '') || null,
            aspectMin: parseFloat(btn.getAttribute('data-smm-video-editor-aspect-min') || '') || null,
            aspectMax: parseFloat(btn.getAttribute('data-smm-video-editor-aspect-max') || '') || null,
        });
    }

    function bindVideoEditorErrorActions() {
        var container = root.querySelector('[data-smm-compose-error-alerts]');
        if (!container || container.dataset.smmVideoEditorBound === '1') return;

        container.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-smm-video-editor-open]');
            if (!btn) return;
            event.preventDefault();
            openVideoEditorFromButton(btn);
        });
        container.dataset.smmVideoEditorBound = '1';
    }

    function bindVideoEditChooser() {
        var chooser = document.querySelector('[data-smm-video-edit-chooser]');
        if (!chooser || chooser.dataset.smmBound === '1') return;

        chooser.querySelectorAll('[data-smm-video-edit-close]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                closeVideoEditChooser();
            });
        });

        chooser.querySelectorAll('[data-smm-video-edit-action]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                var mode = btn.getAttribute('data-smm-video-edit-action') || 'trim';
                var videoIndex = parseInt(chooser.getAttribute('data-smm-video-edit-index') || '-1', 10);
                closeVideoEditChooser();
                if (videoIndex < 0) return;
                launchVideoEditor(mode, videoIndex);
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            if (chooser.classList.contains('is-hidden')) return;
            closeVideoEditChooser();
        });

        chooser.dataset.smmBound = '1';
    }

    function updateComposeErrors() {
        updateComposeErrorAlert();
        updateComposePlatformTabErrors();
    }

    function closePostTypeMenu() {
        if (postTypeMenu) {
            postTypeMenu.classList.add('is-hidden');
            postTypeMenu.hidden = true;
        }
        if (postTypeTrigger) {
            postTypeTrigger.setAttribute('aria-expanded', 'false');
        }
    }

    function updateEditorPlatformExtras() {
        var platforms = getSelectedComposePlatforms();
        if (platformExtras) {
            // Boost and first comment are Meta-only surfaces.
            var showExtras = platforms.length > 0 && activeComposePlatform !== 'tiktok';
            platformExtras.classList.toggle('is-hidden', !showExtras);
            platformExtras.hidden = !showExtras;
        }
        if (firstCommentWrap) {
            var showIg = activeComposePlatform === 'instagram'
                && getEffectivePreviewFormat('instagram') === 'post'
                && platforms.indexOf('instagram') !== -1;
            firstCommentWrap.classList.toggle('is-hidden', !showIg);
            firstCommentWrap.hidden = !showIg;
        }
        root.querySelectorAll('[data-smm-story-notice]').forEach(function (notice) {
            var platform = notice.getAttribute('data-smm-story-notice') || '';
            var show = getComposePostTypeForPlatform(platform) === 'story' && activeComposePlatform === platform && platforms.indexOf(platform) !== -1;
            notice.classList.toggle('is-hidden', !show);
            notice.hidden = !show;
        });
    }

    function resetTikTokSettings() {
        tiktokSettings = { privacyLevel: '', allowComment: false, allowDuet: false, allowStitch: false };
        tiktokCreatorInfo = null;
        tiktokCreatorInfoAccountId = '';
        tiktokCreatorInfoPending = false;
    }

    /**
     * TikTok requires apps to show the creator's current audience options and to
     * honour accounts that have comments, duet, or stitch switched off.
     */
    function loadTikTokCreatorInfo() {
        var account = getPrimaryAccountForPlatform('tiktok');
        if (!account) {
            return;
        }
        if (tiktokCreatorInfoPending || tiktokCreatorInfoAccountId === account.id) {
            return;
        }
        if (!config.endpoints || !config.endpoints.tiktokCreatorInfo) {
            return;
        }

        tiktokCreatorInfoPending = true;
        var url = config.endpoints.tiktokCreatorInfo + (config.endpoints.tiktokCreatorInfo.indexOf('?') === -1 ? '?' : '&') +
            'account_id=' + encodeURIComponent(account.id);

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                return r && typeof r.json === 'function' ? r.json() : null;
            })
            .then(function (data) {
                if (!data || !data.ok || !data.creator) {
                    return;
                }
                tiktokCreatorInfo = data.creator;
                tiktokCreatorInfoAccountId = account.id;
                applyTikTokCreatorDefaults();
                updateTikTokOptionsUi();
                updateComposeErrors();
            })
            .catch(function () {})
            .finally(function () {
                tiktokCreatorInfoPending = false;
            });
    }

    function applyTikTokCreatorDefaults() {
        if (!tiktokCreatorInfo) return;

        var allowed = tiktokAllowedPrivacyLevels();
        if (
            tiktokSettings.privacyLevel !== ''
            && allowed.length > 0
            && allowed.indexOf(tiktokSettings.privacyLevel) === -1
        ) {
            // Never substitute another audience — clear it so the creator re-picks.
            tiktokSettings.privacyLevel = '';
        }
        if (tiktokCreatorInfo.comment_disabled) tiktokSettings.allowComment = false;
        if (tiktokCreatorInfo.duet_disabled) tiktokSettings.allowDuet = false;
        if (tiktokCreatorInfo.stitch_disabled) tiktokSettings.allowStitch = false;
    }

    function updateTikTokOptionsUi() {
        var wrap = root.querySelector('[data-smm-tiktok-options]');
        if (!wrap) return;

        var show = activeComposePlatform === 'tiktok' && getSelectedComposePlatforms().indexOf('tiktok') !== -1;
        wrap.classList.toggle('is-hidden', !show);
        wrap.hidden = !show;

        if (!show) return;

        loadTikTokCreatorInfo();
        updateTikTokPrivacyUi();

        root.querySelectorAll('[data-smm-tiktok-toggle]').forEach(function (input) {
            if (!(input instanceof HTMLInputElement)) return;
            var kind = input.getAttribute('data-smm-tiktok-toggle') || '';
            var lockedByCreator = false;
            var allowed = true;

            if (kind === 'comment') {
                allowed = tiktokSettings.allowComment;
                lockedByCreator = !!(tiktokCreatorInfo && tiktokCreatorInfo.comment_disabled);
            } else if (kind === 'duet') {
                allowed = tiktokSettings.allowDuet;
                lockedByCreator = !!(tiktokCreatorInfo && tiktokCreatorInfo.duet_disabled);
            } else if (kind === 'stitch') {
                allowed = tiktokSettings.allowStitch;
                lockedByCreator = !!(tiktokCreatorInfo && tiktokCreatorInfo.stitch_disabled);
            }

            input.checked = allowed && !lockedByCreator;
            input.disabled = lockedByCreator;
            var label = input.closest('.smm-compose-tiktok-toggle');
            if (label) {
                label.classList.toggle('is-locked', lockedByCreator);
                label.title = lockedByCreator ? 'Turned off in this TikTok account\'s privacy settings.' : '';
            }
        });
    }

    /**
     * TikTok only allows the audiences it returns for the account, so keep the
     * other choices visible but greyed out instead of hiding them.
     *
     * @returns {string[]} empty when TikTok has not told us yet
     */
    function tiktokAllowedPrivacyLevels() {
        if (!tiktokCreatorInfo || !Array.isArray(tiktokCreatorInfo.privacy_level_options)) {
            return [];
        }
        return tiktokCreatorInfo.privacy_level_options.filter(function (level) {
            return TIKTOK_PRIVACY_LEVELS.indexOf(level) !== -1;
        });
    }

    function updateTikTokPrivacyUi() {
        var select = root.querySelector('[data-smm-tiktok-privacy]');
        if (!(select instanceof HTMLSelectElement)) return;

        var allowed = tiktokAllowedPrivacyLevels();
        var changed = false;

        Array.prototype.forEach.call(select.options, function (option) {
            // The empty option is the "pick one" placeholder, never an audience.
            var blocked = option.value !== '' && allowed.length > 0 && allowed.indexOf(option.value) === -1;
            if (option.disabled !== blocked) {
                option.disabled = blocked;
                changed = true;
            }
            option.title = blocked ? 'TikTok does not allow this audience for this account right now.' : '';
        });

        if (select.value !== tiktokSettings.privacyLevel) {
            select.value = tiktokSettings.privacyLevel;
            changed = true;
        }

        if (changed) {
            refreshTikTokPrivacyDropdown(select);
        }
    }

    function refreshTikTokPrivacyDropdown(select) {
        if (!window.LeadBirdDropdown) return;
        var wrapper = select.closest('.lb-dropdown');
        if (wrapper && typeof window.LeadBirdDropdown.refresh === 'function') {
            window.LeadBirdDropdown.refresh(wrapper, select);
            return;
        }
        if (typeof window.LeadBirdDropdown.init === 'function') {
            window.LeadBirdDropdown.init(select.parentNode || root);
        }
    }

    function bindTikTokOptions() {
        var privacySelect = root.querySelector('[data-smm-tiktok-privacy]');
        if (privacySelect instanceof HTMLSelectElement && privacySelect.dataset.smmBound !== '1') {
            privacySelect.addEventListener('change', function () {
                tiktokSettings.privacyLevel = privacySelect.value || '';
                setNote('');
            });
            privacySelect.dataset.smmBound = '1';
        }

        root.querySelectorAll('[data-smm-tiktok-toggle]').forEach(function (input) {
            if (!(input instanceof HTMLInputElement) || input.dataset.smmBound === '1') return;
            input.addEventListener('change', function () {
                var kind = input.getAttribute('data-smm-tiktok-toggle') || '';
                if (kind === 'comment') tiktokSettings.allowComment = input.checked;
                if (kind === 'duet') tiktokSettings.allowDuet = input.checked;
                if (kind === 'stitch') tiktokSettings.allowStitch = input.checked;
            });
            input.dataset.smmBound = '1';
        });
    }

    function buildTikTokOptionsPayload() {
        return {
            privacy_level: tiktokSettings.privacyLevel || '',
            disable_comment: !tiktokSettings.allowComment,
            disable_duet: !tiktokSettings.allowDuet,
            disable_stitch: !tiktokSettings.allowStitch,
        };
    }

    function updateComposePreviewPanels() {
        var platforms = getSelectedComposePlatforms();

        pauseAllComposePreviewVideos();

        if (previewEmpty) {
            previewEmpty.hidden = platforms.length > 0;
        }
        if (previewStack) {
            previewStack.classList.toggle('is-hidden', platforms.length === 0);
            previewStack.hidden = platforms.length === 0;
        }

        if (platforms.length > 0 && (!activeComposePlatform || platforms.indexOf(activeComposePlatform) === -1)) {
            activeComposePlatform = platforms[0];
            syncComposePlatformTabActiveState();
        }

        root.querySelectorAll('[data-smm-preview-panel]').forEach(function (panel) {
            var platform = panel.getAttribute('data-smm-preview-panel') || '';
            var format = panel.getAttribute('data-smm-preview-format') || 'post';
            var show = platforms.indexOf(platform) !== -1 && platform === activeComposePlatform && format === getEffectivePreviewFormat(platform);
            panel.classList.toggle('is-hidden', !show);
            panel.hidden = !show;
        });

        ['instagram', 'facebook'].forEach(function (platform) {
            var account = getPrimaryAccountForPlatform(platform);
            var defaultName = platform === 'instagram' ? 'your.account' : 'Your account';
            var displayName = account ? account.displayName : defaultName;

            root.querySelectorAll('[data-smm-preview-account-name="' + platform + '"], [data-smm-preview-account-name="' + platform + '-story"], [data-smm-preview-account-name="' + platform + '-reel"]').forEach(function (nameEl) {
                nameEl.textContent = displayName;
            });

            renderPreviewAvatar(platform, account, 'post');
            renderPreviewAvatar(platform, account, 'story');
            renderPreviewAvatar(platform, account, 'reel');
        });

        renderPreviewAvatar('tiktok', getPrimaryAccountForPlatform('tiktok'), 'post');

        updateEditorPlatformExtras();
        updatePostTypeUi();
        updateTikTokOptionsUi();
        updateComposeErrors();
    }

    function setActiveComposePlatform(platform) {
        if (platform !== activeComposePlatform) {
            persistActivePlatformContent();
            persistActivePlatformMedia();
        }
        activeComposePlatform = platform || '';
        syncComposePlatformTabActiveState();
        loadActivePlatformContent();
        loadActivePlatformMedia();
        updatePostTypeUi();
        updateComposePreviewPanels();
        updatePreview();
        updateComposeErrors();
    }

    function renderComposePlatformTabs(platforms) {
        if (!platformTabs) return;
        if (platforms.length === 0) {
            platformTabs.innerHTML = '';
            platformTabs.classList.add('is-hidden');
            platformTabs.hidden = true;
            activeComposePlatform = '';
            updateComposePreviewPanels();
            updateComposeErrors();
            return;
        }

        if (!activeComposePlatform || platforms.indexOf(activeComposePlatform) === -1) {
            activeComposePlatform = platforms[0];
        }

        platformTabs.innerHTML = platforms.map(function (platform) {
            var icon = platformIcons[platform] || '';
            var isActive = platform === activeComposePlatform;
            var label = composePlatformLabel(platform);
            return '<div class="smm-compose-platform-tab-group' + (isActive ? ' is-active' : '') + '" data-smm-platform-tab-group="' + escapeHtml(platform) + '">' +
                '<button type="button" class="smm-compose-platform-tab' + (isActive ? ' is-active' : '') + '" data-smm-platform-tab="' + escapeHtml(platform) + '" role="tab" aria-selected="' + (isActive ? 'true' : 'false') + '" aria-label="' + escapeHtml(label) + '">' +
                '<span class="smm-compose-platform-tab-row" data-smm-platform-tab-row>' +
                '<span class="smm-compose-platform-tab-icon" aria-hidden="true">' + icon + '</span>' +
                '</span></button>' +
                '<span class="smm-compose-platform-tab-indicator" aria-hidden="true"></span>' +
                '</div>';
        }).join('');

        platformTabs.classList.remove('is-hidden');
        platformTabs.hidden = false;

        platformTabs.querySelectorAll('[data-smm-platform-tab]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                setActiveComposePlatform(btn.getAttribute('data-smm-platform-tab') || '');
            });
        });

        updateComposeErrors();
    }

    function updateAccountPickerUi() {
        var selected = getSelectedComposeAccounts();
        var platforms = getSelectedComposePlatforms();

        if (accountPlaceholder) {
            accountPlaceholder.hidden = selected.length > 0;
        }
        if (clearAccountsBtn) {
            clearAccountsBtn.classList.toggle('is-hidden', selected.length === 0);
        }
        if (accountTrigger) {
            accountTrigger.classList.toggle('has-selection', selected.length > 0);
        }
        if (accountPills) {
            if (selected.length === 0) {
                accountPills.innerHTML = '';
                accountPills.hidden = true;
            } else {
                accountPills.hidden = false;
                accountPills.innerHTML = selected.map(function (input) {
                    var platform = input.getAttribute('data-platform') || '';
                    var name = input.getAttribute('data-display-name') || 'Account';
                    var icon = platformIcons[platform] || '';
                    return '<span class="smm-compose-account-pill smm-compose-account-pill--' + escapeHtml(platform) + '">' +
                        '<span class="smm-compose-account-pill-icon" aria-hidden="true">' + icon + '</span>' +
                        '<span class="smm-compose-account-pill-name font-sans">' + escapeHtml(name) + '</span>' +
                        '<button type="button" class="smm-compose-account-pill-remove" data-smm-remove-account="' + escapeHtml(input.value) + '" aria-label="Remove ' + escapeHtml(name) + '">&times;</button>' +
                        '</span>';
                }).join('');

                accountPills.querySelectorAll('[data-smm-remove-account]').forEach(function (btn) {
                    btn.addEventListener('click', function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        var accountId = btn.getAttribute('data-smm-remove-account') || '';
                        var input = null;
                        root.querySelectorAll('[data-smm-compose-account]').forEach(function (el) {
                            if (el instanceof HTMLInputElement && el.value === accountId) {
                                input = el;
                            }
                        });
                        if (input instanceof HTMLInputElement) {
                            input.checked = false;
                            updateAccountPickerUi();
                        }
                    });
                });
            }
        }

        renderComposePlatformTabs(platforms);
        updateComposePreviewPanels();
        updatePreview();
    }

    function bindComposeAvatarFallbacks() {
        root.querySelectorAll('[data-smm-compose-avatar-img]').forEach(function (img) {
            img.addEventListener('error', function () {
                if (!(img instanceof HTMLImageElement)) return;
                img.classList.add('is-hidden');
                var fallback = img.nextElementSibling;
                if (fallback instanceof HTMLElement) {
                    fallback.classList.remove('is-hidden');
                }
            });
        });
    }

    /**
     * Narrow screens drop the separate "Save as draft" button, so the post
     * button becomes a menu instead of publishing on the first tap.
     */
    function submitActsAsMenu() {
        return window.matchMedia('(max-width: 900px)').matches;
    }

    function closeSubmitMenu() {
        if (!submitMenu) return;
        submitMenu.classList.add('is-hidden');
        submitMenu.hidden = true;
        if (submitBtn) submitBtn.setAttribute('aria-expanded', 'false');
    }

    function toggleSubmitMenu() {
        if (!submitMenu) return;
        var isOpen = !submitMenu.classList.contains('is-hidden');
        if (isOpen) {
            closeSubmitMenu();
            return;
        }
        closeScheduleModal();
        submitMenu.classList.remove('is-hidden');
        submitMenu.hidden = false;
        if (submitBtn) submitBtn.setAttribute('aria-expanded', 'true');
    }

    function closeComposeMenus() {
        if (accountPicker) accountPicker.classList.remove('is-open');
        if (accountMenu) accountMenu.hidden = true;
        if (accountTrigger) accountTrigger.setAttribute('aria-expanded', 'false');
        closePostTypeMenu();
        closeScheduleModal();
        closeSubmitMenu();
    }

    function toggleWisdomPanel(show) {
        if (!wisdomPanel || !composeBody) return;
        var shouldShow = typeof show === 'boolean' ? show : wisdomPanel.classList.contains('is-hidden');
        wisdomPanel.classList.toggle('is-hidden', !shouldShow);
        wisdomPanel.hidden = !shouldShow;
        composeBody.classList.toggle('is-wisdom-hidden', !shouldShow);
        var toggleBtn = root.querySelector('[data-smm-wisdom-toggle]');
        if (toggleBtn) toggleBtn.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
    }

    function resetComposeForm() {
        if (form) form.reset();
        resetMediaSelection();
        resetInstagramTags();
        instagramPostCarouselIndex = 0;
        instagramCarouselState = { signature: '', files: [], urls: [] };
        resetComposePlatformContent();
        activeComposePostType = 'post';
        updatePostTypeUi();
        updatePreview();
        if (scheduledAtEl) scheduledAtEl.value = '';
        setPublishMode('now');
        updateScheduleUi();
        updateAccountPickerUi();
        closeComposeMenus();
        if (wisdomAskInput) wisdomAskInput.value = '';
        toggleWisdomPanel(false);
    }

    function openComposeModal(options) {
        options = options || {};
        if (!composeModal) return;
        resetComposeForm();
        var date = options.date || null;
        var hour = options.hour != null ? options.hour : 17;
        if (date && isFutureOrToday(date)) {
            var scheduled = new Date(date.getTime());
            scheduled.setHours(hour, 0, 0, 0);
            if (scheduledAtEl) scheduledAtEl.value = toDatetimeLocal(scheduled);
            setPublishMode('schedule');
            openScheduleModal();
        } else if (options.mode === 'draft') {
            setPublishMode('draft');
        } else if (options.mode === 'now') {
            setPublishMode('now');
        } else {
            setPublishMode('now');
        }
        updateScheduleUi();
        composeModal.classList.remove('is-hidden');
        composeModal.hidden = false;
        document.body.classList.add('smm-compose-open');
        if (captionEl) captionEl.focus();
    }

    function closeComposeModal() {
        if (!composeModal) return;
        composeModal.classList.add('is-hidden');
        composeModal.hidden = true;
        document.body.classList.remove('smm-compose-open');
        closeComposeMenus();
    }

    function openDayPanel(date) {
        if (!dayPanel || !calendarLayout) return;
        selectedDay = new Date(date.getTime());
        selectedDay.setHours(0, 0, 0, 0);
        var dateEl = dayPanel.querySelector('[data-smm-day-panel-date]');
        if (dateEl) dateEl.textContent = formatDayPanelDate(selectedDay);
        renderDayPanelSlots();
        dayPanel.classList.remove('is-hidden');
        calendarLayout.classList.add('has-day-panel');
        renderCalendar();
    }

    function closeDayPanel() {
        if (!dayPanel || !calendarLayout) return;
        selectedDay = null;
        dayPanel.classList.add('is-hidden');
        calendarLayout.classList.remove('has-day-panel');
        var menu = dayPanel.querySelector('[data-smm-day-menu]');
        if (menu) menu.classList.add('is-hidden');
        renderCalendar();
    }

    function postWhen(post) {
        var raw = post._calendar_at || post.timestamp || post.published_at || post.scheduled_at || post.created_at || '';
        if (!raw) return null;
        var normalized = String(raw).trim();
        if (normalized.indexOf('T') === -1 && normalized.indexOf(' ') !== -1) {
            normalized = normalized.replace(' ', 'T');
        }
        normalized = normalized.replace(/([+-]\d{2})(\d{2})$/, '$1:$2');
        var when = new Date(normalized);
        return Number.isNaN(when.getTime()) ? null : when;
    }

    function postCalendarId(post) {
        if ((post.source || 'leadbird') === 'live') {
            return 'live:' + post.id;
        }
        return String(post.id || '');
    }

    function findCalendarPost(postKey) {
        if (String(postKey).indexOf('live:') === 0) {
            var liveId = String(postKey).slice(5);
            return livePosts.find(function (p) { return p.id === liveId; }) || null;
        }
        return queuePosts.find(function (p) { return String(p.id) === String(postKey); }) || null;
    }

    function calendarPosts() {
        var items = [];

        withoutTikTokDuplicates(queuePosts).forEach(function (post) {
            var status = post.status || '';
            if (!['scheduled', 'published', 'draft'].includes(status)) return;

            var calendarAt = '';
            if ((status === 'published' || status === 'failed') && post.published_at) {
                calendarAt = post.published_at;
            } else if (post.scheduled_at) {
                calendarAt = post.scheduled_at;
            }
            if (!calendarAt) return;

            items.push(Object.assign({}, post, { _calendar_at: calendarAt }));
        });

        livePosts.forEach(function (post) {
            var when = post.timestamp || post.published_at || '';
            if (!when) return;
            items.push(Object.assign({}, post, {
                source: 'live',
                _calendar_at: when,
            }));
        });

        if (activeAccount) {
            items = items.filter(function (post) {
                if ((post.source || 'leadbird') === 'live') {
                    return post.account_id === activeAccount;
                }
                return (post.targets || []).some(function (target) {
                    return target.connected_account_id === activeAccount;
                });
            });
        }

        return items.filter(matchesCalendarFilters);
    }

    function postStatusKey(post) {
        if ((post.source || 'leadbird') === 'live') return 'published';
        var status = post.status || 'draft';
        return status === 'scheduled' || status === 'published' ? status : 'draft';
    }

    function postPlatformKeys(post) {
        if ((post.source || 'leadbird') === 'live') {
            return post.platform ? [String(post.platform)] : [];
        }
        return (post.targets || []).map(function (target) {
            return String(target.platform || '');
        }).filter(Boolean);
    }

    function postAccountKeys(post) {
        if ((post.source || 'leadbird') === 'live') {
            return post.account_id ? [String(post.account_id)] : [];
        }
        return (post.targets || []).map(function (target) {
            return String(target.connected_account_id || '');
        }).filter(Boolean);
    }

    /** An empty group means "no restriction", so unset filters show everything. */
    function matchesCalendarFilters(post) {
        if (
            calendarFilters.status.length > 0
            && calendarFilters.status.indexOf(postStatusKey(post)) === -1
        ) {
            return false;
        }

        if (calendarFilters.platform.length > 0) {
            var platforms = postPlatformKeys(post);
            var platformMatch = platforms.some(function (platform) {
                return calendarFilters.platform.indexOf(platform) !== -1;
            });
            if (!platformMatch) return false;
        }

        if (calendarFilters.account.length > 0) {
            var accountIds = postAccountKeys(post);
            var accountMatch = accountIds.some(function (id) {
                return calendarFilters.account.indexOf(id) !== -1;
            });
            if (!accountMatch) return false;
        }

        return true;
    }

    function calendarFilterCount() {
        return calendarFilters.status.length
            + calendarFilters.platform.length
            + calendarFilters.account.length;
    }

    function postsOnDay(date) {
        var iso = formatIsoDate(date);
        return calendarPosts().filter(function (post) {
            var when = postWhen(post);
            if (!when) return false;
            return formatIsoDate(when) === iso;
        }).sort(function (a, b) {
            var aw = postWhen(a);
            var bw = postWhen(b);
            return (aw ? aw.getTime() : 0) - (bw ? bw.getTime() : 0);
        });
    }

    function postAccountLabel(post) {
        if (post.display_name) return post.display_name;
        if (post.targets && post.targets[0]) {
            return post.targets[0].platform_label || 'Account';
        }
        return post.platform_label || 'Account';
    }

    function postStatusLabel(post) {
        if ((post.source || 'leadbird') === 'live') return 'Published';
        var status = post.status || 'draft';
        if (status === 'published') return 'Published';
        if (status === 'scheduled') return 'Scheduled';
        if (status === 'draft') return 'Draft';
        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    function postMediaUrl(post) {
        return post.thumbnail_url || post.media_url || post.image_url || '';
    }

    function renderCalendarPostCard(post, options) {
        options = options || {};
        var when = postWhen(post);
        if (!when) return '';
        var isLive = (post.source || 'leadbird') === 'live';
        var timeLabel = when.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
        var caption = (post.caption || '').trim();
        var platform = post.platform || (post.targets && post.targets[0] && post.targets[0].platform) || '';
        var accountName = postAccountLabel(post);
        var mediaUrl = postMediaUrl(post);
        var statusLabel = postStatusLabel(post);
        var platformIcon = config.platformIcons && config.platformIcons[platform] ? config.platformIcons[platform] : '';
        var thumb = '';
        if (mediaUrl && isVideoMediaUrl(mediaUrl)) {
            // A raw video URL in an <img> only ever renders broken, so show the label.
            thumb = '<div class="smm-cal-post-thumb-wrap smm-cal-post-thumb-wrap--empty"><span>Video</span></div>';
        } else if (mediaUrl) {
            thumb = '<div class="smm-cal-post-thumb-wrap"><img class="smm-cal-post-thumb" src="' + escapeHtml(mediaUrl) + '" alt="" loading="lazy" decoding="async"></div>';
        } else {
            thumb = '<div class="smm-cal-post-thumb-wrap smm-cal-post-thumb-wrap--empty"><span>' + escapeHtml(post.media_type === 'video' ? 'Video' : 'Text') + '</span></div>';
        }
        var statusClass = isLive || post.status === 'published' ? ' is-published' : '';
        var compactClass = options.compact ? ' smm-cal-post-card--compact' : '';
        var listClass = options.list ? ' smm-cal-post-card--list' : '';

        return '<article class="smm-cal-post-card' + compactClass + listClass + statusClass + '" data-smm-cal-post="' + escapeHtml(postCalendarId(post)) + '" data-smm-cal-source="' + escapeHtml(post.source || 'leadbird') + '">' +
            thumb +
            '<div class="smm-cal-post-card-body">' +
            '<div class="smm-cal-post-card-top">' +
            '<span class="smm-cal-post-card-platform" aria-hidden="true">' + platformIcon + '</span>' +
            '<span class="smm-cal-post-card-account font-sans">' + escapeHtml(accountName) + '</span>' +
            '<span class="smm-cal-post-card-time font-sans">' + escapeHtml(timeLabel) + '</span>' +
            '</div>' +
            '<p class="smm-cal-post-card-caption font-sans">' + escapeHtml(caption.slice(0, options.list ? 180 : 72) || 'Post') + '</p>' +
            '<div class="smm-cal-post-card-foot">' +
            '<span class="smm-cal-post-card-status font-sans">' + escapeHtml(statusLabel) + (isLive ? ' via social' : '') + '</span>' +
            '</div>' +
            '</div>' +
            '</article>';
    }

    function ensureCalendarData() {
        return loadLiveFeed().then(function () {
            renderCalendar();
            if (selectedDay) renderDayPanelSlots();
        });
    }

    function renderDayPanelSlots() {
        if (!dayPanel || !selectedDay) return;
        var slotsEl = dayPanel.querySelector('[data-smm-day-slots]');
        if (!slotsEl) return;
        var dayPosts = postsOnDay(selectedDay);
        var html = renderRecommendedTimeSuggestHtml(selectedDay, { dayPanel: true, posts: dayPosts });
        dayPosts.forEach(function (post) {
            html += renderCalendarPostCard(post, { compact: true });
        });
        slotsEl.innerHTML = html || '<p class="smm-day-panel-empty font-sans">No posts scheduled. Tap + to add content.</p>';
        slotsEl.querySelectorAll('[data-smm-day-slot-hour]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var hour = parseInt(btn.getAttribute('data-smm-day-slot-hour') || '17', 10);
                openComposeModal({ date: selectedDay, hour: hour });
            });
        });
        slotsEl.querySelectorAll('[data-smm-cal-post]').forEach(function (el) {
            el.addEventListener('click', function () {
                var postKey = el.getAttribute('data-smm-cal-post');
                var source = el.getAttribute('data-smm-cal-source') || 'leadbird';
                if (!postKey) return;
                if (source === 'live') {
                    var livePost = findCalendarPost(postKey);
                    if (livePost && livePost.permalink) {
                        window.open(livePost.permalink, '_blank', 'noopener');
                    }
                    return;
                }
                openEditModal('leadbird', postKey);
            });
        });
    }

    function navigateCalendar(direction) {
        if (calendarViewMode === 'month') {
            calendarMonth = startOfMonth(new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + direction, 1));
        } else if (calendarViewMode === 'week') {
            calendarWeekStart = addDays(calendarWeekStart, direction * 7);
        } else {
            calendarWeekStart = addDays(calendarWeekStart, direction * 7);
        }
        renderCalendar();
    }

    function goToToday() {
        var today = new Date();
        calendarWeekStart = startOfWeek(today);
        calendarMonth = startOfMonth(today);
        renderCalendar();
    }

    function updateToolbarLabel() {
        var rangeLabel = root.querySelector('[data-smm-cal-range-label]');
        if (!rangeLabel) return;
        if (calendarViewMode === 'month' || calendarViewMode === 'list') {
            rangeLabel.textContent = formatMonthLabel(calendarMonth);
        } else {
            rangeLabel.textContent = formatRangeLabel(calendarWeekStart);
        }
        var volumeMonth = root.querySelector('[data-smm-volume-month]');
        if (volumeMonth) volumeMonth.textContent = formatMonthLong(calendarMonth);
    }

    function bindCalendarInteractions(container) {
        if (!container) return;
        container.querySelectorAll('[data-smm-open-compose]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.stopPropagation();
                var dateStr = btn.getAttribute('data-smm-day');
                var hour = parseInt(btn.getAttribute('data-smm-hour') || '17', 10);
                if (dateStr) {
                    var parts = dateStr.split('-');
                    openComposeModal({ date: new Date(parts[0], parts[1] - 1, parts[2]), hour: hour });
                    return;
                }
                openComposeModal();
            });
        });
        container.querySelectorAll('[data-smm-cal-day-select]').forEach(function (cell) {
            cell.addEventListener('click', function () {
                var dateStr = cell.getAttribute('data-smm-cal-day-select');
                if (!dateStr) return;
                var parts = dateStr.split('-');
                var date = new Date(parts[0], parts[1] - 1, parts[2]);
                if (isFutureOrToday(date)) {
                    openDayPanel(date);
                } else {
                    openDayPanel(date);
                }
            });
        });
        container.querySelectorAll('[data-smm-cal-post]').forEach(function (el) {
            el.addEventListener('click', function (event) {
                event.stopPropagation();
                var postKey = el.getAttribute('data-smm-cal-post');
                var source = el.getAttribute('data-smm-cal-source') || 'leadbird';
                if (!postKey) return;
                if (source === 'live') {
                    var livePost = findCalendarPost(postKey);
                    if (livePost && livePost.permalink) {
                        window.open(livePost.permalink, '_blank', 'noopener');
                    }
                    return;
                }
                openEditModal('leadbird', postKey);
            });
        });
        container.querySelectorAll('[data-smm-list-add]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.stopPropagation();
                var dateStr = btn.getAttribute('data-smm-list-add');
                if (!dateStr) return;
                var parts = dateStr.split('-');
                openComposeModal({ date: new Date(parts[0], parts[1] - 1, parts[2]), hour: 17 });
            });
        });
    }

    function renderVolumeTrack() {
        var track = root.querySelector('[data-smm-volume-track]');
        if (!track) return;
        var year = calendarMonth.getFullYear();
        var month = calendarMonth.getMonth();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var counts = {};
        calendarPosts().forEach(function (post) {
            var when = postWhen(post);
            if (!when) return;
            var iso = formatIsoDate(when);
            counts[iso] = (counts[iso] || 0) + 1;
        });
        var html = '';
        for (var day = 1; day <= daysInMonth; day += 1) {
            var date = new Date(year, month, day);
            var iso = formatIsoDate(date);
            var count = counts[iso] || 0;
            var today = new Date();
            var isToday = isSameDay(date, today);
            var inListRange = calendarViewMode === 'list' && isSameDay(date, calendarWeekStart);
            html += '<div class="smm-volume-day' + (isToday ? ' is-today' : '') + (inListRange ? ' is-range' : '') + '">' +
                '<span class="smm-volume-day-num">' + day + '</span>' +
                (count > 0 ? '<span class="smm-volume-day-count">' + count + '</span>' : '') +
                '</div>';
        }
        track.innerHTML = html;
    }

    function showView(name) {
        activeView = name;
        document.querySelectorAll('[data-smm-view]').forEach(function (btn) {
            var view = btn.getAttribute('data-smm-view') || '';
            btn.classList.toggle('is-active', view === name);
        });
        root.querySelectorAll('[data-smm-panel]').forEach(function (panel) {
            var panelName = panel.getAttribute('data-smm-panel') || '';
            var isTarget = panelName === name;
            panel.classList.toggle('is-active', isTarget);
            panel.hidden = !isTarget;
        });
        if (name === 'content' || name === 'drafts') {
            renderPosts();
        }
        if (name === 'calendar') {
            ensureCalendarData();
        }
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setNote(message, type) {
        if (!formNote) return;
        formNote.textContent = message || '';
        formNote.classList.remove('is-error', 'is-success');
        if (type) formNote.classList.add('is-' + type);
    }

    function publishOverlayTitleForMode(mode) {
        if (mode === 'draft') return 'Saving your draft';
        if (mode === 'schedule') return 'Scheduling your post';

        return 'Publishing your post';
    }

    function publishOverlayCopyForMode(mode) {
        if (mode === 'draft') return 'Saving your draft…';
        if (mode === 'schedule') return 'Adding it to your calendar…';

        var names = getSelectedComposePlatforms().map(composePlatformLabel).filter(Boolean);
        if (names.length === 0) {
            return 'Sending your post…';
        }

        return 'Sending it to ' + listToSentence(names) + '…';
    }

    function listToSentence(items) {
        if (items.length === 1) return items[0];
        if (items.length === 2) return items[0] + ' and ' + items[1];

        return items.slice(0, -1).join(', ') + ' and ' + items[items.length - 1];
    }

    function showPublishOverlay(title, copy) {
        if (!publishOverlay) return;
        if (publishOverlayTitle) publishOverlayTitle.textContent = title;
        if (publishOverlayFill) publishOverlayFill.style.width = '0%';
        // Local media prep has nothing to measure, so start with the sweeping bar.
        setPublishProgress(null, copy);
        publishOverlay.hidden = false;
        publishOverlay.classList.remove('is-hidden');
        publishOverlay.setAttribute('aria-hidden', 'false');
    }

    /** Pass a null ratio while the server works and we have no byte count to show. */
    function setPublishProgress(ratio, copy) {
        if (!publishOverlay) return;
        if (copy && publishOverlayCopy) publishOverlayCopy.textContent = copy;

        var indeterminate = ratio === null;
        if (publishOverlayTrack) {
            publishOverlayTrack.classList.toggle('is-indeterminate', indeterminate);
        }
        if (publishOverlayPct) {
            publishOverlayPct.classList.toggle('is-hidden', indeterminate);
        }
        if (indeterminate) {
            if (publishOverlayTrack) publishOverlayTrack.removeAttribute('aria-valuenow');
            return;
        }

        var pct = Math.max(0, Math.min(100, Math.round(ratio * 100)));
        if (publishOverlayFill) publishOverlayFill.style.width = pct + '%';
        if (publishOverlayPct) publishOverlayPct.textContent = pct + '%';
        if (publishOverlayTrack) publishOverlayTrack.setAttribute('aria-valuenow', String(pct));
    }

    function hidePublishOverlay() {
        if (!publishOverlay) return;
        publishOverlay.classList.add('is-hidden');
        publishOverlay.hidden = true;
        publishOverlay.setAttribute('aria-hidden', 'true');
        if (publishOverlayTrack) publishOverlayTrack.classList.remove('is-indeterminate');
        if (publishOverlayFill) publishOverlayFill.style.width = '0%';
    }

    /**
     * XHR instead of fetch: only XHR reports upload progress, and media uploads
     * are the slow part of publishing.
     */
    function postFormWithProgress(url, formData, onProgress) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.withCredentials = true;
            xhr.setRequestHeader('Accept', 'application/json');

            if (xhr.upload && typeof onProgress === 'function') {
                xhr.upload.addEventListener('progress', function (event) {
                    if (event.lengthComputable && event.total > 0) {
                        onProgress(event.loaded / event.total);
                    }
                });
                xhr.upload.addEventListener('load', function () {
                    onProgress(1);
                });
            }

            xhr.addEventListener('load', function () {
                try {
                    resolve(JSON.parse(xhr.responseText));
                } catch (err) {
                    resolve(null);
                }
            });
            xhr.addEventListener('error', function () {
                reject(new Error('network'));
            });
            xhr.addEventListener('abort', function () {
                reject(new Error('aborted'));
            });

            xhr.send(formData);
        });
    }

    /**
     * Grabs a still frame from a video so cards have a preview. The server has no
     * ffmpeg, and publishing deletes the uploaded video, so this is the only poster
     * a published post will ever have.
     */
    function captureVideoPosterBlob(file) {
        return new Promise(function (resolve) {
            if (!file || String(file.type || '').indexOf('video/') !== 0 || !window.URL) {
                resolve(null);
                return;
            }

            var url = URL.createObjectURL(file);
            var video = document.createElement('video');
            var settled = false;

            function finish(blob) {
                if (settled) return;
                settled = true;
                URL.revokeObjectURL(url);
                resolve(blob || null);
            }

            video.muted = true;
            video.playsInline = true;
            video.preload = 'auto';

            video.addEventListener('error', function () {
                finish(null);
            });

            video.addEventListener('loadeddata', function () {
                // Frame zero is often black, so step a little way into the clip.
                try {
                    video.currentTime = Math.min(0.5, (video.duration || 1) / 2);
                } catch (err) {
                    finish(null);
                }
            });

            video.addEventListener('seeked', function () {
                try {
                    var width = video.videoWidth;
                    var height = video.videoHeight;
                    if (!width || !height) {
                        finish(null);
                        return;
                    }

                    var scale = Math.min(1, 720 / Math.max(width, height));
                    var canvas = document.createElement('canvas');
                    canvas.width = Math.round(width * scale);
                    canvas.height = Math.round(height * scale);
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function (blob) {
                        finish(blob);
                    }, 'image/jpeg', 0.82);
                } catch (err) {
                    finish(null);
                }
            });

            // A stubborn file must never block publishing.
            setTimeout(function () {
                finish(null);
            }, 6000);

            video.src = url;
        });
    }

    function capturePosterForSubmit() {
        var platforms = getSelectedComposePlatforms();

        for (var i = 0; i < platforms.length; i++) {
            var files = getFilesForPlatform(platforms[i]) || [];
            for (var j = 0; j < files.length; j++) {
                if (String(files[j].type || '').indexOf('video/') === 0) {
                    return captureVideoPosterBlob(files[j]);
                }
            }
        }

        return Promise.resolve(null);
    }

    function revokePreviewUrls() {
        previewObjectUrls.forEach(function (url) {
            if (url.indexOf('blob:') === 0) {
                URL.revokeObjectURL(url);
            }
        });
        previewObjectUrls = [];
        clearStoryPreviewImageCache();
    }

    function resetInstagramTags() {
        instagramUserTags = [];
        pendingIgTagPosition = null;
        syncInstagramTagsField();
        hideIgTagPopover();
        renderInstagramTags();
        updateIgTagHintState();
    }

    function syncInstagramTagsField() {
        var field = root.querySelector('[data-smm-ig-user-tags]');
        if (!field) return;
        field.value = instagramUserTags.length ? JSON.stringify(instagramUserTags) : '';
    }

    function normalizeInstagramUsername(value) {
        var username = (value || '').trim().replace(/^@+/, '').replace(/\s+/g, '');
        if (!username || !/^[a-zA-Z0-9._]+$/.test(username)) {
            return '';
        }
        return username;
    }

    function getInstagramPostMediaEl() {
        return root.querySelector('[data-smm-preview-media="instagram"]');
    }

    function canTagInstagramImage() {
        var files = getFilesForPlatform('instagram');
        if (files.length === 0) return false;
        clampInstagramPostCarouselIndex(files.length);
        var file = files[instagramPostCarouselIndex];
        return file && (file.type || '').indexOf('video/') !== 0;
    }

    function instagramPostFilesSignature(files) {
        return files.map(function (file) {
            return [file.name, file.size, file.lastModified, file.type].join(':');
        }).join('|');
    }

    function clampInstagramPostCarouselIndex(fileCount) {
        if (fileCount <= 0) {
            instagramPostCarouselIndex = 0;
            return;
        }
        if (instagramPostCarouselIndex >= fileCount) {
            instagramPostCarouselIndex = fileCount - 1;
        }
        if (instagramPostCarouselIndex < 0) {
            instagramPostCarouselIndex = 0;
        }
    }

    function getInstagramCarouselViewport() {
        return root.querySelector('[data-smm-ig-carousel-viewport]');
    }

    function renderInstagramPostCarouselDots(count, activeIndex) {
        var dotsEl = root.querySelector('[data-smm-ig-carousel-dots]');
        if (!dotsEl) return;

        if (count <= 1) {
            dotsEl.innerHTML = '';
            dotsEl.hidden = true;
            return;
        }

        dotsEl.hidden = false;
        dotsEl.innerHTML = '';

        for (var i = 0; i < count; i++) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'smm-compose-preview-ig-carousel-dot' + (i === activeIndex ? ' is-active' : '');
            dot.setAttribute('data-smm-ig-carousel-dot', String(i));
            dot.setAttribute('role', 'tab');
            dot.setAttribute('aria-label', 'Slide ' + (i + 1) + ' of ' + count);
            dot.setAttribute('aria-selected', i === activeIndex ? 'true' : 'false');
            dotsEl.appendChild(dot);
        }
    }

    function updateInstagramPostCarouselNav(count, activeIndex) {
        var carousel = root.querySelector('[data-smm-ig-carousel]');
        var prevBtn = root.querySelector('[data-smm-ig-carousel-prev]');
        var nextBtn = root.querySelector('[data-smm-ig-carousel-next]');
        var hasMultiple = count > 1;

        if (carousel) {
            carousel.classList.toggle('has-multiple', hasMultiple);
        }
        if (prevBtn) {
            prevBtn.hidden = !hasMultiple;
            prevBtn.disabled = activeIndex <= 0;
        }
        if (nextBtn) {
            nextBtn.hidden = !hasMultiple;
            nextBtn.disabled = activeIndex >= count - 1;
        }
    }

    function renderInstagramPostCarouselSlide(files, urls) {
        var viewport = getInstagramCarouselViewport();
        var mediaEl = getInstagramPostMediaEl();
        if (!viewport || !mediaEl || !files.length || !urls.length) return false;

        clampInstagramPostCarouselIndex(files.length);
        var index = instagramPostCarouselIndex;
        var file = files[index];
        var url = urls[index];
        var isVideo = (file.type || '').indexOf('video/') === 0;

        pauseAllComposePreviewVideos();
        viewport.innerHTML = buildInstagramPostMediaHtml(url, isVideo);
        applyPreviewAspectMode(mediaEl, 'instagram', 'instagram', isVideo);
        renderInstagramPostCarouselDots(files.length, index);
        updateInstagramPostCarouselNav(files.length, index);
        renderInstagramTags();
        updateIgTagHintState();

        delete mediaEl.dataset.smmIgTagBound;
        bindInstagramTagSurface();

        return isVideo;
    }

    function renderInstagramPostPreview(platformFiles) {
        var mediaEl = getInstagramPostMediaEl();
        var carousel = root.querySelector('[data-smm-ig-carousel]');
        var emptyEl = root.querySelector('[data-smm-ig-preview-empty]');
        if (!mediaEl) return false;

        if (platformFiles.length === 0) {
            instagramPostCarouselIndex = 0;
            instagramCarouselState = { signature: '', files: [], urls: [] };
            if (carousel) {
                carousel.classList.add('is-hidden');
                carousel.hidden = true;
            }
            if (emptyEl) {
                emptyEl.hidden = false;
            }
            mediaEl.classList.add('is-empty');
            mediaEl.classList.remove('is-native-aspect');
            return false;
        }

        var signature = instagramPostFilesSignature(platformFiles);
        if (signature !== instagramCarouselState.signature) {
            instagramPostCarouselIndex = 0;
        }
        instagramCarouselState.signature = signature;
        instagramCarouselState.files = platformFiles.slice();
        instagramCarouselState.urls = platformFiles.map(function (file) {
            var blobUrl = URL.createObjectURL(file);
            previewObjectUrls.push(blobUrl);
            return blobUrl;
        });

        mediaEl.classList.remove('is-empty');
        if (emptyEl) {
            emptyEl.hidden = true;
        }
        if (carousel) {
            carousel.classList.remove('is-hidden');
            carousel.hidden = false;
        }

        clampInstagramPostCarouselIndex(platformFiles.length);
        return renderInstagramPostCarouselSlide(instagramCarouselState.files, instagramCarouselState.urls);
    }

    function stepInstagramPostCarousel(delta) {
        var files = instagramCarouselState.files.length
            ? instagramCarouselState.files
            : getFilesForPlatform('instagram');
        if (files.length <= 1) return;

        var nextIndex = instagramPostCarouselIndex + delta;
        if (nextIndex < 0 || nextIndex >= files.length) return;

        instagramPostCarouselIndex = nextIndex;
        hideIgTagPopover();
        resetInstagramTags();
        renderInstagramPostCarouselSlide(files, instagramCarouselState.urls);
    }

    function bindInstagramPostCarouselControls() {
        if (root.dataset.smmIgCarouselBound === '1') return;

        var prevBtn = root.querySelector('[data-smm-ig-carousel-prev]');
        var nextBtn = root.querySelector('[data-smm-ig-carousel-next]');
        var dotsEl = root.querySelector('[data-smm-ig-carousel-dots]');
        var carousel = root.querySelector('[data-smm-ig-carousel]');

        if (prevBtn) {
            prevBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                stepInstagramPostCarousel(-1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                stepInstagramPostCarousel(1);
            });
        }

        if (dotsEl) {
            dotsEl.addEventListener('click', function (event) {
                var dot = event.target.closest('[data-smm-ig-carousel-dot]');
                if (!dot) return;
                event.preventDefault();
                event.stopPropagation();
                var index = parseInt(dot.getAttribute('data-smm-ig-carousel-dot') || '-1', 10);
                if (index < 0 || index === instagramPostCarouselIndex) return;
                instagramPostCarouselIndex = index;
                hideIgTagPopover();
                resetInstagramTags();
                renderInstagramPostCarouselSlide(instagramCarouselState.files, instagramCarouselState.urls);
            });
        }

        if (carousel) {
            carousel.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    stepInstagramPostCarousel(-1);
                } else if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    stepInstagramPostCarousel(1);
                }
            });
        }

        root.dataset.smmIgCarouselBound = '1';
    }

    function buildInstagramPostMediaHtml(url, isVideo) {
        if (isVideo) {
            return '<video class="smm-compose-preview-native-video" src="' + escapeHtml(url) + '" controls controlsList="nodownload nofullscreen noremoteplayback" disablePictureInPicture muted playsinline></video>';
        }
        return (
            '<div class="smm-compose-preview-ig-media-inner" data-smm-ig-tag-surface>' +
            '<img src="' + escapeHtml(url) + '" alt="Preview">' +
            '<div class="smm-compose-preview-ig-tags" data-smm-ig-tags aria-live="polite"></div>' +
            '</div>'
        );
    }

    function renderInstagramTags() {
        var tagsEl = root.querySelector('[data-smm-ig-tags]');
        if (!tagsEl) return;

        tagsEl.innerHTML = instagramUserTags.map(function (tag, index) {
            return (
                '<div class="smm-compose-preview-ig-tag" style="left:' + tag.x + '%;top:' + tag.y + '%;" data-smm-ig-tag-index="' + index + '">' +
                '<span class="smm-compose-preview-ig-tag-label font-sans">@' + escapeHtml(tag.username) +
                '<button type="button" class="smm-compose-preview-ig-tag-remove" data-smm-ig-tag-remove="' + index + '" aria-label="Remove tag for @' + escapeHtml(tag.username) + '">&times;</button>' +
                '</span></div>'
            );
        }).join('');

        tagsEl.querySelectorAll('[data-smm-ig-tag-remove]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var removeIndex = parseInt(btn.getAttribute('data-smm-ig-tag-remove') || '-1', 10);
                if (removeIndex < 0) return;
                instagramUserTags.splice(removeIndex, 1);
                syncInstagramTagsField();
                renderInstagramTags();
            });
        });
    }

    function updateIgTagHintState() {
        var hint = root.querySelector('[data-smm-ig-tag-hint]');
        var tagSurface = root.querySelector('[data-smm-ig-tag-surface]');
        var canTag = canTagInstagramImage();

        if (tagSurface) {
            tagSurface.classList.toggle('is-tag-ready', canTag);
        }
        if (hint) {
            hint.classList.toggle('is-disabled', !canTag);
            var label = hint.querySelector('span');
            if (label) {
                label.textContent = canTag
                    ? 'Click on image to tag people'
                    : 'Upload a photo to tag people on your image';
            }
        }
    }

    function hideIgTagPopover() {
        var popover = root.querySelector('[data-smm-ig-tag-popover]');
        var input = root.querySelector('[data-smm-ig-tag-input]');
        pendingIgTagPosition = null;
        if (popover) {
            popover.classList.add('is-hidden');
            popover.hidden = true;
            popover.style.left = '';
            popover.style.top = '';
        }
        if (input) input.value = '';
    }

    function showIgTagPopover(clientX, clientY) {
        var popover = root.querySelector('[data-smm-ig-tag-popover]');
        var input = root.querySelector('[data-smm-ig-tag-input]');
        if (!popover) return;

        popover.classList.remove('is-hidden');
        popover.hidden = false;
        popover.style.left = '-9999px';
        popover.style.top = '-9999px';

        var popoverWidth = popover.offsetWidth || 224;
        var popoverHeight = popover.offsetHeight || 120;
        var margin = 10;
        var left = clientX + margin;
        var top = clientY + margin;

        if (left + popoverWidth > window.innerWidth - margin) {
            left = clientX - popoverWidth - margin;
        }
        if (top + popoverHeight > window.innerHeight - margin) {
            top = clientY - popoverHeight - margin;
        }

        left = Math.max(margin, Math.min(left, window.innerWidth - popoverWidth - margin));
        top = Math.max(margin, Math.min(top, window.innerHeight - popoverHeight - margin));

        popover.style.left = left + 'px';
        popover.style.top = top + 'px';

        if (input) {
            input.value = '';
            input.focus();
        }
    }

    function confirmIgTag() {
        var input = root.querySelector('[data-smm-ig-tag-input]');
        if (!pendingIgTagPosition || !input) return;

        var username = normalizeInstagramUsername(input.value);
        if (!username) {
            setNote('Enter a valid Instagram username to tag.', 'error');
            if (input) input.focus();
            return;
        }

        if (instagramUserTags.some(function (tag) { return tag.username.toLowerCase() === username.toLowerCase(); })) {
            setNote('That username is already tagged on this image.', 'error');
            if (input) input.focus();
            return;
        }

        instagramUserTags.push({
            username: username,
            x: pendingIgTagPosition.x,
            y: pendingIgTagPosition.y,
        });
        syncInstagramTagsField();
        renderInstagramTags();
        hideIgTagPopover();
        setNote('');
    }

    function bindInstagramTagSurface() {
        var mediaEl = getInstagramPostMediaEl();
        if (!mediaEl || mediaEl.dataset.smmIgTagBound === '1') return;

        mediaEl.addEventListener('click', function (event) {
            var surface = event.target.closest('[data-smm-ig-tag-surface]');
            if (!surface || !canTagInstagramImage()) return;
            if (event.target.closest('[data-smm-ig-tag-remove]')) return;

            event.stopPropagation();

            var rect = surface.getBoundingClientRect();
            if (!rect.width || !rect.height) return;

            var x = ((event.clientX - rect.left) / rect.width) * 100;
            var y = ((event.clientY - rect.top) / rect.height) * 100;
            x = Math.max(4, Math.min(x, 96));
            y = Math.max(6, Math.min(y, 94));

            pendingIgTagPosition = { x: Math.round(x * 10) / 10, y: Math.round(y * 10) / 10 };
            showIgTagPopover(event.clientX, event.clientY);
        });

        mediaEl.dataset.smmIgTagBound = '1';
    }

    function bindInstagramPreviewExtras() {
        var collabToggle = root.querySelector('[data-smm-ig-collab-toggle]');
        var collabSection = root.querySelector('.smm-compose-ig-collab');
        if (collabToggle && collabSection && collabToggle.dataset.smmIgCollabBound !== '1') {
            collabToggle.addEventListener('click', function () {
                var collapsed = collabSection.classList.toggle('is-collapsed');
                collabToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            });
            collabToggle.dataset.smmIgCollabBound = '1';
        }

        var tagCancel = root.querySelector('[data-smm-ig-tag-cancel]');
        var tagAdd = root.querySelector('[data-smm-ig-tag-add]');
        var tagInput = root.querySelector('[data-smm-ig-tag-input]');

        if (tagCancel && tagCancel.dataset.smmIgTagBound !== '1') {
            tagCancel.addEventListener('click', function (event) {
                event.preventDefault();
                hideIgTagPopover();
            });
            tagCancel.dataset.smmIgTagBound = '1';
        }

        if (tagAdd && tagAdd.dataset.smmIgTagBound !== '1') {
            tagAdd.addEventListener('click', function (event) {
                event.preventDefault();
                confirmIgTag();
            });
            tagAdd.dataset.smmIgTagBound = '1';
        }

        if (tagInput && tagInput.dataset.smmIgTagBound !== '1') {
            tagInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    confirmIgTag();
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    hideIgTagPopover();
                }
            });
            tagInput.dataset.smmIgTagBound = '1';
        }

        bindInstagramTagSurface();

        bindInstagramPostCarouselControls();

        bindIgStoryPlaybackControls();
        bindFbStoryPlaybackControls();

        if (root.dataset.smmIgTagDismissBound !== '1') {
            document.addEventListener('click', function (event) {
                var popover = root.querySelector('[data-smm-ig-tag-popover]');
                if (!popover || popover.hidden) return;
                if (popover.contains(event.target)) return;
                if (event.target.closest('[data-smm-ig-tag-surface]')) return;
                hideIgTagPopover();
            });
            root.dataset.smmIgTagDismissBound = '1';
        }
    }

    function syncMediaInput() {
        if (!mediaInput || typeof DataTransfer === 'undefined') return;
        var transfer = new DataTransfer();
        selectedFiles.forEach(function (file) {
            transfer.items.add(file);
        });
        mediaInput.files = transfer.files;
    }

    function formatStoryDuration(seconds) {
        var total = Math.max(0, Math.floor(Number(seconds) || 0));
        var mins = Math.floor(total / 60);
        var secs = total % 60;
        return mins + ':' + String(secs).padStart(2, '0');
    }

    var igStoryPlayback = {
        isPlaying: false,
        isMuted: true,
        isVideo: false,
        duration: 5,
        timer: null,
        tick: 0,
    };

    function getIgStoryMediaEl() {
        return root.querySelector('[data-smm-preview-media="instagram-story"]');
    }

    function getIgStoryVideo() {
        var mediaEl = getIgStoryMediaEl();
        if (!mediaEl) return null;
        var video = mediaEl.querySelector('video');
        return video instanceof HTMLVideoElement ? video : null;
    }

    function updateIgStoryScrubber(percent) {
        var fill = root.querySelector('[data-smm-ig-story-scrubber-fill]');
        var handle = root.querySelector('[data-smm-ig-story-scrubber-handle]');
        var pct = Math.max(0, Math.min(100, percent));
        if (fill) fill.style.width = pct + '%';
        if (handle) handle.style.left = pct + '%';
    }

    function updateIgStoryTimeDisplay(current, total) {
        var timeEl = root.querySelector('[data-smm-ig-story-playback-time]');
        if (!timeEl) return;
        timeEl.textContent = formatStoryDuration(current) + ' / ' + formatStoryDuration(total);
    }

    function updateIgStoryPlaybackUi() {
        var playBtn = root.querySelector('[data-smm-ig-story-play]');
        var volumeBtn = root.querySelector('[data-smm-ig-story-volume]');
        var playIcon = root.querySelector('[data-smm-ig-story-play-icon]');
        var pauseIcon = root.querySelector('[data-smm-ig-story-pause-icon]');
        var volumeOnIcon = root.querySelector('[data-smm-ig-story-volume-on]');
        var volumeOffIcon = root.querySelector('[data-smm-ig-story-volume-off]');

        if (playIcon) playIcon.hidden = igStoryPlayback.isPlaying;
        if (pauseIcon) pauseIcon.hidden = !igStoryPlayback.isPlaying;
        if (playBtn) {
            playBtn.setAttribute('aria-label', igStoryPlayback.isPlaying ? 'Pause' : 'Play');
        }

        var hasAudio = igStoryPlayback.isVideo;
        if (volumeBtn) {
            volumeBtn.classList.toggle('is-disabled', !hasAudio);
            volumeBtn.setAttribute('aria-label', igStoryPlayback.isMuted ? 'Unmute' : 'Mute');
        }
        if (volumeOnIcon && volumeOffIcon) {
            volumeOnIcon.hidden = !hasAudio || igStoryPlayback.isMuted;
            volumeOffIcon.hidden = hasAudio && !igStoryPlayback.isMuted;
        }
    }

    function stopIgStoryImageTimer() {
        if (igStoryPlayback.timer) {
            clearInterval(igStoryPlayback.timer);
            igStoryPlayback.timer = null;
        }
    }

    function startIgStoryImageTimer() {
        stopIgStoryImageTimer();
        var duration = igStoryPlayback.duration || 5;
        igStoryPlayback.timer = setInterval(function () {
            igStoryPlayback.tick += 0.1;
            var pct = (igStoryPlayback.tick / duration) * 100;
            updateIgStoryScrubber(pct);
            updateIgStoryTimeDisplay(igStoryPlayback.tick, duration);
            if (igStoryPlayback.tick >= duration) {
                stopIgStoryImageTimer();
                igStoryPlayback.isPlaying = false;
                igStoryPlayback.tick = 0;
                updateIgStoryScrubber(0);
                updateIgStoryTimeDisplay(0, duration);
                updateIgStoryPlaybackUi();
            }
        }, 100);
    }

    function toggleIgStoryPlay() {
        var mediaEl = getIgStoryMediaEl();
        if (!mediaEl || mediaEl.classList.contains('is-empty')) return;

        var video = getIgStoryVideo();
        if (video) {
            if (video.paused) {
                pauseAllComposePreviewVideos(video);
                video.play().catch(function () {});
            } else {
                video.pause();
            }
            return;
        }

        if (igStoryPlayback.isPlaying) {
            stopIgStoryImageTimer();
            igStoryPlayback.isPlaying = false;
        } else {
            igStoryPlayback.isPlaying = true;
            startIgStoryImageTimer();
        }
        updateIgStoryPlaybackUi();
    }

    function toggleIgStoryMute() {
        var video = getIgStoryVideo();
        if (!video) return;
        video.muted = !video.muted;
        igStoryPlayback.isMuted = video.muted;
        updateIgStoryPlaybackUi();
    }

    function resetIgStoryPlayback() {
        var video = getIgStoryVideo();
        if (video) {
            video.pause();
            video.muted = true;
            try {
                video.currentTime = 0;
            } catch (err) {
                /* ignore seek errors on unload */
            }
        }
        stopIgStoryImageTimer();
        igStoryPlayback.isPlaying = false;
        igStoryPlayback.isMuted = true;
        igStoryPlayback.tick = 0;
        updateIgStoryScrubber(0);

        var mediaEl = getIgStoryMediaEl();
        if (mediaEl) mediaEl.classList.remove('is-playback-ready');
        updateIgStoryPlaybackUi();
    }

    function bindIgStoryVideoEvents(video) {
        video.muted = true;
        video.playsInline = true;
        video.preload = 'metadata';
        video.pause();

        video.addEventListener('loadedmetadata', function () {
            if (isFinite(video.duration) && video.duration > 0) {
                igStoryPlayback.duration = video.duration;
                updateIgStoryTimeDisplay(0, video.duration);
            }
        });
        video.addEventListener('timeupdate', function () {
            var duration = video.duration || igStoryPlayback.duration || 5;
            var pct = duration > 0 ? (video.currentTime / duration) * 100 : 0;
            updateIgStoryScrubber(pct);
            updateIgStoryTimeDisplay(video.currentTime, duration);
        });
        video.addEventListener('play', function () {
            igStoryPlayback.isPlaying = true;
            updateIgStoryPlaybackUi();
        });
        video.addEventListener('pause', function () {
            igStoryPlayback.isPlaying = false;
            updateIgStoryPlaybackUi();
        });
        video.addEventListener('ended', function () {
            igStoryPlayback.isPlaying = false;
            updateIgStoryScrubber(100);
            updateIgStoryPlaybackUi();
        });
        video.addEventListener('volumechange', function () {
            igStoryPlayback.isMuted = video.muted;
            updateIgStoryPlaybackUi();
        });
    }

    function initIgStoryPlayback(isVideo, durationSeconds) {
        resetIgStoryPlayback();
        igStoryPlayback.isVideo = isVideo;
        igStoryPlayback.duration = isVideo && durationSeconds ? durationSeconds : 5;

        var mediaEl = getIgStoryMediaEl();
        if (mediaEl) mediaEl.classList.add('is-playback-ready');

        var video = getIgStoryVideo();
        if (video) {
            bindIgStoryVideoEvents(video);
            if (isFinite(video.duration) && video.duration > 0) {
                igStoryPlayback.duration = video.duration;
            }
        }
        updateIgStoryTimeDisplay(0, igStoryPlayback.duration);
        updateIgStoryPlaybackUi();
    }

    function bindIgStoryPlaybackControls() {
        var playBtn = root.querySelector('[data-smm-ig-story-play]');
        var volumeBtn = root.querySelector('[data-smm-ig-story-volume]');
        var mediaEl = getIgStoryMediaEl();

        if (playBtn && playBtn.dataset.smmIgStoryPlayBound !== '1') {
            playBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggleIgStoryPlay();
            });
            playBtn.dataset.smmIgStoryPlayBound = '1';
        }

        if (volumeBtn && volumeBtn.dataset.smmIgStoryVolumeBound !== '1') {
            volumeBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggleIgStoryMute();
            });
            volumeBtn.dataset.smmIgStoryVolumeBound = '1';
        }

        if (mediaEl && mediaEl.dataset.smmIgStoryMediaBound !== '1') {
            mediaEl.addEventListener('click', function (event) {
                if (mediaEl.classList.contains('is-empty')) return;
                if (event.target.closest('button')) return;
                event.stopPropagation();
                toggleIgStoryPlay();
            });
            mediaEl.dataset.smmIgStoryMediaBound = '1';
        }
    }

    function buildStoryMediaHtml(url, isVideo, storyPlatform) {
        var platformKey = storyPlatform || 'instagram';
        if (isVideo) {
            return '<video class="smm-compose-preview-story-media" src="' + escapeHtml(url) + '" muted playsinline preload="metadata"></video>'
                + '<div class="smm-compose-preview-story-caption is-hidden" data-smm-preview-story-caption="' + platformKey + '" hidden aria-hidden="true"></div>';
        }
        return '<canvas class="smm-compose-preview-story-media" data-smm-story-canvas="' + platformKey + '"></canvas>';
    }

    function clearStoryPreviewImageCache() {
        storyPreviewImageCache = {};
    }

    function drawStoryCaptionOverlay(ctx, width, height, caption) {
        var text = (caption || '').trim();
        if (!text) return;

        var padding = Math.max(24, Math.round(width * 0.05));
        var barHeight = Math.max(Math.round(height * 0.24), 120);
        var barTop = height - barHeight;
        var gradient = ctx.createLinearGradient(0, barTop, 0, height);
        gradient.addColorStop(0, 'rgba(0,0,0,0)');
        gradient.addColorStop(0.35, 'rgba(0,0,0,0.45)');
        gradient.addColorStop(1, 'rgba(0,0,0,0.78)');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, barTop, width, barHeight);

        var fontSize = Math.max(22, Math.round(width / 26));
        ctx.fillStyle = '#ffffff';
        ctx.font = '600 ' + fontSize + 'px "Google Sans Flex", system-ui, sans-serif';
        ctx.textBaseline = 'top';
        var lines = wrapCanvasText(ctx, text.slice(0, STORY_CAPTION_CHAR_LIMIT), width - (padding * 2));
        var lineHeight = Math.round(fontSize * 1.35);
        var textBlockHeight = lines.length * lineHeight;
        var y = barTop + Math.max(padding, (barHeight - textBlockHeight) / 2);

        lines.forEach(function (line) {
            ctx.fillText(line, padding, y);
            y += lineHeight;
        });
    }

    function loadStoryPreviewImage(platform, file, url, callback) {
        var cacheKey = platform + ':' + (file.name || 'file') + ':' + file.size + ':' + file.lastModified;
        if (storyPreviewImageCache[cacheKey]) {
            callback(storyPreviewImageCache[cacheKey]);
            return;
        }

        var image = new Image();
        image.onload = function () {
            storyPreviewImageCache[cacheKey] = image;
            callback(image);
        };
        image.onerror = function () {
            callback(null);
        };
        image.src = url;
    }

    function refreshStoryPreviewBurn(platform) {
        if (getComposePostTypeForPlatform(platform) !== 'story') return;

        var key = platform === 'instagram' ? 'instagram-story' : 'facebook-story';
        var mediaEl = root.querySelector('[data-smm-preview-media="' + key + '"]');
        if (!mediaEl || mediaEl.classList.contains('is-empty')) return;

        var files = getFilesForPlatform(platform);
        if (!files.length) return;

        var file = files[0];
        var caption = getPlatformFullCaption(platform);
        var isVideo = (file.type || '').indexOf('video/') === 0;

        if (isVideo) {
            var storyCaptionEl = mediaEl.querySelector('[data-smm-preview-story-caption="' + platform + '"]');
            if (!storyCaptionEl) return;
            var showVideoCaption = caption !== '';
            storyCaptionEl.textContent = caption;
            storyCaptionEl.classList.toggle('is-hidden', !showVideoCaption);
            storyCaptionEl.hidden = !showVideoCaption;
            storyCaptionEl.setAttribute('aria-hidden', showVideoCaption ? 'false' : 'true');
            return;
        }

        var canvas = mediaEl.querySelector('canvas[data-smm-story-canvas="' + platform + '"]');
        if (!canvas) return;

        var previewUrl = canvas.getAttribute('data-smm-preview-url') || '';
        if (!previewUrl) return;

        loadStoryPreviewImage(platform, file, previewUrl, function (image) {
            if (!image) return;
            var width = image.naturalWidth || image.width || 1080;
            var height = image.naturalHeight || image.height || 1920;
            canvas.width = width;
            canvas.height = height;
            var ctx = canvas.getContext('2d');
            if (!ctx) return;
            ctx.clearRect(0, 0, width, height);
            ctx.drawImage(image, 0, 0, width, height);
            drawStoryCaptionOverlay(ctx, width, height, caption);
        });
    }

    function scheduleStoryPreviewBurn() {
        if (storyPreviewBurnTimer) {
            clearTimeout(storyPreviewBurnTimer);
        }
        storyPreviewBurnTimer = setTimeout(function () {
            ['instagram', 'facebook'].forEach(refreshStoryPreviewBurn);
        }, 40);
    }

    function updateInstagramStoryPreviewState(hasMedia, isVideo, durationSeconds) {
        var foot = root.querySelector('[data-smm-ig-story-foot]');
        var playback = root.querySelector('[data-smm-ig-story-playback]');
        var show = hasMedia;

        if (foot) {
            foot.classList.toggle('is-hidden', !show);
            foot.hidden = !show;
        }
        if (playback) {
            playback.classList.toggle('is-hidden', !show);
            playback.hidden = !show;
        }

        if (!show) {
            resetIgStoryPlayback();
            return;
        }

        initIgStoryPlayback(isVideo, durationSeconds);
        bindIgStoryPlaybackControls();
    }

    var fbStoryPlayback = {
        isPlaying: false,
        isMuted: true,
        isVideo: false,
        duration: 5,
        timer: null,
        tick: 0,
    };

    function getFbStoryMediaEl() {
        return root.querySelector('[data-smm-preview-media="facebook-story"]');
    }

    function getFbStoryVideo() {
        var mediaEl = getFbStoryMediaEl();
        if (!mediaEl) return null;
        var video = mediaEl.querySelector('video');
        return video instanceof HTMLVideoElement ? video : null;
    }

    function updateFbStoryScrubber(percent) {
        var fill = root.querySelector('[data-smm-fb-story-scrubber-fill]');
        var handle = root.querySelector('[data-smm-fb-story-scrubber-handle]');
        var pct = Math.max(0, Math.min(100, percent));
        if (fill) fill.style.width = pct + '%';
        if (handle) handle.style.left = pct + '%';
    }

    function updateFbStoryTimeDisplay(current, total) {
        var timeEl = root.querySelector('[data-smm-fb-story-playback-time]');
        if (!timeEl) return;
        timeEl.textContent = formatStoryDuration(current) + ' / ' + formatStoryDuration(total);
    }

    function updateFbStoryPlaybackUi() {
        var playBtn = root.querySelector('[data-smm-fb-story-play]');
        var volumeBtn = root.querySelector('[data-smm-fb-story-volume]');
        var playIcon = root.querySelector('[data-smm-fb-story-play-icon]');
        var pauseIcon = root.querySelector('[data-smm-fb-story-pause-icon]');
        var volumeOnIcon = root.querySelector('[data-smm-fb-story-volume-on]');
        var volumeOffIcon = root.querySelector('[data-smm-fb-story-volume-off]');

        if (playIcon) playIcon.hidden = fbStoryPlayback.isPlaying;
        if (pauseIcon) pauseIcon.hidden = !fbStoryPlayback.isPlaying;
        if (playBtn) {
            playBtn.setAttribute('aria-label', fbStoryPlayback.isPlaying ? 'Pause' : 'Play');
        }

        var hasAudio = fbStoryPlayback.isVideo;
        if (volumeBtn) {
            volumeBtn.classList.toggle('is-disabled', !hasAudio);
            volumeBtn.setAttribute('aria-label', fbStoryPlayback.isMuted ? 'Unmute' : 'Mute');
        }
        if (volumeOnIcon && volumeOffIcon) {
            volumeOnIcon.hidden = !hasAudio || fbStoryPlayback.isMuted;
            volumeOffIcon.hidden = hasAudio && !fbStoryPlayback.isMuted;
        }
    }

    function stopFbStoryImageTimer() {
        if (fbStoryPlayback.timer) {
            clearInterval(fbStoryPlayback.timer);
            fbStoryPlayback.timer = null;
        }
    }

    function startFbStoryImageTimer() {
        stopFbStoryImageTimer();
        var duration = fbStoryPlayback.duration || 5;
        fbStoryPlayback.timer = setInterval(function () {
            fbStoryPlayback.tick += 0.1;
            var pct = (fbStoryPlayback.tick / duration) * 100;
            updateFbStoryScrubber(pct);
            updateFbStoryTimeDisplay(fbStoryPlayback.tick, duration);
            if (fbStoryPlayback.tick >= duration) {
                stopFbStoryImageTimer();
                fbStoryPlayback.isPlaying = false;
                fbStoryPlayback.tick = 0;
                updateFbStoryScrubber(0);
                updateFbStoryTimeDisplay(0, duration);
                updateFbStoryPlaybackUi();
            }
        }, 100);
    }

    function toggleFbStoryPlay() {
        var mediaEl = getFbStoryMediaEl();
        if (!mediaEl || mediaEl.classList.contains('is-empty')) return;

        var video = getFbStoryVideo();
        if (video) {
            if (video.paused) {
                pauseAllComposePreviewVideos(video);
                video.play().catch(function () {});
            } else {
                video.pause();
            }
            return;
        }

        if (fbStoryPlayback.isPlaying) {
            stopFbStoryImageTimer();
            fbStoryPlayback.isPlaying = false;
        } else {
            fbStoryPlayback.isPlaying = true;
            startFbStoryImageTimer();
        }
        updateFbStoryPlaybackUi();
    }

    function toggleFbStoryMute() {
        var video = getFbStoryVideo();
        if (!video) return;
        video.muted = !video.muted;
        fbStoryPlayback.isMuted = video.muted;
        updateFbStoryPlaybackUi();
    }

    function resetFbStoryPlayback() {
        var video = getFbStoryVideo();
        if (video) {
            video.pause();
            video.muted = true;
            try {
                video.currentTime = 0;
            } catch (err) {
                /* ignore seek errors on unload */
            }
        }
        stopFbStoryImageTimer();
        fbStoryPlayback.isPlaying = false;
        fbStoryPlayback.isMuted = true;
        fbStoryPlayback.tick = 0;
        updateFbStoryScrubber(0);

        var mediaEl = getFbStoryMediaEl();
        if (mediaEl) mediaEl.classList.remove('is-playback-ready');
        updateFbStoryPlaybackUi();
    }

    function bindFbStoryVideoEvents(video) {
        video.muted = true;
        video.playsInline = true;
        video.preload = 'metadata';
        video.pause();

        video.addEventListener('loadedmetadata', function () {
            if (isFinite(video.duration) && video.duration > 0) {
                fbStoryPlayback.duration = video.duration;
                updateFbStoryTimeDisplay(0, video.duration);
            }
        });
        video.addEventListener('timeupdate', function () {
            var duration = video.duration || fbStoryPlayback.duration || 5;
            var pct = duration > 0 ? (video.currentTime / duration) * 100 : 0;
            updateFbStoryScrubber(pct);
            updateFbStoryTimeDisplay(video.currentTime, duration);
        });
        video.addEventListener('play', function () {
            fbStoryPlayback.isPlaying = true;
            updateFbStoryPlaybackUi();
        });
        video.addEventListener('pause', function () {
            fbStoryPlayback.isPlaying = false;
            updateFbStoryPlaybackUi();
        });
        video.addEventListener('ended', function () {
            fbStoryPlayback.isPlaying = false;
            updateFbStoryScrubber(100);
            updateFbStoryPlaybackUi();
        });
        video.addEventListener('volumechange', function () {
            fbStoryPlayback.isMuted = video.muted;
            updateFbStoryPlaybackUi();
        });
    }

    function initFbStoryPlayback(isVideo, durationSeconds) {
        resetFbStoryPlayback();
        fbStoryPlayback.isVideo = isVideo;
        fbStoryPlayback.duration = isVideo && durationSeconds ? durationSeconds : 5;

        var mediaEl = getFbStoryMediaEl();
        if (mediaEl) mediaEl.classList.add('is-playback-ready');

        var video = getFbStoryVideo();
        if (video) {
            bindFbStoryVideoEvents(video);
            if (isFinite(video.duration) && video.duration > 0) {
                fbStoryPlayback.duration = video.duration;
            }
        }
        updateFbStoryTimeDisplay(0, fbStoryPlayback.duration);
        updateFbStoryPlaybackUi();
    }

    function bindFbStoryPlaybackControls() {
        var playBtn = root.querySelector('[data-smm-fb-story-play]');
        var volumeBtn = root.querySelector('[data-smm-fb-story-volume]');
        var mediaEl = getFbStoryMediaEl();

        if (playBtn && playBtn.dataset.smmFbStoryPlayBound !== '1') {
            playBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggleFbStoryPlay();
            });
            playBtn.dataset.smmFbStoryPlayBound = '1';
        }

        if (volumeBtn && volumeBtn.dataset.smmFbStoryVolumeBound !== '1') {
            volumeBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggleFbStoryMute();
            });
            volumeBtn.dataset.smmFbStoryVolumeBound = '1';
        }

        if (mediaEl && mediaEl.dataset.smmFbStoryMediaBound !== '1') {
            mediaEl.addEventListener('click', function (event) {
                if (mediaEl.classList.contains('is-empty')) return;
                if (event.target.closest('button')) return;
                event.stopPropagation();
                toggleFbStoryPlay();
            });
            mediaEl.dataset.smmFbStoryMediaBound = '1';
        }
    }

    function updateFacebookStoryPreviewState(hasMedia, isVideo, durationSeconds) {
        var foot = root.querySelector('[data-smm-fb-story-foot]');
        var playback = root.querySelector('[data-smm-fb-story-playback]');
        var show = hasMedia;

        if (foot) {
            foot.classList.toggle('is-hidden', !show);
            foot.hidden = !show;
        }
        if (playback) {
            playback.classList.toggle('is-hidden', !show);
            playback.hidden = !show;
        }

        if (!show) {
            resetFbStoryPlayback();
            return;
        }

        initFbStoryPlayback(isVideo, durationSeconds);
        bindFbStoryPlaybackControls();
    }

    function createReelPlaybackController(config) {
        var prefix = config.prefix;
        var mediaKey = config.mediaKey;
        var defaultMuted = config.defaultMuted !== false;
        var autoPlay = config.autoPlay === true;
        var state = {
            isPlaying: false,
            isMuted: defaultMuted,
            isVideo: false,
            duration: 5,
        };

        function q(name) {
            return root.querySelector('[data-smm-' + prefix + '-' + name + ']');
        }

        function getMediaEl() {
            return root.querySelector('[data-smm-preview-media="' + mediaKey + '"]');
        }

        function getVideo() {
            var mediaEl = getMediaEl();
            if (!mediaEl) return null;
            var video = mediaEl.querySelector('video');
            return video instanceof HTMLVideoElement ? video : null;
        }

        function updateScrubber(percent) {
            var fill = q('scrubber-fill');
            var handle = q('scrubber-handle');
            var pct = Math.max(0, Math.min(100, percent));
            if (fill) fill.style.width = pct + '%';
            if (handle) handle.style.left = pct + '%';
        }

        function updateTimeDisplay(current, total) {
            var timeEl = q('playback-time');
            if (!timeEl) return;
            timeEl.textContent = formatStoryDuration(current) + ' / ' + formatStoryDuration(total);
        }

        function updatePlaybackUi() {
            var playBtn = q('play');
            var volumeBtn = q('volume');
            var playIcon = q('play-icon');
            var pauseIcon = q('pause-icon');
            var volumeOnIcon = q('volume-on');
            var volumeOffIcon = q('volume-off');

            if (playIcon) playIcon.hidden = state.isPlaying;
            if (pauseIcon) pauseIcon.hidden = !state.isPlaying;
            if (playBtn) playBtn.setAttribute('aria-label', state.isPlaying ? 'Pause' : 'Play');

            var hasAudio = state.isVideo;
            if (volumeBtn) {
                volumeBtn.classList.toggle('is-disabled', !hasAudio);
                volumeBtn.setAttribute('aria-label', state.isMuted ? 'Unmute' : 'Mute');
            }
            if (volumeOnIcon && volumeOffIcon) {
                volumeOnIcon.hidden = !hasAudio || state.isMuted;
                volumeOffIcon.hidden = hasAudio && !state.isMuted;
            }
        }

        function reset() {
            var video = getVideo();
            if (video) {
                video.pause();
                video.currentTime = 0;
                video.muted = defaultMuted;
            }
            state.isPlaying = false;
            state.isMuted = defaultMuted;
            updateScrubber(0);
            updateTimeDisplay(0, state.duration || 5);
            updatePlaybackUi();
        }

        function togglePlay() {
            var mediaEl = getMediaEl();
            if (!mediaEl || mediaEl.classList.contains('is-empty')) return;

            var video = getVideo();
            if (!video) return;

            if (video.paused) {
                pauseAllComposePreviewVideos(video);
                video.play().catch(function () {});
            } else {
                video.pause();
            }
        }

        function toggleMute() {
            var video = getVideo();
            if (!video) return;
            video.muted = !video.muted;
            state.isMuted = video.muted;
            updatePlaybackUi();
        }

        function bindVideoEvents(video) {
            video.preload = 'metadata';
            video.muted = state.isMuted;
            video.addEventListener('loadedmetadata', function () {
                if (isFinite(video.duration) && video.duration > 0) {
                    state.duration = video.duration;
                    updateTimeDisplay(0, video.duration);
                }
            });
            video.addEventListener('timeupdate', function () {
                var duration = video.duration || state.duration || 5;
                var pct = duration > 0 ? (video.currentTime / duration) * 100 : 0;
                updateScrubber(pct);
                updateTimeDisplay(video.currentTime, duration);
            });
            video.addEventListener('play', function () {
                state.isPlaying = true;
                updatePlaybackUi();
            });
            video.addEventListener('pause', function () {
                state.isPlaying = false;
                updatePlaybackUi();
            });
            video.addEventListener('ended', function () {
                state.isPlaying = false;
                updateScrubber(100);
                updatePlaybackUi();
            });
            video.addEventListener('volumechange', function () {
                state.isMuted = video.muted;
                updatePlaybackUi();
            });
        }

        function init(isVideo, durationSeconds) {
            reset();
            state.isVideo = isVideo;
            state.duration = isVideo && durationSeconds ? durationSeconds : 5;

            var mediaEl = getMediaEl();
            if (mediaEl) mediaEl.classList.add('is-playback-ready');

            var video = getVideo();
            if (video) {
                bindVideoEvents(video);
                if (isFinite(video.duration) && video.duration > 0) {
                    state.duration = video.duration;
                }
                if (autoPlay && isVideo) {
                    pauseAllComposePreviewVideos(video);
                    video.play().catch(function () {
                        video.muted = true;
                        state.isMuted = true;
                        updatePlaybackUi();
                        video.play().catch(function () {});
                    });
                }
            }
            updateTimeDisplay(0, state.duration);
            updatePlaybackUi();
        }

        function bindControls() {
            var playBtn = q('play');
            var volumeBtn = q('volume');
            var mediaEl = getMediaEl();

            if (playBtn && playBtn.dataset.smmReelPlayBound !== prefix) {
                playBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    togglePlay();
                });
                playBtn.dataset.smmReelPlayBound = prefix;
            }

            if (volumeBtn && volumeBtn.dataset.smmReelVolumeBound !== prefix) {
                volumeBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    toggleMute();
                });
                volumeBtn.dataset.smmReelVolumeBound = prefix;
            }

            if (mediaEl && mediaEl.dataset.smmReelMediaBound !== prefix) {
                mediaEl.addEventListener('click', function (event) {
                    if (mediaEl.classList.contains('is-empty')) return;
                    if (event.target.closest('button')) return;
                    event.stopPropagation();
                    togglePlay();
                });
                mediaEl.dataset.smmReelMediaBound = prefix;
            }
        }

        function updatePreviewState(hasMedia, isVideo, durationSeconds) {
            var playback = root.querySelector('[data-smm-' + prefix + '-playback]');
            var show = hasMedia;

            if (playback) {
                playback.classList.toggle('is-hidden', !show);
                playback.hidden = !show;
            }

            if (!show) {
                reset();
                var mediaEl = getMediaEl();
                if (mediaEl) mediaEl.classList.remove('is-playback-ready');
                return;
            }

            init(isVideo, durationSeconds);
            bindControls();
        }

        return {
            reset: reset,
            updatePreviewState: updatePreviewState,
        };
    }

    var igReelPlayback = createReelPlaybackController({
        prefix: 'ig-reel',
        mediaKey: 'instagram-reel',
        defaultMuted: false,
        autoPlay: true,
    });

    var fbReelPlayback = createReelPlaybackController({
        prefix: 'fb-reel',
        mediaKey: 'facebook-reel',
        defaultMuted: false,
        autoPlay: true,
    });

    var tiktokPlayback = createReelPlaybackController({
        prefix: 'tiktok',
        mediaKey: 'tiktok',
        defaultMuted: false,
        autoPlay: true,
    });

    var fbPostPlayback = createReelPlaybackController({
        prefix: 'fb-post',
        mediaKey: 'facebook',
        defaultMuted: true,
        autoPlay: false,
    });

    function previewMediaKeyToPlatform(key) {
        if (key === 'facebook' || key.indexOf('facebook-') === 0) return 'facebook';
        if (key === 'instagram' || key.indexOf('instagram-') === 0) return 'instagram';
        if (key === 'tiktok' || key.indexOf('tiktok-') === 0) return 'tiktok';
        return '';
    }

    function previewMediaKeyToFormat(key) {
        if (key.indexOf('-story') !== -1) return 'story';
        if (key.indexOf('-reel') !== -1) return 'reel';
        return 'post';
    }

    function isActivePreviewMediaKey(key) {
        var platform = previewMediaKeyToPlatform(key);
        if (!platform || platform !== activeComposePlatform) return false;
        return getEffectivePreviewFormat(platform) === previewMediaKeyToFormat(key);
    }

    function pauseAllComposePreviewVideos(exceptVideo) {
        if (!root) return;
        root.querySelectorAll('video').forEach(function (video) {
            if (exceptVideo && video === exceptVideo) return;
            if (!video.paused) video.pause();
        });
        if (mediaGrid) {
            mediaGrid.querySelectorAll('video').forEach(function (video) {
                if (exceptVideo && video === exceptVideo) return;
                if (!video.paused) video.pause();
            });
        }
    }

    function clearPreviewStateForKey(key) {
        if (key === 'instagram-story') {
            updateInstagramStoryPreviewState(false, false);
            return;
        }
        if (key === 'facebook-story') {
            updateFacebookStoryPreviewState(false, false);
            return;
        }
        if (key === 'instagram-reel') {
            igReelPlayback.updatePreviewState(false, false);
            return;
        }
        if (key === 'facebook-reel') {
            fbReelPlayback.updatePreviewState(false, false);
            return;
        }
        if (key === 'tiktok') {
            tiktokPlayback.updatePreviewState(false, false);
            return;
        }
        if (key === 'facebook') {
            fbPostPlayback.updatePreviewState(false, false);
        }
    }

    function buildPostMediaHtml(url, isVideo) {
        if (isVideo) {
            return '<video class="smm-compose-preview-post-media" src="' + escapeHtml(url) + '" muted playsinline preload="metadata"></video>';
        }
        return '<img src="' + escapeHtml(url) + '" alt="Preview">';
    }

    function previewShouldUseNativeAspect(platform, previewFormat, isVideo) {
        if (!isVideo) return false;
        var meta = getVideoMetadataForPlatform(platform);
        if (!meta || !meta.loaded) return false;

        if (previewFormat === 'story' || previewFormat === 'reel') {
            return !isVertical916Ratio(meta.aspectRatio);
        }
        if (previewFormat === 'post' && platform === 'facebook') {
            return !isFacebookPostAspectValid(meta.width, meta.height);
        }
        if (platform === 'tiktok') {
            // TikTok letterboxes anything that is not vertical.
            return !isVertical916Ratio(meta.aspectRatio);
        }
        return false;
    }

    function applyPreviewAspectMode(mediaEl, platform, key, isVideo) {
        var format = previewMediaKeyToFormat(key);
        if (key === 'facebook' || key === 'instagram') {
            format = 'post';
        }
        mediaEl.classList.toggle('is-native-aspect', previewShouldUseNativeAspect(platform, format, isVideo));
    }

    function bindStoryVideoDuration(videoEl, platform) {
        if (!(videoEl instanceof HTMLVideoElement)) return;
        var state = platform === 'facebook' ? fbStoryPlayback : igStoryPlayback;
        var updateTime = platform === 'facebook' ? updateFbStoryTimeDisplay : updateIgStoryTimeDisplay;
        videoEl.addEventListener('loadedmetadata', function () {
            var duration = videoEl.duration;
            if (!isFinite(duration) || duration <= 0) return;
            state.duration = duration;
            updateTime(videoEl.currentTime || 0, duration);
        });
    }

    function buildReelMediaHtml(url, isVideo, unmuted) {
        if (isVideo) {
            var mutedAttr = unmuted ? '' : ' muted';
            return '<video class="smm-compose-preview-reel-media" src="' + escapeHtml(url) + '" playsinline preload="metadata"' + mutedAttr + '></video>';
        }
        return '<img class="smm-compose-preview-reel-media" src="' + escapeHtml(url) + '" alt="Preview">';
    }

    function renderPreviewMedia(skipRevoke) {
        var mediaEls = root.querySelectorAll('[data-smm-preview-media]');
        if (!mediaEls.length) return;

        pauseAllComposePreviewVideos();

        if (!skipRevoke) {
            revokePreviewUrls();
        }

        var emptyHtml = '<span class="font-sans">Your photo shows here</span>';
        var emptyVideoHtml = '<span class="font-sans">Your video shows here</span>';
        var activeIgPostHasVideo = false;

        mediaEls.forEach(function (mediaEl) {
            var key = mediaEl.getAttribute('data-smm-preview-media') || '';
            var platform = previewMediaKeyToPlatform(key);
            var platformFiles = platform ? getFilesForPlatform(platform) : [];
            var isReelKey = key.indexOf('reel') !== -1;
            var emptyMessage = (isReelKey || key === 'tiktok') ? emptyVideoHtml : emptyHtml;

            if (platformFiles.length === 0 || !isActivePreviewMediaKey(key)) {
                if (key === 'instagram') {
                    renderInstagramPostPreview([]);
                    clearPreviewStateForKey(key);
                    return;
                }
                mediaEl.classList.add('is-empty');
                mediaEl.classList.remove('is-native-aspect');
                mediaEl.innerHTML = emptyMessage;
                clearPreviewStateForKey(key);
            return;
        }

            if (key === 'instagram') {
                mediaEl.classList.remove('is-empty');
                if (renderInstagramPostPreview(platformFiles)) {
                    activeIgPostHasVideo = true;
                }
                return;
            }

            var file = platformFiles[0];
                var url = URL.createObjectURL(file);
                previewObjectUrls.push(url);
                var isVideo = (file.type || '').indexOf('video/') === 0;
            var meta = getVideoMetadataForPlatform(platform);
            var durationSeconds = meta && meta.loaded ? meta.duration : null;
            var reelMediaHtml = buildReelMediaHtml(url, isVideo, true);

            mediaEl.classList.remove('is-empty');

            if (key === 'tiktok') {
                mediaEl.innerHTML = reelMediaHtml;
                applyPreviewAspectMode(mediaEl, platform, key, isVideo);
                tiktokPlayback.updatePreviewState(true, isVideo, durationSeconds);
            return;
        }
            if (key === 'facebook') {
                mediaEl.innerHTML = buildPostMediaHtml(url, isVideo);
                applyPreviewAspectMode(mediaEl, platform, key, isVideo);
                fbPostPlayback.updatePreviewState(isVideo, isVideo, durationSeconds);
            return;
        }
            if (key === 'instagram-story' || key === 'facebook-story') {
                var storyPlatform = key === 'instagram-story' ? 'instagram' : 'facebook';
                mediaEl.innerHTML = buildStoryMediaHtml(url, isVideo, storyPlatform);
                if (!isVideo) {
                    var storyCanvas = mediaEl.querySelector('canvas[data-smm-story-canvas="' + storyPlatform + '"]');
                    if (storyCanvas) {
                        storyCanvas.setAttribute('data-smm-preview-url', url);
                        refreshStoryPreviewBurn(storyPlatform);
                    }
                }
                applyPreviewAspectMode(mediaEl, platform, key, isVideo);
                if (key === 'instagram-story') {
                    var igStoryVideo = mediaEl.querySelector('video');
                    if (igStoryVideo) bindStoryVideoDuration(igStoryVideo, 'instagram');
                    updateInstagramStoryPreviewState(true, isVideo, durationSeconds);
                }
                if (key === 'facebook-story') {
                    var fbStoryVideo = mediaEl.querySelector('video');
                    if (fbStoryVideo) bindStoryVideoDuration(fbStoryVideo, 'facebook');
                    updateFacebookStoryPreviewState(true, isVideo, durationSeconds);
                }
            return;
        }
            if (key === 'instagram-reel') {
                mediaEl.innerHTML = reelMediaHtml;
                applyPreviewAspectMode(mediaEl, platform, key, isVideo);
                igReelPlayback.updatePreviewState(true, isVideo, durationSeconds);
                return;
            }
            if (key === 'facebook-reel') {
                mediaEl.innerHTML = reelMediaHtml;
                applyPreviewAspectMode(mediaEl, platform, key, isVideo);
                fbReelPlayback.updatePreviewState(true, isVideo, durationSeconds);
            }
        });

        if (!activeIgPostHasVideo) {
            resetInstagramTags();
        }

        renderInstagramTags();
        updateIgTagHintState();
        bindInstagramTagSurface();
        updatePreviewCaptions();
    }

    /**
     * Which thumbnails get the red error dot: the unsupported files for TikTok,
     * the video for Meta's aspect and duration rules.
     */
    function composeMediaErrorIndexes(errors) {
        var flagged = {};
        if (!errors.length) return flagged;

        if (activeComposePlatform === 'tiktok') {
            var keepIndex = getFirstVideoIndexForPlatform('tiktok');
            var hasDurationError = errors.some(function (error) {
                return error.id === 'tiktok-duration';
            });
            selectedFiles.forEach(function (file, index) {
                if (index !== keepIndex || hasDurationError) {
                    flagged[index] = true;
                }
            });
            return flagged;
        }

        selectedFiles.forEach(function (file, index) {
            if ((file.type || '').indexOf('video/') === 0) {
                flagged[index] = true;
            }
        });
        return flagged;
    }

    function renderMediaGrid() {
        if (!mediaGrid) return;

        revokePreviewUrls();
        ensureVideoMetadataLoaded();

        if (selectedFiles.length === 0) {
            selectedFileMetadata = [];
            selectedOriginalFiles = [];
            selectedOriginalFileMetadata = [];
            mediaGrid.innerHTML = '';
            mediaGrid.classList.add('is-empty');
            renderPreviewMedia(true);
            updateComposePreviewPanels();
            return;
        }

        mediaGrid.classList.remove('is-empty');
        var activeErrors = getComposeErrorsForPlatform(activeComposePlatform);
        var flaggedMediaIndexes = composeMediaErrorIndexes(activeErrors);

        mediaGrid.innerHTML = selectedFiles.map(function (file, index) {
            var url = URL.createObjectURL(file);
            previewObjectUrls.push(url);
            var isVideo = (file.type || '').indexOf('video/') === 0;
            var media = isVideo
                ? '<video src="' + escapeHtml(url) + '" muted playsinline></video>'
                : '<img src="' + escapeHtml(url) + '" alt="">';
            var meta = selectedFileMetadata[index];
            var badgeText = 'Video';
            if (isVideo && meta && meta.loaded) {
                badgeText = formatStoryDuration(meta.duration);
            }
            var badge = isVideo ? '<span class="smm-media-badge font-sans">' + escapeHtml(badgeText) + '</span>' : '';
            var hasItemError = flaggedMediaIndexes[index] === true;
            var errorBadge = hasItemError
                ? '<span class="smm-media-error-badge" aria-hidden="true"><svg width="10" height="10" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M12 8v5M12 16h.01" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg></span>'
                : '';
            var editBtn = isVideo
                ? '<button type="button" class="smm-media-edit font-sans" data-smm-video-edit-open="' + index + '" aria-label="Edit video">' +
                    '<img class="smm-media-edit-icon" src="' + escapeHtml((config.appUrl || '') + '/assets/images/social-media/edit-media-pencil-icon.png?v=1') + '" alt="" width="14" height="14" loading="lazy" decoding="async" aria-hidden="true">' +
                    '<span>Edit video</span>' +
                    '</button>'
                : '';
            return (
                '<div class="smm-media-item' + (hasItemError ? ' has-error' : '') + '">' +
                media +
                badge +
                errorBadge +
                editBtn +
                '<button type="button" class="smm-media-remove" data-smm-remove-media="' + index + '" aria-label="Remove media">&times;</button>' +
                '</div>'
            );
        }).join('');

        mediaGrid.querySelectorAll('[data-smm-remove-media]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var removeIndex = parseInt(btn.getAttribute('data-smm-remove-media') || '-1', 10);
                if (removeIndex < 0) return;
                selectedFiles.splice(removeIndex, 1);
                selectedFileMetadata.splice(removeIndex, 1);
                selectedOriginalFiles.splice(removeIndex, 1);
                selectedOriginalFileMetadata.splice(removeIndex, 1);
                syncMediaInput();
                renderMediaGrid();
            });
        });

        mediaGrid.querySelectorAll('[data-smm-video-edit-open]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var editIndex = parseInt(btn.getAttribute('data-smm-video-edit-open') || '-1', 10);
                if (editIndex < 0) return;
                openVideoEditChooser(editIndex);
            });
        });

        renderPreviewMedia(true);
        updateComposePreviewPanels();
        updateComposeErrors();
    }

    function addMediaFiles(fileList) {
        if (!fileList) return;

        var addedImage = false;

        Array.prototype.slice.call(fileList).forEach(function (file) {
            if (selectedFiles.length >= 10) return;
            selectedFiles.push(file);
            var isVideo = (file.type || '').indexOf('video/') === 0;
            if (!isVideo) addedImage = true;
            selectedOriginalFiles.push(isVideo ? file : null);
            selectedOriginalFileMetadata.push(null);
        });

        syncMediaInput();
        renderMediaGrid();

        if (addedImage && activeComposePlatform === 'tiktok') {
            warnTikTokImageUnsupported();
        }
    }

    function warnTikTokImageUnsupported() {
        if (!window.LeadBirdModal || typeof window.LeadBirdModal.alert !== 'function') return;

        window.LeadBirdModal.alert({
            title: 'Images aren\'t supported on TikTok',
            message: 'You can only publish videos to TikTok. Press the × on the photo to remove it, then keep your video.',
            tone: 'error',
        });
    }

    function resetMediaSelection() {
        selectedFiles = [];
        selectedFileMetadata = [];
        selectedOriginalFiles = [];
        selectedOriginalFileMetadata = [];
        if (activeComposePlatform) {
            ensurePlatformContent(activeComposePlatform);
            composePlatformContent[activeComposePlatform].files = [];
            composePlatformContent[activeComposePlatform].fileMetadata = [];
            composePlatformContent[activeComposePlatform].originalFiles = [];
            composePlatformContent[activeComposePlatform].originalFileMetadata = [];
        }
        syncMediaInput();
        renderMediaGrid();
    }

    function getComposeCharLimit() {
        var postType = getComposePostTypeForPlatform(activeComposePlatform || 'facebook');
        if (postType === 'story') {
            return STORY_CAPTION_CHAR_LIMIT;
        }
        if (activeComposePlatform === 'instagram' || activeComposePlatform === 'tiktok') {
            return 2200;
        }
        return 63206;
    }

    function updateComposeCharCount() {
        var countEl = root.querySelector('[data-smm-char-count]');
        if (!countEl || !captionEl) {
            return;
        }

        var length = captionEl.value.length;
        var limit = getComposeCharLimit();

        if (limit === 0) {
            countEl.textContent = '0 / 0';
            countEl.classList.remove('is-over-limit');
            return;
        }

        countEl.textContent = length.toLocaleString() + ' / ' + limit.toLocaleString();
        countEl.classList.toggle('is-over-limit', length > limit);
    }

    function updatePreviewCaptions() {
        persistActivePlatformContent();

        var fbFull = getPlatformFullCaption('facebook');
        var fbCaption = root.querySelector('[data-smm-preview-caption="facebook"]');
        if (fbCaption) {
            var showFbCaption = fbFull !== '';
            fbCaption.textContent = fbFull;
            fbCaption.classList.toggle('is-hidden', !showFbCaption);
            fbCaption.hidden = !showFbCaption;
        }

        var igAccount = getPrimaryAccountForPlatform('instagram');
        var igUsername = igAccount ? igAccount.displayName : 'your.account';
        var igUsernameLine = root.querySelector('[data-smm-preview-ig-username-line]');
        var igCaptionBody = root.querySelector('[data-smm-preview-ig-caption-body]');
        if (igUsernameLine) igUsernameLine.textContent = igUsername;

        var igFull = getPlatformFullCaption('instagram');
        if (igCaptionBody) {
            var showCaption = igFull !== '';
            igCaptionBody.textContent = igFull;
            igCaptionBody.classList.toggle('is-hidden', !showCaption);
            igCaptionBody.hidden = !showCaption;
        }

        var tiktokAccount = getPrimaryAccountForPlatform('tiktok');
        var tiktokName = tiktokAccount ? tiktokAccount.displayName.replace(/^@/, '') : 'your.account';
        var tiktokNameEl = root.querySelector('[data-smm-preview-account-name="tiktok"]');
        if (tiktokNameEl) tiktokNameEl.textContent = tiktokName;

        var tiktokCaptionEl = root.querySelector('[data-smm-preview-caption="tiktok"]');
        if (tiktokCaptionEl) {
            var tiktokFull = getPlatformFullCaption('tiktok');
            tiktokCaptionEl.textContent = tiktokFull;
            tiktokCaptionEl.classList.toggle('is-hidden', tiktokFull === '');
            tiktokCaptionEl.hidden = tiktokFull === '';
        }

        ['instagram', 'facebook'].forEach(function (platform) {
            if (getComposePostTypeForPlatform(platform) !== 'story') {
                var storyCaptionEl = root.querySelector('[data-smm-preview-story-caption="' + platform + '"]');
                if (storyCaptionEl) {
                    storyCaptionEl.textContent = '';
                    storyCaptionEl.classList.add('is-hidden');
                    storyCaptionEl.hidden = true;
                }
            }
        });

        scheduleStoryPreviewBurn();
        updateComposeCharCount();
        if (activeComposePlatform === 'tiktok') {
            updateComposeErrors();
        }
    }

    function updatePreview() {
        updatePreviewCaptions();
        renderPreviewMedia();
    }

    function currentPublishMode() {
        var draft = root.querySelector('[data-smm-mode="draft"]');
        var schedule = root.querySelector('[data-smm-mode="schedule"]');
        if (draft && draft.checked) return 'draft';
        if (schedule && schedule.checked) return 'schedule';
        return 'now';
    }

    function updateSubmitLabel() {
        updateScheduleUi();
    }

    function bindPlannerViews() {
        document.querySelectorAll('[data-smm-view]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showView(btn.getAttribute('data-smm-view') || 'calendar');
            });
        });

        document.querySelectorAll('[data-smm-open-compose]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openComposeModal();
            });
        });
    }

    function bindCalendarNav() {
        var prevBtn = root.querySelector('[data-smm-cal-prev]');
        var nextBtn = root.querySelector('[data-smm-cal-next]');
        var todayBtn = root.querySelector('[data-smm-cal-today]');

        if (prevBtn) prevBtn.addEventListener('click', function () { navigateCalendar(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { navigateCalendar(1); });
        if (todayBtn) todayBtn.addEventListener('click', goToToday);
    }

    function bindCalendarViewToggle() {
        root.querySelectorAll('[data-smm-cal-view]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                calendarViewMode = btn.getAttribute('data-smm-cal-view') || 'month';
                root.querySelectorAll('[data-smm-cal-view]').forEach(function (b) {
                    var active = b === btn;
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                if (volumeEl) {
                    volumeEl.classList.toggle('is-hidden', calendarViewMode !== 'list');
                }
                if (calendarViewMode === 'list') {
                    closeDayPanel();
                }
                if (calendarViewMode === 'week' || calendarViewMode === 'list') {
                    ensureCalendarData();
                } else {
                    renderCalendar();
                }
            });
        });
    }

    function bindComposeModal() {
        root.querySelectorAll('[data-smm-compose-close]').forEach(function (btn) {
            btn.addEventListener('click', closeComposeModal);
        });

        var wisdomToggle = root.querySelector('[data-smm-wisdom-toggle]');
        if (wisdomToggle) {
            wisdomToggle.addEventListener('click', function () {
                toggleWisdomPanel();
            });
        }

        var wisdomClose = root.querySelector('[data-smm-wisdom-close]');
        if (wisdomClose) {
            wisdomClose.addEventListener('click', function () {
                toggleWisdomPanel(false);
            });
        }

        var wisdomRefresh = root.querySelector('[data-smm-wisdom-refresh]');
        if (wisdomRefresh) {
            wisdomRefresh.addEventListener('click', function () {
                if (wisdomAskInput) wisdomAskInput.value = '';
            });
        }

        if (accountTrigger && accountMenu && accountPicker) {
            accountTrigger.addEventListener('click', function (event) {
                event.stopPropagation();
                var open = accountPicker.classList.toggle('is-open');
                accountMenu.hidden = !open;
                accountTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        root.querySelectorAll('[data-smm-compose-account]').forEach(function (input) {
            input.addEventListener('change', updateAccountPickerUi);
        });

        if (clearAccountsBtn) {
            clearAccountsBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                root.querySelectorAll('[data-smm-compose-account]:checked').forEach(function (input) {
                    input.checked = false;
                });
                updateAccountPickerUi();
            });
        }

        if (postTypeTrigger && postTypeMenu) {
            postTypeTrigger.addEventListener('click', function (event) {
                event.stopPropagation();
                var open = postTypeMenu.classList.toggle('is-hidden');
                postTypeMenu.hidden = open;
                postTypeTrigger.setAttribute('aria-expanded', open ? 'false' : 'true');
            });
            postTypeMenu.querySelectorAll('[data-smm-post-type]').forEach(function (btn) {
                btn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    setActiveComposePostType(btn.getAttribute('data-smm-post-type') || 'post');
                });
            });
        }


        if (scheduleDateTrigger && scheduleDateInput) {
            scheduleDateTrigger.addEventListener('click', function () {
                if (typeof scheduleDateInput.showPicker === 'function') {
                    scheduleDateInput.showPicker();
                } else {
                    scheduleDateInput.click();
                }
            });
            scheduleDateInput.addEventListener('change', function () {
                if (!scheduleDateInput.value) return;
                var current = buildScheduleDateFromFields() || getDefaultScheduleDate();
                var parts = scheduleDateInput.value.split('-');
                current.setFullYear(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                if (scheduleDateLabel) {
                    scheduleDateLabel.textContent = formatScheduleDateLabel(current);
                }
            });
        }

        if (scheduleDoneBtn) {
            scheduleDoneBtn.addEventListener('click', function () {
                applyScheduleSelection();
            });
        }

        if (scheduleRecommendedToggle && scheduleRecommendedPanel) {
            scheduleRecommendedToggle.addEventListener('click', function () {
                var open = scheduleRecommendedPanel.classList.toggle('is-hidden');
                scheduleRecommendedPanel.hidden = open;
                scheduleRecommendedToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
            });
        }

        if (scheduleLaterBtn) {
            scheduleLaterBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                var iconEl = scheduleLaterBtn.querySelector('[data-smm-schedule-later-icon]');
                var clickedIcon = iconEl && iconEl.contains(event.target);
                var hasSchedule = scheduleLaterBtn.classList.contains('has-schedule');
                var modalOpen = scheduleModal && !scheduleModal.classList.contains('is-hidden');

                if (clickedIcon) {
                    if (modalOpen) {
                        closeScheduleModal();
                        return;
                    }
                    if (hasSchedule) {
                        clearScheduleSelection();
                        return;
                    }
                }

                if (modalOpen) {
                    closeScheduleModal();
                    return;
                }

                setPublishMode('schedule');
                openScheduleModal();
            });
        }

        root.querySelectorAll('[data-smm-save-draft]').forEach(function (saveDraftBtn) {
            saveDraftBtn.addEventListener('click', function () {
                closeSubmitMenu();
                setPublishMode('draft');
                submitMenuBypass = true;
                if (submitBtn) submitBtn.click();
            });
        });

        var submitMenuPost = root.querySelector('[data-smm-submit-menu-post]');
        if (submitMenuPost) {
            submitMenuPost.addEventListener('click', function (event) {
                event.stopPropagation();
                closeSubmitMenu();
                setPublishMode('now');
                submitMenuBypass = true;
                if (submitBtn) submitBtn.click();
            });
        }

        window.addEventListener('resize', function () {
            closeSubmitMenu();
            syncSubmitMenuAffordance();
        });

        var triggerMedia = root.querySelector('[data-smm-trigger-media]');
        if (triggerMedia && mediaInput) {
            triggerMedia.addEventListener('click', function () {
                mediaInput.click();
            });
        }

        initEmojiPicker();

        var wisdomSend = root.querySelector('[data-smm-wisdom-send]');
        if (wisdomSend) {
            wisdomSend.addEventListener('click', function () {
                if (!wisdomAskInput || !aiPromptEl) return;
                var question = wisdomAskInput.value.trim();
                if (!question) return;
                aiPromptEl.value = question;
                runGenerate('caption', wisdomSend);
            });
        }

        document.addEventListener('click', function (event) {
            if (!composeModal || composeModal.classList.contains('is-hidden')) return;
            if (accountPicker && !accountPicker.contains(event.target)) {
                accountPicker.classList.remove('is-open');
                if (accountMenu) accountMenu.hidden = true;
                if (accountTrigger) accountTrigger.setAttribute('aria-expanded', 'false');
            }
            if (scheduleModal && !scheduleModal.classList.contains('is-hidden')) {
                var dialog = scheduleModal.querySelector('.smm-schedule-modal-dialog');
                if (
                    dialog
                    && !dialog.contains(event.target)
                    && !(scheduleLaterBtn && scheduleLaterBtn.contains(event.target))
                ) {
                    closeScheduleModal();
                }
            }
            if (postTypeMenu && postTypeTrigger && !postTypeMenu.contains(event.target) && !postTypeTrigger.contains(event.target)) {
                closePostTypeMenu();
            }
            if (submitSplit && submitMenu && !submitSplit.contains(event.target)) {
                closeSubmitMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && composeModal && !composeModal.classList.contains('is-hidden')) {
                if (submitMenu && !submitMenu.classList.contains('is-hidden')) {
                    closeSubmitMenu();
                    return;
                }
                if (scheduleModal && !scheduleModal.classList.contains('is-hidden')) {
                    closeScheduleModal();
                    return;
                }
                closeComposeModal();
            }
        });
    }

    function bindDayPanel() {
        var closeBtn = root.querySelector('[data-smm-day-panel-close]');
        if (closeBtn) closeBtn.addEventListener('click', closeDayPanel);

        var addBtn = root.querySelector('[data-smm-day-add]');
        var menu = root.querySelector('[data-smm-day-menu]');
        if (addBtn && menu) {
            addBtn.addEventListener('click', function () {
                menu.classList.toggle('is-hidden');
            });
        }

        var createPostBtn = root.querySelector('[data-smm-day-create-post]');
        if (createPostBtn) {
            createPostBtn.addEventListener('click', function () {
                if (selectedDay) openComposeModal({ date: selectedDay, hour: 17 });
            });
        }
    }

    function bindMonthPicker() {
        var toggle = root.querySelector('[data-smm-cal-range]');
        var picker = root.querySelector('[data-smm-month-picker]');
        var grid = root.querySelector('[data-smm-month-picker-grid]');
        var yearEl = root.querySelector('[data-smm-month-picker-year]');
        if (!toggle || !picker || !grid) return;

        var pickerYear = calendarMonth.getFullYear();

        function renderPicker() {
            if (yearEl) yearEl.textContent = String(pickerYear);
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            grid.innerHTML = months.map(function (label, index) {
                var selected = calendarMonth.getFullYear() === pickerYear && calendarMonth.getMonth() === index;
                return '<button type="button" class="smm-month-picker-btn' + (selected ? ' is-selected' : '') + '" data-smm-month-index="' + index + '">' +
                    escapeHtml(label) +
                    (selected ? '<svg width="12" height="12" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 10.5 8.5 14 15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' : '') +
                    '</button>';
            }).join('');
            grid.querySelectorAll('[data-smm-month-index]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var index = parseInt(btn.getAttribute('data-smm-month-index') || '0', 10);
                    calendarMonth = new Date(pickerYear, index, 1);
                    calendarWeekStart = startOfWeek(calendarMonth);
                    picker.classList.add('is-hidden');
                    toggle.setAttribute('aria-expanded', 'false');
                    renderCalendar();
                });
            });
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            picker.classList.toggle('is-hidden');
            var open = !picker.classList.contains('is-hidden');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                pickerYear = calendarMonth.getFullYear();
                renderPicker();
            }
        });

        var prevYear = root.querySelector('[data-smm-month-prev]');
        var nextYear = root.querySelector('[data-smm-month-next]');
        if (prevYear) {
            prevYear.addEventListener('click', function (event) {
                event.stopPropagation();
                pickerYear -= 1;
                renderPicker();
            });
        }
        if (nextYear) {
            nextYear.addEventListener('click', function (event) {
                event.stopPropagation();
                pickerYear += 1;
                renderPicker();
            });
        }

        document.addEventListener('click', function (event) {
            if (!picker.contains(event.target) && event.target !== toggle && !toggle.contains(event.target)) {
                picker.classList.add('is-hidden');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function bindCalendarFilters() {
        var toggle = root.querySelector('[data-smm-cal-filters]');
        var panel = root.querySelector('[data-smm-cal-filters-panel]');
        var body = root.querySelector('[data-smm-cal-filters-body]');
        var countEl = root.querySelector('[data-smm-cal-filters-count]');
        var clearBtn = root.querySelector('[data-smm-cal-filters-clear]');
        if (!toggle || !panel || !body) return;

        var accounts = Array.isArray(config.accounts) ? config.accounts : [];

        function filterGroups() {
            var groups = [{
                key: 'status',
                label: 'Status',
                options: [
                    { value: 'scheduled', label: 'Scheduled' },
                    { value: 'published', label: 'Published' },
                    { value: 'draft', label: 'Drafts' },
                ],
            }];

            var platforms = [];
            accounts.forEach(function (account) {
                var platform = String(account.platform || '');
                if (!platform) return;
                var known = platforms.some(function (option) {
                    return option.value === platform;
                });
                if (!known) {
                    platforms.push({ value: platform, label: composePlatformLabel(platform) });
                }
            });
            // A single network makes the group meaningless — everything matches it.
            if (platforms.length > 1) {
                groups.push({ key: 'platform', label: 'Network', options: platforms });
            }

            if (accounts.length > 1) {
                groups.push({
                    key: 'account',
                    label: 'Account',
                    options: accounts.map(function (account) {
                        return {
                            value: String(account.id || ''),
                            label: String(account.display_name || account.platform_label || 'Account'),
                        };
                    }).filter(function (option) {
                        return option.value !== '';
                    }),
                });
            }

            return groups;
        }

        function renderPanel() {
            body.innerHTML = filterGroups().map(function (group) {
                var options = group.options.map(function (option) {
                    var checked = calendarFilters[group.key].indexOf(option.value) !== -1;
                    return '<label class="smm-planner-filter-option">' +
                        '<input type="checkbox" class="smm-planner-filter-input"' +
                        ' data-smm-cal-filter-group="' + escapeHtml(group.key) + '"' +
                        ' value="' + escapeHtml(option.value) + '"' +
                        (checked ? ' checked' : '') + '>' +
                        '<span class="smm-planner-filter-box" aria-hidden="true">' +
                        '<svg width="10" height="10" viewBox="0 0 20 20" fill="none"><path d="M5 10.5 8.5 14 15 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                        '</span>' +
                        '<span class="smm-planner-filter-label font-sans">' + escapeHtml(option.label) + '</span>' +
                        '</label>';
                }).join('');

                return '<div class="smm-planner-filter-group">' +
                    '<p class="smm-planner-filter-group-label font-sans">' + escapeHtml(group.label) + '</p>' +
                    options +
                    '</div>';
            }).join('');

            body.querySelectorAll('[data-smm-cal-filter-group]').forEach(function (input) {
                input.addEventListener('change', function () {
                    var group = input.getAttribute('data-smm-cal-filter-group') || '';
                    if (!calendarFilters[group]) return;
                    var value = input.value;
                    var index = calendarFilters[group].indexOf(value);
                    if (input.checked && index === -1) {
                        calendarFilters[group].push(value);
                    } else if (!input.checked && index !== -1) {
                        calendarFilters[group].splice(index, 1);
                    }
                    applyCalendarFilters();
                });
            });
        }

        function updateFilterAffordance() {
            var count = calendarFilterCount();
            toggle.classList.toggle('is-active', count > 0);
            if (countEl) {
                countEl.textContent = String(count);
                countEl.classList.toggle('is-hidden', count === 0);
                countEl.hidden = count === 0;
            }
            if (clearBtn) clearBtn.disabled = count === 0;
        }

        function applyCalendarFilters() {
            updateFilterAffordance();
            renderCalendar();
            if (selectedDay) renderDayPanelSlots();
        }

        function closePanel() {
            panel.classList.add('is-hidden');
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = panel.classList.contains('is-hidden');
            if (!willOpen) {
                closePanel();
                return;
            }
            renderPanel();
            panel.classList.remove('is-hidden');
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                calendarFilters = { status: [], platform: [], account: [] };
                renderPanel();
                applyCalendarFilters();
            });
        }

        document.addEventListener('click', function (event) {
            if (panel.classList.contains('is-hidden')) return;
            if (panel.contains(event.target) || toggle.contains(event.target)) return;
            closePanel();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.classList.contains('is-hidden')) {
                closePanel();
                toggle.focus();
            }
        });

        updateFilterAffordance();
    }

    function bindVolumeToggle() {
        var toggle = root.querySelector('[data-smm-volume-toggle]');
        var volume = root.querySelector('[data-smm-volume]');
        if (!toggle || !volume) return;
        toggle.addEventListener('click', function () {
            var collapsed = volume.classList.toggle('is-collapsed');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    }

    function updateStatusFieldState() {
        if (!statusWrap) return;
        var isLiveOnly = activeSource === 'live';
        statusWrap.classList.toggle('is-disabled', isLiveOnly);
        var statusSelect = root.querySelector('[data-smm-filter-select]');
        if (statusSelect) {
            statusSelect.disabled = isLiveOnly;
        }
    }

    function bindFilters() {
        root.querySelectorAll('[data-smm-filter-account-select]').forEach(function (accountSelect) {
            accountSelect.addEventListener('change', function () {
                activeAccount = accountSelect.value || '';
                root.querySelectorAll('[data-smm-filter-account-select]').forEach(function (select) {
                    if (select !== accountSelect) {
                        select.value = activeAccount;
                        if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.refresh === 'function') {
                            var wrapper = select.closest('.lb-dropdown');
                            if (wrapper) {
                                window.LeadBirdDropdown.refresh(wrapper, select);
                            }
                        }
                    }
                });
                liveLoadedFor = '';
                refreshQueuePosts().then(function () {
                    renderPosts();
                    ensureCalendarData();
                });
            });
        });

        var statusSelect = root.querySelector('[data-smm-filter-select]');
        if (statusSelect) {
            statusSelect.addEventListener('change', function () {
                activeFilter = statusSelect.value || 'all';
                refreshQueuePosts().then(function () {
                    renderPosts();
                });
            });
        }

        root.querySelectorAll('[data-smm-source]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeSource = btn.getAttribute('data-smm-source') || 'all';
                root.querySelectorAll('[data-smm-source]').forEach(function (b) {
                    var isActive = b === btn;
                    b.classList.toggle('is-active', isActive);
                    b.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                updateStatusFieldState();
                renderPosts();
            });
        });

        updateStatusFieldState();
    }

    function bindScheduleMode() {
        root.querySelectorAll('[data-smm-mode]').forEach(function (input) {
            input.addEventListener('change', function () {
                var mode = currentPublishMode();
                if (schedulePicker) schedulePicker.classList.toggle('is-hidden', mode !== 'schedule');
                updateSubmitLabel();
            });
        });
        updateSubmitLabel();
    }

    function bindMediaUpload() {
        if (!mediaInput) return;

        mediaInput.addEventListener('change', function () {
            addMediaFiles(mediaInput.files);
            mediaInput.value = '';
        });
    }

    function runGenerate(type, triggerBtn) {
        var promptFromBtn = triggerBtn && triggerBtn.getAttribute('data-smm-ai-prompt-btn');
        if (promptFromBtn && aiPromptEl) {
            aiPromptEl.value = promptFromBtn;
        }
        if (wisdomAskInput && wisdomAskInput.value.trim() !== '' && aiPromptEl && type === 'caption') {
            aiPromptEl.value = wisdomAskInput.value.trim();
        }

        if (triggerBtn) triggerBtn.disabled = true;

                var payload = { type: type };
                if (type === 'hashtags') {
                    payload.caption = captionEl ? captionEl.value : '';
                } else {
                    payload.prompt = aiPromptEl ? aiPromptEl.value : '';
            if (!payload.prompt && captionEl && captionEl.value.trim() !== '') {
                payload.prompt = 'Improve this social media caption: ' + captionEl.value.trim();
            }
                }

        return fetch(config.endpoints.generate, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (type === 'hashtags' && data.hashtags && hashtagsEl) {
                            hashtagsEl.value = data.hashtags;
                        } else if (data.caption && captionEl) {
                            captionEl.value = data.caption;
                        } else {
                            setNote(data.error || data.message || 'Could not generate.', 'error');
                        }
                updatePreviewCaptions();
                    })
                    .catch(function () {
                        setNote('Could not reach the server.', 'error');
                    })
                    .finally(function () {
                if (triggerBtn) triggerBtn.disabled = false;
            });
    }

    function bindGenerate() {
        root.querySelectorAll('[data-smm-generate]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var type = btn.getAttribute('data-smm-generate') || 'caption';
                runGenerate(type, btn);
            });
        });
    }

    function selectedAccounts() {
        return Array.prototype.slice.call(root.querySelectorAll('[data-smm-compose-account]:checked')).map(function (el) {
            return el.value;
        });
    }

    function wrapCanvasText(ctx, text, maxWidth) {
        var words = text.split(/\s+/);
        var lines = [];
        var current = '';

        words.forEach(function (word) {
            if (!word) return;
            var candidate = current ? current + ' ' + word : word;
            if (ctx.measureText(candidate).width > maxWidth && current) {
                lines.push(current);
                current = word;
            } else {
                current = candidate;
            }
        });

        if (current) lines.push(current);
        return lines.length ? lines : [text];
    }

    function burnStoryCaptionOnImageFile(file, caption) {
        return new Promise(function (resolve) {
            var text = (caption || '').trim();
            if (!file || !text || (file.type || '').indexOf('image/') !== 0) {
                resolve(file);
                return;
            }

            var image = new Image();
            var objectUrl = URL.createObjectURL(file);
            image.onload = function () {
                var canvas = document.createElement('canvas');
                canvas.width = image.naturalWidth || image.width;
                canvas.height = image.naturalHeight || image.height;
                var ctx = canvas.getContext('2d');
                if (!ctx) {
                    URL.revokeObjectURL(objectUrl);
                    resolve(file);
                    return;
                }

                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
                drawStoryCaptionOverlay(ctx, canvas.width, canvas.height, text);

                canvas.toBlob(function (blob) {
                    URL.revokeObjectURL(objectUrl);
                    if (!blob) {
                        resolve(file);
                        return;
                    }
                    var outputName = (file.name || 'story.jpg').replace(/\.[^.]+$/, '') + '-story.jpg';
                    resolve(new File([blob], outputName, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', 0.92);
            };
            image.onerror = function () {
                URL.revokeObjectURL(objectUrl);
                resolve(file);
            };
            image.src = objectUrl;
        });
    }

    function prepareStoryMediaForSubmit() {
        var platforms = getSelectedComposePlatforms();
        var tasks = platforms.map(function (platform) {
            if (getComposePostTypeForPlatform(platform) !== 'story') {
                return Promise.resolve();
            }

            var caption = getPlatformFullCaption(platform);
            if (!caption) {
                return Promise.resolve();
            }

            var files = getFilesForPlatform(platform);
            if (!files.length) {
                return Promise.resolve();
            }

            var file = files[0];
            if ((file.type || '').indexOf('image/') !== 0) {
                return Promise.resolve();
            }

            return burnStoryCaptionOnImageFile(file, caption).then(function (processedFile) {
                ensurePlatformContent(platform);
                composePlatformContent[platform].files = [processedFile];
                if (platform === activeComposePlatform) {
                    selectedFiles = [processedFile];
                }
            });
        });

        return Promise.all(tasks).then(function () {
            persistActivePlatformMedia();
            syncMediaInput();
        });
    }

    function insertEmojiAtCaptionCursor(emoji) {
        if (!captionEl || !emoji) return;
        var start = captionEl.selectionStart || 0;
        var end = captionEl.selectionEnd || 0;
        var value = captionEl.value;
        captionEl.value = value.slice(0, start) + emoji + value.slice(end);
        captionEl.focus();
        captionEl.selectionStart = captionEl.selectionEnd = start + emoji.length;
        persistActivePlatformContent();
        updatePreviewCaptions();
    }

    function initEmojiPicker() {
        var emojiBtn = root.querySelector('[data-smm-insert-emoji]');
        var picker = root.querySelector('[data-smm-emoji-picker]');
        var grid = root.querySelector('[data-smm-emoji-grid]');
        var closeBtn = root.querySelector('[data-smm-emoji-close]');
        var emojiAnchor = emojiBtn ? emojiBtn.closest('.smm-compose-emoji-anchor') : null;
        if (!emojiBtn || !picker || !grid) return;

        grid.innerHTML = COMPOSE_EMOJIS.map(function (emoji) {
            return '<button type="button" class="smm-compose-emoji-option" data-smm-emoji-value="' + escapeHtml(emoji) + '" aria-label="Insert ' + escapeHtml(emoji) + '">' + emoji + '</button>';
        }).join('');

        function positionEmojiPicker() {
            var rect = emojiBtn.getBoundingClientRect();
            var pickerWidth = picker.offsetWidth || 296;
            var pickerHeight = picker.offsetHeight || 264;
            var gap = 8;
            var left = Math.max(8, Math.min(rect.right - pickerWidth, window.innerWidth - pickerWidth - 8));
            var top;

            if (window.innerHeight - rect.bottom >= pickerHeight + gap + 12) {
                top = rect.bottom + gap;
            } else {
                top = Math.max(8, rect.top - pickerHeight - gap);
            }

            picker.style.left = left + 'px';
            picker.style.top = top + 'px';
        }

        function closePicker() {
            picker.classList.add('is-hidden');
            picker.classList.remove('is-floating');
            picker.hidden = true;
            emojiBtn.setAttribute('aria-expanded', 'false');
            if (emojiAnchor && picker.parentNode !== emojiAnchor) {
                emojiAnchor.appendChild(picker);
            }
            picker.style.left = '';
            picker.style.top = '';
        }

        function openPicker() {
            document.body.appendChild(picker);
            picker.classList.remove('is-hidden');
            picker.classList.add('is-floating');
            picker.hidden = false;
            emojiBtn.setAttribute('aria-expanded', 'true');
            requestAnimationFrame(positionEmojiPicker);
        }

        emojiBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            if (picker.hidden) {
                openPicker();
            } else {
                closePicker();
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                closePicker();
            });
        }

        grid.addEventListener('click', function (event) {
            var button = event.target.closest('[data-smm-emoji-value]');
            if (!button) return;
            insertEmojiAtCaptionCursor(button.getAttribute('data-smm-emoji-value') || '');
            closePicker();
        });

        document.addEventListener('click', function (event) {
            if (picker.hidden) return;
            if (picker.contains(event.target) || emojiBtn.contains(event.target)) return;
            closePicker();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !picker.hidden) {
                closePicker();
            }
        });

        window.addEventListener('resize', function () {
            if (!picker.hidden) {
                positionEmojiPicker();
            }
        });
    }

    function bindForm() {
        if (!form) return;

        function submitComposeForm() {
            setNote('');

            var accounts = selectedAccounts();
            if (accounts.length === 0) {
                setNote(
                    config.tiktokEnabled
                        ? 'Select at least one Facebook Page, Instagram feed, or TikTok account.'
                        : 'Select at least one Facebook Page or Instagram feed account.',
                    'error'
                );
                return;
            }

            if (hasBlockingComposeErrors()) {
                updateComposeErrors();
                setNote('Fix the errors in your post before publishing.', 'error');
                return;
            }

            var contentError = validatePlatformComposeContent();
            if (contentError) {
                setNote(contentError, 'error');
                return;
            }

            persistActivePlatformContent();
            persistActivePlatformMedia();
            var mode = currentPublishMode();

            if (submitBtn) submitBtn.disabled = true;
            setNote('');
            showPublishOverlay(publishOverlayTitleForMode(mode), 'Preparing your media…');

            prepareStoryMediaForSubmit().then(function () {
            var formData = new FormData(form);
            formData.delete('account_ids[]');
                formData.delete('media[]');
            accounts.forEach(function (id) {
                formData.append('account_ids[]', id);
            });

                getSelectedComposePlatforms().forEach(function (platform) {
                    getFilesForPlatform(platform).forEach(function (file) {
                        formData.append('media_' + platform + '[]', file);
                    });
                });

                var activeContent = composePlatformContent[activeComposePlatform] || { caption: '', hashtags: '' };
                formData.set('caption', activeContent.caption || '');
                formData.set('hashtags', activeContent.hashtags || '');
                formData.set('platform_content', JSON.stringify(buildPlatformContentPayload()));

            if (mode === 'now') {
                formData.append('publish_now', '1');
                formData.delete('scheduled_at');
            } else if (mode === 'schedule') {
                formData.append('publish_now', '0');
                    if (!scheduledAtEl || !scheduledAtEl.value) {
                        hidePublishOverlay();
                        setNote('Pick a date and time to schedule this post.', 'error');
                        if (submitBtn) submitBtn.disabled = false;
                        return;
                    }
            } else {
                formData.append('save_draft', '1');
                formData.append('publish_now', '0');
                formData.delete('scheduled_at');
            }

            if (aiPromptEl && aiPromptEl.value.trim() !== '') {
                formData.append('ai_assisted', '1');
            }

                syncInstagramTagsField();
                var igTagsField = root.querySelector('[data-smm-ig-user-tags]');
                if (igTagsField && igTagsField.value) {
                    formData.set('instagram_user_tags', igTagsField.value);
                }

                return capturePosterForSubmit().then(function (poster) {
                    if (poster) {
                        formData.append('poster', poster, 'poster.jpg');
                    }

                    return postFormWithProgress(config.endpoints.save, formData, function (ratio) {
                        if (ratio >= 1) {
                            setPublishProgress(null, publishOverlayCopyForMode(mode));
                            return;
                        }
                        // Leave the last stretch of the bar for the server to finish the job.
                        setPublishProgress(ratio * 0.9, 'Uploading your media…');
                    });
                });
            })
                .then(function (data) {
                    if (!data) return;
                    if (!data.ok) {
                        setNote(data.error || data.message || 'Something went wrong.', 'error');
                        return;
                    }

                    if (data.post) {
                        queuePosts.unshift(data.post);
                    }

                    refreshStats();
                    renderPosts();
                    renderCalendar();
                    if (selectedDay) renderDayPanelSlots();
                    closeComposeModal();
                    resetComposeForm();
                })
                .catch(function () {
                    setNote('Could not reach the server.', 'error');
                })
                .finally(function () {
                    hidePublishOverlay();
                    if (submitBtn) submitBtn.disabled = false;
                });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitComposeForm();
        });

        if (submitBtn) {
            submitBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (submitMenuBypass) {
                    submitMenuBypass = false;
                } else if (submitMenu && submitActsAsMenu() && currentPublishMode() !== 'schedule') {
                    toggleSubmitMenu();
                    return;
                }
                if (currentPublishMode() === 'now') {
                    submitComposeForm();
                    return;
                }
                if (currentPublishMode() === 'schedule') {
                    if (!scheduledAtEl || !scheduledAtEl.value) {
                        setNote('Pick a date and time to schedule this post.', 'error');
                        openScheduleModal();
                        return;
                    }
                    submitComposeForm();
                    return;
                }
                submitComposeForm();
            });
        }

        if (captionEl) captionEl.addEventListener('input', updatePreviewCaptions);
        if (hashtagsEl) hashtagsEl.addEventListener('input', updatePreviewCaptions);
        bindInstagramPreviewExtras();
    }

    function refreshStats() {
        var url = config.endpoints.posts + '?filter=all';
        if (activeAccount) url += '&account_id=' + encodeURIComponent(activeAccount);

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) return;
                queuePosts = data.posts || [];
                var stats = data.stats || {};
                root.querySelectorAll('[data-smm-stat]').forEach(function (el) {
                    var key = el.getAttribute('data-smm-stat');
                    if (key && stats[key] !== undefined) {
                        el.textContent = String(stats[key]);
                    }
                });
            })
            .catch(function () {});
    }

    function refreshQueuePosts() {
        var url = config.endpoints.posts + '?filter=' + encodeURIComponent(activeFilter);
        if (activeAccount) url += '&account_id=' + encodeURIComponent(activeAccount);

        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) queuePosts = data.posts || [];
            });
    }

    function loadLiveFeed() {
        var cacheKey = activeAccount || '__all__';
        if (liveLoadedFor === cacheKey) {
            return Promise.resolve();
        }

        var url = config.endpoints.feed + '?account_id=' + encodeURIComponent(activeAccount || 'all');

        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    livePosts = data.posts || [];
                    liveFeedWarnings = data.warnings || [];
                    liveSyncedPlatforms = data.synced_platforms || [];
                    liveLoadedFor = cacheKey;
                } else {
                    livePosts = [];
                    liveFeedWarnings = data.error ? [data.error] : [];
                    liveSyncedPlatforms = [];
                }
            })
            .catch(function () {
                livePosts = [];
                liveFeedWarnings = ['Could not load posts from your connected accounts.'];
                liveSyncedPlatforms = [];
            });
    }

    function platformIsSynced(platform) {
        return liveSyncedPlatforms.indexOf(platform) !== -1;
    }

    /**
     * TikTok only hands back a real numeric video id for a post the public can see.
     * A private ("Only me") post keeps its publish id instead, and video/list never
     * returns it — so the live feed cannot stand in for our record.
     */
    function tiktokIdIsListable(platformPostId) {
        return /^\d+$/.test(String(platformPostId || ''));
    }

    function isPublishedTikTokRecord(post) {
        if ((post.source || 'leadbird') === 'live') return false;
        if ((post.status || '') !== 'published') return false;

        var targets = post.targets || [];
        if (targets.length === 0) return false;

        return targets.every(function (target) {
            return target.platform === 'tiktok' && tiktokIdIsListable(target.platform_post_id);
        });
    }

    /**
     * Once TikTok answers, its own list is the truth for any video it can list: it
     * carries the real cover art, and a video the creator deleted in the TikTok app
     * simply stops coming back. Our published record would only be a stale ghost.
     * Private posts are never listed, so those records are all the creator has.
     */
    function withoutTikTokDuplicates(list) {
        if (!platformIsSynced('tiktok')) return list;

        return list.filter(function (post) {
            return !isPublishedTikTokRecord(post);
            });
    }

    function filteredQueuePosts() {
        if (activeFilter === 'all') return queuePosts;
        return queuePosts.filter(function (post) {
            return (post.status || '') === activeFilter;
        });
    }

    function postsForView() {
        var queue = withoutTikTokDuplicates(filteredQueuePosts());
        if (activeSource === 'leadbird') return queue;
        if (activeSource === 'live') return livePosts;
        return livePosts.concat(queue);
    }

    function postAccountMeta(post) {
        var accountId = post.account_id || (post.targets && post.targets[0] && post.targets[0].connected_account_id) || '';
        var account = null;
        var accounts = Array.isArray(config.accounts) ? config.accounts : [];
        for (var i = 0; i < accounts.length; i++) {
            if (String(accounts[i].id) === String(accountId)) {
                account = accounts[i];
                break;
            }
        }

        var platform = post.platform || (post.targets && post.targets[0] && post.targets[0].platform) || (account && account.platform) || 'instagram';
        return {
            name: post.display_name || (account && account.display_name) || 'Lead Bird',
            avatar: post.avatar_src || (account && account.avatar_src) || '',
            platform: platform,
            platformLabel: post.platform_label || (post.targets && post.targets[0] && post.targets[0].platform_label) || (account && account.platform_label) || 'Post',
        };
    }

    function postPlatformIconHtml(platform) {
        var icons = config.platformIcons || {};
        return icons[platform] || icons.instagram || '';
    }

    function buildPostCaptionText(post) {
        var caption = (post.caption || '').trim();
        var hashtags = (post.hashtags || '').trim();
        if (caption && hashtags) {
            return caption + '\n\n' + hashtags;
        }
        return caption || hashtags || '';
    }

    function formatPostTimestamp(raw) {
        if (!raw) {
            return '';
        }
        var date = new Date(raw);
        if (isNaN(date.getTime())) {
            return raw;
        }
        return date.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    function truncatePostCaption(text) {
        var value = (text || '').replace(/\s+/g, ' ').trim();
        if (!value) {
            return 'No caption yet.';
        }
        if (value.length <= 72) {
            return value;
        }
        return value.slice(0, 69) + '...';
    }

    function postInsightsMeta(post) {
        var isLive = (post.source || 'leadbird') === 'live';
        if (isLive && post.id) {
            return {
                accountId: post.account_id || '',
                platform: post.platform || '',
                platformPostId: post.id,
            };
        }

        if ((post.status || '') === 'published' && Array.isArray(post.targets)) {
            var target = post.targets.find(function (item) {
                return item.platform_post_id && (item.status || '') === 'published';
            });
            if (!target) {
                target = post.targets.find(function (item) {
                    return item.platform_post_id;
                });
            }
            if (target) {
                /*
                 * A private TikTok post only ever carries a publish id, and TikTok's
                 * stats endpoint takes video ids — asking would just return an error.
                 */
                if (target.platform === 'tiktok' && !tiktokIdIsListable(target.platform_post_id)) {
                    return null;
                }

                return {
                    accountId: target.connected_account_id || '',
                    platform: target.platform || '',
                    platformPostId: target.platform_post_id || '',
                };
            }
        }

        return null;
    }

    function insightsUnavailableReason(post) {
        if ((post.status || '') === 'published' && Array.isArray(post.targets)) {
            var privateTikTok = post.targets.some(function (target) {
                return target.platform === 'tiktok' && !tiktokIdIsListable(target.platform_post_id);
            });
            if (privateTikTok) {
                return 'TikTok does not report stats for a post only you can see.';
            }
        }

        return 'Insights are available after the post is live.';
    }

    function normalizeFeedMediaType(type) {
        var t = String(type || '').toLowerCase();
        if (t === 'video' || t === 'reels' || t === 'reel') return 'video';
        if (t === 'carousel_album' || t === 'carousel') return 'carousel';
        if (t === 'text') return 'text';
        return t || 'image';
    }

    function isVideoMediaUrl(url) {
        return /\.(mp4|mov|m4v)(\?|$)/i.test(String(url || ''));
    }

    function postMediaTypeBadge(post) {
        if (post.media_items && post.media_items.length > 1) {
            return '<span class="smm-post-card-type" aria-hidden="true">' +
                '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<rect x="3" y="7" width="14" height="14" rx="2"/>' +
                '<path d="M7 3h10a4 4 0 0 1 4 4v10"/>' +
                '</svg></span>';
        }
        var type = normalizeFeedMediaType(post.media_type);
        var product = String(post.media_product_type || '').toLowerCase();
        if (type === 'video' || product === 'reels') {
            return '<span class="smm-post-card-type" aria-hidden="true">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">' +
                '<path d="M8 5v14l11-7z"/>' +
                '</svg></span>';
        }
        return '';
    }

    function buildFeedMedia(post) {
        var type = normalizeFeedMediaType(post.media_type);
        var poster = post.thumbnail_url || '';
        var url = poster || post.media_url || post.image_url || '';

        if (post.media_items && post.media_items.length > 0) {
            var first = post.media_items[0] || {};
            type = normalizeFeedMediaType(first.media_type || type);
            url = first.thumbnail_url || first.media_url || url;
        }

        // The saved poster frame beats the video file: it paints instantly, and once
        // publishing clears the upload it is the only preview left.
        if (type === 'video' && poster) {
            return '<img class="smm-post-card-img" src="' + escapeHtml(poster) + '" alt="" loading="lazy" decoding="async">';
        }

        if (!url) {
            return '<div class="smm-post-card-empty font-sans"><span>' +
                (type === 'video' ? 'Video' : 'Text post') + '</span></div>';
        }

        // Prefer a still thumbnail for Reels/videos; only use <video> when the URL is actually a video file.
        if (type === 'video' && isVideoMediaUrl(url)) {
            // The #t seek makes the browser paint that frame instead of a black box.
            return '<video class="smm-post-card-img" src="' + escapeHtml(url) + '#t=0.5" muted playsinline preload="metadata"></video>';
        }

        return '<img class="smm-post-card-img" src="' + escapeHtml(url) + '" alt="" loading="lazy" decoding="async">';
    }

    function platformBadge(post) {
        var label = post.platform_label || (post.targets && post.targets[0] && post.targets[0].platform_label) || 'Post';
        var cls = post.platform === 'instagram' || (post.targets && post.targets[0] && post.targets[0].platform === 'instagram')
            ? 'smm-badge--ig' : 'smm-badge--fb';
        return '<span class="smm-badge ' + cls + '">' + escapeHtml(label) + '</span>';
    }

    function sourceBadge(post) {
        if ((post.source || 'leadbird') === 'live') {
            return '<span class="smm-badge smm-badge--live">Live on social</span>';
        }
        return '<span class="smm-badge smm-badge--queue">Lead Bird</span>';
    }

    function renderPosts() {
        var postsList = getActivePostsList();
        if (!postsList) return;

        var render = function () {
            var list;
            if (activeView === 'drafts') {
                list = queuePosts.filter(function (post) {
                    return (post.status || '') === 'draft';
                });
            } else {
                list = postsForView();
            }

            if (activeSource !== 'leadbird' && activeAccount === '' && activeSource === 'live') {
                postsList.innerHTML = '<div class="smm-empty-posts"><p class="font-heading">Pick an account above</p><p class="font-sans">Choose an account to browse posts already live on it.</p></div>';
                return;
            }

            if (list.length === 0) {
                var emptyCopy = '<div class="smm-empty-posts"><p class="font-heading">No posts here yet</p>';
                if (activeSource === 'leadbird') {
                    emptyCopy += '<p class="font-sans">Drafts and scheduled posts you create in Lead Bird show up here. Use Compose to add one.</p>';
                } else if (activeSource === 'live') {
                    emptyCopy += '<p class="font-sans">Posts already live on your connected accounts show here. If this stays empty, reconnect the account in Connected Accounts.</p>';
                } else {
                    emptyCopy += '<p class="font-sans">This view mixes posts already live on your connected accounts with drafts and scheduled posts from Lead Bird.</p>';
                }
                if (liveFeedWarnings.length && activeView === 'content') {
                    emptyCopy += '<p class="smm-feed-warning font-sans">' + escapeHtml(liveFeedWarnings.join(' ')) + '</p>';
                }
                emptyCopy += '</div>';
                postsList.innerHTML = emptyCopy;
                return;
            }

            postsList.innerHTML = list.map(function (post) {
                var isLive = (post.source || 'leadbird') === 'live';
                var meta = postAccountMeta(post);
                var captionText = buildPostCaptionText(post);
                var cardCaption = escapeHtml(truncatePostCaption(captionText));
                var time = formatPostTimestamp(post.timestamp || post.published_at || post.scheduled_at || post.created_at || '');
                var status = isLive ? 'Live' : (post.status || 'draft');
                var actions = '';
                var insightsMeta = postInsightsMeta(post);

                if (isLive) {
                    if (post.can_edit) {
                        actions += '<button type="button" class="smm-btn smm-btn--ghost" data-smm-edit-live="' + escapeHtml(post.id) + '" data-account="' + escapeHtml(post.account_id || '') + '">Edit</button>';
                    }
                    if (post.can_delete) {
                        actions += '<button type="button" class="smm-btn smm-btn--danger" data-smm-delete-live="' + escapeHtml(post.id) + '" data-account="' + escapeHtml(post.account_id || '') + '">Delete</button>';
                    }
                    // TikTok exposes no edit or delete endpoint, so send the creator to the app.
                    if (!post.can_edit && !post.can_delete && post.permalink) {
                        actions += '<a class="smm-btn smm-btn--ghost smm-btn--link" href="' + escapeHtml(post.permalink) + '" target="_blank" rel="noopener">Manage on ' +
                            escapeHtml(composePlatformLabel(meta.platform)) + '</a>';
                    }
                } else {
                    if (post.can_edit) {
                        actions += '<button type="button" class="smm-btn smm-btn--ghost" data-smm-edit-local="' + escapeHtml(post.id) + '">Edit</button>';
                    }
                    if (status === 'scheduled') {
                        actions += '<button type="button" class="smm-btn smm-btn--ghost" data-smm-cancel="' + escapeHtml(post.id) + '">Cancel</button>';
                    }
                    if (post.can_delete) {
                        actions += '<button type="button" class="smm-btn smm-btn--danger" data-smm-delete="' + escapeHtml(post.id) + '">Delete</button>';
                    }
                    if (status === 'failed') {
                        actions += '<button type="button" class="smm-btn smm-btn--primary" data-smm-retry="' + escapeHtml(post.id) + '">Retry</button>';
                    }
                }

                var insightsButton = insightsMeta
                    ? '<button type="button" class="smm-post-card-insights font-sans" data-smm-insights-account="' + escapeHtml(insightsMeta.accountId) + '" data-smm-insights-platform="' + escapeHtml(insightsMeta.platform) + '" data-smm-insights-post="' + escapeHtml(insightsMeta.platformPostId) + '">See insights</button>'
                    : '<button type="button" class="smm-post-card-insights font-sans" disabled title="' + escapeHtml(insightsUnavailableReason(post)) + '">See insights</button>';

                return (
                    '<article class="smm-post-card" data-post-source="' + escapeHtml(post.source || 'leadbird') + '">' +
                    '<div class="smm-post-card-media">' + buildFeedMedia(post) +
                    '<span class="smm-post-card-platform" aria-label="' + escapeHtml(meta.platformLabel) + '">' + postPlatformIconHtml(meta.platform) + '</span>' +
                    postMediaTypeBadge(post) +
                    '</div>' +
                    '<div class="smm-post-card-body">' +
                    '<p class="smm-post-card-caption font-sans">' + cardCaption + '</p>' +
                    '<p class="smm-post-card-time font-sans">' + escapeHtml(time) + '</p>' +
                    (actions ? '<div class="smm-post-card-actions">' + actions + '</div>' : '') +
                    insightsButton +
                    '</div></article>'
                );
            }).join('');

            bindPostActions();
            bindFeedImageFallbacks();

            if (liveFeedWarnings.length && activeView === 'content' && postsList) {
                var warning = document.createElement('p');
                warning.className = 'smm-feed-warning font-sans';
                warning.textContent = liveFeedWarnings.join(' ');
                postsList.prepend(warning);
            }
        };

        if ((activeSource === 'live' || activeSource === 'all') && activeView === 'content') {
            loadLiveFeed().then(render);
            return;
        }

        render();
    }

    function bindFeedImageFallbacks() {
        var postsList = getActivePostsList();
        if (!postsList) return;

        postsList.querySelectorAll('.smm-post-card-img').forEach(function (el) {
            if (el.dataset.smmFeedBound === '1') return;
            el.dataset.smmFeedBound = '1';
            el.addEventListener('error', function () {
                el.replaceWith(createFeedPlaceholder(el.tagName === 'VIDEO' ? 'Video unavailable' : 'Image unavailable'));
            });
        });
    }

    function createFeedPlaceholder(label) {
        var node = document.createElement('div');
        node.className = 'smm-post-card-empty font-sans';
        node.innerHTML = '<span>' + label + '</span>';
        return node;
    }

    function bindPostActions() {
        var postsList = getActivePostsList();
        if (!postsList) return;

        postsList.querySelectorAll('[data-smm-delete]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                postAction(btn.getAttribute('data-smm-delete'), 'delete');
            });
        });
        postsList.querySelectorAll('[data-smm-cancel]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                postAction(btn.getAttribute('data-smm-cancel'), 'cancel');
            });
        });
        postsList.querySelectorAll('[data-smm-retry]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                retryPublish(btn.getAttribute('data-smm-retry'));
            });
        });
        postsList.querySelectorAll('[data-smm-edit-local]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openEditModal('leadbird', btn.getAttribute('data-smm-edit-local'));
            });
        });
        postsList.querySelectorAll('[data-smm-edit-live]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openEditModal('live', btn.getAttribute('data-smm-edit-live'), btn.getAttribute('data-account'));
            });
        });
        postsList.querySelectorAll('[data-smm-delete-live]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.LeadBirdModal.confirm({
                    title: 'Delete this post?',
                    message: 'This removes it from your Page or Instagram feed. You cannot undo this.',
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
                            account_id: btn.getAttribute('data-account'),
                            platform_post_id: btn.getAttribute('data-smm-delete-live'),
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
                            liveLoadedFor = '';
                            renderPosts();
                        });
                });
            });
        });

        postsList.querySelectorAll('[data-smm-insights-post]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var accountId = btn.getAttribute('data-smm-insights-account') || '';
                var platform = btn.getAttribute('data-smm-insights-platform') || '';
                var platformPostId = btn.getAttribute('data-smm-insights-post') || '';
                if (!accountId || !platform || !platformPostId) return;

                var pageBase = (config.endpoints && config.endpoints.insightsPage)
                    ? config.endpoints.insightsPage
                    : ((config.appUrl || '').replace(/\/$/, '') + '/social-media-insights');
                var joiner = pageBase.indexOf('?') === -1 ? '?' : '&';
                window.location.href = pageBase + joiner
                    + 'account_id=' + encodeURIComponent(accountId)
                    + '&platform=' + encodeURIComponent(platform)
                    + '&platform_post_id=' + encodeURIComponent(platformPostId);
            });
        });
    }

    function openInsightsModal(accountId, platform, platformPostId) {
        if (!insightsModal || !insightsBody || !config.endpoints.insights) return;
        if (!accountId || !platform || !platformPostId) return;

        if (insightsTitle) {
            insightsTitle.textContent = 'Post insights';
        }
        insightsBody.innerHTML = '<p class="smm-insights-loading font-sans">Loading insights…</p>';
        insightsModal.classList.remove('is-hidden');
        insightsModal.hidden = false;
        insightsModal.setAttribute('aria-hidden', 'false');

        var url = config.endpoints.insights +
            '?account_id=' + encodeURIComponent(accountId) +
            '&platform=' + encodeURIComponent(platform) +
            '&platform_post_id=' + encodeURIComponent(platformPostId);

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    insightsBody.innerHTML = '<p class="smm-insights-error font-sans">' + escapeHtml(data.error || 'Could not load insights for this post.') + '</p>';
                    return;
                }

                var metrics = Array.isArray(data.metrics) ? data.metrics : [];
                if (!metrics.length) {
                    insightsBody.innerHTML = '<p class="smm-insights-empty font-sans">No insights are available for this post yet.</p>';
                    return;
                }

                insightsBody.innerHTML = '<div class="smm-insights-grid">' + metrics.map(function (metric) {
                    return (
                        '<div class="smm-insights-stat">' +
                        '<p class="smm-insights-stat-label font-sans">' + escapeHtml(metric.label || metric.name || 'Metric') + '</p>' +
                        '<p class="smm-insights-stat-value font-heading">' + escapeHtml(formatInsightValue(metric.value)) + '</p>' +
                        '</div>'
                    );
                }).join('') + '</div>';
            })
            .catch(function () {
                insightsBody.innerHTML = '<p class="smm-insights-error font-sans">Could not load insights. Try again in a moment.</p>';
            });
    }

    function closeInsightsModal() {
        if (!insightsModal) return;
        insightsModal.classList.add('is-hidden');
        insightsModal.hidden = true;
        insightsModal.setAttribute('aria-hidden', 'true');
    }

    function formatInsightValue(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }
        if (typeof value === 'number' && !isNaN(value)) {
            return value.toLocaleString();
        }
        return String(value);
    }

    if (insightsModal) {
        insightsModal.querySelectorAll('[data-smm-insights-close]').forEach(function (el) {
            el.addEventListener('click', closeInsightsModal);
        });
    }

    function openEditModal(source, postId, accountId) {
        if (!modal || !editForm) return;

        var post = null;
        if (source === 'live') {
            post = livePosts.find(function (p) { return p.id === postId; });
        } else {
            post = queuePosts.find(function (p) { return p.id === postId; });
        }
        if (!post) return;

        editForm.querySelector('[data-smm-edit-source]').value = source;
        editForm.querySelector('[data-smm-edit-id]').value = source === 'live' ? '' : postId;
        editForm.querySelector('[data-smm-edit-account-id]').value = accountId || '';
        editForm.querySelector('[data-smm-edit-platform-id]').value = source === 'live' ? postId : '';
        editForm.querySelector('[data-smm-edit-caption]').value = post.caption || '';
        var hashtagsWrap = editForm.querySelector('[data-smm-edit-hashtags-wrap]');
        var note = editForm.querySelector('[data-smm-edit-note]');
        if (source === 'live') {
            if (hashtagsWrap) hashtagsWrap.classList.add('is-hidden');
            if (note) note.textContent = post.can_edit
                ? 'Updates the public post on your Facebook Page wall.'
                : 'Instagram captions cannot be edited after publish. Delete and repost instead.';
            editForm.querySelector('[data-smm-edit-caption]').disabled = !post.can_edit;
        } else {
            if (hashtagsWrap) hashtagsWrap.classList.remove('is-hidden');
            editForm.querySelector('[data-smm-edit-hashtags]').value = post.hashtags || '';
            if (note) note.textContent = 'Changes apply to this draft, scheduled, or failed post in Lead Bird.';
            editForm.querySelector('[data-smm-edit-caption]').disabled = false;
        }

        modal.classList.remove('is-hidden');
        modal.hidden = false;
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('is-hidden');
        modal.hidden = true;
    }

    function bindModal() {
        root.querySelectorAll('[data-smm-modal-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });

        if (!editForm) return;
        editForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var source = editForm.querySelector('[data-smm-edit-source]').value;

            if (source === 'live') {
                fetch(config.endpoints.update, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        type: 'live',
                        account_id: editForm.querySelector('[data-smm-edit-account-id]').value,
                        platform_post_id: editForm.querySelector('[data-smm-edit-platform-id]').value,
                        caption: editForm.querySelector('[data-smm-edit-caption]').value,
                    }),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.ok) {
                            window.LeadBirdModal.alert({
                                title: 'Update failed',
                                message: data.error || 'Could not save your changes.',
                                tone: 'error',
                            });
                            return;
                        }
                        liveLoadedFor = '';
                        closeModal();
                        renderPosts();
                    });
                return;
            }

            var formData = new FormData(editForm);
            formData.append('post_id', editForm.querySelector('[data-smm-edit-id]').value);
            var post = queuePosts.find(function (p) {
                return p.id === editForm.querySelector('[data-smm-edit-id]').value;
            });
            if (post && post.media_url && post.media_url.indexOf('/storage/') !== -1) {
                formData.append('existing_media_url', post.media_url);
            }
            (post && post.targets ? post.targets : []).forEach(function (t) {
                if (t.connected_account_id) formData.append('account_ids[]', t.connected_account_id);
            });

            fetch(config.endpoints.update, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        window.LeadBirdModal.alert({
                            title: 'Update failed',
                            message: data.error || 'Could not save your changes.',
                            tone: 'error',
                        });
                        return;
                    }
                    if (data.post) {
                        queuePosts = queuePosts.map(function (p) {
                            return p.id === data.post.id ? data.post : p;
                        });
                    }
                    closeModal();
                    refreshStats();
                    renderPosts();
                });
        });
    }

    function postAction(postId, action) {
        if (!postId) return;
        fetch(config.endpoints.delete, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ post_id: postId, action: action }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    window.LeadBirdModal.alert({
                        title: 'Action failed',
                        message: data.error || data.message || 'Something went wrong.',
                        tone: 'error',
                    });
                    return;
                }
                queuePosts = queuePosts.filter(function (p) { return p.id !== postId; });
                refreshStats();
                renderPosts();
                renderCalendar();
            });
    }

    function retryPublish(postId) {
        fetch(config.appUrl + '/social-media/publish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ post_id: postId }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    window.LeadBirdModal.alert({
                        title: 'Retry failed',
                        message: data.error || 'Could not publish this post.',
                        tone: 'error',
                    });
                    return;
                }
                if (data.post) {
                    queuePosts = queuePosts.map(function (p) { return p.id === postId ? data.post : p; });
                }
                refreshStats();
                renderPosts();
            });
    }

    function renderWeekView() {
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var days = [];
        for (var i = 0; i < 7; i += 1) days.push(addDays(calendarWeekStart, i));

        var html = '<div class="smm-cal-week smm-cal-week--condensed">';
        html += '<div class="smm-cal-week-head">';
        days.forEach(function (day) {
            var holidays = holidaysForDate(day);
            var holidayHtml = holidays.map(function (name) {
                return '<span class="smm-cal-holiday" title="' + escapeHtml(name) + '">' + escapeHtml(name) + '</span>';
            }).join('');
            var todayClass = isSameDay(day, today) ? ' is-today' : '';
            html += '<div class="smm-cal-day-head' + todayClass + '">' +
                '<span class="smm-cal-day-name">' + escapeHtml(day.toLocaleDateString(undefined, { weekday: 'short' })) + '</span>' +
                '<span class="smm-cal-day-num">' + day.getDate() + '</span>' +
                '<div class="smm-cal-holidays">' + holidayHtml + '</div></div>';
        });
        html += '</div>';
        html += '<div class="smm-cal-week-scroll" data-smm-cal-week-scroll tabindex="0" aria-label="Weekly schedule">';
        html += '<p class="smm-cal-week-tz font-sans">GMT ' + formatTimezoneOffset() + '</p>';
        html += '<div class="smm-cal-week-columns">';
        days.forEach(function (day) {
            var dayPosts = postsOnDay(day);
            html += '<div class="smm-cal-week-col">';
            dayPosts.forEach(function (post) {
                html += renderCalendarPostCard(post, { compact: true });
            });
            html += renderRecommendedTimeSuggestHtml(day, { posts: dayPosts });
            html += '</div>';
        });
        html += '</div></div></div>';
        calendarEl.innerHTML = html;
        bindCalendarInteractions(calendarEl);
    }

    function renderMonthView() {
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var monthStart = startOfMonth(calendarMonth);
        var gridStart = startOfWeek(monthStart);
        var html = '<div class="smm-cal-month"><div class="smm-cal-month-head">';
        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(function (label) {
            html += '<div class="smm-cal-month-weekday font-heading">' + label + '</div>';
        });
        html += '</div><div class="smm-cal-month-grid">';
        for (var i = 0; i < 42; i += 1) {
            var day = addDays(gridStart, i);
            var inMonth = day.getMonth() === calendarMonth.getMonth();
            var iso = formatIsoDate(day);
            var dayPosts = postsOnDay(day);
            var classes = 'smm-cal-month-cell';
            if (!inMonth) classes += ' is-outside';
            if (isSameDay(day, today)) classes += ' is-today';
            if (selectedDay && isSameDay(day, selectedDay)) classes += ' is-selected';
            var holidays = holidaysForDate(day);
            var liveCount = dayPosts.filter(function (post) { return (post.source || 'leadbird') === 'live'; }).length;
            var badgeLabel = '';
            if (dayPosts.length > 0) {
                if (liveCount === dayPosts.length) {
                    badgeLabel = dayPosts.length + ' Published via social';
                } else if (liveCount > 0) {
                    badgeLabel = dayPosts.length + ' posts';
                } else {
                    badgeLabel = dayPosts.length + ' scheduled';
                }
            }
            html += '<button type="button" class="' + classes + '" data-smm-cal-day-select="' + iso + '">' +
                '<span class="smm-cal-month-num">' + day.getDate() + '</span>' +
                '<span class="smm-cal-month-meta">';
            holidays.forEach(function (name) {
                html += '<span class="smm-cal-month-holiday">' + escapeHtml(name) + '</span>';
            });
            if (badgeLabel) {
                html += '<span class="smm-cal-month-badge font-sans">' + escapeHtml(badgeLabel) + '</span>';
            }
            html += '</span></button>';
        }
        html += '</div></div>';
        calendarEl.innerHTML = html;
        bindCalendarInteractions(calendarEl);
    }

    function renderListView() {
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var start = isSameDay(calendarWeekStart, today) || calendarWeekStart < today ? today : calendarWeekStart;
        var html = '<div class="smm-cal-list">';
        for (var i = 0; i < 14; i += 1) {
            var day = addDays(start, i);
            var iso = formatIsoDate(day);
            var dayPosts = postsOnDay(day);
            var label = isSameDay(day, today)
                ? 'Today | ' + day.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
                : day.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            html += '<section class="smm-cal-list-day">' +
                '<header class="smm-cal-list-head">' +
                '<h3 class="font-heading">' + escapeHtml(label) + ' <span class="smm-cal-list-count">(' + dayPosts.length + ' posts)</span></h3>' +
                '<button type="button" class="smm-cal-list-add-btn" data-smm-list-add="' + iso + '" aria-label="Add post">+</button>' +
                '</header><div class="smm-cal-list-body">';
            dayPosts.forEach(function (post) {
                html += renderCalendarPostCard(post, { list: true });
            });
            html += renderRecommendedTimeSuggestHtml(day, { extraClass: 'smm-cal-list-suggest', posts: dayPosts });
            html += '</div></section>';
        }
        html += '</div>';
        calendarEl.innerHTML = html;
        bindCalendarInteractions(calendarEl);
    }

    function renderCalendar() {
        if (!calendarEl) return;
        root.classList.toggle('smm-planner--month-view', calendarViewMode === 'month');
        root.classList.toggle('smm-planner--week-view', calendarViewMode === 'week');
        root.classList.toggle('smm-planner--list-view', calendarViewMode === 'list');
        updateToolbarLabel();
        renderVolumeTrack();
        if (calendarViewMode === 'list') {
            if (volumeEl) volumeEl.classList.remove('is-hidden');
            renderListView();
            return;
        }
        if (volumeEl) volumeEl.classList.add('is-hidden');
        if (calendarViewMode === 'week') {
            renderWeekView();
            return;
        }
        renderMonthView();
    }

    function formatTimezoneOffset() {
        var offset = -new Date().getTimezoneOffset() / 60;
        var sign = offset >= 0 ? '+' : '-';
        var abs = Math.abs(offset);
        var hours = String(Math.floor(abs)).padStart(2, '0');
        var mins = String(Math.round((abs % 1) * 60)).padStart(2, '0');
        return sign + hours + ':' + mins;
    }

    bindPlannerViews();
    bindCalendarNav();
    bindCalendarViewToggle();
    bindComposeModal();
    bindVideoEditorErrorActions();
    bindVideoEditChooser();
    bindTikTokOptions();
    bindComposeAvatarFallbacks();
    bindDayPanel();
    bindMonthPicker();
    bindCalendarFilters();
    bindVolumeToggle();
    bindFilters();
    bindScheduleMode();
    bindMediaUpload();
    bindGenerate();
    bindForm();
    bindModal();
    updatePreview();
    renderPosts();
    ensureCalendarData();

    (function applyPlannerUrlState() {
        var params = new URLSearchParams(window.location.search);
        var view = params.get('view');
        if (view && ['calendar', 'drafts', 'content'].indexOf(view) !== -1) {
            showView(view);
        }
        if (params.get('compose') === '1') {
            openComposeModal();
        }
    })();
})();
