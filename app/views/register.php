<main id="main-content" class="auth-page auth-register">
  <section class="auth-stage" aria-label="Inscription Africo Cash">
    <div class="auth-visual">
      <a class="brand auth-brand" href="<?= route_path('landing_page') ?>" aria-label="Africo Cash - Accueil">
        <img src="/assets/img/nav_brand.png" alt="" class="brand-mark" width="44" height="44">
        <span>Africo Cash</span>
      </a>
      <div class="auth-visual-copy">
        <p class="eyebrow">Ouverture rapide</p>
        <h1>Un compte prêt, puis un onboarding guidé.</h1>
        <p>Commencez avec email et mot de passe. Les détails KYC légers et vos préférences seront configurés après.</p>
      </div>
      <div class="auth-signal-grid" aria-hidden="true">
        <span><i class="fa-solid fa-user-check"></i> Profil guidé</span>
        <span><i class="fa-solid fa-wallet"></i> Wallet CDF/USD</span>
        <span><i class="fa-solid fa-mobile-screen-button"></i> Mobile Money</span>
      </div>
    </div>
    <section class="auth-card">
      <div class="auth-card-head">
        <p class="eyebrow">Inscription</p>
        <h2>Créer votre accès</h2>
        <p>Seulement un email et un mot de passe. Votre numéro Africo sera généré automatiquement.</p>
      </div>
<?php $registerError = $_SESSION['_flash_error'] ?? null; unset($_SESSION['_flash_error']); ?>
      <form class="auth-form" action="" method="post">
        <?php if ($registerError): ?>
          <div class="form-feedback form-feedback--error"><?= e($registerError) ?></div>
        <?php endif; ?>
        <label class="form-field auth-field">
          <span>Email</span>
          <i class="fa-solid fa-envelope" aria-hidden="true"></i>
          <input type="email" name="email" autocomplete="email" required placeholder="vous@entreprise.com">
        </label>
        <label class="form-field auth-field">
          <span>Mot de passe</span>
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          <input type="password" name="password" autocomplete="new-password" minlength="8" required placeholder="8 caractères minimum">
        </label>
        <label class="form-field auth-field">
          <span>Confirmer le mot de passe</span>
          <i class="fa-solid fa-key" aria-hidden="true"></i>
          <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required placeholder="Confirmez votre mot de passe">
        </label>
        <button class="btn btn-primary btn-full auth-submit" type="submit">
          <span>Créer mon compte</span>
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>
      </form>
      <p class="auth-switch">Déjà inscrit ? <a href="<?= route_path('login') ?>">Se connecter</a></p>
    </section>
  </section>
</main>
