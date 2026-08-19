<?php
$pageTitle = 'Départements & Matières — ' . APP_NAME;
$extraCss  = 'catalogue.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>

<?= Session::getFlash() ?>

<div class="catalogue-header">
    <div>
        <h1>🏫 Départements & Matières</h1>
        <p>Configurez le catalogue pédagogique de l'établissement.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=progression-officielle" class="btn btn-outline">
        ← Progression officielle
    </a>
</div>

<div class="catalogue-layout">

    <!-- ============================================================
         COLONNE GAUCHE : Départements
    ============================================================ -->
    <div class="catalogue-section">
        <div class="cs-header">
            <h2>🏛️ Départements <span class="count-badge"><?= count($depts) ?></span></h2>
            <button class="btn btn-primary btn-sm" onclick="toggleModal('modal-add-dept')">
                ＋ Ajouter
            </button>
        </div>

        <?php if (empty($depts)): ?>
            <div class="cs-empty">Aucun département. Créez-en un pour commencer.</div>
        <?php else: ?>
        <div class="cs-list">
            <?php foreach ($depts as $d): ?>
            <div class="cs-item">
                <div class="cs-item-main">
                    <div class="cs-item-name">
                        <?= htmlspecialchars($d['nom_departement'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($d['code_departement']): ?>
                            <span class="cs-code"><?= htmlspecialchars($d['code_departement']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($d['description']): ?>
                    <div class="cs-item-desc">
                        <?= htmlspecialchars($d['description'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>
                    <div class="cs-item-meta">
                        📚 <?= (int)$d['nb_matieres'] ?> matière<?= $d['nb_matieres'] > 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="cs-item-actions">
                    <button class="btn-icon btn-icon--edit"
                            onclick="openEditDept(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)"
                            title="Modifier">✏️</button>
                    <?php if ((int)$d['nb_matieres'] === 0): ?>
                    <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-catalogue"
                          onsubmit="return confirm('Supprimer ce département ?')">
                        <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="form_action"    value="delete_dept">
                        <input type="hidden" name="id_departement" value="<?= (int)$d['id_departement'] ?>">
                        <button type="submit" class="btn-icon btn-icon--danger" title="Supprimer">🗑</button>
                    </form>
                    <?php else: ?>
                        <span class="btn-icon btn-icon--locked" title="Contient des matières">🔒</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         COLONNE DROITE : Matières
    ============================================================ -->
    <div class="catalogue-section">
        <div class="cs-header">
            <h2>📚 Matières <span class="count-badge"><?= count($matieres) ?></span></h2>
            <button class="btn btn-primary btn-sm"
                    onclick="toggleModal('modal-add-mat')"
                    <?= empty($depts) ? 'disabled title="Créez d\'abord un département"' : '' ?>>
                ＋ Ajouter
            </button>
        </div>

        <?php if (empty($matieres)): ?>
            <div class="cs-empty">Aucune matière. Ajoutez-en une depuis le formulaire ci-dessus.</div>
        <?php else: ?>

        <!-- Regroupement par département -->
        <?php
        $parDept = [];
        foreach ($matieres as $m) {
            $parDept[$m['nom_departement']][] = $m;
        }
        ?>
        <?php foreach ($parDept as $deptNom => $mats): ?>
        <div class="mat-group">
            <div class="mat-group-title">
                🏛️ <?= htmlspecialchars($deptNom, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="cs-list cs-list--mats">
                <?php foreach ($mats as $m): ?>
                <div class="cs-item">
                    <div class="cs-item-main">
                        <div class="cs-item-name">
                            <?= htmlspecialchars($m['nom_matiere'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($m['code_matiere']): ?>
                                <span class="cs-code"><?= htmlspecialchars($m['code_matiere']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="cs-item-meta">
                            <span>Coef. <?= (float)$m['coefficient'] ?></span>
                            <?php if ($m['volume_horaire_annuel']): ?>
                                <span>⏱ <?= (int)$m['volume_horaire_annuel'] ?>h/an</span>
                            <?php endif; ?>
                            <span>📋 <?= (int)$m['nb_programmes'] ?> programme<?= $m['nb_programmes'] > 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                    <div class="cs-item-actions">
                        <button class="btn-icon btn-icon--edit"
                                onclick="openEditMat(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)"
                                title="Modifier">✏️</button>
                        <?php if ((int)$m['nb_programmes'] === 0): ?>
                        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-catalogue"
                              onsubmit="return confirm('Supprimer cette matière ?')">
                            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="form_action" value="delete_mat">
                            <input type="hidden" name="id_matiere"  value="<?= (int)$m['id_matiere'] ?>">
                            <button type="submit" class="btn-icon btn-icon--danger" title="Supprimer">🗑</button>
                        </form>
                        <?php else: ?>
                            <span class="btn-icon btn-icon--locked" title="Liée à des programmes">🔒</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- ============================================================
     MODALS
============================================================ -->

<!-- ── Modal : Ajouter département ── -->
<div class="modal-overlay" id="modal-add-dept" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>🏛️ Nouveau département</h3>
            <button class="modal-close" onclick="toggleModal('modal-add-dept')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-catalogue">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="add_dept">
            <div class="form-group">
                <label>Nom du département <span class="required">*</span></label>
                <input type="text" name="nom_departement" placeholder="Ex : Mathématiques" required maxlength="100">
            </div>
            <div class="form-group">
                <label>Code <span class="optional">(optionnel, unique)</span></label>
                <input type="text" name="code_departement" placeholder="Ex : MATH" maxlength="20">
            </div>
            <div class="form-group">
                <label>Description <span class="optional">(optionnel)</span></label>
                <textarea name="description" rows="2" placeholder="Description du département..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-add-dept')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal : Modifier département ── -->
<div class="modal-overlay" id="modal-edit-dept" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Modifier le département</h3>
            <button class="modal-close" onclick="toggleModal('modal-edit-dept')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-catalogue">
            <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action"    value="edit_dept">
            <input type="hidden" name="id_departement" id="edit-dept-id">
            <div class="form-group">
                <label>Nom <span class="required">*</span></label>
                <input type="text" name="nom_departement" id="edit-dept-nom" required maxlength="100">
            </div>
            <div class="form-group">
                <label>Code <span class="optional">(optionnel)</span></label>
                <input type="text" name="code_departement" id="edit-dept-code" maxlength="20">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit-dept-desc" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-edit-dept')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal : Ajouter matière ── -->
<div class="modal-overlay" id="modal-add-mat" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>📚 Nouvelle matière</h3>
            <button class="modal-close" onclick="toggleModal('modal-add-mat')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-catalogue">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="add_mat">
            <div class="form-group">
                <label>Département <span class="required">*</span></label>
                <select name="id_departement" required>
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?= (int)$d['id_departement'] ?>">
                            <?= htmlspecialchars($d['nom_departement']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nom de la matière <span class="required">*</span></label>
                <input type="text" name="nom_matiere" placeholder="Ex : Mathématiques" required maxlength="100">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Code <span class="optional">(unique)</span></label>
                    <input type="text" name="code_matiere" placeholder="Ex : MATH" maxlength="20">
                </div>
                <div class="form-group">
                    <label>Coefficient <span class="required">*</span></label>
                    <input type="number" name="coefficient" value="1" min="0.5" max="10" step="0.5" required>
                </div>
            </div>
            <div class="form-group">
                <label>Volume horaire annuel (h) <span class="optional">(optionnel)</span></label>
                <input type="number" name="volume_horaire_annuel" min="1" max="500" placeholder="Ex : 150">
            </div>
            <div class="form-group">
                <label>Description <span class="optional">(optionnel)</span></label>
                <textarea name="description" rows="2" placeholder="Description de la matière..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-add-mat')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal : Modifier matière ── -->
<div class="modal-overlay" id="modal-edit-mat" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Modifier la matière</h3>
            <button class="modal-close" onclick="toggleModal('modal-edit-mat')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-catalogue">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="edit_mat">
            <input type="hidden" name="id_matiere"  id="edit-mat-id">
            <div class="form-group">
                <label>Département <span class="required">*</span></label>
                <select name="id_departement" id="edit-mat-dept" required>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?= (int)$d['id_departement'] ?>">
                            <?= htmlspecialchars($d['nom_departement']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nom <span class="required">*</span></label>
                <input type="text" name="nom_matiere" id="edit-mat-nom" required maxlength="100">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Code</label>
                    <input type="text" name="code_matiere" id="edit-mat-code" maxlength="20">
                </div>
                <div class="form-group">
                    <label>Coefficient <span class="required">*</span></label>
                    <input type="number" name="coefficient" id="edit-mat-coef" min="0.5" max="10" step="0.5" required>
                </div>
            </div>
            <div class="form-group">
                <label>Volume horaire annuel (h)</label>
                <input type="number" name="volume_horaire_annuel" id="edit-mat-vol" min="1" max="500">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit-mat-desc" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-edit-mat')">Annuler</button>
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
// Fermer en cliquant l'overlay
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.style.display = 'none'; });
});

function openEditDept(d) {
    document.getElementById('edit-dept-id').value   = d.id_departement;
    document.getElementById('edit-dept-nom').value  = d.nom_departement;
    document.getElementById('edit-dept-code').value = d.code_departement || '';
    document.getElementById('edit-dept-desc').value = d.description || '';
    toggleModal('modal-edit-dept');
}

function openEditMat(m) {
    document.getElementById('edit-mat-id').value   = m.id_matiere;
    document.getElementById('edit-mat-dept').value = m.id_departement;
    document.getElementById('edit-mat-nom').value  = m.nom_matiere;
    document.getElementById('edit-mat-code').value = m.code_matiere || '';
    document.getElementById('edit-mat-coef').value = m.coefficient;
    document.getElementById('edit-mat-vol').value  = m.volume_horaire_annuel || '';
    document.getElementById('edit-mat-desc').value = m.description || '';
    toggleModal('modal-edit-mat');
}
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
