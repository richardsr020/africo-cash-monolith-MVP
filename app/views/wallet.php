<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>

<!-- ═══════════════════════════════════════════
     HEADER
═══════════════════════════════════════════ -->
<header class="wallet-header">
  <div class="wallet-header__left">
    <h1>Portefeuille</h1>
    <p class="wallet-header__date" data-current-date>Vue d'ensemble des comptes</p>
  </div>
  <div class="wallet-header__right">
    <span class="wallet-balance-global" data-total-balance>--</span>
  </div>
</header>

<!-- ═══════════════════════════════════════════
     WALLET TABS
═══════════════════════════════════════════ -->
<section class="wallet-tabs" role="tablist">
  <button class="wallet-tab wallet-tab--active" data-wallet-tab="current" role="tab" aria-selected="true">
    <i class="fa-solid fa-wallet"></i> Dépenses courantes
  </button>
  <button class="wallet-tab" data-wallet-tab="savings" role="tab" aria-selected="false">
    <i class="fa-solid fa-piggy-bank"></i> Épargne
    <span class="wallet-tab__badge" data-savings-features-badge style="display:none">2</span>
  </button>
</section>

<!-- ═══════════════════════════════════════════
     CURRENT WALLET PANEL
═══════════════════════════════════════════ -->
<section class="wallet-panel wallet-panel--active" data-wallet-panel="current" aria-label="Portefeuille dépenses courantes">
  <div class="wallet-balances" data-wallet-balances="current">
    <div class="wallet-balance-skeleton">
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     SAVINGS WALLET PANEL
═══════════════════════════════════════════ -->
<section class="wallet-panel" data-wallet-panel="savings" aria-label="Portefeuille épargne" style="display:none">
  <div class="wallet-balances" data-wallet-balances="savings">
    <div class="wallet-balance-skeleton">
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
    </div>
  </div>


  <!-- === SAVINGS CONFIG === -->
  <article class="panel savings-config" data-savings-config-panel>
    <div class="panel__head">
      <h2><i class="fa-solid fa-gear"></i> Configuration épargne</h2>
    </div>
    <div class="savings-config__body" data-savings-config-body>
      <div class="skeleton-card" style="height:120px"></div>
    </div>
  </article>
</section>

<!-- ═══════════════════════════════════════════
     CONVERSION WIDGET
═══════════════════════════════════════════ -->
<!-- ═══════════════════════════════════════════
     PAYMENT LINKS WIDGET
═══════════════════════════════════════════ -->
<section class="wallet-links" data-links-widget>
  <article class="panel">
    <div class="panel__head">
      <h2><i class="fa-solid fa-qrcode"></i> Liens de paiement</h2>
      <a href="<?= route_path('payment_links') ?>" class="btn btn-sm btn-soft">Gérer</a>
    </div>
    <div class="wallet-links__body" data-links-body>
      <p class="text-muted">Créez un lien pour recevoir un paiement sans être connecté.</p>
      <a href="<?= route_path('payment_links') ?>?action=create" class="btn btn-primary btn-full">
        <i class="fa-solid fa-plus"></i> Nouveau lien
      </a>
    </div>
  </article>
</section>

<!-- ═══════════════════════════════════════════
     CONVERSION WIDGET
