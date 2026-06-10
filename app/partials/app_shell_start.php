<?php
$appRoutes = array_filter(
    $pages,
    static fn (array $route): bool => ($route['section'] ?? null) === 'app'
);
?>
<header class="app-topbar">
  <a class="brand" href="<?= route_path('dashboard') ?>" aria-label="Africo Cash - Tableau de bord">
    <img src="/assets/img/nav_brand.png" alt="" class="brand-mark" width="40" height="40">
    <span>Africo Cash</span>
  </a>
  <div class="app-topbar-actions">
    <span class="app-user-chip" data-current-user>Session</span>
    <button class="theme-toggle" type="button" data-theme-toggle>
      <i class="fa-solid fa-moon" aria-hidden="true"></i>
      <span data-theme-label>Dark</span>
    </button>
    <a class="btn btn-soft" href="<?= route_path('profile') ?>">Profil</a>
    <button class="btn btn-soft" type="button" data-logout>
      <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
      Sortir
    </button>
  </div>
</header>

<div class="app-layout">
  <aside class="app-sidebar" aria-label="Navigation applicative">
    <nav class="app-nav">
      <?php foreach ($appRoutes as $routeKey => $route): ?>
        <a href="<?= route_path((string) $routeKey) ?>" class="<?= $pageKey === $routeKey ? 'is-active' : '' ?>">
          <i class="fa-solid <?= e((string) ($route['icon'] ?? 'fa-circle')) ?>" aria-hidden="true"></i>
          <span><?= e((string) $route['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <main id="main-content" class="app-main">
    <section class="app-page-head">
      <div>
        <p class="eyebrow">Africo Cash</p>
        <h1><?= e((string) $currentPage['label']) ?></h1>
        <p><?= e((string) $currentPage['description']) ?></p>
      </div>
      <button class="btn btn-primary" type="button" data-sync-view>
        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
        Synchroniser
      </button>
    </section>
