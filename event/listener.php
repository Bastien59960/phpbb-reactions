<?php
/**
 * Reactions Extension Listener for phpBB 3.3+ (complete)
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

    /** @var array Liste des 10 émojis courantes selon le cahier des charges (👍 et 👎 en 1 et 2) */
    protected $common_emojis = [
        '👍', '👎', '❤️', '😂', '😮', '😢', '😡', '👏', '🔥', '🎉'
    ];

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

        // Forcer la connexion en utf8mb4 quand c'est possible
        try {
            $this->db->sql_query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_bin'");
        } catch (\Exception $e) {
            // Ne pas planter l'extension si la DB refuse cette commande
            error_log('[phpBB Reactions] Could not set names: ' . $e->getMessage());
        }

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
        try {
            $ajax_url = $this->helper->route('bastien59960_reactions_ajax', []);
        } catch (\Exception $e) {
            // Fallback: construction manuelle si la route n'existe pas
            $ajax_url = append_sid('app.php/reactions/ajax');
        }

        $this->template->assign_vars([
            'S_REACTIONS_ENABLED' => true,
            'REACTIONS_CSS_PATH'  => $css_path,
            'REACTIONS_JS_PATH'   => $js_path,
            'U_REACTIONS_AJAX'    => $ajax_url,
            'S_SESSION_ID'        => isset($this->user->data['session_id']) ? $this->user->data['session_id'] : '',
        ]);

        // Exposer l'URL AJAX et le SID dans le JS global (variable JS prête à injecter)
        $this->template->assign_var(
            'REACTIONS_AJAX_URL_JS',
            'window.REACTIONS_AJAX_URL = "' . addslashes($ajax_url) . '"; window.REACTIONS_SID = "' . addslashes(isset($this->user->data['session_id']) ? $this->user->data['session_id'] : '') . '";'
        );
    }

    /**
     * Placeholder : enrichir user_cache_data si besoin
     *
     * @param \phpbb\event\data $event
     */
    public function load_language_and_data($event)
    {
        // Méthode gardée pour compatibilité et éventuelle extension
    }

    /**
     * Prépare et injecte les réactions pour un post (SEULEMENT celles avec count > 0)
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

        // Vérifier que le post existe
        if (!$this->is_valid_post($post_id)) {
            error_log('[phpBB Reactions] display_reactions: post_id ' . $post_id . ' not found');
            $event['post_row'] = $post_row;
            return;
        }

        // Récupération depuis la DB
        $reactions_by_db = $this->get_post_reactions($post_id); // [emoji => count]
        $user_reactions = $this->get_user_reactions($post_id, (int) $this->user->data['user_id']); // [emoji, ...]

        // Filtrer selon cahier des charges : seulement count > 0
        $visible = [];
        foreach ($reactions_by_db as $emoji => $count) {
            if ((int) $count > 0) {
                $visible[] = [
                    'EMOJI'        => $emoji,
                    'COUNT'        => (int) $count,
                    'USER_REACTED' => in_array($emoji, $user_reactions, true),
                ];
            }
        }

        // Pour l'affichage côté template, renvoyer les 10 "émojis courantes" en fallback si aucune reaction stockée
        if (empty($visible)) {
            // fallback propre : seulement les 10 émojis courantes, sans compte
            $visible = array_map(function ($e) {
                return ['EMOJI' => $e, 'COUNT' => 0, 'USER_REACTED' => false];
            }, $this->common_emojis);
        }

        $post_row = array_merge($post_row, [
            'S_REACTIONS_ENABLED' => true,
            'post_reactions'      => $visible,
        ]);

        error_log('[phpBB Reactions] post_reactions assigned: ' . count($visible) . ' entries for post ' . $post_id);
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
     * Récupère la liste des emojis que l'utilisateur courant a ajouté pour ce post
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
            $key = isset($row['reaction_key']) ? $row['reaction_key'] : '';
            if ($key !== '') {
                $user_reactions[] = $key;
            }
        }
        $this->db->sql_freeresult($result);

        // Supprimer les doublons et réindexer
        $unique = array_values(array_unique($user_reactions));
        error_log('[Reactions Debug User] Final user_reactions: ' . json_encode($unique, JSON_UNESCAPED_UNICODE));
        return $unique;
    }

    /**
     * Vérifie que le post existe en DB
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
     * Comptage du nombre total de réactions pour un post (distinct emojis)
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
     * Comptage des réactions d'un utilisateur pour un post
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

    /**
     * Récupère depuis la DB le nombre de réactions par emoji pour un post
     * Retourne un tableau associatif [emoji => count]
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
            $key = isset($row['reaction_key']) ? $row['reaction_key'] : '';
            if ($key !== '') {
                $reactions[$key] = (int) $row['reaction_count'];
            } else {
                error_log('[Reactions Debug] ALERTE: reaction_key vide dans la row');
            }
        }
        $this->db->sql_freeresult($result);

        error_log('[Reactions Debug] get_post_reactions final for post_id=' . $post_id . ': ' . json_encode($reactions, JSON_UNESCAPED_UNICODE));
        error_log('[Reactions Debug] Nombre total de rows: ' . $row_count);

        return $reactions;
    }

    /**
     * Validation utilitaire d'un emoji (utilisable depuis ajax.php)
     * Accepte les 10 émojis courantes + tout emoji non vide si souhaité.
     *
     * @param string $emoji
     * @return bool
     */
    public function is_valid_emoji($emoji)
    {
        if (!is_string($emoji) || $emoji === '') {
            return false;
        }

        // Vérifier dans la liste des 10 émojis courantes
        if (in_array($emoji, $this->common_emojis, true)) {
            return true;
        }

        // Fallback permissif : autoriser d'autres emojis (peut être restreint dans ajax.php)
        // Ici on vérifie juste qu'il y a au moins un caractère unicode (non vide)
        return (mb_strlen(trim($emoji)) > 0);
    }
}
