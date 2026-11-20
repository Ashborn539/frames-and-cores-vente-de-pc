<?php
declare(strict_types=1);

/**
 * Ouvre ou réutilise une connexion PDO vers la base MySQL de l'application.
 * Configure le mode d'erreur et synchronise le fuseau horaire SQL sur UTC.
 */
function getBD(): PDO
{
    static $bdd = null;
    if ($bdd === null) {
        $bdd = new PDO('mysql:host=localhost;dbname=computer_database;charset=utf8', 'root', '');
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $bdd->exec("SET time_zone = '+00:00'");
    }
    return $bdd;
}
