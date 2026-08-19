<?php
$pageTitle = 'Gestion des enseignants — ' . APP_NAME;
$extraCss  = 'enseignant.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>

<?= Session::getFlash() ?>

<!-- ===== Bannière mot de passe (affiché une seule fois) ===== -->
<?php if ($flashPassword): ?>
<div class="pwd-banner" id="pwdBanner">
    <div class="pwd-banner-icon">🔑</div>
    <div class="pwd-banner-body">
        <div class="pwd-banner-title">
            Mot de passe temporaire pour <strong><?= htmlspecialchars($flashPasswordFor, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="pwd-banner-hint">
            ⚠️ Ce mot de passe n'est affiché qu'une seule fois. Communiquez-le à l'enseignant en main propre ou par messagerie sécurisée.
        </div>
        <div class="pwd-display">
            <code id="pwdCode"><?= htmlspecialchars($flashPassword, ENT_QUOTES, 'UTF-8') ?></code>
            <button type="button" class="btn-copy" onclick="copyPwd()" title="Copier">📋 Copier</button>
        </div>
    </div>
    <button class="pwd-banner-close" onclick="document.getElementById('pwdBanner').remove()" title="Fermer">✕</button>
</div>
<?php endif; ?>

<!-- ===== En-tête ===== -->
<div class="ens-header">
    <div>
        <h1>👨‍🏫 Gestion des enseignants</h1>
        <p>Inscrivez et gérez les comptes enseignants de l'établissement.</p>
    </div>
    <button class="btn btn-primary" onclick="toggleModal('modal-add')">
        ＋ Inscrire un enseignant
    </button>
</div>

<!-- ===== Stats ===== -->
<div class="ens-stats">
    <div class="stat-card">
        <span class="stat-num"><?= count($enseignants) ?></span>
        <span class="stat-label">Total</span>
    </div>
    <div class="stat-card stat-card--green">
        <span class="stat-num"><?= count(array_filter($enseignants, fn($e) => $e['est_actif'])) ?></span>
        <span class="stat-label">Actifs</span>
    </div>
    <div class="stat-card stat-card--gray">
        <span class="stat-num"><?= count(array_filter($enseignants, fn($e) => !$e['est_actif'])) ?></span>
        <span class="stat-label">Inactifs</span>
    </div>
    <div class="stat-card stat-card--blue">
        <span class="stat-num"><?= count(array_filter($enseignants, fn($e) => $e['derniere_connexion'])) ?></span>
        <span class="stat-label">Connectés au moins 1 fois</span>
    </div>
</div>

<!-- ===== Liste des enseignants ===== -->
<?php if (empty($enseignants)): ?>
    <div class="empty-state">
        <div class="empty-icon">👨‍🏫</div>
        <h3>Aucun enseignant inscrit</h3>
        <p>Cliquez sur « Inscrire un enseignant » pour créer le premier compte.</p>
    </div>
<?php else: ?>

<div class="ens-table-wrap">
    <table class="ens-table">
        <thead>
            <tr>
                <th>Enseignant</th>
                <th>Email</th>
                <th>Affectations</th>
                <th>Inscrit le</th>
                <th>Dernière connexion</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($enseignants as $e): ?>
        <tr class="<?= !$e['est_actif'] ? 'row--inactive' : '' ?>">
            <td>
                <div class="ens-name-cell">
                    <div class="ens-avatar-sm">
                        <?= strtoupper(mb_substr($e['prenom'], 0, 1) . mb_substr($e['nom'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="ens-fullname">
                            <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if (!$e['est_actif']): ?>
                            <span class="badge-inactive">Inactif</span>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td class="td-email"><?= htmlspecialchars($e['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="td-center">
                <span class="aff-count"><?= (int)$e['nb_affectations'] ?></span>
            </td>
            <td class="td-date">
                <?= date('d/m/Y', strtotime($e['date_inscription'])) ?>
            </td>
            <td class="td-date">
                <?php if ($e['derniere_connexion']): ?>
                    <?= date('d/m/Y H:i', strtotime($e['derniere_connexion'])) ?>
                <?php else: ?>
                    <span class="never-connected">Jamais connecté</span>
                <?php endif; ?>
            </td>
            <td class="td-center">
                <?php if ($e['est_actif']): ?>
                    <span class="badge-actif">● Actif</span>
                <?php else: ?>
                    <span class="badge-inactif">● Inactif</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="action-btns">
                    <!-- Affecter -->
                    <a href="<?= APP_URL ?>/app.php?page=affecter-enseignant&id=<?= (int)$e['id_utilisateur'] ?>"
                       class="btn-icon btn-icon--assign" title="Gérer les affectations">📋</a>

                    <!-- Modifier -->
                    <button class="btn-icon btn-icon--edit"
                            onclick="openEdit(<?= htmlspecialchars(json_encode($e), ENT_QUOTES) ?>)"
                            title="Modifier">✏️</button>

                    <!-- Reset mdp -->
                    <button class="btn-icon btn-icon--key"
                            onclick="openReset(<?= (int)$e['id_utilisateur'] ?>, '<?= htmlspecialchars($e['prenom'].' '.$e['nom'], ENT_QUOTES, 'UTF-8') ?>')"
                            title="Réinitialiser le mot de passe">🔑</button>

                    <!-- Activer / Désactiver -->
                    <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-enseignants"
                          onsubmit="return confirm('<?= $e['est_actif'] ? 'Désactiver' : 'Réactiver' ?> ce compte ?')">
                        <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="form_action"     value="toggle_actif">
                        <input type="hidden" name="id_utilisateur"  value="<?= (int)$e['id_utilisateur'] ?>">
                        <input type="hidden" name="est_actif"       value="<?= (int)$e['est_actif'] ?>">
                        <button type="submit"
                                class="btn-icon <?= $e['est_actif'] ? 'btn-icon--disable' : 'btn-icon--enable' ?>"
                                title="<?= $e['est_actif'] ? 'Désactiver' : 'Réactiver' ?>">
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
<?php endif; ?>

<!-- ============================================================
     MODAL : Inscrire un enseignant
============================================================ -->
<div class="modal-overlay" id="modal-add" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>👨‍🏫 Inscrire un enseignant</h3>
            <button class="modal-close" onclick="toggleModal('modal-add')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-enseignants">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="add_enseignant">

            <div class="form-row">
                <div class="form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="nom" placeholder="Ex : Dupont" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Prénom <span class="required">*</span></label>
                    <input type="text" name="prenom" placeholder="Ex : Jean" required maxlength="100">
                </div>
            </div>

            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" placeholder="jean.dupont@ecole.cm" required maxlength="150">
            </div>

            <!-- Choix du mot de passe -->
            <div class="form-group">
                <label>Mot de passe initial</label>
                <div class="pwd-choice">
                    <label class="radio-option">
                        <input type="radio" name="pwd_mode" value="default" checked
                               onchange="togglePwdMode(this.value)">
                        <div class="radio-body">
                            <strong>Mot de passe par défaut</strong>
                            <code class="default-pwd-preview"><?= htmlspecialchars(EnseignantModel::DEFAULT_PASSWORD) ?></code>
                        </div>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="pwd_mode" value="custom"
                               onchange="togglePwdMode(this.value)">
                        <div class="radio-body">
                            <strong>Mot de passe personnalisé</strong>
                            <small>Minimum 8 caractères</small>
                        </div>
                    </label>
                </div>

                <!-- Champ mot de passe par défaut (caché) -->
                <input type="hidden" name="use_default_password" id="use_default_password" value="1">

                <!-- Champ mot de passe personnalisé -->
                <div id="custom-pwd-field" style="display:none; margin-top:10px">
                    <div class="pwd-input-wrap">
                        <input type="password" id="custom_password" name="custom_password"
                               placeholder="Minimum 8 caractères" minlength="8" autocomplete="new-password">
                        <button type="button" class="btn-eye"
                                onclick="toggleEye('custom_password', this)">👁️</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-add')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer le compte</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL : Modifier enseignant
============================================================ -->
<div class="modal-overlay" id="modal-edit" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Modifier l'enseignant</h3>
            <button class="modal-close" onclick="toggleModal('modal-edit')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-enseignants">
            <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action"    value="edit_enseignant">
            <input type="hidden" name="id_utilisateur" id="edit-id">

            <div class="form-row">
                <div class="form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="nom" id="edit-nom" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Prénom <span class="required">*</span></label>
                    <input type="text" name="prenom" id="edit-prenom" required maxlength="100">
                </div>
            </div>
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" id="edit-email" required maxlength="150">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-edit')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL : Réinitialiser le mot de passe
============================================================ -->
<div class="modal-overlay" id="modal-reset" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>🔑 Réinitialiser le mot de passe</h3>
            <button class="modal-close" onclick="toggleModal('modal-reset')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=gestion-enseignants">
            <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action"    value="reset_password">
            <input type="hidden" name="id_utilisateur" id="reset-id">

            <div class="form-group">
                <div class="reset-for" id="reset-for-name"></div>
            </div>

            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <div class="reset-choices">
                    <label class="radio-option">
                        <input type="radio" name="reset_mode" value="default" checked
                               onchange="toggleResetMode(this.value)">
                        <div class="radio-body">
                            <strong>Remettre le mot de passe par défaut</strong>
                            <code class="default-pwd-preview"><?= htmlspecialchars(EnseignantModel::DEFAULT_PASSWORD) ?></code>
                        </div>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="reset_mode" value="generated"
                               onchange="toggleResetMode(this.value)">
                        <div class="radio-body">
                            <strong>Générer un mot de passe aléatoire</strong>
                            <small>Sécurisé, 12 caractères</small>
                        </div>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="reset_mode" value="custom"
                               onchange="toggleResetMode(this.value)">
                        <div class="radio-body">
                            <strong>Mot de passe personnalisé</strong>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Champs cachés pour les modes -->
            <input type="hidden" name="use_default_pwd"   id="reset_use_default" value="1">
            <input type="hidden" name="use_generated_pwd" id="reset_use_generated" value="">

            <div id="reset-custom-field" style="display:none">
                <div class="form-group">
                    <div class="pwd-input-wrap">
                        <input type="password" id="new_password" name="new_password"
                               placeholder="Minimum 8 caractères" autocomplete="new-password">
                        <button type="button" class="btn-eye"
                                onclick="toggleEye('new_password', this)">👁️</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modal-reset')">Annuler</button>
                <button type="submit" class="btn btn-primary">Réinitialiser</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Modals ──
function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = m.style.display === 'none' ? 'flex' : 'none';
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.style.display = 'none'; });
});

// ── Affichage/masquage mot de passe ──
function toggleEye(inputId, btn) {
    const input = document.getElementById(inputId);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.textContent = isText ? '👁️' : '🙈';
}

// ── Mode mot de passe (ajout) ──
function togglePwdMode(mode) {
    const customField = document.getElementById('custom-pwd-field');
    const useDefault  = document.getElementById('use_default_password');
    if (mode === 'default') {
        customField.style.display = 'none';
        useDefault.value = '1';
        document.getElementById('custom_password').removeAttribute('required');
    } else {
        customField.style.display = 'block';
        useDefault.value = '';
        document.getElementById('custom_password').setAttribute('required', '');
    }
}

// ── Mode reset mot de passe ──
function toggleResetMode(mode) {
    document.getElementById('reset_use_default').value   = mode === 'default'   ? '1' : '';
    document.getElementById('reset_use_generated').value = mode === 'generated' ? '1' : '';
    document.getElementById('reset-custom-field').style.display =
        mode === 'custom' ? 'block' : 'none';
}

// ── Ouvrir modal modifier ──
function openEdit(e) {
    document.getElementById('edit-id').value     = e.id_utilisateur;
    document.getElementById('edit-nom').value    = e.nom;
    document.getElementById('edit-prenom').value = e.prenom;
    document.getElementById('edit-email').value  = e.email;
    toggleModal('modal-edit');
}

// ── Ouvrir modal reset ──
function openReset(id, name) {
    document.getElementById('reset-id').value       = id;
    document.getElementById('reset-for-name').textContent = 'Enseignant : ' + name;
    // Réinitialiser le mode
    document.querySelector('#modal-reset input[value="default"]').checked = true;
    toggleResetMode('default');
    toggleModal('modal-reset');
}

// ── Copier le mot de passe flash ──
function copyPwd() {
    const code = document.getElementById('pwdCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.querySelector('.btn-copy');
        btn.textContent = '✅ Copié !';
        setTimeout(() => btn.textContent = '📋 Copier', 2000);
    });
}
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
