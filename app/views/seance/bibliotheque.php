<?php
$pageTitle = 'Bibliothèque de séances — ' . APP_NAME;
$extraCss  = 'bibliotheque.css';
include APP_ROOT . '/app/views/layouts/header.php';
?>

<?= Session::getFlash() ?>

<!-- ===== EN-TÊTE ===== -->
<div class="biblio-header">
    <div>
        <h1>📚 Bibliothèque de séances</h1>
        <p>Retrouvez, réutilisez et enrichissez toutes vos séances passées.</p>
    </div>
    <a href="<?= APP_URL ?>/app.php?page=saisie-seance" class="btn btn-primary">
        ✏️ Nouvelle séance
    </a>
</div>

<!-- ===== FILTRES ===== -->
<form method="GET" action="<?= APP_URL ?>/app.php" class="biblio-filters">
    <input type="hidden" name="page" value="bibliotheque-seances">
    <div class="filter-group">
        <input type="text" name="q" placeholder="🔍 Rechercher dans le contenu..."
               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="filter-group">
        <select name="matiere">
            <option value="">— Toutes les matières —</option>
            <?php foreach ($matieres as $m): ?>
                <option value="<?= (int)$m['id_matiere'] ?>"
                    <?= $idMatiere === (int)$m['id_matiere'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['nom_matiere']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-outline">Filtrer</button>
    <?php if ($idMatiere || $search): ?>
        <a href="<?= APP_URL ?>/app.php?page=bibliotheque-seances" class="btn-reset">✕ Réinitialiser</a>
    <?php endif; ?>
</form>

<!-- ===== COMPTEUR ===== -->
<div class="biblio-count">
    <?= count($seances) ?> séance<?= count($seances) > 1 ? 's' : '' ?> trouvée<?= count($seances) > 1 ? 's' : '' ?>
</div>

<!-- ===== LISTE DES SÉANCES ===== -->
<?php if (empty($seances)): ?>
    <div class="empty-state">
        <div class="empty-icon">📂</div>
        <h3>Aucune séance trouvée</h3>
        <p>Commencez par saisir votre première séance de cours.</p>
        <a href="<?= APP_URL ?>/app.php?page=saisie-seance" class="btn btn-primary">
            ✏️ Saisir une séance
        </a>
    </div>
<?php else: ?>

<div class="seances-grid">
    <?php foreach ($seances as $s): ?>
    <div class="seance-card" id="seance-<?= (int)$s['id_seance'] ?>">

        <!-- En-tête de la carte -->
        <div class="sc-header">
            <div class="sc-meta">
                <span class="sc-date">
                    📅 <?= date('d/m/Y', strtotime($s['date_seance'])) ?>
                </span>
                <span class="sc-horaire">
                    🕐 <?= substr($s['heure_debut'],0,5) ?> – <?= substr($s['heure_fin'],0,5) ?>
                </span>
            </div>
            <div class="sc-tags">
                <span class="tag tag--classe"><?= htmlspecialchars($s['nom_classe']) ?></span>
                <span class="tag tag--matiere"><?= htmlspecialchars($s['nom_matiere']) ?></span>
            </div>
        </div>

        <!-- Point du programme -->
        <?php if ($s['titre_chapitre']): ?>
        <div class="sc-programme">
            📋 <?= htmlspecialchars($s['titre_chapitre'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <!-- Contenu traité -->
        <div class="sc-contenu">
            <?= nl2br(htmlspecialchars($s['contenu_traite'], ENT_QUOTES, 'UTF-8')) ?>
        </div>

        <!-- Objectifs atteints -->
        <?php if ($s['objectifs_atteints']): ?>
        <div class="sc-objectifs">
            🎯 <?= htmlspecialchars($s['objectifs_atteints'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <!-- Commentaire -->
        <?php if ($s['commentaire_enseignant']): ?>
        <div class="sc-commentaire">
            💬 <?= htmlspecialchars($s['commentaire_enseignant'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <!-- Pièces jointes existantes -->
        <?php if (!empty($s['pieces_jointes'])): ?>
        <div class="sc-pieces">
            <div class="sc-pieces-title">📎 Pièces jointes (<?= count($s['pieces_jointes']) ?>)</div>
            <div class="pieces-list">
                <?php foreach ($s['pieces_jointes'] as $p): ?>
                <div class="piece-item">
                    <a href="<?= APP_URL ?>/<?= htmlspecialchars($p['url_fichier']) ?>"
                       target="_blank" class="piece-link">
                        <?= getFileIcon($p['type_fichier']) ?>
                        <span><?= htmlspecialchars($p['nom_original']) ?></span>
                        <small><?= formatSize((int)$p['taille_fichier']) ?></small>
                    </a>
                    <form method="POST"
                          action="<?= APP_URL ?>/app.php?page=bibliotheque-seances"
                          class="piece-delete-form"
                          onsubmit="return confirm('Supprimer cette pièce jointe ?')">
                        <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="form_action" value="delete_piece">
                        <input type="hidden" name="id_piece"    value="<?= (int)$p['id_piece'] ?>">
                        <input type="hidden" name="id_seance"   value="<?= (int)$s['id_seance'] ?>">
                        <button type="submit" class="btn-icon btn-icon--danger" title="Supprimer">🗑</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upload de nouvelles pièces jointes -->
        <div class="sc-upload">
            <button type="button" class="btn-upload-toggle"
                    onclick="toggleUpload(<?= (int)$s['id_seance'] ?>)">
                📎 Ajouter des pièces jointes
            </button>
            <div id="upload-panel-<?= (int)$s['id_seance'] ?>" class="upload-panel" style="display:none">
                <form method="POST"
                      action="<?= APP_URL ?>/app.php?page=bibliotheque-seances"
                      enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="upload_pieces">
                    <input type="hidden" name="id_seance"   value="<?= (int)$s['id_seance'] ?>">

                    <div class="upload-dropzone" id="dz-<?= (int)$s['id_seance'] ?>"
                         onclick="document.getElementById('files-<?= (int)$s['id_seance'] ?>').click()"
                         ondragover="event.preventDefault(); this.classList.add('dragover')"
                         ondragleave="this.classList.remove('dragover')"
                         ondrop="handleDrop(event, <?= (int)$s['id_seance'] ?>)">
                        <div class="dz-icon">📁</div>
                        <p>Cliquez ou glissez-déposez vos fichiers ici</p>
                        <small>PDF, Word, Excel, PowerPoint, Images — max <?= UPLOAD_MAX_MB ?> Mo/fichier</small>
                        <div id="dz-preview-<?= (int)$s['id_seance'] ?>" class="dz-preview"></div>
                    </div>

                    <input type="file" id="files-<?= (int)$s['id_seance'] ?>"
                           name="fichiers[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip"
                           style="display:none"
                           onchange="previewFiles(this, <?= (int)$s['id_seance'] ?>)">

                    <div class="upload-actions">
                        <button type="button" class="btn btn-outline btn-sm"
                                onclick="toggleUpload(<?= (int)$s['id_seance'] ?>)">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            ⬆️ Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Actions de la carte -->
        <div class="sc-actions">
            <!-- Bouton Réutiliser -->
            <a href="<?= APP_URL ?>/app.php?page=saisie-seance&reuse=<?= (int)$s['id_seance'] ?>"
               class="btn btn-primary btn-sm">
                ♻️ Réutiliser
            </a>
        </div>

    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<script>
function toggleUpload(id) {
    const panel = document.getElementById('upload-panel-' + id);
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function previewFiles(input, seanceId) {
    const preview = document.getElementById('dz-preview-' + seanceId);
    preview.innerHTML = '';
    Array.from(input.files).forEach(f => {
        const div = document.createElement('div');
        div.className = 'dz-file-chip';
        div.innerHTML = `📄 <span>${escHtml(f.name)}</span> <small>(${formatSize(f.size)})</small>`;
        preview.appendChild(div);
    });
    document.getElementById('dz-' + seanceId).classList.add('has-files');
}

function handleDrop(event, seanceId) {
    event.preventDefault();
    event.currentTarget.classList.remove('dragover');
    const input = document.getElementById('files-' + seanceId);
    input.files  = event.dataTransfer.files;
    previewFiles(input, seanceId);
}

function escHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' Ko';
    return (bytes/1048576).toFixed(1) + ' Mo';
}

// Ouvrir automatiquement le panneau si on revient après un upload
<?php
$anchor = $_SERVER['HTTP_REFERER'] ?? '';
preg_match('/#seance-(\d+)/', $anchor, $m);
if (!empty($m[1])): ?>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('seance-<?= (int)$m[1] ?>');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
});
<?php endif; ?>
</script>

<?php include APP_ROOT . '/app/views/layouts/footer.php'; ?>
<?php
// Helpers pour la vue (définis ici pour garder la vue autonome)
function getFileIcon(string $mime): string {
    if (str_contains($mime, 'pdf'))   return '📕';
    if (str_contains($mime, 'word') || str_contains($mime, 'document')) return '📘';
    if (str_contains($mime, 'excel') || str_contains($mime, 'sheet'))   return '📗';
    if (str_contains($mime, 'ppt')  || str_contains($mime, 'presentation')) return '📙';
    if (str_contains($mime, 'image')) return '🖼️';
    if (str_contains($mime, 'zip'))   return '🗜️';
    return '📄';
}
function formatSize(int $bytes): string {
    if ($bytes < 1024)    return $bytes . ' o';
    if ($bytes < 1048576) return round($bytes/1024, 1) . ' Ko';
    return round($bytes/1048576, 1) . ' Mo';
}
?>
