<?php

/**
 * AffectationModel — Salles + affectations enseignants.
 * Supporte les affectations multiples (plusieurs matières, salles, départements).
 */
class AffectationModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  SALLES
    // =========================================================

    public function getAllSalles(): array
    {
        $result = $this->db->query(
            "SELECT s.*,
                (SELECT COUNT(*) FROM affectation_enseignant ae
                 WHERE ae.id_salle = s.id_salle) AS nb_affectations
             FROM salle s ORDER BY s.nom_salle"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getSallesActives(): array
    {
        $result = $this->db->query(
            "SELECT * FROM salle WHERE est_active = 1 ORDER BY nom_salle"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addSalle(string $nom, ?int $capacite, string $type,
                              ?string $localisation): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO salle (nom_salle, capacite, type_salle, localisation)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("siss", $nom, $capacite, $type, $localisation);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function updateSalle(int $id, string $nom, ?int $capacite,
                                 string $type, ?string $localisation, int $active): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE salle SET nom_salle=?, capacite=?, type_salle=?,
             localisation=?, est_active=? WHERE id_salle=?"
        );
        $stmt->bind_param("sissii", $nom, $capacite, $type, $localisation, $active, $id);
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public function deleteSalle(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM affectation_enseignant WHERE id_salle = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ((int)$stmt->get_result()->fetch_assoc()['n'] > 0) return false;
        $stmt->close();
        $stmt = $this->db->prepare("DELETE FROM salle WHERE id_salle = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function salleNomExists(string $nom, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM salle WHERE nom_salle = ? AND id_salle != ?"
        );
        $stmt->bind_param("si", $nom, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // =========================================================
    //  DONNÉES DE RÉFÉRENCE
    // =========================================================

    public function getAllEnseignants(): array
    {
        $result = $this->db->query(
            "SELECT id_utilisateur, nom, prenom, email
             FROM utilisateur WHERE role = 'enseignant' AND est_actif = 1
             ORDER BY nom, prenom"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllDepartements(): array
    {
        $result = $this->db->query(
            "SELECT * FROM departement ORDER BY nom_departement"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllMatieres(): array
    {
        $result = $this->db->query(
            "SELECT m.*, d.nom_departement, d.code_departement, d.id_departement AS dept_id
             FROM matiere m
             JOIN departement d ON m.id_departement = d.id_departement
             ORDER BY d.nom_departement, m.nom_matiere"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllClasses(string $annee = ''): array
    {
        if ($annee) {
            $stmt = $this->db->prepare(
                "SELECT * FROM classe WHERE annee_scolaire = ? ORDER BY niveau, nom_classe"
            );
            $stmt->bind_param("s", $annee);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $result = $this->db->query(
            "SELECT * FROM classe ORDER BY annee_scolaire DESC, niveau, nom_classe"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAnnesScolaires(): array
    {
        $result = $this->db->query(
            "SELECT DISTINCT annee_scolaire FROM classe ORDER BY annee_scolaire DESC"
        );
        $fromDb = array_column($result->fetch_all(MYSQLI_ASSOC), 'annee_scolaire');
        $m = (int)date('m'); $y = (int)date('Y');
        $current = $m >= 9 ? "{$y}-".($y+1) : ($y-1)."-{$y}";
        if (!in_array($current, $fromDb)) array_unshift($fromDb, $current);
        return array_unique($fromDb);
    }

    // =========================================================
    //  AFFECTATIONS — LECTURE
    // =========================================================

    /**
     * Toutes les affectations d'une année, groupées par enseignant.
     */
    public function getAffectationsByAnnee(string $annee): array
    {
        $stmt = $this->db->prepare(
            "SELECT ae.*,
                CONCAT(u.nom,' ',u.prenom) AS nom_enseignant,
                u.email,
                c.nom_classe, c.niveau, c.filiere,
                m.nom_matiere, m.code_matiere, m.coefficient,
                d.nom_departement, d.code_departement,
                s.nom_salle, s.type_salle
             FROM affectation_enseignant ae
             JOIN utilisateur u ON ae.id_utilisateur = u.id_utilisateur
             JOIN classe c      ON ae.id_classe      = c.id_classe
             JOIN matiere m     ON ae.id_matiere     = m.id_matiere
             JOIN departement d ON m.id_departement  = d.id_departement
             LEFT JOIN salle s  ON ae.id_salle       = s.id_salle
             WHERE ae.annee_scolaire = ?
             ORDER BY u.nom, d.nom_departement, m.nom_matiere, c.nom_classe"
        );
        $stmt->bind_param("s", $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Affectations d'un enseignant donné.
     */
    public function getAffectationsByEnseignant(int $idEns, string $annee): array
    {
        $stmt = $this->db->prepare(
            "SELECT ae.*,
                c.nom_classe, c.niveau,
                m.nom_matiere, m.code_matiere,
                d.nom_departement, d.code_departement,
                s.nom_salle
             FROM affectation_enseignant ae
             JOIN classe c      ON ae.id_classe  = c.id_classe
             JOIN matiere m     ON ae.id_matiere = m.id_matiere
             JOIN departement d ON m.id_departement = d.id_departement
             LEFT JOIN salle s  ON ae.id_salle   = s.id_salle
             WHERE ae.id_utilisateur = ? AND ae.annee_scolaire = ?
             ORDER BY d.nom_departement, m.nom_matiere, c.nom_classe"
        );
        $stmt->bind_param("is", $idEns, $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    //  AFFECTATIONS — ÉCRITURE
    // =========================================================

    /**
     * Crée UNE affectation (classe + matière + salle optionnelle).
     */
    public function addAffectation(int $idEns, int $idClasse, int $idMatiere,
                                    string $annee, ?int $idSalle,
                                    ?int $volHoraire, int $principal): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO affectation_enseignant
             (id_utilisateur, id_classe, id_matiere, annee_scolaire,
              id_salle, volume_horaire_hebdo, est_principal, date_affectation)
             VALUES (?,?,?,?,?,?,?,CURDATE())"
        );
        $stmt->bind_param("iiisiii", $idEns, $idClasse, $idMatiere,
                          $annee, $idSalle, $volHoraire, $principal);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    /**
     * Crée PLUSIEURS affectations en une transaction.
     * $lignes = [[id_classe, id_matiere, id_salle|null, vol_horaire|null, est_principal], ...]
     * Retourne [created => int, skipped => int]
     */
    public function addAffectationsMultiples(int $idEns, string $annee,
                                              array $lignes): array
    {
        $created = 0;
        $skipped = 0;

        $this->db->begin_transaction();
        try {
            foreach ($lignes as $l) {
                $idClasse  = (int)$l['id_classe'];
                $idMatiere = (int)$l['id_matiere'];
                $idSalle   = isset($l['id_salle']) && $l['id_salle'] ? (int)$l['id_salle'] : null;
                $vol       = isset($l['volume']) && $l['volume'] ? (int)$l['volume'] : null;
                $principal = isset($l['principal']) ? 1 : 0;

                if ($idClasse <= 0 || $idMatiere <= 0) { $skipped++; continue; }

                // Vérifier doublon
                if ($this->affectationExists($idEns, $idClasse, $idMatiere, $annee)) {
                    $skipped++; continue;
                }

                $this->addAffectation($idEns, $idClasse, $idMatiere,
                                      $annee, $idSalle, $vol, $principal);
                $created++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function updateAffectation(int $id, ?int $idSalle,
                                       ?int $volHoraire, int $principal): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE affectation_enseignant
             SET id_salle=?, volume_horaire_hebdo=?, est_principal=?
             WHERE id_affectation=?"
        );
        $stmt->bind_param("iiii", $idSalle, $volHoraire, $principal, $id);
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public function deleteAffectation(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM affectation_enseignant WHERE id_affectation = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function affectationExists(int $idEns, int $idClasse, int $idMat,
                                       string $annee, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM affectation_enseignant
             WHERE id_utilisateur=? AND id_classe=? AND id_matiere=?
               AND annee_scolaire=? AND id_affectation!=?"
        );
        $stmt->bind_param("iiisi", $idEns, $idClasse, $idMat, $annee, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
}
