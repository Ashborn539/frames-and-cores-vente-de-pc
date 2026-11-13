<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/csrf.php';

// Détection d'un appel AJAX
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['ajax']) && (string) $_POST['ajax'] === '1')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

/**
 * Gère l'envoi d'un message d'erreur (JSON en AJAX, ou redirection HTTP classique).
 *
 * @param string $message Message d'erreur destiné à l'utilisateur.
 * @param bool $isAjax Indique si la requête provient d'AJAX.
 * @param int $statusCode Code de statut HTTP en cas d'erreur.
 * @return never
 */
function sendRegistrationError(string $message, bool $isAjax, int $statusCode = 200): void
{
    if ($isAjax) {
        if ($statusCode !== 200) {
            http_response_code($statusCode);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'succes' => false,
            'message' => $message
        ]);
        exit;
    }

    header('Location: ../../nouveau.php?error=' . rawurlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendRegistrationError('Veuillez utiliser le formulaire d’inscription.', $isAjax);
}

// Vérification du jeton CSRF
$token = (string) ($_POST['csrf_token'] ?? '');
$tokens = $_SESSION['csrf_tokens'] ?? [];
$isTokenValid = false;

foreach ($tokens as $validToken) {
    if (is_string($validToken) && hash_equals($validToken, $token)) {
        $isTokenValid = true;
        break;
    }
}

if (!$isTokenValid) {
    sendRegistrationError('Requête refusée. Le jeton de sécurité est absent ou expiré.', $isAjax, 403);
}

// Normalisation des champs
$nom = trim((string) ($_POST['n'] ?? ''));
$prenom = trim((string) ($_POST['p'] ?? ''));
$adresse = trim((string) ($_POST['adr'] ?? ''));
$telephone = trim((string) ($_POST['num'] ?? ''));
$email = trim((string) ($_POST['mail'] ?? ''));
$motDePasse = (string) ($_POST['mdp1'] ?? '');
$confirmation = (string) ($_POST['mdp2'] ?? '');

// Contrôles de validation
if ($nom === '' || $prenom === '' || $adresse === '' || $telephone === '' || $email === '' || $motDePasse === '' || $confirmation === '') {
    sendRegistrationError('Tous les champs sont obligatoires.', $isAjax);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendRegistrationError('L’adresse e-mail est invalide.', $isAjax);
}

// Doit contenir au minimum 1 lettre, 1 chiffre et 1 caractère spécial
if (!preg_match('/^(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9\s]).+$/', $motDePasse)) {
    sendRegistrationError('Le mot de passe doit contenir au minimum 1 lettre, 1 chiffre et 1 caractère spécial.', $isAjax);
}

if ($motDePasse !== $confirmation) {
    sendRegistrationError('Les mots de passe ne correspondent pas.', $isAjax);
}

try {
    $bdd = getBD();

    // Vérifier si l'adresse e-mail existe déjà
    $verifStmt = $bdd->prepare('SELECT COUNT(*) FROM `user` WHERE mail = :email');
    $verifStmt->execute(['email' => $email]);
    if ((int) $verifStmt->fetchColumn() > 0) {
        sendRegistrationError('Cette adresse email existe déjà.', $isAjax);
    }

    // Insertion du nouvel utilisateur
    $requete = $bdd->prepare(
        'INSERT INTO `user` (`nom`, `prenom`, `adr`, `num`, `mail`, `mdp`)
        VALUES (:nom, :prenom, :adresse, :telephone, :email, :mot_de_passe)'
    );

    $requete->execute([
        'nom' => $nom,
        'prenom' => $prenom,
        'adresse' => $adresse,
        'telephone' => $telephone,
        'email' => $email,
        'mot_de_passe' => password_hash($motDePasse, PASSWORD_DEFAULT),
    ]);

    $idClient = (int) $bdd->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['id_client'] = $idClient;
    $_SESSION['nom'] = $nom;
    $_SESSION['prenom'] = $prenom;

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'succes' => true,
            'message' => 'Votre compte a été créé avec succès !'
        ]);
        exit;
    }

    header('Location: ../../index.php');
    exit;

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    if ($exception->getCode() === '23000') {
        sendRegistrationError('Cette adresse email existe déjà.', $isAjax);
    }
    sendRegistrationError('Impossible de créer le compte. Une erreur est survenue.', $isAjax);
}
