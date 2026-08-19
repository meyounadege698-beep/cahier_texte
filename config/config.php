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

// ===== UPLOADS =====
define('UPLOAD_DIR',     APP_ROOT . '/uploads');
define('UPLOAD_URL',     APP_URL  . '/uploads');
define('UPLOAD_MAX_MB',  10);  // taille max par fichier en Mo
define('UPLOAD_ALLOWED', ['pdf','doc','docx','xls','xlsx','ppt','pptx',
                           'jpg','jpeg','png','gif','webp','txt','zip']);
