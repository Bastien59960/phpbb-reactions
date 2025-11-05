<?php
/**
 * Script de diagnostic avancé pour déboguer les extensions phpBB
 * Version corrigée - Suppression de load_from_extension() qui n'existe pas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// --- Détection robuste du chemin racine de phpBB ---
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
    die("❌ ERREUR FATALE: Impossible de trouver la racine du forum (common.php introuvable).\n");
}

define('IN_PHPBB', true);
$phpEx = 'php';

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC AVANCÉ DU CONTENEUR DE SERVICES phpBB             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
echo "📁 Chemin racine phpBB : " . $phpbb_root_path . "\n\n";

try {
    // ========== PHASE 1 : Initialisation ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 1 : Initialisation de l'environnement                │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $required_files = [
        'config.' . $phpEx,
        'vendor/autoload.' . $phpEx,
        'includes/constants.' . $phpEx,
        'phpbb/class_loader.' . $phpEx,
    ];
    
    foreach ($required_files as $file) {
        $filepath = $phpbb_root_path . $file;
        if (!file_exists($filepath)) {
            throw new \Exception("Fichier requis manquant : $filepath");
        }
        require($filepath);
        echo "✅ Chargé : $file\n";
    }
    
    $phpbb_class_loader = new \phpbb\class_loader('phpbb\\', "{$phpbb_root_path}phpbb/", $phpEx);
    $phpbb_class_loader->register();
    
    echo "✅ Autoloader enregistré\n\n";

    // ========== PHASE 2 : Nettoyage du cache ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 2 : Nettoyage du cache du conteneur                  │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $cache_dir = $phpbb_root_path . 'cache/production/';
    
    if (!is_dir($cache_dir)) {
        echo "⚠️  Répertoire cache inexistant, tentative de création : $cache_dir\n";
        if (!mkdir($cache_dir, 0777, true)) {
            throw new \Exception("Impossible de créer le répertoire de cache.");
        }
        echo "✅ Répertoire de cache créé.\n";
    } else {
        $cache_files = glob($cache_dir . '{container_*,data_container_*,autoload_*}.php', GLOB_BRACE);
        if ($cache_files === false) {
            echo "⚠️  Impossible de lister les fichiers de cache\n";
        } else if (count($cache_files) > 0) {
            foreach ($cache_files as $file) {
                if (is_file($file) && is_writable($file)) {
                    if (unlink($file)) {
                        echo "🗑️  Supprimé: " . basename($file) . "\n";
                    } else {
                        echo "⚠️  Impossible de supprimer: " . basename($file) . "\n";
                    }
                }
            }
        } else {
            echo "ℹ️  Aucun fichier de cache à supprimer\n";
        }
    }

    if (!is_writable($cache_dir)) {
        echo "❌ ERREUR : Le répertoire de cache n'est pas accessible en écriture : $cache_dir\n";
        echo "💡 Exécutez : chmod -R 777 $cache_dir\n";
    }
    echo "\n";

    // ========== PHASE 3 : Construction du conteneur ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 3 : Construction du conteneur                        │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    try {
        $phpbb_config_php_file = new \phpbb\config_php_file($phpbb_root_path, $phpEx);
        echo "✅ Config PHP créée\n";
        
        $config_values = $phpbb_config_php_file->get_all();
        echo "✅ Configuration chargée\n";
        
        $cache_driver_class = 'phpbb\\cache\\driver\\file';
        
        if (isset($config_values['acm_type'])) {
            $acm_type = $config_values['acm_type'];
            if (strpos($acm_type, '\\') !== false) {
                $cache_driver_class = $acm_type;
                echo "✅ Type de cache détecté (chemin complet) : $acm_type\n";
            } else {
                $cache_driver_class = 'phpbb\\cache\\driver\\' . $acm_type;
                echo "✅ Type de cache détecté (nom simple) : $acm_type\n";
            }
        } else {
            echo "⚠️  acm_type non défini, utilisation de 'file' par défaut\n";
        }
        
        $custom_parameters = [
            'cache.driver.class' => $cache_driver_class,
            'core.table_prefix' => isset($config_values['table_prefix']) ? $config_values['table_prefix'] : 'phpbb_',
            'core.adm_relative_path' => 'adm/',
            'core.php_ext' => $phpEx,
            'core.environment' => 'production',
        ];
        
        if (isset($config_values['dbms'])) {
            $custom_parameters['dbal.driver.class'] = $config_values['dbms'];
        }
        
        echo "✅ Paramètres préparés : " . count($custom_parameters) . " paramètres\n";
        
        // Connexion base de données
        $dbms = $config_values['dbms'];
        if (strpos($dbms, '\\') !== false) {
            $db_driver_class = $dbms;
        } else {
            $db_driver_class = '\phpbb\db\driver\\' . $dbms;
        }

        $db_connection = new $db_driver_class();
        $db_connection->sql_connect(
            $config_values['dbhost'],
            $config_values['dbuser'],
            $config_values['dbpasswd'],
            $config_values['dbname'],
            $config_values['dbport'],
            false,
            false
        );
        echo "✅ Connexion à la base de données initialisée.\n";

    } catch (\Exception $e) {
        throw new \Exception("Impossible de créer config_php_file : " . $e->getMessage());
    }
    
    try {
        // CORRECTION CRITIQUE : On passe le chemin CONFIG, pas l'objet
        $phpbb_container_builder = new \phpbb\di\container_builder(
            $phpbb_root_path . 'config',
            $phpbb_root_path,
            $phpEx
        );
        
        $phpbb_container_builder->with_custom_parameters($custom_parameters);
        echo "✅ Container builder créé\n";
        echo "✅ Paramètres injectés dans le container builder\n";
    } catch (\Exception $e) {
        throw new \Exception("Impossible de créer container_builder : " . $e->getMessage());
    }

    echo "⚙️  Compilation du conteneur (sans cache)...\n";

    // CORRECTION : On supprime l'appel à load_from_extension() qui n'existe pas
    // phpBB charge automatiquement les extensions via le container builder
    
    $phpbb_container_builder = $phpbb_container_builder->without_cache();
    echo "⚠️ Mode sans cache activé\n";

    try {
        echo "⚙️  Obtention du conteneur...\n";
        $phpbb_container = $phpbb_container_builder->get_container();

        // Injection des services synthétiques
        $phpbb_container->set('dbal.conn', $db_connection);
        echo "✅ Service 'dbal.conn' injecté.\n";

        $config = new \phpbb\config\db(
            $phpbb_container->get('dbal.conn'),
            $phpbb_container->get('cache.driver'),
            $phpbb_container->getParameter('core.table_prefix') . 'config'
        );
        $phpbb_container->set('config', $config);
        echo "✅ Service 'config' injecté.\n";

        echo "✅ Conteneur chargé avec succès.\n\n";
    } catch (\Exception $e) {
        throw new \Exception("Erreur lors de la compilation : " . $e->getMessage() . "\n   Fichier: " . $e->getFile() . ":" . $e->getLine());
    }

    // ========== PHASE 4 : Vérification des extensions ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 4 : Extensions activées                               │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $reactions_found = false;
    try {
        if ($phpbb_container->has('ext.manager')) {
            $ext_manager = $phpbb_container->get('ext.manager');
            $enabled_extensions = $ext_manager->all_enabled();
            
            if (empty($enabled_extensions)) {
                echo "⚠️  Aucune extension activée\n";
            } else {
                foreach ($enabled_extensions as $ext_name) {
                    $is_target = (strpos($ext_name, 'bastien59960/reactions') !== false);
                    if ($is_target) {
                        $reactions_found = true;
                    }
                    echo ($is_target ? "🎯 " : "   ") . $ext_name . "\n";
                }
            }
            
            if (!$reactions_found) {
                echo "\n⚠️  Extension bastien59960/reactions NON TROUVÉE\n";
            }
        } else {
            echo "⚠️  Extension manager non disponible\n";
        }
    } catch (\Exception $e) {
        echo "❌ Erreur : " . $e->getMessage() . "\n";
    }
    echo "\n";

    // ========== PHASE 5 : Analyse des services cron ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 5 : Analyse des services cron                        │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    try {
        $all_services = $phpbb_container->getServiceIds();
        $cron_services = array_filter($all_services, function($id) {
            return strpos($id, 'cron.task') === 0;
        });
        
        echo "📊 Services cron trouvés : " . count($cron_services) . "\n\n";
        
        if (empty($cron_services)) {
            echo "⚠️  Aucun service cron\n";
        } else {
            foreach ($cron_services as $cron_id) {
                $is_target = (strpos($cron_id, 'bastien59960') !== false);
                echo ($is_target ? "🔍 " : "   ") . $cron_id;
                
                try {
                    if (!$phpbb_container->has($cron_id)) {
                        echo " ❌ Non disponible\n";
                        continue;
                    }
                    
                    $service = $phpbb_container->get($cron_id);
                    $class = get_class($service);
                    echo " → " . $class;
                    
                    if (method_exists($service, 'get_name')) {
                        try {
                            $name = $service->get_name();
                            echo empty($name) ? " [❌ VIDE]" : " [✅ '$name']";
                        } catch (\Exception $e) {
                            echo " [❌ Erreur]";
                        }
                    } else {
                        echo " [⚠️ PAS DE get_name()]";
                    }
                    
                    echo "\n";
                } catch (\Exception $e) {
                    echo " ❌ " . $e->getMessage() . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "❌ Erreur : " . $e->getMessage() . "\n";
    }
    echo "\n";

    // ========== PHASE 6 : Test email digest ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 6 : Vérification templates email                     │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $templates = [
        'ext/bastien59960/reactions/styles/all/template/email/reaction_digest.html',
        'ext/bastien59960/reactions/styles/all/template/email/reaction_digest.txt',
        'ext/bastien59960/reactions/language/fr/email.php',
    ];
    
    foreach ($templates as $template) {
        $path = $phpbb_root_path . $template;
        if (file_exists($path)) {
            $size = filesize($path);
            if ($size === 0) {
                echo "⚠️  VIDE : " . basename($template) . "\n";
            } else {
                echo "✅ OK (" . $size . " bytes) : " . basename($template) . "\n";
            }
        } else {
            echo "❌ MANQUANT : " . basename($template) . "\n";
        }
    }

    echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  DIAGNOSTIC TERMINÉ                                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";

} catch (\Throwable $e) {
    echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ ERREUR FATALE                                             ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}