(function bootDab(windowObject, documentObject) {
  "use strict";

  const api = windowObject.AfricoApi;
  const dom = windowObject.AfricoDom;

  const state = {
    step: "code",
    code: "",
    amount: "",
    currency: "CDF",
    pin: "",
    status: "En attente",
    busy: false,
    result: "",
  };

  function money(value) {
    return `${Number(value || 0).toLocaleString("fr-FR")} ${state.currency}`;
  }

  function currentBuffer() {
    if (state.step === "code") {
      return state.code;
    }

    if (state.step === "amount") {
      return state.amount;
    }

    return state.pin;
  }

  function setCurrentBuffer(value) {
    if (state.step === "code") {
      state.code = value.slice(0, 6);
      return;
    }

    if (state.step === "amount") {
      state.amount = value.slice(0, 8);
      return;
    }

    state.pin = value.slice(0, 4);
  }

  function screenText() {
    if (state.busy) {
      return "<strong>Traitement...</strong>Connexion API Africo Cash\nValidation du retrait en cours.";
    }

    if (state.result) {
      return state.result;
    }

    if (state.step === "code") {
      return `<strong>Code de retrait</strong>${state.code.padEnd(6, "-")}\nEntrez le code à 6 chiffres.`;
    }

    if (state.step === "amount") {
      return `<strong>Montant</strong>${money(state.amount)}\nChoisissez un montant ou utilisez le clavier.`;
    }

    return `<strong>PIN client</strong>${"*".repeat(state.pin.length).padEnd(4, "-")}\nValidez avec OK.`;
  }

  function render() {
    const screen = dom.query("[data-dab-screen]");
    const codeSummary = dom.query('[data-dab-summary="code"]');
    const amountSummary = dom.query('[data-dab-summary="amount"]');
    const statusSummary = dom.query('[data-dab-summary="status"]');

    if (screen) {
      screen.innerHTML = screenText();
    }

    if (codeSummary) {
      codeSummary.textContent = state.code || "------";
    }

    if (amountSummary) {
      amountSummary.textContent = money(state.amount);
    }

    if (statusSummary) {
      statusSummary.textContent = state.status;
    }
  }

  function reset() {
    state.step = "code";
    state.code = "";
    state.amount = "";
    state.pin = "";
    state.status = "En attente";
    state.busy = false;
    state.result = "";
    render();
  }

  async function submitWithdrawal() {
    state.busy = true;
    state.status = "Validation";
    render();

    try {
      const response = await api.post("/dab/withdraw", {
        code: state.code,
        amount: Number(state.amount),
        currency: state.currency,
        pin: state.pin,
      });
      const transaction = response.data.transaction;
      state.status = "Approuvé";
      state.result = `<strong>Retrait approuvé</strong>${response.data.message}\nRéf: ${transaction.reference}\nSolde: ${money(transaction.remaining_balance)}`;
      dom.showToast("Retrait DAB approuvé.", "success");
    } catch (error) {
      const message = error.response?.data?.message || "Retrait refusé par l’API.";
      state.status = "Refusé";
      state.result = `<strong>Retrait refusé</strong>${message}\nAppuyez sur Nouvelle opération.`;
      dom.showToast(message, "error");
    } finally {
      state.busy = false;
      render();
    }
  }

  function confirmStep() {
    if (state.busy || state.result) {
      return;
    }

    if (state.step === "code" && state.code.length === 6) {
      state.step = "amount";
      state.status = "Code saisi";
    } else if (state.step === "amount" && Number(state.amount) > 0) {
      state.step = "pin";
      state.status = "Montant saisi";
    } else if (state.step === "pin" && state.pin.length === 4) {
      submitWithdrawal();
      return;
    } else {
      dom.showToast("Information incomplète.", "error");
    }

    render();
  }

  function bindKeypad() {
    dom.queryAll("[data-dab-key]").forEach((button) => {
      dom.on(button, "click", () => {
        if (state.busy || state.result) {
          return;
        }

        setCurrentBuffer(`${currentBuffer()}${button.dataset.dabKey}`);
        render();
      });
    });

    dom.queryAll("[data-dab-action]").forEach((button) => {
      dom.on(button, "click", () => {
        const action = button.dataset.dabAction;

        if (action === "ok") {
          confirmStep();
        } else if (action === "clear") {
          setCurrentBuffer("");
          render();
        } else if (action === "reset") {
          reset();
        }
      });
    });
  }

  function bindAmounts() {
    dom.queryAll("[data-dab-amount]").forEach((button) => {
      dom.on(button, "click", () => {
        if (state.step !== "amount" || state.busy || state.result) {
          return;
        }

        state.amount = button.dataset.dabAmount || "";
        render();
      });
    });

    dom.on(dom.query("[data-dab-currency]"), "change", (event) => {
      state.currency = event.currentTarget.value;
      render();
    });
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    bindKeypad();
    bindAmounts();
    render();
  });
})(window, document);
