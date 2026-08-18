<?php

/**
 * HomeController — Page d'accueil publique du projet.
 * Accessible à tous, connectés ou non.
 */
class HomeController
{
    public function index(): void
    {
        $pageTitle = APP_NAME . ' — Cahier de Texte Digital';
        include APP_ROOT . '/app/views/home/index.php';
    }
}
