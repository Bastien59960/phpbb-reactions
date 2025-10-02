<?php
/**
 * Script de debug pour identifier l'erreur 500
 */

// Simuler une requête AJAX
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Données de test
$test_data = [
    'post_id' => 1,
    'emoji' => '👍',
    'action' => 'add',
    'sid' => 'test_session_id'
];

// Simuler le contenu de la requête
$GLOBALS['php_input'] = json_encode($test_data);

// Rediriger php://input
function file_get_contents($filename) {
    if ($filename === 'php://input') {
        return $GLOBALS['php_input'];
    }
    return \file_get_contents($filename);
}

echo "=== Debug AJAX Controller ===\n";

// Test de la syntaxe PHP
echo "Test de la syntaxe...\n";
$output = [];
$return = 0;
exec('php -l controller/ajax.php 2>&1', $output, $return);

if ($return !== 0) {
    echo "❌ Erreur de syntaxe dans ajax.php:\n";
    echo implode("\n", $output) . "\n";
} else {
    echo "✓ Syntaxe OK\n";
}

// Test des constantes
echo "\nTest des constantes...\n";
if (!defined('ANONYMOUS')) {
    define('ANONYMOUS', 1);
    echo "✓ Constante ANONYMOUS définie\n";
} else {
    echo "✓ Constante ANONYMOUS déjà définie\n";
}

// Test de la classe
echo "\nTest de la classe...\n";
try {
    if (class_exists('bastien59960\reactions\controller\ajax')) {
        echo "✓ Classe ajax trouvée\n";
    } else {
        echo "❌ Classe ajax non trouvée\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors du test de la classe: " . $e->getMessage() . "\n";
}

echo "\n=== Debug terminé ===\n";
?>
