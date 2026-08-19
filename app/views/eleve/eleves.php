<?php
$pageTitle = ($classe ? $classe['nom_classe'].' — ' : '') . 'Élèves — ' . APP_NAME;
$extraCss  = 'eleve.css';
include APP_ROOT . '/app/views/layouts/header.php';
$anneeDefaut = $classe['annee_scolaire'] ?? '';
?>

<?= Session::getFlash() ?>

<div class="elv-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= APP_URL ?>/app.php?page=gestion-classes">Classes</a>
            <span>›</span>
            <span><?= $classe ? htmlspecialchars($classe['nom_classe']) : 'Élèves' ?></span>
        </div>
        <h1>👤 Élèves
            <?php if ($classe): ?>
                — <span style="color:#4f46e5"><?= htmlspecialchars($classe['nom_classe']) ?></span>
            <?php endif; ?>
        </h1>
        <p><?= count($eleves) ?> élève<?= count($eleves) > 1 ? 's' : '' ?> inscrit<?= count($eleves) > 1 ? 's' : '' ?></p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="<?= APP_URL ?>/app.php?page=gestion-classes" class="btn btn-outline">← Classes</a>
        <?php if ($classe): ?>
        <button class="btn btn-primary" onclick="toggleModal('modal-add-eleve')">
            ＋ Inscrire un élève
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Sélecteur de classe -->
<?php if (!$classe): ?>
<div class="pres-selector" style="margin-bottom:24px">
    <div class="form-group">
        <label>Classe</label>
        <select onchange="window.location.href='<?= APP_URL ?>/app.php?page=gestion-eleves&classe='+this.value">
            <option value="">— Sélectionner une classe —</option>
            <?php foreach ($classes as $cl): ?>
                <option value="<?= (int)$cl['id_classe'] ?>"><?= htmlspecialchars($cl['nom_classe'].' ('.$cl['annee_scolaire'].')') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<?php endif; ?>

