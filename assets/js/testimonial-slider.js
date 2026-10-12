(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        var track = slider.querySelector('.ts-track');
        var dots = slider.querySelectorAll('.ts-dots button');
        var count = dots.length;
        var current = 0;
        var timer;

        if (!track || count < 2) {
            return;
        }

        function go(index) {
            current = (index + count) % count;
            track.style.transform = 'translateX(' + (-100 * current) + '%)';
            dots.forEach(function (d, i) { d.classList.toggle('on', i === current); });
        }

        function schedule() {
            if (reduceMotion) {
                return;
            }
            clearInterval(timer);
            timer = setInterval(function () { go(current + 1); }, 5000);
        }

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { go(i); schedule(); });
        });
        schedule();
    });
})();
