<?php
// Enable libxml error handling
libxml_use_internal_errors(true);

// Charger le fichier XML
$xml = simplexml_load_file('whatsapp.xml');
if ($xml === false) {
    die("Erreur lors du chargement du fichier XML: " . implode("<br>", array_map(fn($e) => $e->message, libxml_get_errors())));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle message submission
    if (isset($_POST['contenu_message']) && !empty($_POST['contenu_message'])) {
        $expediteur = $_POST['expediteur'];
        $destinataire = $_POST['destinataire'] ?? '';
        $groupe = $_POST['groupe'] ?? '';
        $contenu = htmlspecialchars($_POST['contenu_message']);
        $heure = date('c');

        if ($destinataire) {
            $contact = $xml->xpath("//contact[@id='$destinataire']")[0];
            $message = $contact->messages->addChild('message');
            $message->addAttribute('expediteur', $expediteur);
            $message->addChild('contenu', $contenu);
            $message_info = $message->addChild('message_info');
            $message_info->addAttribute('heure', $heure);
            $message_info->addAttribute('check', 'false');
        } elseif ($groupe) {
            $groupeNode = $xml->xpath("//groupe[@id='$groupe']")[0];
            $message = $groupeNode->messages->addChild('message');
            $message->addAttribute('expediteur', $expediteur);
            $message->addChild('contenu', $contenu);
            $message_info = $message->addChild('message_info');
            $message_info->addAttribute('heure', $heure);
            $message_info->addAttribute('check', 'false');
        }
    }

    // Handle contact addition
    if (isset($_POST['ajouter_contact'])) {
        $prenom = htmlspecialchars($_POST['prenom']);
        $nom = htmlspecialchars($_POST['nom']);
        $numero_telephone = htmlspecialchars($_POST['numero_telephone'] ?? '');
        $status = htmlspecialchars($_POST['status'] ?? '');

        // Generate new contact ID
        $existingIds = array_map('strval', $xml->xpath("//contact/@id"));
        $newId = empty($existingIds) ? 1 : max($existingIds) + 1;

        // Add new contact to XML
        $contact = $xml->discussions->contacts->addChild('contact');
        $contact->addAttribute('id', $newId);
        $contact->addChild('prenom', $prenom);
        $contact->addChild('nom', $nom);
        $contact->addChild('numero_telephone', $numero_telephone);
        $contact->addChild('status', $status);
        $contact->addChild('messages');

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = 'images/';
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                die("Erreur : Le dossier 'images/' n'existe pas ou n'est pas inscriptible.");
            }
            $photoName = 'contact_' . $newId . '_' . time() . '.' . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoPath = $uploadDir . $photoName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                $contact->addChild('photo_profile', $photoPath);
            } else {
                $contact->addChild('photo_profile', 'images/image.png');
            }
        } else {
            $contact->addChild('photo_profile', 'images/image.png');
        }
    }

    // Handle contact update
    if (isset($_POST['update_contact']) && isset($_POST['contact_id'])) {
        $contactId = $_POST['contact_id'];
        $contact = $xml->xpath("//contact[@id='$contactId']")[0];
        $contact->prenom = htmlspecialchars($_POST['prenom']);
        $contact->nom = htmlspecialchars($_POST['nom']);
        $contact->numero_telephone = htmlspecialchars($_POST['numero_telephone'] ?? '');
        $contact->status = htmlspecialchars($_POST['status'] ?? '');

        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = 'images/';
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                die("Erreur : Le dossier 'images/' n'existe pas ou n'est pas inscriptible.");
            }
            $photoName = 'contact_' . $contactId . '_' . time() . '.' . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoPath = $uploadDir . $photoName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                $contact->photo_profile = $photoPath;
            }
        }
    }

    // Handle group creation
    if (isset($_POST['creer_groupe'])) {
        $nom_groupe = htmlspecialchars($_POST['nom_groupe']);
        $admin_id = htmlspecialchars($_POST['admin_id']);
        $membres = isset($_POST['membres']) && is_array($_POST['membres']) ? array_map('htmlspecialchars', $_POST['membres']) : [];

        // Generate new group ID
        $existingGroupIds = array_map('strval', $xml->xpath("//groupe/@id"));
        $newGroupId = empty($existingGroupIds) ? 1 : max($existingGroupIds) + 1;

        // Add new group to XML
        $groupe = $xml->discussions->groupes->addChild('groupe');
        $groupe->addAttribute('id', $newGroupId);
        $groupe->addChild('nom_groupe', $nom_groupe);
        $admin = $groupe->addChild('admin');
        $admin->addAttribute('ref', $admin_id);
        $membresNode = $groupe->addChild('membres');
        foreach ($membres as $membreId) {
            $membre = $membresNode->addChild('membre');
            $membre->addAttribute('ref', $membreId);
        }
        $groupe->addChild('messages');

        // Handle photo upload
        if (!empty($_FILES['photo_groupe']['name'])) {
            $uploadDir = 'images/';
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                die("Erreur : Le dossier 'images/' n'existe pas ou n'est pas inscriptible.");
            }
            $photoName = 'groupe_' . $newGroupId . '_' . time() . '.' . pathinfo($_FILES['photo_groupe']['name'], PATHINFO_EXTENSION);
            $photoPath = $uploadDir . $photoName;
            if (move_uploaded_file($_FILES['photo_groupe']['tmp_name'], $photoPath)) {
                $groupe->addChild('photo_groupe', $photoPath);
            } else {
                $groupe->addChild('photo_groupe', 'images/group.png');
            }
        } else {
            $groupe->addChild('photo_groupe', 'images/group.png');
        }
    }

    // Handle group update
    if (isset($_POST['update_groupe']) && isset($_POST['groupe_id'])) {
        $groupeId = $_POST['groupe_id'];
        $groupe = $xml->xpath("//groupe[@id='$groupeId']")[0];
        $groupe->nom_groupe = htmlspecialchars($_POST['nom_groupe']);
        $groupe->admin['ref'] = $_POST['admin_id'];

        if (!empty($_FILES['photo_groupe']['name'])) {
            $uploadDir = 'images/';
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                die("Erreur : Le dossier 'images/' n'existe pas ou n'est pas inscriptible.");
            }
            $photoName = 'groupe_' . $groupeId . '_' . time() . '.' . pathinfo($_FILES['photo_groupe']['name'], PATHINFO_EXTENSION);
            $photoPath = $uploadDir . $photoName;
            if (move_uploaded_file($_FILES['photo_groupe']['tmp_name'], $photoPath)) {
                $groupe->photo_groupe = $photoPath;
            }
        }

        // Add new member
        if (!empty($_POST['new_member'])) {
            $newMemberId = $_POST['new_member'];
            $existingMembers = [];
            foreach ($groupe->membres->membre as $membre) {
                $existingMembers[] = (string)$membre['ref'];
            }
            if (!in_array($newMemberId, $existingMembers)) {
                $newMembre = $groupe->membres->addChild('membre');
                $newMembre->addAttribute('ref', $newMemberId);
            }
        }

        // Remove member
        if (!empty($_POST['remove_member_id'])) {
            $removeMemberId = $_POST['remove_member_id'];
            foreach ($groupe->membres->membre as $membre) {
                if ((string)$membre['ref'] === $removeMemberId) {
                    $dom = dom_import_simplexml($membre);
                    $dom->parentNode->removeChild($dom);
                    break;
                }
            }
        }
    }

    // Save XML
    $xml->asXML('whatsapp.xml');
    header('Location: index.php');
    exit;
}
?>