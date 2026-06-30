(function bootAdmin(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  function badgeIcon(badge) {
    if (badge === "gold") return '<i class="fa-solid fa-star" style="color:gold" title="Doré"></i> Doré';
    if (badge === "silver") return '<i class="fa-solid fa-star" style="color:silver" title="Argenté"></i> Argenté';
    return '<span style="color:var(--color-subtle)">—</span>';
  }

  documentObject.addEventListener("DOMContentLoaded", async () => {
    try {
      const response = await api.get("/app/admin/overview");
      const data = response.data.data;
      dom.query("[data-admin-users]").textContent = data.users.toLocaleString("fr-FR");
      dom.query("[data-admin-agents]").textContent = data.agents.toLocaleString("fr-FR");
      dom.query("[data-admin-transactions]").textContent = data.transactions.toLocaleString("fr-FR");
      dom.query("[data-admin-volume]").textContent = (data.volume / 100).toLocaleString("fr-FR");
      dom.query("[data-admin-silver]").textContent = data.silverCount.toLocaleString("fr-FR");
      dom.query("[data-admin-gold]").textContent = data.goldCount.toLocaleString("fr-FR");

      const tbody = dom.query("[data-admin-badge-rows]");
      if (tbody && data.badgeData) {
        tbody.innerHTML = data.badgeData.length
          ? data.badgeData.map(u => `
            <tr style="border-bottom:1px solid var(--color-border)">
              <td style="padding:0.5rem">${u.full_name}</td>
              <td style="padding:0.5rem">${u.afric_number}</td>
              <td style="padding:0.5rem">${badgeIcon(u.badge)}</td>
              <td style="padding:0.5rem"><strong>${u.trust_score}</strong>/1000</td>
            </tr>`).join("")
          : '<tr><td colspan="4" style="padding:1rem;text-align:center;color:var(--color-subtle)">Aucun utilisateur</td></tr>';
      }
    } catch (error) {
      dom.showToast("Console admin indisponible.", "error");
    }
  });
})(window, document);
