<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/chat_filter.php';
require_once __DIR__ . '/chat_config.php';

// Verification de la session
$idClient = $_SESSION['id_client'] ?? null;
if (!$idClient) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour participer au tchat.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$message = trim((string) ($_POST['message'] ?? ''));
$longueurMessage = mb_strlen($message, 'UTF-8');

// Controle de la longueur du message
if ($longueurMessage === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le message ne peut pas être vide.']);
    exit;
}

if ($longueurMessage > 256) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le message ne doit pas dépasser 256 caractères.']);
    exit;
}

// Verification du contenu
$evaluation = evaluateMessageSafety($message);
if (!$evaluation['safe']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'hate_detected' => true,
        'message' => 'Votre message a été refusé car il contient des propos inappropriés.',
        'score' => $evaluation['score']
    ]);
    exit;
}

// Purge des messages expires puis insertion
try {
    $bdd = getBD();
    purgeExpiredChatMessages($bdd);

    $requete = $bdd->prepare("INSERT INTO `chat_messages` (`id_user`, `message`, `date_envoi`) VALUES (:id_user, :message, NOW())");
    $requete->execute([
        'id_user' => (int) $idClient,
        'message' => $message
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès.'
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
}
