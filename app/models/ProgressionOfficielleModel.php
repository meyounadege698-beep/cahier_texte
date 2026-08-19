<?php

/**
 * ProgressionOfficielleModel
 * Gère les départements, matières, programmes officiels et chapitres.
 */
class ProgressionOfficielleModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  DÉPARTEMENTS
    // =========================================================

    public function getAllDepartements(): array
    {
        $result = $this->db->query(
            "SELECT id_departement, nom_departement, code_departement
             FROM departement ORDER BY nom_departement"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    //  MATIÈRES
    // =========================================================

    public function getMatieresByDepartement(int $idDept): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_matiere, nom_matiere, code_matiere, coefficient
             FROM matiere WHERE id_departement = ? ORDER BY nom_matiere"
        );
        $stmt->bind_param("i", $idDept);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getMatiereById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, d.nom_departement
             FROM matiere m
             JOIN departement d ON m.id_departement = d.id_departement
             WHERE m.id_matiere = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    // =========================================================
    //  PROGRAMMES OFFICIELS
    // =========================================================

    /**
     * Récupère tous les programmes d'une matière pour une année scolaire.
     */
    public function getProgrammesByMatiere(int $idMatiere, string $annee): array
    {
        $stmt = $this->db->prepare(
            "SELECT po.*, u.nom, u.prenom,
                    (SELECT COUNT(*) FROM chapitre ch WHERE ch.id_programme = po.id_programme) AS nb_chapitres
             FROM programme_officiel po
             JOIN utilisateur u ON po.id_utilisateur = u.id_utilisateur
             WHERE po.id_matiere = ? AND po.annee_scolaire = ?
             ORDER BY po.date_creation DESC"
        );
        $stmt->bind_param("is", $idMatiere, $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Récupère tous les programmes créés par un censeur.
     */
    public function getProgrammesByCenseur(int $idUtilisateur): array
    {
        $stmt = $this->db->prepare(
            "SELECT po.*, m.nom_matiere, m.code_matiere, d.nom_departement,
                    (SELECT COUNT(*) FROM chapitre ch WHERE ch.id_programme = po.id_programme) AS nb_chapitres
             FROM programme_officiel po
             JOIN matiere m ON po.id_matiere = m.id_matiere
             JOIN departement d ON m.id_departement = d.id_departement
             WHERE po.id_utilisateur = ?
             ORDER BY po.annee_scolaire DESC, m.nom_matiere"
        );
        $stmt->bind_param("i", $idUtilisateur);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProgrammeById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT po.*, m.nom_matiere, d.nom_departement
             FROM programme_officiel po
             JOIN matiere m ON po.id_matiere = m.id_matiere
             JOIN departement d ON m.id_departement = d.id_departement
             WHERE po.id_programme = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Crée un nouveau programme officiel (statut BROUILLON).
     */
    public function createProgramme(int $idMatiere, int $idUtilisateur, string $titre,
                                    string $annee, ?string $description, ?int $volumeHoraire): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO programme_officiel
             (id_matiere, id_utilisateur, titre_programme, annee_scolaire,
              description, volume_horaire_total, statut, est_actif, date_creation)
             VALUES (?, ?, ?, ?, ?, ?, 'BROUILLON', 1, NOW())"
        );
        $stmt->bind_param("iisssi", $idMatiere, $idUtilisateur, $titre, $annee, $description, $volumeHoraire);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    /**
     * Publie un programme (BROUILLON → PUBLIE).
     */
    public function publierProgramme(int $idProgramme, int $idUtilisateur): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE programme_officiel
             SET statut = 'PUBLIE', date_publication = NOW()
             WHERE id_programme = ? AND id_utilisateur = ? AND statut = 'BROUILLON'"
        );
        $stmt->bind_param("ii", $idProgramme, $idUtilisateur);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    // =========================================================
    //  CHAPITRES (points du programme)
    // =========================================================

    public function getChapitresByProgramme(int $idProgramme): array
    {
        $stmt = $this->db->prepare(
            "SELECT ch.*,
                    (SELECT COUNT(*) FROM leçon l WHERE l.id_chapitre = ch.id_chapitre) AS nb_lecons
             FROM chapitre ch
             WHERE ch.id_programme = ?
             ORDER BY ch.ordre_chapitre"
        );
        $stmt->bind_param("i", $idProgramme);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Ajoute un chapitre (point du programme).
     * L'ordre est calculé automatiquement.
     */
    public function addChapitre(int $idProgramme, string $titre,
                                ?string $description, ?string $objectifs,
                                ?int $volumeHoraire, ?int $dureeSemaines): int
    {
        // Calculer le prochain ordre
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(ordre_chapitre), 0) + 1 AS prochain_ordre
             FROM chapitre WHERE id_programme = ?"
        );
        $stmt->bind_param("i", $idProgramme);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $ordre = (int)$row['prochain_ordre'];
        $stmt->close();

        $stmt = $this->db->prepare(
            "INSERT INTO chapitre
             (id_programme, titre_chapitre, description, ordre_chapitre,
              objectifs_pedagogiques, volume_horaire_prevu, duree_semaines)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issisii", $idProgramme, $titre, $description,
                          $ordre, $objectifs, $volumeHoraire, $dureeSemaines);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    /**
     * Supprime un chapitre (et ses leçons en cascade via FK).
     */
    public function deleteChapitre(int $idChapitre, int $idProgramme): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM chapitre WHERE id_chapitre = ? AND id_programme = ?"
        );
        $stmt->bind_param("ii", $idChapitre, $idProgramme);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Vérifie si l'année scolaire est valide pour saisie
     * (doit être future ou en cours : avant le 1er septembre de l'année de début).
     */
    public static function anneeEnCoursOuFuture(string $annee): bool
    {
        // Format attendu : "2026-2027"
        if (!preg_match('/^(\d{4})-(\d{4})$/', $annee, $m)) return false;
        $debutAnnee = (int)$m[1];
        // La saisie est autorisée avant le 1er septembre de l'année de début
        $dateButoir = mktime(0, 0, 0, 9, 1, $debutAnnee);
        return time() < $dateButoir;
    }

    /**
     * Retourne les années scolaires disponibles (courante + 2 suivantes).
     */
    public static function getAnneesScolaires(): array
    {
        $annees = [];
        $anneeActuelle = (int)date('Y');
        // Si on est après septembre, l'année courante est anneeActuelle/(anneeActuelle+1)
        if ((int)date('m') >= 9) {
            $debut = $anneeActuelle;
        } else {
            $debut = $anneeActuelle - 1;
        }
        for ($i = 0; $i <= 2; $i++) {
            $annees[] = ($debut + $i) . '-' . ($debut + $i + 1);
        }
        return $annees;
    }
}
