<?php
/**
 * Script de diagnostic pour forcer la compilation du conteneur et afficher les erreurs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// --- Détection robuste du chemin racine de phpBB ---
$current_dir = __DIR__;
$phpbb_root_path = '';
for ($i = 0; $i < 5; $i++) { // Limite de 5 niveaux pour éviter une boucle infinie
    if (file_exists($current_dir . '/common.php')) {
        $phpbb_root_path = $current_dir . '/';
        break;
    }
    $current_dir = dirname($current_dir);
}

if (empty($phpbb_root_path)) {
    die("ERREUR FATALE: Impossible de trouver la racine du forum (common.php introuvable).\n");
}

define('IN_PHPBB', true);
$phpEx = 'php';

echo "=== DIAGNOSTIC DU CONTENEUR DE SERVICES ===\n\n";
echo "Chemin racine phpBB détecté : " . $phpbb_root_path . "\n\n";

try {
    echo "1. Initialisation de l'environnement de base (sans charger le conteneur en cache)...\n";
    // On charge uniquement les fichiers nécessaires pour construire un conteneur manuellement
    require($phpbb_root_path . 'config.' . $phpEx);
    require($phpbb_root_path . 'vendor/autoload.' . $phpEx);
    require($phpbb_root_path . 'includes/constants.' . $phpEx);
    require($phpbb_root_path . 'phpbb/di/extension/core.' . $phpEx); // ✅ CORRECTION : Charger la classe de base des extensions
    echo "   ✅ Environnement de base initialisé.\n\n";

    echo "2. Construction manuelle d'un NOUVEAU conteneur de services...\n";
    // On utilise le constructeur de conteneur de phpBB pour simuler une purge complète.
    // Le 'false' en 4ème argument force la reconstruction et ignore le cache.
    $builder = new \phpbb\di\container_builder(
        $phpbb_root_path . 'config/services.yml',
        $phpbb_root_path . 'cache/production',
        $phpbb_root_path . 'config/parameters.yml',
        false // <-- C'est la clé : on force la reconstruction !
    );
    // On récupère le conteneur non compilé (le ContainerBuilder)
    $phpbb_container = $builder->get_container_builder();
    echo "   ✅ Nouveau ContainerBuilder créé.\n\n";

    echo "3. Compilation du nouveau conteneur...\n";
    try {
        // C'est ici que les erreurs de syntaxe YAML ou de dépendances seront trouvées.
        $phpbb_container->compile();
        echo "   ✅ Conteneur compilé avec succès\n\n";
    } catch (\Exception $e) {
        echo "   ❌ ERREUR DE COMPILATION:\n";
        echo "   Message: " . htmlspecialchars($e->getMessage()) . "\n";
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
        // 'cron.task.bastien59960.reactions.notification_task', // Commenté car désactivé dans services.yml pour le moment
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