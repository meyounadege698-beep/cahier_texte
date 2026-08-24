<?php

/**
 * DevoirController — Gestion des devoirs (enseignant).
 */
class DevoirController
{
    private DevoirModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/DevoirModel.php';
        $this->model = new DevoirModel();
        require_once APP_ROOT . '/core/Roles.php';
        Roles::requireEnseignant();
    }

    // ── Liste des devoirs ────────────────────────────────────
    public function index(): void
    {
        $idEnseignant = (int)Session::get('user_id');
        $devoirs      = $this->model->getDevoirsByEnseignant($idEnseignant);

        // Séances récentes pour le formulaire d'ajout
        require_once APP_ROOT . '/app/models/SeanceModel.php';
        $seanceModel = new SeanceModel();
        $seances     = $seanceModel->getSeancesRecentes($idEnseignant);

        $csrf      = Session::generateCsrf();
        $pageTitle = 'Mes devoirs — ' . APP_NAME;
        $extraCss  = 'devoir.css';
        include APP_ROOT . '/app/views/devoir/index.php';
    }

    // ── Créer un devoir ──────────────────────────────────────
    public function create(): void
    {
        $this->checkPost();
        $idEnseignant = (int)Session::get('user_id');
        $idSeance     = (int)($_POST['id_seance']    ?? 0);
        $titre        = trim($_POST['titre']         ?? '');
        $consigne     = trim($_POST['consigne']      ?? '');
        $type         = trim($_POST['type_devoir']   ?? 'DM');
        $dateRemise   = trim($_POST['date_remise']   ?? '');
        $coeff        = (int)($_POST['coeff_notation'] ?? 1);
        $noteSur      = (int)($_POST['note_sur']     ?? 20);

        $errors = [];
        if ($idSeance <= 0)    $errors[] = "Veuillez sélectionner une séance.";
        if (empty($titre))     $errors[] = "Le titre est obligatoire.";
        if (empty($consigne))  $errors[] = "La consigne est obligatoire.";
        if (empty($dateRemise))$errors[] = "La date de remise est obligatoire.";
        if (!in_array($type, ['DM','DS','EVAL','PROJET'])) $type = 'DM';

        // Vérifier que la séance appartient à l'enseignant
        require_once APP_ROOT . '/app/models/SeanceModel.php';
        $seanceModel = new SeanceModel();
        $seanceInfo  = $seanceModel->getSeanceById($idSeance, $idEnseignant);
        if (!$seanceInfo) $errors[] = "Séance introuvable.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->create($idSeance, $titre, $consigne, $type,
                                  $dateRemise, max(1, $coeff), max(1, $noteSur));
            Session::setFlash('success', "Devoir « {$titre} » créé.");
        }
        header('Location: ' . APP_URL . '/app.php?page=devoirs'); exit();
    }

    // ── Supprimer un devoir ──────────────────────────────────
    public function delete(): void
    {
        $this->checkPost();
        $idEnseignant = (int)Session::get('user_id');
        $idDevoir     = (int)($_POST['id_devoir'] ?? 0);

        if (!$this->model->belongsToEnseignant($idDevoir, $idEnseignant)) {
            Session::setFlash('error', 'Devoir introuvable.');
        } else {
            $this->model->delete($idDevoir);
            Session::setFlash('success', 'Devoir supprimé.');
        }
        header('Location: ' . APP_URL . '/app.php?page=devoirs'); exit();
    }

    private function checkPost(): void
    {
        $token = trim($_POST['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=devoirs'); exit();
        }
    }
}
