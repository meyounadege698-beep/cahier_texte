<?php

/**
 * Uploader — Gestion sécurisée des uploads de fichiers.
 *
 * Usage :
 *   $uploader = new Uploader();
 *   $result   = $uploader->handle($_FILES['fichiers'], $idSeance, $idUtilisateur);
 *   // $result = ['saved' => [...], 'errors' => [...]]
 */
class Uploader
{
    private string $uploadDir;
    private array  $allowed;
    private int    $maxBytes;

    public function __construct()
    {
        $this->uploadDir = UPLOAD_DIR;
        $this->allowed   = UPLOAD_ALLOWED;
        $this->maxBytes  = UPLOAD_MAX_MB * 1024 * 1024;
    }

    /**
     * Traite un champ file multiple ($_FILES['champ']).
     * Retourne ['saved' => [...infos...], 'errors' => [...messages...]]
     */
    public function handle(array $filesField, int $idSeance, int $idUtilisateur): array
    {
        $saved  = [];
        $errors = [];

        // Normaliser la structure $_FILES pour les champs multiples
        $files = $this->normalize($filesField);

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur upload « {$file['name']} » (code {$file['error']}).";
                continue;
            }

            // Vérifier la taille
            if ($file['size'] > $this->maxBytes) {
                $errors[] = "« {$file['name']} » dépasse la taille maximale (" . UPLOAD_MAX_MB . " Mo).";
                continue;
            }

            // Vérifier l'extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowed, true)) {
                $errors[] = "« {$file['name']} » : extension .{$ext} non autorisée.";
                continue;
            }

            // Vérifier le type MIME réel (pas celui déclaré par le client)
            $mime = mime_content_type($file['tmp_name']);
            if (!$this->mimeAllowed($mime)) {
                $errors[] = "« {$file['name']} » : type de fichier non autorisé.";
                continue;
            }

            // Construire un nom de fichier unique et sûr
            $subDir  = $this->uploadDir . '/' . $idUtilisateur;
            if (!is_dir($subDir)) {
                mkdir($subDir, 0755, true);
            }

            $safeName = $idSeance . '_' . uniqid() . '.' . $ext;
            $destPath = $subDir . '/' . $safeName;
            $urlPath  = 'uploads/' . $idUtilisateur . '/' . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $errors[] = "Impossible de sauvegarder « {$file['name']} ».";
                continue;
            }

            $saved[] = [
                'nom_original'  => basename($file['name']),
                'url_fichier'   => $urlPath,
                'type_fichier'  => $mime,
                'taille_fichier'=> (int)$file['size'],
            ];
        }

        return ['saved' => $saved, 'errors' => $errors];
    }

    /**
     * Supprime un fichier physique de façon sécurisée.
     * S'assure que le chemin est bien dans /uploads/.
     */
    public function deleteFile(string $urlPath): bool
    {
        // urlPath est de la forme "uploads/X/fichier.ext"
        $fullPath = APP_ROOT . '/' . ltrim($urlPath, '/');
        $realPath = realpath($fullPath);

        if (!$realPath) return false;

        // Vérifier que le fichier est bien dans le dossier uploads
        $uploadReal = realpath($this->uploadDir);
        if (!str_starts_with($realPath, $uploadReal)) return false;

        return unlink($realPath);
    }

    // ── Normaliser $_FILES pour les champs multiples ──
    private function normalize(array $field): array
    {
        if (!is_array($field['name'])) {
            return [$field];
        }
        $files = [];
        foreach ($field['name'] as $i => $name) {
            $files[] = [
                'name'     => $name,
                'tmp_name' => $field['tmp_name'][$i],
                'error'    => $field['error'][$i],
                'size'     => $field['size'][$i],
                'type'     => $field['type'][$i],
            ];
        }
        return $files;
    }

    // ── Vérification MIME basique ──
    private function mimeAllowed(string $mime): bool
    {
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'text/plain',
            'application/zip',
        ];
        return in_array($mime, $allowedMimes, true);
    }
}
