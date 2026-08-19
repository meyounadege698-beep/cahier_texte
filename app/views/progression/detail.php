<?php
$pageTitle = 'Programme : ' . htmlspecialchars($programme['titre_programme']) . ' — ' . APP_NAME;
$extraCss  = 'progression.css';
include APP_ROOT . '/app/views/layouts/header.php';

$estBrouillon = $programme['statut'] === 'BROUILLON';
$estPublie    = $programme['statut'] === 'PUBLIE';
?>

<?= Session::getFlash() ?>

<!-- En-tête programme -->
<div class="prog-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= APP_URL ?>/app.php?page=progression-officielle">Progression officielle</a>
            <span>›</span>
            <span><?= htmlspecialchars($programme['titre_programme'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <h1><?= htmlspecialchars($programme['titre_programme'], ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="prog-info-bar">
            <span>📚 <?= htmlspecialchars($programme['nom_matiere']) ?></span>
            <span>🏫 <?= htmlspecialchars($programme['nom_departement']) ?></span>
            <span>📅 <?= htmlspecialchars($programme['annee_scolaire']) ?></span>
            <?php if ($programme['volume_horaire_total']): ?>
                <span>⏱ <?= (int)$programme['volume_horaire_total'] ?>h prévues</span>
            <?php endif; ?>
            <span class="prog-badge prog-badge--<?= strtolower($programme['statut']) ?>">
                <?php
                $labels = ['BROUILLON' => '✏️ Brouillon', 'PUBLIE' => '✅ Publié', 'ARCHIVE' => '📦 Archivé'];
                echo $labels[$programme['statut']] ?? $programme['statut'];
                ?>
            </span>
        </div>
    </div>
    <div class="prog-header-actions">
        <?php if ($estBrouillon && !empty($chapitres)): ?>
            <form method="POST" action="<?= APP_URL ?>/app.php?page=progression-officielle-detail&id=<?= (int)$programme['id_programme'] ?>"
                  onsubmit="return confirm('Publier ce programme ? Les enseignants pourront l\'utiliser. Cette action est irréversible.')">
                <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                <input type="hidden" name="form_action"  value="publier">
                <button type="submit" class="btn btn-success">🚀 Publier le programme</button>
            </form>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/app.php?page=progression-officielle" class="btn btn-outline">← Retour</a>
    </div>
</div>

<?php if ($estPublie): ?>
    <div class="alert-success-banner">
        ✅ Ce programme est <strong>publié</strong>. Les enseignants peuvent sélectionner ces points lors de leurs séances.
        La modification des chapitres n'est plus possible.
    </div>
<?php endif; ?>

<div class="detail-layout">

    <!-- ===== COLONNE GAUCHE : liste des points ===== -->
    <div class="chapitres-list">
        <div class="section-header-row">
            <h2>Points du programme
                <span class="count-badge"><?= count($chapitres) ?></span>
            </h2>
        </div>

        <?php if (empty($chapitres)): ?>
            <div class="empty-state-small">
                <p>Aucun point de programme pour l'instant.</p>
                <p>Utilisez le formulaire ci-contre pour ajouter votre premier chapitre.</p>
            </div>
        <?php else: ?>
            <div class="chapitre-items">
                <?php foreach ($chapitres as $ch): ?>
                <div class="chapitre-item">
                    <div class="chapitre-num">
                        <?= (int)$ch['ordre_chapitre'] ?>
                    </div>
                    <div class="chapitre-body">
                        <div class="chapitre-titre">
                            <?= htmlspecialchars($ch['titre_chapitre'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if ($ch['objectifs_pedagogiques']): ?>
                            <div class="chapitre-objectifs">
                                🎯 <?= htmlspecialchars($ch['objectifs_pedagogiques'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($ch['description']): ?>
                            <div class="chapitre-desc">
                                <?= htmlspecialchars($ch['description'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <div class="chapitre-meta">
                            <?php if ($ch['volume_horaire_prevu']): ?>
                                <span>⏱ <?= (int)$ch['volume_horaire_prevu'] ?>h</span>
                            <?php endif; ?>
                            <?php if ($ch['duree_semaines']): ?>
                                <span>📆 <?= (int)$ch['duree_semaines'] ?> sem.</span>
                            <?php endif; ?>
                            <span>📝 <?= (int)$ch['nb_lecons'] ?> leçon<?= $ch['nb_lecons'] > 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                    <?php if ($estBrouillon): ?>
                    <div class="chapitre-actions">
                        <form method="POST"
                              action="<?= APP_URL ?>/app.php?page=progression-officielle-detail&id=<?= (int)$programme['id_programme'] ?>"
                              onsubmit="return confirm('Supprimer ce point du programme ?')">
                            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id_chapitre"  value="<?= (int)$ch['id_chapitre'] ?>">
                            <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
                            <input type="hidden" name="form_action"  value="delete_chapitre">
                            <button type="submit" class="btn-icon btn-icon--danger" title="Supprimer">🗑</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== COLONNE DROITE : formulaire ajout ===== -->
    <?php if ($estBrouillon): ?>
    <div class="add-chapitre-panel">
        <h2>➕ Ajouter un point</h2>
        <p class="panel-hint">Exemple : <em>Chapitre 1 : Les nombres complexes</em></p>

        <form method="POST"
              action="<?= APP_URL ?>/app.php?page=progression-officielle-detail&id=<?= (int)$programme['id_programme'] ?>">
            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_programme" value="<?= (int)$programme['id_programme'] ?>">
            <input type="hidden" name="form_action"  value="add_chapitre">

            <!-- Titre (obligatoire) -->
            <div class="form-group">
                <label for="titre_chapitre">Titre du point <span class="required">*</span></label>
                <input type="text" id="titre_chapitre" name="titre_chapitre"
                       placeholder="Ex : Chapitre 3 : Intégrales"
                       required maxlength="200"
                       autocomplete="off">
            </div>

            <!-- Objectifs pédagogiques -->
            <div class="form-group">
                <label for="objectifs_pedagogiques">Objectifs pédagogiques <span class="optional">(optionnel)</span></label>
                <textarea id="objectifs_pedagogiques" name="objectifs_pedagogiques"
                          rows="2"
                          placeholder="Ce que l'élève doit maîtriser..."></textarea>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description <span class="optional">(optionnel)</span></label>
                <textarea id="description" name="description"
                          rows="2"
                          placeholder="Détails supplémentaires..."></textarea>
            </div>

            <!-- Volume horaire + durée côte à côte -->
            <div class="form-row">
                <div class="form-group">
                    <label for="volume_horaire_prevu">Heures prévues</label>
                    <input type="number" id="volume_horaire_prevu" name="volume_horaire_prevu"
                           min="1" max="200" placeholder="Ex : 20">
                </div>
                <div class="form-group">
                    <label for="duree_semaines">Durée (semaines)</label>
                    <input type="number" id="duree_semaines" name="duree_semaines"
                           min="1" max="40" placeholder="Ex : 4">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                ➕ Ajouter ce point
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="add-chapitre-panel add-chapitre-panel--locked">
        <div class="locked-icon">🔒</div>
        <h3>Programme publié</h3>
        <p>La modification des points n'est plus possible une fois le programme publié.</p>
    </div>
    <?php endif; ?>

</div>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
