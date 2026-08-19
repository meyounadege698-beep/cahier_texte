<?php

/**
 * DevoirModel — Devoirs rattachés aux séances.
 * Table : devoir
 */
class DevoirModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getDevoirsBySeance(int $idSeance): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM devoir WHERE id_seance = ? ORDER BY date_remise"
        );
        $stmt->bind_param("i", $idSeance);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Devoirs récents d'un enseignant (toutes séances confondues).
     */
    public function getDevoirsByEnseignant(int $idEnseignant): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, s.date_seance, c.nom_classe, m.nom_matiere
             FROM devoir d
             JOIN seance s  ON d.id_seance  = s.id_seance
             JOIN classe c  ON s.id_classe  = c.id_classe
             JOIN matiere m ON s.id_matiere = m.id_matiere
             WHERE s.id_utilisateur = ?
             ORDER BY d.date_remise DESC, d.date_creation DESC
             LIMIT 50"
        );
        $stmt->bind_param("i", $idEnseignant);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM devoir WHERE id_devoir = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(int $idSeance, string $titre, string $consigne,
                            string $type, string $dateRemise,
                            int $coeff, int $noteSur): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO devoir
             (id_seance, titre, consigne, type_devoir, date_remise,
              coeff_notation, note_sur, date_creation)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("issssii", $idSeance, $titre, $consigne,
                          $type, $dateRemise, $coeff, $noteSur);
        $stmt->execute();
        $id = (int)$this->db->insert_id;
        $stmt->close();
        return $id;
    }

    public function update(int $id, string $titre, string $consigne,
                            string $type, string $dateRemise,
                            int $coeff, int $noteSur): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE devoir SET titre=?, consigne=?, type_devoir=?, date_remise=?,
             coeff_notation=?, note_sur=?, date_modification=NOW()
             WHERE id_devoir=?"
        );
        $stmt->bind_param("sssssii", $titre, $consigne, $type,
                          $dateRemise, $coeff, $noteSur, $id);
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM devoir WHERE id_devoir = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Vérifie que le devoir appartient bien à une séance de l'enseignant.
     */
    public function belongsToEnseignant(int $idDevoir, int $idEnseignant): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM devoir d
             JOIN seance s ON d.id_seance = s.id_seance
             WHERE d.id_devoir = ? AND s.id_utilisateur = ?"
        );
        $stmt->bind_param("ii", $idDevoir, $idEnseignant);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
}
