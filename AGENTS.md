# AGENTS.md — Cahier de Texte Digital

> Dernière mise à jour : 2026-08-23 v7 — par Kiro (progression officielle v2 + wizard + accès admin universel)
> Ce fichier est la source de vérité du contexte projet. Le lire intégralement avant toute action.

---

## 1. Vue d'ensemble

**Nom :** Cahier de Texte Digital  
**Objectif métier :** Application web scolaire permettant à différents acteurs d'un établissement (élèves, enseignants, parents, censeur, administrateur) de gérer et consulter le cahier de texte numérique. Remplace le cahier papier.  
**Public cible :** Établissements scolaires francophones (contexte Cameroun détecté). Utilisateurs finaux : élèves, enseignants, parents d'élèves, censeurs, administrateurs.  
**Statut global :** MVP — authentification complète terminée, dashboard shell en place, fonctionnalités métier (cahier de texte proprement dit) non commencées.

---

## 2. Stack technique

| Composant | Choix | Version minimale |
|---|---|---|
| Langage serveur | PHP | 8.2 (type `mixed`, `?Type` utilisés) |
| Base de données | MariaDB / MySQL | 10.4+ |
| Extension PHP DB | MySQLi (natif) | — |
| Serveur local | XAMPP | Apache + PHP 8.2 |
| CSS | Vanilla CSS (pas de framework) | — |
| JS | Vanilla JS inline (toggle password uniquement) | — |
| Déploiement | Local XAMPP pour l'instant | — |

**Pas de Composer, pas de framework, pas d'ORM, pas de Node.js.** Tout est fait à la main en PHP procédural orienté MVC maison.

---

## 3. Architecture

```
cahier_texte/
├── index.php                        ← Page d'accueil visiteur (point d'entrée public)
├── app.php                          ← Front Controller MVC (toutes les routes app)
├── .htaccess                        ← Sécurité + réécriture URL
├── cahierdetexte.sql                ← Script SQL complet
├── migration_salles_affectations.sql← Migration : tables salle + colonnes id_salle
├── AGENTS.md                        ← Ce fichier
│
├── config/config.php                ← DB_*, APP_*, UPLOAD_*, TABLE_USERS
│
├── core/
│   ├── Database.php                 ← Singleton MySQLi
│   ├── Session.php                  ← Session + flash + CSRF
│   ├── Router.php                   ← Routeur ?page= (whitelist)
│   └── Uploader.php                 ← Upload sécurisé (MIME check, dossier /uploads/)
│
├── app/
│   ├── controllers/
│   │   ├── AuthController.php       ← login/register/logout
│   │   ├── DashboardController.php  ← dashboard différencié par rôle
│   │   ├── HomeController.php       ← page d'accueil visiteur
│   │   ├── EnseignantController.php ← CRUD enseignants + reset mdp (censeur)
│   │   ├── EleveController.php      ← CRUD classes + élèves (censeur)
│   │   ├── AffectationController.php← Salles + affectations multiples (censeur)
│   │   ├── CatalogueController.php  ← Depts + matières (censeur)
│   │   ├── ProgressionOfficielleController.php ← Programmes + chapitres (censeur)
│   │   ├── SupervisionController.php← Tableau de bord supervision (censeur)
│   │   ├── SeanceController.php     ← Saisie séances + upload (enseignant)
│   │   ├── BibliothequeController.php← Bibliothèque séances + upload (enseignant)
│   │   ├── PresenceController.php   ← Appel + présences + historique (enseignant)
│   │   ├── DevoirController.php     ← Devoirs rattachés aux séances (enseignant)
│   │   └── ApiController.php        ← Endpoints JSON AJAX (matières, points programme)
│   ├── models/
│   │   ├── UserModel.php            ← Auth (findByEmail, create, updateLastLogin)
│   │   ├── EnseignantModel.php      ← CRUD enseignants, generatePassword
│   │   ├── EleveModel.php           ← CRUD classes + élèves, recherche, matricule
│   │   ├── AffectationModel.php     ← Salles + affectations multiples
│   │   ├── CatalogueModel.php       ← Depts + matières CRUD
│   │   ├── ProgressionOfficielleModel.php ← Programmes + chapitres
│   │   ├── SupervisionModel.php     ← KPIs, alertes, taux couverture, validations
│   │   ├── PresenceModel.php        ← Appel, présences, stats assiduité
│   │   ├── DevoirModel.php          ← Devoirs rattachés séances
│   │   └── SeanceModel.php          ← Séances + pièces jointes + bibliothèque
│   └── views/
│       ├── home/index.php           ← Page visiteur complète
│       ├── auth/login.php           ← Vue connexion standalone
│       ├── auth/register.php        ← Vue inscription standalone
│       ├── dashboard/index.php      ← Dashboard différencié par rôle
│       ├── layouts/header.php       ← Navbar + DOCTYPE
│       ├── layouts/footer.php       ← Ferme body/html
│       ├── errors/404.php
│       ├── enseignant/index.php     ← Liste + inscription enseignants
│       ├── eleve/classes.php        ← CRUD classes
│       ├── eleve/eleves.php         ← CRUD élèves d'une classe
│       ├── affectation/index.php    ← Salles + vue globale affectations
│       ├── affectation/affecter.php ← Formulaire affectation multiple
│       ├── catalogue/index.php      ← Depts + matières
│       ├── progression/index.php    ← Liste programmes
│       ├── progression/create.php   ← Créer programme
│       ├── progression/detail.php   ← Chapitres + publication
│       ├── supervision/index.php    ← Tableau de bord censeur
│       ├── presence/form.php        ← Prise d'appel par séance
│       ├── presence/historique.php  ← Stats assiduité par classe
│       ├── devoir/index.php         ← Liste + création devoirs
│       └── seance/
│           ├── form.php             ← Saisie séance + upload
│           └── bibliotheque.php     ← Bibliothèque + réutilisation
│
├── assets/
│   ├── style.css                    ← Navbar, dashboard, cards, module-cards
│   ├── auth.css                     ← Login/register
│   ├── home.css                     ← Page d'accueil visiteur
│   ├── progression.css              ← Module progression officielle
│   ├── catalogue.css                ← Depts + matières
│   ├── affectation.css              ← Salles + affectations
│   ├── enseignant.css               ← Gestion enseignants
│   ├── eleve.css                    ← Classes + élèves
│   ├── supervision.css              ← Tableau de bord censeur
│   ├── presence.css                 ← Appel + présences
│   ├── devoir.css                   ← Module devoirs
│   ├── seance.css                   ← Saisie séance
│   └── bibliotheque.css             ← Bibliothèque séances
│
└── uploads/                         ← Fichiers uploadés (ignoré par .htaccess)
    └── {id_utilisateur}/            ← Sous-dossier par enseignant
```

