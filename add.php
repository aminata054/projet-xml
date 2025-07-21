<?php
// Activer la gestion des erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Activer la gestion des erreurs libxml
libxml_use_internal_errors(true);

// Inclure auto_save.php pour les sauvegardes
require_once 'auto_save.php';
$autoSave = new AutoSaveManager('whatsapp.xml');

// Charger le fichier XML
$xml = simplexml_load_file('whatsapp.xml');
if ($xml === false) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur chargement XML: ' . implode(', ', array_map(fn($e) => $e->message, libxml_get_errors()))]);
    exit;
}

// Valider XML avec DTD
$dom = new DOMDocument();
$dom->loadXML(file_get_contents('whatsapp.xml'));
if (!$dom->validate()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Fichier XML invalide selon la DTD']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout de message
    if (isset($_POST['contenu_message']) && !empty($_POST['contenu_message'])) {
        $expediteur = htmlspecialchars($_POST['expediteur']);
        $destinataire = htmlspecialchars($_POST['destinataire'] ?? '');
        $groupe = htmlspecialchars($_POST['groupe'] ?? '');
        $contenu = htmlspecialchars($_POST['contenu_message']);
        $heure = date('c');

        // Générer un ID unique pour le message
        $existingMessageIds = array_map('strval', $xml->xpath("//message/@id"));
        $newMessageId = empty($existingMessageIds) ? 1 : max($existingMessageIds) + 1;

        if ($destinataire) {
            $contact = $xml->xpath("//contact[@id='$destinataire']")[0] ?? null;
            if ($contact) {
                $message = $contact->messages->addChild('message');
                $message->addAttribute('id', $newMessageId);
                $message->addAttribute('type', 'texte');
                $message->addAttribute('expediteur', $expediteur);
                $message->addAttribute('destinataire', $destinataire);
                $message->addChild('contenu', $contenu);
                $message_info = $message->addChild('message_info');
                $message_info->addAttribute('heure', $heure);
                $message_info->addAttribute('statut', 'envoyé');
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Contact destinataire non trouvé']);
                exit;
            }
        } elseif ($groupe) {
            $groupeNode = $xml->xpath("//groupe[@id='$groupe']")[0] ?? null;
            if ($groupeNode) {
                $message = $groupeNode->messages->addChild('message');
                $message->addAttribute('id', $newMessageId);
                $message->addAttribute('type', 'texte');
                $message->addAttribute('expediteur', $expediteur);
                $message->addChild('contenu', $contenu);
                $message_info = $message->addChild('message_info');
                $message_info->addAttribute('heure', $heure);
                $message_info->addAttribute('statut', 'envoyé');
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Groupe non trouvé']);
                exit;
            }
        }

        // Sauvegarder XML et créer une sauvegarde
        if ($xml->asXML('whatsapp.xml')) {
            $autoSave->createBackup();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Message ajouté avec succès']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde XML']);
        }
        exit;
    }

    // Ajout de contact
    if (isset($_POST['ajouter_contact'])) {
        $prenom = htmlspecialchars($_POST['prenom']);
        $nom = htmlspecialchars($_POST['nom']);
        $numero_telephone = htmlspecialchars($_POST['numero_telephone'] ?? '');
        $status = htmlspecialchars($_POST['status'] ?? '');

        // Vérifier doublon numéro
        $existingContact = $xml->xpath("//contact[numero_telephone='$numero_telephone']");
        if ($existingContact) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Numéro de téléphone déjà utilisé']);
            exit;
        }

        // Générer un nouvel ID
        $existingIds = array_map('strval', $xml->xpath("//contact/@id"));
        $newId = empty($existingIds) ? 1 : max($existingIds) + 1;

        // Ajouter le contact au XML
        $contact = $xml->discussions->contacts->addChild('contact');
        $contact->addAttribute('id', $newId);
        $contact->addChild('prenom', $prenom);
        $contact->addChild('nom', $nom);
        $contact->addChild('numero_telephone', $numero_telephone);
        $contact->addChild('status', $status);
        $contact->addChild('photo_profile', 'images/image.png');
        $contact->addChild('messages');

        // Gestion de la photo
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = 'images/';
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Dossier 'images/' non existant ou non inscriptible"]);
                exit;
            }
            $photoName = 'contact_' . $newId . '_' . time() . '.' . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoPath = $uploadDir . $photoName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                $contact->photo_profile = $photoPath;
            }
        }

        // Sauvegarder XML et créer une sauvegarde
        if ($xml->asXML('whatsapp.xml')) {
            $autoSave->createBackup();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Contact ajouté avec succès']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde XML']);
        }
        exit;
    }

    // Mise à jour de contact
    if (isset($_POST['update_contact']) && isset($_POST['contact_id'])) {
        $contactId = htmlspecialchars($_POST['contact_id']);
        $contact = $xml->xpath("//contact[@id='$contactId']")[0] ?? null;
        if ($contact) {
            $contact->prenom = htmlspecialchars($_POST['prenom']);
            $contact->nom = htmlspecialchars($_POST['nom']);
            $contact->numero_telephone = htmlspecialchars($_POST['numero_telephone'] ?? '');
            $contact->status = htmlspecialchars($_POST['status'] ?? '');

            if (!empty($_FILES['photo']['name'])) {
                $uploadDir = 'images/';
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => "Dossier 'images/' non existant ou non inscriptible"]);
                    exit;
                }
                $photoName = 'contact_' . $contactId . '_' . time() . '.' . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photoPath = $uploadDir . $photoName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                    $contact->photo_profile = $photoPath;
                }
            }

            // Sauvegarder XML et créer une sauvegarde
            if ($xml->asXML('whatsapp.xml')) {
                $autoSave->createBackup();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Contact mis à jour avec succès']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde XML']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Contact non trouvé']);
        }
        exit;
    }

    // Création de groupe
    if (isset($_POST['creer_groupe'])) {
        $nom_groupe = htmlspecialchars($_POST['nom_groupe']);
        $admin_id = htmlspecialchars($_POST['admin_id']);
        $membres = isset($_POST['membres']) && is_array($_POST['membres']) ? array_map('htmlspecialchars', $_POST['membres']) : [];

        // Vérifier nombre minimum de membres
        if (count($membres) < 2) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Un groupe doit avoir au moins 2 membres']);
            exit;
        }

        // Générer un nouvel ID de groupe
        $existingGroupIds = array_map('strval', $xml->xpath("//groupe/@id"));
        $newGroupId = empty($existingGroupIds) ? 1 : max($existingGroupIds) + 1;

        // Ajouter le groupe au XML
        $groupe = $xml->discussions->groupes->addChild('groupe');
        $groupe->addAttribute('id', $newGroupId);
        $groupe->addChild('nom_groupe', $nom_groupe);
        $groupe->addChild('photo_groupe', 'images/group.png');
        $admin = $groupe->addChild('admin');
        $admin->addAttribute('ref', $admin_id);
        $membresNode = $groupe->addChild('membres');
        foreach ($membres as $membreId) {
            $membre = $membresNode->addChild('membre');
            $membre->addAttribute('ref', $membreId);
        }
        $groupe->addChild('messages');

        // Gestion de la photo du groupe
        if (!empty($_FILES['photo_groupe']['name'])) {
            $uploadDir = 'images/';
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Dossier 'images/' non existant ou non inscriptible"]);
                exit;
            }
            $photoName = 'groupe_' . $newGroupId . '_' . time() . '.' . pathinfo($_FILES['photo_groupe']['name'], PATHINFO_EXTENSION);
            $photoPath = $uploadDir . $photoName;
            if (move_uploaded_file($_FILES['photo_groupe']['tmp_name'], $photoPath)) {
                $groupe->photo_groupe = $photoPath;
            }
        }

        // Sauvegarder XML et créer une sauvegarde
        if ($xml->asXML('whatsapp.xml')) {
            $autoSave->createBackup();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Groupe créé avec succès']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde XML']);
        }
        exit;
    }

    // Mise à jour de groupe
    if (isset($_POST['update_groupe']) && isset($_POST['groupe_id'])) {
        $groupeId = htmlspecialchars($_POST['groupe_id']);
        $groupe = $xml->xpath("//groupe[@id='$groupeId']")[0] ?? null;
        if ($groupe) {
            $groupe->nom_groupe = htmlspecialchars($_POST['nom_groupe']);
            $groupe->admin['ref'] = htmlspecialchars($_POST['admin_id']);

            if (!empty($_FILES['photo_groupe']['name'])) {
                $uploadDir = 'images/';
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => "Dossier 'images/' non existant ou non inscriptible"]);
                    exit;
                }
                $photoName = 'groupe_' . $groupeId . '_' . time() . '.' . pathinfo($_FILES['photo_groupe']['name'], PATHINFO_EXTENSION);
                $photoPath = $uploadDir . $photoName;
                if (move_uploaded_file($_FILES['photo_groupe']['tmp_name'], $photoPath)) {
                    $groupe->photo_groupe = $photoPath;
                }
            }

            // Ajouter un nouveau membre
            if (!empty($_POST['new_member'])) {
                $newMemberId = htmlspecialchars($_POST['new_member']);
                $existingMembers = [];
                foreach ($groupe->membres->membre as $membre) {
                    $existingMembers[] = (string)$membre['ref'];
                }
                if (!in_array($newMemberId, $existingMembers)) {
                    $newMembre = $groupe->membres->addChild('membre');
                    $newMembre->addAttribute('ref', $newMemberId);
                }
            }

            // Supprimer un membre
            if (!empty($_POST['remove_member_id'])) {
                $removeMemberId = htmlspecialchars($_POST['remove_member_id']);
                foreach ($groupe->membres->membre as $membre) {
                    if ((string)$membre['ref'] === $removeMemberId) {
                        $dom = dom_import_simplexml($membre);
                        $dom->parentNode->removeChild($dom);
                        break;
                    }
                }
            }

            // Sauvegarder XML et créer une sauvegarde
            if ($xml->asXML('whatsapp.xml')) {
                $autoSave->createBackup();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Groupe mis à jour avec succès']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde XML']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Groupe non trouvé']);
        }
        exit;
    }

    // Si aucune action valide, renvoyer une erreur
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    exit;
}
?>