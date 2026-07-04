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
    return labels[transaction.type] || transaction.provider_name || transaction.recipient_name || "Transaction";
  }

  function transactionIcon(type) {
    const icons = {
      deposit: "fa-arrow-down",
      deposit_agent: "fa-arrow-down",
      deposit_bank: "fa-arrow-down",
      deposit_mobile_money: "fa-arrow-down",
      send: "fa-arrow-up",
      send_mobile_money: "fa-arrow-up",
      withdraw: "fa-arrow-up",
      withdraw_agent: "fa-arrow-up",
      withdraw_bank: "fa-arrow-up",
      withdraw_atm: "fa-arrow-up",
      bill: "fa-file-invoice",
      bill_payment: "fa-file-invoice",
      conversion: "fa-arrows-rotate",
      wallet_transfer: "fa-piggy-bank",
      atm: "fa-money-bill",
    };
    return icons[type] || "fa-circle";
  }

  function findMetric(metrics, currency) {
    return metrics[currency] || null;
  }

  function renderSparkline(container, values) {
    if (!container) return;
    const max = Math.max(...values, 1);
    const spans = container.querySelectorAll("span");
    spans.forEach((span, i) => {
      const v = values[i] !== undefined ? values[i] : 0;
      span.style.setProperty("--value", `${Math.round((v / max) * 100)}%`);
    });
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    const now = new Date();
    const dateEl = dom.query("[data-current-date]");
    if (dateEl) {
      dateEl.textContent = now.toLocaleDateString("fr-FR", { weekday: "long", year: "numeric", month: "long", day: "numeric" });
    }

    dom.queryAll("[data-quick-link]").forEach((button) => {
      dom.on(button, "click", () => windowObject.location.assign(button.dataset.quickLink));
    });

    let currentPeriod = "1m";

    function renderChart(data) {
      const chartData = data.chart;
      const container = dom.query("[data-balance-chart]");
      if (!container) return;
      const days = Object.keys(chartData).sort();
      if (!days.length) {
        container.innerHTML = '<p class="text-muted" style="grid-column:1/-1;text-align:center">Aucune donnée pour cette période.</p>';
        return;
      }
      const maxVal = days.reduce((max, day) => {
        const totals = Object.values(chartData[day] || {}).reduce((s, v) => s + (v.income || 0) + (v.outcome || 0), 0);
        return Math.max(max, totals);
      }, 1);
      container.innerHTML = days.map((day) => {
        const totals = Object.values(chartData[day] || {}).reduce((s, v) => s + (v.income || 0) + (v.outcome || 0), 0);
        const pct = Math.max(5, Math.round((totals / maxVal) * 100));
        const label = day.slice(5);
        return `<div class="bar-col"><span class="bar-col__fill" style="--value:${pct}%"></span><span class="bar-col__lbl">${label}</span></div>`;
      }).join("");
    }

    function renderDonut(typeBreakdown) {
      const donutValue = dom.query("[data-donut-value]");
      const transferRatio = dom.query("[data-transfer-ratio]");
      const billRatio = dom.query("[data-bill-ratio]");
      const cashRatio = dom.query("[data-cash-ratio]");
      if (!typeBreakdown) return;
      if (donutValue) {
        const topCategory = ["transfer", "bills", "cash"].reduce((a, b) => typeBreakdown[a] > typeBreakdown[b] ? a : b);
        donutValue.textContent = typeBreakdown[topCategory] + "%";
      }
      if (transferRatio) transferRatio.textContent = typeBreakdown.transfer + "%";
      if (billRatio) billRatio.textContent = typeBreakdown.bills + "%";
      if (cashRatio) cashRatio.textContent = typeBreakdown.cash + "%";
    }

    function renderMonthly(monthly) {
      if (!monthly) return;
      const setVal = (sel, val) => { const el = dom.query(sel); if (el) el.textContent = val; };
      setVal("[data-month-income]", money(monthly.income));
      setVal("[data-month-expense]", money(monthly.expense));
      setVal("[data-month-tx-count]", String(monthly.tx_count));
      setVal("[data-month-income-delta]", monthly.income_delta);
      setVal("[data-month-expense-delta]", monthly.expense_delta);
      setVal("[data-month-tx-delta]", monthly.tx_delta);
      setVal("[data-month-income-vs]", "vs " + money(monthly.income_vs));
      setVal("[data-month-expense-vs]", "vs " + money(monthly.expense_vs));
      setVal("[data-month-tx-vs]", "vs " + monthly.tx_vs);
      const incomeBar = dom.query("[data-month-income-bar]");
      if (incomeBar) incomeBar.style.width = monthly.income_bar + "%";
      const expenseBar = dom.query("[data-month-expense-bar]");
      if (expenseBar) expenseBar.style.width = monthly.expense_bar + "%";
      const txBar = dom.query("[data-month-tx-bar]");
      if (txBar) txBar.style.width = monthly.tx_bar + "%";

      const trendUp = dom.query("[data-month-income-delta]");
      if (trendUp) {
        trendUp.className = "msum-card__trend " + (monthly.income_delta && monthly.income_delta.startsWith("-") ? "msum-card__trend--down" : "msum-card__trend--up");
      }
      const trendDown = dom.query("[data-month-expense-delta]");
      if (trendDown) {
        trendDown.className = "msum-card__trend " + (monthly.expense_delta && monthly.expense_delta.startsWith("-") ? "msum-card__trend--up" : "msum-card__trend--down");
      }
    }

    function renderAlerts(alerts) {
      const banner = dom.query("[data-alert-banner]");
      if (!banner || !alerts || !alerts.length) {
        if (banner) banner.style.display = "none";
        return;
      }
      const alert = alerts[0];
      const titleEl = dom.query("[data-alert-title]");
      const bodyEl = dom.query("[data-alert-body]");
      if (titleEl) titleEl.textContent = alert.title;
      if (bodyEl) bodyEl.textContent = alert.body || "";
      banner.style.display = "";
      banner.className = "alert-banner alert-banner--" + (alert.alert_type || "info");
      const closeBtn = dom.query("[data-alert-close]");
      if (closeBtn) {
        dom.on(closeBtn, "click", () => { banner.style.display = "none"; });
      }
    }

    function renderGoals(goals) {
      const container = dom.query("[data-goals-list]");
      if (!container) return;
      if (!goals || !goals.length) {
        container.innerHTML = '<p class="text-muted" style="text-align:center;padding:1rem">Aucun objectif d\'épargne défini.</p>';
        return;
      }
      container.innerHTML = goals.map((g, i) => {
        const pct = g.target_amount > 0 ? Math.round((g.current_amount / g.target_amount) * 100) : 0;
        return `<div class="goal-item">
          <div class="goal-item__top">
            <span class="goal-item__name">${g.name}</span>
            <span class="goal-item__amount">${money(g.current_amount, g.currency)} / ${money(g.target_amount, g.currency)}</span>
          </div>
          <div class="goal-item__bar-bg">
            <div class="goal-item__bar goal-item__bar--${i + 1}" style="width:${Math.min(100, pct)}%"></div>
          </div>
          <span class="goal-item__pct">${pct}%</span>
        </div>`;
      }).join("");
    }

    function loadPeriodData(period) {
      api.get("/app/summary/period?period=" + period).then((resp) => {
        const data = resp.data.data;
        renderChart(data);
      }).catch(() => {});
    }

    api.get("/app/summary").then((response) => {
      const data = response.data.data;
      const metrics = data.metrics;
      const currencies = data.accounts.map(a => a.currency);
      const primaryCurrency = currencies[0] || "CDF";
      const primary = findMetric(metrics, primaryCurrency) || metrics.CDF;

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

      const balances = currencies.map(c => metrics[c] ? metrics[c].balance : 0);
      const incomes = currencies.map(c => metrics[c] ? metrics[c].income : 0);
      const outcomes = currencies.map(c => metrics[c] ? metrics[c].outcome : 0);
      const savingsBal = currencies.map(c => metrics[c] ? metrics[c].savings_balance : 0);
      renderSparkline(dom.query("[data-sparkline-balance]"), balances);
      renderSparkline(dom.query("[data-sparkline-income]"), incomes);
      renderSparkline(dom.query("[data-sparkline-outcome]"), outcomes);
      renderSparkline(dom.query("[data-sparkline-savings]"), savingsBal);

      renderDonut(data.type_breakdown);
      renderMonthly(data.monthly);
      renderChart(data);

      const balanceDeltaEl = dom.query("[data-balance-delta]");
      if (balanceDeltaEl && data.monthly) {
        balanceDeltaEl.textContent = data.monthly.income_delta;
        balanceDeltaEl.className = "metric-card__chip " + (data.monthly.income_delta && data.monthly.income_delta.startsWith("-") ? "chip--down" : "chip--up");
      }
      const incomeDeltaEl = dom.query("[data-income-delta]");
      if (incomeDeltaEl && data.monthly) {
        incomeDeltaEl.textContent = data.monthly.income_delta;
        incomeDeltaEl.className = "metric-card__chip chip--" + (data.monthly.income_delta && data.monthly.income_delta.startsWith("-") ? "down" : "up");
      }
      const outcomeDeltaEl = dom.query("[data-outcome-delta]");
      if (outcomeDeltaEl && data.monthly) {
        outcomeDeltaEl.textContent = data.monthly.expense_delta;
        outcomeDeltaEl.className = "metric-card__chip chip--" + (data.monthly.expense_delta && data.monthly.expense_delta.startsWith("-") ? "up" : "down");
      }

      const recent = dom.query("[data-recent-transactions]");
      recent.innerHTML = data.recent_transactions.length
        ? data.recent_transactions.map((transaction) => `
          <div class="activity-item">
            <i class="fa-solid ${transactionIcon(transaction.type)}"></i>
            <span>${transactionLabel(transaction)}</span>
            <strong>${money(transaction.total_amount, transaction.currency)}</strong>
          </div>
        `).join("")
        : '<div class="activity-item"><i class="fa-solid fa-circle-info"></i><span>Aucune transaction pour le moment</span><strong>--</strong></div>';
    }).catch(() => dom.showToast("Impossible de charger le dashboard.", "error"));

    api.get("/app/trust-score").then((res) => {
      const trust = res.data.data;
      renderTrustWidget(trust);
    }).catch(() => {});

    api.get("/app/savings-goals").then((res) => {
      renderGoals(res.data.data);
    }).catch(() => {});

    api.get("/app/alerts").then((res) => {
      renderAlerts(res.data.data);
    }).catch(() => {});

    dom.queryAll("[data-period]").forEach((tab) => {
      dom.on(tab, "click", function () {
        const period = this.getAttribute("data-period");
        if (period === currentPeriod) return;
        currentPeriod = period;
        dom.queryAll("[data-period]").forEach(t => t.classList.remove("period-tab--active"));
        this.classList.add("period-tab--active");
        loadPeriodData(period);
      });
    });

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
