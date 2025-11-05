<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /test_autoload.php
 * Rôle : Script de diagnostic pour tester le chargement automatique (autoload)
 * des classes de l'extension. Il vérifie que l'autoloader de phpBB peut
 * trouver et charger correctement les classes principales de l'extension.
 */

define('IN_PHPBB', true);
$phpbb_root_path = __DIR__ . '/';
$phpEx = 'php';

require($phpbb_root_path . 'common.' . $phpEx);

echo "=== TEST DE CHARGEMENT DES CLASSES ===\n\n";

$classes_to_test = [
    'bastien59960\reactions\cron\test_task',
    'bastien59960\reactions\cron\notification_task',
    'bastien59960\reactions\notification\type\reaction',
    'bastien59960\reactions\notification\type\reaction_email_digest',
    'bastien59960\reactions\controller\helper',
    'bastien59960\reactions\event\listener',
];

foreach ($classes_to_test as $class) {
    echo "Test de la classe: $class\n";
    
    try {
        if (class_exists($class)) {
            echo "  ✅ Classe trouvée et chargeable\n";
            
            // Essayer de récupérer les infos sur le constructeur
            $reflection = new ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            
            if ($constructor) {
                echo "  📋 Paramètres du constructeur:\n";
                foreach ($constructor->getParameters() as $param) {
                    $type = $param->hasType() ? $param->getType() : 'pas de type';
                    $name = $param->getName();
                    $optional = $param->isOptional() ? ' (optionnel)' : '';
                    echo "    - $name: $type$optional\n";
                }
            }
        } else {
            echo "  ❌ Classe introuvable\n";
        }
    } catch (Throwable $e) {
        echo "  ❌ ERREUR: " . $e->getMessage() . "\n";
        echo "  📍 Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
    }
    
    echo "\n";
}

echo "=== FIN DU TEST ===\n";
