<?php
/**
 * Script de diagnostic simplifié pour phpBB 3.3.x
 * Compatible avec la structure de configuration de phpBB 3.3
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Détection du chemin racine
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
echo "║  DIAGNOSTIC EXTENSION REACTIONS - phpBB 3.3                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
echo "📁 Racine phpBB : " . $phpbb_root_path . "\n\n";

try {
    // ========== PHASE 1 : Initialisation simplifiée ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 1 : Chargement de l'environnement phpBB              │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    // Charger common.php qui initialise TOUT phpBB
    require($phpbb_root_path . 'common.' . $phpEx);
    echo "✅ common.php chargé (DB + Config + User + Cache initialisés)\n\n";

    // Récupérer le conteneur depuis $phpbb_container (variable globale)
    global $phpbb_container;
    
    if (!isset($phpbb_container)) {
        throw new \Exception("Le conteneur phpBB n'est pas disponible");
    }
    
    echo "✅ Conteneur récupéré depuis common.php\n\n";

    // ========== PHASE 2 : Vérification de l'extension ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 2 : État de l'extension bastien59960/reactions       │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    if ($phpbb_container->has('ext.manager')) {
        $ext_manager = $phpbb_container->get('ext.manager');
        $enabled = $ext_manager->all_enabled();
        
        $found = false;
        foreach ($enabled as $ext_name) {
            if (strpos($ext_name, 'bastien59960/reactions') !== false) {
                echo "✅ Extension ACTIVÉE : $ext_name\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "❌ Extension NON activée ou introuvable\n";
            echo "💡 Activez via : php bin/phpbbcli.php extension:enable bastien59960/reactions\n";
        }
    }
    echo "\n";

    // ========== PHASE 3 : Services cron ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 3 : Vérification des services cron                   │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $cron_services = [
        'cron.task.bastien59960.reactions.test_task',
        'cron.task.bastien59960.reactions.notification_task',
    ];
    
    foreach ($cron_services as $service_id) {
        echo "🔍 Test de : $service_id\n";
        
        if (!$phpbb_container->has($service_id)) {
            echo "   ❌ Service NON enregistré\n\n";
            continue;
        }
        
        try {
            $service = $phpbb_container->get($service_id);
            $class = get_class($service);
            echo "   ✅ Classe : $class\n";
            
            if (method_exists($service, 'get_name')) {
                $name = $service->get_name();
                if (empty($name)) {
                    echo "   ❌ get_name() retourne VIDE (c'est le problème !)\n";
                } else {
                    echo "   ✅ get_name() : '$name'\n";
                }
            } else {
                echo "   ❌ Méthode get_name() MANQUANTE\n";
            }
            
            if (method_exists($service, 'is_runnable')) {
                $runnable = $service->is_runnable();
                echo "   " . ($runnable ? "✅" : "⚠️") . " is_runnable() : " . ($runnable ? "true" : "false") . "\n";
            }
            
            if (method_exists($service, 'should_run')) {
                $should_run = $service->should_run();
                echo "   " . ($should_run ? "✅" : "ℹ️") . " should_run() : " . ($should_run ? "true" : "false") . "\n";
            }
            
        } catch (\Exception $e) {
            echo "   ❌ ERREUR : " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    // ========== PHASE 4 : Templates email ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 4 : Vérification des templates email                 │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    $files_to_check = [
        'ext/bastien59960/reactions/styles/all/template/email/reaction_digest.html',
        'ext/bastien59960/reactions/styles/all/template/email/reaction_digest.txt',
        'ext/bastien59960/reactions/language/fr/email.php',
        'ext/bastien59960/reactions/language/fr/common.php',
    ];
    
    foreach ($files_to_check as $file) {
        $path = $phpbb_root_path . $file;
        if (file_exists($path)) {
            $size = filesize($path);
            $status = $size > 0 ? "✅" : "⚠️";
            echo "$status " . basename($file) . " ($size bytes)\n";
            
            if ($size === 0) {
                echo "   💡 Ce fichier est VIDE, c'est un problème !\n";
            }
        } else {
            echo "❌ MANQUANT : " . basename($file) . "\n";
        }
    }
    echo "\n";

    // ========== PHASE 5 : Configuration email ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 5 : Configuration email du forum                     │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    global $config;
    
    echo "Email activé : " . ($config['email_enable'] ? "✅ OUI" : "❌ NON") . "\n";
    echo "Fonction email : " . ($config['email_function_name'] ?? 'mail') . "\n";
    
    if (isset($config['smtp_delivery'])) {
        echo "Méthode : " . ($config['smtp_delivery'] ? "SMTP" : "PHP mail()") . "\n";
    }
    
    if (isset($config['bastien59960_reactions_spam_time'])) {
        echo "Délai anti-spam reactions : " . $config['bastien59960_reactions_spam_time'] . " minutes\n";
    } else {
        echo "⚠️ Config anti-spam non trouvée (défaut : 45 min)\n";
    }
    
    if (isset($config['bastien59960_reactions_cron_last_run'])) {
        $last = $config['bastien59960_reactions_cron_last_run'];
        echo "Dernier run cron : " . ($last > 0 ? date('Y-m-d H:i:s', $last) : "jamais") . "\n";
    }
    echo "\n";

    // ========== PHASE 6 : Test base de données ==========
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ PHASE 6 : Réactions en attente de notification             │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n";
    
    global $db, $table_prefix;
    
    $sql = 'SELECT COUNT(*) as total
            FROM ' . $table_prefix . 'post_reactions
            WHERE reaction_notified = 0';
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    
    $count = (int) $row['total'];
    echo "Réactions non notifiées : " . $count . "\n";
    
    if ($count === 0) {
        echo "💡 Aucune réaction en attente → testez en ajoutant une réaction sur un post\n";
    } else {
        echo "✅ Des réactions attendent d'être envoyées par email\n";
        
        // Afficher les 5 premières
        $sql = 'SELECT r.reaction_id, r.post_id, r.reaction_emoji, r.reaction_time,
                       u.username
                FROM ' . $table_prefix . 'post_reactions r
                LEFT JOIN ' . USERS_TABLE . ' u ON r.user_id = u.user_id
                WHERE r.reaction_notified = 0
                ORDER BY r.reaction_time DESC
                LIMIT 5';
        $result = $db->sql_query($sql);
        
        echo "\nExemples (max 5) :\n";
        while ($row = $db->sql_fetchrow($result)) {
            echo "  • Post #{$row['post_id']} : {$row['reaction_emoji']} par {$row['username']} (" . date('Y-m-d H:i:s', $row['reaction_time']) . ")\n";
        }
        $db->sql_freeresult($result);
    }
    echo "\n";

    // ========== RÉSUMÉ ==========
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  DIAGNOSTIC TERMINÉ                                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "💡 PROCHAINES ÉTAPES :\n";
    echo "   1. Vérifiez que tous les fichiers ci-dessus existent et ne sont PAS vides\n";
    echo "   2. Si des réactions sont en attente, lancez manuellement le cron :\n";
    echo "      php bin/phpbbcli.php cron:run bastien59960.reactions.notification -vvv\n";
    echo "   3. Surveillez les logs : tail -f /var/log/apache2/error.log\n";
    echo "   4. Si get_name() retourne vide, corrigez cron/notification_task.php\n\n";

} catch (\Throwable $e) {
    echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ ERREUR FATALE                                             ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "Type : " . get_class($e) . "\n";
    echo "Message : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace :\n" . $e->getTraceAsString() . "\n\n";
    echo "💡 Si l'erreur concerne common.php, vérifiez les permissions et config.php\n";
}