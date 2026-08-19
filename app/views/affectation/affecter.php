<?php
$pageTitle = 'Affecter ' . htmlspecialchars($enseignant['prenom'].' '.$enseignant['nom']) . ' — ' . APP_NAME;
$extraCss  = 'affectation.css';
include APP_ROOT . '/app/views/layouts/header.php';

// Indexer les matières par département pour le JS
$matieresByDept = [];
foreach ($matieres as $m) {
    $matieresByDept[$m['dept_id']][] = $m;
}
?>

<?= Session::getFlash() ?>

<!-- En-tête -->
<div class="aff-page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= APP_URL ?>/app.php?page=gestion-affectations#affectations">Affectations</a>
            <span>›</span>
            <span><?= htmlspecialchars($enseignant['prenom'].' '.$enseignant['nom'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <h1>👨‍🏫 Affecter
            <span class="ens-name-highlight">
                <?= htmlspecialchars($enseignant['prenom'].' '.$enseignant['nom'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        </h1>
        <p>Attribuez une ou plusieurs combinaisons département / matière / classe / salle.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=gestion-affectations" class="btn btn-outline">← Retour</a>
</div>

<!-- Sélecteur d'année -->
<div class="aff-year-filter" style="margin-bottom:24px">
    <?php foreach ($annees as $a): ?>
    <a href="<?= APP_URL ?>/app.php?page=affecter-enseignant&id=<?= (int)$enseignant['id_utilisateur'] ?>&annee=<?= urlencode($a) ?>"
       class="year-btn <?= $a === $annee ? 'year-btn--active' : '' ?>">
        <?= htmlspecialchars($a) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="affecter-layout">

    <!-- ===== FORMULAIRE D'AFFECTATION MULTIPLE ===== -->
    <div class="affecter-form-card">
        <h2>➕ Nouvelles affectations</h2>
        <p class="form-hint">
            Chaque ligne = une combinaison classe + matière + salle.
            Vous pouvez ajouter autant de lignes que nécessaire.
        </p>

        <form id="formAffecter" method="POST"
              action="<?= APP_URL ?>/app.php?page=gestion-affectations"
              novalidate>
            <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action"    value="affecter_multiple">
            <input type="hidden" name="id_utilisateur" value="<?= (int)$enseignant['id_utilisateur'] ?>">

            <!-- Année scolaire -->
            <div class="form-group" style="max-width:200px; margin-bottom:20px">
                <label>Année scolaire <span class="required">*</span></label>
                <select name="annee_scolaire" required>
                    <?php foreach ($annees as $a): ?>
                        <option value="<?= $a ?>" <?= $a === $annee ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tableau des lignes -->
            <div class="lignes-table-wrap">
                <table class="lignes-table" id="lignesTable">
                    <thead>
                        <tr>
                            <th>Département</th>
                            <th>Matière <span class="required">*</span></th>
                            <th>Classe <span class="required">*</span></th>
                            <th>Salle(s)</th>
                            <th>H/sem.</th>
                            <th>Principal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="lignesBody">
                        <!-- Les lignes sont ajoutées dynamiquement -->
                    </tbody>
                </table>
            </div>

            <div class="lignes-actions">
                <button type="button" class="btn-add-ligne" onclick="ajouterLigne()">
                    ➕ Ajouter une ligne
                </button>
            </div>

            <div class="form-submit-row" style="margin-top:24px">
                <a href="<?= APP_URL ?>/app.php?page=gestion-affectations" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit" disabled>
                    💾 Enregistrer les affectations
                </button>
            </div>
        </form>
    </div>

    <!-- ===== AFFECTATIONS EXISTANTES ===== -->
    <div class="affecter-existing">
        <h2>📋 Affectations existantes
            <span class="count-badge"><?= count($affectations) ?></span>
            <small><?= htmlspecialchars($annee) ?></small>
        </h2>

        <?php if (empty($affectations)): ?>
            <p class="empty-hint">Aucune affectation pour cette année.</p>
        <?php else: ?>
        <div class="existing-list">
            <?php
            // Grouper par département
            $parDept = [];
            foreach ($affectations as $a) {
                $parDept[$a['nom_departement']][] = $a;
            }
            foreach ($parDept as $dept => $items): ?>
            <div class="existing-dept">
                <div class="existing-dept-title">
                    🏛️ <?= htmlspecialchars($dept, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php foreach ($items as $a): ?>
                <div class="existing-item">
                    <div class="existing-item-body">
                        <span class="tag tag--matiere">
                            <?= htmlspecialchars($a['nom_matiere']) ?>
                            <?php if ($a['code_matiere']): ?>
                                <small>(<?= htmlspecialchars($a['code_matiere']) ?>)</small>
                            <?php endif; ?>
                        </span>
                        <span class="tag tag--classe">
                            🏫 <?= htmlspecialchars($a['nom_classe']) ?>
                        </span>
                        <?php if ($a['nom_salle']): ?>
                            <span class="tag tag--salle">🚪 <?= htmlspecialchars($a['nom_salle']) ?></span>
                        <?php endif; ?>
                        <?php if ($a['volume_horaire_hebdo']): ?>
                            <span class="tag tag--meta">⏱ <?= (int)$a['volume_horaire_hebdo'] ?>h</span>
                        <?php endif; ?>
                        <?php if ($a['est_principal']): ?>
                            <span class="tag tag--principal">⭐</span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-affectations"
                          onsubmit="return confirm('Supprimer ?')">
                        <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="form_action"    value="delete_aff">
                        <input type="hidden" name="id_affectation" value="<?= (int)$a['id_affectation'] ?>">
                        <input type="hidden" name="annee_scolaire" value="<?= htmlspecialchars($annee) ?>">
                        <button type="submit" class="btn-icon btn-icon--danger" title="Supprimer">🗑</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Données JSON pour le JS -->
<script>
const DEPARTEMENTS = <?= json_encode(array_values($departements)) ?>;
const MATIERES_BY_DEPT = <?= json_encode($matieresByDept) ?>;
const CLASSES = <?= json_encode(array_values($classes)) ?>;
const SALLES  = <?= json_encode(array_values($salles)) ?>;

let ligneCount = 0;

function ajouterLigne() {
    ligneCount++;
    const i = ligneCount;
    const tbody = document.getElementById('lignesBody');

    const tr = document.createElement('tr');
    tr.id = 'ligne-' + i;
    tr.innerHTML = `
        <td>
            <select class="sel-dept" onchange="onDeptChange(this, ${i})" required>
                <option value="">— Dept. —</option>
                ${DEPARTEMENTS.map(d =>
                    `<option value="${d.id_departement}">${escH(d.nom_departement)}</option>`
                ).join('')}
            </select>
        </td>
        <td>
            <select name="id_matiere[]" id="sel-mat-${i}" disabled required>
                <option value="">— Matière —</option>
            </select>
        </td>
        <td>
            <select name="id_classe[]" required>
                <option value="">— Classe —</option>
                ${CLASSES.map(c =>
                    `<option value="${c.id_classe}">${escH(c.nom_classe)} (${escH(c.niveau)})</option>`
                ).join('')}
            </select>
        </td>
        <td>
            <select name="id_salle[]">
                <option value="">— Aucune —</option>
                ${SALLES.map(s =>
                    `<option value="${s.id_salle}">${escH(s.nom_salle)}${s.capacite?' ('+s.capacite+' pl.)':''}</option>`
                ).join('')}
            </select>
        </td>
        <td>
            <input type="number" name="volume[]" min="1" max="40"
                   placeholder="h" style="width:56px">
        </td>
        <td style="text-align:center">
            <input type="checkbox" name="principal[${i}]" value="1"
                   title="Enseignant principal">
        </td>
        <td>
            <button type="button" class="btn-icon btn-icon--danger"
                    onclick="supprimerLigne(${i})" title="Supprimer la ligne">🗑</button>
        </td>
    `;
    tbody.appendChild(tr);
    updateSubmitBtn();
}

function onDeptChange(sel, i) {
    const idDept  = parseInt(sel.value);
    const selMat  = document.getElementById('sel-mat-' + i);
    selMat.innerHTML = '<option value="">— Matière —</option>';

    const mats = MATIERES_BY_DEPT[idDept] || [];
    mats.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id_matiere;
        opt.textContent = m.nom_matiere + (m.code_matiere ? ' ('+m.code_matiere+')' : '');
        selMat.appendChild(opt);
    });
    selMat.disabled = mats.length === 0;
    selMat.setAttribute('name', 'id_matiere[]');
}

function supprimerLigne(i) {
    document.getElementById('ligne-' + i)?.remove();
    updateSubmitBtn();
}

function updateSubmitBtn() {
    const nb = document.querySelectorAll('#lignesBody tr').length;
    document.getElementById('btnSubmit').disabled = nb === 0;
}

function escH(t) {
    return String(t ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Ajouter une première ligne automatiquement
ajouterLigne();
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
