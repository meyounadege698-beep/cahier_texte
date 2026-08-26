<?php
$pageTitle = 'Rapports — ' . APP_NAME;
$extraCss  = 'rapport.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>
<?= Session::getFlash() ?>

<div class="rpt-header">
    <div>
        <h1>📄 Génération de rapports</h1>
        <p>Sélectionnez le type de rapport et les filtres, puis exportez en PDF via votre navigateur.</p>
    </div>
</div>

<div class="rpt-layout">

    <!-- ── TYPE PROGRESSION ── -->
    <div class="rpt-card">
        <div class="rpt-card-icon">📊</div>
        <h3>Rapport de progression</h3>
        <p>Avancement du programme par classe, matière et enseignant.</p>
        <form method="GET" action="<?= APP_URL ?>/app.php" target="_blank">
            <input type="hidden" name="page" value="rapport-print">
            <input type="hidden" name="type" value="progression">
            <div class="rpt-form-group">
                <label>Année scolaire</label>
                <select name="annee" required>
                    <?php foreach ($annees as $a): ?>
                        <option value="<?= $a ?>" <?= $a===$annee?'selected':'' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rpt-form-group">
                <label>Classe</label>
                <select name="classe" required>
                    <option value="">— Toutes —</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?= (int)$cl['id_classe'] ?>">
                            <?= htmlspecialchars($cl['nom_classe'].' ('.$cl['niveau'].')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rpt-form-group">
                <label>Matière</label>
                <select name="matiere">
                    <option value="">— Toutes —</option>
                    <?php foreach ($matieres as $m): ?>
                        <option value="<?= (int)$m['id_matiere'] ?>"><?= htmlspecialchars($m['nom_matiere']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rpt-form-group">
                <label>Enseignant</label>
                <select name="enseignant">
                    <option value="">— Tous —</option>
                    <?php foreach ($enseignants as $e): ?>
                        <option value="<?= (int)$e['id_utilisateur'] ?>"><?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary rpt-btn">🖨️ Générer / Imprimer</button>
        </form>
    </div>

    <!-- ── TYPE PRÉSENCE ── -->
    <div class="rpt-card">
        <div class="rpt-card-icon">📝</div>
        <h3>Rapport de présence</h3>
        <p>Statistiques d'assiduité des élèves par classe et période.</p>
        <form method="GET" action="<?= APP_URL ?>/app.php" target="_blank">
            <input type="hidden" name="page" value="rapport-print">
            <input type="hidden" name="type" value="presence">
            <div class="rpt-form-group">
                <label>Classe <span class="req">*</span></label>
                <select name="classe" required>
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?= (int)$cl['id_classe'] ?>">
                            <?= htmlspecialchars($cl['nom_classe'].' ('.$cl['niveau'].')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rpt-form-row">
                <div class="rpt-form-group">
                    <label>Du</label>
                    <input type="date" name="date_debut" value="<?= date('Y-09-01') ?>">
                </div>
                <div class="rpt-form-group">
                    <label>Au</label>
                    <input type="date" name="date_fin" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary rpt-btn">🖨️ Générer / Imprimer</button>
        </form>
    </div>

    <!-- ── TYPE ANNUEL ── -->
    <div class="rpt-card">
        <div class="rpt-card-icon">📅</div>
        <h3>Rapport annuel de synthèse</h3>
        <p>Vue globale de l'avancement de tous les programmes par enseignant.</p>
        <form method="GET" action="<?= APP_URL ?>/app.php" target="_blank">
            <input type="hidden" name="page" value="rapport-print">
            <input type="hidden" name="type" value="annuel">
            <div class="rpt-form-group">
                <label>Année scolaire <span class="req">*</span></label>
                <select name="annee" required>
                    <?php foreach ($annees as $a): ?>
                        <option value="<?= $a ?>" <?= $a===$annee?'selected':'' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary rpt-btn">🖨️ Générer / Imprimer</button>
        </form>
    </div>

</div>

<div class="rpt-hint">
    💡 <strong>Comment exporter en PDF ?</strong>
    Après avoir cliqué sur "Générer", une nouvelle page s'ouvre.
    Utilisez <strong>Ctrl+P</strong> (ou Cmd+P sur Mac) → sélectionnez <strong>"Enregistrer en PDF"</strong> comme imprimante.
</div>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
