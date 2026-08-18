<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/home.css">
</head>
<body>

<!-- ============================================================
     NAVBAR
============================================================ -->
<nav class="navbar" id="navbar">
    <a href="<?= APP_URL ?>/index.php" class="nav-brand">
        <span class="brand-icon">📘</span>
        <span class="brand-text"><?= APP_NAME ?></span>
    </a>
    <div class="nav-links">
        <a href="#fonctionnalites" class="nav-link">Fonctionnalités</a>
        <a href="#roles"           class="nav-link">Qui peut l'utiliser ?</a>
        <a href="#comment"         class="nav-link">Comment ça marche</a>
        <?php if (Session::isLoggedIn()): ?>
            <a href="<?= APP_URL ?>/app.php?page=dashboard" class="btn btn-primary">Mon espace →</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/app.php?page=login"    class="btn btn-outline">Se connecter</a>
            <a href="<?= APP_URL ?>/app.php?page=register" class="btn btn-primary">Commencer →</a>
        <?php endif; ?>
    </div>
    <button class="nav-toggle" onclick="toggleMenu()" aria-label="Menu">☰</button>
</nav>

<!-- ============================================================
     HERO
============================================================ -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">✨ Outil numérique scolaire</div>
        <h1>Le cahier de texte<br><span class="gradient-text">enfin numérique</span></h1>
        <p class="hero-desc">
            Gérez la progression pédagogique, les séances de cours, les présences et les devoirs
            de votre établissement depuis une seule plateforme — accessible, traçable, fiable.
        </p>
        <div class="hero-actions">
            <?php if (Session::isLoggedIn()): ?>
                <a href="<?= APP_URL ?>/app.php?page=dashboard" class="btn btn-primary btn-lg">
                    Accéder à mon espace →
                </a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/app.php?page=register" class="btn btn-primary btn-lg">
                    Créer un compte gratuit
                </a>
                <a href="<?= APP_URL ?>/app.php?page=login" class="btn btn-ghost btn-lg">
                    Se connecter
                </a>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div class="stat"><span class="stat-num">5</span><span class="stat-label">Rôles utilisateurs</span></div>
            <div class="stat-sep">|</div>
            <div class="stat"><span class="stat-num">8</span><span class="stat-label">Modules fonctionnels</span></div>
            <div class="stat-sep">|</div>
            <div class="stat"><span class="stat-num">100%</span><span class="stat-label">Traçabilité pédagogique</span></div>
        </div>
    </div>
    <div class="hero-visual">
        <div class="hero-card card-float-1">
            <div class="hc-icon">📊</div>
            <div class="hc-text">
                <strong>Progression</strong>
                <span>Mathématiques — Tle C</span>
                <div class="progress-bar"><div class="progress-fill" style="width:72%"></div></div>
                <small>72% du programme couvert</small>
            </div>
        </div>
        <div class="hero-card card-float-2">
            <div class="hc-icon">✅</div>
            <div class="hc-text">
                <strong>Séance validée</strong>
                <span>Fonctions exponentielles</span>
                <small>M. Diop — 14/08/2026</small>
            </div>
        </div>
        <div class="hero-card card-float-3">
            <div class="hc-icon">🔔</div>
            <div class="hc-text">
                <strong>Alerte censeur</strong>
                <span>2 cahiers non remplis</span>
                <small>Cette semaine</small>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     FONCTIONNALITÉS
