<?php

/**
 * Router — Routeur frontal basé sur le paramètre GET "page".
 *
 * Usage :
 *   index.php?page=login
 *   index.php?page=register
 *   index.php?page=dashboard
 *   index.php           → dashboard (si connecté) sinon login
 */
class Router
{
    /** @var array<string, array{controller: string, action: string}> */
    private array $routes = [];

    public function add(string $page, string $controller, string $action): void
    {
        $this->routes[$page] = [
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    public function dispatch(): void
    {
        $page = trim($_GET['page'] ?? 'dashboard');

        // Whitelist des pages autorisées
        if (!array_key_exists($page, $this->routes)) {
            $this->notFound();
            return;
        }

        $route      = $this->routes[$page];
        $controller = $route['controller'];
        $action     = $route['action'];

        $file = APP_ROOT . '/app/controllers/' . $controller . '.php';

        if (!file_exists($file)) {
            $this->notFound();
            return;
        }

        require_once $file;
        $ctrl = new $controller();
        $ctrl->$action();
    }

    private function notFound(): void
    {
        http_response_code(404);
        include APP_ROOT . '/app/views/errors/404.php';
    }
}
