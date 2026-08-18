# AGENTS.md — Cahier de Texte Digital

> Dernière mise à jour : 2026-08-14 v3 — par Kiro (corrections fondations A)
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
├── index.php                        ← Front Controller UNIQUE — toutes les requêtes passent ici
├── .htaccess                        ← Bloque accès direct à app/, config/, core/ + réécriture URL
├── utilisateur.sql                  ← Script SQL complet (CREATE DATABASE + CREATE TABLE)
├── AGENTS.md                        ← Ce fichier
│
├── config/
│   └── config.php                   ← Constantes globales (DB_*, APP_*, TABLE_USERS)
│
├── core/                            ← Classes système, chargées manuellement dans index.php
│   ├── Database.php                 ← Singleton MySQLi — une seule connexion partagée
│   ├── Session.php                  ← Session + flash messages + CSRF
│   └── Router.php                   ← Routeur par ?page= (whitelist explicite)
│
├── app/
│   ├── controllers/                 ← Chargés dynamiquement par le Router
│   │   ├── AuthController.php       ← login / loginForm / register / registerForm / logout
│   │   └── DashboardController.php  ← index (page principale protégée)
│   ├── models/
│   │   └── UserModel.php            ← CRUD table `utilisateur` via MySQLi préparé
│   └── views/
│       ├── auth/
│       │   ├── login.php            ← Vue standalone (pas de layout) — HTML complet
│       │   └── register.php         ← Vue standalone (pas de layout) — HTML complet
│       ├── dashboard/
│       │   └── index.php            ← Inclut layouts/header.php + layouts/footer.php
│       ├── layouts/
│       │   ├── header.php           ← DOCTYPE + navbar (utilisé par les pages internes)
│       │   └── footer.php           ← Ferme </main></body></html>
│       └── errors/
│           └── 404.php              ← Page d'erreur 404
│
└── assets/
    ├── style.css                    ← Styles partagés : navbar, dashboard, cards, alerts
    └── auth.css                     ← Styles spécifiques login/register (fond dégradé)
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

- **Structure MVC** : Front Controller, Router, core classes (Database, Session, Router)
- **Authentification complète** :
  - Inscription (validation serveur, bcrypt, vérification email unique, CSRF)
  - Connexion (CSRF, message d'erreur générique intentionnel, session sécurisée)
  - Déconnexion (destruction complète session + cookie)
  - Protection CSRF sur tous les formulaires POST
  - Protection fixation de session (`session_regenerate_id`)
  - Cookie `httponly` + `SameSite: Strict`
  - Redirection automatique si déjà connecté
  - Re-remplissage formulaire après erreur (`old_input` en session)
- **Dashboard** : page protégée avec infos profil, session active, actions rapides
- **Navbar** : affichage dynamique connecté/non connecté + badge rôle
- **Gestion des rôles** : 5 rôles définis (voir section 6)
- **Nettoyage du projet** : tous les fichiers doublons supprimés

### 🚧 Fonctionnalités EN COURS

- Néant — en attente des prochaines instructions métier

### 📋 Fonctionnalités NON commencées (attendues pour un cahier de texte)

- Gestion des classes / matières / niveaux
- Saisie des cours par les enseignants (cahier de texte proprement dit)
- Consultation des cours par les élèves et parents
- Gestion des devoirs et évaluations
- Suivi des absences
- Tableau de bord différencié par rôle (enseignant vs élève vs admin)
- Modification du profil utilisateur
- Réinitialisation du mot de passe
- Module administration (gestion des utilisateurs)

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

- **Nom de base de données avec espace** : ~~`DB_NAME = 'mon projet'`~~ **Corrigé le 2026-08-14** → `DB_NAME = 'cahierdetexte'` dans `config/config.php` et `cahierdetexte.sql`.

- **`implode()` sans séparateur sur les erreurs** : ~~Bug dans `AuthController::register()`~~ **Corrigé le 2026-08-14** → `implode('<br>', $errors)`.

- **`est_actif` non vérifié au login** : ~~Présent en BDD mais jamais vérifié~~ **Corrigé le 2026-08-14** → vérification dans `AuthController::login()` avec message explicite.

- **`derniere_connexion` jamais mise à jour** : ~~Colonne BDD ignorée~~ **Corrigé le 2026-08-14** → `UserModel::updateLastLogin()` appelé à chaque login réussi.

- **`prenom` absent du flux** : ~~Colonne BDD non gérée côté PHP~~ **Corrigé le 2026-08-14** → `UserModel::create()`, `findByEmail()`, `findById()`, `Session::setUser()`, formulaire register, dashboard tous mis à jour.

- **`SESSION::getFlash()` affiche le HTML brut** : La méthode retourne du HTML avec `echo`. Si appelée dans un contexte où l'output est bufférisé différemment, penser à utiliser `echo Session::getFlash()` (déjà fait dans les vues).

- **"Se souvenir de moi" non implémenté côté serveur** : La vue login affiche la checkbox et lit `$_COOKIE['remember_email']`, mais `AuthController::login()` ne définit jamais ce cookie. La feature est visuelle seulement pour l'instant.

- **Dossiers `assets copy/` et `includes copy/`** : Étaient des artefacts de l'ancien projet. Supprimés. Ne pas les recréer.

- **`TABLE_utilisateur` vs `TABLE_USERS`** : L'ancienne config définissait `TABLE_utilisateur` (faute). La constante correcte est `TABLE_USERS` définie dans `config/config.php` et pointant vers la table `utilisateur`.

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

Les tâches suivantes sont ordonnées par dépendance logique :

1. **Corriger le bug `implode`** dans `AuthController::register()` (séparateur `<br>` manquant) — 5 min
2. **Implémenter "Se souvenir de moi"** dans `AuthController::login()` — définir le cookie 30j
3. **Créer les tables métier** : `classe`, `matiere`, `cours` (cahier de texte) — nécessite discussion avec le propriétaire sur le modèle de données
4. **Dashboard différencié par rôle** : afficher des cartes/modules différents selon `$user['role']`
5. **Module enseignant** : saisie d'une entrée de cahier de texte (date, matière, classe, contenu du cours, devoirs)
6. **Module élève/parent** : consultation du cahier de texte filtré par classe
7. **Module censeur** : vue globale de tous les cahiers, supervision
8. **Module administrateur** : gestion des utilisateurs (liste, activation, suppression)
9. **Page modification de profil** : changer nom / email / mot de passe
10. **Réinitialisation mot de passe** par email (nécessite configuration SMTP)

**Dépendances :** 3 → 4 → 5 → 6 → 7. Les tâches 1, 2, 9 sont indépendantes.

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
