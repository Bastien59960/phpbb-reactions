<?php
/**
 * Fichier : test_task.php
 * Chemin : bastien59960/reactions/cron/test_task.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 * 
 * Rôle : 
 * Tâche cron de test pour l'extension Reactions.
 * Cette tâche est conçue pour valider que le système de cron de phpBB
 * enregistre, nomme et exécute correctement les tâches de cette extension.
 * 
 * Logique :
 * - S'exécute au maximum une fois toutes les 24 heures.
 * - Écrit une entrée dans le journal d'administration de phpBB pour confirmer son exécution.
 * - Stocke le timestamp de sa dernière exécution dans la table de configuration.
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\cron;

if (!defined('IN_PHPBB')) {
    exit;
}

class test_task extends \phpbb\cron\task\base
{
    /**
     * @var \phpbb\config\config
     */
    protected $config;

    /**
     * @var \phpbb\log\log_interface
     */
    protected $log;

    /**
     * Constructeur de la tâche cron.
     * L'injection de dépendances est gérée par config/services.yml.
     *
     * @param \phpbb\config\config $config Service de configuration de phpBB.
     * @param \phpbb\log\log_interface $log Service de journalisation de phpBB.
     */
    public function __construct(\phpbb\config\config $config, \phpbb\log\log_interface $log)
    {
        $this->config = $config;
        $this->log = $log;
    }

    /**
     * Retourne le nom de la tâche (clé de langue).
     * C'est cette clé qui est utilisée pour afficher le nom dans l'ACP.
     *
     * @return string
     */
    public function get_name()
    {
        return 'CRON_TASK_BASTIEN_REACTIONS_TEST';
    }

    /**
     * Méthode principale exécutée par le cron.
     * Log l'exécution dans le journal d'administration et met à jour le timestamp.
     */
    public function run()
    {
        // Ajoute une entrée dans le journal d'administration.
        // Le 'false' en 4ème argument indique que la clé de langue n'a pas de paramètres.
        $this->log->add('admin', 0, '', 'LOG_REACTIONS_CRON_TEST_RUN');

        // Met à jour la date de dernière exécution dans la config.
        // Le 'false' en 3ème argument empêche la purge du cache, non nécessaire ici.
        $this->config->set('bastien59960_reactions_last_test_run', time(), false);
    }

    /**
     * Détermine si la tâche peut s'exécuter (conditions de base).
     * Doit retourner true pour que should_run() soit évalué.
     *
     * @return bool
     */
    public function is_runnable()
    {
        return true;
    }

    /**
     * Contrôle la fréquence d'exécution de la tâche.
     * La tâche ne s'exécutera que si 24 heures (86400s) se sont écoulées
     * depuis la dernière exécution.
     *
     * @return bool
     */
    public function should_run()
    {
        // Récupère la date de dernière exécution (0 si elle n'existe pas).
        $last_run = (int) ($this->config['bastien59960_reactions_last_test_run'] ?? 0);
        
        // Retourne true si le dernier lancement date de plus de 24 heures.
        return $last_run < (time() - 86400);
    }
}