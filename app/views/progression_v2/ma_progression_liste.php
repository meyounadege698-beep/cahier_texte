<?php
$pageTitle = 'Ma progression — ' . APP_NAME;
$extraCss  = 'progression_v2.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>
<?= Session::getFlash() ?>

<div class="pv2-header">
    <div>
        <h1>📋 Ma progression officielle</h1>
        <p>Consultez les programmes qui vous ont été attribués par le censeur.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=saisie-seance" class="btn btn-primary">✏️ Saisir une séance</a>
</div>

<?php if (empty($progressions)): ?>
<div class="pv2-empty">
    <div class="pv2-empty-icon">📋</div>
    <h3>Aucune progression attribuée</h3>
    <p>Le censeur n'a pas encore attribué de programme officiel pour vos matières. Revenez après la rentrée.</p>
</div>
<?php else: ?>
<div class="pv2-cards">
    <?php foreach ($progressions as $p): ?>
    <?php
    $total     = max(1, (int)$p['nb_lecons']);
    $terminees = (int)$p['nb_terminees'];
    $pct       = round($terminees / $total * 100);
    $color     = $pct >= 75 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#4f46e5');
    ?>
    <div class="pv2-card">
        <div class="pv2-card-top">
            <span class="pv2-annee-badge"><?= htmlspecialchars($p['annee_scolaire'] ?? '') ?></span>
        </div>
        <h3 class="pv2-card-titre">
            <?= htmlspecialchars($p['nom_matiere']) ?>
            <?= $p['code_matiere'] ? '<small style="font-weight:400;color:#64748b">('.$p['code_matiere'].')</small>' : '' ?>
        </h3>
        <div class="pv2-card-matiere">🏫 <?= htmlspecialchars($p['nom_classe']) ?> (<?= htmlspecialchars($p['niveau']) ?>)</div>
        <?php if (!empty($p['titre_programme'])): ?>
            <div style="font-size:12px;color:#64748b;font-style:italic">📋 <?= htmlspecialchars(mb_substr($p['titre_programme'],0,60)) ?></div>
        <?php endif; ?>
        <div class="pv2-card-stats">
            <span>🎯 <?= $terminees ?>/<?= $total ?> leçons</span>
            <span>⏳ <?= (int)$p['nb_en_cours'] ?> en cours</span>
        </div>
        <!-- Barre de progression -->
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:4px;transition:width .6s"></div>
        </div>
        <div style="font-size:12px;color:<?= $color ?>;font-weight:700"><?= $pct ?>% couvert</div>
        <a href="<?= APP_URL ?>/app.php?page=ma-progression-detail&classe=<?= (int)$p['id_classe'] ?>&matiere=<?= (int)$p['id_matiere'] ?>"
           class="btn btn-primary btn-sm pv2-card-btn">Voir le détail →</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
