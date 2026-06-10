# CAHIER DES CHARGES TECHNIQUE ET FONCTIONNEL – AFRICO CASH

## VERSION PROFESSIONNELLE DE PRODUCTION

---

# DÉMARRAGE FRONTEND LOCAL

Attention : l'adresse correcte est `localhost`, pas `locahost`.

Depuis la racine du projet :

```bash
php -S localhost:8000 router.php
```

Puis ouvrir :

* `http://localhost:8000/`
* `http://localhost:8000/connexion`
* `http://localhost:8000/inscription`
* `http://localhost:8000/dashboard`
* `http://localhost:8000/portefeuille`
* `http://localhost:8000/transactions`
* `http://localhost:8000/mobile-money`
* `http://localhost:8000/banques`
* `http://localhost:8000/atm`
* `http://localhost:8000/factures`
* `http://localhost:8000/profil`
* `http://localhost:8000/admin`

Le fichier PHP sert uniquement de routeur/chargeur de vues. Les interactions frontend sont prévues en JavaScript vanilla avec Axios pour les appels API asynchrones.

---

# 1. PRÉSENTATION DU PROJET

Africo Cash est une plateforme de services financiers numériques développée par Africo Group.

La plateforme permet aux particuliers, entreprises et institutions de :

* Détenir un portefeuille électronique sécurisé.
* Envoyer et recevoir de l'argent.
* Effectuer des dépôts et retraits.
* Réaliser des transferts vers des banques.
* Réaliser des transferts vers les opérateurs Mobile Money.
* Effectuer des conversions monétaires.
* Payer des factures.
* Effectuer des retraits sur distributeurs automatiques (ATM) sans carte bancaire.
* Consulter leur historique de transactions.
* Gérer leur profil et leurs paramètres de sécurité.

Le système est accessible via :