**Pattern architectural :** MVC maison sans autoloader — les contrôleurs sont chargés par `require_once` dans le Router. Les modèles sont chargés par `require_once` dans le constructeur du contrôleur.

**Flux de données — connexion :**
```
GET /?page=login
  → Router → AuthController::loginForm() → inclut views/auth/login.php

POST /?page=login  (détecté dans index.php, redirigé vers do-login)
  → Router → AuthController::login()
      → Session::verifyCsrf()
      → UserModel::findByEmail()
      → password_verify()
      → Session::setUser()  [stocke user_id, nom, email, role]
      → Redirect /?page=dashboard
```

**Flux de données — page protégée :**
```
GET /?page=dashboard
  → Router → DashboardController::index()
      → Session::isLoggedIn() ? non → redirect login
      → UserModel::findById(session.user_id)  ← rechargement BDD à chaque accès
      → inclut views/dashboard/index.php
```

---

## 4. État actuel du projet

### ✅ Fonctionnalités TERMINÉES

- **Structure MVC** : Front Controller (`app.php`), Router, core (Database, Session, Router, Uploader, **Roles**)
- **Roles.php** : helper centralisé — `administrateur` a accès à TOUT (censeur + enseignant)
- **Page d'accueil visiteur** : `index.php` racine, présentation complète du projet
- **Authentification** : inscription, connexion, déconnexion, CSRF, bcrypt, `est_actif`, `derniere_connexion`, `prenom`
- **Dashboard différencié** : enseignant (7 modules), censeur (8 modules), **administrateur (11 modules = tout)**
- **Progression officielle V2 (wizard)** : structure par semaine conforme Ministère Cameroun — dept → matière → année → titre → semaines avec dates → chapitres+compétences → leçons (type/grand titre/heures) → objectifs atomisés. Prévisualisation temps réel. Publication + attribution enseignant.
- **Migration v3 appliquée** : `semaine_programme`, `objectif_lecon`, `objectif_atteint`, colonnes `type_lecon`/`grand_titre`/`nb_heures`
- **Ma progression** : vue enseignant sur les programmes attribués, statut par leçon, objectifs
- **Module censeur — Enseignants** : inscription mdp par défaut/personnalisé/généré, reset mdp
- **Module censeur — Affectations multiples** : N lignes (classe+matière+salle+volume)
- **Module censeur — Salles, Catalogue, Classes, Élèves, Supervision** : tous livrés
- **Module enseignant — Saisie séance, Bibliothèque, Appel, Présence, Devoirs** : tous livrés
- **Upload fichiers** : `core/Uploader.php` sécurisé, vérification MIME

### 🚧 Fonctionnalités EN COURS