═══════════════════════════════════════════ -->
<section class="wallet-conversion" data-conversion-widget style="display:none" aria-label="Convertisseur de devises">
  <div class="panel">
    <div class="panel__head">
      <h2><i class="fa-solid fa-arrows-rotate"></i> Conversion</h2>
      <button class="panel__close" data-conversion-close type="button">&times;</button>
    </div>
    <div class="conversion-form">
      <div class="conversion-field">
        <label>Devise source</label>
        <select data-conversion-from>
          <option value="CDF">CDF</option>
          <option value="USD">USD</option>
        </select>
      </div>
      <div class="conversion-swap">
        <button class="conversion-swap-btn" data-conversion-swap type="button">
          <i class="fa-solid fa-arrow-right-arrow-left"></i>
        </button>
      </div>
      <div class="conversion-field">
        <label>Devise cible</label>
        <select data-conversion-to>
          <option value="USD">USD</option>
          <option value="CDF">CDF</option>
        </select>
      </div>
      <div class="conversion-field conversion-field--wide">
        <label>Montant</label>
        <input type="number" data-conversion-amount min="1" placeholder="Saisissez un montant">
      </div>
      <div class="conversion-result" data-conversion-result>
        <span>--</span>
      </div>
      <button class="btn btn-primary" data-conversion-execute type="button">
        <i class="fa-solid fa-check"></i> Convertir
      </button>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     TRANSFER MODALS
═══════════════════════════════════════════ -->
<div class="modal-overlay" data-modal="to-savings" style="display:none">
  <div class="modal">
    <div class="modal__head">
      <h3><i class="fa-solid fa-piggy-bank"></i> Alimenter l'épargne</h3>
      <button class="modal__close" data-modal-close>&times;</button>
    </div>
    <div class="modal__body">
      <p>Transférer des fonds de votre compte courant vers votre épargne.</p>
      <div class="form-group">
        <label>Devise</label>
        <select data-transfer-currency class="form-control">
          <option value="CDF">CDF</option>
          <option value="USD">USD</option>
        </select>
      </div>
      <div class="form-group">
        <label>Montant</label>
        <input type="number" data-transfer-amount class="form-control" min="1" placeholder="Montant à épargner">
      </div>
      <div class="form-group">
        <label>PIN de sécurité</label>
        <input type="password" data-transfer-pin class="form-control" maxlength="4" inputmode="numeric" pattern="[0-9]*" placeholder="****">
      </div>
      <p class="form-help">Opération sans frais.</p>
      <div class="form-error" data-transfer-error style="display:none"></div>
    </div>
    <div class="modal__foot">
      <button class="btn btn-soft" data-modal-close>Annuler</button>
      <button class="btn btn-primary" data-transfer-confirm>Confirmer le transfert</button>
    </div>
  </div>
</div>

<div class="modal-overlay" data-modal="from-savings" style="display:none">
  <div class="modal">
    <div class="modal__head">
      <h3><i class="fa-solid fa-circle-up"></i> Retirer de l'épargne</h3>
      <button class="modal__close" data-modal-close>&times;</button>
    </div>
    <div class="modal__body">
      <p>Transférer des fonds de votre épargne vers votre compte courant.</p>
      <div data-from-savings-status></div>
      <div class="form-group">
        <label>Devise</label>
        <select data-transfer-currency class="form-control">
          <option value="CDF">CDF</option>
          <option value="USD">USD</option>
        </select>
      </div>
      <div class="form-group">
        <label>Montant</label>
        <input type="number" data-transfer-amount class="form-control" min="1" placeholder="Montant à retirer">
      </div>
      <div class="form-group">
        <label>PIN de sécurité</label>
        <input type="password" data-transfer-pin class="form-control" maxlength="4" inputmode="numeric" pattern="[0-9]*" placeholder="****">
      </div>
      <div class="form-error" data-transfer-error style="display:none"></div>
    </div>
    <div class="modal__foot">
      <button class="btn btn-soft" data-modal-close>Annuler</button>
      <button class="btn btn-primary" data-transfer-confirm>Confirmer le retrait</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     RECENT MOVEMENTS
═══════════════════════════════════════════ -->
<section class="wallet-movements" aria-label="Mouvements récents">
  <article class="panel">
    <div class="panel__head">
      <h2><i class="fa-solid fa-clock-rotate-left"></i> Derniers mouvements</h2>
      <span class="movement-count" data-movement-count>0</span>
    </div>
    <div class="movement-list" data-wallet-movements>
      <div class="movement-skeleton">
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
      </div>
    </div>
  </article>
</section>

<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
