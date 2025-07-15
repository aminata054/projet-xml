<?php
class ErrorHandler {
    
    public static function handleXMLError($xmlFile) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlFile);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorLog = "Erreurs XML dans $xmlFile:\n";
            
            foreach ($errors as $error) {
                $errorLog .= "- Ligne {$error->line}: {$error->message}\n";
            }
            
            error_log($errorLog);
            
            return [
                'success' => false,
                'message' => 'Erreur de format XML. Veuillez vérifier la structure du fichier.',
                'details' => $errors
            ];
        }
        
        return ['success' => true, 'xml' => $xml];
    }
    
    public static function logError($message, $context = []) {
        $logEntry = date('Y-m-d H:i:s') . " - " . $message;
        if (!empty($context)) {
            $logEntry .= " - Context: " . json_encode($context);
        }
        error_log($logEntry . "\n", 3, 'logs/app.log');
    }
}
?>
