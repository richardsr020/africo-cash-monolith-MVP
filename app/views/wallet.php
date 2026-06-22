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
     BALANCE CARDS
═══════════════════════════════════════════ -->
<section class="wallet-balances" data-wallet-balances aria-label="Soldes par devise">
  <div class="wallet-balance-skeleton">
    <div class="skeleton-card"></div>
    <div class="skeleton-card"></div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     QUICK ACTIONS
═══════════════════════════════════════════ -->
<section class="wallet-actions" aria-label="Actions rapides">
  <button class="wallet-action" data-action="deposit">
    <span class="wallet-action__icon wallet-action__icon--deposit"><i class="fa-solid fa-circle-down"></i></span>
    <span class="wallet-action__label">Dépôt</span>
  </button>
  <button class="wallet-action" data-action="withdraw">
    <span class="wallet-action__icon wallet-action__icon--withdraw"><i class="fa-solid fa-circle-up"></i></span>
    <span class="wallet-action__label">Retrait</span>
  </button>
  <button class="wallet-action" data-action="convert">
    <span class="wallet-action__icon wallet-action__icon--convert"><i class="fa-solid fa-arrows-rotate"></i></span>
    <span class="wallet-action__label">Convertir</span>
  </button>
  <button class="wallet-action" data-action="stats">
    <span class="wallet-action__icon wallet-action__icon--stats"><i class="fa-solid fa-chart-simple"></i></span>
    <span class="wallet-action__label">Statistiques</span>
  </button>
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
