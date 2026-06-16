<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<div class="mobile-money-page">
  <div class="mm-header">
    <h1><i class="fa-solid fa-mobile-screen-button"></i> Mobile Money</h1>
    <p>Connectez vos portefeuilles mobile money pour approvisionner votre compte Africo Cash.</p>
  </div>

  <!-- Provider grid -->
  <section class="mm-provider-grid" data-provider-grid>
    <article class="mm-card" data-provider="Vodacom">
      <div class="mm-card-icon"><img src="/assets/img/vodacom.png" alt="Vodacom"></div>
      <h3>Vodacom</h3>
      <p>M-Pesa, transferts et paiements mobile.</p>
      <button class="btn btn-primary mm-connect-btn" type="button">
        <i class="fa-solid fa-link"></i> Connecter
      </button>
    </article>
    <article class="mm-card" data-provider="Airtel Money">
      <div class="mm-card-icon"><img src="/assets/img/airtel.jpg" alt="Airtel Money"></div>
      <h3>Airtel Money</h3>
      <p>Transferts et retraits instantanés.</p>
      <button class="btn btn-primary mm-connect-btn" type="button">
        <i class="fa-solid fa-link"></i> Connecter
      </button>
    </article>
    <article class="mm-card" data-provider="Orange Money">
      <div class="mm-card-icon"><img src="/assets/img/Orange_logo.svg.png" alt="Orange Money"></div>
      <h3>Orange Money</h3>
      <p>Interopérabilité paiement et cash-out.</p>
      <button class="btn btn-primary mm-connect-btn" type="button">
        <i class="fa-solid fa-link"></i> Connecter
      </button>
    </article>
    <article class="mm-card" data-provider="Afrimoney">
      <div class="mm-card-icon"><img src="/assets/img/Africell_Logo.jpg" alt="Afrimoney"></div>
      <h3>Afrimoney</h3>
      <p>Canal Mobile Money Africell.</p>
      <button class="btn btn-primary mm-connect-btn" type="button">
        <i class="fa-solid fa-link"></i> Connecter
      </button>
    </article>
  </section>

  <!-- Linked accounts -->
  <section class="mm-panel">
    <div class="mm-panel-header">
      <h2><i class="fa-solid fa-wallet"></i> Portefeuilles connectés</h2>
      <span class="mm-count-badge" data-linked-count>0</span>
    </div>
    <div class="mm-linked-list" data-linked-list>
      <div class="mm-empty">
        <i class="fa-solid fa-plug"></i>
        <span>Aucun portefeuille mobile money connecté.</span>
      </div>
    </div>
  </section>
</div>

<!-- Connect modal -->
<dialog class="modal" data-mm-modal aria-labelledby="mm-modal-title">
  <form class="modal-card" method="dialog">
    <button class="modal-close" type="button" data-mm-modal-close aria-label="Fermer">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <h2 id="mm-modal-title">Connecter <span data-mm-modal-provider></span></h2>
    <p class="modal-copy">Entrez le numéro associé à votre compte mobile money pour le lier à Africo Cash.</p>
    <label class="mm-field">
      <span>Numéro mobile money</span>
      <input type="tel" data-mm-phone inputmode="numeric" pattern="[0-9]{10,15}" required placeholder="Ex: 0991234567" maxlength="15">
    </label>
    <div class="mm-modal-actions">
      <button class="btn btn-soft" type="button" data-mm-modal-close>Annuler</button>
      <button class="btn btn-primary" type="submit" data-mm-submit>
        <i class="fa-solid fa-link"></i> Connecter
      </button>
    </div>
  </form>
</dialog>

<!-- Validation modal (simulation step) -->
<dialog class="modal" data-mm-verify-modal aria-labelledby="mm-verify-title">
  <div class="modal-card">
    <button class="modal-close" type="button" data-mm-verify-close aria-label="Fermer">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="mm-verify-content">
      <i class="fa-solid fa-shield-halved mm-verify-icon"></i>
      <h2 id="mm-verify-title">Code de vérification</h2>
      <p class="modal-copy">Un SMS a été envoyé au <strong data-mm-verify-phone></strong>. Entrez le code reçu pour confirmer la liaison.</p>
      <div class="mm-code-inputs" data-code-inputs>
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="mm-code-digit" data-code-0>
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="mm-code-digit" data-code-1>
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="mm-code-digit" data-code-2>
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="mm-code-digit" data-code-3>
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="mm-code-digit" data-code-4>
        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="mm-code-digit" data-code-5>
      </div>
      <button class="btn btn-primary" type="button" data-mm-verify-submit style="width:100%;margin-top:0.5rem">
        <i class="fa-solid fa-check"></i> Vérifier
      </button>
    </div>
  </div>
</dialog>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>