<?php
$pageTitle = 'Wizard — ' . htmlspecialchars($programme['titre_programme']) . ' — ' . APP_NAME;
$extraCss  = 'progression_v2.css';
include APP_ROOT . '/app/views/layouts/header.php';

$estPublie    = $programme['statut'] === 'PUBLIE';
$estBrouillon = $programme['statut'] === 'BROUILLON';

// Nombre total de semaines déjà saisies
$nbSemaines  = count($progression);
$nbChapitres = array_sum(array_map(fn($s) => count($s['chapitres'] ?? []), $progression));
$nbLecons    = array_sum(array_map(fn($s) =>
    array_sum(array_map(fn($ch) => count($ch['lecons'] ?? []), $s['chapitres'] ?? [])),
    $progression));

// Calcul du prochain numéro de semaine
$prochainNumero = $nbSemaines + 1;
?>
<?= Session::getFlash() ?>

<!-- ── En-tête programme ── -->
<div class="pv2-wiz-header">
    <div class="pv2-wiz-breadcrumb">
        <a href="<?= APP_URL ?>/app.php?page=prog-officielle-v2">Programmes</a>
        <span>›</span>
        <span><?= htmlspecialchars($programme['titre_programme'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="pv2-wiz-meta">
        <div class="pv2-wiz-title-row">
            <h1><?= htmlspecialchars($programme['titre_programme'], ENT_QUOTES, 'UTF-8') ?></h1>
            <span class="pv2-status-badge <?= $estPublie ? 'badge--pub' : 'badge--draft' ?>">
                <?= $estPublie ? '✅ Publié' : '✏️ Brouillon' ?>
            </span>
        </div>
        <div class="pv2-wiz-info-bar">
            <span>🏛️ <?= htmlspecialchars($programme['nom_departement']) ?></span>
            <span>📚 <?= htmlspecialchars($programme['nom_matiere']) ?></span>
            <span>📅 <?= htmlspecialchars($programme['annee_scolaire']) ?></span>
            <?php if ($programme['volume_horaire_total']): ?>
                <span>⏱ <?= (int)$programme['volume_horaire_total'] ?>h</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="pv2-wiz-header-actions">
        <?php if ($estBrouillon && $nbLecons > 0): ?>
        <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-publier"
              onsubmit="return confirm('Publier ce programme ? Cette action est irréversible.')">
            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
            <button type="submit" class="btn btn-success">🚀 Publier le programme</button>
        </form>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/app.php?page=prog-officielle-v2" class="btn btn-outline">← Retour</a>
    </div>
</div>

<!-- ── Stats rapides ── -->
<div class="pv2-wiz-stats">
    <div class="pv2-stat"><span class="pv2-stat-n"><?= $nbSemaines ?></span><span class="pv2-stat-l">Semaines</span></div>
    <div class="pv2-stat"><span class="pv2-stat-n"><?= $nbChapitres ?></span><span class="pv2-stat-l">Chapitres</span></div>
    <div class="pv2-stat"><span class="pv2-stat-n"><?= $nbLecons ?></span><span class="pv2-stat-l">Leçons</span></div>
</div>

<?php if ($estPublie): ?>
<div class="pv2-pub-banner">
    ✅ Ce programme est publié. La modification n'est plus possible.
    Vous pouvez attribuer ce programme à des enseignants ci-dessous.
</div>
<?php endif; ?>

<!-- ================================================================
     LAYOUT PRINCIPAL : formulaire gauche | tableau temps réel droite
================================================================ -->
<div class="pv2-wiz-layout">

    <!-- ── COLONNE GAUCHE : formulaires de saisie ── -->
    <?php if ($estBrouillon): ?>
    <div class="pv2-wiz-forms">

        <!-- SECTION A : Ajouter une semaine -->
        <div class="pv2-panel" id="panel-semaine">
            <div class="pv2-panel-header" onclick="togglePanel('semaine')">
                <span>📅 Ajouter une semaine</span>
                <span class="pv2-panel-arrow" id="arrow-semaine">▼</span>
            </div>
            <div class="pv2-panel-body" id="body-semaine">
                <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-semaine">
                    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                    <div class="pv2-form-row">
                        <div class="pv2-form-group">
                            <label>N° semaine <span class="req">*</span></label>
                            <input type="number" name="numero_semaine" min="1" max="52"
                                   value="<?= $prochainNumero ?>" required>
                        </div>
                        <div class="pv2-form-group">
                            <label>Du <span class="req">*</span></label>
                            <input type="date" name="date_debut" required
                                   id="inp-date-debut" onchange="calcDateFin()">
                        </div>
                        <div class="pv2-form-group">
                            <label>Au <span class="req">*</span></label>
                            <input type="date" name="date_fin" required id="inp-date-fin">
                        </div>
                    </div>
                    <div class="pv2-form-group">
                        <label>Titre de la période <span class="opt">(optionnel)</span></label>
                        <input type="text" name="titre_periode" placeholder="Ex : Séquence 1 — Nombres complexes" maxlength="200">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">➕ Ajouter la semaine</button>
                </form>
            </div>
        </div>

        <!-- SECTION B : Ajouter un chapitre -->
        <div class="pv2-panel" id="panel-chapitre">
            <div class="pv2-panel-header" onclick="togglePanel('chapitre')">
                <span>📖 Ajouter un chapitre à une semaine</span>
                <span class="pv2-panel-arrow" id="arrow-chapitre">▼</span>
            </div>
            <div class="pv2-panel-body" id="body-chapitre">
                <?php if (empty($progression)): ?>
                    <p class="pv2-hint">⚠️ Ajoutez d'abord une semaine.</p>
                <?php else: ?>
                <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-chapitre">
                    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                    <div class="pv2-form-group">
                        <label>Semaine <span class="req">*</span></label>
                        <select name="id_semaine" required>
                            <option value="">— Choisir une semaine —</option>
                            <?php foreach ($progression as $s): ?>
                                <option value="<?= (int)$s['id_semaine'] ?>">
                                    Semaine <?= (int)$s['numero_semaine'] ?> :
                                    <?= date('d/m/Y', strtotime($s['date_debut'])) ?> → <?= date('d/m/Y', strtotime($s['date_fin'])) ?>
                                    <?= $s['titre_periode'] ? '— '.$s['titre_periode'] : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pv2-form-group">
                        <label>Titre du chapitre <span class="req">*</span></label>
                        <input type="text" name="titre_chapitre" required maxlength="200"
                               placeholder="Ex : CHAPITRE 1 : Nombres complexes">
                    </div>
                    <div class="pv2-form-group">
                        <label>Compétences visées <span class="opt">(phrase courte)</span></label>
                        <input type="text" name="competences_semaine" maxlength="300"
                               placeholder="Ex : Maîtriser la définition et les opérations sur les nombres complexes">
                    </div>
                    <div class="pv2-form-row">
                        <div class="pv2-form-group">
                            <label>Volume horaire (h) <span class="opt"></span></label>
                            <input type="number" name="volume_horaire_prevu" min="1" max="100" placeholder="Ex : 6">
                        </div>
                        <div class="pv2-form-group">
                            <label>Durée (semaines) <span class="opt"></span></label>
                            <input type="number" name="duree_semaines" min="1" max="20" placeholder="Ex : 2">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">➕ Ajouter le chapitre</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION C : Ajouter une leçon -->
        <div class="pv2-panel" id="panel-lecon">
            <div class="pv2-panel-header" onclick="togglePanel('lecon')">
                <span>🎯 Ajouter une leçon</span>
                <span class="pv2-panel-arrow" id="arrow-lecon">▼</span>
            </div>
            <div class="pv2-panel-body" id="body-lecon">
                <?php
                // Flatten : tous les chapitres de toutes les semaines
                $tousChapitres = [];
                foreach ($progression as $s) {
                    foreach (($s['chapitres'] ?? []) as $ch) {
                        $tousChapitres[] = $ch + ['semaine_num' => $s['numero_semaine']];
                    }
                }
                ?>
                <?php if (empty($tousChapitres)): ?>
                    <p class="pv2-hint">⚠️ Ajoutez d'abord un chapitre.</p>
                <?php else: ?>
                <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-lecon">
                    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                    <div class="pv2-form-group">
                        <label>Chapitre <span class="req">*</span></label>
                        <select name="id_chapitre" id="selChapitreLecon" required onchange="updateLeconCount(this)">
                            <option value="">— Choisir un chapitre —</option>
                            <?php foreach ($tousChapitres as $ch): ?>
                                <option value="<?= (int)$ch['id_chapitre'] ?>"
                                        data-nb="<?= (int)($ch['nb_lecons'] ?? 0) ?>">
                                    Sem. <?= $ch['semaine_num'] ?> → <?= htmlspecialchars($ch['titre_chapitre']) ?>
                                    (<?= (int)($ch['nb_lecons']??0) ?> leç.)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pv2-form-row">
                        <div class="pv2-form-group" style="flex:2">
                            <label>Titre de la leçon <span class="req">*</span></label>
                            <input type="text" name="titre_lecon" required maxlength="200"
                                   placeholder="Ex : Leçon 1 : Définition et formes d'un nombre complexe">
                        </div>
                    </div>
                    <div class="pv2-form-group">
                        <label>Grand titre (intitulé officiel) <span class="opt"></span></label>
                        <input type="text" name="grand_titre" maxlength="300"
                               placeholder="Ex : LES NOMBRES COMPLEXES">
                    </div>
                    <div class="pv2-form-row">
                        <div class="pv2-form-group">
                            <label>Type de leçon <span class="req">*</span></label>
                            <div class="pv2-type-btns">
                                <label class="pv2-type-btn">
                                    <input type="radio" name="type_lecon" value="theorique" checked>
                                    <span>📘 Théorique</span>
                                </label>
                                <label class="pv2-type-btn">
                                    <input type="radio" name="type_lecon" value="pratique">
                                    <span>🔬 Pratique</span>
                                </label>
                                <label class="pv2-type-btn">
                                    <input type="radio" name="type_lecon" value="theorique_pratique">
                                    <span>📘🔬 Mixte</span>
                                </label>
                            </div>
                        </div>
                        <div class="pv2-form-group">
                            <label>Nombre d'heures <span class="req">*</span></label>
                            <input type="number" name="nb_heures" min="0.5" max="20" step="0.5"
                                   placeholder="Ex : 2" required>
                        </div>
                    </div>
                    <div class="pv2-form-group">
                        <label>Objectifs pédagogiques généraux <span class="opt">(résumé)</span></label>
                        <textarea name="objectifs_pedagogiques" rows="2"
                                  placeholder="Ex : À la fin de cette leçon, l'élève doit savoir définir un nombre complexe..."></textarea>
                    </div>
                    <div class="pv2-form-row">
                        <div class="pv2-form-group">
                            <label>Prérequis <span class="opt"></span></label>
                            <input type="text" name="prerequis" placeholder="Ex : Nombres réels" maxlength="200">
                        </div>
                        <div class="pv2-form-group">
                            <label>Mots-clés <span class="opt"></span></label>
                            <input type="text" name="mots_cles" placeholder="Ex : complexe, module, argument" maxlength="200">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">➕ Ajouter la leçon</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION D : Ajouter un objectif pédagogique -->
        <div class="pv2-panel" id="panel-objectif">
            <div class="pv2-panel-header" onclick="togglePanel('objectif')">
                <span>✅ Ajouter un objectif pédagogique</span>
                <span class="pv2-panel-arrow" id="arrow-objectif">▼</span>
            </div>
            <div class="pv2-panel-body" id="body-objectif">
                <?php
                $toutesLecons = [];
                foreach ($progression as $s) {
                    foreach (($s['chapitres'] ?? []) as $ch) {
                        foreach (($ch['lecons'] ?? []) as $l) {
                            $toutesLecons[] = $l + [
                                'titre_chapitre' => $ch['titre_chapitre'],
                                'semaine_num'    => $s['numero_semaine']
                            ];
                        }
                    }
                }
                ?>
                <?php if (empty($toutesLecons)): ?>
                    <p class="pv2-hint">⚠️ Ajoutez d'abord une leçon.</p>
                <?php else: ?>
                <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-objectif">
                    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                    <div class="pv2-form-group">
                        <label>Leçon <span class="req">*</span></label>
                        <select name="id_lecon" required>
                            <option value="">— Choisir une leçon —</option>
                            <?php foreach ($toutesLecons as $l): ?>
                                <option value="<?= (int)$l['id_leçon'] ?>">
                                    Sem.<?= $l['semaine_num'] ?> › <?= htmlspecialchars(mb_substr($l['titre_chapitre'],0,25)) ?> › <?= htmlspecialchars(mb_substr($l['titre_leçon'],0,35)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pv2-form-row">
                        <div class="pv2-form-group" style="flex:2">
                            <label>Libellé de l'objectif <span class="req">*</span></label>
                            <input type="text" name="libelle" required maxlength="500"
                                   placeholder="Ex : Définir un nombre complexe sous forme algébrique">
                        </div>
                        <div class="pv2-form-group">
                            <label>Type</label>
                            <select name="type_objectif">
                                <option value="savoir_faire">Savoir-faire</option>
                                <option value="savoir">Savoir</option>
                                <option value="savoir_etre">Savoir-être</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">➕ Ajouter l'objectif</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /pv2-wiz-forms -->
    <?php else: ?>
    <!-- Programme publié : panneau d'attribution -->
    <div class="pv2-wiz-forms">
        <?php
        require_once APP_ROOT . '/app/models/ProgressionOfficielleV2Model.php';
        $enseignantsAffectables = (new ProgressionOfficielleV2Model())
            ->getEnseignantsAffectesAuProgramme((int)$programme['id_programme']);
        ?>
        <div class="pv2-panel" id="panel-attribution">
            <div class="pv2-panel-header" onclick="togglePanel('attribution')">
                <span>👨‍🏫 Attribuer à un enseignant</span>
                <span class="pv2-panel-arrow" id="arrow-attribution">▼</span>
            </div>
            <div class="pv2-panel-body" id="body-attribution">
                <?php if (empty($enseignantsAffectables)): ?>
                    <p class="pv2-hint">⚠️ Aucun enseignant affecté à cette matière. Allez dans
                    <a href="<?= APP_URL ?>/app.php?page=gestion-affectations">Affectations</a>.</p>
                <?php else: ?>
                <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-attribuer">
                    <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                    <div class="pv2-form-group">
                        <label>Enseignant <span class="req">*</span></label>
                        <select name="id_enseignant" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($enseignantsAffectables as $e): ?>
                                <option value="<?= (int)$e['id_utilisateur'] ?>">
                                    <?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?>
                                    <?= $e['deja_attribue'] > 0 ? '✅ déjà attribué' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pv2-form-group">
                        <label>Classe <span class="req">*</span></label>
                        <select name="id_classe" required>
                            <option value="">— Sélectionner —</option>
                            <?php
                            $classesVues = [];
                            foreach ($enseignantsAffectables as $e):
                                if (in_array($e['id_classe'], $classesVues)) continue;
                                $classesVues[] = $e['id_classe'];
                            ?>
                                <option value="<?= (int)$e['id_classe'] ?>">
                                    <?= htmlspecialchars($e['nom_classe'].' ('.$e['niveau'].')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">📤 Attribuer le programme</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── COLONNE DROITE : tableau de progression temps réel ── -->
    <div class="pv2-wiz-preview" id="pv2Preview">
        <div class="pv2-preview-header">
            <span>📊 Tableau de progression</span>
            <span class="pv2-preview-count" id="previewCount">
                <?= $nbSemaines ?> sem. · <?= $nbChapitres ?> chap. · <?= $nbLecons ?> leç.
            </span>
        </div>

        <div id="previewContent">
            <?php if (empty($progression)): ?>
            <div class="pv2-preview-empty" id="previewEmpty">
                <div style="font-size:48px;margin-bottom:12px">📋</div>
                <p>Le tableau de progression s'affichera ici au fur et à mesure de votre saisie.</p>
            </div>
            <?php else: ?>
            <?php foreach ($progression as $s): ?>
            <div class="pv2-sem-block" data-semaine="<?= (int)$s['id_semaine'] ?>">
                <div class="pv2-sem-header">
                    <div class="pv2-sem-info">
                        <span class="pv2-sem-num">S<?= (int)$s['numero_semaine'] ?></span>
                        <div>
                            <div class="pv2-sem-dates">
                                📅 <?= date('d/m/Y', strtotime($s['date_debut'])) ?> → <?= date('d/m/Y', strtotime($s['date_fin'])) ?>
                            </div>
                            <?php if ($s['titre_periode']): ?>
                                <div class="pv2-sem-titre"><?= htmlspecialchars($s['titre_periode']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($estBrouillon): ?>
                    <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-del-semaine"
                          onsubmit="return confirm('Supprimer cette semaine et tout son contenu ?')">
                        <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id_semaine"   value="<?= (int)$s['id_semaine'] ?>">
                        <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                        <button type="submit" class="pv2-del-btn" title="Supprimer la semaine">🗑</button>
                    </form>
                    <?php endif; ?>
                </div>

                <?php foreach (($s['chapitres'] ?? []) as $ch): ?>
                <div class="pv2-ch-block">
                    <div class="pv2-ch-header">
                        <div class="pv2-ch-info">
                            <span class="pv2-ch-num"><?= (int)$ch['ordre_chapitre'] ?></span>
                            <div>
                                <div class="pv2-ch-titre"><?= htmlspecialchars($ch['titre_chapitre'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($ch['competences_semaine']): ?>
                                    <div class="pv2-ch-comp">🎯 <?= htmlspecialchars($ch['competences_semaine']) ?></div>
                                <?php endif; ?>
                                <div class="pv2-ch-meta">
                                    <?php if ($ch['volume_horaire_prevu']): ?>
                                        <span>⏱ <?= (int)$ch['volume_horaire_prevu'] ?>h</span>
                                    <?php endif; ?>
                                    <span>📝 <?= (int)($ch['nb_lecons']??0) ?> leçon<?= ($ch['nb_lecons']??0)>1?'s':'' ?></span>
                                    <span>⏱ <?= (int)($ch['total_heures']??0) ?>h total</span>
                                </div>
                            </div>
                        </div>
                        <?php if ($estBrouillon): ?>
                        <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-del-chapitre"
                              onsubmit="return confirm('Supprimer ce chapitre et ses leçons ?')">
                            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id_chapitre"  value="<?= (int)$ch['id_chapitre'] ?>">
                            <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                            <button type="submit" class="pv2-del-btn" title="Supprimer">🗑</button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <?php foreach (($ch['lecons'] ?? []) as $l): ?>
                    <div class="pv2-lecon-item">
                        <div class="pv2-lecon-left">
                            <span class="pv2-lecon-type pv2-type-<?= $l['type_lecon'] ?>">
                                <?= $l['type_lecon'] === 'theorique' ? '📘' : ($l['type_lecon'] === 'pratique' ? '🔬' : '📘🔬') ?>
                            </span>
                            <div class="pv2-lecon-body">
                                <div class="pv2-lecon-titre">
                                    Leçon <?= (int)$l['ordre_leçon'] ?> : <?= htmlspecialchars($l['titre_leçon'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <?php if ($l['grand_titre']): ?>
                                    <div class="pv2-lecon-grand"><?= htmlspecialchars($l['grand_titre']) ?></div>
                                <?php endif; ?>
                                <div class="pv2-lecon-meta">
                                    <?php if ($l['nb_heures']): ?><span>⏱ <?= $l['nb_heures'] ?>h</span><?php endif; ?>
                                    <span><?= count($l['objectifs'] ?? []) ?> objectif<?= count($l['objectifs']??[])>1?'s':'' ?></span>
                                </div>
                                <?php if (!empty($l['objectifs'])): ?>
                                <ul class="pv2-obj-list">
                                    <?php foreach ($l['objectifs'] as $obj): ?>
                                    <li class="pv2-obj-item">
                                        <span class="pv2-obj-type pv2-obj-<?= $obj['type_objectif'] ?>">
                                            <?= $obj['type_objectif'] === 'savoir' ? 'S' : ($obj['type_objectif'] === 'savoir_faire' ? 'SF' : 'SE') ?>
                                        </span>
                                        <span><?= htmlspecialchars($obj['libelle'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($estBrouillon): ?>
                                        <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-del-objectif" style="display:inline">
                                            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id_objectif"  value="<?= (int)$obj['id_objectif'] ?>">
                                            <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                                            <button type="submit" class="pv2-del-obj-btn" title="Supprimer">✕</button>
                                        </form>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($estBrouillon): ?>
                        <form method="POST" action="<?= APP_URL ?>/app.php?page=prog-officielle-v2-del-lecon"
                              onsubmit="return confirm('Supprimer cette leçon et ses objectifs ?')">
                            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id_lecon"     value="<?= (int)$l['id_leçon'] ?>">
                            <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                            <button type="submit" class="pv2-del-btn" title="Supprimer">🗑</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div><!-- /previewContent -->
    </div><!-- /pv2-wiz-preview -->

</div><!-- /pv2-wiz-layout -->

<script>
const PROG_ID  = <?= (int)$programme['id_programme'] ?>;
const APP_URL  = '<?= APP_URL ?>';
const IS_DRAFT = <?= $estBrouillon ? 'true' : 'false' ?>;

// ── Toggle panneaux ──
function togglePanel(name) {
    const body  = document.getElementById('body-' + name);
    const arrow = document.getElementById('arrow-' + name);
    if (!body) return;
    const open = body.classList.toggle('open');
    arrow.textContent = open ? '▲' : '▼';
}
// Ouvrir le premier panneau par défaut
document.addEventListener('DOMContentLoaded', () => {
    const first = document.querySelector('.pv2-panel-body');
    if (first) { first.classList.add('open'); }
    const firstArrow = document.querySelector('.pv2-panel-arrow');
    if (firstArrow) firstArrow.textContent = '▲';
});

// ── Calcul automatique date fin (vendredi de la semaine du lundi) ──
function calcDateFin() {
    const dd = document.getElementById('inp-date-debut');
    const df = document.getElementById('inp-date-fin');
    if (!dd.value) return;
    const d = new Date(dd.value);
    const day = d.getDay(); // 0=dim, 1=lun...
    // Aller au vendredi de la même semaine
    const diff = day <= 5 ? (5 - day) : (5 + 7 - day);
    const fin = new Date(d);
    fin.setDate(fin.getDate() + diff);
    df.value = fin.toISOString().split('T')[0];
}

// ── Mise à jour compteur ──
function updateLeconCount(sel) { /* pour future extension */ }
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
