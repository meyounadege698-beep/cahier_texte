<?php

/**
 * DashboardController — Page principale après connexion.
 * Affiche un dashboard différencié selon le rôle (enseignant / censeur / administrateur).
 */
class DashboardController
{
    private UserModel $userModel;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/UserModel.php';
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        if (!Session::isLoggedIn()) {
            Session::setFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }

        $userId = (int) Session::get('user_id');
        $user   = $this->userModel->findById($userId);

        if (!$user) {
            Session::destroy();
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }

        include APP_ROOT . '/app/views/dashboard/index.php';
    }
}
