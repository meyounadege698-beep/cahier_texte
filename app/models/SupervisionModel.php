<?php

/**
 * SupervisionModel — Données pour le tableau de bord censeur.
 * Alertes, taux de couverture, validations en attente.
 */
class SupervisionModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  STATS GLOBALES
    // =========================================================

    public function getStatsGlobales(string $annee): array
    {
        // Nb enseignants actifs
        $r = $this->db->query(
            "SELECT COUNT(*) AS n FROM utilisateur WHERE role='enseignant' AND est_actif=1"
        );
        $nbEnseignants = (int)$r->fetch_assoc()['n'];

        // Nb séances cette semaine
        $r = $this->db->query(
            "SELECT COUNT(*) AS n FROM seance
             WHERE statut='REALISEE'
               AND YEARWEEK(date_seance,1) = YEARWEEK(CURDATE(),1)"
        );
        $nbSeancesSemaine = (int)$r->fetch_assoc()['n'];

        // Nb validations en attente
        $r = $this->db->query(
            "SELECT COUNT(*) AS n FROM validation_progression WHERE statut='EN_ATTENTE'"
        );
        $nbValidations = (int)$r->fetch_assoc()['n'];

        // Nb programmes publiés pour l'année
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM programme_officiel
             WHERE statut='PUBLIE' AND annee_scolaire=?"
        );
        $stmt->bind_param("s", $annee);
        $stmt->execute();
        $nbProgrammes = (int)$stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();

        return compact('nbEnseignants','nbSeancesSemaine','nbValidations','nbProgrammes');
    }

    // =========================================================
    //  TAUX DE COUVERTURE DU PROGRAMME PAR ENSEIGNANT
    // =========================================================

    public function getTauxCouverture(string $annee): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id_utilisateur,
                    CONCAT(u.nom,' ',u.prenom) AS enseignant,
                    c.nom_classe, m.nom_matiere,
                    COUNT(DISTINCT pp.id_progression)                               AS total_lecons,
                    COUNT(DISTINCT CASE WHEN pp.statut='TERMINEE' THEN pp.id_progression END) AS terminees,
                    COUNT(DISTINCT CASE WHEN pp.statut='EN_COURS' THEN pp.id_progression END) AS en_cours,
                    ROUND(COUNT(DISTINCT CASE WHEN pp.statut='TERMINEE' THEN pp.id_progression END)
                          * 100.0 / NULLIF(COUNT(DISTINCT pp.id_progression),0), 1) AS taux
             FROM progression_programme pp
             JOIN utilisateur u ON pp.id_utilisateur = u.id_utilisateur
             JOIN classe c      ON pp.id_classe      = c.id_classe
             JOIN matiere m     ON pp.id_matiere     = m.id_matiere
             JOIN leçon l       ON pp.id_leçon       = l.id_leçon
             JOIN chapitre ch   ON l.id_chapitre     = ch.id_chapitre
             JOIN programme_officiel po ON ch.id_programme = po.id_programme
             WHERE po.annee_scolaire = ?
             GROUP BY u.id_utilisateur, c.id_classe, m.id_matiere
             ORDER BY taux ASC, u.nom"
        );
        $stmt->bind_param("s", $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    //  ALERTES : CAHIERS NON REMPLIS
    // =========================================================

    /**
     * Enseignants qui n'ont pas saisi de séance depuis X jours.
     */
    public function getAlertesCahiersNonRemplis(int $seuilJours = 7): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id_utilisateur, CONCAT(u.nom,' ',u.prenom) AS enseignant,
                    u.email,
                    MAX(s.date_seance) AS derniere_seance,
                    DATEDIFF(CURDATE(), MAX(s.date_seance)) AS jours_inactivite,
                    COUNT(DISTINCT ae.id_classe) AS nb_classes
             FROM utilisateur u
             JOIN affectation_enseignant ae ON ae.id_utilisateur = u.id_utilisateur
             LEFT JOIN seance s ON s.id_utilisateur = u.id_utilisateur
                               AND s.statut = 'REALISEE'
             WHERE u.role = 'enseignant' AND u.est_actif = 1
             GROUP BY u.id_utilisateur
             HAVING (derniere_seance IS NULL OR jours_inactivite >= ?)
             ORDER BY jours_inactivite DESC, u.nom"
        );
        $stmt->bind_param("i", $seuilJours);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    //  VALIDATIONS EN ATTENTE
    // =========================================================

    public function getValidationsEnAttente(): array
    {
        $result = $this->db->query(
            "SELECT pp.id_progression,
                    CONCAT(u.nom,' ',u.prenom) AS enseignant,
                    c.nom_classe, m.nom_matiere,
                    l.titre_leçon, ch.titre_chapitre,
                    pp.date_debut, pp.date_fin,
                    pp.progression_pourcentage,
                    vp.statut AS statut_validation,
                    vp.date_validation,
                    vp.id_validation_progression
             FROM progression_programme pp
             JOIN utilisateur u ON pp.id_utilisateur = u.id_utilisateur
             JOIN classe c      ON pp.id_classe      = c.id_classe
             JOIN matiere m     ON pp.id_matiere     = m.id_matiere
             JOIN leçon l       ON pp.id_leçon       = l.id_leçon
             JOIN chapitre ch   ON l.id_chapitre     = ch.id_chapitre
             LEFT JOIN validation_progression vp ON pp.id_progression = vp.id_progression
             WHERE pp.statut = 'TERMINEE'
               AND (vp.statut IS NULL OR vp.statut = 'EN_ATTENTE')
             ORDER BY pp.date_fin DESC"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Valide ou refuse une progression.
     */
    public function validerProgression(int $idProgression, int $idCenseur,
                                        string $statut, ?string $commentaire,
                                        ?string $ecart, ?string $actions): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO validation_progression
             (id_progression, id_utilisateur, statut, commentaire,
              ecart_programme, actions_correctives, date_validation)
             VALUES (?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
               id_utilisateur    = VALUES(id_utilisateur),
               statut            = VALUES(statut),
               commentaire       = VALUES(commentaire),
               ecart_programme   = VALUES(ecart_programme),
               actions_correctives = VALUES(actions_correctives),
               date_validation   = NOW()"
        );
        $stmt->bind_param("iissss", $idProgression, $idCenseur,
                          $statut, $commentaire, $ecart, $actions);
        $stmt->execute();
        $stmt->close();
    }

    // =========================================================
    //  ACTIVITÉ RÉCENTE (fil d'événements)
    // =========================================================

    public function getActiviteRecente(int $limit = 15): array
    {
        $result = $this->db->query(
            "SELECT s.id_seance, s.date_seance, s.heure_debut, s.heure_fin,
                    s.contenu_traite, s.date_saisie,
                    CONCAT(u.nom,' ',u.prenom) AS enseignant,
                    c.nom_classe, m.nom_matiere
             FROM seance s
             JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur
             JOIN classe c      ON s.id_classe      = c.id_classe
             JOIN matiere m     ON s.id_matiere     = m.id_matiere
             WHERE s.statut = 'REALISEE'
             ORDER BY s.date_saisie DESC
             LIMIT {$limit}"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAnneeCourante(): string
    {
        $m = (int)date('m'); $y = (int)date('Y');
        return $m >= 9 ? "{$y}-".($y+1) : ($y-1)."-{$y}";
    }
}
