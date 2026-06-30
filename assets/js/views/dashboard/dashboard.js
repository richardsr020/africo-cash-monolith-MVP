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

  function findMetric(metrics, currency) {
    return metrics[currency] || null;
  }

  function renderPerCurrencyRow(container, metrics, currencies) {
    const parts = currencies
      .filter(c => metrics[c])
      .map(c => money(metrics[c].balance, c));
    container.textContent = parts.join(" / ");
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    api.get("/app/summary").then((response) => {
      const data = response.data.data;
      const metrics = data.metrics;
      const currencies = data.accounts.map(a => a.currency);
      const primaryCurrency = currencies[0] || "CDF";
      const primary = findMetric(metrics, primaryCurrency) || metrics.CDF;

      // Show primary currency as main, secondary inline
      const totalEl = dom.query("[data-total-balance]");
      const primaryTotal = primary ? money(primary.balance, primaryCurrency) : "--";
      if (currencies.length > 1) {
        const secondaryCur = currencies[1];
        const secondary = findMetric(metrics, secondaryCur);
        const secondaryTotal = secondary ? money(secondary.balance, secondaryCur) : "";
        totalEl.innerHTML = `${primaryTotal} <small style="font-size:0.6em;color:var(--color-muted)">${secondaryTotal}</small>`;
      } else {
        totalEl.textContent = primaryTotal;
      }

      dom.query("[data-account-count]").textContent = `${data.accounts.length} comptes actifs`;

      if (currencies.length > 1 && primary && metrics[currencies[1]]) {
        const sec = metrics[currencies[1]];
        dom.query("[data-income-total]").innerHTML =
          `${money(primary.income, primaryCurrency)} <small style="display:block;font-size:0.6em;color:var(--color-muted)">${money(sec.income, currencies[1])}</small>`;
        dom.query("[data-outcome-total]").innerHTML =
          `${money(primary.outcome, primaryCurrency)} <small style="display:block;font-size:0.6em;color:var(--color-muted)">${money(sec.outcome, currencies[1])}</small>`;
      } else {
        dom.query("[data-income-total]").textContent = primary ? money(primary.income, primaryCurrency) : "--";
        dom.query("[data-outcome-total]").textContent = primary ? money(primary.outcome, primaryCurrency) : "--";
      }

      const savRate = primary ? primary.savings_rate : 0;
      dom.query("[data-savings-rate]").textContent = `${savRate}%`;
      dom.query("[data-donut-value]").textContent = `${savRate}%`;

      const totalCount = primary ? primary.total_count : 0;
      dom.query("[data-transfer-ratio]").textContent = `${Math.min(80, totalCount * 12)}%`;
      dom.query("[data-bill-ratio]").textContent = `${Math.min(45, totalCount * 7)}%`;
      dom.query("[data-cash-ratio]").textContent = `${Math.max(10, 100 - Math.min(90, totalCount * 12))}%`;

      const chartData = data.chart[primaryCurrency] || data.chart.CDF || [24, 32, 45, 38, 52, 61, 48];
      dom.queryAll("[data-balance-chart] span").forEach((bar, index) => {
        bar.style.setProperty("--value", `${chartData[index] || 24}%`);
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

    // Load trust score
    api.get("/app/trust-score").then((res) => {
      const trust = res.data.data;
      renderTrustWidget(trust);
    }).catch(() => {});

    function renderTrustWidget(trust) {
      const container = dom.query("[data-trust-widget]");
      if (!container) return;

      const badgeIcons = {
        gold: '<i class="fa-solid fa-star" style="color:gold;font-size:2rem"></i>',
        silver: '<i class="fa-solid fa-star" style="color:silver;font-size:2rem"></i>',
        none: '<i class="fa-solid fa-star" style="color:var(--color-subtle);font-size:2rem"></i>',
      };

      const badgeLabels = {
        gold: 'Doré',
        silver: 'Argenté',
        none: 'Aucun badge',
      };

      const icon = badgeIcons[trust.badge] || badgeIcons.none;
      const label = badgeLabels[trust.badge] || badgeLabels.none;

      let progressHtml = '';
      if (trust.progression && trust.progression.next_badge) {
        const criteria = Object.values(trust.progression.criteria);
        const metCount = criteria.filter(c => c.met).length;
        const totalCount = criteria.length;
        const pct = totalCount > 0 ? Math.round((metCount / totalCount) * 100) : 0;

        progressHtml = `
          <div class="trust-progress">
            <div class="trust-progress-header">
              <span>Prochain badge: <strong>${trust.progression.next_badge === 'gold' ? 'Doré' : 'Argenté'}</strong></span>
              <span>${metCount}/${totalCount}</span>
            </div>
            <div class="trust-progress-bar-bg">
              <div class="trust-progress-bar-fill" style="width:${pct}%"></div>
            </div>
            <div class="trust-criteria">
              ${criteria.map(c => `
                <div class="trust-criterion ${c.met ? 'met' : 'unmet'}">
                  <i class="fa-solid ${c.met ? 'fa-check-circle' : 'fa-circle'}"></i>
                  <span>${c.label}</span>
                </div>
              `).join('')}
            </div>
          </div>`;
      }

      container.innerHTML = `
        <div class="trust-badge-display">
          ${icon}
          <div class="trust-badge-info">
            <strong class="trust-badge-label">${label}</strong>
            <span class="trust-badge-score">${trust.trust_score}/1000</span>
          </div>
        </div>
        ${progressHtml}
        <a class="btn btn-soft btn-sm" href="/profil" style="margin-top:0.5rem">
          <i class="fa-solid fa-arrow-right"></i> Voir détails
        </a>
      `;
    }
  });
})(window, document);
