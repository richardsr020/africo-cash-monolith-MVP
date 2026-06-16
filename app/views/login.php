<main id="main-content" class="auth-page auth-login">
  <section class="auth-stage" aria-label="Connexion Africo Cash">
    <div class="auth-visual">
      <a class="brand auth-brand" href="<?= route_path('landing_page') ?>" aria-label="Africo Cash - Accueil">
        <img src="/assets/img/nav_brand.png" alt="" class="brand-mark" width="44" height="44">
        <span>Africo Cash</span>
      </a>
      <div class="auth-visual-copy">
        <p class="eyebrow">Neobanque mobile money</p>
        <h1>Reprenez le contrôle de votre argent.</h1>
        <p>Portefeuille, Mobile Money, banques, factures et retraits sans carte réunis dans un espace sécurisé.</p>
      </div>
      <div class="auth-signal-grid" aria-hidden="true">
        <span><i class="fa-solid fa-shield-halved"></i> Sessions sécurisées</span>
        <span><i class="fa-solid fa-bolt"></i> Paiements instantanés</span>
        <span><i class="fa-solid fa-chart-line"></i> Solde en temps réel</span>
      </div>
    </div>
    <section class="auth-card">
      <div class="auth-card-head">
        <p class="eyebrow">Connexion</p>
        <h2>Bon retour</h2>
        <p>Utilisez votre email et votre mot de passe pour accéder à votre dashboard.</p>
      </div>
<?php $loginError = $_SESSION['_flash_error'] ?? null; unset($_SESSION['_flash_error']); ?>
      <form class="auth-form" action="" method="post">
        <?php if ($loginError): ?>
          <div class="form-feedback form-feedback--error"><?= e($loginError) ?></div>
        <?php endif; ?>
        <label class="form-field auth-field">
          <span>Email</span>
          <i class="fa-solid fa-envelope" aria-hidden="true"></i>
          <input type="email" name="email" autocomplete="email" required placeholder="vous@entreprise.com">
        </label>
        <label class="form-field auth-field">
          <span>Mot de passe</span>
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          <input type="password" name="password" autocomplete="current-password" minlength="8" required placeholder="8 caractères minimum">
        </label>
        <div class="auth-row">
          <label class="checkbox-field auth-checkbox">
            <input type="checkbox" name="remember">
            <span>Rester connecté</span>
          </label>
          <a href="<?= route_path('login') ?>">Mot de passe oublié ?</a>
        </div>
        <button class="btn btn-primary btn-full auth-submit" type="submit">
          <span>Se connecter</span>
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>
      </form>
      <p class="auth-switch">Nouveau sur Africo Cash ? <a href="<?= route_path('register') ?>">Créer un compte</a></p>
    </section>
  </section>
</main>
