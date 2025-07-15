<?php
class HistoryManager {
    private $historyFile = 'history.xml';
    
    public function __construct() {
        if (!file_exists($this->historyFile)) {
            $this->createHistoryFile();
        }
    }
    
    private function createHistoryFile() {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><history></history>');
        $xml->asXML($this->historyFile);
    }
    
    public function logAction($action, $details, $userId = '1') {
        $xml = simplexml_load_file($this->historyFile);
        
        $entry = $xml->addChild('entry');
        $entry->addAttribute('id', uniqid());
        $entry->addAttribute('timestamp', date('c'));
        $entry->addAttribute('user_id', $userId);
        
        $entry->addChild('action', htmlspecialchars($action));
        $entry->addChild('details', htmlspecialchars($details));
        
        $xml->asXML($this->historyFile);
    }
    
    public function getHistory($limit = 50, $userId = null) {
        if (!file_exists($this->historyFile)) {
            return [];
        }
        
        $xml = simplexml_load_file($this->historyFile);
        $entries = [];
        
        foreach ($xml->entry as $entry) {
            if ($userId && (string)$entry['user_id'] !== $userId) {
                continue;
            }
            
            $entries[] = [
                'id' => (string)$entry['id'],
                'timestamp' => (string)$entry['timestamp'],
                'user_id' => (string)$entry['user_id'],
                'action' => (string)$entry->action,
                'details' => (string)$entry->details
            ];
        }
        
        // Trier par timestamp décroissant
        usort($entries, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($entries, 0, $limit);
    }
    
    public function clearHistory($olderThan = null) {
        if ($olderThan) {
            $xml = simplexml_load_file($this->historyFile);
            $cutoffTime = strtotime($olderThan);
            
            foreach ($xml->entry as $entry) {
                $entryTime = strtotime((string)$entry['timestamp']);
                if ($entryTime < $cutoffTime) {
                    $dom = dom_import_simplexml($entry);
                    $dom->parentNode->removeChild($dom);
                }
            }
            
            $xml->asXML($this->historyFile);
        } else {
            $this->createHistoryFile();
        }
    }
}

// API endpoint pour l'historique
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $history = new HistoryManager();
    
    switch ($_GET['action']) {
        case 'get_history':
            $limit = $_GET['limit'] ?? 50;
            $userId = $_GET['user_id'] ?? null;
            echo json_encode($history->getHistory($limit, $userId));
            break;
            
        case 'clear_history':
            $olderThan = $_GET['older_than'] ?? null;
            $history->clearHistory($olderThan);
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['error' => 'Action non reconnue']);
    }
    exit;
}
?>
