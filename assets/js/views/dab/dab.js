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
    phase: null,
    reference: "",
  };

  function money(value) {
    return `${Number(value || 0).toLocaleString("fr-FR")} ${state.currency}`;
  }

  function moneyCents(cents) {
    return `${(Number(cents || 0) / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 })} ${state.currency}`;
  }

  function currentBuffer() {
    if (state.step === "code") return state.code;
    if (state.step === "amount") return state.amount;
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
    if (state.busy && state.phase === "dispense") {
      return "<strong>Distribution des billets</strong>\nVeuillez patienter...\n\n<i class='fa-solid fa-money-bill-wave fa-fade' style='font-size:1.6rem;display:block;text-align:center;margin-top:0.5rem'></i>";
    }

    if (state.busy && state.phase === "authorize") {
      return "<strong>Traitement...</strong>Connexion API Africo Cash\nValidation du retrait en cours.";
    }

    if (state.busy && state.phase === "confirm") {
      return "<strong>Finalisation...</strong>Mise à jour du solde en cours.";
    }

    if (state.result) return state.result;

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
    const slots = dom.query(".dab-slots");

    if (screen) screen.innerHTML = screenText();

    if (codeSummary) codeSummary.textContent = state.code || "------";
    if (amountSummary) amountSummary.textContent = money(state.amount);
    if (statusSummary) statusSummary.textContent = state.status;

    if (slots) {
      slots.classList.toggle("is-dispensing", state.phase === "dispense");
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
    state.phase = null;
    state.reference = "";
    render();
  }

  async function phaseAuthorize() {
    state.busy = true;
    state.phase = "authorize";
    state.status = "Validation";
    render();

    try {
      const response = await api.post("/app/dab/authorize", {
        code: state.code,
        amount: Number(state.amount),
        currency: state.currency,
        pin: state.pin,
      });
      const result = response.data;
      state.reference = result.data.reference;
      state.status = "Autorisé";
      dom.showToast("Retrait autorisé, distribution des billets...", "success");

      state.phase = "dispense";
      render();

      await new Promise((resolve) => setTimeout(resolve, 3000));

      await phaseConfirm();
    } catch (error) {
      const message = error.response?.data?.message || "Retrait refusé par l'API.";
      state.status = "Refusé";
      state.result = `<strong>Retrait refusé</strong>${message}\nAppuyez sur Nouvelle opération.`;
      dom.showToast(message, "error");
      state.busy = false;
      state.phase = null;
      render();
    }
  }

  async function phaseConfirm() {
    state.busy = true;
    state.phase = "confirm";
    state.status = "Finalisation";
    render();

    try {
      const response = await api.post("/app/dab/confirm", {
        code: state.code,
      });
      const result = response.data;
      const txn = result.data.transaction;
      state.status = "Approuvé";
      state.phase = null;
      state.result = `<strong>Retrait effectué</strong>${result.message}\nRéf: ${txn.reference}\nMontant: ${moneyCents(txn.amount)}\nSolde: ${moneyCents(txn.remaining_balance)}`;
      dom.showToast("Retrait DAB effectué avec succès.", "success");
    } catch (error) {
      const message = error.response?.data?.message || "Erreur lors de la finalisation.";
      state.status = "Échec";
      state.result = `<strong>Erreur</strong>${message}\nContactez le service client.\nAppuyez sur Nouvelle opération.`;
      dom.showToast(message, "error");
    }

    state.busy = false;
    state.phase = null;
    render();
  }

  function confirmStep() {
    if (state.busy || state.result) return;

    if (state.step === "code" && state.code.length === 6) {
      state.step = "amount";
      state.status = "Code saisi";
    } else if (state.step === "amount" && Number(state.amount) > 0) {
      state.step = "pin";
      state.status = "Montant saisi";
    } else if (state.step === "pin" && state.pin.length === 4) {
      phaseAuthorize();
      return;
    } else {
      dom.showToast("Information incomplète.", "error");
    }

    render();
  }

  function bindKeypad() {
    dom.queryAll("[data-dab-key]").forEach((button) => {
      dom.on(button, "click", () => {
        if (state.busy || state.result) return;
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
        if (state.step !== "amount" || state.busy || state.result) return;
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
