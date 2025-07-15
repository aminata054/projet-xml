<?php
class SearchManager {
    private $xml;
    
    public function __construct($xmlFile) {
        if (!file_exists($xmlFile)) {
            throw new Exception("Fichier XML non trouvé: $xmlFile");
        }
        
        $this->xml = simplexml_load_file($xmlFile);
        if ($this->xml === false) {
            throw new Exception("Erreur lors du chargement du fichier XML");
        }
    }
    
    public function searchContacts($query) {
        $results = [];
        $query = strtolower(trim($query));
        
        if (empty($query)) {
            return $results;
        }
        
        foreach ($this->xml->discussions->contacts->contact as $contact) {
            $nom = strtolower((string)$contact->nom);
            $prenom = strtolower((string)$contact->prenom);
            $numero = (string)$contact->numero_telephone;
            $fullName = $prenom . ' ' . $nom;
            
            if (strpos($nom, $query) !== false || 
                strpos($prenom, $query) !== false || 
                strpos(strtolower($fullName), $query) !== false ||
                strpos($numero, $query) !== false) {
                
                $results[] = [
                    'id' => (string)$contact['id'],
                    'nom' => (string)$contact->nom,
                    'prenom' => (string)$contact->prenom,
                    'numero' => (string)$contact->numero_telephone,
                    'photo' => (string)$contact->photo_profile ?: 'images/default-avatar.png',
                    'status' => (string)$contact->status,
                    'type' => 'contact',
                    'fullName' => $fullName,
                    'relevance' => $this->calculateRelevance($query, $fullName, $numero)
                ];
            }
        }
        
        // Trier par pertinence
        usort($results, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });
        
        return $results;
    }
    
    public function searchMessages($query, $contactId = null, $groupeId = null) {
        $results = [];
        $query = strtolower(trim($query));
        
        if (empty($query)) {
            return $results;
        }
        
        // Recherche dans les messages des contacts
        foreach ($this->xml->discussions->contacts->contact as $contact) {
            if ($contactId && (string)$contact['id'] !== $contactId) continue;
            
            foreach ($contact->messages->message as $message) {
                $contenu = strtolower((string)$message->contenu);
                if (strpos($contenu, $query) !== false) {
                    $results[] = [
                        'id' => (string)$message['id'],
                        'contenu' => (string)$message->contenu,
                        'expediteur' => (string)$message['expediteur'],
                        'heure' => (string)$message->message_info['heure'],
                        'contact_id' => (string)$contact['id'],
                        'contact_nom' => (string)$contact->prenom . ' ' . (string)$contact->nom,
                        'type' => 'message_contact',
                        'relevance' => $this->calculateMessageRelevance($query, $contenu),
                        'context' => $this->getMessageContext($message, $contact)
                    ];
                }
            }
        }
        
        // Recherche dans les messages des groupes
        foreach ($this->xml->discussions->groupes->groupe as $groupe) {
            if ($groupeId && (string)$groupe['id'] !== $groupeId) continue;
            
            foreach ($groupe->messages->message as $message) {
                $contenu = strtolower((string)$message->contenu);
                if (strpos($contenu, $query) !== false) {
                    $results[] = [
                        'id' => (string)$message['id'],
                        'contenu' => (string)$message->contenu,
                        'expediteur' => (string)$message['expediteur'],
                        'heure' => (string)$message->message_info['heure'],
                        'groupe_id' => (string)$groupe['id'],
                        'groupe_nom' => (string)$groupe->nom_groupe,
                        'type' => 'message_groupe',
                        'relevance' => $this->calculateMessageRelevance($query, $contenu),
                        'context' => $this->getMessageContext($message, $groupe)
                    ];
                }
            }
        }
        
        // Trier par date décroissante puis par pertinence
        usort($results, function($a, $b) {
            $timeA = strtotime($a['heure']);
            $timeB = strtotime($b['heure']);
            
            if ($timeA === $timeB) {
                return $b['relevance'] - $a['relevance'];
            }
            
            return $timeB - $timeA;
        });
        
        return array_slice($results, 0, 50); // Limiter à 50 résultats
    }
    
    public function searchGroups($query) {
        $results = [];
        $query = strtolower(trim($query));
        
        if (empty($query)) {
            return $results;
        }
        
        foreach ($this->xml->discussions->groupes->groupe as $groupe) {
            $nomGroupe = strtolower((string)$groupe->nom_groupe);
            
            if (strpos($nomGroupe, $query) !== false) {
                $membersCount = count($groupe->membres->membre);
                $adminId = (string)$groupe->admin['ref'];
                
                // Trouver le nom de l'admin
                $adminName = 'Inconnu';
                $adminContact = $this->xml->xpath("//contact[@id='$adminId']");
                if (!empty($adminContact)) {
                    $adminName = (string)$adminContact[0]->prenom . ' ' . (string)$adminContact[0]->nom;
                }
                
                $results[] = [
                    'id' => (string)$groupe['id'],
                    'nom_groupe' => (string)$groupe->nom_groupe,
                    'photo_groupe' => (string)$groupe->photo_groupe ?: 'images/default-group.png',
                    'admin' => $adminName,
                    'admin_id' => $adminId,
                    'members_count' => $membersCount,
                    'type' => 'groupe',
                    'relevance' => $this->calculateRelevance($query, $nomGroupe, '')
                ];
            }
        }
        
        // Trier par pertinence
        usort($results, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });
        
        return $results;
    }
    
    public function searchAll($query) {
        $results = [
            'contacts' => $this->searchContacts($query),
            'messages' => $this->searchMessages($query),
            'groups' => $this->searchGroups($query)
        ];
        
        // Ajouter des statistiques
        $results['stats'] = [
            'total_contacts' => count($results['contacts']),
            'total_messages' => count($results['messages']),
            'total_groups' => count($results['groups']),
            'query' => $query,
            'search_time' => microtime(true)
        ];
        
        return $results;
    }
    
    private function calculateRelevance($query, $text, $secondary = '') {
        $text = strtolower($text);
        $query = strtolower($query);
        $relevance = 0;
        
        // Correspondance exacte
        if ($text === $query) {
            $relevance += 100;
        }
        
        // Commence par la requête
        if (strpos($text, $query) === 0) {
            $relevance += 50;
        }
        
        // Contient la requête
        if (strpos($text, $query) !== false) {
            $relevance += 25;
        }
        
        // Recherche dans le texte secondaire
        if (!empty($secondary)) {
            $secondary = strtolower($secondary);
            if (strpos($secondary, $query) !== false) {
                $relevance += 10;
            }
        }
        
        // Bonus pour les mots courts (plus précis)
        if (strlen($query) <= 3) {
            $relevance += 5;
        }
        
        return $relevance;
    }
    
    private function calculateMessageRelevance($query, $content) {
        $relevance = $this->calculateRelevance($query, $content);
        
        // Bonus pour les messages récents (approximatif)
        $relevance += 5;
        
        return $relevance;
    }
    
    private function getMessageContext($message, $parent) {
        $context = [];
        
        // Type de conversation
        $context['conversation_type'] = isset($parent->nom_groupe) ? 'groupe' : 'contact';
        
        // Nom de la conversation
        if ($context['conversation_type'] === 'groupe') {
            $context['conversation_name'] = (string)$parent->nom_groupe;
        } else {
            $context['conversation_name'] = (string)$parent->prenom . ' ' . (string)$parent->nom;
        }
        
        // Informations sur l'expéditeur
        $expediteurId = (string)$message['expediteur'];
        if ($expediteurId === '1') {
            $context['sender_name'] = 'Moi';
        } else {
            $expediteurContact = $this->xml->xpath("//contact[@id='$expediteurId']");
            if (!empty($expediteurContact)) {
                $context['sender_name'] = (string)$expediteurContact[0]->prenom . ' ' . (string)$expediteurContact[0]->nom;
            } else {
                $context['sender_name'] = 'Inconnu';
            }
        }
        
        return $context;
    }
    
    public function getSearchSuggestions($query) {
        $suggestions = [];
        $query = strtolower(trim($query));
        
        if (strlen($query) < 2) {
            return $suggestions;
        }
        
        // Suggestions basées sur les noms de contacts
        foreach ($this->xml->discussions->contacts->contact as $contact) {
            $fullName = (string)$contact->prenom . ' ' . (string)$contact->nom;
            if (strpos(strtolower($fullName), $query) === 0) {
                $suggestions[] = [
                    'text' => $fullName,
                    'type' => 'contact',
                    'icon' => '👤'
                ];
            }
        }
        
        // Suggestions basées sur les noms de groupes
        foreach ($this->xml->discussions->groupes->groupe as $groupe) {
            $nomGroupe = (string)$groupe->nom_groupe;
            if (strpos(strtolower($nomGroupe), $query) === 0) {
                $suggestions[] = [
                    'text' => $nomGroupe,
                    'type' => 'groupe',
                    'icon' => '👥'
                ];
            }
        }
        
        return array_slice($suggestions, 0, 5); // Limiter à 5 suggestions
    }
}

