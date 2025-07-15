<?php
// Inclure les nouvelles fonctionnalités
require_once 'history.php';
require_once 'auto_save.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialiser les gestionnaires
    $history = new HistoryManager();
    $autoSave = new AutoSaveManager('whatsapp.xml');
    
    // Charger le fichier XML
    $xml = simplexml_load_file('whatsapp.xml');

    // Fonction pour générer un nouvel ID de message
    function getNewMessageId($xml) {
        $ids = $xml->xpath("//message/@id");
        $ids = array_map('intval', $ids);
        return $ids ? max($ids) + 1 : 1;
    }

    // Envoi de message à un contact
    if (!empty($_POST['destinataire']) && !empty($_POST['contenu_message'])) {
        $destinataireId = filter_var($_POST['destinataire'], FILTER_VALIDATE_INT);
        $contenuMessage = htmlspecialchars($_POST['contenu_message'], ENT_QUOTES, 'UTF-8');
        $expediteurId = filter_var($_POST['expediteur'], FILTER_VALIDATE_INT);

        if (!$destinataireId || !$expediteurId) {
            die("Erreur : IDs invalides");
        }

        // Trouver le contact correspondant
        $contactObj = $xml->xpath("//contact[@id='$destinataireId']");

        if ($contactObj) {
            // Générer un nouvel identifiant pour le message
            $nouvelId = getNewMessageId($xml);

            // Créer un nouvel élément de message
            $nouveauMessage = $contactObj[0]->messages->addChild('message');
            $nouveauMessage->addAttribute('id', $nouvelId);
            $nouveauMessage->addAttribute('type', 'texte');
            $nouveauMessage->addAttribute('expediteur', $expediteurId);
            $nouveauMessage->addAttribute('destinataire', $destinataireId);

            // Ajouter le contenu du message
            $nouveauMessage->addChild('contenu', $contenuMessage);

            // Ajouter les informations sur le message
            $messageInfo = $nouveauMessage->addChild('message_info');
            $messageInfo->addAttribute('heure', date('Y-m-d\TH:i:s'));
            $messageInfo->addAttribute('statut', 'envoye');

            // Enregistrer les modifications dans le fichier XML
            $xml->asXML('whatsapp.xml');
            
            // Logger l'action
            $contactName = (string)$contactObj[0]->prenom . ' ' . (string)$contactObj[0]->nom;
            $history->logAction('send_message', "Message envoyé à $contactName: $contenuMessage", $expediteurId);
            
            // Créer une sauvegarde
            $autoSave->createBackup();

            // Rediriger vers index.php
            header('Location: index.php');
            exit;
        } else {
            echo "Erreur : Le contact avec l'ID $destinataireId n'existe pas.";
        }
    }

    // Envoi de message à un groupe
    if (!empty($_POST['groupe']) && !empty($_POST['contenu_message'])) {
        $groupeId = filter_var($_POST['groupe'], FILTER_VALIDATE_INT);
        $contenuMessage = htmlspecialchars($_POST['contenu_message'], ENT_QUOTES, 'UTF-8');
        $expediteurId = filter_var($_POST['expediteur'], FILTER_VALIDATE_INT);

        if (!$groupeId || !$expediteurId) {
            die("Erreur : IDs invalides");
        }

        // Trouver le groupe correspondant
        $groupeObj = $xml->xpath("//groupe[@id='$groupeId']");

        if ($groupeObj) {
            // Générer un nouvel identifiant pour le message
            $nouvelId = getNewMessageId($xml);

            // Créer un nouvel élément de message
            $nouveauMessage = $groupeObj[0]->messages->addChild('message');
            $nouveauMessage->addAttribute('id', $nouvelId);
            $nouveauMessage->addAttribute('type', 'texte');
            $nouveauMessage->addAttribute('expediteur', $expediteurId);

            // Ajouter le contenu du message
            $nouveauMessage->addChild('contenu', $contenuMessage);

            // Ajouter les informations sur le message
            $messageInfo = $nouveauMessage->addChild('message_info');
            $messageInfo->addAttribute('heure', date('Y-m-d\TH:i:s'));
            $messageInfo->addAttribute('statut', 'envoye');

            // Enregistrer les modifications dans le fichier XML
            $xml->asXML('whatsapp.xml');
            
            // Logger l'action
            $groupeName = (string)$groupeObj[0]->nom_groupe;
            $history->logAction('send_group_message', "Message envoyé au groupe $groupeName: $contenuMessage", $expediteurId);
            
            // Créer une sauvegarde
            $autoSave->createBackup();

            // Rediriger vers index.php
            header('Location: index.php');
            exit;
        } else {
            echo "Erreur : Le groupe avec l'ID $groupeId n'existe pas.";
        }
    }

    // Si les champs requis ne sont pas remplis
    if ((isset($_POST['destinataire']) || isset($_POST['groupe']) || isset($_POST['contenu_message'])) && 
        (empty($_POST['contenu_message']) || (empty($_POST['destinataire']) && empty($_POST['groupe'])))) {
        echo "Erreur : Veuillez remplir tous les champs du formulaire.";
    }
}
?>
