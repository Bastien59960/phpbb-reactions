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
        foreach ($migrations_in_db as $migration) {
            $name = $migration['migration_name'];
            // Extraire le nom de classe depuis le nom de migration
            $parts = explode('\\', $name);
            $class_name_from_db = end($parts);
            $file_path = $migrations_path . '/' . $class_name_from_db . '.php';
            
            echo "📋 Migration en DB : $name\n";
            if (file_exists($file_path)) {
                echo "   ✅ Fichier existe : " . basename($file_path) . "\n";
            } else {
                echo "   ❌ Fichier MANQUANT : " . basename($file_path) . "\n";
                echo "   💡 Cette migration orpheline pourrait causer le problème !\n";
            }
        }
    } else {
        echo "ℹ️  Aucune migration enregistrée en base de données\n";
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
    
} catch (\Throwable $e) {
    echo "\n❌ ERREUR FATALE : " . $e->getMessage() . "\n";
    echo "   Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace :\n" . $e->getTraceAsString() . "\n";
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  DEBUG TERMINÉ                                                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

