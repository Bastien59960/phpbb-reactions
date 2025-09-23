<?php
/**
 * Reactions Extension for phpBB 3.3
 * AJAX Controller
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ajax
{
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;
    
    /** @var \phpbb\user */
    protected $user;
    
    /** @var \phpbb\request\request */
    protected $request;
    
    /** @var \phpbb\auth\auth */
    protected $auth;
    
    /** @var \phpbb\language\language */
    protected $language;
    
    /** @var string */
    protected $post_reactions_table;
    
    /** @var string */
    protected $posts_table;
    protected $topics_table; 
    protected $forums_table;
    
    protected $root_path; 
    protected $php_ext; 

    /**
     * Constructor
     */
    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        \phpbb\request\request $request,
        \phpbb\auth\auth $auth,
        \phpbb\language\language $language,
        $post_reactions_table,
        $posts_table,
        $topics_table,
        $forums_table,
        $root_path,
        $php_ext
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
        $this->auth = $auth;
        $this->language = $language;
        $this->post_reactions_table = $post_reactions_table;
        $this->posts_table = $posts_table;
        $this->topics_table = $topics_table;
        $this->forums_table = $forums_table;
        $this->root_path = $root_path;
        $this->php_ext = $php_ext;
        
        $this->language->add_lang('common', 'bastien59960/reactions');
    }

    /**
     * Handle AJAX reactions
     */
   public function handle()
{
    try {
        error_log('[Reactions DEBUG] handle() démarré');

        // 1. Vérification utilisateur
        if ($this->user->data['user_id'] == ANONYMOUS) {
            error_log('[Reactions DEBUG] Utilisateur anonyme → refus');
            throw new HttpException(403, 'User not logged in.');
        }

        // 2. Lecture du JSON
        $raw = file_get_contents('php://input');
        error_log('[Reactions DEBUG] Corps brut reçu: ' . $raw);

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            error_log('[Reactions DEBUG] JSON invalide: ' . json_last_error_msg());
            throw new HttpException(400, 'Invalid JSON data');
        }

        // 3. Extraction des variables
        $sid     = $data['sid'] ?? '';
        $post_id = (int) ($data['post_id'] ?? 0);
        $emoji   = $data['emoji'] ?? '';
        $action  = $data['action'] ?? '';

        error_log("[Reactions DEBUG] Données extraites: sid=$sid, post_id=$post_id, emoji=$emoji, action=$action");

        // 4. Vérification CSRF
        if ($sid !== $this->user->data['session_id']) {
            error_log('[Reactions DEBUG] SID invalide: attendu=' . $this->user->data['session_id']);
            throw new HttpException(403, 'Jeton CSRF invalide.');
        }

        // 5. Vérification action
        if (!in_array($action, ['add', 'remove', 'get'])) {
            error_log("[Reactions DEBUG] Action invalide: $action");
            return new JsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }

        // 6. Vérification post
        if (!$post_id || !$this->is_valid_post($post_id)) {
            error_log("[Reactions DEBUG] Post invalide: $post_id");
            return new JsonResponse([
                'success' => false,
                'error'   => $this->language->lang('REACTION_INVALID_POST')
            ], 400);
        }

        // 7. Vérification emoji
        if ($action !== 'get' && (!$emoji || !$this->is_valid_emoji($emoji))) {
            error_log("[Reactions DEBUG] Emoji invalide: $emoji");
            return new JsonResponse([
                'success' => false,
                'error'   => $this->language->lang('REACTION_INVALID_EMOJI')
            ], 400);
        }

        // 8. Vérification permissions
        if (!$this->can_react_to_post($post_id)) {
            error_log("[Reactions DEBUG] Permission refusée pour post_id=$post_id");
            return new JsonResponse([
                'success' => false,
                'error'   => $this->language->lang('REACTION_NOT_AUTHORIZED')
            ], 403);
        }

        // 9. Logique principale
        switch ($action) {
            case 'add':
                error_log("[Reactions DEBUG] Action=add → post_id=$post_id, emoji=$emoji");
                return $this->add_reaction($post_id, $emoji);

            case 'remove':
                error_log("[Reactions DEBUG] Action=remove → post_id=$post_id, emoji=$emoji");
                return $this->remove_reaction($post_id, $emoji);

            case 'get':
                error_log("[Reactions DEBUG] Action=get → post_id=$post_id");
                return $this->get_reactions($post_id);
        }

        // 10. Cas inattendu
        error_log("[Reactions DEBUG] Action inconnue: $action");
        return new JsonResponse(['success' => false, 'error' => 'Unknown action'], 400);

    } catch (\Throwable $e) {
        error_log('[Reactions DEBUG] Exception attrapée: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        return new JsonResponse(['error' => 'Erreur serveur'], 500);
    }
}


    
/**
 * Add a reaction
 */
