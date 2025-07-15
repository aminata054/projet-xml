<?php
class NotificationManager {
    private $xmlFile;
    private $lastModified;
    
    public function __construct($xmlFile) {
        $this->xmlFile = $xmlFile;
        $this->lastModified = filemtime($xmlFile);
    }
    
    public function checkForUpdates() {
        $currentModified = filemtime($this->xmlFile);
        
        if ($currentModified > $this->lastModified) {
            $this->lastModified = $currentModified;
            return $this->getRecentMessages();
        }
        
        return [];
    }
    
    private function getRecentMessages($minutes = 1) {
        $xml = simplexml_load_file($this->xmlFile);
        $recentMessages = [];
        $cutoffTime = time() - ($minutes * 60);
        
        // Messages des contacts
        foreach ($xml->discussions->contacts->contact as $contact) {
            foreach ($contact->messages->message as $message) {
                $messageTime = strtotime((string)$message->message_info['heure']);
                if ($messageTime > $cutoffTime) {
                    $recentMessages[] = [
                        'type' => 'contact',
                        'id' => (string)$message['id'],
                        'contenu' => (string)$message->contenu,
                        'expediteur' => (string)$message['expediteur'],
                        'contact_id' => (string)$contact['id'],
                        'contact_nom' => (string)$contact->prenom . ' ' . (string)$contact->nom,
                        'heure' => (string)$message->message_info['heure']
                    ];
                }
            }
        }
        
        // Messages des groupes
        foreach ($xml->discussions->groupes->groupe as $groupe) {
            foreach ($groupe->messages->message as $message) {
                $messageTime = strtotime((string)$message->message_info['heure']);
                if ($messageTime > $cutoffTime) {
                    $recentMessages[] = [
                        'type' => 'groupe',
                        'id' => (string)$message['id'],
                        'contenu' => (string)$message->contenu,
                        'expediteur' => (string)$message['expediteur'],
                        'groupe_id' => (string)$groupe['id'],
                        'groupe_nom' => (string)$groupe->nom_groupe,
                        'heure' => (string)$message->message_info['heure']
                    ];
                }
            }
        }
        
        return $recentMessages;
    }
    
    public function getUnreadCount($userId) {
        $xml = simplexml_load_file($this->xmlFile);
        $unreadCount = 0;
        
        // Compter les messages non lus des contacts
        foreach ($xml->discussions->contacts->contact as $contact) {
            foreach ($contact->messages->message as $message) {
                if ((string)$message['destinataire'] === $userId && 
                    (string)$message->message_info['statut'] !== 'lu') {
                    $unreadCount++;
                }
            }
        }
        
        return $unreadCount;
    }
}

// API endpoint pour les notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $notifications = new NotificationManager('whatsapp.xml');
    
    switch ($_GET['action']) {
        case 'check_updates':
            echo json_encode($notifications->checkForUpdates());
            break;
            
        case 'unread_count':
            $userId = $_GET['user_id'] ?? '1';
            echo json_encode(['count' => $notifications->getUnreadCount($userId)]);
            break;
            
        default:
            echo json_encode(['error' => 'Action non reconnue']);
    }
    exit;
}
?>
