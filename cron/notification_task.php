<?php
/**
 * Tâche cron pour les notifications de réactions
 * 
 * Cette tâche cron gère l'envoi des notifications par email avec délai anti-spam.
 * Elle vérifie les réactions non notifiées plus anciennes que le délai configuré
 * et envoie les emails groupés par message.
 * 
 * Fonctionnalités :
 * - Vérification des réactions non notifiées
 * - Respect du délai anti-spam configuré (45 min par défaut)
 * - Envoi d'emails groupés par message
 * - Marquage des réactions comme notifiées
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\cron;

/**
 * Tâche cron pour les notifications de réactions
 * 
 * Gère l'envoi des notifications par email avec délai anti-spam.
 * Cette tâche est exécutée périodiquement par le système cron de phpBB.
 */
class notification_task extends \phpbb\cron\task\base
{
    // =============================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =============================================================================
    
    /** @var \phpbb\db\driver\driver_interface Connexion à la base de données */
    protected $db;
    
    /** @var \phpbb\config\config Configuration du forum */
    protected $config;
    
    /** @var \phpbb\notification\manager Gestionnaire de notifications */
    protected $notification_manager;
    
    /** @var string Nom de la table des réactions */
    protected $post_reactions_table;
    
    /** @var \phpbb\user_loader Chargeur d'utilisateurs */
    protected $user_loader;
    
    /** @var string Chemin racine du forum */
    protected $phpbb_root_path;
    
    /** @var string Extension des fichiers PHP */
    protected $php_ext;

    // =============================================================================
    // CONSTRUCTEUR
    // =============================================================================
    
    /**
     * Constructeur de la tâche cron
     * 
     * Initialise tous les services nécessaires pour gérer les notifications de réactions.
     * 
     * @param \phpbb\db\driver\driver_interface $db Connexion base de données
     * @param \phpbb\config\config $config Configuration du forum
     * @param \phpbb\notification\manager $notification_manager Gestionnaire de notifications
     * @param \phpbb\user_loader $user_loader Chargeur d'utilisateurs
     * @param string $post_reactions_table Nom de la table des réactions
     * @param string $phpbb_root_path Chemin racine du forum
     * @param string $php_ext Extension des fichiers PHP
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\config\config $config,
        \phpbb\notification\manager $notification_manager,
        \phpbb\user_loader $user_loader,
        $post_reactions_table,
        $phpbb_root_path,
        $php_ext
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->notification_manager = $notification_manager;
        $this->user_loader = $user_loader;
        $this->post_reactions_table = $post_reactions_table;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
    }

    // =============================================================================
    // MÉTHODES REQUISES PAR LE SYSTÈME CRON
    // =============================================================================
    
    /**
     * Récupérer le nom de la tâche cron
     * 
     * Cette méthode est requise par le système cron de phpBB.
     * Elle retourne un identifiant unique pour cette tâche.
     * 
     * @return string Nom de la tâche cron
     */
    public function get_name()
    {
        return 'cron.task.reactions.notification_task';
    }

    /**
     * Vérifier si la tâche peut être exécutée
     * 
     * Cette méthode vérifie si la tâche peut être exécutée.
     * Elle retourne true si le délai anti-spam est configuré et > 0.
     * 
     * @return bool True si la tâche peut être exécutée, False sinon
     */
    public function is_runnable()
    {
        return isset($this->config['bastien59960_reactions_spam_time']) &&
               (int) $this->config['bastien59960_reactions_spam_time'] > 0;
    }

    /**
     * Vérifier si la tâche doit être exécutée
     * 
     * Cette méthode est appelée par le système cron pour déterminer
     * si la tâche doit être exécutée maintenant.
     * 
     * @return bool True si la tâche doit être exécutée, False sinon
     */
    public function should_run()
    {
        // phpBB décidera de l'intervalle. On retourne true si runnable.
        return true;
    }

    // =============================================================================
    // MÉTHODE PRINCIPALE D'EXÉCUTION
    // =============================================================================
    
