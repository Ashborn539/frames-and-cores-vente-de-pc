<?php
declare(strict_types=1);

// Duree de retention des messages en minutes
const CHAT_MESSAGE_LIFETIME_MINUTES = 10;

// Recupere la duree active avec possibilite de surcharge via chat_config.json
function getChatMessageLifetimeMinutes(): int
{
    static $dureeDeVie = null;
    if ($dureeDeVie !== null) {
        return $dureeDeVie;
    }

    $cheminConfigJson = __DIR__ . '/../data/chat_config.json';
    if (file_exists($cheminConfigJson)) {
        $donnees = json_decode((string) file_get_contents($cheminConfigJson), true);
        if (isset($donnees['lifetime_minutes']) && is_numeric($donnees['lifetime_minutes'])) {
            $dureeDeVie = max(1, (int) $donnees['lifetime_minutes']);
            return $dureeDeVie;
        }
    }

    $dureeDeVie = max(1, CHAT_MESSAGE_LIFETIME_MINUTES);
    return $dureeDeVie;
}

// Supprime les messages plus vieux que la retention definie
function purgeExpiredChatMessages(PDO $bdd): int
{
    $minutes = getChatMessageLifetimeMinutes();
    $requete = $bdd->prepare("DELETE FROM `chat_messages` WHERE `date_envoi` < NOW() - INTERVAL :minutes MINUTE");
    $requete->bindValue(':minutes', $minutes, PDO::PARAM_INT);
    $requete->execute();
    return $requete->rowCount();
}
