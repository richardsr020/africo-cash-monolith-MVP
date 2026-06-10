<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="metric-grid">
  <article class="metric-card"><span>Utilisateurs</span><strong data-admin-users>--</strong><small>Total local</small></article>
  <article class="metric-card"><span>Agents</span><strong data-admin-agents>--</strong><small>Rôles agent</small></article>
  <article class="metric-card"><span>Transactions</span><strong data-admin-transactions>--</strong><small>Opérations MVP</small></article>
  <article class="metric-card"><span>Volume</span><strong data-admin-volume>--</strong><small>Toutes devises</small></article>
</section>
<section class="panel">
  <h2>Console opérationnelle</h2>
  <div class="quick-grid">
    <button data-api-action="admin-users">Gestion utilisateurs</button>
    <button data-api-action="admin-agents">Gestion agents</button>
    <button data-api-action="admin-rates">Taux de change</button>
    <button data-api-action="admin-log">Journal d'activité</button>
  </div>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
