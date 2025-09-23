<?php
/**
 * Reactions Extension for phpBB 3.3
 * AJAX Controller
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
        // 1. Log de débogage pour s'assurer que le contrôleur est appelé
        error_log('[phpBB Reactions] Controller handle() called');

        // 2. Vérification de la méthode HTTP et du jeton CSRF (commenté pour débogage)
        //if (!$this->request->is_valid_csrf_token()) {
        //    throw new HttpException(403, 'CSRF token is not valid.');
        //}

        // Vérifier si c'est une requête AJAX
        if (!$this->request->is_ajax()) {
            error_log('[phpBB Reactions] Not an AJAX request');
            throw new HttpException(400, 'Bad request');
        }

        // Vérifier l'authentification de l'utilisateur
        if ($this->user->data['user_id'] == ANONYMOUS) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->language->lang('REACTION_NOT_AUTHORIZED'),
            ]);
        }

        // Lire le corps de la requête JSON
        $input = file_get_contents('php://input');
        error_log('[phpBB Reactions] Raw input: ' . $input); // Log pour débogage
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[phpBB Reactions] JSON decode error: ' . json_last_error_msg());
            return new JsonResponse([
                'success' => false,
                'message' => 'INVALID_JSON',
            ]);
        }

        // Valider le SID pour CSRF (nouvelle implémentation)
        $sid = $data['sid'] ?? '';
        if ($sid !== $this->user->data['session_id']) {
            error_log('[phpBB Reactions] Invalid SID: received ' . $sid . ', expected ' . $this->user->data['session_id']);
            throw new HttpException(403, 'Jeton CSRF invalide.');
        }

        // Récupérer et valider les données
        $post_id = $data['post_id'] ?? 0;
        $emoji = $data['reaction_emoji'] ?? '';

        if ($post_id <= 0 || empty($emoji)) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->language->lang('REACTION_INVALID_INPUT'),
            ]);
        }

        // Vérifier les permissions
        if (!$this->auth->acl_get('f_reply', $this->get_forum_id_from_post($post_id))) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->language->lang('REACTION_NOT_AUTHORIZED'),
            ]);
        }

        // Vérifier si le post existe et n'est pas verrouillé
        if (!$this->can_react_to_post($post_id)) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->language->lang('REACTION_INVALID_POST'),
            ]);
        }

        $user_id = $this->user->data['user_id'];
        $topic_id = $this->get_topic_id($post_id);

        // Vérifier si la réaction existe déjà
        if ($this->reaction_exists($post_id, $user_id, $emoji)) {
            // Supprimer la réaction
            $this->remove_reaction($post_id, $user_id, $emoji);
            $user_reacted = false;
        } else {
            // Ajouter la réaction
            $this->add_reaction($post_id, $topic_id, $user_id, $emoji);
            $user_reacted = true;
        }

        // Récupérer le nouveau compteur
        $count = $this->get_reaction_count($post_id, $emoji);

        return new JsonResponse([
            'success' => true,
            'post_id' => $post_id,
            'emoji' => $emoji,
            'count' => $count,
            'user_reacted' => $user_reacted,
        ]);
    }

    /**
     * Add new reaction
     */
    private function add_reaction($post_id, $topic_id, $user_id, $emoji)
    {
        $sql_arr = [
            'post_id' => (int) $post_id,
            'topic_id' => (int) $topic_id,
            'user_id' => (int) $user_id,
            'reaction_emoji' => (string) $emoji,
            'reaction_time' => (int) time(),
        ];
        $sql = 'INSERT INTO ' . $this->post_reactions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_arr);
        $this->db->sql_query($sql);
    }

    /**
     * Remove reaction
     */
    private function remove_reaction($post_id, $user_id, $emoji)
    {
        $sql = 'DELETE FROM ' . $this->post_reactions_table . '
                WHERE post_id = ' . (int) $post_id . '
                AND user_id = ' . (int) $user_id . '
                AND reaction_emoji = \'' . $this->db->sql_escape($emoji) . '\'';
        $this->db->sql_query($sql);
    }

    /**
     * Check if user can react to this post (post exists and not locked)
     */
    private function can_react_to_post($post_id)
    {
        $sql = 'SELECT p.post_id, t.topic_status, f.forum_status
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

    /**
     * Get forum ID from post ID (pour permissions)
     */
    private function get_forum_id_from_post($post_id)
    {
        $sql = 'SELECT forum_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return (int) ($row['forum_id'] ?? 0);
    }
}
