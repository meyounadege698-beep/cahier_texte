<?php
$pageTitle = 'Gestion des classes — ' . APP_NAME;
$extraCss  = 'eleve.css';
include APP_ROOT . '/app/views/layouts/header.php';
$niveaux = ['6ème','5ème','4ème','3ème','Seconde','Première','Terminale','Autre'];
?>

<?= Session::getFlash() ?>

<div class="elv-header">
    <div>
        <h1>🏫 Gestion des classes</h1>
        <p>Créez et gérez les classes de l'établissement par année scolaire.</p>
    </div>
    <button class="btn btn-primary" onclick="toggleModal('modal-add-classe')">
        ＋ Nouvelle classe
    </button>
</div>

<!-- Filtre années -->
<div class="aff-year-filter" style="margin-bottom:20px">
    <?php foreach ($annees as $a): ?>
    <a href="<?= APP_URL ?>/app.php?page=gestion-classes&annee=<?= urlencode($a) ?>"
       class="year-btn <?= $a === $annee ? 'year-btn--active' : '' ?>">
        <?= htmlspecialchars($a) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Grille des classes -->
<?php if (empty($classes)): ?>
    <div class="empty-state">
        <div class="empty-icon">🏫</div>
        <h3>Aucune classe pour <?= htmlspecialchars($annee) ?></h3>
        <p>Créez la première classe de l'année scolaire.</p>
    </div>
<?php else: ?>
<div class="classes-grid">
    <?php foreach ($classes as $cl): ?>
    <div class="classe-card">
        <div class="classe-top">
            <div class="classe-niveau"><?= htmlspecialchars($cl['niveau']) ?></div>
            <?php if ($cl['filiere']): ?>
                <span class="classe-filiere"><?= htmlspecialchars($cl['filiere']) ?></span>
            <?php endif; ?>
        </div>
        <h3 class="classe-nom"><?= htmlspecialchars($cl['nom_classe'], ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="classe-meta">
            <span>👥 <?= (int)$cl['nb_eleves'] ?> élève<?= $cl['nb_eleves'] > 1 ? 's' : '' ?></span>
            <span>/ <?= (int)$cl['effectif_max'] ?> max</span>
            <span>📅 <?= htmlspecialchars($cl['annee_scolaire']) ?></span>
        </div>
        <div class="classe-progress">
            <div class="classe-progress-bar"
                 style="width:<?= $cl['effectif_max'] > 0 ? min(100, round($cl['nb_eleves']/$cl['effectif_max']*100)) : 0 ?>%">
            </div>
        </div>
        <div class="classe-actions">
            <a href="<?= APP_URL ?>/app.php?page=gestion-eleves&classe=<?= (int)$cl['id_classe'] ?>"
               class="btn btn-outline btn-sm">👤 Élèves</a>
            <button class="btn-icon btn-icon--edit"
                    onclick="openEditClasse(<?= htmlspecialchars(json_encode($cl), ENT_QUOTES) ?>)"
                    title="Modifier">✏️</button>
            <?php if ((int)$cl['nb_eleves'] === 0): ?>
            <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-classes"
                  onsubmit="return confirm('Supprimer cette classe ?')">
                <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_action"    value="delete_classe">
                <input type="hidden" name="id_classe"      value="<?= (int)$cl['id_classe'] ?>">
                <input type="hidden" name="annee_scolaire" value="<?= htmlspecialchars($annee) ?>">
                <button type="submit" class="btn-icon btn-icon--danger">🗑</button>
            </form>
            <?php else: ?>
                <span class="btn-icon btn-icon--locked" title="Contient des élèves">🔒</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal : Nouvelle classe -->
<div class="modal-overlay" id="modal-add-classe" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>🏫 Nouvelle classe</h3>
            <button class="modal-close" onclick="toggleModal('modal-add-classe')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-classes">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="add_classe">
            <div class="form-group"><label>Nom de la classe <span class="required">*</span></label>
                <input type="text" name="nom_classe" placeholder="Ex : Terminale C" required maxlength="50"></div>
            <div class="form-row">
                <div class="form-group"><label>Niveau <span class="required">*</span></label>
                    <select name="niveau" required>
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($niveaux as $n): ?>
                            <option value="<?= $n ?>"><?= $n ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Filière</label>
                    <input type="text" name="filiere" placeholder="Ex : Scientifique" maxlength="50"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Année scolaire <span class="required">*</span></label>
                    <select name="annee_scolaire" required>
                        <?php foreach ($annees as $a): ?>
                            <option value="<?= $a ?>" <?= $a === $annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Effectif max</label>
                    <input type="number" name="effectif_max" value="50" min="1" max="200"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-add-classe')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal : Modifier classe -->
<div class="modal-overlay" id="modal-edit-classe" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Modifier la classe</h3>
            <button class="modal-close" onclick="toggleModal('modal-edit-classe')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-classes">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="edit_classe">
            <input type="hidden" name="id_classe"   id="edit-classe-id">
            <div class="form-group"><label>Nom <span class="required">*</span></label>
                <input type="text" name="nom_classe" id="edit-classe-nom" required maxlength="50"></div>
            <div class="form-row">
                <div class="form-group"><label>Niveau <span class="required">*</span></label>
                    <select name="niveau" id="edit-classe-niveau" required>
                        <?php foreach ($niveaux as $n): ?>
                            <option value="<?= $n ?>"><?= $n ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Filière</label>
                    <input type="text" name="filiere" id="edit-classe-filiere" maxlength="50"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Année scolaire</label>
                    <input type="text" name="annee_scolaire" id="edit-classe-annee" readonly></div>
                <div class="form-group"><label>Effectif max</label>
                    <input type="number" name="effectif_max" id="edit-classe-effectif" min="1" max="200"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-edit-classe')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = m.style.display === 'none' ? 'flex' : 'none';
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.style.display = 'none'; });
});
function openEditClasse(c) {
    document.getElementById('edit-classe-id').value      = c.id_classe;
    document.getElementById('edit-classe-nom').value     = c.nom_classe;
    document.getElementById('edit-classe-niveau').value  = c.niveau;
    document.getElementById('edit-classe-filiere').value = c.filiere || '';
    document.getElementById('edit-classe-annee').value   = c.annee_scolaire;
    document.getElementById('edit-classe-effectif').value= c.effectif_max;
    toggleModal('modal-edit-classe');
}
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
