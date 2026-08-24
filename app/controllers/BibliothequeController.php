<?php

/**
 * BibliothequeController — Bibliothèque des séances (enseignant).
 *
 * Routes :
 *   GET  app.php?page=bibliotheque-seances           → index()
 *   POST app.php?page=bibliotheque-seances           → uploadPieces()
 *   POST app.php?page=do-delete-piece                → deletePiece()
 */
class BibliothequeController
{
    private SeanceModel $model;

    public function __construct()
    {
        require_once APP_ROOT . '/app/models/SeanceModel.php';
        require_once APP_ROOT . '/core/Uploader.php';
        $this->model = new SeanceModel();
        require_once APP_ROOT . '/core/Roles.php';
        Roles::requireEnseignant();
    }

    // =========================================================
    //  LISTE — bibliothèque complète
    // =========================================================
    public function index(): void
    {
        $idEnseignant = (int)Session::get('user_id');

        // Filtres GET
        $idMatiere = (int)($_GET['matiere'] ?? 0);
        $search    = trim($_GET['q'] ?? '');

        $seances  = $this->model->getBibliotheque($idEnseignant, $idMatiere, $search);
        $matieres = $this->model->getMatieresFiltres($idEnseignant);

        // Charger les pièces jointes pour chaque séance
        foreach ($seances as &$s) {
            $s['pieces_jointes'] = $this->model->getPiecesJointes((int)$s['id_seance']);
        }
        unset($s);

        $csrf      = Session::generateCsrf();
        $pageTitle = 'Bibliothèque de séances — ' . APP_NAME;
        $extraCss  = 'bibliotheque.css';
        include APP_ROOT . '/app/views/seance/bibliotheque.php';
    }

    // =========================================================
    //  UPLOAD DE PIÈCES JOINTES sur une séance existante
    // =========================================================
    public function uploadPieces(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=bibliotheque-seances');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=bibliotheque-seances');
            exit();
        }

        $idEnseignant = (int)Session::get('user_id');
        $idSeance     = (int)($_POST['id_seance'] ?? 0);

        // Vérifier que la séance appartient à l'enseignant
        $seance = $this->model->getSeanceById($idSeance, $idEnseignant);
        if (!$seance) {
            Session::setFlash('error', 'Séance introuvable.');
            header('Location: ' . APP_URL . '/app.php?page=bibliotheque-seances');
            exit();
        }

        // Traiter les fichiers
        if (empty($_FILES['fichiers']['name'][0]) && empty($_FILES['fichiers']['name'])) {
            Session::setFlash('error', 'Aucun fichier sélectionné.');
            header('Location: ' . APP_URL . '/app.php?page=bibliotheque-seances#seance-' . $idSeance);
            exit();
        }

        $uploader = new Uploader();
        $result   = $uploader->handle($_FILES['fichiers'], $idSeance, $idEnseignant);

        // Enregistrer en BDD
        foreach ($result['saved'] as $f) {
            $this->model->savePieceJointe(
                $idSeance,
                $f['nom_original'],
                $f['url_fichier'],
                $f['type_fichier'],
                $f['taille_fichier']
            );
        }

        $nb = count($result['saved']);
        if ($nb > 0) {
            Session::setFlash('success', "{$nb} fichier(s) ajouté(s) avec succès.");
        }
        if (!empty($result['errors'])) {
            Session::setFlash('error', implode('<br>', $result['errors']));
        }

        header('Location: ' . APP_URL . '/app.php?page=bibliotheque-seances#seance-' . $idSeance);
        exit();
    }

    // =========================================================
    //  SUPPRESSION D'UNE PIÈCE JOINTE
    // =========================================================
    public function deletePiece(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/app.php?page=bibliotheque-seances');
            exit();
        }

        $token = trim($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrf($token)) {
            Session::setFlash('error', 'Requête invalide.');
            header('Location: ' . APP_URL . '/app.php?page=bibliotheque-seances');
            exit();
        }

        $idEnseignant = (int)Session::get('user_id');
        $idPiece      = (int)($_POST['id_piece'] ?? 0);
        $idSeance     = (int)($_POST['id_seance'] ?? 0);

        $urlFichier = $this->model->deletePieceJointe($idPiece, $idEnseignant);
        if ($urlFichier) {
            $uploader = new Uploader();
            $uploader->deleteFile($urlFichier);
            Session::setFlash('success', 'Pièce jointe supprimée.');
        } else {
            Session::setFlash('error', 'Pièce jointe introuvable.');
        }

        header('Location: ' . APP_URL . '/app.php?page=bibliotheque-seances#seance-' . $idSeance);
        exit();
    }
}
