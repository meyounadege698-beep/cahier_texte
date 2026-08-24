<?php

/**
 * ProgressionOfficielleV2Model
 * Gestion complète de la progression officielle structurée par semaine.
 * Conforme à la structure du Ministère de l'Éducation du Cameroun.
 *
 * Hiérarchie : programme → semaine → chapitre → leçon → objectif_lecon
 */
class ProgressionOfficielleV2Model
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  DONNÉES DE RÉFÉRENCE
    // =========================================================

    public function getAllDepartements(): array
    {
        $result = $this->db->query(
            "SELECT * FROM departement ORDER BY nom_departement"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getMatieresByDepartement(int $idDept): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM matiere WHERE id_departement = ? ORDER BY nom_matiere"
        );
        $stmt->bind_param("i", $idDept);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getAnneesScolaires(): array
    {
        $m = (int)date('m'); $y = (int)date('Y');
        $debut = $m >= 9 ? $y : $y - 1;
        $annees = [];
        for ($i = 0; $i <= 2; $i++) {
            $annees[] = ($debut + $i) . '-' . ($debut + $i + 1);
        }
        return $annees;
    }

    // =========================================================
    //  PROGRAMME OFFICIEL
    // =========================================================

    public function createProgramme(int $idMatiere, int $idUtilisateur, string $titre,
                                     string $annee, ?string $description,
                                     ?int $volumeHoraire): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO programme_officiel
             (id_matiere, id_utilisateur, titre_programme, annee_scolaire,
              description, volume_horaire_total, statut, est_actif, date_creation)
             VALUES (?, ?, ?, ?, ?, ?, 'BROUILLON', 1, NOW())"
        );
        $stmt->bind_param("iisssi", $idMatiere, $idUtilisateur, $titre,
                          $annee, $description, $volumeHoraire);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function getProgrammeById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT po.*, m.nom_matiere, m.code_matiere,
                    d.nom_departement, d.id_departement,
                    u.nom AS censeur_nom, u.prenom AS censeur_prenom
             FROM programme_officiel po
             JOIN matiere m     ON po.id_matiere     = m.id_matiere
             JOIN departement d ON m.id_departement  = d.id_departement
             JOIN utilisateur u ON po.id_utilisateur = u.id_utilisateur
             WHERE po.id_programme = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getProgrammesByCenseur(int $idUtilisateur): array
    {
        $stmt = $this->db->prepare(
            "SELECT po.*,
                    m.nom_matiere, m.code_matiere,
                    d.nom_departement,
                    (SELECT COUNT(*) FROM semaine_programme sp WHERE sp.id_programme = po.id_programme) AS nb_semaines,
                    (SELECT COUNT(*) FROM chapitre ch WHERE ch.id_programme = po.id_programme) AS nb_chapitres,
                    (SELECT COUNT(*) FROM leçon l JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
                     WHERE ch.id_programme = po.id_programme) AS nb_lecons
             FROM programme_officiel po
             JOIN matiere m     ON po.id_matiere    = m.id_matiere
             JOIN departement d ON m.id_departement = d.id_departement
             WHERE po.id_utilisateur = ?
             ORDER BY po.annee_scolaire DESC, m.nom_matiere"
        );
        $stmt->bind_param("i", $idUtilisateur);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function publierProgramme(int $id, int $idCenseur): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE programme_officiel
             SET statut = 'PUBLIE', date_publication = NOW()
             WHERE id_programme = ? AND id_utilisateur = ? AND statut = 'BROUILLON'"
        );
        $stmt->bind_param("ii", $id, $idCenseur);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    // =========================================================
    //  SEMAINES
    // =========================================================

    public function getSemainesByProgramme(int $idProgramme): array
    {
        $stmt = $this->db->prepare(
            "SELECT sp.*,
                    (SELECT COUNT(*) FROM chapitre ch WHERE ch.id_semaine = sp.id_semaine) AS nb_chapitres
             FROM semaine_programme sp
             WHERE sp.id_programme = ?
             ORDER BY sp.numero_semaine"
        );
        $stmt->bind_param("i", $idProgramme);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addSemaine(int $idProgramme, int $numero,
                                string $dateDebut, string $dateFin,
                                ?string $titrePeriode): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO semaine_programme
             (id_programme, numero_semaine, date_debut, date_fin, titre_periode)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $idProgramme, $numero, $dateDebut, $dateFin, $titrePeriode);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function deleteSemaine(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM semaine_programme WHERE id_semaine = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    // =========================================================
    //  CHAPITRES
    // =========================================================

    public function getChapitresBySemaine(int $idSemaine): array
    {
        $stmt = $this->db->prepare(
            "SELECT ch.*,
                    (SELECT COUNT(*) FROM leçon l WHERE l.id_chapitre = ch.id_chapitre) AS nb_lecons,
                    (SELECT COALESCE(SUM(l.nb_heures), 0) FROM leçon l WHERE l.id_chapitre = ch.id_chapitre) AS total_heures
             FROM chapitre ch
             WHERE ch.id_semaine = ?
             ORDER BY ch.ordre_chapitre"
        );
        $stmt->bind_param("i", $idSemaine);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addChapitre(int $idProgramme, int $idSemaine, string $titre,
                                 ?string $competences, ?string $description,
                                 ?string $objectifsPeda, ?int $volumeHoraire,
                                 ?int $dureeSemaines): int
    {
        // Calculer le prochain ordre
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(ordre_chapitre), 0) + 1 AS next_ordre
             FROM chapitre WHERE id_programme = ?"
        );
        $stmt->bind_param("i", $idProgramme);
        $stmt->execute();
        $ordre = (int)$stmt->get_result()->fetch_assoc()['next_ordre'];
        $stmt->close();

        $stmt = $this->db->prepare(
            "INSERT INTO chapitre
             (id_programme, id_semaine, titre_chapitre, competences_semaine,
              description, ordre_chapitre, objectifs_pedagogiques,
              volume_horaire_prevu, duree_semaines)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisssisii", $idProgramme, $idSemaine, $titre,
                          $competences, $description, $ordre,
                          $objectifsPeda, $volumeHoraire, $dureeSemaines);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function deleteChapitre(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM chapitre WHERE id_chapitre = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    // =========================================================
    //  LEÇONS
    // =========================================================

    public function getLeconsByChapitreWithObjectifs(int $idChapitre): array
    {
        $stmt = $this->db->prepare(
            "SELECT l.* FROM leçon l WHERE l.id_chapitre = ? ORDER BY l.ordre_leçon"
        );
        $stmt->bind_param("i", $idChapitre);
        $stmt->execute();
        $lecons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($lecons as &$l) {
            $l['objectifs'] = $this->getObjectifsByLecon((int)$l['id_leçon']);
        }
        return $lecons;
    }

    public function addLecon(int $idChapitre, string $titre, string $grandTitre,
                              string $type, ?string $objectifsPeda,
                              ?float $nbHeures, ?string $prerequis,
                              ?string $motsCles): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(ordre_leçon), 0) + 1 AS next_ordre
             FROM leçon WHERE id_chapitre = ?"
        );
        $stmt->bind_param("i", $idChapitre);
        $stmt->execute();
        $ordre = (int)$stmt->get_result()->fetch_assoc()['next_ordre'];
        $stmt->close();

        $stmt = $this->db->prepare(
            "INSERT INTO leçon
             (id_chapitre, titre_leçon, grand_titre, type_lecon,
              objectifs_pedagogiques, ordre_leçon, nb_heures,
              prerequis, mots_cles, source, date_creation)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'officiel', NOW())"
        );
        $stmt->bind_param("sssssidss", $titre, $grandTitre, $type,
                          $objectifsPeda, $idChapitre, $ordre,
                          $nbHeures, $prerequis, $motsCles);

        // Correction : bind_param doit correspondre à l'ordre des ?
        $stmt->close();
        $stmt = $this->db->prepare(
            "INSERT INTO leçon
             (id_chapitre, titre_leçon, grand_titre, type_lecon,
              objectifs_pedagogiques, ordre_leçon, nb_heures,
              prerequis, mots_cles, source, date_creation)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'officiel', NOW())"
        );
        $stmt->bind_param("issssiiss",
            $idChapitre, $titre, $grandTitre, $type,
            $objectifsPeda, $ordre, $nbHeures, $prerequis, $motsCles
        );
        $stmt->execute();
        $id = (int)$this->db->insert_id;
        $stmt->close();
        return $id;
    }

    public function deleteLecon(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM leçon WHERE id_leçon = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    // =========================================================
    //  OBJECTIFS PÉDAGOGIQUES
    // =========================================================

    public function getObjectifsByLecon(int $idLecon): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM objectif_lecon WHERE id_leçon = ? ORDER BY ordre"
        );
        $stmt->bind_param("i", $idLecon);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addObjectif(int $idLecon, string $libelle,
                                 string $type = 'savoir_faire'): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(ordre), 0) + 1 AS next FROM objectif_lecon WHERE id_leçon = ?"
        );
        $stmt->bind_param("i", $idLecon);
        $stmt->execute();
        $ordre = (int)$stmt->get_result()->fetch_assoc()['next'];
        $stmt->close();

        $stmt = $this->db->prepare(
            "INSERT INTO objectif_lecon (id_leçon, libelle, type_objectif, ordre)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("issi", $idLecon, $libelle, $type, $ordre);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function deleteObjectif(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM objectif_lecon WHERE id_objectif = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    // =========================================================
    //  VUE COMPLÈTE (pour affichage temps réel + enseignant)
    // =========================================================

    /**
     * Retourne la progression complète d'un programme :
     * semaines → chapitres → leçons → objectifs
     */
    public function getProgressionComplete(int $idProgramme): array
    {
        $semaines = $this->getSemainesByProgramme($idProgramme);

        foreach ($semaines as &$s) {
            $s['chapitres'] = $this->getChapitresBySemaine((int)$s['id_semaine']);
            foreach ($s['chapitres'] as &$ch) {
                $ch['lecons'] = $this->getLeconsByChapitreWithObjectifs((int)$ch['id_chapitre']);
            }
        }
        return $semaines;
    }

    // =========================================================
    //  ATTRIBUTION À UN ENSEIGNANT
    // =========================================================

    /**
     * Attribue un programme à un enseignant en créant les progressions individuelles.
     */
    public function attribuerAEnseignant(int $idProgramme, int $idEnseignant,
                                          int $idClasse): array
    {
        $programme = $this->getProgrammeById($idProgramme);
        if (!$programme || $programme['statut'] !== 'PUBLIE') {
            return ['success' => false, 'message' => 'Programme introuvable ou non publié.'];
        }

        $idMatiere = (int)$programme['id_matiere'];
        $annee     = $programme['annee_scolaire'];

        $affectation = $this->db->prepare(
            "SELECT 1
             FROM affectation_enseignant
             WHERE id_utilisateur = ?
               AND id_classe = ?
               AND id_matiere = ?
               AND annee_scolaire = ?
             LIMIT 1"
        );
        $affectation->bind_param("iiis", $idEnseignant, $idClasse, $idMatiere, $annee);
        $affectation->execute();
        $affectation->store_result();
        $isAssigned = $affectation->num_rows > 0;
        $affectation->close();

        if (!$isAssigned) {
            return [
                'success' => false,
                'message' => 'Cet enseignant n\'est pas affecté à cette classe et à cette matière pour l\'année scolaire sélectionnée.'
            ];
        }

        // Récupérer toutes les leçons du programme avec leurs semaines
        $stmt = $this->db->prepare(
            "SELECT l.id_leçon, l.titre_leçon,
                    COALESCE(sp.date_debut, CURDATE()) AS date_semaine
             FROM leçon l
             JOIN chapitre ch ON l.id_chapitre = ch.id_chapitre
             LEFT JOIN semaine_programme sp ON ch.id_semaine = sp.id_semaine
             WHERE ch.id_programme = ?
             ORDER BY COALESCE(sp.numero_semaine, 999), ch.ordre_chapitre, l.ordre_leçon"
        );
        $stmt->bind_param("i", $idProgramme);
        $stmt->execute();
        $lecons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $created = 0;
        $skipped = 0;

        $this->db->begin_transaction();
        try {
            foreach ($lecons as $l) {
                $idLecon    = (int)$l['id_leçon'];
                $dateDebut  = $l['date_semaine'];

                // Vérifier doublon
                $check = $this->db->prepare(
                    "SELECT 1 FROM progression_programme
                     WHERE id_utilisateur=? AND id_leçon=? AND id_classe=? AND id_matiere=?"
                );
                $check->bind_param("iiii", $idEnseignant, $idLecon, $idClasse, $idMatiere);
                $check->execute();
                $check->store_result();
                if ($check->num_rows > 0) { $skipped++; $check->close(); continue; }
                $check->close();

                $ins = $this->db->prepare(
                    "INSERT INTO progression_programme
                     (id_utilisateur, id_leçon, id_classe, id_matiere,
                      date_debut, statut, progression_pourcentage)
                     VALUES (?, ?, ?, ?, ?, 'NON_COMMENCEE', 0)"
                );
                $ins->bind_param("iiiis", $idEnseignant, $idLecon, $idClasse, $idMatiere, $dateDebut);
                $ins->execute();
                $ins->close();
                $created++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'message' => "{$created} progression(s) créée(s), {$skipped} doublon(s) ignoré(s)."
        ];
    }

    // =========================================================
    //  PROGRESSION DE L'ENSEIGNANT (vue enrichie)
    // =========================================================

    /**
     * Retourne la progression attribuée à un enseignant, enrichie
     * avec les objectifs et le statut de chaque leçon.
     */
    public function getProgressionEnseignant(int $idEnseignant, int $idClasse,
                                              int $idMatiere): array
    {
        $stmt = $this->db->prepare(
            "SELECT pp.*,
                    l.titre_leçon, l.grand_titre, l.type_lecon, l.nb_heures,
                    l.objectifs_pedagogiques,
                    ch.titre_chapitre, ch.competences_semaine, ch.ordre_chapitre,
                    sp.numero_semaine, sp.date_debut AS semaine_debut,
                    sp.date_fin AS semaine_fin, sp.titre_periode,
                    po.titre_programme, po.annee_scolaire
             FROM progression_programme pp
             JOIN leçon l          ON pp.id_leçon      = l.id_leçon
             JOIN chapitre ch      ON l.id_chapitre    = ch.id_chapitre
             JOIN programme_officiel po ON ch.id_programme = po.id_programme
             LEFT JOIN semaine_programme sp ON ch.id_semaine = sp.id_semaine
             WHERE pp.id_utilisateur = ? AND pp.id_classe = ? AND pp.id_matiere = ?
             ORDER BY COALESCE(sp.numero_semaine, 999), ch.ordre_chapitre, l.ordre_leçon"
        );
        $stmt->bind_param("iii", $idEnseignant, $idClasse, $idMatiere);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Enrichir avec les objectifs individuels
        foreach ($rows as &$r) {
            $r['objectifs'] = $this->getObjectifsByLecon((int)$r['id_leçon']);
        }
        return $rows;
    }

    // =========================================================
    //  OBJECTIFS ATTEINTS (cahier de texte)
    // =========================================================

    public function getObjectifsAtteintsBySeance(int $idSeance): array
    {
        $stmt = $this->db->prepare(
            "SELECT oa.*, ol.libelle, ol.type_objectif
             FROM objectif_atteint oa
             JOIN objectif_lecon ol ON oa.id_objectif = ol.id_objectif
             WHERE oa.id_seance = ?"
        );
        $stmt->bind_param("i", $idSeance);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function saveObjectifsAtteints(int $idSeance, array $objectifs): void
    {
        // objectifs = [id_objectif => ['atteint' => 1|0, 'commentaire' => '...']]
        $stmt = $this->db->prepare(
            "INSERT INTO objectif_atteint (id_seance, id_objectif, est_atteint, commentaire)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               est_atteint  = VALUES(est_atteint),
               commentaire  = VALUES(commentaire),
               date_evaluation = NOW()"
        );
        foreach ($objectifs as $idObj => $data) {
            $idObj    = (int)$idObj;
            $atteint  = (int)($data['atteint']     ?? 0);
            $comment  = trim($data['commentaire']  ?? '') ?: null;
            $stmt->bind_param("iiis", $idSeance, $idObj, $atteint, $comment);
            $stmt->execute();
        }
        $stmt->close();
    }

    // =========================================================
    //  ENSEIGNANTS AFFECTÉS À UN PROGRAMME (pour attribution)
    // =========================================================

    public function getEnseignantsAffectesAuProgramme(int $idProgramme): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom, u.email,
                    c.id_classe, c.nom_classe, c.niveau,
                    ae.annee_scolaire,
                    (SELECT COUNT(*) FROM progression_programme pp
                     WHERE pp.id_utilisateur = u.id_utilisateur
                       AND pp.id_matiere = po.id_matiere
                       AND pp.id_classe = ae.id_classe) AS deja_attribue
             FROM affectation_enseignant ae
             JOIN utilisateur u         ON ae.id_utilisateur = u.id_utilisateur
             JOIN classe c              ON ae.id_classe       = c.id_classe
             JOIN programme_officiel po ON ae.id_matiere = po.id_matiere
                                      AND ae.annee_scolaire = po.annee_scolaire
             WHERE po.id_programme = ? AND u.est_actif = 1
             ORDER BY u.nom, c.nom_classe"
        );
        $stmt->bind_param("i", $idProgramme);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function isEnseignantAffecteAuProgramme(int $idProgramme, int $idEnseignant, int $idClasse): bool
    {
        $programme = $this->getProgrammeById($idProgramme);
        if (!$programme) {
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT 1
             FROM affectation_enseignant
             WHERE id_utilisateur = ?
               AND id_classe = ?
               AND id_matiere = ?
               AND annee_scolaire = ?
             LIMIT 1"
        );
        $stmt->bind_param("iiis", $idEnseignant, $idClasse, (int)$programme['id_matiere'], $programme['annee_scolaire']);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();

        return $ok;
    }
}
