<?php
declare(strict_types=1);

function evaluateMessageSafety(string $message): array
{
    static $model = null;

    // Charge le dictionnaire des poids au premier appel
    if ($model === null) {
        $jsonPath = __DIR__ . '/../data/model_weights.json';
        if (!file_exists($jsonPath)) {
            $jsonPath = __DIR__ . '/../filter/data/model_weights.json';
        }
        if (!file_exists($jsonPath)) {
            return ['safe' => true, 'score' => 0.0, 'words_matched' => []];
        }
        $content = file_get_contents($jsonPath);
        $model = json_decode($content, true) ?: ['bias' => 0.0, 'weights' => []];
    }

    $bias = (float) ($model['bias'] ?? 0.0);
    $weights = $model['weights'] ?? [];

    // Extraction des mots en minuscules
    preg_match_all('/\b[a-zA-Z]{2,}\b/', strtolower($message), $matches);
    $tokens = array_unique($matches[0] ?? []);

    // Addition des poids correspondants avec le biais
    $score = $bias;
    $matched = [];

    foreach ($tokens as $token) {
        if (isset($weights[$token])) {
            $w = (float) $weights[$token];
            $score += $w;
            $matched[$token] = $w;
        }
    }

    // Score positif ou nul = accepte, negatif = refuse
    return [
        'safe' => $score >= 0.0,
        'score' => round($score, 4),
        'words_matched' => $matched
    ];
}
