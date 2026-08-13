<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/style.css">
    <?php if (!empty($extraCss)): ?>
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/<?= htmlspecialchars($extraCss) ?>">
    <?php endif; ?>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="<?= APP_URL ?>/?page=dashboard" class="nav-brand">
        📘 <?= APP_NAME ?>
    </a>
    <div class="nav-links">
        <?php if (Session::isLoggedIn()): ?>
            <span class="nav-user">
                👤 <?= htmlspecialchars(Session::get('nom'), ENT_QUOTES, 'UTF-8') ?>
                <span class="badge-role"><?= htmlspecialchars(Session::get('role'), ENT_QUOTES, 'UTF-8') ?></span>
            </span>
            <a href="<?= APP_URL ?>/?page=logout" class="btn btn-outline">🚪 Déconnexion</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/?page=login"    class="btn btn-outline">Se connecter</a>
            <a href="<?= APP_URL ?>/?page=register" class="btn btn-primary">S'inscrire</a>
        <?php endif; ?>
    </div>
</nav>

<main>
