-- ============================================================
-- Cahier de Texte Digital — Script SQL
-- Table : utilisateur
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Créer la base si elle n'existe pas
CREATE DATABASE IF NOT EXISTS `mon projet`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `mon projet`;

-- ============================================================
-- Structure de la table `utilisateur`
-- ============================================================
CREATE TABLE IF NOT EXISTS `utilisateur` (
    `id_utilisateur`   INT(11)      NOT NULL AUTO_INCREMENT,
    `nom`              VARCHAR(100) NOT NULL,
    `email`            VARCHAR(150) NOT NULL,
    `mot_de_passe_hash` VARCHAR(255) NOT NULL,
    `role`             ENUM('eleve', 'enseignant', 'parent', 'administrateur')
                                    NOT NULL DEFAULT 'eleve',
    `date_inscription` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_utilisateur`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
