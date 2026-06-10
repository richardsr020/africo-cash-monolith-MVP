<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="content-grid two-columns">
  <article class="panel">
    <h2>Profil utilisateur</h2>
    <form class="form-grid" data-profile-form>
      <label class="form-field form-span"><span>Nom complet</span><input name="full_name" data-profile-full-name required></label>
      <label class="form-field"><span>Email</span><input type="email" name="email" data-profile-email disabled></label>
      <label class="form-field"><span>Téléphone</span><input name="phone" data-profile-phone required></label>
      <label class="form-field"><span>Ville</span><input name="city" data-profile-city></label>
      <button class="btn btn-primary form-span" type="submit">Enregistrer</button>
    </form>
  </article>
  <article class="panel">
    <h2>Sécurité</h2>
    <div class="security-list"><div><i class="fa-solid fa-lock"></i><span>Mot de passe sécurisé</span></div><div><i class="fa-solid fa-message"></i><span>Validation renforcée disponible</span></div><div><i class="fa-solid fa-id-card"></i><span data-profile-kyc>Identité en cours</span></div></div>
  </article>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
