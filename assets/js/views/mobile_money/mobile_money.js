(function bootMobileMoney(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function renderLinked(accounts) {
    dom.query("[data-linked-mobile-money]").innerHTML = accounts.length
      ? accounts.map((account) => `<div><span>${account.provider}</span><strong>${account.account_reference}</strong></div>`).join("")
      : "<div><span>Aucun opérateur connecté</span><strong>--</strong></div>";
  }

  documentObject.addEventListener("DOMContentLoaded", async () => {
    try {
      const response = await api.get("/app/mobile-money");
      renderLinked(response.data.data.linked_accounts);
    } catch (error) {
      dom.showToast("Mobile Money indisponible.", "error");
    }

    dom.queryAll("[data-provider]").forEach((button) => {
      dom.on(button, "click", async () => {
        dom.setSubmitting(button, true, "Connexion...");
        try {
          const response = await api.post("/app/mobile-money/link", { provider: button.dataset.provider });
          dom.showToast(`${button.dataset.provider} connecté.`, "success");
          renderLinked([response.data.data.linked_account]);
        } catch (error) {
          dom.showToast("Connexion opérateur impossible.", "error");
        } finally {
          dom.setSubmitting(button, false);
        }
      });
    });
  });
})(window, document);
