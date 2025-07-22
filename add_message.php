<?php
// Activer la gestion des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
libxml_use_internal_errors(true);

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Charger le fichier XML
    $xml = simplexml_load_file(__DIR__ . '/whatsapp.xml');
    if ($xml === false) {
        $errors = libxml_get_errors();
        $errorMessages = array_map(fn($e) => $e->message . " at line " . $e->line, $errors);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du chargement du fichier XML : ' . implode(', ', $errorMessages)]);
        exit;
    }

    // Fonction pour générer un nouvel ID de message
    function getNewMessageId($xml) {
        $ids = $xml->xpath("//message/@id");
        $ids = array_map('intval', $ids);
        return $ids ? max($ids) + 1 : 1;
    }

    // Valider les champs obligatoires
    if (empty($_POST['expediteur'])) {
        echo json_encode(['success' => false, 'message' => 'Expéditeur requis']);
        exit;
    }
    if (empty($_POST['contenu_message']) || (empty($_POST['destinataire']) && empty($_POST['groupe']))) {
        echo json_encode(['success' => false, 'message' => 'Contenu du message et destinataire ou groupe requis']);
        exit;
    }

    $expediteurId = htmlspecialchars($_POST['expediteur']);
    $contenuMessage = htmlspecialchars($_POST['contenu_message']);
    $nouvelId = getNewMessageId($xml);

    // Vérifier que l'expéditeur existe
    $expediteurObj = $xml->xpath("//contact[@id='$expediteurId']");
    if (empty($expediteurObj)) {
        echo json_encode(['success' => false, 'message' => "L'expéditeur avec l'ID $expediteurId n'existe pas"]);
        exit;
    }

    // Envoi de message à un contact
    if (!empty($_POST['destinataire'])) {
        $destinataireId = htmlspecialchars($_POST['destinataire']);
        $contactObj = $xml->xpath("//contact[@id='$destinataireId']");

        if ($contactObj) {
            $nouveauMessage = $contactObj[0]->messages->addChild('message');
            $nouveauMessage->addAttribute('id', $nouvelId);
            $nouveauMessage->addAttribute('type', 'texte');
            $nouveauMessage->addAttribute('expediteur', $expediteurId);
            $nouveauMessage->addAttribute('destinataire', $destinataireId);
            $nouveauMessage->addChild('contenu', $contenuMessage);
            $messageInfo = $nouveauMessage->addChild('message_info');
            $messageInfo->addAttribute('heure', date('Y-m-d\TH:i:s'));
            $messageInfo->addAttribute('statut', 'envoye');

            // Enregistrer le XML
            if ($xml->asXML(__DIR__ . '/whatsapp.xml')) {
                echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde du fichier XML']);
            }
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => "Le contact avec l'ID $destinataireId n'existe pas"]);
            exit;
        }
    }

    // Envoi de message à un groupe
    if (!empty($_POST['groupe'])) {
        $groupeId = htmlspecialchars($_POST['groupe']);
        $groupeObj = $xml->xpath("//groupe[@id='$groupeId']");

        if ($groupeObj) {
            $nouveauMessage = $groupeObj[0]->messages->addChild('message');
            $nouveauMessage->addAttribute('id', $nouvelId);
            $nouveauMessage->addAttribute('type', 'texte');
            $nouveauMessage->addAttribute('expediteur', $expediteurId);
            $nouveauMessage->addChild('contenu', $contenuMessage);
            $messageInfo = $nouveauMessage->addChild('message_info');
            $messageInfo->addAttribute('heure', date('Y-m-d\TH:i:s'));
            $messageInfo->addAttribute('statut', 'envoye');

            // Enregistrer le XML
            if ($xml->asXML(__DIR__ . '/whatsapp.xml')) {
                echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde du fichier XML']);
            }
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => "Le groupe avec l'ID $groupeId n'existe pas"]);
            exit;
        }
    }

    // Si aucun destinataire ou groupe n'est spécifié
    echo json_encode(['success' => false, 'message' => 'Destinataire ou groupe requis']);
    exit;
}
?>