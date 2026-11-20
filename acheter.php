<?php
session_start();
require_once ('./includes/php/bd.php');
require_once ('./includes/php/csrf.php');
require_once ('./includes/php/reservation.php');

$idClient = (int) ($_SESSION['id_client'] ?? 0);
if ($idClient === 0) {
    header('Location: connexion.php');
    exit;
}

$bdd = getBD();
$erreur = '';
$commandeEnregistree = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    try {
        $bdd->beginTransaction();
        cleanupExpiredReservations($bdd);
        $reservations = lockActiveReservations($bdd, $idClient);

        if ($reservations === []) {
            throw new RuntimeException('Les réservations du panier ont expiré.');
        }

        $commandeRequete = $bdd->prepare(
            'INSERT INTO Commandes (id_art, id_client, quantite, envoi)
            VALUES (:id_art, :id_client, :quantite, FALSE)'
        );
        $confirmationRequete = $bdd->prepare(
            "UPDATE reservations
            SET statut = 'confirmee'
            WHERE id_reservation = :id_reservation AND statut = 'active'"
        );

        foreach ($reservations as $reservation) {
            $commandeRequete->execute([
                'id_art' => (int) $reservation['id_art'],
                'id_client' => $idClient,
                'quantite' => (int) $reservation['quantite'],
            ]);
            $confirmationRequete->execute([
                'id_reservation' => (int) $reservation['id_reservation'],
            ]);
        }

        $bdd->commit();
        unset($_SESSION['panier']);
        $commandeEnregistree = true;
    } catch (Throwable $exception) {
        if ($bdd->inTransaction()) {
            $bdd->rollBack();
        }
        error_log($exception->getMessage());
        $erreur = 'La commande n’a pas pu être enregistrée. Veuillez réessayer.';
    }
} else {
    cleanupExpiredReservations($bdd);
    $reservations = fetchActiveReservations($bdd, $idClient);
    if ($reservations === []) {
        header('Location: panier.php');
        exit;
    }
}

$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Validation de la commande">
    <title>Commande - Frames &amp; cores</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body class="formulaire">
    <h1>Commande</h1>
    <?php if ($commandeEnregistree): ?>
        <p class="form-success">Votre commande a bien été enregistrée.</p>
        <p class="formulaire-link"><a href="./index.php">Retour à l'accueil</a></p>
    <?php elseif ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <p>Confirmez votre commande pour la valider.</p>
        <form action="acheter.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn">Confirmer la commande</button>
        </form>
        <p class="formulaire-link"><a href="./panier.php">Retour au panier</a></p>
    <?php else: ?>
        <p class="form-error"><?php echo htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="formulaire-link"><a href="./panier.php">Retour au panier</a></p>
    <?php endif; ?>
</body>
</html>
