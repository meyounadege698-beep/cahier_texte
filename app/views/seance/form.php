<?php
$pageTitle = 'Saisie de séance — ' . APP_NAME;
$extraCss  = 'seance.css';
include APP_ROOT . '/app/views/layouts/header.php';

$selectedChapitre = (int)($_GET['selected_chapitre'] ?? $old['id_chapitre'] ?? 0);
$selectedClasse   = (int)($_GET['classe']  ?? $old['id_classe']  ?? 0);
$selectedMatiere  = (int)($_GET['matiere'] ?? $old['id_matiere'] ?? 0);
?>

<?= Session::getFlash() ?>

<!-- Bannière réutilisation -->
<?php if (!empty($isReuse)): ?>
<div class="reuse-banner">
    ♻️ Vous réutilisez une séance existante. Le contenu a été pré-rempli — modifiez-le selon vos besoins.
    <a href="<?= APP_URL ?>/app.php?page=saisie-seance">Recommencer à zéro</a>
</div>
<?php endif; ?>

<div class="seance-header">
    <div>
        <h1>✏️ Saisie de séance</h1>
        <p>Enregistrez le contenu de votre cours et le point du programme traité.</p>
    </div>
</div>

<div class="seance-layout">

    <!-- ===== FORMULAIRE PRINCIPAL ===== -->
    <div class="seance-form-card">
        <form id="formSeance" method="POST"
              action="<?= APP_URL ?>/app.php?page=saisie-seance"
              enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action"  value="save">

            <!-- ── ÉTAPE 1 : Classe & Matière ── -->
            <div class="form-step">
                <div class="step-label"><span class="step-num">1</span> Classe et matière</div>

                <?php if (empty($classes)): ?>
                    <div class="alert-warning">
                        ⚠️ Aucune classe ne vous est affectée pour l'année <?= htmlspecialchars($anneeCourante ?? '') ?>.
                        Contactez l'administrateur.
                    </div>
                <?php else: ?>

                <div class="form-row">
                    <!-- Classe -->
                    <div class="form-group">
                        <label for="id_classe">Classe <span class="required">*</span></label>
                        <select id="id_classe" name="id_classe" required
                                onchange="onClasseChange(this.value)">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= (int)$cl['id_classe'] ?>"
                                    <?= $selectedClasse === (int)$cl['id_classe'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cl['nom_classe']) ?>
                                    <?php if ($cl['filiere']): ?>
                                        — <?= htmlspecialchars($cl['filiere']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Matière (chargée dynamiquement) -->
                    <div class="form-group">
                        <label for="id_matiere">Matière <span class="required">*</span></label>
                        <select id="id_matiere" name="id_matiere" required
                                onchange="onMatiereChange(this.value)"
                                <?= empty($matieres) ? 'disabled' : '' ?>>
                            <option value="">— Choisir d'abord une classe —</option>
                            <?php foreach ($matieres as $mat): ?>
                                <option value="<?= (int)$mat['id_matiere'] ?>"
                                    <?= $selectedMatiere === (int)$mat['id_matiere'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mat['nom_matiere']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php endif; ?>
            </div>

            <!-- ── ÉTAPE 2 : Point du programme ── -->
            <div class="form-step">
                <div class="step-label"><span class="step-num">2</span> Point du programme traité</div>

                <div class="form-group">
                    <label for="id_chapitre">
                        Sélectionner le point traité
                        <span class="optional">(laissez vide si hors programme)</span>
                    </label>
                    <select id="id_chapitre" name="id_chapitre"
                            <?= empty($points) ? 'disabled' : '' ?>>
                        <option value="">— Sélectionner d'abord une matière —</option>
                        <?php foreach ($points as $pt): ?>
                            <option value="<?= (int)$pt['id_chapitre'] ?>"
                                <?= $selectedChapitre === (int)$pt['id_chapitre'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pt['titre_chapitre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Objectifs du point sélectionné (affiché dynamiquement) -->
                <div id="objectifs-point" class="objectifs-hint" style="display:none"></div>

                <!-- Bouton point manquant -->
                <?php if ($programmeActif): ?>
                <div class="point-manquant-zone">
                    <button type="button" class="btn-add-point" onclick="togglePointManquant()">
                        ＋ Ajouter un point manquant au programme
                    </button>
                    <div id="point-manquant-panel" class="point-manquant-panel" style="display:none">
                        <p class="panel-info">
                            📢 Ce point sera ajouté au programme officiel publié
                            <strong>« <?= htmlspecialchars($programmeActif['titre_programme']) ?> »</strong>
                            et sera disponible pour tous les enseignants.
                        </p>
                        <input type="hidden" name="id_programme_manquant"
                               value="<?= (int)$programmeActif['id_programme'] ?>">
                        <div class="form-group">
                            <label for="titre_point_manquant">Titre du point <span class="required">*</span></label>
                            <input type="text" id="titre_point_manquant"
                                   name="titre_point_manquant"
                                   placeholder="Ex : Chapitre 4 : Les suites numériques"
                                   maxlength="200">
                        </div>
                        <div class="form-group">
                            <label for="objectifs_point_manquant">Objectifs pédagogiques <span class="optional">(optionnel)</span></label>
                            <textarea id="objectifs_point_manquant"
                                      name="objectifs_point_manquant"
                                      rows="2"
                                      placeholder="Ce que l'élève devra maîtriser..."></textarea>
                        </div>
                        <div class="point-manquant-actions">
                            <button type="button" class="btn btn-outline btn-sm"
                                    onclick="togglePointManquant()">Annuler</button>
                            <button type="button" class="btn btn-warning btn-sm"
                                    onclick="soumettrePointManquant()">
                                ➕ Ajouter au programme officiel
                            </button>
                        </div>
                    </div>
                </div>
                <?php elseif ($selectedMatiere > 0): ?>
                <div class="no-programme-warning">
                    ⚠️ Aucun programme officiel publié pour cette matière.
                    Contactez le censeur pour publier le programme avant de pouvoir sélectionner un point.
                </div>
                <?php endif; ?>
            </div>

            <!-- ── ÉTAPE 3 : Date & Horaires ── -->
            <div class="form-step">
                <div class="step-label"><span class="step-num">3</span> Date et horaires</div>
                <div class="form-row form-row--3">
                    <div class="form-group">
                        <label for="date_seance">Date <span class="required">*</span></label>
                        <input type="date" id="date_seance" name="date_seance"
                               value="<?= htmlspecialchars($old['date_seance'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                               max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="heure_debut">Heure début <span class="required">*</span></label>
                        <input type="time" id="heure_debut" name="heure_debut"
                               value="<?= htmlspecialchars($old['heure_debut'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="heure_fin">Heure fin <span class="required">*</span></label>
                        <input type="time" id="heure_fin" name="heure_fin"
                               value="<?= htmlspecialchars($old['heure_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>
                </div>
            </div>

            <!-- ── ÉTAPE 4 : Contenu pédagogique ── -->
            <div class="form-step">
                <div class="step-label"><span class="step-num">4</span> Contenu pédagogique</div>

                <div class="form-group">
                    <label for="contenu_traite">Contenu traité <span class="required">*</span></label>
                    <textarea id="contenu_traite" name="contenu_traite" rows="4"
                              placeholder="Décrivez ce qui a été fait pendant cette séance..."
                              required><?= htmlspecialchars($old['contenu_traite'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="objectifs_atteints">Objectifs atteints <span class="optional">(optionnel)</span></label>
                    <textarea id="objectifs_atteints" name="objectifs_atteints" rows="2"
                              placeholder="Quels objectifs ont été atteints ?"><?= htmlspecialchars($old['objectifs_atteints'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="commentaire_enseignant">Commentaire personnel <span class="optional">(optionnel)</span></label>
                    <textarea id="commentaire_enseignant" name="commentaire_enseignant" rows="2"
                              placeholder="Difficultés rencontrées, remarques..."><?= htmlspecialchars($old['commentaire_enseignant'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <!-- ── ÉTAPE 5 : Pièces jointes ── -->
            <div class="form-step">
                <div class="step-label"><span class="step-num">5</span> Pièces jointes <span class="optional">(optionnel)</span></div>

                <div class="upload-dropzone" id="dz-form"
                     onclick="document.getElementById('form-files').click()"
                     ondragover="event.preventDefault(); this.classList.add('dragover')"
                     ondragleave="this.classList.remove('dragover')"
                     ondrop="handleDropForm(event)">
                    <div class="dz-icon">📎</div>
                    <p>Cliquez ou glissez-déposez vos fichiers</p>
                    <small>PDF, Word, Excel, PowerPoint, Images — max <?= UPLOAD_MAX_MB ?> Mo/fichier</small>
                    <div id="dz-preview-form" class="dz-preview"></div>
                </div>
                <input type="file" id="form-files" name="fichiers[]" multiple
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip"
                       style="display:none"
                       onchange="previewFormFiles(this)">
            </div>

            <div class="form-submit-row">
                <a href="<?= APP_URL ?>/app.php?page=dashboard" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Enregistrer la séance
                </button>
            </div>

        </form>

        <!-- Formulaire caché pour soumettre le point manquant -->
        <form id="formPointManquant" method="POST"
              action="<?= APP_URL ?>/app.php?page=saisie-seance" style="display:none">
            <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action"  value="ajouter_point">
            <input type="hidden" name="id_programme" id="pm_id_programme">
            <input type="hidden" name="id_matiere"   id="pm_id_matiere">
            <input type="hidden" name="id_classe"    id="pm_id_classe">
            <input type="hidden" name="titre_point"  id="pm_titre">
            <input type="hidden" name="objectifs_point" id="pm_objectifs">
        </form>
    </div>

    <!-- ===== COLONNE DROITE : séances récentes ===== -->
    <div class="seances-recentes">
        <h2>🕐 Séances récentes</h2>
        <a href="<?= APP_URL ?>/app.php?page=bibliotheque-seances" class="btn-biblio-link">
            📚 Voir toute la bibliothèque →
        </a>
        <?php if (empty($seancesRecentes)): ?>
            <p class="empty-hint">Aucune séance ces 7 derniers jours.</p>
        <?php else: ?>
            <div class="seance-list">
                <?php foreach ($seancesRecentes as $s): ?>
                <div class="seance-item">
                    <div class="seance-date">
                        <?= date('d/m', strtotime($s['date_seance'])) ?>
                    </div>
                    <div class="seance-info">
                        <div class="seance-classe">
                            <?= htmlspecialchars($s['nom_classe']) ?> —
                            <?= htmlspecialchars($s['nom_matiere']) ?>
                        </div>
                        <?php if ($s['titre_chapitre']): ?>
                        <div class="seance-point">
                            📋 <?= htmlspecialchars($s['titre_chapitre']) ?>
                        </div>
                        <?php endif; ?>
                        <div class="seance-horaire">
                            🕐 <?= substr($s['heure_debut'], 0, 5) ?> – <?= substr($s['heure_fin'], 0, 5) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
const APP_URL = '<?= APP_URL ?>';
// Points chargés en mémoire pour affichage des objectifs
let pointsCache = {};
let programmeActifId = <?= $programmeActif ? (int)$programmeActif['id_programme'] : 0 ?>;

// ── Changement de classe → recharge les matières ──
function onClasseChange(idClasse) {
    const selMatiere = document.getElementById('id_matiere');
    const selChapitre = document.getElementById('id_chapitre');
    selMatiere.innerHTML  = '<option value="">Chargement...</option>';
    selMatiere.disabled   = true;
    selChapitre.innerHTML = '<option value="">— Sélectionner d\'abord une matière —</option>';
    selChapitre.disabled  = true;
    hideObjectifs();

    if (!idClasse) {
        selMatiere.innerHTML = '<option value="">— Choisir d\'abord une classe —</option>';
        return;
    }

    fetch(`${APP_URL}/app.php?page=api-matieres-classe&classe=${idClasse}`)
        .then(r => r.json())
        .then(data => {
            selMatiere.innerHTML = '<option value="">— Sélectionner une matière —</option>';
            data.forEach(m => {
                selMatiere.innerHTML += `<option value="${m.id_matiere}">${m.nom_matiere}</option>`;
            });
            selMatiere.disabled = false;
        });
}

// ── Changement de matière → recharge les points du programme ──
function onMatiereChange(idMatiere) {
    const selChapitre = document.getElementById('id_chapitre');
    selChapitre.innerHTML = '<option value="">Chargement...</option>';
    selChapitre.disabled  = true;
    hideObjectifs();
    programmeActifId = 0;
    document.querySelector('.point-manquant-zone') &&
        (document.querySelector('.point-manquant-zone').style.display = 'none');

    if (!idMatiere) {
        selChapitre.innerHTML = '<option value="">— Sélectionner d\'abord une matière —</option>';
        return;
    }

    fetch(`${APP_URL}/app.php?page=api-points-programme&matiere=${idMatiere}`)
        .then(r => r.json())
        .then(data => {
            pointsCache = {};
            selChapitre.innerHTML = '<option value="">— Aucun point sélectionné —</option>';
            if (data.points.length === 0) {
                selChapitre.innerHTML += '<option disabled>Aucun programme publié pour cette matière</option>';
            } else {
                data.points.forEach(p => {
                    pointsCache[p.id_chapitre] = p.objectifs_pedagogiques;
                    selChapitre.innerHTML += `<option value="${p.id_chapitre}">${p.titre_chapitre}</option>`;
                });
            }
            selChapitre.disabled = false;

            // Afficher/cacher le bouton point manquant
            const zone = document.querySelector('.point-manquant-zone');
            const warn = document.querySelector('.no-programme-warning');
            if (data.programme) {
                programmeActifId = data.programme.id_programme;
                if (zone) zone.style.display = 'block';
                if (warn) warn.style.display = 'none';
                document.getElementById('pm_id_programme').value = programmeActifId;
            } else {
                if (zone) zone.style.display = 'none';
                if (warn) warn.style.display = 'block';
            }
        });
}

// ── Afficher les objectifs du point sélectionné ──
document.getElementById('id_chapitre')?.addEventListener('change', function() {
    const obj = pointsCache[this.value];
    const div = document.getElementById('objectifs-point');
    if (obj && obj.trim()) {
        div.innerHTML = '🎯 <strong>Objectifs :</strong> ' + escapeHtml(obj);
        div.style.display = 'block';
    } else {
        hideObjectifs();
    }
});

function hideObjectifs() {
    const div = document.getElementById('objectifs-point');
    if (div) { div.style.display = 'none'; div.innerHTML = ''; }
}

// ── Toggle panneau point manquant ──
function togglePointManquant() {
    const panel = document.getElementById('point-manquant-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

// ── Soumettre le point manquant via le formulaire caché ──
function soumettrePointManquant() {
    const titre = document.getElementById('titre_point_manquant').value.trim();
    if (!titre) {
        alert('Veuillez saisir le titre du point manquant.');
        return;
    }
    if (!confirm(`Ajouter "${titre}" au programme officiel ? Ce point sera visible par tous les enseignants.`)) return;

    document.getElementById('pm_id_programme').value = programmeActifId;
    document.getElementById('pm_id_matiere').value   = document.getElementById('id_matiere').value;
    document.getElementById('pm_id_classe').value    = document.getElementById('id_classe').value;
    document.getElementById('pm_titre').value        = titre;
    document.getElementById('pm_objectifs').value    = document.getElementById('objectifs_point_manquant').value;
    document.getElementById('formPointManquant').submit();
}

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Upload zone du formulaire principal ──
function previewFormFiles(input) {
    const preview = document.getElementById('dz-preview-form');
    preview.innerHTML = '';
    Array.from(input.files).forEach(f => {
        const div = document.createElement('div');
        div.className = 'dz-file-chip';
        div.innerHTML = `📄 <span>${escapeHtml(f.name)}</span> <small>(${fmtSize(f.size)})</small>`;
        preview.appendChild(div);
    });
    if (input.files.length > 0) document.getElementById('dz-form').classList.add('has-files');
}
function handleDropForm(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('dragover');
    const input = document.getElementById('form-files');
    input.files = event.dataTransfer.files;
    previewFormFiles(input);
}
function fmtSize(bytes) {
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' Ko';
    return (bytes/1048576).toFixed(1) + ' Mo';
}

// ── Validation heure fin > heure début ──
document.getElementById('heure_fin')?.addEventListener('change', function() {
    const debut = document.getElementById('heure_debut').value;
    if (debut && this.value <= debut) {
        this.setCustomValidity("L'heure de fin doit être après l'heure de début.");
    } else {
        this.setCustomValidity('');
    }
});

// Initialisation si classe/matière déjà sélectionnée (rechargement après erreur)
<?php if ($selectedClasse > 0 && $selectedMatiere > 0): ?>
// Préremplissage après retour serveur — points déjà chargés côté PHP
document.getElementById('id_chapitre').disabled = false;
<?php if ($selectedChapitre > 0): ?>
document.getElementById('id_chapitre').value = '<?= $selectedChapitre ?>';
<?php endif; ?>
<?php endif; ?>
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
