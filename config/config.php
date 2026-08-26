<?php

// ===== BASE DE DONNÉES =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cahierdetexte');

// ===== APPLICATION =====
define('APP_NAME', 'Cahier de Texte');
define('APP_URL',  'http://localhost/cahier_texte');

// APP_ROOT = racine du projet (dossier cahier_texte/)
// config.php est dans cahier_texte/config/ donc __DIR__ = .../cahier_texte/config
// dirname(__DIR__) = .../cahier_texte  ← CORRECT
define('APP_ROOT', dirname(__DIR__));

// ===== TABLES =====
define('TABLE_USERS', 'utilisateur');

// ===== IA (Anthropic Claude) =====
// Remplacez par votre clé dans ce fichier uniquement — ne commitez JAMAIS ce fichier
define('AI_API_KEY',    'VOTRE_CLE_ICI');   // ← mettre votre clé ici
define('AI_API_URL',    'https://api.anthropic.com/v1/messages');
define('AI_MODEL',      'claude-haiku-20240307');  // modèle rapide et économique
define('AI_MAX_TOKENS', 300);

define('UPLOAD_DIR',     APP_ROOT . '/uploads');
define('UPLOAD_URL',     APP_URL  . '/uploads');
define('UPLOAD_MAX_MB',  10);  // taille max par fichier en Mo
define('UPLOAD_ALLOWED', ['pdf','doc','docx','xls','xlsx','ppt','pptx',
                           'jpg','jpeg','png','gif','webp','txt','zip']);
