(function () {
    'use strict';

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function initCarousel(root) {
        var track = root.querySelector('[data-pricing-carousel-track]');
        var viewport = root.querySelector('[data-pricing-carousel-viewport]');
        var prevBtn = root.querySelector('[data-pricing-carousel-prev]');
        var nextBtn = root.querySelector('[data-pricing-carousel-next]');
        var dotsWrap = root.querySelector('[data-pricing-carousel-dots]');

        if (!track || !viewport) {
            return;
        }

        var slides = Array.prototype.slice.call(track.children);
        if (slides.length === 0) {
            return;
        }

        var activeIndex = 0;
        var dots = [];
        var touchStartX = 0;
        var touchDeltaX = 0;

        function isMobile() {
            return window.matchMedia('(max-width: 767px)').matches;
        }

        function goToIndex(index) {
            var safeIndex = clamp(index, 0, slides.length - 1);
            activeIndex = safeIndex;

            if (isMobile()) {
                track.style.transform = 'translateX(-' + (safeIndex * 100) + '%)';
            } else {
                track.style.transform = '';
            }

            updateUi();
        }

        function updateUi() {
            if (prevBtn) {
                prevBtn.disabled = !isMobile() || activeIndex <= 0;
            }
            if (nextBtn) {
                nextBtn.disabled = !isMobile() || activeIndex >= slides.length - 1;
            }

            dots.forEach(function (dot, index) {
                var isActive = index === activeIndex;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
                dot.tabIndex = isActive ? 0 : -1;
            });
        }

        function buildDots() {
            if (!dotsWrap) {
                return;
            }

            dotsWrap.innerHTML = '';
            dots = slides.map(function (_slide, index) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'pricing-carousel-dot' + (index === 0 ? ' is-active' : '');
                dot.setAttribute('role', 'tab');
                dot.setAttribute('aria-label', 'Plan ' + (index + 1));
                dot.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
                dot.addEventListener('click', function () {
                    goToIndex(index);
                });
                dotsWrap.appendChild(dot);
                return dot;
            });
        }

        buildDots();

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                goToIndex(activeIndex - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                goToIndex(activeIndex + 1);
            });
        }

        viewport.addEventListener('touchstart', function (event) {
            if (!isMobile() || event.touches.length !== 1) {
                return;
            }
            touchStartX = event.touches[0].clientX;
            touchDeltaX = 0;
        }, { passive: true });

        viewport.addEventListener('touchmove', function (event) {
            if (!isMobile() || event.touches.length !== 1) {
                return;
            }
            touchDeltaX = event.touches[0].clientX - touchStartX;
        }, { passive: true });

        viewport.addEventListener('touchend', function () {
            if (!isMobile()) {
                return;
            }
            if (touchDeltaX < -40) {
                goToIndex(activeIndex + 1);
            } else if (touchDeltaX > 40) {
                goToIndex(activeIndex - 1);
            }
            touchDeltaX = 0;
        }, { passive: true });

        window.addEventListener('resize', function () {
            goToIndex(activeIndex);
        });

        var popularIndex = slides.findIndex(function (slide) {
            return slide.textContent.indexOf('Popular!') !== -1;
        });
        if (popularIndex < 0) {
            popularIndex = slides.findIndex(function (slide) {
                return slide.querySelector('.pricing-tier.is-selected') !== null;
            });
        }

        window.requestAnimationFrame(function () {
            goToIndex(popularIndex > 0 && isMobile() ? popularIndex : 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-pricing-carousel]').forEach(initCarousel);
        });
    } else {
        document.querySelectorAll('[data-pricing-carousel]').forEach(initCarousel);
    }
})();
