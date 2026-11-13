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
    <meta name="description" content="Créer un compte - Frames &amp; cores">
    <meta name="author" content="author">
    <link rel="stylesheet" href="./assets/css/style.css?v=<?php echo time(); ?>">
    <title>Frames &amp; cores - Sign Up</title>
</head>
<body class="formulaire">

    <h1>Créer un compte</h1>
    <div id="form-feedback" class="form-error" style="<?php echo ($error !== '') ? '' : 'display: none;'; ?>">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <form action="./includes/php/submit_formulaire.php" method="post" id="form-inscription" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <p>
            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="n" required>
            <span class="field-msg erreur" id="msg-nom"></span>
        </p>
        <p>
            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="p" required>
            <span class="field-msg erreur" id="msg-prenom"></span>
        </p>
        <p>
            <label for="adresse">Adresse :</label>
            <input type="text" id="adresse" name="adr" required>
            <span class="field-msg erreur" id="msg-adresse"></span>
        </p>
        <p> 
            <label for="num">Téléphone :</label>
            <input type="text" id="num" name="num" required>
            <span class="field-msg erreur" id="msg-num"></span>
        </p>
        <p>
            <label for="mail">Email :</label>
            <input type="email" id="mail" name="mail" required>
            <span class="field-msg erreur" id="msg-mail"></span>
        </p>
        <p>
            <label for="mdp1">Mot de passe :</label>
            <input type="password" id="mdp1" name="mdp1" required>
            <span class="field-msg erreur" id="msg-mdp1"></span>
        </p>
        <p>
            <label for="mdp2">Confirmer le mot de passe :</label>
            <input type="password" id="mdp2" name="mdp2" required>
            <span class="field-msg erreur" id="msg-mdp2"></span>
        </p>

        <button type="submit" class="btn" id="btn-submit" disabled>Confirmer</button>
    </form>

    <p class="formulaire-link">
        Déjà un compte ? <a href="./connexion.php">Se connecter</a>
    </p>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="./assets/js/inscription.js?v=<?php echo time(); ?>"></script>
</body>
</html>
