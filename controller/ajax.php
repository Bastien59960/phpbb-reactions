<?php
/**
 * Reactions Extension for phpBB 3.3
 * AJAX Controller
 * * @copyright (c) 2025 Bastien59960
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
    // 1. Log de débogage pour s'assurer que le contrôleur est appelé
    error_log('[phpBB Reactions] Controller handle() called');

    // 2. Vérification de l'authentification de l'utilisateur
    if ($this->user->data['user_id'] == ANONYMOUS) {
        throw new HttpException(403, 'User not logged in.');
    }

    // 3. Récupérer le corps de la requête JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        throw new HttpException(400, 'Invalid JSON data');
    }
    
    $sid = $data['sid'] ?? '';
    
    if ($sid !== $this->user->data['session_id']) {
        throw new HttpException(403, 'Jeton CSRF invalide.');
    }

    // 4. Récupérer les variables depuis le tableau JSON
    $post_id = $data['post_id'] ?? 0;
    $emoji = $data['emoji'] ?? '';
    $action = $data['action'] ?? '';

    // 5. Vérifications de base
    if (!in_array($action, ['add', 'remove', 'get'])) {
        return new JsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
    
    if (!$post_id || !$this->is_valid_post($post_id)) {
        return new JsonResponse(['success' => false, 'error' => $this->language->lang('REACTION_INVALID_POST')], 400);
    }
    
    if ($action !== 'get' && (!$emoji || !$this->is_valid_emoji($emoji))) {
        return new JsonResponse(['success' => false, 'error' => $this->language->lang('REACTION_INVALID_EMOJI')], 400);
    }

    // 6. Vérifier les permissions
    if (!$this->can_react_to_post($post_id)) {
        return new JsonResponse(['success' => false, 'error' => $this->language->lang('REACTION_NOT_AUTHORIZED')], 403);
    }

    // 7. Logique principale
    switch ($action) {
        case 'add':
            return $this->add_reaction($post_id, $emoji);
        case 'remove':
            return $this->remove_reaction($post_id, $emoji);
        case 'get':
            return $this->get_reactions($post_id);
        default:
            return new JsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
    }
}
    
    /**
     * Add a reaction
     */
    private function add_reaction($post_id, $emoji)
    {
        $user_id = $this->user->data['user_id'];
        
        if ($this->reaction_exists($post_id, $user_id, $emoji)) {
            return new JsonResponse(['success' => false, 'error' => $this->language->lang('REACTION_ALREADY_EXISTS')], 400);
        }
        
        $topic_id = $this->get_topic_id($post_id);
        
        $sql = 'INSERT INTO ' . $this->post_reactions_table . ' 
                 (post_id, topic_id, user_id, reaction_emoji, reaction_time) 
                 VALUES (' . (int) $post_id . ', ' . (int) $topic_id . ', ' . (int) $user_id . ', \'' . $this->db->sql_escape($emoji) . '\', ' . time() . ')';
        
        $this->db->sql_query($sql);
        
        if ($this->db->sql_affectedrows() > 0) {
            $count = $this->get_reaction_count($post_id, $emoji);
            return new JsonResponse([
                'success' => true,
                'message' => $this->language->lang('REACTION_SUCCESS_ADD'),
                'count' => $count,
                'user_reacted' => true
            ]);
        } else {
            return new JsonResponse(['success' => false, 'error' => $this->language->lang('REACTION_ERROR')], 500);
        }
    }

    /**
     * Remove a reaction
     */
    private function remove_reaction($post_id, $emoji)
    {
        $user_id = $this->user->data['user_id'];
        
        $sql = 'DELETE FROM ' . $this->post_reactions_table . ' 
                WHERE post_id = ' . (int) $post_id . ' 
                AND user_id = ' . (int) $user_id . ' 
                AND reaction_emoji = \'' . $this->db->sql_escape($emoji) . '\'';
        
        $this->db->sql_query($sql);
        
        if ($this->db->sql_affectedrows() > 0) {
            $count = $this->get_reaction_count($post_id, $emoji);
            return new JsonResponse([
                'success' => true,
                'message' => $this->language->lang('REACTION_SUCCESS_REMOVE'),
                'count' => $count,
                'user_reacted' => false
            ]);
        } else {
            return new JsonResponse(['success' => false, 'error' => $this->language->lang('REACTION_NOT_FOUND')], 404);
        }
    }

    /**
     * Get reactions for a post
     */
    private function get_reactions($post_id)
    {
        $reactions = [];
        $user_reactions = [];
        
        $sql = 'SELECT reaction_emoji, COUNT(*) as reaction_count 
                FROM ' . $this->post_reactions_table . ' 
                WHERE post_id = ' . (int) $post_id . ' 
                GROUP BY reaction_emoji';
        $result = $this->db->sql_query($sql);
        
        while ($row = $this->db->sql_fetchrow($result)) {
            $reactions[$row['reaction_emoji']] = (int) $row['reaction_count'];
        }
        $this->db->sql_freeresult($result);
        
        if ($this->user->data['user_id'] != ANONYMOUS) {
            $sql = 'SELECT reaction_emoji 
                    FROM ' . $this->post_reactions_table . ' 
                    WHERE post_id = ' . (int) $post_id . ' 
                    AND user_id = ' . (int) $this->user->data['user_id'];
            $result = $this->db->sql_query($sql);
            
            while ($row = $this->db->sql_fetchrow($result)) {
                $user_reactions[] = $row['reaction_emoji'];
            }
            $this->db->sql_freeresult($result);
        }
        
        return new JsonResponse([
            'success' => true,
            'reactions' => $reactions,
            'user_reactions' => $user_reactions
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

