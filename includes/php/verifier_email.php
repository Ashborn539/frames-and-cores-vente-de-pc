<?php
declare(strict_types=1);

require_once __DIR__ . '/bd.php';

header('Content-Type: application/json; charset=utf-8');

$email = trim((string) ($_REQUEST['mail'] ?? $_REQUEST['email'] ?? ''));

if ($email === '') {
    echo json_encode([
        'existe' => false,
        'message' => 'Veuillez saisir une adresse email.'
    ]);
    exit;
}

try {
    $bdd = getBD();
    $stmt = $bdd->prepare('SELECT COUNT(*) FROM `user` WHERE mail = :mail');
    $stmt->execute(['mail' => $email]);
    $exists = ((int) $stmt->fetchColumn() > 0);

    echo json_encode([
        'existe' => $exists,
        'message' => $exists
            ? 'Cette adresse email existe déjà.'
            : 'Cette adresse email est disponible.'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'existe' => false,
        'message' => 'Erreur lors de la vérification.'
    ]);
}
