<?php
$pageTitle = 'Dashboard — ' . APP_NAME;
include APP_ROOT . '/app/views/layouts/header.php';
$role = Session::get('role');
?>

<?= Session::getFlash() ?>

<!-- Bannière de bienvenue -->
<div class="welcome-banner">
    <div class="welcome-text">
        <h2>Bienvenue, <?= htmlspecialchars($user['prenom'] ?: $user['nom'], ENT_QUOTES, 'UTF-8') ?> 👋</h2>
        <p>
            <?php
            $roleDesc = [
                'enseignant'     => 'Espace enseignant — saisissez vos séances et suivez votre progression.',
                'censeur'        => 'Espace censeur — supervisez les programmes et gérez l\'établissement.',
                'administrateur' => 'Espace administrateur — gérez la plateforme.',
            ];
            echo $roleDesc[$role] ?? 'Bienvenue sur ' . APP_NAME;
            ?>
        </p>
    </div>
    <div class="welcome-avatar">
        <?php
        $roleIcons = ['enseignant'=>'👨‍🏫','censeur'=>'🔍','administrateur'=>'⚙️'];
        echo $roleIcons[$role] ?? '👤';
        ?>
    </div>
</div>

<?php if ($role === 'enseignant'): ?>
<!-- ============================================================
     DASHBOARD ENSEIGNANT
============================================================ -->
<div class="dash-role-title">Mon espace enseignant</div>

<div class="dashboard-grid dashboard-grid--modules">

    <a href="<?= APP_URL ?>/app.php?page=saisie-seance" class="module-card module-card--primary">
        <div class="module-icon">✏️</div>
        <div class="module-name">Saisir une séance</div>
        <div class="module-desc">Enregistrez le contenu de votre cours du jour</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=appel" class="module-card module-card--green">
        <div class="module-icon">📝</div>
        <div class="module-name">Appel & Présence</div>
        <div class="module-desc">Prenez l'appel et enregistrez les absences</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=devoirs" class="module-card module-card--orange">
        <div class="module-icon">📚</div>
        <div class="module-name">Mes devoirs</div>
        <div class="module-desc">Gérez les devoirs donnés à vos classes</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=bibliotheque-seances" class="module-card">
        <div class="module-icon">📂</div>
        <div class="module-name">Bibliothèque</div>
        <div class="module-desc">Retrouvez et réutilisez vos séances passées</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=historique-presences" class="module-card">
        <div class="module-icon">📊</div>
        <div class="module-name">Assiduité</div>
        <div class="module-desc">Consultez les statistiques de présence par classe</div>
    </a>

</div>

<!-- Infos profil -->
<div class="dashboard-grid" style="margin-top:24px">
    <div class="card">
        <div class="card-header">
            <div class="card-icon">👤</div>
            <div>
                <div class="card-title">Mon profil</div>
                <div class="card-subtitle">Informations du compte</div>
            </div>
        </div>
        <ul class="info-list">
            <li><span class="info-label">📛 Nom</span>
                <span class="info-value"><?= htmlspecialchars($user['nom'].' '.$user['prenom'], ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span class="info-label">✉️ Email</span>
                <span class="info-value"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span class="info-label">🔗 Dernière connexion</span>
                <span class="info-value"><?= $user['derniere_connexion'] ? date('d/m/Y H:i', strtotime($user['derniere_connexion'])) : 'Première connexion' ?></span></li>
        </ul>
        <div class="card-actions" style="margin-top:16px">
            <a href="<?= APP_URL ?>/app.php?page=logout" class="btn btn-danger">🚪 Déconnexion</a>
        </div>
    </div>
</div>

<?php elseif ($role === 'censeur'): ?>
<!-- ============================================================
     DASHBOARD CENSEUR
============================================================ -->
<div class="dash-role-title">Mon espace censeur</div>

<div class="dashboard-grid dashboard-grid--modules">

    <a href="<?= APP_URL ?>/app.php?page=supervision" class="module-card module-card--primary">
        <div class="module-icon">🔍</div>
        <div class="module-name">Supervision</div>
        <div class="module-desc">Alertes, taux de couverture, validations</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=progression-officielle" class="module-card module-card--purple">
        <div class="module-icon">📋</div>
        <div class="module-name">Progression officielle</div>
        <div class="module-desc">Saisir le programme national avant la rentrée</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=gestion-enseignants" class="module-card module-card--green">
        <div class="module-icon">👨‍🏫</div>
        <div class="module-name">Enseignants</div>
        <div class="module-desc">Inscrire et gérer les comptes enseignants</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=affecter-enseignant&id=0" class="module-card module-card--orange"
       onclick="event.preventDefault(); window.location.href='<?= APP_URL ?>/app.php?page=gestion-affectations'">
        <div class="module-icon">🏫</div>
        <div class="module-name">Affectations</div>
        <div class="module-desc">Attribuer classes, matières et salles</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=gestion-catalogue" class="module-card">
        <div class="module-icon">📚</div>
        <div class="module-name">Depts & Matières</div>
        <div class="module-desc">Gérer le catalogue pédagogique</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=gestion-classes" class="module-card">
        <div class="module-icon">🏫</div>
        <div class="module-name">Classes</div>
        <div class="module-desc">Créer et gérer les classes par année scolaire</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=gestion-eleves" class="module-card">
        <div class="module-icon">🎒</div>
        <div class="module-name">Élèves</div>
        <div class="module-desc">Inscrire et gérer les fiches élèves</div>
    </a>

    <a href="<?= APP_URL ?>/app.php?page=gestion-affectations" class="module-card">
        <div class="module-icon">🗝️</div>
        <div class="module-name">Salles</div>
        <div class="module-desc">Gérer les salles de classe</div>
    </a>

</div>

<!-- Infos profil -->
<div class="dashboard-grid" style="margin-top:24px">
    <div class="card">
        <div class="card-header">
            <div class="card-icon">👤</div>
            <div><div class="card-title">Mon profil</div></div>
        </div>
        <ul class="info-list">
            <li><span class="info-label">📛 Nom</span>
                <span class="info-value"><?= htmlspecialchars($user['nom'].' '.$user['prenom'], ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span class="info-label">✉️ Email</span>
                <span class="info-value"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span></li>
        </ul>
        <div class="card-actions" style="margin-top:16px">
            <a href="<?= APP_URL ?>/app.php?page=logout" class="btn btn-danger">🚪 Déconnexion</a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ============================================================
     DASHBOARD GÉNÉRIQUE (administrateur etc.)
============================================================ -->
<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-icon">👤</div>
            <div><div class="card-title">Mon profil</div></div>
        </div>
        <ul class="info-list">
            <li><span class="info-label">📛 Nom</span>
                <span class="info-value"><?= htmlspecialchars($user['nom'].' '.$user['prenom'], ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span class="info-label">✉️ Email</span>
                <span class="info-value"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span class="info-label">🏷️ Rôle</span>
                <span class="info-value"><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span class="info-label">📅 Inscrit le</span>
                <span class="info-value"><?= $user['date_inscription'] ? date('d/m/Y', strtotime($user['date_inscription'])) : '—' ?></span></li>
        </ul>
        <div class="card-actions" style="margin-top:16px">
            <a href="<?= APP_URL ?>/app.php?page=logout" class="btn btn-danger">🚪 Déconnexion</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
