<?php

/**
 * DashboardController — Page principale après connexion.
 */
class DashboardController
{
    private UserModel $userModel;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/UserModel.php';
        $this->userModel = new UserModel();
    }

    /**
     * Affiche le dashboard.
     * Redirige vers login si non connecté.
     */
    public function index(): void
    {
        if (!Session::isLoggedIn()) {
            Session::setFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            header('Location: ' . APP_URL . '/?page=login');
            exit();
        }

        // Récupérer les données fraîches depuis la BDD
        $userId = (int) Session::get('user_id');
        $user   = $this->userModel->findById($userId);

        if (!$user) {
            Session::destroy();
            header('Location: ' . APP_URL . '/?page=login');
            exit();
        }

        include APP_ROOT . '/app/views/dashboard/index.php';
    }
}
