(function exposeDomHelpers(windowObject, documentObject) {
  "use strict";

  let toastTimer = null;

  function query(selector, scope = documentObject) {
    if (!scope) {
      return null;
    }

    return scope.querySelector(selector);
  }

  function queryAll(selector, scope = documentObject) {
    if (!scope) {
      return [];
    }

    return Array.from(scope.querySelectorAll(selector));
  }

  function on(target, eventName, callback, options) {
    if (!target) {
      return () => {};
    }

    target.addEventListener(eventName, callback, options);

    return () => target.removeEventListener(eventName, callback, options);
  }

  function showToast(message, type = "info") {
    const toast = query("[data-toast]");

    if (!toast) {
      return;
    }

    windowObject.clearTimeout(toastTimer);
    toast.textContent = message;
    toast.dataset.type = type;
    toast.classList.add("is-visible");
    toastTimer = windowObject.setTimeout(() => {
      toast.classList.remove("is-visible");
    }, 3200);
  }

  function openModal(modal) {
    if (!modal) {
      return;
    }

    if (typeof modal.showModal === "function") {
      modal.showModal();
    } else {
      modal.setAttribute("open", "");
    }

    documentObject.body.classList.add("is-locked");
  }

  function closeModal(modal) {
    if (!modal) {
      return;
    }

    if (typeof modal.close === "function" && modal.open) {
      modal.close();
    } else {
      modal.removeAttribute("open");
    }

    documentObject.body.classList.remove("is-locked");
  }

  function serializeForm(form) {
    const formData = new FormData(form);
    const payload = {};

    formData.forEach((value, key) => {
      payload[key] = typeof value === "string" ? value.trim() : value;
    });

    return payload;
  }

  function setSubmitting(button, isSubmitting, loadingLabel = "Traitement...") {
    if (!button) {
      return;
    }

    if (!button.dataset.defaultLabel) {
      button.dataset.defaultLabel = button.textContent.trim();
    }

    button.disabled = isSubmitting;
    button.textContent = isSubmitting ? loadingLabel : button.dataset.defaultLabel;
  }

  windowObject.AfricoDom = Object.freeze({
    query,
    queryAll,
    on,
    showToast,
    openModal,
    closeModal,
    serializeForm,
    setSubmitting,
  });
})(window, document);
