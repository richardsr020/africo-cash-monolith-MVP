<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="panel">
  <h2>Nouvelle transaction</h2>
  <form class="form-grid" data-transaction-form>
    <label class="form-field"><span>Type</span><select name="type" required><option value="send">Envoi</option><option value="deposit">Dépôt</option><option value="withdraw">Retrait</option><option value="conversion">Conversion</option></select></label>
    <label class="form-field"><span>Montant</span><input type="number" name="amount" min="1" required placeholder="50000"></label>
    <label class="form-field"><span>Devise</span><select name="currency" required><option>CDF</option><option>USD</option></select></label>
    <label class="form-field"><span>Bénéficiaire</span><input type="text" name="beneficiary" required placeholder="Numéro Africo ou téléphone"></label>
    <button class="btn btn-primary form-span" type="submit">Calculer les frais et confirmer</button>
  </form>
</section>
<section class="panel">
  <h2>Historique</h2>
  <div class="data-table" data-transaction-table><div><span>ID</span><span>Canal</span><span>Montant</span><span>Statut</span></div></div>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
