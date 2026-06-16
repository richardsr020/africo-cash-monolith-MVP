<?php require __DIR__ . '/../partials/app_shell_start.php'; ?>
<div class="profile-page">
  <!-- Profile header -->
  <section class="profile-header">
    <div class="profile-avatar" data-profile-avatar>
      <span data-profile-initials>--</span>
    </div>
    <div class="profile-meta">
      <h1 data-profile-display-name>---</h1>
      <p class="profile-tag" data-profile-tag>Membre depuis ---</p>
      <div class="profile-badges">
        <span class="profile-badge" data-profile-kyc-badge>Identité en cours</span>
        <span class="profile-badge" data-profile-2fa-badge style="display:none">2FA</span>
        <span class="profile-badge" data-profile-role-badge>Utilisateur</span>
      </div>
    </div>
  </section>

  <!-- Tabs -->
  <div class="profile-tabs" data-profile-tabs>
    <button class="profile-tab is-active" data-tab="info"><i class="fa-solid fa-user-pen"></i> Infos</button>
    <button class="profile-tab" data-tab="security"><i class="fa-solid fa-shield-halved"></i> Sécurité</button>
    <button class="profile-tab" data-tab="preferences"><i class="fa-solid fa-bell"></i> Notifications</button>
    <button class="profile-tab" data-tab="sessions"><i class="fa-solid fa-laptop"></i> Sessions</button>
    <button class="profile-tab" data-tab="linked"><i class="fa-solid fa-link"></i> Liens</button>
    <button class="profile-tab" data-tab="activity"><i class="fa-solid fa-clock-rotate-left"></i> Activité</button>
  </div>

  <!-- ═══ Tab: Info ═══ -->
  <section class="profile-panel" data-tab-content="info">
    <div class="profile-panel-header">
      <h2><i class="fa-solid fa-user-pen"></i> Informations personnelles</h2>
    </div>
    <form class="profile-form" data-profile-form>
      <div class="profile-form-row">
        <label class="profile-field">
          <span>Nom complet</span>
          <input name="full_name" data-field="full_name" required>
        </label>
        <label class="profile-field">
          <span>Email</span>
          <input type="email" data-field="email" disabled>
        </label>
      </div>
      <div class="profile-form-row">
        <label class="profile-field">
          <span>N° Africo</span>
          <input data-field="afric_number" disabled>
        </label>
        <label class="profile-field">
          <span>Téléphone</span>
          <input name="phone" data-field="phone" required>
        </label>
      </div>
      <div class="profile-form-row">
        <label class="profile-field">
          <span>Ville</span>
          <input name="city" data-field="city">
        </label>
        <label class="profile-field">
          <span>Profession</span>
          <input name="profession" data-field="profession">
        </label>
      </div>
      <label class="profile-field">
        <span>Adresse</span>
        <input name="address" data-field="address">
      </label>
      <div class="profile-form-actions">
        <button class="btn btn-primary" type="submit">
          <i class="fa-solid fa-floppy-disk"></i> Enregistrer
        </button>
      </div>
    </form>
  </section>

  <!-- ═══ Tab: Security ═══ -->
  <section class="profile-panel" data-tab-content="security" style="display:none">
    <div class="profile-panel-header">
      <h2><i class="fa-solid fa-shield-halved"></i> Sécurité</h2>
    </div>

    <div class="profile-security-section">
      <h3><i class="fa-solid fa-lock"></i> Mot de passe</h3>
      <form class="profile-form" data-change-password-form>
        <div class="profile-form-row">
          <label class="profile-field">
            <span>Mot de passe actuel</span>
            <input type="password" name="current_password" required placeholder="********">
          </label>
          <label class="profile-field">
            <span>Nouveau mot de passe</span>
            <input type="password" name="new_password" required minlength="8" placeholder="8 caractères min">
          </label>
        </div>
        <div class="profile-form-actions">
          <button class="btn btn-primary" type="submit">
            <i class="fa-solid fa-key"></i> Changer le mot de passe
          </button>
        </div>
      </form>
    </div>

    <div class="profile-security-section">
      <h3><i class="fa-solid fa-circle-check"></i> PIN de sécurité</h3>
      <form class="profile-form" data-change-pin-form>
        <div class="profile-form-row">
          <label class="profile-field">
            <span>PIN actuel</span>
            <input type="password" name="current_pin" required maxlength="4" inputmode="numeric" pattern="[0-9]{4}" placeholder="4 chiffres">
          </label>
          <label class="profile-field">
            <span>Nouveau PIN</span>
            <input type="password" name="new_pin" required maxlength="4" inputmode="numeric" pattern="[0-9]{4}" placeholder="4 chiffres">
          </label>
        </div>
        <div class="profile-form-actions">
          <button class="btn btn-primary" type="submit">
            <i class="fa-solid fa-circle-check"></i> Changer le PIN
          </button>
        </div>
      </form>
    </div>

    <div class="profile-security-section">
      <h3><i class="fa-solid fa-mobile-screen-button"></i> Authentification à deux facteurs (2FA)</h3>
      <p style="margin:0;color:var(--color-subtle);font-size:0.85rem">Recevez un code de vérification à chaque connexion sur un nouvel appareil.</p>
      <div class="profile-2fa-status" data-2fa-status>
        <span class="profile-2fa-indicator" data-2fa-indicator>Désactivé</span>
        <button class="btn btn-primary" data-2fa-toggle type="button">
          <i class="fa-solid fa-shield"></i> <span data-2fa-btn-text>Activer</span>
        </button>
      </div>
    </div>
  </section>

  <!-- ═══ Tab: Preferences (Notifications) ═══ -->
  <section class="profile-panel" data-tab-content="preferences" style="display:none">
    <div class="profile-panel-header">
      <h2><i class="fa-solid fa-bell"></i> Préférences de notification</h2>
    </div>
    <div class="profile-prefs" data-prefs-list>
      <div class="pref-item">
        <div class="pref-info"><strong>Notifications SMS</strong><span>Alertes et confirmations par SMS</span></div>
        <label class="pref-toggle"><input type="checkbox" data-pref="notify_sms"><span class="pref-slider"></span></label>
      </div>
      <div class="pref-item">
        <div class="pref-info"><strong>Notifications email</strong><span>Reçus et relevés par email</span></div>
        <label class="pref-toggle"><input type="checkbox" data-pref="notify_email"><span class="pref-slider"></span></label>
      </div>
      <div class="pref-item">
        <div class="pref-info"><strong>Notifications push</strong><span>Alertes en temps réel dans l'application</span></div>
        <label class="pref-toggle"><input type="checkbox" data-pref="notify_push"><span class="pref-slider"></span></label>
      </div>
      <div class="pref-item">
        <div class="pref-info"><strong>Alertes de connexion</strong><span>Recevez une alerte en cas de nouvelle connexion</span></div>
        <label class="pref-toggle"><input type="checkbox" data-pref="login_alerts"><span class="pref-slider"></span></label>
      </div>
      <div class="pref-item">
        <div class="pref-info"><strong>Alertes de transaction</strong><span>Notifications pour chaque transaction</span></div>
        <label class="pref-toggle"><input type="checkbox" data-pref="transaction_alerts"><span class="pref-slider"></span></label>
      </div>
      <div class="pref-item">
        <div class="pref-info"><strong>Offres marketing</strong><span>Recevez des offres et promotions</span></div>
        <label class="pref-toggle"><input type="checkbox" data-pref="marketing"><span class="pref-slider"></span></label>
      </div>
    </div>
    <div class="profile-form-actions" style="margin-top:0.5rem">
      <button class="btn btn-primary" data-save-prefs type="button">
        <i class="fa-solid fa-floppy-disk"></i> Sauvegarder
      </button>
    </div>
  </section>

  <!-- ═══ Tab: Sessions ═══ -->
  <section class="profile-panel" data-tab-content="sessions" style="display:none">
    <div class="profile-panel-header">
      <h2><i class="fa-solid fa-laptop"></i> Sessions actives</h2>
      <span class="profile-count-badge" data-session-count>0</span>
    </div>
    <div class="profile-sessions" data-sessions-list>
      <div class="profile-empty">
        <i class="fa-solid fa-wifi"></i>
        <span>Aucune session active.</span>
      </div>
    </div>
  </section>

  <!-- ═══ Tab: Linked accounts ═══ -->
  <section class="profile-panel" data-tab-content="linked" style="display:none">
    <div class="profile-panel-header">
      <h2><i class="fa-solid fa-link"></i> Comptes liés</h2>
      <span class="profile-count-badge" data-linked-count>0</span>
    </div>
    <div class="profile-linked" data-linked-list>
      <div class="profile-empty">
        <i class="fa-solid fa-plug"></i>
        <span>Aucun compte lié.</span>
      </div>
    </div>
  </section>

  <!-- ═══ Tab: Activity ═══ -->
  <section class="profile-panel" data-tab-content="activity" style="display:none">
    <div class="profile-panel-header">
      <h2><i class="fa-solid fa-clock-rotate-left"></i> Activité récente</h2>
    </div>
    <div class="profile-activity" data-activity-list>
      <div class="profile-empty">
        <i class="fa-solid fa-arrow-right-arrow-left"></i>
        <span>Aucune activité récente.</span>
      </div>
    </div>
  </section>
</div>
<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>