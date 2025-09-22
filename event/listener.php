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
     *
     * Note: ici on ne génère que des URLs globales (AJAX). Les URLs par post
     * doivent être générées dans display_reactions() où $post_id est disponible.
     *
     * @param \phpbb\event\data $event
     */
    public function add_assets_to_page($event)
    {
        $this->language->add_lang('common', 'bastien59960/reactions');

        $css_path = './ext/bastien59960/reactions/styles/prosilver/theme/reactions.css';
        $js_path  = './ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js';

        // URL AJAX globale (route sans paramètres obligatoires)
        $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);

        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,
            'REACTIONS_CSS_PATH'  => $css_path,
            'REACTIONS_JS_PATH'   => $js_path,
            'U_REACTIONS_AJAX'    => $ajax_url,
        ]);

        $this->template->assign_var('REACTIONS_AJAX_URL_JS', 'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '";');
    }

    /**
     * Load language and user data
     *
     * @param \phpbb\event\data $event
     */
    public function load_language_and_data($event)
    {
        if (isset($event['user_cache_data'])) {
            $user_cache_data = $event['user_cache_data'];
            // Possibilité d'enrichir $user_cache_data ici
            $event['user_cache_data'] = $user_cache_data;
        }
    }

    /**
     * Display reactions on posts
     *
     * @param \phpbb\event\data $event
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

        // Récupérer réactions et réactions de l'utilisateur courant
        $reactions      = $this->get_post_reactions($post_id);
        $user_reactions = $this->get_user_reactions($post_id, $this->user->data['user_id']);

        $reactions_data = [];
        foreach ($reactions as $emoji => $count) {
            $reactions_data[] = [
                'EMOJI'        => (string)
