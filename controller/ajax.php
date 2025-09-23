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
        // Vérification de la méthode HTTP et du jeton CSRF
        if (!$this->request->is_valid_csrf_token() || $this->request->method('POST') === false) {
            throw new HttpException(403, 'Requête invalide ou jeton CSRF manquant.');
        }

        // Vérification de l'authentification de l'utilisateur
        if ($this->user->data['user_id'] == ANONYMOUS) {
            throw new HttpException(403, 'Vous devez être connecté pour réagir.');
        }

        // Récupérer le corps de la requête JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new HttpException(400, 'Données JSON invalides.');
        }

        // Récupérer les variables depuis le tableau JSON
        $post_id = $data['post_id'] ?? 0;
        $emoji = $data['reaction_emoji'] ?? '';

        // Vérifications de base
        if (!$post_id || !$this->is_valid_post($post_id)) {
            throw new HttpException(400, $this->language->lang('REACTION_INVALID_POST'));
        }
        
        if (empty($emoji) || !$this->is_valid_emoji($emoji)) {
            throw new HttpException(400, $this->language->lang('REACTION_INVALID_EMOJI'));
        }
        
        // Vérifier les permissions
        if (!$this->can_react_to_post($post_id)) {
            throw new HttpException(403, $this->language->lang('REACTION_NOT_AUTHORIZED'));
        }

        // Gérer la logique d'ajout ou de suppression de la réaction
        $user_reacted = $this->reaction_exists($post_id, $this->user->data['user_id'], $emoji);

        if ($user_reacted) {
            // L'utilisateur a déjà réagi, on supprime la réaction
            $this->remove_reaction($post_id, $emoji);
            $user_reacted = false;
        } else {
            // L'utilisateur n'a pas réagi, on l'ajoute
            $topic_id = $this->get_topic_id($post_id);
            $this->add_reaction($post_id, $topic_id, $emoji);
            $user_reacted = true;
        }

        // Calcul du nouveau total pour l'emoji
        $count = $this->get_reaction_count($post_id, $emoji);

        // Retourner la réponse JSON finale
        return new JsonResponse([
            'success'      => true,
            'count'        => $count,
            'user_reacted' => $user_reacted
        ]);
    }

    /**
     * Add a reaction
     */
    private function add_reaction($post_id, $topic_id, $emoji)
    {
        $sql = 'INSERT INTO ' . $this->post_reactions_table . ' 
                (post_id, topic_id, user_id, reaction_emoji, reaction_time) 
                VALUES (' . (int) $post_id . ', ' . (int) $topic_id . ', ' . (int) $this->user->data['user_id'] . ', \'' . $this->db->sql_escape($emoji) . '\', ' . time() . ')';
        
        $this->db->sql_query($sql);
    }

    /**
     * Remove a reaction
     */
    private function remove_reaction($post_id, $emoji)
    {
        $sql = 'DELETE FROM ' . $this->post_reactions_table . ' 
                WHERE post_id = ' . (int) $post_id . ' 
                AND user_id = ' . (int) $this->user->data['user_id'] . ' 
                AND reaction_emoji = \'' . $this->db->sql_escape($emoji) . '\'';
        
        $this->db->sql_query($sql);
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
        // Lire le contenu du fichier categories.json
        $json_path = $this->root_path . 'ext/bastien59960/reactions/styles/prosilver/template/categories.json';
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
        // Si l'utilisateur est anonyme, il ne peut pas réagir
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
}