<!-- Tableau des élèves -->
<?php if (!empty($eleves)): ?>
<div class="ens-table-wrap">
    <table class="ens-table">
        <thead>
            <tr>
                <th>Élève</th>
                <th>Matricule</th>
                <th>Sexe</th>
                <th>Date de naissance</th>
                <th>Contact parent</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($eleves as $e): ?>
        <tr class="<?= !$e['est_actif'] ? 'row--inactive' : '' ?>">
            <td>
                <div class="ens-name-cell">
                    <div class="ens-avatar-sm" style="background:linear-gradient(135deg,#10b981,#047857)">
                        <?= strtoupper(mb_substr($e['prenom'],0,1).mb_substr($e['nom'],0,1)) ?>
                    </div>
                    <div>
                        <div class="ens-fullname"><?= htmlspecialchars($e['nom'].' '.$e['prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (!$e['est_actif']): ?><span class="badge-inactive">Transféré</span><?php endif; ?>
                    </div>
                </div>
            </td>
            <td class="td-email"><?= htmlspecialchars($e['matricule']) ?></td>
            <td class="td-center"><?= $e['sexe'] ?? '—' ?></td>
            <td class="td-date"><?= $e['date_naissance'] ? date('d/m/Y', strtotime($e['date_naissance'])) : '—' ?></td>
            <td class="td-date"><?= $e['email_parent'] ? htmlspecialchars($e['email_parent']) : ($e['telephone'] ?? '—') ?></td>
            <td class="td-center">
                <?php if ($e['est_actif']): ?>
                    <span class="badge-actif">● Actif</span>
                <?php else: ?>
                    <span class="badge-inactif">● Transféré</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon btn-icon--edit"
                            onclick="openEditEleve(<?= htmlspecialchars(json_encode($e), ENT_QUOTES) ?>)"
                            title="Modifier">✏️</button>
                    <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-eleves"
                          onsubmit="return confirm('<?= $e['est_actif'] ? 'Marquer comme transféré' : 'Réactiver' ?> cet élève ?')">
                        <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="form_action" value="toggle_eleve">
                        <input type="hidden" name="id_eleve"    value="<?= (int)$e['id_eleve'] ?>">
                        <input type="hidden" name="id_classe"   value="<?= (int)($classe['id_classe'] ?? 0) ?>">
                        <input type="hidden" name="est_actif"   value="<?= (int)$e['est_actif'] ?>">
                        <button type="submit"
                                class="btn-icon <?= $e['est_actif'] ? 'btn-icon--disable' : 'btn-icon--enable' ?>"
                                title="<?= $e['est_actif'] ? 'Transférer' : 'Réactiver' ?>">
                            <?= $e['est_actif'] ? '🔴' : '🟢' ?>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif ($classe): ?>
    <div class="empty-state">
        <div class="empty-icon">👤</div>
        <h3>Aucun élève dans cette classe</h3>
        <p>Inscrivez le premier élève.</p>
    </div>
<?php endif; ?>

<!-- Modal : Inscrire élève -->
<?php if ($classe): ?>
<div class="modal-overlay" id="modal-add-eleve" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>👤 Inscrire un élève</h3>
            <button class="modal-close" onclick="toggleModal('modal-add-eleve')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-eleves">
            <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action"    value="add_eleve">
            <input type="hidden" name="id_classe"      value="<?= (int)$classe['id_classe'] ?>">
            <input type="hidden" name="annee_scolaire" value="<?= htmlspecialchars($classe['annee_scolaire']) ?>">

            <div class="form-row">
                <div class="form-group"><label>Nom <span class="required">*</span></label>
                    <input type="text" name="nom" placeholder="Nom de famille" required maxlength="100"></div>
                <div class="form-group"><label>Prénom <span class="required">*</span></label>
                    <input type="text" name="prenom" placeholder="Prénom" required maxlength="100"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Matricule <span class="required">*</span></label>
                    <input type="text" name="matricule" placeholder="Ex : 2024-001" required maxlength="30"></div>
                <div class="form-group"><label>Sexe</label>
                    <select name="sexe">
                        <option value="">—</option>
                        <option value="M">Masculin</option>
                        <option value="F">Féminin</option>
                    </select></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Date de naissance</label>
                    <input type="date" name="date_naissance"></div>
                <div class="form-group"><label>Lieu de naissance</label>
                    <input type="text" name="lieu_naissance" maxlength="100"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Téléphone</label>
                    <input type="text" name="telephone" placeholder="+237 6XX..." maxlength="20"></div>
                <div class="form-group"><label>Email parent</label>
                    <input type="email" name="email_parent" placeholder="parent@email.com" maxlength="150"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-add-eleve')">Annuler</button>
                <button type="submit" class="btn btn-primary">Inscrire</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal : Modifier élève -->
<div class="modal-overlay" id="modal-edit-eleve" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Modifier l'élève</h3>
            <button class="modal-close" onclick="toggleModal('modal-edit-eleve')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-eleves">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="edit_eleve">
            <input type="hidden" name="id_eleve"    id="edit-eleve-id">
            <input type="hidden" name="id_classe"   value="<?= (int)$classe['id_classe'] ?>">
            <input type="hidden" name="annee_scolaire" value="<?= htmlspecialchars($classe['annee_scolaire']) ?>">

            <div class="form-row">
                <div class="form-group"><label>Nom <span class="required">*</span></label>
                    <input type="text" name="nom" id="edit-eleve-nom" required maxlength="100"></div>
                <div class="form-group"><label>Prénom <span class="required">*</span></label>
                    <input type="text" name="prenom" id="edit-eleve-prenom" required maxlength="100"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Matricule <span class="required">*</span></label>
                    <input type="text" name="matricule" id="edit-eleve-mat" required maxlength="30"></div>
                <div class="form-group"><label>Sexe</label>
                    <select name="sexe" id="edit-eleve-sexe">
                        <option value="">—</option>
                        <option value="M">Masculin</option>
                        <option value="F">Féminin</option>
                    </select></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Date de naissance</label>
                    <input type="date" name="date_naissance" id="edit-eleve-naiss"></div>
                <div class="form-group"><label>Lieu de naissance</label>
                    <input type="text" name="lieu_naissance" id="edit-eleve-lieu" maxlength="100"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Téléphone</label>
                    <input type="text" name="telephone" id="edit-eleve-tel" maxlength="20"></div>
                <div class="form-group"><label>Email parent</label>
                    <input type="email" name="email_parent" id="edit-eleve-email" maxlength="150"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-edit-eleve')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = m.style.display === 'none' ? 'flex' : 'none';
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.style.display = 'none'; });
});
function openEditEleve(e) {
    document.getElementById('edit-eleve-id').value    = e.id_eleve;
    document.getElementById('edit-eleve-nom').value   = e.nom;
    document.getElementById('edit-eleve-prenom').value= e.prenom;
    document.getElementById('edit-eleve-mat').value   = e.matricule;
    document.getElementById('edit-eleve-sexe').value  = e.sexe || '';
    document.getElementById('edit-eleve-naiss').value = e.date_naissance || '';
    document.getElementById('edit-eleve-lieu').value  = e.lieu_naissance || '';
    document.getElementById('edit-eleve-tel').value   = e.telephone || '';
    document.getElementById('edit-eleve-email').value = e.email_parent || '';
    toggleModal('modal-edit-eleve');
}
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
