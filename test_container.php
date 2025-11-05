<?php
/**
 * Script de diagnostic avancé pour déboguer les extensions phpBB
 * Spécialement conçu pour traquer les problèmes de cron et de services
 * Version robuste avec gestion d'erreurs complète
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
            throw new \Exception("Impossible de créer le répertoire de cache. Vérifiez les permissions.");
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
        
        // Récupérer toutes les valeurs de config.php
        $config_values = $phpbb_config_php_file->get_all();
        echo "✅ Configuration chargée\n";
        
        // Déterminer la classe du driver de cache
        $cache_driver_class = 'phpbb\\cache\\driver\\file'; // Valeur par défaut
        
        if (isset($config_values['acm_type'])) {
            $acm_type = $config_values['acm_type'];
            
            // Vérifier si acm_type contient déjà le chemin complet
            if (strpos($acm_type, '\\') !== false) {
                // C'est déjà un chemin complet de classe
                $cache_driver_class = $acm_type;
                echo "✅ Type de cache détecté (chemin complet) : $acm_type\n";
            } else {
                // C'est juste le nom simple (ex: 'file'), on construit le chemin
                $cache_driver_class = 'phpbb\\cache\\driver\\' . $acm_type;
                echo "✅ Type de cache détecté (nom simple) : $acm_type\n";
            }
        } else {
            echo "⚠️  acm_type non défini, utilisation de 'file' par défaut\n";
        }
        
        // Préparer TOUS les paramètres nécessaires pour le conteneur
        $custom_parameters = [
            'cache.driver.class' => $cache_driver_class,
            'core.table_prefix' => isset($config_values['table_prefix']) ? $config_values['table_prefix'] : 'phpbb_',
            'core.adm_relative_path' => isset($config_values['acm_type']) ? 'adm/' : 'adm/',
            'core.php_ext' => $phpEx,
            'core.environment' => 'production',
        ];
        
        // Ajouter tous les autres paramètres de config.php qui pourraient être nécessaires
        if (isset($config_values['dbms'])) {
            $custom_parameters['dbal.driver.class'] = $config_values['dbms'];
        }
        
        echo "✅ Paramètres préparés : " . count($custom_parameters) . " paramètres\n";
        
        // --- INJECTION CRITIQUE DE LA BASE DE DONNÉES ---
        // C'est l'étape qui manquait et qui causait l'erreur "synthetic service".
        // On crée manuellement la connexion à la base de données et on l'injecte
        // dans le conteneur avant de le compiler.
        $dbms = $config_values['dbms'];
        if (strpos($dbms, '\\') !== false) {
            // Le nom contient déjà le namespace complet
            $db_driver_class = $dbms;
        } else {
            // C'est un nom simple, on préfixe le namespace
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
        // CORRECTION: The container builder expects the config *path*, not the config_php_file object.
        $phpbb_container_builder = new \phpbb\di\container_builder($phpbb_root_path . 'config', $phpbb_root_path, $phpEx);
        
        // IMPORTANT : Utiliser with_custom_parameters() pour injecter TOUS les paramètres
        $phpbb_container_builder->with_custom_parameters($custom_parameters);
        echo "✅ Container builder créé\n";
        echo "✅ Paramètres injectés dans le container builder\n";
    } catch (\Exception $e) {
        throw new \Exception("Impossible de créer container_builder : " . $e->getMessage());
    }

    echo "⚙️  Le container builder va maintenant charger les services du cœur et des extensions...\n";

    // --- INJECTION CRITIQUE DE L'EXTENSION CORE ---
    // On doit enregistrer et charger l'extension "core" de phpBB pour que le
    // builder sache où trouver les fichiers de config d'environnement (ex: production/config.yml)
    $core_extension = new \phpbb\di\extension\core($phpbb_root_path . 'config'); // Le chemin est correct
    $phpbb_container_builder->addExtension($core_extension); // CORRECTION : La méthode est addExtension()
    $phpbb_container_builder->loadFromExtension('core');

    $phpbb_container_builder = $phpbb_container_builder->without_cache();
    echo "⚠️ Mode sans cache activé pour forcer la reconstruction complète\n";

    try {
        echo "⚙️  Obtention du conteneur... (phpBB va compiler et mettre en cache si nécessaire)\n";
        $phpbb_container = $phpbb_container_builder->get_container();

        // On injecte le service de base de données "synthétique"
        $phpbb_container->set('dbal.conn', $db_connection);
        echo "✅ Service 'dbal.conn' injecté dans le conteneur.\n";

        // Maintenant que dbal.conn existe, on peut initialiser la config de la base de données
        $config = new \phpbb\config\db($phpbb_container->get('dbal.conn'), $phpbb_container->get('cache.driver'), $phpbb_container->get('config')['table_prefix'] . 'config');
        $phpbb_container->set('config', $config);
        echo "✅ Service 'config' (base de données) injecté dans le conteneur.\n";

        echo "✅ Conteneur chargé avec succès.\n\n";
    } catch (\Exception $e) {
        throw new \Exception("Erreur lors de la compilation du conteneur : " . $e->getMessage() . "\n   Fichier: " . $e->getFile() . ":" . $e->getLine());
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
                echo "\n⚠️  ATTENTION : Extension bastien59960/reactions NON TROUVÉE dans les extensions activées\n";
                echo "💡 Activez l'extension via l'ACP ou la commande : bin/phpbbcli.php extension:enable bastien59960/reactions\n";
            }
        } else {
            echo "⚠️  Extension manager non disponible dans le conteneur\n";
        }
    } catch (\Exception $e) {
        echo "❌ Erreur lors de la récupération des extensions : " . $e->getMessage() . "\n";
    }
    echo "\n";

    // ========== PHASE 5 : Analyse détaillée des services cron ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 5 : Analyse COMPLÈTE des services cron               │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    try {
        $all_services = $phpbb_container->getServiceIds();
        $cron_services = array_filter($all_services, function($id) {
            return strpos($id, 'cron.task') === 0;
        });
        
        echo "📊 Nombre total de services cron : " . count($cron_services) . "\n\n";
        
        if (empty($cron_services)) {
            echo "⚠️  Aucun service cron trouvé dans le conteneur\n";
        } else {
            foreach ($cron_services as $cron_id) {
                $is_target = (strpos($cron_id, 'bastien59960') !== false);
                echo ($is_target ? "🔍 " : "   ") . $cron_id;
                
                try {
                    if (!$phpbb_container->has($cron_id)) {
                        echo " ❌ Service non disponible\n";
                        continue;
                    }
                    
                    $service = $phpbb_container->get($cron_id);
                    if (!is_object($service)) {
                        echo " ❌ Le service n'est pas un objet\n";
                        continue;
                    }
                    
                    $class = get_class($service);
                    echo " → " . $class;
                    
                    // Vérification de la méthode get_name()
                    if (method_exists($service, 'get_name')) {
                        try {
                            $name = $service->get_name();
                            if (empty($name) || trim($name) === '') {
                                echo " [❌ get_name() retourne VIDE]";
                            } else {
                                echo " [Nom: '$name']";
                            }
                        } catch (\Exception $e) {
                            echo " [❌ get_name() erreur: " . $e->getMessage() . "]";
                        }
                    } else {
                        echo " [⚠️ PAS DE get_name()]";
                    }
                    
                    // Vérification si c'est une instance de cron\task\base
                    if ($service instanceof \phpbb\cron\task\base) {
                        echo " ✅";
                    } else {
                        echo " [⚠️ N'hérite PAS de base]";
                    }
                    
                    echo "\n";
                } catch (\Exception $e) {
                    echo " ❌ ERREUR: " . $e->getMessage() . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "❌ Erreur lors de l'analyse des services cron : " . $e->getMessage() . "\n";
    }
    echo "\n";

    // ========== PHASE 6 : Test spécifique de votre extension ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 6 : Test des services de bastien59960/reactions      │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $target_services = [
        'bastien59960.reactions.helper' => 'Helper',
        'bastien59960.reactions.listener' => 'Event Listener',
        'bastien59960.reactions.ajax' => 'Contrôleur AJAX',
        'cron.task.bastien59960.reactions.test_task' => 'Tâche Cron Test',
        'bastien59960.reactions.notification.type.reaction' => 'Notification Reaction',
        'bastien59960.reactions.notification.type.reaction_email_digest' => 'Notification Email Digest',
    ];

    foreach ($target_services as $service_id => $description) {
        echo "🔍 $description ($service_id)\n";
        
        try {
            if (!$phpbb_container->has($service_id)) {
                echo "   ❌ SERVICE NON ENREGISTRÉ dans le conteneur\n";
                echo "   💡 Vérifiez votre fichier config/services.yml\n\n";
                continue;
            }
            
            $service = $phpbb_container->get($service_id);
            
            if (!is_object($service)) {
                echo "   ❌ Le service n'est pas un objet valide\n\n";
                continue;
            }
            
            $class = get_class($service);
            echo "   ✅ Service chargé : $class\n";
            
            // Tests spécifiques pour les crons
            if (strpos($service_id, 'cron.task') === 0) {
                echo "   📋 Tests spécifiques CRON :\n";
                
                // Test 1 : Héritage
                if ($service instanceof \phpbb\cron\task\base) {
                    echo "      ✅ Hérite de \\phpbb\\cron\\task\\base\n";
                } else {
                    echo "      ❌ N'hérite PAS de \\phpbb\\cron\\task\\base\n";
                    echo "      💡 Votre classe doit étendre \\phpbb\\cron\\task\\base\n";
                }
                
                // Test 2 : Méthode get_name()
                if (method_exists($service, 'get_name')) {
                    try {
                        $name = $service->get_name();
                        if (empty($name) || trim($name) === '') {
                            echo "      ❌ get_name() retourne une chaîne VIDE\n";
                            echo "      💡 C'est EXACTEMENT pourquoi le cron apparaît comme '*' dans la liste\n";
                            echo "      💡 Ajoutez : return 'CRON_TASK_BASTIEN_REACTIONS_TEST'; dans get_name()\n";
                        } else {
                            echo "      ✅ get_name() retourne : '$name'\n";
                        }
                    } catch (\Exception $e) {
                        echo "      ❌ get_name() lance une exception : " . $e->getMessage() . "\n";
                        echo "      📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
                    }
                } else {
                    echo "      ❌ Méthode get_name() MANQUANTE\n";
                    echo "      💡 Ajoutez la méthode : public function get_name() { return 'CRON_...'; }\n";
                }
                
                // Test 3 : Méthode run()
                if (method_exists($service, 'run')) {
                    echo "      ✅ Méthode run() présente\n";
                } else {
                    echo "      ❌ Méthode run() MANQUANTE (REQUIS)\n";
                }
                
                // Test 4 : Méthode is_runnable()
                if (method_exists($service, 'is_runnable')) {
                    try {
                        $runnable = $service->is_runnable();
                        echo "      ✅ is_runnable() retourne : " . ($runnable ? 'true' : 'false') . "\n";
                    } catch (\Exception $e) {
                        echo "      ❌ is_runnable() lance une exception : " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "      ❌ Méthode is_runnable() MANQUANTE (REQUIS)\n";
                }
                
                // Test 5 : Méthode should_run()
                if (method_exists($service, 'should_run')) {
                    try {
                        $should_run = $service->should_run();
                        echo "      ✅ should_run() retourne : " . ($should_run ? 'true' : 'false') . "\n";
                    } catch (\Exception $e) {
                        echo "      ❌ should_run() lance une exception : " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "      ℹ️  Méthode should_run() absente (hérité de base - OK)\n";
                }
                
                // Test 6 : Vérification des interfaces
                $interfaces = class_implements($service);
                if ($interfaces !== false && !empty($interfaces)) {
                    echo "      ℹ️  Interfaces implémentées : " . implode(', ', $interfaces) . "\n";
                }
            }
            
            echo "\n";
        } catch (\Exception $e) {
            echo "   ❌ ERREUR lors du chargement : " . $e->getMessage() . "\n";
            echo "   📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
        }
    }

    // ========== PHASE 7 : Vérification du fichier services.yml ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 7 : Vérification du fichier services.yml             │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $services_yml_path = $phpbb_root_path . 'ext/bastien59960/reactions/config/services.yml';
    if (file_exists($services_yml_path)) {
        echo "✅ Fichier services.yml trouvé : $services_yml_path\n";
        echo "📄 Extraits concernant les crons :\n\n";
        
        try {
            $content = file_get_contents($services_yml_path);
            if ($content === false) {
                echo "❌ Impossible de lire le fichier\n";
            } else {
                $lines = explode("\n", $content);
                $in_cron_section = false;
                $indent_level = 0;
                
                foreach ($lines as $line_num => $line) {
                    // Détecte le début d'une section cron
                    if (strpos($line, 'cron.task.bastien59960') !== false) {
                        $in_cron_section = true;
                        $indent_level = strlen($line) - strlen(ltrim($line));
                    }
                    
                    if ($in_cron_section) {
                        $current_indent = strlen($line) - strlen(ltrim($line));
                        
                        // Affiche la ligne
                        echo "   " . ($line_num + 1) . " | " . $line . "\n";
                        
                        // Fin de section si retour au même niveau d'indentation ou moins
                        if (trim($line) !== '' && $current_indent <= $indent_level && !strpos($line, 'cron.task.bastien59960')) {
                            $in_cron_section = false;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            echo "❌ Erreur lors de la lecture : " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Fichier services.yml NON TROUVÉ\n";
        echo "📍 Chemin attendu : $services_yml_path\n";
        echo "💡 L'extension ne peut pas fonctionner sans ce fichier\n";
    }
    echo "\n";

    // ========== PHASE 8 : Vérification des fichiers de langue ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 8 : Vérification des fichiers de langue              │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $lang_files = [
        $phpbb_root_path . 'ext/bastien59960/reactions/language/fr/common.php',
        $phpbb_root_path . 'ext/bastien59960/reactions/language/en/common.php',
    ];
    
    foreach ($lang_files as $lang_file) {
        if (file_exists($lang_file)) {
            echo "✅ Trouvé : " . basename(dirname($lang_file)) . "/common.php\n";
            
            // Vérification de la clé CRON
            $content = file_get_contents($lang_file);
            if (strpos($content, 'BASTIEN59960_REACTIONS_TEST') !== false) {
                echo "   ✅ Contient la clé BASTIEN59960_REACTIONS_TEST\n";
            } else {
                echo "   ⚠️  Ne contient PAS la clé BASTIEN59960_REACTIONS_TEST\n";
            }
        } else {
            echo "⚠️  Manquant : " . basename(dirname($lang_file)) . "/common.php\n";
        }
    }
    echo "\n";

    // ========== RÉSUMÉ FINAL ==========
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  RÉSUMÉ DU DIAGNOSTIC                                         ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "✅ Ce qui fonctionne :\n";
    echo "   • Conteneur compilé sans erreur\n";
    echo "   • Environnement phpBB correctement initialisé\n";
    
    echo "\n💡 Actions recommandées :\n";
    echo "   1. Vérifiez les détails de la PHASE 6 ci-dessus\n";
    echo "   2. Si get_name() retourne vide → c'est LA cause du '*' dans la liste\n";
    echo "   3. Assurez-vous que votre classe hérite de \\phpbb\\cron\\task\\base\n";
    echo "   4. Vérifiez que le tag 'cron.task' est présent dans services.yml\n";
    echo "   5. Après correction, purgez le cache : rm -rf cache/production/*\n";
    echo "   6. Puis réactivez l'extension si nécessaire\n";
    
    echo "\n" . str_repeat("═", 67) . "\n";

} catch (\Throwable $e) {
    echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ ERREUR FATALE                                             ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace complète:\n";
    echo $e->getTraceAsString() . "\n\n";
    echo "💡 Ce script nécessite que phpBB soit correctement installé et configuré.\n";
}