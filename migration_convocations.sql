-- ============================================================
-- Migration : Table convocation
-- Pour l'envoi de convocations aux enseignants depuis la supervision
-- Date : 2026-08-23
-- ============================================================

USE `cahierdetexte`;

CREATE TABLE IF NOT EXISTS `convocation` (
    `id_convocation`   int(11)     NOT NULL AUTO_INCREMENT,
    `id_enseignant`    int(11)     NOT NULL COMMENT 'Enseignant convoqué',
    `id_admin`         int(11)     NOT NULL COMMENT 'Administrateur/censeur émetteur',
    `motif`            text        NOT NULL COMMENT 'Motif de la convocation',
    `date_convocation` datetime    NOT NULL COMMENT 'Date/heure de la convocation',
    `lieu`             varchar(200) DEFAULT NULL COMMENT 'Lieu de la convocation',
    `statut`           enum('envoyee','lue','acquittee') DEFAULT 'envoyee',
    `date_envoi`       datetime    DEFAULT current_timestamp(),
    `date_lecture`     datetime    DEFAULT NULL,
    PRIMARY KEY (`id_convocation`),
    KEY `idx_conv_enseignant` (`id_enseignant`),
    KEY `idx_conv_admin`      (`id_admin`),
    KEY `idx_conv_statut`     (`statut`),
    CONSTRAINT `fk_conv_enseignant`
        FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateur` (`id_utilisateur`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_conv_admin`
        FOREIGN KEY (`id_admin`) REFERENCES `utilisateur` (`id_utilisateur`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Convocations envoyées aux enseignants';
