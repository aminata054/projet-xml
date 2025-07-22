<?php
// Enable libxml error handling
libxml_use_internal_errors(true);

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Charger le fichier XML
$xml = simplexml_load_file('whatsapp.xml');
if ($xml === false) {
    $errors = libxml_get_errors();
    $errorMessage = "Erreur lors du chargement du fichier XML: " . implode(", ", array_map(fn($e) => $e->message, $errors));
    sendJsonResponse(false, $errorMessage);
}

function generateMessageId($xml) {
    // Récupérer tous les IDs de messages existants
    $existingIds = [];
    
    // Messages des contacts
    foreach ($xml->xpath('//contact/messages/message/@id') as $id) {
        $existingIds[] = (int)$id;
    }
    
    // Messages des groupes
    foreach ($xml->xpath('//groupe/messages/message/@id') as $id) {
        $existingIds[] = (int)$id;
    }
    
    // Retourner le prochain ID disponible
    return empty($existingIds) ? 1 : max($existingIds) + 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle message submission
        if (isset($_POST['contenu_message']) && !empty(trim($_POST['contenu_message']))) {
            $expediteur = $_POST['expediteur'] ?? '1';
            $destinataire = $_POST['destinataire'] ?? '';
            $groupe = $_POST['groupe'] ?? '';
            $contenu = htmlspecialchars(trim($_POST['contenu_message']));
            $heure = date('c');
            
            $messageId = generateMessageId($xml);
            
            if (!empty($destinataire)) {
                // Message vers un contact individuel
                $contact = $xml->xpath("//contact[@id='$destinataire']")[0];
                if ($contact) {
                    if (!isset($contact->messages)) {
                        $contact->addChild('messages');
                    }
                    
                    $message = $contact->messages->addChild('message');
                    $message->addAttribute('id', $messageId);
                    $message->addAttribute('type', 'texte');
                    $message->addAttribute('expediteur', $expediteur);
                    $message->addAttribute('destinataire', $destinataire);
                    $message->addChild('contenu', $contenu);
                    
                    $message_info = $message->addChild('message_info');
                    $message_info->addAttribute('heure', $heure);
                    $message_info->addAttribute('statut', 'double check');
                } else {
                    sendJsonResponse(false, "Contact avec ID $destinataire non trouvé");
                }
                
            } elseif (!empty($groupe)) {
                // Message vers un groupe
                $groupeNode = $xml->xpath("//groupe[@id='$groupe']")[0];
                if ($groupeNode) {
                    if (!isset($groupeNode->messages)) {
                        $groupeNode->addChild('messages');
                    }
                    
                    $message = $groupeNode->messages->addChild('message');
                    $message->addAttribute('id', $messageId);
                    $message->addAttribute('type', 'texte');
                    $message->addAttribute('expediteur', $expediteur);
                    $message->addChild('contenu', $contenu);
                    
                    $message_info = $message->addChild('message_info');
                    $message_info->addAttribute('heure', $heure);
                    $message_info->addAttribute('statut', 'double check');
                } else {
                    sendJsonResponse(false, "Groupe avec ID $groupe non trouvé");
                }
            } else {
                sendJsonResponse(false, "Aucun destinataire spécifié");
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
            if ($contact) {
                if (empty(trim($_POST['prenom']))) {
                    sendJsonResponse(false, "Le prénom est requis");
                }
                if (empty(trim($_POST['nom']))) {
                    sendJsonResponse(false, "Le nom est requis");
                }

                $contact->prenom = htmlspecialchars(trim($_POST['prenom']));
                $contact->nom = htmlspecialchars(trim($_POST['nom']));
                $contact->numero_telephone = htmlspecialchars($_POST['numero_telephone'] ?? '');
                $contact->status = htmlspecialchars($_POST['status'] ?? 'Hors ligne');

                if (!empty($_FILES['photo']['name'])) {
                    $uploadDir = 'images/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $photoName = 'contact_' . $contactId . '_' . time() . '.' . $extension;
                    $photoPath = $uploadDir . $photoName;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                        $contact->photo_profile = $photoPath;
                    }
                }
            } else {
                sendJsonResponse(false, "Contact non trouvé");
            }
        }

       // Handle group creation
if (isset($_POST['creer_groupe'])) {
    if (empty(trim($_POST['nom_groupe']))) {
        sendJsonResponse(false, "Le nom du groupe est requis");
    }

    $nom_groupe = htmlspecialchars(trim($_POST['nom_groupe']));
    $admin_id = htmlspecialchars($_POST['admin_id'] ?? '1');
    $membres = isset($_POST['membres']) && is_array($_POST['membres']) ? array_map('htmlspecialchars', $_POST['membres']) : [];

    // Validate inputs
    if (count($membres) < 2) {
        sendJsonResponse(false, "Au moins 2 membres doivent être sélectionnés");
    }
    if (!in_array($admin_id, $membres)) {
        sendJsonResponse(false, "L'administrateur doit être un membre du groupe");
    }

    // Generate new group ID
    $existingGroupIds = [];
    foreach ($xml->xpath("//groupe/@id") as $id) {
        $existingGroupIds[] = (int)$id;
    }
    $newGroupId = empty($existingGroupIds) ? 1 : max($existingGroupIds) + 1;

    // Ensure groupes node exists
    if (!isset($xml->discussions->groupes)) {
        $xml->discussions->addChild('groupes');
    }

    // Add new group to XML
    $groupe = $xml->discussions->groupes->addChild('groupe');
    $groupe->addAttribute('id', $newGroupId);
    $groupe->addChild('nom_groupe', $nom_groupe);

    // Handle photo upload
    $photoPath = 'images/group.png'; // Default photo
    if (!empty($_FILES['photo_groupe']['name'])) {
        $uploadDir = 'images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($_FILES['photo_groupe']['name'], PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($extension), $allowedTypes) && $_FILES['photo_groupe']['size'] <= 2 * 1024 * 1024) {
            $photoName = 'groupe_' . $newGroupId . '_' . time() . '.' . $extension;
            $photoPath = $uploadDir . $photoName;
            
            if (!move_uploaded_file($_FILES['photo_groupe']['tmp_name'], $photoPath)) {
                $photoPath = 'images/group.png';
            }
        } else {
            sendJsonResponse(false, "Photo invalide ou trop volumineuse (max 2MB)");
        }
    }
    $groupe->addChild('photo_groupe', $photoPath);

    // Add members with full contact details
    $membresNode = $groupe->addChild('membres');
    foreach ($membres as $membreId) {
        // CORRECTION : Utiliser XPath pour trouver le contact
        $contactResults = $xml->xpath("//contact[@id='$membreId']");
        if (!empty($contactResults)) {
            $contact = $contactResults[0];
            $newContact = $membresNode->addChild('contact');
            $newContact->addAttribute('id', $membreId);
            $newContact->addChild('nom', (string)$contact->nom);
            $newContact->addChild('prenom', (string)$contact->prenom);
            $newContact->addChild('numero_telephone', (string)$contact->numero_telephone);
            $newContact->addChild('photo_profile', (string)$contact->photo_profile);
            $newContact->addChild('status', (string)$contact->status);
        } else {
            // Remove the group and fail if any contact is not found
            $dom = dom_import_simplexml($groupe);
            $dom->parentNode->removeChild($dom);
            sendJsonResponse(false, "Contact avec ID $membreId introuvable");
        }
    }

    // Add admin
    $admin = $groupe->addChild('admin');
    $admin->addAttribute('ref', $admin_id);

    // Initialize empty messages
    $groupe->addChild('messages');
}

        // Handle group update
        if (isset($_POST['update_groupe']) && isset($_POST['groupe_id'])) {
            $groupeId = $_POST['groupe_id'];
            $groupe = $xml->xpath("//groupe[@id='$groupeId']")[0];
            if ($groupe) {
                if (empty(trim($_POST['nom_groupe']))) {
                    sendJsonResponse(false, "Le nom du groupe est requis");
                }

                $groupe->nom_groupe = htmlspecialchars(trim($_POST['nom_groupe']));
                $groupe->admin['ref'] = $_POST['admin_id'] ?? '1';

                if (!empty($_FILES['photo_groupe']['name'])) {
                    $uploadDir = 'images/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $extension = pathinfo($_FILES['photo_groupe']['name'], PATHINFO_EXTENSION);
                    $photoName = 'groupe_' . $groupeId . '_' . time() . '.' . $extension;
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
            } else {
                sendJsonResponse(false, "Groupe non trouvé");
            }
        }

        // Save XML
        if ($xml->asXML('whatsapp.xml')) {
            // Déterminer le message de succès selon l'action
            $successMessage = "Opération réussie";
            if (isset($_POST['ajouter_contact'])) {
                $successMessage = "Contact ajouté avec succès";
            } elseif (isset($_POST['update_contact'])) {
                $successMessage = "Contact mis à jour avec succès";
            } elseif (isset($_POST['creer_groupe'])) {
                $successMessage = "Groupe créé avec succès";
            } elseif (isset($_POST['update_groupe'])) {
                $successMessage = "Groupe mis à jour avec succès";
            } elseif (isset($_POST['contenu_message'])) {
                $successMessage = "Message envoyé avec succès";
            }

            // Si c'est une requête AJAX (pour les contacts/groupes), renvoyer du JSON
            if (isset($_POST['ajouter_contact']) || isset($_POST['creer_groupe'])) {
                sendJsonResponse(true, $successMessage);
            } else {
                // Pour les autres actions, rediriger
                header('Location: index.php');
                exit;
            }
        } else {
            sendJsonResponse(false, "Erreur lors de la sauvegarde du fichier XML");
        }

    } catch (Exception $e) {
        error_log("Erreur dans add.php: " . $e->getMessage());
        sendJsonResponse(false, "Erreur serveur: " . $e->getMessage());
    }
} else {
    sendJsonResponse(false, "Méthode non autorisée");
}