- Néant — tous les modules planifiés sont livrés et syntaxiquement validés

### 📋 Fonctionnalités restantes (améliorations futures)

1. **Génération rapports PDF** — librairie externe nécessaire (ex: TCPDF ou DomPDF)
2. **Réinitialisation mot de passe par email** — nécessite config SMTP
3. **Mode hors-ligne avec synchronisation** — Service Worker / PWA
4. **Résumé automatique par IA** des notes du professeur — API externe

---

## 5. Journal des décisions techniques (ADR condensé)

- **Décision** : Pas de Composer / pas d'autoloader PSR-4  
  **Alternatives envisagées** : Composer + autoload  
  **Raison** : Projet de stage étudiant, environnement XAMPP simple, pas de dépendances tierces nécessaires  
  **Date** : 2026-08-13

- **Décision** : MySQLi natif plutôt que PDO  
  **Alternatives envisagées** : PDO  
  **Raison** : Base de code initiale utilisait MySQLi, cohérence maintenue. Les deux auraient convenu.  
  **Date** : 2026-08-13

- **Décision** : Routeur par `?page=` (query string) plutôt qu'URLs propres `/login`  
  **Alternatives envisagées** : Réécriture complète Apache (URLs propres)  
  **Raison** : Compatibilité maximale XAMPP sans configuration Apache avancée. Le `.htaccess` présent redirige vers index.php mais les liens internes utilisent `?page=`.  
  **Date** : 2026-08-13

- **Décision** : POST handlers séparés (`do-login`, `do-register`) dans le routeur  
  **Alternatives envisagées** : Même route pour GET et POST, détection dans le contrôleur  
  **Raison** : La détection GET/POST est faite dans `index.php` avant dispatch — le routeur reste simple et les routes sont explicites.  
  **Date** : 2026-08-13

- **Décision** : Les vues auth (`login.php`, `register.php`) sont des fichiers HTML complets sans layout partagé  
  **Alternatives envisagées** : Utiliser le même layout header/footer  
  **Raison** : Les pages auth ont un fond dégradé plein écran (`auth-body`) incompatible avec le layout navbar des pages internes. Deux CSS distincts : `auth.css` et `style.css`.  
  **Date** : 2026-08-13

- **Décision** : Le rôle `surveillant_general` a été ajouté puis supprimé  
  **Raison suppression** : Demande explicite du propriétaire du projet (2026-08-14). Remplacé uniquement par `censeur`.  
  **Date** : 2026-08-14

- **Décision** : Rechargement BDD de l'utilisateur à chaque accès au dashboard  
  **Alternatives envisagées** : Utiliser uniquement les données en session  
  **Raison** : Garantit que les données affichées sont toujours à jour si le compte est modifié entre deux requêtes.  
  **Date** : 2026-08-13

---

## 6. Conventions du projet

### Rôles utilisateurs (ENUM BDD + whitelist PHP)

| Valeur BDD | Libellé affiché | Icône dashboard |
|---|---|---|
| `eleve` | Élève | 🎓 |
| `enseignant` | Enseignant | 👨‍🏫 |
| `parent` | Parent / Tuteur | 👨‍👩‍👧 |
| `censeur` | Censeur | 🔍 |
| `administrateur` | Administrateur | ⚙️ |

**Important :** La liste des rôles valides est définie à 3 endroits — ils doivent TOUJOURS être synchronisés :
1. `utilisateur.sql` — ENUM de la colonne `role`
2. `app/controllers/AuthController.php` — `in_array()` ligne ~131
3. `app/views/auth/register.php` — tableau `$roles`

### Nommage

- Contrôleurs : `PascalCase` + suffixe `Controller`
- Méthodes contrôleur : `camelCase` (ex: `loginForm`, `registerForm`)
- Vues : `snake_case.php` dans le dossier correspondant au contrôleur
- Variables de vue transmises via `include` (scope partagé, pas de `extract`)
- Constantes globales : `UPPER_SNAKE_CASE` (ex: `APP_URL`, `TABLE_USERS`)

### Sécurité — règles non négociables

- Toujours `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` pour tout output HTML
- Toujours des requêtes préparées MySQLi (`prepare` + `bind_param`)
- Jamais de message d'erreur différenciant "email inconnu" vs "mauvais mot de passe"
- CSRF token obligatoire sur tous les formulaires POST

---

## 7. Pièges connus / erreurs déjà rencontrées

- **Nom de base de données avec espace** : ~~`DB_NAME = 'mon projet'`~~ **Corrigé 2026-08-14** → `DB_NAME = 'cahierdetexte'`.

- **`implode()` sans séparateur** : ~~Bug `AuthController::register()`~~ **Corrigé 2026-08-14** → `implode('<br>', $errors)`.

