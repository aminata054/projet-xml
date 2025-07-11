<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un contact</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container chat-container">
        <h2>Ajouter un contact</h2>
        <form method="post" action="add.php" enctype="multipart/form-data" class="profile-form">
            <div class="form-group">
                <label for="prenom">Prénom :</label>
                <input type="text" name="prenom" id="prenom" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" name="nom" id="nom" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="numero">Numéro de téléphone :</label>
                <input type="text" name="numero" id="numero" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="photo">Photo de profil :</label>
                <input type="file" name="photo" id="photo" class="form-file" accept="image/*">
            </div>
            <div class="form-group">
                <label for="status">Statut :</label>
                <input type="text" name="status" id="status" class="form-input"
                    placeholder="En ligne, Hors ligne, etc.">
            </div>
            <button type="submit" name="ajouter_contact" class="sidebar-button">Ajouter</button>
        </form>
    </div>
</body>

</html>