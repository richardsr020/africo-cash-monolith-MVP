<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="content-grid two-columns">
  <article class="panel">
    <h2>Générer un code ATM</h2>
    <form class="form-grid" data-atm-form>
      <label class="form-field"><span>Montant</span><input type="number" name="amount" min="1" required placeholder="100000"></label>
      <label class="form-field"><span>Devise</span><select name="currency"><option>CDF</option><option>USD</option></select></label>
      <button class="btn btn-primary form-span" type="submit">Générer un code temporaire</button>
    </form>
  </article>
  <article class="panel atm-code-card">
    <h2>Code actif</h2>
    <strong data-atm-code>— — — — — —</strong>
    <p>Utilisez ce code pour retirer votre argent en toute sécurité.</p>
  </article>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
