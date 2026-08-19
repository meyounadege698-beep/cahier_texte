<?php

/**
 * SeanceController — Saisie de séances par l'enseignant.
 * Inclut : pièces jointes (upload) + bouton "Réutiliser".
 */
class SeanceController
{
    private SeanceModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/SeanceModel.php';
        require_once APP_ROOT . '/core/Uploader.php';
        $this->model = new SeanceModel();

        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login');
            exit();
        }
        if (Session::get('role') !== 'enseignant') {
            Session::setFlash('error', 'Accès réservé aux enseignants.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard');
            exit();
        }
    }

    // =========================================================
    //  FORMULAIRE DE SAISIE
    // =========================================================
    public function form(): void
    {
        $idEnseignant    = (int)Session::get('user_id');
        $classes         = $this->model->getClassesByEnseignant($idEnseignant);
        $seancesRecentes = $this->model->getSeancesRecentes($idEnseignant);

        // Pré-remplissage via "Réutiliser" (?reuse=ID)
        $reuseData = [];
        $idReuse   = (int)($_GET['reuse'] ?? 0);
        if ($idReuse > 0) {
            $src = $this->model->getSeanceById($idReuse, $idEnseignant);
            if ($src) {
                $reuseData = [
                    'id_classe'             => $src['id_classe'],
                    'id_matiere'            => $src['id_matiere'],
                    'id_chapitre'           => $src['id_chapitre'] ?? 0,
                    'contenu_traite'        => $src['contenu_traite'],
                    'objectifs_atteints'    => $src['objectifs_atteints'],
                    'commentaire_enseignant'=> $src['commentaire_enseignant'],
                    'heure_debut'           => $src['heure_debut'],
                    'heure_fin'             => $src['heure_fin'],
                    'date_seance'           => date('Y-m-d'), // aujourd'hui
                ];
            }
        }

        // Fusion old_input (prioritaire) / reuseData
        $old = Session::get('old_input') ?? $reuseData;
        Session::set('old_input', null);

        // Classe / matière / points présélectionnés
        $idClasse  = (int)($_GET['classe']  ?? $old['id_classe']  ?? 0);
        $idMatiere = (int)($_GET['matiere'] ?? $old['id_matiere'] ?? 0);

        $matieres = $idClasse > 0
            ? $this->model->getMatieresByEnseignantAndClasse($idEnseignant, $idClasse)
            : [];
        $points = $idMatiere > 0
            ? $this->model->getPointsProgramme($idMatiere)
            : [];
        $programmeActif = $idMatiere > 0
            ? $this->model->getProgrammeActif($idMatiere)
            : null;

        $csrf      = Session::generateCsrf();
        $pageTitle = 'Saisie de séance — ' . APP_NAME;
        $extraCss  = 'seance.css';
        $isReuse   = $idReuse > 0 && !empty($reuseData);
        include APP_ROOT . '/app/views/seance/form.php';
    }

    // =========================================================
    //  ENREGISTREMENT + UPLOAD
    // =========================================================
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=saisie-seance');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=saisie-seance');
            exit();
        }

        $idEnseignant = (int)Session::get('user_id');
        $idClasse     = (int)($_POST['id_classe']   ?? 0);
        $idMatiere    = (int)($_POST['id_matiere']  ?? 0);
        $idChapitre   = (int)($_POST['id_chapitre'] ?? 0);
        $dateSeance   = trim($_POST['date_seance']  ?? '');
        $heureDebut   = trim($_POST['heure_debut']  ?? '');
        $heureFin     = trim($_POST['heure_fin']    ?? '');
        $contenu      = trim($_POST['contenu_traite']           ?? '');
        $objectifs    = trim($_POST['objectifs_atteints']        ?? '') ?: null;
        $commentaire  = trim($_POST['commentaire_enseignant']    ?? '') ?: null;

        Session::set('old_input', [
            'id_classe'             => $idClasse,
            'id_matiere'            => $idMatiere,
            'id_chapitre'           => $idChapitre,
            'date_seance'           => $dateSeance,
            'heure_debut'           => $heureDebut,
            'heure_fin'             => $heureFin,
            'contenu_traite'        => $contenu,
            'objectifs_atteints'    => $objectifs,
            'commentaire_enseignant'=> $commentaire,
        ]);

        $errors = [];
        if ($idClasse  <= 0)    $errors[] = "Veuillez sélectionner une classe.";
        if ($idMatiere <= 0)    $errors[] = "Veuillez sélectionner une matière.";
        if (empty($dateSeance)) $errors[] = "La date est obligatoire.";
        if (empty($heureDebut)) $errors[] = "L'heure de début est obligatoire.";
        if (empty($heureFin))   $errors[] = "L'heure de fin est obligatoire.";
        if (!empty($heureDebut) && !empty($heureFin) && $heureFin <= $heureDebut)
                                $errors[] = "L'heure de fin doit être après l'heure de début.";
        if (empty($contenu))    $errors[] = "Le contenu traité est obligatoire.";

        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
            header('Location: ' . APP_URL . '/app.php?page=saisie-seance'
                . '&classe=' . $idClasse . '&matiere=' . $idMatiere);
            exit();
        }

        // Progression
        $idProgression = 0;
        if ($idChapitre > 0) {
            $idProgression = $this->model->getOrCreateProgression(
                $idEnseignant, $idChapitre, $idClasse, $idMatiere
            );
        }

        // Créer la séance
        $idSeance = $this->model->createSeance([
            'id_utilisateur'        => $idEnseignant,
            'id_classe'             => $idClasse,
            'id_matiere'            => $idMatiere,
            'id_progression'        => $idProgression ?: null,
            'date_seance'           => $dateSeance,
            'heure_debut'           => $heureDebut,
            'heure_fin'             => $heureFin,
            'contenu_traite'        => $contenu,
            'objectifs_atteints'    => $objectifs,
            'commentaire_enseignant'=> $commentaire,
        ]);

        // Traiter les fichiers uploadés si présents
        $uploadErrors = [];
        if (!empty($_FILES['fichiers']['name'][0]) || !empty($_FILES['fichiers']['name'])) {
            $uploader = new Uploader();
            $result   = $uploader->handle($_FILES['fichiers'], $idSeance, $idEnseignant);
            foreach ($result['saved'] as $f) {
                $this->model->savePieceJointe(
                    $idSeance, $f['nom_original'], $f['url_fichier'],
                    $f['type_fichier'], $f['taille_fichier']
                );
            }
            $uploadErrors = $result['errors'];
        }

        Session::set('old_input', null);

        $msg = 'Séance enregistrée avec succès !';
        if (!empty($uploadErrors)) {
            $msg .= ' Attention : ' . implode(', ', $uploadErrors);
            Session::setFlash('error', $msg);
        } else {
            Session::setFlash('success', $msg);
        }

        header('Location: ' . APP_URL . '/app.php?page=saisie-seance');
        exit();
    }

    // =========================================================
    //  AJOUT D'UN POINT MANQUANT
    // =========================================================
    public function ajouterPoint(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=saisie-seance');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=saisie-seance');
            exit();
        }

        $idProgramme = (int)($_POST['id_programme'] ?? 0);
        $idMatiere   = (int)($_POST['id_matiere']   ?? 0);
        $idClasse    = (int)($_POST['id_classe']    ?? 0);
        $titre       = trim($_POST['titre_point']   ?? '');
        $objectifs   = trim($_POST['objectifs_point'] ?? '') ?: null;

        if ($idProgramme <= 0 || empty($titre)) {
            Session::setFlash('error', 'Titre du point obligatoire.');
            header('Location: ' . APP_URL . '/app.php?page=saisie-seance'
                . '&classe=' . $idClasse . '&matiere=' . $idMatiere);
            exit();
        }

        $idChapitre = $this->model->ajouterPointManquant(
            $idProgramme, $titre, $objectifs, (int)Session::get('user_id')
        );

        Session::setFlash('success', "Point « {$titre} » ajouté au programme officiel.");
        header('Location: ' . APP_URL . '/app.php?page=saisie-seance'
            . '&classe=' . $idClasse
            . '&matiere=' . $idMatiere
            . '&selected_chapitre=' . $idChapitre);
        exit();
    }
}
