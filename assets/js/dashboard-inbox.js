(function () {
    var app = document.querySelector('.inbox-app');
    if (!app) {
        return;
    }

    var searchInput = document.querySelector('[data-inbox-search]');
    var threadList = document.querySelector('[data-inbox-thread-list]');
    var searchEmpty = document.querySelector('[data-inbox-search-empty]');
    var channelFilter = document.querySelector('[data-inbox-channel-filter]');
    var sortFilter = document.querySelector('[data-inbox-sort]');
    var selectedLabelId = app.getAttribute('data-selected-label') || '';
    var favoritesLabelId = app.getAttribute('data-favorites-label-id') || '';
    var appUrl = window.location.origin;
    var inboxLabels = [];
    try {
        inboxLabels = JSON.parse(app.getAttribute('data-inbox-labels-json') || '[]');
    } catch (error) {
        inboxLabels = [];
    }

    var threadMenu = document.querySelector('[data-inbox-thread-menu]');
    var threadMenuTitle = document.querySelector('[data-inbox-thread-menu-title]');
    var threadMenuLabels = document.querySelector('[data-inbox-thread-menu-labels]');
    var threadMenuDelete = document.querySelector('[data-inbox-thread-menu-delete]');
    var threadMenuAddLabel = document.querySelector('[data-inbox-thread-menu-add-label]');
    var activeThreadMenuTrigger = null;
    var activeThreadMenuConversationId = '';

    function getThreadLabelIds(thread) {
        var raw = thread.getAttribute('data-label-ids') || '';
        if (raw === '') {
            return [];
        }
        return raw.split(',').filter(function (id) {
            return id !== '';
        });
    }

    function setThreadLabelIds(thread, labelIds) {
        thread.setAttribute('data-label-ids', labelIds.join(','));
        if (favoritesLabelId !== '') {
            thread.classList.toggle('is-favorite', labelIds.indexOf(favoritesLabelId) !== -1);
        }
    }

    function refreshLabelCounts() {
        var counts = {};
        getThreads().forEach(function (thread) {
            getThreadLabelIds(thread).forEach(function (labelId) {
                counts[labelId] = (counts[labelId] || 0) + 1;
            });
        });
        document.querySelectorAll('[data-inbox-label-count]').forEach(function (el) {
            var labelId = el.getAttribute('data-inbox-label-count') || '';
            if (labelId !== '') {
                el.textContent = String(counts[labelId] || 0);
            }
        });
    }

    function applyThreadFilters() {
        if (!threadList) {
            return;
        }

        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var channelValue = channelFilter ? channelFilter.value : '';
        var labelFilter = selectedLabelId;
        var visibleCount = 0;
        var threads = getThreads();
        var rows = Array.prototype.slice.call(threads);

        if (sortFilter && rows.length > 1) {
            var sortDir = sortFilter.value === 'oldest' ? 1 : -1;
            rows.sort(function (a, b) {
                var aTime = Date.parse(a.getAttribute('data-timestamp') || '') || 0;
                var bTime = Date.parse(b.getAttribute('data-timestamp') || '') || 0;
                return (aTime - bTime) * sortDir;
            });
            rows.forEach(function (row) {
                threadList.appendChild(row);
            });
        }

        rows.forEach(function (thread) {
            var haystack = (thread.getAttribute('data-search-text') || '').toLowerCase();
            var matchesSearch = query === '' || haystack.indexOf(query) !== -1;
            var threadPlatform = thread.getAttribute('data-platform') || '';
            var matchesChannel = channelValue === '' || threadPlatform === channelValue;
            var labelIds = getThreadLabelIds(thread);
            var matchesLabel = labelFilter === '' || labelIds.indexOf(labelFilter) !== -1;
            var matches = matchesSearch && matchesChannel && matchesLabel;

            thread.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        if (searchEmpty) {
            var showEmpty = visibleCount === 0 && (query !== '' || channelValue !== '' || labelFilter !== '');
            searchEmpty.hidden = !showEmpty;
            searchEmpty.classList.toggle('is-hidden', !showEmpty);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyThreadFilters);
    }

    if (channelFilter) {
        channelFilter.addEventListener('change', applyThreadFilters);
    }

    if (sortFilter) {
        sortFilter.addEventListener('change', applyThreadFilters);
    }

    function getThreads() {
        return threadList ? threadList.querySelectorAll('[data-inbox-thread]') : [];
    }

    function updateLabelToggleUi(conversationId, labelId, assigned, labelIds) {
        document.querySelectorAll('[data-inbox-label-toggle]').forEach(function (btn) {
            if (
                btn.getAttribute('data-conversation-id') === conversationId
                && btn.getAttribute('data-label-id') === labelId
            ) {
                btn.classList.toggle('is-active', assigned);
                btn.setAttribute('aria-pressed', assigned ? 'true' : 'false');
            }
        });

        if (threadMenu && activeThreadMenuConversationId === conversationId) {
            updateLabelMenuItemState(labelId, assigned);
        }

        if (threadList) {
            threadList.querySelectorAll('[data-inbox-thread]').forEach(function (thread) {
                if (thread.getAttribute('data-conversation-id') === conversationId) {
                    setThreadLabelIds(thread, labelIds);
                }
            });
        }

        refreshLabelCounts();
        applyThreadFilters();
    }

    function handleThreadMenuLabelClick(menuLabelBtn) {
        if (!menuLabelBtn || activeThreadMenuConversationId === '') {
            return;
        }

        var menuLabelId = menuLabelBtn.getAttribute('data-inbox-thread-menu-label') || '';
        if (menuLabelId === '') {
            return;
        }

        menuLabelBtn.disabled = true;
        toggleLabelAssignment(activeThreadMenuConversationId, menuLabelId)
            .catch(function (error) {
                if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                    window.LeadBirdModal.alert({
                        title: 'Something went wrong',
                        message: error.message || 'Could not update label.',
                        tone: 'error',
                    });
                }
            })
            .finally(function () {
                menuLabelBtn.disabled = false;
            });
    }

    function handleThreadMenuDeleteClick(menuDeleteBtn) {
        if (!menuDeleteBtn || activeThreadMenuConversationId === '') {
            return;
        }

        var deleteConversationId = activeThreadMenuConversationId;
        var confirmDeleteChat = function () {
            menuDeleteBtn.disabled = true;
            deleteConversation(deleteConversationId)
                .catch(function (error) {
                    if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                        window.LeadBirdModal.alert({
                            title: 'Something went wrong',
                            message: error.message || 'Could not delete chat.',
                            tone: 'error',
                        });
                    }
                })
                .finally(function () {
                    menuDeleteBtn.disabled = false;
                });
        };

        if (window.LeadBirdModal && typeof window.LeadBirdModal.confirm === 'function') {
            window.LeadBirdModal.confirm({
                title: 'Delete this chat?',
                message: 'This permanently removes all conversations with this person, clears what the AI remembers about them, and cannot be undone.',
                confirmLabel: 'Delete',
                tone: 'danger',
            }).then(function (ok) {
                if (ok) {
                    confirmDeleteChat();
                }
            });
        } else {
            confirmDeleteChat();
        }
    }

    if (threadMenu) {
        threadMenu.addEventListener('click', function (event) {
            event.stopPropagation();

            var menuDeleteBtn = event.target.closest('[data-inbox-thread-menu-delete]');
            if (menuDeleteBtn) {
                event.preventDefault();
                handleThreadMenuDeleteClick(menuDeleteBtn);
                return;
            }

            var menuLabelBtn = event.target.closest('[data-inbox-thread-menu-label]');
            if (menuLabelBtn) {
                event.preventDefault();
                handleThreadMenuLabelClick(menuLabelBtn);
                return;
            }

            var menuAddLabelBtn = event.target.closest('[data-inbox-thread-menu-add-label]');
            if (menuAddLabelBtn) {
                event.preventDefault();
                closeThreadMenu();
                if (typeof openLabelModal === 'function') {
                    openLabelModal();
                }
            }
        });
    }

    window.addEventListener('resize', closeThreadMenu);
    window.addEventListener('scroll', closeThreadMenu, true);

    function closeThreadMenu() {
        if (!threadMenu) {
            return;
        }

        threadMenu.hidden = true;
        if (activeThreadMenuTrigger) {
            activeThreadMenuTrigger.setAttribute('aria-expanded', 'false');
            activeThreadMenuTrigger = null;
        }
        activeThreadMenuConversationId = '';
    }

    function positionThreadMenu(trigger) {
        if (!threadMenu || !(trigger instanceof HTMLElement)) {
            return;
        }

        var gap = 6;
        var viewportPadding = 12;
        threadMenu.hidden = false;
        threadMenu.style.visibility = 'hidden';
        threadMenu.style.top = '0px';
        threadMenu.style.left = '0px';

        var triggerRect = trigger.getBoundingClientRect();
        var menuWidth = threadMenu.offsetWidth;
        var menuHeight = threadMenu.offsetHeight;
        var spaceBelow = window.innerHeight - triggerRect.bottom;
        var spaceAbove = triggerRect.top;
        var openUp = spaceBelow < menuHeight + gap && spaceAbove > spaceBelow;

        var top = openUp
            ? Math.max(viewportPadding, triggerRect.top - menuHeight - gap)
            : Math.min(window.innerHeight - menuHeight - viewportPadding, triggerRect.bottom + gap);
        var left = Math.max(viewportPadding, triggerRect.right - menuWidth);

        threadMenu.style.top = Math.round(top) + 'px';
        threadMenu.style.left = Math.round(left) + 'px';
        threadMenu.style.visibility = '';
    }

    function createLabelIcon(slug) {
        var icon = document.createElement('span');
        icon.className = 'inbox-thread-menu-label-icon';
        icon.setAttribute('aria-hidden', 'true');

        if (slug === 'favorites') {
            icon.classList.add('inbox-thread-menu-label-icon--favorite');
            icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
            return icon;
        }

        icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
        return icon;
    }

    function createLabelCheck() {
        var check = document.createElement('span');
        check.className = 'inbox-thread-menu-label-check';
        check.setAttribute('aria-hidden', 'true');
        check.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        return check;
    }

    function renderThreadMenuLabels(conversationId, labelIds) {
        if (!threadMenuLabels) {
            return;
        }

        threadMenuLabels.innerHTML = '';
        if (!Array.isArray(inboxLabels) || inboxLabels.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'inbox-thread-menu-empty';
            empty.textContent = 'No labels yet. Create one to organize this chat.';
            threadMenuLabels.appendChild(empty);
            return;
        }

        inboxLabels.forEach(function (label) {
            var labelId = label.id || '';
            if (labelId === '') {
                return;
            }

            var assigned = labelIds.indexOf(labelId) !== -1;
            var slug = label.slug || '';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'inbox-thread-menu-label' + (assigned ? ' is-active' : '');
            btn.setAttribute('role', 'menuitemcheckbox');
            btn.setAttribute('aria-checked', assigned ? 'true' : 'false');
            btn.setAttribute('data-inbox-thread-menu-label', labelId);

            var text = document.createElement('span');
            text.className = 'inbox-thread-menu-label-text';
            text.textContent = label.name || 'Label';

            btn.appendChild(createLabelIcon(slug));
            btn.appendChild(text);
            btn.appendChild(createLabelCheck());
            threadMenuLabels.appendChild(btn);
        });
    }

    function updateLabelMenuItemState(labelId, assigned) {
        if (!threadMenuLabels) {
            return;
        }

        threadMenuLabels.querySelectorAll('[data-inbox-thread-menu-label]').forEach(function (btn) {
            if (btn.getAttribute('data-inbox-thread-menu-label') !== labelId) {
                return;
            }
            btn.classList.toggle('is-active', assigned);
            btn.setAttribute('aria-checked', assigned ? 'true' : 'false');
        });
    }

    function openThreadMenu(trigger, thread) {
        if (!threadMenu || !trigger || !thread) {
            return;
        }

        var conversationId = thread.getAttribute('data-conversation-id') || '';
        if (conversationId === '') {
            return;
        }

        var customerName = thread.getAttribute('data-customer-name') || 'Conversation';
        if (threadMenuTitle) {
            threadMenuTitle.textContent = customerName;
        }

        var labelIds = getThreadLabelIds(thread);
        activeThreadMenuConversationId = conversationId;
        renderThreadMenuLabels(conversationId, labelIds);
        positionThreadMenu(trigger);

        if (activeThreadMenuTrigger && activeThreadMenuTrigger !== trigger) {
            activeThreadMenuTrigger.setAttribute('aria-expanded', 'false');
        }

        activeThreadMenuTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
    }

    function removeDeletedThreads(result, fallbackConversationId) {
        var removedIds = {};
        var removedCustomerId = '';

        if (result && result.data) {
            if (Array.isArray(result.data.conversation_ids)) {
                result.data.conversation_ids.forEach(function (id) {
                    if (id) {
                        removedIds[id] = true;
                    }
                });
            }
            removedCustomerId = result.data.customer_id || '';
        }

        if (Object.keys(removedIds).length === 0 && fallbackConversationId) {
            removedIds[fallbackConversationId] = true;
        }

        if (!threadList) {
            return;
        }

        threadList.querySelectorAll('[data-inbox-thread]').forEach(function (thread) {
            var conversationId = thread.getAttribute('data-conversation-id') || '';
            var customerId = thread.getAttribute('data-customer-id') || '';
            if (removedIds[conversationId] || (removedCustomerId !== '' && customerId === removedCustomerId)) {
                thread.remove();
            }
        });
    }

    function deleteConversation(conversationId) {
        return fetch(appUrl + '/inbox/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ conversation_id: conversationId }),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    throw new Error(result.data.message || result.data.error || 'Could not delete chat.');
                }

                var params = new URLSearchParams(window.location.search);
                var currentConversation = params.get('conversation') || '';
                closeThreadMenu();

                var deletedCurrent = currentConversation !== ''
                    && (
                        (Array.isArray(result.data.conversation_ids) && result.data.conversation_ids.indexOf(currentConversation) !== -1)
                        || currentConversation === conversationId
                    );

                if (deletedCurrent) {
                    params.delete('conversation');
                    var nextQuery = params.toString();
                    window.location.href = window.location.pathname + (nextQuery ? '?' + nextQuery : '');
                    return;
                }

                removeDeletedThreads(result, conversationId);
                refreshLabelCounts();
                applyThreadFilters();
            });
    }

    function toggleLabelAssignment(conversationId, labelId) {
        return fetch(appUrl + '/inbox/labels/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                conversation_id: conversationId,
                label_id: labelId,
            }),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    throw new Error(result.data.message || 'Could not update label.');
                }
                updateLabelToggleUi(
                    conversationId,
                    labelId,
                    !!result.data.assigned,
                    Array.isArray(result.data.label_ids) ? result.data.label_ids : []
                );
                return result.data;
            });
    }

    document.addEventListener('click', function (event) {
        var menuTrigger = event.target.closest('[data-inbox-thread-menu-trigger]');
        if (menuTrigger) {
            event.preventDefault();
            event.stopPropagation();
            var thread = menuTrigger.closest('[data-inbox-thread]');
            if (!thread) {
                return;
            }

            if (activeThreadMenuTrigger === menuTrigger && threadMenu && !threadMenu.hidden) {
                closeThreadMenu();
                return;
            }

            openThreadMenu(menuTrigger, thread);
            return;
        }

        if (threadMenu && !threadMenu.hidden && !event.target.closest('[data-inbox-thread-menu]')) {
            closeThreadMenu();
        }

        var toggleBtn = event.target.closest('[data-inbox-label-toggle]');
        if (toggleBtn) {
            event.preventDefault();
            var conversationId = toggleBtn.getAttribute('data-conversation-id') || '';
            var labelId = toggleBtn.getAttribute('data-label-id') || '';
            if (conversationId === '' || labelId === '') {
                return;
            }
            toggleBtn.disabled = true;
            toggleLabelAssignment(conversationId, labelId)
                .catch(function (error) {
                    if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                        window.LeadBirdModal.alert({
                            title: 'Something went wrong',
                            message: error.message || 'Could not update label.',
                            tone: 'error',
                        });
                    }
                })
                .finally(function () {
                    toggleBtn.disabled = false;
                });
            return;
        }

        var deleteBtn = event.target.closest('[data-inbox-label-delete]');
        if (deleteBtn) {
            event.preventDefault();
            event.stopPropagation();
            var deleteLabelId = deleteBtn.getAttribute('data-label-id') || '';
            var deleteLabelName = deleteBtn.getAttribute('data-label-name') || 'this label';
            var confirmDelete = function () {
                fetch(appUrl + '/inbox/labels/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ label_id: deleteLabelId }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.data.ok) {
                            throw new Error(result.data.message || 'Could not delete label.');
                        }
                        window.location.href = window.location.pathname;
                    })
                    .catch(function (error) {
                        if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                            window.LeadBirdModal.alert({
                                title: 'Something went wrong',
                                message: error.message || 'Could not delete label.',
                                tone: 'error',
                            });
                        }
                    });
            };

            if (window.LeadBirdModal && typeof window.LeadBirdModal.confirm === 'function') {
                window.LeadBirdModal.confirm({
                    title: 'Delete this label?',
                    message: 'Chats will stay in your inbox. Only the label "' + deleteLabelName + '" will be removed.',
                    confirmLabel: 'Delete',
                    tone: 'danger',
                }).then(function (ok) {
                    if (ok) {
                        confirmDelete();
                    }
                });
            } else {
                confirmDelete();
            }
        }
    });

    var labelModal = document.querySelector('[data-inbox-label-modal]');
    var labelForm = document.querySelector('[data-inbox-label-form]');
    var labelNameInput = document.querySelector('[data-inbox-label-name-input]');
    var labelFormError = document.querySelector('[data-inbox-label-form-error]');

    function openLabelModal() {
        if (!labelModal) {
            return;
        }
        labelModal.hidden = false;
        if (labelFormError) {
            labelFormError.hidden = true;
            labelFormError.classList.add('is-hidden');
            labelFormError.textContent = '';
        }
        if (labelNameInput) {
            labelNameInput.value = '';
            labelNameInput.focus();
        }
    }

    function closeLabelModal() {
        if (labelModal) {
            labelModal.hidden = true;
        }
    }

    document.querySelectorAll('[data-inbox-label-add]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            openLabelModal();
        });
    });

    document.querySelectorAll('[data-inbox-label-modal-close], .inbox-label-modal-backdrop').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeLabelModal();
        });
    });

    if (labelForm) {
        labelForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!labelNameInput) {
                return;
            }
            var name = labelNameInput.value.trim();
            if (name === '') {
                if (labelFormError) {
                    labelFormError.textContent = 'Enter a label name.';
                    labelFormError.hidden = false;
                    labelFormError.classList.remove('is-hidden');
                }
                return;
            }

            fetch(appUrl + '/inbox/labels/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ name: name }),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data.ok) {
                        throw new Error(result.data.message || 'Could not create label.');
                    }
                    window.location.reload();
                })
                .catch(function (error) {
                    if (labelFormError) {
                        labelFormError.textContent = error.message || 'Could not create label.';
                        labelFormError.hidden = false;
                        labelFormError.classList.remove('is-hidden');
                    }
                });
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && threadMenu && !threadMenu.hidden) {
            closeThreadMenu();
        }
    });

    document.querySelectorAll('[data-inbox-avatar-img]').forEach(function (img) {
        img.addEventListener('error', function () {
            img.classList.add('is-hidden');
            var wrap = img.closest('.inbox-thread-row-avatar, .inbox-chat-avatar, .inbox-message-avatar, .inbox-profile-avatar');
            if (!wrap) {
                return;
            }
            var initials = wrap.querySelector('[class*="-initials"]');
            if (initials) {
                initials.classList.remove('is-hidden');
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && labelModal && !labelModal.hidden) {
            closeLabelModal();
        }
    });

    if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.init === 'function') {
        var toolbar = document.querySelector('.inbox-toolbar');
        if (toolbar) {
            window.LeadBirdDropdown.init(toolbar);
        }
    }

    applyThreadFilters();

    var rail = document.querySelector('[data-inbox-message-rail]');

    function scrollRailToBottom() {
        if (!rail) return;
        var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        rail.scrollTo({
            top: rail.scrollHeight,
            behavior: prefersReducedMotion ? 'auto' : 'smooth',
        });
    }

    if (rail && document.querySelector('.inbox-main-body.has-thread')) {
        scrollRailToBottom();
    }

    var toggle = document.querySelector('[data-inbox-details-toggle]');
    var panel = document.querySelector('[data-inbox-details-panel]');
    var closeBtn = document.querySelector('[data-inbox-details-close]');
    var chatPanel = document.getElementById('selected-thread');

    if (toggle && panel && chatPanel) {
        var backdrop = null;

        function setOpen(isOpen) {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            panel.hidden = !isOpen;

            if (isOpen) {
                if (!backdrop) {
                    backdrop = document.createElement('button');
                    backdrop.type = 'button';
                    backdrop.className = 'inbox-details-backdrop';
                    backdrop.setAttribute('aria-label', 'Close details');
                    backdrop.addEventListener('click', function () {
                        setOpen(false);
                    });
                }
                chatPanel.insertBefore(backdrop, panel);
                return;
            }

            if (backdrop && backdrop.parentNode) {
                backdrop.parentNode.removeChild(backdrop);
            }
        }

        toggle.addEventListener('click', function () {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                setOpen(false);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
            }
        });
    }

    var compose = document.querySelector('[data-inbox-compose]');
    var conversationId = (compose && compose.getAttribute('data-conversation-id')) || '';
    var ownerName = (compose && compose.getAttribute('data-owner-name')) || 'You';
    var ownerInitials = (compose && compose.getAttribute('data-owner-initials')) || 'YO';
    var form = compose ? compose.querySelector('[data-inbox-compose-form]') : null;
    var input = compose ? compose.querySelector('[data-inbox-compose-input]') : null;
    var sendBtn = compose ? compose.querySelector('.inbox-compose-send') : null;
    var aiToggle = compose ? compose.querySelector('[data-inbox-ai-toggle]') : null;
    var statusCopy = compose ? compose.querySelector('[data-inbox-compose-status]') : null;
    var errorEl = null;

    function setAiAutoReply(enabled) {
        if (!compose) return;
        compose.setAttribute('data-ai-auto-reply', enabled ? '1' : '0');
        if (aiToggle) {
            aiToggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
            aiToggle.setAttribute(
                'aria-label',
                enabled ? 'Turn off AI replies for this chat' : 'Turn on AI replies for this chat'
            );
        }
        if (statusCopy) {
            statusCopy.textContent = enabled
                ? 'Hannah replies automatically. You can jump in any time.'
                : 'AI is paused for this chat. Your replies go straight to the customer.';
        }
    }

    function clearError() {
        if (errorEl && errorEl.parentNode) {
            errorEl.parentNode.removeChild(errorEl);
            errorEl = null;
        }
    }

    function showError(message) {
        if (!compose) return;
        clearError();
        errorEl = document.createElement('p');
        errorEl.className = 'inbox-compose-error';
        errorEl.setAttribute('role', 'alert');
        errorEl.textContent = message;
        compose.appendChild(errorEl);
    }

    function resizeInput() {
        if (!input) return;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 136) + 'px';
    }

    function formatTime(date) {
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatMessageBody(text) {
        if (window.LeadBirdChatFormat && typeof window.LeadBirdChatFormat.toHtml === 'function') {
            return window.LeadBirdChatFormat.toHtml(text);
        }

        return escapeHtml(text).replace(/\n/g, '<br>');
    }

    function appendOwnerMessage(text, meta) {
        var emptyState = rail.querySelector('.inbox-thread-empty');
        if (emptyState) {
            emptyState.remove();
        }

        meta = meta || {};
        var createdAtRaw = String(meta.created_at || '');
        var messageId = String(meta.id || '');
        var createdAt = createdAtRaw ? new Date(createdAtRaw.replace(' ', 'T') + 'Z') : new Date();
        if (Number.isNaN(createdAt.getTime())) {
            createdAt = new Date(createdAtRaw || Date.now());
        }

        var row = document.createElement('article');
        row.className = 'inbox-message-row is-outbound is-owner';
        if (messageId) {
            row.setAttribute('data-message-id', messageId);
        }
        if (createdAtRaw) {
            row.setAttribute('data-created-at', createdAtRaw);
        }
        row.innerHTML =
            '<div class="inbox-message-bubble-wrap">' +
                '<div class="inbox-message-meta">' +
                    '<strong>' + escapeHtml(ownerName) + '</strong>' +
                    '<span>' + formatTime(createdAt) + '</span>' +
                '</div>' +
                '<div class="inbox-message-bubble inbox-message-bubble--owner">' +
                    '<p>' + formatMessageBody(text) + '</p>' +
                '</div>' +
            '</div>' +
            '<span class="inbox-message-avatar inbox-message-avatar--owner" aria-hidden="true">' +
                escapeHtml(ownerInitials) +
            '</span>';

        rail.appendChild(row);
        scrollRailToBottom();
        if (createdAtRaw) {
            bumpSyncCursor(createdAtRaw);
            updateThreadPreview(conversationId, 'owner', text, createdAtRaw);
        }
    }

    function customerDisplayName() {
        var title = document.querySelector('.inbox-chat-title');
        return title ? (title.textContent || 'Customer').trim() : 'Customer';
    }

    function appendInboundOrAiMessage(message) {
        if (!rail || !message) {
            return;
        }

        var messageId = String(message.id || '');
        if (messageId && rail.querySelector('[data-message-id="' + messageId.replace(/"/g, '') + '"]')) {
            return;
        }

        var emptyState = rail.querySelector('.inbox-thread-empty');
        if (emptyState) {
            emptyState.remove();
        }

        var senderType = String(message.sender_type || 'customer');
        var isAi = senderType === 'ai';
        var isOwner = senderType === 'owner';
        var isOutbound = isAi || isOwner;
        var text = String(message.message_text || '');
        var createdAtRaw = String(message.created_at || '');
        var createdAt = createdAtRaw ? new Date(createdAtRaw.replace(' ', 'T') + 'Z') : new Date();
        if (Number.isNaN(createdAt.getTime())) {
            createdAt = new Date(createdAtRaw);
        }
        if (Number.isNaN(createdAt.getTime())) {
            createdAt = new Date();
        }

        var label = isAi ? 'Lead Bird AI' : (isOwner ? ownerName : customerDisplayName());
        var row = document.createElement('article');
        row.className = 'inbox-message-row'
            + (isOutbound ? ' is-outbound' : '')
            + (isAi ? ' is-ai' : '')
            + (isOwner ? ' is-owner' : '');
        if (messageId) {
            row.setAttribute('data-message-id', messageId);
        }
        if (createdAtRaw) {
            row.setAttribute('data-created-at', createdAtRaw);
        }

        var avatarHtml = '';
        if (isAi) {
            avatarHtml = '<span class="inbox-message-avatar inbox-message-avatar--ai" aria-hidden="true"></span>';
        } else if (isOwner) {
            avatarHtml = '<span class="inbox-message-avatar inbox-message-avatar--owner" aria-hidden="true">'
                + escapeHtml(ownerInitials) + '</span>';
        } else {
            avatarHtml = '<span class="inbox-message-avatar" aria-hidden="true"><span class="inbox-message-avatar-initials">'
                + escapeHtml((customerDisplayName().slice(0, 2) || 'CU').toUpperCase())
                + '</span></span>';
        }

        var bubbleClass = 'inbox-message-bubble'
            + (isAi ? ' inbox-message-bubble--ai' : '')
            + (isOwner ? ' inbox-message-bubble--owner' : '');

        row.innerHTML =
            (isOutbound ? '' : avatarHtml) +
            '<div class="inbox-message-bubble-wrap">' +
                '<div class="inbox-message-meta">' +
                    '<strong>' + escapeHtml(label) + '</strong>' +
                    '<span>' + formatTime(createdAt) + '</span>' +
                '</div>' +
                '<div class="' + bubbleClass + '">' +
                    '<p>' + (text ? formatMessageBody(text) : 'Message unavailable.') + '</p>' +
                '</div>' +
            '</div>' +
            (isOutbound ? avatarHtml : '');

        rail.appendChild(row);
        scrollRailToBottom();
        bumpSyncCursor(createdAtRaw);
    }

    function bumpSyncCursor(createdAt) {
        if (!createdAt) {
            return;
        }
        if (!window.__inboxSyncAfter || createdAt > window.__inboxSyncAfter) {
            window.__inboxSyncAfter = createdAt;
        }
    }

    function relativeLabel(iso) {
        if (!iso) return '';
        var date = new Date(String(iso).replace(' ', 'T') + 'Z');
        if (Number.isNaN(date.getTime())) {
            date = new Date(iso);
        }
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        var diffMs = Date.now() - date.getTime();
        var mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'Just now';
        if (mins < 60) return mins + 'm ago';
        var hours = Math.floor(mins / 60);
        if (hours < 24) return hours + 'h ago';
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    function updateThreadPreview(conversationIdValue, senderType, text, createdAt) {
        if (!threadList || !conversationIdValue) {
            return;
        }
        var thread = threadList.querySelector('[data-conversation-id="' + conversationIdValue + '"]');
        if (!thread) {
            return;
        }
        if (createdAt) {
            thread.setAttribute('data-timestamp', createdAt);
        }
        var timeEl = thread.querySelector('.inbox-thread-row-time');
        if (timeEl && createdAt) {
            timeEl.textContent = relativeLabel(createdAt) || timeEl.textContent;
        }
        var previewEl = thread.querySelector('.inbox-thread-row-preview');
        if (previewEl) {
            var tag = '';
            if (senderType === 'ai') tag = '<span class="inbox-thread-row-tag">AI · </span>';
            else if (senderType === 'owner') tag = '<span class="inbox-thread-row-tag">You · </span>';
            var preview = String(text || '').replace(/\s+/g, ' ').trim();
            if (preview.length > 56) {
                preview = preview.slice(0, 53) + '...';
            }
            previewEl.innerHTML = tag + escapeHtml(preview || 'No message content yet');
        }
        if (threadList.firstChild !== thread) {
            threadList.insertBefore(thread, threadList.firstChild);
        }
        applyThreadFilters();
    }

    if (input) {
        input.addEventListener('input', resizeInput);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                if (form) {
                    form.requestSubmit();
                }
            }
        });
        resizeInput();

        var canAutoFocus = window.matchMedia('(min-width: 920px) and (hover: hover)').matches;
        if (canAutoFocus) {
            input.focus({ preventScroll: true });
        }
    }

    if (aiToggle) {
        aiToggle.addEventListener('click', function () {
            var nextEnabled = aiToggle.getAttribute('aria-pressed') !== 'true';
            aiToggle.disabled = true;

            fetch(appUrl + '/inbox/toggle-ai', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    conversation_id: conversationId,
                    ai_auto_reply: nextEnabled,
                }),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data.ok) {
                        throw new Error(result.data.message || 'Could not update AI setting.');
                    }
                    setAiAutoReply(nextEnabled);
                    clearError();
                })
                .catch(function (error) {
                    showError(error.message || 'Could not update AI setting.');
                })
                .finally(function () {
                    aiToggle.disabled = false;
                });
        });
    }

    if (form && input && sendBtn) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            clearError();

            var text = input.value.trim();
            if (text === '' || conversationId === '') {
                showError('Write a message before sending.');
                return;
            }

            sendBtn.disabled = true;
            sendBtn.classList.add('is-sending');

            fetch(appUrl + '/inbox/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    conversation_id: conversationId,
                    message_text: text,
                    pause_ai: true,
                }),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data.ok) {
                        throw new Error(result.data.message || 'Could not send your message.');
                    }

                    appendOwnerMessage(text, (result.data.data && result.data.data.message) || {});
                    input.value = '';
                    resizeInput();
                    setAiAutoReply(false);
                })
                .catch(function (error) {
                    if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                        window.LeadBirdModal.alert({
                            title: 'Something went wrong',
                            message: error.message || 'Could not send your message.',
                            tone: 'error',
                        });
                    } else {
                        showError(error.message || 'Could not send your message.');
                    }
                })
                .finally(function () {
                    sendBtn.disabled = false;
                    sendBtn.classList.remove('is-sending');
                    input.focus();
                });
        });
    }

    // Live inbox sync — pull new customer/AI messages without full page refresh.
    (function startInboxSync() {
        var rows = rail ? rail.querySelectorAll('[data-created-at]') : [];
        var latest = '';
        rows.forEach(function (row) {
            var stamp = row.getAttribute('data-created-at') || '';
            if (stamp > latest) {
                latest = stamp;
            }
        });
        if (!latest) {
            // Fallback: slightly in the past so brand-new messages still appear.
            latest = new Date(Date.now() - 15000).toISOString().slice(0, 19).replace('T', ' ');
        }
        window.__inboxSyncAfter = latest;

        var syncing = false;
        function pollInbox() {
            if (syncing || document.hidden) {
                return;
            }
            syncing = true;
            var after = window.__inboxSyncAfter || latest;
            var url = appUrl + '/inbox/sync?after=' + encodeURIComponent(after);
            if (conversationId) {
                url += '&conversation_id=' + encodeURIComponent(conversationId);
            }

            fetch(url, { credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data || !data.ok) {
                        return;
                    }

                    if (conversationId && Array.isArray(data.messages)) {
                        data.messages.forEach(function (message) {
                            if (String(message.conversation_id || conversationId) !== conversationId) {
                                return;
                            }
                            if (String(message.sender_type || '') === 'owner') {
                                // Owner messages are appended immediately on send.
                                bumpSyncCursor(message.created_at);
                                return;
                            }
                            appendInboundOrAiMessage(message);
                            updateThreadPreview(
                                conversationId,
                                message.sender_type,
                                message.message_text,
                                message.created_at
                            );
                        });
                    }

                    if (Array.isArray(data.threads)) {
                        data.threads.forEach(function (thread) {
                            bumpSyncCursor(thread.created_at);
                            if (conversationId && thread.conversation_id === conversationId) {
                                return;
                            }
                            updateThreadPreview(
                                thread.conversation_id,
                                thread.sender_type,
                                thread.preview,
                                thread.created_at
                            );
                        });
                    }

                    if (data.conversation_status && aiToggle) {
                        setAiAutoReply(data.conversation_status !== 'human');
                    }
                })
                .catch(function () {})
                .finally(function () {
                    syncing = false;
                });
        }

        window.setInterval(pollInbox, 3500);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                pollInbox();
            }
        });
        pollInbox();
    })();
})();
