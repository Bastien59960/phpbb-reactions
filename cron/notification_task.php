<?php
/**
 * Notification cron task for Reactions extension
 *
 * Envoie une notification groupée par post aux auteurs de messages
 * et marque les réactions comme "notified".
 *
 * @copyright (c) 2025
 * @license GNU GPL v2
 */

namespace bastien59960\reactions\cron;

class notification_task extends \phpbb\cron\task\base
{
    protected $db;
    protected $config;
    protected $notification_manager;
    protected $post_reactions_table;
    protected $user_loader;
    protected $phpbb_root_path;
    protected $php_ext;

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

    public function get_name()
    {
        return 'cron.task.reactions.notification_task';
    }


    /**
     * Si l'admin a défini le temps anti-spam > 0 on lance la tâche.
     */
    public function is_runnable()
    {
        return isset($this->config['bastien59960_reactions_spam_time']) &&
               (int) $this->config['bastien59960_reactions_spam_time'] > 0;
    }

    public function should_run()
    {
        // phpBB décidera de l'intervalle. On retourne true si runnable.
        return true;
    }

    public function run()
    {
        // delay (en secondes) avant notification (anti-spam window)
        $spam_delay = (int) $this->config['bastien59960_reactions_spam_time'];

        if ($spam_delay <= 0)
        {
            // rien à faire si la config est à 0
            return;
        }

        $threshold_timestamp = time() - $spam_delay;

        // 1) Récupérer toutes les réactions non notifiées plus anciennes que le seuil
        $sql = 'SELECT r.reaction_id, r.post_id, r.user_id AS reacter_id, p.poster_id AS author_id
                FROM ' . $this->post_reactions_table . ' r
                LEFT JOIN ' . POSTS_TABLE . ' p ON (r.post_id = p.post_id)
                WHERE r.reaction_notified = 0
                  AND r.reaction_time < ' . $threshold_timestamp . '
                ORDER BY r.post_id, r.reaction_time ASC';

        $result = $this->db->sql_query($sql);

        $grouped = [];
        $mark_ids = [];

        while ($row = $this->db->sql_fetchrow($result))
        {
            $post_id    = (int) $row['post_id'];
            $author_id  = isset($row['author_id']) ? (int) $row['author_id'] : 0;
            $reacter_id = (int) $row['reacter_id'];
            $reaction_id = (int) $row['reaction_id'];

            // regrouper par post (et par auteur de post)
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

        if (empty($grouped))
        {
            // rien à notifier
            return;
        }

        // 2) Pour chaque groupe, envoyer une notification au propriétaire du post
        foreach ($grouped as $post_id => $data)
        {
            $author_id = (int) $data['author_id'];

            // si pas d'auteur connu, skip
            if ($author_id <= 0)
            {
                continue;
            }

            // construire les données de notification
            $notification_data = [
                'post_id'     => $post_id,
                'reacter_ids' => array_values(array_unique($data['reacter_ids'])),
            ];

            try
            {
                // utiliser notification manager pour créer la notification
                $this->notification_manager->add_notifications(
                    'bastien59960.reaction',
                    [$author_id],
                    $notification_data
                );
            }
            catch (\Exception $e)
            {
                // éviter d'interrompre le cron si une notification échoue
                continue;
            }
        }

        // 3) Marquer toutes les réactions traitées comme notifiées
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
