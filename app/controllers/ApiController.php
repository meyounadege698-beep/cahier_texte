<?php

/**
 * ApiController — Endpoints JSON pour les appels AJAX.
 */
class ApiController
{
    public function __construct()
    {
        if (!Session::isLoggedIn()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Non autorisé']);
            exit();
        }
    }

    /**
     * GET app.php?page=api-matieres&dept=X
     * Matières d'un département (pour progression officielle).
     */
    public function getMatieres(): void
    {
        require_once APP_ROOT . '/app/models/ProgressionOfficielleModel.php';
        $model    = new ProgressionOfficielleModel();
        $idDept   = (int)($_GET['dept'] ?? 0);
        $matieres = $idDept > 0 ? $model->getMatieresByDepartement($idDept) : [];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($matieres);
        exit();
    }    /**
     * GET app.php?page=api-matieres-classe&classe=X
     * Matières affectées à l'enseignant connecté pour une classe.
     */
    public function getMatieresClasse(): void
    {
        require_once APP_ROOT . '/app/models/SeanceModel.php';
        $model      = new SeanceModel();
        $idClasse   = (int)($_GET['classe'] ?? 0);
        $idEnseignant = (int)Session::get('user_id');
        $matieres   = $idClasse > 0
            ? $model->getMatieresByEnseignantAndClasse($idEnseignant, $idClasse)
            : [];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($matieres);
        exit();
    }

    /**
     * GET app.php?page=api-points-programme&matiere=X
     * Points du programme officiel publié pour une matière.
     */
    public function getPointsProgramme(): void
    {
        require_once APP_ROOT . '/app/models/SeanceModel.php';
        $model     = new SeanceModel();
        $idMatiere = (int)($_GET['matiere'] ?? 0);
        $points    = $idMatiere > 0 ? $model->getPointsProgramme($idMatiere) : [];
        $programme = $idMatiere > 0 ? $model->getProgrammeActif($idMatiere) : null;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['points' => $points, 'programme' => $programme]);
        exit();
    }
}

    /**
     * POST app.php?page=api-ia-resume
     * Génère un résumé IA d'une séance.
     */
    public function iaResume(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Méthode invalide.']);
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            echo json_encode(['success' => false, 'error' => 'Token invalide.']);
            exit();
        }

        require_once APP_ROOT . '/core/AiService.php';
        $ai = new AiService();

        $contenu     = trim($_POST['contenu']    ?? '');
        $objectifs   = trim($_POST['objectifs']  ?? '');
        $commentaire = trim($_POST['commentaire']?? '');

        if (empty($contenu)) {
            echo json_encode(['success' => false, 'error' => 'Contenu vide.']);
            exit();
        }

        $result = $ai->resumeSeance($contenu, $objectifs, $commentaire);
        echo json_encode($result);
        exit();
    }

    /**
     * POST app.php?page=api-ia-objectifs
     * Suggère des objectifs pédagogiques pour une leçon.
     */
    public function iaObjectifs(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $token = trim($_POST['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf($token)) {
            echo json_encode(['success' => false, 'error' => 'Token invalide.']);
            exit();
        }

        require_once APP_ROOT . '/core/AiService.php';
        $ai = new AiService();

        $result = $ai->suggererObjectifs(
            trim($_POST['titre_lecon']  ?? ''),
            trim($_POST['grand_titre']  ?? ''),
            trim($_POST['matiere']      ?? '')
        );
        echo json_encode($result);
        exit();
    }
