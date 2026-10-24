/* =========================================================
   StudyBuddy — Explore carousel
   Shows a 3-card window (prev / active / next) out of all the
   decks in .deck, with wraparound. Left/right arrows rotate it.
   Safe no-op if the carousel markup is absent.
   ========================================================= */
(function () {
    "use strict";

    function init() {
        var deck = document.querySelector(".deck");
        if (!deck) return;

        var cards = Array.prototype.slice.call(deck.querySelectorAll(".card"));
        var total = cards.length;
        if (total === 0) return;

        var prevBtn = document.querySelector(".carousel-arrow--prev");
        var nextBtn = document.querySelector(".carousel-arrow--next");
        var currentEl = document.querySelector(".carousel-current");
        var totalEl = document.querySelector(".carousel-total");

        if (totalEl) totalEl.textContent = String(total);

        // Start on the second deck (index 1) so the first view shows a
        // clean prev/active/next window with no wraparound.
        var current = total > 2 ? 1 : 0;

        function mod(n, m) {
            return ((n % m) + m) % m;
        }

        function render() {
            var prevIdx = mod(current - 1, total);
            var nextIdx = mod(current + 1, total);

            for (var i = 0; i < total; i++) {
                var c = cards[i];
                c.classList.remove(
                    "card--active",
                    "card--side",
                    "card--side-prev",
                    "card--side-next",
                    "card--hidden"
                );

                if (i === current) {
                    c.classList.add("card--active");
                    c.style.order = "1";
                } else if (total > 1 && i === prevIdx) {
                    c.classList.add("card--side", "card--side-prev");
                    c.style.order = "0";
                } else if (total > 2 && i === nextIdx) {
                    c.classList.add("card--side", "card--side-next");
                    c.style.order = "2";
                } else {
                    c.classList.add("card--hidden");
                    c.style.order = "3";
                }
            }

            if (currentEl) currentEl.textContent = String(current + 1);
        }

        function go(delta) {
            current = mod(current + delta, total);
            render();
        }

        if (prevBtn) prevBtn.addEventListener("click", function () { go(-1); });
        if (nextBtn) nextBtn.addEventListener("click", function () { go(1); });

        // Keyboard arrows when the carousel area has focus within it.
        document.addEventListener("keydown", function (ev) {
            if (ev.key === "ArrowLeft") { go(-1); }
            else if (ev.key === "ArrowRight") { go(1); }
        });

        render();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
