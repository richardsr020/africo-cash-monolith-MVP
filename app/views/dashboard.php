<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="metric-grid">
  <article class="metric-card"><span>Solde total</span><strong data-total-balance>--</strong><small data-account-count>Chargement</small></article>
  <article class="metric-card"><span>Entrées</span><strong data-income-total>--</strong><small>Transactions créditées</small></article>
  <article class="metric-card"><span>Sorties</span><strong data-outcome-total>--</strong><small>Transactions débitées</small></article>
  <article class="metric-card"><span>Épargne</span><strong data-savings-rate>--</strong><small>du solde disponible</small></article>
</section>

<section class="dashboard-hero">
  <article class="panel balance-panel">
    <div>
      <p class="eyebrow">Vue analytique</p>
      <h2>Flux financiers</h2>
      <p>Suivez le solde, les canaux utilisés et la répartition de vos dépenses.</p>
    </div>
    <div class="balance-chart" aria-label="Évolution du solde" data-balance-chart>
      <span style="--value: 30%"></span>
      <span style="--value: 42%"></span>
      <span style="--value: 54%"></span>
      <span style="--value: 48%"></span>
      <span style="--value: 67%"></span>
      <span style="--value: 72%"></span>
      <span style="--value: 81%"></span>
    </div>
  </article>
  <article class="panel split-panel">
    <h2>Répartition</h2>
    <div class="donut-chart" aria-label="Répartition des usages">
      <span data-donut-value>--</span>
    </div>
    <div class="legend-list">
      <div><span class="legend-dot transfer"></span><p>Transferts</p><strong data-transfer-ratio>--</strong></div>
      <div><span class="legend-dot bills"></span><p>Factures</p><strong data-bill-ratio>--</strong></div>
      <div><span class="legend-dot cash"></span><p>Retraits</p><strong data-cash-ratio>--</strong></div>
    </div>
  </article>
</section>

<section class="content-grid two-columns">
  <article class="panel activity-panel">
    <h2>Activité récente</h2>
    <div class="activity-list" data-recent-transactions>
      <div><i class="fa-solid fa-spinner fa-spin"></i><span>Chargement des opérations</span><strong>--</strong></div>
    </div>
  </article>
  <article class="panel">
    <h2>Actions rapides</h2>
    <div class="quick-grid">
      <button data-quick-link="/transactions"><i class="fa-solid fa-paper-plane"></i><span>Envoyer</span></button>
      <button data-quick-link="/transactions"><i class="fa-solid fa-arrow-down"></i><span>Dépôt</span></button>
      <button data-quick-link="/atm"><i class="fa-solid fa-money-bill-transfer"></i><span>Retrait</span></button>
      <button data-quick-link="/mobile-money"><i class="fa-solid fa-mobile-screen-button"></i><span>Mobile Money</span></button>
    </div>
  </article>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
