<?php

/**
 * ProgressionOfficielleV2Controller
 * Wizard de saisie de la progression officielle structurée par semaine.
 *
 * Routes :
 *   GET  prog-officielle-v2              → index() liste des programmes
 *   GET  prog-officielle-v2-creer        → creerForm()
 *   POST prog-officielle-v2-creer        → creerProgramme()
 *   GET  prog-officielle-v2-wizard&id=X  → wizard()  (saisie semaines/chapitres/leçons)
 *   POST prog-officielle-v2-semaine      → addSemaine()
 *   POST prog-officielle-v2-chapitre     → addChapitre()
 *   POST prog-officielle-v2-lecon        → addLecon()
 *   POST prog-officielle-v2-objectif     → addObjectif()
 *   POST prog-officielle-v2-del-semaine  → deleteSemaine()
 *   POST prog-officielle-v2-del-chapitre → deleteChapitre()
 *   POST prog-officielle-v2-del-lecon    → deleteLecon()
 *   POST prog-officielle-v2-del-objectif → deleteObjectif()
 *   POST prog-officielle-v2-publier      → publier()
 *   POST prog-officielle-v2-attribuer    → attribuer()
 *   GET  prog-officielle-v2-json&id=X    → jsonProgramme() (AJAX temps réel)
 *   GET  api-matieres-dept&dept=X        → Déjà dans ApiController
 */
