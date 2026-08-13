<?php

/**
 * index.php — Point d'entrée unique (Front Controller)
 *
 * Toutes les requêtes passent par ici.
 * URL : http://localhost/cahier_texte/?page=login
 *       http://localhost/cahier_texte/?page=register
 *       http://localhost/cahier_texte/?page=dashboard
 *       http://localhost/cahier_texte/?page=logout
 */

// ===== CHARGEMENT DE LA CONFIGURATION =====
require_once __DIR__ . '/config/config.php';

// ===== CHARGEMENT DU CORE =====
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Router.php';

// ===== DÉMARRAGE DE LA SESSION =====
Session::start();

// ===== ROUTEUR =====
$router = new Router();

// Authentification
$router->add('login',    'AuthController', 'loginForm');
$router->add('do-login', 'AuthController', 'login');       // POST login
$router->add('register', 'AuthController', 'registerForm');
$router->add('do-register', 'AuthController', 'register'); // POST register
$router->add('logout',   'AuthController', 'logout');

// Dashboard (page principale)
$router->add('dashboard', 'DashboardController', 'index');

// ===== DISPATCH =====
// Si une action POST est soumise, on route vers le bon handler
$page = $_GET['page'] ?? 'dashboard';

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-login';
} elseif ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-register';
}

$router->dispatch();
