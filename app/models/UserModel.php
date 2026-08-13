<?php

/**
 * UserModel — Opérations BDD sur la table `utilisateur`.
 *
 * Colonnes : id_utilisateur, nom, email, mot_de_passe_hash, role, date_inscription
 */
class UserModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

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
     * Crée un nouvel utilisateur.
     * Hache le mot de passe avec bcrypt.
     */
    public function create(string $nom, string $email, string $password, string $role): bool
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "INSERT INTO " . TABLE_USERS . "
             (nom, email, mot_de_passe_hash, role, date_inscription)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("ssss", $nom, $email, $hash, $role);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Trouve un utilisateur par email.
     * Retourne le tableau complet ou null.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_utilisateur, nom, email, mot_de_passe_hash, role, date_inscription
             FROM " . TABLE_USERS . "
             WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc() ?: null;
        $stmt->close();
        return $user;
    }

    /**
     * Trouve un utilisateur par son ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_utilisateur, nom, email, role, date_inscription
             FROM " . TABLE_USERS . "
             WHERE id_utilisateur = ? LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc() ?: null;
        $stmt->close();
        return $user;
    }
}
