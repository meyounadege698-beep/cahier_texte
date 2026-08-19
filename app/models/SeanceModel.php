<?php

/**
 * SeanceModel — Opérations BDD pour les séances de cours.
 * Tables : seance, leçon, chapitre, progression_programme,
 *          affectation_enseignant, classe, piece_jointe
 */
class SeanceModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  AFFECTATIONS DE L'ENSEIGNANT
    // =========================================================

    public function getClassesByEnseignant(int $idEnseignant): array
    {
        $annee = $this->getAnneeCourante();
        $stmt  = $this->db->prepare(
            "SELECT DISTINCT c.id_classe, c.nom_classe, c.niveau, c.filiere
             FROM affectation_enseignant ae
             JOIN classe c ON ae.id_classe = c.id_classe
             WHERE ae.id_utilisateur = ? AND ae.annee_scolaire = ?
             ORDER BY c.niveau, c.nom_classe"
        );
        $stmt->bind_param("is", $idEnseignant, $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getMatieresByEnseignantAndClasse(int $idEnseignant, int $idClasse): array
    {
        $annee = $this->getAnneeCourante();
        $stmt  = $this->db->prepare(
            "SELECT m.id_matiere, m.nom_matiere, m.code_matiere, m.coefficient
             FROM affectation_enseignant ae
             JOIN matiere m ON ae.id_matiere = m.id_matiere
             WHERE ae.id_utilisateur = ? AND ae.id_classe = ? AND ae.annee_scolaire = ?
             ORDER BY m.nom_matiere"
        );
        $stmt->bind_param("iis", $idEnseignant, $idClasse, $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    //  POINTS DU PROGRAMME
    // =========================================================

    public function getPointsProgramme(int $idMatiere): array
    {
        $annee = $this->getAnneeCourante();
        $stmt  = $this->db->prepare(
            "SELECT ch.id_chapitre, ch.titre_chapitre, ch.ordre_chapitre,
                    ch.objectifs_pedagogiques, po.titre_programme, po.annee_scolaire,
                    (SELECT COUNT(*) FROM leçon l WHERE l.id_chapitre = ch.id_chapitre) AS nb_lecons
             FROM chapitre ch
             JOIN programme_officiel po ON ch.id_programme = po.id_programme
             WHERE po.id_matiere = ?
               AND po.annee_scolaire = ?
               AND po.statut = 'PUBLIE'
               AND po.est_actif = 1
             ORDER BY ch.ordre_chapitre"
        );
        $stmt->bind_param("is", $idMatiere, $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProgrammeActif(int $idMatiere): ?array
    {
        $annee = $this->getAnneeCourante();
        $stmt  = $this->db->prepare(
            "SELECT id_programme, titre_programme, annee_scolaire
             FROM programme_officiel
             WHERE id_matiere = ? AND annee_scolaire = ?
               AND statut = 'PUBLIE' AND est_actif = 1
             LIMIT 1"
        );
        $stmt->bind_param("is", $idMatiere, $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function ajouterPointManquant(int $idProgramme, string $titre,
                                          ?string $objectifs, int $idCreateur): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(ordre_chapitre), 0) + 1 AS prochain
             FROM chapitre WHERE id_programme = ?"
        );
        $stmt->bind_param("i", $idProgramme);
        $stmt->execute();
        $ordre = (int)$stmt->get_result()->fetch_assoc()['prochain'];
        $stmt->close();

        $stmt = $this->db->prepare(
            "INSERT INTO chapitre
             (id_programme, titre_chapitre, ordre_chapitre, objectifs_pedagogiques)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("isis", $idProgramme, $titre, $ordre, $objectifs);
        $stmt->execute();
        $idChapitre = (int)$this->db->insert_id;
        $stmt->close();

        $stmt = $this->db->prepare(
            "INSERT INTO leçon
             (id_chapitre, titre_leçon, objectifs_pedagogiques, ordre_leçon,
              source, id_createur, date_creation)
             VALUES (?, ?, ?, 1, 'enseignant', ?, NOW())"
        );
        $objectifsLecon = $objectifs ?? '';
        $stmt->bind_param("issi", $idChapitre, $titre, $objectifsLecon, $idCreateur);
        $stmt->execute();
        $stmt->close();

        return $idChapitre;
    }

    // =========================================================
    //  SÉANCES — CRÉATION
    // =========================================================

    public function createSeance(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO seance
             (id_utilisateur, id_classe, id_matiere, id_progression,
              date_seance, heure_debut, heure_fin,
              contenu_traite, objectifs_atteints, commentaire_enseignant,
              statut, date_saisie)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'REALISEE', NOW())"
        );
        $stmt->bind_param(
            "iiiissssss",
            $data['id_utilisateur'],
            $data['id_classe'],
            $data['id_matiere'],
            $data['id_progression'],
            $data['date_seance'],
            $data['heure_debut'],
            $data['heure_fin'],
            $data['contenu_traite'],
            $data['objectifs_atteints'],
            $data['commentaire_enseignant']
        );
        $stmt->execute();
        $idSeance = (int)$this->db->insert_id;
        $stmt->close();
        return $idSeance;
    }

    public function getOrCreateProgression(int $idUtilisateur, int $idChapitre,
                                             int $idClasse, int $idMatiere): int
    {
        $stmt = $this->db->prepare(
            "SELECT id_leçon FROM leçon WHERE id_chapitre = ? ORDER BY ordre_leçon LIMIT 1"
        );
        $stmt->bind_param("i", $idChapitre);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return 0;

        $idLecon = (int)$row['id_leçon'];

        $stmt = $this->db->prepare(
            "SELECT id_progression FROM progression_programme
             WHERE id_utilisateur = ? AND id_leçon = ? AND id_classe = ? AND id_matiere = ?
             LIMIT 1"
        );
        $stmt->bind_param("iiii", $idUtilisateur, $idLecon, $idClasse, $idMatiere);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $stmt = $this->db->prepare(
                "UPDATE progression_programme
                 SET statut = 'EN_COURS', date_modification = NOW()
                 WHERE id_progression = ? AND statut = 'NON_COMMENCEE'"
            );
            $stmt->bind_param("i", $row['id_progression']);
            $stmt->execute();
            $stmt->close();
            return (int)$row['id_progression'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO progression_programme
             (id_utilisateur, id_leçon, id_classe, id_matiere,
              date_debut, statut, progression_pourcentage)
             VALUES (?, ?, ?, ?, CURDATE(), 'EN_COURS', 0)"
        );
        $stmt->bind_param("iiii", $idUtilisateur, $idLecon, $idClasse, $idMatiere);
        $stmt->execute();
        $id = (int)$this->db->insert_id;
        $stmt->close();
        return $id;
    }

    public function getSeancesRecentes(int $idEnseignant): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.nom_classe, m.nom_matiere, ch.titre_chapitre
             FROM seance s
             JOIN classe c  ON s.id_classe  = c.id_classe
             JOIN matiere m ON s.id_matiere = m.id_matiere
             LEFT JOIN progression_programme pp ON s.id_progression = pp.id_progression
             LEFT JOIN leçon l   ON pp.id_leçon   = l.id_leçon
             LEFT JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
             WHERE s.id_utilisateur = ?
               AND s.date_seance >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             ORDER BY s.date_seance DESC, s.heure_debut DESC
             LIMIT 10"
        );
        $stmt->bind_param("i", $idEnseignant);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    //  BIBLIOTHÈQUE DE SÉANCES
    // =========================================================

    public function getBibliotheque(int $idEnseignant, int $idMatiere = 0,
                                     string $search = ''): array
    {
        $sql = "SELECT s.id_seance, s.date_seance, s.heure_debut, s.heure_fin,
                       s.contenu_traite, s.objectifs_atteints, s.commentaire_enseignant,
                       c.nom_classe, m.nom_matiere, m.id_matiere,
                       ch.titre_chapitre, ch.id_chapitre,
                       (SELECT COUNT(*) FROM piece_jointe pj
                        WHERE pj.id_seance = s.id_seance) AS nb_pieces
                FROM seance s
                JOIN classe c  ON s.id_classe  = c.id_classe
                JOIN matiere m ON s.id_matiere = m.id_matiere
                LEFT JOIN progression_programme pp ON s.id_progression = pp.id_progression
                LEFT JOIN leçon l   ON pp.id_leçon   = l.id_leçon
                LEFT JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
                WHERE s.id_utilisateur = ?";

        $params = [$idEnseignant];
        $types  = "i";

        if ($idMatiere > 0) {
            $sql     .= " AND s.id_matiere = ?";
            $params[] = $idMatiere;
            $types   .= "i";
        }
        if (!empty($search)) {
            $like     = '%' . $search . '%';
            $sql     .= " AND (s.contenu_traite LIKE ? OR ch.titre_chapitre LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $types   .= "ss";
        }
        $sql .= " ORDER BY s.date_seance DESC, s.heure_debut DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSeanceById(int $idSeance, int $idEnseignant): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.nom_classe, m.nom_matiere, m.id_matiere,
                    ch.titre_chapitre, ch.id_chapitre
             FROM seance s
             JOIN classe c  ON s.id_classe  = c.id_classe
             JOIN matiere m ON s.id_matiere = m.id_matiere
             LEFT JOIN progression_programme pp ON s.id_progression = pp.id_progression
             LEFT JOIN leçon l   ON pp.id_leçon   = l.id_leçon
             LEFT JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
             WHERE s.id_seance = ? AND s.id_utilisateur = ?"
        );
        $stmt->bind_param("ii", $idSeance, $idEnseignant);
        $stmt->execute();
        $seance = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$seance) return null;
        $seance['pieces_jointes'] = $this->getPiecesJointes($idSeance);
        return $seance;
    }

    // =========================================================
    //  PIÈCES JOINTES
    // =========================================================

    public function getPiecesJointes(int $idSeance): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_piece, nom_original, url_fichier, type_fichier,
                    taille_fichier, date_upload
             FROM piece_jointe WHERE id_seance = ? ORDER BY date_upload"
        );
        $stmt->bind_param("i", $idSeance);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function savePieceJointe(int $idSeance, string $nomOriginal,
                                     string $urlFichier, string $typeFichier,
                                     int $tailleFichier): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO piece_jointe
             (id_seance, nom_original, url_fichier, type_fichier, taille_fichier, date_upload)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("isssi", $idSeance, $nomOriginal, $urlFichier,
                          $typeFichier, $tailleFichier);
        $stmt->execute();
        $id = (int)$this->db->insert_id;
        $stmt->close();
        return $id;
    }

    public function deletePieceJointe(int $idPiece, int $idEnseignant): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT pj.url_fichier FROM piece_jointe pj
             JOIN seance s ON pj.id_seance = s.id_seance
             WHERE pj.id_piece = ? AND s.id_utilisateur = ?"
        );
        $stmt->bind_param("ii", $idPiece, $idEnseignant);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;

        $stmt = $this->db->prepare("DELETE FROM piece_jointe WHERE id_piece = ?");
        $stmt->bind_param("i", $idPiece);
        $stmt->execute();
        $stmt->close();
        return $row['url_fichier'];
    }

    public function getMatieresFiltres(int $idEnseignant): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT m.id_matiere, m.nom_matiere
             FROM seance s JOIN matiere m ON s.id_matiere = m.id_matiere
             WHERE s.id_utilisateur = ?
             ORDER BY m.nom_matiere"
        );
        $stmt->bind_param("i", $idEnseignant);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    //  UTILITAIRES
    // =========================================================

    public function getAnneeCourante(): string
    {
        $m = (int)date('m');
        $y = (int)date('Y');
        return $m >= 9 ? "{$y}-" . ($y + 1) : ($y - 1) . "-{$y}";
    }
}