============================================================ -->
<section class="section" id="fonctionnalites">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Ce que vous pouvez faire</span>
            <h2>Toutes les fonctionnalités<br>dont votre établissement a besoin</h2>
            <p>De la saisie du programme officiel jusqu'à la génération du rapport PDF — tout est pensé pour simplifier le quotidien pédagogique.</p>
        </div>

        <div class="features-grid">

            <!-- 1. Progression officielle -->
            <div class="feature-card feature-card--primary">
                <div class="fc-icon">📋</div>
                <h3>Progression officielle</h3>
                <p>
                    Le censeur saisit les points du programme national par département et par matière
                    <strong>avant le début de l'année scolaire</strong>. Cette progression devient
                    le référentiel partagé de tous les enseignants.
                </p>
                <ul class="fc-list">
                    <li>✓ Organisation département → matière → chapitres → leçons</li>
                    <li>✓ Programme national en vigueur comme référence</li>
                    <li>✓ Publication officielle avant la rentrée</li>
                    <li>✓ Mise à jour possible par l'enseignant si point manquant</li>
                </ul>
            </div>

            <!-- 2. Saisie pédagogique -->
            <div class="feature-card">
                <div class="fc-icon">✏️</div>
                <h3>Saisie et suivi pédagogique</h3>
                <p>
                    L'enseignant sélectionne le point de programme traité lors de chaque séance.
                    Il peut enrichir avec ses propres notes, ressources et pièces jointes.
                </p>
                <ul class="fc-list">
                    <li>✓ Sélection guidée dans la progression officielle</li>
                    <li>✓ Ajout de point manquant si nécessaire</li>
                    <li>✓ Bibliothèque de séances réutilisables</li>
                    <li>✓ Pièces jointes (documents, exercices, supports)</li>
                </ul>
            </div>

            <!-- 3. Appel et présence -->
            <div class="feature-card">
                <div class="fc-icon">📝</div>
                <h3>Appel et présence</h3>
                <p>
                    Prenez l'appel directement depuis la plateforme après chaque séance.
                    L'historique d'assiduité est automatiquement constitué.
                </p>
                <ul class="fc-list">
                    <li>✓ Statuts : Présent / Absent / Retard / Excusé</li>
                    <li>✓ Saisie par l'enseignant ou le censeur</li>
                    <li>✓ Historique par élève et par classe</li>
                    <li>✓ Motif d'absence enregistrable</li>
                </ul>
            </div>

            <!-- 4. Devoirs -->
            <div class="feature-card">
                <div class="fc-icon">📚</div>
                <h3>Devoirs et évaluations</h3>
                <p>
                    Chaque séance peut être accompagnée d'un devoir enregistré comme
                    trace pédagogique officielle, consultable par le censeur.
                </p>
                <ul class="fc-list">
                    <li>✓ Types : DM, DS, Évaluation, Projet</li>
                    <li>✓ Titre, consigne, date de remise</li>
                    <li>✓ Rattaché à chaque séance</li>
                    <li>✓ Coefficient et note sur paramétrable</li>
                </ul>
            </div>

            <!-- 5. Supervision -->
            <div class="feature-card feature-card--accent">
                <div class="fc-icon">🔍</div>
                <h3>Supervision et conformité</h3>
                <p>
                    Le censeur dispose d'un tableau de bord dédié pour surveiller l'avancement
                    réel du programme dans chaque classe et prendre des mesures correctives.
                </p>
                <ul class="fc-list">
                    <li>✓ Tableau de bord censeur en temps réel</li>
                    <li>✓ Alertes cahiers non remplis</li>
                    <li>✓ Validation hebdomadaire des séances</li>
                    <li>✓ Taux de couverture du programme</li>
                    <li>✓ Envoi de convocations aux enseignants absents</li>
                </ul>
            </div>

            <!-- 6. Rapports PDF -->
            <div class="feature-card">
                <div class="fc-icon">📄</div>
                <h3>Génération de rapports PDF</h3>
                <p>
                    Un formulaire simple suffit pour générer automatiquement un rapport
                    complet prêt à l'export — sans aucune ressaisie manuelle.
                </p>
                <ul class="fc-list">
                    <li>✓ Rapports de progression par classe / matière</li>
                    <li>✓ Rapports de présence par élève</li>
                    <li>✓ Rapports d'évaluation</li>
                    <li>✓ Rapport annuel de synthèse</li>
                </ul>
            </div>

            <!-- 7. Fonctionnalités avancées -->
            <div class="feature-card">
                <div class="fc-icon">🤖</div>
                <h3>Fonctionnalités avancées</h3>
                <p>
                    Des outils modernes pour aller plus loin dans l'analyse pédagogique
                    et garantir la continuité même sans connexion.
                </p>
                <ul class="fc-list">
                    <li>✓ Résumé automatique par IA des notes du professeur</li>
                    <li>✓ Mode hors-ligne avec synchronisation</li>
                    <li>✓ Chaîne de progression pilotée et traçable</li>
                    <li>✓ Historique complet des modifications</li>
                </ul>
            </div>

            <!-- 8. Administration -->
            <div class="feature-card">
                <div class="fc-icon">⚙️</div>
                <h3>Administration de l'établissement</h3>
                <p>
                    L'administrateur gère les comptes, les classes, les affectations
                    enseignants et toute la configuration de l'établissement.
                </p>
                <ul class="fc-list">
                    <li>✓ Gestion des utilisateurs (activation / désactivation)</li>
                    <li>✓ Affectation enseignants → classes → matières</li>
                    <li>✓ Gestion des classes et niveaux</li>
                    <li>✓ Configuration de l'année scolaire</li>
                </ul>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     RÔLES UTILISATEURS
