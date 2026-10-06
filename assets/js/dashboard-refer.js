(function () {
    'use strict';

    var page = document.querySelector('.ref-page');
    if (!page) return;

    var linkInput = document.getElementById('ref-link-input');
    var balanceValue = page.querySelector('[data-ref-balance-value]');
    var balanceToggle = page.querySelector('[data-ref-balance-toggle]');
    var copyBtn = page.querySelector('[data-ref-copy]');
    var shareBtn = page.querySelector('[data-ref-share]');
    var openClaimBtn = page.querySelector('[data-ref-open-claim]');
    var claimOverlay = document.querySelector('[data-ref-claim-overlay]');
    var closeClaimBtn = document.querySelector('[data-ref-close-claim]');
    var rewardToggle = page.querySelector('[data-ref-toggle-rewards]');
    var rewardHistory = page.querySelector('[data-ref-reward-history]');
    var inviteList = page.querySelector('.ref-invite-list');
    var invitePagination = page.querySelector('.ref-pagination');
    var inviteEmpty = page.querySelector('.ref-card--history > .ref-empty');

    var balanceVisible = true;
    var storedBalance = balanceValue ? balanceValue.textContent.trim() : '';

    function copyReferralLink() {
        if (!linkInput) return Promise.resolve(false);
        var text = linkInput.value;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).then(function () { return true; });
        }
        linkInput.focus();
        linkInput.select();
        try {
            return Promise.resolve(document.execCommand('copy'));
        } catch (e) {
            return Promise.resolve(false);
        }
    }

    function flashCopyLabel(btn) {
        if (!btn) return;
        var original = btn.textContent;
        btn.textContent = 'Copied';
        window.setTimeout(function () {
            btn.textContent = original;
        }, 1600);
    }

    copyBtn?.addEventListener('click', function () {
        copyReferralLink().then(function (ok) {
            if (ok) flashCopyLabel(copyBtn);
        });
    });

    shareBtn?.addEventListener('click', function () {
        var url = linkInput ? linkInput.value : '';
        if (!url) return;

        if (navigator.share) {
            navigator.share({
                title: 'Join Lead Bird',
                text: 'Sign up for Lead Bird with my referral link.',
                url: url,
            }).catch(function () {
                copyReferralLink().then(function (ok) {
                    if (ok) flashCopyLabel(copyBtn);
                });
            });
            return;
        }

        copyReferralLink().then(function (ok) {
            if (ok) flashCopyLabel(copyBtn);
        });
    });

    balanceToggle?.addEventListener('click', function () {
        if (!balanceValue) return;
        balanceVisible = !balanceVisible;
        balanceToggle.setAttribute('aria-pressed', balanceVisible ? 'false' : 'true');
        balanceToggle.setAttribute('aria-label', balanceVisible ? 'Hide balance' : 'Show balance');

        if (balanceVisible) {
            balanceValue.textContent = storedBalance;
            balanceValue.classList.remove('is-hidden');
        } else {
            balanceValue.textContent = '••••••';
            balanceValue.classList.add('is-hidden');
        }
    });

    function openClaimModal() {
        if (!claimOverlay) return;
        claimOverlay.hidden = false;
        claimOverlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ref-claim-open');
        if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.init === 'function') {
            window.LeadBirdDropdown.init(claimOverlay);
        }
        if (window.LeadBirdModal && typeof window.LeadBirdModal.init === 'function') {
            window.LeadBirdModal.init(claimOverlay);
        }
        closeClaimBtn?.focus();
    }

    function closeClaimModal() {
        if (!claimOverlay) return;
        claimOverlay.hidden = true;
        claimOverlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ref-claim-open');
        openClaimBtn?.focus();
    }

    openClaimBtn?.addEventListener('click', function () {
        if (openClaimBtn.disabled) return;
        openClaimModal();
    });

    closeClaimBtn?.addEventListener('click', closeClaimModal);

    claimOverlay?.addEventListener('click', function (event) {
        if (event.target === claimOverlay) {
            closeClaimModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && claimOverlay && !claimOverlay.hidden) {
            closeClaimModal();
        }
    });

    rewardToggle?.addEventListener('click', function () {
        if (!rewardHistory) return;
        var showing = !rewardHistory.hidden;
        rewardHistory.hidden = showing;
        rewardToggle.setAttribute('aria-expanded', showing ? 'false' : 'true');
        rewardToggle.textContent = showing ? 'View reward history' : 'Hide reward history';

        if (inviteList) inviteList.hidden = !showing;
        if (invitePagination) invitePagination.hidden = !showing;
        if (inviteEmpty) inviteEmpty.hidden = !showing;
    });
})();
