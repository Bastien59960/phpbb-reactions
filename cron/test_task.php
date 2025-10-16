<?php
/**
 * Fichier : cron/test_task.php — bastien59960/reactions
 *
 * Tâche cron de test minimaliste.
 *
 * Rôle :
 * Cette tâche sert uniquement à vérifier si le système de cron de phpBB
 * enregistre et exécute correctement une nouvelle tâche de cette extension.
 * Elle n'a aucune dépendance pour éviter tout conflit.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
namespace bastien59960\reactions\cron;

if (!defined('IN_PHPBB'))
{
    exit;
}

class test_task extends \phpbb\cron\task\base
{
    /**
     * Méthode principale exécutée par le cron.
     * Écrit un message dans le log d'erreurs PHP pour confirmer son exécution.
     */
    public function run()
    {
        error_log('[Reactions Test Cron] La tâche de test a été exécutée avec succès.');
    }

    /**
     * Indique si la tâche doit s'exécuter. Retourne toujours `true` pour être "prête".
     */
    public function should_run()
    {
        return true;
    }
}