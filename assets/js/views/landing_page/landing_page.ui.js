(function exposeLandingUi(windowObject, documentObject) {
  "use strict";

  const dom = windowObject.AfricoDom;

  function initFeatureCards() {
    dom.queryAll("[data-feature-card]").forEach((card) => {
      dom.on(card, "click", () => {
        const title = card.dataset.featureCard;
        dom.showToast(`${title} disponible dans votre espace Africo Cash.`);
      });
    });
  }

  function initFooterActions() {
    const messages = {
      about: "Africo Cash connecte banques, Mobile Money, agents et services ATM.",
      legal: "Mentions légales Africo Cash.",
      agent: "Notre équipe partenaire vous recontactera rapidement.",
    };

    dom.queryAll("[data-footer-action]").forEach((button) => {
      dom.on(button, "click", () => {
        dom.showToast(messages[button.dataset.footerAction] || "Action en préparation.");
      });
    });
  }

  function bindModalClose(modal) {
    dom.queryAll("[data-close-modal]", modal).forEach((button) => {
      dom.on(button, "click", () => dom.closeModal(modal));
    });

    dom.on(modal, "click", (event) => {
      if (event.target === modal) {
        dom.closeModal(modal);
      }
    });
  }

  function initModals() {
    const registerModal = dom.query("[data-register-modal]");
    const contactModal = dom.query("[data-contact-modal]");

    dom.queryAll("[data-register-trigger]").forEach((button) => {
      dom.on(button, "click", () => dom.openModal(registerModal));
    });

    dom.queryAll("[data-open-contact]").forEach((button) => {
      dom.on(button, "click", (event) => {
        event.preventDefault();
        dom.openModal(contactModal);
      });
    });

    bindModalClose(registerModal);
    bindModalClose(contactModal);
  }

  function markInvalidFields(form) {
    dom.queryAll("[required]", form).forEach((field) => {
      field.setAttribute("aria-invalid", String(!field.checkValidity()));
    });
  }

  function clearInvalidState(form) {
    dom.queryAll("[aria-invalid]", form).forEach((field) => {
      field.removeAttribute("aria-invalid");
    });
  }

  function init(callbacks) {
    initModals();
    initFeatureCards();
    initFooterActions();

    const form = dom.query("[data-register-form]");

    dom.on(form, "input", () => clearInvalidState(form));
    dom.on(form, "submit", callbacks.onRegisterSubmit);
  }

  windowObject.AfricoLandingUi = Object.freeze({
    init,
    markInvalidFields,
  });
})(window, document);
