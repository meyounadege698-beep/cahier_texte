<?php
$pageTitle = 'Ma progression — ' . APP_NAME;
$extraCss  = 'progression_v2.css';
include APP_ROOT . '/app/views/layouts/header.php';

// Grouper la progression par semaine
$parSemaine = [];
foreach ($progression as $row) {
    $numS = $row['numero_semaine'] ?? '—';
    $parSemaine[$numS]['semaine'] = [
        'numero'       => $row['numero_semaine'],
        'date_debut'   => $row['semaine_debut'],
        'date_fin'     => $row['semaine_fin'],
        'titre_periode'=> $row['titre_periode'],
    ];
    $titreChap = $row['titre_chapitre'];
    $parSemaine[$numS]['chapitres'][$titreChap][] = $row;
}
?>

<?= Session::getFlash() ?>

<div class="pv2-ens-header">
    <div>
        <h1>📋 Ma progression officielle</h1>
        <p>
            <strong><?= htmlspecialchars($matiere['nom_matiere'] ?? '') ?></strong>
            — <?= htmlspecialchars($classe['nom_classe'] ?? '') ?>
            — <?= htmlspecialchars($annee) ?>
        </p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=saisie-seance" class="btn btn-primary">
        ✏️ Saisir une séance →
    </a>
</div>

<?php if (empty($parSemaine)): ?>
<div class="pv2-empty">
    <div class="pv2-empty-icon">📋</div>
    <h3>Aucune progression attribuée</h3>
    <p>Le censeur n'a pas encore attribué de programme pour cette matière et classe.</p>
</div>
<?php else: ?>

<div class="pv2-prog-container">
<?php foreach ($parSemaine as $numS => $sData): ?>
    <?php $s = $sData['semaine']; ?>
    <div class="pv2-prog-sem-block">
        <div class="pv2-prog-sem-title">
            <div>
                <span class="pv2-prog-sem-num">Semaine <?= htmlspecialchars((string)$numS) ?></span>
                <?php if (!empty($s['titre_periode'])): ?>
                    — <span style="font-weight:400"><?= htmlspecialchars($s['titre_periode']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($s['date_debut']) && !empty($s['date_fin'])): ?>
            <span class="pv2-prog-sem-dates">
                📅 <?= date('d/m/Y', strtotime($s['date_debut'])) ?>
                → <?= date('d/m/Y', strtotime($s['date_fin'])) ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="pv2-prog-ch-block">
        <?php foreach (($sData['chapitres'] ?? []) as $titreChap => $lecons): ?>
            <div class="pv2-prog-ch-header">
                <span class="pv2-ch-num" style="width:26px;height:26px;font-size:11px"><?= (int)($lecons[0]['ordre_chapitre'] ?? 1) ?></span>
                <span class="pv2-prog-ch-title"><?= htmlspecialchars($titreChap, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($lecons[0]['competences_semaine'])): ?>
                    <span style="font-size:12px;color:#4f46e5;margin-left:8px">🎯 <?= htmlspecialchars($lecons[0]['competences_semaine']) ?></span>
                <?php endif; ?>
            </div>

            <?php foreach ($lecons as $l): ?>
            <div class="pv2-prog-lecon-row">
                <!-- Icône statut -->
                <div class="pv2-prog-lecon-status status-<?= htmlspecialchars($l['statut'] ?? 'NON_COMMENCEE') ?>">
                    <?php
                    $statusIcons = ['NON_COMMENCEE'=>'○','EN_COURS'=>'◑','TERMINEE'=>'●','ANNULEE'=>'✕'];
                    echo $statusIcons[$l['statut']] ?? '○';
                    ?>
                </div>

                <!-- Infos leçon -->
                <div class="pv2-prog-lecon-info">
                    <div class="pv2-prog-lecon-titre">
                        <?= htmlspecialchars($l['titre_leçon'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php if (!empty($l['grand_titre'])): ?>
                        <div class="pv2-prog-lecon-grand"><?= htmlspecialchars($l['grand_titre']) ?></div>
                    <?php endif; ?>
                    <div class="pv2-prog-lecon-meta">
                        <?php if (!empty($l['type_lecon'])): ?>
                        <span>
                            <?= $l['type_lecon']==='theorique' ? '📘 Théorique' : ($l['type_lecon']==='pratique' ? '🔬 Pratique' : '📘🔬 Mixte') ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($l['nb_heures'])): ?>
                            <span>⏱ <?= $l['nb_heures'] ?>h</span>
                        <?php endif; ?>
                        <span class="pv2-statut-label-<?= $l['statut'] ?>">
                            <?php
                            $sl = ['NON_COMMENCEE'=>'Non commencée','EN_COURS'=>'En cours',
                                   'TERMINEE'=>'Terminée','ANNULEE'=>'Annulée'];
                            echo $sl[$l['statut']] ?? $l['statut'];
                            ?>
                        </span>
                    </div>

                    <!-- Objectifs pédagogiques cochables -->
                    <?php if (!empty($l['objectifs'])): ?>
                    <ul class="pv2-prog-obj-list">
                        <?php foreach ($l['objectifs'] as $obj): ?>
                        <li class="pv2-prog-obj-item">
                            <span class="pv2-obj-type pv2-obj-<?= $obj['type_objectif'] ?>">
                                <?= $obj['type_objectif']==='savoir' ? 'S' : ($obj['type_objectif']==='savoir_faire' ? 'SF' : 'SE') ?>
                            </span>
                            <span><?= htmlspecialchars($obj['libelle'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php elseif (!empty($l['objectifs_pedagogiques'])): ?>
                        <div style="font-size:12px;color:#64748b;margin-top:6px;font-style:italic">
                            🎯 <?= htmlspecialchars(mb_substr($l['objectifs_pedagogiques'],0,120)) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Progression % -->
                <div style="text-align:center;min-width:60px">
                    <div style="font-size:18px;font-weight:900;color:<?= (int)($l['progression_pourcentage']??0)>=100?'#10b981':'#4f46e5' ?>">
                        <?= (int)($l['progression_pourcentage'] ?? 0) ?>%
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;width:50px;margin:4px auto 0">
                        <div style="height:100%;background:<?= (int)($l['progression_pourcentage']??0)>=100?'#10b981':'#4f46e5' ?>;width:<?= min(100,(int)($l['progression_pourcentage']??0)) ?>%;border-radius:3px;transition:width .5s"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
