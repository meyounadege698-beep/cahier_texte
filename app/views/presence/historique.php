<?php
$pageTitle = 'Historique présences — ' . APP_NAME;
$extraCss  = 'presence.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>

<?= Session::getFlash() ?>

<div class="pres-header">
    <div>
        <h1>📊 Assiduité par classe</h1>
        <p>Consultez les statistiques de présence élève par élève.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=appel" class="btn btn-primary">📝 Faire l'appel</a>
</div>

<!-- Filtres -->
<form method="GET" action="<?= APP_URL ?>/app.php" class="pres-filter-bar">
    <input type="hidden" name="page" value="historique-presences">
    <div class="form-group">
        <label>Classe</label>
        <select name="classe" onchange="this.form.submit()">
            <option value="">— Toutes les classes —</option>
            <?php foreach ($classes as $cl): ?>
                <option value="<?= (int)$cl['id_classe'] ?>"
                    <?= (int)$cl['id_classe'] === $idClasse ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cl['nom_classe']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($idClasse > 0 && !empty($statsEleves)): ?>
<div class="histo-table-wrap">
    <table class="histo-table">
        <thead>
            <tr>
                <th>Élève</th>
                <th>Matricule</th>
                <th class="td-center">Séances</th>
                <th class="td-center">✅ Présent</th>
                <th class="td-center">❌ Absent</th>
                <th class="td-center">⏰ Retard</th>
                <th class="td-center">📋 Excusé</th>
                <th class="td-center">Taux</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($statsEleves as $e): ?>
        <tr>
            <td>
                <div class="histo-name">
                    <div class="eleve-avatar eleve-avatar--sm">
                        <?= strtoupper(mb_substr($e['prenom'],0,1).mb_substr($e['nom'],0,1)) ?>
                    </div>
                    <?= htmlspecialchars($e['nom'].' '.$e['prenom'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </td>
            <td class="td-muted"><?= htmlspecialchars($e['matricule']) ?></td>
            <td class="td-center"><?= (int)$e['total_seances'] ?></td>
            <td class="td-center td-green"><?= (int)$e['nb_present'] ?></td>
            <td class="td-center td-red"><?= (int)$e['nb_absent'] ?></td>
            <td class="td-center td-orange"><?= (int)$e['nb_retard'] ?></td>
            <td class="td-center td-blue"><?= (int)$e['nb_excuse'] ?></td>
            <td class="td-center">
                <?php $taux = (float)($e['taux_presence'] ?? 0); ?>
                <div class="taux-bar-wrap">
                    <div class="taux-bar" style="width:<?= min(100,$taux) ?>%; background:<?= $taux >= 75 ? '#10b981' : ($taux >= 50 ? '#f59e0b' : '#ef4444') ?>"></div>
                    <span class="taux-val"><?= $taux ?>%</span>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif ($idClasse > 0): ?>
    <div class="pres-empty-hint">Aucune donnée de présence pour cette classe.</div>
<?php else: ?>
    <div class="pres-empty-hint">Sélectionnez une classe pour afficher les statistiques.</div>
<?php endif; ?>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
