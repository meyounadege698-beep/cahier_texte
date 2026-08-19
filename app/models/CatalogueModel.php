<?php

/**
 * CatalogueModel — CRUD sur les départements et matières.
 */
class CatalogueModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  DÉPARTEMENTS
    // =========================================================

    public function getAllDepts(): array
    {
        $result = $this->db->query(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM matiere m WHERE m.id_departement = d.id_departement) AS nb_matieres
             FROM departement d ORDER BY d.nom_departement"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getDeptById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM departement WHERE id_departement = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function addDept(string $nom, ?string $code, ?string $description): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO departement (nom_departement, code_departement, description)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $nom, $code, $description);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function updateDept(int $id, string $nom, ?string $code, ?string $description): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE departement SET nom_departement=?, code_departement=?, description=?
             WHERE id_departement=?"
        );
        $stmt->bind_param("sssi", $nom, $code, $description, $id);
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public function deleteDept(int $id): bool
    {
        // Vérifier qu'il n'y a pas de matières liées
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM matiere WHERE id_departement = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $n = (int)$stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();
        if ($n > 0) return false; // bloqué par contrainte métier

        $stmt = $this->db->prepare("DELETE FROM departement WHERE id_departement = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function deptCodeExists(string $code, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM departement WHERE code_departement = ? AND id_departement != ?"
        );
        $stmt->bind_param("si", $code, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // =========================================================
    //  MATIÈRES
    // =========================================================

    public function getAllMats(): array
    {
        $result = $this->db->query(
            "SELECT m.*, d.nom_departement, d.code_departement,
                    (SELECT COUNT(*) FROM programme_officiel po WHERE po.id_matiere = m.id_matiere) AS nb_programmes
             FROM matiere m
             JOIN departement d ON m.id_departement = d.id_departement
             ORDER BY d.nom_departement, m.nom_matiere"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getMatiereById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, d.nom_departement FROM matiere m
             JOIN departement d ON m.id_departement = d.id_departement
             WHERE m.id_matiere = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function addMat(int $idDept, string $nom, ?string $code,
                            float $coef, ?int $volumeHoraire, ?string $description): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO matiere
             (id_departement, nom_matiere, code_matiere, coefficient,
              volume_horaire_annuel, description)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issdis", $idDept, $nom, $code, $coef, $volumeHoraire, $description);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function updateMat(int $id, int $idDept, string $nom, ?string $code,
                               float $coef, ?int $volumeHoraire, ?string $description): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE matiere SET id_departement=?, nom_matiere=?, code_matiere=?,
             coefficient=?, volume_horaire_annuel=?, description=?
             WHERE id_matiere=?"
        );
        $stmt->bind_param("issdisi", $idDept, $nom, $code, $coef,
                          $volumeHoraire, $description, $id);
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public function deleteMat(int $id): bool
    {
        // Vérifier qu'aucun programme ne la référence
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM programme_officiel WHERE id_matiere = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $n = (int)$stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();
        if ($n > 0) return false;

        $stmt = $this->db->prepare("DELETE FROM matiere WHERE id_matiere = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function matCodeExists(string $code, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM matiere WHERE code_matiere = ? AND id_matiere != ?"
        );
        $stmt->bind_param("si", $code, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
}
