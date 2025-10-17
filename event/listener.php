<?php
/**
 * Fichier : event/listener.php — bastien59960/reactions/event/listener.php
 *
 * Listener d'événements pour l'extension Reactions.
 *
 * Ce fichier permet d'intercepter et de traiter les événements du cycle de vie phpBB (affichage, envoi de message, etc.) pour intégrer la logique des réactions dans le forum.
 *
 * Points clés de la logique métier :
 *   - Ajout des scripts et templates nécessaires à l'affichage des réactions
 *   - Intégration avec les hooks phpBB pour enrichir l'expérience utilisateur
 *   - Possibilité d'étendre ou de modifier le comportement natif du forum via les événements
 *
 * Ce listener est enregistré dans services.yml et agit en complément des contrôleurs principaux.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

/**
 * Fichier : event/listener.php — bastien59960/reactions/event/listener.php
 * CORRECTION : Ajout de $root_path manquant
 */

namespace bastien59960\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\db\driver\driver_interface;

class listener implements EventSubscriberInterface
{
    // =============================================================================
    // PROPRIÉTÉS DE LA CLASSE
    // =============================================================================
    
    protected $db;
    protected $user;
    protected $post_reactions_table;
    protected $posts_table;
    protected $template;
    protected $language;
    protected $helper;
    protected $config;
    
    // ✅ AJOUT DE LA PROPRIÉTÉ MANQUANTE
    protected $root_path;
    protected $php_ext;

    protected $common_emojis = [
        '👍', '👎', '❤️', '😂', '😮', '😢', '😡', '🔥', '💌', '🥳'
    ];

    // =============================================================================
    // CONSTRUCTEUR CORRIGÉ
    // =============================================================================
    
    /**
     * ✅ CORRECTION : Ajout de $root_path et $php_ext
     */
    public function __construct(
        driver_interface $db,
        \phpbb\user $user,
        $post_reactions_table,
        \phpbb\template\template $template,
        \phpbb\language\language $language,
        \bastien59960\reactions\controller\helper $helper, // ✅ Nouveau type-hint correspondant à votre service
        \phpbb\config\config $config,
        $root_path,      // ✅ NOUVEAU PARAMÈTRE
        $php_ext         // ✅ NOUVEAU PARAMÈTRE
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->post_reactions_table = $post_reactions_table;
        $this->template = $template;
        $this->language = $language;
        $this->helper = $helper;
        $this->config = $config;
        $this->root_path = $root_path;    // ✅ STOCKAGE
        $this->php_ext = $php_ext;        // ✅ STOCKAGE
        
        try {
            $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");
        } catch (\Exception $e) {
            error_log('[phpBB Reactions] Could not set names: ' . $e->getMessage());
        }
    }

    // =============================================================================
    // RESTE DU CODE INCHANGÉ
    // =============================================================================
    
    public static function getSubscribedEvents()
    {
        return [
            'core.user_setup'                => 'load_language_files',
            'core.page_header'               => 'add_assets_to_page',
            'core.viewtopic_cache_user_data' => 'load_language_and_data',
            'core.viewtopic_post_row_after'  => 'display_reactions',
            'core.viewforum_modify_topicrow' => 'add_forum_data',
        ];
    }

    /**
     * ✅ MAINTENANT $this->root_path EXISTE
     */
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

        $post_emoji_size = (int) ($this->config['bastien59960_reactions_post_emoji_size'] ?? 24);
        $picker_width = (int) ($this->config['bastien59960_reactions_picker_width'] ?? 320);
        $picker_height = (int) ($this->config['bastien59960_reactions_picker_height'] ?? 280);
        $picker_emoji_size = (int) ($this->config['bastien59960_reactions_picker_emoji_size'] ?? 24);
        $picker_show_categories = (int) ($this->config['bastien59960_reactions_picker_show_categories'] ?? 1);
        $picker_show_search = (int) ($this->config['bastien59960_reactions_picker_show_search'] ?? 1);
        $picker_use_json = (int) ($this->config['bastien59960_reactions_picker_use_json'] ?? 1);
        $sync_interval = (int) ($this->config['bastien59960_reactions_sync_interval'] ?? 5000);

