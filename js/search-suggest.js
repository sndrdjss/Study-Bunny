/* =========================================================
   StudyBuddy — Live search suggestions
   Fetches matches from suggest.php and renders a dropdown
   under the search bar. Keyboard + mouse accessible.
   ========================================================= */
(function () {
  "use strict";

  var input = document.getElementById("search-input");
  var list = document.getElementById("search-suggestions");
  if (!input || !list) return;

  var DEBOUNCE_MS = 200;
  var debounceTimer = null;
  var latestRequest = 0; // guards against out-of-order responses
  var items = []; // current suggestion data
  var activeIndex = -1; // highlighted item for keyboard nav

  function setExpanded(expanded) {
    input.setAttribute("aria-expanded", expanded ? "true" : "false");
  }

  function hideList() {
    list.hidden = true;
    list.innerHTML = "";
    items = [];
    activeIndex = -1;
    setExpanded(false);
  }

  function render(data) {
    list.innerHTML = "";
    items = data || [];
    activeIndex = -1;

    if (!items.length) {
      hideList();
      return;
    }

    items.forEach(function (item, index) {
      var li = document.createElement("li");
      li.className = "suggestion suggestion--" + (item.type || "deck");
      li.setAttribute("role", "option");
      li.setAttribute("id", "suggestion-" + index);
      li.dataset.index = String(index);

      var badge = document.createElement("span");
      badge.className = "suggestion-type";
      badge.textContent = item.type === "category" ? "Category" : "Deck";

      var label = document.createElement("span");
      label.className = "suggestion-label";
      label.textContent = item.label; // textContent => no HTML injection

      li.appendChild(badge);
      li.appendChild(label);

      // Use mousedown so it fires before the input's blur hides the list.
      li.addEventListener("mousedown", function (e) {
        e.preventDefault();
        go(item);
      });
      li.addEventListener("mouseenter", function () {
        setActive(index);
      });

      list.appendChild(li);
    });

    list.hidden = false;
    setExpanded(true);
  }

  function setActive(index) {
    var nodes = list.querySelectorAll(".suggestion");
    nodes.forEach(function (n) {
      n.classList.remove("is-active");
      n.setAttribute("aria-selected", "false");
    });
    activeIndex = index;
    if (index >= 0 && index < nodes.length) {
      nodes[index].classList.add("is-active");
      nodes[index].setAttribute("aria-selected", "true");
      input.setAttribute("aria-activedescendant", "suggestion-" + index);
    } else {
      input.removeAttribute("aria-activedescendant");
    }
  }

  function go(item) {
    if (item && item.url) {
      window.location.href = item.url;
    }
  }

  function fetchSuggestions(value) {
    var requestId = ++latestRequest;
    fetch("suggest.php?q=" + encodeURIComponent(value), {
      headers: { Accept: "application/json" },
    })
      .then(function (res) {
        return res.ok ? res.json() : [];
      })
      .then(function (data) {
        // Ignore stale responses that arrived out of order.
        if (requestId !== latestRequest) return;
        render(data);
      })
      .catch(function () {
        if (requestId !== latestRequest) return;
        hideList();
      });
  }

  input.addEventListener("input", function () {
    var value = input.value.trim();
    if (debounceTimer) clearTimeout(debounceTimer);

    if (value === "") {
      latestRequest++; // invalidate any in-flight request
      hideList();
      return;
    }

    debounceTimer = setTimeout(function () {
      fetchSuggestions(value);
    }, DEBOUNCE_MS);
  });

  input.addEventListener("keydown", function (e) {
    if (list.hidden || !items.length) return;

    switch (e.key) {
      case "ArrowDown":
        e.preventDefault();
        setActive((activeIndex + 1) % items.length);
        break;
      case "ArrowUp":
        e.preventDefault();
        setActive((activeIndex - 1 + items.length) % items.length);
        break;
      case "Enter":
        if (activeIndex >= 0 && activeIndex < items.length) {
          e.preventDefault();
          go(items[activeIndex]);
        }
        // otherwise let the form submit to search.php normally
        break;
      case "Escape":
        hideList();
        break;
    }
  });

  // Hide on blur, with a short delay so a click/mousedown can register.
  input.addEventListener("blur", function () {
    setTimeout(hideList, 120);
  });
})();
