<?php
/**
 * Script pour forcer phpBB à recharger les types de notifications
 * 
 * Ce script force phpBB à reconstruire le cache du container DI et à
 * recharger les types de notifications depuis services.yml.
 * 
 * Usage: php reload-notification-types.php
 */

define('IN_PHPBB', true);

// Calculer le chemin vers la racine du forum
// Le script est dans ext/bastien59960/reactions/
// Il faut remonter de 3 niveaux : reactions/ -> bastien59960/ -> ext/ -> racine
$script_dir = dirname(__FILE__);
$phpbb_root_path = realpath($script_dir . '/../../../') . '/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

if (getenv('PHPBB_ROOT_PATH')) {
    $phpbb_root_path = getenv('PHPBB_ROOT_PATH');
    if (substr($phpbb_root_path, -1) !== '/') {
        $phpbb_root_path .= '/';
    }
}

try {
    require($phpbb_root_path . 'common.' . $phpEx);
    
    // Démarrer la session
    $user->session_begin();
    $auth->acl($user->data);
    $user->setup();
    
    // Récupérer le gestionnaire de notifications depuis le container
    global $phpbb_container;
    $notification_manager = $phpbb_container->get('notification_manager');
    
    // Vérifier que le service est bien enregistré dans le container
    echo "Vérification de l'enregistrement des services...\n";
    
    // Vérifier si le service existe dans le container
    $service_found = false;
    try {
        $reaction_service = $phpbb_container->get('bastien59960.reactions.notification.type.reaction');
        $type_name = $reaction_service->get_type();
        echo "✅ Service 'bastien59960.reactions.notification.type.reaction' trouvé dans le container\n";
        echo "   get_type() retourne : '$type_name'\n";
        $service_found = true;
    } catch (\Exception $e) {
        echo "❌ ERREUR : Service 'bastien59960.reactions.notification.type.reaction' NON trouvé dans le container\n";
        echo "   Message : " . $e->getMessage() . "\n";
        echo "   Le service doit être enregistré dans services.yml avec le tag 'notification.type.driver'\n";
        
        // Essayer de lister tous les services avec le tag notification.type.driver
        echo "\n   Tentative de liste des services avec tag 'notification.type.driver'...\n";
        try {
            // Cette méthode peut ne pas exister, donc on l'essaie dans un try/catch
            if (method_exists($phpbb_container, 'findTaggedServiceIds')) {
                $tagged_services = $phpbb_container->findTaggedServiceIds('notification.type.driver');
                echo "   Services trouvés avec tag 'notification.type.driver' : " . count($tagged_services) . "\n";
                foreach ($tagged_services as $service_id) {
                    echo "     - $service_id\n";
                }
            }
        } catch (\Exception $e2) {
            // Ignorer si la méthode n'existe pas
        }
    }
    
    // Forcer le rechargement des types de notifications
    echo "\nRechargement des types de notifications...\n";
    
    try {
        $notification_manager->enable_notifications('bastien59960.reactions.notification.type.reaction');
        echo "✅ Type 'reaction' réenregistré\n";
    } catch (\Exception $e) {
        echo "⚠️  Type 'reaction' : " . $e->getMessage() . "\n";
    }
    
    try {
        $notification_manager->enable_notifications('bastien59960.reactions.notification.type.reaction_email_digest');
        echo "✅ Type 'reaction_email_digest' réenregistré\n";
    } catch (\Exception $e) {
        echo "⚠️  Type 'reaction_email_digest' : " . $e->getMessage() . "\n";
    }
    
    // Vérifier à nouveau que le service est accessible après enable_notifications
    try {
        $reaction_service = $phpbb_container->get('bastien59960.reactions.notification.type.reaction');
        $type_name = $reaction_service->get_type();
        echo "✅ Service accessible après enable_notifications (get_type() = '$type_name')\n";
    } catch (\Exception $e) {
        echo "❌ ERREUR : Service toujours inaccessible après enable_notifications\n";
        echo "   Message : " . $e->getMessage() . "\n";
    }
    
    // Vider le cache pour forcer la reconstruction du container
    // Essayer plusieurs services de cache possibles
    try {
        $cache = $phpbb_container->get('cache');
        $cache->purge();
        echo "✅ Cache vidé (via service 'cache')\n";
    } catch (\Exception $e) {
        try {
            $cache = $phpbb_container->get('cache.driver');
            $cache->purge();
            echo "✅ Cache vidé (via service 'cache.driver')\n";
        } catch (\Exception $e2) {
            echo "⚠️  Impossible de vider le cache directement, utilisez 'phpbbcli cache:purge'\n";
        }
    }
    
    echo "✅ Types de notifications rechargés avec succès\n";
    
} catch (Exception $e) {
    die("ERREUR : " . $e->getMessage() . "\n");
}

