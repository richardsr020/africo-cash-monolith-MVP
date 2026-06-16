(function bootBills(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function money(cents, currency) {
    return `${Number(cents || 0).toLocaleString("fr-FR")} ${currency}`;
  }

  function el(selector, ctx) {
    return (ctx || documentObject).querySelector(selector);
  }

  function show(sel) { const e = el(sel); if (e) e.style.display = ''; }
  function hide(sel) { const e = el(sel); if (e) e.style.display = 'none'; }

  let lastVerifiedBill = null;

  documentObject.addEventListener("DOMContentLoaded", () => {
    // Service buttons → fill form
    documentObject.querySelectorAll(".service-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const svc = btn.dataset.service;
        const ref = el("[data-bill-form] input[name='reference']");
        const svcInput = el("[data-bill-form] input[name='service']");
        if (svcInput) svcInput.value = svc;
        if (ref) ref.focus();
        el("[data-bill-panel]").scrollIntoView({ behavior: "smooth" });
      });
    });

    // Verify bill
    dom.on(el("[data-bill-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const btn = el("button[type='submit']", form);
      dom.setSubmitting(btn, true, "Vérification...");
      hide("[data-bill-confirm]");
      hide("[data-bill-success]");

      try {
        const fd = new FormData(form);
        const response = await api.post("/app/bills/verify", Object.fromEntries(fd));
        const bill = response.data.data.bill;

        lastVerifiedBill = bill;

        el("[data-bill-result]").innerHTML = `
          <div><span>Service</span><strong>${bill.service}</strong></div>
          <div><span>Référence</span><strong>${bill.reference}</strong></div>
          <div><span>Client</span><strong>${bill.customer_name}</strong></div>
          <div><span>Montant</span><strong class="bill-amount">${money(bill.amount, bill.currency)}</strong></div>
          <div><span>Échéance</span><strong>${bill.due_date || 'N/A'}</strong></div>
          <div><span>Statut</span><strong class="bill-status">Vérifié</strong></div>
        `;

        el("[data-panel-title]").textContent = `Paiement ${bill.service}`;
        el("[data-panel-desc]").textContent = `Facture ${bill.reference} — ${money(bill.amount, bill.currency)}`;
        show("[data-bill-confirm]");
        dom.showToast("Facture vérifiée.", "success");
      } catch (error) {
        el("[data-bill-result]").innerHTML = `
          <div style="color:var(--color-danger);justify-content:center">
            <i class="fa-solid fa-circle-exclamation"></i> Facture introuvable. Vérifiez la référence.
          </div>`;
        dom.showToast("Facture introuvable.", "error");
      } finally {
        dom.setSubmitting(btn, false);
      }
    });

    // Pay bill
    dom.on(el("[data-bill-pay]"), "click", async () => {
      if (!lastVerifiedBill) return;

      const pin = el("#billPin");
      if (!pin || !pin.value || pin.value.length !== 4) {
        dom.showToast("Veuillez entrer votre code PIN à 4 chiffres.", "error");
        pin?.focus();
        return;
      }

      const payBtn = el("[data-bill-pay]");
      payBtn.disabled = true;
      payBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Paiement en cours...';

      try {
        const response = await api.post("/app/bills/pay", {
          reference: lastVerifiedBill.reference,
          service: lastVerifiedBill.service,
          amount: lastVerifiedBill.amount,
          currency: lastVerifiedBill.currency,
          pin: pin.value,
        });

        const data = response.data.data;

        hide("[data-bill-confirm]");
        el("[data-bill-result]").innerHTML = '';

        el("[data-pay-success-detail]").innerHTML = `
          <div><span>Service</span><strong>${data.service}</strong></div>
          <div><span>Référence</span><strong>${data.bill_reference}</strong></div>
          <div><span>Montant</span><strong class="bill-amount">${money(data.amount, data.currency)}</strong></div>
          <div><span>Réf. transaction</span><strong>${data.reference}</strong></div>
        `;
        el("[data-pay-success-msg]").textContent = `Votre facture ${data.service} a été payée avec succès.`;
        show("[data-bill-success]");

        dom.showToast("Paiement effectué !", "success");
      } catch (error) {
        dom.showToast(error.response?.data?.error?.message || "Erreur de paiement.", "error");
      } finally {
        payBtn.disabled = false;
        payBtn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Confirmer et payer';
      }
    });

    // Reset after pay
    dom.on(el("[data-bill-reset]"), "click", () => {
      lastVerifiedBill = null;
      hide("[data-bill-success]");
      hide("[data-bill-confirm]");
      el("[data-bill-result]").innerHTML = '';
      el("[data-bill-form]").reset();
      el("#billPin").value = '';
      el("[data-panel-title]").textContent = 'Vérification préalable';
      el("[data-panel-desc]").textContent = 'Entrez la référence de votre facture pour vérifier son montant avant de payer.';
    });

    // ── Auto-payment ──

    // Load existing
    async function loadAutoPayments() {
      try {
        const response = await api.get("/app/bills/auto-pay");
        const list = response.data.data.auto_payments;
        const container = el("[data-auto-list]");

        if (!list || list.length === 0) {
          container.innerHTML = '<div class="auto-empty"><i class="fa-solid fa-clock"></i> Aucun prélèvement automatique configuré.</div>';
          return;
        }

        container.innerHTML = list.map(ap => `
          <div class="auto-item">
            <div class="auto-item-info">
              <strong>${ap.service_type}</strong>
              <span>${ap.customer_reference} · ${ap.frequency === 'monthly' ? 'Mensuel' : ap.frequency} · Jour ${ap.day_of_month}</span>
              ${ap.amount ? `<span>Montant: ${money(ap.amount, ap.currency)}</span>` : '<span>Montant variable</span>'}
              ${ap.next_pay_at ? `<span>Prochain: ${new Date(ap.next_pay_at).toLocaleDateString('fr-FR')}</span>` : ''}
            </div>
            <div class="auto-item-actions">
              <span class="auto-status ${ap.is_active ? 'active' : 'inactive'}">${ap.is_active ? 'Actif' : 'Inactif'}</span>
              <button class="btn btn-soft btn-sm" data-toggle-auto="${ap.id}">
                <i class="fa-solid ${ap.is_active ? 'fa-pause' : 'fa-play'}"></i>
              </button>
              <button class="btn btn-soft btn-sm btn-danger-soft" data-delete-auto="${ap.id}">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </div>
        `).join('');
      } catch (e) {
        // silent
      }
    }

    // Create
    dom.on(el("[data-auto-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const btn = el("button[type='submit']", form);
      dom.setSubmitting(btn, true, "Enregistrement...");

      try {
        const fd = new FormData(form);
        const payload = Object.fromEntries(fd);
        if (payload.amount === '') payload.amount = null;
        if (payload.max_amount === '') payload.max_amount = null;

        await api.post("/app/bills/auto-pay", payload);
        dom.showToast("Prélèvement automatique activé.", "success");
        form.reset();
        loadAutoPayments();
      } catch (error) {
        dom.showToast(error.response?.data?.error?.message || "Erreur.", "error");
      } finally {
        dom.setSubmitting(btn, false);
      }
    });

    // Toggle / Delete (event delegation)
    dom.on(el("[data-auto-list]"), "click", async (event) => {
      const toggleBtn = event.target.closest("[data-toggle-auto]");
      if (toggleBtn) {
        const id = toggleBtn.dataset.toggleAuto;
        try {
          await api.post(`/app/bills/auto-pay/${id}`);
          loadAutoPayments();
        } catch (e) {
          dom.showToast("Erreur.", "error");
        }
        return;
      }

      const deleteBtn = event.target.closest("[data-delete-auto]");
      if (deleteBtn) {
        const id = deleteBtn.dataset.deleteAuto;
        if (!confirm("Supprimer ce prélèvement automatique ?")) return;
        try {
          await api.delete(`/app/bills/auto-pay/${id}`);
          dom.showToast("Prélèvement supprimé.", "success");
          loadAutoPayments();
        } catch (e) {
          dom.showToast("Erreur.", "error");
        }
      }
    });

    loadAutoPayments();
  });
})(window, document);
