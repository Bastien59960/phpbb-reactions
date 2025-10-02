<?php
/**
 * Script de test pour vérifier le chargement des traductions
 */

// Simuler l'environnement phpBB
define('IN_PHPBB', true);

echo "=== Test des traductions ACP ===\n";

// Test du fichier français
echo "\n--- Test français ---\n";
$lang = array();
include 'language/fr/acp/common.php';

if (isset($lang['ACP_REACTIONS_TITLE'])) {
    echo "✓ ACP_REACTIONS_TITLE: " . $lang['ACP_REACTIONS_TITLE'] . "\n";
} else {
    echo "❌ ACP_REACTIONS_TITLE manquant\n";
}

if (isset($lang['ACP_REACTIONS_SETTINGS'])) {
    echo "✓ ACP_REACTIONS_SETTINGS: " . $lang['ACP_REACTIONS_SETTINGS'] . "\n";
} else {
    echo "❌ ACP_REACTIONS_SETTINGS manquant\n";
}

// Test du fichier anglais
echo "\n--- Test anglais ---\n";
$lang = array();
include 'language/en/acp/common.php';

if (isset($lang['ACP_REACTIONS_TITLE'])) {
    echo "✓ ACP_REACTIONS_TITLE: " . $lang['ACP_REACTIONS_TITLE'] . "\n";
} else {
    echo "❌ ACP_REACTIONS_TITLE manquant\n";
}

if (isset($lang['ACP_REACTIONS_SETTINGS'])) {
    echo "✓ ACP_REACTIONS_SETTINGS: " . $lang['ACP_REACTIONS_SETTINGS'] . "\n";
} else {
    echo "❌ ACP_REACTIONS_SETTINGS manquant\n";
}

echo "\n=== Test terminé ===\n";
?>
