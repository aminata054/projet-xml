<?php
// Enable libxml error handling
libxml_use_internal_errors(true);

// Charger le fichier XML
$xml = simplexml_load_file('whatsapp.xml');
if ($xml === false) {
    echo "Erreur lors du chargement du fichier XML:<br>";
    foreach (libxml_get_errors() as $error) {
        echo $error->message . " at line " . $error->line . "<br>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - WhatsApp Web</title>
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <div class="chat-container">
        <h2>Paramètres</h2>

        <!-- Gérer les contacts -->
        <div class="settings-section">
            <h3>Contacts</h3>
            <ul class="contact-list">
                <?php
                foreach ($xml->discussions->contacts->contact as $contact) {
                    $contactId = (string)$contact['id'];
                    if ($contactId != '1') { // Exclure l'utilisateur actuel
                        $fullName = htmlspecialchars((string)$contact->prenom . ' ' . (string)$contact->nom);
                        echo "<li class='contact-item'>$fullName</li>";
                    }
                }
                ?>
            </ul>
            <a href="add_contact.php" class="sidebar-button">Nouveau contact</a>
        </div>

        <!-- Gérer les groupes -->
        <div class="settings-section">
            <h3>Groupes</h3>
            <ul class="contact-list">
                <?php
                foreach ($xml->discussions->groupes->groupe as $groupe) {
                    $nomGroupe = htmlspecialchars((string)$groupe->nom_groupe);
                    echo "<li class='contact-item'>$nomGroupe</li>";
                }
                ?>
            </ul>
            <a href="add_group.php" class="sidebar-button">Nouveau groupe</a>
        </div>
    </div>
</body>

</html>