<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="service-grid">
  <button data-api-action="bill-university"><i class="fa-solid fa-graduation-cap"></i><span>Universités</span></button>
  <button data-api-action="bill-tv"><i class="fa-solid fa-tv"></i><span>Télévision</span></button>
  <button data-api-action="bill-internet"><i class="fa-solid fa-wifi"></i><span>Internet</span></button>
  <button data-api-action="bill-water"><i class="fa-solid fa-droplet"></i><span>Eau</span></button>
  <button data-api-action="bill-power"><i class="fa-solid fa-bolt"></i><span>Électricité</span></button>
</section>
<section class="panel">
  <h2>Vérification préalable</h2>
  <form class="form-grid" data-bill-form>
    <label class="form-field"><span>Référence facture</span><input name="reference" required placeholder="FAC-2026-001"></label>
    <label class="form-field"><span>Service</span><input name="service" required placeholder="Électricité"></label>
    <button class="btn btn-primary form-span" type="submit">Vérifier la facture</button>
  </form>
  <div class="activity-list" data-bill-result></div>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
