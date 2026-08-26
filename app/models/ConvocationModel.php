<?php

/**
 * ConvocationModel — Convocations enseignants.
 */
class ConvocationModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $result = $this->db->query(
            "SELECT c.*,
                CONCAT(e.nom,' ',e.prenom) AS nom_enseignant, e.email AS email_enseignant,
                CONCAT(a.nom,' ',a.prenom) AS nom_emetteur
             FROM convocation c
             JOIN utilisateur e ON c.id_enseignant = e.id_utilisateur
             JOIN utilisateur a ON c.id_admin      = a.id_utilisateur
             ORDER BY c.date_envoi DESC"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByEnseignant(int $idEns): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,
                CONCAT(a.nom,' ',a.prenom) AS nom_emetteur
             FROM convocation c
             JOIN utilisateur a ON c.id_admin = a.id_utilisateur
             WHERE c.id_enseignant = ?
             ORDER BY c.date_envoi DESC"
        );
        $stmt->bind_param("i", $idEns);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(int $idEnseignant, int $idAdmin, string $motif,
                            string $dateConvocation, ?string $lieu): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO convocation (id_enseignant, id_admin, motif, date_convocation, lieu)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $idEnseignant, $idAdmin, $motif, $dateConvocation, $lieu);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function marquerLue(int $id, int $idEnseignant): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE convocation SET statut='lue', date_lecture=NOW()
             WHERE id_convocation = ? AND id_enseignant = ? AND statut = 'envoyee'"
        );
        $stmt->bind_param("ii", $id, $idEnseignant);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function acquitter(int $id, int $idEnseignant): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE convocation SET statut='acquittee'
             WHERE id_convocation = ? AND id_enseignant = ?"
        );
        $stmt->bind_param("ii", $id, $idEnseignant);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM convocation WHERE id_convocation = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function getNonLues(int $idEnseignant): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM convocation
             WHERE id_enseignant = ? AND statut = 'envoyee'"
        );
        $stmt->bind_param("i", $idEnseignant);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['n'];
    }
}
