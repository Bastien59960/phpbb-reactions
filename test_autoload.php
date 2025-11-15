<?php
/**
 * Fichier : test_autoload.php
 * Chemin : bastien59960/reactions/test_autoload.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Script de diagnostic pour tester le chargement automatique (autoloading) des
 * classes de l'extension. Il vérifie que l'autoloader de Composer configuré
 * par phpBB est capable de trouver et de charger correctement les classes
 * critiques de l'extension (contrôleurs, tâches cron, notifications, etc.),
 * ce qui est essentiel pour le bon fonctionnement de l'injection de dépendances.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
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
