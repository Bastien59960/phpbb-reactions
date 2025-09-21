<?php
/**
* Post Reactions extension for phpBB.
*
* @copyright (c) 2025 Bastien59960
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace bastien59960\reactions\controller;

class main
{
    protected $db;
    protected $user;
    protected $request;

    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\request\request $request)
    {
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
    }

    public function handle()
    {
        // 1. On vérifie si l'utilisateur est connecté.
        if (!$this->user->data['is_registered']) {
            return array('status' => 'error', 'message' => 'Not logged in');
        }

        // 2. On récupère les données envoyées par la requête AJAX.
        $post_id = $this->request->variable('post_id', 0);
        $reaction_unicode = $this->request->variable('reaction', '', true);

        // 3. On vérifie que les données sont valides.
        if (!$post_id || !$reaction_unicode) {
            return array('status' => 'error', 'message' => 'Missing data');
        }

        // 4. On gère la logique de la base de données.
        $user_id = $this->user->data['user_id'];
        $table_name = $this->db->sql_table('post_reactions');

        // On vérifie si l'utilisateur a déjà réagi à ce post.
        $sql = 'SELECT reaction_id, reaction_unicode
            FROM ' . $table_name . '
            WHERE user_id = ' . (int) $user_id . '
            AND post_id = ' . (int) $post_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($row) {
            // L'utilisateur a déjà réagi.
            if ($row['reaction_unicode'] === $reaction_unicode) {
                // Il reclique sur le même emoji, on supprime sa réaction.
                $sql = 'DELETE FROM ' . $table_name . '
                    WHERE reaction_id = ' . (int) $row['reaction_id'];
                $this->db->sql_query($sql);
                $action = 'removed';
            } else {
                // Il change d'emoji, on met à jour sa réaction.
                $sql_array = array(
                    'reaction_unicode' => $reaction_unicode,
                    'reaction_time'    => time(),
                );
                $sql = 'UPDATE ' . $table_name . '
                    SET ' . $this->db->sql_build_array('UPDATE', $sql_array) . '
                    WHERE reaction_id = ' . (int) $row['reaction_id'];
                $this->db->sql_query($sql);
                $action = 'updated';
            }
        } else {
            // C'est une nouvelle réaction, on l'ajoute.
            $sql_array = array(
                'post_id'          => $post_id,
                'user_id'          => $user_id,
                'reaction_unicode' => $reaction_unicode,
                'reaction_time'    => time(),
                // NOTE: On ajoutera le topic_id plus tard, c'est mieux de le faire côté front-end.
            );
            $sql = 'INSERT INTO ' . $table_name . ' ' . $this->db->sql_build_array('INSERT', $sql_array);
            $this->db->sql_query($sql);
            $action = 'added';
        }

        // 5. On renvoie les compteurs mis à jour au front-end.
        $counters = $this->get_reactions_count($post_id);

        return array(
            'status'   => 'success',
            'action'   => $action,
            'counters' => $counters,
        );
    }

    protected function get_reactions_count($post_id)
    {
        $sql = 'SELECT reaction_unicode, COUNT(reaction_id) as count
            FROM ' . $this->db->sql_table('post_reactions') . '
            WHERE post_id = ' . (int) $post_id . '
            GROUP BY reaction_unicode';
        $result = $this->db->sql_query($sql);

        $counters = array();
        while ($row = $this->db->sql_fetchrow($result)) {
            $counters[$row['reaction_unicode']] = (int) $row['count'];
        }
        $this->db->sql_freeresult($result);

        return $counters;
    }
}
