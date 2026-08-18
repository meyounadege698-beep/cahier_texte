<?php

/**
 * app.php — Front Controller MVC
 *
 * Toutes les routes applicatives passent ici.
 *   http://localhost/cahier_texte/app.php?page=login
 *   http://localhost/cahier_texte/app.php?page=register
 *   http://localhost/cahier_texte/app.php?page=dashboard
 *   http://localhost/cahier_texte/app.php?page=logout
 *
 * Page d'accueil visiteur → index.php (racine)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Router.php';

Session::start();

$router = new Router();

// Authentification
$router->add('login',       'AuthController', 'loginForm');
$router->add('do-login',    'AuthController', 'login');
$router->add('register',    'AuthController', 'registerForm');
$router->add('do-register', 'AuthController', 'register');
$router->add('logout',      'AuthController', 'logout');

// Application
$router->add('dashboard',   'DashboardController', 'index');

// ===== DISPATCH =====
$page = $_GET['page'] ?? (Session::isLoggedIn() ? 'dashboard' : 'login');

// Rerouter les POST vers les handlers dédiés
if ($page === 'login'    && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-login';
} elseif ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-register';
}

$router->dispatch();
