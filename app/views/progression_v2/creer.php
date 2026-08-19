<?php
$pageTitle = 'Nouveau programme — ' . APP_NAME;
$extraCss  = 'progression_v2.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>
<?= Session::getFlash() ?>

<div class="pv2-header">
    <div>
        <h1>📋 Nouveau programme officiel</h1>
        <p>Définissez l'en-tête du programme avant d'entrer dans le wizard de saisie hebdomadaire.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=prog-officielle-v2" class="btn btn-outline">← Retour</a>
</div>

<div class="pv2-form-card">
    <div class="pv2-alert-info">
        🗓️ <strong>Rappel :</strong> La saisie du programme doit être effectuée <strong>avant le début de l'année scolaire</strong> choisie.
    </div>

    <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-creer" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="pv2-form-section">
            <div class="pv2-section-label">1. Matière</div>
            <div class="pv2-form-row">
                <div class="pv2-form-group">
                    <label>Département <span class="req">*</span></label>
                    <select id="selDept" name="id_departement" required onchange="loadMatieres(this.value)">
                        <option value="">— Département —</option>
                        <?php foreach ($departements as $d): ?>
                            <option value="<?= (int)$d['id_departement'] ?>"
                                <?= ((int)($old['idDept']??0)===(int)$d['id_departement'])?'selected':'' ?>>
                                <?= htmlspecialchars($d['nom_departement']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pv2-form-group">
                    <label>Matière <span class="req">*</span></label>
                    <select id="selMatiere" name="id_matiere" required <?= empty($matieres)?'disabled':'' ?>>
                        <option value="">— Choisir d'abord un département —</option>
                        <?php foreach ($matieres as $m): ?>
                            <option value="<?= (int)$m['id_matiere'] ?>"
                                <?= ((int)($old['idMatiere']??0)===(int)$m['id_matiere'])?'selected':'' ?>>
                                <?= htmlspecialchars($m['nom_matiere']) ?> (Coef. <?= $m['coefficient'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="pv2-form-section">
            <div class="pv2-section-label">2. Programme</div>
            <div class="pv2-form-group">
                <label>Année scolaire <span class="req">*</span></label>
                <select name="annee_scolaire" required>
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($annees as $a): ?>
                        <option value="<?= $a ?>" <?= ($old['annee']??'')===$a?'selected':'' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pv2-form-group">
                <label>Titre du programme <span class="req">*</span></label>
                <input type="text" name="titre_programme" required maxlength="200"
                       placeholder="Ex : Programme Mathématiques Terminale C 2026-2027"
                       value="<?= htmlspecialchars($old['titre']??'', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="pv2-form-group">
                <label>Description <span class="opt">(optionnel)</span></label>
                <textarea name="description" rows="3" placeholder="Description générale du programme..."><?= htmlspecialchars($old['description']??'', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="pv2-form-group" style="max-width:180px">
                <label>Volume horaire total (h) <span class="opt">(optionnel)</span></label>
                <input type="number" name="volume_horaire_total" min="1" max="500" placeholder="Ex : 150">
            </div>
        </div>

        <div class="pv2-form-actions">
            <a href="<?= APP_URL ?>/app.php?page=prog-officielle-v2" class="btn btn-outline">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer et saisir les leçons →</button>
        </div>
    </form>
</div>

<script>
const APP_URL = '<?= APP_URL ?>';
function loadMatieres(idDept) {
    const sel = document.getElementById('selMatiere');
    sel.innerHTML = '<option value="">Chargement...</option>';
    sel.disabled = true;
    if (!idDept) { sel.innerHTML = '<option value="">— Choisir d\'abord un département —</option>'; return; }
    fetch(`${APP_URL}/app.php?page=api-matieres&dept=${idDept}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">— Sélectionner une matière —</option>';
            data.forEach(m => {
                sel.innerHTML += `<option value="${m.id_matiere}">${m.nom_matiere} (Coef. ${m.coefficient})</option>`;
            });
            sel.disabled = false;
        });
}
</script>
<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
