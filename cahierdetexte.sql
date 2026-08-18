-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 18 août 2026 à 10:52
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `cahierdetexte`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `creer_progression_annuelle` (IN `p_enseignant_id` INT, IN `p_classe_id` INT, IN `p_matiere_id` INT, IN `p_annee_scolaire` VARCHAR(15))   BEGIN
    DECLARE v_programme_id INT;
    
    IF NOT EXISTS (SELECT 1 FROM utilisateur WHERE id_utilisateur = p_enseignant_id AND role = 'enseignant') THEN
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
    SELECT 
        p_enseignant_id, 
        l.id_leçon, 
        p_classe_id, 
        p_matiere_id, 
        CURDATE(), 
        'NON_COMMENCEE'
    FROM leçon l
    JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
    WHERE ch.id_programme = v_programme_id
    ORDER BY ch.ordre_chapitre, l.ordre_leçon;
    
    SELECT COUNT(*) AS lecons_planifiees
    FROM leçon l 
    JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
    WHERE ch.id_programme = v_programme_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `generer_rapport_progression` (IN `p_enseignant_id` INT, IN `p_classe_id` INT, IN `p_matiere_id` INT, IN `p_date_debut` DATE, IN `p_date_fin` DATE)   BEGIN
    SELECT 
        CONCAT(u.nom, ' ', u.prenom) AS enseignant,
        c.nom_classe,
        m.nom_matiere,
        ch.titre_chapitre,
        l.titre_leçon,
        pp.date_debut,
        pp.date_fin,
        pp.statut,
        pp.progression_pourcentage,
        pp.observation_professeur,
        vp.statut AS validation_statut,
        vp.commentaire AS validation_commentaire,
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `mettre_a_jour_progression` (IN `p_progression_id` INT, IN `p_statut` VARCHAR(20), IN `p_pourcentage` INT, IN `p_observation` TEXT, IN `p_ressources` TEXT, IN `p_difficulte` TEXT)   BEGIN
    IF NOT EXISTS (SELECT 1 FROM progression_programme WHERE id_progression = p_progression_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Progression non trouvée';
    END IF;
    
    UPDATE progression_programme SET
        statut = p_statut,
        progression_pourcentage = p_pourcentage,
        observation_professeur = p_observation,
        ressources_utilisees = p_ressources,
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `valider_progression` (IN `p_progression_id` INT, IN `p_censeur_id` INT, IN `p_statut` VARCHAR(20), IN `p_commentaire` TEXT, IN `p_ecart` TEXT, IN `p_actions` TEXT)   BEGIN
    IF NOT EXISTS (SELECT 1 FROM utilisateur WHERE id_utilisateur = p_censeur_id AND role = 'censeur') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'L''utilisateur n''est pas un censeur valide';
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM progression_programme WHERE id_progression = p_progression_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Progression non trouvée';
    END IF;
    
    INSERT INTO validation_progression
        (id_progression, id_utilisateur, statut, commentaire, ecart_programme, actions_correctives, date_validation)
    VALUES 
        (p_progression_id, p_censeur_id, p_statut, p_commentaire, p_ecart, p_actions, CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE
        id_utilisateur = p_censeur_id,
        statut = p_statut,
        commentaire = p_commentaire,
        ecart_programme = p_ecart,
        actions_correctives = p_actions,
        date_validation = CURRENT_TIMESTAMP;
    
    SELECT 'Validation enregistrée avec succès' AS message;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `affectation_enseignant`
--

CREATE TABLE `affectation_enseignant` (
  `id_affectation` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `annee_scolaire` varchar(15) NOT NULL,
  `volume_horaire_hebdo` int(11) DEFAULT NULL,
  `est_principal` tinyint(1) DEFAULT 0,
  `date_affectation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `chapitre`
--

CREATE TABLE `chapitre` (
  `id_chapitre` int(11) NOT NULL,
  `id_programme` int(11) NOT NULL,
  `titre_chapitre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `ordre_chapitre` int(11) NOT NULL,
  `objectifs_pedagogiques` text DEFAULT NULL,
  `volume_horaire_prevu` int(11) DEFAULT NULL,
  `duree_semaines` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `classe`
--

CREATE TABLE `classe` (
  `id_classe` int(11) NOT NULL,
  `nom_classe` varchar(50) NOT NULL,
  `niveau` varchar(30) NOT NULL,
  `filiere` varchar(50) DEFAULT NULL,
  `annee_scolaire` varchar(15) NOT NULL,
  `effectif_max` int(11) DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `departement`
--

CREATE TABLE `departement` (
  `id_departement` int(11) NOT NULL,
  `nom_departement` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `code_departement` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devoir`
--

CREATE TABLE `devoir` (
  `id_devoir` int(11) NOT NULL,
  `id_seance` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `consigne` text NOT NULL,
  `type_devoir` enum('DM','DS','EVAL','PROJET') NOT NULL,
  `date_remise` date NOT NULL,
  `coeff_notation` int(11) DEFAULT 1,
  `note_sur` int(11) DEFAULT 20,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `eleve`
--

CREATE TABLE `eleve` (
  `id_eleve` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `matricule` varchar(30) NOT NULL,
  `annee_scolaire` varchar(15) NOT NULL DEFAULT '2026-2027',
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(100) DEFAULT NULL,
  `sexe` enum('M','F') DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email_parent` varchar(150) DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `leçon`
--

CREATE TABLE `leçon` (
  `id_leçon` int(11) NOT NULL,
  `id_chapitre` int(11) NOT NULL,
  `titre_leçon` varchar(200) NOT NULL,
  `objectifs_pedagogiques` text NOT NULL,
  `contenu` text DEFAULT NULL,
  `ordre_leçon` int(11) NOT NULL,
  `duree_estimee` int(11) DEFAULT NULL,
  `prerequis` text DEFAULT NULL,
  `mots_cles` text DEFAULT NULL,
  `source` enum('officiel','enseignant') NOT NULL DEFAULT 'officiel',
  `id_createur` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `matiere`
--

CREATE TABLE `matiere` (
  `id_matiere` int(11) NOT NULL,
  `id_departement` int(11) NOT NULL,
  `nom_matiere` varchar(100) NOT NULL,
  `code_matiere` varchar(20) DEFAULT NULL,
  `coefficient` decimal(3,1) DEFAULT 1.0,
  `volume_horaire_annuel` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `piece_jointe`
--

CREATE TABLE `piece_jointe` (
  `id_piece` int(11) NOT NULL,
  `id_seance` int(11) NOT NULL,
  `url_fichier` varchar(255) NOT NULL,
  `type_fichier` varchar(50) DEFAULT NULL,
  `nom_original` varchar(150) NOT NULL,
  `taille_fichier` bigint(20) DEFAULT NULL,
  `date_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presence`
--

CREATE TABLE `presence` (
  `id_presence` int(11) NOT NULL,
  `id_seance` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `statut_presence` enum('PRESENT','ABSENT','RETARD','EXCUSE') DEFAULT 'PRESENT',
  `motif_absence` text DEFAULT NULL,
  `date_appel` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `programme_officiel`
--

CREATE TABLE `programme_officiel` (
  `id_programme` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `titre_programme` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `annee_scolaire` varchar(15) NOT NULL,
  `volume_horaire_total` int(11) DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  `statut` enum('BROUILLON','PUBLIE','ARCHIVE') DEFAULT 'BROUILLON',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_publication` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `progression_programme`
--

CREATE TABLE `progression_programme` (
  `id_progression` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_leçon` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` enum('NON_COMMENCEE','EN_COURS','TERMINEE','ANNULEE') DEFAULT 'NON_COMMENCEE',
  `progression_pourcentage` int(11) DEFAULT 0,
  `observation_professeur` text DEFAULT NULL,
  `ressources_utilisees` text DEFAULT NULL,
  `difficulte_rencontree` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rapport_genere`
--

CREATE TABLE `rapport_genere` (
  `id_rapport` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type_rapport` enum('PROGRESSION','PRESENCE','EVALUATION','ANNUEL') NOT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `filtre_texte` text DEFAULT NULL,
  `date_generation` datetime DEFAULT current_timestamp(),
  `fichier_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `seance`
--

CREATE TABLE `seance` (
  `id_seance` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_progression` int(11) DEFAULT NULL,
  `date_seance` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `contenu_traite` text NOT NULL,
  `objectifs_atteints` text DEFAULT NULL,
  `commentaire_enseignant` text DEFAULT NULL,
  `statut` enum('PREVUE','EN_COURS','REALISEE','ANNULEE') DEFAULT 'PREVUE',
  `date_saisie` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL,
  `mot_de_passe_hash` varchar(255) NOT NULL,
  `role` enum('enseignant','censeur','administrateur') NOT NULL,
  `date_inscription` datetime DEFAULT current_timestamp(),
  `derniere_connexion` datetime DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `validation_censeur`
--

CREATE TABLE `validation_censeur` (
  `id_validation` int(11) NOT NULL,
  `id_seance` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_validation` datetime DEFAULT current_timestamp(),
  `statut` enum('APPROUVE','REFUSE','EN_ATTENTE') DEFAULT 'EN_ATTENTE',
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `validation_progression`
--

CREATE TABLE `validation_progression` (
  `id_validation_progression` int(11) NOT NULL,
  `id_progression` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_validation` datetime DEFAULT current_timestamp(),
  `statut` enum('APPROUVE','REFUSE','EN_ATTENTE') DEFAULT 'EN_ATTENTE',
  `commentaire` text DEFAULT NULL,
  `ecart_programme` text DEFAULT NULL,
  `actions_correctives` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `affectation_enseignant`
--
ALTER TABLE `affectation_enseignant`
  ADD PRIMARY KEY (`id_affectation`),
  ADD UNIQUE KEY `unique_affectation` (`id_utilisateur`,`id_classe`,`id_matiere`,`annee_scolaire`),
  ADD KEY `idx_affectation_enseignant` (`id_utilisateur`),
  ADD KEY `idx_affectation_classe` (`id_classe`),
  ADD KEY `idx_affectation_matiere` (`id_matiere`);

--
-- Index pour la table `chapitre`
--
ALTER TABLE `chapitre`
  ADD PRIMARY KEY (`id_chapitre`),
  ADD KEY `idx_chapitre_programme` (`id_programme`);

--
-- Index pour la table `classe`
--
ALTER TABLE `classe`
  ADD PRIMARY KEY (`id_classe`);

--
-- Index pour la table `departement`
--
ALTER TABLE `departement`
  ADD PRIMARY KEY (`id_departement`),
  ADD UNIQUE KEY `code_departement` (`code_departement`);

--
-- Index pour la table `devoir`
--
ALTER TABLE `devoir`
  ADD PRIMARY KEY (`id_devoir`),
  ADD KEY `idx_devoir_seance` (`id_seance`);

--
-- Index pour la table `eleve`
--
ALTER TABLE `eleve`
  ADD PRIMARY KEY (`id_eleve`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `idx_eleve_classe` (`id_classe`);

--
-- Index pour la table `leçon`
--
ALTER TABLE `leçon`
  ADD PRIMARY KEY (`id_leçon`),
  ADD KEY `idx_leçon_chapitre` (`id_chapitre`),
  ADD KEY `idx_lecon_createur` (`id_createur`);

--
-- Index pour la table `matiere`
--
ALTER TABLE `matiere`
  ADD PRIMARY KEY (`id_matiere`),
  ADD UNIQUE KEY `code_matiere` (`code_matiere`),
  ADD KEY `idx_matiere_departement` (`id_departement`);

--
-- Index pour la table `piece_jointe`
--
ALTER TABLE `piece_jointe`
  ADD PRIMARY KEY (`id_piece`),
  ADD KEY `idx_piece_seance` (`id_seance`);

--
-- Index pour la table `presence`
--
ALTER TABLE `presence`
  ADD PRIMARY KEY (`id_presence`),
  ADD UNIQUE KEY `unique_presence` (`id_seance`,`id_eleve`),
  ADD KEY `idx_presence_seance` (`id_seance`),
  ADD KEY `idx_presence_eleve` (`id_eleve`);

--
-- Index pour la table `programme_officiel`
--
ALTER TABLE `programme_officiel`
  ADD PRIMARY KEY (`id_programme`),
  ADD KEY `idx_programme_matiere` (`id_matiere`),
  ADD KEY `idx_programme_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `progression_programme`
--
ALTER TABLE `progression_programme`
  ADD PRIMARY KEY (`id_progression`),
  ADD UNIQUE KEY `unique_progression_lecon` (`id_utilisateur`,`id_leçon`,`id_classe`,`id_matiere`),
  ADD KEY `idx_progression_enseignant` (`id_utilisateur`),
  ADD KEY `idx_progression_lecon` (`id_leçon`),
  ADD KEY `idx_progression_classe` (`id_classe`),
  ADD KEY `idx_progression_matiere` (`id_matiere`);

--
-- Index pour la table `rapport_genere`
--
ALTER TABLE `rapport_genere`
  ADD PRIMARY KEY (`id_rapport`),
  ADD KEY `idx_rapport_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `seance`
--
ALTER TABLE `seance`
  ADD PRIMARY KEY (`id_seance`),
  ADD KEY `idx_seance_enseignant` (`id_utilisateur`),
  ADD KEY `idx_seance_classe` (`id_classe`),
  ADD KEY `idx_seance_matiere` (`id_matiere`),
  ADD KEY `idx_seance_progression` (`id_progression`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `validation_censeur`
--
ALTER TABLE `validation_censeur`
  ADD PRIMARY KEY (`id_validation`),
  ADD UNIQUE KEY `unique_validation_seance` (`id_seance`),
  ADD KEY `idx_validation_seance` (`id_seance`),
  ADD KEY `idx_validation_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `validation_progression`
--
ALTER TABLE `validation_progression`
  ADD PRIMARY KEY (`id_validation_progression`),
  ADD UNIQUE KEY `unique_validation_progression` (`id_progression`),
  ADD KEY `idx_validation_progression_utilisateur` (`id_utilisateur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `affectation_enseignant`
--
ALTER TABLE `affectation_enseignant`
  MODIFY `id_affectation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `chapitre`
--
ALTER TABLE `chapitre`
  MODIFY `id_chapitre` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `classe`
--
ALTER TABLE `classe`
  MODIFY `id_classe` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `departement`
--
ALTER TABLE `departement`
  MODIFY `id_departement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devoir`
--
ALTER TABLE `devoir`
  MODIFY `id_devoir` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `eleve`
--
ALTER TABLE `eleve`
  MODIFY `id_eleve` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `leçon`
--
ALTER TABLE `leçon`
  MODIFY `id_leçon` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `matiere`
--
ALTER TABLE `matiere`
  MODIFY `id_matiere` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `piece_jointe`
--
ALTER TABLE `piece_jointe`
  MODIFY `id_piece` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `presence`
--
ALTER TABLE `presence`
  MODIFY `id_presence` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `programme_officiel`
--
ALTER TABLE `programme_officiel`
  MODIFY `id_programme` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `progression_programme`
--
ALTER TABLE `progression_programme`
  MODIFY `id_progression` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rapport_genere`
--
ALTER TABLE `rapport_genere`
  MODIFY `id_rapport` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `seance`
--
ALTER TABLE `seance`
  MODIFY `id_seance` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `validation_censeur`
--
ALTER TABLE `validation_censeur`
  MODIFY `id_validation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `validation_progression`
--
ALTER TABLE `validation_progression`
  MODIFY `id_validation_progression` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `affectation_enseignant`
--
ALTER TABLE `affectation_enseignant`
  ADD CONSTRAINT `fk_affectation_classe` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_affectation_matiere` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_affectation_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `chapitre`
--
ALTER TABLE `chapitre`
  ADD CONSTRAINT `fk_chapitre_programme` FOREIGN KEY (`id_programme`) REFERENCES `programme_officiel` (`id_programme`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `devoir`
--
ALTER TABLE `devoir`
  ADD CONSTRAINT `fk_devoir_seance` FOREIGN KEY (`id_seance`) REFERENCES `seance` (`id_seance`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `eleve`
--
ALTER TABLE `eleve`
  ADD CONSTRAINT `fk_eleve_classe` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `leçon`
--
ALTER TABLE `leçon`
  ADD CONSTRAINT `fk_lecon_chapitre` FOREIGN KEY (`id_chapitre`) REFERENCES `chapitre` (`id_chapitre`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lecon_createur` FOREIGN KEY (`id_createur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `matiere`
--
ALTER TABLE `matiere`
  ADD CONSTRAINT `fk_matiere_departement` FOREIGN KEY (`id_departement`) REFERENCES `departement` (`id_departement`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `piece_jointe`
--
ALTER TABLE `piece_jointe`
  ADD CONSTRAINT `fk_piece_seance` FOREIGN KEY (`id_seance`) REFERENCES `seance` (`id_seance`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `presence`
--
ALTER TABLE `presence`
  ADD CONSTRAINT `fk_presence_eleve` FOREIGN KEY (`id_eleve`) REFERENCES `eleve` (`id_eleve`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presence_seance` FOREIGN KEY (`id_seance`) REFERENCES `seance` (`id_seance`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `programme_officiel`
--
ALTER TABLE `programme_officiel`
  ADD CONSTRAINT `fk_programme_matiere` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_programme_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `progression_programme`
--
ALTER TABLE `progression_programme`
  ADD CONSTRAINT `fk_progression_classe` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progression_lecon` FOREIGN KEY (`id_leçon`) REFERENCES `leçon` (`id_leçon`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progression_matiere` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progression_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rapport_genere`
--
ALTER TABLE `rapport_genere`
  ADD CONSTRAINT `fk_rapport_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `seance`
--
ALTER TABLE `seance`
  ADD CONSTRAINT `fk_seance_classe` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id_classe`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seance_matiere` FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id_matiere`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seance_progression` FOREIGN KEY (`id_progression`) REFERENCES `progression_programme` (`id_progression`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_seance_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `validation_censeur`
--
ALTER TABLE `validation_censeur`
  ADD CONSTRAINT `fk_validation_censeur_seance` FOREIGN KEY (`id_seance`) REFERENCES `seance` (`id_seance`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_validation_censeur_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `validation_progression`
--
ALTER TABLE `validation_progression`
  ADD CONSTRAINT `fk_validation_progression_progression` FOREIGN KEY (`id_progression`) REFERENCES `progression_programme` (`id_progression`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_validation_progression_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
