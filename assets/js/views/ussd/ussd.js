(function bootUssd(windowObject, documentObject) {
  "use strict";

  const api = windowObject.AfricoApi;
  const dom = windowObject.AfricoDom;

  const state = {
    session: "dial",
    input: "",
    screen: "Composez *400#\npuis appuyez sur Appeler.",
    busy: false,
  };

  const labels = {
    dial: "Composition",
    menu: "Menu",
    send_recipient: "Destinataire",
    send_amount: "Envoi",
    send_confirm: "Confirmation",
    withdraw_amount: "Retrait",
    withdraw_confirm: "Confirmation",
  };

  function render() {
    const screen = dom.query("[data-ussd-screen]");
    const entry = dom.query("[data-ussd-entry]");
    const status = dom.query("[data-ussd-state]");

    if (screen) {
      screen.textContent = state.busy ? "Connexion au service Africo Cash..." : state.screen;
    }

    if (entry) {
      entry.textContent = state.input || " ";
    }

    if (status) {
      status.textContent = labels[state.session] || "Session";
    }
  }

  async function sendInput() {
    if (state.busy) {
      return;
    }

    state.busy = true;
    render();

    try {
      const response = await api.post("/ussd/session", {
        state: state.session,
        input: state.input,
      });

      state.session = response.data.state || "dial";
      state.screen = response.data.screen || "Session USSD.";
      state.input = "";
      dom.showToast("Réponse USSD reçue.", "success");
    } catch (error) {
      state.screen = "Service USSD indisponible.\nRéessayez dans un instant.";
      dom.showToast("Impossible de joindre l’API USSD.", "error");
    } finally {
      state.busy = false;
      render();
    }
  }

  function endSession() {
    state.session = "dial";
    state.input = "";
    state.screen = "Session terminée.\nComposez *400# pour recommencer.";
    render();
  }

  function bindKeys() {
    dom.queryAll("[data-ussd-key]").forEach((button) => {
      dom.on(button, "click", () => {
        if (state.busy) {
          return;
        }

        state.input = `${state.input}${button.dataset.ussdKey}`.slice(0, 18);
        render();
      });
    });

    dom.queryAll("[data-ussd-action]").forEach((button) => {
      dom.on(button, "click", () => {
        const action = button.dataset.ussdAction;

        if (action === "call") {
          sendInput();
        } else if (action === "clear") {
          state.input = state.input.slice(0, -1);
          render();
        } else if (action === "end") {
          endSession();
        }
      });
    });
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    bindKeys();
    render();
  });
})(window, document);
