<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class main
{
    protected $db;
    protected $user;
    protected $request;
    protected $template;
    protected $auth;
    protected $helper;
    protected $reactions_table;
    protected $posts_table;

    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\auth\auth $auth,
        \phpbb\controller\helper $helper,
        $reactions_table,
        $posts_table
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
        $this->template = $template;
        $this->auth = $auth;
        $this->helper = $helper;
        $this->reactions_table = $reactions_table;
        $this->posts_table = $posts_table;
    }

    /**
     * Handle AJAX reaction requests
     */
    public function handle()
    {
        // Check if it's an AJAX request
        if (!$this->request->is_ajax()) {
            throw new HttpException(400, 'Bad request');
        }

        // Check user authentication
        if ($this->user->data['user_id'] == ANONYMOUS) {
            return new JsonResponse([
                'success'  => false,
                'error' => 'AUTH_REQUIRED',
            ]);
        }

        // Get and validate POST data
        $post_id = $this->request->variable('post_id', 0);
        $reaction_emoji = $this->request->variable('emoji', '', true);
        $action = $this->request->variable('action', '');

        if ($post_id == 0 || empty($reaction_emoji)) {
            return new JsonResponse([
                'success'  => false,
                'error' => 'INVALID_INPUT',
            ]);
        }

        // Vérifier que le post existe
        $sql = 'SELECT topic_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $post_data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$post_data) {
            return new JsonResponse([
                'success' => false,
                'error' => 'POST_NOT_FOUND'
            ]);
        }

        $topic_id = $post_data['topic_id'];
        
        // Check if user has already reacted with this emoji
        $sql = 'SELECT reaction_id FROM ' . $this->reactions_table . '
            WHERE post_id = ' . (int) $post_id . '
            AND user_id = ' . (int) $this->user->data['user_id'] . '
            AND reaction_emoji = \'' . $this->db->sql_escape($reaction_emoji) . '\'';
        $result = $this->db->sql_query($sql);
        $existing_reaction = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        if ($existing_reaction) {
            // User already reacted with this emoji, remove it
            $this->remove_reaction($existing_reaction['reaction_id']);
            $user_reacted = false;
        } else {
            // Add new reaction
            $this->add_reaction($post_id, $topic_id, $reaction_emoji);
            $user_reacted = true;
        }

        // Get updated count for this specific emoji
        $count = $this->get_reaction_count($post_id, $reaction_emoji);

        return new JsonResponse([
            'success' => true,
            'post_id' => $post_id,
            'emoji' => $reaction_emoji,
            'count' => $count,
            'user_reacted' => $user_reacted
        ]);
    }

    /**
     * Add new reaction
     */
    protected function add_reaction($post_id, $topic_id, $reaction_emoji)
    {
        $sql_arr = [
            'post_id'          => (int) $post_id,
            'topic_id'         => (int) $topic_id,
            'user_id'          => (int) $this->user->data['user_id'],
            'reaction_emoji'   => (string) $reaction_emoji, // Corrigé: reaction_emoji au lieu de reaction_unicode
            'reaction_time'    => (int) time(),
        ];
        $sql = 'INSERT INTO ' . $this->reactions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_arr);
        $this->db->sql_query($sql);
    }
    
    /**
     * Remove reaction
     */
    protected function remove_reaction($reaction_id)
    {
        $sql = 'DELETE FROM ' . $this->reactions_table . ' WHERE reaction_id = ' . (int) $reaction_id;
        $this->db->sql_query($sql);
    }
    
    /**
     * Get reaction count for a specific emoji on a post
     */
    protected function get_reaction_count($post_id, $emoji)
    {
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->reactions_table . '
            WHERE post_id = ' . (int) $post_id . '
            AND reaction_emoji = \'' . $this->db->sql_escape($emoji) . '\'';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        return (int) $row['count'];
    }
}
