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

        // debug rapide — devrait apparaître dans error.log si le service est instancié
        error_log('[phpBB Reactions] Listener::__construct invoked');
    }

    /**
     * Subscribe to phpBB events
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            'core.page_header'               => 'add_assets_to_page',
            'core.viewtopic_cache_user_data' => 'load_language_and_data',
            'core.viewtopic_post_row_after'  => 'display_reactions',
            'core.viewforum_modify_topicrow' => 'add_forum_data',
        ];
    }

    /**
     * Add CSS/JS and load language
     *
     * @param \phpbb\event\data $event
     */
    public function add_assets_to_page($event)
    {
        // Charger le fichier de langue de l'extension
        $this->language->add_lang('common', 'bastien59960/reactions');

        // Assigner l'URL AJAX pour JS
        $this->template->assign_var('REACTIONS_AJAX_URL', $this->helper->route('bastien59960_reactions_ajax'));

        // Assigner le SID pour CSRF en JS
        $this->template->assign_var('REACTIONS_SID', $this->user->data['session_id']);

        // Ajouter CSS
        $this->template->assign_var('REACTIONS_CSS', 'ext/bastien59960/reactions/styles/all/theme/reactions.css');

        // Ajouter JS
        $this->template->assign_var('REACTIONS_JS', 'ext/bastien59960/reactions/styles/all/template/js/reactions.js');

        // Log pour débogage
        error_log('[phpBB Reactions] Assets added to page, SID: ' . $this->user->data['session_id']);
    }

    /**
     * Load language and user data for viewtopic
     *
     * @param \phpbb\event\data $event
     */
    public function load_language_and_data($event)
    {
        // Charger la langue si nécessaire
        $this->language->add_lang('common', 'bastien59960/reactions');
    }

    /**
     * Display reactions in viewtopic
     *
     * @param \phpbb\event\data $event
     */
    public function display_reactions($event)
    {
        $post_row = $event['post_row'];
        $post_id = $post_row['POST_ID'];
        $user_id = $this->user->data['user_id'];

        // Récupérer les réactions du post
        $reactions = $this->get_post_reactions($post_id);

        // Récupérer les réactions de l'utilisateur courant
        $user_reactions = $this->get_user_reactions($post_id, $user_id);

        // Assigner au template
        foreach ($reactions as $emoji => $count) {
            $this->template->assign_block_vars('post_reactions', [
                'EMOJI' => $emoji,
                'COUNT' => $count,
                'USER_REACTED' => in_array($emoji, $user_reactions),
            ]);
        }

        // Activer les réactions si l'utilisateur n'est pas un bot
        $this->template->assign_var('S_REACTIONS_ENABLED', !$event['s_is_bot']);

        $event['post_row'] = $post_row;
    }

    /**
     * Add data for viewforum if necessary
     *
     * @param \phpbb\event\data $event
     */
    public function add_forum_data($event)
    {
        // rien pour l'instant
    }

    /**
     * Récupère le nombre de réactions par emoji pour un post
     *
     * @param int $post_id
     * @return array emoji => count
     */
    private function get_post_reactions($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji AS reaction_key, COUNT(*) AS reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                GROUP BY reaction_emoji';
        $result = $this->db->sql_query($sql);

        $reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $key = isset($row['reaction_key']) ? $row['reaction_key'] : '';
            if ($key !== '') {
                $reactions[$key] = (int) $row['reaction_count'];
            }
        }
        $this->db->sql_freeresult($result);

        return $reactions;
    }

    /**
     * Récupère la liste des emojis que l'utilisateur courant a ajoutés pour ce post
     *
     * @param int $post_id
     * @param int $user_id
     * @return array list d'emojis
     */
    private function get_user_reactions($post_id, $user_id)
    {
        $post_id = (int) $post_id;
        $user_id = (int) $user_id;

        if ($user_id === ANONYMOUS || $post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji AS reaction_key
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                  AND user_id = ' . $user_id;
        $result = $this->db->sql_query($sql);

        $user_reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $key = isset($row['reaction_key']) ? $row['reaction_key'] : '';
            if ($key !== '') {
                $user_reactions[] = $key;
            }
        }
        $this->db->sql_freeresult($result);

        // remove duplicates and reindex
        return array_values(array_unique($user_reactions));
    }
}
