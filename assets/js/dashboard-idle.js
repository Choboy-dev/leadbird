(function () {
    'use strict';

    var config = window.LeadBirdSession || {};
    var idleMs = Number(config.idleMs) || 30 * 60 * 1000;
    var warningMs = Number(config.warningMs) || 2 * 60 * 1000;
    var touchUrl = config.touchUrl || '';
    var logoutUrl = config.logoutUrl || '';
    var idleMinutes = Number(config.idleMinutes) || 30;

    if (!touchUrl || !logoutUrl || idleMs <= warningMs) {
        return;
    }

    var lastActivityAt = Date.now();
    var warningShown = false;
    var warningModalOpen = false;
    var touchTimer = null;
    var checkTimer = null;
    var countdownTimer = null;
    var logoutAt = 0;

    var activityEvents = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];

    function signOut() {
        window.location.href = logoutUrl;
    }

    function scheduleTouch() {
        if (touchTimer !== null) {
            return;
        }

        touchTimer = window.setTimeout(function () {
            touchTimer = null;
            fetch(touchUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: '{}',
            })
                .then(function (response) {
                    if (response.status === 401) {
                        signOut();
                    }
                })
                .catch(function () {
                    /* ignore transient network errors */
                });
        }, 45000);
    }

    function recordActivity() {
        if (warningModalOpen) {
            return;
        }

        lastActivityAt = Date.now();
        warningShown = false;
        logoutAt = 0;
        scheduleTouch();
    }

    function msUntilIdleLimit() {
        return idleMs - (Date.now() - lastActivityAt);
    }

    function formatCountdown(ms) {
        var totalSeconds = Math.max(0, Math.ceil(ms / 1000));
        var minutes = Math.floor(totalSeconds / 60);
        var seconds = totalSeconds % 60;
        if (minutes > 0) {
            return minutes + ' min ' + String(seconds).padStart(2, '0') + ' sec';
        }
        return seconds + ' sec';
    }

    function showWarningModal() {
        if (warningModalOpen || !window.LeadBirdModal || typeof window.LeadBirdModal.confirm !== 'function') {
            return;
        }

        warningModalOpen = true;
        logoutAt = lastActivityAt + idleMs;

        function messageText() {
            return 'You have been inactive for ' + idleMinutes + ' minutes. For your security, you will be signed out in '
                + formatCountdown(logoutAt - Date.now()) + '.';
        }

        window.LeadBirdModal.confirm({
            title: 'Still there?',
            message: messageText(),
            confirmLabel: 'Stay signed in',
            cancelLabel: 'Sign out now',
            tone: 'default',
        }).then(function (staySignedIn) {
            warningModalOpen = false;
            window.clearInterval(countdownTimer);
            countdownTimer = null;

            if (staySignedIn) {
                recordActivity();
                fetch(touchUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: '{}',
                }).catch(function () {
                    /* ignore */
                });
                return;
            }

            signOut();
        });

        countdownTimer = window.setInterval(function () {
            var remaining = logoutAt - Date.now();
            if (remaining <= 0) {
                window.clearInterval(countdownTimer);
                countdownTimer = null;
                signOut();
                return;
            }

            var messageEl = document.getElementById('lb-modal-message');
            if (messageEl) {
                messageEl.textContent = messageText();
            }
        }, 1000);
    }

    function evaluateIdleState() {
        var remaining = msUntilIdleLimit();

        if (remaining <= 0) {
            signOut();
            return;
        }

        if (remaining <= warningMs && !warningShown) {
            warningShown = true;
            showWarningModal();
        }
    }

    activityEvents.forEach(function (eventName) {
        document.addEventListener(eventName, recordActivity, { passive: true });
    });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            evaluateIdleState();
        }
    });

    recordActivity();
    checkTimer = window.setInterval(evaluateIdleState, 15000);
})();
