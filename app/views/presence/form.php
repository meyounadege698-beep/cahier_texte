<?php
$pageTitle = 'Appel & Présence — ' . APP_NAME;
$extraCss  = 'presence.css';
include APP_ROOT . '/app/views/layouts/header.php';
$statutLabels = ['PRESENT'=>'Présent','ABSENT'=>'Absent','RETARD'=>'Retard','EXCUSE'=>'Excusé'];
$statutColors = ['PRESENT'=>'green','ABSENT'=>'red','RETARD'=>'orange','EXCUSE'=>'blue'];
?>

<?= Session::getFlash() ?>

<div class="pres-header">
    <div>
        <h1>📝 Appel & Présence</h1>
        <p>Enregistrez la présence des élèves pour chaque séance.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=historique-presences" class="btn btn-outline">
        📊 Historique
    </a>
</div>

<div class="pres-layout">

    <!-- Sélecteur classe + séance -->
    <div class="pres-selector">
        <div class="form-group">
            <label>Classe</label>
            <select id="selClasse" onchange="window.location.href='<?= APP_URL ?>/app.php?page=appel&classe='+this.value">
                <option value="">— Sélectionner une classe —</option>
                <?php foreach ($classes as $cl): ?>
                    <option value="<?= (int)$cl['id_classe'] ?>"
                        <?= (int)$cl['id_classe'] === $idClasse ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cl['nom_classe'].' ('.$cl['niveau'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($idClasse > 0 && !empty($seances)): ?>
        <div class="form-group">
            <label>Séance</label>
            <select id="selSeance" onchange="window.location.href='<?= APP_URL ?>/app.php?page=appel&classe=<?= $idClasse ?>&seance='+this.value">
                <option value="">— Choisir une séance —</option>
                <?php foreach ($seances as $s): ?>
                    <option value="<?= (int)$s['id_seance'] ?>"
                        <?= (int)$s['id_seance'] === $idSeance ? 'selected' : '' ?>>
                        <?= date('d/m/Y', strtotime($s['date_seance'])) ?>
                        <?= substr($s['heure_debut'],0,5) ?>–<?= substr($s['heure_fin'],0,5) ?>
                        — <?= htmlspecialchars($s['nom_matiere']) ?>
                        <?php if ($s['nb_appels'] > 0): ?>
                            ✅ (appel fait)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php elseif ($idClasse > 0): ?>
            <div class="pres-empty-hint">Aucune séance réalisée pour cette classe.</div>
        <?php endif; ?>
    </div>

    <!-- Formulaire d'appel -->
    <?php if ($idSeance > 0 && $seanceInfo && !empty($eleves)): ?>
    <div class="appel-card">
        <div class="appel-header">
            <div>
                <h2>📋 Appel — <?= htmlspecialchars($seanceInfo['nom_classe']) ?></h2>
                <div class="appel-meta">
                    <span>📅 <?= date('d/m/Y', strtotime($seanceInfo['date_seance'])) ?></span>
                    <span>⏱ <?= substr($seanceInfo['heure_debut'],0,5) ?> – <?= substr($seanceInfo['heure_fin'],0,5) ?></span>
                    <span>📚 <?= htmlspecialchars($seanceInfo['nom_matiere']) ?></span>
                    <span>👤 <?= count($eleves) ?> élèves</span>
                </div>
            </div>
            <!-- Sélection rapide globale -->
            <div class="appel-quick">
                <button type="button" class="btn-quick btn-quick--present"
                        onclick="setAll('PRESENT')">✅ Tous présents</button>
                <button type="button" class="btn-quick btn-quick--absent"
                        onclick="setAll('ABSENT')">❌ Tous absents</button>
            </div>
        </div>

        <form method="POST" action="<?= APP_URL ?>/app.php?page=appel">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="save_appel">
            <input type="hidden" name="id_seance"   value="<?= (int)$idSeance ?>">

            <div class="eleves-list">
                <?php foreach ($eleves as $e): ?>
                <?php $p = $presences[$e['id_eleve']] ?? null; ?>
                <div class="eleve-row" id="row-<?= (int)$e['id_eleve'] ?>">
                    <div class="eleve-identity">
                        <div class="eleve-avatar">
                            <?= strtoupper(mb_substr($e['prenom'],0,1).mb_substr($e['nom'],0,1)) ?>
                        </div>
                        <div>
                            <div class="eleve-name">
                                <?= htmlspecialchars($e['nom'].' '.$e['prenom'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="eleve-matricule"><?= htmlspecialchars($e['matricule']) ?></div>
                        </div>
                    </div>

                    <!-- Boutons statut -->
                    <div class="statut-btns">
                        <?php foreach (['PRESENT','ABSENT','RETARD','EXCUSE'] as $s): ?>
                        <label class="statut-label statut-label--<?= strtolower($s) ?>">
                            <input type="radio"
                                   name="statut[<?= (int)$e['id_eleve'] ?>]"
                                   value="<?= $s ?>"
                                   <?= ($p && $p['statut_presence'] === $s) || (!$p && $s === 'PRESENT') ? 'checked' : '' ?>
                                   onchange="toggleMotif(<?= (int)$e['id_eleve'] ?>, this.value)">
                            <?= $statutLabels[$s] ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Motif absence -->
                    <div class="motif-wrap" id="motif-<?= (int)$e['id_eleve'] ?>"
                         style="<?= ($p && in_array($p['statut_presence'],['ABSENT','EXCUSE'])) ? '' : 'display:none' ?>">
                        <input type="text"
                               name="motif[<?= (int)$e['id_eleve'] ?>]"
                               placeholder="Motif (optionnel)"
                               value="<?= htmlspecialchars($p['motif_absence'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               maxlength="200">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="appel-submit">
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Enregistrer l'appel
                </button>
            </div>
        </form>
    </div>
    <?php elseif ($idSeance > 0 && empty($eleves)): ?>
        <div class="pres-empty-hint">Aucun élève dans cette classe.</div>
    <?php endif; ?>

</div>

<script>
function setAll(statut) {
    document.querySelectorAll(`input[type="radio"][value="${statut}"]`).forEach(r => {
        r.checked = true;
        const idEleve = r.name.match(/\[(\d+)\]/)[1];
        toggleMotif(idEleve, statut);
    });
}
function toggleMotif(idEleve, statut) {
    const div = document.getElementById('motif-' + idEleve);
    if (div) div.style.display = (statut === 'ABSENT' || statut === 'EXCUSE') ? 'block' : 'none';
}
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
