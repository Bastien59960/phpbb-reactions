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
        // Charger le fichier de langue (le 2e argument peut être laissé si les fichiers de langue sont dans l'extension)
        $this->language->add_lang('common', 'bastien59960/reactions');

        // Chemins relatifs vers les assets de l'extension (à adapter si vous servez autrement)
        $css_path = './ext/bastien59960/reactions/styles/prosilver/theme/reactions.css';
        $js_path  = './ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js';

        // Routes AJAX / actions : adaptez les noms si vos routes sont différentes
        $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);
        // URL pour ajouter une réaction (utilisé plus tard dans display_reactions)
        $add_react_base = $this->helper->route('bastien59960_reactions_add', []);

        // Assigner les variables au template
        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,
            'REACTIONS_CSS_PATH'  => $css_path,
            'REACTIONS_JS_PATH'   => $js_path,
            'U_REACTIONS_AJAX'    => $ajax_url,
            'U_REACTIONS_ADD'     => $add_react_base,
        ]);

        // Ajouter l'URL AJAX en JavaScript global (sécurisé)
        $this->template->assign_var('REACTIONS_AJAX_URL_JS', 'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '";');
    }

    /**
     * Load language and user data
     *
     * @param \phpbb\event\data $event
     */
    public function load_language_and_data($event)
    {
        // Exemple simple : récupérer et remettre les données d'utilisateur si présentes.
        // Laisser l'événement intact si vous n'avez rien à enrichir.
        if (isset($event['user_cache_data'])) {
            $user_cache_data = $event['user_cache_data'];
            // -> Vous pouvez enrichir $user_cache_data ici si besoin.
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
        // Récupération sécurisée des éléments fournis par l'événement
        $post_row = isset($event['post_row']) ? $event['post_row'] : [];
        $row = isset($event['row']) ? $event['row'] : [];
        $post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;

        if ($post_id <= 0) {
            // Rien à afficher si pas de post_id valide
            $event['post_row'] = $post_row;
            return;
        }

        // Récupérer les réactions et celles de l'utilisateur courant
        $reactions = $this->get_post_reactions($post_id);
        $user_reactions = $this->get_user_reactions($post_id, $this->user->data['user_id']);

        // Préparer les données pour le template (format adapté pour assign_block_vars si nécessaire)
        $reactions_data = [];
        foreach ($reactions as $emoji => $count) {
            $reactions_data[] = [
                'EMOJI' => (string) $emoji,
                'COUNT' => (int) $count,
                'USER_REACTED' => in_array($emoji, $user_reactions),
            ];
        }

        // Construire l'URL d'ajout de réaction pour ce post (si votre route demande post_id en param)
        $u_react = $this->helper->route('bastien59960_reactions_add', ['post_id' => $post_id]);

        // Ajouter / fusionner les données dans la ligne de post retournée au template
        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'POST_REACTIONS'      => $reactions_data,
            'U_REACT'             => $u_react,
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
        // Placeholder : ajouter des données au niveau du forum si nécessaire
    }

    /**
     * Get reactions count for a post
     *
     * @param int $post_id
     * @return array
     */
    private function get_post_reactions($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji, COUNT(*) as reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
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
        $post_id = (int) $post_id;
        $user_id = (int) $user_id;

        if ($user_id === ANONYMOUS || $post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                    AND user_id = ' . $user_id;
        $result = $this->db->sql_query($sql);_
