<!DOCTYPE html>
<html lang="fr">
    <head>
        <!-- General HTML settings  -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion</title>

        <!-- PHP scripts -->
        <?php
            require_once('server.php');
            if(isset($_SESSION['badpass'])){
                ?>
                <script>
                    alert("Nom d'utilisateur et/ou mot de passe incorrect.");
                </script>
                <?php
                unset($_SESSION['badpass']);
            };
        ?>
    </head>
    <body>
        <form action="index.php" method="post">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" name="username" id="username">
            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password">
            <button type="submit" name="login">Connexion</button>
            <button type="reset">Effacer</button>
        </form>
        <p>
            Pas encore membre ?
            <a href="./register.php">Inscrivez-vous !</a>
        </p>
    </body>
</html>
<?php $conn = null; ?>