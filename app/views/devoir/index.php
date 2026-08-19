<?php
$pageTitle = 'Mes devoirs — ' . APP_NAME;
$extraCss  = 'devoir.css';
include APP_ROOT . '/app/views/layouts/header.php';
$typeLabels = ['DM'=>'Devoir Maison','DS'=>'Devoir Surveillé','EVAL'=>'Évaluation','PROJET'=>'Projet'];
$typeColors = ['DM'=>'blue','DS'=>'red','EVAL'=>'orange','PROJET'=>'green'];
?>

<?= Session::getFlash() ?>

<div class="dev-header">
    <div>
        <h1>📚 Mes devoirs</h1>
        <p>Enregistrez et gérez les devoirs donnés à vos classes.</p>
    </div>
    <button class="btn btn-primary" onclick="toggleModal('modal-add-devoir')">
        ＋ Ajouter un devoir
    </button>
</div>

<?php if (empty($devoirs)): ?>
    <div class="empty-state">
        <div class="empty-icon">📚</div>
        <h3>Aucun devoir enregistré</h3>
        <p>Cliquez sur « Ajouter un devoir » pour commencer.</p>
    </div>
<?php else: ?>
<div class="dev-grid">
    <?php foreach ($devoirs as $d): ?>
    <div class="dev-card dev-card--<?= $typeColors[$d['type_devoir']] ?? 'blue' ?>">
        <div class="dev-card-top">
            <span class="dev-type"><?= $typeLabels[$d['type_devoir']] ?? $d['type_devoir'] ?></span>
            <span class="dev-date-remise">
                📅 Remise : <?= date('d/m/Y', strtotime($d['date_remise'])) ?>
            </span>
        </div>
        <h3 class="dev-titre"><?= htmlspecialchars($d['titre'], ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="dev-meta">
            <span>🏫 <?= htmlspecialchars($d['nom_classe']) ?></span>
            <span>📖 <?= htmlspecialchars($d['nom_matiere']) ?></span>
            <span>📅 Séance : <?= date('d/m/Y', strtotime($d['date_seance'])) ?></span>
            <span>📊 /<?= (int)$d['note_sur'] ?> — Coef. <?= (int)$d['coeff_notation'] ?></span>
        </div>
        <p class="dev-consigne">
            <?= htmlspecialchars(mb_substr($d['consigne'], 0, 120), ENT_QUOTES, 'UTF-8') ?>
            <?= mb_strlen($d['consigne']) > 120 ? '...' : '' ?>
        </p>
        <div class="dev-actions">
            <form method="POST" action="<?= APP_URL ?>/app.php?page=devoirs"
                  onsubmit="return confirm('Supprimer ce devoir ?')">
                <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_action" value="delete_devoir">
                <input type="hidden" name="id_devoir"   value="<?= (int)$d['id_devoir'] ?>">
                <button type="submit" class="btn-icon btn-icon--danger" title="Supprimer">🗑</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal ajout devoir -->
<div class="modal-overlay" id="modal-add-devoir" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3>📚 Nouveau devoir</h3>
            <button class="modal-close" onclick="toggleModal('modal-add-devoir')">✕</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=devoirs">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="create_devoir">

            <div class="form-group">
                <label>Séance concernée <span class="required">*</span></label>
                <select name="id_seance" required>
                    <option value="">— Choisir une séance —</option>
                    <?php foreach ($seances as $s): ?>
                        <option value="<?= (int)$s['id_seance'] ?>">
                            <?= date('d/m/Y', strtotime($s['date_seance'])) ?>
                            — <?= htmlspecialchars($s['nom_matiere']) ?>
                            — <?= htmlspecialchars($s['nom_classe']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type <span class="required">*</span></label>
                <select name="type_devoir" required>
                    <?php foreach ($typeLabels as $v => $l): ?>
                        <option value="<?= $v ?>"><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Titre <span class="required">*</span></label>
                <input type="text" name="titre" placeholder="Ex : Exercice sur les intégrales"
                       required maxlength="150">
            </div>
            <div class="form-group">
                <label>Consigne <span class="required">*</span></label>
                <textarea name="consigne" rows="4"
                          placeholder="Décrivez le travail demandé..." required></textarea>
            </div>
            <div class="form-group">
                <label>Date de remise <span class="required">*</span></label>
                <input type="date" name="date_remise" required
                       min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Note sur</label>
                    <input type="number" name="note_sur" value="20" min="1" max="100">
                </div>
                <div class="form-group">
                    <label>Coefficient</label>
                    <input type="number" name="coeff_notation" value="1" min="1" max="10">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline"
                        onclick="toggleModal('modal-add-devoir')">Annuler</button>
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
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
