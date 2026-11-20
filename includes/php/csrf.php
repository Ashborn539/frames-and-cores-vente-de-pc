<?php
declare(strict_types=1);

/**
 * Génère un jeton CSRF et en conserve au maximum deux actifs en session.
 */
function csrfToken(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'] ??= [];
    $_SESSION['csrf_tokens'][] = $token;

    if (count($_SESSION['csrf_tokens']) > 2) {
        array_shift($_SESSION['csrf_tokens']);
    }

    return $token;
}

/**
 * Vérifie si le jeton fourni correspond à l'un des jetons valides de la session.
 */
function isValidCsrfToken(?string $token): bool
{
    if (!$token) {
        return false;
    }

    foreach ($_SESSION['csrf_tokens'] ?? [] as $validToken) {
        if (is_string($validToken) && hash_equals($validToken, $token)) {
            return true;
        }
    }

    return false;
}

/**
 * Interrompt la requête avec un code 403 si le jeton CSRF est absent ou expiré.
 */
function requireValidCsrfToken(): void
{
    if (!isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Requête refusée. Le jeton de sécurité est absent ou expiré.');
    }
}
