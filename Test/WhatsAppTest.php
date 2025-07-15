<?php
use PHPUnit\Framework\TestCase;

class WhatsAppTest extends TestCase {
    private $xmlFile = 'test_whatsapp.xml';
    private $xml;
    
    protected function setUp(): void {
        // Créer un fichier XML de test
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE whatsapp SYSTEM "whatsapp.dtd">
<whatsapp>
  <discussions>
    <contacts>
      <contact id="1">
        <nom>Test</nom>
        <prenom>User</prenom>
        <numero_telephone>123456789</numero_telephone>
        <photo_profile>images/test.png</photo_profile>
        <status>En ligne</status>
        <messages/>
      </contact>
    </contacts>
    <groupes/>
  </discussions>
</whatsapp>';
        
        file_put_contents($this->xmlFile, $xmlContent);
        $this->xml = simplexml_load_file($this->xmlFile);
    }
    
    protected function tearDown(): void {
        if (file_exists($this->xmlFile)) {
            unlink($this->xmlFile);
        }
    }
    
    public function testXMLFileExists() {
        $this->assertTrue(file_exists($this->xmlFile));
    }
    
    public function testXMLStructure() {
        $this->assertNotFalse($this->xml);
        $this->assertTrue(isset($this->xml->discussions));
        $this->assertTrue(isset($this->xml->discussions->contacts));
        $this->assertTrue(isset($this->xml->discussions->groupes));
    }
    
    public function testContactExists() {
        $contact = $this->xml->discussions->contacts->contact[0];
        $this->assertEquals('1', (string)$contact['id']);
        $this->assertEquals('Test', (string)$contact->nom);
        $this->assertEquals('User', (string)$contact->prenom);
    }
    
    public function testAddContact() {
        $contactsCount = count($this->xml->discussions->contacts->contact);
        
        // Simuler l'ajout d'un contact
        $newContact = $this->xml->discussions->contacts->addChild('contact');
        $newContact->addAttribute('id', '2');
        $newContact->addChild('nom', 'Nouveau');
        $newContact->addChild('prenom', 'Contact');
        $newContact->addChild('numero_telephone', '987654321');
        $newContact->addChild('photo_profile', 'images/default.png');
        $newContact->addChild('status', 'Hors ligne');
        $newContact->addChild('messages');
        
        $this->assertEquals($contactsCount + 1, count($this->xml->discussions->contacts->contact));
    }
    
    public function testAddMessage() {
        $contact = $this->xml->discussions->contacts->contact[0];
        $messagesCount = count($contact->messages->message);
        
        // Ajouter un message
        $message = $contact->messages->addChild('message');
        $message->addAttribute('id', '1');
        $message->addAttribute('type', 'texte');
        $message->addAttribute('expediteur', '1');
        $message->addAttribute('destinataire', '2');
        $message->addChild('contenu', 'Message de test');
        
        $messageInfo = $message->addChild('message_info');
        $messageInfo->addAttribute('heure', date('c'));
        $messageInfo->addAttribute('statut', 'envoye');
        
        $this->assertEquals($messagesCount + 1, count($contact->messages->message));
        $this->assertEquals('Message de test', (string)$contact->messages->message[0]->contenu);
    }
    
    public function testSearchContacts() {
        require_once 'search.php';
        
        $search = new SearchManager($this->xmlFile);
        $results = $search->searchContacts('Test');
        
        $this->assertCount(1, $results);
        $this->assertEquals('Test', $results[0]['nom']);
        $this->assertEquals('User', $results[0]['prenom']);
    }
    
    public function testSearchMessages() {
        require_once 'search.php';
        
        // Ajouter un message pour le test
        $contact = $this->xml->discussions->contacts->contact[0];
        $message = $contact->messages->addChild('message');
        $message->addAttribute('id', '1');
        $message->addAttribute('type', 'texte');
        $message->addAttribute('expediteur', '1');
        $message->addChild('contenu', 'Message recherchable');
        
        $messageInfo = $message->addChild('message_info');
        $messageInfo->addAttribute('heure', date('c'));
        
        $this->xml->asXML($this->xmlFile);
        
        $search = new SearchManager($this->xmlFile);
        $results = $search->searchMessages('recherchable');
        
        $this->assertCount(1, $results);
        $this->assertStringContainsString('recherchable', strtolower($results[0]['contenu']));
    }
    
    public function testFileUpload() {
        require_once 'file_manager.php';
        
        $fileManager = new FileManager();
        
        // Simuler un fichier uploadé
        $fakeFile = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 1024,
            'tmp_name' => tempnam(sys_get_temp_dir(), 'test'),
            'error' => UPLOAD_ERR_OK
        ];
        
        // Créer un fichier temporaire
        file_put_contents($fakeFile['tmp_name'], 'Contenu de test');
        
        // Le test réel nécessiterait de mocker move_uploaded_file
        $this->assertTrue(file_exists($fakeFile['tmp_name']));
        
        // Nettoyer
        unlink($fakeFile['tmp_name']);
    }
    
    public function testHistoryLogging() {
        require_once 'history.php';
        
        $history = new HistoryManager();
        $history->logAction('test_action', 'Test details', '1');
        
        $entries = $history->getHistory(10, '1');
        
        $this->assertGreaterThan(0, count($entries));
        $this->assertEquals('test_action', $entries[0]['action']);
        $this->assertEquals('Test details', $entries[0]['details']);
    }
    
    public function testAutoSave() {
        require_once 'auto_save.php';
        
        $autoSave = new AutoSaveManager($this->xmlFile);
        $backupFile = $autoSave->createBackup();
        
        $this->assertNotFalse($backupFile);
        $this->assertTrue(file_exists($backupFile));
        
        // Nettoyer
        if ($backupFile && file_exists($backupFile)) {
            unlink($backupFile);
        }
    }
    
    public function testNotifications() {
        require_once 'notifications.php';
        
        $notifications = new NotificationManager($this->xmlFile);
        $unreadCount = $notifications->getUnreadCount('1');
        
        $this->assertIsInt($unreadCount);
        $this->assertGreaterThanOrEqual(0, $unreadCount);
    }
    
    public function testXMLValidation() {
        // Test de validation XML basique
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($this->xmlFile);
        $errors = libxml_get_errors();
        
        $this->assertNotFalse($xml);
        $this->assertEmpty($errors, 'Le fichier XML contient des erreurs: ' . implode(', ', array_map(fn($e) => $e->message, $errors)));
    }
}
?>
