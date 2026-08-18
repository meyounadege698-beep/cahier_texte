<?php

/**
 * UserModel — Opérations BDD sur la table `utilisateur`.
 *
 * Colonnes : id_utilisateur, nom, prenom, email, mot_de_passe_hash,
 *            role, date_inscription, derniere_connexion, est_actif
 */
class UserModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  LECTURE
    // =========================================================

    /**
     * Trouve un utilisateur par email.
     * Inclut est_actif pour vérification au login.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_utilisateur, nom, prenom, email, mot_de_passe_hash,
                    role, date_inscription, derniere_connexion, est_actif
             FROM " . TABLE_USERS . "
             WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $user;
    }

    /**
     * Trouve un utilisateur par son ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_utilisateur, nom, prenom, email, role,
                    date_inscription, derniere_connexion, est_actif
             FROM " . TABLE_USERS . "
             WHERE id_utilisateur = ? LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $user;
    }

    // =========================================================
    //  ÉCRITURE
    // =========================================================

    /**
     * Vérifie si un email existe déjà.
     */
    public function existsByEmail(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id_utilisateur FROM " . TABLE_USERS . " WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Crée un nouvel utilisateur avec prenom.
     * Hache le mot de passe avec bcrypt.
     */
    public function create(string $nom, string $prenom, string $email, string $password, string $role): bool
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "INSERT INTO " . TABLE_USERS . "
             (nom, prenom, email, mot_de_passe_hash, role, date_inscription)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("sssss", $nom, $prenom, $email, $hash, $role);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Met à jour la date de dernière connexion.
     * Appelé à chaque login réussi.
     */
    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE " . TABLE_USERS . "
             SET derniere_connexion = NOW()
             WHERE id_utilisateur = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}
