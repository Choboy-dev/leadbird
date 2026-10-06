(function () {
    var config = window.LeadBirdSocialMedia || {};
    var FFMPEG_BASE = (config.appUrl || '') + '/assets/vendor/ffmpeg/';
    var FFMPEG_JS = FFMPEG_BASE + 'ffmpeg.js';
    var FFMPEG_UTIL = FFMPEG_BASE + 'ffmpeg-util.js';
    var FFMPEG_CORE = FFMPEG_BASE;

    var modal = document.querySelector('[data-smm-video-editor-modal]');
    if (!modal) return;

    var dialog = modal.querySelector('[data-smm-video-editor-dialog]');
    var titleEl = modal.querySelector('[data-smm-video-editor-title]');
    var loadingEl = modal.querySelector('[data-smm-video-editor-loading]');
    var workspaceEl = modal.querySelector('[data-smm-video-editor-workspace]');
    var errorEl = modal.querySelector('[data-smm-video-editor-error]');
    var errorCopyEl = modal.querySelector('[data-smm-video-editor-error-copy]');
    var previewEl = modal.querySelector('[data-smm-video-editor-preview]');
    var stageEl = modal.querySelector('[data-smm-video-editor-stage]');
    var cropOverlayEl = modal.querySelector('[data-smm-video-editor-crop-overlay]');
    var cropWindowEl = modal.querySelector('[data-smm-video-editor-crop-window]');
    var trimPanelEl = modal.querySelector('[data-smm-video-editor-trim-panel]');
    var cropPanelEl = modal.querySelector('[data-smm-video-editor-crop-panel]');
    var cropRatiosEl = modal.querySelector('[data-smm-video-editor-crop-ratios]');
    var cropNoteEl = modal.querySelector('[data-smm-video-editor-crop-note]');
    var trimDurationEl = modal.querySelector('[data-smm-video-editor-trim-duration]');
    var trimStartLabelEl = modal.querySelector('[data-smm-video-editor-trim-start-label]');
    var trimEndLabelEl = modal.querySelector('[data-smm-video-editor-trim-end-label]');
    var trimPlayheadEl = modal.querySelector('[data-smm-video-editor-trim-playhead]');
    var trimMaskLeftEl = modal.querySelector('[data-smm-video-editor-trim-mask-left]');
    var trimMaskRightEl = modal.querySelector('[data-smm-video-editor-trim-mask-right]');
    var trimTrackEl = modal.querySelector('[data-smm-video-editor-trim-track]');
    var trimSelectionEl = modal.querySelector('[data-smm-video-editor-trim-selection]');
    var trimStartHandle = modal.querySelector('[data-smm-video-editor-trim-start]');
    var trimEndHandle = modal.querySelector('[data-smm-video-editor-trim-end]');
    var trimPlayBtn = modal.querySelector('[data-smm-video-editor-trim-play]');
    var trimPlayIconEl = trimPlayBtn ? trimPlayBtn.querySelector('.smm-video-editor-trim-play-icon') : null;
    var trimPauseIconEl = trimPlayBtn ? trimPlayBtn.querySelector('.smm-video-editor-trim-pause-icon') : null;
    var cropPlayBtn = modal.querySelector('[data-smm-video-editor-crop-play]');
    var cropTimeEl = modal.querySelector('[data-smm-video-editor-crop-time]');
    var cropPlayIconEl = cropPlayBtn ? cropPlayBtn.querySelector('.smm-video-editor-play-icon') : null;
    var cropPauseIconEl = cropPlayBtn ? cropPlayBtn.querySelector('.smm-video-editor-pause-icon') : null;
    var saveBtn = modal.querySelector('[data-smm-video-editor-save]');
    var saveLabelEl = modal.querySelector('[data-smm-video-editor-save-label]');
    var cancelBtn = modal.querySelector('.smm-video-editor-cancel');
    var saveProgressEl = modal.querySelector('[data-smm-video-editor-save-progress]');
    var saveProgressFillEl = modal.querySelector('[data-smm-video-editor-save-progress-fill]');
    var saveProgressLabelEl = modal.querySelector('[data-smm-video-editor-save-progress-label]');
    var closeBtns = modal.querySelectorAll('[data-smm-video-editor-close]');

    var ffmpegInstance = null;
    var ffmpegLoadingPromise = null;
    var scriptsLoaded = false;
    var previewUrl = '';
    var activeSession = null;
    var trimDrag = null;
    var cropDrag = null;
    var isSaving = false;

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[data-src="' + src + '"]');
            if (existing) {
                if (existing.dataset.loaded === '1') {
                    resolve();
                    return;
                }
                existing.addEventListener('load', resolve, { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }
            var script = document.createElement('script');
            script.src = src;
            script.dataset.src = src;
            script.async = true;
            script.onload = function () {
                script.dataset.loaded = '1';
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function ensureFfmpegLoaded() {
        if (ffmpegInstance) return Promise.resolve(ffmpegInstance);
        if (ffmpegLoadingPromise) return ffmpegLoadingPromise;

        ffmpegLoadingPromise = loadScript(FFMPEG_JS)
            .then(function () { return loadScript(FFMPEG_UTIL); })
            .then(function () {
                if (!window.FFmpegWASM || !window.FFmpegUtil) {
                    throw new Error('Video editor libraries failed to load.');
                }
                var FFmpeg = window.FFmpegWASM.FFmpeg;
                var ffmpeg = new FFmpeg();
                return ffmpeg.load({
                    coreURL: FFMPEG_CORE + 'ffmpeg-core.js',
                    wasmURL: FFMPEG_CORE + 'ffmpeg-core.wasm',
                }).then(function () {
                    ffmpegInstance = ffmpeg;
                    scriptsLoaded = true;
                    return ffmpeg;
                });
            });

        return ffmpegLoadingPromise;
    }

    function formatDuration(seconds) {
        var total = Math.max(0, Number(seconds) || 0);
        var mins = Math.floor(total / 60);
        var secs = Math.floor(total % 60);
        return mins + ':' + String(secs).padStart(2, '0');
    }

    function setVisible(el, show) {
        if (!el) return;
        el.classList.toggle('is-hidden', !show);
        el.hidden = !show;
    }

    function setSaveProgress(percent) {
        var pct = Math.max(0, Math.min(100, Math.round(Number(percent) || 0)));
        if (saveProgressFillEl) saveProgressFillEl.style.width = pct + '%';
        if (saveProgressLabelEl) saveProgressLabelEl.textContent = pct + '%';
        if (saveProgressEl) saveProgressEl.setAttribute('aria-valuenow', String(pct));
    }

    function setSavingState(isSavingState, progress) {
        isSaving = isSavingState;

        if (saveBtn) {
            saveBtn.disabled = isSavingState;
            saveBtn.setAttribute('aria-disabled', isSavingState ? 'true' : 'false');
        }

        if (saveLabelEl) saveLabelEl.textContent = isSavingState ? 'Saving…' : 'Save';

        if (workspaceEl) workspaceEl.classList.toggle('is-saving', isSavingState);
        setVisible(saveProgressEl, isSavingState);

        closeBtns.forEach(function (btn) {
            btn.disabled = isSavingState;
            if (isSavingState) {
                btn.setAttribute('aria-disabled', 'true');
            } else {
                btn.removeAttribute('aria-disabled');
            }
        });

        if (cancelBtn) {
            cancelBtn.disabled = isSavingState;
            if (isSavingState) {
                cancelBtn.setAttribute('aria-disabled', 'true');
            } else {
                cancelBtn.removeAttribute('aria-disabled');
            }
        }

        if (typeof progress === 'number') {
            setSaveProgress(progress);
        } else if (!isSavingState) {
            setSaveProgress(0);
        }
    }

    function setSaving(isSavingState) {
        setSavingState(isSavingState, isSavingState ? 0 : 0);
    }

    function revokePreviewUrl() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = '';
        }
    }

    function resetModalUi() {
        setVisible(loadingEl, true);
        setVisible(workspaceEl, false);
        setVisible(errorEl, false);
        setVisible(trimPanelEl, false);
        setVisible(cropPanelEl, false);
        setVisible(cropOverlayEl, false);
        setSavingState(false);
        setSaveProgress(0);
        if (previewEl) {
            previewEl.pause();
            previewEl.removeAttribute('src');
            previewEl.load();
        }
    }

    function showError(message) {
        setVisible(loadingEl, false);
        setVisible(workspaceEl, false);
        setVisible(errorEl, true);
        if (errorCopyEl) errorCopyEl.textContent = message || 'Something went wrong while opening the editor.';
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.setAttribute('aria-disabled', 'true');
        }
    }

    function computeCenterCrop(width, height, targetRatio) {
        var currentRatio = width / height;
        var cropW;
        var cropH;
        var x;
        var y;
        if (currentRatio > targetRatio) {
            cropH = height;
            cropW = Math.min(width, Math.floor(height * targetRatio));
            x = Math.max(0, Math.round((width - cropW) / 2));
            y = 0;
        } else {
            cropW = width;
            cropH = Math.min(height, Math.floor(width / targetRatio));
            x = 0;
            y = Math.max(0, Math.round((height - cropH) / 2));
        }
        return { cropW: cropW, cropH: cropH, x: x, y: y };
    }

    function syncCropDragState(session) {
        var crop = session.crop;
        var width = session.meta.width || 0;
        var height = session.meta.height || 0;
        if (!crop || !width || !height) {
            session.cropDragMode = null;
            return;
        }

        var slackX = width - crop.cropW;
        var slackY = height - crop.cropH;
        if (slackX > 0 && slackY > 0) {
            session.cropDragMode = 'both';
        } else if (slackX > 0) {
            session.cropDragMode = 'x';
        } else if (slackY > 0) {
            session.cropDragMode = 'y';
        } else {
            session.cropDragMode = null;
        }
    }

    function getDefaultCropRatio(session) {
        if (session.targetRatio) return session.targetRatio;

        var width = session.meta.width || 0;
        var height = session.meta.height || 0;
        if (!width || !height || !session.aspectMin || !session.aspectMax) {
            return 9 / 16;
        }

        var currentRatio = width / height;
        if (currentRatio > session.aspectMax) return session.aspectMax;
        if (currentRatio < session.aspectMin) return session.aspectMin;
        return currentRatio >= 1 ? session.aspectMax : session.aspectMin;
    }

    function shouldShowCropRatioPicker(session) {
        return !!(session.aspectMin && session.aspectMax && !session.fixedTargetRatio);
    }

    function applyCropRatio(session, ratio) {
        var width = session.meta.width || 0;
        var height = session.meta.height || 0;
        if (!width || !height || !ratio) return;

        session.selectedCropRatio = ratio;
        session.crop = computeCenterCrop(width, height, ratio);
        syncCropDragState(session);
        setCropNote(session);
        updateCropOverlay(session);
        bindCropDrag(session);
        updateCropRatioPickerUi(session);
    }

    function updateCropRatioPickerUi(session) {
        if (!cropRatiosEl) return;
        var activeRatio = session.selectedCropRatio || getCropTargetRatio(session);
        cropRatiosEl.querySelectorAll('[data-smm-video-editor-crop-ratio]').forEach(function (btn) {
            var ratio = parseFloat(btn.getAttribute('data-smm-video-editor-crop-ratio') || '');
            var isActive = isFinite(ratio) && Math.abs(ratio - activeRatio) < 0.02;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function bindCropRatioPicker(session) {
        if (!cropRatiosEl) return;

        var showPicker = shouldShowCropRatioPicker(session);
        setVisible(cropRatiosEl, showPicker);
        if (!showPicker) return;

        cropRatiosEl.querySelectorAll('[data-smm-video-editor-crop-ratio]').forEach(function (btn) {
            if (btn.dataset.smmCropRatioBound === '1') return;
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                var ratio = parseFloat(btn.getAttribute('data-smm-video-editor-crop-ratio') || '');
                if (!isFinite(ratio)) return;
                applyCropRatio(session, ratio);
            });
            btn.dataset.smmCropRatioBound = '1';
        });

        updateCropRatioPickerUi(session);
    }

    function getCropTargetRatio(session) {
        if (session.selectedCropRatio) return session.selectedCropRatio;
        if (session.targetRatio) return session.targetRatio;
        if (session.aspectMin && session.aspectMax) {
            return getDefaultCropRatio(session);
        }
        return 9 / 16;
    }

    function clampCropPosition(session) {
        var crop = session.crop;
        var width = session.meta.width || 0;
        var height = session.meta.height || 0;
        if (!crop || !width || !height) return;

        crop.x = Math.max(0, Math.min(width - crop.cropW, Math.round(crop.x)));
        crop.y = Math.max(0, Math.min(height - crop.cropH, Math.round(crop.y)));
    }

    function getCropDisplayMetrics(session) {
        if (!stageEl || !previewEl) return null;

        var stageRect = stageEl.getBoundingClientRect();
        var videoW = session.meta.width || previewEl.videoWidth;
        var videoH = session.meta.height || previewEl.videoHeight;
        if (!videoW || !videoH || !stageRect.width || !stageRect.height) return null;

        var scale = Math.min(stageRect.width / videoW, stageRect.height / videoH);
        var displayW = videoW * scale;
        var displayH = videoH * scale;
        var offsetX = (stageRect.width - displayW) / 2;
        var offsetY = (stageRect.height - displayH) / 2;

        return { scale: scale, offsetX: offsetX, offsetY: offsetY, videoW: videoW, videoH: videoH };
    }

    function computeRangeCrop(width, height, minRatio, maxRatio) {
        var currentRatio = width / height;
        if (currentRatio > maxRatio) {
            return computeCenterCrop(width, height, maxRatio);
        }
        if (currentRatio < minRatio) {
            return computeCenterCrop(width, height, minRatio);
        }
        return { cropW: width, cropH: height, x: 0, y: 0 };
    }

    function updateTrimUi(session) {
        if (!trimSelectionEl || !trimStartHandle || !trimEndHandle || !trimDurationEl) return;
        var duration = session.duration || 0;
        if (duration <= 0) return;

        var startPct = (session.trimStart / duration) * 100;
        var endPct = (session.trimEnd / duration) * 100;
        trimStartHandle.style.left = startPct + '%';
        trimEndHandle.style.left = endPct + '%';
        trimSelectionEl.style.left = startPct + '%';
        trimSelectionEl.style.width = Math.max(0, endPct - startPct) + '%';
        if (trimMaskLeftEl) trimMaskLeftEl.style.width = startPct + '%';
        if (trimMaskRightEl) {
            trimMaskRightEl.style.left = endPct + '%';
            trimMaskRightEl.style.width = Math.max(0, 100 - endPct) + '%';
        }
        if (trimStartLabelEl) trimStartLabelEl.textContent = formatDuration(session.trimStart);
        if (trimEndLabelEl) trimEndLabelEl.textContent = formatDuration(session.trimEnd);
        trimDurationEl.textContent = formatDuration(Math.max(0, session.trimEnd - session.trimStart)) + ' selected';
    }

    function updateTrimPlayhead(session, currentTime) {
        if (!trimPlayheadEl || !session.duration) return;
        var pct = (currentTime / session.duration) * 100;
        trimPlayheadEl.style.left = pct + '%';
    }

    function setTrimPlaybackUi(isPlaying) {
        if (!trimPlayBtn) return;
        trimPlayBtn.classList.toggle('is-playing', isPlaying);
        trimPlayBtn.setAttribute('aria-label', isPlaying ? 'Pause selection' : 'Play selection');
        if (trimPlayIconEl) trimPlayIconEl.classList.toggle('is-hidden', isPlaying);
        if (trimPauseIconEl) trimPauseIconEl.classList.toggle('is-hidden', !isPlaying);
        if (trimPlayheadEl) {
            trimPlayheadEl.classList.toggle('is-hidden', !isPlaying);
            trimPlayheadEl.hidden = !isPlaying;
        }
    }

    function bindTrimHandles(session) {
        if (!trimTrackEl || !trimStartHandle || !trimEndHandle) return;

        function pointerDown(handle, type) {
            return function (event) {
                event.preventDefault();
                trimDrag = { type: type, trackRect: trimTrackEl.getBoundingClientRect() };
            };
        }

        trimStartHandle.onmousedown = pointerDown(trimStartHandle, 'start');
        trimEndHandle.onmousedown = pointerDown(trimEndHandle, 'end');
        trimStartHandle.ontouchstart = pointerDown(trimStartHandle, 'start');
        trimEndHandle.ontouchstart = pointerDown(trimEndHandle, 'end');

        function onMove(clientX) {
            if (!trimDrag || !session.duration) return;
            var pct = (clientX - trimDrag.trackRect.left) / trimDrag.trackRect.width;
            pct = Math.max(0, Math.min(1, pct));
            var time = pct * session.duration;
            var minGap = 0.25;

            if (trimDrag.type === 'start') {
                session.trimStart = Math.min(time, session.trimEnd - minGap);
                session.trimStart = Math.max(0, session.trimStart);
            } else {
                session.trimEnd = Math.max(time, session.trimStart + minGap);
                session.trimEnd = Math.min(session.duration, session.trimEnd);
            }
            updateTrimUi(session);
        }

        function onPointerMove(event) {
            if (!trimDrag) return;
            var clientX = event.touches ? event.touches[0].clientX : event.clientX;
            onMove(clientX);
        }

        function onPointerUp() {
            trimDrag = null;
        }

        window.addEventListener('mousemove', onPointerMove);
        window.addEventListener('mouseup', onPointerUp);
        window.addEventListener('touchmove', onPointerMove, { passive: false });
        window.addEventListener('touchend', onPointerUp);
    }

    function updateCropOverlay(session) {
        if (!cropOverlayEl || !cropWindowEl || !stageEl || !previewEl) return;
        var crop = session.crop;
        if (!crop) return;

        var metrics = getCropDisplayMetrics(session);
        if (!metrics) return;

        var scale = metrics.scale;
        var offsetX = metrics.offsetX;
        var offsetY = metrics.offsetY;

        var winLeft = offsetX + crop.x * scale;
        var winTop = offsetY + crop.y * scale;
        var winWidth = crop.cropW * scale;
        var winHeight = crop.cropH * scale;

        cropWindowEl.style.left = winLeft + 'px';
        cropWindowEl.style.top = winTop + 'px';
        cropWindowEl.style.width = winWidth + 'px';
        cropWindowEl.style.height = winHeight + 'px';

        var canDrag = !!session.cropDragMode;
        cropWindowEl.classList.toggle('is-draggable', canDrag);
        cropWindowEl.setAttribute('aria-label', canDrag ? 'Drag to reposition crop area' : 'Crop preview');

        var stageRect = stageEl.getBoundingClientRect();
        var shades = {
            top: modal.querySelector('[data-smm-video-editor-crop-shade-top]'),
            bottom: modal.querySelector('[data-smm-video-editor-crop-shade-bottom]'),
            left: modal.querySelector('[data-smm-video-editor-crop-shade-left]'),
            right: modal.querySelector('[data-smm-video-editor-crop-shade-right]'),
        };

        if (shades.top) {
            shades.top.style.height = Math.max(0, winTop) + 'px';
        }
        if (shades.bottom) {
            shades.bottom.style.top = (winTop + winHeight) + 'px';
            shades.bottom.style.height = Math.max(0, stageRect.height - winTop - winHeight) + 'px';
        }
        if (shades.left) {
            shades.left.style.top = winTop + 'px';
            shades.left.style.width = Math.max(0, winLeft) + 'px';
            shades.left.style.height = winHeight + 'px';
        }
        if (shades.right) {
            shades.right.style.left = (winLeft + winWidth) + 'px';
            shades.right.style.top = winTop + 'px';
            shades.right.style.width = Math.max(0, stageRect.width - winLeft - winWidth) + 'px';
            shades.right.style.height = winHeight + 'px';
        }
    }

    function bindCropDrag(session) {
        if (!cropWindowEl) return;

        function pointerDown(event) {
            if (!session.cropDragMode) return;
            event.preventDefault();
            if (typeof cropWindowEl.setPointerCapture === 'function') {
                cropWindowEl.setPointerCapture(event.pointerId);
            }
            cropDrag = {
                mode: session.cropDragMode,
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
                originX: session.crop.x,
                originY: session.crop.y,
            };
            if (previewEl && !previewEl.paused) {
                previewEl.pause();
            }
            cropWindowEl.classList.add('is-dragging');
        }

        function onPointerMove(event) {
            if (!cropDrag || cropDrag.mode !== session.cropDragMode) return;
            if (event.pointerId !== cropDrag.pointerId) return;

            var metrics = getCropDisplayMetrics(session);
            if (!metrics || !metrics.scale) return;

            if (cropDrag.mode === 'both' || cropDrag.mode === 'x') {
                var deltaX = (event.clientX - cropDrag.startX) / metrics.scale;
                session.crop.x = cropDrag.originX + deltaX;
            }
            if (cropDrag.mode === 'both' || cropDrag.mode === 'y') {
                var deltaY = (event.clientY - cropDrag.startY) / metrics.scale;
                session.crop.y = cropDrag.originY + deltaY;
            }

            clampCropPosition(session);
            updateCropOverlay(session);
        }

        function onPointerUp() {
            if (!cropDrag) return;
            cropDrag = null;
            cropWindowEl.classList.remove('is-dragging');
        }

        if (cropWindowEl.dataset.smmCropDragBound !== '1') {
            cropWindowEl.addEventListener('pointerdown', pointerDown);
            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerUp);
            window.addEventListener('pointercancel', onPointerUp);
            cropWindowEl.dataset.smmCropDragBound = '1';
        }
    }

    function setCropNote(session) {
        if (!cropNoteEl) return;

        if (shouldShowCropRatioPicker(session) && !session.cropDragMode) {
            cropNoteEl.textContent = 'This ratio fits your video. Save to apply the crop.';
            return;
        }

        if (session.cropDragMode === 'both') {
            cropNoteEl.textContent = 'Drag the frame to reposition. Play the preview to check your crop.';
            return;
        }

        if (session.cropDragMode === 'x') {
            cropNoteEl.textContent = 'Drag left or right to choose what stays in frame.';
            return;
        }

        if (session.cropDragMode === 'y') {
            cropNoteEl.textContent = 'Drag up or down to choose what stays in frame.';
            return;
        }

        cropNoteEl.textContent = 'This ratio fits your video. Save to apply the crop.';
    }

    function updateCropTimeDisplay(session) {
        if (!cropTimeEl || !previewEl) return;
        var duration = session.duration || previewEl.duration || 0;
        cropTimeEl.textContent = formatDuration(previewEl.currentTime) + ' / ' + formatDuration(duration);
    }

    function setCropPlaybackUi(isPlaying) {
        if (!cropPlayBtn) return;
        cropPlayBtn.classList.toggle('is-playing', isPlaying);
        cropPlayBtn.setAttribute('aria-label', isPlaying ? 'Pause video' : 'Play video');
        if (cropPlayIconEl) cropPlayIconEl.classList.toggle('is-hidden', isPlaying);
        if (cropPauseIconEl) cropPauseIconEl.classList.toggle('is-hidden', !isPlaying);
    }

    function bindCropPlayback(session) {
        if (!cropPlayBtn || !previewEl) return;

        previewEl.loop = true;

        previewEl.addEventListener('timeupdate', function onCropTimeUpdate() {
            if (!activeSession || activeSession.mode !== 'crop') {
                previewEl.removeEventListener('timeupdate', onCropTimeUpdate);
                return;
            }
            updateCropTimeDisplay(session);
        });

        previewEl.addEventListener('loadedmetadata', function onCropMetadata() {
            updateCropTimeDisplay(session);
        });

        previewEl.addEventListener('play', function onCropPlay() {
            setCropPlaybackUi(true);
        });

        previewEl.addEventListener('pause', function onCropPause() {
            setCropPlaybackUi(false);
        });

        cropPlayBtn.onclick = function () {
            if (previewEl.paused) {
                previewEl.play().catch(function () {});
            } else {
                previewEl.pause();
            }
        };

        setCropPlaybackUi(false);
        updateCropTimeDisplay(session);
    }

    function prepareSession(options) {
        var meta = options.meta || {};
        var width = meta.width || 0;
        var height = meta.height || 0;
        var duration = meta.duration || 0;
        var session = {
            mode: options.mode || 'trim',
            file: options.file,
            meta: meta,
            duration: duration,
            trimStart: 0,
            trimEnd: Math.min(duration, options.maxDuration || duration),
            maxDuration: options.maxDuration || null,
            targetRatio: options.targetRatio || null,
            fixedTargetRatio: !!options.targetRatio,
            aspectMin: options.aspectMin || null,
            aspectMax: options.aspectMax || null,
            selectedCropRatio: null,
            cropDragMode: null,
            crop: null,
            onSave: typeof options.onSave === 'function' ? options.onSave : null,
        };

        if (session.mode === 'trim' && session.maxDuration && duration > session.maxDuration) {
            session.trimEnd = session.maxDuration;
        }

        if (session.mode === 'crop') {
            var cropRatio = getDefaultCropRatio(session);
            session.selectedCropRatio = cropRatio;
            session.crop = computeCenterCrop(width, height, cropRatio);
            syncCropDragState(session);
            setCropNote(session);
        }

        return session;
    }

    function showWorkspace(session) {
        setVisible(loadingEl, false);
        setVisible(workspaceEl, true);
        setVisible(errorEl, false);
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.setAttribute('aria-disabled', 'false');
        }

        if (titleEl) {
            titleEl.textContent = session.mode === 'trim' ? 'Trim video' : 'Crop video';
        }

        revokePreviewUrl();
        previewUrl = URL.createObjectURL(session.file);
        previewEl.src = previewUrl;
        previewEl.muted = true;
        previewEl.loop = false;

        setVisible(trimPanelEl, session.mode === 'trim');
        setVisible(cropPanelEl, session.mode === 'crop');
        setVisible(cropOverlayEl, session.mode === 'crop');

        if (session.mode === 'trim') {
            updateTrimUi(session);
            bindTrimHandles(session);
            setTrimPlaybackUi(false);
            if (trimPlayBtn) {
                trimPlayBtn.onclick = function () {
                    if (!previewEl.paused) {
                        previewEl.pause();
                        return;
                    }
                    previewEl.currentTime = session.trimStart;
                    previewEl.play().catch(function () {});
                    var stopAt = session.trimEnd;
                    var onTime = function () {
                        updateTrimPlayhead(session, previewEl.currentTime);
                        if (previewEl.currentTime >= stopAt) {
                            previewEl.pause();
                            previewEl.removeEventListener('timeupdate', onTime);
                            setTrimPlaybackUi(false);
                        }
                    };
                    previewEl.addEventListener('timeupdate', onTime);
                    setTrimPlaybackUi(true);
                };
            }
            previewEl.addEventListener('pause', function onTrimPause() {
                if (!activeSession || activeSession.mode !== 'trim') {
                    previewEl.removeEventListener('pause', onTrimPause);
                    return;
                }
                setTrimPlaybackUi(false);
            });
        }

        if (session.mode === 'crop') {
            var refreshCropUi = function () {
                bindCropRatioPicker(session);
                updateCropOverlay(session);
                bindCropDrag(session);
                updateCropTimeDisplay(session);
            };
            bindCropPlayback(session);
            bindCropRatioPicker(session);
            previewEl.onloadeddata = refreshCropUi;
            if (previewEl.readyState >= 2) refreshCropUi();
            window.addEventListener('resize', function onResize() {
                if (!activeSession || activeSession.mode !== 'crop') {
                    window.removeEventListener('resize', onResize);
                    return;
                }
                updateCropOverlay(activeSession);
            });
        }
    }

    function getInputExtension(file) {
        var name = (file && file.name) ? file.name : 'video.mp4';
        var parts = name.split('.');
        if (parts.length > 1) {
            return parts.pop().toLowerCase();
        }
        return 'mp4';
    }

    function buildOutputFileName(file) {
        var name = (file && file.name) ? file.name : 'video.mp4';
        var dot = name.lastIndexOf('.');
        var base = dot > 0 ? name.slice(0, dot) : name;
        return base + '-edited.mp4';
    }

    function processVideo(session) {
        var fetchFile = window.FFmpegUtil.fetchFile;
        var ffmpeg = ffmpegInstance;
        var ext = getInputExtension(session.file);
        var inputName = 'input.' + ext;
        var outputName = 'output.mp4';
        var filters = [];

        if (session.mode === 'trim') {
            return fetchFile(session.file).then(function (data) {
                return ffmpeg.writeFile(inputName, data).then(function () {
                    return ffmpeg.exec([
                        '-ss', String(session.trimStart),
                        '-to', String(session.trimEnd),
                        '-i', inputName,
                        '-c:v', 'libx264',
                        '-preset', 'veryfast',
                        '-c:a', 'aac',
                        '-movflags', '+faststart',
                        outputName,
                    ]);
                }).then(function () {
                    return ffmpeg.readFile(outputName);
                });
            });
        }

        if (session.mode === 'crop' && session.crop) {
            filters.push('crop=' + session.crop.cropW + ':' + session.crop.cropH + ':' + session.crop.x + ':' + session.crop.y);
        }

        if (filters.length === 0) {
            return Promise.reject(new Error('Nothing to change.'));
        }

        var vf = filters.join(',');

        return fetchFile(session.file).then(function (data) {
            return ffmpeg.writeFile(inputName, data).then(function () {
                var args = ['-i', inputName, '-vf', vf, '-c:v', 'libx264', '-preset', 'veryfast', '-c:a', 'aac', '-movflags', '+faststart', outputName];
                return ffmpeg.exec(args);
            }).then(function () {
                return ffmpeg.readFile(outputName);
            });
        });
    }

    function closeEditor() {
        if (isSaving) return;
        modal.classList.add('is-hidden');
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('smm-video-editor-open');
        activeSession = null;
        trimDrag = null;
        cropDrag = null;
        revokePreviewUrl();
        resetModalUi();
    }

    function openEditor(options) {
        if (!options || !options.file) return;

        activeSession = prepareSession(options);
        resetModalUi();
        modal.classList.remove('is-hidden');
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('smm-video-editor-open');

        ensureFfmpegLoaded()
            .then(function () {
                if (!activeSession) return;
                showWorkspace(activeSession);
            })
            .catch(function (error) {
                showError(error && error.message ? error.message : 'Could not load the video editor.');
            });
    }

    function saveEditor() {
        if (!activeSession || !ffmpegInstance || isSaving) return;

        if (previewEl) previewEl.pause();

        var progressHandler = function (event) {
            var raw = event && typeof event.progress === 'number' ? event.progress : 0;
            var clamped = Math.max(0, Math.min(1, raw));
            setSavingState(true, 12 + Math.round(clamped * 78));
        };

        setSavingState(true, 4);
        ffmpegInstance.on('progress', progressHandler);

        processVideo(activeSession)
            .then(function (outputData) {
                setSavingState(true, 95);
                var blob = new Blob([outputData], { type: 'video/mp4' });
                var editedFile = new File([blob], buildOutputFileName(activeSession.file), { type: 'video/mp4' });
                setSavingState(true, 100);
                return new Promise(function (resolve) {
                    window.setTimeout(resolve, 220);
                }).then(function () {
                    if (activeSession.onSave) activeSession.onSave(editedFile);
                    isSaving = false;
                    closeEditor();
                });
            })
            .catch(function (error) {
                setSavingState(false);
                if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
                    window.LeadBirdModal.alert({
                        title: 'Could not save video',
                        message: error && error.message ? error.message : 'Something went wrong while processing your video.',
                        tone: 'error',
                    });
                }
            })
            .finally(function () {
                ffmpegInstance.off('progress', progressHandler);
            });
    }

    modal.querySelectorAll('[data-smm-video-editor-close]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            closeEditor();
        });
    });

    if (saveBtn) {
        saveBtn.addEventListener('click', function (event) {
            event.preventDefault();
            saveEditor();
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('is-hidden') && !isSaving) {
            closeEditor();
            return;
        }

        if (event.key !== ' ' && event.code !== 'Space') return;
        if (modal.classList.contains('is-hidden') || isSaving) return;
        if (!activeSession || activeSession.mode !== 'crop' || !previewEl) return;

        var tag = (event.target && event.target.tagName) ? event.target.tagName.toLowerCase() : '';
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || tag === 'button') return;

        event.preventDefault();
        if (previewEl.paused) {
            previewEl.play().catch(function () {});
        } else {
            previewEl.pause();
        }
    });

    window.LeadBirdVideoEditor = {
        open: openEditor,
        close: closeEditor,
    };
})();
