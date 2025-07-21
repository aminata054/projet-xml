<?php
// Activer la gestion des erreurs libxml
libxml_use_internal_errors(true);

// Charger le fichier XML
$xml = simplexml_load_file('whatsapp.xml');
if ($xml === false) {
    echo "Erreur chargement XML:<br>";
    foreach (libxml_get_errors() as $error) {
        echo $error->message . " at line " . $error->line . "<br>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>WhatsApp Web - XML Project</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Discussions</h2>
                <a href="settings.php" class="sidebar-button" id="settings-link">Paramètres</a>
            </div>
            <div class="chat-list">
                <?php
                // Afficher les contacts
                foreach ($xml->discussions->contacts->contact as $contact) {
                    $contactId = (string)$contact['id'];
                    $nom = (string)$contact->nom;
                    $prenom = (string)$contact->prenom;
                    $fullName = $prenom . ' ' . $nom;
                    $avatar = file_exists((string)$contact->photo_profile) ? (string)$contact->photo_profile : 'images/image.png';
                    $lastMessage = $contact->messages->message ? (string)$contact->messages->message[count($contact->messages->message)-1]->contenu : 'Aucun message';
                    echo "
                    <div class='chat-item' data-user='contact-$contactId' data-name='$fullName' data-avatar='$avatar'>
                        <img src='$avatar' alt='$fullName'>
                        <div class='chat-info'>
                            <h4>$fullName</h4>
                            <p>$lastMessage</p>
                        </div>
                    </div>";
                }

                // Afficher les groupes
                if (!$xml->discussions->groupes->groupe) {
                    echo "<p>Aucun groupe trouvé</p>";
                }
                foreach ($xml->discussions->groupes->groupe as $groupe) {
                    $groupeId = (string)$groupe['id'];
                    $nomGroupe = (string)$groupe->nom_groupe;
                    $avatar = !empty($groupe->photo_groupe) && file_exists((string)$groupe->photo_groupe) ? (string)$groupe->photo_groupe : 'images/group.png';
                    $lastMessage = $groupe->messages->message ? (string)$groupe->messages->message[count($groupe->messages->message)-1]->contenu : 'Aucun message';
                    echo "
                    <div class='chat-item' data-user='groupe-$groupeId' data-name='$nomGroupe' data-avatar='$avatar'>
                        <img src='$avatar' alt='$nomGroupe'>
                        <div class='chat-info'>
                            <h4>$nomGroupe</h4>
                            <p>$lastMessage</p>
                        </div>
                    </div>";
                }
                ?>
            </div>
            <div class="sidebar-footer">
                <a href="add_contact.php" class="sidebar-button">Ajouter un contact</a>
                <a href="add_group.php" class="sidebar-button">Créer un groupe</a>
            </div>
        </aside>

        <main class="chat-app">
            <div class="chat-header">
                <img src="images/image.png" alt="Avatar">
                <div class="contact-info">
                    <h3>Sélectionnez une discussion</h3>
                    <p>En ligne</p>
                </div>
            </div>
            <div class="chat-body"></div>
            <form class="chat-footer" action="add.php" method="post">
                <input type="hidden" name="destinataire" value="">
                <input type="hidden" name="groupe" value="">
                <input type="hidden" name="expediteur" value="1">
                <input type="text" name="contenu_message" placeholder="Tapez un message..." />
                <button type="submit" class="chat-button">Envoyer</button>
            </form>
        </main>

        <aside class="right-sidebar">
            <div class="right-sidebar-header">
                <h2>Paramètres</h2>
            </div>
            <div class="right-sidebar-content">
                <div class="settings-section" id="dynamic-settings">
                    <h3>Profil</h3>
                    <p>Sélectionnez une discussion pour voir les détails.</p>
                </div>
            </div>
        </aside>
    </div>

    <script>
    window.messages = <?php
        $jsMessages = [];
        foreach ($xml->discussions->contacts->contact as $contact) {
            $contactId = (string)$contact['id'];
            $jsMessages["contact-$contactId"] = [];
            foreach ($contact->messages->message as $message) {
                $sender = (string)$message['expediteur'] === '1' ? 'sent' : 'received';
                $jsMessages["contact-$contactId"][] = [
                    'text' => (string)$message->contenu,
                    'time' => (string)$message->message_info['heure'],
                    'sender' => $sender,
                    'statut' => (string)$message->message_info['statut']
                ];
            }
        }
        foreach ($xml->discussions->groupes->groupe as $groupe) {
            $groupeId = (string)$groupe['id'];
            $jsMessages["groupe-$groupeId"] = [];
            foreach ($groupe->messages->message as $message) {
                $senderId = (string)$message['expediteur'];
                $senderName = $senderId === '1' ? 'Moi' : '';
                if ($senderId !== '1') {
                    $senderContact = $xml->xpath("//contact[@id='$senderId']")[0];
                    $senderName = (string)$senderContact->prenom . ' ' . (string)$senderContact->nom;
                }
                $sender = $senderId === '1' ? 'sent' : 'received';
                $jsMessages["groupe-$groupeId"][] = [
                    'text' => (string)$message->contenu,
                    'time' => (string)$message->message_info['heure'],
                    'sender' => $sender,
                    'senderName' => $senderName,
                    'statut' => (string)$message->message_info['statut']
                ];
            }
        }
        echo json_encode($jsMessages);
    ?>;

    window.contacts = <?php
        $jsContacts = [];
        foreach ($xml->discussions->contacts->contact as $contact) {
            $contactId = (string)$contact['id'];
            $jsContacts["contact-$contactId"] = [
                'prenom' => (string)$contact->prenom,
                'nom' => (string)$contact->nom,
                'numero' => (string)$contact->numero_telephone ?: 'Non défini',
                'photo_profile' => file_exists((string)$contact->photo_profile) ? (string)$contact->photo_profile : 'images/image.png',
                'status' => (string)$contact->status ?: 'Non défini'
            ];
        }
        echo json_encode($jsContacts);
    ?>;

    window.groupes = <?php
        $jsGroupes = [];
        foreach ($xml->discussions->groupes->groupe as $groupe) {
            $groupeId = (string)$groupe['id'];
            $membres = [];
            $membreIds = [];
            foreach ($groupe->membres->membre as $membre) {
                $membreId = (string)$membre['ref'];
                $membreContact = $xml->xpath("//contact[@id='$membreId']")[0] ?? null;
                if ($membreContact) {
                    $membres[] = (string)$membreContact->prenom . ' ' . (string)$membreContact->nom;
                    $membreIds[] = $membreId;
                }
            }
            $adminId = (string)$groupe->admin['ref'];
            $adminContact = $xml->xpath("//contact[@id='$adminId']")[0] ?? null;
            $adminName = $adminContact ? (string)$adminContact->prenom . ' ' . (string)$adminContact->nom : 'Admin inconnu';
            $jsGroupes["groupe-$groupeId"] = [
                'nom_groupe' => (string)$groupe->nom_groupe,
                'photo_groupe' => !empty($groupe->photo_groupe) && file_exists((string)$groupe->photo_groupe) ? (string)$groupe->photo_groupe : 'images/group.png',
                'membres' => $membres,
                'membreIds' => $membreIds,
                'admin' => $adminName,
                'adminId' => $adminId
            ];
        }
        echo json_encode($jsGroupes);
    ?>;

    window.allContacts = <?php
        $allContacts = [];
        foreach ($xml->discussions->contacts->contact as $contact) {
            $contactId = (string)$contact['id'];
            $allContacts[$contactId] = (string)$contact->prenom . ' ' . (string)$contact->nom;
        }
        echo json_encode($allContacts);
    ?>;

    console.log('Contacts:', window.contacts);
    console.log('Groupes:', window.groupes);
    console.log('All Contacts:', window.allContacts);
    </script>
    <script src="script.js"></script>
</body>
</html>