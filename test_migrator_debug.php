<?php
/**
 * Script de debug approfondi pour identifier le problème array_merge()
 * Simule exactement ce que fait phpBB lors de l'activation d'une extension
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
echo "║  DEBUG MIGRATOR - Simulation activation extension             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require($phpbb_root_path . 'common.' . $phpEx);

global $phpbb_container, $db, $table_prefix;

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ TEST : Simulation du chargement des migrations par phpBB    │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

try {
    // Obtenir le migrator de phpBB
    if (!$phpbb_container->has('migrator')) {
        echo "❌ Service 'migrator' non trouvé\n";
        exit(1);
    }
    
    $migrator = $phpbb_container->get('migrator');
    echo "✅ Migrator obtenu : " . get_class($migrator) . "\n\n";
    
    // Obtenir le gestionnaire d'extensions
    if (!$phpbb_container->has('ext.manager')) {
        echo "❌ Service 'ext.manager' non trouvé\n";
        exit(1);
    }
    
    $ext_manager = $phpbb_container->get('ext.manager');
    echo "✅ Extension manager obtenu\n\n";
    
    // Vérifier l'état de l'extension
    $ext_name = 'bastien59960/reactions';
    echo "🔍 Vérification de l'extension : $ext_name\n";
    
    $ext_path = $phpbb_root_path . 'ext/' . $ext_name;
    if (!is_dir($ext_path)) {
        echo "❌ Répertoire extension introuvable : $ext_path\n";
        exit(1);
    }
    echo "✅ Répertoire extension trouvé\n";
    
    // Obtenir la version depuis le composer.json ou ext.php directement
    $composer_file = $ext_path . '/composer.json';
    if (file_exists($composer_file)) {
        $composer = json_decode(file_get_contents($composer_file), true);
        if (isset($composer['version'])) {
            echo "📋 Version de l'extension (composer.json) : " . $composer['version'] . "\n";
        }
    }
    
    // Charger la classe ext pour vérifier qu'elle existe
    $ext_class_file = $ext_path . '/ext.php';
    if (!file_exists($ext_class_file)) {
        echo "❌ Fichier ext.php introuvable\n";
        exit(1);
    }
    
    require_once($ext_class_file);
    $ext_class = '\\bastien59960\\reactions\\ext';
    
    if (!class_exists($ext_class)) {
        echo "❌ Classe extension non trouvée : $ext_class\n";
        exit(1);
    }
    echo "✅ Classe extension trouvée\n\n";
    
    // Trouver toutes les migrations
    $migrations_path = $ext_path . '/migrations';
    if (!is_dir($migrations_path)) {
        echo "❌ Répertoire migrations introuvable\n";
        exit(1);
    }
    
    $migration_files = glob($migrations_path . '/*.php');
    echo "📋 Fichiers de migration trouvés : " . count($migration_files) . "\n";
    
    foreach ($migration_files as $file) {
        echo "   - " . basename($file) . "\n";
    }
    echo "\n";
    
    // Essayer de charger chaque migration comme le fait phpBB
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ TEST : Chargement des migrations une par une                │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $all_dependencies = array();
    
    foreach ($migration_files as $file) {
        $filename = basename($file, '.php');
        $class_name = '\\bastien59960\\reactions\\migrations\\' . $filename;
        
        echo "🔍 Chargement de : $class_name\n";
        
        if (!file_exists($file)) {
            echo "   ❌ Fichier introuvable\n\n";
            continue;
        }
        
        require_once($file);
        
        if (!class_exists($class_name)) {
            echo "   ❌ Classe non trouvée après chargement\n\n";
            continue;
        }
        
        echo "   ✅ Classe chargée\n";
        
        // Tester depends_on() comme le fait migrator.php
        $reflection = new ReflectionClass($class_name);
        if ($reflection->hasMethod('depends_on')) {
            $method = $reflection->getMethod('depends_on');
            if ($method->isStatic()) {
                try {
                    $deps = $method->invoke(null);
                    
                    echo "   📋 depends_on() appelé\n";
                    echo "      Type retourné : " . gettype($deps) . "\n";
                    
                    if (is_array($deps)) {
                        echo "      ✅ Retourne un array (" . count($deps) . " éléments)\n";
                        foreach ($deps as $dep) {
                            echo "         - $dep\n";
                        }
                        
                        // Simuler array_merge() comme à la ligne 788 de migrator.php
                        echo "      🔄 Test array_merge()...\n";
                        try {
                            $all_dependencies = array_merge($all_dependencies, $deps);
                            echo "      ✅ array_merge() réussi\n";
                        } catch (\TypeError $e) {
                            echo "      ❌ ERREUR array_merge() : " . $e->getMessage() . "\n";
                            echo "      💡 C'est probablement la cause du problème !\n";
                        }
                    } else {
                        echo "      ❌ Retourne un " . gettype($deps) . " au lieu d'un array !\n";
                        echo "      💡 Valeur : " . var_export($deps, true) . "\n";
                        
                        // Tenter quand même array_merge pour voir l'erreur
                        try {
                            $all_dependencies = array_merge($all_dependencies, $deps);
                        } catch (\TypeError $e) {
                            echo "      ❌ ERREUR array_merge() : " . $e->getMessage() . "\n";
                            echo "      💡 C'est la cause du problème !\n";
                        }
                    }
                } catch (\Throwable $e) {
                    echo "   ❌ ERREUR lors de l'appel de depends_on() : " . $e->getMessage() . "\n";
                    echo "      Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
                }
            }
        }
        
        echo "\n";
    }
    
    echo "✅ Tous les tests de chargement terminés\n";
    echo "   Total dépendances collectées : " . count($all_dependencies) . "\n\n";
    
    // Vérifier si la migration de phpBB core référencée existe
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ VÉRIFICATION : Migration phpBB core référencée               │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $core_migration = '\phpbb\db\migration\data\v33x\v3310';
    echo "🔍 Vérification de : $core_migration\n";
    
    // Chercher le fichier de migration dans phpBB
    $core_migration_path = $phpbb_root_path . 'phpbb/db/migration/data/v33x/v3310.php';
    if (file_exists($core_migration_path)) {
        echo "   ✅ Fichier trouvé : $core_migration_path\n";
        
        require_once($core_migration_path);
        if (class_exists($core_migration)) {
            echo "   ✅ Classe chargée\n";
            
            $reflection = new ReflectionClass($core_migration);
            if ($reflection->hasMethod('depends_on')) {
                $method = $reflection->getMethod('depends_on');
                if ($method->isStatic()) {
                    try {
                        $deps = $method->invoke(null);
                        $type = gettype($deps);
                        if ($type === 'array') {
                            echo "   ✅ depends_on() retourne un array\n";
                        } else {
                            echo "   ❌ depends_on() retourne un $type au lieu d'un array !\n";
                            echo "      💡 C'est peut-être la cause du problème !\n";
                        }
                    } catch (\Throwable $e) {
                        echo "   ❌ ERREUR : " . $e->getMessage() . "\n";
                    }
                }
            }
        } else {
            echo "   ❌ Classe non trouvée après chargement\n";
        }
    } else {
        echo "   ⚠️  Fichier non trouvé : $core_migration_path\n";
        echo "   💡 La migration core pourrait être dans un autre emplacement\n";
    }
    echo "\n";
    
    // Vérifier s'il y a des migrations en base de données qui n'ont pas de fichiers
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ VÉRIFICATION : Migrations en base vs fichiers                 │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $sql = "SELECT migration_name, migration_depends_on 
            FROM {$table_prefix}migrations 
            WHERE migration_name LIKE '%bastien59960%reactions%'
            ORDER BY migration_name";
    $result = $db->sql_query($sql);
    $migrations_in_db = $db->sql_fetchrowset($result);
    $db->sql_freeresult($result);
    
    if (!empty($migrations_in_db)) {
        echo "⚠️  ATTENTION : Des migrations sont encore en base de données !\n";
        echo "   Cela pourrait causer le problème array_merge()\n\n";
        
        foreach ($migrations_in_db as $migration) {
            $name = $migration['migration_name'];
            $depends_on_serialized = $migration['migration_depends_on'];
            
            // Extraire le nom de classe depuis le nom de migration
            $parts = explode('\\', $name);
            $class_name_from_db = end($parts);
            $file_path = $migrations_path . '/' . $class_name_from_db . '.php';
            
            echo "📋 Migration en DB : $name\n";
            
            // Tester la désérialisation des dépendances
            $depends_on_unserialized = @unserialize($depends_on_serialized);
            if ($depends_on_unserialized === false && $depends_on_serialized !== serialize(false)) {
                echo "   ❌ PROBLÈME : Impossible de désérialiser migration_depends_on !\n";
                echo "      Valeur sérialisée : $depends_on_serialized\n";
                echo "      💡 C'est probablement la cause de l'erreur array_merge() !\n";
            } else {
                $type = gettype($depends_on_unserialized);
                if ($type === 'array') {
                    echo "   ✅ Dépendances désérialisées : array (" . count($depends_on_unserialized) . " éléments)\n";
                } else {
                    echo "   ❌ PROBLÈME : Dépendances désérialisées en $type au lieu d'un array !\n";
                    echo "      Valeur : " . var_export($depends_on_unserialized, true) . "\n";
                    echo "      💡 C'est la cause de l'erreur array_merge() !\n";
                }
            }
            
            if (file_exists($file_path)) {
                echo "   ✅ Fichier existe : " . basename($file_path) . "\n";
            } else {
                echo "   ❌ Fichier MANQUANT : " . basename($file_path) . "\n";
                echo "   💡 Cette migration orpheline pourrait causer le problème !\n";
            }
            echo "\n";
        }
    } else {
        echo "✅ Aucune migration enregistrée en base de données (état propre)\n";
    }
    echo "\n";
    
    // TEST CRITIQUE : Simuler exactement ce que fait migrator.php ligne 788
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ TEST CRITIQUE : Simulation migrator.php ligne 788            │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    echo "🔍 Tentative de récupération des migrations via le migrator...\n";
    
    try {
        // Essayer de charger les migrations comme le fait phpBB
        $extension_migrations = array();
        
        foreach ($migration_files as $file) {
            $filename = basename($file, '.php');
            $class_name = '\\bastien59960\\reactions\\migrations\\' . $filename;
            
            if (class_exists($class_name)) {
                $extension_migrations[] = $class_name;
            }
        }
        
        echo "📋 Migrations trouvées : " . count($extension_migrations) . "\n";
        
        // Simuler le code de migrator.php qui collecte les dépendances
        $all_deps = array();
        
        foreach ($extension_migrations as $migration_class) {
            echo "🔍 Traitement de : $migration_class\n";
            
            if (!class_exists($migration_class)) {
                echo "   ❌ Classe non trouvée\n\n";
                continue;
            }
            
            $reflection = new ReflectionClass($migration_class);
            if ($reflection->hasMethod('depends_on')) {
                $method = $reflection->getMethod('depends_on');
                if ($method->isStatic()) {
                    $deps = $method->invoke(null);
                    
                    echo "   Type de depends_on() : " . gettype($deps) . "\n";
                    
                    // C'est ici que l'erreur se produit dans migrator.php ligne 788
                    if (!is_array($deps)) {
                        echo "   ❌ PROBLÈME DÉTECTÉ : depends_on() retourne " . gettype($deps) . " au lieu d'un array !\n";
                        echo "      Valeur : " . var_export($deps, true) . "\n";
                        echo "      💡 C'est la cause de l'erreur array_merge() !\n\n";
                        continue;
                    }
                    
                    echo "   ✅ depends_on() retourne un array\n";
                    
                    // Simuler array_merge() comme à la ligne 788
                    try {
                        $all_deps = array_merge($all_deps, $deps);
                        echo "   ✅ array_merge() réussi\n";
                    } catch (\TypeError $e) {
                        echo "   ❌ ERREUR array_merge() : " . $e->getMessage() . "\n";
                        echo "      💡 C'est la cause exacte du problème !\n";
                    }
                }
            }
            echo "\n";
        }
        
        echo "✅ Test terminé. Total dépendances : " . count($all_deps) . "\n";
        
    } catch (\Throwable $e) {
        echo "❌ ERREUR lors du test avec migrator : " . $e->getMessage() . "\n";
        echo "   Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Trace :\n" . $e->getTraceAsString() . "\n";
    }
    
    // TEST FINAL : Simuler exactement ce que fait phpBB lors de l'activation
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ TEST FINAL : Simulation activation réelle par phpBB         │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    echo "🔍 Tentative de récupération des migrations depuis la base de données...\n";
    
    // Récupérer toutes les migrations de cette extension depuis la DB
    $sql = "SELECT migration_name, migration_depends_on 
            FROM {$table_prefix}migrations 
            WHERE migration_name LIKE '%bastien59960%reactions%'
            ORDER BY migration_name";
    $result = $db->sql_query($sql);
    $db_migrations = $db->sql_fetchrowset($result);
    $db->sql_freeresult($result);
    
    if (!empty($db_migrations)) {
        echo "📋 Migrations trouvées en base de données : " . count($db_migrations) . "\n\n";
        
        $all_deps_from_db = array();
        
        foreach ($db_migrations as $db_migration) {
            $migration_name = $db_migration['migration_name'];
            $depends_on_serialized = $db_migration['migration_depends_on'];
            
            echo "🔍 Traitement de la migration depuis DB : $migration_name\n";
            
            // Désérialiser les dépendances comme le fait phpBB
            $depends_on = @unserialize($depends_on_serialized);
            
            if ($depends_on === false && $depends_on_serialized !== serialize(false)) {
                echo "   ❌ ERREUR : Impossible de désérialiser migration_depends_on !\n";
                echo "      Valeur sérialisée : $depends_on_serialized\n";
                echo "      💡 C'est probablement la cause de l'erreur array_merge() !\n\n";
                continue;
            }
            
            $type = gettype($depends_on);
            echo "   Type après désérialisation : $type\n";
            
            if (!is_array($depends_on)) {
                echo "   ❌ PROBLÈME CRITIQUE : depends_on n'est PAS un array !\n";
                echo "      Type : $type\n";
                echo "      Valeur : " . var_export($depends_on, true) . "\n";
                echo "      💡 C'est la cause exacte de l'erreur array_merge() ligne 788 !\n\n";
                
                // Tenter array_merge pour confirmer l'erreur
                try {
                    $all_deps_from_db = array_merge($all_deps_from_db, $depends_on);
                } catch (\TypeError $e) {
                    echo "   ❌ ERREUR array_merge() confirmée : " . $e->getMessage() . "\n";
                    echo "      💡 C'est exactement l'erreur que vous voyez !\n\n";
                }
                continue;
            }
            
            echo "   ✅ depends_on est un array (" . count($depends_on) . " éléments)\n";
            
            // Tenter array_merge() comme à la ligne 788 de migrator.php
            try {
                $all_deps_from_db = array_merge($all_deps_from_db, $depends_on);
                echo "   ✅ array_merge() réussi\n";
            } catch (\TypeError $e) {
                echo "   ❌ ERREUR array_merge() : " . $e->getMessage() . "\n";
                echo "      💡 C'est la cause exacte du problème !\n";
            }
            echo "\n";
        }
        
        echo "✅ Test terminé. Total dépendances depuis DB : " . count($all_deps_from_db) . "\n";
    } else {
        echo "ℹ️  Aucune migration en base de données (état propre pour activation)\n";
        echo "   💡 Si l'erreur persiste, elle vient peut-être d'une autre extension\n\n";
        
        // Vérifier s'il y a d'autres migrations problématiques
        echo "🔍 Recherche de migrations problématiques dans TOUTES les extensions...\n";
        
        $sql = "SELECT migration_name, migration_depends_on 
                FROM {$table_prefix}migrations 
                ORDER BY migration_name";
        $result = $db->sql_query($sql);
        $all_migrations = $db->sql_fetchrowset($result);
        $db->sql_freeresult($result);
        
        $problematic_migrations = array();
        
        foreach ($all_migrations as $migration) {
            $depends_on_serialized = $migration['migration_depends_on'];
            $depends_on = @unserialize($depends_on_serialized);
            
            if ($depends_on === false && $depends_on_serialized !== serialize(false)) {
                $problematic_migrations[] = array(
                    'name' => $migration['migration_name'],
                    'issue' => 'impossible_to_unserialize',
                    'value' => $depends_on_serialized
                );
            } elseif (!is_array($depends_on) && $depends_on !== false) {
                $problematic_migrations[] = array(
                    'name' => $migration['migration_name'],
                    'issue' => 'not_an_array',
                    'type' => gettype($depends_on),
                    'value' => $depends_on
                );
            }
        }
        
        if (!empty($problematic_migrations)) {
            echo "⚠️  Migrations problématiques trouvées : " . count($problematic_migrations) . "\n\n";
            foreach ($problematic_migrations as $problem) {
                echo "❌ Migration : " . $problem['name'] . "\n";
                if ($problem['issue'] === 'impossible_to_unserialize') {
                    echo "   Problème : Impossible de désérialiser migration_depends_on\n";
                    echo "   Valeur : " . $problem['value'] . "\n";
                } elseif ($problem['issue'] === 'not_an_array') {
                    echo "   Problème : depends_on n'est pas un array (type: " . $problem['type'] . ")\n";
                    echo "   Valeur : " . var_export($problem['value'], true) . "\n";
                }
                echo "   💡 Cette migration pourrait causer l'erreur array_merge() !\n\n";
            }
        } else {
            echo "✅ Aucune migration problématique trouvée dans toutes les extensions\n";
        }
    }
    
} catch (\Throwable $e) {
    echo "\n❌ ERREUR FATALE : " . $e->getMessage() . "\n";
    echo "   Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace :\n" . $e->getTraceAsString() . "\n";
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  DEBUG TERMINÉ                                                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

