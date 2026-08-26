<?php

/**
 * RapportModel — Données pour les rapports PDF.
 * Génération via page HTML printable (CSS @media print).
 */
class RapportModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Rapport de progression ───────────────────────────────
    public function getProgression(int $idClasse, int $idMatiere,
                                    string $annee, ?int $idEnseignant = null): array
    {
        $sql = "SELECT pp.*,
                    CONCAT(u.nom,' ',u.prenom) AS enseignant,
                    c.nom_classe, c.niveau,
                    m.nom_matiere,
                    l.titre_leçon, l.grand_titre, l.type_lecon, l.nb_heures,
                    ch.titre_chapitre, ch.ordre_chapitre,
                    sp.numero_semaine, sp.date_debut AS semaine_debut, sp.date_fin AS semaine_fin
                FROM progression_programme pp
                JOIN utilisateur u ON pp.id_utilisateur = u.id_utilisateur
                JOIN classe c      ON pp.id_classe      = c.id_classe
                JOIN matiere m     ON pp.id_matiere     = m.id_matiere
                JOIN leçon l       ON pp.id_leçon       = l.id_leçon
                JOIN chapitre ch   ON l.id_chapitre     = ch.id_chapitre
                LEFT JOIN semaine_programme sp ON ch.id_semaine = sp.id_semaine
                JOIN programme_officiel po ON ch.id_programme = po.id_programme
                WHERE pp.id_classe = ? AND pp.id_matiere = ? AND po.annee_scolaire = ?";

        $types  = "iis";
        $params = [$idClasse, $idMatiere, $annee];

        if ($idEnseignant) {
            $sql    .= " AND pp.id_utilisateur = ?";
            $types  .= "i";
            $params[] = $idEnseignant;
        }
        $sql .= " ORDER BY COALESCE(sp.numero_semaine,999), ch.ordre_chapitre, l.ordre_leçon";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Rapport de présence ──────────────────────────────────
    public function getPresence(int $idClasse, string $dateDebut, string $dateFin): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.nom, e.prenom, e.matricule,
                    COUNT(p.id_presence) AS total,
                    SUM(p.statut_presence='PRESENT') AS present,
                    SUM(p.statut_presence='ABSENT')  AS absent,
                    SUM(p.statut_presence='RETARD')  AS retard,
                    SUM(p.statut_presence='EXCUSE')  AS excuse,
                    ROUND(SUM(p.statut_presence='PRESENT')*100/NULLIF(COUNT(p.id_presence),0),1) AS taux
             FROM eleve e
             LEFT JOIN presence p  ON e.id_eleve = p.id_eleve
             LEFT JOIN seance s    ON p.id_seance = s.id_seance
                                  AND s.date_seance BETWEEN ? AND ?
             WHERE e.id_classe = ? AND e.est_actif = 1
             GROUP BY e.id_eleve
             ORDER BY e.nom, e.prenom"
        );
        $stmt->bind_param("ssi", $dateDebut, $dateFin, $idClasse);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Rapport annuel (séances par enseignant) ──────────────
    public function getAnnuel(string $annee): array
    {
        $stmt = $this->db->prepare(
            "SELECT CONCAT(u.nom,' ',u.prenom) AS enseignant,
                    c.nom_classe, m.nom_matiere,
                    COUNT(DISTINCT pp.id_progression) AS nb_lecons_planifiees,
                    SUM(pp.statut='TERMINEE')  AS terminees,
                    SUM(pp.statut='EN_COURS')  AS en_cours,
                    ROUND(AVG(pp.progression_pourcentage),1) AS avancement,
                    COUNT(DISTINCT s.id_seance) AS nb_seances
             FROM progression_programme pp
             JOIN utilisateur u ON pp.id_utilisateur = u.id_utilisateur
             JOIN classe c      ON pp.id_classe      = c.id_classe
             JOIN matiere m     ON pp.id_matiere     = m.id_matiere
             JOIN leçon l       ON pp.id_leçon       = l.id_leçon
             JOIN chapitre ch   ON l.id_chapitre     = ch.id_chapitre
             JOIN programme_officiel po ON ch.id_programme = po.id_programme
             LEFT JOIN seance s ON s.id_utilisateur = pp.id_utilisateur
                               AND s.id_classe = pp.id_classe
                               AND s.id_matiere = pp.id_matiere
             WHERE po.annee_scolaire = ?
             GROUP BY u.id_utilisateur, c.id_classe, m.id_matiere
             ORDER BY u.nom, c.nom_classe"
        );
        $stmt->bind_param("s", $annee);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Données de référence pour les filtres ────────────────
    public function getClasses(string $annee = ''): array
    {
        if ($annee) {
            $stmt = $this->db->prepare("SELECT * FROM classe WHERE annee_scolaire=? ORDER BY niveau,nom_classe");
            $stmt->bind_param("s", $annee); $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return $this->db->query("SELECT * FROM classe ORDER BY annee_scolaire DESC,niveau,nom_classe")->fetch_all(MYSQLI_ASSOC);
    }

    public function getMatieres(): array
    {
        return $this->db->query("SELECT * FROM matiere ORDER BY nom_matiere")->fetch_all(MYSQLI_ASSOC);
    }

    public function getEnseignants(): array
    {
        return $this->db->query(
            "SELECT id_utilisateur, nom, prenom FROM utilisateur WHERE role='enseignant' AND est_actif=1 ORDER BY nom"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function getAnnees(): array
    {
        $rows = $this->db->query("SELECT DISTINCT annee_scolaire FROM classe ORDER BY annee_scolaire DESC")->fetch_all(MYSQLI_ASSOC);
        return array_column($rows, 'annee_scolaire');
    }

    public function getClasseById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM classe WHERE id_classe=?");
        $stmt->bind_param("i",$id); $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getMatiereById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM matiere WHERE id_matiere=?");
        $stmt->bind_param("i",$id); $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }
}
