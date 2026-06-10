(function bootBanking(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;
  documentObject.addEventListener("DOMContentLoaded", () => {
    dom.on(dom.query("[data-banking-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const button = dom.query("button[type='submit']", form);
      dom.setSubmitting(button, true, "Vérification...");
      try {
        const response = await api.post("/app/banking/transfer", dom.serializeForm(form));
        dom.showToast(`Virement ${response.data.data.transaction.reference} exécuté.`, "success");
        form.reset();
      } catch (error) {
        dom.showToast(error.response?.data?.error?.message || "Virement impossible.", "error");
      } finally {
        dom.setSubmitting(button, false);
      }
    });
  });
})(window, document);
