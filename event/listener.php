<?php
/**
 * Reactions Extension Listener for phpBB 3.3+ (CORRIGÉ)
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\db\driver\driver_interface;

class listener implements EventSubscriberInterface
{
    /** @var driver_interface */
    protected $db;

    /** @var \phpbb\user */
    protected $user;

    /** @var string */
    protected $post_reactions_table;

    /** @var string */
    protected $posts_table;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\language\language */
    protected $language;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /** @var array Liste des 10 émojis courantes selon le cahier des charges */
    protected $common_emojis = [
        '👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '👌', '🥳'
    ];

    public function __construct(
        driver_interface $db,
        \phpbb\user $user,
        $post_reactions_table,
        $posts_table,
        \phpbb\template\template $template,
        \phpbb\language\language $language,
        \phpbb\controller\helper $helper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->post_reactions_table = $post_reactions_table;
        $this->posts_table = $posts_table;
        $this->template = $template;
        $this->language = $language;
        $this->helper = $helper;

        try {
            $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");
        } catch (\Exception $e) {
            error_log('[phpBB Reactions] Could not set names: ' . $e->getMessage());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'core.page_header'               => 'add_assets_to_page',
            'core.viewtopic_cache_user_data' => 'load_language_and_data',
            'core.viewtopic_post_row_after'  => 'display_reactions',
            'core.viewforum_modify_topicrow' => 'add_forum_data',
        ];
    }

    public function add_assets_to_page($event)
    {
        $this->language->add_lang('common', 'bastien59960/reactions');

        $css_path = './ext/bastien59960/reactions/styles/prosilver/theme/reactions.css';
        $js_path  = './ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js';

        try {
            $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);
        } catch (\Exception $e) {
            $ajax_url = append_sid('app.php/reactions/ajax');
        }

        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,
            'REACTIONS_CSS_PATH'  => $css_path,
            'REACTIONS_JS_PATH'   => $js_path,
            'U_REACTIONS_AJAX'    => $ajax_url,
            'S_SESSION_ID'        => isset($this->user->data['session_id']) ? $this->user->data['session_id'] : '',
        ]);

        $this->template->assign_var(
            'REACTIONS_AJAX_URL_JS',
            'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '"; window.REACTIONS_SID = "' . addslashes(isset($this->user->data['session_id']) ? $this->user->data['session_id'] : '') . '";'
        );
    }

    public function load_language_and_data($event)
    {
        // Placeholder
    }

    /**
     * CORRECTION MAJEURE : Affiche uniquement les réactions existantes avec count > 0
     */
    public function display_reactions($event)
    {
        $post_row = isset($event['post_row']) ? $event['post_row'] : [];
        $row      = isset($event['row']) ? $event['row'] : [];
        $post_id  = isset($row['post_id']) ? (int) $row['post_id'] : 0;

        if ($post_id <= 0) {
            $event['post_row'] = $post_row;
            return;
        }

        if (!$this->is_valid_post($post_id)) {
            error_log('[phpBB Reactions] display_reactions: post_id ' . $post_id . ' not found');
            $event['post_row'] = $post_row;
            return;
        }

        // Récupération des réactions depuis la DB
        $reactions_by_db = $this->get_post_reactions($post_id);
        $user_reactions = $this->get_user_reactions($post_id, (int) $this->user->data['user_id']);
        $list_users_reaction = $this->get_list_users_reactions($post_id);

        // CORRECTION : Ne retourner que les réactions avec count > 0
        $visible_reactions = [];
        foreach ($reactions_by_db as $emoji => $count) {
            if ((int) $count > 0) {
                $visible_reactions[] = [
                    'EMOJI'        => $emoji,
                    'COUNT'        => (int) $count,
                    'USER_REACTED' => in_array($emoji, $user_reactions, true),
                ];
            }
        }
		
		$visible_users = [];
		foreach ($list_users_reaction as $emoji => $nom)
		{
			$visible_users[] = [
				'EMOJI'		=> $emoji,
				'NOM'		=> $nom,
			];
		}
		
        // CORRECTION : Plus de fallback avec des emojis à count=0
        // Selon cahier des charges : "les émojis n'apparaissent que s'il y a des réactions"
		$post_row['S_REACTIONS_ENABLED'] = true;
		$post_row['post_reactions'] = [];
		$post_row['list_reactions'] = [];
		
		// Assigner les réactions via le système de blocs de template
		foreach ($visible_reactions as $reaction) {
		    $this->template->assign_block_vars('postrow.post_reactions', $reaction);
		}
		
		//~ error_log('[phpBB Reactions] post_reactions assignees pour post ' . $post_id . ': ' . count($visible_reactions) . ' reactions, structure: ' . print_r($post_row['post_reactions'], true));
		        //~ $event['post_row'] = $post_row;
		
		foreach ($visible_users as $users_name) {
		    $this->template->assign_block_vars('postrow.list_reactions', $users_name);
		}
		//~ error_log('[phpBB Reactions] list_reactions assignees pour post ' . $post_id . ': ' . count($visible_users) . ' reactions, structure: ' . print_r($post_row['list_reactions'], true));

        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'post_reactions'      => $visible_reactions, // Seules les vraies réactions
        ]);

        $event['post_row'] = $post_row;
    }

    public function add_forum_data($event)
    {
        // Placeholder
    }

    /**
     * CORRECTION : Amélioration du debug pour get_user_reactions
     */
    private function get_user_reactions($post_id, $user_id)
    {
        $post_id = (int) $post_id;
        $user_id = (int) $user_id;

        if ($user_id === ANONYMOUS || $post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                  AND user_id = ' . $user_id;

        $result = $this->db->sql_query($sql);

        $user_reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            if (!empty($row['reaction_emoji'])) {
                $user_reactions[] = $row['reaction_emoji'];
            }
        }
        $this->db->sql_freeresult($result);

        $unique = array_values(array_unique($user_reactions));
        return $unique;
    }

    private function is_valid_post($post_id)
    {
        $sql = 'SELECT post_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return (bool) $exists;
    }

