(function bootRegister(windowObject, documentObject) {
  "use strict";

  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;
  const auth = windowObject.AfricoAuth;

  function apiMessage(error, fallback) {
    return error.response?.data?.error?.message || error.message || fallback;
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    dom.on(dom.query("[data-register-page-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const password = form.elements.password;
      const confirmation = form.elements.password_confirmation;
      const button = dom.query("button[type='submit']", form);

      if (password && confirmation && password.value !== confirmation.value) {
        confirmation.setAttribute("aria-invalid", "true");
        dom.showToast("Les mots de passe ne correspondent pas.", "error");
        return;
      }

      confirmation?.removeAttribute("aria-invalid");
      dom.setSubmitting(button, true, "Création...");

      try {
        const payload = dom.serializeForm(form);
        const registerResponse = await api.post("/auth/register", {
          email: payload.email,
          password: payload.password,
        });
        const { user, access_token: accessToken } = registerResponse.data.data;

        auth.setToken(accessToken);
        dom.showToast(`Compte créé. Numéro Africo : ${user.africo_number}`, "success");
        windowObject.setTimeout(() => {
          windowObject.location.assign(registerResponse.data.data.next || "/onboarding");
        }, 900);
      } catch (error) {
        dom.showToast(apiMessage(error, "Création de compte impossible."), "error");
      } finally {
        dom.setSubmitting(button, false);
      }
    });
  });
})(window, document);
