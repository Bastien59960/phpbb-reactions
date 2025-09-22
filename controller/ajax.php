<?php
/**
 * Reactions Extension for phpBB 3.3
 * AJAX Controller
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\controller;

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
        $posts_table
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
        $this->auth = $auth;
        $this->language = $language;
        $this->post_reactions_table = $post_reactions_table;
        $this->posts_table = $posts_table;
        
        $this->language->add_lang('common', 'bastien59960/reactions');
    }

    /**
     * Handle AJAX reactions
     */
    public function handle()
    {
        // Vérifier que c'est une requête AJAX
        if (!$this->request->is_ajax()) {
            return $this->json_response(['error' => 'Invalid request'], 400);
        }

        // Vérifier l'authentification
        if ($this->user->data['user_id'] == ANONYMOUS) {
            return $this->json_response(['error' => $this->language->lang('REACTION_NOT_AUTHORIZED')], 403);
        }

        $action = $this->request->variable('action', '');
        $post_id = $this->request->variable('post_id', 0);
        $emoji = $this->request->variable('emoji', '', true);
        
        // Vérifications de base
        if (!in_array($action, ['add', 'remove', 'get'])) {
            return $this->json_response(['error' => 'Invalid action'], 400);
        }
        
        if (!$post_id || !$this->is_valid_post($post_id)) {
            return $this->json_response(['error' => $this->language->lang('REACTION_INVALID_POST')], 400);
        }
        
        if ($action !== 'get' && (!$emoji || !$this->is_valid_emoji($emoji))) {
            return $this->json_response(['error' => $this->language->lang('REACTION_INVALID_EMOJI')], 400);
        }

        // Vérifier les permissions
        if (!$this->can_react_to_post($post_id)) {
            return $this->json_response(['error' => $this->language->lang('REACTION_NOT_AUTHORIZED')], 403);
        }

        switch ($action) {
            case 'add':
                return $this->add_reaction($post_id, $emoji);
            case 'remove':
                return $this->remove_reaction($post_id, $emoji);
            case 'get':
                return $this->get_reactions($post_id);
            default:
                return $this->json_response(['error' => 'Unknown action'], 400);
        }
    }

    /**
     * Add a reaction
     */
    private function add_reaction($post_id, $emoji)
    {
        $user_id = $this->user->data['user_id'];
        
        // Vérifier si la réaction existe déjà
        if ($this->reaction_exists($post_id, $user_id, $emoji)) {
            return $this->json_response(['error' => $this->language->lang('REACTION_ALREADY_EXISTS')], 400);
        }
        
        // Obtenir le topic_id
        $topic_id = $this->get_topic_id($post_id);
        
        // Ajouter la réaction
        $sql = 'INSERT INTO ' . $this->post_reactions_table . ' 
                (post_id, topic_id, user_id, reaction_emoji, reaction_time) 
                VALUES (' . (int) $post_id . ', ' . (int) $topic_id . ', ' . (int) $user_id . ', \'' . $this->db->sql_escape($emoji) . '\', ' . time() . ')';
        
        $this->db->sql_query($sql);
        
        if ($this->db->sql_affectedrows() > 0) {
            $count = $this->get_reaction_count($post_id, $emoji);
            return $this->json_response([
                'success' => true,
                'message' => $this->language->lang('REACTION_SUCCESS_ADD'),
                'count' => $count,
                'user_reacted' => true
            ]);
        } else {
            return $this->json_response(['error' => $this->language->lang('REACTION_ERROR')], 500);
        }
    }

    /**
     * Remove a reaction
     */
    private function remove_reaction($post_id, $emoji)
    {
        $user_id = $this->user->data['user_id'];
        
        // Supprimer la réaction
        $sql = 'DELETE FROM ' . $this->post_reactions_table . ' 
                WHERE post_id = ' . (int) $post_id . ' 
                AND user_id = ' . (int) $user_id . ' 
                AND reaction_emoji = \'' . $this->db->sql_escape($emoji) . '\'';
        
        $this->db->sql_query($sql);
        
        if ($this->db->sql_affectedrows() > 0) {
            $count = $this->get_reaction_count($post_id, $emoji);
            return $this->json_response([
                'success' => true,
                'message' => $this->language->lang('REACTION_SUCCESS_REMOVE'),
                'count' => $count,
                'user_reacted' => false
            ]);
        } else {
            return $this->json_response(['error' => $this->language->lang('REACTION_NOT_FOUND')], 404);
        }
    }

    /**
     * Get reactions for a post
     */
    private function get_reactions($post_id)
    {
        $reactions = [];
        $user_reactions = [];
        
        // Obtenir toutes les réactions pour ce post
        $sql = 'SELECT reaction_emoji, COUNT(*) as reaction_count 
                FROM ' . $this->post_reactions_table . ' 
                WHERE post_id = ' . (int) $post_id . ' 
                GROUP BY reaction_emoji';
        $result = $this->db->sql_query($sql);
        
        while ($row = $this->db->sql_fetchrow($result)) {
            $reactions[$row['reaction_emoji']] = (int) $row['reaction_count'];
        }
        $this->db->sql_freeresult($result);
        
        // Obtenir les réactions de l'utilisateur actuel
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
        
        return $this->json_response([
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
     * Check if emoji is valid (basic Unicode emoji validation)
     */
    private function is_valid_emoji($emoji)
    {
        // Liste des emojis autorisés (peut être étendue)
        $allowed_emojis = [
            '😀', '😁', '😂', '🤣', '😃', '😄', '😅', '😆', '😉', '😊',
            '😋', '😎', '😍', '😘', '🥰', '😗', '😙', '😚', '😇', '🥳',
            '😈', '👿', '😠', '😡', '🤬', '😱', '😰', '😨', '😧', '😦',
            '😮', '😯', '😲', '🤯', '😳', '🥺', '😢', '😭', '😤', '😪',
            '👍', '👎', '👌', '✌️', '🤞', '👏', '🙌', '👐', '🤲', '🙏',
            '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔',
            '💯', '💥', '💢', '💨', '💫', '⭐', '🌟', '✨', '⚡', '🔥'
        ];
        
        return in_array($emoji, $allowed_emojis) && mb_strlen($emoji, 'UTF-8') <= 4;
    }

    /**
     * Check if user can react to a post
     */
    private function can_react_to_post($post_id)
    {
        // Obtenir les informations du post et du forum
        $sql = 'SELECT p.post_id, p.forum_id, p.poster_id, t.topic_status, f.forum_status
                FROM ' . $this->posts_table . ' p
                JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id
                JOIN ' . FORUMS_TABLE . ' f ON p.forum_id = f.forum_id
                WHERE p.post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $post_data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        if (!$post_data) {
            return false;
        }
        
        // Vérifier les permissions de lecture du forum
        if (!$this->auth->acl_get('f_read', $post_data['forum_id'])) {
            return false;
        }
        
        // Vérifier si le topic/forum est ouvert
        if ($post_data['topic_status'] == ITEM_LOCKED || $post_data['forum_status'] == ITEM_LOCKED) {
            return false;
        }
        
        // Les utilisateurs peuvent réagir s'ils peuvent lire (peut être personnalisé)
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

    /**
     * Send JSON response
     */
    private function json_response($data, $status_code = 200)
    {
        // Définir les headers
        header('Content-Type: application/json');
        http_response_code($status_code);
        
        echo json_encode($data);
        exit;
    }
}
