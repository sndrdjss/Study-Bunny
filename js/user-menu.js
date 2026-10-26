/* =========================================================
   StudyBuddy — navbar account dropdown (vanilla, no deps)
   - Click button toggles menu
   - Escape closes and returns focus to the button
   - Click outside / Tab out closes
   - ArrowDown on button opens + focuses first item
   - ArrowUp / ArrowDown / Home / End move between items
   ========================================================= */
(function () {
  "use strict";

  function init(root) {
    var btn = root.querySelector(".user-menu__btn");
    var list = root.querySelector(".user-menu__list");
    if (!btn || !list) return;

    function items() {
      return Array.prototype.slice.call(list.querySelectorAll('[role="menuitem"]'));
    }

    function isOpen() {
      return !list.hasAttribute("hidden");
    }

    function open(focusIndex) {
      list.removeAttribute("hidden");
      btn.setAttribute("aria-expanded", "true");
      if (typeof focusIndex === "number") {
        var all = items();
        if (all.length) {
          var i = focusIndex < 0 ? all.length - 1 : Math.min(focusIndex, all.length - 1);
          all[i].focus();
        }
      }
    }

    function close(returnFocus) {
      if (!isOpen()) return;
      list.setAttribute("hidden", "");
      btn.setAttribute("aria-expanded", "false");
      if (returnFocus) btn.focus();
    }

    btn.addEventListener("click", function () {
      if (isOpen()) close(false);
      else open();
    });

    btn.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown") {
        e.preventDefault();
        open(0);
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        open(-1);
      } else if (e.key === "Escape") {
        close(true);
      }
    });

    list.addEventListener("keydown", function (e) {
      var all = items();
      var idx = all.indexOf(document.activeElement);
      switch (e.key) {
        case "ArrowDown":
          e.preventDefault();
          all[(idx + 1) % all.length].focus();
          break;
        case "ArrowUp":
          e.preventDefault();
          all[(idx - 1 + all.length) % all.length].focus();
          break;
        case "Home":
          e.preventDefault();
          all[0].focus();
          break;
        case "End":
          e.preventDefault();
          all[all.length - 1].focus();
          break;
        case "Escape":
          e.preventDefault();
          close(true);
          break;
        case "Tab":
          close(false);
          break;
      }
    });

    // Close when focus leaves the whole menu (e.g. Tab away from button).
    root.addEventListener("focusout", function (e) {
      if (e.relatedTarget && !root.contains(e.relatedTarget)) close(false);
    });

    document.addEventListener("click", function (e) {
      if (!root.contains(e.target)) close(false);
    });
  }

  function boot() {
    var menus = document.querySelectorAll(".user-menu");
    for (var i = 0; i < menus.length; i++) init(menus[i]);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
