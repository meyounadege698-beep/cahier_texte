<?php

/**
 * Session — Gestion centralisée des sessions et messages flash.
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => false, // Mettre true en production (HTTPS)
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    // ===== AUTHENTIFICATION =====

    /**
     * Stocke l'utilisateur en session après connexion.
     * Colonnes BDD : id_utilisateur, nom, prenom, email, role, est_actif
     */
    public static function setUser(array $user): void
    {
        session_regenerate_id(true); // Prévention fixation de session
        $_SESSION['user_id']   = $user['id_utilisateur'];
        $_SESSION['nom']       = $user['nom'];
        $_SESSION['prenom']    = $user['prenom'] ?? '';
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_in'] = true;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        session_destroy();
    }

    // ===== MESSAGES FLASH =====

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    public static function getFlash(): string
    {
        if (empty($_SESSION['flash'])) {
            return '';
        }

        $html = '';
        foreach ($_SESSION['flash'] as $type => $message) {
            $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $icon = ($type === 'error') ? '⚠️' : '✅';
            $html .= "<div class=\"alert alert-{$type}\">{$icon} {$safe}</div>";
        }

        unset($_SESSION['flash']);
        return $html;
    }

    // ===== CSRF =====

    public static function generateCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        return isset($_SESSION['csrf_token']) &&
               hash_equals($_SESSION['csrf_token'], $token);
    }

    // ===== ACCÈS GÉNÉRIQUES =====

    public static function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }
}