class ProgressionOfficielleV2Controller
{
    private ProgressionOfficielleV2Model $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/ProgressionOfficielleV2Model.php';
        $this->model = new ProgressionOfficielleV2Model();

        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
        if (Session::get('role') !== 'censeur') {
            Session::setFlash('error', 'Accès réservé au censeur.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard'); exit();
        }
    }

    // ── Liste des programmes ─────────────────────────────────
    public function index(): void
    {
        $programmes = $this->model->getProgrammesByCenseur((int)Session::get('user_id'));
        $csrf       = Session::generateCsrf();
        $pageTitle  = 'Progression officielle — ' . APP_NAME;
        $extraCss   = 'progression_v2.css';
        include APP_ROOT . '/app/views/progression_v2/index.php';
    }

    // ── Formulaire création ──────────────────────────────────
    public function creerForm(): void
    {
        $departements = $this->model->getAllDepartements();
        $annees       = ProgressionOfficielleV2Model::getAnneesScolaires();
        $csrf         = Session::generateCsrf();
        $old          = Session::get('old_input') ?? [];
        Session::set('old_input', null);
        $matieres     = !empty($old['id_departement'])
            ? $this->model->getMatieresByDepartement((int)$old['id_departement'])
            : [];
        $pageTitle = 'Nouveau programme — ' . APP_NAME;
        $extraCss  = 'progression_v2.css';
        include APP_ROOT . '/app/views/progression_v2/creer.php';
    }

    // ── Créer le programme ───────────────────────────────────
    public function creerProgramme(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=prog-officielle-v2-creer'); exit();
        }
        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=prog-officielle-v2-creer'); exit();
        }

        $idMatiere    = (int)($_POST['id_matiere']       ?? 0);
        $idDept       = (int)($_POST['id_departement']   ?? 0);
        $titre        = trim($_POST['titre_programme']   ?? '');
        $annee        = trim($_POST['annee_scolaire']    ?? '');
        $description  = trim($_POST['description']       ?? '') ?: null;
        $volumeH      = (int)($_POST['volume_horaire_total'] ?? 0) ?: null;

        Session::set('old_input', compact('idMatiere','idDept','titre','annee','description'));

        $errors = [];
        if ($idMatiere <= 0)  $errors[] = "Veuillez sélectionner une matière.";
        if (empty($titre))    $errors[] = "Le titre du programme est obligatoire.";
        if (empty($annee))    $errors[] = "L'année scolaire est obligatoire.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            header('Location: ' . APP_URL . '/app.php?page=prog-officielle-v2-creer'); exit();
        }

        $idProgramme = $this->model->createProgramme(
            $idMatiere, (int)Session::get('user_id'),
            $titre, $annee, $description, $volumeH
        );
        Session::set('old_input', null);
        Session::setFlash('success', 'Programme créé ! Ajoutez maintenant les semaines et leçons.');
        header('Location: ' . APP_URL . '/app.php?page=prog-officielle-v2-wizard&id=' . $idProgramme);
        exit();
    }

    // ── Wizard principal (semaines → chapitres → leçons) ────
    public function wizard(): void
    {
        $idProgramme = (int)($_GET['id'] ?? 0);
        $programme   = $this->model->getProgrammeById($idProgramme);

        if (!$programme || (int)$programme['id_utilisateur'] !== (int)Session::get('user_id')) {
            Session::setFlash('error', 'Programme introuvable.');
            header('Location: ' . APP_URL . '/app.php?page=prog-officielle-v2'); exit();
        }

        $progression = $this->model->getProgressionComplete($idProgramme);
        $csrf        = Session::generateCsrf();
        $pageTitle   = 'Wizard — ' . $programme['titre_programme'] . ' — ' . APP_NAME;
        $extraCss    = 'progression_v2.css';
        include APP_ROOT . '/app/views/progression_v2/wizard.php';
    }

    // ── Ajouter une semaine ──────────────────────────────────
    public function addSemaine(): void
    {
        $this->checkPost();
        $idProgramme  = (int)($_POST['id_programme']  ?? 0);
        $numero       = (int)($_POST['numero_semaine']?? 0);
        $dateDebut    = trim($_POST['date_debut']      ?? '');
        $dateFin      = trim($_POST['date_fin']        ?? '');
        $titrePeriode = trim($_POST['titre_periode']   ?? '') ?: null;

        $errors = [];
        if ($idProgramme <= 0)  $errors[] = "Programme invalide.";
        if ($numero <= 0)       $errors[] = "Numéro de semaine invalide.";
        if (empty($dateDebut))  $errors[] = "La date de début est obligatoire.";
        if (empty($dateFin))    $errors[] = "La date de fin est obligatoire.";
        if (!empty($dateDebut) && !empty($dateFin) && $dateFin < $dateDebut)
                                $errors[] = "La date de fin doit être après la date de début.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->addSemaine($idProgramme, $numero, $dateDebut, $dateFin, $titrePeriode);
            Session::setFlash('success', "Semaine {$numero} ajoutée.");
        }
        $this->redirectWizard($idProgramme);
    }

    // ── Ajouter un chapitre ──────────────────────────────────
    public function addChapitre(): void
    {
        $this->checkPost();
        $idProgramme = (int)($_POST['id_programme']          ?? 0);
        $idSemaine   = (int)($_POST['id_semaine']            ?? 0);
        $titre       = trim($_POST['titre_chapitre']         ?? '');
        $competences = trim($_POST['competences_semaine']    ?? '') ?: null;
        $description = trim($_POST['description']            ?? '') ?: null;
        $objectifs   = trim($_POST['objectifs_pedagogiques'] ?? '') ?: null;
        $volumeH     = (int)($_POST['volume_horaire_prevu']  ?? 0) ?: null;
        $dureeSem    = (int)($_POST['duree_semaines']        ?? 0) ?: null;

        if (empty($titre) || $idSemaine <= 0) {
            Session::setFlash('error', 'Titre du chapitre et semaine obligatoires.');
        } else {
            $this->model->addChapitre($idProgramme, $idSemaine, $titre,
                                      $competences, $description, $objectifs,
                                      $volumeH, $dureeSem);
            Session::setFlash('success', "Chapitre « {$titre} » ajouté.");
        }
        $this->redirectWizard($idProgramme);
    }

    // ── Ajouter une leçon ────────────────────────────────────
    public function addLecon(): void
    {
        $this->checkPost();
        $idProgramme = (int)($_POST['id_programme']       ?? 0);
        $idChapitre  = (int)($_POST['id_chapitre']        ?? 0);
        $titre       = trim($_POST['titre_lecon']         ?? '');
        $grandTitre  = trim($_POST['grand_titre']         ?? '') ?: $titre;
        $type        = trim($_POST['type_lecon']          ?? 'theorique');
        $objectifs   = trim($_POST['objectifs_pedagogiques'] ?? '') ?: null;
        $nbHeures    = (float)str_replace(',', '.', $_POST['nb_heures'] ?? '0') ?: null;
        $prerequis   = trim($_POST['prerequis']           ?? '') ?: null;
        $motsCles    = trim($_POST['mots_cles']           ?? '') ?: null;

        $typesValides = ['theorique','pratique','theorique_pratique'];
        if (!in_array($type, $typesValides)) $type = 'theorique';

        if (empty($titre) || $idChapitre <= 0) {
            Session::setFlash('error', 'Titre de la leçon et chapitre obligatoires.');
        } else {
            $this->model->addLecon($idChapitre, $titre, $grandTitre, $type,
                                   $objectifs, $nbHeures, $prerequis, $motsCles);
            Session::setFlash('success', "Leçon « {$titre} » ajoutée.");
        }
        $this->redirectWizard($idProgramme);
    }

    // ── Ajouter un objectif ──────────────────────────────────
    public function addObjectif(): void
    {
        $this->checkPost();
        $idProgramme = (int)($_POST['id_programme'] ?? 0);
        $idLecon     = (int)($_POST['id_lecon']     ?? 0);
        $libelle     = trim($_POST['libelle']       ?? '');
        $type        = trim($_POST['type_objectif'] ?? 'savoir_faire');

        $typesValides = ['savoir','savoir_faire','savoir_etre'];
        if (!in_array($type, $typesValides)) $type = 'savoir_faire';

        if (empty($libelle) || $idLecon <= 0) {
            Session::setFlash('error', 'Libellé de l\'objectif obligatoire.');
        } else {
            $this->model->addObjectif($idLecon, $libelle, $type);
        }
        $this->redirectWizard($idProgramme);
    }

    // ── Suppressions ─────────────────────────────────────────
    public function deleteSemaine(): void
    {
        $this->checkPost();
        $id = (int)($_POST['id_semaine']   ?? 0);
        $idP= (int)($_POST['id_programme'] ?? 0);
        $this->model->deleteSemaine($id);
        Session::setFlash('success', 'Semaine supprimée.');
        $this->redirectWizard($idP);
    }

    public function deleteChapitre(): void
    {
        $this->checkPost();
        $id = (int)($_POST['id_chapitre']  ?? 0);
        $idP= (int)($_POST['id_programme'] ?? 0);
        $this->model->deleteChapitre($id);
        Session::setFlash('success', 'Chapitre supprimé.');
        $this->redirectWizard($idP);
    }

    public function deleteLecon(): void
    {
        $this->checkPost();
        $id = (int)($_POST['id_lecon']     ?? 0);
        $idP= (int)($_POST['id_programme'] ?? 0);
        $this->model->deleteLecon($id);
        Session::setFlash('success', 'Leçon supprimée.');
        $this->redirectWizard($idP);
    }

    public function deleteObjectif(): void
    {
        $this->checkPost();
        $id = (int)($_POST['id_objectif']  ?? 0);
        $idP= (int)($_POST['id_programme'] ?? 0);
        $this->model->deleteObjectif($id);
        $this->redirectWizard($idP);
    }

    // ── Publier le programme ─────────────────────────────────
    public function publier(): void
    {
        $this->checkPost();
        $idP = (int)($_POST['id_programme'] ?? 0);
        $ok  = $this->model->publierProgramme($idP, (int)Session::get('user_id'));
        if ($ok) Session::setFlash('success', '✅ Programme publié ! Les enseignants peuvent maintenant être assignés.');
        else     Session::setFlash('error', 'Publication impossible (déjà publié ?).');
        $this->redirectWizard($idP);
    }

    // ── Attribuer à un enseignant ────────────────────────────
    public function attribuer(): void
    {
        $this->checkPost();
        $idP      = (int)($_POST['id_programme']  ?? 0);
        $idEns    = (int)($_POST['id_enseignant'] ?? 0);
        $idClasse = (int)($_POST['id_classe']     ?? 0);

        if ($idEns <= 0 || $idClasse <= 0) {
            Session::setFlash('error', 'Enseignant et classe obligatoires.');
        } else {
            $result = $this->model->attribuerAEnseignant($idP, $idEns, $idClasse);
            if ($result['success']) Session::setFlash('success', $result['message']);
            else                    Session::setFlash('error',   $result['message']);
        }
        $this->redirectWizard($idP);
    }

    // ── JSON temps réel ──────────────────────────────────────
    public function jsonProgramme(): void
    {
        $idP = (int)($_GET['id'] ?? 0);
        $data = $this->model->getProgressionComplete($idP);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }

    // ── Helpers ──────────────────────────────────────────────
    private function checkPost(): void
    {
        $token = trim($_POST['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=prog-officielle-v2'); exit();
        }
    }

    private function redirectWizard(int $idProgramme): void
    {
        header('Location: ' . APP_URL . '/app.php?page=prog-officielle-v2-wizard&id=' . $idProgramme);
        exit();
    }
}

// Méthode statique pour la vue enseignant (appelée depuis un contrôleur dédié ou ici)
// Accessible via : app.php?page=ma-progression&classe=X&matiere=X
