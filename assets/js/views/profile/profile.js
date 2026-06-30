(function bootProfile(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  let userData = null;
  let prefsData = null;

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

  let currentTab = "info";

  documentObject.addEventListener("click", (e) => {
    const tab = e.target.closest(".profile-tab");
    if (!tab) return;
    const name = tab.dataset.tab;
    if (name === currentTab) return;

    elAll(".profile-tab").forEach((t) => t.classList.remove("is-active"));
    tab.classList.add("is-active");

    elAll("[data-tab-content]").forEach((p) => (p.style.display = "none"));
    const content = el(`[data-tab-content="${name}"]`);
    if (content) content.style.display = "";

    currentTab = name;
  });

  /* ── Load profile ── */

  async function loadProfile() {
    try {
      const response = await api.get("/app/profile");
      userData = response.data.data.user;
      renderProfile();
    } catch (_e) {
      dom.showToast("Profil indisponible.", "error");
    }
  }

  function renderProfile() {
    if (!userData) return;
    const u = userData;
    const onboarding = u.onboarding || {};
    const initials = u.full_name
      ? u.full_name
          .split(" ")
          .map((w) => w[0])
          .join("")
          .toUpperCase()
          .slice(0, 2)
      : "--";

    el("[data-profile-initials]").textContent = initials;
    el("[data-profile-display-name]").textContent = onboarding.preferred_name || u.full_name;
    el("[data-profile-tag]").textContent = `Membre depuis ${u.created_at ? new Date(u.created_at).toLocaleDateString("fr-FR", { year: "numeric", month: "long" }) : "---"} · ${u.afric_number || ""}`;

    // Badges
    const kycBadge = el("[data-profile-kyc-badge]");
    if (u.is_verified) {
      kycBadge.textContent = "Identité vérifiée";
      kycBadge.className = "profile-badge verified";
    } else {
      kycBadge.textContent = "Identité en cours";
      kycBadge.className = "profile-badge pending";
    }

    const roleBadge = el("[data-profile-role-badge]");
    roleBadge.textContent =
      u.role === "admin" ? "Administrateur" : u.role === "agent" ? "Agent" : "Utilisateur";

    // Info form
    el("[data-field='full_name']").value = u.full_name || "";
    el("[data-field='email']").value = u.email || "";
    el("[data-field='afric_number']").value = u.afric_number || "";
    el("[data-field='phone']").value = u.notification_phone || "";
    el("[data-field='city']").value = onboarding.city || "";
    el("[data-field='profession']").value = onboarding.profession || "";
    el("[data-field='address']").value = onboarding.address || "";
  }

  /* ── Save profile ── */

  dom.on(el("[data-profile-form]"), "submit", async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const btn = el("button[type='submit']", form);

    dom.setSubmitting(btn, true, "Enregistrement...");
    try {
      await api.post("/app/profile", dom.serializeForm(form));
      dom.showToast("Profil mis à jour.", "success");
      loadProfile();
    } catch (err) {
      dom.showToast(err.response?.data?.error?.message || "Erreur.", "error");
    } finally {
      dom.setSubmitting(btn, false);
    }
  });

  /* ── Change password ── */

  dom.on(el("[data-change-password-form]"), "submit", async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const btn = el("button[type='submit']", form);

    dom.setSubmitting(btn, true, "Modification...");
    try {
      const response = await api.post("/app/profile/change-password", dom.serializeForm(form));
      dom.showToast(response.data.data.message || "Mot de passe modifié.", "success");
      form.reset();
    } catch (err) {
      dom.showToast(err.response?.data?.error?.message || "Erreur.", "error");
    } finally {
      dom.setSubmitting(btn, false);
    }
  });

  /* ── Change PIN ── */

  dom.on(el("[data-change-pin-form]"), "submit", async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const btn = el("button[type='submit']", form);

    dom.setSubmitting(btn, true, "Modification...");
    try {
      const response = await api.post("/app/profile/change-pin", dom.serializeForm(form));
      dom.showToast(response.data.data.message || "PIN modifié.", "success");
      form.reset();
    } catch (err) {
      dom.showToast(err.response?.data?.error?.message || "Erreur.", "error");
    } finally {
      dom.setSubmitting(btn, false);
    }
  });

  /* ── 2FA Toggle ── */

  async function load2FA() {
    if (!prefsData) return;
    const enabled = prefsData.two_factor_enabled;
    const indicator = el("[data-2fa-indicator]");
    const btnText = el("[data-2fa-btn-text]");
    const badge = el("[data-profile-2fa-badge]");

    if (enabled) {
      indicator.textContent = "Activé";
      indicator.className = "profile-2fa-indicator enabled";
      btnText.textContent = "Désactiver";
      badge.style.display = "";
    } else {
      indicator.textContent = "Désactivé";
      indicator.className = "profile-2fa-indicator disabled";
      btnText.textContent = "Activer";
      badge.style.display = "none";
    }
  }

  el("[data-2fa-toggle]")?.addEventListener("click", async () => {
    const enabled = prefsData?.two_factor_enabled;
    const endpoint = enabled ? "/app/profile/two-factor/disable" : "/app/profile/two-factor/enable";

    try {
      await api.post(endpoint);
      prefsData.two_factor_enabled = !enabled;
      load2FA();
      dom.showToast(enabled ? "2FA désactivé." : "2FA activé !", "success");
    } catch (err) {
      dom.showToast("Erreur.", "error");
    }
  });

  /* ── Preferences ── */

  async function loadPreferences() {
    try {
      const response = await api.get("/app/profile/preferences");
      prefsData = response.data.data.preferences;

      elAll("[data-pref]").forEach((cb) => {
        const key = cb.dataset.pref;
        cb.checked = !!prefsData[key];
      });

      load2FA();
    } catch (_e) {
      /* silent */
    }
  }

  el("[data-save-prefs]")?.addEventListener("click", async () => {
    const payload = {};
    elAll("[data-pref]").forEach((cb) => {
      payload[cb.dataset.pref] = cb.checked;
    });

    try {
      const response = await api.post("/app/profile/preferences", payload);
      prefsData = response.data.data.preferences;
      load2FA();
      dom.showToast("Préférences sauvegardées.", "success");
    } catch (_e) {
      dom.showToast("Erreur.", "error");
    }
  });

  /* ── Sessions ── */

  async function loadSessions() {
    try {
      const response = await api.get("/app/profile/sessions");
      const sessions = response.data.data.sessions || [];
      const container = el("[data-sessions-list]");
      const badge = el("[data-session-count]");
      if (badge) badge.textContent = sessions.length;

      if (!sessions.length) {
        container.innerHTML =
          '<div class="profile-empty"><i class="fa-solid fa-wifi"></i><span>Aucune session active.</span></div>';
        return;
      }

      container.innerHTML = sessions
        .map(
          (s, idx) =>
            `<div class="session-item">
              <div class="session-info">
                <strong><i class="fa-solid fa-${s.user_agent && s.user_agent.includes("Mobile") ? "mobile-screen-button" : "laptop"}"></i> ${s.user_agent ? s.user_agent.split("/")[0].split(" ").slice(0, 2).join(" ") || "Appareil" : "Appareil inconnu"}</strong>
                <span>${s.ip_address || "IP inconnue"} · Dernière utilisation: ${s.last_used_at ? new Date(s.last_used_at).toLocaleDateString("fr-FR", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" }) : "—"} · Connecté le ${new Date(s.created_at).toLocaleDateString("fr-FR", { day: "numeric", month: "short" })}</span>
              </div>
              <button class="btn btn-soft session-revoke-btn" data-session-revoke="${s.id}" type="button">
                <i class="fa-solid fa-xmark"></i> Révoquer
              </button>
            </div>`
        )
        .join("");
    } catch (_e) {
      /* silent */
    }
  }

  documentObject.addEventListener("click", async (e) => {
    const revokeBtn = e.target.closest("[data-session-revoke]");
    if (!revokeBtn) return;
    const id = revokeBtn.dataset.sessionRevoke;
    if (!confirm("Révoquer cette session ?")) return;

    try {
      await api.delete(`/app/profile/sessions/${id}`);
      dom.showToast("Session révoquée.", "success");
      loadSessions();
    } catch (err) {
      dom.showToast(err.response?.data?.error?.message || "Erreur.", "error");
    }
  });

  /* ── Linked accounts ── */

  async function loadLinkedAccounts() {
    try {
      const response = await api.get("/app/profile/linked-accounts");
      const accounts = response.data.data.linked_accounts || [];
      const container = el("[data-linked-list]");
      const badge = el("[data-linked-count]");
      if (badge) badge.textContent = accounts.length;

      if (!accounts.length) {
        container.innerHTML =
          '<div class="profile-empty"><i class="fa-solid fa-plug"></i><span>Aucun compte lié.</span></div>';
        return;
      }

      container.innerHTML = accounts
        .map(
          (a) =>
            `<div class="linked-item">
              <div class="linked-icon"><i class="fa-solid fa-${a.type === "mobile_money" ? "mobile-screen-button" : "building-columns"}"></i></div>
              <div class="linked-info">
                <strong>${a.provider}</strong>
                <span>${a.account_label} · ${a.account_reference}</span>
              </div>
              <span class="linked-status">${a.status === "active" ? "Actif" : a.status}</span>
            </div>`
        )
        .join("");
    } catch (_e) {
      /* silent */
    }
  }

  /* ── Activity ── */

  async function loadActivity() {
    try {
      const response = await api.get("/app/profile/activity");
      const activity = response.data.data.activity || [];
      const container = el("[data-activity-list]");

      if (!activity.length) {
        container.innerHTML =
          '<div class="profile-empty"><i class="fa-solid fa-arrow-right-arrow-left"></i><span>Aucune activité récente.</span></div>';
        return;
      }

      const typeLabels = {
        send: "Envoi",
        deposit: "Dépôt",
        withdraw: "Retrait",
        bill: "Facture",
        send_bank: "Virement bancaire",
        conversion: "Conversion",
      };

      container.innerHTML = activity
        .map(
          (a) =>
            `<div class="activity-item">
              <div class="activity-info">
                <strong>${typeLabels[a.type] || a.type}</strong>
                <span>${a.transaction_reference} · ${a.recipient_name || a.provider_name || ""} · ${new Date(a.created_at).toLocaleDateString("fr-FR", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" })}</span>
              </div>
              <div class="activity-amount ${["deposit"].includes(a.type) ? "credit" : "debit"}">
                ${["deposit"].includes(a.type) ? "+" : "-"}${money(a.total_amount, a.currency)}
              </div>
            </div>`
        )
        .join("");
    } catch (_e) {
      /* silent */
    }
  }

  /* ── Forgot PIN ── */

  function openForgotPinModal() {
    const modal = el("[data-forgot-pin-modal]");
    if (modal) modal.style.display = "";
  }

  function closeForgotPinModal() {
    const modal = el("[data-forgot-pin-modal]");
    if (modal) modal.style.display = "none";
    const form = el("[data-forgot-pin-form]");
    if (form) form.reset();
  }

  dom.on(el("[data-forgot-pin-btn]"), "click", openForgotPinModal);

  dom.on(el("[data-forgot-pin-close]"), "click", closeForgotPinModal);
  dom.on(el("[data-forgot-pin-cancel]"), "click", closeForgotPinModal);

  el("[data-forgot-pin-modal]")?.addEventListener("click", (e) => {
    if (e.target === e.currentTarget) closeForgotPinModal();
  });

  dom.on(el("[data-forgot-pin-form]"), "submit", async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const btn = el("button[type='submit']", form);

    dom.setSubmitting(btn, true, "Réinitialisation...");
    try {
      const response = await api.post("/app/profile/forgot-pin", dom.serializeForm(form));
      dom.showToast(response.data.data.message || "PIN réinitialisé.", "success");
      closeForgotPinModal();
    } catch (err) {
      dom.showToast(err.response?.data?.error?.message || "Erreur.", "error");
    } finally {
      dom.setSubmitting(btn, false);
    }
  });

  /* ── Trust Score ── */

  async function loadTrust() {
    const container = el("[data-profile-trust]");
    if (!container) return;

    try {
      const response = await api.get("/app/trust-score");
      const trust = response.data.data;
      renderTrustFull(container, trust);
    } catch (_e) {
      container.innerHTML = '<div class="profile-empty"><i class="fa-solid fa-exclamation-circle"></i><span>Indisponible.</span></div>';
    }
  }

  function renderTrustFull(container, trust) {
    const badgeIcons = {
      gold: '<i class="fa-solid fa-star" style="color:gold;font-size:3rem"></i>',
      silver: '<i class="fa-solid fa-star" style="color:silver;font-size:3rem"></i>',
      none: '<i class="fa-solid fa-star" style="color:var(--color-subtle);font-size:3rem"></i>',
    };
    const badgeLabels = {
      gold: 'Badge Doré',
      silver: 'Badge Argenté',
      none: 'Aucun badge',
    };
    const badgeClasses = {
      gold: 'trust-badge-gold',
      silver: 'trust-badge-silver',
      none: 'trust-badge-none',
    };

    let criteriaHtml = "";
    let nextBadgeHtml = "";

    if (trust.progression && trust.progression.next_badge) {
      const criteria = Object.values(trust.progression.criteria);
      const metCount = criteria.filter(c => c.met).length;
      const totalCount = criteria.length;
      const pct = totalCount > 0 ? Math.round((metCount / totalCount) * 100) : 0;

      criteriaHtml = `
        <div class="profile-section-card">
          <h3>Progression vers <strong>${trust.progression.next_badge === 'gold' ? 'Doré' : 'Argenté'}</strong></h3>
          <div class="trust-progress-bar-bg" style="margin:0.75rem 0">
            <div class="trust-progress-bar-fill" style="width:${pct}%"></div>
          </div>
          <div class="trust-criteria" style="display:grid;gap:0.5rem">
            ${criteria.map(c => `
              <div class="trust-criterion ${c.met ? 'met' : 'unmet'}" style="display:flex;align-items:center;gap:0.5rem;font-size:0.9rem">
                <i class="fa-solid ${c.met ? 'fa-check-circle' : 'fa-circle'}" style="color:${c.met ? 'var(--color-success, #10b981)' : 'var(--color-subtle)'}"></i>
                <span>${c.label}: ${c.met ? 'OK' : (c.current != null ? c.current + ' / ' + c.required : 'Voir détail')}</span>
              </div>
            `).join('')}
          </div>
        </div>`;
    }

    if (trust.badge === 'gold' || trust.badge === 'silver') {
      nextBadgeHtml = trust.badge === 'gold'
        ? '<p style="color:var(--color-subtle);font-size:0.85rem">Vous avez atteint le niveau maximum !</p>'
        : '';
    }

    container.innerHTML = `
      <div class="profile-trust-summary" style="text-align:center;padding:1.5rem">
        <div class="${badgeClasses[trust.badge] || 'trust-badge-none'}">${badgeIcons[trust.badge] || badgeIcons.none}</div>
        <h3 style="margin:0.5rem 0 0">${badgeLabels[trust.badge] || badgeLabels.none}</h3>
        <p style="font-size:1.5rem;font-weight:700;margin:0.25rem 0">${trust.trust_score} <small style="font-size:0.6em;color:var(--color-subtle)">/ 1000</small></p>
        <small style="color:var(--color-subtle)">
          ${trust.tx_count_6m} transactions · ${trust.rating_count} évaluations
          ${trust.rating_count > 0 ? '· ' + trust.rating_avg.toFixed(1) + ' ★' : ''}
        </small>
        ${nextBadgeHtml}
      </div>
      ${criteriaHtml}
      <div class="profile-section-card" id="ratings-list">
        <h3>Évaluations reçues</h3>
        <div data-ratings-list><p style="color:var(--color-subtle)">Chargement...</p></div>
      </div>
    `;

    // Load ratings
    loadRatings();
  }

  async function loadRatings() {
    const container = el("[data-ratings-list]");
    if (!container) return;

    try {
      const response = await api.get("/app/ratings");
      const ratings = response.data.data.ratings || [];
      if (!ratings.length) {
        container.innerHTML = '<p style="color:var(--color-subtle)">Aucune évaluation pour le moment.</p>';
        return;
      }
      container.innerHTML = ratings.map(r => `
        <div class="rating-item" style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid var(--color-border)">
          <div style="display:flex;gap:2px;color:gold">
            ${Array.from({length: 5}, (_, i) => `<i class="fa-solid fa-star" style="color:${i < r.rating ? 'gold' : 'var(--color-border)'};font-size:0.8rem"></i>`).join('')}
          </div>
          <div style="flex:1;min-width:0">
            <strong style="font-size:0.85rem">${r.rater_name}</strong>
            ${r.comment ? '<p style="margin:0;font-size:0.8rem;color:var(--color-subtle)">"' + r.comment + '"</p>' : ''}
          </div>
          <small style="color:var(--color-subtle);flex-shrink:0">${new Date(r.created_at).toLocaleDateString("fr-FR", { day: "numeric", month: "short" })}</small>
        </div>
      `).join('');
    } catch (_e) {
      container.innerHTML = '<p style="color:var(--color-subtle)">Impossible de charger les évaluations.</p>';
    }
  }

  /* ── Init ── */

  documentObject.addEventListener("DOMContentLoaded", () => {
    loadProfile();
    loadPreferences();
    loadSessions();
    loadLinkedAccounts();
    loadActivity();
    loadTrust();
  });
})(window, document);
