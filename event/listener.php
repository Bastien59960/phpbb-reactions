<?php
/**
 * Reactions Extension for phpBB 3.3
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\db\driver\driver_interface;

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
     * Constructor unique
     */
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

        // Forcer la connexion en utf8mb4
        $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");

        error_log('[phpBB Reactions] Listener::__construct invoked');
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
    // Charger le fichier de langue de l'extension (si présent)
    $this->language->add_lang('common', 'bastien59960/reactions');

    // Chemins relatifs vers les assets de l'extension
    $css_path = './ext/bastien59960/reactions/styles/prosilver/theme/reactions.css';
    $js_path  = './ext/bastien59960/reactions/styles/prosilver/template/js/reactions.js';

    // URL AJAX globale (route définie dans routing.yml)
    $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);

    $this->template->assign_vars([
        'S_REACTIONS_ENABLED' => true,
        'REACTIONS_CSS_PATH'  => $css_path,
        'REACTIONS_JS_PATH'   => $js_path,
        'U_REACTIONS_AJAX'    => $ajax_url,
        'S_SESSION_ID'       => $this->user->data['session_id'], // Ajouté pour le template
    ]);

    // Exposer l'URL AJAX et le SID dans le JS global
    $this->template->assign_var(
        'REACTIONS_AJAX_URL_JS',
        'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '";'
    );
}
    /**
     * Placeholder : enrichir user_cache_data si besoin
     *
     * @param \phpbb\event\data $event
     */
    public function load_language_and_data($event)
    {
        // RAS pour l'instant — méthode gardée pour compatibilité
    }

    /**
     * Prépare les données des réactions pour chaque post (appelé par core.viewtopic_post_row_after)
     *
     * @param \phpbb\event\data $event
     */
    public function display_reactions($event)
    {
        error_log('[phpBB Reactions] display_reactions called');

        $post_row = isset($event['post_row']) ? $event['post_row'] : [];
        $row      = isset($event['row']) ? $event['row'] : [];
        $post_id  = isset($row['post_id']) ? (int) $row['post_id'] : 0;

        if ($post_id <= 0) {
            $event['post_row'] = $post_row;
            return;
        }

        // Récupération depuis la DB
        $reactions_by_db = $this->get_post_reactions($post_id); // [emoji => count]
        $user_reactions = $this->get_user_reactions($post_id, (int) $this->user->data['user_id']); // [emoji, ...]

        // Réactions par défaut visibles (toujours présentes)
        $default_reactions = ['👍', '❤️', '😂', '😮', '😢'];

        // Construire la liste visible pour ce post :
        // 1) les défauts (dans l'ordre), 2) les réactions supplémentaires venant de la DB
        $visible = [];

        foreach ($default_reactions as $emoji) {
            $count = isset($reactions_by_db[$emoji]) ? (int) $reactions_by_db[$emoji] : 0;

            $visible[] = [
                'EMOJI'        => $emoji,
                'COUNT'        => $count,
                'USER_REACTED' => in_array($emoji, $user_reactions, true),
                'IS_DEFAULT'   => true,
            ];

            // si l'emoji existe en DB, on l'enlève pour éviter duplication
            if (isset($reactions_by_db[$emoji])) {
                unset($reactions_by_db[$emoji]);
            }
        }

        // Ajouter les autres emojis trouvés en DB (choisis par des utilisateurs)
        foreach ($reactions_by_db as $emoji => $count) {
            $visible[] = [
                'EMOJI'        => $emoji,
                'COUNT'        => (int) $count,
                'USER_REACTED' => in_array($emoji, $user_reactions, true),
                'IS_DEFAULT'   => false,
            ];
        }

        // Palette complète (pour reaction-picker) - modifiable selon tes besoins
        $all_reactions_list = [
            '👍','❤️','😂','😮','😢','😡','🔥','👏','🥳','🎉',
            '👌','👀','🤔','🙏','🤩','😴','🤮','💯','🙌','🤝',
            '😅','🤷','😬','🤗','😇','😎','😤','😱','🎯','🧡'
        ];
        $all_reactions = [];
        foreach ($all_reactions_list as $emoji) {
            $all_reactions[] = ['EMOJI' => $emoji];
        }

        // URL pour action 'add' sur ce post (route attend post_id)
        $u_react = $this->helper->route('bastien59960_reactions_add', ['post_id' => $post_id]);

        // Fusionner données dans post_row pour le template (accessible via {postrow.*})
        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'POST_REACTIONS'      => $visible,
            'ALL_REACTIONS'       => $all_reactions,
            'U_REACT'             => $u_react,
        ]);

        $event['post_row'] = $post_row;
    }

    /**
     * Placeholder pour données de forum si nécessaire
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
