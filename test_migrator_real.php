<?php
/**
 * Test avec le migrator RÉEL de phpBB pour identifier le problème array_merge()
 * Ce script simule exactement ce que fait phpBB lors de l'activation d'une extension
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
echo "║  TEST MIGRATOR RÉEL - Simulation activation                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require($phpbb_root_path . 'common.' . $phpEx);

global $phpbb_container, $db, $table_prefix;

try {
    // Obtenir le migrator réel de phpBB
    if (!$phpbb_container->has('migrator')) {
        die("❌ Service 'migrator' non trouvé\n");
    }
    
    $migrator = $phpbb_container->get('migrator');
    echo "✅ Migrator obtenu : " . get_class($migrator) . "\n\n";
    
    // Utiliser la réflexion pour accéder aux méthodes privées/protégées
    $reflection = new ReflectionClass($migrator);
    
    // Essayer de trouver la méthode qui charge les migrations
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ ANALYSE : Méthodes du migrator                              │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $methods = $reflection->getMethods();
    echo "📋 Méthodes disponibles :\n";
    foreach ($methods as $method) {
        $visibility = $method->isPublic() ? 'public' : ($method->isProtected() ? 'protected' : 'private');
        echo "   - {$visibility} " . $method->getName() . "()\n";
    }
    echo "\n";
    
    // Vérifier s'il y a une méthode qui collecte les dépendances
    $deps_method = null;
    foreach ($methods as $method) {
        if (strpos($method->getName(), 'depend') !== false || 
            strpos($method->getName(), 'collect') !== false ||
            strpos($method->getName(), 'load') !== false) {
            echo "🔍 Méthode potentiellement liée : " . $method->getName() . "()\n";
            if ($method->isPublic() || $method->isProtected()) {
                $deps_method = $method;
            }
        }
    }
    echo "\n";
    
    // AFFICHAGE SQL : Vérifier l'état AVANT activation
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ ÉTAT AVANT ACTIVATION : Migrations en base de données       │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $sql = "SELECT migration_name, migration_depends_on 
            FROM {$table_prefix}migrations 
            WHERE migration_name LIKE '%bastien59960%reactions%'
            ORDER BY migration_name";
    $result = $db->sql_query($sql);
    $before_migrations = $db->sql_fetchrowset($result);
    $db->sql_freeresult($result);
    
    echo "📊 Migrations trouvées AVANT activation : " . count($before_migrations) . "\n";
    if (!empty($before_migrations)) {
        foreach ($before_migrations as $mig) {
            echo "   - " . $mig['migration_name'] . "\n";
            $unser = @unserialize($mig['migration_depends_on']);
            if (is_array($unser)) {
                echo "     ✅ Dépendances OK (array)\n";
            } else {
                echo "     ❌ Dépendances PROBLÉMATIQUES (type: " . gettype($unser) . ")\n";
            }
        }
    }
    echo "\n";
    
    // AFFICHAGE SQL : Vérifier TOUTES les migrations pour trouver des problèmes
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ ANALYSE : TOUTES les migrations en base de données         │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $sql = "SELECT migration_name, migration_depends_on,
                   CASE 
                       WHEN migration_depends_on IS NULL THEN 'NULL'
                       WHEN migration_depends_on = '' THEN 'EMPTY'
                       WHEN migration_depends_on LIKE 'a:%' THEN 'ARRAY_SERIALIZED'
                       WHEN migration_depends_on LIKE 's:%' THEN 'STRING_SERIALIZED'
                       ELSE 'OTHER'
                   END as depends_type
            FROM {$table_prefix}migrations 
            ORDER BY migration_name";
    $result = $db->sql_query($sql);
    $all_migrations = $db->sql_fetchrowset($result);
    $db->sql_freeresult($result);
    
    echo "📊 Total migrations en base : " . count($all_migrations) . "\n\n";
    
    $problematic = array();
    $by_type = array();
    
    foreach ($all_migrations as $mig) {
        $type = $mig['depends_type'];
        if (!isset($by_type[$type])) {
            $by_type[$type] = 0;
        }
        $by_type[$type]++;
        
        // Tester la désérialisation
        if ($mig['migration_depends_on'] !== null && $mig['migration_depends_on'] !== '') {
            $unser = @unserialize($mig['migration_depends_on']);
            if ($unser === false && $mig['migration_depends_on'] !== serialize(false)) {
                $problematic[] = array(
                    'name' => $mig['migration_name'],
                    'issue' => 'unserialize_failed',
                    'value' => $mig['migration_depends_on']
                );
            } elseif (!is_array($unser) && $unser !== false) {
                $problematic[] = array(
                    'name' => $mig['migration_name'],
                    'issue' => 'not_array',
                    'type' => gettype($unser),
                    'value' => $unser
                );
            }
        }
    }
    
    echo "📊 Répartition par type :\n";
    foreach ($by_type as $type => $count) {
        echo "   - $type : $count\n";
    }
    echo "\n";
    
    if (!empty($problematic)) {
        echo "⚠️  Migrations problématiques trouvées : " . count($problematic) . "\n\n";
        foreach ($problematic as $prob) {
            echo "❌ Migration : " . $prob['name'] . "\n";
            if ($prob['issue'] === 'unserialize_failed') {
                echo "   Problème : Impossible de désérialiser\n";
                echo "   Valeur : " . substr($prob['value'], 0, 100) . "...\n";
            } elseif ($prob['issue'] === 'not_array') {
                echo "   Problème : Type = " . $prob['type'] . " (devrait être array)\n";
                echo "   Valeur : " . var_export($prob['value'], true) . "\n";
                
                // Tester array_merge pour confirmer l'erreur
                try {
                    $test = array();
                    $test = array_merge($test, $prob['value']);
                } catch (\TypeError $e) {
                    echo "   ❌ ERREUR array_merge() confirmée : " . $e->getMessage() . "\n";
                    echo "   💡 C'est EXACTEMENT l'erreur que vous voyez !\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "✅ Aucune migration problématique trouvée\n";
    }
    echo "\n";
    
    // TEST : Essayer de charger les migrations comme le fait phpBB
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ TEST : Chargement des migrations via le migrator réel        │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $ext_name = 'bastien59960/reactions';
    $ext_path = $phpbb_root_path . 'ext/' . $ext_name;
    $migrations_path = $ext_path . '/migrations';
    
    if (is_dir($migrations_path)) {
        $migration_files = glob($migrations_path . '/*.php');
        echo "📋 Fichiers de migration trouvés : " . count($migration_files) . "\n";
        
        // Charger chaque migration et tester depends_on()
        $all_deps_collected = array();
        
        foreach ($migration_files as $file) {
            $filename = basename($file, '.php');
            $class_name = '\\bastien59960\\reactions\\migrations\\' . $filename;
            
            require_once($file);
            
            if (class_exists($class_name)) {
                echo "🔍 Test de : $class_name\n";
                
                $mig_reflection = new ReflectionClass($class_name);
                if ($mig_reflection->hasMethod('depends_on')) {
                    $depends_method = $mig_reflection->getMethod('depends_on');
                    if ($depends_method->isStatic()) {
                        $deps = $depends_method->invoke(null);
                        
                        echo "   Type retourné : " . gettype($deps) . "\n";
                        
                        if (!is_array($deps)) {
                            echo "   ❌ PROBLÈME : depends_on() retourne " . gettype($deps) . " !\n";
                            echo "      Valeur : " . var_export($deps, true) . "\n";
                            
                            // Tester array_merge
                            try {
                                $all_deps_collected = array_merge($all_deps_collected, $deps);
                            } catch (\TypeError $e) {
                                echo "   ❌ ERREUR array_merge() : " . $e->getMessage() . "\n";
                                echo "      💡 C'est la cause exacte du problème !\n";
                            }
                        } else {
                            echo "   ✅ depends_on() retourne un array\n";
                            try {
                                $all_deps_collected = array_merge($all_deps_collected, $deps);
                                echo "   ✅ array_merge() réussi\n";
                            } catch (\TypeError $e) {
                                echo "   ❌ ERREUR array_merge() : " . $e->getMessage() . "\n";
                            }
                        }
                    }
                }
                echo "\n";
            }
        }
        
        echo "✅ Total dépendances collectées : " . count($all_deps_collected) . "\n";
    }
    
    // AFFICHAGE SQL FINAL : État après tous les tests
    echo "\n┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ ÉTAT FINAL : Migrations en base de données                  │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $sql = "SELECT COUNT(*) as count 
            FROM {$table_prefix}migrations 
            WHERE migration_name LIKE '%bastien59960%reactions%'";
    $result = $db->sql_query($sql);
    $final_count = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    
    echo "📊 Migrations restantes : " . $final_count['count'] . "\n";
    
    if ($final_count['count'] > 0) {
        echo "⚠️  Des migrations sont toujours en base de données !\n";
        echo "   Cela pourrait causer le problème lors de l'activation.\n";
    }
    
} catch (\Throwable $e) {
    echo "\n❌ ERREUR FATALE : " . $e->getMessage() . "\n";
    echo "   Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace :\n" . $e->getTraceAsString() . "\n";
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST TERMINÉ                                                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

