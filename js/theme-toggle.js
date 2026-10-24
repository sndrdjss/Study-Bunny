/* =========================================================
   StudyBuddy — Theme toggle (light/dark) with persistence
   Key: localStorage['studybuddy-theme'] = 'light' | 'dark'
   Dark is the default when nothing is saved.

   NOTE: The *early* application of the saved theme (to avoid a
   flash of the wrong theme before paint) is done by a tiny inline
   snippet in each page's <head>. This file handles wiring the
   switch and syncing its state once the DOM is ready.
   ========================================================= */
(function () {
    "use strict";

    var STORAGE_KEY = "studybuddy-theme";

    function getSavedTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function saveTheme(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {
            /* storage may be unavailable (private mode) — ignore */
        }
    }

    /** Current theme = 'light' if the attribute is set, else 'dark'. */
    function currentTheme() {
        return document.documentElement.getAttribute("data-theme") === "light"
            ? "light"
            : "dark";
    }

    /** Apply a theme to <html> (no-op guards keep it safe everywhere). */
    function applyTheme(theme) {
        if (theme === "light") {
            document.documentElement.setAttribute("data-theme", "light");
        } else {
            document.documentElement.removeAttribute("data-theme");
        }
    }

    /** Sync every switch on the page to the active theme.
        Switch is CHECKED when theme is DARK (knob on the right). */
    function syncSwitches(theme) {
        var inputs = document.querySelectorAll(".theme-switch input");
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].checked = theme === "dark";
        }
    }

    function init() {
        // Ensure the live theme reflects what was saved (early inline
        // snippet usually did this already; this is a safe backstop).
        var saved = getSavedTheme();
        if (saved === "light" || saved === "dark") {
            applyTheme(saved);
        }

        var theme = currentTheme();
        syncSwitches(theme);

        var switches = document.querySelectorAll(".theme-switch input");
        for (var i = 0; i < switches.length; i++) {
            switches[i].addEventListener("change", function (ev) {
                // checked = dark, unchecked = light
                var next = ev.target.checked ? "dark" : "light";
                applyTheme(next);
                saveTheme(next);
                syncSwitches(next);
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
