<?php
/**
 * Reactions Extension for phpBB 3.3
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    /** @var \phpbb\db\driver\driver_interface */
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

    /**
     * Constructor
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
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

        // Log pour vérifier que le constructeur est bien exécuté
        error_log('[phpBB Reactions] Constructeur du listener exécuté !');
    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        error_log('[phpBB Reactions] getSubscribedEvents() appelé !');

        return [
            'core.page_header'               => 'add_assets_to_page',
            'core.viewtopic_cache_user_data' => 'load_language_and_data',
            'core.viewtopic_post_row_after'  => 'display_reactions',
            'core.viewforum_modify_topicrow' => 'add_forum_data',
        ];
    }

    /**
     * Load language and add assets to page
     */
    public function add_assets_to_page($event)
    {
        $this->language->add_lang('common', 'bastien59960/reactions');

        $css_path = './ext/bastien59960/reactions/styles/prosilver/theme/reactions.css';
        $js_path  = './ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js';

        $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);

        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,
            'REACTIONS_CSS_PATH'  => $css_path,
            'REACTIONS_JS_PATH'   => $js_path,
            'U_REACTIONS_AJAX'    => $ajax_url,
        ]);

        $this->template->assign_var(
            'REACTIONS_AJAX_URL_JS',
            'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '";'
        );
    }

    /**
     * Load language and user data
     */
    public function load_language_and_data($event)
    {
        if (isset($event['user_cache_data'])) {
            $user_cache_data = $event['user_cache_data'];
            $event['user_cache_data'] = $user_cache_data;
        }
    }

    /**
     * Display reactions on posts
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

        $reactions      = $this->get_post_reactions($post_id);
        $user_reactions = $this->get_user_reactions($post_id, $this->user->data['user_id']);

        $reactions_data = [];
        foreach ($reactions as $emoji => $count) {
            $reactions_data[] = [
                'EMOJI'            => (string) $emoji,
                'COUNT'            => (int) $count,
                'IS_USER_REACTION' => in_array($emoji, $user_reactions),
            ];
        }

        $this->template->assign_vars([
            'REACTIONS_DATA'      => $reactions_data,
            'S_REACTIONS_POST_ID' => $post_id,
        ]);

        $event['post_row'] = $post_row;
    }

    /**
     * Add forum-level data if needed
     */
    public function add_forum_data($event)
    {
        // Placeholder si besoin
    }

    /**
     * Get reactions count for a post
     */
    private function get_post_reactions($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return [];
        }

        $sql = 'SELECT COALESCE(reaction_emoji, reaction_unicode) AS reaction_key, COUNT(*) as reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                GROUP BY COALESCE(reaction_emoji, reaction_unicode)';
        $result = $this->db->sql_query($sql);

        $reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $reactions[$row['reaction_key']] = (int) $row['reaction_count'];
        }
        $this->db->sql_freeresult($result);

        return $reactions;
    }

    /**
     * Get user reactions for a post
     */
    private function get_user_reactions($post_id, $user_id)
    {
        $post_id = (int) $post_id;
        $user_id = (int) $user_id;

        if ($user_id === ANONYMOUS || $post_id <= 0) {
            return [];
        }

        $sql = 'SELECT COALESCE(reaction_emoji, reaction_unicode) AS reaction_key
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                  AND user_id = ' . $user_id;
        $result = $this->db->sql_query($sql);

        $user_reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $user_reactions[] = $row['reaction_key'];
        }
        $this->db->sql_freeresult($result);

        return $user_reactions;
    }
}