/**	ajout liste users **/
    private function get_list_users_reactions($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return [];
        }
        
        $sql = 'SELECT r.reaction_emoji as emo, u.username_clean as nom FROM `phpbb_users` u INNER JOIN `phpbb_post_reactions` r ON u.user_id=r.user_id WHERE r.post_id=' . $post_id ;
        error_log("[Reactions Debug] get_list_users_reactions SQL: $sql");
        $result = $this->db->sql_query($sql);

        $reactions_users = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            if (!empty($row['emo'])) {
                $reactions_users[$row['emo']] = $row['nom'];
            }
        }
        $this->db->sql_freeresult($result);

        error_log('[Reactions Debug] Users trouves pour post_id=' . $post_id . ': ' . json_encode($reactions_users, JSON_UNESCAPED_UNICODE));
        return $reactions_users;
	}
	
    /**
     * CORRECTION : Amélioration du debug pour get_post_reactions
     */
    private function get_post_reactions($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji, COUNT(*) AS reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                GROUP BY reaction_emoji
                HAVING COUNT(*) > 0
                ORDER BY COUNT(*) DESC';

        $result = $this->db->sql_query($sql);

        $reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            if (!empty($row['reaction_emoji'])) {
                $reactions[$row['reaction_emoji']] = (int) $row['reaction_count'];
            }
        }
        $this->db->sql_freeresult($result);

        return $reactions;
    }

    /**
     * Comptage du nombre total de réactions pour un post (distinct emojis)
     */
    private function count_post_reactions($post_id)
    {
        $sql = 'SELECT COUNT(DISTINCT reaction_emoji) AS total_reactions
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row ? (int) $row['total_reactions'] : 0;
    }

    /**
     * Comptage des réactions d'un utilisateur pour un post
     */
    private function count_user_reactions($post_id, $user_id)
    {
        if ($user_id === ANONYMOUS) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS user_reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id . '
                  AND user_id = ' . (int) $user_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row ? (int) $row['user_reaction_count'] : 0;
    }

    /**
     * Récupère la liste des utilisateurs ayant réagi avec un emoji spécifique
     */
    private function get_users_by_reaction($post_id, $emoji)
    {
        $sql = 'SELECT u.user_id, u.username
                FROM ' . $this->post_reactions_table . ' pr
                JOIN ' . USERS_TABLE . ' u ON pr.user_id = u.user_id
                WHERE pr.post_id = ' . (int) $post_id . "
                  AND pr.reaction_emoji = '" . $this->db->sql_escape($emoji) . "'
                ORDER BY pr.reaction_time ASC";
        
        $result = $this->db->sql_query($sql);
        $users = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $users[] = [
                'user_id' => (int) $row['user_id'],
                'username' => $row['username']
            ];
        }
        $this->db->sql_freeresult($result);

        return $users;
    }

    /**
     * Vérifie les limites selon le cahier des charges
     */
    public function check_reaction_limits($post_id, $user_id)
    {
        // B.1 - Limite de types par post (défaut: 20)
        $max_types = 20; // À rendre configurable via ACP
        $current_types = $this->count_post_reactions($post_id);
        
        // B.2 - Limite par utilisateur/post (défaut: 10)  
        $max_user_reactions = 10; // À rendre configurable via ACP
        $current_user_reactions = $this->count_user_reactions($post_id, $user_id);
        
        return [
            'can_add_new_type' => $current_types < $max_types,
            'can_add_reaction' => $current_user_reactions < $max_user_reactions,
            'current_types' => $current_types,
            'max_types' => $max_types,
            'current_user_reactions' => $current_user_reactions,
            'max_user_reactions' => $max_user_reactions
        ];
    }

    public function is_valid_emoji($emoji)
    {
        if (!is_string($emoji) || $emoji === '') {
            return false;
        }

        if (in_array($emoji, $this->common_emojis, true)) {
            return true;
        }

        return (mb_strlen(trim($emoji)) > 0);
    }
}
