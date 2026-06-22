(function bootWallet(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

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
    };
    return labels[type] || type;
  }

  function typeIcon(type) {
    if (["deposit", "deposit_agent", "deposit_mobile_money", "deposit_bank"].includes(type)) return "in";
    if (["send", "withdraw", "withdraw_agent", "withdraw_bank", "withdraw_atm", "send_bank"].includes(type)) return "out";
    if (type === "bill") return "bill";
    if (type === "conversion") return "convert";
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
    dom.query("[data-wallet-balances]").innerHTML =
      '<div class="wallet-balance-skeleton"><div class="skeleton-card"></div><div class="skeleton-card"></div></div>';
    dom.query("[data-wallet-movements]").innerHTML =
      '<div class="movement-skeleton"><div class="skeleton-row"></div><div class="skeleton-row"></div><div class="skeleton-row"></div></div>';

    api.get("/app/wallet").then((response) => {
      const { accounts, movements } = response.data.data;
      renderBalances(accounts);
      renderMovements(movements);
    }).catch(() => dom.showToast("Portefeuille indisponible.", "error"));
  }

  function renderBalances(accounts) {
    const container = dom.query("[data-wallet-balances]");
    if (!accounts.length) {
      container.innerHTML = '<div class="wallet-balance-card" style="text-align:center;padding:2rem;color:var(--color-muted)"><span>Aucun compte trouvé.</span></div>';
      return;
    }

    const total = accounts.reduce((sum, a) => sum + moneyRaw(a.balance), 0);
    const totalFormatted = accounts.length === 1
      ? money(accounts[0].balance, accounts[0].currency)
      : `${total.toLocaleString("fr-FR", { maximumFractionDigits: 2 })} (total)`;
    dom.query("[data-total-balance]").textContent = totalFormatted;

    container.innerHTML = accounts.map((account, idx) => {
      const isPrimary = idx === 0;
      const bars = Array.from({ length: 7 }, (_, i) => {
        const h = 20 + ((parseInt(account.balance) + i * 7919) % 60);
        return `<span class="bar--${i % 2 === 0 ? "income" : "income"}" style="height:${h}%;animation-delay:${(idx * 7 + i) * 60}ms"></span>`;
      }).join("");

      return `<div class="wallet-balance-card ${isPrimary ? "wallet-balance-card--primary" : ""}">
        <div class="wallet-balance-card__info">
          <span class="wallet-balance-card__currency">${account.currency}</span>
          <strong class="wallet-balance-card__amount">${money(account.balance, account.currency)}</strong>
          <span class="wallet-balance-card__meta">${idx === 0 ? "Compte principal" : "Compte secondaire"}</span>
        </div>
        <div class="wallet-balance-card__chart">${bars}</div>
        <div class="wallet-balance-card__actions">
          <button type="button" data-quick-deposit="${account.currency}"><i class="fa-solid fa-circle-down"></i> Dépôt</button>
          <button type="button" data-quick-withdraw="${account.currency}"><i class="fa-solid fa-circle-up"></i> Retrait</button>
          <button type="button" data-quick-transfer="${account.currency}"><i class="fa-solid fa-paper-plane"></i> Envoyer</button>
        </div>
      </div>`;
    }).join("");

    dom.queryAll("[data-quick-deposit], [data-quick-withdraw], [data-quick-transfer]").forEach((btn) => {
      dom.on(btn, "click", () => {
        windowObject.location.assign("/transactions");
      });
    });
  }

  function renderMovements(movements) {
    const container = dom.query("[data-wallet-movements]");
    const badge = dom.query("[data-movement-count]");

    if (!movements.length) {
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
  dom.on(dom.query("[data-action='stats']"), "click", () => {
    windowObject.location.assign("/dashboard");
  });

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

  /* ── Init ── */

  documentObject.addEventListener("DOMContentLoaded", () => {
    loadWallet();
  });
})(window, document);
