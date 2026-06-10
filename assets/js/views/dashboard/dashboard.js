(function bootDashboard(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function money(cents, currency = "CDF") {
    return `${(Number(cents || 0) / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 })} ${currency}`;
  }

  function transactionLabel(transaction) {
    const labels = {
      send: "Transfert Africo",
      deposit: "Dépôt portefeuille",
      withdraw: "Retrait portefeuille",
      conversion: "Conversion",
      bank: "Virement bancaire",
      bill: "Paiement facture",
      atm: "Retrait ATM",
    };
    return labels[transaction.type] || transaction.provider_name || "Transaction";
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    api.get("/app/summary").then((response) => {
      const data = response.data.data;
      const primaryCurrency = data.accounts[0]?.currency || "CDF";
      dom.query("[data-total-balance]").textContent = money(data.metrics.total_balance, primaryCurrency);
      dom.query("[data-account-count]").textContent = `${data.accounts.length} comptes actifs`;
      dom.query("[data-income-total]").textContent = money(data.metrics.income, primaryCurrency);
      dom.query("[data-outcome-total]").textContent = money(data.metrics.outcome, primaryCurrency);
      dom.query("[data-savings-rate]").textContent = `${data.metrics.savings_rate}%`;
      dom.query("[data-donut-value]").textContent = `${data.metrics.savings_rate}%`;
      dom.query("[data-transfer-ratio]").textContent = `${Math.min(80, data.metrics.total_count * 12)}%`;
      dom.query("[data-bill-ratio]").textContent = `${Math.min(45, data.metrics.total_count * 7)}%`;
      dom.query("[data-cash-ratio]").textContent = `${Math.max(10, 100 - Math.min(90, data.metrics.total_count * 12))}%`;

      dom.queryAll("[data-balance-chart] span").forEach((bar, index) => {
        bar.style.setProperty("--value", `${data.chart[index] || 24}%`);
        bar.style.animationDelay = `${index * 80}ms`;
      });

      const recent = dom.query("[data-recent-transactions]");
      recent.innerHTML = data.recent_transactions.length
        ? data.recent_transactions.map((transaction) => `
          <div>
            <i class="fa-solid ${transaction.type === "deposit" ? "fa-arrow-down" : "fa-arrow-up"}"></i>
            <span>${transactionLabel(transaction)}</span>
            <strong>${money(transaction.total_amount, transaction.currency)}</strong>
          </div>
        `).join("")
        : '<div><i class="fa-solid fa-circle-info"></i><span>Aucune transaction pour le moment</span><strong>--</strong></div>';
    }).catch(() => dom.showToast("Impossible de charger le dashboard.", "error"));

    dom.queryAll("[data-quick-link]").forEach((button) => {
      dom.on(button, "click", () => windowObject.location.assign(button.dataset.quickLink));
    });
  });
})(window, document);
