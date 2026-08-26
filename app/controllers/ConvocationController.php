<?php

/**
 * ConvocationController
 * Censeur/Admin : envoyer et gérer les convocations
 * Enseignant    : voir ses convocations
 */
class ConvocationController
{
    private ConvocationModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/ConvocationModel.php';
        $this->model = new ConvocationModel();
        require_once APP_ROOT . '/core/Roles.php';
        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
    }

    // ── Vue censeur : liste + formulaire envoi ───────────────
    public function index(): void
    {
        Roles::requireCenseur();
        $convocations = $this->model->getAll();
        require_once APP_ROOT . '/app/models/EnseignantModel.php';
        $enseignants  = (new EnseignantModel())->getAll();
        $csrf         = Session::generateCsrf();
        $pageTitle    = 'Convocations — ' . APP_NAME;
        $extraCss     = 'convocation.css';
        include APP_ROOT . '/app/views/convocation/index.php';
    }

    // ── Envoyer une convocation ──────────────────────────────
    public function envoyer(): void
    {
        Roles::requireCenseur();
        $token = trim($_POST['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=convocations'); exit();
        }

        $idEns   = (int)($_POST['id_enseignant']    ?? 0);
        $motif   = trim($_POST['motif']             ?? '');
        $date    = trim($_POST['date_convocation']  ?? '');
        $lieu    = trim($_POST['lieu']              ?? '') ?: null;

        $errors = [];
        if ($idEns <= 0)    $errors[] = "Veuillez sélectionner un enseignant.";
        if (empty($motif))  $errors[] = "Le motif est obligatoire.";
        if (empty($date))   $errors[] = "La date de convocation est obligatoire.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->create($idEns, (int)Session::get('user_id'), $motif, $date, $lieu);
            Session::setFlash('success', '✅ Convocation envoyée.');
        }
        header('Location: ' . APP_URL . '/app.php?page=convocations'); exit();
    }

    // ── Supprimer ────────────────────────────────────────────
    public function supprimer(): void
    {
        Roles::requireCenseur();
        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            header('Location: ' . APP_URL . '/app.php?page=convocations'); exit();
        }
        $id = (int)($_POST['id_convocation'] ?? 0);
        $this->model->delete($id);
        Session::setFlash('success', 'Convocation supprimée.');
        header('Location: ' . APP_URL . '/app.php?page=convocations'); exit();
    }

    // ── Vue enseignant : mes convocations ────────────────────
    public function mesConvocations(): void
    {
        Roles::requireEnseignant();
        $idEns        = (int)Session::get('user_id');
        $convocations = $this->model->getByEnseignant($idEns);
        // Marquer les non lues comme lues à l'ouverture
        foreach ($convocations as $c) {
            if ($c['statut'] === 'envoyee') {
                $this->model->marquerLue((int)$c['id_convocation'], $idEns);
            }
        }
        $csrf      = Session::generateCsrf();
        $pageTitle = 'Mes convocations — ' . APP_NAME;
        $extraCss  = 'convocation.css';
        include APP_ROOT . '/app/views/convocation/enseignant.php';
    }

    // ── Acquitter (enseignant confirme réception) ────────────
    public function acquitter(): void
    {
        Roles::requireEnseignant();
        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            header('Location: ' . APP_URL . '/app.php?page=mes-convocations'); exit();
        }
        $id = (int)($_POST['id_convocation'] ?? 0);
        $this->model->acquitter($id, (int)Session::get('user_id'));
        Session::setFlash('success', 'Convocation acquittée.');
        header('Location: ' . APP_URL . '/app.php?page=mes-convocations'); exit();
    }
}
