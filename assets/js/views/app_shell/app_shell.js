(function bootAppShell(windowObject, documentObject) {
  "use strict";

  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;
  const auth = windowObject.AfricoAuth;

  function loginPath() {
    return `/connexion?return=${encodeURIComponent(windowObject.location.pathname)}`;
  }

  function requireToken() {
    if (!auth.getToken()) {
      windowObject.location.replace(loginPath());
      return false;
    }

    return true;
  }

  function apiMessage(error, fallback) {
    return error.response?.data?.error?.message || error.message || fallback;
  }

  async function syncSession(showSuccess = false) {
    if (!requireToken()) {
      return;
    }

    try {
      const response = await api.get("/me");
      const user = response.data.data.user;
      const label = dom.query("[data-current-user]");

      if (label && user) {
        label.textContent = `${user.full_name} · ${user.africo_number}`;
      }

      if (showSuccess) {
        dom.showToast("Données actualisées.", "success");
      }
    } catch (error) {
      auth.clearToken();
      dom.showToast(apiMessage(error, "Session expirée."), "error");
      windowObject.setTimeout(() => {
        windowObject.location.replace(loginPath());
      }, 600);
    }
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    syncSession();

    dom.on(dom.query("[data-sync-view]"), "click", () => {
      syncSession(true);
    });

    dom.on(dom.query("[data-logout]"), "click", async () => {
      try {
        await api.post("/auth/logout");
        dom.showToast("Session fermée.", "success");
      } catch (error) {
        dom.showToast(apiMessage(error, "Session locale fermée."), "info");
      } finally {
        auth.clearToken();
        windowObject.setTimeout(() => {
          windowObject.location.assign("/connexion");
        }, 300);
      }
    });

    dom.queryAll("[data-api-action]").forEach((button) => {
      dom.on(button, "click", () => {
        dom.showToast("Action ouverte dans votre espace Africo Cash.", "success");
      });
    });
  });
})(window, document);
