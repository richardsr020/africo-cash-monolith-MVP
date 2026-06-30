(function bootBanking(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function money(cents, currency) {
    return `${(Number(cents || 0) / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 })} ${currency}`;
  }

  function el(sel, ctx) {
    return (ctx || documentObject).querySelector(sel);
  }

  function elAll(sel, ctx) {
    return (ctx || documentObject).querySelectorAll(sel);
  }

  /* ── Tab switching ── */

  let currentTab = "partner";

  documentObject.addEventListener("click", (e) => {
    const tab = e.target.closest("[data-tab]");
    if (!tab) return;

    const tabName = tab.dataset.tab;
    if (tabName === currentTab) return;

    elAll(".banking-tab").forEach((t) => t.classList.remove("is-active"));
    tab.classList.add("is-active");

    elAll("[data-tab-content]").forEach((p) => (p.style.display = "none"));
    const content = el(`[data-tab-content="${tabName}"]`);
    if (content) content.style.display = "";

    currentTab = tabName;
  });

  /* ── Partner bank selection ── */

  elAll(".partner-card").forEach((card) => {
    card.addEventListener("click", () => {
      elAll(".partner-card").forEach((c) => c.classList.remove("is-selected"));
      card.classList.add("is-selected");

      const bankName = card.dataset.partner;
      const bankInput = el("[data-partner-bank]");
      const displayInput = el("[data-partner-display]");
      if (bankInput) bankInput.value = bankName;
      if (displayInput) displayInput.value = bankName;
    });
  });

  /* ── Helper: show confirm modal ── */

  const confirmModal = el("[data-banking-confirm-modal]");
  let pendingConfirm = null;

  function showConfirm(title, fields, confirmFn) {
    const detail = el("[data-banking-confirm-detail]");
    detail.innerHTML = fields
      .map((f) => `<div><span>${f.label}</span><strong>${f.value}</strong></div>`)
      .join("");

    const submitBtn = el("[data-confirm-banking-submit]");
    const newBtn = submitBtn.cloneNode(true);
    submitBtn.parentNode.replaceChild(newBtn, submitBtn);

    newBtn.addEventListener("click", async () => {
      newBtn.disabled = true;
      newBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Envoi...';
      try {
        await confirmFn();
        confirmModal.close();
      } catch (_e) {
        newBtn.disabled = false;
        newBtn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Confirmer le virement';
      }
    });

    confirmModal.showModal();
  }

  elAll("[data-close-banking-confirm]").forEach((btn) => {
    btn.addEventListener("click", () => confirmModal?.close());
  });

  /* ── Partner transfer ── */

  dom.on(el("[data-partner-form]"), "submit", async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const fd = new FormData(form);
    const bank = fd.get("bank");
    const account = fd.get("account");
    const amount = fd.get("amount");
    const currency = fd.get("currency");
    const holder = fd.get("holder") || "";

    if (!bank) {
      dom.showToast("Sélectionnez une banque partenaire.", "error");
      return;
    }

    showConfirm(
      "Virement banque partenaire",
      [
        { label: "Banque", value: bank },
        { label: "Compte", value: account },
        { label: "Montant", value: money(amount, currency) },
        { label: "Titulaire", value: holder || "Non spécifié" },
        { label: "Frais", value: "1,5%" },
      ],
      async () => {
        const response = await api.post("/app/banking/partner-transfer", {
          bank,
          account,
          amount: parseInt(amount, 10),
          currency,
          holder,
        });
        const data = response.data.data;
        dom.showToast(`Virement ${data.reference} effectué.`, "success");
        form.reset();
        elAll(".partner-card").forEach((c) => c.classList.remove("is-selected"));
        el("[data-partner-display]").value = "";
        loadHistory();
      }
    );
  });

  /* ── External transfer ── */

  dom.on(el("[data-external-form]"), "submit", async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const fd = new FormData(form);
    const bank = fd.get("bank");
    const swift = fd.get("swift");
    const account = fd.get("account");
    const holder = fd.get("holder");
    const amount = fd.get("amount");
    const currency = fd.get("currency");
    const saveBenef = fd.get("save_beneficiary") === "1";

    showConfirm(
      "Virement externe",
      [
        { label: "Banque", value: bank },
        { label: "SWIFT", value: swift || "—" },
        { label: "Compte", value: account },
        { label: "Titulaire", value: holder },
        { label: "Montant", value: money(amount, currency) },
        { label: "Frais", value: "2,5%" },
      ],
      async () => {
        const response = await api.post("/app/banking/external-transfer", {
          bank,
          swift,
          account,
          holder,
          amount: parseInt(amount, 10),
          currency,
        });
        const data = response.data.data;
        dom.showToast(`Virement externe ${data.reference} effectué.`, "success");

        if (saveBenef) {
          try {
            await api.post("/app/banking/beneficiaries", {
              bank_name: bank,
              account_number: account,
              account_holder: holder,
              swift_code: swift || "",
            });
            loadBeneficiaries();
          } catch (_e) {
            /* silent */
          }
        }

        form.reset();
        loadHistory();
      }
    );
  });

  /* ── Beneficiaries ── */

  async function loadBeneficiaries() {
    try {
      const response = await api.get("/app/banking/beneficiaries");
      const list = response.data.data.beneficiaries || [];
      const container = el("[data-benef-list]");
      const badge = el("[data-benef-count]");
      if (badge) badge.textContent = list.length;

      if (!list.length) {
        container.innerHTML =
          '<div class="benef-empty"><i class="fa-solid fa-users"></i><span>Aucun bénéficiaire enregistré.</span></div>';
        return;
      }

      container.innerHTML = list
        .map(
          (b, idx) =>
            `<div class="benef-item" data-benef-idx="${idx}">
              <div class="benef-item-info">
                <strong>${b.account_holder}</strong>
                <span>${b.bank_name} · ${b.account_number}</span>
              </div>
              <div style="display:flex;align-items:center;gap:0.35rem;flex-shrink:0">
                ${b.is_partner ? '<span class="benef-item-badge">Partenaire</span>' : ""}
                <button class="btn btn-soft benef-transfer-btn" data-benef-transfer="${b.account_number}" data-benef-bank="${b.bank_name}" data-benef-holder="${b.account_holder}" type="button">
                  <i class="fa-solid fa-paper-plane"></i>
                </button>
                <button class="btn btn-soft benef-delete-btn" data-benef-delete="${b.id}" type="button">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </div>`
        )
        .join("");
    } catch (_e) {
      /* silent */
    }
  }

  documentObject.addEventListener("click", async (e) => {
    // Transfer from beneficiary
    const transferBtn = e.target.closest("[data-benef-transfer]");
    if (transferBtn) {
      const bank = transferBtn.dataset.benefBank;
      const account = transferBtn.dataset.benefAccount;
      const holder = transferBtn.dataset.benefHolder;

      el("[data-tab='partner']").click();
      const partnerCard = el(`.partner-card[data-partner="${bank}"]`);
      if (partnerCard) {
        partnerCard.click();
      } else {
        el("[data-tab='external']").click();
        const extForm = el("[data-external-form]");
        extForm.querySelector("[name='bank']").value = bank;
        extForm.querySelector("[name='account']").value = account;
        extForm.querySelector("[name='holder']").value = holder;
      }
      return;
    }

    // Delete beneficiary
    const deleteBtn = e.target.closest("[data-benef-delete]");
    if (deleteBtn) {
      const id = deleteBtn.dataset.benefDelete;
      if (!confirm("Supprimer ce bénéficiaire ?")) return;
      try {
        await api.delete(`/app/banking/beneficiaries/${id}`);
        dom.showToast("Bénéficiaire supprimé.", "success");
        loadBeneficiaries();
      } catch (err) {
        dom.showToast(
          err.response?.data?.error?.message || "Erreur.",
          "error"
        );
      }
    }
  });

  /* ── History ── */

  async function loadHistory() {
    try {
      const response = await api.get("/app/banking/history");
      const txns = response.data.data.transactions || [];
      const container = el("[data-banking-history]");

      if (!txns.length) {
        container.innerHTML =
          '<div class="benef-empty"><i class="fa-solid fa-arrow-right-arrow-left"></i><span>Aucun virement effectué récemment.</span></div>';
        return;
      }

      container.innerHTML = txns
        .map(
          (t) =>
            `<div class="banking-history-item">
              <div class="banking-history-info">
                <strong>${t.provider_name || "Banque"}</strong>
                <span>${t.recipient_name || ""} · ${t.recipient_account || ""}</span>
                <span style="font-size:0.7rem;color:var(--color-muted)">${t.transaction_reference} · ${new Date(t.created_at).toLocaleDateString("fr-FR", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" })}</span>
              </div>
              <div class="banking-history-amount">
                -${money(t.amount, t.currency)}
                ${t.fees > 0 ? `<span>Frais: ${money(t.fees, t.currency)}</span>` : ""}
              </div>
            </div>`
        )
        .join("");
    } catch (_e) {
      /* silent */
    }
  }

  /* ── Init ── */

  documentObject.addEventListener("DOMContentLoaded", () => {
    loadBeneficiaries();
    loadHistory();
  });
})(window, document);
