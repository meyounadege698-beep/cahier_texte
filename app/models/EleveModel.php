<?php

/**
 * EleveModel — CRUD élèves et classes.
 * Tables : eleve, classe
 */
class EleveModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  CLASSES
    // =========================================================

    public function getAllClasses(string $annee = ''): array
    {
        if ($annee) {
            $stmt = $this->db->prepare(
                "SELECT c.*,
                    (SELECT COUNT(*) FROM eleve e WHERE e.id_classe = c.id_classe AND e.est_actif = 1) AS nb_eleves
                 FROM classe c WHERE c.annee_scolaire = ?
                 ORDER BY c.niveau, c.nom_classe"
            );
            $stmt->bind_param("s", $annee);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $result = $this->db->query(
            "SELECT c.*,
                (SELECT COUNT(*) FROM eleve e WHERE e.id_classe = c.id_classe AND e.est_actif = 1) AS nb_eleves
             FROM classe c
             ORDER BY c.annee_scolaire DESC, c.niveau, c.nom_classe"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getClasseById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM classe WHERE id_classe = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function addClasse(string $nom, string $niveau, ?string $filiere,
                               string $annee, int $effectifMax): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO classe (nom_classe, niveau, filiere, annee_scolaire, effectif_max)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssi", $nom, $niveau, $filiere, $annee, $effectifMax);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function updateClasse(int $id, string $nom, string $niveau,
                                  ?string $filiere, string $annee, int $effectifMax): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE classe SET nom_classe=?, niveau=?, filiere=?,
             annee_scolaire=?, effectif_max=? WHERE id_classe=?"
        );
        $stmt->bind_param("ssssii", $nom, $niveau, $filiere, $annee, $effectifMax, $id);
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public function deleteClasse(int $id): bool
    {
        // Bloquer si des élèves ou affectations existent
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM eleve WHERE id_classe = ? AND est_actif = 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ((int)$stmt->get_result()->fetch_assoc()['n'] > 0) return false;
        $stmt->close();

        $stmt = $this->db->prepare("DELETE FROM classe WHERE id_classe = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function getAnnesScolaires(): array
    {
        $result = $this->db->query(
            "SELECT DISTINCT annee_scolaire FROM classe ORDER BY annee_scolaire DESC"
        );
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $annees = array_column($rows, 'annee_scolaire');
        $m = (int)date('m'); $y = (int)date('Y');
        $current = $m >= 9 ? "{$y}-".($y+1) : ($y-1)."-{$y}";
        if (!in_array($current, $annees)) array_unshift($annees, $current);
        return array_unique($annees);
    }

    // =========================================================
    //  ÉLÈVES
    // =========================================================

    public function getElevesByClasse(int $idClasse): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM eleve WHERE id_classe = ? ORDER BY nom, prenom"
        );
        $stmt->bind_param("i", $idClasse);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getEleveById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, c.nom_classe FROM eleve e
             JOIN classe c ON e.id_classe = c.id_classe
             WHERE e.id_eleve = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function matriculeExists(string $matricule, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM eleve WHERE matricule = ? AND id_eleve != ?"
        );
        $stmt->bind_param("si", $matricule, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function addEleve(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO eleve
             (id_classe, nom, prenom, matricule, annee_scolaire,
              date_naissance, lieu_naissance, sexe, telephone, email_parent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iissssssss",
            $data['id_classe'], $data['nom'], $data['prenom'],
            $data['matricule'], $data['annee_scolaire'],
            $data['date_naissance'], $data['lieu_naissance'],
            $data['sexe'], $data['telephone'], $data['email_parent']
        );
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function updateEleve(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE eleve SET id_classe=?, nom=?, prenom=?, matricule=?,
             date_naissance=?, lieu_naissance=?, sexe=?, telephone=?, email_parent=?
             WHERE id_eleve=?"
        );
        $stmt->bind_param(
            "issssssssi",
            $data['id_classe'], $data['nom'], $data['prenom'],
            $data['matricule'], $data['date_naissance'],
            $data['lieu_naissance'], $data['sexe'],
            $data['telephone'], $data['email_parent'], $id
        );
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public function toggleActifEleve(int $id, int $actif): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE eleve SET est_actif=? WHERE id_eleve=?"
        );
        $stmt->bind_param("ii", $actif, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Recherche d'élèves par nom/prénom/matricule.
     */
    public function search(string $q, string $annee = ''): array
    {
        $like = '%' . $q . '%';
        if ($annee) {
            $stmt = $this->db->prepare(
                "SELECT e.*, c.nom_classe FROM eleve e
                 JOIN classe c ON e.id_classe = c.id_classe
                 WHERE e.annee_scolaire = ?
                   AND (e.nom LIKE ? OR e.prenom LIKE ? OR e.matricule LIKE ?)
                 ORDER BY e.nom, e.prenom LIMIT 50"
            );
            $stmt->bind_param("ssss", $annee, $like, $like, $like);
        } else {
            $stmt = $this->db->prepare(
                "SELECT e.*, c.nom_classe FROM eleve e
                 JOIN classe c ON e.id_classe = c.id_classe
                 WHERE e.nom LIKE ? OR e.prenom LIKE ? OR e.matricule LIKE ?
                 ORDER BY e.nom, e.prenom LIMIT 50"
            );
            $stmt->bind_param("sss", $like, $like, $like);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
