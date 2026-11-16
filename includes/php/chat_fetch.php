<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/chat_config.php';

try {
    $bdd = getBD();

    // Suppression des messages trop anciens
    purgeExpiredChatMessages($bdd);

    $lifetimeMinutes = getChatMessageLifetimeMinutes();

    // Recupere les messages encore actifs
    $stmt = $bdd->prepare(
        "SELECT m.id_message, m.message, m.date_envoi, u.prenom, u.nom, u.id_user
         FROM `chat_messages` m
         JOIN `user` u ON m.id_user = u.id_user
         WHERE m.date_envoi >= NOW() - INTERVAL :minutes MINUTE
         ORDER BY m.date_envoi ASC"
    );
    $stmt->bindValue(':minutes', $lifetimeMinutes, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentUserId = (int) ($_SESSION['id_client'] ?? 0);
    $formattedMessages = [];

    foreach ($rows as $row) {
        // Nom affiche : prenom en priorite, sinon nom de famille
        $prenom = trim((string) ($row['prenom'] ?? ''));
        if ($prenom === '') {
            $prenom = trim((string) ($row['nom'] ?? ''));
        }
        $auteur = $prenom !== '' ? ucfirst(mb_strtolower($prenom, 'UTF-8')) : 'Utilisateur';
        $rawMessage = (string) $row['message'];

        $formattedText = sprintf("%s dit '%s'", $auteur, $rawMessage);

        $formattedMessages[] = [
            'id' => (int) $row['id_message'],
            'text' => $formattedText,
            'is_me' => ((int) $row['id_user'] === $currentUserId),
            'time' => date('H:i', strtotime($row['date_envoi']))
        ];
    }

    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'user_connected' => ($currentUserId > 0),
        'lifetime_minutes' => $lifetimeMinutes
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des messages.']);
}
