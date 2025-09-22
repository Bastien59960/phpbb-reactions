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

        // debug: vérifier que le service est instancié
        error_log('[phpBB Reactions] Listener : construct invoked');
    }

    /**
     * Events subscription
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
     * Add CSS/JS and language
     *
     * @param \phpbb\event\data $event
     */
    public function add_assets_to_page($event)
    {
        // charger la langue de l'extension (si existante)
        $this->language->add_lang('common', 'bastien59960/reactions');

        // chemins relatifs des assets (adaptables)
        $css_path = './ext/bastien59960/reactions/styles/prosilver/theme/reactions.css';
        $js_path  = './ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js';

        // URL ajax globale (route définie dans routing.yml)
        $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);

        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,
            'REACTIONS_CSS_PATH'  => $css_path,
            'REACTIONS_JS_PATH'   => $js_path,
            'U_REACTIONS_AJAX'    => $ajax_url,
        ]);

        // expose l'URL ajax en JS global
        $this->template->assign_var('REACTIONS_AJAX_URL_JS', 'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '";');
    }

    /**
     * Placeholder pour étendre les données utilisateur (inutile si non utilisé)
     *
     * @param \phpbb\event\data $event
     */
    public function load_language_and_data($event)
    {
        // pour l'instant rien de spécial, mais la méthode est présente si besoin
    }

    /**
     * Main : préparer les données par post pour le template
     *
     * @param \phpbb\event\data $event
     */
    public function display_reactions($event)
    {
        // debug
        error_log('[phpBB Reactions] display_reactions() called');

        $post_row = isset($event['post_row']) ? $event['post_row'] : [];
        $row      = isset($event['row']) ? $event['row'] : [];
        $post_id  = isset($row['post_id']) ? (int) $row['post_id'] : 0;

        if ($post_id <= 0) {
            // rien à faire
            $event['post_row'] = $post_row;
            return;
        }

        // récupère les données en base
        $reactions_by_db = $this->get_post_reactions($post_id); // assoc: emoji => count
        $user_reactions  = $this->get_user_reactions($post_id, (int) $
