<?php

/**
 * SupervisionController — Tableau de bord censeur.
 */
class SupervisionController
{
    private SupervisionModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/SupervisionModel.php';
        $this->model = new SupervisionModel();

        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
        if (Session::get('role') !== 'censeur') {
            Session::setFlash('error', 'Accès réservé au censeur.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard'); exit();
        }
    }

    public function index(): void
    {
        $annee          = trim($_GET['annee'] ?? $this->model->getAnneeCourante());
        $stats          = $this->model->getStatsGlobales($annee);
        $couverture     = $this->model->getTauxCouverture($annee);
        $alertes        = $this->model->getAlertesCahiersNonRemplis(7);
        $validations    = $this->model->getValidationsEnAttente();
        $activite       = $this->model->getActiviteRecente(10);

        $csrf      = Session::generateCsrf();
        $pageTitle = 'Supervision — ' . APP_NAME;
        $extraCss  = 'supervision.css';
        include APP_ROOT . '/app/views/supervision/index.php';
    }

    public function valider(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=supervision'); exit();
        }
        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=supervision'); exit();
        }

        $idProgression = (int)($_POST['id_progression'] ?? 0);
        $statut        = trim($_POST['statut_validation'] ?? 'APPROUVE');
        $commentaire   = trim($_POST['commentaire']      ?? '') ?: null;
        $ecart         = trim($_POST['ecart_programme']  ?? '') ?: null;
        $actions       = trim($_POST['actions_correctives'] ?? '') ?: null;

        if (!in_array($statut, ['APPROUVE', 'REFUSE'])) $statut = 'APPROUVE';

        $this->model->validerProgression(
            $idProgression, (int)Session::get('user_id'),
            $statut, $commentaire, $ecart, $actions
        );

        $label = $statut === 'APPROUVE' ? 'approuvée' : 'refusée';
        Session::setFlash('success', "Progression {$label}.");
        header('Location: ' . APP_URL . '/app.php?page=supervision'); exit();
    }
}
