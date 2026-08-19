<?php

/**
 * PresenceController — Prise d'appel et historique présences (enseignant).
 */
class PresenceController
{
    private PresenceModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/PresenceModel.php';
        $this->model = new PresenceModel();

        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
        if (!in_array(Session::get('role'), ['enseignant', 'censeur'])) {
            Session::setFlash('error', 'Accès non autorisé.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard'); exit();
        }
    }

    // ── Formulaire d'appel ───────────────────────────────────
    public function form(): void
    {
        $idEnseignant = (int)Session::get('user_id');
        $idSeance     = (int)($_GET['seance'] ?? 0);

        // Charger les classes de l'enseignant
        require_once APP_ROOT . '/app/models/SeanceModel.php';
        $seanceModel = new SeanceModel();
        $classes     = $seanceModel->getClassesByEnseignant($idEnseignant);

        $idClasse = (int)($_GET['classe'] ?? 0);
        $seances  = $idClasse > 0
            ? $this->model->getSeancesForAppel($idEnseignant, $idClasse)
            : [];

        $eleves   = [];
        $presences= [];
        $seanceInfo = null;

        if ($idSeance > 0) {
            $seanceInfo = $this->model->getSeanceInfo($idSeance);
            if ($seanceInfo) {
                $idClasse = (int)$seanceInfo['id_classe'];
                $eleves   = $this->model->getElevesByClasse($idClasse);
                $presences= $this->model->getPresencesBySeance($idSeance);
                // Recharger les séances de la classe
                $seances = $this->model->getSeancesForAppel($idEnseignant, $idClasse);
            }
        }

        $csrf      = Session::generateCsrf();
        $pageTitle = 'Appel & Présence — ' . APP_NAME;
        $extraCss  = 'presence.css';
        include APP_ROOT . '/app/views/presence/form.php';
    }

    // ── Sauvegarder l'appel ──────────────────────────────────
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=appel'); exit();
        }
        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=appel'); exit();
        }

        $idSeance = (int)($_POST['id_seance'] ?? 0);
        $statuts  = $_POST['statut']  ?? [];
        $motifs   = $_POST['motif']   ?? [];

        if ($idSeance <= 0 || empty($statuts)) {
            Session::setFlash('error', 'Données invalides.');
            header('Location: ' . APP_URL . '/app.php?page=appel'); exit();
        }

        $nb = $this->model->saveAppelComplet($idSeance, $statuts, $motifs);
        Session::setFlash('success', "Appel enregistré pour {$nb} élève(s).");
        header('Location: ' . APP_URL . '/app.php?page=appel&seance=' . $idSeance);
        exit();
    }

    // ── Historique d'une classe ──────────────────────────────
    public function historique(): void
    {
        $idClasse = (int)($_GET['classe'] ?? 0);
        $annee    = trim($_GET['annee']   ?? '');

        require_once APP_ROOT . '/app/models/SeanceModel.php';
        $seanceModel = new SeanceModel();
        if (!$annee) $annee = $seanceModel->getAnneeCourante();

        $statsEleves = $idClasse > 0
            ? $this->model->getStatsClasse($idClasse, $annee)
            : [];

        // Charger les classes de l'enseignant
        $classes = $seanceModel->getClassesByEnseignant((int)Session::get('user_id'));

        $pageTitle = 'Historique présences — ' . APP_NAME;
        $extraCss  = 'presence.css';
        include APP_ROOT . '/app/views/presence/historique.php';
    }
}
