<main class="app-page" data-page="payment_links">
  <nav class="page-nav">
    <a href="<?= route_path('wallet') ?>" class="btn btn-soft btn-icon" aria-label="Retour"><i class="fa fa-arrow-left"></i></a>
    <h1>Liens de paiement</h1>
  </nav>

  <section class="page-tabs" role="tablist">
    <button class="tab-btn active" data-tab="list" role="tab" aria-selected="true">Mes liens</button>
    <button class="tab-btn" data-tab="create" role="tab" aria-selected="false">Créer un lien</button>
  </section>

  <section class="tab-panel active" data-panel="list" role="tabpanel">
    <div class="links-list" data-links-list>
      <p class="loading">Chargement...</p>
    </div>
  </section>

  <section class="tab-panel" data-panel="create" role="tabpanel">
    <form class="link-form" data-link-form>
      <div class="form-group">
        <label for="link-type">Type de lien</label>
        <select id="link-type" data-field="type" required>
          <option value="send">Envoi à un utilisateur</option>
          <option value="withdraw">Retrait agent</option>
          <option value="merchant">Paiement marchand</option>
        </select>
      </div>

      <div class="form-group">
        <label for="link-currency">Devise</label>
        <select id="link-currency" data-field="currency" required>
          <option value="CDF">CDF</option>
          <option value="USD">USD</option>
        </select>
      </div>

      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" data-field="amount-toggle" checked>
          <span>Montant fixe</span>
        </label>
      </div>

      <div class="form-group" data-amount-group>
        <label for="link-amount">Montant</label>
        <input type="number" id="link-amount" data-field="amount" min="100" placeholder="Ex: 50000">
      </div>

      <div class="form-group hidden" data-max-amount-group>
        <label for="link-max-amount">Montant maximum</label>
        <input type="number" id="link-max-amount" data-field="max_amount" min="100" placeholder="Ex: 100000">
      </div>

      <div class="form-group">
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

      <div class="form-group">
        <label for="link-pin">PIN (4 à 8 chiffres)</label>
        <input type="password" id="link-pin" data-field="pin" pattern="[0-9]{4,8}" inputmode="numeric" maxlength="8" required placeholder="Ex: 1234">
      </div>

      <button type="submit" class="btn btn-primary btn-full">Générer le lien</button>
    </form>
  </section>

  <div class="modal-overlay hidden" data-link-detail>
    <div class="modal-card">
      <button class="modal-close" data-close-modal aria-label="Fermer">&times;</button>
      <h2>Lien créé !</h2>
      <div class="qr-wrapper" data-qr-container></div>
      <p class="link-code-display" data-link-code></p>
      <p class="link-info" data-link-info></p>
      <p class="link-pin-display"><strong>PIN :</strong> <span data-link-pin></span></p>
      <button class="btn btn-soft btn-full" data-copy-code>Copier le code</button>
      <button class="btn btn-soft btn-full" data-download-qr>Télécharger le QR code</button>
    </div>
  </div>

  <div class="toast-notify" data-toast aria-live="polite" aria-atomic="true"></div>
</main>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
