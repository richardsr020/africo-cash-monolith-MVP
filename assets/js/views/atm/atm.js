(function bootAtm(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  documentObject.addEventListener("DOMContentLoaded", () => {
    dom.on(dom.query("[data-atm-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const target = dom.query("[data-atm-code]");
      const button = dom.query("button[type='submit']", form);
      dom.setSubmitting(button, true, "Génération...");
      try {
        const response = await api.post("/app/atm/code", dom.serializeForm(form));
        if (target) {
          target.textContent = response.data.data.code;
        }
        dom.showToast("Code ATM généré pour 10 minutes.", "success");
      } catch (error) {
        dom.showToast(error.response?.data?.error?.message || "Code ATM impossible.", "error");
      } finally {
        dom.setSubmitting(button, false);
      }
    });
  });
})(window, document);
