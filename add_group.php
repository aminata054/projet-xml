<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un groupe</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container chat-container">
        <h2>Créer un groupe</h2>
        <form method="post" action="add.php" enctype="multipart/form-data" class="profile-form">
            <div class="form-group">
                <label for="nom_groupe">Nom du groupe :</label>
                <input type="text" name="nom_groupe" id="nom_groupe" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="admin_id">Administrateur :</label>
                <select name="admin_id" id="admin_id" class="form-input" required>
                    <?php
                    $xml = simplexml_load_file('whatsapp.xml');
                    foreach ($xml->discussions->contacts->contact as $contact) {
                        $contactId = (string)$contact['id'];
                        $fullName = (string)$contact->prenom . ' ' . (string)$contact->nom;
                        echo "<option value='$contactId'>$fullName</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="membres">Membres :</label>
                <select name="membres[]" id="membres" class="form-input" multiple required>
                    <?php
                    foreach ($xml->discussions->contacts->contact as $contact) {
                        $contactId = (string)$contact['id'];
                        $fullName = (string)$contact->prenom . ' ' . (string)$contact->nom;
                        echo "<option value='$contactId'>$fullName</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="photo">Photo du groupe :</label>
                <input type="file" name="photo_groupe" id="photo" class="form-file" accept="image/*">
            </div>
            <button type="submit" name="creer_groupe" class="sidebar-button">Créer</button>
        </form>
    </div>
</body>

</html>