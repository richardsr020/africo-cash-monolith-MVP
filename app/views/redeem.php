<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>

<header class="wallet-header">
  <div class="wallet-header__left">
    <h1>Utiliser un code</h1>
    <p class="wallet-header__date">Recevez un paiement via un lien sécurisé</p>
  </div>
</header>

<div class="redeem-steps">
  <div class="step active" data-step="1">
    <span class="step-num"><i class="fa-solid fa-keyboard"></i></span>
    <span class="step-label">Code</span>
  </div>
  <div class="step-connector"></div>
  <div class="step" data-step="2">
    <span class="step-num"><i class="fa-solid fa-lock"></i></span>
    <span class="step-label">PIN</span>
  </div>
  <div class="step-connector"></div>
  <div class="step" data-step="3">
    <span class="step-num"><i class="fa-solid fa-check"></i></span>
    <span class="step-label">Confirmer</span>
  </div>
</div>

<article class="panel">
  <form class="redeem-form" data-redeem-form>
    <div class="form-field" data-step-1>
      <label for="redeem-code">Code de paiement</label>
      <input type="text" id="redeem-code" data-field="code" placeholder="ACP-XXXXXXXX" maxlength="14" required autocomplete="off" class="redeem-code-input">
      <p class="help-text">Saisissez le code à 14 caractères fourni par l'émetteur</p>
    </div>

    <div class="form-field hidden" data-step-2>
      <label for="redeem-pin">PIN de sécurité</label>
      <input type="password" id="redeem-pin" data-field="pin" pattern="[0-9]{4,8}" inputmode="numeric" maxlength="8" placeholder="PIN à 4-8 chiffres" autocomplete="off">
    </div>

    <div class="form-field hidden" data-step-3>
      <label for="redeem-amount">Montant (si montant libre)</label>
      <input type="number" id="redeem-amount" data-field="amount" min="100" placeholder="Montant en centimes">
      <p class="help-text">Laissez vide si le montant est fixé par l'émetteur</p>
    </div>

    <button type="submit" class="btn btn-primary btn-full">Recevoir le paiement</button>
  </form>

  <div class="redeem-result hidden" data-redeem-result>
    <div class="result-icon"><i class="fa-solid fa-circle-check"></i></div>
    <h2>Paiement reçu !</h2>
    <div class="result-details">
      <div class="result-row">
        <span class="result-label">Montant</span>
        <strong class="result-value" data-result-amount></strong>
      </div>
      <div class="result-row">
        <span class="result-label">Référence</span>
        <span class="result-value mono" data-result-ref></span>
      </div>
    </div>
    <a href="<?= route_path('wallet') ?>" class="btn btn-primary btn-full">Voir mon portefeuille</a>
  </div>
</article>

<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
