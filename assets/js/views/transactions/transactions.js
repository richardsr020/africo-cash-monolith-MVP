(function bootTransactions(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function money(cents, currency) {
    return `${(Number(cents || 0) / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 })} ${currency}`;
  }

  async function loadTransactions() {
    const response = await api.get("/app/transactions");
    const rows = response.data.data.transactions.map((transaction) => `
      <div><span>${transaction.reference}</span><span>${transaction.provider_name || transaction.type}</span><span>${money(transaction.total_amount, transaction.currency)}</span><strong>${transaction.status}</strong></div>
    `).join("");
    dom.query("[data-transaction-table]").innerHTML = `<div><span>ID</span><span>Canal</span><span>Montant</span><span>Statut</span></div>${rows}`;
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    loadTransactions().catch(() => dom.showToast("Historique indisponible.", "error"));

    dom.on(dom.query("[data-transaction-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const button = dom.query("button[type='submit']", form);
      dom.setSubmitting(button, true, "Traitement...");
      try {
        await api.post("/app/transactions", dom.serializeForm(form));
        form.reset();
        dom.showToast("Transaction exécutée.", "success");
        await loadTransactions();
      } catch (error) {
        dom.showToast(error.response?.data?.error?.message || "Transaction refusée.", "error");
      } finally {
        dom.setSubmitting(button, false);
      }
    });
  });
})(window, document);
