<?php

/**
 * EnseignantController — Gestion des comptes enseignants par le censeur.
 *
 * Routes :
 *   GET  app.php?page=gestion-enseignants          → index()
 *   POST form_action=add_enseignant                → add()
 *   POST form_action=edit_enseignant               → edit()
 *   POST form_action=toggle_actif                  → toggleActif()
 *   POST form_action=reset_password                → resetPassword()
 */
class EnseignantController
{
    private EnseignantModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/EnseignantModel.php';
        $this->model = new EnseignantModel();

        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }
        if (Session::get('role') !== 'censeur') {
            Session::setFlash('error', 'Accès réservé au censeur.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard');
            exit();
        }
    }

    // ── Page liste ───────────────────────────────────────────
    public function index(): void
    {
        $enseignants = $this->model->getAll();
        $csrf        = Session::generateCsrf();

        // Récupérer le mot de passe flash (affiché une seule fois après création/reset)
        $flashPassword    = Session::get('flash_password');
        $flashPasswordFor = Session::get('flash_password_for');
        Session::set('flash_password', null);
        Session::set('flash_password_for', null);

        $pageTitle = 'Gestion des enseignants — ' . APP_NAME;
        $extraCss  = 'enseignant.css';
        include APP_ROOT . '/app/views/enseignant/index.php';
    }

    // ── Créer un enseignant ──────────────────────────────────
    public function add(): void
    {
        $this->checkPost();

        $nom    = trim($_POST['nom']    ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = trim($_POST['email']  ?? '');
        $useDefault = isset($_POST['use_default_password']);
        $customPwd  = trim($_POST['custom_password'] ?? '');

        $errors = [];
        if (empty($nom))    $errors[] = "Le nom est obligatoire.";
        if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                            $errors[] = "Email invalide.";
        if ($this->model->emailExists($email))
                            $errors[] = "Cet email est déjà utilisé.";

        // Déterminer le mot de passe
        if ($useDefault) {
            $password = EnseignantModel::DEFAULT_PASSWORD;
        } else {
            if (strlen($customPwd) < 8)
                $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
            $password = $customPwd;
        }

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            $this->redirect();
        }

        $id = $this->model->create($nom, $prenom, $email, $password);

        if ($id > 0) {
            // Stocker le mdp en session pour l'afficher une seule fois
            Session::set('flash_password', $password);
            Session::set('flash_password_for', $prenom . ' ' . $nom);
            Session::setFlash('success',
                "Compte créé pour {$prenom} {$nom}. Le mot de passe temporaire est affiché ci-dessous.");
        } else {
            Session::setFlash('error', "Erreur lors de la création du compte.");
        }
        $this->redirect();
    }

    // ── Modifier nom/prénom/email ────────────────────────────
    public function edit(): void
    {
        $this->checkPost();

        $id     = (int)($_POST['id_utilisateur'] ?? 0);
        $nom    = trim($_POST['nom']    ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = trim($_POST['email']  ?? '');

        $errors = [];
        if ($id <= 0)    $errors[] = "Enseignant introuvable.";
        if (empty($nom)) $errors[] = "Le nom est obligatoire.";
        if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                         $errors[] = "Email invalide.";
        if ($this->model->emailExists($email, $id))
                         $errors[] = "Cet email est déjà utilisé.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->update($id, $nom, $prenom, $email);
            Session::setFlash('success', "Compte de {$prenom} {$nom} mis à jour.");
        }
        $this->redirect();
    }

    // ── Activer / Désactiver ─────────────────────────────────
    public function toggleActif(): void
    {
        $this->checkPost();

        $id    = (int)($_POST['id_utilisateur'] ?? 0);
        $actif = (int)($_POST['est_actif']      ?? 0);

        if ($id <= 0) {
            Session::setFlash('error', 'Enseignant introuvable.');
            $this->redirect();
        }

        $this->model->toggleActif($id, $actif ? 0 : 1); // bascule
        $label = $actif ? 'désactivé' : 'réactivé';
        Session::setFlash('success', "Compte {$label} avec succès.");
        $this->redirect();
    }

    // ── Réinitialiser le mot de passe ────────────────────────
    public function resetPassword(): void
    {
        $this->checkPost();

        $id         = (int)($_POST['id_utilisateur']  ?? 0);
        $useDefault = isset($_POST['use_default_pwd']);
        $customPwd  = trim($_POST['new_password'] ?? '');

        if ($id <= 0) {
            Session::setFlash('error', 'Enseignant introuvable.');
            $this->redirect();
        }

        $ens = $this->model->getById($id);
        if (!$ens) {
            Session::setFlash('error', 'Enseignant introuvable.');
            $this->redirect();
        }

        if ($useDefault) {
            $newPwd = EnseignantModel::DEFAULT_PASSWORD;
        } elseif (isset($_POST['use_generated_pwd'])) {
            $newPwd = EnseignantModel::generatePassword();
        } else {
            if (strlen($customPwd) < 8) {
                Session::setFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                $this->redirect();
            }
            $newPwd = $customPwd;
        }

        $ok = $this->model->resetPassword($id, $newPwd);
        if ($ok) {
            Session::set('flash_password', $newPwd);
            Session::set('flash_password_for', $ens['prenom'] . ' ' . $ens['nom']);
            Session::setFlash('success',
                "Mot de passe réinitialisé pour {$ens['prenom']} {$ens['nom']}. Communiquez-le manuellement.");
        } else {
            Session::setFlash('error', 'Erreur lors de la réinitialisation.');
        }
        $this->redirect();
    }

    // ── Helpers ──────────────────────────────────────────────
    private function checkPost(): void
    {
        $token = trim($_POST['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            $this->redirect();
        }
    }

    private function redirect(): void
    {
        header('Location: ' . APP_URL . '/app.php?page=gestion-enseignants');
        exit();
    }
}
