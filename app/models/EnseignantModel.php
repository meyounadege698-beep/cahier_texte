<?php

/**
 * EnseignantModel — Gestion des comptes enseignants par le censeur.
 * Toutes les opérations sur la table `utilisateur` filtrées role='enseignant'.
 */
class EnseignantModel
{
    private mysqli $db;

    /** Mot de passe par défaut attribué à la création. */
    public const DEFAULT_PASSWORD = 'Enseignant@2026';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  LECTURE
    // =========================================================

    /**
     * Tous les enseignants avec le nombre d'affectations.
     */
    public function getAll(): array
    {
        $result = $this->db->query(
            "SELECT u.id_utilisateur, u.nom, u.prenom, u.email,
                    u.date_inscription, u.derniere_connexion, u.est_actif,
                    (SELECT COUNT(*) FROM affectation_enseignant ae
                     WHERE ae.id_utilisateur = u.id_utilisateur) AS nb_affectations
             FROM utilisateur u
             WHERE u.role = 'enseignant'
             ORDER BY u.nom, u.prenom"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_utilisateur, nom, prenom, email, est_actif, date_inscription
             FROM utilisateur WHERE id_utilisateur = ? AND role = 'enseignant'"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM utilisateur WHERE email = ? AND id_utilisateur != ?"
        );
        $stmt->bind_param("si", $email, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // =========================================================
    //  CRÉATION avec mot de passe par défaut
    // =========================================================

    /**
     * Crée un enseignant et retourne son ID.
     * Le mot de passe est haché avec bcrypt.
     * Retourne l'id_utilisateur créé ou 0 en cas d'erreur.
     */
    public function create(string $nom, string $prenom, string $email,
                            string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "INSERT INTO utilisateur
             (nom, prenom, email, mot_de_passe_hash, role, date_inscription, est_actif)
             VALUES (?, ?, ?, ?, 'enseignant', NOW(), 1)"
        );
        $stmt->bind_param("ssss", $nom, $prenom, $email, $hash);
        $stmt->execute();
        $id = (int)$this->db->insert_id;
        $stmt->close();
        return $id;
    }

    // =========================================================
    //  MISE À JOUR
    // =========================================================

    public function update(int $id, string $nom, string $prenom, string $email): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE utilisateur SET nom=?, prenom=?, email=?
             WHERE id_utilisateur=? AND role='enseignant'"
        );
        $stmt->bind_param("sssi", $nom, $prenom, $email, $id);
        $stmt->execute();
        return $stmt->affected_rows >= 0;
    }

    public function toggleActif(int $id, int $actif): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE utilisateur SET est_actif=?
             WHERE id_utilisateur=? AND role='enseignant'"
        );
        $stmt->bind_param("ii", $actif, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Réinitialise le mot de passe d'un enseignant.
     * Retourne le nouveau mot de passe en clair (affiché une fois au censeur).
     */
    public function resetPassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "UPDATE utilisateur SET mot_de_passe_hash=?
             WHERE id_utilisateur=? AND role='enseignant'"
        );
        $stmt->bind_param("si", $hash, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Génère un mot de passe aléatoire fort (12 caractères).
     * Format : [A-Z][a-z]{4}[0-9]{3}[!@#$%]{2}[a-z]{2}
     */
    public static function generatePassword(): string
    {
        $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower  = 'abcdefghjkmnpqrstuvwxyz';
        $digits = '23456789';
        $special= '!@#$%';

        $pwd  = $upper[random_int(0, strlen($upper)-1)];
        $pwd .= substr(str_shuffle(str_repeat($lower, 4)), 0, 4);
        $pwd .= substr(str_shuffle(str_repeat($digits, 3)), 0, 3);
        $pwd .= $special[random_int(0, strlen($special)-1)];
        $pwd .= $special[random_int(0, strlen($special)-1)];
        $pwd .= substr(str_shuffle(str_repeat($lower, 2)), 0, 2);

        // Mélanger les positions
        return str_shuffle($pwd);
    }
}