// Fonction pour supprimer un contact
function deleteContact($contactId, $xmlFile = 'whatsapp.xml') {
    try {
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            return ['success' => false, 'message' => 'Erreur lors du chargement du fichier XML'];
        }

        $contactFound = false;
        $xpath = "//contact[@id='$contactId']";
        $contactsToDelete = $xml->xpath($xpath);
        
        if (!empty($contactsToDelete)) {
            foreach ($contactsToDelete as $contact) {
                $dom = dom_import_simplexml($contact);
                $dom->parentNode->removeChild($dom);
                $contactFound = true;
            }
        }

        if (!$contactFound) {
            return ['success' => false, 'message' => 'Contact non trouvé'];
        }

        // Sauvegarder
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        
        if ($dom->save($xmlFile)) {
            return ['success' => true, 'message' => 'Contact supprimé avec succès'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la sauvegarde'];
        }

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
    }
}

// Fonction pour supprimer un groupe
function deleteGroup($groupId, $xmlFile = 'whatsapp.xml') {
    try {
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            return ['success' => false, 'message' => 'Erreur lors du chargement du fichier XML'];
        }

        $groupFound = false;
        $xpath = "//groupe[@id='$groupId']";
        $groupsToDelete = $xml->xpath($xpath);
        
        if (!empty($groupsToDelete)) {
            foreach ($groupsToDelete as $group) {
                $dom = dom_import_simplexml($group);
                $dom->parentNode->removeChild($dom);
                $groupFound = true;
            }
        }

        if (!$groupFound) {
            return ['success' => false, 'message' => 'Groupe non trouvé'];
        }

        // Sauvegarder
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        
        if ($dom->save($xmlFile)) {
            return ['success' => true, 'message' => 'Groupe supprimé avec succès'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la sauvegarde'];
        }

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
    }
}

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Suppression d'un contact
    if (isset($_POST['delete_contact'])) {
        $contactId = $_POST['contact_id'] ?? '';
        
        if (empty($contactId)) {
            echo json_encode(['success' => false, 'message' => 'ID du contact manquant']);
            exit;
        }

        $result = deleteContact($contactId);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // Suppression d'un groupe
    if (isset($_POST['delete_group'])) {
        $groupId = $_POST['group_id'] ?? '';
        
        if (empty($groupId)) {
            echo json_encode(['success' => false, 'message' => 'ID du groupe manquant']);
            exit;
        }

        $result = deleteGroup($groupId);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
?>