    /**
     * Exécuter la tâche cron
     * 
     * Cette méthode est appelée par le système cron de phpBB.
     * Elle traite les réactions non notifiées et envoie les emails avec délai anti-spam.
     * 
     * Processus :
     * 1. Récupérer les réactions non notifiées plus anciennes que le délai configuré
     * 2. Grouper les réactions par message et auteur
     * 3. Envoyer les notifications par email groupées
     * 4. Marquer les réactions comme notifiées
     * 
     * @return void
     */
    public function run()
    {
        // Récupérer le délai anti-spam configuré (en secondes)
        $spam_delay = (int) $this->config['bastien59960_reactions_spam_time'];

        // Vérifier que le délai anti-spam est configuré
        if ($spam_delay <= 0)
        {
            // Rien à faire si la configuration est à 0
            return;
        }

        // Calculer le timestamp seuil (réactions plus anciennes que ce seuil peuvent être notifiées)
        $threshold_timestamp = time() - $spam_delay;

        // =====================================================================
        // 1. RÉCUPÉRATION DES RÉACTIONS NON NOTIFIÉES
        // =====================================================================
        
        // Récupérer toutes les réactions non notifiées plus anciennes que le seuil
        $sql = 'SELECT r.reaction_id, r.post_id, r.user_id AS reacter_id, p.poster_id AS author_id
                FROM ' . $this->post_reactions_table . ' r
                LEFT JOIN ' . POSTS_TABLE . ' p ON (r.post_id = p.post_id)
                WHERE r.reaction_notified = 0
                  AND r.reaction_time < ' . $threshold_timestamp . '
                ORDER BY r.post_id, r.reaction_time ASC';

        $result = $this->db->sql_query($sql);

        $grouped = [];  // Réactions groupées par message
        $mark_ids = []; // IDs des réactions à marquer comme notifiées

        // Traiter chaque réaction
        while ($row = $this->db->sql_fetchrow($result))
        {
            $post_id    = (int) $row['post_id'];
            $author_id  = isset($row['author_id']) ? (int) $row['author_id'] : 0;
            $reacter_id = (int) $row['reacter_id'];
            $reaction_id = (int) $row['reaction_id'];

            // Grouper par message (et par auteur de message)
            if (!isset($grouped[$post_id]))
            {
                $grouped[$post_id] = [
                    'post_id'      => $post_id,
                    'author_id'    => $author_id,
                    'reacter_ids'  => [],
                    'reaction_ids' => [],
                ];
            }

            $grouped[$post_id]['reacter_ids'][] = $reacter_id;
            $grouped[$post_id]['reaction_ids'][] = $reaction_id;
            $mark_ids[] = $reaction_id;
        }
        $this->db->sql_freeresult($result);

        // Vérifier s'il y a des réactions à traiter
        if (empty($grouped))
        {
            // Rien à notifier
            return;
        }

        // =====================================================================
        // 2. ENVOI DES NOTIFICATIONS PAR EMAIL
        // =====================================================================
        
        // Pour chaque groupe de réactions, envoyer une notification à l'auteur du message
        foreach ($grouped as $post_id => $data)
        {
            $author_id = (int) $data['author_id'];

            // Ignorer si l'auteur n'est pas connu
            if ($author_id <= 0)
            {
                continue;
            }

            // Construire les données de notification
            $notification_data = [
                'post_id'     => $post_id,
                'reacter_ids' => array_values(array_unique($data['reacter_ids'])),
            ];

            try
            {
                // Envoyer uniquement les emails avec anti-spam
                // Les notifications par cloche sont déjà envoyées immédiatement
                $this->notification_manager->add_notifications(
                    'bastien59960.reactions.notification',
                    [$author_id],
                    $notification_data
                );
            }
            catch (\Exception $e)
            {
                // Éviter d'interrompre le cron si une notification échoue
                continue;
            }
        }

        // =====================================================================
        // 3. MARQUAGE DES RÉACTIONS COMME NOTIFIÉES
        // =====================================================================
        
        // Marquer toutes les réactions traitées comme notifiées
        if (!empty($mark_ids))
        {
            $mark_ids = array_map('intval', $mark_ids);
            $sql = 'UPDATE ' . $this->post_reactions_table . '
                    SET reaction_notified = 1
                    WHERE ' . $this->db->sql_in_set('reaction_id', $mark_ids);
            $this->db->sql_query($sql);
        }
    }
}
