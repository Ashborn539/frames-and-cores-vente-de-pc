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

    $dureeMinutes = getChatMessageLifetimeMinutes();

    // Recupere les messages encore actifs
    $requete = $bdd->prepare(
        "SELECT m.id_message, m.message, m.date_envoi, u.prenom, u.nom, u.id_user
         FROM `chat_messages` m
         JOIN `user` u ON m.id_user = u.id_user
         WHERE m.date_envoi >= NOW() - INTERVAL :minutes MINUTE
         ORDER BY m.date_envoi ASC"
    );
    $requete->bindValue(':minutes', $dureeMinutes, PDO::PARAM_INT);
    $requete->execute();
    $lignes = $requete->fetchAll(PDO::FETCH_ASSOC);

    $idUtilisateurActuel = (int) ($_SESSION['id_client'] ?? 0);
    $messagesFormates = [];

    foreach ($lignes as $ligne) {
        // Nom affiche : prenom en priorite, sinon nom de famille
        $prenom = trim((string) ($ligne['prenom'] ?? ''));
        if ($prenom === '') {
            $prenom = trim((string) ($ligne['nom'] ?? ''));
        }
        $auteur = $prenom !== '' ? ucfirst(mb_strtolower($prenom, 'UTF-8')) : 'Utilisateur';
        $messageBrut = (string) $ligne['message'];

        $texteFormate = sprintf("%s dit '%s'", $auteur, $messageBrut);

        $messagesFormates[] = [
            'id' => (int) $ligne['id_message'],
            'text' => $texteFormate,
            'is_me' => ((int) $ligne['id_user'] === $idUtilisateurActuel),
            'time' => date('H:i', strtotime($ligne['date_envoi']))
        ];
    }

    echo json_encode([
        'success' => true,
        'messages' => $messagesFormates,
        'user_connected' => ($idUtilisateurActuel > 0),
        'lifetime_minutes' => $dureeMinutes
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des messages.']);
}
