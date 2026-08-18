<?php
/**
 * index.php — Page d'accueil visiteur (point d'entrée public)
 * Affiche directement la présentation du projet.
 * Accessible à tous sans redirection.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Session.php';

Session::start();

// Titre de page
$pageTitle = APP_NAME . ' — Cahier de Texte Digital';

// Inclure directement la vue d'accueil
require_once __DIR__ . '/app/views/home/index.php';
