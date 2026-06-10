(function bootGlobalUi(windowObject, documentObject) {
  "use strict";

  const dom = windowObject.AfricoDom;
  const config = windowObject.AfricoConfig;

  function getPreferredTheme() {
    try {
      const savedTheme = windowObject.localStorage.getItem(config.themeStorageKey);

      if (savedTheme === "dark" || savedTheme === "light") {
        return savedTheme;
      }
    } catch (error) {
      return "light";
    }

    return windowObject.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
  }

  function applyTheme(theme) {
    const isDark = theme === "dark";
    documentObject.documentElement.classList.toggle("dark", isDark);
    dom.queryAll("[data-theme-label]").forEach((label) => {
      label.textContent = isDark ? "Light" : "Dark";
    });
  }

  function persistTheme(theme) {
    try {
      windowObject.localStorage.setItem(config.themeStorageKey, theme);
    } catch (error) {
      applyTheme(theme);
    }
  }

  function initThemeToggle() {
    applyTheme(getPreferredTheme());

    dom.queryAll("[data-theme-toggle]").forEach((button) => {
      dom.on(button, "click", () => {
        const nextTheme = documentObject.documentElement.classList.contains("dark") ? "light" : "dark";
        applyTheme(nextTheme);
        persistTheme(nextTheme);
      });
    });
  }

  function initMobileNavigation() {
    const toggle = dom.query("[data-nav-toggle]");
    const navigation = dom.query("[data-primary-nav]");

    dom.on(toggle, "click", () => {
      const isOpen = navigation.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", String(isOpen));
    });

    dom.queryAll("[data-primary-nav] a").forEach((link) => {
      dom.on(link, "click", () => {
        navigation.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  function initSmoothScroll() {
    dom.queryAll("[data-scroll-target]").forEach((trigger) => {
      dom.on(trigger, "click", (event) => {
        const targetId = trigger.dataset.scrollTarget;
        const target = documentObject.getElementById(targetId);

        if (!target) {
          return;
        }

        event.preventDefault();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    initThemeToggle();
    initMobileNavigation();
    initSmoothScroll();
  });
})(window, document);
