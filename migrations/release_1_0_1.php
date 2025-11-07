<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace bastien59960\reactions\migrations;

class release_1_0_1 extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return array('\\bastien59960\\reactions\\migrations\\release_1_0_0');
    }

    public function update_schema() { return array(); }
    public function revert_schema() { return array(); }

    public function update_data()
    {
        return array(
            array('custom', array(array($this, 'import_old_reactions'))),
            array('config.add', array('bastien59960_reactions_imported', 1)),
        );
    }
    public function revert_data()
    {
        return array(
            array('config.remove', array('bastien59960_reactions_imported')),
        );
    }

    public function import_old_reactions()
    {
        // Récupérer les services
        $log = $this->container->get('log');
        $user = $this->container->get('user');
        $user->add_lang_ext('bastien59960/reactions', 'acp/common');
        $io = null;
        if ($this->container->has('console.io')) {
            $io = $this->container->get('console.io');
        }
        $old_reactions_table = $this->table_prefix . 'reactions';
        $old_types_table = $this->table_prefix . 'reaction_types';
        $new_reactions_table = $this->table_prefix . 'post_reactions';
        if ($io) $io->writeln('  -> Recherche des anciennes tables de réactions...');
        if (!$this->db_tools->sql_table_exists($old_reactions_table) || !$this->db_tools->sql_table_exists($old_types_table)) {
            if ($io) $io->writeln('     <comment>Anciennes tables non trouvées. Importation ignorée.</comment>');
            return;
        }
        $emoji_map = [
            '1f44d.png' => '👍',
            '1f44e.png' => '👎',
            '1f642.png' => '🙂',
            '1f60d.png' => '😍',
            '1f602.png' => '😂',
            '1f611.png' => '😑',
            '1f641.png' => '🙁',
            '1f62f.png' => '😯',
            '1f62d.png' => '😭',
            '1f621.png' => '😡',
            'OMG.png'   => '😮',
        ];
        $sql = 'SELECT reaction_user_id, post_id, topic_id, reaction_file_name, reaction_time
                FROM ' . $old_reactions_table . '
                ORDER BY reaction_time ASC';
        $result = $this->db->sql_query($sql);
        $old_reactions = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_START');
        $total_old = count($old_reactions);
        if ($io) $io->writeln("     <info>{$total_old} anciennes réactions trouvées.</info>");
        if (empty($old_reactions)) {
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_EMPTY');
            return;
        }
        $reactions_to_insert = [];
        $existing_keys = [];
        if ($io) {
            $io->writeln('  -> Conversion des anciennes réactions...');
            $io->progress_start($total_old);
        }
        foreach ($old_reactions as $row) {
            if ($io) $io->progress_advance();
            $png_name = $row['reaction_file_name'];
            if (!isset($emoji_map[$png_name])) { continue; }
            $emoji = $emoji_map[$png_name];
            $post_id = (int) $row['post_id'];
            $user_id = (int) $row['reaction_user_id'];
            $key = $post_id . '-' . $user_id;
            if (isset($existing_keys[$key]) && in_array($emoji, $existing_keys[$key])) { continue; }
            if (!isset($existing_keys[$key])) { $existing_keys[$key] = []; }
            $reactions_to_insert[] = [
                'post_id'           => $post_id,
                'topic_id'          => (int) $row['topic_id'],
                'user_id'           => $user_id,
                'reaction_emoji'    => $emoji,
                'reaction_time'     => (int) $row['reaction_time'],
                'reaction_notified' => 0,
            ];
            $existing_keys[$key][] = $emoji;
        }
        if ($io) $io->progress_finish();
        if (!empty($reactions_to_insert)) {
            $affected_users = [];
            $affected_posts = [];
            foreach ($reactions_to_insert as $reaction) {
                $affected_users[$reaction['user_id']] = true;
                $affected_posts[$reaction['post_id']] = true;
            }
            $count_users = count($affected_users);
            $count_posts = count($affected_posts);
            $count_to_insert = count($reactions_to_insert);
            $skipped_count = $total_old - $count_to_insert;
            if ($io) {
                $io->writeln("     <info>Préparation de l'insertion de {$count_to_insert} réactions pour {$count_users} utilisateurs sur {$count_posts} messages.</info> ({$skipped_count} ignorées/doublons)");
            }
            $this->db->sql_multi_insert($new_reactions_table, $reactions_to_insert);
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array($count_to_insert, $skipped_count, $count_users, $count_posts));
            if ($io) $io->writeln('     <info>Importation terminée avec succès.</info>');
        } else {
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array(0, $total_old, 0, 0));
            if ($io) $io->writeln('     <comment>Aucune nouvelle réaction à importer (toutes ignorées/doublons).</comment>');
        }
    }
}