<?php
/**
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/
namespace bastien59960\reactions\notification\type;

class reaction extends \phpbb\notification\type\base
{
    protected $user_loader;
    protected $db;
    protected $phpbb_root_path;
    protected $user;

    public function __construct(\phpbb\user_loader $user_loader, \phpbb\db\driver\driver_interface $db, $phpbb_root_path, \phpbb\user $user)
    {
        $this->user_loader = $user_loader;
        $this->db = $db;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->user = $user;
    }
    
    /**
     * Le nom du type de notification.
     */
    public static $notification_type = 'bastien59960.reactions.notification';

    /**
     * Les données requises pour cette notification.
     */
    public function get_type()
    {
        return self::$notification_type;
    }

    /**
     * Trouve les utilisateurs à notifier.
     */
    public function find_users_for_notification($data, $options = [])
    {
        // On notifie uniquement l'auteur du message.
        return [$data['post_author']];
    }
    
    /**
     * Récupère le titre de la notification (pour l'email).
     */
    public function get_email_template_variables()
    {
        $reacter_names = $this->get_reacter_names($this->notification_data['reacter_ids']);
        $post_title = $this->get_post_title($this->notification_data['post_id']);

        return [
            'TITLE' => $this->user->lang('REACTIONS_NOTIFICATION_EMAIL_SUBJECT', $reacter_names, $post_title),
            'REACTOR_NAMES' => $reacter_names,
            'POST_TITLE' => $post_title,
            'U_POST_LINK' => $this->get_url(),
        ];
    }

    /**
     * Récupère le message de la notification.
     */
    public function get_title()
    {
        $count = (int) $this->notification_data['reaction_count'];
        $reacter_names = $this->get_reacter_names($this->notification_data['reacter_ids']);
        
        // Utilise le système de pluriels de phpBB
        return $this->user->lang('REACTIONS_NOTIFICATION_TITLE', $reacter_names, $count);
    }

    /**
     * Récupère le lien de la notification.
     */
    public function get_url()
    {
        return append_sid($this->phpbb_root_path . 'viewtopic.php', 'p=' . $this->notification_data['post_id'] . '#p' . $this->notification_data['post_id']);
    }

    /**
     * Fonction pour créer le message.
     */
    public static function get_item_id($data)
    {
        return (int) $data['post_id'];
    }

    /**
     * Fonction pour créer l'ID de l'utilisateur.
     */
    public static function get_item_parent_id($data)
    {
        return (int) $data['post_author'];
    }

    /**
     * Fonction pour créer l'ID du forum.
     */
    public function get_forum_id()
    {
        $sql = 'SELECT forum_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $this->notification_data['post_id'];
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return $row ? (int) $row['forum_id'] : 0;
    }

    /**
     * Fonction pour créer l'ID du topic.
     */
    public function get_topic_id()
    {
        $sql = 'SELECT topic_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $this->notification_data['post_id'];
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return $row ? (int) $row['topic_id'] : 0;
    }

    /**
     * Fonction pour créer l'ID de l'utilisateur parent.
     */
    public static function get_item_parent_id($data)
    {
        return (int) $data['post_author'];
    }

    /**
     * Fonction pour créer l'ID de l'utilisateur parent.
     */
    public function get_item_parent_id()
    {
        return (int) $this->notification_data['post_author'];
    }
    
    /**
     * Récupère le titre du post
     */
    private function get_post_title($post_id)
    {
        $sql = 'SELECT post_subject FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return $row ? $row['post_subject'] : '';
    }
    
    /**
     * Helper pour charger les noms des utilisateurs ayant réagi.
     */
    private function get_reacter_names($user_ids)
    {
        // Limite à 3 noms pour ne pas surcharger la notification
        $user_ids = array_slice($user_ids, 0, 3);
        $this->user_loader->load_users($user_ids);
        
        $names = [];
        foreach ($user_ids as $user_id) {
            $names[] = $this->user_loader->get_username($user_id, 'username');
        }
        
        $others = count($this->notification_data['reacter_ids']) - count($names);
        if ($others > 0) {
            return $this->user->lang('REACTIONS_NOTIFICATION_AND_OTHERS', implode(', ', $names), $others);
        }
        
        return implode(', ', $names);
    }
}
