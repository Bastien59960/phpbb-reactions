<?php
/**
 * Migration d'import des réactions depuis l'ancienne extension
 *
 * Cette migration importe les données depuis une ancienne extension de réactions
 * (table phpbb_reactions) vers la nouvelle structure (table phpbb_post_reactions).
 *
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

class release_1_0_1 extends \phpbb\db\migration\container_aware_migration
{
    /**
     * Dépendance : installation de base requise
     */
    public static function depends_on()
    {
        return ['\bastien59960\reactions\migrations\release_1_0_0'];
    }

    /**
     * Vérifie si l'import a déjà été effectué
     */
    public function effectively_installed()
    {
        return isset($this->config['bastien59960_reactions_imported_from_old']);
    }

    /**
     * Mise à jour des données
     */
    public function update_data()
    {
        return [
            ['custom', [[$this, 'import_from_old_extension']]],
            ['config.add', ['bastien59960_reactions_imported_from_old', 1]],
        ];
    }

    /**
     * Pas de réversion (l'import n'est pas réversible)
     */
    public function revert_data()
    {
        return [
            ['config.remove', ['bastien59960_reactions_imported_from_old']],
        ];
    }

    /**
     * Importe les réactions depuis l'ancienne extension
     *
     * Correspondance des fichiers PNG vers emojis :
     * - 1f44d.png → 👍
     * - 1f44e.png → 👎
     * - 1f642.png → 🙂
     * - 1f60d.png → 😍
     * - 1f602.png → 😂
     * - 1f611.png → 😑
     * - 1f641.png → 🙁
     * - 1f62f.png → 😯
     * - 1f62d.png → 😭
     * - 1f621.png → 😡
     * - OMG.png → 😮
     */
    public function import_from_old_extension()
    {
        $log = $this->container->get('log');
        $user = $this->container->get('user');
        $user->add_lang_ext('bastien59960/reactions', 'acp/common');

        $old_table = $this->table_prefix . 'reactions';
        $new_table = $this->table_prefix . 'post_reactions';

        // Vérifier si l'ancienne table existe
        if (!$this->db_tools->sql_table_exists($old_table)) {
            return true;
        }

        try {
            // Forcer UTF8MB4 pour les emojis
            $this->db->sql_query("SET NAMES 'utf8mb4'");

            // Correspondance PNG → Emoji
            $emoji_map = [
                '1f44d.png' => '👍', '1f44e.png' => '👎', '1f642.png' => '🙂',
                '1f60d.png' => '😍', '1f602.png' => '😂', '1f611.png' => '😑',
                '1f641.png' => '🙁', '1f62f.png' => '😯', '1f62d.png' => '😭',
                '1f621.png' => '😡', 'OMG.png'   => '😮',
            ];

            // Lire les anciennes réactions
            $sql = 'SELECT reaction_user_id, post_id, topic_id, reaction_file_name, reaction_time
                    FROM ' . $old_table . ' ORDER BY reaction_time ASC';
            $result = $this->db->sql_query($sql);
            $old_reactions = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);

            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_START');

            if (empty($old_reactions)) {
                $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_EMPTY');
                return true;
            }

            // Récupérer les réactions déjà existantes (pour éviter les doublons)
            $sql = 'SELECT post_id, user_id, reaction_emoji FROM ' . $new_table;
            $result = $this->db->sql_query($sql);
            $existing_keys = [];
            while ($row = $this->db->sql_fetchrow($result)) {
                $key = $row['post_id'] . '-' . $row['user_id'] . '-' . $row['reaction_emoji'];
                $existing_keys[$key] = true;
            }
            $this->db->sql_freeresult($result);

            // Préparer les données à insérer
            $reactions_to_insert = [];
            $total_old = count($old_reactions);

            foreach ($old_reactions as $row) {
                $png_name = $row['reaction_file_name'];
                if (!isset($emoji_map[$png_name])) {
                    continue;
                }

                $emoji = $emoji_map[$png_name];
                $post_id = (int) $row['post_id'];
                $user_id = (int) $row['reaction_user_id'];
                $key = $post_id . '-' . $user_id . '-' . $emoji;

                // Ignorer si déjà existant
                if (isset($existing_keys[$key])) {
                    continue;
                }

                $reactions_to_insert[] = [
                    'post_id'           => $post_id,
                    'topic_id'          => (int) $row['topic_id'],
                    'user_id'           => $user_id,
                    'reaction_emoji'    => $emoji,
                    'reaction_time'     => (int) $row['reaction_time'],
                    'reaction_notified' => 0,
                ];

                $existing_keys[$key] = true;
            }

            // Insérer les nouvelles réactions
            if (!empty($reactions_to_insert)) {
                $count_inserted = count($reactions_to_insert);
                $count_skipped = $total_old - $count_inserted;

                $this->db->sql_multi_insert($new_table, $reactions_to_insert);

                $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS',
                    false, [$count_inserted, $count_skipped, 0, 0]);
            }
        } catch (\Throwable $e) {
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_FAILED',
                false, [$e->getMessage()]);
        }

        return true;
    }
}
