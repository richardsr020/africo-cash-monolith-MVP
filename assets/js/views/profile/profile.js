(function bootProfile(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  let userData = null;
  let prefsData = null;

  function money(cents, currency) {
    return `${Number(cents || 0).toLocaleString("fr-FR")} ${currency}`;
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

  /* ── Init ── */

  documentObject.addEventListener("DOMContentLoaded", () => {
    loadProfile();
    loadPreferences();
    loadSessions();
    loadLinkedAccounts();
    loadActivity();
  });
})(window, document);