        $json_path = $picker_use_json
            ? $this->root_path . 'ext/bastien59960/reactions/styles/prosilver/theme/categories.json'
            : '';

        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,
            'REACTIONS_CSS_PATH'  => $css_path,
            'REACTIONS_JS_PATH'   => $js_path,
            'U_REACTIONS_AJAX'    => $ajax_url,
            'S_SESSION_ID'        => isset($this->user->data['session_id']) ? $this->user->data['session_id'] : '',
            'REACTIONS_JSON_PATH' => $json_path,
            'REACTIONS_POST_EMOJI_SIZE'   => $post_emoji_size,
            'REACTIONS_PICKER_WIDTH'      => $picker_width,
            'REACTIONS_PICKER_HEIGHT'     => $picker_height,
            'REACTIONS_PICKER_EMOJI_SIZE' => $picker_emoji_size,
            'REACTIONS_PICKER_SHOW_CATEGORIES' => $picker_show_categories,
            'REACTIONS_PICKER_SHOW_SEARCH'     => $picker_show_search,
            'REACTIONS_PICKER_USE_JSON'        => $picker_use_json,
            'REACTIONS_SYNC_INTERVAL'          => $sync_interval,
        ]);

        $debug_mode = (defined('DEBUG') && DEBUG) ? 'true' : 'false';

        $this->template->assign_var(
            'REACTIONS_AJAX_URL_JS',
            'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '";' .
            'window.REACTIONS_SID = "' . addslashes(isset($this->user->data['session_id']) ? $this->user->data['session_id'] : '') . '";' .
            'window.REACTIONS_JSON_PATH = "' . addslashes($json_path) . '";' .
            'window.REACTIONS_DEBUG_MODE = ' . $debug_mode . ';' .
            'window.REACTIONS_OPTIONS = {' .
                'postEmojiSize:' . (int) $post_emoji_size . ',' .
                'pickerWidth:' . (int) $picker_width . ',' .
                'pickerHeight:' . (int) $picker_height . ',' .
                'pickerEmojiSize:' . (int) $picker_emoji_size . ',' .
                'showCategories:' . ($picker_show_categories ? 'true' : 'false') . ',' .
                'showSearch:' . ($picker_show_search ? 'true' : 'false') . ',' .
                'useJson:' . ($picker_use_json ? 'true' : 'false') . ',' .
                'syncInterval:' . (int) $sync_interval .
            '};'
        );
    }

    // Le reste des méthodes reste identique...
    public function load_language_and_data($event) {}
    
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

        $reactions_by_db = $this->get_post_reactions($post_id);
        $user_reactions = $this->get_user_reactions($post_id, (int) $this->user->data['user_id']);

        $visible_reactions = [];
        foreach ($reactions_by_db as $emoji => $count) {
            if ((int) $count > 0) {
                $users_for_emoji = $this->get_users_by_reaction($post_id, $emoji);
                $users_json = json_encode($users_for_emoji, JSON_UNESCAPED_UNICODE);
                
                $visible_reactions[] = [
                    'EMOJI'        => $emoji,
                    'COUNT'        => (int) $count,
                    'USER_REACTED' => in_array($emoji, $user_reactions, true),
                    'USERS'        => $users_json,
                ];
            }
        }

        foreach ($visible_reactions as $reaction) {
            $this->template->assign_block_vars('postrow.post_reactions', $reaction);
        }

        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'post_reactions'      => $visible_reactions,
        ]);

        $event['post_row'] = $post_row;
    }

    public function add_forum_data($event) {}

    public function load_language_files($event)
    {
        $language_sets = [
            'common',
            'reactions',
            'notification/reaction',
            'notification/notification.type.reaction',
            'notification/notification.type.reaction_email_digest',
            'ucp_reactions',
            'acp/common',
        ];

        foreach ($language_sets as $lang_set) {
            $event['lang_set_ext'][] = [
                'ext_name' => 'bastien59960/reactions',
                'lang_set' => $lang_set,
            ];
        }

        return;
    }

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

        return array_values(array_unique($user_reactions));
    }

    private function is_valid_post($post_id)
    {
        $sql = 'SELECT post_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return (bool) $exists;
    }

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
