<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="dab-layout">
  <article class="dab-machine" aria-label="Distributeur Africo Cash">
    <div class="dab-head">
      <span>Africo Cash</span>
      <strong>DAB 24/7</strong>
    </div>

    <div class="dab-screen" data-dab-screen aria-live="polite"></div>

    <div class="dab-controls">
      <button type="button" data-dab-amount="10000">10 000</button>
      <button type="button" data-dab-amount="25000">25 000</button>
      <button type="button" data-dab-amount="50000">50 000</button>
      <button type="button" data-dab-amount="100000">100 000</button>
    </div>

    <div class="dab-keypad" aria-label="Clavier DAB">
      <button type="button" data-dab-key="1">1</button>
      <button type="button" data-dab-key="2">2</button>
      <button type="button" data-dab-key="3">3</button>
      <button type="button" data-dab-key="4">4</button>
      <button type="button" data-dab-key="5">5</button>
      <button type="button" data-dab-key="6">6</button>
      <button type="button" data-dab-key="7">7</button>
      <button type="button" data-dab-key="8">8</button>
      <button type="button" data-dab-key="9">9</button>
      <button class="is-danger" type="button" data-dab-action="clear">C</button>
      <button type="button" data-dab-key="0">0</button>
      <button class="is-success" type="button" data-dab-action="ok">OK</button>
    </div>

    <div class="dab-slots" aria-hidden="true">
      <span></span>
      <span></span>
    </div>
  </article>

  <aside class="panel dab-panel">
    <h2>Retrait simulé</h2>
    <label class="form-field">
      <span>Devise</span>
      <select data-dab-currency>
        <option value="CDF">CDF</option>
        <option value="USD">USD</option>
      </select>
    </label>
    <div class="dab-summary">
      <div><span>Code</span><strong data-dab-summary="code">------</strong></div>
      <div><span>Montant</span><strong data-dab-summary="amount">0 CDF</strong></div>
      <div><span>Statut</span><strong data-dab-summary="status">En attente</strong></div>
    </div>
    <button class="btn btn-soft btn-full" type="button" data-dab-action="reset">Nouvelle opération</button>
  </aside>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
