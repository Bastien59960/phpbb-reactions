<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\controller;

use phpbb\json_response;
use phpbb\exception\http_exception;

class main
{
    protected $db;
    protected $user;
    protected $request;
    protected $template;
    protected $auth;
    protected $helper;
    protected $tables;
    protected $reactions_table;

    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\auth\auth $auth,
        \phpbb\controller\helper $helper,
        $tables
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
        $this->template = $template;
        $this->auth = $auth;
        $this->helper = $helper;
        $this->tables = $tables;
        $this->reactions_table = $tables['post_reactions'];
    }

    /**
     * Handle AJAX reaction requests
     */
    public function handle()
    {
        // Check if it's an AJAX request
        if (!$this->request->is_ajax()) {
            throw new http_exception(400, 'AJAX_REQUIRED');
        }

        // Check if user is logged in
        if (!$this->user->data['is_registered']) {
            return $this->json_response('error', 'NOT_LOGGED_IN');
        }

        // Get and validate request data
        $post_id = $this->request->variable('post_id', 0);
        $reaction_unicode = $this->request->variable('reaction_unicode', '', true);

        // Validate input
        if (!$post_id || !$reaction_unicode) {
            return $this->json_response('error', 'MISSING_DATA');
        }

        // Check permissions for this post's forum
        $forum_id = $this->get_forum_id_from_post($post_id);
        if (!$forum_id || !$this->auth->acl_get('f_use_reactions', $forum_id)) {
            return $this->json_response('error', 'NO_PERMISSION');
        }

        try {
            // Begin transaction
            $this->db->sql_transaction('begin');

            $user_id = $this->user->data['user_id'];
            $action = '';

            // Check if user already reacted to this post
            $sql = 'SELECT * 
                FROM ' . $this->reactions_table . ' 
                WHERE user_id = ' . (int) $user_id . ' 
                AND post_id = ' . (int) $post_id;
            $result = $this->db->sql_query($sql);
            $existing_reaction = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if ($existing_reaction) {
                // User already reacted - update or remove
                if ($existing_reaction['reaction_unicode'] === $reaction_unicode) {
                    // Remove reaction (clicking same reaction again)
                    $this->remove_reaction($existing_reaction['reaction_id']);
                    $action = 'removed';
                } else {
                    // Update to new reaction
                    $this->update_reaction($existing_reaction['reaction_id'], $reaction_unicode);
                    $action = 'updated';
                }
            } else {
                // New reaction
                $this->add_reaction($post_id, $user_id, $reaction_unicode);
                $action = 'added';
            }

            // Commit transaction
            $this->db->sql_transaction('commit');

            // Get updated counts and return response
            $counters = $this->get_reactions_count($post_id);
            $user_reaction = $this->get_user_reaction($post_id, $user_id);

            return $this->json_response('success', '', [
                'action' => $action,
                'counters' => $counters,
                'user_reaction' => $user_reaction,
                'post_id' => $post_id
            ]);

        } catch (\Exception $e) {
            $this->db->sql_transaction('rollback');
            return $this->json_response('error', 'DATABASE_ERROR', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Get reactions HTML for a specific post
     */
    public function get_reactions($post_id)
    {
        if (!$post_id) {
            return '';
        }

        // Get forum ID for permission check
        $forum_id = $this->get_forum_id_from_post($post_id);
        if (!$forum_id || !$this->auth->acl_get('f_see_reactions', $forum_id)) {
            return '';
        }

        // Get all available reactions
        $available_reactions = $this->get_available_reactions();
        
        // Get reaction counts for this post
        $reaction_counts = $this->get_reactions_count($post_id);
        
        // Get user's current reaction (if any)
        $user_reaction = $this->user->data['is_registered'] ? 
            $this->get_user_reaction($post_id, $this->user->data['user_id']) : null;

        // Assign template variables
        $this->template->assign_vars([
            'S_IS_REGISTERED'      => $this->user->data['is_registered'],
            'S_CAN_USE_REACTIONS'  => $this->auth->acl_get('f_use_reactions', $forum_id),
            'POST_ID'              => $post_id,
            'USER_REACTION'        => $user_reaction,
        ]);

        // Assign each available reaction
        foreach ($available_reactions as $reaction) {
            $count = isset($reaction_counts[$reaction['unicode']]) ? $reaction_counts[$reaction['unicode']] : 0;
            $is_user_reaction = $user_reaction && $user_reaction['reaction_unicode'] === $reaction['unicode'];
            
            $this->template->assign_block_vars('reactions', [
                'UNICODE'   => $reaction['unicode'],
                'NAME'      => $reaction['name'],
                'COUNT'     => $count,
                'IS_USER'   => $is_user_reaction,
            ]);
        }

        // Render the template
        return $this->template->render('reactions.html');
    }

    /**
     * Get available reactions (hardcoded for now)
     */
    protected function get_available_reactions()
    {
        return [    
            ['unicode' => '👍', 'name' => 'Like'],
            ['unicode' => '❤️', 'name' => 'Love'],
            ['unicode' => '😂', 'name' => 'Laugh'],
            ['unicode' => '😮', 'name' => 'Wow'],
            ['unicode' => '😢', 'name' => 'Sad'],
            ['unicode' => '😠', 'name' => 'Angry'],
        ];
    }

    /**
     * Get reaction counts for a post
     */
    protected function get_reactions_count($post_id)
    {
        $sql = 'SELECT reaction_unicode, COUNT(*) as count
            FROM ' . $this->reactions_table . '
            WHERE post_id = ' . (int) $post_id . '
            GROUP BY reaction_unicode';
        $result = $this->db->sql_query($sql);

        $counters = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $counters[$row['reaction_unicode']] = (int) $row['count'];
        }
        $this->db->sql_freeresult($result);

        return $counters;
    }

    /**
     * Get user's reaction for a post
     */
    protected function get_user_reaction($post_id, $user_id)
    {
        $sql = 'SELECT *
            FROM ' . $this->reactions_table . '
            WHERE post_id = ' . (int) $post_id . '
            AND user_id = ' . (int) $user_id;
        $result = $this->db->sql_query($sql);
        $reaction = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $reaction;
    }

    /**
     * Get forum ID from post ID
     */
    protected function get_forum_id_from_post($post_id)
    {
        $sql = 'SELECT forum_id 
            FROM ' . $this->tables['posts'] . ' 
            WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row ? $row['forum_id'] : false;
    }

    /**
     * Add a new reaction
     */
    protected function add_reaction($post_id, $user_id, $reaction_unicode)
    {
        $topic_id = $this->get_topic_id_from_post($post_id);
        
        $sql_arr = [
            'post_id'          => $post_id,
            'topic_id'         => $topic_id,
            'user_id'          => $user_id,
            'reaction_unicode' => $reaction_unicode,
            'reaction_time'    => time(),
        ];

        $sql = 'INSERT INTO ' . $this->reactions_table . ' 
            ' . $this->db->sql_build_array('INSERT', $sql_arr);
        $this->db->sql_query($sql);
    }

    /**
     * Update existing reaction
     */
    protected function update_reaction($reaction_id, $reaction_unicode)
    {
        $sql_arr = [
            'reaction_unicode' => $reaction_unicode,
            'reaction_time'    => time(),
        ];

        $sql = 'UPDATE ' . $this->reactions_table . ' 
            SET ' . $this->db->sql_build_array('UPDATE', $sql_arr) . '
            WHERE reaction_id = ' . (int) $reaction_id;
        $this->db->sql_query($sql);
    }

    /**
     * Remove reaction
     */
    protected function remove_reaction($reaction_id)
    {
        $sql = 'DELETE FROM ' . $this->reactions_table . ' 
            WHERE reaction_id = ' . (int) $reaction_id;
        $this->db->sql_query($sql);
    }

    /**
     * Get topic ID from post ID
     */
    protected function get_topic_id_from_post($post_id)
    {
        $sql = 'SELECT topic_id 
            FROM ' . $this->tables['posts'] . ' 
            WHERE post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row ? $row['topic_id'] : 0;
    }

    /**
     * JSON response helper
     */
    protected function json_response($status, $message = '', $data = [])
    {
        $response = array_merge([
            'status'  => $status,
            'message' => $message ? $this->user->lang($message) : '',
        ], $data);

        $json_response = new json_response();
        return $json_response->send($response);
    }
}