private function add_reaction($post_id, $emoji)
{
    error_log("[Reactions DEBUG] add_reaction() post_id=$post_id, emoji=$emoji");

    try {
        $post_id = (int) $post_id;
        $user_id = (int) $this->user->data['user_id'];

        // 1) Récupérer topic_id depuis phpbb_posts (posts_table)
        $sql = 'SELECT topic_id
                FROM ' . $this->posts_table . '
                WHERE post_id = ' . $post_id;
        error_log("[Reactions DEBUG] SQL topic lookup = $sql");
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$row || !isset($row['topic_id'])) {
            error_log("[Reactions DEBUG] post_id=$post_id introuvable ou sans topic_id");
            return new JsonResponse([
                'success' => false,
                'error'   => $this->language->lang('REACTION_INVALID_POST'),
            ], 400);
        }

        $topic_id = (int) $row['topic_id'];

        // 2) Vérifier doublon (même user, même post, même emoji)
        $sql = 'SELECT reaction_id
                FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . $post_id . '
                  AND user_id = ' . $user_id . "
                  AND reaction_emoji = '" . $this->db->sql_escape($emoji) . "'";
        error_log("[Reactions DEBUG] SQL check duplicate = $sql");
        $result = $this->db->sql_query($sql);
        $already = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($already) {
            error_log("[Reactions DEBUG] doublon: user_id=$user_id, post_id=$post_id, emoji=$emoji");
            return new JsonResponse([
                'success' => false,
                'error'   => $this->language->lang('REACTION_ALREADY_ADDED'),
            ], 400);
        }

        // 3) Validation rapide sur longueur emoji vs varchar(10)
        // Attention: utf8mb3_bin + emojis multi-octets; si >10 octets, on refuse proprement.
        if (strlen($emoji) > 10) {
            error_log("[Reactions DEBUG] emoji trop long pour varchar(10): len=" . strlen($emoji));
            return new JsonResponse([
                'success' => false,
                'error'   => $this->language->lang('REACTION_INVALID_EMOJI'),
            ], 400);
        }

        // 4) Insertion
        $sql_ary = [
            'post_id'        => $post_id,
            'topic_id'       => $topic_id,
            'user_id'        => $user_id,
            'reaction_emoji' => $emoji,
            'reaction_time'  => time(),
        ];
        $sql = 'INSERT INTO ' . $this->post_reactions_table . ' '
             . $this->db->sql_build_array('INSERT', $sql_ary);

        error_log("[Reactions DEBUG] SQL insert = $sql");
        $this->db->sql_query($sql);

        // 5) Retourner les réactions mises à jour pour ce post
        return $this->get_reactions($post_id);

    } catch (\Throwable $e) {
        error_log("[Reactions DEBUG] Exception add_reaction: " . $e->getMessage());
        return new JsonResponse([
            'success' => false,
            'error'   => 'Erreur serveur: ' . $e->getMessage(),
        ], 500);
    }
}


/**
 * Remove a reaction
 */
private function remove_reaction($post_id, $emoji)
{
    $sql = 'DELETE FROM ' . $this->post_reactions_table . '
            WHERE post_id = ' . (int) $post_id . '
              AND user_id = ' . (int) $this->user->data['user_id'] . "
              AND emoji = '" . $this->db->sql_escape($emoji) . "'";
    $this->db->sql_query($sql);

    return $this->get_reactions($post_id);
}

/**
 * Get all reactions for a post
 */
private function get_reactions($post_id)
{
    $sql = 'SELECT emoji, COUNT(*) AS count
            FROM ' . $this->post_reactions_table . '
            WHERE post_id = ' . (int) $post_id . '
            GROUP BY emoji';
    $result = $this->db->sql_query($sql);

    $reactions = [];
    while ($row = $this->db->sql_fetchrow($result)) {
        $reactions[$row['emoji']] = (int) $row['count'];
    }
    $this->db->sql_freeresult($result);

    return new JsonResponse([
        'success'   => true,
        'post_id'   => $post_id,
        'reactions' => $reactions,
    ]);
}


    /**
     * Check if a post is valid
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
     * Check if emoji is valid
     */
    private function is_valid_emoji($emoji)
    {
        $json_path = $this->root_path . 'ext/bastien59960/reactions/styles/prosilver/theme/categories.json';
        if (!file_exists($json_path)) {
            return false;
        }
        
        $json_content = file_get_contents($json_path);
        $data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['emojis'])) {
            return false;
        }

        foreach ($data['emojis'] as $category_name => $subcategories) {
            foreach ($subcategories as $subcategory_name => $emojis) {
                foreach ($emojis as $emoji_data) {
                    if ($emoji_data['emoji'] === $emoji) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Check if user can react to a post
     */
    private function can_react_to_post($post_id)
    {
        if ($this->user->data['user_id'] == ANONYMOUS) {
            return false;
        }

        $sql = 'SELECT p.post_id, p.forum_id, p.poster_id, t.topic_status, f.forum_status
                FROM ' . $this->posts_table . ' p
                JOIN ' . $this->topics_table . ' t ON p.topic_id = t.topic_id
                JOIN ' . $this->forums_table . ' f ON p.forum_id = f.forum_id
                WHERE p.post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $post_data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        if (!$post_data) {
            return false;
        }
        
        if ($post_data['topic_status'] == ITEM_LOCKED || $post_data['forum_status'] == ITEM_LOCKED) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if reaction already exists
     */
    private function reaction_exists($post_id, $user_id, $emoji)
    {
        $sql = 'SELECT reaction_id FROM ' . $this->post_reactions_table . ' 
                WHERE post_id = ' . (int) $post_id . ' 
                AND user_id = ' . (int) $user_id . ' 
                AND reaction_emoji = \'' . $this->db->sql_escape($emoji) . '\'';
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return (bool) $exists;
    }

    /**
     * Get reaction count for specific emoji
     */
    private function get_reaction_count($post_id, $emoji)
    {
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->post_reactions_table . ' 
                WHERE post_id = ' . (int) $post_id . ' 
                AND reaction_emoji = \'' . $this->db->sql_escape($emoji) . '\'';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return (int) ($row['count'] ?? 0);
    }

    /**
     * Get topic ID from post ID
     */
    private function get_topic_id($post_id)
    {
        $sql = 'SELECT topic_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return (int) ($row['topic_id'] ?? 0);
    }

} // fin de la classe ajax

