<?php

/**
 * RapportController — Génération de rapports PDF via impression navigateur.
 *
 * Routes :
 *   GET app.php?page=rapports          → index() formulaire de sélection
 *   GET app.php?page=rapport-print     → print() page imprimable (ouvre onglet)
 */
class RapportController
{
    private RapportModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/RapportModel.php';
        $this->model = new RapportModel();
        require_once APP_ROOT . '/core/Roles.php';
        Roles::requireCenseur();
    }

    // ── Formulaire de sélection ──────────────────────────────
    public function index(): void
    {
        $annees       = $this->model->getAnnees();
        $annee        = $_GET['annee'] ?? ($annees[0] ?? '');
        $classes      = $this->model->getClasses($annee);
        $matieres     = $this->model->getMatieres();
        $enseignants  = $this->model->getEnseignants();
        $csrf         = Session::generateCsrf();
        $pageTitle    = 'Rapports — ' . APP_NAME;
        $extraCss     = 'rapport.css';
        include APP_ROOT . '/app/views/rapport/index.php';
    }

    // ── Page imprimable ──────────────────────────────────────
    public function print(): void
    {
        $type       = trim($_GET['type']       ?? 'progression');
        $idClasse   = (int)($_GET['classe']    ?? 0);
        $idMatiere  = (int)($_GET['matiere']   ?? 0);
        $idEns      = (int)($_GET['enseignant']?? 0) ?: null;
        $annee      = trim($_GET['annee']      ?? '');
        $dateDebut  = trim($_GET['date_debut'] ?? '');
        $dateFin    = trim($_GET['date_fin']   ?? '');

        $data     = [];
        $classe   = $idClasse  > 0 ? $this->model->getClasseById($idClasse)  : null;
        $matiere  = $idMatiere > 0 ? $this->model->getMatiereById($idMatiere) : null;

        switch ($type) {
            case 'progression':
                $data = $this->model->getProgression($idClasse, $idMatiere, $annee, $idEns);
                break;
            case 'presence':
                $data = $this->model->getPresence($idClasse, $dateDebut ?: '2000-01-01', $dateFin ?: date('Y-m-d'));
                break;
            case 'annuel':
                $data = $this->model->getAnnuel($annee);
                break;
        }

        // Page sans layout (standalone pour impression)
        include APP_ROOT . '/app/views/rapport/print.php';
    }
}
