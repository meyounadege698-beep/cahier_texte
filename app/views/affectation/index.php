<?php
$pageTitle = 'Salles & Affectations — ' . APP_NAME;
$extraCss  = 'affectation.css';
include APP_ROOT . '/app/views/layouts/header.php';

$typesLabels = ['classe'=>'Salle de classe','laboratoire'=>'Laboratoire',
                'salle_info'=>'Salle informatique','amphi'=>'Amphithéâtre','autre'=>'Autre'];
?>

<?= Session::getFlash() ?>

<div class="aff-page-header">
    <div>
        <h1>🏫 Salles & Affectations</h1>
        <p>Gérez les salles de classe et attribuez les enseignants à leurs classes et matières.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=gestion-catalogue" class="btn btn-outline">← Catalogue</a>
</div>

<!-- ============================================================
     ONGLETS
============================================================ -->
<div class="aff-tabs">
    <button class="aff-tab active" onclick="switchTab('salles', this)">🏛️ Salles</button>
    <button class="aff-tab" onclick="switchTab('affectations', this)">👨‍🏫 Affectations</button>
</div>

<!-- ============================================================
     ONGLET SALLES
============================================================ -->
<div id="tab-salles" class="aff-tab-content">

    <div class="tab-header">
        <h2>Salles de l'établissement <span class="count-badge"><?= count($salles) ?></span></h2>
        <button class="btn btn-primary btn-sm" onclick="toggleModal('modal-add-salle')">
            ＋ Ajouter une salle
        </button>
    </div>

    <?php if (empty($salles)): ?>
        <div class="empty-state-inline">Aucune salle créée. Commencez par en ajouter une.</div>
    <?php else: ?>
    <div class="salles-grid">
        <?php foreach ($salles as $s): ?>
        <div class="salle-card <?= !$s['est_active'] ? 'salle-card--inactive' : '' ?>">
            <div class="salle-top">
                <div class="salle-icon">
                    <?php
                    $icons = ['classe'=>'🏫','laboratoire'=>'🔬','salle_info'=>'💻','amphi'=>'🎓','autre'=>'🚪'];
                    echo $icons[$s['type_salle']] ?? '🚪';
                    ?>
                </div>
                <div class="salle-name"><?= htmlspecialchars($s['nom_salle'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php if (!$s['est_active']): ?>
                    <span class="badge-inactive">Inactive</span>
                <?php endif; ?>
            </div>
            <div class="salle-meta">
                <span><?= htmlspecialchars($typesLabels[$s['type_salle']] ?? $s['type_salle']) ?></span>
                <?php if ($s['capacite']): ?>
                    <span>👥 <?= (int)$s['capacite'] ?> places</span>
                <?php endif; ?>
                <?php if ($s['localisation']): ?>
                    <span>📍 <?= htmlspecialchars($s['localisation'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span>🔗 <?= (int)$s['nb_affectations'] ?> affectation<?= $s['nb_affectations'] > 1 ? 's' : '' ?></span>
            </div>
            <div class="salle-actions">
                <button class="btn-icon btn-icon--edit"
                        onclick="openEditSalle(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"
                        title="Modifier">✏️</button>
                <?php if ((int)$s['nb_affectations'] === 0): ?>
                <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-affectations"
                      onsubmit="return confirm('Supprimer la salle « <?= htmlspecialchars($s['nom_salle'], ENT_QUOTES, 'UTF-8') ?> » ?')">
                    <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="delete_salle">
                    <input type="hidden" name="id_salle"    value="<?= (int)$s['id_salle'] ?>">
                    <button type="submit" class="btn-icon btn-icon--danger" title="Supprimer">🗑</button>
                </form>
                <?php else: ?>
                    <span class="btn-icon btn-icon--locked" title="Salle utilisée">🔒</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     ONGLET AFFECTATIONS
============================================================ -->
<div id="tab-affectations" class="aff-tab-content" style="display:none">

    <!-- Filtre par année -->
    <div class="aff-year-filter">
        <?php foreach ($annees as $a): ?>
        <a href="<?= APP_URL ?>/app.php?page=gestion-affectations&annee=<?= urlencode($a) ?>#affectations"
           class="year-btn <?= $a === $annee ? 'year-btn--active' : '' ?>"
           onclick="document.getElementById('tab-affectations').style.display='block';
                    document.getElementById('tab-salles').style.display='none';">
            <?= htmlspecialchars($a) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="tab-header">
        <h2>Affectations <?= htmlspecialchars($annee) ?>
            <span class="count-badge"><?= count($affectations) ?></span>
        </h2>
        <button class="btn btn-primary btn-sm"
                onclick="toggleModal('modal-add-aff')"
                <?= empty($enseignants) ? 'disabled title="Aucun enseignant disponible"' : '' ?>>
            ＋ Affecter un enseignant
        </button>
    </div>

    <?php if (empty($affectations)): ?>
        <div class="empty-state-inline">
            Aucune affectation pour l'année <?= htmlspecialchars($annee) ?>.
        </div>
    <?php else: ?>

    <!-- Regroupement par enseignant -->
    <?php
    $parEns = [];
    foreach ($affectations as $a) {
        $parEns[$a['nom_enseignant']][] = $a;
    }
    ?>
    <div class="aff-list">
    <?php foreach ($parEns as $nomEns => $aff): ?>
        <div class="ens-group">
            <div class="ens-group-header">
                <span class="ens-avatar">👨‍🏫</span>
                <div>
                    <div class="ens-name"><?= htmlspecialchars($nomEns, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="ens-email"><?= htmlspecialchars($aff[0]['email'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <span class="count-badge"><?= count($aff) ?> affectation<?= count($aff) > 1 ? 's' : '' ?></span>
            </div>
            <div class="aff-items">
                <?php foreach ($aff as $a): ?>
                <div class="aff-item">
                    <div class="aff-item-body">
                        <!-- Département -->
                        <div class="aff-dept">
                            <span class="tag tag--dept">
                                <?= htmlspecialchars($a['code_departement'] ?? $a['nom_departement'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?= htmlspecialchars($a['nom_departement'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <!-- Matière + Classe + Salle -->
                        <div class="aff-details">
                            <span class="tag tag--matiere">
                                <?= htmlspecialchars($a['nom_matiere']) ?>
                                <?php if ($a['code_matiere']): ?>
                                    <small>(<?= htmlspecialchars($a['code_matiere']) ?>)</small>
                                <?php endif; ?>
                            </span>
                            <span class="tag tag--classe">
                                🏫 <?= htmlspecialchars($a['nom_classe']) ?>
                                <small><?= htmlspecialchars($a['niveau']) ?></small>
                            </span>
                            <?php if ($a['nom_salle']): ?>
                            <span class="tag tag--salle">
                                🚪 <?= htmlspecialchars($a['nom_salle']) ?>
                            </span>
                            <?php else: ?>
                            <span class="tag tag--no-salle">Pas de salle</span>
                            <?php endif; ?>
                            <?php if ($a['volume_horaire_hebdo']): ?>
                            <span class="tag tag--meta">⏱ <?= (int)$a['volume_horaire_hebdo'] ?>h/sem.</span>
                            <?php endif; ?>
                            <?php if ($a['est_principal']): ?>
                            <span class="tag tag--principal">⭐ Principal</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="aff-item-actions">
                        <button class="btn-icon btn-icon--edit"
                                onclick="openEditAff(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)"
                                title="Modifier salle / horaire">✏️</button>
                        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-affectations"
                              onsubmit="return confirm('Supprimer cette affectation ?')">
                            <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="form_action"     value="delete_aff">
                            <input type="hidden" name="id_affectation"  value="<?= (int)$a['id_affectation'] ?>">
                            <input type="hidden" name="annee_scolaire"  value="<?= htmlspecialchars($annee) ?>">
                            <button type="submit" class="btn-icon btn-icon--danger" title="Supprimer">🗑</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     MODALS SALLES
============================================================ -->
<div class="modal-overlay" id="modal-add-salle" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>🏛️ Nouvelle salle</h3>
            <button class="modal-close" onclick="toggleModal('modal-add-salle')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-affectations">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="add_salle">
            <div class="form-group"><label>Nom de la salle <span class="required">*</span></label>
                <input type="text" name="nom_salle" placeholder="Ex : Salle A101" required maxlength="50"></div>
            <div class="form-row">
                <div class="form-group"><label>Type</label>
                    <select name="type_salle">
                        <?php foreach ($typesLabels as $v => $l): ?>
                            <option value="<?= $v ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Capacité (places)</label>
                    <input type="number" name="capacite" min="1" max="500" placeholder="Ex : 40"></div>
            </div>
            <div class="form-group"><label>Localisation <span class="optional">(Bâtiment / Niveau)</span></label>
                <input type="text" name="localisation" placeholder="Ex : Bâtiment B, 2ème étage" maxlength="100"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-add-salle')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-edit-salle" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Modifier la salle</h3>
            <button class="modal-close" onclick="toggleModal('modal-edit-salle')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-affectations">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="edit_salle">
            <input type="hidden" name="id_salle"    id="edit-salle-id">
            <div class="form-group"><label>Nom <span class="required">*</span></label>
                <input type="text" name="nom_salle" id="edit-salle-nom" required maxlength="50"></div>
            <div class="form-row">
                <div class="form-group"><label>Type</label>
                    <select name="type_salle" id="edit-salle-type">
                        <?php foreach ($typesLabels as $v => $l): ?>
                            <option value="<?= $v ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Capacité</label>
                    <input type="number" name="capacite" id="edit-salle-cap" min="1" max="500"></div>
            </div>
            <div class="form-group"><label>Localisation</label>
                <input type="text" name="localisation" id="edit-salle-loc" maxlength="100"></div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="est_active" id="edit-salle-active" value="1">
                    Salle active
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-edit-salle')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL NOUVELLE AFFECTATION — redirige vers la page dédiée
============================================================ -->
<div class="modal-overlay" id="modal-add-aff" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>👨‍🏫 Affecter un enseignant</h3>
            <button class="modal-close" onclick="toggleModal('modal-add-aff')">✕</button>
        </div>
        <div class="form-group" style="padding:0 28px 8px">
            <p style="font-size:14px;color:#64748b;margin-bottom:16px">
                Sélectionnez l'enseignant à affecter. Vous pourrez ensuite lui attribuer
                plusieurs départements, matières, classes et salles.
            </p>
            <label>Enseignant <span class="required">*</span></label>
            <select id="select-ens-affecter" onchange="">
                <option value="">— Sélectionner un enseignant —</option>
                <?php foreach ($enseignants as $e): ?>
                    <option value="<?= (int)$e['id_utilisateur'] ?>">
                        <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="toggleModal('modal-add-aff')">Annuler</button>
            <button type="button" class="btn btn-primary"
                    onclick="goAffecter()">Gérer ses affectations →</button>
        </div>
    </div>
</div>

<!-- ── Modal modifier affectation ── -->
<div class="modal-overlay" id="modal-edit-aff" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Modifier l'affectation</h3>
            <button class="modal-close" onclick="toggleModal('modal-edit-aff')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-affectations">
            <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action"     value="edit_aff">
            <input type="hidden" name="id_affectation"  id="edit-aff-id">
            <input type="hidden" name="annee_scolaire"  id="edit-aff-annee">

            <div class="aff-readonly-info" id="edit-aff-info"></div>

            <div class="form-group"><label>Salle habituelle</label>
                <select name="id_salle" id="edit-aff-salle">
                    <option value="">— Aucune salle fixe —</option>
                    <?php foreach ($salles as $s): if (!$s['est_active']) continue; ?>
                        <option value="<?= (int)$s['id_salle'] ?>">
                            <?= htmlspecialchars($s['nom_salle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select></div>

            <div class="form-group"><label>Volume horaire hebdomadaire (h)</label>
                <input type="number" name="volume_horaire_hebdo" id="edit-aff-vol" min="1" max="40"></div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="est_principal" id="edit-aff-principal" value="1">
                    Enseignant principal
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-edit-aff')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Onglets ──
function switchTab(name, btn) {
    document.querySelectorAll('.aff-tab-content').forEach(t => t.style.display = 'none');
    document.getElementById('tab-' + name).style.display = 'block';
    document.querySelectorAll('.aff-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
// Ouvrir l'onglet affectations si #affectations dans l'URL
if (window.location.hash === '#affectations' || '<?= $_GET['annee'] ?? '' ?>' !== '') {
    document.querySelectorAll('.aff-tab')[1]?.click();
}

// ── Modals ──
function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = m.style.display === 'none' ? 'flex' : 'none';
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.style.display = 'none'; });
});

// ── Éditer salle ──
function openEditSalle(s) {
    document.getElementById('edit-salle-id').value    = s.id_salle;
    document.getElementById('edit-salle-nom').value   = s.nom_salle;
    document.getElementById('edit-salle-type').value  = s.type_salle;
    document.getElementById('edit-salle-cap').value   = s.capacite || '';
    document.getElementById('edit-salle-loc').value   = s.localisation || '';
    document.getElementById('edit-salle-active').checked = s.est_active == 1;
    toggleModal('modal-edit-salle');
}

// ── Éditer affectation ──
function openEditAff(a) {
    document.getElementById('edit-aff-id').value      = a.id_affectation;
    document.getElementById('edit-aff-annee').value   = a.annee_scolaire;
    document.getElementById('edit-aff-salle').value   = a.id_salle || '';
    document.getElementById('edit-aff-vol').value     = a.volume_horaire_hebdo || '';
    document.getElementById('edit-aff-principal').checked = a.est_principal == 1;
    document.getElementById('edit-aff-info').innerHTML =
        `<strong>${escH(a.nom_enseignant)}</strong> → ${escH(a.nom_matiere)} dans ${escH(a.nom_classe)}`;
    toggleModal('modal-edit-aff');
}
function escH(t) {
    return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function goAffecter() {
    const sel = document.getElementById('select-ens-affecter');
    const id  = sel.value;
    if (!id) { alert('Veuillez sélectionner un enseignant.'); return; }
    window.location.href = '<?= APP_URL ?>/app.php?page=affecter-enseignant&id=' + id;
}
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
