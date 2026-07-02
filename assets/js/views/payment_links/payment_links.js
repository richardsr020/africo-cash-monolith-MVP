(function () {
  const dom = window.dom;
  const api = window.apiClient;
  const toast = dom.query("[data-toast]");

  const tabs = dom.queryAll("[data-tab]");
  const panels = {
    list: dom.query("[data-panel='list']"),
    create: dom.query("[data-panel='create']"),
  };
  const linksList = dom.query("[data-links-list]");
  const form = dom.query("[data-link-form]");
  const detailModal = dom.query("[data-link-detail]");
  const qrContainer = dom.query("[data-qr-container]");
  const linkCodeDisplay = dom.query("[data-link-code]");
  const linkInfo = dom.query("[data-link-info]");
  const linkPin = dom.query("[data-link-pin]");

  function switchTab(name) {
    tabs.forEach(function (t) {
      const isActive = t.getAttribute("data-tab") === name;
      t.classList.toggle("active", isActive);
      t.setAttribute("aria-selected", String(isActive));
    });
    Object.keys(panels).forEach(function (key) {
      panels[key].classList.toggle("active", key === name);
    });
    if (name === "list") loadLinks();
  }

  tabs.forEach(function (tab) {
    dom.on(tab, "click", function () {
      switchTab(tab.getAttribute("data-tab"));
    });
  });

  function showToast(msg, type) {
    toast.textContent = msg;
    toast.className = "toast-notify " + (type || "info");
    clearTimeout(toast._hide);
    toast._hide = setTimeout(function () { toast.className = "toast-notify hidden"; }, 3000);
  }

  function getField(name) {
    var el = dom.query("[data-field='" + name + "']");
    return el ? el.value : "";
  }

  function setField(name, val) {
    var el = dom.query("[data-field='" + name + "']");
    if (el) el.value = val;
  }

  function loadLinks() {
    linksList.innerHTML = '<p class="loading">Chargement...</p>';
    api.get("/api/app/links").then(function (data) {
      renderLinks(data.data || []);
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
      var statusClass = "status-" + link.status;
      var amountStr = link.amount ? (link.amount / 100).toLocaleString("fr-CD") + " " + link.currency : "Montant libre";
      var expires = new Date(link.expires_at.replace(" ", "T")).toLocaleString("fr-CD");

      html += '<div class="link-card">';
      html += '  <div class="link-card-header">';
      html += '    <span class="link-type-badge">' + typeLabel + "</span>";
      html += '    <span class="link-status ' + statusClass + '">' + statusLabel + "</span>";
      html += "  </div>";
      html += '  <div class="link-card-body">';
      html += '    <p class="link-code">' + link.code + "</p>";
      html += '    <p class="link-meta">' + amountStr + " &middot; Expire le " + expires + "</p>";
      html += "  </div>";
      if (link.status === "active") {
        html += '  <div class="link-card-actions">';
        html += '    <button class="btn btn-sm btn-outline" data-share="' + link.code + '">Partager</button>';
        html += '    <button class="btn btn-sm btn-danger" data-revoke="' + link.id + '">Révoquer</button>';
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
        var link = window.location.origin + "/payer?code=" + encodeURIComponent(code);
        if (navigator.share) {
          navigator.share({ title: "Lien de paiement Africo Cash", text: code, url: link });
        } else {
          navigator.clipboard.writeText(code).then(function () {
            showToast("Code copié !", "success");
          });
        }
      });
    });
  }

  function revokeLink(id) {
    api.post("/api/app/links/" + id + "/revoke").then(function () {
      showToast("Lien révoqué.", "success");
      loadLinks();
    }).catch(function () {
      showToast("Erreur lors de la révocation.", "error");
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
      showToast("Veuillez définir un montant ou un plafond.", "error");
      return;
    }
    if (!payload.pin || payload.pin.length < 4) {
      showToast("PIN requis (4 à 8 chiffres).", "error");
      return;
    }

    var submitBtn = form.querySelector("button[type='submit']");
    submitBtn.disabled = true;
    submitBtn.textContent = "Génération...";

    api.post("/api/app/links/create", payload).then(function (data) {
      submitBtn.disabled = false;
      submitBtn.textContent = "Générer le lien";
      form.reset();
      showLinkDetail(data.data);
    }).catch(function (err) {
      submitBtn.disabled = false;
      submitBtn.textContent = "Générer le lien";
      var msg = (err && err.error && err.error.message) || "Erreur lors de la création.";
      showToast(msg, "error");
    });
  });

  function showLinkDetail(link) {
    qrContainer.innerHTML = "";
    linkCodeDisplay.textContent = link.code;

    var typeLabel = { send: "Envoi", withdraw: "Retrait agent", merchant: "Paiement marchand" }[link.type] || link.type;
    var amountStr = link.amount ? (link.amount / 100).toLocaleString("fr-CD") + " " + link.currency : "Montant libre";
    linkInfo.textContent = typeLabel + " - " + amountStr;
    linkPin.textContent = getField("pin");

    var redeemUrl = window.location.origin + "/payer?code=" + encodeURIComponent(link.code);
    new QRCode(qrContainer, {
      text: redeemUrl,
      width: 200,
      height: 200,
      colorDark: "#1a1a2e",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H,
    });

    detailModal.classList.remove("hidden");
  }

  dom.query("[data-close-modal]").forEach(function (btn) {
    dom.on(btn, "click", function () {
      detailModal.classList.add("hidden");
      switchTab("list");
    });
  });

  dom.on(detailModal, "click", function (e) {
    if (e.target === detailModal) {
      detailModal.classList.add("hidden");
      switchTab("list");
    }
  });

  dom.query("[data-copy-code]").forEach(function (btn) {
    dom.on(btn, "click", function () {
      var code = linkCodeDisplay.textContent;
      navigator.clipboard.writeText(code).then(function () {
        showToast("Code copié !", "success");
      });
    });
  });

  dom.query("[data-download-qr]").forEach(function (btn) {
    dom.on(btn, "click", function () {
      var canvas = qrContainer.querySelector("canvas");
      if (canvas) {
        var link = document.createElement("a");
        link.download = "africo-payment-link.png";
        link.href = canvas.toDataURL("image/png");
        link.click();
      }
    });
  });

  loadLinks();
})();
