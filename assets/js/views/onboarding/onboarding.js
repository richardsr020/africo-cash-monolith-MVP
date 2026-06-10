(function bootOnboarding(windowObject, documentObject) {
  "use strict";

  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function apiMessage(error, fallback) {
    return error.response?.data?.error?.message || error.message || fallback;
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    const form = dom.query("[data-onboarding-form]");
    const steps = dom.queryAll("[data-step]");
    const dots = dom.queryAll("[data-step-dot]");
    const previousButton = dom.query("[data-step-prev]");
    const nextButton = dom.query("[data-step-next]");
    const submitButton = dom.query("[data-step-submit]");
    let currentStep = 0;

    function showStep(index) {
      currentStep = Math.max(0, Math.min(index, steps.length - 1));
      steps.forEach((step, stepIndex) => step.classList.toggle("is-active", stepIndex === currentStep));
      dots.forEach((dot, dotIndex) => dot.classList.toggle("is-active", dotIndex <= currentStep));
      previousButton.hidden = currentStep === 0;
      nextButton.hidden = currentStep === steps.length - 1;
      submitButton.hidden = currentStep !== steps.length - 1;
    }

    function validateCurrentStep() {
      const activeStep = steps[currentStep];
      const invalid = dom.queryAll("[required]", activeStep).find((field) => !field.checkValidity());

      if (invalid) {
        invalid.setAttribute("aria-invalid", "true");
        invalid.focus();
        dom.showToast("Complétez les champs requis pour continuer.", "error");
        return false;
      }

      dom.queryAll("[aria-invalid='true']", activeStep).forEach((field) => field.removeAttribute("aria-invalid"));
      return true;
    }

    dom.on(previousButton, "click", () => showStep(currentStep - 1));
    dom.on(nextButton, "click", () => {
      if (validateCurrentStep()) {
        showStep(currentStep + 1);
      }
    });

    dom.on(form, "submit", async (event) => {
      event.preventDefault();
      if (!validateCurrentStep()) {
        return;
      }

      dom.setSubmitting(submitButton, true, "Activation...");

      try {
        const payload = dom.serializeForm(form);
        const response = await api.post("/auth/onboarding", payload);
        dom.showToast("Votre dashboard est prêt.", "success");
        windowObject.setTimeout(() => {
          windowObject.location.assign(response.data.data.next || "/dashboard");
        }, 650);
      } catch (error) {
        dom.showToast(apiMessage(error, "Configuration impossible."), "error");
      } finally {
        dom.setSubmitting(submitButton, false);
      }
    });

    showStep(0);
  });
})(window, document);
