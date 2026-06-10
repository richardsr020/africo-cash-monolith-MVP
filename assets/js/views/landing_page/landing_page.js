(function bootLandingPage(windowObject, documentObject) {
  "use strict";

  const dom = windowObject.AfricoDom;
  const ui = windowObject.AfricoLandingUi;
  const service = windowObject.AfricoLandingService;

  async function handleRegisterSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const submitButton = dom.query("[data-submit-label]", form);

    if (!form.checkValidity()) {
      ui.markInvalidFields(form);
      form.reportValidity();
      return;
    }

    const payload = dom.serializeForm(form);
    dom.setSubmitting(submitButton, true, "Envoi...");

    try {
      await service.requestEarlyAccess(payload);
      dom.showToast("Préinscription reçue. Continuez l’inscription complète.", "success");
      form.reset();
      dom.closeModal(dom.query("[data-register-modal]"));
      windowObject.setTimeout(() => {
        windowObject.location.assign("/inscription");
      }, 900);
    } catch (error) {
      const message = error.response?.data?.error?.message || error.message || "Impossible de finaliser la demande.";

      dom.showToast(message, "error");
    } finally {
      dom.setSubmitting(submitButton, false);
    }
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    ui.init({
      onRegisterSubmit: handleRegisterSubmit,
    });
  });
})(window, document);
