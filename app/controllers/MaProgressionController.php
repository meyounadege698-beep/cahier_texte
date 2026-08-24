<?php

/**
 * MaProgressionController
 * Vue de l'enseignant sur la progression qui lui a été attribuée.
 * Accessible aussi à l'administrateur.
 *
 * Routes :
 *   GET app.php?page=ma-progression                          → index()
 *   GET app.php?page=ma-progression-detail&classe=X&matiere=Y → detail()
 */
class MaProgressionController
{
    public function __construct()
    {
        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
        $role = Session::get('role');
        if (!in_array($role, ['enseignant', 'administrateur'])) {
            Session::setFlash('error', 'Accès réservé aux enseignants.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard'); exit();
        }
    }

    // ── Liste des progressions attribuées à cet enseignant ──
    public function index(): void
    {
        $idEnseignant = (int)Session::get('user_id');

        require_once APP_ROOT . '/app/models/SeanceModel.php';
        $seanceModel = new SeanceModel();
        $annee       = $seanceModel->getAnneeCourante();

        // Récupérer toutes les combinaisons classe/matière où l'enseignant a une progression
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT DISTINCT pp.id_classe, pp.id_matiere,
                    c.nom_classe, c.niveau,
                    m.nom_matiere, m.code_matiere,
                    po.titre_programme, po.annee_scolaire,
                    COUNT(pp.id_progression) AS nb_lecons,
                    SUM(pp.statut = 'TERMINEE')   AS nb_terminees,
                    SUM(pp.statut = 'EN_COURS')   AS nb_en_cours,
                    ROUND(AVG(pp.progression_pourcentage),1) AS avancement
             FROM progression_programme pp
             JOIN classe c              ON pp.id_classe  = c.id_classe
             JOIN matiere m             ON pp.id_matiere = m.id_matiere
             LEFT JOIN leçon l          ON pp.id_leçon   = l.id_leçon
             LEFT JOIN chapitre ch      ON l.id_chapitre = ch.id_chapitre
             LEFT JOIN programme_officiel po ON ch.id_programme = po.id_programme
             WHERE pp.id_utilisateur = ?
             GROUP BY pp.id_classe, pp.id_matiere
             ORDER BY c.nom_classe, m.nom_matiere"
        );
        $stmt->bind_param("i", $idEnseignant);
        $stmt->execute();
        $progressions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $pageTitle = 'Ma progression — ' . APP_NAME;
        $extraCss  = 'progression_v2.css';
        include APP_ROOT . '/app/views/progression_v2/ma_progression_liste.php';
    }

    // ── Détail d'une progression (une matière + classe) ─────
    public function detail(): void
    {
        $idEnseignant = (int)Session::get('user_id');
        $idClasse     = (int)($_GET['classe']  ?? 0);
        $idMatiere    = (int)($_GET['matiere'] ?? 0);

        if ($idClasse <= 0 || $idMatiere <= 0) {
            header('Location: ' . APP_URL . '/app.php?page=ma-progression'); exit();
        }

        require_once APP_ROOT . '/app/models/ProgressionOfficielleV2Model.php';
        $model      = new ProgressionOfficielleV2Model();
        $progression= $model->getProgressionEnseignant($idEnseignant, $idClasse, $idMatiere);

        // Infos matière + classe
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM matiere WHERE id_matiere = ?");
        $stmt->bind_param("i", $idMatiere); $stmt->execute();
        $matiere = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $stmt = $db->prepare("SELECT * FROM classe WHERE id_classe = ?");
        $stmt->bind_param("i", $idClasse); $stmt->execute();
        $classe = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        require_once APP_ROOT . '/app/models/SeanceModel.php';
        $annee = (new SeanceModel())->getAnneeCourante();

        $pageTitle = 'Progression — ' . ($matiere['nom_matiere'] ?? '') . ' — ' . APP_NAME;
        $extraCss  = 'progression_v2.css';
        include APP_ROOT . '/app/views/progression_v2/enseignant.php';
    }
}
