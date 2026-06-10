<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="content-grid two-columns">
  <article class="panel">
    <h2>Virement bancaire</h2>
    <form class="form-grid" data-banking-form>
      <label class="form-field form-span"><span>Banque</span><input type="text" name="bank" required placeholder="Banque partenaire"></label>
      <label class="form-field"><span>Compte</span><input type="text" name="account" required placeholder="N° compte"></label>
      <label class="form-field"><span>Montant</span><input type="number" name="amount" min="1" required placeholder="250"></label>
      <label class="form-field"><span>Devise</span><select name="currency"><option>USD</option><option>CDF</option></select></label>
      <button class="btn btn-primary form-span" type="submit">Vérifier et envoyer</button>
    </form>
  </article>
  <article class="panel">
    <h2>Réconciliation</h2>
    <div class="activity-list" data-banking-status><div><span>Statut</span><strong>Prêt</strong></div><div><span>Frais</span><strong>1,5%</strong></div><div><span>Délai</span><strong>Instantané MVP</strong></div></div>
  </article>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
