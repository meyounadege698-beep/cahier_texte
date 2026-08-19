<?php

/**
 * app.php — Front Controller MVC
 *
 * Toutes les routes applicatives passent ici.
 *   http://localhost/cahier_texte/app.php?page=login
 *   http://localhost/cahier_texte/app.php?page=register
 *   http://localhost/cahier_texte/app.php?page=dashboard
 *   http://localhost/cahier_texte/app.php?page=logout
 *
 * Page d'accueil visiteur → index.php (racine)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Router.php';

Session::start();

$router = new Router();

// Authentification
$router->add('login',       'AuthController', 'loginForm');
$router->add('do-login',    'AuthController', 'login');
$router->add('register',    'AuthController', 'registerForm');
$router->add('do-register', 'AuthController', 'register');
$router->add('logout',      'AuthController', 'logout');

// API JSON (AJAX)
$router->add('api-matieres',          'ApiController', 'getMatieres');
$router->add('api-matieres-classe',   'ApiController', 'getMatieresClasse');
$router->add('api-points-programme',  'ApiController', 'getPointsProgramme');

// Application
$router->add('dashboard',             'DashboardController', 'index');

// Présence & appel (enseignant + censeur)
$router->add('appel',                'PresenceController', 'form');
$router->add('do-save-appel',        'PresenceController', 'save');
$router->add('historique-presences', 'PresenceController', 'historique');

// Devoirs (enseignant)
$router->add('devoirs',              'DevoirController', 'index');
$router->add('do-create-devoir',     'DevoirController', 'create');
$router->add('do-delete-devoir',     'DevoirController', 'delete');

// Supervision censeur
$router->add('supervision',          'SupervisionController', 'index');
$router->add('do-valider-prog',      'SupervisionController', 'valider');

// Bibliothèque séances (enseignant)
$router->add('bibliotheque-seances',    'BibliothequeController', 'index');
$router->add('do-upload-pieces',        'BibliothequeController', 'uploadPieces');
$router->add('do-delete-piece',         'BibliothequeController', 'deletePiece');

// Saisie séance (enseignant)
$router->add('saisie-seance',         'SeanceController', 'form');
$router->add('do-saisie-seance',      'SeanceController', 'save');
$router->add('do-ajouter-point',      'SeanceController', 'ajouterPoint');

// Gestion classes & élèves (censeur)
$router->add('gestion-classes',     'EleveController', 'classes');
$router->add('gestion-eleves',      'EleveController', 'eleves');
$router->add('do-add-classe',       'EleveController', 'addClasse');
$router->add('do-edit-classe',      'EleveController', 'editClasse');
$router->add('do-delete-classe',    'EleveController', 'deleteClasse');
$router->add('do-add-eleve',        'EleveController', 'addEleve');
$router->add('do-edit-eleve',       'EleveController', 'editEleve');
$router->add('do-toggle-eleve',     'EleveController', 'toggleEleve');

// Gestion enseignants (censeur)
$router->add('gestion-enseignants',  'EnseignantController', 'index');
$router->add('do-add-enseignant',    'EnseignantController', 'add');
$router->add('do-edit-enseignant',   'EnseignantController', 'edit');
$router->add('do-toggle-actif',      'EnseignantController', 'toggleActif');
$router->add('do-reset-password',    'EnseignantController', 'resetPassword');

// Gestion salles & affectations (censeur)
$router->add('gestion-affectations',  'AffectationController', 'index');
$router->add('affecter-enseignant',   'AffectationController', 'affecterForm');
$router->add('do-affecter-multiple',  'AffectationController', 'affecterMultiple');
$router->add('do-add-salle',          'AffectationController', 'addSalle');
$router->add('do-edit-salle',         'AffectationController', 'editSalle');
$router->add('do-delete-salle',       'AffectationController', 'deleteSalle');
$router->add('do-add-aff',            'AffectationController', 'addAffectation');
$router->add('do-edit-aff',           'AffectationController', 'editAffectation');
$router->add('do-delete-aff',         'AffectationController', 'deleteAffectation');

// Gestion catalogue départements/matières (censeur)
$router->add('gestion-catalogue',  'CatalogueController', 'index');
$router->add('do-add-dept',        'CatalogueController', 'addDept');
$router->add('do-edit-dept',       'CatalogueController', 'editDept');
$router->add('do-delete-dept',     'CatalogueController', 'deleteDept');
$router->add('do-add-mat',         'CatalogueController', 'addMat');
$router->add('do-edit-mat',        'CatalogueController', 'editMat');
$router->add('do-delete-mat',      'CatalogueController', 'deleteMat');

// Progression officielle (censeur)
$router->add('progression-officielle',              'ProgressionOfficielleController', 'index');
$router->add('progression-officielle-create',       'ProgressionOfficielleController', 'createForm');
$router->add('do-progression-officielle-create',    'ProgressionOfficielleController', 'createProgramme');
$router->add('progression-officielle-detail',       'ProgressionOfficielleController', 'detail');
$router->add('do-progression-officielle-chapitre',  'ProgressionOfficielleController', 'addChapitre');
$router->add('do-delete-chapitre',                  'ProgressionOfficielleController', 'deleteChapitre');
$router->add('do-publier-programme',                'ProgressionOfficielleController', 'publier');

// ===== DISPATCH =====
$page = $_GET['page'] ?? (Session::isLoggedIn() ? 'dashboard' : 'login');

// Rerouter les POST vers les handlers dédiés
if ($page === 'login'    && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-login';
} elseif ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-register';
} elseif ($page === 'appel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-save-appel';
} elseif ($page === 'devoirs' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'create_devoir') $_GET['page'] = 'do-create-devoir';
    if ($action === 'delete_devoir') $_GET['page'] = 'do-delete-devoir';
} elseif ($page === 'supervision' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-valider-prog';
} elseif ($page === 'saisie-seance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? 'save';
    if ($action === 'ajouter_point') {
        $_GET['page'] = 'do-ajouter-point';
    } else {
        $_GET['page'] = 'do-saisie-seance';
    }
} elseif ($page === 'gestion-classes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'add_classe')    $_GET['page'] = 'do-add-classe';
    if ($action === 'edit_classe')   $_GET['page'] = 'do-edit-classe';
    if ($action === 'delete_classe') $_GET['page'] = 'do-delete-classe';
} elseif ($page === 'gestion-eleves' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'add_eleve')     $_GET['page'] = 'do-add-eleve';
    if ($action === 'edit_eleve')    $_GET['page'] = 'do-edit-eleve';
    if ($action === 'toggle_eleve')  $_GET['page'] = 'do-toggle-eleve';
} elseif ($page === 'gestion-enseignants' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'add_enseignant')  $_GET['page'] = 'do-add-enseignant';
    if ($action === 'edit_enseignant') $_GET['page'] = 'do-edit-enseignant';
    if ($action === 'toggle_actif')    $_GET['page'] = 'do-toggle-actif';
    if ($action === 'reset_password')  $_GET['page'] = 'do-reset-password';
} elseif ($page === 'gestion-affectations' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'add_salle')         $_GET['page'] = 'do-add-salle';
    if ($action === 'edit_salle')        $_GET['page'] = 'do-edit-salle';
    if ($action === 'delete_salle')      $_GET['page'] = 'do-delete-salle';
    if ($action === 'add_aff')           $_GET['page'] = 'do-add-aff';
    if ($action === 'edit_aff')          $_GET['page'] = 'do-edit-aff';
    if ($action === 'delete_aff')        $_GET['page'] = 'do-delete-aff';
    if ($action === 'affecter_multiple') $_GET['page'] = 'do-affecter-multiple';
} elseif ($page === 'bibliotheque-seances' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'upload_pieces') $_GET['page'] = 'do-upload-pieces';
    if ($action === 'delete_piece')  $_GET['page'] = 'do-delete-piece';
} elseif ($page === 'gestion-catalogue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'add_dept')    $_GET['page'] = 'do-add-dept';
    if ($action === 'edit_dept')   $_GET['page'] = 'do-edit-dept';
    if ($action === 'delete_dept') $_GET['page'] = 'do-delete-dept';
    if ($action === 'add_mat')     $_GET['page'] = 'do-add-mat';
    if ($action === 'edit_mat')    $_GET['page'] = 'do-edit-mat';
    if ($action === 'delete_mat')  $_GET['page'] = 'do-delete-mat';
} elseif ($page === 'progression-officielle-create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['page'] = 'do-progression-officielle-create';
} elseif ($page === 'progression-officielle-detail' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Le sous-formulaire (ajout chapitre) envoie un champ action
    $action = $_POST['form_action'] ?? '';
    if ($action === 'add_chapitre')    $_GET['page'] = 'do-progression-officielle-chapitre';
    if ($action === 'delete_chapitre') $_GET['page'] = 'do-delete-chapitre';
    if ($action === 'publier')         $_GET['page'] = 'do-publier-programme';
}

$router->dispatch();
