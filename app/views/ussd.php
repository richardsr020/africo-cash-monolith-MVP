<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<section class="ussd-layout">
  <article class="ussd-phone" aria-label="Téléphone USSD Africo Cash">
    <div class="ussd-speaker" aria-hidden="true"></div>
    <div class="ussd-screen" data-ussd-screen aria-live="polite"></div>
    <div class="ussd-entry" data-ussd-entry aria-live="polite"></div>

    <div class="ussd-keypad" aria-label="Clavier numérique">
      <button type="button" data-ussd-key="1">1</button>
      <button type="button" data-ussd-key="2">2</button>
      <button type="button" data-ussd-key="3">3</button>
      <button type="button" data-ussd-key="4">4</button>
      <button type="button" data-ussd-key="5">5</button>
      <button type="button" data-ussd-key="6">6</button>
      <button type="button" data-ussd-key="7">7</button>
      <button type="button" data-ussd-key="8">8</button>
      <button type="button" data-ussd-key="9">9</button>
      <button type="button" data-ussd-key="*">*</button>
      <button type="button" data-ussd-key="0">0</button>
      <button type="button" data-ussd-key="#">#</button>
    </div>

    <div class="ussd-actions">
      <button class="is-muted" type="button" data-ussd-action="clear">Effacer</button>
      <button class="is-call" type="button" data-ussd-action="call">
        <i class="fa-solid fa-phone" aria-hidden="true"></i>
        Appeler
      </button>
      <button class="is-end" type="button" data-ussd-action="end">Fin</button>
    </div>
  </article>

  <aside class="panel ussd-panel">
    <h2>Session USSD</h2>
    <div class="ussd-status">
      <div><span>Code</span><strong>*144#</strong></div>
      <div><span>État</span><strong data-ussd-state>Composition</strong></div>
      <div><span>Réseau</span><strong>Africo Cash</strong></div>
    </div>
  </aside>
</section>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
