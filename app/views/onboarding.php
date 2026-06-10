<main id="main-content" class="onboarding-page">
  <section class="onboarding-shell" data-onboarding-shell>
    <aside class="onboarding-aside">
      <a class="brand" href="<?= route_path('landing_page') ?>" aria-label="Africo Cash - Accueil">
        <img src="/assets/img/nav_brand.png" alt="" class="brand-mark" width="44" height="44">
        <span>Africo Cash</span>
      </a>
      <div>
        <p class="eyebrow">Configuration</p>
        <h1>Personnalisez votre expérience financière.</h1>
        <p>Chaque étape prépare votre dashboard, vos plafonds de démonstration et les modules Mobile Money, banque et ATM.</p>
      </div>
      <div class="onboarding-progress" aria-label="Progression onboarding">
        <span data-step-dot class="is-active"><i class="fa-solid fa-user"></i></span>
        <span data-step-dot><i class="fa-solid fa-briefcase"></i></span>
        <span data-step-dot><i class="fa-solid fa-shield-halved"></i></span>
      </div>
    </aside>

    <form class="onboarding-card" data-onboarding-form novalidate>
      <section class="onboarding-step is-active" data-step="0">
        <p class="eyebrow">Étape 1</p>
        <h2>Votre profil</h2>
        <div class="form-grid">
          <label class="form-field form-span">
            <span>Nom complet</span>
            <input name="full_name" autocomplete="name" required placeholder="Grace Mbuyi">
          </label>
          <label class="form-field">
            <span>Nom affiché</span>
            <input name="preferred_name" placeholder="Grace">
          </label>
          <label class="form-field">
            <span>Téléphone</span>
            <input name="phone" type="tel" autocomplete="tel" required placeholder="+243800123456">
          </label>
          <label class="form-field form-span">
            <span>Ville</span>
            <input name="city" autocomplete="address-level2" required placeholder="Kinshasa">
          </label>
        </div>
      </section>

      <section class="onboarding-step" data-step="1">
        <p class="eyebrow">Étape 2</p>
        <h2>Votre usage</h2>
        <div class="choice-grid">
          <label><input type="radio" name="primary_use" value="personal" checked><span><i class="fa-solid fa-user"></i> Personnel</span></label>
          <label><input type="radio" name="primary_use" value="business"><span><i class="fa-solid fa-store"></i> Business</span></label>
          <label><input type="radio" name="primary_use" value="agent"><span><i class="fa-solid fa-building-user"></i> Agent</span></label>
        </div>
        <div class="form-grid">
          <label class="form-field">
            <span>Volume mensuel</span>
            <select name="monthly_volume">
              <option value="starter">Moins de 500 USD</option>
              <option value="growth">500 à 5 000 USD</option>
              <option value="scale">Plus de 5 000 USD</option>
            </select>
          </label>
          <label class="form-field">
            <span>Devise par défaut</span>
            <select name="default_currency">
              <option value="CDF">CDF</option>
              <option value="USD">USD</option>
            </select>
          </label>
          <label class="form-field form-span">
            <span>Opérateur Mobile Money principal</span>
            <select name="mobile_operator">
              <option value="Airtel Money">Airtel Money</option>
              <option value="Orange Money">Orange Money</option>
              <option value="Afrimoney">Afrimoney</option>
              <option value="M-Pesa">M-Pesa</option>
            </select>
          </label>
        </div>
      </section>

      <section class="onboarding-step" data-step="2">
        <p class="eyebrow">Étape 3</p>
        <h2>Sécurité</h2>
        <div class="form-grid">
          <label class="form-field">
            <span>Profession</span>
            <input name="profession" autocomplete="organization-title" placeholder="Commerçant">
          </label>
          <label class="form-field">
            <span>Type de compte</span>
            <select name="account_type">
              <option value="personal">Personnel</option>
              <option value="business">Business</option>
              <option value="agent">Agent</option>
            </select>
          </label>
          <label class="form-field form-span">
            <span>PIN de sécurité</span>
            <input name="security_pin" inputmode="numeric" minlength="4" maxlength="4" pattern="[0-9]{4}" required placeholder="4 chiffres">
          </label>
        </div>
        <div class="security-preview">
          <i class="fa-solid fa-circle-check"></i>
          <span>Votre dashboard sera activé immédiatement après validation.</span>
        </div>
      </section>

      <div class="onboarding-actions">
        <button class="btn btn-soft" type="button" data-step-prev>
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          Retour
        </button>
        <button class="btn btn-primary" type="button" data-step-next>
          Continuer
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>
        <button class="btn btn-primary" type="submit" data-step-submit hidden>
          Finaliser
          <i class="fa-solid fa-check" aria-hidden="true"></i>
        </button>
      </div>
    </form>
  </section>
</main>
