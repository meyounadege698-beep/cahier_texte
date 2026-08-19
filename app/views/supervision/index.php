<?php
$pageTitle = 'Tableau de bord Censeur — ' . APP_NAME;
$extraCss  = 'supervision.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>

<?= Session::getFlash() ?>

<div class="sup-header">
    <div>
        <h1>🔍 Supervision pédagogique</h1>
        <p>Suivez l'avancement des programmes et la conformité des cahiers de texte.</p>
    </div>
    <span class="annee-badge">📅 <?= htmlspecialchars($annee) ?></span>
</div>

<!-- ===== KPI CARDS ===== -->
<div class="kpi-grid">
    <div class="kpi-card kpi-card--blue">
        <div class="kpi-icon">👨‍🏫</div>
        <div class="kpi-val"><?= (int)$stats['nbEnseignants'] ?></div>
        <div class="kpi-label">Enseignants actifs</div>
    </div>
    <div class="kpi-card kpi-card--green">
        <div class="kpi-icon">✅</div>
        <div class="kpi-val"><?= (int)$stats['nbSeancesSemaine'] ?></div>
        <div class="kpi-label">Séances cette semaine</div>
    </div>
    <div class="kpi-card kpi-card--orange">
        <div class="kpi-icon">⏳</div>
        <div class="kpi-val"><?= (int)$stats['nbValidations'] ?></div>
        <div class="kpi-label">Validations en attente</div>
    </div>
    <div class="kpi-card kpi-card--purple">
        <div class="kpi-icon">📋</div>
        <div class="kpi-val"><?= (int)$stats['nbProgrammes'] ?></div>
        <div class="kpi-label">Programmes publiés</div>
    </div>
    <?php if (count($alertes) > 0): ?>
    <div class="kpi-card kpi-card--red">
        <div class="kpi-icon">🔔</div>
        <div class="kpi-val"><?= count($alertes) ?></div>
        <div class="kpi-label">Cahiers non remplis</div>
    </div>
    <?php endif; ?>
</div>

