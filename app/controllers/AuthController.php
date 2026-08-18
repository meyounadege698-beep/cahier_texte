<?php

/**
 * AuthController — Gère l'inscription, la connexion et la déconnexion.
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

    /** Affiche le formulaire de connexion. */
    public function loginForm(): void
    {
        // Déjà connecté → page d'accueil
        if (Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/index.php');
            exit();
        }
        $csrf = Session::generateCsrf();
        include APP_ROOT . '/app/views/auth/login.php';
    }

    /** Traite la soumission du formulaire de connexion. */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/?page=login');
            exit();
        }

        // Vérification CSRF
        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide. Veuillez réessayer.');
            header('Location: ' . APP_URL . '/?page=login');
            exit();
        }

        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']       ?? '';

        // Validation basique
        $errors = [];
        if (empty($email))                              $errors[] = "L'email est obligatoire.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'adresse email est invalide.";
        if (empty($password))                           $errors[] = "Le mot de passe est obligatoire.";

        if (!empty($errors)) {
            Session::setFlash('error', implode(' ', $errors));
            header('Location: ' . APP_URL . '/?page=login');
            exit();
        }

        // Recherche utilisateur
        $user = $this->userModel->findByEmail($email);

        // Vérification mot de passe (message générique pour sécurité)
        if (!$user || !password_verify($password, $user['mot_de_passe_hash'])) {
            Session::setFlash('error', 'Email ou mot de passe incorrect.');
            header('Location: ' . APP_URL . '/?page=login');
            exit();
        }

        // Vérifier que le compte est actif
        if (!$user['est_actif']) {
            Session::setFlash('error', 'Votre compte a été désactivé. Contactez l\'administrateur.');
            header('Location: ' . APP_URL . '/?page=login');
            exit();
        }

        // Mettre à jour la date de dernière connexion
        $this->userModel->updateLastLogin((int) $user['id_utilisateur']);

        // Connexion réussie
        Session::setUser($user);
        Session::setFlash('success', 'Bienvenue, ' . htmlspecialchars($user['prenom'] ?: $user['nom']) . ' !');
        header('Location: ' . APP_URL . '/?page=dashboard');
        exit();
    }

    // =========================================================
    //  INSCRIPTION
    // =========================================================

    /** Affiche le formulaire d'inscription. */
    public function registerForm(): void
    {
        // Déjà connecté → page d'accueil
        if (Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/index.php');
            exit();
        }
        $csrf = Session::generateCsrf();
        $old  = Session::get('old_input') ?? [];
        Session::set('old_input', null);
        include APP_ROOT . '/app/views/auth/register.php';
    }

    /** Traite la soumission du formulaire d'inscription. */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/?page=register');
            exit();
        }

        // Vérification CSRF
        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide. Veuillez réessayer.');
            header('Location: ' . APP_URL . '/?page=register');
            exit();
        }

        // Récupération et nettoyage
        $nom      = trim($_POST['nom']      ?? '');
        $prenom   = trim($_POST['prenom']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']       ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $role     = trim($_POST['role']     ?? 'enseignant');

        // Sauvegarde pour re-remplissage du formulaire
        Session::set('old_input', ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'role' => $role]);

        // Validation
        $errors = [];
        if (empty($nom))                                        $errors[] = "Le nom est obligatoire.";
        if (strlen($nom) < 2)                                   $errors[] = "Le nom doit contenir au moins 2 caractères.";
        if (empty($prenom))                                     $errors[] = "Le prénom est obligatoire.";
        if (empty($email))                                      $errors[] = "L'email est obligatoire.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = "L'adresse email est invalide.";
        if (strlen($password) < 8)                              $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        if ($password !== $confirm)                             $errors[] = "Les mots de passe ne correspondent pas.";
        if (!in_array($role, ['enseignant', 'censeur', 'administrateur']))
                                                                $errors[] = "Le rôle sélectionné est invalide.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            header('Location: ' . APP_URL . '/?page=register');
            exit();
        }

        // Vérifier si l'email existe déjà
        if ($this->userModel->existsByEmail($email)) {
            Session::setFlash('error', 'Cette adresse email est déjà utilisée.');
            header('Location: ' . APP_URL . '/?page=register');
            exit();
        }

        // Création du compte
        $created = $this->userModel->create($nom, $prenom, $email, $password, $role);

        if (!$created) {
            Session::setFlash('error', "Une erreur est survenue. Veuillez réessayer.");
            header('Location: ' . APP_URL . '/?page=register');
            exit();
        }

        // Inscription réussie → redirection login
        Session::set('old_input', null);
        Session::setFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
        header('Location: ' . APP_URL . '/?page=login');
        exit();
    }

    // =========================================================
    //  DÉCONNEXION
    // =========================================================

    public function logout(): void
    {
        Session::destroy();
        // Après déconnexion → page d'accueil principale
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}
