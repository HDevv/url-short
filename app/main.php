<!DOCTYPE html>
<html lang="fr">
<head>
        <!-- General HTML settings  -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>URL shortener</title>

        <!-- PHP scripts -->
        <?php
            require_once('server.php');
            $user_links = $user->getLinks($conn);
            if(isset($_SESSION['badfileformat'])){
                ?>
                <script>
                    alert("Format de fichier non accepté. Images uniquement.");
                </script>
                <?php
                unset($_SESSION['badfileformat']);
            };
            if(isset($_SESSION['uploaderror'])){
                ?>
                <script>
                    alert("Erreur lors de l'envoi de fichier.");
                </script>
                <?php
                unset($_SESSION['uploaderror']);
            };
        ?>
    </head>
    <body>
        <header>
            <a style="float: right;" href="./logout.php">Se déconnecter</a>
        </header>
        <main>
            <h1>URL Shortener</h1>
            <section>
                <h2>Nouveau raccourci</h2>
                <p>Entrez l'URL que vous souhaitez raccourcir puis cliquez sur le bouton "Shorten" :</p>
                <form action="main.php" method="post">
                    <label for="original_url">URL original :</label>
                    <input type="text" name="original_url" id="original_url" required>
                    <input type="submit" name="new_link" value="Shorten">
                </form>
            </section>
            <section>
                <h2>Nouveau fichier</h2>
                <p>Déposez un fichier sur notre serveur afin de le télécharger plus tard via le lien raccourci fourni.</p>
                <form method="post" enctype="multipart/form-data">
                    <label for="upload">Votre fichier</label>
                    <input type="file" name="upload" id="upload">
                    <input type="submit" name="file_upload" value="Envoyer le fichier">
                </form>
            </section>
            <section>
                <h2>Vos liens et fichiers existants</h2>
                <?php if(sizeof($user_links) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Identifiant</th>
                            <th>Original</th>
                            <th>Raccourci</th>
                            <th>Nombre de clics</th>
                            <th>Statut</th>
                            <th>Fichier lié</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($user_links as $link): ?>
                        <tr>
                            <td><?= $link['id'] ?></td>
                            <td>
                                <?php if($link['original']): ?>
                                <a href="<?= $link['original'] ?>"><?= $link['original'] ?></a>
                                <?php else: ?>
                                Aucun
                                <?php endif; ?>
                            </td>
                            <td><a href="<?= $link['original'] ?>">http://localhost/Cours/CDA2025.2/PHP_avec_M._Berthier/Évaluation/app/?<?= $link['short'] ?></a></td>
                            <td><?= $link['clicks'] ?></td>
                            <td><?= $link['state'] ? 'Actif' : 'Désactivé' ?></td>
                            <td>
                            <?php if($link['attached_file_name']): ?>
                                <a href="<?= $link['attached_file_name'] ?>"><?= $link['attached_file_name'] ?></a>
                                <?php else: ?>
                                Aucun
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Vous n'avez aucun raccourci pour le moment.</p>
                <?php endif; ?>
            </section>
        </main>
        <footer>

        </footer>
    </body>
</html>
<?php $conn = null; ?>