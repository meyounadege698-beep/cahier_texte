-- ============================================================
-- Migration : Salles de classe + enrichissement affectations
-- À exécuter dans phpMyAdmin sur la base `cahierdetexte`
-- Date : 2026-08-18
-- ============================================================

USE `cahierdetexte`;

-- ── 1. Table salle ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `salle` (
    `id_salle`     int(11)     NOT NULL AUTO_INCREMENT,
    `nom_salle`    varchar(50) NOT NULL COMMENT 'Ex : Salle A1, Labo Physique',
    `capacite`     int(11)     DEFAULT NULL COMMENT 'Nombre de places',
    `type_salle`   enum('classe','laboratoire','salle_info','amphi','autre')
                               DEFAULT 'classe' COMMENT 'Type de salle',
    `localisation` varchar(100) DEFAULT NULL COMMENT 'Bâtiment / niveau',
    `est_active`   tinyint(1)  DEFAULT 1,
    PRIMARY KEY (`id_salle`),
    UNIQUE KEY `uq_nom_salle` (`nom_salle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Salles de l''établissement';

-- ── 2. Ajouter id_salle dans affectation_enseignant ─────────
ALTER TABLE `affectation_enseignant`
    ADD COLUMN `id_salle` int(11) DEFAULT NULL
        COMMENT 'Salle habituelle pour cette affectation'
        AFTER `id_matiere`;

-- Clé étrangère (nullable : une affectation peut ne pas avoir de salle fixe)
ALTER TABLE `affectation_enseignant`
    ADD CONSTRAINT `fk_affectation_salle`
    FOREIGN KEY (`id_salle`) REFERENCES `salle` (`id_salle`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- ── 3. Ajouter id_salle dans seance (salle réelle de la séance) ──
ALTER TABLE `seance`
    ADD COLUMN `id_salle` int(11) DEFAULT NULL
        COMMENT 'Salle où la séance a eu lieu'
        AFTER `id_progression`;

ALTER TABLE `seance`
    ADD CONSTRAINT `fk_seance_salle`
    FOREIGN KEY (`id_salle`) REFERENCES `salle` (`id_salle`)
    ON DELETE SET NULL ON UPDATE CASCADE;
