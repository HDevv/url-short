<!DOCTYPE html>
<html lang="fr">
    <head>
        <!-- General HTML settings  -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inscription</title>

        <!-- PHP scripts -->
        <?php require_once('server.php'); ?>
    </head>
    <body>
        <h1>Incription</h1>
        <form method="post" action="register.php">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" name="username" id="username">
            <label for="maim">E-mail</label>
            <input type="email" name="mail" id="mail">
            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password">
            <label for="password_confirmation">Confirmez votre mot de passe</label>
            <input type="password" name="password_confirmation" id="password_confirmation">
            <button type="submit" name="register">S'inscrire</button>
            <button type="reset">Effacer</button>
        </form>
        <p>
            Déjà membre ?
            <a href="./index.php">Connectez-vous !</a>
        </p>
    </body>
</html>
<?php $conn = null; ?>