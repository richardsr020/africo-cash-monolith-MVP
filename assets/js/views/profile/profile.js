(function bootProfile(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  documentObject.addEventListener("DOMContentLoaded", async () => {
    try {
      const response = await api.get("/app/profile");
      const user = response.data.data.user;
      dom.query("[data-profile-full-name]").value = user.full_name;
      dom.query("[data-profile-email]").value = user.email;
      dom.query("[data-profile-phone]").value = user.notification_phone;
      dom.query("[data-profile-city]").value = user.onboarding.city || "";
      dom.query("[data-profile-kyc]").textContent = user.is_verified ? "Identité vérifiée" : "Identité en cours";
    } catch (error) {
      dom.showToast("Profil indisponible.", "error");
    }

    dom.on(dom.query("[data-profile-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const button = dom.query("button[type='submit']", form);
      dom.setSubmitting(button, true, "Enregistrement...");
      try {
        await api.post("/app/profile", dom.serializeForm(form));
        dom.showToast("Profil enregistré.", "success");
      } catch (error) {
        dom.showToast("Enregistrement impossible.", "error");
      } finally {
        dom.setSubmitting(button, false);
      }
    });
  });
})(window, document);
