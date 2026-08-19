<?php

/**
 * EleveController — Gestion des classes et élèves (censeur).
 *
 * Routes :
 *   GET  app.php?page=gestion-classes               → classes()
 *   GET  app.php?page=gestion-eleves&classe=X       → eleves()
 *   POST form_action = add_classe / edit_classe / delete_classe
 *   POST form_action = add_eleve / edit_eleve / toggle_eleve
 */
class EleveController
{
    private EleveModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/EleveModel.php';
        $this->model = new EleveModel();

        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
        if (Session::get('role') !== 'censeur') {
            Session::setFlash('error', 'Accès réservé au censeur.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard'); exit();
        }
    }

    // ── Page classes ─────────────────────────────────────────
    public function classes(): void
    {
        $annees  = $this->model->getAnnesScolaires();
        $annee   = trim($_GET['annee'] ?? ($annees[0] ?? ''));
        $classes = $this->model->getAllClasses($annee);
        $csrf    = Session::generateCsrf();

        $pageTitle = 'Gestion des classes — ' . APP_NAME;
        $extraCss  = 'eleve.css';
        include APP_ROOT . '/app/views/eleve/classes.php';
    }

    // ── Page élèves d'une classe ─────────────────────────────
    public function eleves(): void
    {
        $idClasse = (int)($_GET['classe'] ?? 0);
        $classe   = $idClasse > 0 ? $this->model->getClasseById($idClasse) : null;
        $eleves   = $idClasse > 0 ? $this->model->getElevesByClasse($idClasse) : [];
        $annees   = $this->model->getAnnesScolaires();
        $classes  = $this->model->getAllClasses();
        $csrf     = Session::generateCsrf();

        $pageTitle = $classe
            ? 'Élèves — ' . $classe['nom_classe'] . ' — ' . APP_NAME
            : 'Gestion des élèves — ' . APP_NAME;
        $extraCss  = 'eleve.css';
        include APP_ROOT . '/app/views/eleve/eleves.php';
    }

    // =========================================================
    //  ACTIONS CLASSES
    // =========================================================

    public function addClasse(): void
    {
        $this->check();
        $nom        = trim($_POST['nom_classe']      ?? '');
        $niveau     = trim($_POST['niveau']          ?? '');
        $filiere    = trim($_POST['filiere']         ?? '') ?: null;
        $annee      = trim($_POST['annee_scolaire']  ?? '');
        $effectif   = (int)($_POST['effectif_max']  ?? 50);

        $errors = [];
        if (empty($nom))    $errors[] = "Le nom de la classe est obligatoire.";
        if (empty($niveau)) $errors[] = "Le niveau est obligatoire.";
        if (empty($annee))  $errors[] = "L'année scolaire est obligatoire.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->addClasse($nom, $niveau, $filiere, $annee, max(1, $effectif));
            Session::setFlash('success', "Classe « {$nom} » créée.");
        }
        header('Location: ' . APP_URL . '/app.php?page=gestion-classes&annee=' . urlencode($annee));
        exit();
    }

    public function editClasse(): void
    {
        $this->check();
        $id       = (int)($_POST['id_classe']       ?? 0);
        $nom      = trim($_POST['nom_classe']        ?? '');
        $niveau   = trim($_POST['niveau']            ?? '');
        $filiere  = trim($_POST['filiere']           ?? '') ?: null;
        $annee    = trim($_POST['annee_scolaire']    ?? '');
        $effectif = (int)($_POST['effectif_max']    ?? 50);

        $errors = [];
        if ($id <= 0)       $errors[] = "Classe introuvable.";
        if (empty($nom))    $errors[] = "Le nom est obligatoire.";
        if (empty($niveau)) $errors[] = "Le niveau est obligatoire.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $this->model->updateClasse($id, $nom, $niveau, $filiere, $annee, max(1, $effectif));
            Session::setFlash('success', "Classe mise à jour.");
        }
        header('Location: ' . APP_URL . '/app.php?page=gestion-classes&annee=' . urlencode($annee));
        exit();
    }

    public function deleteClasse(): void
    {
        $this->check();
        $id    = (int)($_POST['id_classe']    ?? 0);
        $annee = trim($_POST['annee_scolaire']?? '');

        if (!$this->model->deleteClasse($id)) {
            Session::setFlash('error', "Impossible : des élèves sont rattachés à cette classe.");
        } else {
            Session::setFlash('success', "Classe supprimée.");
        }
        header('Location: ' . APP_URL . '/app.php?page=gestion-classes&annee=' . urlencode($annee));
        exit();
    }

    // =========================================================
    //  ACTIONS ÉLÈVES
    // =========================================================

    public function addEleve(): void
    {
        $this->check();
        $idClasse = (int)($_POST['id_classe'] ?? 0);
        $data = $this->extractEleveData();

        $errors = [];
        if ($idClasse <= 0)        $errors[] = "Veuillez sélectionner une classe.";
        if (empty($data['nom']))   $errors[] = "Le nom est obligatoire.";
        if (empty($data['prenom']))$errors[] = "Le prénom est obligatoire.";
        if (empty($data['matricule'])) $errors[] = "Le matricule est obligatoire.";
        if ($this->model->matriculeExists($data['matricule']))
                                   $errors[] = "Ce matricule est déjà utilisé.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $data['id_classe'] = $idClasse;
            $this->model->addEleve($data);
            Session::setFlash('success', "Élève « {$data['nom']} {$data['prenom']} » inscrit.");
        }
        header('Location: ' . APP_URL . '/app.php?page=gestion-eleves&classe=' . $idClasse);
        exit();
    }

    public function editEleve(): void
    {
        $this->check();
        $id       = (int)($_POST['id_eleve']  ?? 0);
        $idClasse = (int)($_POST['id_classe'] ?? 0);
        $data     = $this->extractEleveData();

        $errors = [];
        if ($id <= 0)              $errors[] = "Élève introuvable.";
        if (empty($data['nom']))   $errors[] = "Le nom est obligatoire.";
        if (empty($data['matricule'])) $errors[] = "Le matricule est obligatoire.";
        if ($this->model->matriculeExists($data['matricule'], $id))
                                   $errors[] = "Ce matricule est déjà utilisé.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            $data['id_classe'] = $idClasse;
            $this->model->updateEleve($id, $data);
            Session::setFlash('success', "Fiche élève mise à jour.");
        }
        header('Location: ' . APP_URL . '/app.php?page=gestion-eleves&classe=' . $idClasse);
        exit();
    }

    public function toggleEleve(): void
    {
        $this->check();
        $id       = (int)($_POST['id_eleve']  ?? 0);
        $actif    = (int)($_POST['est_actif'] ?? 0);
        $idClasse = (int)($_POST['id_classe'] ?? 0);

        $this->model->toggleActifEleve($id, $actif ? 0 : 1);
        $label = $actif ? 'désactivé' : 'réactivé';
        Session::setFlash('success', "Élève {$label}.");
        header('Location: ' . APP_URL . '/app.php?page=gestion-eleves&classe=' . $idClasse);
        exit();
    }

    // ── Helpers ──────────────────────────────────────────────
    private function check(): void
    {
        $token = trim($_POST['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=gestion-classes'); exit();
        }
    }

    private function extractEleveData(): array
    {
        $annee = trim($_POST['annee_scolaire'] ?? '');
        if (!$annee) {
            $m = (int)date('m'); $y = (int)date('Y');
            $annee = $m >= 9 ? "{$y}-".($y+1) : ($y-1)."-{$y}";
        }
        return [
            'nom'            => trim($_POST['nom']             ?? ''),
            'prenom'         => trim($_POST['prenom']          ?? ''),
            'matricule'      => trim($_POST['matricule']       ?? ''),
            'annee_scolaire' => $annee,
            'date_naissance' => trim($_POST['date_naissance']  ?? '') ?: null,
            'lieu_naissance' => trim($_POST['lieu_naissance']  ?? '') ?: null,
            'sexe'           => trim($_POST['sexe']            ?? '') ?: null,
            'telephone'      => trim($_POST['telephone']       ?? '') ?: null,
            'email_parent'   => trim($_POST['email_parent']    ?? '') ?: null,
        ];
    }
}
