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
    
    // Forcer le rechargement des types de notifications
    echo "Rechargement des types de notifications...\n";
    
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

