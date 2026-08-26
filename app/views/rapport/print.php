<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport — <?= APP_NAME ?></title>
    <style>
        /* ── Reset & base ── */
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; font-size:11pt; color:#1e293b; background:#fff; }
        a { color:inherit; text-decoration:none; }

        /* ── En-tête du rapport ── */
        .rpt-head {
            display:flex; align-items:center; justify-content:space-between;
            border-bottom:3px solid #4f46e5; padding-bottom:12px; margin-bottom:20px;
        }
        .rpt-head-left h1 { font-size:16pt; font-weight:800; color:#4f46e5; }
        .rpt-head-left p  { font-size:9pt; color:#64748b; margin-top:3px; }
        .rpt-head-right   { text-align:right; font-size:9pt; color:#64748b; }
        .rpt-head-logo    { font-size:28pt; }

        .rpt-meta {
            background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;
            padding:10px 14px; margin-bottom:20px; display:flex; gap:20px; flex-wrap:wrap;
            font-size:9.5pt;
        }
        .rpt-meta span { color:#374151; }
        .rpt-meta strong { color:#1e293b; }

        /* ── Tableaux ── */
        table { width:100%; border-collapse:collapse; margin-bottom:20px; font-size:9.5pt; }
        thead th {
            background:#4f46e5; color:#fff; padding:8px 10px;
            text-align:left; font-weight:700; font-size:9pt;
        }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tbody tr:hover { background:#eff6ff; }
        tbody td { padding:7px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

        /* ── Badges statut ── */
        .badge { display:inline-block; padding:2px 8px; border-radius:50px; font-size:8pt; font-weight:700; }
        .badge-ok   { background:#d1fae5; color:#065f46; }
        .badge-wip  { background:#fef9c3; color:#854d0e; }
        .badge-no   { background:#f1f5f9; color:#475569; }
        .badge-pres { background:#dbeafe; color:#1e40af; }
        .badge-abs  { background:#fee2e2; color:#991b1b; }
        .badge-ret  { background:#fff7ed; color:#c2410c; }
        .badge-exc  { background:#f5f3ff; color:#4338ca; }

        /* ── Barre progression ── */
        .prog-bar-wrap { width:80px; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; display:inline-block; vertical-align:middle; margin-right:6px; }
        .prog-bar { height:100%; border-radius:4px; }

        /* ── Section titres ── */
        .section-title {
            background:#1e293b; color:#fff; padding:7px 12px;
            font-size:10pt; font-weight:700; margin:16px 0 8px;
            border-radius:6px;
        }
        .subsection { font-size:9.5pt; font-weight:700; color:#4f46e5; padding:5px 0; border-bottom:1px solid #e2e8f0; margin:10px 0 6px; }

        /* ── Pied de page ── */
        .rpt-footer {
            border-top:1px solid #e2e8f0; padding-top:8px; margin-top:20px;
            display:flex; justify-content:space-between; font-size:8pt; color:#94a3b8;
        }

        /* ── Impression ── */
        @media print {
            body { font-size:10pt; }
            .no-print { display:none !important; }
            .rpt-head { border-color:#4f46e5; }
            thead th { background:#4f46e5 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .badge { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .prog-bar { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .section-title { background:#1e293b !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            table { page-break-inside:avoid; }
            .section-block { page-break-inside:avoid; }
        }
    </style>
</head>
<body>

<!-- Bouton imprimer (masqué à l'impression) -->
<div class="no-print" style="background:#4f46e5;color:#fff;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px">
    <span>📄 Rapport prêt — cliquez sur <strong>Imprimer / Enregistrer en PDF</strong></span>
    <div style="display:flex;gap:10px">
        <button onclick="window.print()"
                style="background:#fff;color:#4f46e5;border:none;padding:8px 20px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px">
            🖨️ Imprimer / PDF
        </button>
        <button onclick="window.close()"
                style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.4);padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px">
            ✕ Fermer
        </button>
    </div>
</div>

<div style="padding:24px 32px;max-width:1000px;margin:0 auto">

    <!-- En-tête -->
    <div class="rpt-head">
        <div class="rpt-head-left">
            <h1>
                <?php
                $titres = ['progression'=>'Rapport de Progression','presence'=>'Rapport de Présence','annuel'=>'Rapport Annuel de Synthèse'];
                echo $titres[$type] ?? 'Rapport';
                ?>
            </h1>
            <p><?= APP_NAME ?> — Généré le <?= date('d/m/Y à H:i') ?></p>
        </div>
        <div class="rpt-head-right">
            <div class="rpt-head-logo">📋</div>
        </div>
    </div>

    <!-- Métadonnées du rapport -->
    <div class="rpt-meta">
        <?php if ($classe): ?>
            <span>🏫 Classe : <strong><?= htmlspecialchars($classe['nom_classe'].' ('.$classe['niveau'].')') ?></strong></span>
        <?php endif; ?>
        <?php if ($matiere): ?>
            <span>📚 Matière : <strong><?= htmlspecialchars($matiere['nom_matiere']) ?></strong></span>
        <?php endif; ?>
        <?php if ($annee): ?>
            <span>📅 Année : <strong><?= htmlspecialchars($annee) ?></strong></span>
        <?php endif; ?>
        <?php if ($type === 'presence' && $dateDebut && $dateFin): ?>
            <span>📆 Période : <strong><?= date('d/m/Y', strtotime($dateDebut)) ?> → <?= date('d/m/Y', strtotime($dateFin)) ?></strong></span>
        <?php endif; ?>
        <span>📊 <?= count($data) ?> ligne<?= count($data) > 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($data)): ?>
        <p style="text-align:center;color:#64748b;padding:40px">Aucune donnée pour ces critères.</p>

    <?php elseif ($type === 'progression'): ?>
    <!-- ════════════════ RAPPORT PROGRESSION ════════════════ -->
    <?php
    // Grouper par semaine → chapitre
    $parSem = [];
    foreach ($data as $r) {
        $sem = ($r['numero_semaine'] ?? '—') . '|' . ($r['semaine_debut'] ?? '') . '|' . ($r['semaine_fin'] ?? '');
        $parSem[$sem][$r['titre_chapitre']][] = $r;
    }
    foreach ($parSem as $semKey => $chapitres):
        [$numSem, $sdebut, $sfin] = explode('|', $semKey);
    ?>
    <div class="section-block">
        <div class="section-title">
            Semaine <?= htmlspecialchars($numSem) ?>
            <?php if ($sdebut && $sfin): ?>
                — <?= date('d/m/Y', strtotime($sdebut)) ?> au <?= date('d/m/Y', strtotime($sfin)) ?>
            <?php endif; ?>
        </div>

        <?php foreach ($chapitres as $titreCh => $lecons): ?>
        <div class="subsection">📖 <?= htmlspecialchars($titreCh) ?></div>
        <table>
            <thead>
                <tr>
                    <th style="width:4%">#</th>
                    <th style="width:32%">Leçon</th>
                    <th style="width:18%">Type</th>
                    <th style="width:8%">Heures</th>
                    <th style="width:18%">Statut</th>
                    <th style="width:20%">Avancement</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lecons as $l): ?>
            <tr>
                <td><?= (int)($l['ordre_lecon'] ?? 1) ?></td>
                <td>
                    <strong><?= htmlspecialchars($l['titre_leçon'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if (!empty($l['grand_titre'])): ?>
                        <br><small style="color:#64748b"><?= htmlspecialchars($l['grand_titre']) ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $typeMap = ['theorique'=>'📘 Théorique','pratique'=>'🔬 Pratique','theorique_pratique'=>'📘🔬 Mixte'];
                    echo $typeMap[$l['type_lecon'] ?? ''] ?? '—';
                    ?>
                </td>
                <td><?= $l['nb_heures'] ?? '—' ?>h</td>
                <td>
                    <?php
                    $s = $l['statut'] ?? 'NON_COMMENCEE';
                    $sc = ['NON_COMMENCEE'=>['badge-no','Non commencée'],'EN_COURS'=>['badge-wip','En cours'],'TERMINEE'=>['badge-ok','Terminée'],'ANNULEE'=>['badge-no','Annulée']];
                    [$cls,$lbl] = $sc[$s] ?? ['badge-no',$s];
                    ?>
                    <span class="badge <?= $cls ?>"><?= $lbl ?></span>
                </td>
                <td>
                    <?php $pct = min(100, (int)($l['progression_pourcentage'] ?? 0)); ?>
                    <div class="prog-bar-wrap">
                        <div class="prog-bar" style="width:<?= $pct ?>%;background:<?= $pct>=100?'#10b981':($pct>=50?'#f59e0b':'#4f46e5') ?>"></div>
                    </div>
                    <?= $pct ?>%
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php elseif ($type === 'presence'): ?>
    <!-- ════════════════ RAPPORT PRÉSENCE ════════════════ -->
    <table>
        <thead>
            <tr>
                <th>Élève</th>
                <th>Matricule</th>
                <th style="text-align:center">Total séances</th>
                <th style="text-align:center">Présent</th>
                <th style="text-align:center">Absent</th>
                <th style="text-align:center">Retard</th>
                <th style="text-align:center">Excusé</th>
                <th style="text-align:center">Taux</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $e): ?>
        <tr>
            <td><strong><?= htmlspecialchars($e['nom'].' '.$e['prenom'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td style="color:#64748b"><?= htmlspecialchars($e['matricule']) ?></td>
            <td style="text-align:center"><?= (int)$e['total'] ?></td>
            <td style="text-align:center"><span class="badge badge-pres"><?= (int)$e['present'] ?></span></td>
            <td style="text-align:center"><span class="badge badge-abs"><?= (int)$e['absent'] ?></span></td>
            <td style="text-align:center"><span class="badge badge-ret"><?= (int)$e['retard'] ?></span></td>
            <td style="text-align:center"><span class="badge badge-exc"><?= (int)$e['excuse'] ?></span></td>
            <td style="text-align:center">
                <?php $taux = (float)($e['taux'] ?? 0); ?>
                <div class="prog-bar-wrap">
                    <div class="prog-bar" style="width:<?= min(100,$taux) ?>%;background:<?= $taux>=75?'#10b981':($taux>=50?'#f59e0b':'#ef4444') ?>"></div>
                </div>
                <strong><?= $taux ?>%</strong>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php elseif ($type === 'annuel'): ?>
    <!-- ════════════════ RAPPORT ANNUEL ════════════════ -->
    <table>
        <thead>
            <tr>
                <th>Enseignant</th>
                <th>Classe</th>
                <th>Matière</th>
                <th style="text-align:center">Leçons planifiées</th>
                <th style="text-align:center">Terminées</th>
                <th style="text-align:center">En cours</th>
                <th style="text-align:center">Séances</th>
                <th style="text-align:center">Avancement</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $r): ?>
        <tr>
            <td><strong><?= htmlspecialchars($r['enseignant'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><span class="badge badge-pres"><?= htmlspecialchars($r['nom_classe']) ?></span></td>
            <td><?= htmlspecialchars($r['nom_matiere']) ?></td>
            <td style="text-align:center"><?= (int)$r['nb_lecons_planifiees'] ?></td>
            <td style="text-align:center"><span class="badge badge-ok"><?= (int)$r['terminees'] ?></span></td>
            <td style="text-align:center"><span class="badge badge-wip"><?= (int)$r['en_cours'] ?></span></td>
            <td style="text-align:center"><?= (int)$r['nb_seances'] ?></td>
            <td style="text-align:center">
                <?php $av = (float)($r['avancement'] ?? 0); ?>
                <div class="prog-bar-wrap">
                    <div class="prog-bar" style="width:<?= min(100,$av) ?>%;background:<?= $av>=75?'#10b981':($av>=40?'#f59e0b':'#ef4444') ?>"></div>
                </div>
                <strong><?= $av ?>%</strong>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Pied de page -->
    <div class="rpt-footer">
        <span><?= APP_NAME ?> — <?= date('d/m/Y H:i') ?></span>
        <span>Document généré automatiquement — confidentiel</span>
    </div>

</div>

<script>
// Auto-imprimer si paramètre auto=1
if (new URLSearchParams(window.location.search).get('auto') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 500));
}
</script>
</body>
</html>