============================================================ -->
<section class="section section--alt" id="roles">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Pour tout l'établissement</span>
            <h2>Un espace dédié<br>pour chaque acteur</h2>
        </div>

        <div class="roles-grid">

            <div class="role-card">
                <div class="role-icon">👨‍🏫</div>
                <h3>Enseignant</h3>
                <p>Saisit ses séances, suit sa progression, enregistre les présences et les devoirs. Accède à la bibliothèque de séances réutilisables.</p>
                <div class="role-actions">
                    <span class="tag">Saisie séances</span>
                    <span class="tag">Appel</span>
                    <span class="tag">Devoirs</span>
                    <span class="tag">Bibliothèque</span>
                </div>
            </div>

            <div class="role-card role-card--highlight">
                <div class="role-badge">Clé de voûte</div>
                <div class="role-icon">🔍</div>
                <h3>Censeur</h3>
                <p>Définit la progression officielle avant la rentrée, supervise l'avancement réel, valide les séances, génère les alertes et les rapports.</p>
                <div class="role-actions">
                    <span class="tag">Programme officiel</span>
                    <span class="tag">Supervision</span>
                    <span class="tag">Validation</span>
                    <span class="tag">Alertes</span>
                </div>
            </div>

            <div class="role-card">
                <div class="role-icon">⚙️</div>
                <h3>Administrateur</h3>
                <p>Gère les comptes utilisateurs, les classes, les affectations et toute la configuration de la plateforme pour l'établissement.</p>
                <div class="role-actions">
                    <span class="tag">Comptes</span>
                    <span class="tag">Classes</span>
                    <span class="tag">Affectations</span>
                    <span class="tag">Convocations</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     COMMENT ÇA MARCHE
============================================================ -->
<section class="section" id="comment">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Processus</span>
            <h2>Une chaîne pédagogique<br>de bout en bout</h2>
            <p>Chaque action s'enchaîne logiquement pour garantir un suivi fiable et comparable.</p>
        </div>

        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-icon">📋</div>
                <h3>Le censeur prépare</h3>
                <p>Avant la rentrée, il saisit le programme national par département, matière, chapitres et leçons. C'est la référence officielle de l'année.</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-icon">✏️</div>
                <h3>L'enseignant enseigne</h3>
                <p>À chaque cours, il sélectionne le point de programme traité, prend l'appel, note les devoirs et joint ses ressources pédagogiques.</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-icon">🔍</div>
                <h3>Le censeur supervise</h3>
                <p>Il visualise en temps réel l'avancement réel vs prévu, valide les séances, reçoit les alertes et lance les actions correctives.</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-icon">📄</div>
                <h3>Rapports générés</h3>
                <p>En un clic, un rapport PDF complet est exporté — progression, présences, devoirs — sans aucune ressaisie manuelle.</p>
            </div>
        </div>

    </div>
</section>

<!-- ============================================================
     CTA FINAL
============================================================ -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box">
            <h2>Prêt à digitaliser votre établissement ?</h2>
            <p>Rejoignez la plateforme et transformez la gestion pédagogique de votre école dès aujourd'hui.</p>
            <div class="cta-actions">
                <?php if (Session::isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/app.php?page=dashboard" class="btn btn-white btn-lg">
                        Accéder à mon espace →
                    </a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/app.php?page=register" class="btn btn-white btn-lg">
                        Créer un compte →
                    </a>
                    <a href="<?= APP_URL ?>/app.php?page=login" class="btn btn-ghost-white btn-lg">
                        Se connecter
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     FOOTER
============================================================ -->
<footer class="home-footer">
    <div class="container">
        <div class="footer-brand">
            <span class="brand-icon">📘</span>
            <strong><?= APP_NAME ?></strong>
        </div>
        <p class="footer-desc">Plateforme de gestion pédagogique numérique pour les établissements scolaires.</p>
        <div class="footer-links">
            <a href="#fonctionnalites">Fonctionnalités</a>
            <a href="#roles">Utilisateurs</a>
            <a href="#comment">Comment ça marche</a>
            <a href="<?= APP_URL ?>/app.php?page=login">Connexion</a>
            <a href="<?= APP_URL ?>/app.php?page=register">Inscription</a>
        </div>
        <p class="footer-copy">&copy; <?= date('Y') ?> <?= APP_NAME ?> — Tous droits réservés.</p>
    </div>
</footer>

<script>
// Sticky navbar shadow on scroll
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// Mobile menu toggle
function toggleMenu() {
    document.querySelector('.nav-links').classList.toggle('open');
}

// Smooth scroll pour les ancres
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Animation d'apparition au scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1 });
document.querySelectorAll('.feature-card, .role-card, .step').forEach(el => observer.observe(el));
</script>

</body>
</html>
