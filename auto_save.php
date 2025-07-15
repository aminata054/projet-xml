<?php
class AutoSaveManager {
    private $xmlFile;
    private $backupDir = 'backups/';
    private $maxBackups = 10;
    
    public function __construct($xmlFile) {
        $this->xmlFile = $xmlFile;
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    public function createBackup() {
        if (!file_exists($this->xmlFile)) {
            return false;
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . 'whatsapp_backup_' . $timestamp . '.xml';
        
        if (copy($this->xmlFile, $backupFile)) {
            $this->cleanOldBackups();
            return $backupFile;
        }
        
        return false;
    }
    
    private function cleanOldBackups() {
        $backups = glob($this->backupDir . 'whatsapp_backup_*.xml');
        
        if (count($backups) > $this->maxBackups) {
            // Trier par date de modification
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Supprimer les plus anciens
            $toDelete = array_slice($backups, 0, count($backups) - $this->maxBackups);
            foreach ($toDelete as $file) {
                unlink($file);
            }
        }
    }
    
    public function autoSave() {
        // Créer une sauvegarde toutes les 5 minutes
        $lastBackup = $this->getLastBackupTime();
        $now = time();
        
        if ($now - $lastBackup > 300) { // 5 minutes
            return $this->createBackup();
        }
        
        return false;
    }
    
    private function getLastBackupTime() {
        $backups = glob($this->backupDir . 'whatsapp_backup_*.xml');
        
        if (empty($backups)) {
            return 0;
        }
        
        $lastModified = 0;
        foreach ($backups as $backup) {
            $modified = filemtime($backup);
            if ($modified > $lastModified) {
                $lastModified = $modified;
            }
        }
        
        return $lastModified;
    }
    
    public function restoreBackup($backupFile) {
        if (!file_exists($backupFile)) {
            return false;
        }
        
        // Valider le fichier XML avant la restauration
        $xml = simplexml_load_file($backupFile);
        if ($xml === false) {
            return false;
        }
        
        return copy($backupFile, $this->xmlFile);
    }
    
    public function getBackupList() {
        $backups = glob($this->backupDir . 'whatsapp_backup_*.xml');
        $backupList = [];
        
        foreach ($backups as $backup) {
            $backupList[] = [
                'filename' => basename($backup),
                'filepath' => $backup,
                'size' => filesize($backup),
                'created' => filemtime($backup),
                'created_formatted' => date('Y-m-d H:i:s', filemtime($backup))
            ];
        }
        
        // Trier par date décroissante
        usort($backupList, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backupList;
    }
}

// Démarrer la sauvegarde automatique
$autoSave = new AutoSaveManager('whatsapp.xml');
$autoSave->autoSave();

// API endpoint pour la sauvegarde
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'create_backup':
            $backup = $autoSave->createBackup();
            echo json_encode(['success' => $backup !== false, 'backup' => $backup]);
            break;
            
        case 'get_backups':
            echo json_encode($autoSave->getBackupList());
            break;
            
        case 'restore_backup':
            $backupFile = $_GET['backup_file'] ?? '';
            $success = $autoSave->restoreBackup($backupFile);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            echo json_encode(['error' => 'Action non reconnue']);
    }
    exit;
}
?>
