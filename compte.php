<?php
session_start();
require_once ('./includes/php/csrf.php');

$prenomSession = trim((string) ($_SESSION['prenom'] ?? ''));
$nomSession = trim((string) ($_SESSION['nom'] ?? ''));

if ($prenomSession === '' && $nomSession === '') {
    header('Location: ./connexion.php');
    exit;
}

$nomAffiche = htmlspecialchars(mb_convert_case($prenomSession . ' ' . $nomSession, MB_CASE_TITLE, 'UTF-8'), ENT_QUOTES, 'UTF-8');
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Votre compte utilisateur">
    <title>Mon compte - Frames &amp; cores</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body class="formulaire">
    <h1>Mon compte</h1>
    <p>Bienvenue <?php echo $nomAffiche; ?>.</p>
    <p><a class="back-btn-account" href="./index.php">Retour à l'accueil</a></p>
    <p><a class="back-btn-account" href="./historique.php">Historique des commandes</a></p>
    <form class="disconnect-form" action="./includes/php/disconnect.php" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="disconnect-btn">Se déconnecter</button>
    </form>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <?php include_once('./includes/php/chat_widget.php'); ?>
    <script src="./assets/js/chat.js"></script>
</body>
</html>
