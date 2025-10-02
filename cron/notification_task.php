<?php
/**
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/
namespace bastien59960\reactions\cron;

class notification_task extends \phpbb\cron\task\base
{
    // ... Le constructeur ne change pas ...
    protected $db;
    protected $config;
    protected $notification_manager;
    protected $post_reactions_table;

    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\notification\manager $notification_manager, $post_reactions_table)
    {
        $this->db = $db;
        $this->config = $config;
        $this->notification_manager = $notification_manager;
        $this->post_reactions_table = $post_reactions_table;
    }


    /**
     * Exécute la tâche.
     * LOGIQUE ENTIÈREMENT CORRIGÉE POUR LES TIMESTAMPS UNIX
     */
    public function run()
    {
        $spam_time_minutes = (int) $this->config['bastien59960_reactions_spam_time'];
        if ($spam_time_minutes <= 0)
        {
            return;
        }

        // 1. Calculer le timestamp seuil en PHP. C'est plus propre.
        // time() donne le timestamp Unix actuel.
        $threshold_timestamp = time() - ($spam_time_minutes * 60);

        // 2. On récupère les réactions non notifiées et plus vieilles que le seuil
        $sql = 'SELECT r.reaction_id, r.post_id, r.user_id, p.post_author
                FROM ' . $this->post_reactions_table . ' r
                LEFT JOIN ' . POSTS_TABLE . ' p ON (r.post_id = p.post_id)
                WHERE r.reaction_notified = 0
                  AND r.reaction_time < ' . $threshold_timestamp; // La comparaison se fait entre deux nombres

        $result = $this->db->sql_query($sql);

        $reactions_to_notify = [];
        $notified_reaction_ids = [];

        while ($row = $this->db->sql_fetchrow($result))
        {
            // 3. On groupe les réactions par post pour envoyer une seule notification
            $reactions_to_notify[$row['post_id']]['reactions'][] = $row;
            $reactions_to_notify[$row['post_id']]['post_author'] = $row['post_author'];
            $notified_reaction_ids[] = (int) $row['reaction_id'];
        }
        $this->db->sql_freeresult($result);

        if (empty($reactions_to_notify)) {
            return; // Rien à faire
        }

        // 4. On envoie les notifications groupées (cette partie ne change pas)
        foreach ($reactions_to_notify as $post_id => $data)
        {
            $author_id = (int) $data['post_author'];
            // Sécurité : ne pas s'auto-notifier et vérifier que l'auteur existe
            if (!$author_id || in_array($author_id, array_column($data['reactions'], 'user_id')))
            {
                continue;
            }

            $notification_data = [
                'post_id'       => $post_id,
                'reacter_ids'   => array_unique(array_column($data['reactions'], 'user_id')),
                'reaction_count'=> count($data['reactions']),
                'post_author'   => $author_id, // On passe l'ID de l'auteur directement
            ];

            $this->notification_manager->add_notifications('bastien59960.reactions.notification.type.reaction', $notification_data);
        }

        // 5. On marque les réactions comme notifiées dans la base de données (cette partie ne change pas)
        if (!empty($notified_reaction_ids))
        {
            $sql = 'UPDATE ' . $this->post_reactions_table . '
                    SET reaction_notified = 1
                    WHERE ' . $this->db->sql_in_set('reaction_id', $notified_reaction_ids);
            $this->db->sql_query($sql);
        }
    }

    // Les fonctions is_runnable() et should_run() ne changent pas
    public function is_runnable()
    {
        return (int) $this->config['bastien59960_reactions_spam_time'] > 0;
    }

    public function should_run()
    {
        return true;
    }
}
