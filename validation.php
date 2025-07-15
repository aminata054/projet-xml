<?php
class XMLValidator {
    
    public static function validateWithXSD($xmlFile, $xsdFile) {
        if (!file_exists($xmlFile)) {
            return ['valid' => false, 'errors' => ["Fichier XML non trouvé: $xmlFile"]];
        }
        
        if (!file_exists($xsdFile)) {
            return ['valid' => false, 'errors' => ["Fichier XSD non trouvé: $xsdFile"]];
        }
        
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $xml = new DOMDocument();
        $xml->load($xmlFile);
        
        if (!$xml->schemaValidate($xsdFile)) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $level = '';
                switch ($error->level) {
                    case LIBXML_ERR_WARNING:
                        $level = 'Avertissement';
                        break;
                    case LIBXML_ERR_ERROR:
                        $level = 'Erreur';
                        break;
                    case LIBXML_ERR_FATAL:
                        $level = 'Erreur fatale';
                        break;
                }
                $errorMessages[] = "$level ligne {$error->line}: " . trim($error->message);
            }
            return ['valid' => false, 'errors' => $errorMessages];
        }
        
        return ['valid' => true, 'errors' => []];
    }
    
    public static function validateWithDTD($xmlFile, $dtdFile = null) {
        if (!file_exists($xmlFile)) {
            return ['valid' => false, 'errors' => ["Fichier XML non trouvé: $xmlFile"]];
        }
        
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $xml = new DOMDocument();
        $xml->load($xmlFile);
        
        // Si un DTD externe est fourni
        if ($dtdFile && file_exists($dtdFile)) {
            $dtdContent = file_get_contents($dtdFile);
            $xml->validateOnParse = true;
        }
        
        if (!$xml->validate()) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = "Ligne {$error->line}: " . trim($error->message);
            }
            return ['valid' => false, 'errors' => $errorMessages];
        }
        
        return ['valid' => true, 'errors' => []];
    }
    
    public static function sanitizeInput($input, $type = 'string') {
        if (empty($input)) {
            return '';
        }
        
        switch ($type) {
            case 'int':
                $result = filter_var($input, FILTER_VALIDATE_INT);
                return $result !== false ? $result : 0;
                
            case 'email':
                $result = filter_var($input, FILTER_VALIDATE_EMAIL);
                return $result !== false ? $result : '';
                
            case 'phone':
                // Nettoyer le numéro de téléphone
                $cleaned = preg_replace('/[^0-9+\-\s()]/', '', $input);
                return trim($cleaned);
                
            case 'url':
                $result = filter_var($input, FILTER_VALIDATE_URL);
                return $result !== false ? $result : '';
                
            case 'filename':
                // Nettoyer le nom de fichier
                $cleaned = preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
                return substr($cleaned, 0, 255);
                
            default:
                // Nettoyage par défaut pour les chaînes
                $cleaned = strip_tags($input);
                $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
                return trim($cleaned);
        }
    }
    
    public static function validateContactData($data) {
        $errors = [];
        
        // Validation du prénom
        if (empty($data['prenom'])) {
            $errors['prenom'] = 'Le prénom est obligatoire';
        } elseif (strlen($data['prenom']) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
        } elseif (!preg_match('/^[A-Za-zÀ-ÿ\s\'-]+$/', $data['prenom'])) {
            $errors['prenom'] = 'Le prénom contient des caractères non autorisés';
        }
        
        // Validation du nom
        if (empty($data['nom'])) {
            $errors['nom'] = 'Le nom est obligatoire';
        } elseif (strlen($data['nom']) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
        } elseif (!preg_match('/^[A-Za-zÀ-ÿ\s\'-]+$/', $data['nom'])) {
            $errors['nom'] = 'Le nom contient des caractères non autorisés';
        }
        
        // Validation du numéro de téléphone
        if (!empty($data['numero_telephone'])) {
            $phone = preg_replace('/[^0-9+]/', '', $data['numero_telephone']);
            if (strlen($phone) < 8 || strlen($phone) > 15) {
                $errors['numero_telephone'] = 'Le numéro de téléphone doit contenir entre 8 et 15 chiffres';
            }
        }
        
        // Validation du statut
        $validStatuses = ['En ligne', 'Hors ligne', 'Occupé', 'Absent'];
        if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors['status'] = 'Statut non valide';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public static function validateGroupData($data) {
        $errors = [];
        
        // Validation du nom du groupe
        if (empty($data['nom_groupe'])) {
            $errors['nom_groupe'] = 'Le nom du groupe est obligatoire';
        } elseif (strlen($data['nom_groupe']) < 3) {
            $errors['nom_groupe'] = 'Le nom du groupe doit contenir au moins 3 caractères';
        } elseif (strlen($data['nom_groupe']) > 50) {
            $errors['nom_groupe'] = 'Le nom du groupe ne peut pas dépasser 50 caractères';
        }
        
        // Validation de l'admin
        if (empty($data['admin_id'])) {
            $errors['admin_id'] = 'Un administrateur doit être sélectionné';
        } elseif (!is_numeric($data['admin_id'])) {
            $errors['admin_id'] = 'ID administrateur non valide';
        }
        
        // Validation des membres
        if (empty($data['membres']) || !is_array($data['membres'])) {
            $errors['membres'] = 'Au moins un membre doit être sélectionné';
        } elseif (count($data['membres']) < 2) {
            $errors['membres'] = 'Un groupe doit contenir au moins 2 membres';
        } elseif (count($data['membres']) > 100) {
            $errors['membres'] = 'Un groupe ne peut pas contenir plus de 100 membres';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public static function validateMessageData($data) {
        $errors = [];
        
        // Validation du contenu
        if (empty($data['contenu_message'])) {
            $errors['contenu_message'] = 'Le message ne peut pas être vide';
        } elseif (strlen($data['contenu_message']) > 1000) {
            $errors['contenu_message'] = 'Le message ne peut pas dépasser 1000 caractères';
        }
        
        // Validation de l'expéditeur
        if (empty($data['expediteur'])) {
            $errors['expediteur'] = 'Expéditeur manquant';
        } elseif (!is_numeric($data['expediteur'])) {
            $errors['expediteur'] = 'ID expéditeur non valide';
        }
        
        // Validation du destinataire ou groupe
        if (empty($data['destinataire']) && empty($data['groupe'])) {
            $errors['destination'] = 'Un destinataire ou un groupe doit être spécifié';
        }
        
        if (!empty($data['destinataire']) && !is_numeric($data['destinataire'])) {
            $errors['destinataire'] = 'ID destinataire non valide';
        }
        
        if (!empty($data['groupe']) && !is_numeric($data['groupe'])) {
            $errors['groupe'] = 'ID groupe non valide';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public static function validateFileUpload($file) {
        $errors = [];
        
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Aucun fichier n\'a été uploadé';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Vérifier la taille
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            $errors[] = 'Le fichier est trop volumineux (max 2MB)';
        }
        
        // Vérifier le type MIME
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Type de fichier non autorisé. Seules les images sont acceptées.';
        }
        
        // Vérifier l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Extension de fichier non autorisée';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
    
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function checkXMLIntegrity($xmlFile) {
        $issues = [];
        
        if (!file_exists($xmlFile)) {
            return ['valid' => false, 'issues' => ['Fichier XML non trouvé']];
        }
        
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            return ['valid' => false, 'issues' => ['Fichier XML corrompu']];
        }
        
        // Vérifier la structure de base
        if (!isset($xml->discussions)) {
            $issues[] = 'Élément <discussions> manquant';
        }
        
        if (!isset($xml->discussions->contacts)) {
            $issues[] = 'Élément <contacts> manquant';
        }
        
        if (!isset($xml->discussions->groupes)) {
            $issues[] = 'Élément <groupes> manquant';
        }
        
        // Vérifier les IDs uniques
        $contactIds = [];
        $groupIds = [];
        $messageIds = [];
        
        foreach ($xml->discussions->contacts->contact as $contact) {
            $id = (string)$contact['id'];
            if (in_array($id, $contactIds)) {
                $issues[] = "ID de contact dupliqué: $id";
            }
            $contactIds[] = $id;
            
            // Vérifier les messages du contact
            foreach ($contact->messages->message as $message) {
                $msgId = (string)$message['id'];
                if (in_array($msgId, $messageIds)) {
                    $issues[] = "ID de message dupliqué: $msgId";
                }
                $messageIds[] = $msgId;
            }
        }
        
        foreach ($xml->discussions->groupes->groupe as $groupe) {
            $id = (string)$groupe['id'];
            if (in_array($id, $groupIds)) {
                $issues[] = "ID de groupe dupliqué: $id";
            }
            $groupIds[] = $id;
            
            // Vérifier les messages du groupe
            foreach ($groupe->messages->message as $message) {
                $msgId = (string)$message['id'];
                if (in_array($msgId, $messageIds)) {
                    $issues[] = "ID de message dupliqué: $msgId";
                }
                $messageIds[] = $msgId;
            }
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'stats' => [
                'contacts' => count($contactIds),
                'groups' => count($groupIds),
                'messages' => count($messageIds)
            ]
        ];
    }
}

// API endpoint pour la validation avec gestion d'erreurs améliorée
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_GET['action']) {
            case 'validate':
                $xmlFile = 'whatsapp.xml';
                $xsdFile = 'whatsapp.xsd';
                
                // Essayer d'abord avec XSD, puis avec DTD si XSD n'existe pas
                if (file_exists($xsdFile)) {
                    $result = XMLValidator::validateWithXSD($xmlFile, $xsdFile);
                } else {
                    $result = XMLValidator::validateWithDTD($xmlFile);
                }
                
                echo json_encode([
                    'success' => true,
                    'valid' => $result['valid'],
                    'errors' => $result['errors']
                ]);
                break;
                
            case 'check_integrity':
                $result = XMLValidator::checkXMLIntegrity('whatsapp.xml');
                echo json_encode([
                    'success' => true,
                    'valid' => $result['valid'],
                    'issues' => $result['issues'],
                    'stats' => $result['stats'] ?? []
                ]);
                break;
                
            case 'generate_csrf':
                $token = XMLValidator::generateCSRFToken();
                echo json_encode([
                    'success' => true,
                    'token' => $token
                ]);
                break;
                
            default:
                throw new Exception('Action non reconnue');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

// Fonction utilitaire pour valider les données POST
function validatePostData($data, $type) {
    switch ($type) {
        case 'contact':
            return XMLValidator::validateContactData($data);
        case 'group':
            return XMLValidator::validateGroupData($data);
        case 'message':
            return XMLValidator::validateMessageData($data);
        default:
            return ['valid' => false, 'errors' => ['Type de validation non reconnu']];
    }
}
?>
