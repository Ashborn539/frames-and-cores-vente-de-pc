<?php
declare(strict_types=1);

function evaluateMessageSafety(string $message): array
{
    static $modele = null;

    // Charge le dictionnaire des poids au premier appel
    if ($modele === null) {
        $cheminJson = __DIR__ . '/../data/model_weights.json';
        if (!file_exists($cheminJson)) {
            $cheminJson = __DIR__ . '/../filter/data/model_weights.json';
        }
        if (!file_exists($cheminJson)) {
            return ['safe' => true, 'score' => 0.0, 'words_matched' => []];
        }
        $contenu = file_get_contents($cheminJson);
        $modele = json_decode($contenu, true) ?: ['bias' => 0.0, 'weights' => []];
    }

    $biais = (float) ($modele['bias'] ?? 0.0);
    $poids = $modele['weights'] ?? [];

    // Extraction des mots en minuscules
    preg_match_all('/\b[a-zA-Z]{2,}\b/', strtolower($message), $correspondances);
    $mots = array_unique($correspondances[0] ?? []);

    // Addition des poids correspondants avec le biais
    $score = $biais;
    $motsTrouves = [];

    foreach ($mots as $mot) {
        if (isset($poids[$mot])) {
            $poidsMot = (float) $poids[$mot];
            $score += $poidsMot;
            $motsTrouves[$mot] = $poidsMot;
        }
    }

    // Score positif ou nul = accepte, negatif = refuse
    return [
        'safe' => $score >= 0.0,
        'score' => round($score, 4),
        'words_matched' => $motsTrouves
    ];
}
