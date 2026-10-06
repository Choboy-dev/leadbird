(function () {
    'use strict';

    function initKnowledgeQa() {
        var root = document.querySelector('[data-kb-training]');
        var config = window.LeadBirdKnowledgeTraining || {};
        if (!root || !config.saveFaqUrl) {
            return;
        }

        var qaForm = root.querySelector('[data-kb-form="qa"]');
        var qaEntryIdInput = root.querySelector('[data-kb-qa-entry-id]');
        var qaPayloadInput = root.querySelector('[data-kb-qa-payload]');
        var qaSubmit = root.querySelector('[data-kb-faq-submit]');
        var qaQuestionInput = root.querySelector('#kb-faq-question');
        var qaAnswerType = root.querySelector('[data-kb-qa-answer-type]');
        var qaItems = root.querySelector('[data-kb-qa-items]');
        var qaList = root.querySelector('[data-kb-qa-list]');
        var qaEmpty = root.querySelector('[data-kb-qa-empty]');
        var qaHubCount = root.querySelector('[data-kb-hub-count="qa"]');
        var textEditor = root.querySelector('[data-kb-qa-text-editor]');
        var listItemsWrap = root.querySelector('[data-kb-qa-list-items]');
        var checklistItemsWrap = root.querySelector('[data-kb-qa-checklist-items]');
        var imageList = root.querySelector('[data-kb-qa-image-list]');
        var imageInput = root.querySelector('[data-kb-qa-image-input]');
        var pdfInput = root.querySelector('[data-kb-qa-pdf-input]');
        var pdfChip = root.querySelector('[data-kb-qa-pdf-chip]');
        var pdfName = root.querySelector('[data-kb-qa-pdf-name]');
        var videoPlatform = root.querySelector('[data-kb-qa-video-platform]');
        var videoUrl = root.querySelector('[data-kb-qa-video-url]');
        var locationAddress = root.querySelector('[data-kb-qa-location-address]');
        var locationMapType = root.querySelector('[data-kb-qa-location-map-type]');
        var mapFrame = root.querySelector('[data-kb-qa-map-frame]');

        var qaState = {
            images: [],
            pdf: null,
        };
        var savedEditorRange = null;

        function captureEditorSelection() {
            if (!(textEditor instanceof HTMLElement)) {
                savedEditorRange = null;
                return;
            }

            var selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) {
                savedEditorRange = null;
                return;
            }

            var range = selection.getRangeAt(0);
            if (!textEditor.contains(range.commonAncestorContainer)) {
                savedEditorRange = null;
                return;
            }

            savedEditorRange = range.cloneRange();
        }

        function restoreEditorSelection() {
            if (!savedEditorRange) {
                return false;
            }

            var selection = window.getSelection();
            if (!selection) {
                return false;
            }

            selection.removeAllRanges();
            selection.addRange(savedEditorRange);
            return true;
        }

        function normalizeLinkUrl(url) {
            var trimmed = String(url || '').trim();
            if (!trimmed) {
                return '';
            }
            if (/^(https?:\/\/|mailto:|tel:)/i.test(trimmed)) {
                return trimmed;
            }
            return 'https://' + trimmed;
        }

        function decorateEditorLinks(container) {
            if (!(container instanceof HTMLElement)) {
                return;
            }

            container.querySelectorAll('a[href]').forEach(function (anchor) {
                anchor.target = '_blank';
                anchor.rel = 'noopener noreferrer';
            });
        }

        function applyLinkToEditor(url) {
            if (!(textEditor instanceof HTMLElement)) {
                return false;
            }

            var linkUrl = normalizeLinkUrl(url);
            if (!linkUrl || !savedEditorRange || savedEditorRange.collapsed) {
                return false;
            }

            textEditor.focus();
            if (!restoreEditorSelection()) {
                return false;
            }

            var selection = window.getSelection();
            if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                return false;
            }

            var range = selection.getRangeAt(0);
            var anchor = document.createElement('a');
            anchor.href = linkUrl;
            anchor.target = '_blank';
            anchor.rel = 'noopener noreferrer';

            try {
                anchor.appendChild(range.extractContents());
                range.insertNode(anchor);
                range.setStartAfter(anchor);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
                savedEditorRange = range.cloneRange();
                return true;
            } catch (error) {
                return false;
            }
        }

        function setStatus(message, isError) {
            var el = root.querySelector('[data-kb-status="qa"]');
            if (!(el instanceof HTMLElement)) {
                return;
            }
            el.textContent = message || '';
            el.classList.toggle('is-error', Boolean(isError));
            el.classList.toggle('is-success', Boolean(message) && !isError);
        }

        function postForm(url, formData) {
            return fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            }).then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            }).then(function (result) {
                if (!result.data || !result.data.ok) {
                    throw new Error((result.data && result.data.message) || 'Something went wrong.');
                }
                return result.data;
            });
        }

        function formatFaqCountLabel(count) {
            if (count <= 0) {
                return 'Provide a question-and-answer pairing your agent can use in conversations.';
            }
            return count === 1 ? '1 answer' : count + ' answers';
        }

        function updateQaHubCount(count) {
            if (!(qaHubCount instanceof HTMLElement)) {
                return;
            }
            qaHubCount.textContent = formatFaqCountLabel(count);
            qaHubCount.classList.toggle('kb-source-desc--count', count > 0);
        }

        function showAnswerPanel(type) {
            root.querySelectorAll('[data-kb-qa-panel]').forEach(function (panel) {
                if (!(panel instanceof HTMLElement)) {
                    return;
                }
                var active = panel.getAttribute('data-kb-qa-panel') === type;
                panel.hidden = !active;
                panel.classList.toggle('is-active', active);
            });
        }

        function buildQaCard(faq) {
            var li = document.createElement('li');
            li.className = 'kb-qa-card';
            li.setAttribute('data-kb-qa-id', faq.id || '');
            li.setAttribute('data-kb-qa-entry', JSON.stringify(faq));
            li.innerHTML = '<div class="kb-qa-card-head">'
                + '<span class="kb-qa-card-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M9.09 9a3 3 0 0 1 5.82 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg></span>'
                + '<h3 class="kb-qa-card-title font-heading"></h3>'
                + '<span class="kb-qa-card-meta"></span></div>'
                + '<p class="kb-qa-card-preview"></p>'
                + '<div class="kb-qa-card-foot"><span class="kb-qa-card-learned"></span>'
                + '<div class="kb-qa-card-actions">'
                + '<button type="button" class="kb-qa-card-edit" data-kb-edit-qa>Edit</button>'
                + '<button type="button" class="kb-qa-card-remove" data-kb-delete-qa data-entry-id="">Remove</button>'
                + '</div></div>';
            li.querySelector('.kb-qa-card-title').textContent = faq.q || '';
            li.querySelector('.kb-qa-card-meta').textContent = (faq.char_count || 0) + ' characters';
            li.querySelector('.kb-qa-card-preview').textContent = faq.a || '';
            li.querySelector('.kb-qa-card-learned').textContent = 'Agent learned on ' + (faq.learned_at || 'recently');
            li.querySelector('[data-entry-id]').setAttribute('data-entry-id', faq.id || '');
            return li;
        }

        function renderQaEntries(faqs) {
            if (!(qaItems instanceof HTMLElement)) {
                return;
            }
            qaItems.innerHTML = '';
            (faqs || []).forEach(function (faq) {
                qaItems.appendChild(buildQaCard(faq));
            });
            if (qaList instanceof HTMLElement) {
                qaList.classList.toggle('is-empty', (faqs || []).length === 0);
            }
            if (qaEmpty instanceof HTMLElement) {
                qaEmpty.hidden = (faqs || []).length > 0;
            }
        }

        function clearListItems() {
            if (listItemsWrap instanceof HTMLElement) {
                listItemsWrap.innerHTML = '';
            }
        }

        function clearChecklistItems() {
            if (checklistItemsWrap instanceof HTMLElement) {
                checklistItemsWrap.innerHTML = '';
            }
        }

        function addListItem(item) {
            if (!(listItemsWrap instanceof HTMLElement)) {
                return;
            }
            var row = document.createElement('div');
            row.className = 'kb-qa-list-item';
            row.innerHTML = '<input type="text" class="kb-qa-input" placeholder="List item" data-kb-qa-list-title>'
                + '<button type="button" class="kb-qa-item-remove" data-kb-qa-remove-list-item aria-label="Remove item">&times;</button>';
            if (item) {
                var titleInput = row.querySelector('[data-kb-qa-list-title]');
                if (titleInput instanceof HTMLInputElement) {
                    titleInput.value = String(item.title || item.description || '').trim();
                }
            }
            listItemsWrap.appendChild(row);
        }

        function addChecklistItem(label) {
            if (!(checklistItemsWrap instanceof HTMLElement)) {
                return;
            }
            var row = document.createElement('div');
            row.className = 'kb-qa-checklist-item';
            row.innerHTML = '<span class="kb-qa-checklist-box" aria-hidden="true"></span>'
                + '<input type="text" class="kb-qa-input" placeholder="Option" data-kb-qa-checklist-label>'
                + '<button type="button" class="kb-qa-item-remove" data-kb-qa-remove-checklist-item aria-label="Remove option">&times;</button>';
            var input = row.querySelector('[data-kb-qa-checklist-label]');
            if (input instanceof HTMLInputElement && label) {
                input.value = label;
            }
            checklistItemsWrap.appendChild(row);
        }

        function renderImageList() {
            if (!(imageList instanceof HTMLElement)) {
                return;
            }
            imageList.innerHTML = '';
            qaState.images.forEach(function (image, index) {
                var li = document.createElement('li');
                li.className = 'kb-qa-image-chip';
                li.innerHTML = '<img src="" alt=""><span></span><button type="button" aria-label="Remove image">&times;</button>';
                var img = li.querySelector('img');
                var name = li.querySelector('span');
                var removeBtn = li.querySelector('button');
                if (img instanceof HTMLImageElement) {
                    img.src = image.url || '';
                }
                if (name) {
                    name.textContent = image.name || 'Image';
                }
                if (removeBtn instanceof HTMLButtonElement) {
                    removeBtn.addEventListener('click', function () {
                        qaState.images.splice(index, 1);
                        renderImageList();
                    });
                }
                imageList.appendChild(li);
            });
        }

        function updateMapPreview() {
            if (!(mapFrame instanceof HTMLIFrameElement) || !(locationAddress instanceof HTMLInputElement)) {
                return;
            }
            var address = locationAddress.value.trim();
            if (address === '') {
                mapFrame.src = 'about:blank';
                return;
            }
            mapFrame.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(address) + '&output=embed';
        }

        function resetQaForm() {
            if (qaForm instanceof HTMLFormElement) {
                qaForm.reset();
            }
            if (qaEntryIdInput instanceof HTMLInputElement) {
                qaEntryIdInput.value = '';
            }
            if (textEditor instanceof HTMLElement) {
                textEditor.innerHTML = '';
            }
            qaState.images = [];
            qaState.pdf = null;
            renderImageList();
            if (pdfChip instanceof HTMLElement) {
                pdfChip.hidden = true;
            }
            if (pdfName instanceof HTMLElement) {
                pdfName.textContent = '';
            }
            clearListItems();
            addListItem();
            clearChecklistItems();
            addChecklistItem('');
            showAnswerPanel('text');
            if (qaAnswerType instanceof HTMLSelectElement) {
                qaAnswerType.value = 'text';
                if (window.LeadBirdDropdown) {
                    window.LeadBirdDropdown.refresh(qaAnswerType.closest('.lb-dropdown'), qaAnswerType);
                }
            }
            setStatus('', false);
            updateMapPreview();
            updateQaSaveState();
        }

        function collectPayload() {
            var type = qaAnswerType instanceof HTMLSelectElement ? qaAnswerType.value : 'text';
            if (type === 'text' && textEditor instanceof HTMLElement) {
                return { html: textEditor.innerHTML, text: textEditor.textContent || '' };
            }
            if (type === 'list') {
                var items = [];
                if (listItemsWrap instanceof HTMLElement) {
                    listItemsWrap.querySelectorAll('.kb-qa-list-item').forEach(function (row) {
                        var title = row.querySelector('[data-kb-qa-list-title]');
                        items.push({
                            title: title instanceof HTMLInputElement ? title.value.trim() : '',
                        });
                    });
                }
                return { items: items };
            }
            if (type === 'video') {
                return {
                    platform: videoPlatform instanceof HTMLSelectElement ? videoPlatform.value : '',
                    url: videoUrl instanceof HTMLInputElement ? videoUrl.value.trim() : '',
                };
            }
            if (type === 'images') {
                return { images: qaState.images.slice() };
            }
            if (type === 'checklist') {
                var options = [];
                if (checklistItemsWrap instanceof HTMLElement) {
                    checklistItemsWrap.querySelectorAll('[data-kb-qa-checklist-label]').forEach(function (input) {
                        if (input instanceof HTMLInputElement && input.value.trim() !== '') {
                            options.push(input.value.trim());
                        }
                    });
                }
                return { items: options };
            }
            if (type === 'pdf' && qaState.pdf) {
                return qaState.pdf;
            }
            if (type === 'location') {
                return {
                    address: locationAddress instanceof HTMLInputElement ? locationAddress.value.trim() : '',
                    map_type: locationMapType instanceof HTMLSelectElement ? locationMapType.value : '',
                };
            }
            return {};
        }

        function populateForm(entry) {
            resetQaForm();
            if (!entry) {
                return;
            }
            if (qaEntryIdInput instanceof HTMLInputElement) {
                qaEntryIdInput.value = entry.id || '';
            }
            if (qaQuestionInput instanceof HTMLInputElement) {
                qaQuestionInput.value = entry.q || '';
            }
            if (qaAnswerType instanceof HTMLSelectElement) {
                qaAnswerType.value = entry.answer_type || 'text';
                if (window.LeadBirdDropdown) {
                    window.LeadBirdDropdown.refresh(qaAnswerType.closest('.lb-dropdown'), qaAnswerType);
                }
            }
            showAnswerPanel(entry.answer_type || 'text');
            var payload = entry.payload || {};

            if (entry.answer_type === 'text' && textEditor instanceof HTMLElement) {
                textEditor.innerHTML = payload.html || payload.text || entry.a || '';
                decorateEditorLinks(textEditor);
            }
            if (entry.answer_type === 'list') {
                clearListItems();
                (payload.items || [{ title: '' }]).forEach(function (item) {
                    addListItem({
                        title: item.title || item.description || '',
                    });
                });
            }
            if (entry.answer_type === 'video') {
                if (videoPlatform instanceof HTMLSelectElement) {
                    videoPlatform.value = payload.platform || '';
                }
                if (videoUrl instanceof HTMLInputElement) {
                    videoUrl.value = payload.url || '';
                }
            }
            if (entry.answer_type === 'images') {
                qaState.images = Array.isArray(payload.images) ? payload.images.slice() : [];
                renderImageList();
            }
            if (entry.answer_type === 'checklist') {
                clearChecklistItems();
                var items = Array.isArray(payload.items) ? payload.items : [''];
                if (items.length === 0) {
                    items = [''];
                }
                items.forEach(addChecklistItem);
            }
            if (entry.answer_type === 'pdf' && payload.url) {
                qaState.pdf = payload;
                if (pdfChip instanceof HTMLElement) {
                    pdfChip.hidden = false;
                }
                if (pdfName instanceof HTMLElement) {
                    pdfName.textContent = payload.file_name || 'Uploaded PDF';
                }
            }
            if (entry.answer_type === 'location') {
                if (locationAddress instanceof HTMLInputElement) {
                    locationAddress.value = payload.address || '';
                }
                if (locationMapType instanceof HTMLSelectElement) {
                    locationMapType.value = payload.map_type || '';
                }
                updateMapPreview();
            }
            updateQaSaveState();
        }

        function updateQaSaveState() {
            if (!(qaSubmit instanceof HTMLButtonElement)) {
                return;
            }
            var question = qaQuestionInput instanceof HTMLInputElement ? qaQuestionInput.value.trim() : '';
            if (question === '') {
                qaSubmit.disabled = true;
                return;
            }

            var type = qaAnswerType instanceof HTMLSelectElement ? qaAnswerType.value : 'text';
            var ready = false;

            if (type === 'text' && textEditor instanceof HTMLElement) {
                ready = (textEditor.textContent || '').trim() !== '';
            } else if (type === 'list' && listItemsWrap instanceof HTMLElement) {
                listItemsWrap.querySelectorAll('.kb-qa-list-item').forEach(function (row) {
                    var title = row.querySelector('[data-kb-qa-list-title]');
                    if (title instanceof HTMLInputElement && title.value.trim() !== '') {
                        ready = true;
                    }
                });
            } else if (type === 'video') {
                ready = videoUrl instanceof HTMLInputElement && videoUrl.value.trim() !== '';
            } else if (type === 'images') {
                ready = qaState.images.length > 0;
            } else if (type === 'checklist' && checklistItemsWrap instanceof HTMLElement) {
                checklistItemsWrap.querySelectorAll('[data-kb-qa-checklist-label]').forEach(function (input) {
                    if (input instanceof HTMLInputElement && input.value.trim() !== '') {
                        ready = true;
                    }
                });
            } else if (type === 'pdf') {
                ready = Boolean(qaState.pdf && qaState.pdf.url);
            } else if (type === 'location') {
                ready = locationAddress instanceof HTMLInputElement && locationAddress.value.trim() !== '';
            }

            qaSubmit.disabled = !ready;
        }

        function uploadAsset(file, assetType) {
            if (!config.uploadFaqAssetUrl) {
                return Promise.reject(new Error('Upload is not available.'));
            }
            var body = new FormData();
            body.append('asset_type', assetType);
            body.append('file', file);
            return postForm(config.uploadFaqAssetUrl, body).then(function (data) {
                return data.asset || {};
            });
        }

        window.LeadBirdKnowledgeQa = {
            resetQaForm: resetQaForm,
            renderQaEntries: renderQaEntries,
            updateQaHubCount: updateQaHubCount,
            populateForm: populateForm,
        };

        if (qaAnswerType instanceof HTMLSelectElement) {
            qaAnswerType.addEventListener('change', function () {
                showAnswerPanel(qaAnswerType.value);
                updateQaSaveState();
            });
        }

        root.addEventListener('input', function (event) {
            if (event.target instanceof HTMLElement && event.target.closest('[data-kb-form="qa"]')) {
                updateQaSaveState();
            }
        });

        root.addEventListener('change', function (event) {
            if (event.target instanceof HTMLElement && event.target.closest('[data-kb-form="qa"]')) {
                updateQaSaveState();
            }
        });

        if (textEditor instanceof HTMLElement) {
            ['mouseup', 'keyup'].forEach(function (eventName) {
                textEditor.addEventListener(eventName, captureEditorSelection);
            });
        }

        root.querySelectorAll('[data-kb-qa-cmd]').forEach(function (button) {
            button.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            button.addEventListener('click', function () {
                var cmd = button.getAttribute('data-kb-qa-cmd') || '';
                if (!(textEditor instanceof HTMLElement)) {
                    return;
                }
                if (cmd === 'createLink') {
                    captureEditorSelection();
                    if (!savedEditorRange || savedEditorRange.collapsed) {
                        if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                            window.LeadBirdModal.alert({
                                title: 'Select text first',
                                message: 'Highlight the words you want to turn into a link, then click Link again.',
                            });
                        }
                        return;
                    }
                    if (!window.LeadBirdModal || typeof window.LeadBirdModal.prompt !== 'function') {
                        return;
                    }
                    window.LeadBirdModal.prompt({
                        title: 'Insert link',
                        message: 'Paste the full URL you want to link to.',
                        label: 'Link URL',
                        placeholder: 'https://example.com/page',
                        confirmLabel: 'Insert link',
                        cancelLabel: 'Cancel',
                    }).then(function (url) {
                        if (!url) {
                            return;
                        }
                        if (!applyLinkToEditor(url)) {
                            if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                                window.LeadBirdModal.alert({
                                    title: 'Could not add link',
                                    message: 'Select the text you want to link and try again.',
                                    tone: 'error',
                                });
                            }
                            return;
                        }
                        decorateEditorLinks(textEditor);
                        updateQaSaveState();
                    });
                    return;
                }
                textEditor.focus();
                restoreEditorSelection();
                document.execCommand(cmd, false, null);
                updateQaSaveState();
            });
        });

        root.querySelectorAll('[data-kb-qa-add-list-item]').forEach(function (button) {
            button.addEventListener('click', function () {
                addListItem();
            });
        });

        root.querySelectorAll('[data-kb-qa-add-checklist-item]').forEach(function (button) {
            button.addEventListener('click', function () {
                addChecklistItem('');
            });
        });

        root.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            if (target.closest('[data-kb-qa-remove-list-item]')) {
                var listRow = target.closest('.kb-qa-list-item');
                if (listRow instanceof HTMLElement) {
                    listRow.remove();
                }
                if (listItemsWrap instanceof HTMLElement && listItemsWrap.children.length === 0) {
                    addListItem();
                }
            }
            if (target.closest('[data-kb-qa-remove-checklist-item]')) {
                var checklistRow = target.closest('.kb-qa-checklist-item');
                if (checklistRow instanceof HTMLElement) {
                    checklistRow.remove();
                }
                if (checklistItemsWrap instanceof HTMLElement && checklistItemsWrap.children.length === 0) {
                    addChecklistItem('');
                }
            }
            if (target.closest('[data-kb-qa-image-trigger]') && imageInput instanceof HTMLInputElement) {
                imageInput.click();
            }
            if (target.closest('[data-kb-qa-pdf-trigger]') && pdfInput instanceof HTMLInputElement) {
                pdfInput.click();
            }
            if (target.closest('[data-kb-qa-pdf-clear]')) {
                qaState.pdf = null;
                if (pdfChip instanceof HTMLElement) {
                    pdfChip.hidden = true;
                }
            }
            if (target.closest('[data-kb-edit-qa]')) {
                var card = target.closest('.kb-qa-card');
                if (card instanceof HTMLElement) {
                    var raw = card.getAttribute('data-kb-qa-entry');
                    if (raw) {
                        populateForm(JSON.parse(raw));
                        if (typeof window.LeadBirdKnowledgeTrainingShowScreen === 'function') {
                            window.LeadBirdKnowledgeTrainingShowScreen('qa');
                        }
                    }
                }
            }
            if (target.closest('[data-kb-delete-qa]') && window.LeadBirdModal) {
                var deleteBtn = target.closest('[data-kb-delete-qa]');
                var entryId = deleteBtn instanceof HTMLElement ? deleteBtn.getAttribute('data-entry-id') || '' : '';
                if (!entryId) {
                    return;
                }
                window.LeadBirdModal.confirm({
                    title: 'Remove this question?',
                    message: 'Your AI will stop using this answer.',
                    confirmLabel: 'Remove',
                    tone: 'danger',
                }).then(function (ok) {
                    if (!ok) {
                        return;
                    }
                    var body = new FormData();
                    body.append('action', 'delete');
                    body.append('entry_id', entryId);
                    postForm(config.saveFaqUrl, body).then(function (data) {
                        renderQaEntries(data.faqs || []);
                        updateQaHubCount(data.count || 0);
                    }).catch(function (error) {
                        window.LeadBirdModal.alert({ title: 'Could not remove', message: error.message, tone: 'error' });
                    });
                });
            }
        });

        if (imageInput instanceof HTMLInputElement) {
            imageInput.addEventListener('change', function () {
                var files = imageInput.files ? Array.from(imageInput.files) : [];
                files.forEach(function (file) {
                    uploadAsset(file, 'image').then(function (asset) {
                        qaState.images.push({
                            url: asset.url || '',
                            name: asset.file_name || file.name,
                            stored_name: asset.stored_name || '',
                        });
                        renderImageList();
                        updateQaSaveState();
                    }).catch(function (error) {
                        setStatus(error.message, true);
                    });
                });
                imageInput.value = '';
            });
        }

        if (pdfInput instanceof HTMLInputElement) {
            pdfInput.addEventListener('change', function () {
                var file = pdfInput.files && pdfInput.files[0];
                if (!file) {
                    return;
                }
                uploadAsset(file, 'pdf').then(function (asset) {
                    qaState.pdf = {
                        url: asset.url || '',
                        file_name: asset.file_name || file.name,
                        stored_name: asset.stored_name || '',
                    };
                    if (pdfChip instanceof HTMLElement) {
                        pdfChip.hidden = false;
                    }
                    if (pdfName instanceof HTMLElement) {
                        pdfName.textContent = qaState.pdf.file_name;
                    }
                    updateQaSaveState();
                }).catch(function (error) {
                    setStatus(error.message, true);
                }).finally(function () {
                    pdfInput.value = '';
                });
            });
        }

        if (locationAddress instanceof HTMLInputElement) {
            locationAddress.addEventListener('input', updateMapPreview);
        }

        if (qaForm instanceof HTMLFormElement) {
            qaForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (qaSubmit instanceof HTMLButtonElement) {
                    qaSubmit.disabled = true;
                }
                setStatus('Saving…', false);

                var payload = collectPayload();
                if (qaPayloadInput instanceof HTMLInputElement) {
                    qaPayloadInput.value = JSON.stringify(payload);
                }

                var body = new FormData(qaForm);
                body.set('payload_json', JSON.stringify(payload));
                body.set('answer_type', qaAnswerType instanceof HTMLSelectElement ? qaAnswerType.value : 'text');

                postForm(config.saveFaqUrl, body).then(function (data) {
                    renderQaEntries(data.faqs || []);
                    updateQaHubCount(data.count || 0);
                    resetQaForm();
                    setStatus('Saved.', false);
                    if (typeof window.LeadBirdKnowledgeTrainingShowScreen === 'function') {
                        window.LeadBirdKnowledgeTrainingShowScreen('qa-list');
                    }
                }).catch(function (error) {
                    setStatus(error.message, true);
                }).finally(function () {
                    if (qaSubmit instanceof HTMLButtonElement) {
                        updateQaSaveState();
                    }
                });
            });
        }

        resetQaForm();
        updateQaSaveState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKnowledgeQa);
    } else {
        initKnowledgeQa();
    }
})();
