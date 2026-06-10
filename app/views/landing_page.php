<header class="site-header" data-site-header>
  <div class="container header-shell">
    <a class="brand" href="?page=landing_page" aria-label="Africo Cash - Accueil">
      <img src="/assets/img/nav_brand.png" alt="" class="brand-mark" width="54" height="44">
      <span>Africo Cash</span>
    </a>

    <button class="nav-toggle" type="button" data-nav-toggle aria-controls="primary-navigation" aria-expanded="false">
      <span class="sr-only">Ouvrir le menu</span>
      <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>

    <nav class="primary-nav" id="primary-navigation" data-primary-nav aria-label="Navigation principale">
      <a href="#home" data-scroll-target="home">Accueil</a>
      <a href="#features" data-scroll-target="features">Services</a>
      <a href="#partners" data-scroll-target="partners">Partenaires</a>
      <a href="#security" data-scroll-target="security">Confiance</a>
      <a href="#contact" data-open-contact>Contact</a>
      <a href="<?= route_path('login') ?>">Connexion</a>
      <button class="theme-toggle" type="button" data-theme-toggle>
        <i class="fa-solid fa-moon" aria-hidden="true"></i>
        <span data-theme-label>Dark</span>
      </button>
      <a class="btn btn-primary" href="<?= route_path('register') ?>">
        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
        Créer un compte
      </a>
    </nav>
  </div>
</header>

<main id="main-content">
  <section class="hero-section section" id="home">
    <div class="container hero-grid">
      <div class="hero-copy reveal-on-load">
        <p class="eyebrow">Néobanque panafricaine</p>
        <h1>Votre argent circule plus vite, plus loin, plus simplement.</h1>
        <p class="hero-lead">Africo Cash réunit portefeuille, Mobile Money, banque et retraits sans carte dans une expérience claire, premium et sécurisée.</p>
        <div class="hero-actions">
          <a class="btn btn-primary btn-lg" href="<?= route_path('register') ?>">
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            Ouvrir un compte
          </a>
          <a class="btn btn-soft btn-lg" href="#features" data-scroll-target="features">
            Voir les services
          </a>
        </div>
        <dl class="hero-stats" aria-label="Indicateurs Africo Cash">
          <div>
            <dt>24/7</dt>
            <dd>accès au compte</dd>
          </div>
          <div>
            <dt>4</dt>
            <dd>services essentiels</dd>
          </div>
          <div>
            <dt>Multi</dt>
            <dd>devises & canaux</dd>
          </div>
        </dl>
      </div>

      <div class="hero-visual" aria-label="Aperçu du portefeuille Africo Cash">
        <div class="hero-stage float-card" aria-label="Slides Africo Cash">
          <article class="hero-slide wallet-slide" aria-label="Aperçu de portefeuille Africo Cash">
            <div class="phone-card">
              <div class="phone-topbar"></div>
              <p class="muted-label">Solde disponible</p>
              <strong>2 450 000 CDF</strong>
              <span class="wallet-currency">≈ 860 USD</span>
              <div class="quick-actions">
                <span><i class="fa-solid fa-paper-plane"></i> Envoyer</span>
                <span><i class="fa-solid fa-building-columns"></i> Banque</span>
                <span><i class="fa-solid fa-money-bill-transfer"></i> Retrait</span>
              </div>
            </div>
          </article>
          <figure class="hero-slide photo-slide">
            <img src="/assets/img/handsome-man-using-modern-smartphone-outdoors.jpg" alt="Client utilisant Africo Cash sur smartphone">
          </figure>
          <figure class="hero-slide photo-slide">
            <img src="/assets/img/stunned-displeased-shocked-african-american-teenage-woman-with-curly-hair-gasping-dropping-jaw-from-disappointment-looking-smartphone-screen.jpg" alt="Fille teenager regardant son téléphone">
          </figure>
          <figure class="hero-slide photo-slide">
            <img src="/assets/img/half-length-shot-afro-woman-holds-mobile-phone-enjoys-nice-talk-online-social-networks-reads-funny-article-internet.jpg" alt="Cliente Africo Cash utilisant son smartphone">
          </figure>
        </div>
      </div>
    </div>
  </section>

  <section class="section partners-section" id="partners">
    <div class="container">
      <div class="section-heading">
        <p class="eyebrow">Nos partenaires</p>
        <h2>Un réseau financier connecté.</h2>
      </div>
      <div class="partner-marquee" aria-label="Logos des partenaires Africo Cash">
        <div class="partner-track">
          <img src="/assets/img/airtel.jpg" alt="Airtel Money">
          <img src="/assets/img/Orange_logo.svg.png" alt="Orange Money">
          <img src="/assets/img/Africell_Logo.jpg" alt="Africell">
          <img src="/assets/img/vodacom.png" alt="Vodacom">
          <img src="/assets/img/rawbank.jpeg" alt="Rawbank">
          <img src="/assets/img/equity.png" alt="Equity BCDC">
          <img src="/assets/img/tmb.png" alt="TMB">
          <img src="/assets/img/ecobank.jpeg" alt="Ecobank">
          <img src="/assets/img/images.png" alt="Partenaire bancaire Africo Cash">
          <img src="/assets/img/png-clipart-point-of-exchange -retail.png" alt="Point de vente partenaire">
          <img src="/assets/img/airtel.jpg" alt="">
          <img src="/assets/img/Orange_logo.svg.png" alt="">
          <img src="/assets/img/Africell_Logo.jpg" alt="">
          <img src="/assets/img/vodacom.png" alt="">
          <img src="/assets/img/rawbank.jpeg" alt="">
          <img src="/assets/img/equity.png" alt="">
          <img src="/assets/img/tmb.png" alt="">
          <img src="/assets/img/ecobank.jpeg" alt="">
          <img src="/assets/img/images.png" alt="">
          <img src="/assets/img/png-clipart-point-of-exchange -retail.png" alt="">
        </div>
      </div>
    </div>
  </section>

  <section class="section" id="features">
    <div class="container">
      <div class="section-heading">
        <p class="eyebrow">Services clés</p>
        <h2>Une expérience financière pensée pour le quotidien.</h2>
        <p>Envoyez, recevez, payez et retirez votre argent avec une interface rapide, lisible et conçue pour inspirer confiance.</p>
      </div>

      <div class="feature-grid">
        <article class="feature-card" data-feature-card="Retrait ATM sans carte">
          <div class="icon-box"><i class="fa-solid fa-credit-card"></i></div>
          <h3>Retrait ATM sans carte</h3>
          <p>Retirez vos fonds avec un code sécurisé, même sans carte bancaire.</p>
        </article>
        <article class="feature-card" data-feature-card="Interopérabilité totale">
          <div class="icon-box"><i class="fa-solid fa-right-left"></i></div>
          <h3>Transferts fluides</h3>
          <p>Envoyez vers Africo Cash, banques partenaires, agents et Mobile Money.</p>
        </article>
        <article class="feature-card" data-feature-card="Commissions transparentes">
          <div class="icon-box"><i class="fa-solid fa-chart-line"></i></div>
          <h3>Frais lisibles</h3>
          <p>Consultez les frais avant validation et gardez le contrôle sur chaque opération.</p>
        </article>
        <article class="feature-card" data-feature-card="Sécurité avancée">
          <div class="icon-box"><i class="fa-solid fa-shield-halved"></i></div>
          <h3>Sécurité avancée</h3>
          <p>Des protections discrètes mais solides pour chaque connexion et transaction.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="section workflow-section" id="security">
    <div class="container">
      <div class="section-heading">
        <p class="eyebrow">Parcours client</p>
        <h2>Simple à ouvrir, agréable à utiliser.</h2>
      </div>
      <div class="step-grid">
        <article class="step-card">
          <span>01</span>
          <h3>Inscription</h3>
          <p>Créez votre compte avec votre email et un mot de passe sécurisé.</p>
        </article>
        <article class="step-card">
          <span>02</span>
          <h3>Portefeuille</h3>
          <p>Configurez vos informations et choisissez les services utiles à votre quotidien.</p>
        </article>
        <article class="step-card">
          <span>03</span>
          <h3>Transactions</h3>
          <p>Effectuez vos dépôts, transferts, paiements et retraits depuis un seul espace.</p>
        </article>
      </div>
    </div>
  </section>



  <section class="section">
    <div class="container cta-panel">
      <div>
        <p class="eyebrow">Africo Cash</p>
        <h2>Ouvrez votre compte et pilotez votre argent avec clarté.</h2>
      </div>
      <a class="btn btn-dark btn-lg" href="<?= route_path('register') ?>">
        Créer un compte
      </a>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a class="brand footer-brand" href="?page=landing_page" aria-label="Africo Cash - Accueil">
        <img src="/assets/img/nav_brand.png" alt="" class="brand-mark" width="40" height="40">
        <span>Africo Cash</span>
      </a>
      <p>© 2026 Africo Cash — Fintech inclusive pour l'Afrique.</p>
    </div>
    <nav class="footer-links" aria-label="Liens secondaires">
      <button type="button" data-footer-action="about">À propos</button>
      <button type="button" data-footer-action="legal">Mentions légales</button>
      <button type="button" data-footer-action="agent">Devenir agent</button>
      <button type="button" data-open-contact>Support</button>
    </nav>
  </div>
