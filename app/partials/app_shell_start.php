<?php
$appRoutes = array_filter(
    $pages,
    static fn (array $route): bool => ($route['section'] ?? null) === 'app'
);
$simpleRoutes = ['dashboard', 'wallet', 'profile'];
?>
<header class="app-topbar">
  <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars" aria-hidden="true"></i>
  </button>
  <a class="brand" href="<?= route_path('dashboard') ?>" aria-label="Africo Cash - Tableau de bord">
    <img src="/assets/img/nav_brand.png" alt="Africo Cash Logo" class="brand-mark" width="40" height="40">
    <span class="brand-text">Africo Cash</span>
  </a>
  <div class="app-topbar-actions">
    <span class="app-user-chip" data-current-user>Session</span>
    <button class="theme-toggle" type="button" data-theme-toggle aria-label="Changer le thème">
      <i class="fa-solid fa-moon" aria-hidden="true"></i>
      <span class="theme-label" data-theme-label>Dark</span>
    </button>
    <a class="btn btn-soft" href="<?= route_path('profile') ?>">
      <i class="fa-solid fa-user" aria-hidden="true"></i>
      <span class="btn-text">Profil</span>
    </a>
    <button class="btn btn-soft" type="button" data-logout aria-label="Se déconnecter">
      <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
      <span class="btn-text">Sortir</span>
    </button>
  </div>
</header>

<div class="app-layout">
  <aside class="app-sidebar" aria-label="Navigation applicative" data-sidebar>
    <nav class="app-nav" aria-label="Menu principal">
      <?php
      $currentUserRole = (string) ($currentUser['role'] ?? 'customer');
      foreach ($appRoutes as $routeKey => $route):
        if ($routeKey === 'admin' && $currentUserRole !== 'admin') continue;
        $modeGroup = in_array((string) $routeKey, $simpleRoutes, true) ? 'both' : 'advanced';
      ?>
        <a href="<?= route_path((string) $routeKey) ?>" 
           class="<?= ($pageKey ?? '') === $routeKey ? 'is-active' : '' ?>"
           <?= ($pageKey ?? '') === $routeKey ? 'aria-current="page"' : '' ?>
           data-mode-group="<?= $modeGroup ?>">
          <i class="fa-solid <?= e((string) ($route['icon'] ?? 'fa-circle')) ?>" aria-hidden="true"></i>
          <span class="nav-label"><?= e((string) $route['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer" data-sidebar-footer>
      <button class="sidebar-mode-toggle" type="button" data-toggle-sidebar-mode>
        <i class="fa-solid fa-chart-simple"></i>
        <span class="nav-label" data-sidebar-mode-label>Mode avancé</span>
      </button>
    </div>
  </aside>
  <main id="main-content">

<!-- Ajouter ce script pour la gestion des erreurs si nécessaire -->
<script>
  (function() {
    // Vérifier que toutes les variables PHP sont définies
    if (typeof $pageKey === 'undefined') {
      window.$pageKey = '';
    }
  })();
</script>