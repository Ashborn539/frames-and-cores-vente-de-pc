<?php
declare(strict_types=1);

// Duree de retention des messages en minutes
const CHAT_MESSAGE_LIFETIME_MINUTES = 10;

// Recupere la duree active avec possibilite de surcharge via chat_config.json
function getChatMessageLifetimeMinutes(): int
{
    static $lifetime = null;
    if ($lifetime !== null) {
        return $lifetime;
    }

    $jsonConfig = __DIR__ . '/../data/chat_config.json';
    if (file_exists($jsonConfig)) {
        $data = json_decode((string) file_get_contents($jsonConfig), true);
        if (isset($data['lifetime_minutes']) && is_numeric($data['lifetime_minutes'])) {
            $lifetime = max(1, (int) $data['lifetime_minutes']);
            return $lifetime;
        }
    }

    $lifetime = max(1, CHAT_MESSAGE_LIFETIME_MINUTES);
    return $lifetime;
}

// Supprime les messages plus vieux que la retention definie
function purgeExpiredChatMessages(PDO $bdd): int
{
    $minutes = getChatMessageLifetimeMinutes();
    $stmt = $bdd->prepare("DELETE FROM `chat_messages` WHERE `date_envoi` < NOW() - INTERVAL :minutes MINUTE");
    $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
}