- **`est_actif` non vérifié au login** : **Corrigé 2026-08-14** → vérification dans `AuthController::login()`.

- **`derniere_connexion` jamais mise à jour** : **Corrigé 2026-08-14** → `UserModel::updateLastLogin()` à chaque login.

- **`prenom` absent du flux** : **Corrigé 2026-08-14** → partout (UserModel, Session, vues).

- **`fs_append` hors classe** : En 2026-08-18, `SeanceModel.php` a eu des méthodes ajoutées **après** le `}` de fermeture de classe via `fs_append`, causant un parse error. **Solution** : toujours réécrire le fichier complet (`fs_write`) plutôt qu'utiliser `fs_append` sur une classe PHP.

- **URLs `/?page=` vs `/app.php?page=`** : Le projet a deux points d'entrée — `index.php` (page visiteur) et `app.php` (front controller MVC). Tous les liens internes doivent pointer vers `app.php?page=`, pas `/?page=`. Vérifier systématiquement après modification de vues.

- **`DevoirModel::create()` — espace dans bind_param** : La chaîne de types contenait un espace (`"issssi i"`) au lieu de `"issssii"`. Corrigé 2026-08-19.

- **`migration_salles_affectations.sql`** : Doit être exécuté manuellement dans phpMyAdmin avant d'utiliser les modules Salles et Affectations. Ajoute la table `salle` et les colonnes `id_salle` dans `affectation_enseignant` et `seance`.

- **"Se souvenir de moi"** : Visuel seulement — la checkbox est affichée mais le cookie n'est jamais défini côté serveur. À implémenter si nécessaire.

- **Rôles en minuscules** : La BDD et le code PHP utilisent tous les deux les minuscules (`enseignant`, `censeur`, `administrateur`). Ne jamais écrire `ENSEIGNANT` en majuscules dans les comparaisons PHP ni dans les ENUM SQL.

- **`APP_ROOT`** : Défini comme `dirname(__DIR__)` dans `config/config.php` (qui est dans `/config/`). Donc `APP_ROOT` = racine du projet. Correct.

---

## 8. Variables d'environnement & configuration

Pas de fichier `.env` — configuration dans `config/config.php` directement.

| Constante | Valeur actuelle | À changer en production |
|---|---|---|
| `DB_HOST` | `localhost` | Adresse serveur DB prod |
| `DB_USER` | `root` | Utilisateur DB dédié (pas root) |
| `DB_PASS` | `''` (vide) | Mot de passe fort |
| `DB_NAME` | `cahierdetexte` | Inchangé en production |
| `APP_URL` | `http://localhost/cahier_texte` | URL de production (HTTPS) |
| `APP_NAME` | `Cahier de Texte` | Inchangé |
| `TABLE_USERS` | `utilisateur` | Inchangé |

**En production :** passer `secure => true` dans `Session::start()` (cookie HTTPS uniquement).

---

## 9. Prochaines étapes prioritaires

Tous les modules métier planifiés sont livrés. Les prochaines étapes sont des améliorations :

1. **Génération PDF** — Rapport progression + présences via formulaire (classe, matière, période). Nécessite TCPDF ou DomPDF via Composer.
2. **Réinitialisation mot de passe par email** — Lien de reset avec token, nécessite config SMTP (`php.ini` ou service tiers).
3. **Mode hors-ligne PWA** — Service Worker + cache API pour saisie sans connexion, sync au retour.
4. **Résumé IA des notes** — Appel API (OpenAI ou équivalent) sur `commentaire_enseignant` pour résumer.
5. **Import élèves en masse** — Upload CSV/Excel pour inscrire plusieurs élèves d'un coup.
6. **Convocations enseignants** — Génération et envoi de convocations depuis la supervision (table `convocation` déjà dans la BDD).

**État du projet :** MVP complet et fonctionnel. Toutes les fonctionnalités métier décrites dans la spécification initiale sont implémentées.

---

## 10. Glossaire métier

| Terme | Définition dans ce projet |
|---|---|
| **Cahier de texte** | Document numérique où l'enseignant consigne les cours dispensés, les devoirs donnés et les évaluations prévues, par classe et par matière |
| **Censeur** | Responsable pédagogique de l'établissement, supervise les enseignants et l'avancement des programmes |
| **Élève** | Apprenant inscrit dans une classe de l'établissement |
| **Enseignant** | Professeur qui saisit les entrées du cahier de texte |
| **Parent** | Tuteur légal d'un élève, accès en lecture uniquement |
| **Administrateur** | Gestionnaire technique de l'application (gestion des comptes, configuration) |
| **Établissement** | L'école / lycée qui utilise l'application |
| **Rôle** | Profil fonctionnel d'un utilisateur — détermine ses droits d'accès et son interface |