// API endpoint pour la recherche avec gestion d'erreurs améliorée
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $search = new SearchManager('whatsapp.xml');
        $query = $_GET['q'] ?? '';
        
        // Validation de la requête
        if (strlen($query) > 100) {
            throw new Exception('Requête trop longue (max 100 caractères)');
        }
        
        switch ($_GET['action']) {
            case 'search_contacts':
                $results = $search->searchContacts($query);
                echo json_encode([
                    'success' => true,
                    'data' => $results,
                    'count' => count($results)
                ]);
                break;
                
            case 'search_messages':
                $contactId = $_GET['contact_id'] ?? null;
                $groupeId = $_GET['groupe_id'] ?? null;
                $results = $search->searchMessages($query, $contactId, $groupeId);
                echo json_encode([
                    'success' => true,
                    'data' => $results,
                    'count' => count($results)
                ]);
                break;
                
            case 'search_groups':
                $results = $search->searchGroups($query);
                echo json_encode([
                    'success' => true,
                    'data' => $results,
                    'count' => count($results)
                ]);
                break;
                
            case 'search_all':
                $results = $search->searchAll($query);
                echo json_encode([
                    'success' => true,
                    'data' => $results
                ]);
                break;
                
            case 'suggestions':
                $suggestions = $search->getSearchSuggestions($query);
                echo json_encode([
                    'success' => true,
                    'data' => $suggestions
                ]);
                break;
                
            default:
                throw new Exception('Action non reconnue');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]);
    }
    
    exit;
}

// Fonction utilitaire pour nettoyer les requêtes
function sanitizeSearchQuery($query) {
    // Supprimer les caractères dangereux
    $query = strip_tags($query);
    $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
    
    // Limiter la longueur
    $query = substr($query, 0, 100);
    
    // Supprimer les espaces multiples
    $query = preg_replace('/\s+/', ' ', $query);
    
    return trim($query);
}
?>
