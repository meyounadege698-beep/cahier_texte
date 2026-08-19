-- ============================================================
-- Migration v3 : Progression officielle structurée par semaine
-- Conforme au programme du Ministère de l'Éducation du Cameroun
--
-- À exécuter dans phpMyAdmin sur la base `cahierdetexte`
-- Date : 2026-08-19
--
-- Résumé des modifications :
--   1. Nouvelle table `semaine_programme`  → semaines calendaires du programme
--   2. ALTER TABLE `chapitre`              → ajout id_semaine (FK nullable)
--   3. ALTER TABLE `leçon`                 → ajout type_lecon + grand_titre
--   4. Nouvelle table `objectif_lecon`     → objectifs atomisés et cochables
--   5. Nouvelle table `objectif_atteint`   → suivi objectif par séance
-- ============================================================

USE `cahierdetexte`;

-- ============================================================
-- 1. TABLE : semaine_programme
--    Représente une semaine calendaire dans un programme officiel.
--    Chaque programme est découpé en N semaines (de la rentrée à la fin
--    d'année), conformément à la structure du Ministère camerounais.
-- ============================================================
CREATE TABLE IF NOT EXISTS `semaine_programme` (
    `id_semaine`       int(11)     NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la semaine',
    `id_programme`     int(11)     NOT NULL COMMENT 'Programme auquel appartient cette semaine',
    `numero_semaine`   int(11)     NOT NULL COMMENT 'Numéro de la semaine dans l''année (1, 2, 3...)',
    `date_debut`       date        NOT NULL COMMENT 'Premier jour de la semaine (ex: 05/09/2026)',
    `date_fin`         date        NOT NULL COMMENT 'Dernier jour de la semaine (ex: 10/09/2026)',
    `titre_periode`    varchar(200) DEFAULT NULL COMMENT 'Titre optionnel de la période / séquence',
    `observation`      text         DEFAULT NULL COMMENT 'Observation pédagogique pour cette semaine',
    `date_creation`    datetime     DEFAULT current_timestamp(),
    PRIMARY KEY (`id_semaine`),
    UNIQUE KEY `uq_semaine_programme` (`id_programme`, `numero_semaine`),
    KEY `idx_semaine_dates` (`date_debut`, `date_fin`),
    KEY `idx_semaine_programme` (`id_programme`),
    CONSTRAINT `fk_semaine_programme`
        FOREIGN KEY (`id_programme`) REFERENCES `programme_officiel` (`id_programme`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Semaines calendaires d''un programme officiel (structure Ministère Cameroun)';


-- ============================================================
-- 2. ALTER TABLE : chapitre
--    Ajout de id_semaine pour rattacher un chapitre à une semaine précise.
--    Nullable : les chapitres existants ne sont pas liés à une semaine.
-- ============================================================
ALTER TABLE `chapitre`
    ADD COLUMN `id_semaine` int(11) DEFAULT NULL
        COMMENT 'Semaine à laquelle ce chapitre est planifié (nullable pour les anciens chapitres)'
        AFTER `id_programme`,
    ADD COLUMN `competences_semaine` text DEFAULT NULL
        COMMENT 'Compétences visées pour cette semaine en une phrase (ex: Maîtriser les nombres complexes)'
        AFTER `objectifs_pedagogiques`;

-- Clé étrangère nullable (SET NULL si la semaine est supprimée)
ALTER TABLE `chapitre`
    ADD CONSTRAINT `fk_chapitre_semaine`
        FOREIGN KEY (`id_semaine`) REFERENCES `semaine_programme` (`id_semaine`)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `chapitre`
    ADD KEY `idx_chapitre_semaine` (`id_semaine`);


-- ============================================================
-- 3. ALTER TABLE : leçon
--    Ajout de :
--      - type_lecon   : Théorique ou Pratique (obligatoire selon le Ministère)
--      - grand_titre  : Le grand titre de la leçon (distinct du titre court)
--      - nb_heures    : Nombre d'heures prévu pour cette leçon
--        (duree_estimee existait en minutes → nb_heures en heures entières)
-- ============================================================
ALTER TABLE `leçon`
    ADD COLUMN `type_lecon` enum('theorique','pratique','theorique_pratique')
        NOT NULL DEFAULT 'theorique'
        COMMENT 'Type de leçon : théorique, pratique ou mixte'
        AFTER `contenu`,
    ADD COLUMN `grand_titre` varchar(300) DEFAULT NULL
        COMMENT 'Grand titre / intitulé officiel de la leçon'
        AFTER `type_lecon`,
    ADD COLUMN `nb_heures` decimal(4,1) DEFAULT NULL
        COMMENT 'Nombre d''heures prévu pour cette leçon (ex: 2.0, 1.5)'
        AFTER `grand_titre`;


-- ============================================================
-- 4. TABLE : objectif_lecon
--    Objectifs pédagogiques atomisés (un enregistrement par objectif).
--    Remplace progressivement le champ TEXT `objectifs_pedagogiques`
--    dans la table `leçon` pour permettre le cochage individuel.
--
--    Note : l'ancien champ `objectifs_pedagogiques` dans `leçon` est
--    conservé pour compatibilité avec les données existantes.
-- ============================================================
CREATE TABLE IF NOT EXISTS `objectif_lecon` (
    `id_objectif`  int(11)      NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de l''objectif',
    `id_leçon`     int(11)      NOT NULL COMMENT 'Leçon à laquelle appartient cet objectif',
    `libelle`      varchar(500) NOT NULL COMMENT 'Libellé de l''objectif pédagogique',
    `ordre`        int(11)      NOT NULL DEFAULT 1 COMMENT 'Ordre d''affichage',
    `type_objectif` enum('savoir','savoir_faire','savoir_etre')
                   DEFAULT 'savoir_faire' COMMENT 'Nature de l''objectif',
    `date_creation` datetime    DEFAULT current_timestamp(),
    PRIMARY KEY (`id_objectif`),
    KEY `idx_objectif_lecon` (`id_leçon`),
    KEY `idx_objectif_ordre` (`ordre`),
    CONSTRAINT `fk_objectif_lecon`
        FOREIGN KEY (`id_leçon`) REFERENCES `leçon` (`id_leçon`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Objectifs pédagogiques atomisés par leçon (cochables par l''enseignant)';


-- ============================================================
-- 5. TABLE : objectif_atteint
--    Suivi objectif par objectif pour chaque séance.
--    Permet à l'enseignant de cocher les objectifs atteints
--    lors du remplissage du cahier de texte.
-- ============================================================
CREATE TABLE IF NOT EXISTS `objectif_atteint` (
    `id_suivi`        int(11)   NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du suivi',
    `id_seance`       int(11)   NOT NULL COMMENT 'Séance concernée',
    `id_objectif`     int(11)   NOT NULL COMMENT 'Objectif concerné',
    `est_atteint`     tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = objectif atteint, 0 = non atteint',
    `commentaire`     text       DEFAULT NULL COMMENT 'Commentaire de l''enseignant sur cet objectif',
    `date_evaluation` datetime   DEFAULT current_timestamp() COMMENT 'Date de l''évaluation',
    PRIMARY KEY (`id_suivi`),
    UNIQUE KEY `uq_objectif_seance` (`id_seance`, `id_objectif`),
    KEY `idx_suivi_seance`    (`id_seance`),
    KEY `idx_suivi_objectif`  (`id_objectif`),
    CONSTRAINT `fk_suivi_seance`
        FOREIGN KEY (`id_seance`) REFERENCES `seance` (`id_seance`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_suivi_objectif`
        FOREIGN KEY (`id_objectif`) REFERENCES `objectif_lecon` (`id_objectif`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Suivi des objectifs pédagogiques atteints par séance (cochage par l''enseignant)';


-- ============================================================
-- 6. Mettre à jour la procédure stockée creer_progression_annuelle
--    pour qu'elle respecte la structure semaine → chapitre → leçon
-- ============================================================
DROP PROCEDURE IF EXISTS `creer_progression_annuelle`;

DELIMITER $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `creer_progression_annuelle` (
    IN `p_enseignant_id`  INT,
    IN `p_classe_id`      INT,
    IN `p_matiere_id`     INT,
    IN `p_annee_scolaire` VARCHAR(15)
)
BEGIN
    DECLARE v_programme_id INT;

    -- Vérifier que l'utilisateur est bien un enseignant
    IF NOT EXISTS (
        SELECT 1 FROM utilisateur
        WHERE id_utilisateur = p_enseignant_id AND role = 'enseignant'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'L''utilisateur n''est pas un enseignant valide';
    END IF;

    -- Récupérer le programme officiel publié actif
    SELECT id_programme INTO v_programme_id
    FROM programme_officiel
    WHERE id_matiere     = p_matiere_id
      AND annee_scolaire = p_annee_scolaire
      AND est_actif      = TRUE
      AND statut         = 'PUBLIE'
    LIMIT 1;

    IF v_programme_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Aucun programme officiel actif trouvé pour cette matière et cette année';
    END IF;

    -- Créer une progression pour chaque leçon du programme
    -- (via semaine → chapitre → leçon)
    INSERT IGNORE INTO progression_programme
        (id_utilisateur, id_leçon, id_classe, id_matiere, date_debut, statut)
    SELECT
        p_enseignant_id,
        l.id_leçon,
        p_classe_id,
        p_matiere_id,
        COALESCE(sp.date_debut, CURDATE()),  -- Date de début = date de la semaine
        'NON_COMMENCEE'
    FROM leçon l
    JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
    LEFT JOIN semaine_programme sp ON ch.id_semaine = sp.id_semaine
    WHERE ch.id_programme = v_programme_id
    ORDER BY
        COALESCE(sp.numero_semaine, 999),
        ch.ordre_chapitre,
        l.ordre_leçon;

    -- Retourner le nombre de leçons planifiées
    SELECT COUNT(*) AS lecons_planifiees
    FROM leçon l
    JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
    WHERE ch.id_programme = v_programme_id;
END$$

DELIMITER ;


-- ============================================================
-- 7. Vérification finale : afficher la structure
-- ============================================================
SELECT 'semaine_programme' AS nouvelle_table,
       COUNT(*) AS nb_colonnes
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'cahierdetexte' AND TABLE_NAME = 'semaine_programme'

UNION ALL

SELECT 'objectif_lecon' AS nouvelle_table,
       COUNT(*) AS nb_colonnes
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'cahierdetexte' AND TABLE_NAME = 'objectif_lecon'

UNION ALL

SELECT 'objectif_atteint' AS nouvelle_table,
       COUNT(*) AS nb_colonnes
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'cahierdetexte' AND TABLE_NAME = 'objectif_atteint';

-- ============================================================
-- FIN DE LA MIGRATION v3
-- Résumé de ce qui a été créé / modifié :
--   + semaine_programme   (nouvelle table)
--   + chapitre.id_semaine (nouvelle colonne FK nullable)
--   + chapitre.competences_semaine (nouvelle colonne TEXT)
--   + leçon.type_lecon    (nouvelle colonne ENUM)
--   + leçon.grand_titre   (nouvelle colonne VARCHAR)
--   + leçon.nb_heures     (nouvelle colonne DECIMAL)
--   + objectif_lecon      (nouvelle table)
--   + objectif_atteint    (nouvelle table)
--   ~ creer_progression_annuelle (procédure mise à jour)
-- ============================================================
