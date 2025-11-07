<?php
/**
 * Script de test pour diagnostiquer le problème array_merge() dans les migrations
 * Simule ce que fait phpBB lors du chargement des migrations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$current_dir = __DIR__;
$phpbb_root_path = '';
for ($i = 0; $i < 5; $i++) {
    if (file_exists($current_dir . '/common.php')) {
        $phpbb_root_path = $current_dir . '/';
        break;
    }
    $current_dir = dirname($current_dir);
}

if (empty($phpbb_root_path)) {
    die("❌ ERREUR: Impossible de trouver common.php\n");
}

define('IN_PHPBB', true);
$phpEx = 'php';

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST DES MIGRATIONS - DIAGNOSTIC array_merge()                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require($phpbb_root_path . 'common.' . $phpEx);

global $db, $table_prefix;

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ TEST 1 : Chargement des classes de migration                 │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

$migration_classes = [
    '\bastien59960\reactions\migrations\release_1_0_0',
    '\bastien59960\reactions\migrations\release_1_0_1',
];

foreach ($migration_classes as $class_name) {
    echo "🔍 Test de : $class_name\n";
    
    try {
        if (!class_exists($class_name)) {
            echo "   ❌ Classe non trouvée\n\n";
            continue;
        }
        
        echo "   ✅ Classe trouvée\n";
        
        // Créer une instance de test (sans appeler le constructeur réel)
        $reflection = new ReflectionClass($class_name);
        
        // Tester depends_on()
        if ($reflection->hasMethod('depends_on')) {
            $method = $reflection->getMethod('depends_on');
            if ($method->isStatic()) {
                $result = $method->invoke(null);
                $type = gettype($result);
                if ($type === 'array') {
                    echo "   ✅ depends_on() retourne un array (" . count($result) . " éléments)\n";
                    foreach ($result as $dep) {
                        echo "      - Dépendance : $dep\n";
                    }
                } else {
                    echo "   ❌ depends_on() retourne un $type au lieu d'un array !\n";
                    echo "      Valeur : " . var_export($result, true) . "\n";
                }
            }
        }
        
        // Tester update_schema()
        if ($reflection->hasMethod('update_schema')) {
            $method = $reflection->getMethod('update_schema');
            $result = $method->invoke($reflection->newInstanceWithoutConstructor());
            $type = gettype($result);
            if ($type === 'array') {
                echo "   ✅ update_schema() retourne un array\n";
            } else {
                echo "   ❌ update_schema() retourne un $type au lieu d'un array !\n";
                echo "      Valeur : " . var_export($result, true) . "\n";
            }
        }
        
        // Tester update_data()
        if ($reflection->hasMethod('update_data')) {
            $method = $reflection->getMethod('update_data');
            $result = $method->invoke($reflection->newInstanceWithoutConstructor());
            $type = gettype($result);
            if ($type === 'array') {
                echo "   ✅ update_data() retourne un array (" . count($result) . " éléments)\n";
            } else {
                echo "   ❌ update_data() retourne un $type au lieu d'un array !\n";
                echo "      Valeur : " . var_export($result, true) . "\n";
            }
        }
        
    } catch (\Throwable $e) {
        echo "   ❌ ERREUR : " . $e->getMessage() . "\n";
        echo "      Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n";
}

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ TEST 2 : Vérification des dépendances en base de données      │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

$sql = "SELECT migration_name, migration_depends_on 
        FROM {$table_prefix}migrations 
        WHERE migration_name LIKE '%bastien59960%reactions%'
        ORDER BY migration_name";
$result = $db->sql_query($sql);
$migrations = $db->sql_fetchrowset($result);
$db->sql_freeresult($result);

if (empty($migrations)) {
    echo "ℹ️  Aucune migration enregistrée en base de données\n\n";
} else {
    foreach ($migrations as $migration) {
        $name = $migration['migration_name'];
        $depends_on = $migration['migration_depends_on'];
        
        echo "📋 Migration : $name\n";
        echo "   Dépendances (sérialisées) : $depends_on\n";
        
        // Tenter de désérialiser
        $unserialized = @unserialize($depends_on);
        if ($unserialized === false && $depends_on !== serialize(false)) {
            echo "   ⚠️  Impossible de désérialiser les dépendances\n";
        } else {
            $type = gettype($unserialized);
            if ($type === 'array') {
                echo "   ✅ Dépendances désérialisées : array (" . count($unserialized) . " éléments)\n";
                foreach ($unserialized as $dep) {
                    echo "      - $dep\n";
                }
            } else {
                echo "   ❌ Dépendances désérialisées en $type au lieu d'un array !\n";
                echo "      Valeur : " . var_export($unserialized, true) . "\n";
            }
        }
        echo "\n";
    }
}

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ TEST 3 : Simulation de array_merge() avec les dépendances     │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

// Simuler ce que fait migrator.php ligne 788
try {
    $all_dependencies = array();
    
    foreach ($migration_classes as $class_name) {
        if (!class_exists($class_name)) {
            continue;
        }
        
        $reflection = new ReflectionClass($class_name);
        if ($reflection->hasMethod('depends_on')) {
            $method = $reflection->getMethod('depends_on');
            if ($method->isStatic()) {
                $deps = $method->invoke(null);
                
                echo "🔍 Test array_merge() avec dépendances de $class_name\n";
                echo "   Type de \$deps : " . gettype($deps) . "\n";
                
                if (is_array($deps)) {
                    echo "   ✅ \$deps est un array\n";
                    $all_dependencies = array_merge($all_dependencies, $deps);
                    echo "   ✅ array_merge() réussi\n";
                } else {
                    echo "   ❌ \$deps n'est PAS un array ! Type : " . gettype($deps) . "\n";
                    echo "      Valeur : " . var_export($deps, true) . "\n";
                    echo "   ⚠️  C'est probablement la cause de l'erreur !\n";
                    
                    // Tenter quand même array_merge pour voir l'erreur
                    try {
                        $all_dependencies = array_merge($all_dependencies, $deps);
                    } catch (\TypeError $e) {
                        echo "   ❌ ERREUR array_merge() : " . $e->getMessage() . "\n";
                    }
                }
                echo "\n";
            }
        }
    }
    
    echo "✅ Tous les array_merge() ont réussi\n";
    echo "   Total dépendances collectées : " . count($all_dependencies) . "\n";
    
} catch (\Throwable $e) {
    echo "❌ ERREUR lors de la simulation : " . $e->getMessage() . "\n";
    echo "   Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace :\n" . $e->getTraceAsString() . "\n";
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST TERMINÉ                                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

