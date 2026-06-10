(function bootAdmin(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;
  documentObject.addEventListener("DOMContentLoaded", async () => {
    try {
      const response = await api.get("/app/admin/overview");
      const data = response.data.data;
      dom.query("[data-admin-users]").textContent = data.users.toLocaleString("fr-FR");
      dom.query("[data-admin-agents]").textContent = data.agents.toLocaleString("fr-FR");
      dom.query("[data-admin-transactions]").textContent = data.transactions.toLocaleString("fr-FR");
      dom.query("[data-admin-volume]").textContent = (data.volume / 100).toLocaleString("fr-FR");
    } catch (error) {
      dom.showToast("Console admin indisponible.", "error");
    }
  });
})(window, document);
