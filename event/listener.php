<?php
/**
 * Reactions Extension for phpBB 3.3
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
// Debug temporaire
$this->template->assign_var('DEBUG_REACTIONS', 'Extension reactions chargée !');

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
     *
     * @param \phpbb\db\driver\driver_interface $db
     * @param \phpbb\user $user
     * @param string $post_reactions_table
     * @param string $posts_table
     * @param \phpbb\template\template $template
     * @param \phpbb\language\language $language
     * @param \phpbb\controller\helper $helper
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
    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            'core.page_header' => 'add_assets_to_page',
            'core.viewtopic_cache_user_data' => 'load_language_and_data',
            'core.viewtopic_post_row_after' => 'display_reactions',
            'core.viewforum_modify_topicrow' => 'add_forum_data',
        ];
    }

    /**
     * Load language and add assets to page
     *
     * @param \phpbb\event\data $event
     */
    public function add_assets_to_page($event)
    {
        // Charger le fichier de langue
        $this->language->add_lang('common', 'bastien59960/reactions');
        
        // Construire les chemins des assets - méthode compatible phpBB 3.3
        $web_root_path = $this->helper->get_current_url();
        $ext_web_path = str_replace(generate_board_url() . '/', '', $this->helper->route('bastien59960_reactions_controller', [], false));
        $ext_web_path = dirname($ext_web_path) . '/';
        
        // Alternative plus simple : utiliser les chemins relatifs
        $css_path = './ext/bastien59960/reactions/styles/prosilver/theme/reactions.css';
        $js_path = './ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js';
        
        // Assigner les variables au template - MÉTHODE CORRECTE pour phpBB 3.3
        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,
            'REACTIONS_CSS_PATH' => $css_path,
            'REACTIONS_JS_PATH' => $js_path,
            'U_REACTIONS_AJAX' => $this->helper->route('bastien59960_reactions_ajax', []),
        ]);
        
        // Ajouter l'URL AJAX en JavaScript global
        $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);
        $this->template->assign_var('REACTIONS_AJAX_URL_JS', 'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '";');
    }
    
    /**
     * Load language and user data
     *
     * @param \phpbb\event\data $event
     */
    public function load_language_and_data($event)
    {
        // Charger des données supplémentaires si nécessaire
        $user_cache_data = $event['user_cache_data'];
        
        // Vous pouvez ajouter des données utilisateur ici si besoin
        $event['user_cache_data'] = $user_cache_data;
    }

    /**
     * Display reactions on posts
     *
     * @param \phpbb\event\data $event
     */
    public function display_reactions($event)
    {
        $post_row = $event['post_row'];
        $row = $event['row'];
        $post_id = $row['post_id'];
        
        // Récupérer les réactions pour ce post
        $reactions = $this->get_post_reactions($post_id);
        $user_reactions = $this->get_user_reactions($post_id, $this->user->data['user_id']);
        
        // Préparer les données pour le template
        $reactions_data = [];
        foreach ($reactions as $emoji => $count) {
            $reactions_data[] = [
                'EMOJI' => $emoji,
                'COUNT' => $count,
                'USER_REACTED' => in_array($emoji, $user_reactions),
            ];
        }
        
        // Ajouter aux données du post
        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'POST_REACTIONS' => $reactions_data,
            'U_REACT' => $this->helper->route('bastien59960_reactions_add', ['post_id' => $post_id]),
        ]);
        
        $event['post_row'] = $post_row;
    }
    
    /**
     * Add forum-level data if needed
     *
     * @param \phpbb\event\data $event
     */
    public function add_forum_data($event)
    {
        // Ajouter des données au niveau du forum si nécessaire
    }
    
    /**
     * Get reactions count for a post
     *
     * @param int $post_id
     * @return array
     */
    private function get_post_reactions($post_id)
    {
        $sql = 'SELECT reaction_emoji, COUNT(*) as reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id . '
                GROUP BY reaction_emoji';
        $result = $this->db->sql_query($sql);
        
        $reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $reactions[$row['reaction_emoji']] = (int) $row['reaction_count'];
        }
        $this->db->sql_freeresult($result);
        
        return $reactions;
    }
    
    /**
     * Get user reactions for a post
     *
     * @param int $post_id
     * @param int $user_id
     * @return array
     */
    private function get_user_reactions($post_id, $user_id)
    {
        if ($user_id == ANONYMOUS) {
            return [];
        }
        
        $sql = 'SELECT reaction_emoji
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id . '
                    AND user_id = ' . (int) $user_id;
        $result = $this->db->sql_query($sql);
        
        $reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $reactions[] = $row['reaction_emoji'];
        }
        $this->db->sql_freeresult($result);
        
        return $reactions;
    }
}
