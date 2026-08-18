-- ============================================================
-- Cahier de Texte Digital — Script SQL v3
-- Mise à jour 2026-08-14 :
--   + seance_template  : bibliothèque de séances réutilisables
--   + planning_seance  : planning prévisionnel (base des alertes)
--   + convocation      : convocations enseignants par l'admin
--   + leçon.source / id_createur / date_creation : traçabilité ajouts enseignant
--   + eleve.annee_scolaire : cohérence historique présences
--   + rapport_genere restructuré : filtres typés (plus de filtre_texte libre)
--   + résumé_ia dans seance : stockage futur des résumés IA
--   + utilisateur.est_actif vérifié (commentaire PHP à appliquer)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- Base de données
-- ============================================================
CREATE DATABASE IF NOT EXISTS `cahierdetexte`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `cahierdetexte`;

-- ============================================================
-- PROCÉDURES STOCKÉES
-- ============================================================
DELIMITER $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `creer_progression_annuelle` (
    IN `p_enseignant_id`  INT,
    IN `p_classe_id`      INT,
    IN `p_matiere_id`     INT,
    IN `p_annee_scolaire` VARCHAR(15)
)
BEGIN
    DECLARE v_programme_id INT;
    IF NOT EXISTS (
        SELECT 1 FROM utilisateur
        WHERE id_utilisateur = p_enseignant_id AND role = 'enseignant'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'L''utilisateur n''est pas un enseignant valide';
    END IF;
    SELECT id_programme INTO v_programme_id
    FROM programme_officiel
    WHERE id_matiere = p_matiere_id AND annee_scolaire = p_annee_scolaire
      AND est_actif = TRUE AND statut = 'PUBLIE'
    LIMIT 1;
    IF v_programme_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aucun programme officiel actif trouvé';
    END IF;
    INSERT INTO progression_programme
        (id_utilisateur, id_leçon, id_classe, id_matiere, date_debut, statut)
    SELECT p_enseignant_id, l.id_leçon, p_classe_id, p_matiere_id, CURDATE(), 'NON_COMMENCEE'
    FROM leçon l
    JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
    WHERE ch.id_programme = v_programme_id
    ORDER BY ch.ordre_chapitre, l.ordre_leçon;
    SELECT COUNT(*) AS lecons_planifiees
    FROM leçon l JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
    WHERE ch.id_programme = v_programme_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `generer_rapport_progression` (
    IN `p_enseignant_id` INT, IN `p_classe_id` INT,
    IN `p_matiere_id` INT, IN `p_date_debut` DATE, IN `p_date_fin` DATE
)
BEGIN
    SELECT CONCAT(u.nom,' ',u.prenom) AS enseignant, c.nom_classe, m.nom_matiere,
        ch.titre_chapitre, l.titre_leçon, pp.date_debut, pp.date_fin, pp.statut,
        pp.progression_pourcentage, pp.observation_professeur,
        vp.statut AS validation_statut, vp.commentaire AS validation_commentaire,
        COUNT(s.id_seance) AS nb_seances
    FROM progression_programme pp
    JOIN utilisateur u ON pp.id_utilisateur = u.id_utilisateur
    JOIN classe c ON pp.id_classe = c.id_classe
    JOIN matiere m ON pp.id_matiere = m.id_matiere
    JOIN leçon l ON pp.id_leçon = l.id_leçon
    JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
    LEFT JOIN validation_progression vp ON pp.id_progression = vp.id_progression
    LEFT JOIN seance s ON s.id_progression = pp.id_progression AND s.statut = 'REALISEE'
    WHERE (p_enseignant_id IS NULL OR pp.id_utilisateur = p_enseignant_id)
      AND (p_classe_id IS NULL OR pp.id_classe = p_classe_id)
      AND (p_matiere_id IS NULL OR pp.id_matiere = p_matiere_id)
      AND (p_date_debut IS NULL OR pp.date_debut >= p_date_debut)
      AND (p_date_fin IS NULL OR pp.date_fin <= p_date_fin)
    GROUP BY pp.id_progression
    ORDER BY u.nom, c.nom_classe, ch.ordre_chapitre, l.ordre_leçon;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `mettre_a_jour_progression` (
    IN `p_progression_id` INT, IN `p_statut` VARCHAR(20), IN `p_pourcentage` INT,
    IN `p_observation` TEXT, IN `p_ressources` TEXT, IN `p_difficulte` TEXT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM progression_programme WHERE id_progression = p_progression_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Progression non trouvée';
    END IF;
    UPDATE progression_programme SET
        statut = p_statut, progression_pourcentage = p_pourcentage,
        observation_professeur = p_observation, ressources_utilisees = p_ressources,
        difficulte_rencontree = p_difficulte,
        date_fin = IF(p_statut = 'TERMINEE', CURDATE(), NULL),
        date_modification = CURRENT_TIMESTAMP
    WHERE id_progression = p_progression_id;
    IF p_statut = 'TERMINEE' THEN
        INSERT INTO validation_progression (id_progression, id_utilisateur, statut)
        SELECT p_progression_id, id_utilisateur, 'EN_ATTENTE'
        FROM utilisateur WHERE role = 'censeur' LIMIT 1;
    END IF;
    SELECT 'Progression mise à jour avec succès' AS message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `valider_progression` (
    IN `p_progression_id` INT, IN `p_censeur_id` INT, IN `p_statut` VARCHAR(20),
    IN `p_commentaire` TEXT, IN `p_ecart` TEXT, IN `p_actions` TEXT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM utilisateur WHERE id_utilisateur = p_censeur_id AND role = 'censeur') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'L''utilisateur n''est pas un censeur valide';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM progression_programme WHERE id_progression = p_progression_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Progression non trouvée';
    END IF;
    INSERT INTO validation_progression
        (id_progression, id_utilisateur, statut, commentaire, ecart_programme, actions_correctives, date_validation)
    VALUES (p_progression_id, p_censeur_id, p_statut, p_commentaire, p_ecart, p_actions, CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE
        id_utilisateur = p_censeur_id, statut = p_statut, commentaire = p_commentaire,
        ecart_programme = p_ecart, actions_correctives = p_actions, date_validation = CURRENT_TIMESTAMP;
    SELECT 'Validation enregistrée avec succès' AS message;
END$$

DELIMITER ;

-- ============================================================
-- TABLE : utilisateur
-- Colonnes prenom, derniere_connexion, est_actif ajoutées
-- NOTE PHP : vérifier est_actif = 1 dans AuthController::login()
-- ============================================================
CREATE TABLE `utilisateur` (
    `id_utilisateur`     int(11)      NOT NULL COMMENT 'Identifiant unique',
    `nom`                varchar(100) NOT NULL COMMENT 'Nom de l''utilisateur',
    `prenom`             varchar(100) NOT NULL DEFAULT '' COMMENT 'Prénom de l''utilisateur',
    `email`              varchar(150) NOT NULL COMMENT 'Email (identifiant de connexion)',
    `mot_de_passe_hash`  varchar(255) NOT NULL COMMENT 'Mot de passe hashé bcrypt',
    `role`               enum('enseignant','censeur','administrateur') NOT NULL COMMENT 'Rôle (minuscules, cohérence PHP)',
    `date_inscription`   datetime     DEFAULT current_timestamp() COMMENT 'Date d''inscription',
    `derniere_connexion` datetime     DEFAULT NULL COMMENT 'Mise à jour à chaque login dans AuthController',
    `est_actif`          tinyint(1)   DEFAULT 1 COMMENT 'Compte actif — vérifié au login'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `utilisateur`
    (`id_utilisateur`,`nom`,`prenom`,`email`,`mot_de_passe_hash`,`role`,`date_inscription`,`derniere_connexion`,`est_actif`)
VALUES
    (1,'Diop','Mamadou','mamadou.diop@ecole.cm','$2y$10$PLACEHOLDER_HASH_1','enseignant','2026-08-14 16:08:19',NULL,1),
    (2,'Fall','Aminata','aminata.fall@ecole.cm','$2y$10$PLACEHOLDER_HASH_2','enseignant','2026-08-14 16:08:19',NULL,1),
    (3,'Sow','Cheikh','cheikh.sow@ecole.cm','$2y$10$PLACEHOLDER_HASH_3','censeur','2026-08-14 16:08:19',NULL,1),
    (4,'Diallo','Ousmane','ousmane.diallo@ecole.cm','$2y$10$PLACEHOLDER_HASH_4','administrateur','2026-08-14 16:08:19',NULL,1);

-- ============================================================
-- TABLE : departement
-- ============================================================
CREATE TABLE `departement` (
    `id_departement`   int(11)      NOT NULL COMMENT 'Identifiant du département',
    `nom_departement`  varchar(100) NOT NULL COMMENT 'Nom du département',
    `description`      text         DEFAULT NULL,
    `code_departement` varchar(20)  DEFAULT NULL COMMENT 'Code court (ex: MATH)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departement` (`id_departement`,`nom_departement`,`description`,`code_departement`) VALUES
    (1,'Sciences','Département des sciences','SCI'),
    (2,'Lettres','Département des lettres et langues','LET'),
    (3,'Mathématiques','Département des mathématiques','MATH');

-- ============================================================
-- TABLE : matiere
-- ============================================================
CREATE TABLE `matiere` (
    `id_matiere`            int(11)      NOT NULL COMMENT 'Identifiant de la matière',
    `id_departement`        int(11)      NOT NULL COMMENT 'Département auquel appartient la matière',
    `nom_matiere`           varchar(100) NOT NULL,
    `code_matiere`          varchar(20)  DEFAULT NULL,
    `coefficient`           decimal(3,1) DEFAULT 1.0,
    `volume_horaire_annuel` int(11)      DEFAULT NULL COMMENT 'Volume horaire officiel annuel',
    `description`           text         DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `matiere` (`id_matiere`,`id_departement`,`nom_matiere`,`code_matiere`,`coefficient`,`volume_horaire_annuel`,`description`) VALUES
    (1,1,'Physique-Chimie','PC',3.0,120,NULL),
    (2,1,'SVT','SVT',3.0,120,NULL),
    (3,2,'Français','FR',4.0,150,NULL),
    (4,2,'Anglais','ANG',2.0,100,NULL),
    (5,3,'Mathématiques','MATH',4.0,150,NULL);

-- ============================================================
-- TABLE : classe
-- ============================================================
CREATE TABLE `classe` (
    `id_classe`      int(11)     NOT NULL COMMENT 'Identifiant de la classe',
    `nom_classe`     varchar(50) NOT NULL,
    `niveau`         varchar(30) NOT NULL COMMENT 'Ex: Terminale, Première, Seconde',
    `filiere`        varchar(50) DEFAULT NULL COMMENT 'Ex: Générale, Scientifique',
    `annee_scolaire` varchar(15) NOT NULL COMMENT 'Ex: 2026-2027',
    `effectif_max`   int(11)     DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `classe` (`id_classe`,`nom_classe`,`niveau`,`filiere`,`annee_scolaire`,`effectif_max`) VALUES
    (1,'Terminale A','Terminale','Générale','2026-2027',45),
    (2,'Terminale C','Terminale','Scientifique','2026-2027',40),
    (3,'Première A','Première','Générale','2026-2027',45),
    (4,'Seconde','Seconde','Générale','2026-2027',50);

-- ============================================================
-- TABLE : eleve
-- AJOUT : annee_scolaire — pour cohérence historique présences
-- si un élève change de classe, l'historique reste lisible
-- ============================================================
CREATE TABLE `eleve` (
    `id_eleve`       int(11)      NOT NULL COMMENT 'Identifiant de l''élève',
    `id_classe`      int(11)      NOT NULL COMMENT 'Classe actuelle',
    `nom`            varchar(100) NOT NULL,
    `prenom`         varchar(100) NOT NULL,
    `matricule`      varchar(30)  NOT NULL COMMENT 'Matricule unique',
    `annee_scolaire` varchar(15)  NOT NULL DEFAULT '2026-2027' COMMENT 'AJOUT : année d''inscription dans cette classe',
    `date_naissance` date         DEFAULT NULL,
    `lieu_naissance` varchar(100) DEFAULT NULL,
    `sexe`           enum('M','F') DEFAULT NULL,
    `adresse`        text         DEFAULT NULL,
    `telephone`      varchar(20)  DEFAULT NULL,
    `email_parent`   varchar(150) DEFAULT NULL COMMENT 'Email du parent pour notifications',
    `est_actif`      tinyint(1)   DEFAULT 1 COMMENT 'Actif ou transféré'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : programme_officiel
-- Saisi par le censeur avant le début de l'année
-- ============================================================
CREATE TABLE `programme_officiel` (
    `id_programme`        int(11)      NOT NULL COMMENT 'Identifiant du programme',
    `id_matiere`          int(11)      NOT NULL,
    `id_utilisateur`      int(11)      NOT NULL COMMENT 'Censeur créateur',
    `titre_programme`     varchar(200) NOT NULL,
    `description`         text         DEFAULT NULL,
    `annee_scolaire`      varchar(15)  NOT NULL,
    `volume_horaire_total` int(11)     DEFAULT NULL,
    `est_actif`           tinyint(1)   DEFAULT 1,
    `statut`              enum('BROUILLON','PUBLIE','ARCHIVE') DEFAULT 'BROUILLON',
    `date_creation`       datetime     DEFAULT current_timestamp(),
    `date_publication`    datetime     DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Programme officiel saisi par le censeur en début d''année';

INSERT INTO `programme_officiel`
    (`id_programme`,`id_matiere`,`id_utilisateur`,`titre_programme`,`description`,`annee_scolaire`,`volume_horaire_total`,`est_actif`,`statut`,`date_creation`,`date_publication`)
VALUES
    (1,5,3,'Programme Mathématiques Terminale','Programme complet mathématiques terminale','2026-2027',150,1,'PUBLIE','2026-08-14 16:08:19',NULL),
    (2,3,3,'Programme Français Terminale','Programme complet français terminale','2026-2027',150,1,'PUBLIE','2026-08-14 16:08:19',NULL),
    (3,1,3,'Programme Physique-Chimie Terminale','Programme complet physique-chimie terminale','2026-2027',120,1,'PUBLIE','2026-08-14 16:08:19',NULL);

-- ============================================================
-- TABLE : chapitre
-- ============================================================
CREATE TABLE `chapitre` (
    `id_chapitre`            int(11)      NOT NULL,
    `id_programme`           int(11)      NOT NULL,
    `titre_chapitre`         varchar(200) NOT NULL,
    `description`            text         DEFAULT NULL,
    `ordre_chapitre`         int(11)      NOT NULL COMMENT 'Ordre dans le programme',
    `objectifs_pedagogiques` text         DEFAULT NULL,
    `volume_horaire_prevu`   int(11)      DEFAULT NULL,
    `duree_semaines`         int(11)      DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chapitre` (`id_chapitre`,`id_programme`,`titre_chapitre`,`description`,`ordre_chapitre`,`objectifs_pedagogiques`,`volume_horaire_prevu`,`duree_semaines`) VALUES
    (1,1,'Fonctions exponentielles','Étude des fonctions exponentielles et logarithmes',1,'Maîtriser les propriétés des fonctions exponentielles',20,4),
    (2,1,'Dérivation et applications','Étude des dérivées et applications',2,'Maîtriser les techniques de dérivation',25,5),
    (3,1,'Intégrales','Calcul intégral et primitives',3,'Maîtriser le calcul intégral',20,4);

-- ============================================================
-- TABLE : leçon
-- AJOUT : source, id_createur, date_creation
--   source = 'officiel'   : créée par le censeur dans le programme
--   source = 'enseignant' : ajoutée par l'enseignant (point manquant)
--     → mise à jour automatique de la progression officielle
-- ============================================================
CREATE TABLE `leçon` (
    `id_leçon`               int(11)      NOT NULL COMMENT 'Identifiant de la leçon',
    `id_chapitre`            int(11)      NOT NULL,
    `titre_leçon`            varchar(200) NOT NULL,
    `objectifs_pedagogiques` text         NOT NULL,
    `contenu`                text         DEFAULT NULL,
    `ordre_leçon`            int(11)      NOT NULL COMMENT 'Ordre dans le chapitre',
    `duree_estimee`          int(11)      DEFAULT NULL COMMENT 'Durée estimée en minutes',
    `prerequis`              text         DEFAULT NULL,
    `mots_cles`              text         DEFAULT NULL,
    `source`                 enum('officiel','enseignant') NOT NULL DEFAULT 'officiel'
                             COMMENT 'AJOUT : officiel=censeur, enseignant=ajout libre',
    `id_createur`            int(11)      DEFAULT NULL
                             COMMENT 'AJOUT : FK utilisateur — NULL si import programme national',
    `date_creation`          datetime     DEFAULT current_timestamp()
                             COMMENT 'AJOUT : traçabilité de la création'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Leçons du programme — incluant les ajouts enseignants';

INSERT INTO `leçon`
    (`id_leçon`,`id_chapitre`,`titre_leçon`,`objectifs_pedagogiques`,`contenu`,`ordre_leçon`,`duree_estimee`,`prerequis`,`mots_cles`,`source`,`id_createur`)
VALUES
    (1,1,'Définition et propriétés des exponentielles','Comprendre la définition et les propriétés fondamentales','Définition, propriétés, graphique',1,120,'Fonctions usuelles',NULL,'officiel',3),
    (2,1,'Équations et inéquations exponentielles','Résoudre des équations et inéquations avec exponentielles','Méthodes de résolution',2,90,'Propriétés des exponentielles',NULL,'officiel',3),
    (3,1,'Fonctions logarithmes','Comprendre la définition et les propriétés des logarithmes','Définition, propriétés, changement de base',3,120,'Exponentielles',NULL,'officiel',3);

