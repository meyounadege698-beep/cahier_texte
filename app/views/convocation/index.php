<?php
$pageTitle = 'Convocations — ' . APP_NAME;
$extraCss  = 'convocation.css';
include APP_ROOT . '/app/views/layouts/header.php';
$statutLabel = ['envoyee'=>'📤 Envoyée','lue'=>'👁 Lue','acquittee'=>'✅ Acquittée'];
$statutCls   = ['envoyee'=>'conv-envoyee','lue'=>'conv-lue','acquittee'=>'conv-acquittee'];
?>
<?= Session::getFlash() ?>

<div class="conv-header">
    <div>
        <h1>📨 Convocations</h1>
        <p>Envoyez des convocations aux enseignants et suivez leur statut.</p>
    </div>
</div>

<div class="conv-layout">

    <!-- Formulaire envoi -->
    <div class="conv-form-card">
        <h2>✉️ Nouvelle convocation</h2>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=do-envoyer-convocation">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="conv-form-group">
                <label>Enseignant <span class="req">*</span></label>
                <select name="id_enseignant" required>
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($enseignants as $e): ?>
                        <option value="<?= (int)$e['id_utilisateur'] ?>">
                            <?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="conv-form-group">
                <label>Motif <span class="req">*</span></label>
                <textarea name="motif" rows="3" required
                          placeholder="Ex : Absence non justifiée le 15/09/2026, retards répétés..."></textarea>
            </div>
            <div class="conv-form-row">
                <div class="conv-form-group">
                    <label>Date de convocation <span class="req">*</span></label>
                    <input type="datetime-local" name="date_convocation" required
                           min="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="conv-form-group">
                    <label>Lieu <span class="opt">(optionnel)</span></label>
                    <input type="text" name="lieu" placeholder="Ex : Bureau du censeur, Salle A" maxlength="200">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">📤 Envoyer la convocation</button>
        </form>
    </div>

    <!-- Liste des convocations -->
    <div class="conv-list-panel">
        <h2>📋 Historique <span class="count-badge"><?= count($convocations) ?></span></h2>
        <?php if (empty($convocations)): ?>
            <div class="conv-empty">Aucune convocation envoyée.</div>
        <?php else: ?>
        <div class="conv-list">
            <?php foreach ($convocations as $c): ?>
            <div class="conv-item <?= $statutCls[$c['statut']] ?? '' ?>">
                <div class="conv-item-header">
                    <div>
                        <span class="conv-ens">👨‍🏫 <?= htmlspecialchars($c['nom_enseignant'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="conv-email"><?= htmlspecialchars($c['email_enseignant']) ?></span>
                    </div>
                    <span class="conv-statut-badge <?= $statutCls[$c['statut']] ?? '' ?>">
                        <?= $statutLabel[$c['statut']] ?? $c['statut'] ?>
                    </span>
                </div>
                <div class="conv-motif"><?= nl2br(htmlspecialchars($c['motif'], ENT_QUOTES, 'UTF-8')) ?></div>
                <div class="conv-meta">
                    📅 Convocation le : <strong><?= date('d/m/Y à H:i', strtotime($c['date_convocation'])) ?></strong>
                    <?php if ($c['lieu']): ?>
                        — 📍 <?= htmlspecialchars($c['lieu']) ?>
                    <?php endif; ?>
                    <br>📤 Envoyée le : <?= date('d/m/Y H:i', strtotime($c['date_envoi'])) ?>
                    par <?= htmlspecialchars($c['nom_emetteur']) ?>
                    <?php if ($c['date_lecture']): ?>
                        — 👁 Lue le : <?= date('d/m/Y H:i', strtotime($c['date_lecture'])) ?>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= APP_URL ?>/app.php?page=do-supprimer-convocation"
                      onsubmit="return confirm('Supprimer cette convocation ?')" style="text-align:right;margin-top:8px">
                    <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_convocation"  value="<?= (int)$c['id_convocation'] ?>">
                    <button type="submit" class="btn-icon btn-icon--danger btn-sm">🗑 Supprimer</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
