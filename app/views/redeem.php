<main class="app-page" data-page="redeem">
  <nav class="page-nav">
    <a href="<?= route_path('payment_links') ?>" class="btn btn-soft btn-icon" aria-label="Retour"><i class="fa fa-arrow-left"></i></a>
    <h1>Utiliser un code</h1>
  </nav>

  <section class="redeem-form-section">
    <div class="redeem-steps">
      <div class="step active" data-step="1">
        <span class="step-num">1</span>
        <span>Saisir le code</span>
      </div>
      <div class="step" data-step="2">
        <span class="step-num">2</span>
        <span>Entrer le PIN</span>
      </div>
      <div class="step" data-step="3">
        <span class="step-num">3</span>
        <span>Confirmer</span>
      </div>
    </div>

    <form class="redeem-form" data-redeem-form>
      <div class="form-group" data-step-1>
        <label for="redeem-code">Code de paiement</label>
        <div class="code-input-wrapper">
          <input type="text" id="redeem-code" data-field="code" placeholder="ACP-XXXXXXXX" maxlength="14" required autocomplete="off">
        </div>
        <p class="help-text">Saisissez le code à 14 caractères fourni par l'émetteur (ex: ACP-XXXXXXXX)</p>
      </div>

      <div class="form-group hidden" data-step-2>
        <label for="redeem-pin">PIN de sécurité</label>
        <input type="password" id="redeem-pin" data-field="pin" pattern="[0-9]{4,8}" inputmode="numeric" maxlength="8" placeholder="PIN à 4-8 chiffres" autocomplete="off">
      </div>

      <div class="form-group hidden" data-step-3>
        <label for="redeem-amount">Montant (si montant libre)</label>
        <input type="number" id="redeem-amount" data-field="amount" min="100" placeholder="Montant en CDF">
        <p class="help-text">Laissez vide si le montant est fixé par l'émetteur</p>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Recevoir le paiement</button>
    </form>

    <div class="redeem-result hidden" data-redeem-result>
      <div class="result-icon success"><i class="fa fa-check-circle"></i></div>
      <h2>Paiement reçu !</h2>
      <p><strong>Montant :</strong> <span data-result-amount></span></p>
      <p><strong>Référence :</strong> <span data-result-ref></span></p>
      <a href="<?= route_path('wallet') ?>" class="btn btn-primary">Voir mon portefeuille</a>
    </div>
  </section>

  <div class="toast-notify" data-toast aria-live="polite" aria-atomic="true"></div>
</main>
