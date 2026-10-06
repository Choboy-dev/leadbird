(function () {
    var page = document.querySelector('[data-help-page]');
    if (!page) return;

    var accordion = page.querySelector('[data-help-accordion]');
    if (accordion) {
        accordion.querySelectorAll('[data-help-accordion-item]').forEach(function (item) {
            var trigger = item.querySelector('.hc-accordion-trigger');
            var panel = item.querySelector('.hc-accordion-panel');
            if (!trigger || !panel) return;

            trigger.addEventListener('click', function () {
                var isOpen = item.classList.contains('is-open');

                accordion.querySelectorAll('[data-help-accordion-item]').forEach(function (other) {
                    other.classList.remove('is-open');
                    var otherTrigger = other.querySelector('.hc-accordion-trigger');
                    var otherPanel = other.querySelector('.hc-accordion-panel');
                    if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
                    if (otherPanel) otherPanel.hidden = true;
                });

                if (!isOpen) {
                    item.classList.add('is-open');
                    trigger.setAttribute('aria-expanded', 'true');
                    panel.hidden = false;
                }
            });
        });
    }

    var recommended = page.querySelector('[data-help-recommended]');
    var showMoreBtn = page.querySelector('[data-help-show-more]');
    if (recommended && showMoreBtn) {
        var items = recommended.querySelectorAll('.hc-row-link');
        var limit = 5;
        if (items.length > limit) {
            showMoreBtn.hidden = false;
            items.forEach(function (li, index) {
                if (index >= limit) li.hidden = true;
            });
            showMoreBtn.addEventListener('click', function () {
                items.forEach(function (li) {
                    li.hidden = false;
                });
                showMoreBtn.hidden = true;
            });
        }
    }

    var searchInput = page.querySelector('[data-help-search]');
    if (searchInput) {
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                var form = searchInput.closest('form');
                if (form) form.submit();
            }
        });
    }
})();
