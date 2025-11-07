<?php
/**
 * Test EXACT de ce que fait phpBB lors de l'activation
 * Simule la méthode populate_migrations() qui charge TOUTES les migrations depuis la DB
 * et teste array_merge() exactement comme à la ligne 788 de migrator.php
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
echo "║  TEST EXACT - Simulation ligne 788 migrator.php              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

require($phpbb_root_path . 'common.' . $phpEx);

global $phpbb_container, $db, $table_prefix;

try {
    // Obtenir le migrator réel
    if (!$phpbb_container->has('migrator')) {
        die("❌ Service 'migrator' non trouvé\n");
    }
    
    $migrator = $phpbb_container->get('migrator');
    echo "✅ Migrator obtenu : " . get_class($migrator) . "\n\n";
    
    // SIMULER EXACTEMENT ce que fait populate_migrations()
    // Cette méthode charge TOUTES les migrations depuis la base de données
    // et collecte leurs dépendances avec array_merge() à la ligne 788
    
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ SIMULATION : populate_migrations() - ligne 788              │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    // Récupérer TOUTES les migrations depuis la base de données
    $sql = "SELECT migration_name, migration_depends_on 
            FROM {$table_prefix}migrations 
            ORDER BY migration_name";
    $result = $db->sql_query($sql);
    $all_migrations_from_db = $db->sql_fetchrowset($result);
    $db->sql_freeresult($result);
    
    echo "📊 Total migrations en base de données : " . count($all_migrations_from_db) . "\n\n";
    
    // Simuler le code de populate_migrations() qui collecte les dépendances
    // C'est ici que l'erreur se produit à la ligne 788
    $all_dependencies = array();
    $problematic_migrations = array();
    
    foreach ($all_migrations_from_db as $migration_row) {
        $migration_name = $migration_row['migration_name'];
        $migration_depends_on_serialized = $migration_row['migration_depends_on'];
        
        echo "🔍 Traitement de : $migration_name\n";
        
        // Désérialiser les dépendances comme le fait phpBB
        $migration_depends_on = @unserialize($migration_depends_on_serialized);
        
        // Vérifier si la désérialisation a échoué
        if ($migration_depends_on === false && $migration_depends_on_serialized !== serialize(false)) {
            echo "   ❌ ERREUR : Impossible de désérialiser migration_depends_on !\n";
            echo "      Valeur sérialisée : " . substr($migration_depends_on_serialized, 0, 100) . "...\n";
            $problematic_migrations[] = array(
                'name' => $migration_name,
                'issue' => 'unserialize_failed',
                'value' => $migration_depends_on_serialized
            );
            echo "\n";
            continue;
        }
        
        // Vérifier le type après désérialisation
        $type = gettype($migration_depends_on);
        echo "   Type après désérialisation : $type\n";
        
        // C'EST ICI QUE L'ERREUR SE PRODUIT À LA LIGNE 788
        // phpBB fait : $all_dependencies = array_merge($all_dependencies, $migration_depends_on);
        // Si $migration_depends_on n'est pas un array, cela génère l'erreur TypeError
        
        if (!is_array($migration_depends_on)) {
            echo "   ❌ PROBLÈME CRITIQUE : depends_on n'est PAS un array !\n";
            echo "      Type : $type\n";
            echo "      Valeur : " . var_export($migration_depends_on, true) . "\n";
            echo "      💡 C'est EXACTEMENT la cause de l'erreur array_merge() ligne 788 !\n";
            
            $problematic_migrations[] = array(
                'name' => $migration_name,
                'issue' => 'not_array',
                'type' => $type,
                'value' => $migration_depends_on
            );
            
            // Tenter array_merge pour confirmer l'erreur exacte
            try {
                $all_dependencies = array_merge($all_dependencies, $migration_depends_on);
            } catch (\TypeError $e) {
                echo "   ❌ ERREUR array_merge() confirmée : " . $e->getMessage() . "\n";
                echo "      💡 C'est EXACTEMENT l'erreur que vous voyez lors de l'activation !\n";
            }
            echo "\n";
            continue;
        }
        
        // Si c'est un array, tenter array_merge() comme à la ligne 788
        echo "   ✅ depends_on est un array (" . count($migration_depends_on) . " éléments)\n";
        try {
            $all_dependencies = array_merge($all_dependencies, $migration_depends_on);
            echo "   ✅ array_merge() réussi\n";
        } catch (\TypeError $e) {
            echo "   ❌ ERREUR array_merge() : " . $e->getMessage() . "\n";
            echo "      💡 C'est la cause exacte du problème !\n";
            
            $problematic_migrations[] = array(
                'name' => $migration_name,
                'issue' => 'array_merge_failed',
                'error' => $e->getMessage()
            );
        }
        echo "\n";
    }
    
    echo "✅ Simulation terminée. Total dépendances collectées : " . count($all_dependencies) . "\n\n";
    
    // AFFICHAGE DES MIGRATIONS PROBLÉMATIQUES
    if (!empty($problematic_migrations)) {
        echo "┌─────────────────────────────────────────────────────────────┐\n";
        echo "│ ⚠️  MIGRATIONS PROBLÉMATIQUES DÉTECTÉES                     │\n";
        echo "└─────────────────────────────────────────────────────────────┘\n";
        
        echo "📊 Nombre de migrations problématiques : " . count($problematic_migrations) . "\n\n";
        
        foreach ($problematic_migrations as $prob) {
            echo "❌ Migration : " . $prob['name'] . "\n";
            
            if ($prob['issue'] === 'unserialize_failed') {
                echo "   Problème : Impossible de désérialiser migration_depends_on\n";
                echo "   Valeur sérialisée : " . substr($prob['value'], 0, 150) . "...\n";
                echo "   💡 Cette migration cause l'erreur array_merge() ligne 788 !\n";
            } elseif ($prob['issue'] === 'not_array') {
                echo "   Problème : depends_on n'est pas un array (type: " . $prob['type'] . ")\n";
                echo "   Valeur : " . var_export($prob['value'], true) . "\n";
                echo "   💡 Cette migration cause l'erreur array_merge() ligne 788 !\n";
            } elseif ($prob['issue'] === 'array_merge_failed') {
                echo "   Problème : array_merge() a échoué\n";
                echo "   Erreur : " . $prob['error'] . "\n";
                echo "   💡 Cette migration cause l'erreur array_merge() ligne 788 !\n";
            }
            echo "\n";
        }
        
        echo "🔧 SOLUTION :\n";
        echo "   1. Supprimez ces migrations problématiques de la base de données :\n";
        foreach ($problematic_migrations as $prob) {
            echo "      DELETE FROM {$table_prefix}migrations WHERE migration_name = '" . addslashes($prob['name']) . "';\n";
        }
        echo "\n";
        echo "   2. Ou corrigez les données dans la base de données pour que migration_depends_on soit un array sérialisé.\n\n";
    } else {
        echo "┌─────────────────────────────────────────────────────────────┐\n";
        echo "│ ✅ AUCUNE MIGRATION PROBLÉMATIQUE TROUVÉE                    │\n";
        echo "└─────────────────────────────────────────────────────────────┘\n";
        echo "💡 Si l'erreur persiste, elle pourrait venir d'une autre source.\n";
        echo "   Vérifiez les logs d'erreur PHP pour plus de détails.\n\n";
    }
    
    // STATISTIQUES DÉTAILLÉES
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ STATISTIQUES DÉTAILLÉES                                     │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $sql = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN migration_depends_on IS NULL THEN 1 END) as null_count,
                COUNT(CASE WHEN migration_depends_on = '' THEN 1 END) as empty_count,
                COUNT(CASE WHEN migration_depends_on LIKE 'a:%' THEN 1 END) as array_count,
                COUNT(CASE WHEN migration_depends_on LIKE 's:%' THEN 1 END) as string_count,
                COUNT(CASE WHEN migration_depends_on NOT LIKE 'a:%' 
                          AND migration_depends_on NOT LIKE 's:%'
                          AND migration_depends_on IS NOT NULL 
                          AND migration_depends_on != '' THEN 1 END) as other_count
            FROM {$table_prefix}migrations";
    $result = $db->sql_query($sql);
    $stats = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    
    echo "📊 Répartition des migrations :\n";
    echo "   - Total : " . $stats['total'] . "\n";
    echo "   - NULL : " . $stats['null_count'] . "\n";
    echo "   - Vide : " . $stats['empty_count'] . "\n";
    echo "   - Array sérialisé (a:...) : " . $stats['array_count'] . " ✅\n";
    echo "   - String sérialisé (s:...) : " . $stats['string_count'] . " ❌\n";
    echo "   - Autre : " . $stats['other_count'] . " ❌\n\n";
    
    if ($stats['string_count'] > 0 || $stats['other_count'] > 0) {
        echo "⚠️  ATTENTION : Il y a " . ($stats['string_count'] + $stats['other_count']) . " migration(s) avec des dépendances non-array !\n";
        echo "   Ces migrations causent l'erreur array_merge() ligne 788.\n\n";
        
        // Afficher les migrations problématiques
        $sql = "SELECT migration_name, LEFT(migration_depends_on, 100) as depends_preview
                FROM {$table_prefix}migrations 
                WHERE (migration_depends_on LIKE 's:%' 
                       OR (migration_depends_on NOT LIKE 'a:%' 
                           AND migration_depends_on NOT LIKE 's:%'
                           AND migration_depends_on IS NOT NULL 
                           AND migration_depends_on != ''))
                LIMIT 20";
        $result = $db->sql_query($sql);
        $problematic_list = $db->sql_fetchrowset($result);
        $db->sql_freeresult($result);
        
        if (!empty($problematic_list)) {
            echo "📋 Liste des migrations problématiques :\n";
            foreach ($problematic_list as $prob) {
                echo "   - " . $prob['migration_name'] . "\n";
                echo "     Dépendances : " . $prob['depends_preview'] . "...\n";
            }
            echo "\n";
        }
    }
    
} catch (\Throwable $e) {
    echo "\n❌ ERREUR FATALE : " . $e->getMessage() . "\n";
    echo "   Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace :\n" . $e->getTraceAsString() . "\n";
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST TERMINÉ                                                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
