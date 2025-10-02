<?php
/**
 * Script de test pour le contrôleur AJAX
 */

// Simuler l'environnement phpBB
define('IN_PHPBB', true);
define('ANONYMOUS', 1);

// Inclure les fichiers nécessaires
require_once 'controller/ajax.php';

use bastien59960\reactions\controller\ajax;

echo "=== Test du contrôleur AJAX ===\n";

try {
    // Test de la classe
    $reflection = new ReflectionClass('bastien59960\reactions\controller\ajax');
    echo "✓ Classe ajax trouvée\n";
    
    // Test du constructeur
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    echo "✓ Constructeur trouvé avec " . count($params) . " paramètres\n";
    
    // Lister les paramètres
    foreach ($params as $param) {
        echo "  - " . $param->getName() . " (" . $param->getType() . ")\n";
    }
    
    // Test des méthodes
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    echo "\n✓ Méthodes publiques trouvées:\n";
    foreach ($methods as $method) {
        echo "  - " . $method->getName() . "\n";
    }
    
    echo "\n=== Test terminé avec succès ===\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
