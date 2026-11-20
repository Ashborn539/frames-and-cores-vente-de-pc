<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/csrf.php';

$isAjax = !empty($_POST['ajax'])
    || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

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

if (!isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
    sendRegistrationError('Requête refusée. Le jeton de sécurité est absent ou expiré.', $isAjax, 403);
}

$nom = trim((string) ($_POST['n'] ?? ''));
$prenom = trim((string) ($_POST['p'] ?? ''));
$adresse = trim((string) ($_POST['adr'] ?? ''));
$telephone = trim((string) ($_POST['num'] ?? ''));
$email = trim((string) ($_POST['mail'] ?? ''));
$motDePasse = (string) ($_POST['mdp1'] ?? '');
$confirmation = (string) ($_POST['mdp2'] ?? '');

if ($nom === '' || $prenom === '' || $adresse === '' || $telephone === '' || $email === '' || $motDePasse === '' || $confirmation === '') {
    sendRegistrationError('Tous les champs sont obligatoires.', $isAjax);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendRegistrationError('L’adresse e-mail est invalide.', $isAjax);
}

if (!preg_match('/^(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9\s]).+$/', $motDePasse)) {
    sendRegistrationError('Le mot de passe doit contenir au minimum 1 lettre, 1 chiffre et 1 caractère spécial.', $isAjax);
}

if ($motDePasse !== $confirmation) {
    sendRegistrationError('Les mots de passe ne correspondent pas.', $isAjax);
}

try {
    $bdd = getBD();

    $verifStmt = $bdd->prepare('SELECT COUNT(*) FROM `user` WHERE mail = :email');
    $verifStmt->execute(['email' => $email]);
    if ((int) $verifStmt->fetchColumn() > 0) {
        sendRegistrationError('Cette adresse email existe déjà.', $isAjax);
    }

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