</footer>

<dialog class="modal" data-register-modal aria-labelledby="register-modal-title">
  <form class="modal-card" method="dialog" data-register-form novalidate>
    <button class="modal-close" type="button" data-close-modal aria-label="Fermer">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
    <p class="eyebrow">Ouverture de compte</p>
    <h2 id="register-modal-title">Créer un compte Africo Cash</h2>
    <p class="modal-copy">Laissez vos informations essentielles pour démarrer l’ouverture de compte.</p>

    <label class="form-field">
      <span>Nom complet</span>
      <input type="text" name="full_name" autocomplete="name" minlength="3" required placeholder="Grace Mbuyi">
    </label>
    <label class="form-field">
      <span>Téléphone</span>
      <input type="tel" name="phone" autocomplete="tel" required placeholder="+243800123456">
    </label>
    <label class="form-field">
      <span>Pays</span>
      <select name="country" required>
        <option value="CD">République démocratique du Congo</option>
      </select>
    </label>
    <button class="btn btn-primary btn-full" type="submit" data-submit-label>Commencer</button>
  </form>
</dialog>

<dialog class="modal" data-contact-modal aria-labelledby="contact-modal-title">
  <div class="modal-card">
    <button class="modal-close" type="button" data-close-modal aria-label="Fermer">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
    <p class="eyebrow">Contact</p>
    <h2 id="contact-modal-title">Contacter Africo Cash</h2>
    <p class="modal-copy">Notre équipe vous accompagne pour l'ouverture de compte, les paiements et les services partenaires.</p>
    <div class="contact-list">
      <a href="mailto:support@africocash.com"><i class="fa-solid fa-envelope"></i> support@africocash.com</a>
      <a href="tel:+243800123456"><i class="fa-solid fa-phone"></i> +243 800 123 456</a>
    </div>
  </div>
</dialog>
