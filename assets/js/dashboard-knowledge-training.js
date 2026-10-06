(function () {
    'use strict';

    function initKnowledgeTraining() {
        var root = document.querySelector('[data-kb-training]');
        var config = window.LeadBirdKnowledgeTraining || {};
        if (!root) {
            return;
        }

        var screens = root.querySelectorAll('[data-kb-screen]');
        var activeTrainingScreen = 'hub';

        function showScreen(name) {
            screens.forEach(function (screen) {
                if (!(screen instanceof HTMLElement)) {
                    return;
                }
                var isActive = screen.getAttribute('data-kb-screen') === name;
                screen.classList.toggle('is-active', isActive);
                screen.hidden = !isActive;
            });

            if (activeTrainingScreen !== name) {
                scrollActiveScreenToTop();
                activeTrainingScreen = name;
            }
        }

        window.LeadBirdKnowledgeTrainingShowScreen = showScreen;

        function normalizeLinkUrl(path) {
            var value = String(path || '').trim();
            if (value === '') {
                return '';
            }
            if (/^https?:\/\//i.test(value)) {
                return value;
            }
            return 'https://' + value.replace(/^\/+/, '');
        }

        root.querySelectorAll('[data-kb-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-kb-open') || 'hub';
                if (target === 'knowledge') {
                    resetKnowledgeForm();
                }
                if (target === 'document') {
                    resetDocumentForm();
                }
                if (target === 'link') {
                    resetLinkForm();
                }
                if (target === 'qa' && window.LeadBirdKnowledgeQa && typeof window.LeadBirdKnowledgeQa.resetQaForm === 'function') {
                    window.LeadBirdKnowledgeQa.resetQaForm();
                }
                showScreen(target);
            });
        });

        root.querySelectorAll('[data-kb-back]').forEach(function (button) {
            button.addEventListener('click', function () {
                showScreen(button.getAttribute('data-kb-back') || 'hub');
            });
        });

        function setStatus(key, message, isError) {
            var el = root.querySelector('[data-kb-status="' + key + '"]');
            if (!(el instanceof HTMLElement)) {
                return;
            }
            el.textContent = message || '';
            el.classList.toggle('is-error', Boolean(isError));
            el.classList.toggle('is-success', Boolean(message) && !isError);
        }

        function postForm(url, formData, onSuccess) {
            return fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.data || !result.data.ok) {
                        throw new Error((result.data && result.data.message) || 'Something went wrong.');
                    }
                    if (typeof onSuccess === 'function') {
                        onSuccess(result.data);
                    }
                    return result.data;
                });
        }

        var knowledgeForm = root.querySelector('[data-kb-form="knowledge"]');
        var knowledgeTextarea = root.querySelector('[data-kb-char-input]');
        var knowledgeCounter = root.querySelector('[data-kb-char-counter]');
        var knowledgeIdInput = root.querySelector('[data-kb-knowledge-id]');
        var knowledgeItems = root.querySelector('[data-kb-knowledge-items]');
        var knowledgeList = root.querySelector('[data-kb-knowledge-list]');
        var knowledgeEmpty = root.querySelector('[data-kb-knowledge-empty]');
        var knowledgeHubCount = root.querySelector('[data-kb-hub-count="knowledge"]');

        function formatKnowledgeCountLabel(count) {
            if (count <= 0) {
                return 'Add information to train your AI.';
            }
            return count === 1 ? '1 entry' : count + ' entries';
        }

        function updateKnowledgeHubCount(count) {
            if (!(knowledgeHubCount instanceof HTMLElement)) {
                return;
            }
            knowledgeHubCount.textContent = formatKnowledgeCountLabel(count);
            knowledgeHubCount.classList.toggle('kb-source-desc--count', count > 0);
        }

        function resetKnowledgeForm() {
            if (knowledgeForm instanceof HTMLFormElement) {
                knowledgeForm.reset();
            }
            if (knowledgeIdInput instanceof HTMLInputElement) {
                knowledgeIdInput.value = '';
            }
            updateKnowledgeCounter();
            updateKnowledgeSaveState();
            setStatus('knowledge', '', false);
        }

        function updateKnowledgeSaveState() {
            if (!(knowledgeForm instanceof HTMLFormElement) || !(knowledgeTextarea instanceof HTMLTextAreaElement)) {
                return;
            }
            var button = knowledgeForm.querySelector('button[type="submit"]');
            if (button instanceof HTMLButtonElement) {
                button.disabled = knowledgeTextarea.value.trim() === '';
            }
        }

        function renderKnowledgeEntries(entries) {
            if (!(knowledgeItems instanceof HTMLElement)) {
                return;
            }

            var list = entries || [];
            knowledgeItems.innerHTML = '';
            list.forEach(function (entry) {
                var li = document.createElement('li');
                li.className = 'kb-knowledge-card';
                li.setAttribute('data-kb-knowledge-id', entry.id || '');
                li.innerHTML = ''
                    + '<div class="kb-knowledge-card-head">'
                    + '<span class="kb-knowledge-card-icon" aria-hidden="true">'
                    + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'
                    + '</span>'
                    + '<h3 class="kb-knowledge-card-title font-heading"></h3>'
                    + '<span class="kb-knowledge-card-meta"></span>'
                    + '</div>'
                    + '<p class="kb-knowledge-card-preview"></p>'
                    + '<div class="kb-knowledge-card-foot">'
                    + '<span class="kb-knowledge-card-learned"></span>'
                    + '<div class="kb-knowledge-card-actions">'
                    + '<button type="button" class="kb-knowledge-card-edit" data-kb-edit-knowledge>Edit</button>'
                    + '<button type="button" class="kb-knowledge-card-remove" data-kb-delete="knowledge" data-chunk-id="">Remove</button>'
                    + '</div>'
                    + '</div>';
                li.querySelector('.kb-knowledge-card-title').textContent = entry.title || 'Knowledge entry';
                li.querySelector('.kb-knowledge-card-meta').textContent = (entry.char_count || 0) + ' characters';
                li.querySelector('.kb-knowledge-card-preview').textContent = entry.content || '';
                li.querySelector('.kb-knowledge-card-learned').textContent = entry.learned_at
                    ? 'Agent learned on ' + entry.learned_at
                    : 'Agent learned recently';
                li.querySelector('[data-kb-edit-knowledge]').setAttribute('data-knowledge-id', entry.id || '');
                li.querySelector('[data-kb-edit-knowledge]').setAttribute('data-knowledge-title', entry.title || '');
                li.querySelector('[data-kb-edit-knowledge]').setAttribute('data-knowledge-content', entry.content || '');
                li.querySelector('[data-chunk-id]').setAttribute('data-chunk-id', entry.id || '');
                knowledgeItems.appendChild(li);
            });

            if (knowledgeList instanceof HTMLElement) {
                knowledgeList.classList.toggle('is-empty', list.length === 0);
            }
            if (knowledgeEmpty instanceof HTMLElement) {
                knowledgeEmpty.hidden = list.length > 0;
            }
        }

        function updateKnowledgeCounter() {
            if (!(knowledgeTextarea instanceof HTMLTextAreaElement) || !(knowledgeCounter instanceof HTMLElement)) {
                return;
            }
            var limit = knowledgeTextarea.maxLength > 0 ? knowledgeTextarea.maxLength : 10000;
            var length = knowledgeTextarea.value.length;
            knowledgeCounter.textContent = length + '/' + limit;
            knowledgeCounter.classList.toggle('is-limit', length >= limit);
            updateKnowledgeSaveState();
        }

        if (knowledgeTextarea instanceof HTMLTextAreaElement) {
            knowledgeTextarea.addEventListener('input', updateKnowledgeCounter);
            updateKnowledgeCounter();
            updateKnowledgeSaveState();
        }

        if (knowledgeForm instanceof HTMLFormElement && config.saveTextUrl) {
            knowledgeForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = knowledgeForm.querySelector('button[type="submit"]');
                if (button instanceof HTMLButtonElement) {
                    button.disabled = true;
                }
                setStatus('knowledge', 'Saving…', false);
                postForm(config.saveTextUrl, new FormData(knowledgeForm))
                    .then(function (data) {
                        renderKnowledgeEntries(data.entries || []);
                        updateKnowledgeHubCount(data.count || 0);
                        resetKnowledgeForm();
                        showScreen('knowledge-list');
                    })
                    .catch(function (error) {
                        setStatus('knowledge', error.message, true);
                    })
                    .finally(function () {
                        if (button instanceof HTMLButtonElement) {
                            updateKnowledgeSaveState();
                        }
                    });
            });
        }

        var linkForm = root.querySelector('[data-kb-form="link"]');
        var linkUrlInput = root.querySelector('[data-kb-url-input]');
        var linkIdInput = root.querySelector('[data-kb-link-id]');
        var linkPagesBlock = root.querySelector('[data-kb-link-pages-block]');
        var linkPagesList = root.querySelector('[data-kb-link-pages-list]');
        var linkPagesCount = root.querySelector('[data-kb-link-pages-count]');
        var linkPagesHint = root.querySelector('[data-kb-link-pages-hint]');
        var linkSelectAll = root.querySelector('[data-kb-link-select-all]');
        var linkCrawlBtn = root.querySelector('[data-kb-link-crawl]');
        var linkSaveBtn = root.querySelector('[data-kb-link-save]');
        var linkRecrawlToggle = root.querySelector('[data-kb-link-recrawl-toggle]');
        var linkFrequencyWrap = root.querySelector('[data-kb-link-frequency]');
        var linkItems = root.querySelector('[data-kb-link-items]');
        var linkList = root.querySelector('[data-kb-link-list]');
        var linkEmpty = root.querySelector('[data-kb-link-empty]');
        var linkHubCount = root.querySelector('[data-kb-hub-count="link"]');
        var linkEntrySubtitle = root.querySelector('[data-kb-link-subtitle]');
        var discoveredPages = [];
        var maxSelectablePages = 10;
        var activeLinkPolls = {};
        var linkCrawlRequestId = 0;
        var linkDefaultSubtitle = linkEntrySubtitle instanceof HTMLElement
            ? linkEntrySubtitle.textContent
            : 'Add website URLs to train your agent with dynamic information.';

        function formatLinkCountLabel(count, subpageCount) {
            if (count <= 0) {
                return 'Add website URLs to train your agent with dynamic information.';
            }
            if (count === 1) {
                if (subpageCount <= 1) {
                    return '1 link';
                }
                return '1 link, ' + subpageCount + ' subpages';
            }
            return count + ' links, ' + subpageCount + ' subpages';
        }

        function updateLinkHubCount(count, subpageCount) {
            if (!(linkHubCount instanceof HTMLElement)) {
                return;
            }
            linkHubCount.textContent = formatLinkCountLabel(count, subpageCount || 0);
            linkHubCount.classList.toggle('kb-source-desc--count', count > 0);
        }

        function stripProtocol(url) {
            return String(url || '').replace(/^https?:\/\//i, '').replace(/^\/+/, '');
        }

        function linkUrlKey(url) {
            return String(url || '').trim().replace(/\/+$/, '').toLowerCase();
        }

        function parseLinkSelectedUrls(raw) {
            if (!raw) {
                return [];
            }
            try {
                var parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed.filter(Boolean).map(String) : [];
            } catch (error) {
                return [];
            }
        }

        function titleFromLinkUrl(url) {
            var path = String(url || '').replace(/^https?:\/\/[^/]+/i, '');
            var segment = path.split('/').filter(Boolean).pop() || 'Page';
            return segment.replace(/[-_]+/g, ' ').replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        }

        function mergeLinkPages(pages, selectedUrls) {
            var merged = (pages || []).slice();
            var seen = {};
            merged.forEach(function (page) {
                seen[linkUrlKey(page.url)] = true;
            });
            (selectedUrls || []).forEach(function (url) {
                var key = linkUrlKey(url);
                if (!key || seen[key]) {
                    return;
                }
                seen[key] = true;
                merged.push({
                    url: url,
                    title: titleFromLinkUrl(url),
                });
            });
            return merged;
        }

        function isLinkEditing() {
            return linkIdInput instanceof HTMLInputElement && linkIdInput.value.trim() !== '';
        }

        function updateLinkEditorChrome() {
            if (linkEntrySubtitle instanceof HTMLElement) {
                linkEntrySubtitle.textContent = isLinkEditing()
                    ? 'Refresh the page list, then choose which URLs your agent should learn from.'
                    : linkDefaultSubtitle;
            }
            if (linkSaveBtn instanceof HTMLButtonElement) {
                linkSaveBtn.textContent = isLinkEditing() ? 'Save changes' : 'Save';
            }
        }

        function updateLinkActionButtons() {
            var hasPages = discoveredPages.length > 0;
            if (linkCrawlBtn instanceof HTMLButtonElement) {
                linkCrawlBtn.hidden = false;
                linkCrawlBtn.textContent = hasPages ? 'Refresh pages' : 'Crawl';
                linkCrawlBtn.classList.toggle('kb-entry-save--refresh', hasPages);
            }
            if (linkSaveBtn instanceof HTMLButtonElement) {
                linkSaveBtn.hidden = !hasPages;
            }
            updateLinkEditorChrome();
        }

        function updateLinkPageSelectionUi() {
            if (!(linkPagesCount instanceof HTMLElement) || !(linkPagesList instanceof HTMLElement)) {
                return;
            }

            var checked = linkPagesList.querySelectorAll('[data-kb-link-page]:checked');
            var count = checked.length;
            linkPagesCount.textContent = count + '/' + maxSelectablePages;

            if (linkPagesHint instanceof HTMLElement) {
                linkPagesHint.hidden = count === 0;
            }

            var atLimit = count >= maxSelectablePages;
            linkPagesList.querySelectorAll('.kb-link-page-row').forEach(function (row) {
                if (!(row instanceof HTMLElement)) {
                    return;
                }
                var input = row.querySelector('[data-kb-link-page]');
                if (!(input instanceof HTMLInputElement)) {
                    return;
                }
                row.classList.toggle('is-locked', atLimit && !input.checked);
            });

            if (linkSelectAll instanceof HTMLInputElement) {
                var allBoxes = linkPagesList.querySelectorAll('[data-kb-link-page]');
                linkSelectAll.checked = allBoxes.length > 0 && count === allBoxes.length;
                linkSelectAll.indeterminate = count > 0 && count < allBoxes.length;
                var selectAllRow = linkSelectAll.closest('.kb-link-page-row');
                if (selectAllRow instanceof HTMLElement) {
                    selectAllRow.classList.toggle('is-locked', atLimit && count > 0 && !linkSelectAll.checked);
                }
            }
        }

        function scrollActiveScreenToTop() {
            var main = root.closest('.ai-wisdom-main');
            if (main instanceof HTMLElement) {
                main.scrollTop = 0;
            }
        }

        function bindLinkPageRow(label, input) {
            input.addEventListener('change', function () {
                updateLinkPageSelectionUi();
                updateLinkSaveState();
            });
        }

        function renderDiscoveredPages(pages, preselectedUrls) {
            var selectedKeys = {};
            (preselectedUrls || []).forEach(function (url) {
                selectedKeys[linkUrlKey(url)] = true;
            });
            var hasPresetSelection = Object.keys(selectedKeys).length > 0;

            discoveredPages = mergeLinkPages(pages || [], preselectedUrls || []);
            if (!(linkPagesList instanceof HTMLElement) || !(linkPagesBlock instanceof HTMLElement)) {
                return;
            }

            linkPagesList.innerHTML = '';
            discoveredPages.forEach(function (page, index) {
                var label = document.createElement('label');
                label.className = 'kb-link-page-row';
                label.innerHTML = '<input type="checkbox" data-kb-link-page value="">'
                    + '<span class="kb-link-page-check" aria-hidden="true"></span>'
                    + '<span class="kb-link-page-copy"><strong></strong><span></span></span>';
                var input = label.querySelector('[data-kb-link-page]');
                var titleEl = label.querySelector('strong');
                var urlEl = label.querySelector('.kb-link-page-copy span');
                if (input instanceof HTMLInputElement) {
                    input.value = page.url || '';
                    if (hasPresetSelection) {
                        input.checked = Boolean(selectedKeys[linkUrlKey(page.url)]);
                    } else {
                        input.checked = index < maxSelectablePages;
                    }
                    bindLinkPageRow(label, input);
                }
                if (titleEl) {
                    titleEl.textContent = page.title || 'Page';
                }
                if (urlEl) {
                    urlEl.textContent = page.url || '';
                }
                linkPagesList.appendChild(label);
            });

            linkPagesBlock.hidden = discoveredPages.length === 0;
            updateLinkPageSelectionUi();
            updateLinkActionButtons();
            updateLinkSaveState();
        }

        function updateLinkSaveState() {
            if (linkSaveBtn instanceof HTMLButtonElement && !linkSaveBtn.hidden) {
                linkSaveBtn.disabled = getSelectedLinkUrls().length === 0;
            }
        }

        function updateLinkCrawlState() {
            if (!(linkCrawlBtn instanceof HTMLButtonElement) || linkCrawlBtn.hidden) {
                return;
            }
            var fullUrl = linkUrlInput instanceof HTMLInputElement ? normalizeLinkUrl(linkUrlInput.value) : '';
            linkCrawlBtn.disabled = fullUrl === '';
        }

        function resetLinkForm() {
            discoveredPages = [];
            if (linkForm instanceof HTMLFormElement) {
                linkForm.reset();
            }
            if (linkIdInput instanceof HTMLInputElement) {
                linkIdInput.value = '';
            }
            renderDiscoveredPages([]);
            if (linkRecrawlToggle instanceof HTMLInputElement) {
                linkRecrawlToggle.checked = true;
            }
            updateLinkRecrawlUi();
            setStatus('link', '', false);
            updateLinkActionButtons();
            if (linkCrawlBtn instanceof HTMLButtonElement) {
                linkCrawlBtn.hidden = false;
                linkCrawlBtn.classList.remove('kb-entry-save--refresh');
            }
            if (linkSaveBtn instanceof HTMLButtonElement) {
                linkSaveBtn.hidden = true;
            }
            updateLinkCrawlState();
            updateLinkSaveState();
            updateLinkEditorChrome();
        }

        function readLinkEditorOptions(trigger) {
            if (!(trigger instanceof HTMLElement)) {
                return null;
            }
            return {
                id: trigger.getAttribute('data-link-id') || '',
                url: trigger.getAttribute('data-link-url') || '',
                selectedUrls: parseLinkSelectedUrls(trigger.getAttribute('data-link-selected-urls') || '[]'),
                recrawlEnabled: trigger.getAttribute('data-recrawl-enabled') === '1',
                recrawlFrequency: trigger.getAttribute('data-recrawl-frequency') || 'monthly',
            };
        }

        function applyLinkEditorOptions(options) {
            if (!options) {
                return;
            }
            if (linkIdInput instanceof HTMLInputElement) {
                linkIdInput.value = options.id || '';
            }
            if (linkUrlInput instanceof HTMLInputElement) {
                linkUrlInput.value = stripProtocol(options.url || '');
            }
            if (linkRecrawlToggle instanceof HTMLInputElement) {
                linkRecrawlToggle.checked = Boolean(options.recrawlEnabled);
            }
            var frequency = options.recrawlFrequency || 'monthly';
            var frequencyInput = root.querySelector('input[name="recrawl_frequency"][value="' + frequency + '"]');
            if (frequencyInput instanceof HTMLInputElement) {
                frequencyInput.checked = true;
            }
            updateLinkRecrawlUi();
            updateLinkEditorChrome();
            updateLinkCrawlState();
        }

        function runLinkCrawl(preselectedUrls) {
            if (!(linkUrlInput instanceof HTMLInputElement) || !(linkCrawlBtn instanceof HTMLButtonElement) || !config.crawlLinkUrl) {
                return;
            }

            var fullUrl = normalizeLinkUrl(linkUrlInput.value);
            if (fullUrl === '') {
                setStatus('link', 'Enter a website link.', true);
                return;
            }

            linkCrawlBtn.disabled = true;
            var requestId = ++linkCrawlRequestId;
            setStatus(
                'link',
                discoveredPages.length > 0 ? 'Refreshing pages on this website…' : 'Finding pages on this website…',
                false
            );

            var body = new FormData();
            body.append('url', fullUrl);

            postForm(config.crawlLinkUrl, body, function (data) {
                if (requestId !== linkCrawlRequestId) {
                    return;
                }
                maxSelectablePages = data.max_selectable || 10;
                if (linkUrlInput.value.indexOf('://') === -1) {
                    linkUrlInput.value = stripProtocol(data.url || fullUrl);
                }
                renderDiscoveredPages(data.pages || [], preselectedUrls || []);
                setStatus('link', 'Select the pages you want your agent to learn.', false);
            }).catch(function (error) {
                if (requestId !== linkCrawlRequestId) {
                    return;
                }
                setStatus('link', error.message, true);
            }).finally(function () {
                if (requestId !== linkCrawlRequestId) {
                    return;
                }
                linkCrawlBtn.disabled = false;
                updateLinkCrawlState();
            });
        }

        function openLinkEditor(trigger) {
            var options = readLinkEditorOptions(trigger);
            if (!options || !options.id) {
                return;
            }

            setStatus('link', '', false);
            applyLinkEditorOptions(options);

            var selectedUrls = options.selectedUrls || [];
            if (selectedUrls.length > 0) {
                renderDiscoveredPages([], selectedUrls);
            } else {
                discoveredPages = [];
                if (linkPagesList instanceof HTMLElement) {
                    linkPagesList.innerHTML = '';
                }
                if (linkPagesBlock instanceof HTMLElement) {
                    linkPagesBlock.hidden = true;
                }
                updateLinkActionButtons();
            }

            showScreen('link');
            runLinkCrawl(selectedUrls);
        }

        function updateLinkRecrawlUi() {
            var enabled = linkRecrawlToggle instanceof HTMLInputElement ? linkRecrawlToggle.checked : false;
            if (linkFrequencyWrap instanceof HTMLElement) {
                linkFrequencyWrap.classList.toggle('is-disabled', !enabled);
            }
        }

        if (linkRecrawlToggle instanceof HTMLInputElement) {
            linkRecrawlToggle.addEventListener('change', updateLinkRecrawlUi);
        }

        if (linkSelectAll instanceof HTMLInputElement && linkPagesList instanceof HTMLElement) {
            linkSelectAll.addEventListener('change', function () {
                var boxes = linkPagesList.querySelectorAll('[data-kb-link-page]');
                var limit = maxSelectablePages;
                var selected = 0;
                boxes.forEach(function (box) {
                    if (!(box instanceof HTMLInputElement)) {
                        return;
                    }
                    if (linkSelectAll.checked && selected < limit) {
                        box.checked = true;
                        selected += 1;
                    } else if (!linkSelectAll.checked) {
                        box.checked = false;
                    } else {
                        box.checked = false;
                    }
                });
                updateLinkPageSelectionUi();
                updateLinkSaveState();
            });
        }

        function getSelectedLinkUrls() {
            if (!(linkPagesList instanceof HTMLElement)) {
                return [];
            }
            var urls = [];
            linkPagesList.querySelectorAll('[data-kb-link-page]:checked').forEach(function (box) {
                if (box instanceof HTMLInputElement && box.value) {
                    urls.push(box.value);
                }
            });
            return urls;
        }

        function buildLinkCardHtml(link) {
            var subpages = link.subpages || [];
            var subpageHtml = '';
            subpages.slice(0, 4).forEach(function (page) {
                subpageHtml += '<li>' + (page.url || '') + '</li>';
            });
            if ((link.subpage_count || 0) > 4) {
                subpageHtml += '<li class="kb-link-card-more">' + ((link.subpage_count || 0) - 4) + '+ subpages more</li>';
            }

            var selectedUrlsJson = encodeDataAttr(JSON.stringify(link.selected_urls || subpages.map(function (page) {
                return page.url || '';
            }).filter(Boolean)));
            var linkUrlAttr = encodeDataAttr(link.url || '');
            var editAttrs = ' data-kb-edit-link data-link-id="' + encodeDataAttr(link.id || '') + '"'
                + ' data-link-url="' + linkUrlAttr + '"'
                + ' data-link-selected-urls="' + selectedUrlsJson + '"'
                + ' data-recrawl-enabled="' + (link.recrawl_enabled ? '1' : '0') + '"'
                + ' data-recrawl-frequency="' + encodeDataAttr(link.recrawl_frequency || 'monthly') + '"';

            var footHtml = '';
            if (link.status === 'processing') {
                footHtml = '<div class="kb-link-progress" data-kb-link-progress><div class="kb-link-progress-bar" data-kb-link-progress-bar"></div></div>'
                    + '<p class="kb-link-progress-label">Agent is gathering insights from the URL…</p>';
            } else if (link.status === 'error') {
                footHtml = '<p class="kb-link-card-error">' + (link.error_message || 'Could not crawl this link.') + '</p>'
                    + '<div class="kb-link-card-actions">'
                    + '<button type="button" class="kb-link-card-edit-btn"' + editAttrs + '>Edit</button>'
                    + '<button type="button" class="kb-link-card-remove" data-kb-delete="link" data-chunk-id="' + encodeDataAttr(link.id || '') + '">Remove</button>'
                    + '</div>';
            } else {
                footHtml = '<span class="kb-link-card-learned">Agent learned on ' + (link.learned_at || 'recently') + '</span>'
                    + '<div class="kb-link-card-actions">'
                    + '<button type="button" class="kb-link-card-edit-btn"' + editAttrs + '>Edit</button>'
                    + '<button type="button" class="kb-link-card-remove" data-kb-delete="link" data-chunk-id="' + encodeDataAttr(link.id || '') + '">Remove</button>'
                    + '</div>';
            }

            return '<div class="kb-link-card-head">'
                + '<span class="kb-link-card-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></span>'
                + '<div class="kb-link-card-copy"><h3 class="kb-link-card-title font-heading"></h3><ul class="kb-link-card-subpages">' + subpageHtml + '</ul></div>'
                + '<button type="button" class="kb-link-card-edit"' + editAttrs + ' aria-label="Edit link">'
                + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>'
                + '</button></div><div class="kb-link-card-foot">' + footHtml + '</div>';
        }

        function encodeDataAttr(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;');
        }

        function replaceLinkCard(link) {
            if (!(linkItems instanceof HTMLElement) || !link || !link.id) {
                return;
            }

            var existing = linkItems.querySelector('[data-kb-link-id="' + link.id + '"]');
            if (!(existing instanceof HTMLElement)) {
                return;
            }

            var li = existing.closest('li');
            if (!(li instanceof HTMLElement)) {
                return;
            }

            var replacement = document.createElement('li');
            replacement.className = 'kb-link-card' + (link.status === 'processing' ? ' is-processing' : '');
            replacement.setAttribute('data-kb-link-id', link.id || '');
            replacement.setAttribute('data-kb-link-status', link.status || 'ready');
            replacement.innerHTML = buildLinkCardHtml(link);
            var titleEl = replacement.querySelector('.kb-link-card-title');
            if (titleEl) {
                titleEl.textContent = link.url || link.title || 'Website link';
            }
            li.replaceWith(replacement);
            if (link.status === 'processing' && link.id) {
                startLinkProcessing(link.id);
            }
        }

        function renderLinkEntries(links) {
            if (!(linkItems instanceof HTMLElement)) {
                return;
            }
            linkItems.innerHTML = '';
            (links || []).forEach(function (link) {
                var li = document.createElement('li');
                li.className = 'kb-link-card' + (link.status === 'processing' ? ' is-processing' : '');
                li.setAttribute('data-kb-link-id', link.id || '');
                li.setAttribute('data-kb-link-status', link.status || 'ready');
                li.innerHTML = buildLinkCardHtml(link);
                li.querySelector('.kb-link-card-title').textContent = link.url || link.title || 'Website link';
                linkItems.appendChild(li);
                if (link.status === 'processing' && link.id) {
                    startLinkProcessing(link.id);
                }
            });
            if (linkList instanceof HTMLElement) {
                linkList.classList.toggle('is-empty', (links || []).length === 0);
            }
            if (linkEmpty instanceof HTMLElement) {
                linkEmpty.hidden = (links || []).length > 0;
            }
        }

        function animateLinkProgress(linkId) {
            var card = root.querySelector('[data-kb-link-id="' + linkId + '"]');
            if (!(card instanceof HTMLElement)) {
                return;
            }
            var bar = card.querySelector('[data-kb-link-progress-bar]');
            if (!(bar instanceof HTMLElement)) {
                return;
            }
            var progress = 12;
            bar.style.width = progress + '%';
            var timer = window.setInterval(function () {
                if (!card.classList.contains('is-processing')) {
                    window.clearInterval(timer);
                    return;
                }
                progress = Math.min(progress + Math.random() * 12, 92);
                bar.style.width = progress + '%';
            }, 900);
            card.setAttribute('data-kb-progress-timer', String(timer));
        }

        function startLinkProcessing(linkId) {
            if (!linkId || activeLinkPolls[linkId] || !config.processLinkUrl) {
                return;
            }

            animateLinkProgress(linkId);

            fetch(config.processLinkUrl, {
                method: 'POST',
                body: (function () {
                    var body = new FormData();
                    body.append('chunk_id', linkId);
                    return body;
                })(),
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    delete activeLinkPolls[linkId];
                    if (!result.data || !result.data.ok) {
                        throw new Error((result.data && result.data.message) || 'Could not process link.');
                    }
                    renderLinkEntries(result.data.links || []);
                    updateLinkHubCount(result.data.count || 0, result.data.subpage_count || 0);
                })
                .catch(function (error) {
                    delete activeLinkPolls[linkId];
                    if (config.linkStatusUrl) {
                        fetch(config.linkStatusUrl + '?chunk_id=' + encodeURIComponent(linkId), {
                            credentials: 'same-origin',
                            headers: { Accept: 'application/json' },
                        })
                            .then(function (response) {
                                return response.json();
                            })
                            .then(function (data) {
                                if (data && data.ok && data.link) {
                                    replaceLinkCard(data.link);
                                }
                            })
                            .catch(function () {
                                setStatus('link', error.message, true);
                            });
                    }
                });

            activeLinkPolls[linkId] = true;
        }

        if (linkItems instanceof HTMLElement) {
            linkItems.querySelectorAll('[data-kb-link-status="processing"]').forEach(function (card) {
                if (card instanceof HTMLElement) {
                    startLinkProcessing(card.getAttribute('data-kb-link-id') || '');
                }
            });
        }

        if (linkCrawlBtn instanceof HTMLButtonElement && config.crawlLinkUrl) {
            linkCrawlBtn.addEventListener('click', function () {
                runLinkCrawl(getSelectedLinkUrls());
            });
        }

        if (linkForm instanceof HTMLFormElement && config.saveLinkUrl) {
            linkForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!(linkUrlInput instanceof HTMLInputElement)) {
                    return;
                }

                var fullUrl = normalizeLinkUrl(linkUrlInput.value);
                var selectedUrls = getSelectedLinkUrls();
                if (fullUrl === '') {
                    setStatus('link', 'Enter a website link.', true);
                    return;
                }
                if (selectedUrls.length === 0) {
                    setStatus('link', 'Select at least one page.', true);
                    return;
                }

                if (linkSaveBtn instanceof HTMLButtonElement) {
                    linkSaveBtn.disabled = true;
                }
                setStatus('link', isLinkEditing() ? 'Saving changes…' : 'Saving link…', false);

                var body = new FormData();
                body.append('url', fullUrl);
                selectedUrls.forEach(function (url) {
                    body.append('selected_urls[]', url);
                });
                if (linkIdInput instanceof HTMLInputElement && linkIdInput.value) {
                    body.append('chunk_id', linkIdInput.value);
                }
                if (linkRecrawlToggle instanceof HTMLInputElement) {
                    body.append('recrawl_enabled', linkRecrawlToggle.checked ? '1' : '0');
                }
                var frequency = linkForm.querySelector('input[name="recrawl_frequency"]:checked');
                if (frequency instanceof HTMLInputElement) {
                    body.append('recrawl_frequency', frequency.value);
                }

                postForm(config.saveLinkUrl, body, function (data) {
                    renderLinkEntries(data.links || []);
                    updateLinkHubCount(data.count || 0, data.subpage_count || 0);
                    resetLinkForm();
                    showScreen('link-list');
                    if (data.link && data.link.id && data.link.status === 'processing') {
                        startLinkProcessing(data.link.id);
                    }
                }).catch(function (error) {
                    setStatus('link', error.message, true);
                }).finally(function () {
                    if (linkSaveBtn instanceof HTMLButtonElement) {
                        updateLinkSaveState();
                    }
                });
            });
        }

        updateLinkRecrawlUi();
        updateLinkActionButtons();
        updateLinkCrawlState();
        if (linkUrlInput instanceof HTMLInputElement) {
            linkUrlInput.addEventListener('input', updateLinkCrawlState);
        }

        var documentForm = root.querySelector('[data-kb-form="document"]');
        var dropzone = root.querySelector('[data-kb-dropzone]');
        var fileInput = root.querySelector('[data-kb-file-input]');
        var fileTrigger = root.querySelector('[data-kb-file-trigger]');
        var fileChip = root.querySelector('[data-kb-file-chip]');
        var fileChipName = root.querySelector('[data-kb-file-chip-name]');
        var fileClear = root.querySelector('[data-kb-file-clear]');
        var fileUsageInput = root.querySelector('[data-kb-file-usage-input]');
        var fileUsageCounter = root.querySelector('[data-kb-file-usage-counter]');
        var fileItems = root.querySelector('[data-kb-file-items]');
        var fileList = root.querySelector('[data-kb-file-list]');
        var fileEmpty = root.querySelector('[data-kb-file-empty]');
        var documentHubCount = root.querySelector('[data-kb-hub-count="document"]');

        function formatFileCountLabel(count) {
            if (count <= 0) {
                return 'Upload files to train your agent.';
            }
            return count === 1 ? '1 file' : count + ' files';
        }

        function updateDocumentHubCount(count) {
            if (!(documentHubCount instanceof HTMLElement)) {
                return;
            }
            documentHubCount.textContent = formatFileCountLabel(count);
            documentHubCount.classList.toggle('kb-source-desc--count', count > 0);
        }

        function updateFileSelection() {
            if (!(fileInput instanceof HTMLInputElement)) {
                return;
            }
            var hasFile = Boolean(fileInput.files && fileInput.files[0]);
            if (fileChip instanceof HTMLElement && fileChipName instanceof HTMLElement) {
                if (hasFile) {
                    fileChipName.textContent = fileInput.files[0].name;
                    fileChip.hidden = false;
                } else {
                    fileChip.hidden = true;
                    fileChipName.textContent = '';
                }
            }
            if (dropzone instanceof HTMLElement) {
                dropzone.hidden = hasFile;
            }
            updateDocumentSaveState();
        }

        function updateDocumentSaveState() {
            if (!(documentForm instanceof HTMLFormElement) || !(fileInput instanceof HTMLInputElement)) {
                return;
            }
            var button = documentForm.querySelector('button[type="submit"]');
            var hasFile = Boolean(fileInput.files && fileInput.files[0]);
            if (button instanceof HTMLButtonElement) {
                button.disabled = !hasFile;
            }
        }

        function updateFileUsageCounter() {
            if (!(fileUsageInput instanceof HTMLTextAreaElement) || !(fileUsageCounter instanceof HTMLElement)) {
                return;
            }
            var limit = fileUsageInput.maxLength > 0 ? fileUsageInput.maxLength : 300;
            var length = fileUsageInput.value.length;
            fileUsageCounter.textContent = length + '/' + limit;
            fileUsageCounter.classList.toggle('is-limit', length >= limit);
        }

        function resetDocumentForm() {
            if (documentForm instanceof HTMLFormElement) {
                documentForm.reset();
            }
            updateFileSelection();
            updateFileUsageCounter();
            updateDocumentSaveState();
            setStatus('document', '', false);
        }

        function renderFileEntries(documents) {
            if (!(fileItems instanceof HTMLElement)) {
                return;
            }
            fileItems.innerHTML = '';
            (documents || []).forEach(function (doc) {
                var li = document.createElement('li');
                li.className = 'kb-file-card';
                li.setAttribute('data-kb-file-id', doc.id || '');
                var previewHtml = doc.is_image && doc.preview_url
                    ? '<div class="kb-file-card-preview"><img src="' + doc.preview_url + '" alt="" loading="lazy" decoding="async"></div>'
                    : '';
                li.innerHTML = previewHtml
                    + '<div class="kb-file-card-body">'
                    + '<div class="kb-file-card-head">'
                    + '<span class="kb-file-card-icon" aria-hidden="true"><img src="' + (config.documentIconUrl || '') + '" alt="" width="16" height="16"></span>'
                    + '<div class="kb-file-card-copy"><h3 class="kb-file-card-title font-heading"></h3><p class="kb-file-card-subtitle"></p></div>'
                    + '</div>'
                    + '<div class="kb-file-card-foot"><span class="kb-file-card-learned"></span>'
                    + '<button type="button" class="kb-file-card-remove" data-kb-delete="document" data-chunk-id="">Remove</button>'
                    + '</div></div>';
                li.querySelector('.kb-file-card-title').textContent = doc.title || 'Uploaded file';
                li.querySelector('.kb-file-card-subtitle').textContent = doc.subheading || doc.file_name || '';
                li.querySelector('.kb-file-card-learned').textContent = doc.learned_at
                    ? 'Agent learned on ' + doc.learned_at
                    : 'Agent learned recently';
                li.querySelector('[data-chunk-id]').setAttribute('data-chunk-id', doc.id || '');
                fileItems.appendChild(li);
            });
            if (fileList instanceof HTMLElement) {
                fileList.classList.toggle('is-empty', (documents || []).length === 0);
            }
            if (fileEmpty instanceof HTMLElement) {
                fileEmpty.hidden = (documents || []).length > 0;
            }
        }

        function openFilePicker() {
            if (fileInput instanceof HTMLInputElement) {
                fileInput.click();
            }
        }

        if (dropzone instanceof HTMLElement && fileInput instanceof HTMLInputElement) {
            dropzone.addEventListener('click', function (event) {
                if (event.target instanceof HTMLElement && event.target.closest('[data-kb-file-trigger]')) {
                    return;
                }
                openFilePicker();
            });
            dropzone.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openFilePicker();
                }
            });
            if (fileTrigger instanceof HTMLButtonElement) {
                fileTrigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    openFilePicker();
                });
            }
            if (fileClear instanceof HTMLButtonElement) {
                fileClear.addEventListener('click', function () {
                    fileInput.value = '';
                    updateFileSelection();
                });
            }
            fileInput.addEventListener('change', updateFileSelection);
            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                });
            });
            dropzone.addEventListener('drop', function (event) {
                if (event.dataTransfer && event.dataTransfer.files.length > 0) {
                    fileInput.files = event.dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        if (fileUsageInput instanceof HTMLTextAreaElement) {
            fileUsageInput.addEventListener('input', updateFileUsageCounter);
            updateFileUsageCounter();
        }

        if (documentForm instanceof HTMLFormElement && config.uploadDocumentUrl) {
            documentForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = documentForm.querySelector('button[type="submit"]');
                if (!(fileInput instanceof HTMLInputElement) || !fileInput.files || !fileInput.files[0]) {
                    setStatus('document', 'Choose a file first.', true);
                    return;
                }
                if (button instanceof HTMLButtonElement) {
                    button.disabled = true;
                }
                setStatus('document', 'Reading file and training your agent…', false);
                postForm(config.uploadDocumentUrl, new FormData(documentForm), function (data) {
                    renderFileEntries(data.documents || []);
                    updateDocumentHubCount(data.count || 0);
                    resetDocumentForm();
                    showScreen('document-list');
                }).catch(function (error) {
                    setStatus('document', error.message, true);
                }).finally(function () {
                    if (button instanceof HTMLButtonElement) {
                        updateDocumentSaveState();
                    }
                });
            });
        }

        root.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            var editKnowledge = target.closest('[data-kb-edit-knowledge]');
            if (editKnowledge instanceof HTMLElement) {
                if (knowledgeIdInput instanceof HTMLInputElement) {
                    knowledgeIdInput.value = editKnowledge.getAttribute('data-knowledge-id') || '';
                }
                if (knowledgeTextarea instanceof HTMLTextAreaElement) {
                    knowledgeTextarea.value = editKnowledge.getAttribute('data-knowledge-content') || '';
                }
                updateKnowledgeCounter();
                updateKnowledgeSaveState();
                showScreen('knowledge');
                if (knowledgeTextarea instanceof HTMLElement) {
                    knowledgeTextarea.focus();
                }
                return;
            }

            var editLink = target.closest('[data-kb-edit-link]');
            if (editLink instanceof HTMLElement) {
                openLinkEditor(editLink);
                return;
            }

            var deleteItem = target.closest('[data-kb-delete]');
            if (deleteItem instanceof HTMLElement && config.deleteItemUrl && window.LeadBirdModal) {
                var type = deleteItem.getAttribute('data-kb-delete') || '';
                var chunkId = deleteItem.getAttribute('data-chunk-id') || '';
                if (!chunkId) {
                    return;
                }
                window.LeadBirdModal.confirm({
                    title: 'Remove this item?',
                    message: 'Your AI will forget what it learned from it.',
                    confirmLabel: 'Remove',
                    tone: 'danger',
                }).then(function (ok) {
                    if (!ok) {
                        return;
                    }
                    var body = new FormData();
                    body.append('type', type);
                    body.append('chunk_id', chunkId);
                    postForm(config.deleteItemUrl, body, function (data) {
                        var row = deleteItem.closest('[data-kb-item-id], [data-kb-knowledge-id], [data-kb-file-id], [data-kb-link-id]');
                        if (row instanceof HTMLElement) {
                            row.remove();
                        }
                        if (type === 'knowledge') {
                            renderKnowledgeEntries(data.entries || []);
                            updateKnowledgeHubCount(data.count || 0);
                        }
                        if (type === 'document') {
                            renderFileEntries(data.documents || []);
                            updateDocumentHubCount(data.count || 0);
                        }
                        if (type === 'link') {
                            renderLinkEntries(data.links || []);
                            updateLinkHubCount(data.count || 0, data.subpage_count || 0);
                        }
                    }).catch(function (error) {
                        window.LeadBirdModal.alert({ title: 'Could not remove', message: error.message, tone: 'error' });
                    });
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKnowledgeTraining);
    } else {
        initKnowledgeTraining();
    }
})();
