<?php
/**
 * Script de diagnostic pour forcer la compilation du conteneur et afficher les erreurs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('IN_PHPBB', true);
$phpbb_root_path = __DIR__ . '/';
$phpEx = 'php';

echo "=== DIAGNOSTIC DU CONTENEUR DE SERVICES ===\n\n";

try {
    echo "1. Chargement de common.php...\n";
    require($phpbb_root_path . 'common.' . $phpEx);
    echo "   ✅ common.php chargé\n\n";

    echo "2. Récupération du conteneur...\n";
    global $phpbb_container;
    
    if (!$phpbb_container) {
        die("   ❌ ERREUR: Le conteneur est NULL!\n");
    }
    echo "   ✅ Conteneur récupéré\n\n";

    echo "3. Test de compilation forcée...\n";
    try {
        $phpbb_container->compile();
        echo "   ✅ Conteneur compilé avec succès\n\n";
    } catch (\Exception $e) {
        echo "   ❌ ERREUR DE COMPILATION:\n";
        echo "   Message: " . $e->getMessage() . "\n";
        echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Trace:\n" . $e->getTraceAsString() . "\n\n";
        die();
    }

    echo "4. Liste des services de l'extension...\n";
    $services_to_test = [
        'bastien59960.reactions.helper',
        'bastien59960.reactions.listener',
        'bastien59960.reactions.ajax',
        'cron.task.bastien59960.reactions.test_task',
        'cron.task.bastien59960.reactions.notification_task',
        'bastien59960.reactions.notification.type.reaction',
        'bastien59960.reactions.notification.type.reaction_email_digest',
    ];

    foreach ($services_to_test as $service_id) {
        echo "   - Test de $service_id... ";
        try {
            if ($phpbb_container->has($service_id)) {
                $service = $phpbb_container->get($service_id);
                echo "✅ OK (" . get_class($service) . ")\n";
            } else {
                echo "❌ SERVICE INTROUVABLE\n";
            }
        } catch (\Exception $e) {
            echo "❌ ERREUR: " . $e->getMessage() . "\n";
        }
    }

    echo "\n5. Liste de TOUS les services cron...\n";
    $all_services = $phpbb_container->getServiceIds();
    $cron_services = array_filter($all_services, function($id) {
        return strpos($id, 'cron.task') === 0;
    });
    
    foreach ($cron_services as $cron_id) {
        echo "   - $cron_id\n";
    }

    echo "\n=== FIN DU DIAGNOSTIC ===\n";

} catch (\Throwable $e) {
    echo "\n❌ ERREUR FATALE:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}