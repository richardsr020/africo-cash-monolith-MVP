(function bootDashboard(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function money(cents, currency) {
    var curr = currency || "CDF";
    return (Number(cents || 0) / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 }) + " " + curr;
  }

  function transactionLabel(t) {
    var labels = { send: "Envoi", deposit: "Dépôt", withdraw: "Retrait", conversion: "Conversion", bank: "Virement", bill: "Facture", atm: "Retrait ATM" };
    return labels[t.type] || t.recipient_name || "Opération";
  }

  function timeAgo(dateStr) {
    var diff = Date.now() - new Date(dateStr.replace(" ", "T") + "Z").getTime();
    var mins = Math.floor(diff / 60000);
    if (mins < 1) return "à l'instant";
    if (mins < 60) return "il y a " + mins + " min";
    var hrs = Math.floor(mins / 60);
    if (hrs < 24) return "il y a " + hrs + "h";
    var days = Math.floor(hrs / 24);
    return "il y a " + days + "j";
  }

  function renderSimple(data) {
    var user = data.user;
    var accounts = data.accounts;
    var metrics = data.metrics;
    var currencies = accounts.map(function (a) { return a.currency; });
    var primary = currencies[0] || "CDF";
    var m = metrics[primary] || metrics.CDF || { balance: 0, income: 0, outcome: 0, savings_rate: 0 };

    dom.query("[data-user-name]").textContent = user.full_name || user.email || "--";

    var totalEl = dom.query("[data-total-balance]");
    if (totalEl) {
      var primaryVal = money(m.balance, primary);
      if (currencies.length > 1 && metrics[currencies[1]]) {
        var sec = money(metrics[currencies[1]].balance, currencies[1]);
        totalEl.innerHTML = primaryVal + ' <span style="opacity:0.5">' + sec + "</span>";
      } else {
        totalEl.textContent = primaryVal;
      }
    }

    var subEl = dom.query("[data-balance-secondary]");
    if (subEl) {
      subEl.textContent = m.income > 0 || m.outcome > 0
        ? "Entrées: " + money(m.income, primary) + " · Sorties: " + money(m.outcome, primary)
        : "";
    }

    var tx = data.recent_transactions;
    var txSection = dom.query("[data-last-tx]");
    if (txSection && tx && tx.length > 0) {
      txSection.hidden = false;
      dom.query("[data-tx-label]").textContent = transactionLabel(tx[0]);
      dom.query("[data-tx-amount]").textContent = money(tx[0].total_amount, tx[0].currency);
    }
  }

  function renderSparkline(container, values) {
    if (!container) return;
    var max = Math.max.apply(null, values.concat([1]));
    var spans = container.querySelectorAll("span");
    spans.forEach(function (span, i) {
      span.style.setProperty("--value", (values[i] !== undefined ? Math.round((values[i] / max) * 100) : 0) + "%");
    });
  }

  function renderChart(data) {
    var chartData = data.chart;
    var container = dom.query("[data-balance-chart]");
    if (!container) return;
    var days = Object.keys(chartData).sort();
    if (!days.length) {
      container.innerHTML = '<p class="text-muted" style="grid-column:1/-1;text-align:center">Aucune donnée.</p>';
      return;
    }
    var maxVal = days.reduce(function (max, day) {
      var totals = Object.values(chartData[day] || {}).reduce(function (s, v) { return s + (v.income || 0) + (v.outcome || 0); }, 0);
      return Math.max(max, totals);
    }, 1);
    container.innerHTML = days.map(function (day) {
      var totals = Object.values(chartData[day] || {}).reduce(function (s, v) { return s + (v.income || 0) + (v.outcome || 0); }, 0);
      var pct = Math.max(5, Math.round((totals / maxVal) * 100));
      return '<div class="bar-col"><span class="bar-col__fill" style="--value:' + pct + '%"></span><span class="bar-col__lbl">' + day.slice(5) + "</span></div>";
    }).join("");
  }

  function renderDonut(typeBreakdown) {
    if (!typeBreakdown) return;
    var donutValue = dom.query("[data-donut-value]");
    if (donutValue) {
      var top = ["transfer", "bills", "cash"].reduce(function (a, b) { return typeBreakdown[a] > typeBreakdown[b] ? a : b; });
      donutValue.textContent = typeBreakdown[top] + "%";
    }
    var set = function (sel, val) { var el = dom.query(sel); if (el) el.textContent = val + "%"; };
    set("[data-transfer-ratio]", typeBreakdown.transfer);
    set("[data-bill-ratio]", typeBreakdown.bills);
    set("[data-cash-ratio]", typeBreakdown.cash);
  }

  function renderMonthly(monthly) {
    if (!monthly) return;
    var set = function (sel, val) { var el = dom.query(sel); if (el) el.textContent = val; };
    set("[data-month-income]", money(monthly.income));
    set("[data-month-expense]", money(monthly.expense));
    set("[data-month-tx-count]", String(monthly.tx_count));
    set("[data-month-income-delta]", monthly.income_delta);
    set("[data-month-expense-delta]", monthly.expense_delta);
    set("[data-month-tx-delta]", monthly.tx_delta);
    set("[data-month-income-vs]", "vs " + money(monthly.income_vs));
    set("[data-month-expense-vs]", "vs " + money(monthly.expense_vs));
    set("[data-month-tx-vs]", "vs " + monthly.tx_vs);
    var incomeBar = dom.query("[data-month-income-bar]");
    if (incomeBar) incomeBar.style.width = monthly.income_bar + "%";
    var expenseBar = dom.query("[data-month-expense-bar]");
    if (expenseBar) expenseBar.style.width = monthly.expense_bar + "%";
    var txBar = dom.query("[data-month-tx-bar]");
    if (txBar) txBar.style.width = monthly.tx_bar + "%";
  }

  function renderFull(data) {
    var accounts = data.accounts;
    var metrics = data.metrics;
    var currencies = accounts.map(function (a) { return a.currency; });
    var primary = currencies[0] || "CDF";
    var m = metrics[primary] || metrics.CDF;

    var totalEl = dom.query("[data-total-balance-full]");
    if (totalEl) {
      var pv = money(m ? m.balance : 0, primary);
      if (currencies.length > 1 && metrics[currencies[1]]) {
        totalEl.innerHTML = pv + ' <small style="font-size:0.6em;color:var(--color-muted)">' + money(metrics[currencies[1]].balance, currencies[1]) + "</small>";
      } else {
        totalEl.textContent = pv;
      }
    }

    dom.query("[data-account-count]").textContent = accounts.length + " comptes actifs";

    if (currencies.length > 1 && m && metrics[currencies[1]]) {
      var sec = metrics[currencies[1]];
      dom.query("[data-income-total]").innerHTML = money(m.income, primary) + ' <small style="display:block;font-size:0.6em;color:var(--color-muted)">' + money(sec.income, currencies[1]) + "</small>";
      dom.query("[data-outcome-total]").innerHTML = money(m.outcome, primary) + ' <small style="display:block;font-size:0.6em;color:var(--color-muted)">' + money(sec.outcome, currencies[1]) + "</small>";
    } else {
      dom.query("[data-income-total]").textContent = m ? money(m.income, primary) : "--";
      dom.query("[data-outcome-total]").textContent = m ? money(m.outcome, primary) : "--";
    }

    dom.query("[data-savings-rate]").textContent = (m ? m.savings_rate : 0) + "%";

    var balances = currencies.map(function (c) { return metrics[c] ? metrics[c].balance : 0; });
    var incomes = currencies.map(function (c) { return metrics[c] ? metrics[c].income : 0; });
    var outcomes = currencies.map(function (c) { return metrics[c] ? metrics[c].outcome : 0; });
    var savingsBal = currencies.map(function (c) { return metrics[c] ? metrics[c].savings_balance : 0; });
    renderSparkline(dom.query("[data-sparkline-balance]"), balances);
    renderSparkline(dom.query("[data-sparkline-income]"), incomes);
    renderSparkline(dom.query("[data-sparkline-outcome]"), outcomes);
    renderSparkline(dom.query("[data-sparkline-savings]"), savingsBal);

    renderDonut(data.type_breakdown);
    renderMonthly(data.monthly);
    renderChart(data);

    if (data.monthly) {
      var bde = dom.query("[data-balance-delta]");
      if (bde) { bde.textContent = data.monthly.income_delta; bde.className = "metric-card__chip " + (data.monthly.income_delta && data.monthly.income_delta.indexOf("-") === 0 ? "chip--down" : "chip--up"); }
      var ide = dom.query("[data-income-delta]");
      if (ide) { ide.textContent = data.monthly.income_delta; ide.className = "metric-card__chip chip--" + (data.monthly.income_delta && data.monthly.income_delta.indexOf("-") === 0 ? "down" : "up"); }
      var ode = dom.query("[data-outcome-delta]");
      if (ode) { ode.textContent = data.monthly.expense_delta; ode.className = "metric-card__chip chip--" + (data.monthly.expense_delta && data.monthly.expense_delta.indexOf("-") === 0 ? "up" : "down"); }
    }

    var recent = dom.query("[data-recent-transactions]");
    if (recent && data.recent_transactions.length) {
      recent.innerHTML = data.recent_transactions.map(function (t) {
        var icons = { deposit: "fa-arrow-down", send: "fa-arrow-up", withdraw: "fa-arrow-up", bill: "fa-file-invoice", conversion: "fa-arrows-rotate", bank: "fa-building-columns", atm: "fa-money-bill" };
        var icon = icons[t.type] || "fa-circle";
        var label = transactionLabel(t);
        return '<div class="activity-item"><i class="fa-solid ' + icon + '"></i><span>' + label + '</span><strong>' + money(t.total_amount, t.currency) + "</strong></div>";
      }.bind(this)).join("");
    } else if (recent) {
      recent.innerHTML = '<div class="activity-item"><i class="fa-solid fa-circle-info"></i><span>Aucune transaction</span><strong>--</strong></div>';
    }
  }

  function renderTrustWidget(trust) {
    var container = dom.query("[data-trust-widget]");
    if (!container) return;
    var badges = { gold: { icon: '<i class="fa-solid fa-star" style="color:gold;font-size:2rem"></i>', label: "Doré" }, silver: { icon: '<i class="fa-solid fa-star" style="color:silver;font-size:2rem"></i>', label: "Argenté" }, none: { icon: '<i class="fa-solid fa-star" style="color:var(--color-subtle);font-size:2rem"></i>', label: "Aucun badge" } };
    var b = badges[trust.badge] || badges.none;
    var prog = "";
    if (trust.progression && trust.progression.next_badge) {
      var criteria = Object.values(trust.progression.criteria);
      var met = criteria.filter(function (c) { return c.met; }).length;
      var pct = criteria.length > 0 ? Math.round((met / criteria.length) * 100) : 0;
      prog = '<div class="trust-progress"><div class="trust-progress-header"><span>Prochain badge: <strong>' + (trust.progression.next_badge === "gold" ? "Doré" : "Argenté") + '</strong></span><span>' + met + "/" + criteria.length + '</span></div><div class="trust-progress-bar-bg"><div class="trust-progress-bar-fill" style="width:' + pct + '%"></div></div><div class="trust-criteria">' + criteria.map(function (c) { return '<div class="trust-criterion ' + (c.met ? "met" : "unmet") + '"><i class="fa-solid ' + (c.met ? "fa-check-circle" : "fa-circle") + '"></i><span>' + c.label + "</span></div>"; }).join("") + "</div></div>";
    }
    container.innerHTML = '<div class="trust-badge-display">' + b.icon + '<div class="trust-badge-info"><strong class="trust-badge-label">' + b.label + '</strong><span class="trust-badge-score">' + trust.trust_score + '/1000</span></div></div>' + prog + '<a class="btn btn-soft btn-sm" href="/profil" style="margin-top:0.5rem"><i class="fa-solid fa-arrow-right"></i> Voir détails</a>';
  }

  function renderGoals(goals) {
    var container = dom.query("[data-goals-list]");
    if (!container) return;
    if (!goals || !goals.length) {
      container.innerHTML = '<p class="text-muted" style="text-align:center;padding:1rem">Aucun objectif.</p>';
      return;
    }
    container.innerHTML = goals.map(function (g, i) {
      var pct = g.target_amount > 0 ? Math.round((g.current_amount / g.target_amount) * 100) : 0;
      return '<div class="goal-item"><div class="goal-item__top"><span class="goal-item__name">' + g.name + '</span><span class="goal-item__amount">' + money(g.current_amount, g.currency) + " / " + money(g.target_amount, g.currency) + '</span></div><div class="goal-item__bar-bg"><div class="goal-item__bar goal-item__bar--' + (i + 1) + '" style="width:' + Math.min(100, pct) + '%"></div></div><span class="goal-item__pct">' + pct + "%</span></div>";
    }).join("");
  }

  function loadFull() {
    api.get("/app/summary").then(function (resp) {
      renderFull(resp.data.data);
    }).catch(function () { dom.showToast("Erreur chargement stats.", "error"); });
    api.get("/app/trust-score").then(function (res) { renderTrustWidget(res.data.data); }).catch(function () {});
    api.get("/app/savings-goals").then(function (res) { renderGoals(res.data.data); }).catch(function () {});
  }

  function loadPeriodData(period) {
    api.get("/app/summary/period?period=" + period).then(function (resp) { renderChart(resp.data.data); }).catch(function () {});
  }

  documentObject.addEventListener("DOMContentLoaded", function () {
    var now = new Date();
    var dateEl = dom.query("[data-current-date]");
    if (dateEl) {
      dateEl.textContent = now.toLocaleDateString("fr-FR", { weekday: "long", year: "numeric", month: "long", day: "numeric" });
    }

    dom.queryAll("[data-quick-link]").forEach(function (button) {
      dom.on(button, "click", function () { windowObject.location.assign(button.dataset.quickLink); });
    });

    api.get("/app/summary").then(function (response) {
      renderSimple(response.data.data);
    }).catch(function () { dom.showToast("Impossible de charger le solde.", "error"); });

    var advancedContent = dom.query("[data-advanced-content]");
    var simpleContent = dom.query("[data-simple-content]");
    var advancedBtn = dom.query("[data-toggle-advanced]");
    var toggleLabel = dom.query("[data-toggle-label]");

    var isAdvanced = false;
    try { isAdvanced = localStorage.getItem("dashboard_advanced") === "true"; } catch (e) {}

    function applyMode(mode) {
      var adv = mode === "advanced";
      if (advancedContent) advancedContent.hidden = !adv;
      if (simpleContent) simpleContent.hidden = adv;
      if (advancedBtn) advancedBtn.classList.toggle("is-open", adv);
      if (toggleLabel) toggleLabel.textContent = adv ? "Masquer les statistiques" : "Voir les statistiques détaillées";
      if (adv) loadFull();
    }

    if (isAdvanced) applyMode("advanced");

    if (advancedBtn) {
      dom.on(advancedBtn, "click", function () {
        isAdvanced = !isAdvanced;
        applyMode(isAdvanced ? "advanced" : "simple");
        try { localStorage.setItem("dashboard_advanced", isAdvanced); } catch (e) {}
        windowObject.dispatchEvent(new CustomEvent("dashboard-mode-change", { detail: { mode: isAdvanced ? "advanced" : "simple" } }));
      });
    }

    dom.queryAll("[data-period]").forEach(function (tab) {
      dom.on(tab, "click", function () {
        var period = this.getAttribute("data-period");
        dom.queryAll("[data-period]").forEach(function (t) { t.classList.remove("period-tab--active"); });
        this.classList.add("period-tab--active");
        loadPeriodData(period);
      });
    });
  });
})(window, document);
