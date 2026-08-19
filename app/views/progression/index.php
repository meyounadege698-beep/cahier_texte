<?php
$pageTitle = 'Progression officielle — ' . APP_NAME;
$extraCss  = 'progression.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>

<?= Session::getFlash() ?>

<div class="prog-header">
    <div>
        <h1>📋 Progression officielle</h1>
        <p>Gérez les programmes par département et matière avant le début de l'année scolaire.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=progression-officielle-create" class="btn btn-primary">
        ＋ Nouveau programme
    </a>
    <a href="<?= APP_URL ?>/app.php?page=gestion-catalogue" class="btn btn-outline">
        🏫 Depts & Matières
    </a>
</div>

<?php if (empty($programmes)): ?>
    <div class="empty-state">
        <div class="empty-icon">📂</div>
        <h3>Aucun programme créé</h3>
        <p>Créez votre premier programme officiel pour commencer à saisir les points de cours.</p>
        <a href="<?= APP_URL ?>/app.php?page=progression-officielle-create" class="btn btn-primary">
            Créer un programme
        </a>
    </div>
<?php else: ?>

    <!-- Filtrage visuel par département -->
    <?php
    $parDept = [];
    foreach ($programmes as $p) {
        $parDept[$p['nom_departement']][] = $p;
    }
    ?>

    <?php foreach ($parDept as $dept => $progs): ?>
    <div class="dept-section">
        <h2 class="dept-title">🏫 <?= htmlspecialchars($dept, ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="prog-grid">
            <?php foreach ($progs as $prog): ?>
            <div class="prog-card <?= $prog['statut'] === 'PUBLIE' ? 'prog-card--published' : '' ?>">
                <div class="prog-card-top">
                    <span class="prog-annee"><?= htmlspecialchars($prog['annee_scolaire']) ?></span>
                    <span class="prog-badge prog-badge--<?= strtolower($prog['statut']) ?>">
                        <?php
                        $labels = ['BROUILLON' => '✏️ Brouillon', 'PUBLIE' => '✅ Publié', 'ARCHIVE' => '📦 Archivé'];
                        echo $labels[$prog['statut']] ?? $prog['statut'];
                        ?>
                    </span>
                </div>
                <h3 class="prog-title"><?= htmlspecialchars($prog['titre_programme'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="prog-matiere">📚 <?= htmlspecialchars($prog['nom_matiere']) ?> — <?= htmlspecialchars($prog['code_matiere']) ?></p>
                <div class="prog-meta">
                    <span>📑 <?= (int)$prog['nb_chapitres'] ?> point<?= $prog['nb_chapitres'] > 1 ? 's' : '' ?></span>
                    <?php if ($prog['volume_horaire_total']): ?>
                        <span>⏱ <?= (int)$prog['volume_horaire_total'] ?>h</span>
                    <?php endif; ?>
                </div>
                <a href="<?= APP_URL ?>/app.php?page=progression-officielle-detail&id=<?= (int)$prog['id_programme'] ?>"
                   class="btn btn-outline btn-sm">
                    Voir / Modifier →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
