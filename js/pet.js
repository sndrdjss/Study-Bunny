/* =========================================================
   StudyBuddy — egg & pet interactions
   - Welcome dialog: select an egg, then confirm (focus trapped,
     Escape ignored because a choice is required).
   - Hatch scene: confetti burst, focus the "Yay!" button,
     close back to the hero where the pet does a little cheer.
   Everything degrades: without JS the forms/links still work.
   ========================================================= */
(function () {
    "use strict";

    var reduceMotion = window.matchMedia &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /* Keep Tab focus inside a dialog. */
    function trapFocus(root, onEscape) {
        root.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                e.preventDefault();
                if (onEscape) onEscape();
                return;
            }
            if (e.key !== "Tab") return;
            var nodes = root.querySelectorAll('button:not([disabled]), a[href], input:not([type="hidden"]):not([disabled])');
            var focusable = Array.prototype.filter.call(nodes, function (el) {
                return el.offsetParent !== null;
            });
            if (!focusable.length) return;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        });
    }

    /* Drop one-shot query flags (?egg=chosen, ?hatched=1) so a refresh doesn't replay. */
    function cleanUrl() {
        if (!window.history || !window.history.replaceState) return;
        var url = new URL(window.location.href);
        url.searchParams.delete("egg");
        url.searchParams.delete("hatched");
        window.history.replaceState(null, "", url.pathname + url.search + url.hash);
    }

    /* ---------------- Welcome dialog ---------------- */
    var welcome = document.getElementById("egg-welcome");
    if (welcome) {
        document.body.classList.add("pet-modal-open");

        var list = welcome.querySelector(".egg-choices");
        var choices = welcome.querySelectorAll(".egg-choice");
        var actions = welcome.querySelector(".egg-welcome-actions");
        var confirmBtn = welcome.querySelector(".btn-egg-confirm");
        var confirmName = welcome.querySelector(".egg-confirm-name");

        // Switch from "click = pick" to "select, then confirm".
        actions.hidden = false;
        confirmBtn.disabled = true;

        Array.prototype.forEach.call(choices, function (btn) {
            btn.setAttribute("aria-pressed", "false");
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                Array.prototype.forEach.call(choices, function (b) {
                    b.setAttribute("aria-pressed", b === btn ? "true" : "false");
                });
                list.classList.add("has-pick");
                confirmBtn.value = btn.value;
                confirmBtn.disabled = false;
                confirmName.textContent = btn.getAttribute("data-name");
            });
        });

        trapFocus(welcome, null);
        if (choices.length) choices[0].focus();
    }

    /* ---------------- Hatch scene ---------------- */
    var hatch = document.getElementById("hatch-overlay");
    if (hatch) {
        document.body.classList.add("pet-modal-open");
        var yay = hatch.querySelector(".btn-yay");
        var confetti = hatch.querySelector(".hatch-confetti");

        if (confetti && !reduceMotion) {
            var colors = ["#FFD65C", "#FF86B3", "#7BE08A", "#6EC6FF", "#FF9838", "#C9A7FF"];
            for (var i = 0; i < 36; i++) {
                var piece = document.createElement("i");
                var angle = Math.random() * Math.PI * 2;
                var dist = 140 + Math.random() * 180;
                piece.style.setProperty("--c", colors[i % colors.length]);
                piece.style.setProperty("--d", (1.8 + Math.random() * 0.2).toFixed(2) + "s");
                piece.style.setProperty("--x", Math.round(Math.cos(angle) * dist) + "px");
                piece.style.setProperty("--y", Math.round(Math.sin(angle) * dist - 60) + "px");
                piece.style.setProperty("--r", Math.round(Math.random() * 720 - 360) + "deg");
                if (i % 5 === 0) {
                    piece.className = "is-star";
                    piece.textContent = "\u2605";
                }
                confetti.appendChild(piece);
            }
        }

        var closed = false;
        function closeHatch() {
            if (closed) return;
            closed = true;
            hatch.classList.add("is-closing");
            setTimeout(function () {
                hatch.remove();
                document.body.classList.remove("pet-modal-open");
                var holder = document.querySelector("#pet-stage .pet-holder");
                if (holder && !reduceMotion) holder.classList.add("pet-holder--cheer");
                var stage = document.getElementById("pet-stage");
                if (stage) stage.scrollIntoView({ block: "center", behavior: reduceMotion ? "auto" : "smooth" });
            }, reduceMotion ? 0 : 350);
        }

        if (yay) {
            yay.addEventListener("click", function (e) {
                e.preventDefault();
                closeHatch();
            });
            // Focus once the banner is visible (instantly with reduced motion).
            setTimeout(function () { yay.focus(); }, reduceMotion ? 0 : 2600);
        }
        trapFocus(hatch, closeHatch);
        cleanUrl();
    }

    /* Egg just chosen: the CSS arrive animation plays; tidy the URL. */
    if (document.querySelector(".pet-holder--arrive")) {
        cleanUrl();
    }
})();
