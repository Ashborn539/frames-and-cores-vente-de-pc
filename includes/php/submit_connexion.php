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
 * Gère l'envoi d'un message d'erreur de connexion (JSON en AJAX, ou redirection classique).
 *
 * @param string $message Message à destination de l'utilisateur.
 * @param bool $isAjax Indique si la requête est AJAX.
 * @param int $statusCode Code de statut HTTP.
 * @return never
 */
function handleLoginError(string $message, bool $isAjax, int $statusCode = 200): void
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

    header('Location: ../../connexion.php?error=' . rawurlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleLoginError('Veuillez utiliser le formulaire de connexion.', $isAjax);
}

// Vérification CSRF
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
    handleLoginError('Requête refusée. Le jeton de sécurité est absent ou expiré.', $isAjax, 403);
}

// Validation des paramètres
$email = trim((string) ($_POST['mail'] ?? ''));
$motDePasse = (string) ($_POST['mdp'] ?? '');

if ($email === '' || $motDePasse === '') {
    handleLoginError('L’adresse e-mail et le mot de passe sont obligatoires.', $isAjax);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    handleLoginError('L’adresse e-mail est invalide.', $isAjax);
}

try {
    $bdd = getBD();
    $requete = $bdd->prepare(
        'SELECT id_user, nom, prenom, mdp
         FROM `user`
         WHERE mail = :email
         LIMIT 1'
    );
    $requete->execute(['email' => $email]);
    $utilisateur = $requete->fetch();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    handleLoginError('Une erreur est survenue. Veuillez réessayer.', $isAjax);
}

if (!$utilisateur || !password_verify($motDePasse, $utilisateur['mdp'])) {
    handleLoginError('Adresse e-mail ou mot de passe incorrect.', $isAjax);
}

session_regenerate_id(true);
$_SESSION['id_client'] = (int) $utilisateur['id_user'];
$_SESSION['nom'] = $utilisateur['nom'];
$_SESSION['prenom'] = $utilisateur['prenom'];

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'succes' => true,
        'message' => 'Connexion réussie ! Redirection en cours...'
    ]);
    exit;
}

header('Location: ../../index.php');
exit;
