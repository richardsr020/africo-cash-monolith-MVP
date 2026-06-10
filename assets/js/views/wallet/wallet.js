(function bootWallet(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function money(cents, currency) {
    return `${(Number(cents || 0) / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 })} ${currency}`;
  }

  documentObject.addEventListener("DOMContentLoaded", async () => {
    try {
      const response = await api.get("/app/wallet");
      const { accounts, movements } = response.data.data;
      dom.query("[data-wallet-balances]").innerHTML = accounts.map((account) => `
        <div class="balance-row"><span>${account.currency}</span><strong>${money(account.balance, account.currency)}</strong><button type="button">Détails</button></div>
      `).join("");
      dom.query("[data-wallet-movements]").innerHTML = movements.length
        ? movements.map((movement) => `<div><span>${movement.reference} · ${movement.type}</span><strong>${money(movement.total_amount, movement.currency)}</strong></div>`).join("")
        : "<div><span>Aucun mouvement récent</span><strong>--</strong></div>";
    } catch (error) {
      dom.showToast("Portefeuille indisponible.", "error");
    }
  });
})(window, document);
