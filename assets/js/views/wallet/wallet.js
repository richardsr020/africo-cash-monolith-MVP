(function bootWallet(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  let state = {
    currentWallet: "current",
    currentSavingsCurrency: "CDF",
    savingsConfig: {},
  };

  function money(cents, currency) {
    return `${(Number(cents || 0) / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 })} ${currency}`;
  }

  function moneyRaw(cents) {
    return Number(cents || 0) / 100;
  }

  function typeLabel(type) {
    const labels = {
      send: "Envoi",
      deposit: "Dépôt",
      withdraw: "Retrait",
      bill: "Facture",
      conversion: "Conversion",
      bank: "Virement",
      atm: "Retrait ATM",
      send_bank: "Virement bancaire",
      wallet_transfer: "Virement interne",
      early_unlock: "Déblocage épargne",
    };
    return labels[type] || type;
  }

  function typeIcon(type) {
    if (["deposit", "deposit_agent", "deposit_mobile_money", "deposit_bank"].includes(type)) return "in";
    if (["send", "withdraw", "withdraw_agent", "withdraw_bank", "withdraw_atm", "send_bank"].includes(type)) return "out";
    if (type === "bill") return "bill";
    if (type === "conversion") return "convert";
    if (type === "wallet_transfer" || type === "early_unlock") return "neutral";
    return "neutral";
  }

  function typeAmountClass(type) {
    if (["deposit", "deposit_agent", "deposit_mobile_money", "deposit_bank"].includes(type)) return "credit";
    if (["send", "withdraw", "withdraw_agent", "withdraw_bank", "withdraw_atm", "send_bank", "bill"].includes(type)) return "debit";
    return "neutral";
  }

  function statusBadge(status) {
    const map = {
      completed: '<span class="movement-item__badge badge--ok">OK</span>',
      pending: '<span class="movement-item__badge badge--pend">En cours</span>',
      failed: '<span class="movement-item__badge badge--fail">Échec</span>',
      cancelled: '<span class="movement-item__badge badge--fail">Annulé</span>',
      succeeded: '<span class="movement-item__badge badge--ok">OK</span>',
    };
    return map[status] || "";
  }

  function loadWallet() {
    showSkeletons();
    api.get("/app/wallet").then((response) => {
      const data = response.data.data;
      const activeType = state.currentWallet;
      renderBalances(data.current_accounts, "current");
      renderBalances(data.savings_accounts, "savings");
      state.savingsConfig = data.savings_config || {};
      renderSavingsConfig(state.currentSavingsCurrency);
      renderTotalBalance(data.current_accounts, data.savings_accounts);
      renderMovements(data.movements);
      updateSavingsBadge(data.savings_config);
    }).catch(() => dom.showToast("Portefeuille indisponible.", "error"));
  }

  function showSkeletons() {
    ["current", "savings"].forEach((type) => {
      const container = dom.query(`[data-wallet-balances="${type}"]`);
      if (container) {
        container.innerHTML =
          '<div class="wallet-balance-skeleton"><div class="skeleton-card"></div><div class="skeleton-card"></div></div>';
      }
    });
    dom.query("[data-wallet-movements]").innerHTML =
      '<div class="movement-skeleton"><div class="skeleton-row"></div><div class="skeleton-row"></div><div class="skeleton-row"></div></div>';
  }

  function renderTotalBalance(currentAccounts, savingsAccounts) {
    const all = [...currentAccounts, ...savingsAccounts];
    const totalFormatted = all.map((a) => money(a.balance, a.currency)).join(" / ");
    dom.query("[data-total-balance]").textContent = totalFormatted;
  }

  function renderBalances(accounts, walletType) {
    const container = dom.query(`[data-wallet-balances="${walletType}"]`);
    if (!container) return;

    if (!accounts || !accounts.length) {
      container.innerHTML = '<div class="wallet-balance-card" style="text-align:center;padding:2rem;color:var(--color-muted)"><span>Aucun compte.</span></div>';
      return;
    }

    container.innerHTML = accounts.map((account, idx) => {
      const bars = Array.from({ length: 7 }, (_, i) => {
        const h = 20 + ((parseInt(account.balance) + i * 7919) % 60);
        return `<span class="bar--income" style="height:${h}%;animation-delay:${(idx * 7 + i) * 60}ms"></span>`;
      }).join("");

      return `<div class="wallet-balance-card">
        <div class="wallet-balance-card__info">
          <span class="wallet-balance-card__currency">${account.currency}</span>
          <strong class="wallet-balance-card__amount">${money(account.balance, account.currency)}</strong>
          <span class="wallet-balance-card__meta">${walletType === "savings" ? "Épargne" : "Courant"}</span>
        </div>
        <div class="wallet-balance-card__chart">${bars}</div>
        <div class="wallet-balance-card__actions">
          ${walletType === "savings"
            ? `<button type="button" data-action-from-savings="${account.currency}"><i class="fa-solid fa-circle-up"></i> Retirer</button>
               <button type="button" data-action-to-savings="${account.currency}"><i class="fa-solid fa-circle-down"></i> Alimenter</button>`
            : `<button type="button" data-quick-deposit="${account.currency}"><i class="fa-solid fa-circle-down"></i> Dépôt</button>
               <button type="button" data-quick-withdraw="${account.currency}"><i class="fa-solid fa-circle-up"></i> Retrait</button>
               <button type="button" data-action-to-savings="${account.currency}"><i class="fa-solid fa-piggy-bank"></i> Épargner</button>`
          }
        </div>
      </div>`;
    }).join("");

    dom.queryAll("[data-quick-deposit], [data-quick-withdraw]").forEach((btn) => {
      dom.on(btn, "click", () => {
        windowObject.location.assign("/transactions");
      });
    });
    dom.queryAll("[data-action-to-savings]").forEach((btn) => {
      dom.on(btn, "click", () => openTransferModal("to-savings", btn.dataset.actionToSavings));
    });
    dom.queryAll("[data-action-from-savings]").forEach((btn) => {
      dom.on(btn, "click", () => openTransferModal("from-savings", btn.dataset.actionFromSavings));
    });
  }

  /* ── Savings Config ── */

  function renderSavingsConfig(currency) {
    const container = dom.query("[data-savings-config-body]");
    if (!container) return;

    const config = (state.savingsConfig[currency] || {});
    const isFlexible = config.mode === "flexible";

    container.innerHTML = `
      <div class="savings-config__currency-tabs">
        <button class="savings-config__currency-tab ${currency === "CDF" ? "savings-config__currency-tab--active" : ""}" data-savings-currency="CDF">CDF</button>
        <button class="savings-config__currency-tab ${currency === "USD" ? "savings-config__currency-tab--active" : ""}" data-savings-currency="USD">USD</button>
      </div>
      <div class="savings-config__row">
        <div class="savings-config__label">
          <strong>Cashback</strong>
          <span>Recevez un pourcentage sur vos achats chez nos partenaires</span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" data-config-cashback ${config.cashback_enabled ? "checked" : ""}>
          <span class="toggle-switch__slider"></span>
        </label>
      </div>
      <div class="savings-config__row">
        <div class="savings-config__label">
          <strong>Arrondi automatique</strong>
          <span>Arrondir chaque dépense à l'unité supérieure et épargner la différence</span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" data-config-roundup ${config.roundup_enabled ? "checked" : ""}>
          <span class="toggle-switch__slider"></span>
        </label>
      </div>
      <div class="savings-config__row">
        <div class="savings-config__label">
          <strong>Mode d'épargne</strong>
          <span>${isFlexible ? "Flexible : retraits libres (max " + (config.flexible_withdrawals_per_month || 2) + "/mois)" : "Bloqué : accès restreint jusqu'à la fin de la période"}</span>
        </div>
        <div class="savings-mode-select">
          <button class="savings-mode-btn ${isFlexible ? "savings-mode-btn--active" : ""}" data-config-mode="flexible">Flexible</button>
          <button class="savings-mode-btn ${!isFlexible ? "savings-mode-btn--active" : ""}" data-config-mode="locked">Bloqué</button>
        </div>
      </div>
      ${!isFlexible ? `
      <div class="savings-config__row">
        <div class="savings-config__label">
          <strong>Durée de blocage</strong>
          <span>Période pendant laquelle l'épargne est inaccessible</span>
        </div>
        <select data-config-lock-duration class="form-control" style="width:auto;padding:6px 10px;font-size:13px">
          <option value="30" ${config.lock_duration_days === 30 ? "selected" : ""}>30 jours</option>
          <option value="60" ${config.lock_duration_days === 60 ? "selected" : ""}>60 jours</option>
          <option value="90" ${config.lock_duration_days === 90 ? "selected" : ""}>3 mois</option>
          <option value="180" ${config.lock_duration_days === 180 ? "selected" : ""}>6 mois</option>
          <option value="365" ${config.lock_duration_days === 365 ? "selected" : ""}>1 an</option>
        </select>
      </div>` : ""}
      <div class="savings-config__info ${config.is_locked ? "savings-config__info--warning" : ""}">
        ${config.is_locked
          ? '<i class="fa-solid fa-lock"></i> Épargne bloquée. Tout retrait anticipé entraîne des frais de ' + (config.early_withdraw_fee_bps / 100) + '% et un délai de ' + config.early_withdraw_delay_days + ' jours.'
          : '<i class="fa-solid fa-info-circle"></i> Retraits effectués ce mois : ' + (config.withdrawals_this_month || 0) + '/' + (config.flexible_withdrawals_per_month || 2) + ' (mode ' + (isFlexible ? "flexible" : "bloqué") + ').'
        }
      </div>
    `;

    dom.queryAll("[data-savings-currency]").forEach((btn) => {
      dom.on(btn, "click", () => {
        state.currentSavingsCurrency = btn.dataset.savingsCurrency;
        renderSavingsConfig(state.currentSavingsCurrency);
      });
    });

    dom.queryAll("[data-config-cashback]").forEach((cb) => {
      dom.on(cb, "change", () => saveConfigField("cashback_enabled", cb.checked ? 1 : 0));
    });
    dom.queryAll("[data-config-roundup]").forEach((cb) => {
      dom.on(cb, "change", () => saveConfigField("roundup_enabled", cb.checked ? 1 : 0));
    });
    dom.queryAll("[data-config-mode]").forEach((btn) => {
      dom.on(btn, "click", () => {
        saveConfigField("mode", btn.dataset.configMode);
      });
    });
    const lockDuration = dom.query("[data-config-lock-duration]");
    if (lockDuration) {
      dom.on(lockDuration, "change", () => saveConfigField("lock_duration_days", parseInt(lockDuration.value)));
    }
  }

  function saveConfigField(field, value) {
    const payload = { currency: state.currentSavingsCurrency };
    payload[field] = value;
    api.post("/app/wallet/savings-config", payload).then((res) => {
      const config = res.data.data;
      const key = state.currentSavingsCurrency;
      if (!state.savingsConfig[key]) state.savingsConfig[key] = {};
      Object.assign(state.savingsConfig[key], config);
      renderSavingsConfig(state.currentSavingsCurrency);
      dom.showToast("Configuration mise à jour.", "success");
    }).catch(() => {
      dom.showToast("Erreur lors de la mise à jour.", "error");
    });
  }

  function updateSavingsBadge(savingsConfig) {
    const badge = dom.query("[data-savings-features-badge]");
    if (!badge) return;
    let count = 0;
    Object.values(savingsConfig || {}).forEach((cfg) => {
      if (cfg.cashback_enabled) count++;
      if (cfg.roundup_enabled) count++;
    });
    if (count > 0) {
      badge.textContent = count;
      badge.style.display = "inline";
    } else {
      badge.style.display = "none";
    }
  }

  /* ── Tabs ── */

  dom.queryAll("[data-wallet-tab]").forEach((tab) => {
    dom.on(tab, "click", () => {
      const target = tab.dataset.walletTab;
      dom.queryAll("[data-wallet-tab]").forEach((t) => {
        t.classList.remove("wallet-tab--active");
        t.setAttribute("aria-selected", "false");
      });
      tab.classList.add("wallet-tab--active");
      tab.setAttribute("aria-selected", "true");

      dom.queryAll("[data-wallet-panel]").forEach((p) => {
        p.style.display = p.dataset.walletPanel === target ? "" : "none";
        p.classList.toggle("wallet-panel--active", p.dataset.walletPanel === target);
      });

      state.currentWallet = target;
    });
  });

  /* ── Transfer Modals ── */

  function openTransferModal(type, presetCurrency) {
    const modal = dom.query(`[data-modal="${type}"]`);
    if (!modal) return;
    modal.style.display = "flex";

    const currencySelect = modal.querySelector("[data-transfer-currency]");
    if (currencySelect && presetCurrency) {
      currencySelect.value = presetCurrency;
    }

    const errorEl = modal.querySelector("[data-transfer-error]");
    if (errorEl) errorEl.style.display = "none";

    if (type === "from-savings") {
      const cfg = state.savingsConfig[presetCurrency || "CDF"] || {};
      const statusEl = modal.querySelector("[data-from-savings-status]");
      if (statusEl) {
        if (cfg.is_locked) {
          statusEl.innerHTML = '<div class="savings-config__info savings-config__info--warning"><i class="fa-solid fa-lock"></i> Épargne bloquée. Les frais de déblocage anticipé s\'appliquent.</div>';
        } else if (cfg.mode === "flexible") {
          const used = cfg.withdrawals_this_month || 0;
          const limit = cfg.flexible_withdrawals_per_month || 2;
          if (used >= limit) {
            statusEl.innerHTML = '<div class="savings-config__info savings-config__info--warning"><i class="fa-solid fa-triangle-exclamation"></i> Limite de ' + limit + ' retraits mensuels atteinte.</div>';
          } else {
            statusEl.innerHTML = '<div class="savings-config__info"><i class="fa-solid fa-check-circle"></i> Retrait flexible disponible (' + (limit - used) + '/' + limit + ' restants).</div>';
          }
        }
      }
    }
  }

  function closeAllModals() {
    dom.queryAll("[data-modal]").forEach((m) => { m.style.display = "none"; });
  }

  dom.queryAll("[data-modal-close]").forEach((btn) => {
    dom.on(btn, "click", closeAllModals);
  });

  dom.queryAll("[data-modal]").forEach((modal) => {
    dom.on(modal, "click", (e) => {
      if (e.target === modal) closeAllModals();
    });
  });

  dom.queryAll("[data-transfer-confirm]").forEach((btn) => {
    dom.on(btn, "click", () => {
      const modal = btn.closest("[data-modal]");
      if (!modal) return;
      const type = modal.dataset.modal;
      const currency = modal.querySelector("[data-transfer-currency]").value;
      const amount = parseInt(modal.querySelector("[data-transfer-amount]").value) || 0;
      const pin = modal.querySelector("[data-transfer-pin]").value;
      const errorEl = modal.querySelector("[data-transfer-error]");

      if (amount <= 0) {
        if (errorEl) { errorEl.textContent = "Montant invalide."; errorEl.style.display = "block"; }
        return;
      }
      if (!/^\d{4}$/.test(pin)) {
        if (errorEl) { errorEl.textContent = "Le PIN doit contenir 4 chiffres."; errorEl.style.display = "block"; }
        return;
      }

      const endpoint = type === "to-savings" ? "/app/wallet/transfer-to-savings" : "/app/wallet/transfer-from-savings";

      api.post(endpoint, { amount, currency, pin }).then((res) => {
        closeAllModals();
        dom.showToast(res.data.data.message || "Opération réussie.", "success");
        loadWallet();
      }).catch((err) => {
        const msg = (err.response && err.response.data && (err.response.data.error ? err.response.data.error.message : err.response.data.message)) || "Erreur lors du transfert.";
        if (errorEl) { errorEl.textContent = msg; errorEl.style.display = "block"; }
      });
    });
  });

  /* ── Quick actions ── */

  dom.on(dom.query("[data-action='deposit']"), "click", () => {
    windowObject.location.assign("/transactions");
  });
  dom.on(dom.query("[data-action='withdraw']"), "click", () => {
    windowObject.location.assign("/transactions");
  });
  dom.on(dom.query("[data-action='convert']"), "click", () => {
    const widget = dom.query("[data-conversion-widget]");
    widget.style.display = widget.style.display === "none" ? "" : "none";
  });
  dom.on(dom.query("[data-conversion-close]"), "click", () => {
    dom.query("[data-conversion-widget]").style.display = "none";
  });
  dom.on(dom.query("[data-action='to-savings']"), "click", () => openTransferModal("to-savings", "CDF"));
  dom.on(dom.query("[data-action='from-savings']"), "click", () => openTransferModal("from-savings", "CDF"));
  dom.on(dom.query("[data-action='to-savings-quick']"), "click", () => openTransferModal("to-savings", "CDF"));

  /* ── Conversion widget ── */

  dom.on(dom.query("[data-conversion-swap]"), "click", () => {
    const from = dom.query("[data-conversion-from]");
    const to = dom.query("[data-conversion-to]");
    const tmp = from.value;
    from.value = to.value;
    to.value = tmp;
    updateConversion();
  });

  dom.on(dom.query("[data-conversion-amount]"), "input", updateConversion);
  dom.on(dom.query("[data-conversion-from]"), "change", updateConversion);
  dom.on(dom.query("[data-conversion-to]"), "change", updateConversion);

  function updateConversion() {
    const from = dom.query("[data-conversion-from]").value;
    const to = dom.query("[data-conversion-to]").value;
    const amount = parseFloat(dom.query("[data-conversion-amount]").value) || 0;
    const result = dom.query("[data-conversion-result]");

    if (amount <= 0) {
      result.innerHTML = "<span>Entrez un montant pour voir la conversion</span>";
      return;
    }

    let rate;
    if (from === "CDF" && to === "USD") {
      rate = 0.00048;
    } else if (from === "USD" && to === "CDF") {
      rate = 2100;
    } else {
      rate = 1;
    }

    const converted = (amount * rate).toLocaleString("fr-FR", {
      maximumFractionDigits: from === "CDF" ? 2 : 0,
    });
    result.innerHTML = `<strong>${amount.toLocaleString("fr-FR")} ${from} = ${converted} ${to}</strong>
      <span style="display:block;font-size:13px;font-weight:400;color:var(--color-muted);margin-top:4px">Taux: 1 ${from} = ${rate} ${to}</span>`;
  }

  dom.on(dom.query("[data-conversion-execute]"), "click", () => {
    const from = dom.query("[data-conversion-from]").value;
    const to = dom.query("[data-conversion-to]").value;
    const amount = parseFloat(dom.query("[data-conversion-amount]").value);

    if (!amount || amount <= 0) {
      dom.showToast("Veuillez saisir un montant valide.", "error");
      return;
    }

    dom.showToast("Conversion simulée traitée avec succès.", "success");
    dom.query("[data-conversion-widget]").style.display = "none";
    dom.query("[data-conversion-amount]").value = "";
    loadWallet();
  });

  /* ── Movements ── */

  function renderMovements(movements) {
    const container = dom.query("[data-wallet-movements]");
    const badge = dom.query("[data-movement-count]");

    if (!movements || !movements.length) {
      container.innerHTML = '<div class="movement-empty"><i class="fa-solid fa-receipt"></i><span>Aucun mouvement récent.</span></div>';
      if (badge) badge.textContent = "0";
      return;
    }

    if (badge) badge.textContent = movements.length;

    container.innerHTML = movements.map((m) => {
      const iconType = typeIcon(m.type);
      return `<div class="movement-item">
        <div class="movement-item__icon movement-item__icon--${iconType}">
          <i class="fa-solid fa-${iconType === "in" ? "circle-down" : iconType === "out" ? "circle-up" : iconType === "bill" ? "file-invoice" : iconType === "convert" ? "arrows-rotate" : "circle"}"></i>
        </div>
        <div class="movement-item__info">
          <p>${typeLabel(m.type)}</p>
          <span>${m.reference || ""}${m.recipient_name ? " · " + m.recipient_name : ""}${m.created_at ? " · " + new Date(m.created_at).toLocaleDateString("fr-FR", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" }) : ""}</span>
        </div>
        <div class="movement-item__amount">
          <strong class="${typeAmountClass(m.type)}">${["deposit", "deposit_agent", "deposit_mobile_money", "deposit_bank"].includes(m.type) ? "+" : "-"}${money(m.total_amount, m.currency)}</strong>
          ${statusBadge(m.status)}
        </div>
      </div>`;
    }).join("");
  }

  /* ── Init ── */

  documentObject.addEventListener("DOMContentLoaded", () => {
    loadWallet();
  });
})(window, document);
