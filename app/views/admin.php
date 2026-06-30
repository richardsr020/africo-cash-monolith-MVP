<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="metric-grid">
  <article class="metric-card"><span>Utilisateurs</span><strong data-admin-users>--</strong><small>Total local</small></article>
  <article class="metric-card"><span>Agents</span><strong data-admin-agents>--</strong><small>Rôles agent</small></article>
  <article class="metric-card"><span>Transactions</span><strong data-admin-transactions>--</strong><small>Opérations MVP</small></article>
  <article class="metric-card"><span>Volume</span><strong data-admin-volume>--</strong><small>Toutes devises</small></article>
  <article class="metric-card"><span><i class="fa-solid fa-star" style="color:silver"></i> Argenté</span><strong data-admin-silver>--</strong></article>
  <article class="metric-card"><span><i class="fa-solid fa-star" style="color:gold"></i> Doré</span><strong data-admin-gold>--</strong></article>
</section>
<section class="panel">
  <h2>Badges utilisateurs</h2>
  <div class="admin-badge-table-container" style="overflow-x:auto;margin-top:0.5rem">
    <table class="admin-badge-table" style="width:100%;border-collapse:collapse;font-size:0.85rem">
      <thead>
        <tr style="border-bottom:2px solid var(--color-border);text-align:left">
          <th style="padding:0.5rem">Nom</th>
          <th style="padding:0.5rem">N° Africo</th>
          <th style="padding:0.5rem">Badge</th>
          <th style="padding:0.5rem">Score</th>
        </tr>
      </thead>
      <tbody data-admin-badge-rows>
        <tr><td colspan="4" style="padding:1rem;text-align:center;color:var(--color-subtle)">Chargement...</td></tr>
      </tbody>
    </table>
  </div>
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
<link rel="stylesheet" href="/assets/css/views/trust_score.css?v=1">
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
