<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<div class="bills-page">
  <div class="bills-header">
    <h1><i class="fa-solid fa-file-invoice-dollar"></i> Factures</h1>
    <p>Payez vos factures directement depuis votre compte Africo Cash</p>
  </div>

  <!-- Step 1: Service selection -->
  <section class="service-grid">
    <button class="service-btn" data-service="Universités" data-icon="graduation-cap"><i class="fa-solid fa-graduation-cap"></i><span>Universités</span></button>
    <button class="service-btn" data-service="Télévision" data-icon="tv"><i class="fa-solid fa-tv"></i><span>Télévision</span></button>
    <button class="service-btn" data-service="Internet" data-icon="wifi"><i class="fa-solid fa-wifi"></i><span>Internet</span></button>
    <button class="service-btn" data-service="Eau" data-icon="droplet"><i class="fa-solid fa-droplet"></i><span>Eau</span></button>
    <button class="service-btn" data-service="Électricité" data-icon="bolt"><i class="fa-solid fa-bolt"></i><span>Électricité</span></button>
  </section>

  <!-- Step 2: Verify & Pay -->
  <section class="bill-panel" data-bill-panel>
    <div class="bill-panel-header">
      <h2><i class="fa-solid fa-magnifying-glass"></i> <span data-panel-title>Vérification préalable</span></h2>
      <span class="bill-step-badge">Étape 1</span>
    </div>
    <p style="margin:0;color:var(--color-subtle);font-size:0.9rem" data-panel-desc>Entrez la référence de votre facture pour vérifier son montant avant de payer.</p>

    <!-- Verify form -->
    <form class="form-grid" data-bill-form>
      <label class="form-field">
        <span>Référence facture</span>
        <input name="reference" required placeholder="Ex: FAC-2026-001">
      </label>
      <label class="form-field">
        <span>Service</span>
        <input name="service" required placeholder="Ex: Électricité, Eau, Internet...">
      </label>
      <button class="btn btn-primary form-span" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i> Vérifier la facture
      </button>
    </form>

    <!-- Verify result -->
    <div class="bill-result" data-bill-result></div>

    <!-- Confirm payment -->
    <div class="bill-confirm" data-bill-confirm style="display:none">
      <div class="bill-divider"></div>
      <h3 style="margin:0 0 0.75rem;font-size:1rem">Confirmer le paiement</h3>
      <div class="form-group">
        <label for="billPin" style="display:block;margin-bottom:0.35rem;font-size:0.8rem;font-weight:600;color:var(--color-subtle)">Code PIN de sécurité</label>
        <input type="password" id="billPin" class="form-input" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" placeholder="4 chiffres" autocomplete="off" style="width:100%;padding:0.7rem 0.9rem;border:1px solid var(--color-border);border-radius:var(--radius-md);background:var(--color-bg);color:var(--color-text);font-size:0.95rem;font-family:inherit;box-sizing:border-box">
      </div>
      <button class="btn btn-primary form-span" data-bill-pay type="button" style="margin-top:0.5rem">
        <i class="fa-solid fa-check-circle"></i> Confirmer et payer
      </button>
    </div>

    <!-- Pay success -->
    <div class="bill-success" data-bill-success style="display:none">
      <div class="bill-divider"></div>
      <div class="bill-success-content">
        <i class="fa-solid fa-check-circle" style="font-size:2.5rem;color:#0f766e"></i>
        <h3 style="margin:0.5rem 0 0.25rem">Paiement effectué</h3>
        <p style="margin:0;color:var(--color-subtle);font-size:0.9rem" data-pay-success-msg>Votre facture a été payée avec succès.</p>
        <div class="bill-result" data-pay-success-detail style="margin-top:1rem"></div>
        <button class="btn btn-secondary" data-bill-reset type="button" style="margin-top:1rem">
          <i class="fa-solid fa-arrow-left"></i> Payer une autre facture
        </button>
      </div>
    </div>
  </section>

  <!-- Auto-payment configuration -->
  <section class="bill-panel" data-auto-panel>
    <div class="bill-panel-header">
      <h2><i class="fa-solid fa-clock"></i> Prélèvements automatiques</h2>
      <span class="bill-step-badge">Optionnel</span>
    </div>
    <p style="margin:0;color:var(--color-subtle);font-size:0.9rem">Configurez un paiement récurrent pour ne plus avoir à payer manuellement chaque mois.</p>

    <form class="form-grid" data-auto-form style="margin-top:0.5rem">
      <label class="form-field">
        <span>Service</span>
        <input name="service" required placeholder="Ex: Électricité">
      </label>
      <label class="form-field">
        <span>Référence facture</span>
        <input name="reference" required placeholder="Ex: FAC-2026-001">
      </label>
      <label class="form-field">
        <span>Montant (optionnel — laissez vide pour montant variable)</span>
        <input name="amount" type="number" min="100" placeholder="Ex: 25000">
      </label>
      <label class="form-field">
        <span>Fréquence</span>
        <select name="frequency">
          <option value="monthly">Mensuel</option>
          <option value="weekly">Hebdomadaire</option>
          <option value="quarterly">Trimestriel</option>
          <option value="yearly">Annuel</option>
        </select>
      </label>
      <label class="form-field">
        <span>Jour du mois</span>
        <input name="day_of_month" type="number" min="1" max="28" value="5" placeholder="1-28">
      </label>
      <label class="form-field">
        <span>Montant maximum (optionnel)</span>
        <input name="max_amount" type="number" min="100" placeholder="Plafond de sécurité">
      </label>
      <button class="btn btn-primary form-span" type="submit">
        <i class="fa-solid fa-floppy-disk"></i> Activer le prélèvement automatique
      </button>
    </form>

    <div class="auto-list" data-auto-list style="margin-top:1rem"></div>
  </section>
</div>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
