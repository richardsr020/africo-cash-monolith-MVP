(function bootRedeem(windowObject, documentObject) {
  "use strict";
  var dom = windowObject.AfricoDom;
  var api = windowObject.AfricoApi;

  var form = dom.query("[data-redeem-form]");
  var steps = {
    1: dom.query("[data-step-1]"),
    2: dom.query("[data-step-2]"),
    3: dom.query("[data-step-3]"),
  };
  var stepIndicators = dom.queryAll("[data-step]");
  var resultDiv = dom.query("[data-redeem-result]");

  function getField(name) {
    var el = dom.query("[data-field='" + name + "']");
    return el ? el.value : "";
  }

  function goToStep(n) {
    Object.keys(steps).forEach(function (k) {
      steps[k].classList.toggle("hidden", parseInt(k, 10) !== n);
    });
    stepIndicators.forEach(function (el) {
      var stepNum = parseInt(el.getAttribute("data-step"), 10);
      el.classList.toggle("active", stepNum === n);
      el.classList.toggle("done", stepNum < n);
    });
  }

  dom.on(form, "submit", function (e) {
    e.preventDefault();
    var code = getField("code");
    var pin = getField("pin");
    var amount = getField("amount");

    if (!code) {
      dom.showToast("Veuillez saisir le code de paiement.", "error");
      goToStep(1);
      return;
    }
    if (!pin) {
      dom.showToast("Veuillez saisir le PIN.", "error");
      goToStep(2);
      return;
    }

    var payload = { code: code, pin: pin };
    if (amount) payload.amount = parseInt(amount, 10);

    var btn = form.querySelector("button[type='submit']");
    dom.setSubmitting(btn, true, "Traitement...");

    api.post("/app/links/redeem", payload).then(function (resp) {
      dom.setSubmitting(btn, false);
      form.classList.add("hidden");
      resultDiv.classList.remove("hidden");

      var data = resp.data.data;
      var amountStr = (data.amount / 100).toLocaleString("fr-CD", { maximumFractionDigits: 2 }) + " " + (data.currency || "CDF");
      dom.queryAll("[data-result-amount]").forEach(function (el) {
        el.textContent = amountStr;
      });
      dom.queryAll("[data-result-ref]").forEach(function (el) {
        el.textContent = data.reference || data.transaction_reference || "";
      });
      dom.showToast("Paiement reçu avec succès !", "success");
    }).catch(function (err) {
      dom.setSubmitting(btn, false);
      var data = err.response ? err.response.data : null;
      var msg = (data && data.error && data.error.message) || (data && data.message) || "Erreur lors du paiement.";
      dom.showToast(msg, "error");
    });
  });

  var codeField = dom.query("[data-field='code']");
  if (codeField) {
    dom.on(codeField, "input", function () {
      if (codeField.value.replace(/[^0-9A-Za-z-]/g, "").length >= 4) goToStep(2);
    });
  }

  var pinField = dom.query("[data-field='pin']");
  if (pinField) {
    dom.on(pinField, "input", function () {
      if (pinField.value.length >= 4) goToStep(3);
    });
  }

  var params = new URLSearchParams(windowObject.location.search);
  var codeParam = params.get("code");
  if (codeParam && codeField) {
    codeField.value = codeParam;
    goToStep(2);
  }
})(window, document);
