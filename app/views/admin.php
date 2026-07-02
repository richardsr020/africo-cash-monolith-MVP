<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>

<main class="admin-page" data-page="admin">
  <nav class="page-nav">
    <h1>Administration</h1>
  </nav>

  <section class="admin-tabs" role="tablist">
    <button class="tab-btn active" data-tab="overview" role="tab" aria-selected="true">Vue d'ensemble</button>
    <button class="tab-btn" data-tab="users" role="tab" aria-selected="false">Utilisateurs</button>
    <button class="tab-btn" data-tab="agents" role="tab" aria-selected="false">Agents</button>
    <button class="tab-btn" data-tab="transactions" role="tab" aria-selected="false">Transactions</button>
    <button class="tab-btn" data-tab="rates" role="tab" aria-selected="false">Taux</button>
    <button class="tab-btn" data-tab="settings" role="tab" aria-selected="false">Frais</button>
    <button class="tab-btn" data-tab="badges" role="tab" aria-selected="false">Badges</button>
    <button class="tab-btn" data-tab="logs" role="tab" aria-selected="false">Journal</button>
  </section>

  <!-- ═══ OVERVIEW ═══ -->
  <section class="tab-panel active" data-panel="overview">
    <div class="metric-grid">
      <article class="metric-card">
        <span>Utilisateurs</span>
        <strong data-admin-users>--</strong>
        <small>Total inscrits</small>
      </article>
      <article class="metric-card">
        <span>Agents</span>
        <strong data-admin-agents>--</strong>
        <small>Rôle agent</small>
      </article>
      <article class="metric-card">
        <span>Transactions</span>
        <strong data-admin-transactions>--</strong>
        <small>Total opérations</small>
      </article>
      <article class="metric-card">
        <span>Volume</span>
        <strong data-admin-volume>--</strong>
        <small>Toutes devises (centimes)</small>
      </article>
      <article class="metric-card">
        <span><i class="fa-solid fa-star" style="color:silver"></i> Argent</span>
        <strong data-admin-silver>--</strong>
        <small>Badge argent</small>
      </article>
      <article class="metric-card">
        <span><i class="fa-solid fa-star" style="color:gold"></i> Or</span>
        <strong data-admin-gold>--</strong>
        <small>Badge doré</small>
      </article>
    </div>

    <article class="panel">
      <div class="panel__head">
        <h2><i class="fa-solid fa-chart-line"></i> Volume 30 derniers jours</h2>
      </div>
      <div class="chart-container" data-volume-chart style="min-height:200px">
        <p class="text-muted">Chargement...</p>
      </div>
    </article>
  </section>

  <!-- ═══ USERS ═══ -->
  <section class="tab-panel" data-panel="users">
    <article class="panel">
      <div class="panel__head">
        <h2><i class="fa-solid fa-users"></i> Gestion des utilisateurs</h2>
      </div>
      <div class="admin-toolbar">
        <input type="search" data-user-search placeholder="Rechercher par nom, numéro ou email..." class="form-control">
      </div>
      <div class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>N° Africo</th>
              <th>Email</th>
              <th>Rôle</th>
              <th>Statut</th>
              <th>Badge</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody data-user-rows>
            <tr><td colspan="8" class="table-empty">Chargement...</td></tr>
          </tbody>
        </table>
      </div>
      <div class="admin-pagination" data-user-pagination></div>
    </article>
  </section>

  <!-- ═══ AGENTS ═══ -->
  <section class="tab-panel" data-panel="agents">
    <article class="panel">
      <div class="panel__head">
        <h2><i class="fa-solid fa-user-tie"></i> Gestion des agents</h2>
      </div>
      <div class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>N° Africo</th>
              <th>Téléphone</th>
              <th>Commission (bps)</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody data-agent-rows>
            <tr><td colspan="7" class="table-empty">Chargement...</td></tr>
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <!-- ═══ TRANSACTIONS ═══ -->
  <section class="tab-panel" data-panel="transactions">
    <article class="panel">
      <div class="panel__head">
        <h2><i class="fa-solid fa-receipt"></i> Transactions récentes</h2>
      </div>
      <div class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Réf</th>
              <th>Type</th>
              <th>Utilisateur</th>
              <th>Montant</th>
              <th>Frais</th>
              <th>Total</th>
              <th>Statut</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody data-transaction-rows>
            <tr><td colspan="8" class="table-empty">Chargement...</td></tr>
          </tbody>
        </table>
      </div>
      <div class="admin-pagination" data-transaction-pagination></div>
    </article>
  </section>

  <!-- ═══ EXCHANGE RATES ═══ -->
  <section class="tab-panel" data-panel="rates">
    <article class="panel">
      <div class="panel__head">
        <h2><i class="fa-solid fa-money-bill-transfer"></i> Taux de change</h2>
      </div>
      <div class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>De</th>
              <th>Vers</th>
              <th>Taux</th>
              <th>Date d'effet</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody data-rate-rows>
            <tr><td colspan="5" class="table-empty">Chargement...</td></tr>
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <!-- ═══ FEES & SETTINGS ═══ -->
  <section class="tab-panel" data-panel="settings">
    <article class="panel">
      <div class="panel__head">
        <h2><i class="fa-solid fa-sliders"></i> Configuration des frais</h2>
      </div>
      <div class="settings-grid" data-settings-container>
        <p class="text-muted">Chargement...</p>
      </div>
    </article>
  </section>

  <!-- ═══ BADGES ═══ -->
  <section class="tab-panel" data-panel="badges">
    <article class="panel">
      <div class="panel__head">
        <h2><i class="fa-solid fa-star"></i> Badges de confiance</h2>
      </div>
      <div class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>N° Africo</th>
              <th>Badge</th>
              <th>Score</th>
              <th>Volume 6m CDF</th>
              <th>Volume 6m USD</th>
              <th>Transactions</th>
              <th>Note moyenne</th>
            </tr>
          </thead>
          <tbody data-badge-rows>
            <tr><td colspan="8" class="table-empty">Chargement...</td></tr>
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <!-- ═══ AUDIT LOGS ═══ -->
  <section class="tab-panel" data-panel="logs">
    <article class="panel">
      <div class="panel__head">
        <h2><i class="fa-solid fa-clipboard-list"></i> Journal d'audit</h2>
      </div>
      <div class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Action</th>
              <th>Type</th>
              <th>ID</th>
              <th>Utilisateur</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody data-log-rows>
            <tr><td colspan="6" class="table-empty">Chargement...</td></tr>
          </tbody>
        </table>
      </div>
    </article>
  </section>
</main>

<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
