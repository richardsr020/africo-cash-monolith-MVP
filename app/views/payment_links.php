<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>

<header class="wallet-header">
  <div class="wallet-header__left">
    <h1>Liens de paiement</h1>
    <p class="wallet-header__date">Créez et gérez vos liens de paiement sécurisés</p>
  </div>
  <div class="wallet-header__right">
    <a href="<?= route_path('payment_links') ?>?action=create" class="btn btn-primary" data-create-link-btn>
      <i class="fa-solid fa-plus"></i> Nouveau lien
    </a>
  </div>
</header>

<section class="wallet-tabs" role="tablist">
  <button class="wallet-tab wallet-tab--active" data-tab="list" role="tab" aria-selected="true">
    <i class="fa-solid fa-list"></i> Mes liens
  </button>
  <button class="wallet-tab" data-tab="create" role="tab" aria-selected="false">
    <i class="fa-solid fa-circle-plus"></i> Créer
  </button>
</section>

<section class="wallet-panel wallet-panel--active" data-panel="list">
  <div class="links-list" data-links-list>
    <div class="wallet-balance-skeleton">
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
    </div>
  </div>
</section>

<section class="wallet-panel" data-panel="create" style="display:none">
  <article class="panel">
    <div class="panel__head">
      <h2><i class="fa-solid fa-pen"></i> Nouveau lien de paiement</h2>
    </div>
    <form class="link-form" data-link-form>
      <div class="form-field">
        <label for="link-type">Type de lien</label>
        <select id="link-type" data-field="type" required>
          <option value="send">Envoi à un utilisateur</option>
          <option value="withdraw">Retrait agent</option>
          <option value="merchant">Paiement marchand</option>
        </select>
      </div>

      <div class="form-field">
        <label for="link-currency">Devise</label>
        <select id="link-currency" data-field="currency" required>
          <option value="CDF">CDF</option>
          <option value="USD">USD</option>
        </select>
      </div>

      <label class="checkbox-field">
        <input type="checkbox" data-field="amount-toggle" checked>
        <span>Montant fixe</span>
      </label>

      <div class="form-field" data-amount-group>
        <label for="link-amount">Montant (en centimes)</label>
        <input type="number" id="link-amount" data-field="amount" min="100" placeholder="Ex: 50000">
      </div>

      <div class="form-field hidden" data-max-amount-group>
        <label for="link-max-amount">Montant maximum (en centimes)</label>
        <input type="number" id="link-max-amount" data-field="max_amount" min="100" placeholder="Ex: 100000">
      </div>

      <div class="form-field">
        <label for="link-duration">Durée de validité</label>
        <select id="link-duration" data-field="duration_hours">
          <option value="1">1 heure</option>
          <option value="6">6 heures</option>
          <option value="24" selected>24 heures</option>
          <option value="72">3 jours</option>
          <option value="168">7 jours</option>
          <option value="720">30 jours</option>
        </select>
      </div>

      <div class="form-field">
        <label for="link-pin">PIN de sécurité (4 à 8 chiffres)</label>
        <input type="password" id="link-pin" data-field="pin" pattern="[0-9]{4,8}" inputmode="numeric" maxlength="8" required placeholder="Ex: 1234">
      </div>

      <button type="submit" class="btn btn-primary btn-full">Générer le lien</button>
    </form>
  </article>
</section>

<dialog class="modal" data-link-detail>
  <div class="modal-card">
    <button class="modal-close" data-close-modal aria-label="Fermer">&times;</button>
    <h2>Lien créé !</h2>
    <p class="modal-copy">Partagez ce code ou le QR code avec le payeur.</p>
    <div class="qr-wrapper" data-qr-container></div>
    <p class="modal-code" data-link-code></p>
    <p class="link-info" data-link-info></p>
    <p class="modal-pin"><strong>PIN :</strong> <span data-link-pin></span></p>
    <div class="modal-actions">
      <button class="btn btn-soft btn-full" data-copy-code><i class="fa-solid fa-copy"></i> Copier le code</button>
      <button class="btn btn-soft btn-full" data-download-qr><i class="fa-solid fa-download"></i> QR code</button>
    </div>
  </div>
</dialog>

<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
