<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>

<div data-simple-content>

<header class="s-top">
  <div class="s-top__greeting">
    <span class="s-top__hello">Bonjour,</span>
    <strong data-user-name>--</strong>
  </div>
  <span class="s-top__date" data-current-date></span>
</header>

<section class="s-balance">
  <span class="s-balance__label">Votre solde</span>
  <div class="s-balance__value-wrapper">
    <span class="s-balance__value" data-total-balance>--</span>
  </div>
  <span class="s-balance__sub" data-balance-secondary></span>
</section>

<section class="s-last-tx" data-last-tx hidden>
  <span class="s-last-tx__label">Dernière opération</span>
  <div class="s-last-tx__content">
    <i class="fa-solid fa-clock-rotate-left"></i>
    <span data-tx-label class="s-last-tx__desc">--</span>
    <strong data-tx-amount class="s-last-tx__amount">--</strong>
  </div>
</section>

<section class="s-actions">
  <a href="/transactions" class="s-action s-action--send">
    <span class="s-action__icon"><i class="fa-solid fa-paper-plane"></i></span>
    <span class="s-action__label">Envoyer</span>
  </a>
  <a href="/transactions" class="s-action s-action--deposit">
    <span class="s-action__icon"><i class="fa-solid fa-arrow-down"></i></span>
    <span class="s-action__label">Déposer</span>
  </a>
  <a href="/factures" class="s-action s-action--bill">
    <span class="s-action__icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
    <span class="s-action__label">Payer une facture</span>
  </a>
</section>

<details class="s-more">
  <summary class="s-more__summary">
    <i class="fa-solid fa-grid-2"></i>
    <span>Plus d'options</span>
    <i class="fa-solid fa-chevron-down s-more__chevron"></i>
  </summary>
  <nav class="s-more__grid">
    <a href="/mobile-money" class="s-more__link">
      <i class="fa-solid fa-mobile-screen-button"></i> Mobile Money
    </a>
    <a href="/banques" class="s-more__link">
      <i class="fa-solid fa-building-columns"></i> Banques
    </a>
    <a href="/factures" class="s-more__link">
      <i class="fa-solid fa-file-invoice"></i> Factures
    </a>
    <a href="/DAB" class="s-more__link">
      <i class="fa-solid fa-money-bills"></i> DAB
    </a>
    <a href="/USSD" class="s-more__link">
      <i class="fa-solid fa-mobile-screen"></i> USSD
    </a>
    <a href="/liens-paiement" class="s-more__link">
      <i class="fa-solid fa-qrcode"></i> Liens de paiement
    </a>
    <a href="/transactions" class="s-more__link">
      <i class="fa-solid fa-right-left"></i> Toutes les transactions
    </a>
  </nav>
</details>

</div>

<div class="s-advanced-toggle">
  <button class="s-toggle-btn" data-toggle-advanced>
    <i class="fa-solid fa-chart-simple"></i>
    <span data-toggle-label>Voir les statistiques détaillées</span>
    <i class="fa-solid fa-chevron-down s-toggle-chevron"></i>
  </button>
</div>

