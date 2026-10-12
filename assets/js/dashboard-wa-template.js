(function () {
    'use strict';

    var root = document.querySelector('[data-wtb-root]');
    if (!root) {
        return;
    }

    var VAR_RE = /\{\{\s*([A-Za-z0-9_]+)\s*\}\}/g;
    var LIMITS = { quick_reply: 3, call_to_action: 1, card: 3, whatsapp_card: 10 };
    var BUTTON_TYPES = {
        quick_reply: ['QUICK_REPLY'],
        call_to_action: ['URL', 'PHONE_NUMBER'],
        card: ['QUICK_REPLY', 'URL', 'PHONE_NUMBER'],
        whatsapp_card: ['QUICK_REPLY', 'URL', 'PHONE_NUMBER', 'COPY_CODE'],
    };
    var COPY_CODE_TEXT = 'Copy offer code';
    var locked = root.getAttribute('data-locked') === '1';
    var isPreview = root.getAttribute('data-preview') === '1';
    var endpoint = root.getAttribute('data-endpoint');
    var templateId = root.getAttribute('data-id');
    var spec = JSON.parse(root.querySelector('[data-wtb-spec]').textContent || '{}');
    var samples = spec.samples || {};

    var nameInput = root.querySelector('[data-wtb-name]');
    var heading = root.querySelector('[data-wtb-heading]');
    var language = root.querySelector('[data-wtb-language]');
    var typeSelect = root.querySelector('[data-wtb-type]');
    var header = root.querySelector('[data-wtb-header]');
    var body = root.querySelector('[data-wtb-body]');
    var footer = root.querySelector('[data-wtb-footer]');
    var samplesBox = root.querySelector('[data-wtb-samples]');
    var buttonsWrap = root.querySelector('[data-wtb-buttons-wrap]');
    var buttonList = root.querySelector('[data-wtb-buttons]');
    var addButton = root.querySelector('[data-wtb-add]');
    var buttonTpl = root.querySelector('[data-wtb-button-tpl]');
    var submitButton = root.querySelector('[data-wtb-submit]');
    var mediaField = root.querySelector('[data-wtb-media-field]');
    var mediaUrl = root.querySelector('[data-wtb-media-url]');
    var mediaSampleField = root.querySelector('[data-wtb-media-sample-field]');
    var mediaSample = root.querySelector('[data-wtb-media-sample]');
    var MEDIA_VAR_RE = /^\{\{\s*([A-Za-z0-9_]+)\s*\}\}$/;
    var listWrap = root.querySelector('[data-wtb-list-wrap]');
    var listButton = root.querySelector('[data-wtb-list-button]');
    var listItems = root.querySelector('[data-wtb-list-items]');
    var listAdd = root.querySelector('[data-wtb-list-add]');
    var listTpl = root.querySelector('[data-wtb-list-item-tpl]');
    var LIST_MAX = 10;
    var rowCount = 0;
    var contentType = typeSelect.value;
    var headerTypeSelect = root.querySelector('[data-wtb-header-type]');
    var headerField = root.querySelector('[data-wtb-header-field]');
    var hannahLinkTpl = locked ? null : root.querySelector('[data-wtb-hannah-open="body"]');

    var preview = {
        media: root.querySelector('[data-wtb-p-media]'),
        header: root.querySelector('[data-wtb-p-header]'),
        body: root.querySelector('[data-wtb-p-body]'),
        footer: root.querySelector('[data-wtb-p-footer]'),
        buttons: root.querySelector('[data-wtb-p-buttons]'),
    };

    function initDropdowns(scope) {
        if (window.LeadBirdDropdown) {
            window.LeadBirdDropdown.init(scope);
        }
    }

    // —— Character counters ——

    function updateCounter(input) {
        var counter = input.parentNode.querySelector('.wtb-counter');
        if (counter) {
            counter.textContent = input.value.length + '/' + input.getAttribute('maxlength');
        }
    }

    root.addEventListener('input', function (event) {
        if (event.target.hasAttribute('data-wtb-count')) {
            updateCounter(event.target);
        }
    });

    // —— Variables and samples ——

    function catalogFormat() {
        var checked = root.querySelector('[data-wtb-catalog-format]:checked');
        return checked ? checked.value : 'catalog';
    }

    function headerType() {
        if (contentType === 'whatsapp_card') {
            return headerTypeSelect.value;
        }
        if (contentType === 'catalog') {
            return catalogFormat() === 'mpm' ? 'text' : 'none';
        }
        if (contentType === 'media' || contentType === 'card') {
            return 'media';
        }
        if (contentType === 'carousel' || contentType === 'flow' || contentType === 'authentication') {
            return 'none';
        }
        return header.value.trim() !== '' ? 'text' : 'none';
    }

    function variables() {
        var found = [];
        [headerType() === 'text' ? header.value : '', contentType === 'authentication' ? '' : body.value].forEach(function (text) {
            text.replace(VAR_RE, function (_, name) {
                if (found.indexOf(name) === -1) {
                    found.push(name);
                }
                return _;
            });
        });
        return found;
    }

    function renderSamples() {
        samplesBox.querySelectorAll('[data-var]').forEach(function (input) {
            samples[input.getAttribute('data-var')] = input.value;
        });
        var vars = isList() ? [] : variables();
        samplesBox.innerHTML = '';
        samplesBox.hidden = vars.length === 0;
        if (!vars.length) {
            return;
        }
        if (!locked) {
            var note = document.createElement('div');
            note.className = 'wtb-samples-note';
            note.innerHTML = '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><circle cx="8" cy="8" r="6.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 7.25v3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="8" cy="5.1" r="0.9" fill="currentColor"/></svg>';
            var noteText = document.createElement('p');
            noteText.textContent = 'Please note that WhatsApp has a requirement that you submit a sample of the content that you plan to send for each variable in order to obtain approval. If you do not provide these samples at this stage, you will be unable to submit your template for WhatsApp approval at a later time.';
            note.appendChild(noteText);
            samplesBox.appendChild(note);
        }
        vars.forEach(function (name, index) {
            var field = document.createElement('div');
            field.className = 'ws-field';
            var id = 'wtb-sample-' + index;
            var label = document.createElement('label');
            label.setAttribute('for', id);
            label.textContent = 'Sample content for variable {{' + name + '}}';
            var input = document.createElement('input');
            input.type = 'text';
            input.id = id;
            input.maxLength = 200;
            input.value = samples[name] || '';
            input.setAttribute('data-var', name);
            input.disabled = locked;
            if (hannahLinkTpl) {
                var labelRow = document.createElement('div');
                labelRow.className = 'wtb-label-row';
                var link = hannahLinkTpl.cloneNode(true);
                link.setAttribute('data-wtb-hannah-open', 'sample');
                link.setAttribute('data-wtb-hannah-var', name);
                labelRow.appendChild(label);
                labelRow.appendChild(link);
                field.appendChild(labelRow);
            } else {
                field.appendChild(label);
            }
            field.appendChild(input);
            samplesBox.appendChild(field);
        });
    }

    // —— Buttons ——

    function allowedButtons() {
        return BUTTON_TYPES[contentType] || [];
    }

    function setRowType(row) {
        var choose = allowedButtons().length > 1;
        var type = choose ? row.querySelector('[data-wtb-btype]').value : 'QUICK_REPLY';
        row.setAttribute('data-type', type);
        row.querySelectorAll('[data-wtb-cta-only]').forEach(function (node) {
            node.hidden = !choose;
        });
        row.querySelector('[data-wtb-text-field]').hidden = type === 'COPY_CODE';
        row.querySelector('[data-wtb-url-field]').hidden = type !== 'URL';
        row.querySelector('[data-wtb-phone-field]').hidden = type !== 'PHONE_NUMBER';
        row.querySelector('[data-wtb-code-field]').hidden = type !== 'COPY_CODE';
        syncUrlSample(row);
    }

    var URL_VAR_RE = /\{\{\s*([A-Za-z0-9_]+)\s*\}\}$/;

    function syncUrlSample(row) {
        var field = row.querySelector('[data-wtb-url-sample-field]');
        var input = row.querySelector('[data-wtb-url-sample]');
        var match = row.getAttribute('data-type') === 'URL' ? URL_VAR_RE.exec(row.querySelector('[data-wtb-burl]').value.trim()) : null;
        if (input.getAttribute('data-var')) {
            samples[input.getAttribute('data-var')] = input.value;
        }
        field.hidden = !match;
        if (match) {
            row.querySelector('[data-wtb-url-var]').textContent = '{{' + match[1] + '}}';
            if (input.getAttribute('data-var') !== match[1]) {
                input.setAttribute('data-var', match[1]);
                input.value = samples[match[1]] || '';
            }
        }
    }

    function addRow(data) {
        var fragment = buttonTpl.content.cloneNode(true);
        var row = fragment.querySelector('[data-wtb-button]');
        rowCount += 1;
        row.querySelectorAll('.ws-field').forEach(function (field, index) {
            var control = field.querySelector('input, select');
            var label = field.querySelector('label');
            if (control && label) {
                control.id = 'wtb-btn-' + rowCount + '-' + index;
                label.setAttribute('for', control.id);
            }
        });
        var select = row.querySelector('[data-wtb-btype]');
        var allowed = allowedButtons();
        Array.prototype.slice.call(select.options).forEach(function (option) {
            if (allowed.indexOf(option.value) === -1) {
                option.remove();
            }
        });
        if (allowed.indexOf(data.type) !== -1) {
            select.value = data.type;
        }
        row.querySelector('[data-wtb-btext]').value = data.text || '';
        row.querySelector('[data-wtb-burl]').value = data.url || '';
        row.querySelector('[data-wtb-bphone]').value = data.phone || '';
        row.querySelector('[data-wtb-bcode]').value = data.code || '';
        row.querySelectorAll('input, select').forEach(function (control) {
            control.disabled = locked;
        });
        if (locked) {
            row.querySelector('[data-wtb-remove]').hidden = true;
        }
        buttonList.appendChild(row);
        initDropdowns(row);
        setRowType(row);
        updateCounter(row.querySelector('[data-wtb-btext]'));
        updateCounter(row.querySelector('[data-wtb-burl]'));
        updateCounter(row.querySelector('[data-wtb-bcode]'));
        select.addEventListener('change', function () {
            setRowType(row);
            renderPreview();
        });
        row.querySelector('[data-wtb-burl]').addEventListener('input', function () {
            syncUrlSample(row);
        });
        return row;
    }

    function rows() {
        return Array.prototype.slice.call(buttonList.querySelectorAll('[data-wtb-button]'));
    }

    // —— List picker ——

    function isList() {
        return typeSelect.value === 'list_picker';
    }

    function listRows() {
        return Array.prototype.slice.call(listItems.querySelectorAll('[data-wtb-list-item]'));
    }

    function addListItem(data) {
        var fragment = listTpl.content.cloneNode(true);
        var item = fragment.querySelector('[data-wtb-list-item]');
        rowCount += 1;
        item.querySelectorAll('.ws-field').forEach(function (field, index) {
            var input = field.querySelector('input');
            input.id = 'wtb-item-' + rowCount + '-' + index;
            field.querySelector('label').setAttribute('for', input.id);
        });
        item.querySelector('[data-wtb-item-name]').value = data.name || '';
        item.querySelector('[data-wtb-item-id]').value = data.id || '';
        item.querySelector('[data-wtb-item-desc]').value = data.description || '';
        item.querySelectorAll('input').forEach(function (input) {
            input.disabled = locked;
            updateCounter(input);
        });
        listItems.appendChild(item);
        return item;
    }

    function syncList() {
        listWrap.hidden = !isList();
        if (isList() && !listRows().length) {
            addListItem({});
        }
        var count = listRows().length;
        listRows().forEach(function (item) {
            item.querySelector('[data-wtb-list-remove]').hidden = locked || count < 2;
        });
        if (listAdd) {
            listAdd.hidden = count >= LIST_MAX;
        }
    }

    function listData() {
        if (!isList()) {
            return [];
        }
        return listRows().map(function (item) {
            return {
                name: item.querySelector('[data-wtb-item-name]').value.trim(),
                id: item.querySelector('[data-wtb-item-id]').value.trim(),
                description: item.querySelector('[data-wtb-item-desc]').value.trim(),
            };
        });
    }

    if (listAdd) {
        listAdd.addEventListener('click', function () {
            var item = addListItem({});
            syncList();
            item.querySelector('[data-wtb-item-name]').focus();
        });
    }

    listItems.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-wtb-list-remove]');
        if (remove) {
            remove.closest('[data-wtb-list-item]').remove();
            syncList();
            renderPreview();
        }
    });
    listWrap.addEventListener('input', renderPreview);

    // —— Media ——

    var DOC_ICON = '<svg viewBox="0 0 16 16" width="22" height="22" aria-hidden="true"><path d="M4 1.75h5.5L13 5.25v9H4z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M9.5 1.75v3.5H13" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>';

    var PLAY_ICON = '<svg class="wtb-play" viewBox="0 0 48 48" width="44" height="44" aria-hidden="true"><circle cx="24" cy="24" r="22" style="fill: rgb(0 0 0 / 0.55)"/><path d="M19 15.5v17l14-8.5z" style="fill: var(--brand-white)"/></svg>';
    var mediaShown = null;
    var mediaTimer = null;

    function mediaGuess(url) {
        var path = url.split(/[?#]/)[0];
        if (/\.(mp4|3gp)$/i.test(path)) {
            return 'VIDEO';
        }
        if (/\.pdf$/i.test(path)) {
            return 'DOCUMENT';
        }
        if (/\.(jpe?g|png)$/i.test(path)) {
            return 'IMAGE';
        }
        return spec.header_format && spec.media_url === url ? spec.header_format : '';
    }

    function mediaMessage(text) {
        var note = document.createElement('span');
        note.className = 'wtb-media-empty';
        note.textContent = text;
        return note;
    }

    function docCard(url) {
        var doc = document.createElement('span');
        doc.className = 'wtb-doc';
        doc.innerHTML = DOC_ICON;
        doc.appendChild(document.createTextNode(url.split(/[?#]/)[0].split('/').pop() || 'PDF document'));
        return doc;
    }

    function loadImage(url, done) {
        var img = new Image();
        img.alt = 'Template photo';
        img.onload = function () {
            done(img);
        };
        img.onerror = function () {
            done(null);
        };
        img.src = url;
    }

    function loadVideo(url, done) {
        var video = document.createElement('video');
        video.muted = true;
        video.playsInline = true;
        video.preload = 'metadata';
        video.onloadeddata = function () {
            var frame = document.createElement('span');
            frame.className = 'wtb-video';
            frame.appendChild(video);
            frame.insertAdjacentHTML('beforeend', PLAY_ICON);
            done(frame);
        };
        video.onerror = function () {
            done(null);
        };
        video.src = url + (url.indexOf('#') === -1 ? '#t=0.1' : '');
    }

    function showMedia(url) {
        if (url === mediaShown) {
            return;
        }
        mediaShown = url;
        preview.media.innerHTML = '';
        if (!/^https?:\/\/\S+$/i.test(url)) {
            preview.media.appendChild(mediaMessage('Your media will appear here'));
            return;
        }
        var guess = mediaGuess(url);
        if (guess === 'DOCUMENT') {
            preview.media.appendChild(docCard(url));
            return;
        }
        preview.media.appendChild(mediaMessage('Loading preview…'));
        var place = function (node) {
            if (mediaShown !== url) {
                return;
            }
            preview.media.innerHTML = '';
            preview.media.appendChild(node || mediaMessage('We couldn’t load a preview from this link. Check it opens a JPG, PNG, MP4 or PDF file.'));
        };
        if (guess === 'VIDEO') {
            loadVideo(url, place);
        } else if (guess === 'IMAGE') {
            loadImage(url, place);
        } else {
            loadImage(url, function (img) {
                if (img) {
                    place(img);
                } else {
                    loadVideo(url, place);
                }
            });
        }
    }

    function usesMedia() {
        return headerType() === 'media';
    }

    function mediaVariable() {
        var match = usesMedia() ? MEDIA_VAR_RE.exec(mediaUrl.value.trim()) : null;
        return match ? match[1] : '';
    }

    function syncMediaSample() {
        var name = mediaVariable();
        var current = mediaSample.getAttribute('data-var');
        if (current) {
            samples[current] = mediaSample.value;
        }
        mediaSampleField.hidden = name === '';
        if (name !== '' && current !== name) {
            mediaSample.setAttribute('data-var', name);
            mediaSample.value = samples[name] || '';
            updateCounter(mediaSample);
        }
        root.querySelector('[data-wtb-media-var]').textContent = name !== '' ? '{{' + name + '}}' : '';
    }

    function renderMedia() {
        mediaField.hidden = !usesMedia();
        if (headerField) {
            headerField.hidden = headerType() !== 'text';
        }
    }

    function onHeaderTypeChange() {
        renderMedia();
        syncMediaSample();
        onContentChange();
    }

    if (headerTypeSelect) {
        headerTypeSelect.addEventListener('change', onHeaderTypeChange);
    }
    root.querySelectorAll('[data-wtb-catalog-format]').forEach(function (radio) {
        radio.addEventListener('change', onHeaderTypeChange);
    });

    mediaSample.addEventListener('input', function () {
        clearTimeout(mediaTimer);
        mediaTimer = setTimeout(renderPreview, 400);
    });

    mediaUrl.addEventListener('input', function () {
        syncMediaSample();
        clearTimeout(mediaTimer);
        mediaTimer = setTimeout(renderPreview, 400);
    });

    function syncButtons() {
        var type = contentType;
        var usesButtons = allowedButtons().length > 0;
        buttonsWrap.hidden = !usesButtons;
        if (usesButtons && !rows().length && !locked) {
            addRow({ type: allowedButtons()[0] });
        }
        rows().forEach(setRowType);
        if (addButton) {
            addButton.hidden = !usesButtons || rows().length >= (LIMITS[type] || 0);
        }
        rows().forEach(function (row) {
            row.querySelector('[data-wtb-remove]').hidden = locked || rows().length < 2;
        });
    }

    function buttonData() {
        if (buttonsWrap.hidden) {
            return [];
        }
        return rows().map(function (row) {
            return {
                type: row.getAttribute('data-type'),
                text: row.querySelector('[data-wtb-btext]').value.trim(),
                url: row.querySelector('[data-wtb-burl]').value.trim(),
                phone: row.querySelector('[data-wtb-bphone]').value.trim(),
                code: row.querySelector('[data-wtb-bcode]').value.trim(),
            };
        });
    }

    function groupQuickReplies(buttons) {
        var quick = buttons.filter(function (button) {
            return button.type === 'QUICK_REPLY';
        });
        var other = buttons.filter(function (button) {
            return button.type !== 'QUICK_REPLY';
        });
        return buttons.length && buttons[0].type === 'QUICK_REPLY' ? quick.concat(other) : other.concat(quick);
    }

    if (addButton) {
        addButton.addEventListener('click', function () {
            var row = addRow({ type: contentType === 'call_to_action' ? 'URL' : allowedButtons()[0] });
            syncButtons();
            renderPreview();
            row.querySelector('[data-wtb-btext]').focus();
        });
    }

    buttonList.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-wtb-remove]');
        if (remove) {
            remove.closest('[data-wtb-button]').remove();
            syncButtons();
            renderPreview();
        }
    });

    // —— Preview ——

    function showText(el, text) {
        el.textContent = '';
        var last = 0;
        text.replace(VAR_RE, function (match, name, offset) {
            el.appendChild(document.createTextNode(text.slice(last, offset)));
            var bold = document.createElement('b');
            bold.textContent = match;
            el.appendChild(bold);
            last = offset + match.length;
            return match;
        });
        el.appendChild(document.createTextNode(text.slice(last)));
    }

    var BUTTON_ICONS = {
        QUICK_REPLY: '<path d="M6.5 4 2.5 8l4 4M3 8h6.5a4 4 0 0 1 4 4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        URL: '<path d="M9 2.5h4.5V7M13.5 2.5 7.5 8.5M11.5 9.5v3a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1h3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        LIST: '<path d="M6 4.5h7.5M6 8h7.5M6 11.5h7.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="3" cy="4.5" r="0.9" fill="currentColor"/><circle cx="3" cy="8" r="0.9" fill="currentColor"/><circle cx="3" cy="11.5" r="0.9" fill="currentColor"/>',
        PHONE_NUMBER: '<path d="M5.5 2.5 3.5 3c-.6.2-1 .8-.9 1.4.6 4.4 4.1 7.9 8.5 8.5.6.1 1.2-.3 1.4-.9l.5-2-2.6-1.3-1.3 1.3a7 7 0 0 1-3.3-3.3l1.3-1.3z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>',
        COPY_CODE: '<rect x="5.5" y="5.5" width="8" height="8" rx="1.3" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M10.5 5.5v-2a1 1 0 0 0-1-1h-6a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2" fill="none" stroke="currentColor" stroke-width="1.4"/>',
        CATALOG: '<path d="M2 3h1.8l1.6 7.5h7.2l1.4-5.5H4.6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" stroke-linecap="round"/><circle cx="6.2" cy="13" r="1" fill="currentColor"/><circle cx="11.6" cy="13" r="1" fill="currentColor"/>',
        FLOW: '<path d="M4.5 2h5l3 3v8.5a.5.5 0 0 1-.5.5H4.5a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M6.5 8h4M6.5 10.5h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
    };

    function previewButton(type, text) {
        var item = document.createElement('span');
        item.className = 'wtb-bubble-button';
        item.innerHTML = '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true">' + (BUTTON_ICONS[type] || '') + '</svg>';
        item.appendChild(document.createTextNode(text));
        return item;
    }

    var bubble = preview.body.parentNode;
    if (contentType === 'card' || contentType === 'whatsapp_card' || contentType === 'catalog') {
        bubble.insertBefore(preview.media, bubble.firstChild);
        preview.media.classList.add('is-top');
    }

    function showCatalogThumb() {
        if (mediaShown === 'catalog') {
            return;
        }
        mediaShown = 'catalog';
        preview.media.innerHTML = '';
        var thumb = mediaMessage('A product photo from your catalog shows here');
        thumb.insertAdjacentHTML('afterbegin', '<svg viewBox="0 0 16 16" width="28" height="28" aria-hidden="true">' + BUTTON_ICONS.CATALOG + '</svg>');
        preview.media.appendChild(thumb);
    }

    function previewButtons() {
        if (contentType === 'catalog') {
            return [catalogFormat() === 'mpm' ? { type: 'LIST', text: 'View items' } : { type: 'CATALOG', text: 'View catalog' }];
        }
        if (contentType === 'flow') {
            return [{ type: 'FLOW', text: flowButton.value.trim() || 'Open form' }];
        }
        if (contentType === 'authentication') {
            return [{ type: 'COPY_CODE', text: authButton.value.trim() || 'Copy code' }];
        }
        var buttons = buttonData().map(function (button) {
            return { type: button.type, text: button.type === 'COPY_CODE' ? COPY_CODE_TEXT : button.text || 'Button' };
        });
        if (contentType === 'card' || contentType === 'whatsapp_card') {
            buttons = groupQuickReplies(buttons);
        }
        if (buttons.length > 3) {
            buttons = buttons.slice(0, 2).concat([{ type: 'LIST', text: 'See all options' }]);
        }
        return buttons;
    }

    function renderPreview() {
        var media = usesMedia() ? (mediaVariable() !== '' ? mediaSample.value.trim() : mediaUrl.value.trim()) : '';
        var catalogThumb = contentType === 'catalog' && catalogFormat() === 'catalog';
        preview.media.hidden = !(catalogThumb || (usesMedia() && (contentType !== 'card' || media !== '')));
        if (catalogThumb) {
            showCatalogThumb();
        } else if (!preview.media.hidden) {
            showMedia(media);
        }
        var headerText = headerType() === 'text' ? header.value.trim() : '';
        showText(preview.header, headerText);
        preview.header.hidden = headerText === '';

        var bodyText = contentType === 'authentication' ? authText() : body.value.trim();
        showText(preview.body, bodyText || (contentType === 'card' ? 'Your title will show here.' : 'Your message will show here.'));
        preview.body.classList.toggle('is-empty', bodyText === '');
        preview.body.hidden = contentType === 'media' && bodyText === '';
        var footerText = contentType === 'authentication' ? authFooterText() : footer.value.trim();
        preview.footer.textContent = footerText;
        preview.footer.hidden = footerText === '';

        preview.buttons.innerHTML = '';
        if (isList() && listButton.value.trim() !== '') {
            preview.buttons.appendChild(previewButton('LIST', listButton.value.trim()));
        }
        previewButtons().forEach(function (button) {
            preview.buttons.appendChild(previewButton(button.type, button.text));
        });
        if (contentType === 'carousel') {
            renderCardsPreview();
        }
        if (contentType === 'flow') {
            renderFormPreview();
        }
    }

    // —— Authentication ——

    var authWrap = root.querySelector('[data-wtb-auth]');
    var authSecurity = root.querySelector('[data-wtb-auth-security]');
    var authExpires = root.querySelector('[data-wtb-auth-expires]');
    var authExpiry = root.querySelector('[data-wtb-auth-expiry]');
    var authExpiryField = root.querySelector('[data-wtb-auth-expiry-field]');
    var authButton = root.querySelector('[data-wtb-auth-button]');

    function authMinutes() {
        return parseInt(authExpiry.value, 10) || 0;
    }

    function authText() {
        return '123456 is your verification code.' + (authSecurity.checked ? ' For your security, do not share this code.' : '');
    }

    function authFooterText() {
        if (!authExpires.checked) {
            return '';
        }
        var minutes = authMinutes();
        return 'This code expires in ' + minutes + (minutes === 1 ? ' minute.' : ' minutes.');
    }

    function authData() {
        return {
            security: authSecurity.checked,
            expires: authExpires.checked,
            expiry: authMinutes(),
            button: authButton.value.trim(),
        };
    }

    if (authWrap) {
        authWrap.addEventListener('change', function () {
            authExpiryField.hidden = !authExpires.checked;
            renderPreview();
        });
        authWrap.addEventListener('input', renderPreview);
    }

    // —— Carousel ——

    var cardsWrap = root.querySelector('[data-wtb-cards]');
    var cardTpl = root.querySelector('[data-wtb-card-tpl]');
    var cardAdd = root.querySelector('[data-wtb-card-add]');
    var cardTypes = root.querySelectorAll('[data-wtb-card-btype]');
    var previewCards = root.querySelector('[data-wtb-p-cards]');
    var CARDS_MIN = 2;
    var CARDS_MAX = 10;
    var cardsTimer = null;

    function cardRows() {
        return cardsWrap ? Array.prototype.slice.call(cardsWrap.querySelectorAll('[data-wtb-card]')) : [];
    }

    function syncCards() {
        var layout = Array.prototype.map.call(cardTypes, function (select) {
            return select.value;
        });
        var cards = cardRows();
        cards.forEach(function (card, index) {
            card.querySelector('[data-wtb-card-title]').textContent = 'Card ' + (index + 1);
            card.querySelector('[data-wtb-card-remove]').hidden = locked || cards.length <= CARDS_MIN;
            card.querySelectorAll('[data-wtb-slot]').forEach(function (slot) {
                var type = layout[parseInt(slot.getAttribute('data-wtb-slot'), 10)] || '';
                slot.hidden = type === '';
                slot.setAttribute('data-type', type);
                slot.querySelector('[data-wtb-slot-url-field]').hidden = type !== 'URL';
                slot.querySelector('[data-wtb-slot-phone-field]').hidden = type !== 'PHONE_NUMBER';
            });
        });
        if (cardAdd) {
            cardAdd.hidden = cards.length >= CARDS_MAX;
        }
    }

    function addCard(data) {
        var card = cardTpl.content.cloneNode(true).querySelector('[data-wtb-card]');
        rowCount += 1;
        card.querySelectorAll('.ws-field').forEach(function (field, index) {
            var control = field.querySelector('input, textarea');
            var label = field.querySelector('label');
            if (control && label) {
                control.id = 'wtb-card-' + rowCount + '-' + index;
                label.setAttribute('for', control.id);
            }
        });
        card.querySelector('[data-wtb-card-media]').value = data.media_url || '';
        card.querySelector('[data-wtb-card-body]').value = data.body || '';
        (data.buttons || []).forEach(function (button, index) {
            var slot = card.querySelector('[data-wtb-slot="' + index + '"]');
            if (slot) {
                slot.querySelector('[data-wtb-slot-text]').value = button.text || '';
                slot.querySelector('[data-wtb-slot-url]').value = button.url || '';
                slot.querySelector('[data-wtb-slot-phone]').value = button.phone || '';
            }
        });
        card.querySelectorAll('input, textarea').forEach(function (control) {
            control.disabled = locked;
            if (control.hasAttribute('data-wtb-count')) {
                updateCounter(control);
            }
        });
        cardsWrap.appendChild(card);
        return card;
    }

    function cardData() {
        return cardRows().map(function (card) {
            var buttons = [];
            card.querySelectorAll('[data-wtb-slot]').forEach(function (slot) {
                var type = slot.getAttribute('data-type');
                if (type) {
                    buttons.push({
                        type: type,
                        text: slot.querySelector('[data-wtb-slot-text]').value.trim(),
                        url: slot.querySelector('[data-wtb-slot-url]').value.trim(),
                        phone: slot.querySelector('[data-wtb-slot-phone]').value.trim(),
                    });
                }
            });
            return {
                media_url: card.querySelector('[data-wtb-card-media]').value.trim(),
                body: card.querySelector('[data-wtb-card-body]').value.trim(),
                buttons: buttons,
            };
        });
    }

    function cardMedia(box, url) {
        if (!/^https?:\/\/\S+$/i.test(url)) {
            box.appendChild(mediaMessage('Photo or video'));
            return;
        }
        var failed = function () {
            box.innerHTML = '';
            box.appendChild(mediaMessage('We couldn’t load this link'));
        };
        if (mediaGuess(url) === 'VIDEO') {
            var video = document.createElement('video');
            video.muted = true;
            video.playsInline = true;
            video.preload = 'metadata';
            video.onerror = failed;
            video.src = url + (url.indexOf('#') === -1 ? '#t=0.1' : '');
            box.appendChild(video);
            box.insertAdjacentHTML('beforeend', PLAY_ICON);
            return;
        }
        var img = new Image();
        img.alt = '';
        img.onerror = failed;
        img.src = url;
        box.appendChild(img);
    }

    function renderCardsPreview() {
        previewCards.innerHTML = '';
        cardData().forEach(function (card) {
            var node = document.createElement('div');
            node.className = 'wtb-p-card';
            var box = document.createElement('div');
            box.className = 'wtb-p-card-media';
            cardMedia(box, card.media_url);
            node.appendChild(box);
            if (card.body !== '') {
                var text = document.createElement('p');
                text.className = 'wtb-p-card-body';
                text.textContent = card.body;
                node.appendChild(text);
            }
            card.buttons.forEach(function (button) {
                node.appendChild(previewButton(button.type, button.text || 'Button'));
            });
            previewCards.appendChild(node);
        });
    }

    if (cardsWrap) {
        cardTypes.forEach(function (select) {
            select.addEventListener('change', function () {
                syncCards();
                renderPreview();
            });
        });
        cardsWrap.addEventListener('input', function () {
            clearTimeout(cardsTimer);
            cardsTimer = setTimeout(renderPreview, 350);
        });
        cardsWrap.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-wtb-card-remove]');
            if (remove) {
                remove.closest('[data-wtb-card]').remove();
                syncCards();
                renderPreview();
            }
        });
        if (cardAdd) {
            cardAdd.addEventListener('click', function () {
                var card = addCard({});
                syncCards();
                renderPreview();
                card.querySelector('[data-wtb-card-media]').focus();
            });
        }
    }

    // —— Flows ——

    var questionsWrap = root.querySelector('[data-wtb-questions]');
    var questionTpl = root.querySelector('[data-wtb-question-tpl]');
    var questionAdd = root.querySelector('[data-wtb-question-add]');
    var flowWrap = root.querySelector('[data-wtb-flow]');
    var flowButton = root.querySelector('[data-wtb-flow-button]');
    var flowTitle = root.querySelector('[data-wtb-flow-title]');
    var flowSubmit = root.querySelector('[data-wtb-flow-submit]');
    var QUESTIONS_MAX = 10;
    var FORM_ICONS = {
        date: '<rect x="2.5" y="3.5" width="11" height="10" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2.5 6.5h11M5.5 2v3M10.5 2v3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
        dropdown: '<path d="m4 6 4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
    };

    function questionRows() {
        return questionsWrap ? Array.prototype.slice.call(questionsWrap.querySelectorAll('[data-wtb-question]')) : [];
    }

    function syncQuestion(question) {
        var select = question.querySelector('[data-wtb-q-type]');
        var option = select.options[select.selectedIndex];
        var label = question.querySelector('[data-wtb-q-label]');
        label.maxLength = parseInt(option.getAttribute('data-max'), 10);
        if (label.value.length > label.maxLength) {
            label.value = label.value.slice(0, label.maxLength);
        }
        updateCounter(label);
        question.querySelector('[data-wtb-q-options-field]').hidden = option.getAttribute('data-choices') !== '1';
    }

    function syncQuestions() {
        var questions = questionRows();
        questions.forEach(function (question, index) {
            question.querySelector('[data-wtb-question-title]').textContent = 'Question ' + (index + 1);
            question.querySelector('[data-wtb-question-remove]').hidden = locked || questions.length < 2;
        });
        if (questionAdd) {
            questionAdd.hidden = questions.length >= QUESTIONS_MAX;
        }
    }

    function addQuestion(data) {
        var question = questionTpl.content.cloneNode(true).querySelector('[data-wtb-question]');
        rowCount += 1;
        question.querySelectorAll('.ws-field').forEach(function (field, index) {
            var control = field.querySelector('input, textarea, select');
            var label = field.querySelector('label');
            if (control && label) {
                control.id = 'wtb-q-' + rowCount + '-' + index;
                label.setAttribute('for', control.id);
            }
        });
        var select = question.querySelector('[data-wtb-q-type]');
        if (data.type) {
            select.value = data.type;
        }
        question.querySelector('[data-wtb-q-label]').value = data.label || '';
        question.querySelector('[data-wtb-q-options]').value = (data.options || []).join('\n');
        question.querySelector('[data-wtb-q-required]').checked = data.required !== false;
        question.querySelectorAll('input, textarea, select').forEach(function (control) {
            control.disabled = locked;
        });
        questionsWrap.appendChild(question);
        initDropdowns(question);
        syncQuestion(question);
        select.addEventListener('change', function () {
            syncQuestion(question);
            renderPreview();
        });
        return question;
    }

    function questionData() {
        return questionRows().map(function (question) {
            return {
                type: question.querySelector('[data-wtb-q-type]').value,
                label: question.querySelector('[data-wtb-q-label]').value.trim(),
                required: question.querySelector('[data-wtb-q-required]').checked,
                options: question.querySelector('[data-wtb-q-options]').value.split('\n').map(function (line) {
                    return line.trim();
                }).filter(function (line) {
                    return line !== '';
                }),
            };
        });
    }

    function flowData() {
        return {
            button: flowButton.value.trim(),
            title: flowTitle.value.trim(),
            submit: flowSubmit.value.trim(),
            questions: questionData(),
        };
    }

    function formMark(type) {
        var mark = document.createElement('span');
        mark.className = 'wtb-form-mark' + (type === 'multi' ? ' wtb-form-mark--box' : '');
        return mark;
    }

    function renderFormPreview() {
        root.querySelector('[data-wtb-p-form-title]').textContent = flowTitle.value.trim() || 'Form title';
        root.querySelector('[data-wtb-p-form-submit]').textContent = flowSubmit.value.trim() || 'Submit';
        var wrap = root.querySelector('[data-wtb-p-form]');
        wrap.innerHTML = '';
        questionData().forEach(function (question) {
            var label = (question.label || 'Question') + (question.required ? '' : ' (optional)');
            var field = document.createElement('div');
            field.className = 'wtb-form-field';
            if (question.type === 'single' || question.type === 'multi') {
                var title = document.createElement('p');
                title.className = 'wtb-form-label';
                title.textContent = label;
                field.appendChild(title);
                (question.options.length ? question.options : ['Option 1', 'Option 2']).forEach(function (option) {
                    var row = document.createElement('span');
                    row.className = 'wtb-form-option';
                    row.appendChild(document.createTextNode(option));
                    row.appendChild(formMark(question.type));
                    field.appendChild(row);
                });
            } else {
                var box = document.createElement('span');
                box.className = 'wtb-form-input' + (question.type === 'long_text' ? ' wtb-form-input--area' : '');
                box.appendChild(document.createTextNode(label));
                if (FORM_ICONS[question.type]) {
                    box.insertAdjacentHTML('beforeend', '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true">' + FORM_ICONS[question.type] + '</svg>');
                }
                field.appendChild(box);
            }
            wrap.appendChild(field);
        });
    }

    if (flowWrap) {
        flowWrap.addEventListener('input', renderPreview);
        flowWrap.addEventListener('change', renderPreview);
        questionsWrap.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-wtb-question-remove]');
            if (remove) {
                remove.closest('[data-wtb-question]').remove();
                syncQuestions();
                renderPreview();
            }
        });
        if (questionAdd) {
            questionAdd.addEventListener('click', function () {
                var question = addQuestion({});
                syncQuestions();
                renderPreview();
                question.querySelector('[data-wtb-q-label]').focus();
            });
        }
    }

    // —— Wiring ——

    function onContentChange() {
        renderSamples();
        renderPreview();
    }

    function fitBody() {
        body.style.height = 'auto';
        body.style.height = body.scrollHeight + 'px';
    }

    function nextVariable() {
        var next = 1;
        var texts = [header.value, body.value, mediaUrl.value, listButton.value];
        listData().forEach(function (item) {
            texts.push(item.name, item.id, item.description);
        });
        texts.forEach(function (text) {
            text.replace(VAR_RE, function (_, name) {
                if (/^\d+$/.test(name)) {
                    next = Math.max(next, parseInt(name, 10) + 1);
                }
                return _;
            });
        });
        return '{{' + next + '}}';
    }

    function insertAtCursor(field, token) {
        var start = field.selectionStart;
        var end = field.selectionEnd;
        if (typeof start !== 'number' || document.activeElement !== field && start === 0 && end === 0) {
            start = end = field.value.length;
        }
        if (field.value.length - (end - start) + token.length > parseInt(field.getAttribute('maxlength'), 10)) {
            field.focus();
            return;
        }
        field.value = field.value.slice(0, start) + token + field.value.slice(end);
        field.focus();
        field.setSelectionRange(start + token.length, start + token.length);
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    root.addEventListener('click', function (event) {
        var button = event.target.closest('[data-wtb-add-var]');
        if (!button) {
            return;
        }
        var target = button.getAttribute('data-wtb-add-var');
        var token = nextVariable();
        insertAtCursor(target === 'body' ? body : button.closest('.ws-field').querySelector('input'), token);
    });

    var editLink = root.querySelector('.wtb-edit');
    if (editLink) {
        editLink.addEventListener('click', function (event) {
            event.stopPropagation();
            if (body.value.trim() === '' || !window.LeadBirdModal) {
                return;
            }
            event.preventDefault();
            window.LeadBirdModal.confirm({
                title: 'Go back and edit?',
                message: 'Changing the name, language or content type clears the message you have written here.',
                confirmLabel: 'Go back',
                cancelLabel: 'Stay here',
            }).then(function (ok) {
                if (ok) {
                    window.location.href = editLink.href;
                }
            });
        });
    }

    header.addEventListener('input', onContentChange);
    body.addEventListener('input', onContentChange);
    body.addEventListener('input', fitBody);
    footer.addEventListener('input', renderPreview);
    samplesBox.addEventListener('input', renderPreview);
    buttonList.addEventListener('input', renderPreview);
    if (nameInput && heading) {
        nameInput.addEventListener('input', function () {
            var clean = nameInput.value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
            if (clean !== nameInput.value) {
                nameInput.value = clean;
            }
            heading.textContent = clean || 'New template';
        });
    }

    // —— Submit and delete ——

    function post(payload) {
        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload),
        }).then(function (res) {
            return res.json().catch(function () {
                return { ok: false };
            });
        });
    }

    function showError(title, message) {
        window.LeadBirdModal.alert({
            title: title,
            message: message || 'Something went wrong. Please try again.',
            tone: 'error',
        });
    }

    var payloadCard = root.querySelector('[data-wtb-payload]');

    function showPayload(payload) {
        if (!payloadCard) {
            return;
        }
        payloadCard.querySelector('[data-wtb-payload-code]').textContent = JSON.stringify(payload, null, 2);
        payloadCard.hidden = false;
        payloadCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    var dialog = root.querySelector('[data-wtb-dialog]');
    var dialogReturn = null;

    function closeDialog() {
        dialog.classList.add('is-hidden');
        dialog.setAttribute('aria-hidden', 'true');
        if (dialogReturn) {
            dialogReturn.focus();
        }
    }

    function categoryValue() {
        var checked = dialog ? dialog.querySelector('[data-wtb-category]:checked') : null;
        return checked ? checked.value : '';
    }

    if (dialog) {
        document.body.appendChild(dialog);
        dialog.addEventListener('change', function (event) {
            if (event.target.matches('[data-wtb-category]')) {
                dialog.querySelector('[data-wtb-dialog-submit]').disabled = false;
            }
        });
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog || event.target.closest('[data-wtb-dialog-cancel]')) {
                closeDialog();
            } else if (event.target.closest('[data-wtb-dialog-submit]') && categoryValue() !== '') {
                closeDialog();
                submitTemplate();
            }
        });
        dialog.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !event.target.closest('.lb-dropdown.is-open')) {
                event.preventDefault();
                closeDialog();
            }
        });
    }

    if (submitButton) {
        submitButton.addEventListener('click', function () {
            dialogReturn = submitButton;
            dialog.classList.remove('is-hidden');
            dialog.setAttribute('aria-hidden', 'false');
            var first = dialog.querySelector('[data-wtb-category]:checked:not([disabled])')
                || dialog.querySelector('[data-wtb-category]:not([disabled])')
                || dialog.querySelector('[data-wtb-dialog-submit]');
            first.focus();
        });
    }

    function templatePayload(action) {
        var collected = {};
        samplesBox.querySelectorAll('[data-var]').forEach(function (input) {
            collected[input.getAttribute('data-var')] = input.value.trim();
        });
        rows().forEach(function (row) {
            var input = row.querySelector('[data-wtb-url-sample]');
            if (!row.querySelector('[data-wtb-url-sample-field]').hidden && input.getAttribute('data-var')) {
                collected[input.getAttribute('data-var')] = input.value.trim();
            }
        });
        if (mediaVariable() !== '') {
            collected[mediaVariable()] = mediaSample.value.trim();
        }
        return {
            action: action,
            preview: isPreview,
            id: templateId,
            name: nameInput ? nameInput.value.trim() : '',
            language: language.value,
            category: categoryValue(),
            content_type: typeSelect.value,
            header: header.value,
            media_url: mediaUrl.value.trim(),
            body: body.value,
            footer: footer.value,
            samples: collected,
            buttons: buttonData(),
            list_button: isList() ? listButton.value.trim() : '',
            list_items: listData(),
            header_type: headerTypeSelect ? headerTypeSelect.value : '',
            catalog_format: catalogFormat(),
            cards: cardData(),
            auth: authWrap ? authData() : {},
            flow: flowWrap ? flowData() : {},
        };
    }

    var saveButton = root.querySelector('[data-wtb-save]');
    if (saveButton) {
        saveButton.addEventListener('click', function () {
            var label = saveButton.textContent;
            saveButton.disabled = true;
            saveButton.textContent = 'Saving…';
            post(templatePayload('save_template_draft'))
                .then(function (result) {
                    if (result && result.ok && result.redirect) {
                        window.location.href = result.redirect;
                        return;
                    }
                    saveButton.disabled = false;
                    saveButton.textContent = label;
                    if (result && result.ok && result.preview) {
                        window.LeadBirdModal.alert({
                            title: 'Not saved in preview mode',
                            message: 'In preview mode nothing is saved. With a connected number, Save keeps this template in Lead Bird.',
                        });
                        return;
                    }
                    showError('Template not saved', result && result.message);
                })
                .catch(function () {
                    saveButton.disabled = false;
                    saveButton.textContent = label;
                    showError('Template not saved', 'Check your internet connection and try again.');
                });
        });
    }

    function submitTemplate() {
        var label = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting…';
        post(templatePayload('submit_template'))
            .then(function (result) {
                if (result && result.ok && result.redirect) {
                    window.location.href = result.redirect;
                    return;
                }
                submitButton.disabled = false;
                submitButton.textContent = label;
                if (result && result.ok && result.payload) {
                    showPayload(result.payload);
                    return;
                }
                showError('Template not submitted', result && result.message);
            })
            .catch(function () {
                submitButton.disabled = false;
                submitButton.textContent = label;
                showError('Template not submitted', 'Check your internet connection and try again.');
            });
    }

    var deleteButton = root.querySelector('[data-wtb-delete]');
    if (deleteButton) {
        deleteButton.addEventListener('click', function () {
            window.LeadBirdModal.confirm({
                title: 'Delete this template?',
                message: root.getAttribute('data-draft') === '1'
                    ? 'It hasn’t been sent to WhatsApp, so it is only removed from Lead Bird. This can’t be undone.'
                    : 'This deletes it from WhatsApp too. You can’t make a new template with the same name for 30 days.',
                confirmLabel: 'Delete',
                cancelLabel: 'Cancel',
                tone: 'danger',
            }).then(function (ok) {
                if (!ok) {
                    return;
                }
                if (isPreview) {
                    window.location.href = root.getAttribute('data-templates-url');
                    return;
                }
                post({ action: 'delete_template', id: templateId })
                    .then(function (result) {
                        if (result && result.ok) {
                            window.location.href = root.getAttribute('data-templates-url');
                            return;
                        }
                        showError('Template not deleted', result && result.message);
                    })
                    .catch(function () {
                        showError('Template not deleted', 'Check your internet connection and try again.');
                    });
            });
        });
    }

    // —— Hannah ——

    var libraryNode = root.querySelector('[data-wtb-hannah-library]');
    var library = libraryNode ? JSON.parse(libraryNode.textContent || '[]') : [];
    var samplesNode = root.querySelector('[data-wtb-hannah-samples]');
    var knownSamples = samplesNode ? JSON.parse(samplesNode.textContent || '{}') : {};

    function libraryType(text) {
        var lower = text.toLowerCase().replace(/_/g, ' ');
        var best = null;
        var bestScore = 0;
        library.forEach(function (type) {
            var score = 0;
            type.keywords.forEach(function (keyword) {
                if (new RegExp('(^|[^a-z])' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).test(lower)) {
                    score += keyword.length;
                }
            });
            if (score > bestScore) {
                best = type;
                bestScore = score;
            }
        });
        if (!best) {
            return null;
        }
        var covered = best.keywords.filter(function (keyword) {
            return lower.indexOf(keyword) !== -1;
        }).join(' ');
        var ignored = ['a', 'an', 'the', 'and', 'for', 'to', 'of', 'our', 'my', 'new', 'message', 'template', 'msg', 'update'];
        var uncovered = lower.split(/[^a-z0-9]+/).filter(function (word) {
            return word !== '' && ignored.indexOf(word) === -1 && covered.indexOf(word) === -1;
        });
        return uncovered.length ? null : best;
    }

    function libraryNext(type) {
        var key = 'lb_hannah_library_' + type.key;
        var index = -1;
        try {
            index = parseInt(window.localStorage.getItem(key) || '-1', 10);
        } catch (error) {
            index = Math.floor(Math.random() * type.bodies.length);
        }
        var pick = null;
        for (var step = 1; step <= type.bodies.length; step += 1) {
            pick = (index + step) % type.bodies.length;
            if (type.bodies[pick].text !== body.value.trim()) {
                break;
            }
        }
        try {
            window.localStorage.setItem(key, String(pick));
        } catch (error) {
            // Rotation just restarts next time.
        }
        return type.bodies[pick];
    }

    function fillBody(text, newSamples) {
        Object.keys(newSamples || {}).forEach(function (name) {
            samples[name] = newSamples[name];
            var input = samplesBox.querySelector('[data-var="' + name + '"]');
            if (input) {
                input.value = newSamples[name];
            }
        });
        body.value = text;
        body.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function fillInput(input, text) {
        input.value = text;
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function askHannah(link, field, extra) {
        var label = link.innerHTML;
        link.disabled = true;
        link.lastChild.textContent = 'Writing…';
        return post(Object.assign({
            action: 'write_template_content',
            field: field,
            name: nameInput ? nameInput.value : '',
            body: body.value,
            buttons: buttonData().map(function (button) {
                return button.text;
            }),
            category: categoryValue(),
            content_type: typeSelect.value,
        }, extra))
            .then(function (result) {
                if (!result || !result.ok) {
                    showError('Hannah couldn’t write this', result && result.message);
                    return null;
                }
                return result;
            })
            .catch(function () {
                showError('Hannah couldn’t write this', 'Check your internet connection and try again.');
                return null;
            })
            .finally(function () {
                link.disabled = false;
                link.innerHTML = label;
            });
    }

    function writeWithHannah(link) {
        var field = link.getAttribute('data-wtb-hannah-open');
        var fieldWrap = link.closest('.ws-field');

        if (field === 'body') {
            var fillMedia = function () {
                if (typeSelect.value === 'media' && mediaUrl.value.trim() === '') {
                    fillInput(mediaUrl, nextVariable());
                }
            };
            var type = nameInput ? libraryType(nameInput.value) : null;
            if (type) {
                askHannah(link, 'pick', {}).then(function (result) {
                    if (result) {
                        var pick = libraryNext(type);
                        fillBody(pick.text, pick.samples);
                        fillMedia();
                    }
                });
                return;
            }
            askHannah(link, 'body', {}).then(function (result) {
                if (result) {
                    fillBody(result.text, result.samples);
                    fillMedia();
                }
            });
            return;
        }

        if (field === 'media') {
            if (mediaVariable() === '') {
                fillInput(mediaUrl, nextVariable());
            }
            mediaSample.focus();
            return;
        }

        if (field === 'sample') {
            var variable = link.getAttribute('data-wtb-hannah-var');
            var sampleInput = fieldWrap.querySelector('[data-var]');
            if (knownSamples[variable] && sampleInput.value.trim() !== knownSamples[variable]) {
                askHannah(link, 'pick', {}).then(function (result) {
                    if (result) {
                        fillInput(sampleInput, knownSamples[variable]);
                    }
                });
                return;
            }
            askHannah(link, 'sample', { variable: variable, current: sampleInput.value }).then(function (result) {
                if (result) {
                    fillInput(sampleInput, result.text);
                }
            });
            return;
        }

        if (field === 'button_text') {
            var row = link.closest('[data-wtb-button]');
            var textInput = row.querySelector('[data-wtb-btext]');
            askHannah(link, 'button_text', { button_type: row.getAttribute('data-type'), current: textInput.value }).then(function (result) {
                if (result) {
                    fillInput(textInput, result.text);
                }
            });
            return;
        }

        if (field === 'name' && nameInput) {
            askHannah(link, 'name', { current: nameInput.value }).then(function (result) {
                if (result) {
                    fillInput(nameInput, result.text);
                }
            });
        }
    }

    root.addEventListener('click', function (event) {
        var link = event.target.closest('[data-wtb-hannah-open]');
        if (link && !link.disabled) {
            event.preventDefault();
            writeWithHannah(link);
        }
    });

    (spec.buttons || []).forEach(addRow);
    (spec.list_items || []).forEach(addListItem);
    if (cardsWrap) {
        (spec.cards || []).forEach(addCard);
        while (!locked && cardRows().length < CARDS_MIN) {
            addCard({});
        }
        syncCards();
    }
    if (questionsWrap) {
        (spec.questions || []).forEach(addQuestion);
        if (!locked && !questionRows().length) {
            addQuestion({});
        }
        syncQuestions();
    }
    syncList();
    updateCounter(listButton);
    syncButtons();
    renderMedia();
    syncMediaSample();
    renderSamples();
    root.querySelectorAll('[data-wtb-count]').forEach(updateCounter);
    renderPreview();
    fitBody();
})();
