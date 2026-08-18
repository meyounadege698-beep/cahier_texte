<?php

/**
 * AuthController — Inscription, connexion, déconnexion.
 *
 * Toutes les redirections pointent vers app.php?page=
 * Sauf post-logout → index.php (page visiteur)
 */
class AuthController
{
    private UserModel $userModel;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/UserModel.php';
        $this->userModel = new UserModel();
    }

    // =========================================================
    //  CONNEXION
    // =========================================================

    public function loginForm(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=dashboard');
            exit();
        }
        $csrf = Session::generateCsrf();
        include APP_ROOT . '/app/views/auth/login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide. Veuillez réessayer.');
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }

        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']       ?? '';

        $errors = [];
        if (empty($email))                              $errors[] = "L'email est obligatoire.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'adresse email est invalide.";
        if (empty($password))                           $errors[] = "Le mot de passe est obligatoire.";

        if (!empty($errors)) {
            Session::setFlash('error', implode(' ', $errors));
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }

        $user = $this->userModel->findByEmail($email);

        // Message générique intentionnel (ne pas distinguer email inconnu / mauvais mdp)
        if (!$user || !password_verify($password, $user['mot_de_passe_hash'])) {
            Session::setFlash('error', 'Email ou mot de passe incorrect.');
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }

        if (!(bool)$user['est_actif']) {
            Session::setFlash('error', "Votre compte est désactivé. Contactez l'administrateur.");
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }

        $this->userModel->updateLastLogin((int)$user['id_utilisateur']);
        Session::setUser($user);
        Session::setFlash('success', 'Bienvenue, ' . htmlspecialchars($user['prenom'] ?: $user['nom']) . ' !');
        header('Location: ' . APP_URL . '/app.php?page=dashboard');
        exit();
    }

    // =========================================================
    //  INSCRIPTION
    // =========================================================

    public function registerForm(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=dashboard');
            exit();
        }
        $csrf = Session::generateCsrf();
        $old  = Session::get('old_input') ?? [];
        Session::set('old_input', null);
        include APP_ROOT . '/app/views/auth/register.php';
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=register');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide. Veuillez réessayer.');
            header('Location: ' . APP_URL . '/app.php?page=register');
            exit();
        }

        $nom      = trim($_POST['nom']             ?? '');
        $prenom   = trim($_POST['prenom']          ?? '');
        $email    = trim($_POST['email']           ?? '');
        $password = $_POST['password']              ?? '';
        $confirm  = $_POST['confirm_password']      ?? '';
        $role     = trim($_POST['role']            ?? 'enseignant');

        Session::set('old_input', ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'role' => $role]);

        $errors = [];
        if (empty($nom))                                $errors[] = "Le nom est obligatoire.";
        if (strlen($nom) < 2)                           $errors[] = "Le nom doit contenir au moins 2 caractères.";
        if (empty($prenom))                             $errors[] = "Le prénom est obligatoire.";
        if (empty($email))                              $errors[] = "L'email est obligatoire.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'adresse email est invalide.";
        if (strlen($password) < 8)                      $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        if ($password !== $confirm)                     $errors[] = "Les mots de passe ne correspondent pas.";
        if (!in_array($role, ['enseignant', 'censeur', 'administrateur']))
                                                        $errors[] = "Le rôle sélectionné est invalide.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            header('Location: ' . APP_URL . '/app.php?page=register');
            exit();
        }

        if ($this->userModel->existsByEmail($email)) {
            Session::setFlash('error', 'Cette adresse email est déjà utilisée.');
            header('Location: ' . APP_URL . '/app.php?page=register');
            exit();
        }

        $created = $this->userModel->create($nom, $prenom, $email, $password, $role);

        if (!$created) {
            Session::setFlash('error', "Une erreur est survenue. Veuillez réessayer.");
            header('Location: ' . APP_URL . '/app.php?page=register');
            exit();
        }

        Session::set('old_input', null);
        Session::setFlash('success', 'Compte créé avec succès ! Connectez-vous.');
        header('Location: ' . APP_URL . '/app.php?page=login');
        exit();
    }

    // =========================================================
    //  DÉCONNEXION
    // =========================================================

    public function logout(): void
    {
        Session::destroy();
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}
