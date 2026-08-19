<?php

/**
 * CatalogueController — Gestion des départements et matières (censeur uniquement).
 *
 * Toutes les actions POST arrivent via form_action (page=gestion-catalogue).
 */
class CatalogueController
{
    private CatalogueModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/CatalogueModel.php';
        $this->model = new CatalogueModel();

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

    // ── Page principale ──────────────────────────────────────
    public function index(): void
    {
        $depts    = $this->model->getAllDepts();
        $matieres = $this->model->getAllMats();
        $csrf     = Session::generateCsrf();
        $pageTitle = 'Départements & Matières — ' . APP_NAME;
        $extraCss  = 'catalogue.css';
        include APP_ROOT . '/app/views/catalogue/index.php';
    }

    // =========================================================
    //  DÉPARTEMENTS
    // =========================================================

    public function addDept(): void
    {
        $this->requirePost();
        $nom  = trim($_POST['nom_departement']  ?? '');
        $code = trim($_POST['code_departement'] ?? '') ?: null;
        $desc = trim($_POST['description']      ?? '') ?: null;

        $errors = [];
        if (empty($nom))              $errors[] = "Le nom du département est obligatoire.";
        if ($code && strlen($code) > 20) $errors[] = "Le code ne peut dépasser 20 caractères.";
        if ($code && $this->model->deptCodeExists($code))
                                      $errors[] = "Ce code département existe déjà.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->addDept($nom, $code, $desc);
            Session::setFlash('success', "Département « {$nom} » créé.");
        }
        $this->redirect();
    }

    public function editDept(): void
    {
        $this->requirePost();
        $id   = (int)($_POST['id_departement']  ?? 0);
        $nom  = trim($_POST['nom_departement']  ?? '');
        $code = trim($_POST['code_departement'] ?? '') ?: null;
        $desc = trim($_POST['description']      ?? '') ?: null;

        $errors = [];
        if ($id <= 0)    $errors[] = "Département introuvable.";
        if (empty($nom)) $errors[] = "Le nom est obligatoire.";
        if ($code && $this->model->deptCodeExists($code, $id))
                         $errors[] = "Ce code département existe déjà.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->updateDept($id, $nom, $code, $desc);
            Session::setFlash('success', "Département mis à jour.");
        }
        $this->redirect();
    }

    public function deleteDept(): void
    {
        $this->requirePost();
        $id = (int)($_POST['id_departement'] ?? 0);
        if (!$this->model->deleteDept($id)) {
            Session::setFlash('error',
                "Impossible de supprimer ce département : des matières lui sont rattachées.");
        } else {
            Session::setFlash('success', "Département supprimé.");
        }
        $this->redirect();
    }

    // =========================================================
    //  MATIÈRES
    // =========================================================

    public function addMat(): void
    {
        $this->requirePost();
        $idDept       = (int)($_POST['id_departement']      ?? 0);
        $nom          = trim($_POST['nom_matiere']          ?? '');
        $code         = trim($_POST['code_matiere']         ?? '') ?: null;
        $coef         = (float)str_replace(',', '.', $_POST['coefficient'] ?? '1');
        $volumeH      = (int)($_POST['volume_horaire_annuel'] ?? 0) ?: null;
        $desc         = trim($_POST['description']          ?? '') ?: null;

        $errors = [];
        if ($idDept <= 0) $errors[] = "Veuillez sélectionner un département.";
        if (empty($nom))  $errors[] = "Le nom de la matière est obligatoire.";
        if ($coef <= 0)   $errors[] = "Le coefficient doit être supérieur à 0.";
        if ($code && $this->model->matCodeExists($code))
                          $errors[] = "Ce code matière existe déjà.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->addMat($idDept, $nom, $code, $coef, $volumeH, $desc);
            Session::setFlash('success', "Matière « {$nom} » créée.");
        }
        $this->redirect();
    }

    public function editMat(): void
    {
        $this->requirePost();
        $id     = (int)($_POST['id_matiere']            ?? 0);
        $idDept = (int)($_POST['id_departement']        ?? 0);
        $nom    = trim($_POST['nom_matiere']            ?? '');
        $code   = trim($_POST['code_matiere']           ?? '') ?: null;
        $coef   = (float)str_replace(',', '.', $_POST['coefficient'] ?? '1');
        $volumeH= (int)($_POST['volume_horaire_annuel'] ?? 0) ?: null;
        $desc   = trim($_POST['description']            ?? '') ?: null;

        $errors = [];
        if ($id <= 0)     $errors[] = "Matière introuvable.";
        if ($idDept <= 0) $errors[] = "Veuillez sélectionner un département.";
        if (empty($nom))  $errors[] = "Le nom est obligatoire.";
        if ($code && $this->model->matCodeExists($code, $id))
                          $errors[] = "Ce code matière existe déjà.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->updateMat($id, $idDept, $nom, $code, $coef, $volumeH, $desc);
            Session::setFlash('success', "Matière mise à jour.");
        }
        $this->redirect();
    }

    public function deleteMat(): void
    {
        $this->requirePost();
        $id = (int)($_POST['id_matiere'] ?? 0);
        if (!$this->model->deleteMat($id)) {
            Session::setFlash('error',
                "Impossible de supprimer cette matière : des programmes y sont rattachés.");
        } else {
            Session::setFlash('success', "Matière supprimée.");
        }
        $this->redirect();
    }

    // ── Helpers ──────────────────────────────────────────────
    private function requirePost(): void
    {
        $token = trim($_POST['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            $this->redirect();
        }
    }

    private function redirect(): void
    {
        header('Location: ' . APP_URL . '/app.php?page=gestion-catalogue');
        exit();
    }
}
