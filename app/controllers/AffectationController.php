<?php

/**
 * AffectationController — Salles + affectations multiples (censeur).
 *
 * Routes :
 *   GET  app.php?page=gestion-affectations            → index()
 *   GET  app.php?page=affecter-enseignant&id=X        → affecterForm()
 *   POST form_action=affecter_multiple                → affecterMultiple()
 *   POST form_action=add_salle / edit_salle / delete_salle
 *   POST form_action=edit_aff / delete_aff
 */
class AffectationController
{
    private AffectationModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/AffectationModel.php';
        $this->model = new AffectationModel();

        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
        if (Session::get('role') !== 'censeur') {
            Session::setFlash('error', 'Accès réservé au censeur.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard'); exit();
        }
    }

    // ── Page liste globale ───────────────────────────────────
    public function index(): void
    {
        $annees       = $this->model->getAnnesScolaires();
        $annee        = trim($_GET['annee'] ?? ($annees[0] ?? ''));
        $salles       = $this->model->getAllSalles();
        $affectations = $this->model->getAffectationsByAnnee($annee);
        $enseignants  = $this->model->getAllEnseignants();
        $classes      = $this->model->getAllClasses($annee);
        $matieres     = $this->model->getAllMatieres();
        $departements = $this->model->getAllDepartements();
        $csrf         = Session::generateCsrf();

        $pageTitle = 'Salles & Affectations — ' . APP_NAME;
        $extraCss  = 'affectation.css';
        include APP_ROOT . '/app/views/affectation/index.php';
    }

    // ── Formulaire affectation multiple d'un enseignant ─────
    public function affecterForm(): void
    {
        $idEns = (int)($_GET['id'] ?? 0);
        if ($idEns <= 0) {
            Session::setFlash('error', 'Enseignant introuvable.');
            header('Location: ' . APP_URL . '/app.php?page=gestion-affectations'); exit();
        }

        require_once APP_ROOT . '/app/models/EnseignantModel.php';
        $ensModel   = new EnseignantModel();
        $enseignant = $ensModel->getById($idEns);
        if (!$enseignant) {
            Session::setFlash('error', 'Enseignant introuvable.');
            header('Location: ' . APP_URL . '/app.php?page=gestion-affectations'); exit();
        }

        $annees       = $this->model->getAnnesScolaires();
        $annee        = trim($_GET['annee'] ?? ($annees[0] ?? ''));
        $salles       = $this->model->getSallesActives();
        $departements = $this->model->getAllDepartements();
        $matieres     = $this->model->getAllMatieres();
        $classes      = $this->model->getAllClasses($annee);
        $affectations = $this->model->getAffectationsByEnseignant($idEns, $annee);
        $csrf         = Session::generateCsrf();

        $pageTitle = 'Affecter ' . $enseignant['prenom'] . ' ' . $enseignant['nom'] . ' — ' . APP_NAME;
        $extraCss  = 'affectation.css';
        include APP_ROOT . '/app/views/affectation/affecter.php';
    }

    // ── Soumettre une ou plusieurs affectations ──────────────
    public function affecterMultiple(): void
    {
        $this->checkPost();

        $idEns = (int)($_POST['id_utilisateur'] ?? 0);
        $annee = trim($_POST['annee_scolaire']  ?? '');

        if ($idEns <= 0 || empty($annee)) {
            Session::setFlash('error', 'Paramètres invalides.');
            $this->redirectEns($idEns, $annee);
        }

        // Reconstruire les lignes depuis les tableaux POST
        // Format POST : id_classe[], id_matiere[], id_salle[], volume[], principal[]
        $idClasses  = $_POST['id_classe']   ?? [];
        $idMatieres = $_POST['id_matiere']  ?? [];
        $idSalles   = $_POST['id_salle']    ?? [];
        $volumes    = $_POST['volume']      ?? [];
        $principals = $_POST['principal']   ?? [];

        if (empty($idClasses) || empty($idMatieres)) {
            Session::setFlash('error', 'Ajoutez au moins une ligne classe + matière.');
            $this->redirectEns($idEns, $annee);
        }

        $lignes = [];
        $count  = min(count($idClasses), count($idMatieres));
        for ($i = 0; $i < $count; $i++) {
            $lignes[] = [
                'id_classe'  => (int)($idClasses[$i]  ?? 0),
                'id_matiere' => (int)($idMatieres[$i] ?? 0),
                'id_salle'   => (int)($idSalles[$i]   ?? 0) ?: null,
                'volume'     => (int)($volumes[$i]     ?? 0) ?: null,
                'principal'  => isset($principals[$i]) ? 1 : 0,
            ];
        }

        try {
            $result = $this->model->addAffectationsMultiples($idEns, $annee, $lignes);
            $msg = "{$result['created']} affectation(s) créée(s).";
            if ($result['skipped'] > 0) {
                $msg .= " {$result['skipped']} doublon(s) ignoré(s).";
            }
            Session::setFlash('success', $msg);
        } catch (\Exception $e) {
            Session::setFlash('error', 'Erreur lors de la création des affectations.');
        }

        $this->redirectEns($idEns, $annee);
    }

    // =========================================================
    //  SALLES
    // =========================================================
    public function addSalle(): void
    {
        $this->checkPost();
        $nom          = trim($_POST['nom_salle']    ?? '');
        $capacite     = (int)($_POST['capacite']    ?? 0) ?: null;
        $type         = trim($_POST['type_salle']   ?? 'classe');
        $localisation = trim($_POST['localisation'] ?? '') ?: null;

        $errors = [];
        if (empty($nom)) $errors[] = "Le nom de la salle est obligatoire.";
        if ($this->model->salleNomExists($nom)) $errors[] = "Cette salle existe déjà.";
        if (!in_array($type, ['classe','laboratoire','salle_info','amphi','autre'])) $type = 'classe';

        if (!empty($errors)) Session::setFlash('error', implode('<br>', $errors));
        else {
            $this->model->addSalle($nom, $capacite, $type, $localisation);
            Session::setFlash('success', "Salle « {$nom} » créée.");
        }
        $this->redirect();
    }

    public function editSalle(): void
    {
        $this->checkPost();
        $id           = (int)($_POST['id_salle']    ?? 0);
        $nom          = trim($_POST['nom_salle']    ?? '');
        $capacite     = (int)($_POST['capacite']    ?? 0) ?: null;
        $type         = trim($_POST['type_salle']   ?? 'classe');
        $localisation = trim($_POST['localisation'] ?? '') ?: null;
        $active       = isset($_POST['est_active']) ? 1 : 0;

        $errors = [];
        if ($id <= 0)    $errors[] = "Salle introuvable.";
        if (empty($nom)) $errors[] = "Le nom est obligatoire.";
        if ($this->model->salleNomExists($nom, $id)) $errors[] = "Ce nom est déjà utilisé.";

        if (!empty($errors)) Session::setFlash('error', implode('<br>', $errors));
        else { $this->model->updateSalle($id, $nom, $capacite, $type, $localisation, $active);
               Session::setFlash('success', "Salle mise à jour."); }
        $this->redirect();
    }

    public function deleteSalle(): void
    {
        $this->checkPost();
        $id = (int)($_POST['id_salle'] ?? 0);
        if (!$this->model->deleteSalle($id))
            Session::setFlash('error', "Impossible : des affectations utilisent cette salle.");
        else
            Session::setFlash('success', "Salle supprimée.");
        $this->redirect();
    }

    // =========================================================
    //  AFFECTATION UNITAIRE (modifier / supprimer)
    // =========================================================
    public function editAffectation(): void
    {
        $this->checkPost();
        $id         = (int)($_POST['id_affectation']       ?? 0);
        $idSalle    = (int)($_POST['id_salle']             ?? 0) ?: null;
        $volHoraire = (int)($_POST['volume_horaire_hebdo'] ?? 0) ?: null;
        $principal  = isset($_POST['est_principal']) ? 1 : 0;
        $annee      = $_POST['annee_scolaire'] ?? '';

        if ($id <= 0) Session::setFlash('error', 'Affectation introuvable.');
        else { $this->model->updateAffectation($id, $idSalle, $volHoraire, $principal);
               Session::setFlash('success', "Affectation mise à jour."); }
        $this->redirect($annee);
    }

    public function deleteAffectation(): void
    {
        $this->checkPost();
        $id    = (int)($_POST['id_affectation'] ?? 0);
        $annee = $_POST['annee_scolaire'] ?? '';
        if (!$this->model->deleteAffectation($id))
            Session::setFlash('error', "Impossible de supprimer.");
        else
            Session::setFlash('success', "Affectation supprimée.");
        $this->redirect($annee);
    }

    // ── Helpers ──────────────────────────────────────────────
    private function checkPost(): void
    {
        $token = trim($_POST['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.'); $this->redirect();
        }
    }

    private function redirect(string $annee = ''): void
    {
        $url = APP_URL . '/app.php?page=gestion-affectations';
        if ($annee) $url .= '&annee=' . urlencode($annee);
        header('Location: ' . $url); exit();
    }

    private function redirectEns(int $idEns, string $annee = ''): void
    {
        $url = APP_URL . '/app.php?page=affecter-enseignant&id=' . $idEns;
        if ($annee) $url .= '&annee=' . urlencode($annee);
        header('Location: ' . $url); exit();
    }
}