<div class="sup-layout">

    <!-- ===== COLONNE GAUCHE ===== -->
    <div class="sup-main">

        <!-- Alertes cahiers non remplis -->
        <?php if (!empty($alertes)): ?>
        <div class="sup-section">
            <div class="sup-section-title sup-section-title--alert">
                🔔 Alertes — Cahiers non remplis (≥ 7 jours)
            </div>
            <div class="alertes-list">
                <?php foreach ($alertes as $a): ?>
                <div class="alerte-item">
                    <div class="alerte-ens">
                        <strong><?= htmlspecialchars($a['enseignant'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="alerte-email"><?= htmlspecialchars($a['email']) ?></span>
                    </div>
                    <div class="alerte-info">
                        <?php if ($a['derniere_seance']): ?>
                            Dernière séance : <?= date('d/m/Y', strtotime($a['derniere_seance'])) ?>
                            <span class="alerte-days">(<?= (int)$a['jours_inactivite'] ?> j)</span>
                        <?php else: ?>
                            <span class="alerte-days">Aucune séance saisie</span>
                        <?php endif; ?>
                    </div>
                    <span class="badge-alerte">⚠️ Relancer</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Taux de couverture programme -->
        <div class="sup-section">
            <div class="sup-section-title">📊 Couverture du programme</div>
            <?php if (empty($couverture)): ?>
                <p class="sup-empty">Aucune donnée de progression pour cette année.</p>
            <?php else: ?>
            <div class="couv-table-wrap">
                <table class="couv-table">
                    <thead>
                        <tr>
                            <th>Enseignant</th>
                            <th>Classe</th>
                            <th>Matière</th>
                            <th>Progression</th>
                            <th>Taux</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($couverture as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['enseignant'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="tag-sm tag-sm--classe"><?= htmlspecialchars($c['nom_classe']) ?></span></td>
                        <td><span class="tag-sm tag-sm--mat"><?= htmlspecialchars($c['nom_matiere']) ?></span></td>
                        <td>
                            <div class="couv-bar-wrap">
                                <div class="couv-bar"
                                     style="width:<?= min(100,(float)$c['taux']) ?>%;
                                            background:<?= (float)$c['taux']>=75?'#10b981':((float)$c['taux']>=40?'#f59e0b':'#ef4444') ?>">
                                </div>
                            </div>
                            <small><?= (int)$c['terminees'] ?>/<?= (int)$c['total_lecons'] ?> leçons</small>
                        </td>
                        <td>
                            <strong class="<?= (float)$c['taux']>=75?'text-green':((float)$c['taux']>=40?'text-orange':'text-red') ?>">
                                <?= $c['taux'] ?>%
                            </strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Activité récente -->
        <div class="sup-section">
            <div class="sup-section-title">⚡ Activité récente</div>
            <?php if (empty($activite)): ?>
                <p class="sup-empty">Aucune séance saisie récemment.</p>
            <?php else: ?>
            <div class="activite-list">
                <?php foreach ($activite as $a): ?>
                <div class="activite-item">
                    <div class="activite-date">
                        <?= date('d/m', strtotime($a['date_seance'])) ?>
                    </div>
                    <div class="activite-body">
                        <div class="activite-title">
                            <strong><?= htmlspecialchars($a['enseignant'], ENT_QUOTES, 'UTF-8') ?></strong>
                            — <?= htmlspecialchars($a['nom_matiere']) ?>
                        </div>
                        <div class="activite-meta">
                            🏫 <?= htmlspecialchars($a['nom_classe']) ?>
                            · <?= substr($a['heure_debut'],0,5) ?>–<?= substr($a['heure_fin'],0,5) ?>
                        </div>
                        <div class="activite-contenu">
                            <?= htmlspecialchars(mb_substr($a['contenu_traite'],0,80)) ?>…
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ===== COLONNE DROITE : VALIDATIONS ===== -->
    <div class="sup-side">
        <div class="sup-section-title">⏳ Validations en attente</div>

        <?php if (empty($validations)): ?>
            <p class="sup-empty">Aucune progression en attente de validation. 🎉</p>
        <?php else: ?>
        <?php foreach ($validations as $v): ?>
        <div class="val-item">
            <div class="val-header">
                <strong><?= htmlspecialchars($v['enseignant'], ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="tag-sm tag-sm--classe"><?= htmlspecialchars($v['nom_classe']) ?></span>
            </div>
            <div class="val-detail">
                📚 <?= htmlspecialchars($v['nom_matiere']) ?><br>
                📋 <?= htmlspecialchars($v['titre_chapitre']) ?><br>
                🎯 <?= htmlspecialchars(mb_substr($v['titre_leçon'],0,60)) ?><br>
                📅 <?= $v['date_debut'] ? date('d/m/Y', strtotime($v['date_debut'])) : '—' ?>
                → <?= $v['date_fin'] ? date('d/m/Y', strtotime($v['date_fin'])) : '—' ?>
                · <strong><?= (int)$v['progression_pourcentage'] ?>%</strong>
            </div>

            <form method="POST" action="<?= APP_URL ?>/app.php?page=supervision"
                  class="val-form">
                <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_action"    value="valider_progression">
                <input type="hidden" name="id_progression" value="<?= (int)$v['id_progression'] ?>">
                <textarea name="commentaire" rows="2"
                          placeholder="Commentaire (optionnel)..."></textarea>
                <div class="val-btns">
                    <button type="submit" name="statut_validation" value="APPROUVE"
                            class="btn btn-success btn-sm">✅ Approuver</button>
                    <button type="submit" name="statut_validation" value="REFUSE"
                            class="btn btn-outline btn-sm"
                            onclick="return confirm('Refuser cette progression ?')">❌ Refuser</button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
