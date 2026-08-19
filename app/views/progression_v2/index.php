<?php
$pageTitle = 'Progression officielle — ' . APP_NAME;
$extraCss  = 'progression_v2.css';
include APP_ROOT . '/app/views/layouts/header.php';
$statutBadge = [
    'BROUILLON' => ['label'=>'✏️ Brouillon',  'cls'=>'badge--draft'],
    'PUBLIE'    => ['label'=>'✅ Publié',      'cls'=>'badge--pub'],
    'ARCHIVE'   => ['label'=>'📦 Archivé',    'cls'=>'badge--arch'],
];
?>
<?= Session::getFlash() ?>

<div class="pv2-header">
    <div>
        <h1>📋 Progression officielle</h1>
        <p>Saisissez les programmes conformes à la structure du Ministère de l'Éducation du Cameroun.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=prog-officielle-v2-creer" class="btn btn-primary">
        ＋ Nouveau programme
    </a>
</div>

<?php if (empty($programmes)): ?>
<div class="pv2-empty">
    <div class="pv2-empty-icon">📋</div>
    <h3>Aucun programme créé</h3>
    <p>Commencez par créer votre premier programme officiel.</p>
    <a href="<?= APP_URL ?>/app.php?page=prog-officielle-v2-creer" class="btn btn-primary">Créer un programme</a>
</div>
<?php else: ?>

<?php
// Grouper par département
$parDept = [];
foreach ($programmes as $p) {
    $parDept[$p['nom_departement']][] = $p;
}
?>

<?php foreach ($parDept as $dept => $progs): ?>
<div class="pv2-dept-section">
    <div class="pv2-dept-title">🏛️ <?= htmlspecialchars($dept, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="pv2-cards">
        <?php foreach ($progs as $p): ?>
        <?php $sb = $statutBadge[$p['statut']] ?? ['label'=>$p['statut'],'cls'=>'']; ?>
        <div class="pv2-card <?= $p['statut'] === 'PUBLIE' ? 'pv2-card--pub' : '' ?>">
            <div class="pv2-card-top">
                <span class="pv2-annee-badge"><?= htmlspecialchars($p['annee_scolaire']) ?></span>
                <span class="pv2-status-badge <?= $sb['cls'] ?>"><?= $sb['label'] ?></span>
            </div>
            <h3 class="pv2-card-titre"><?= htmlspecialchars($p['titre_programme'], ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="pv2-card-matiere">📚 <?= htmlspecialchars($p['nom_matiere']) ?> <?= $p['code_matiere'] ? '('.$p['code_matiere'].')':'' ?></div>
            <div class="pv2-card-stats">
                <span>📅 <?= (int)$p['nb_semaines'] ?> sem.</span>
                <span>📖 <?= (int)$p['nb_chapitres'] ?> chap.</span>
                <span>🎯 <?= (int)$p['nb_lecons'] ?> leç.</span>
            </div>
            <a href="<?= APP_URL ?>/app.php?page=prog-officielle-v2-wizard&id=<?= (int)$p['id_programme'] ?>"
               class="btn <?= $p['statut']==='PUBLIE' ? 'btn-outline' : 'btn-primary' ?> btn-sm pv2-card-btn">
                <?= $p['statut']==='PUBLIE' ? '👁 Voir / Attribuer' : '✏️ Modifier' ?> →
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
