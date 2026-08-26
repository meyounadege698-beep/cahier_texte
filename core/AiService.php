<?php

/**
 * AiService — Intégration Claude (Anthropic) pour les fonctionnalités IA.
 *
 * Usage :
 *   $ai = new AiService();
 *   $resume = $ai->resumeSeance($contenu, $objectifs, $commentaire);
 */
class AiService
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private int    $maxTokens;

    public function __construct()
    {
        $this->apiKey    = defined('AI_API_KEY')    ? AI_API_KEY    : '';
        $this->apiUrl    = defined('AI_API_URL')    ? AI_API_URL    : 'https://api.anthropic.com/v1/messages';
        $this->model     = defined('AI_MODEL')      ? AI_MODEL      : 'claude-haiku-20240307';
        $this->maxTokens = defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 300;
    }

    /**
     * Génère un résumé pédagogique des notes d'une séance.
     *
     * @param string $contenu      Contenu traité pendant la séance
     * @param string $objectifs    Objectifs atteints
     * @param string $commentaire  Commentaire de l'enseignant
     * @return array ['success' => bool, 'resume' => string, 'error' => string]
     */
    public function resumeSeance(string $contenu, string $objectifs = '',
                                  string $commentaire = ''): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'VOTRE_CLE_ICI') {
            return ['success' => false, 'resume' => '', 'error' => 'Clé API non configurée.'];
        }

        $prompt = $this->buildPromptSeance($contenu, $objectifs, $commentaire);
        return $this->call($prompt);
    }

    /**
     * Génère des suggestions d'objectifs pédagogiques pour une leçon.
     */
    public function suggererObjectifs(string $titreLecon, string $grandTitre = '',
                                       string $matiere = ''): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'VOTRE_CLE_ICI') {
            return ['success' => false, 'resume' => '', 'error' => 'Clé API non configurée.'];
        }

        $prompt = "Tu es un expert pédagogique camerounais. "
            . "Propose 3 objectifs pédagogiques précis et mesurables pour la leçon suivante :\n"
            . "Matière : {$matiere}\n"
            . "Grand titre : {$grandTitre}\n"
            . "Titre de la leçon : {$titreLecon}\n\n"
            . "Format : une liste numérotée, chaque objectif commence par un verbe d'action "
            . "(définir, calculer, analyser, démontrer...). Maximum 2 lignes par objectif. "
            . "Réponds en français uniquement.";

        return $this->call($prompt);
    }

    /**
     * Analyse le taux de couverture du programme et génère des recommandations.
     */
    public function analyserProgression(string $enseignant, string $matiere,
                                         string $classe, float $taux,
                                         int $nbSemaines): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'VOTRE_CLE_ICI') {
            return ['success' => false, 'resume' => '', 'error' => 'Clé API non configurée.'];
        }

        $prompt = "Tu es conseiller pédagogique. Analyse cette situation et donne 2-3 recommandations concrètes :\n"
            . "Enseignant : {$enseignant}\n"
            . "Matière : {$matiere}, Classe : {$classe}\n"
            . "Taux de couverture du programme : {$taux}%\n"
            . "Semaines écoulées depuis la rentrée : {$nbSemaines}\n\n"
            . "Sois direct, bienveillant et pratique. Maximum 120 mots. En français.";

        return $this->call($prompt);
    }

    // ── Appel HTTP à l'API Anthropic ────────────────────────
    private function call(string $prompt): array
    {
        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'resume' => '', 'error' => 'Erreur réseau : ' . $curlErr];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? "Erreur HTTP {$httpCode}";
            return ['success' => false, 'resume' => '', 'error' => $errMsg];
        }

        $text = $data['content'][0]['text'] ?? '';
        return ['success' => true, 'resume' => trim($text), 'error' => ''];
    }

    // ── Construction du prompt séance ────────────────────────
    private function buildPromptSeance(string $contenu, string $objectifs,
                                        string $commentaire): string
    {
        $prompt  = "Tu es assistant pédagogique pour un établissement scolaire camerounais. ";
        $prompt .= "Génère un résumé professionnel et synthétique (maximum 80 mots) des notes ";
        $prompt .= "suivantes d'une séance de cours. Le résumé doit être clair, factuel, ";
        $prompt .= "utilisable dans un cahier de texte officiel.\n\n";
        $prompt .= "Contenu traité : {$contenu}\n";
        if ($objectifs) $prompt .= "Objectifs atteints : {$objectifs}\n";
        if ($commentaire) $prompt .= "Notes de l'enseignant : {$commentaire}\n";
        $prompt .= "\nRéponds uniquement avec le résumé, sans titre ni introduction. En français.";
        return $prompt;
    }
}
