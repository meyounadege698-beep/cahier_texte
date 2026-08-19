<?php
$pageTitle = 'Nouveau programme — ' . APP_NAME;
$extraCss  = 'progression.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>

<?= Session::getFlash() ?>

<div class="prog-header">
    <div>
        <h1>📋 Nouveau programme officiel</h1>
        <p>Définissez un programme par matière avant le début de l'année scolaire.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=progression-officielle" class="btn btn-outline">
        ← Retour
    </a>
</div>

<div class="form-card">

    <!-- Alerte contrainte temporelle -->
    <div class="alert-info">
        🗓️ <strong>Contrainte :</strong> La saisie du programme est uniquement possible
        <strong>avant le 1er septembre</strong> de l'année scolaire choisie.
    </div>

    <form method="POST" action="<?= APP_URL ?>/app.php?page=progression-officielle-create" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-section">
            <h2 class="form-section-title">1. Choix de la matière</h2>

            <!-- Département -->
            <div class="form-group">
                <label for="id_departement">Département <span class="required">*</span></label>
                <select id="id_departement" name="id_departement" required
                        onchange="chargerMatieres(this.value)">
                    <option value="">— Sélectionner un département —</option>
                    <?php foreach ($departements as $dept): ?>
                        <option value="<?= (int)$dept['id_departement'] ?>"
                            <?= ((int)($old['id_departement'] ?? 0) === (int)$dept['id_departement']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['nom_departement']) ?>
                            <?php if ($dept['code_departement']): ?>
                                (<?= htmlspecialchars($dept['code_departement']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Matière (chargée dynamiquement) -->
            <div class="form-group">
                <label for="id_matiere">Matière <span class="required">*</span></label>
                <select id="id_matiere" name="id_matiere" required>
                    <option value="">— Choisir d'abord un département —</option>
                    <?php foreach ($matieres as $mat): ?>
                        <option value="<?= (int)$mat['id_matiere'] ?>"
                            <?= ((int)($old['id_matiere'] ?? 0) === (int)$mat['id_matiere']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mat['nom_matiere']) ?>
                            (Coef. <?= $mat['coefficient'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section">
            <h2 class="form-section-title">2. Informations du programme</h2>

            <!-- Année scolaire -->
            <div class="form-group">
                <label for="annee_scolaire">Année scolaire <span class="required">*</span></label>
                <select id="annee_scolaire" name="annee_scolaire" required>
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($annees as $annee): ?>
                        <option value="<?= $annee ?>"
                            <?= (($old['annee_scolaire'] ?? '') === $annee) ? 'selected' : '' ?>
                            <?= !ProgressionOfficielleModel::anneeEnCoursOuFuture($annee) ? 'disabled' : '' ?>>
                            <?= $annee ?>
                            <?= !ProgressionOfficielleModel::anneeEnCoursOuFuture($annee) ? '(dépassée)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Titre -->
            <div class="form-group">
                <label for="titre_programme">Titre du programme <span class="required">*</span></label>
                <input type="text" id="titre_programme" name="titre_programme"
                       placeholder="Ex : Programme Mathématiques Terminale C 2026-2027"
                       value="<?= htmlspecialchars($old['titre_programme'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       required maxlength="200">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description <span class="optional">(optionnel)</span></label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Description générale du programme..."><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Volume horaire -->
            <div class="form-group form-group--small">
                <label for="volume_horaire_total">Volume horaire total (heures) <span class="optional">(optionnel)</span></label>
                <input type="number" id="volume_horaire_total" name="volume_horaire_total"
                       min="1" max="500" placeholder="Ex : 150">
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= APP_URL ?>/app.php?page=progression-officielle" class="btn btn-outline">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer le programme →</button>
        </div>

    </form>
</div>

<script>
// Chargement AJAX des matières selon le département sélectionné
function chargerMatieres(idDept) {
    const select = document.getElementById('id_matiere');
    select.innerHTML = '<option value="">Chargement...</option>';
    select.disabled = true;

    if (!idDept) {
        select.innerHTML = '<option value="">— Choisir d\'abord un département —</option>';
        select.disabled = false;
        return;
    }

    fetch('<?= APP_URL ?>/app.php?page=api-matieres&dept=' + idDept)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">— Sélectionner une matière —</option>';
            data.forEach(m => {
                select.innerHTML += `<option value="${m.id_matiere}">${m.nom_matiere} (Coef. ${m.coefficient})</option>`;
            });
            select.disabled = false;
        })
        .catch(() => {
            select.innerHTML = '<option value="">Erreur de chargement</option>';
            select.disabled = false;
        });
}
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
