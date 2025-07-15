#!/bin/bash

# Script pour exécuter les tests

echo "=== Exécution des tests WhatsApp XML ==="

# Vérifier si PHPUnit est installé
if ! command -v phpunit &> /dev/null; then
    echo "PHPUnit n'est pas installé. Installation via Composer..."
    composer install
fi

# Créer le dossier de tests s'il n'existe pas
mkdir -p tests

# Exécuter les tests
echo "Exécution des tests unitaires..."
phpunit tests/WhatsAppTest.php --verbose

# Générer le rapport de couverture si demandé
if [ "$1" = "--coverage" ]; then
    echo "Génération du rapport de couverture..."
    phpunit tests/WhatsAppTest.php --coverage-html coverage/
    echo "Rapport de couverture généré dans le dossier coverage/"
fi

echo "=== Tests terminés ==="
