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
     * Constructor
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

    /**
     * Add CSS/JS and load language
     *
     * @param \phpbb\event\data $event
     */
    public function add_assets_to_page($event)
    {
        // Charger le fichier de langue de l'extension
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
            'S_SESSION_ID'        => $this->user->data['session_id'],
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
        // RAS pour l'instant - méthode gardée pour compatibilité
    }

    /**
     * CORRECTION MAJEURE : Prépare les données des réactions pour chaque post
     * Selon cahier des charges : SEULEMENT les réactions avec count > 0 sont affichées
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

        // CORRECTION SELON CAHIER DES CHARGES :
        // NE PAS afficher les émojis courantes par défaut sous les messages
        // Afficher SEULEMENT les réactions ayant au minimum 1 compteur
        $visible = [];

        foreach ($reactions_by_db as $emoji => $count) {
            if ($count > 0) { // Condition stricte selon cahier des charges
                $visible[] = [
                    'EMOJI'          => $emoji,
                    'COUNT'          => (int) $count,
                    'USER_REACTED'   => in_array($emoji, $user_reactions, true),
                ];
            }
        }

        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'post_reactions'      => $visible,
        ]);

        error_log('[phpBB Reactions] post_reactions assigned: ' . count($visible) . ' reactions for post ' . $post_id);
        error_log('[phpBB Reactions] reactions data: ' . json_encode($visible, JSON_UNESCAPED_UNICODE));

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
     * MÉTHODE OPTIMISÉE : Récupère la liste des emojis que l'utilisateur courant a ajoutés pour ce post
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
                  AND user_id = ' . $user_id . '
                ORDER BY reaction_time ASC';
        
        error_log("[Reactions Debug User] SQL: $sql");
        $result = $this->db->sql_query($sql);

        $user_reactions = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            error_log("[Reactions Debug User] Row: " . json_encode($row, JSON_UNESCAPED_UNICODE));
            
            $key = isset($row['reaction_key']) ? $row['reaction_key'] : '';
            if ($key !== '') {
                $user_reactions[] = $key;
            }
        }
        $this->db->sql_freeresult($result);

        error_log("[Reactions Debug User] Final user_reactions: " . json_encode($user_reactions, JSON_UNESCAPED_UNICODE));
        
        // Supprimer les doublons et réindexer
        return array_values(array_unique($user_reactions));
    }

    /**
     * NOUVELLE MÉTHODE : Vérification de validité des posts (sécurité)
     *
     * @param int $post_id
     * @return bool
     */
    private function is_valid_post($post_id)
    {
        $sql = 'SELECT post_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return (bool) $exists;
    }

    /**
     * NOUVELLE MÉTHODE : Comptage du nombre total de réactions pour un post
     *
     * @param int $post_id
     * @return int
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
     * NOUVELLE MÉTHODE : Comptage des réactions utilisateur pour un post
     *
     * @param int $post_id
     * @param int $user_id
     * @return int
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



    
    private function get_post_reactions($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return [];
        }

        $sql = 'SELECT reaction_emoji AS reaction_key, COUNT(*) AS reaction_count
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                GROUP BY reaction_emoji
                ORDER BY COUNT(*) DESC';
        
        error_log("[Reactions Debug] get_post_reactions SQL: $sql");
        $result = $this->db->sql_query($sql);

        $reactions = [];
        $row_count = 0;
        
        while ($row = $this->db->sql_fetchrow($result)) {
            $row_count++;
            error_log("[Reactions Debug] Row #$row_count: " . json_encode($row, JSON_UNESCAPED_UNICODE));
            
            $key = isset($row['reaction_key']) ? $row['reaction_key'] : '';
            if ($key !== '') {
                $reactions[$key] = (int) $row['reaction_count'];
            } else {
                error_log("[Reactions Debug] ALERTE: reaction_key vide dans la row");
            }
        }
        $this->db->sql_freeresult($result);

        error_log("[Reactions Debug] get_post_reactions final pour post_id=$post_id: " . json_encode($reactions, JSON_UNESCAPED_UNICODE));
        error_log("[Reactions Debug] Nombre total de rows: $row_count");
        
        return $reactions;
    }

    /**
     * MÉTHODE OPTIMISÉE : Récup
