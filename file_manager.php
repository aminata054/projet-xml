<?php
class FileManager {
    private $uploadDir = 'uploads/';
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
    private $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    public function __construct() {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function uploadFile($file, $messageId) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Aucun fichier uploadé'];
        }
        
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'Fichier trop volumineux (max 5MB)'];
        }
        
        if (!in_array($file['type'], $this->allowedTypes)) {
            return ['success' => false, 'error' => 'Type de fichier non autorisé'];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'msg_' . $messageId . '_' . time() . '.' . $extension;
        $filepath = $this->uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $file['size'],
                'type' => $file['type']
            ];
        }
        
        return ['success' => false, 'error' => 'Erreur lors de l\'upload'];
    }
    
    public function deleteFile($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    public function getFileInfo($filepath) {
        if (!file_exists($filepath)) {
            return null;
        }
        
        return [
            'name' => basename($filepath),
            'size' => filesize($filepath),
            'type' => mime_content_type($filepath),
            'modified' => filemtime($filepath)
        ];
    }
}

// Traitement des uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['attachment'])) {
    $fileManager = new FileManager();
    $messageId = $_POST['message_id'] ?? uniqid();
    
    $result = $fileManager->uploadFile($_FILES['attachment'], $messageId);
    
    if ($result['success']) {
        // Ajouter le fichier au message XML
        $xml = simplexml_load_file('whatsapp.xml');
        
        // Trouver le message et ajouter l'attachement
        $messages = $xml->xpath("//message[@id='$messageId']");
        if (!empty($messages)) {
            $message = $messages[0];
            $attachment = $message->addChild('attachment');
            $attachment->addChild('filename', $result['filename']);
            $attachment->addChild('filepath', $result['filepath']);
            $attachment->addChild('size', $result['size']);
            $attachment->addChild('type', $result['type']);
            
            $xml->asXML('whatsapp.xml');
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>
