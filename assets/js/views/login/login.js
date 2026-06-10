(function bootLogin(windowObject, documentObject) {
  "use strict";

  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;
  const auth = windowObject.AfricoAuth;

  function apiMessage(error, fallback) {
    return error.response?.data?.error?.message || error.message || fallback;
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    dom.on(dom.query("[data-login-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const button = dom.query("button[type='submit']", form);
      const payload = dom.serializeForm(form);

      dom.setSubmitting(button, true, "Connexion...");

      try {
        const response = await api.post("/auth/login", {
          email: payload.email,
          password: payload.password,
        });
        auth.setToken(response.data.data.access_token, Boolean(payload.remember));
        dom.showToast("Connexion réussie.", "success");
        const redirectTo = new URLSearchParams(windowObject.location.search).get("return");
        const next = response.data.data.next || "/dashboard";
        windowObject.location.assign(redirectTo && redirectTo.startsWith("/") ? redirectTo : next);
      } catch (error) {
        dom.showToast(apiMessage(error, "Connexion impossible."), "error");
      } finally {
        dom.setSubmitting(button, false);
      }
    });
  });
})(window, document);
