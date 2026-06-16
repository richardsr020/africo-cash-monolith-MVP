(function bootMobileMoney(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  const providers = [
    { key: "vodacom", name: "Vodacom", icon: "vodacom" },
    { key: "airtel", name: "Airtel Money", icon: "airtel" },
    { key: "orange", name: "Orange Money", icon: "orange" },
    { key: "afrimoney", name: "Afrimoney", icon: "africell" },
  ];

  let linkedAccounts = [];

  /* ── Render linked list ── */

  function updateBadge() {
    const badge = document.querySelector("[data-linked-count]");
    if (badge) badge.textContent = linkedAccounts.length;
  }

  function renderLinked() {
    const container = document.querySelector("[data-linked-list]");
    updateBadge();

    if (!linkedAccounts.length) {
      container.innerHTML =
        '<div class="mm-empty"><i class="fa-solid fa-plug"></i><span>Aucun portefeuille mobile money connecté.</span></div>';
      return;
    }

    container.innerHTML = linkedAccounts
      .map(
        (acc, idx) =>
          `<div class="mm-linked-item" data-linked-idx="${idx}">
            <div class="mm-linked-info">
              <div class="mm-linked-icon"><i class="fa-solid fa-wallet"></i></div>
              <div class="mm-linked-details">
                <strong>${acc.provider}</strong>
                <span>${acc.account_reference}</span>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0">
              <span class="mm-linked-status">Connecté</span>
              <button class="btn btn-soft mm-linked-remove" type="button" data-mm-remove="${idx}">
                <i class="fa-solid fa-unlink"></i>
              </button>
            </div>
          </div>`
      )
      .join("");
  }

  /* ── Load accounts ── */

  async function loadAccounts() {
    try {
      const response = await api.get("/app/mobile-money");
      linkedAccounts = response.data.data.linked_accounts || [];
    } catch (_e) {
      linkedAccounts = [];
    }
    renderLinked();
  }

  /* ── Connect modal ── */

  const modal = document.querySelector("[data-mm-modal]");
  const modalProvider = document.querySelector("[data-mm-modal-provider]");
  const phoneInput = document.querySelector("[data-mm-phone]");
  const submitBtn = modal?.querySelector("[data-mm-submit]");
  let currentProvider = null;

  function openConnectModal(providerName) {
    if (!modal) return;
    currentProvider = providerName;
    if (modalProvider) modalProvider.textContent = providerName;
    if (phoneInput) {
      phoneInput.value = "";
      phoneInput.focus();
    }
    modal.showModal();
  }

  function closeConnectModal() {
    modal?.close();
    currentProvider = null;
  }

  document.querySelectorAll("[data-mm-modal-close]").forEach((btn) => {
    btn.addEventListener("click", closeConnectModal);
  });

  /* ── Verify modal ── */

  const verifyModal = document.querySelector("[data-mm-verify-modal]");
  const verifyPhone = document.querySelector("[data-mm-verify-phone]");
  const codeInputs = document.querySelectorAll(".mm-code-digit");
  const verifySubmit = document.querySelector("[data-mm-verify-submit]");

  function openVerifyModal(phone) {
    if (!verifyModal) return;
    if (verifyPhone) verifyPhone.textContent = phone;
    codeInputs.forEach((inp) => {
      inp.value = "";
      inp.classList.remove("error");
    });
    verifyModal.showModal();
    setTimeout(() => codeInputs[0]?.focus(), 100);
  }

  function closeVerifyModal() {
    verifyModal?.close();
  }

  document.querySelectorAll("[data-mm-verify-close]").forEach((btn) => {
    btn.addEventListener("click", closeVerifyModal);
  });

  /* Auto-advance code digits */
  codeInputs.forEach((inp, idx) => {
    inp.addEventListener("input", () => {
      if (inp.value.length >= 1 && idx < codeInputs.length - 1) {
        codeInputs[idx + 1].focus();
      }
    });
    inp.addEventListener("keydown", (e) => {
      if (e.key === "Backspace" && inp.value.length === 0 && idx > 0) {
        codeInputs[idx - 1].focus();
      }
    });
  });

  /* ── Submit verify code ── */

  verifySubmit?.addEventListener("click", async () => {
    const code = Array.from(codeInputs)
      .map((i) => i.value)
      .join("");

    if (code.length !== 6) {
      dom.showToast("Entrez le code à 6 chiffres reçu par SMS.", "error");
      return;
    }

    verifySubmit.disabled = true;
    verifySubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Vérification...';

    // Simulate a 1.5s verification delay
    await new Promise((r) => setTimeout(r, 1500));

    verifySubmit.disabled = false;
    verifySubmit.innerHTML = '<i class="fa-solid fa-check"></i> Vérifier';

    closeVerifyModal();

    // Now actually link via API
    if (!currentProvider || !phoneInput) return;
    const phone = phoneInput.value.trim();

    try {
      const response2 = await api.post("/app/mobile-money/link", {
        provider: currentProvider,
        phone: phone,
      });
      linkedAccounts = linkedAccounts.concat([response2.data.data.linked_account]);
      renderLinked();
      dom.showToast(`${currentProvider} connecté avec succès !`, "success");
    } catch (err) {
      dom.showToast(
        err.response?.data?.error?.message || "Erreur de connexion.",
        "error"
      );
    } finally {
      closeConnectModal();
    }
  });

  /* ── Remove ── */

  document.addEventListener("click", (e) => {
    const removeBtn = e.target.closest("[data-mm-remove]");
    if (!removeBtn) return;
    const idx = parseInt(removeBtn.dataset.mmRemove, 10);
    const account = linkedAccounts[idx];
    if (!account) return;

    if (!confirm(`Déconnecter ${account.provider} (${account.account_reference}) ?`))
      return;

    api
      .delete("/app/mobile-money/link", {
        data: { provider: account.provider, phone: account.account_reference },
      })
      .then(() => {
        linkedAccounts.splice(idx, 1);
        renderLinked();
        dom.showToast(`${account.provider} déconnecté.`, "success");
      })
      .catch((err) => {
        dom.showToast(
          err.response?.data?.error?.message || "Erreur de déconnexion.",
          "error"
        );
      });
  });

  /* ── Provider connect button click ── */

  document.querySelectorAll("[data-provider]").forEach((card) => {
    const btn = card.querySelector(".mm-connect-btn");
    btn?.addEventListener("click", () => {
      openConnectModal(card.dataset.provider);
    });
  });

  /* ── Submit connect modal ── */

  modal?.addEventListener("submit", (e) => {
    e.preventDefault();
    const phone = phoneInput?.value.trim();

    if (!phone || !/^\d{10,15}$/.test(phone)) {
      dom.showToast("Numéro mobile money invalide (10 à 15 chiffres).", "error");
      phoneInput?.focus();
      return;
    }

    closeConnectModal();
    openVerifyModal(phone);
  });

  /* ── Init ── */

  document.addEventListener("DOMContentLoaded", loadAccounts);
})(window, document);