<div class="s-advanced" data-advanced-content hidden>

  <section class="metric-grid">
    <article class="metric-card metric-card--primary">
      <div class="metric-card__top">
        <span class="metric-card__label">Solde total</span>
        <span class="metric-card__icon"><i class="fa-solid fa-wallet"></i></span>
      </div>
      <strong class="metric-card__value" data-total-balance-full>--</strong>
      <div class="metric-card__bottom">
        <span class="metric-card__chip" data-balance-delta>--</span>
        <div class="sparkline" data-sparkline-balance aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
      </div>
      <small class="metric-card__sub" data-account-count>Chargement…</small>
    </article>
    <article class="metric-card">
      <div class="metric-card__top">
        <span class="metric-card__label">Entrées</span>
        <span class="metric-card__icon metric-card__icon--income"><i class="fa-solid fa-arrow-trend-up"></i></span>
      </div>
      <strong class="metric-card__value" data-income-total>--</strong>
      <div class="metric-card__bottom">
        <span class="metric-card__chip chip--up" data-income-delta>--</span>
        <div class="sparkline sparkline--income" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
      </div>
      <small class="metric-card__sub">Transactions créditées</small>
    </article>
    <article class="metric-card">
      <div class="metric-card__top">
        <span class="metric-card__label">Sorties</span>
        <span class="metric-card__icon metric-card__icon--outcome"><i class="fa-solid fa-arrow-trend-down"></i></span>
      </div>
      <strong class="metric-card__value" data-outcome-total>--</strong>
      <div class="metric-card__bottom">
        <span class="metric-card__chip chip--down" data-outcome-delta>--</span>
        <div class="sparkline sparkline--outcome" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
      </div>
      <small class="metric-card__sub">Transactions débitées</small>
    </article>
    <article class="metric-card">
      <div class="metric-card__top">
        <span class="metric-card__label">Épargne</span>
        <span class="metric-card__icon metric-card__icon--savings"><i class="fa-solid fa-piggy-bank"></i></span>
      </div>
      <strong class="metric-card__value" data-savings-rate>--</strong>
      <div class="metric-card__bottom">
        <span class="metric-card__chip chip--neutral">ce mois</span>
        <div class="sparkline sparkline--savings" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
      </div>
      <small class="metric-card__sub">du solde disponible</small>
    </article>
  </section>

  <section class="monthly-summary" aria-label="Résumé mensuel">
    <div class="msum-card">
      <div class="msum-card__top">
        <span class="msum-card__label">Ce mois</span>
        <span class="msum-card__trend msum-card__trend--up" data-month-income-delta>--</span>
      </div>
      <strong class="msum-card__value" data-month-income>--</strong>
      <div class="msum-card__bar-row">
        <div class="msum-card__bar-bg">
          <div class="msum-card__bar-fill msum-card__bar-fill--income" data-month-income-bar></div>
        </div>
        <span class="msum-card__bar-sub" data-month-income-vs>vs --</span>
      </div>
    </div>
    <div class="msum-card">
      <div class="msum-card__top">
        <span class="msum-card__label">Dépenses</span>
        <span class="msum-card__trend msum-card__trend--down" data-month-expense-delta>--</span>
      </div>
      <strong class="msum-card__value" data-month-expense>--</strong>
      <div class="msum-card__bar-row">
        <div class="msum-card__bar-bg">
          <div class="msum-card__bar-fill msum-card__bar-fill--expense" data-month-expense-bar></div>
        </div>
        <span class="msum-card__bar-sub" data-month-expense-vs>vs --</span>
      </div>
    </div>
    <div class="msum-card">
      <div class="msum-card__top">
        <span class="msum-card__label">Transactions</span>
        <span class="msum-card__trend msum-card__trend--up" data-month-tx-delta>--</span>
      </div>
      <strong class="msum-card__value" data-month-tx-count>--</strong>
      <div class="msum-card__bar-row">
        <div class="msum-card__bar-bg">
          <div class="msum-card__bar-fill msum-card__bar-fill--tx" data-month-tx-bar></div>
        </div>
        <span class="msum-card__bar-sub" data-month-tx-vs>vs --</span>
      </div>
    </div>
  </section>

  <section class="dashboard-hero">
    <article class="panel balance-panel">
      <div class="balance-panel__head">
        <div>
          <p class="eyebrow">Vue analytique</p>
          <h2>Flux financiers</h2>
          <p class="balance-panel__desc">Évolution du solde et des flux sur les dernières périodes.</p>
        </div>
        <div class="period-tabs" role="tablist" aria-label="Période">
          <button class="period-tab period-tab--active" data-period="7j" role="tab">7J</button>
          <button class="period-tab" data-period="1m" role="tab">1M</button>
          <button class="period-tab" data-period="3m" role="tab">3M</button>
        </div>
      </div>
      <div class="balance-chart" aria-label="Évolution du solde" data-balance-chart>
        <div class="bar-col"><span class="bar-col__fill" style="--value:30%"></span><span class="bar-col__lbl">L</span></div>
        <div class="bar-col"><span class="bar-col__fill" style="--value:42%"></span><span class="bar-col__lbl">M</span></div>
        <div class="bar-col"><span class="bar-col__fill" style="--value:54%"></span><span class="bar-col__lbl">M</span></div>
        <div class="bar-col"><span class="bar-col__fill" style="--value:48%"></span><span class="bar-col__lbl">J</span></div>
        <div class="bar-col"><span class="bar-col__fill" style="--value:67%"></span><span class="bar-col__lbl">V</span></div>
        <div class="bar-col"><span class="bar-col__fill" style="--value:72%"></span><span class="bar-col__lbl">S</span></div>
        <div class="bar-col"><span class="bar-col__fill" style="--value:81%"></span><span class="bar-col__lbl">D</span></div>
      </div>
    </article>
    <article class="panel split-panel">
      <h2>Répartition</h2>
      <div class="donut-chart" aria-label="Répartition des usages">
        <span class="donut-chart__value" data-donut-value>--</span>
        <span class="donut-chart__sub">Transferts</span>
      </div>
      <div class="legend-list">
        <div class="legend-item">
          <span class="legend-dot legend-dot--transfer" aria-hidden="true"></span>
          <p>Transferts</p>
          <strong data-transfer-ratio>--</strong>
        </div>
        <div class="legend-item">
          <span class="legend-dot legend-dot--bills" aria-hidden="true"></span>
          <p>Factures</p>
          <strong data-bill-ratio>--</strong>
        </div>
        <div class="legend-item">
          <span class="legend-dot legend-dot--cash" aria-hidden="true"></span>
          <p>Retraits</p>
          <strong data-cash-ratio>--</strong>
        </div>
      </div>
    </article>
  </section>

  <section class="content-grid two-columns">
    <article class="panel activity-panel">
      <div class="panel__head">
        <h2>Activité récente</h2>
        <a class="panel__see-all" href="/transactions">
          Voir tout <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
      <div class="activity-list" data-recent-transactions>
        <div class="activity-item activity-item--loading">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <span>Chargement des opérations…</span>
          <strong>--</strong>
        </div>
      </div>
    </article>
    <div class="side-col">
      <article class="panel quick-panel">
        <div class="panel__head"><h2>Actions rapides</h2></div>
        <div class="quick-grid">
          <button class="quick-btn" data-quick-link="/transactions">
            <span class="quick-btn__icon"><i class="fa-solid fa-paper-plane"></i></span>
            <span class="quick-btn__label">Envoyer</span>
          </button>
          <button class="quick-btn" data-quick-link="/transactions">
            <span class="quick-btn__icon"><i class="fa-solid fa-arrow-down"></i></span>
            <span class="quick-btn__label">Dépôt</span>
          </button>
          <button class="quick-btn" data-quick-link="/transactions">
            <span class="quick-btn__icon"><i class="fa-solid fa-money-bill-transfer"></i></span>
            <span class="quick-btn__label">Retrait</span>
          </button>
          <button class="quick-btn" data-quick-link="/mobile-money">
            <span class="quick-btn__icon"><i class="fa-solid fa-mobile-screen-button"></i></span>
            <span class="quick-btn__label">Mobile Money</span>
          </button>
        </div>
      </article>
      <article class="panel trust-panel">
        <div class="panel__head">
          <h2><i class="fa-solid fa-shield-halved"></i> Cote sociale</h2>
        </div>
        <div class="trust-widget" data-trust-widget>
          <div class="trust-loading">Chargement...</div>
        </div>
      </article>
      <article class="panel goals-panel">
        <div class="panel__head"><h2>Objectifs d'épargne</h2></div>
        <div class="goals-list" data-goals-list>
          <div class="goal-item">
            <div class="goal-item__top">
              <span class="goal-item__name" data-goal-1-name>Fonds d'urgence</span>
              <span class="goal-item__amount" data-goal-1-amount>-- / --</span>
            </div>
            <div class="goal-item__bar-bg">
              <div class="goal-item__bar goal-item__bar--1" data-goal-1-bar style="width:0%"></div>
            </div>
            <span class="goal-item__pct" data-goal-1-pct>--%</span>
          </div>
          <div class="goal-item">
            <div class="goal-item__top">
              <span class="goal-item__name" data-goal-2-name>Voyage annuel</span>
              <span class="goal-item__amount" data-goal-2-amount>-- / --</span>
            </div>
            <div class="goal-item__bar-bg">
              <div class="goal-item__bar goal-item__bar--2" data-goal-2-bar style="width:0%"></div>
            </div>
            <span class="goal-item__pct" data-goal-2-pct>--%</span>
          </div>
          <div class="goal-item">
            <div class="goal-item__top">
              <span class="goal-item__name" data-goal-3-name>Achat véhicule</span>
              <span class="goal-item__amount" data-goal-3-amount>-- / --</span>
            </div>
            <div class="goal-item__bar-bg">
              <div class="goal-item__bar goal-item__bar--3" data-goal-3-bar style="width:0%"></div>
            </div>
            <span class="goal-item__pct" data-goal-3-pct>--%</span>
          </div>
        </div>
      </article>
    </div>
  </section>

</div>

<link rel="stylesheet" href="/assets/css/views/trust_score.css?v=1">
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