* USSD (*400#)
* Application Web
* Application Mobile Android
* Application Mobile iOS
* Interface Agent
* Interface Administrateur

---

# 2. OBJECTIFS DU SYSTÈME

## Objectifs fonctionnels

* Fournir des services financiers accessibles à toute personne.
* Assurer l'interopérabilité avec les banques partenaires.
* Assurer l'interopérabilité avec les opérateurs Mobile Money.
* Réduire la dépendance aux cartes bancaires.
* Permettre les transactions en temps réel.
* Garantir la traçabilité complète des opérations.

## Objectifs techniques

* Haute disponibilité.
* Haute sécurité.
* Scalabilité horizontale.
* Tolérance aux pannes.
* Journalisation complète des opérations.
* Conformité KYC et AML.

---

# 3. TECHNOLOGIES RETENUES

## Frontend

Aucun framework frontend.

Technologies utilisées :

* HTML5
* CSS3
* JavaScript ES6+
* Web Components
* AJAX
* Fetch API

Objectif :

* Réduire la complexité.
* Réduire les coûts.
* Garantir la rapidité d'exécution.
* Contrôle complet du code.

---

## Backend

Technologies :

* PHP 8.5
* PostgreSQL
* Redis
* Nginx
* Linux Ubuntu Server

Le backend est développé sous forme de services modulaires.

---

# 4. ARCHITECTURE GÉNÉRALE

Le système est composé de plusieurs modules indépendants.

## Module Authentification

Responsabilités :

* Connexion.
* Déconnexion.
* Gestion des sessions.
* Gestion des tokens.
* Authentification à deux facteurs.
* Réinitialisation des mots de passe.

---

## Module Utilisateurs

Responsabilités :

* Création des comptes.
* Mise à jour des profils.
* Gestion KYC.
* Gestion des documents.

---

## Module Portefeuille

Responsabilités :

* Gestion des soldes.
* Gestion multi-devises.
* Historique des mouvements.

---

## Module Transactions

Responsabilités :

* Dépôts.
* Retraits.
* Transferts.
* Conversions.
* Paiements.

---

## Module Mobile Money

Responsabilités :

* Connexion Airtel Money.
* Connexion M-Pesa.
* Connexion Orange Money.
* Connexion Afrimoney.

---

## Module Bancaire

Responsabilités :

* Virements entrants.
* Virements sortants.
* Vérification des comptes.
* Réconciliation bancaire.

---

## Module ATM

Responsabilités :

* Génération des codes ATM.
* Validation des codes ATM.
* Autorisation de retrait.
* Journalisation des retraits.

---

## Module Notifications

Responsabilités :

* SMS.
* Email.
* Notifications Push.
* Alertes de sécurité.

---

## Module Commissions

Responsabilités :

* Calcul automatique.
* Répartition automatique.
* Génération des rapports.

---

## Module Administration

Responsabilités :

* Gestion des utilisateurs.
* Gestion des agents.
* Gestion des partenaires.
* Gestion des taux.
* Gestion des commissions.
* Audit.

---

# 5. NIVEAU DE SÉCURITÉ

## Sécurité des mots de passe

Les mots de passe sont :

* Hashés avec Argon2id.
* Jamais stockés en clair.
* Jamais transmis à un tiers.

---

## Authentification

Connexion avec :

* Numéro Africo.
* Mot de passe.
* OTP.

---

## Protection des sessions

* Cookies HttpOnly.
* Cookies Secure.
* SameSite Strict.
* Rotation automatique des sessions.

---

## Protection des API

* JWT signé.
* Signature des requêtes.
* Rate Limiting.
* Anti-Bruteforce.

---

## Protection des données

Toutes les données sensibles sont chiffrées.

Exemples :

* Numéros d'identité.
* Photos KYC.
* Numéros bancaires.
* Numéros Mobile Money.

---

## Journalisation

Chaque opération doit enregistrer :

* Utilisateur.
* Adresse IP.
* Date.
* Heure.
* Appareil.
* Résultat.

Aucune transaction ne peut être supprimée.

---

# 6. FONCTIONNALITÉS PRINCIPALES

## Dépôt

Sources autorisées :

* Agent Africo.
* Banque.
* Mobile Money.

Validation obligatoire :

* Vérification du compte.
* Vérification du montant.
* Vérification du partenaire.

---

## Retrait

Canaux :

* Agent.
* Banque.
* ATM.

Validation obligatoire :

* Authentification.
* Vérification du solde.
* Vérification des limites.

---

## Envoi d'argent

Destinations :

* Compte Africo.
* Banque.
* Mobile Money.

Fonctionnalités :

* Confirmation.
* Calcul des frais.
* Notification.

---

## Conversion monétaire

Devises :

* USD
* CDF

Fonctionnalités :

* Taux en temps réel.
* Historique.
* Confirmation.

---

## Paiement de factures

Services :

* Universités.
* Télévision.
* Internet.
* Eau.
* Électricité.

Fonctionnalités :

* Vérification préalable.
* Confirmation.
* Reçu électronique.

---

## Retrait ATM sans carte

Processus :

1. L'utilisateur demande un retrait.
2. Le système génère un code temporaire.
3. Le code expire automatiquement.
4. L'ATM vérifie le code.
5. L'argent est distribué.
6. Le portefeuille est débité.
7. Une notification est envoyée.

---

# 7. STRUCTURE DES ÉQUIPES

## Chef de Projet

Responsabilités :

* Planification.
* Validation.
* Coordination.
* Contrôle qualité.

---

## Développeur Backend Principal

Responsabilités :

* Architecture backend.
* API.
* Sécurité.
* Base de données.

---

## Développeur Backend Transactions

Responsabilités :

* Portefeuille.
* Dépôts.
* Retraits.
* Transferts.
* Commissions.

---

## Développeur Intégration

Responsabilités :

* Banques.
* Mobile Money.
* SMS.
* Passerelles externes.

---

## Développeur Frontend

Responsabilités :

* HTML.
* CSS.
* JavaScript.
* Accessibilité.
* Responsive Design.

---

## Ingénieur Sécurité

Responsabilités :

* Audit.
* Pentest.
* Surveillance.
* Gestion des incidents.

---

## Administrateur Système

Responsabilités :

* Linux.
* Nginx.
* Sauvegardes.
* Monitoring.
* Déploiement.

---

# 8. BASE DE DONNÉES

Principales tables :

* users
* user_profiles
* agents
* wallets
* wallet_balances
* transactions
* transaction_logs
* commissions
* banks
* mobile_money_providers
* atm_codes
* notifications
* audit_logs
* kyc_documents
* exchange_rates
* invoices
* partners

Toutes les tables doivent inclure :

* id
* created_at
* updated_at

---

# 9. EXIGENCES DE PERFORMANCE

Objectifs :

* Réponse API < 500 ms.
* Disponibilité > 99,9 %.
* Traitement simultané de milliers de transactions.
* Réplication de base de données.
* Sauvegardes automatiques.

---

# 10. LIVRABLES

Le projet doit être livré avec :

* Code source complet.
* Documentation technique.
* Documentation API.
* Documentation base de données.
* Manuel utilisateur.
* Manuel administrateur.
* Procédures de sauvegarde.
* Procédures de restauration.
* Procédures de sécurité.
* Scripts de déploiement.

---

# 11. CONCLUSION

Africo Cash doit être développé comme une infrastructure financière moderne, sécurisée, évolutive et interopérable.

L'architecture doit permettre une montée en charge progressive allant de quelques milliers à plusieurs millions d'utilisateurs sans refonte majeure du système.

L'objectif est de construire une plateforme capable de devenir un acteur majeur des services financiers numériques en Afrique centrale.
