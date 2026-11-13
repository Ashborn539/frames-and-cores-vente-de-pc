<?php
declare(strict_types=1);

require_once __DIR__ . '/bd.php';

header('Content-Type: application/json; charset=utf-8');

// Récupère l'email passé en paramètre via GET ou POST (support de 'mail' et 'email')
$email = trim((string) ($_REQUEST['mail'] ?? $_REQUEST['email'] ?? ''));

if ($email === '') {
    if (isset($_REQUEST['format']) && ($_REQUEST['format'] === 'bool' || $_REQUEST['format'] === 'raw')) {
        echo json_encode(false);
    } else {
        echo json_encode([
            'existe' => false,
            'exists' => false,
            'disponible' => false,
            'message' => 'Veuillez saisir une adresse email.'
        ]);
    }
    exit;
}

try {
    $bdd = getBD();
    $stmt = $bdd->prepare('SELECT COUNT(*) FROM `user` WHERE mail = :mail');
    $stmt->execute(['mail' => $email]);
    $count = (int) $stmt->fetchColumn();
    $exists = ($count > 0);

    // Format lisible du résultat :
    // Fournit le booléen 'existe' / 'exists' ainsi que le message explicatif.
    if (isset($_REQUEST['format']) && ($_REQUEST['format'] === 'bool' || $_REQUEST['format'] === 'raw')) {
        echo json_encode($exists);
    } else {
        echo json_encode([
            'existe' => $exists,
            'exists' => $exists,
            'disponible' => !$exists,
            'message' => $exists
                ? 'Cette adresse email existe déjà.'
                : 'Cette adresse email est disponible.'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'existe' => false,
        'exists' => false,
        'message' => 'Erreur lors de la vérification.'
    ]);
}
