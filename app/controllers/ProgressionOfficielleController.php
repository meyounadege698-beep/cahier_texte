<?php

/**
 * ProgressionOfficielleController
 * Réservé aux censeurs uniquement.
 *
 * Routes :
 *   GET  app.php?page=progression-officielle          → index()
 *   GET  app.php?page=progression-officielle-create   → createForm()
 *   POST app.php?page=progression-officielle-create   → createProgramme()
 *   GET  app.php?page=progression-officielle-detail&id=X → detail()
 *   POST app.php?page=progression-officielle-chapitre → addChapitre()
 *   POST app.php?page=progression-officielle-delete-chapitre → deleteChapitre()
 *   POST app.php?page=progression-officielle-publier  → publier()
 */
class ProgressionOfficielleController
{
    private ProgressionOfficielleModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/ProgressionOfficielleModel.php';
        $this->model = new ProgressionOfficielleModel();

        // Seul le censeur peut accéder
        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }
        if (Session::get('role') !== 'censeur') {
            Session::setFlash('error', 'Accès réservé au censeur.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard');
            exit();
        }
    }

    // =========================================================
    //  LISTE DES PROGRAMMES DU CENSEUR
    // =========================================================
    public function index(): void
    {
        $programmes   = $this->model->getProgrammesByCenseur((int)Session::get('user_id'));
        $departements = $this->model->getAllDepartements();
        $pageTitle    = 'Progression officielle — ' . APP_NAME;
        $extraCss     = 'progression.css';
        include APP_ROOT . '/app/views/progression/index.php';
    }

    // =========================================================
    //  FORMULAIRE CRÉATION PROGRAMME
    // =========================================================
    public function createForm(): void
    {
        $departements = $this->model->getAllDepartements();
        $annees       = ProgressionOfficielleModel::getAnneesScolaires();
        $csrf         = Session::generateCsrf();
        $old          = Session::get('old_input') ?? [];
        Session::set('old_input', null);

        // Matières du dept sélectionné (rechargement JS + fallback PHP)
        $matieres = [];
        if (!empty($old['id_departement'])) {
            $matieres = $this->model->getMatieresByDepartement((int)$old['id_departement']);
        }

        $pageTitle = 'Nouveau programme — ' . APP_NAME;
        $extraCss  = 'progression.css';
        include APP_ROOT . '/app/views/progression/create.php';
    }

    // =========================================================
    //  TRAITEMENT CRÉATION PROGRAMME
    // =========================================================
    public function createProgramme(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle-create');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle-create');
            exit();
        }

        $idMatiere     = (int)($_POST['id_matiere']        ?? 0);
        $titre         = trim($_POST['titre_programme']    ?? '');
        $annee         = trim($_POST['annee_scolaire']     ?? '');
        $description   = trim($_POST['description']        ?? '') ?: null;
        $volumeHoraire = (int)($_POST['volume_horaire_total'] ?? 0) ?: null;

        // Sauvegarde pour re-remplissage
        Session::set('old_input', [
            'id_departement' => (int)($_POST['id_departement'] ?? 0),
            'id_matiere'     => $idMatiere,
            'titre_programme'=> $titre,
            'annee_scolaire' => $annee,
            'description'    => $description,
        ]);

        $errors = [];
        if ($idMatiere <= 0)   $errors[] = "Veuillez sélectionner une matière.";
        if (empty($titre))     $errors[] = "Le titre du programme est obligatoire.";
        if (empty($annee))     $errors[] = "L'année scolaire est obligatoire.";

        // Contrainte : saisie avant le début de l'année scolaire
        if (!empty($annee) && !ProgressionOfficielleModel::anneeEnCoursOuFuture($annee)) {
            $errors[] = "La saisie du programme n'est plus possible pour l'année {$annee} (début d'année dépassé).";
        }

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle-create');
            exit();
        }

        $idProgramme = $this->model->createProgramme(
            $idMatiere,
            (int)Session::get('user_id'),
            $titre, $annee, $description, $volumeHoraire
        );

        Session::set('old_input', null);
        Session::setFlash('success', 'Programme créé. Ajoutez maintenant les points du programme.');
        header('Location: ' . APP_URL . '/app.php?page=progression-officielle-detail&id=' . $idProgramme);
        exit();
    }

    // =========================================================
    //  DÉTAIL PROGRAMME + AJOUT CHAPITRES
    // =========================================================
    public function detail(): void
    {
        $idProgramme = (int)($_GET['id'] ?? 0);
        $programme   = $this->model->getProgrammeById($idProgramme);

        if (!$programme || (int)$programme['id_utilisateur'] !== (int)Session::get('user_id')) {
            Session::setFlash('error', 'Programme introuvable ou accès non autorisé.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        $chapitres = $this->model->getChapitresByProgramme($idProgramme);
        $csrf      = Session::generateCsrf();
        $pageTitle = 'Programme : ' . htmlspecialchars($programme['titre_programme']) . ' — ' . APP_NAME;
        $extraCss  = 'progression.css';
        include APP_ROOT . '/app/views/progression/detail.php';
    }

    // =========================================================
    //  AJOUT D'UN CHAPITRE
    // =========================================================
    public function addChapitre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        $idProgramme   = (int)($_POST['id_programme']         ?? 0);
        $titre         = trim($_POST['titre_chapitre']        ?? '');
        $description   = trim($_POST['description']           ?? '') ?: null;
        $objectifs     = trim($_POST['objectifs_pedagogiques']?? '') ?: null;
        $volumeHoraire = (int)($_POST['volume_horaire_prevu'] ?? 0) ?: null;
        $dureeSemaines = (int)($_POST['duree_semaines']       ?? 0) ?: null;

        // Vérifier que le programme appartient bien à ce censeur
        $programme = $this->model->getProgrammeById($idProgramme);
        if (!$programme || (int)$programme['id_utilisateur'] !== (int)Session::get('user_id')) {
            Session::setFlash('error', 'Programme introuvable.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        // Contrainte : modification impossible si déjà publié et année démarrée
        if ($programme['statut'] === 'ARCHIVE') {
            Session::setFlash('error', 'Ce programme est archivé, modification impossible.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle-detail&id=' . $idProgramme);
            exit();
        }

        if (empty($titre)) {
            Session::setFlash('error', 'Le titre du point de programme est obligatoire.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle-detail&id=' . $idProgramme);
            exit();
        }

        $this->model->addChapitre($idProgramme, $titre, $description, $objectifs, $volumeHoraire, $dureeSemaines);
        Session::setFlash('success', 'Point de programme ajouté.');
        header('Location: ' . APP_URL . '/app.php?page=progression-officielle-detail&id=' . $idProgramme);
        exit();
    }

    // =========================================================
    //  SUPPRESSION D'UN CHAPITRE
    // =========================================================
    public function deleteChapitre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        $idChapitre  = (int)($_POST['id_chapitre']  ?? 0);
        $idProgramme = (int)($_POST['id_programme'] ?? 0);

        $programme = $this->model->getProgrammeById($idProgramme);
        if (!$programme || (int)$programme['id_utilisateur'] !== (int)Session::get('user_id')) {
            Session::setFlash('error', 'Accès non autorisé.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        if ($programme['statut'] === 'PUBLIE') {
            Session::setFlash('error', 'Impossible de supprimer un point d\'un programme déjà publié.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle-detail&id=' . $idProgramme);
            exit();
        }

        $this->model->deleteChapitre($idChapitre, $idProgramme);
        Session::setFlash('success', 'Point supprimé.');
        header('Location: ' . APP_URL . '/app.php?page=progression-officielle-detail&id=' . $idProgramme);
        exit();
    }

    // =========================================================
    //  PUBLICATION DU PROGRAMME
    // =========================================================
    public function publier(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        $idProgramme = (int)($_POST['id_programme'] ?? 0);
        $programme   = $this->model->getProgrammeById($idProgramme);

        if (!$programme || (int)$programme['id_utilisateur'] !== (int)Session::get('user_id')) {
            Session::setFlash('error', 'Programme introuvable.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle');
            exit();
        }

        $chapitres = $this->model->getChapitresByProgramme($idProgramme);
        if (empty($chapitres)) {
            Session::setFlash('error', 'Ajoutez au moins un point de programme avant de publier.');
            header('Location: ' . APP_URL . '/app.php?page=progression-officielle-detail&id=' . $idProgramme);
            exit();
        }

        $ok = $this->model->publierProgramme($idProgramme, (int)Session::get('user_id'));
        if ($ok) {
            Session::setFlash('success', 'Programme publié. Les enseignants peuvent maintenant l\'utiliser.');
        } else {
            Session::setFlash('error', 'Publication impossible (programme déjà publié ?).');
        }
        header('Location: ' . APP_URL . '/app.php?page=progression-officielle-detail&id=' . $idProgramme);
        exit();
    }
}
