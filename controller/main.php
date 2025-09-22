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
        if ($this->user->data['user_id'] == 0) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => 'AUTH_REQUIRED',
            ]);
        }

        // Get and validate POST data
        $post_id = $this->request->variable('post_id', 0);
        $reaction_unicode = $this->request->variable('reaction_unicode', '', true);

        if ($post_id == 0 || empty($reaction_unicode)) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => 'INVALID_INPUT',
            ]);
        }
        
        // Check if user has already reacted
        $sql = 'SELECT * FROM ' . $this->reactions_table . '
            WHERE post_id = ' . (int) $post_id . '
            AND user_id = ' . (int) $this->user->data['user_id'];
        $result = $this->db->sql_query($sql);
        $existing_reaction = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        
        $current_user_reaction = null;
        
        if ($existing_reaction) {
            // User has a reaction
            if ($existing_reaction['reaction_unicode'] == $reaction_unicode) {
                // Same reaction, so remove it
                $this->remove_reaction($existing_reaction['reaction_id']);
                $current_user_reaction = '';
            } else {
                // Different reaction, so update it
                $this->update_reaction($existing_reaction['reaction_id'], $reaction_unicode);
                $current_user_reaction = $reaction_unicode;
            }
        } else {
            // User has no reaction, so add a new one
            $topic_id = $this->get_topic_id_from_post($post_id);
            if ($topic_id > 0) {
                $this->add_reaction($post_id, $topic_id, $reaction_unicode);
                $current_user_reaction = $reaction_unicode;
            }
        }

        // Get updated counts for the post
        $updated_counts = $this->get_reaction_counts($post_id);

        return new JsonResponse([
            'status'        => 'success',
            'post_id'       => $post_id,
            'counters'      => $updated_counts,
            'user_reaction' => $current_user_reaction,
        ]);
    }

    /**
     * Add new reaction
     */
    protected function add_reaction($post_id, $topic_id, $reaction_unicode)
    {
        $sql_arr = [
            'post_id'          => (int) $post_id,
            'topic_id'         => (int) $topic_id,
            'user_id'          => (int) $this->user->data['user_id'],
            'reaction_unicode' => (string) $reaction_unicode,
            'reaction_time'    => (int) time(),
        ];
        $sql = 'INSERT INTO ' . $this->reactions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_arr);
        $this->db->sql_query($sql);
    }
    
    /**
     * Update reaction
     */
    protected function update_reaction($reaction_id, $reaction_unicode)
    {
        $sql_arr = [
            'reaction_unicode' => (string) $reaction_unicode,
            'reaction_time'    => (int) time(),
        ];
        $sql = 'UPDATE ' . $this->reactions_table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_arr) . ' WHERE reaction_id = ' . (int) $reaction_id;
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
     * Get reaction counts for a specific post
     */
    protected function get_reaction_counts($post_id)
    {
        $sql = 'SELECT reaction_unicode, COUNT(reaction_id) as reaction_count FROM ' . $this->reactions_table . '
            WHERE post_id = ' . (int) $post_id . '
            GROUP BY reaction_unicode
            ORDER BY reaction_count DESC';
        $result = $this->db->sql_query($sql);
        
        $counts = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $counts[$row['reaction_unicode']] = (int) $row['reaction_count'];
        }
        $this->db->sql_freeresult($result);
        
        return $counts;
    }
    
    /**
     * Get topic ID from post ID
     */
    protected function get_topic_id_from_post($post_id)
    {
        $sql = 'SELECT topic_id FROM ' . $this->posts_table . ' WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row ? (int) $row['topic_id'] : 0;
    }
}
