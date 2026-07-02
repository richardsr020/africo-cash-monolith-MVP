(function () {
  var dom = window.dom;
  var api = window.apiClient;
  var toast = dom.query("[data-toast]");

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

  function showToast(msg, type) {
    if (!toast) return;
    toast.textContent = msg;
    toast.className = "toast-notify " + (type || "info");
    clearTimeout(toast._hide);
    toast._hide = setTimeout(function () { toast.className = "toast-notify hidden"; }, 3000);
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
      showToast("Veuillez saisir le code de paiement.", "error");
      goToStep(1);
      return;
    }
    if (!pin) {
      showToast("Veuillez saisir le PIN.", "error");
      goToStep(2);
      return;
    }

    var payload = { code: code, pin: pin };
    if (amount) payload.amount = parseInt(amount, 10);

    var btn = form.querySelector("button[type='submit']");
    btn.disabled = true;
    btn.textContent = "Traitement...";

    api.post("/api/app/links/redeem", payload).then(function (data) {
      btn.disabled = false;
      btn.textContent = "Recevoir le paiement";
      form.classList.add("hidden");
      resultDiv.classList.remove("hidden");
      dom.query("[data-result-amount]").forEach(function (el) {
        el.textContent = (data.data.amount / 100).toLocaleString("fr-CD") + " " + data.data.currency;
      });
      dom.query("[data-result-ref]").forEach(function (el) {
        el.textContent = data.data.reference;
      });
      showToast("Paiement reçu avec succès !", "success");
    }).catch(function (err) {
      btn.disabled = false;
      btn.textContent = "Recevoir le paiement";
      var msg = (err && err.error && err.error.message) || "Erreur lors du paiement.";
      showToast(msg, "error");
    });
  });

  var codeField = dom.query("[data-field='code']");
  if (codeField) {
    dom.on(codeField, "input", function () {
      if (codeField.value.length >= 4) goToStep(2);
    });
  }

  var pinField = dom.query("[data-field='pin']");
  if (pinField) {
    dom.on(pinField, "input", function () {
      if (pinField.value.length >= 4) goToStep(3);
    });
  }

  var params = new URLSearchParams(window.location.search);
  var codeParam = params.get("code");
  if (codeParam && codeField) {
    codeField.value = codeParam;
    goToStep(2);
  }
})();
