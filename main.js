// HTDOCS/main.js
// Safe init for injected header + mobile drawer + overlay (no infinite MutationObserver)

(function () {
  let menuInitialized = false;
  let yearInitialized = false;

  function ensureOverlay() {
    let overlay = document.getElementById("menuOverlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.id = "menuOverlay";
      overlay.className = "menu-overlay";
      document.body.appendChild(overlay);
    }
    return overlay;
  }

  function initFooterYearOnce() {
    if (yearInitialized) return;
    const yearEl = document.getElementById("year");
    if (yearEl) {
      yearEl.textContent = new Date().getFullYear();
      yearInitialized = true;
    }
  }

  function initMenuOnce() {
    if (menuInitialized) return true;

    const burger = document.getElementById("burger");
    const mobileMenu = document.getElementById("mobileMenu");
    const header = document.querySelector("header.nav");

    if (!burger || !mobileMenu) return false; // header not ready yet

    const overlay = ensureOverlay();

    const closeMenu = () => {
      mobileMenu.classList.remove("open");
      overlay.classList.remove("open");
      burger.setAttribute("aria-expanded", "false");
    };

    const openMenu = () => {
      mobileMenu.classList.add("open");
      overlay.classList.add("open");
      burger.setAttribute("aria-expanded", "true");
    };

    const toggleMenu = () => {
      const willOpen = !mobileMenu.classList.contains("open");
      if (willOpen) openMenu();
      else closeMenu();
    };

    burger.addEventListener("click", (e) => {
      e.preventDefault();
      toggleMenu();
    });

    mobileMenu.querySelectorAll("a").forEach((a) => {
      a.addEventListener("click", () => closeMenu());
    });

    overlay.addEventListener("click", () => closeMenu());

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeMenu();
    });

    // outside click (safe)
    document.addEventListener("click", (e) => {
      const t = e.target;
      if (!t) return;
      if (mobileMenu.contains(t)) return;
      if (burger.contains(t)) return;
      if (header && header.contains(t)) return;
      closeMenu();
    });

    menuInitialized = true;
    return true;
  }

  function tryInitAll() {
    initFooterYearOnce();
    initMenuOnce();
  }

  // 1) initial try
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", tryInitAll);
  } else {
    tryInitAll();
  }

  // 2) Retry a few times (header is injected async)
  let tries = 0;
  const maxTries = 40; // ~4 seconds total
  const tick = () => {
    tries++;
    tryInitAll();
    if (menuInitialized || tries >= maxTries) return;
    setTimeout(tick, 100);
  };
  setTimeout(tick, 50);

  // 3) Observe ONLY #header container (not whole page), and stop quickly
  const headerHost = document.getElementById("header");
  if (headerHost && !menuInitialized) {
    const obs = new MutationObserver(() => {
      tryInitAll();
      if (menuInitialized) obs.disconnect();
    });
    obs.observe(headerHost, { childList: true, subtree: true });

    // hard stop after 6 seconds (prevents runaway)
    setTimeout(() => {
      try { obs.disconnect(); } catch (_) {}
    }, 6000);
  }
})();