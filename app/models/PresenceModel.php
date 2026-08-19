<?php

/**
 * PresenceModel — Gestion des appels et présences élèves.
 * Tables : presence, eleve, seance, classe
 */
class PresenceModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =========================================================
    //  ÉLÈVES D'UNE CLASSE
    // =========================================================

    public function getElevesByClasse(int $idClasse): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_eleve, nom, prenom, matricule, sexe
             FROM eleve
             WHERE id_classe = ? AND est_actif = 1
             ORDER BY nom, prenom"
        );
        $stmt->bind_param("i", $idClasse);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================
    //  PRÉSENCES D'UNE SÉANCE
    // =========================================================

    /**
     * Retourne les présences déjà enregistrées pour une séance.
     * Indexées par id_eleve pour lookup O(1).
     */
    public function getPresencesBySeance(int $idSeance): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, e.nom, e.prenom, e.matricule
             FROM presence p
             JOIN eleve e ON p.id_eleve = e.id_eleve
             WHERE p.id_seance = ?
             ORDER BY e.nom, e.prenom"
        );
        $stmt->bind_param("i", $idSeance);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Indexer par id_eleve
        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['id_eleve']] = $r;
        }
        return $indexed;
    }

    /**
     * Enregistre ou met à jour la présence d'un élève pour une séance.
     */
    public function savePresence(int $idSeance, int $idEleve,
                                  string $statut, ?string $motif): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO presence (id_seance, id_eleve, statut_presence, motif_absence)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               statut_presence = VALUES(statut_presence),
               motif_absence   = VALUES(motif_absence),
               date_appel      = CURRENT_TIMESTAMP"
        );
        $stmt->bind_param("iiss", $idSeance, $idEleve, $statut, $motif);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Enregistre l'appel complet d'une séance (tableau statuts[id_eleve]).
     */
    public function saveAppelComplet(int $idSeance, array $statuts, array $motifs): int
    {
        $saved = 0;
        foreach ($statuts as $idEleve => $statut) {
            $idEleve = (int)$idEleve;
            $statut  = in_array($statut, ['PRESENT','ABSENT','RETARD','EXCUSE'])
                       ? $statut : 'PRESENT';
            $motif   = !empty($motifs[$idEleve]) ? trim($motifs[$idEleve]) : null;
            $this->savePresence($idSeance, $idEleve, $statut, $motif);
            $saved++;
        }
        return $saved;
    }

    // =========================================================
    //  SÉANCES D'UNE CLASSE (pour choisir la séance d'appel)
    // =========================================================

    public function getSeancesForAppel(int $idEnseignant, int $idClasse): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id_seance, s.date_seance, s.heure_debut, s.heure_fin,
                    s.contenu_traite, m.nom_matiere,
                    (SELECT COUNT(*) FROM presence p WHERE p.id_seance = s.id_seance) AS nb_appels
             FROM seance s
             JOIN matiere m ON s.id_matiere = m.id_matiere
             WHERE s.id_utilisateur = ? AND s.id_classe = ? AND s.statut = 'REALISEE'
             ORDER BY s.date_seance DESC, s.heure_debut DESC
             LIMIT 20"
        );
        $stmt->bind_param("ii", $idEnseignant, $idClasse);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSeanceInfo(int $idSeance): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.nom_classe, m.nom_matiere,
                    CONCAT(u.nom,' ',u.prenom) AS nom_enseignant
             FROM seance s
             JOIN classe c      ON s.id_classe      = c.id_classe
             JOIN matiere m     ON s.id_matiere     = m.id_matiere
             JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur
             WHERE s.id_seance = ?"
        );
        $stmt->bind_param("i", $idSeance);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    // =========================================================
    //  HISTORIQUE ASSIDUITÉ
    // =========================================================

    /**
     * Historique de présence d'un élève.
     */
    public function getHistoriqueEleve(int $idEleve): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, s.date_seance, s.heure_debut, s.heure_fin,
                    m.nom_matiere, c.nom_classe
             FROM presence p
             JOIN seance s  ON p.id_seance  = s.id_seance
             JOIN matiere m ON s.id_matiere = m.id_matiere
             JOIN classe c  ON s.id_classe  = c.id_classe
             WHERE p.id_eleve = ?
             ORDER BY s.date_seance DESC, s.heure_debut DESC"
        );
        $stmt->bind_param("i", $idEleve);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Statistiques d'assiduité d'une classe.
     */
    public function getStatsClasse(int $idClasse, string $annee = ''): array
    {
        if (!$annee) {
            $m = (int)date('m'); $y = (int)date('Y');
            $annee = $m >= 9 ? "{$y}-".($y+1) : ($y-1)."-{$y}";
        }
        $stmt = $this->db->prepare(
            "SELECT e.id_eleve, e.nom, e.prenom, e.matricule,
                    COUNT(p.id_presence)                               AS total_seances,
                    SUM(p.statut_presence = 'PRESENT')                 AS nb_present,
                    SUM(p.statut_presence = 'ABSENT')                  AS nb_absent,
                    SUM(p.statut_presence = 'RETARD')                  AS nb_retard,
                    SUM(p.statut_presence = 'EXCUSE')                  AS nb_excuse,
                    ROUND(SUM(p.statut_presence='PRESENT')*100/
                          NULLIF(COUNT(p.id_presence),0),1)            AS taux_presence
             FROM eleve e
             LEFT JOIN presence p ON e.id_eleve = p.id_eleve
             LEFT JOIN seance s ON p.id_seance = s.id_seance
                              AND s.date_seance LIKE CONCAT(LEFT(?,4),'%')
             WHERE e.id_classe = ? AND e.est_actif = 1
             GROUP BY e.id_eleve
             ORDER BY e.nom, e.prenom"
        );
        $stmt->bind_param("si", $annee, $idClasse);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
