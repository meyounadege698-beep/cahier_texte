<?php
$pageTitle = 'Mes convocations — ' . APP_NAME;
$extraCss  = 'convocation.css';
include APP_ROOT . '/app/views/layouts/header.php';
$statutLabel = ['envoyee'=>'📤 Reçue','lue'=>'👁 Lue','acquittee'=>'✅ Acquittée'];
$statutCls   = ['envoyee'=>'conv-envoyee','lue'=>'conv-lue','acquittee'=>'conv-acquittee'];
?>
<?= Session::getFlash() ?>

<div class="conv-header">
    <div>
        <h1>📨 Mes convocations</h1>
        <p>Consultez les convocations qui vous ont été adressées par la direction.</p>
    </div>
</div>

<?php if (empty($convocations)): ?>
<div class="conv-empty-full">
    <div style="font-size:52px;margin-bottom:16px">📭</div>
    <h3>Aucune convocation</h3>
    <p>Vous n'avez reçu aucune convocation pour l'instant.</p>
</div>
<?php else: ?>
<div class="conv-ens-list">
    <?php foreach ($convocations as $c): ?>
    <div class="conv-item <?= $statutCls[$c['statut']] ?? '' ?> conv-item--ens">
        <div class="conv-item-header">
            <div>
                <span class="conv-date-badge">
                    📅 <?= date('d/m/Y à H:i', strtotime($c['date_convocation'])) ?>
                </span>
                <?php if ($c['lieu']): ?>
                    <span class="conv-lieu">📍 <?= htmlspecialchars($c['lieu']) ?></span>
                <?php endif; ?>
            </div>
            <span class="conv-statut-badge <?= $statutCls[$c['statut']] ?? '' ?>">
                <?= $statutLabel[$c['statut']] ?? $c['statut'] ?>
            </span>
        </div>

        <div class="conv-motif-ens">
            <strong>Motif :</strong><br>
            <?= nl2br(htmlspecialchars($c['motif'], ENT_QUOTES, 'UTF-8')) ?>
        </div>

        <div class="conv-meta">
            Convoqué par : <strong><?= htmlspecialchars($c['nom_emetteur'], ENT_QUOTES, 'UTF-8') ?></strong>
            — Envoyée le : <?= date('d/m/Y H:i', strtotime($c['date_envoi'])) ?>
        </div>

        <?php if ($c['statut'] !== 'acquittee'): ?>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=do-acquitter-convocation"
              style="margin-top:12px">
            <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_convocation" value="<?= (int)$c['id_convocation'] ?>">
            <button type="submit" class="btn btn-outline btn-sm">
                ✅ Acquitter (j'ai bien reçu cette convocation)
            </button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
