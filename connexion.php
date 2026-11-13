<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('./includes/php/csrf.php');
$csrfToken = csrfToken();
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connexion à votre compte">
    <title>Connexion - Frames &amp; cores</title>
    <link rel="stylesheet" href="./assets/css/style.css?v=<?php echo time(); ?>">
</head>

<body class="formulaire">
    <h1>Connexion</h1>

    <div id="form-feedback" class="form-error" style="<?php echo ($error !== '') ? '' : 'display: none;'; ?>">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <form action="./includes/php/submit_connexion.php" method="post" id="form-connexion" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <p>
            <label for="mail">Adresse e-mail :</label>
            <input type="email" id="mail" name="mail" autocomplete="email" required>
            <span class="field-msg erreur" id="msg-mail"></span>
        </p>
        <p>
            <label for="mdp">Mot de passe :</label>
            <input type="password" id="mdp" name="mdp" autocomplete="current-password" required>
            <span class="field-msg erreur" id="msg-mdp"></span>
        </p>
        <button type="submit" class="btn" id="btn-connexion">Se connecter</button>
    </form>

    <p class="formulaire-link">
        Pas encore de compte ? <a href="./nouveau.php">Créer un compte</a>
    </p>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="./assets/js/connexion.js?v=<?php echo time(); ?>"></script>
</body>

</html>
