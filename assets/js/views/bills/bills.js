(function bootBills(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function money(cents, currency) {
    return `${(Number(cents || 0) / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 })} ${currency}`;
  }

  documentObject.addEventListener("DOMContentLoaded", () => {
    dom.on(dom.query("[data-bill-form]"), "submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const button = dom.query("button[type='submit']", form);
      dom.setSubmitting(button, true, "Vérification...");
      try {
        const response = await api.post("/app/bills/verify", dom.serializeForm(form));
        const bill = response.data.data.bill;
        dom.query("[data-bill-result]").innerHTML = `<div><span>${bill.service} · ${bill.reference}</span><strong>${money(bill.amount, bill.currency)}</strong></div><div><span>${bill.customer_name}</span><strong>${bill.status}</strong></div>`;
        dom.showToast("Facture vérifiée.", "success");
      } catch (error) {
        dom.showToast("Facture introuvable.", "error");
      } finally {
        dom.setSubmitting(button, false);
      }
    });
  });
})(window, document);
