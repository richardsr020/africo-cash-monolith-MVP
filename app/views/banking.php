<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<div class="banking-page">
  <div class="banking-header">
    <h1><i class="fa-solid fa-building-columns"></i> Virements bancaires</h1>
    <p>Effectuez des virements vers les banques partenaires ou toute autre banque.</p>
  </div>

  <!-- Tabs -->
  <div class="banking-tabs" data-banking-tabs>
    <button class="banking-tab is-active" data-tab="partner">
      <i class="fa-solid fa-handshake"></i> Banques partenaires
    </button>
    <button class="banking-tab" data-tab="external">
      <i class="fa-solid fa-globe"></i> Banque non partenaire
    </button>
    <button class="banking-tab" data-tab="beneficiaries">
      <i class="fa-solid fa-address-book"></i> Bénéficiaires
    </button>
  </div>

  <!-- ═══ Tab: Partner banks ═══ -->
  <section class="banking-panel" data-tab-content="partner">
    <div class="banking-panel-header">
      <h2><i class="fa-solid fa-handshake"></i> Banques partenaires</h2>
      <span class="banking-info-badge">Frais 1,5%</span>
    </div>
    <p style="margin:0;color:var(--color-subtle);font-size:0.9rem">Sélectionnez une banque partenaire pour effectuer un virement instantané.</p>

    <div class="partner-grid">
      <article class="partner-card" data-partner="Rawbank" data-img="rawbank.jpeg">
        <div class="partner-card-img"><img src="/assets/img/rawbank.jpeg" alt="Rawbank"></div>
        <h3>Rawbank</h3>
        <p>Banque commerciale leader en RDC.</p>
      </article>
      <article class="partner-card" data-partner="Ecobank" data-img="ecobank.jpeg">
        <div class="partner-card-img"><img src="/assets/img/ecobank.jpeg" alt="Ecobank"></div>
        <h3>Ecobank</h3>
        <p>Présence panafricaine.</p>
      </article>
      <article class="partner-card" data-partner="EquityBCDC" data-img="equity.png">
        <div class="partner-card-img"><img src="/assets/img/equity.png" alt="EquityBCDC"></div>
        <h3>Equity BCDC</h3>
        <p>Banque universelle de référence.</p>
      </article>
      <article class="partner-card" data-partner="TMB" data-img="tmb.png">
        <div class="partner-card-img"><img src="/assets/img/tmb.png" alt="TMB"></div>
        <h3>TMB</h3>
        <p>Trust Merchant Bank.</p>
      </article>
    </div>

    <!-- Partner transfer form -->
    <form class="banking-form" data-partner-form style="margin-top:1rem">
      <input type="hidden" name="bank" data-partner-bank>
      <div class="banking-form-row">
        <label class="banking-field">
          <span>Banque sélectionnée</span>
          <input type="text" data-partner-display readonly placeholder="Cliquez sur une banque ci-dessus">
        </label>
        <label class="banking-field">
          <span>N° de compte</span>
          <input type="text" name="account" required placeholder="Ex: 000123456789">
        </label>
      </div>
      <div class="banking-form-row">
        <label class="banking-field">
          <span>Montant</span>
          <input type="number" name="amount" min="100" required placeholder="25000">
        </label>
        <label class="banking-field">
          <span>Devise</span>
          <select name="currency">
            <option value="CDF">CDF</option>
            <option value="USD">USD</option>
          </select>
        </label>
      </div>
      <label class="banking-field">
        <span>Titulaire du compte (optionnel)</span>
        <input type="text" name="holder" placeholder="Nom du bénéficiaire">
      </label>
      <div class="banking-form-actions">
        <button class="btn btn-primary" type="submit">
          <i class="fa-solid fa-paper-plane"></i> Envoyer le virement
        </button>
      </div>
    </form>
  </section>

  <!-- ═══ Tab: External bank ═══ -->
  <section class="banking-panel" data-tab-content="external" style="display:none">
    <div class="banking-panel-header">
      <h2><i class="fa-solid fa-globe"></i> Virement externe</h2>
      <span class="banking-info-badge">Frais 2,5%</span>
    </div>
    <p style="margin:0;color:var(--color-subtle);font-size:0.9rem">Envoyez de l'argent vers n'importe quelle banque, partenaire ou non.</p>

    <form class="banking-form" data-external-form>
      <div class="banking-form-row">
        <label class="banking-field">
          <span>Nom de la banque</span>
          <input type="text" name="bank" required placeholder="Ex: Access Bank, BIC, etc.">
        </label>
        <label class="banking-field">
          <span>Code SWIFT / BIC (optionnel)</span>
          <input type="text" name="swift" placeholder="Ex: BICOCDKI">
        </label>
      </div>
      <div class="banking-form-row">
        <label class="banking-field">
          <span>N° de compte / IBAN</span>
          <input type="text" name="account" required placeholder="Ex: CD68 0001 2345 6789">
        </label>
        <label class="banking-field">
          <span>Titulaire du compte</span>
          <input type="text" name="holder" required placeholder="Nom complet">
        </label>
      </div>
      <div class="banking-form-row">
        <label class="banking-field">
          <span>Montant</span>
          <input type="number" name="amount" min="100" required placeholder="25000">
        </label>
        <label class="banking-field">
          <span>Devise</span>
          <select name="currency">
            <option value="CDF">CDF</option>
            <option value="USD">USD</option>
          </select>
        </label>
      </div>
      <label class="banking-field banking-checkbox">
        <input type="checkbox" name="save_beneficiary" value="1">
        <span>Sauvegarder ce bénéficiaire pour plus tard</span>
      </label>
      <div class="banking-form-actions">
        <button class="btn btn-primary" type="submit">
          <i class="fa-solid fa-paper-plane"></i> Envoyer le virement
        </button>
      </div>
    </form>
  </section>

  <!-- ═══ Tab: Beneficiaries ═══ -->
  <section class="banking-panel" data-tab-content="beneficiaries" style="display:none">
    <div class="banking-panel-header">
      <h2><i class="fa-solid fa-address-book"></i> Bénéficiaires enregistrés</h2>
      <span class="banking-count-badge" data-benef-count>0</span>
    </div>
    <div class="benef-list" data-benef-list>
      <div class="benef-empty">
        <i class="fa-solid fa-users"></i>
        <span>Aucun bénéficiaire enregistré.</span>
      </div>
    </div>
  </section>

  <!-- ═══ Recent transfers ═══ -->
  <section class="banking-panel">
    <div class="banking-panel-header">
      <h2><i class="fa-solid fa-clock-rotate-left"></i> Derniers virements</h2>
    </div>
    <div class="banking-history" data-banking-history>
      <div class="benef-empty">
        <i class="fa-solid fa-arrow-right-arrow-left"></i>
        <span>Aucun virement effectué récemment.</span>
      </div>
    </div>
  </section>
</div>

<!-- confirm modal -->
<dialog class="modal" data-banking-confirm-modal aria-labelledby="banking-confirm-title">
  <div class="modal-card">
    <button class="modal-close" type="button" data-close-banking-confirm aria-label="Fermer">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <h2 id="banking-confirm-title">Confirmer le virement</h2>
    <p class="modal-copy">Vérifiez les informations avant de confirmer.</p>
    <div class="banking-confirm-detail" data-banking-confirm-detail></div>
    <div class="banking-confirm-actions">
      <button class="btn btn-soft" type="button" data-close-banking-confirm>Annuler</button>
      <button class="btn btn-primary" type="button" data-confirm-banking-submit>
        <i class="fa-solid fa-check-circle"></i> Confirmer le virement
      </button>
    </div>
  </div>
</dialog>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>