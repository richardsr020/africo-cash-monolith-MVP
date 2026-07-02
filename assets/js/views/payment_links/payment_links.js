(function bootPaymentLinks(windowObject, documentObject) {
  "use strict";
  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;

  var tabs = dom.queryAll("[data-tab]");
  var panels = {
    list: dom.query("[data-panel='list']"),
    create: dom.query("[data-panel='create']"),
  };
  var linksList = dom.query("[data-links-list]");
  var form = dom.query("[data-link-form]");
  var detailModal = dom.query("[data-link-detail]");
  var qrContainer = dom.query("[data-qr-container]");
  var linkCodeDisplay = dom.query("[data-link-code]");
  var linkInfo = dom.query("[data-link-info]");
  var linkPin = dom.query("[data-link-pin]");

  function money(cents, currency) {
    return (Number(cents || 0) / 100).toLocaleString("fr-CD", { maximumFractionDigits: 2 }) + " " + currency;
  }

  function switchTab(name) {
    tabs.forEach(function (t) {
      var isActive = t.getAttribute("data-tab") === name;
      t.classList.toggle("wallet-tab--active", isActive);
      t.setAttribute("aria-selected", String(isActive));
    });
    Object.keys(panels).forEach(function (key) {
      panels[key].style.display = key === name ? "" : "none";
      panels[key].classList.toggle("wallet-panel--active", key === name);
    });
    if (name === "list") loadLinks();
  }

  tabs.forEach(function (tab) {
    dom.on(tab, "click", function () {
      switchTab(tab.getAttribute("data-tab"));
    });
  });

  var createBtn = dom.query("[data-create-link-btn]");
  if (createBtn) {
    dom.on(createBtn, "click", function (e) {
      var params = new URLSearchParams(windowObject.location.search);
      if (params.get("action") === "create") {
        e.preventDefault();
        switchTab("create");
      }
    });
    var params = new URLSearchParams(windowObject.location.search);
    if (params.get("action") === "create") {
      switchTab("create");
    }
  }

  function getField(name) {
    var el = dom.query("[data-field='" + name + "']");
    return el ? el.value : "";
  }

  function loadLinks() {
    linksList.innerHTML = '<div class="wallet-balance-skeleton"><div class="skeleton-card"></div><div class="skeleton-card"></div></div>';
    api.get("/app/links").then(function (resp) {
      renderLinks(resp.data.data || []);
    }).catch(function () {
      linksList.innerHTML = '<p class="empty-state">Erreur de chargement.</p>';
    });
  }

  function renderLinks(links) {
    if (!links.length) {
      linksList.innerHTML = '<p class="empty-state">Aucun lien actif. Créez-en un !</p>';
      return;
    }
    var html = "";
    links.forEach(function (link) {
      var typeLabel = { send: "Envoi", withdraw: "Retrait agent", merchant: "Paiement marchand" }[link.type] || link.type;
      var statusLabel = { active: "Actif", used: "Utilisé", expired: "Expiré", revoked: "Révoqué" }[link.status] || link.status;
      var amountStr = link.amount ? money(link.amount, link.currency) : "Montant libre";
      var expires = link.expires_at ? new Date(link.expires_at.replace(" ", "T")).toLocaleString("fr-CD") : "";

      html += '<div class="link-card">';
      html += '  <div class="link-card__head">';
      html += '    <span class="link-badge"><i class="fa-solid fa-' + (link.type === "send" ? "paper-plane" : link.type === "withdraw" ? "hand-holding-dollar" : "store") + '"></i> ' + typeLabel + "</span>";
      html += '    <span class="link-badge link-badge--' + link.status + '">' + statusLabel + "</span>";
      html += "  </div>";
      html += '  <p class="link-code">' + link.code + "</p>";
      html += '  <p class="link-meta">' + amountStr + (expires ? " &middot; Expire le " + expires : "") + "</p>";
      if (link.status === "active") {
        html += '  <div class="link-actions">';
        html += '    <button class="btn btn-soft" data-share="' + link.code + '"><i class="fa-solid fa-share-nodes"></i> Partager</button>';
        html += '    <button class="btn btn-soft" data-revoke="' + link.id + '"><i class="fa-solid fa-ban"></i> Révoquer</button>';
        html += "  </div>";
      }
      html += "</div>";
    });
    linksList.innerHTML = html;

    dom.queryAll("[data-revoke]").forEach(function (btn) {
      dom.on(btn, "click", function () {
        var id = btn.getAttribute("data-revoke");
        if (confirm("Révoquer ce lien ?")) {
          revokeLink(parseInt(id, 10));
        }
      });
    });

    dom.queryAll("[data-share]").forEach(function (btn) {
      dom.on(btn, "click", function () {
        var code = btn.getAttribute("data-share");
        var url = windowObject.location.origin + "/payer?code=" + encodeURIComponent(code);
        if (navigator.share) {
          navigator.share({ title: "Lien de paiement Africo Cash", text: code, url: url });
        } else {
          navigator.clipboard.writeText(code).then(function () {
            dom.showToast("Code copié !", "success");
          });
        }
      });
    });
  }

  function revokeLink(id) {
    api.post("/app/links/" + id + "/revoke").then(function () {
      dom.showToast("Lien révoqué.", "success");
      loadLinks();
    }).catch(function () {
      dom.showToast("Erreur lors de la révocation.", "error");
    });
  }

  var amountToggle = dom.query("[data-field='amount-toggle']");
  var amountGroup = dom.query("[data-amount-group]");
  var maxAmountGroup = dom.query("[data-max-amount-group]");

  if (amountToggle) {
    dom.on(amountToggle, "change", function () {
      if (amountToggle.checked) {
        amountGroup.classList.remove("hidden");
        maxAmountGroup.classList.add("hidden");
      } else {
        amountGroup.classList.add("hidden");
        maxAmountGroup.classList.remove("hidden");
      }
    });
  }

  dom.on(form, "submit", function (e) {
    e.preventDefault();
    var payload = {
      type: getField("type"),
      currency: getField("currency"),
      pin: getField("pin"),
      duration_hours: parseInt(getField("duration_hours"), 10),
    };
    if (amountToggle && amountToggle.checked) {
      payload.amount = parseInt(getField("amount"), 10);
    } else {
      var ma = parseInt(getField("max_amount"), 10);
      if (ma) payload.max_amount = ma;
    }
    if (!payload.amount && !payload.max_amount) {
      dom.showToast("Veuillez définir un montant ou un plafond.", "error");
      return;
    }
    if (!payload.pin || payload.pin.length < 4) {
      dom.showToast("PIN requis (4 à 8 chiffres).", "error");
      return;
    }

    var submitBtn = form.querySelector("button[type='submit']");
    dom.setSubmitting(submitBtn, true, "Génération...");

    api.post("/app/links/create", payload).then(function (resp) {
      dom.setSubmitting(submitBtn, false);
      form.reset();
      showLinkDetail(resp.data.data);
    }).catch(function (err) {
      dom.setSubmitting(submitBtn, false);
      var data = err.response ? err.response.data : null;
      var msg = (data && data.error && data.error.message) || data && data.message || "Erreur lors de la création.";
      dom.showToast(msg, "error");
    });
  });

  function showLinkDetail(link) {
    qrContainer.innerHTML = "";
    linkCodeDisplay.textContent = link.code;

    var typeLabel = { send: "Envoi", withdraw: "Retrait agent", merchant: "Paiement marchand" }[link.type] || link.type;
    var amountStr = link.amount ? money(link.amount, link.currency) : "Montant libre";
    linkInfo.textContent = typeLabel + " - " + amountStr;
    linkPin.textContent = getField("pin");

    var redeemUrl = windowObject.location.origin + "/payer?code=" + encodeURIComponent(link.code);
    new QRCode(qrContainer, {
      text: redeemUrl,
      width: 200,
      height: 200,
      colorDark: "#1a1a2e",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H,
    });

    dom.openModal(detailModal);
  }

  dom.queryAll("[data-close-modal]").forEach(function (btn) {
    dom.on(btn, "click", function () {
      dom.closeModal(detailModal);
      switchTab("list");
    });
  });

  dom.on(detailModal, "click", function (e) {
    if (e.target === detailModal || e.target.classList.contains("modal")) {
      dom.closeModal(detailModal);
      switchTab("list");
    }
  });

  dom.on(dom.query("[data-copy-code]"), "click", function () {
    var code = linkCodeDisplay.textContent;
    navigator.clipboard.writeText(code).then(function () {
      dom.showToast("Code copié !", "success");
    });
  });

  dom.on(dom.query("[data-download-qr]"), "click", function () {
    var canvas = qrContainer.querySelector("canvas");
    if (canvas) {
      var link = documentObject.createElement("a");
      link.download = "africo-payment-link.png";
      link.href = canvas.toDataURL("image/png");
      link.click();
    }
  });

  loadLinks();
})(window, document);
