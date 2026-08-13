<?php
$pageTitle = 'Dashboard — ' . APP_NAME;
include APP_ROOT . '/app/views/layouts/header.php';
?>

<!-- Messages flash -->
<?= Session::getFlash() ?>

<!-- Bannière de bienvenue -->
<div class="welcome-banner">
    <div class="welcome-text">
        <h2>Bienvenue, <?= htmlspecialchars($user['nom'], ENT_QUOTES, 'UTF-8') ?> 👋</h2>
        <p>Vous êtes connecté à votre espace <strong><?= APP_NAME ?></strong>.</p>
    </div>
    <div class="welcome-avatar">
        <?php
        $roleIcons = [
            'enseignant'     => '👨‍🏫',
            'eleve'          => '🎓',
            'parent'         => '👨‍👩‍👧',
            'administrateur' => '⚙️',
        ];
        echo $roleIcons[$user['role']] ?? '👤';
        ?>
    </div>
</div>

<!-- Grille de cartes -->
<div class="dashboard-grid">

    <!-- Carte profil -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">👤</div>
            <div>
                <div class="card-title">Mon profil</div>
                <div class="card-subtitle">Informations du compte</div>
            </div>
        </div>
        <ul class="info-list">
            <li>
                <span class="info-label">📛 Nom</span>
                <span class="info-value"><?= htmlspecialchars($user['nom'], ENT_QUOTES, 'UTF-8') ?></span>
            </li>
            <li>
                <span class="info-label">✉️ Email</span>
                <span class="info-value"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
            </li>
            <li>
                <span class="info-label">🏷️ Rôle</span>
                <span class="info-value"><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?></span>
            </li>
            <li>
                <span class="info-label">📅 Inscrit le</span>
                <span class="info-value">
                    <?= $user['date_inscription']
                        ? date('d/m/Y', strtotime($user['date_inscription']))
                        : '—' ?>
                </span>
            </li>
        </ul>
    </div>

    <!-- Carte session -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">🔐</div>
            <div>
                <div class="card-title">Session active</div>
                <div class="card-subtitle">Détails de connexion</div>
            </div>
        </div>
        <ul class="info-list">
            <li>
                <span class="info-label">🆔 ID utilisateur</span>
                <span class="info-value">#<?= (int)$user['id_utilisateur'] ?></span>
            </li>
            <li>
                <span class="info-label">📅 Date</span>
                <span class="info-value"><?= date('d/m/Y') ?></span>
            </li>
            <li>
                <span class="info-label">🕐 Heure</span>
                <span class="info-value"><?= date('H:i') ?></span>
            </li>
            <li>
                <span class="info-label">✅ Statut</span>
                <span class="info-value" style="color:#1e7e34;">● Connecté</span>
            </li>
        </ul>
    </div>

    <!-- Carte actions -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">⚡</div>
            <div>
                <div class="card-title">Actions rapides</div>
                <div class="card-subtitle">Gérer votre compte</div>
            </div>
        </div>
        <div class="card-actions">
            <a href="#" class="btn btn-primary">📖 Cahier de texte</a>
            <a href="#" class="btn btn-outline">✏️ Mon profil</a>
            <a href="<?= APP_URL ?>/?page=logout" class="btn btn-danger">🚪 Déconnexion</a>
        </div>
    </div>

</div>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
