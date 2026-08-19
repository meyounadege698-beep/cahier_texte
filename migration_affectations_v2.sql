-- ============================================================
-- Migration v2 : Affectations multi-départements/classes/salles/matières
-- À exécuter dans phpMyAdmin sur la base `cahierdetexte`
-- Date : 2026-08-19
-- ============================================================

USE `cahierdetexte`;

-- ── 1. Ajouter id_departement dans affectation_enseignant ───
-- Permet de filtrer/grouper les affectations par département
-- sans faire de JOIN sur matiere à chaque fois.
-- Nullable car déduit de id_matiere → matiere.id_departement
ALTER TABLE `affectation_enseignant`
    ADD COLUMN `id_departement` int(11) DEFAULT NULL
        COMMENT 'Département de la matière (dénormalisation pour filtrage rapide)'
        AFTER `id_matiere`;

-- Clé étrangère nullable
ALTER TABLE `affectation_enseignant`
    ADD CONSTRAINT `fk_affectation_departement`
    FOREIGN KEY (`id_departement`) REFERENCES `departement` (`id_departement`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Index pour les requêtes de filtrage par département
ALTER TABLE `affectation_enseignant`
    ADD KEY `idx_affectation_departement` (`id_departement`);

-- ── 2. Peupler id_departement pour les lignes existantes ────
UPDATE `affectation_enseignant` ae
JOIN `matiere` m ON ae.id_matiere = m.id_matiere
SET ae.id_departement = m.id_departement
WHERE ae.id_departement IS NULL;

-- ── 3. Vérification : afficher le résultat ──────────────────
SELECT
    ae.id_affectation,
    CONCAT(u.nom, ' ', u.prenom)  AS enseignant,
    d.nom_departement,
    m.nom_matiere,
    c.nom_classe,
    s.nom_salle,
    ae.annee_scolaire
FROM affectation_enseignant ae
JOIN utilisateur u  ON ae.id_utilisateur  = u.id_utilisateur
JOIN matiere m      ON ae.id_matiere      = m.id_matiere
JOIN departement d  ON ae.id_departement  = d.id_departement
JOIN classe c       ON ae.id_classe       = c.id_classe
LEFT JOIN salle s   ON ae.id_salle        = s.id_salle
ORDER BY u.nom, d.nom_departement, m.nom_matiere;
