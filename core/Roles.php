<?php

/**
 * Roles — Helper centralisé pour les vérifications de rôles.
 *
 * Règle : l'administrateur a accès à TOUT (censeur + enseignant).
 * Cela évite de modifier chaque contrôleur individuellement à l'avenir.
 */
class Roles
{
    /** Rôles qui ont les droits du censeur */
    public const CENSEUR_ROLES = ['censeur', 'administrateur'];

    /** Rôles qui ont les droits de l'enseignant */
    public const ENSEIGNANT_ROLES = ['enseignant', 'administrateur'];

    /** Tous les rôles authentifiés */
    public const ALL_ROLES = ['enseignant', 'censeur', 'administrateur'];

    /**
     * Vérifie que l'utilisateur connecté a le rôle censeur (ou admin).
     * Redirige vers dashboard si non autorisé.
     */
    public static function requireCenseur(): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
        if (!in_array(Session::get('role'), self::CENSEUR_ROLES)) {
            Session::setFlash('error', 'Accès réservé au censeur.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard'); exit();
        }
    }

    /**
     * Vérifie que l'utilisateur connecté a le rôle enseignant (ou admin).
     */
    public static function requireEnseignant(): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
        if (!in_array(Session::get('role'), self::ENSEIGNANT_ROLES)) {
            Session::setFlash('error', 'Accès réservé aux enseignants.');
            header('Location: ' . APP_URL . '/app.php?page=dashboard'); exit();
        }
    }

    /**
     * Vérifie que l'utilisateur est connecté (n'importe quel rôle).
     */
    public static function requireAuth(): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: ' . APP_URL . '/app.php?page=login'); exit();
        }
    }

    /** Raccourcis de vérification (retournent bool sans redirection) */
    public static function isCenseur(): bool
    {
        return in_array(Session::get('role'), self::CENSEUR_ROLES);
    }

    public static function isEnseignant(): bool
    {
        return in_array(Session::get('role'), self::ENSEIGNANT_ROLES);
    }

    public static function isAdmin(): bool
    {
        return Session::get('role') === 'administrateur';
    }
}
