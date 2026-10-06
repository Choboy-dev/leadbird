(function () {
    var page = document.querySelector('.mg-page');
    if (!page) return;

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!prefersReducedMotion && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px 0px -20px 0px',
        });

        page.querySelectorAll('[data-reveal]').forEach(function (el, index) {
            el.style.transitionDelay = Math.min(index * 0.04, 0.28) + 's';
            observer.observe(el);
        });
    } else {
        page.querySelectorAll('[data-reveal]').forEach(function (el) {
            el.classList.add('is-visible');
        });
    }
})();
