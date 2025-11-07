<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /migrations/release_1_0_0.php
 * --------------------------------------------------------------
 * Rôle :
 * Ce script gère l'installation initiale de l'extension Reactions.
 * Il crée les tables, colonnes, configurations, notifications et module UCP nécessaires.
 * --------------------------------------------------------------
 */

namespace bastien59960\reactions\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $sql = 'SELECT notification_type_id
                FROM ' . $types_table . " 
                WHERE notification_type_name = 'notification.type.reaction'";
        $result = $this->db->sql_query($sql);
        $exists = (bool) $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return (
            $this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions') &&
            $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_reactions_notify') &&
            $exists
        );
    }

    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v33x\v3310');
    }

    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'post_reactions' => array(
                    'COLUMNS' => array(
                        'reaction_id'       => array('UINT', null, 'auto_increment'),
                        'post_id'           => array('UINT', 0),
                        'topic_id'          => array('UINT', 0),
                        'user_id'           => array('UINT', 0),
                        'reaction_emoji'    => array('VCHAR:191', ''),
                        'reaction_time'     => array('UINT:11', 0),
                        'reaction_notified' => array('BOOL', 0),
                    ),
                    'PRIMARY_KEY' => 'reaction_id',
                    'KEYS' => array(
                        'post_id'           => array('INDEX', 'post_id'),
                        'topic_id'          => array('INDEX', 'topic_id'),
                        'user_id'           => array('INDEX', 'user_id'),
                        'post_notified_idx' => array('INDEX', array('post_id', 'reaction_notified')),
                    ),
                ),
            ),
            'add_columns' => array(
                $this->table_prefix . 'users' => array(
                    'user_reactions_notify'     => array('BOOL', 1),
                    'user_reactions_cron_email' => array('BOOL', 1),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'post_reactions',
            ),
            'drop_columns' => array(
                $this->table_prefix . 'users' => array(
                    'user_reactions_notify',
                    'user_reactions_cron_email',
                ),
            ),
        );
    }

    public function update_data()
    {
        return array(
            // Configurations de base
            array('config.add', array('bastien59960_reactions_enabled', 1)),
            array('config.add', array('bastien59960_reactions_max_per_post', 20)),
            array('config.add', array('bastien59960_reactions_max_per_user', 10)),
            array('config.add', array('reactions_ucp_preferences_installed', 1)),

            // Configurations interface
            array('config.add', array('bastien59960_reactions_post_emoji_size', 24)),
            array('config.add', array('bastien59960_reactions_picker_width', 320)),
            array('config.add', array('bastien59960_reactions_picker_height', 280)),
            array('config.add', array('bastien59960_reactions_picker_show_categories', 1)),
            array('config.add', array('bastien59960_reactions_picker_show_search', 1)),
            array('config.add', array('bastien59960_reactions_picker_use_json', 1)),
            array('config.add', array('bastien59960_reactions_picker_emoji_size', 24)),
            array('config.add', array('bastien59960_reactions_sync_interval', 5000)),

            // Ajout du module UCP
            array('module.add', array(
                'ucp',
                'UCP_PREFS',
                array(
                    'module_basename'   => '\bastien59960\reactions\ucp\reactions_module',
                    'modes'             => array('settings'),
                ),
            )),

            // Ajout des types de notifications
            array('notification.type.add', array('notification.type.reaction')),
            array('notification.type.add', array('notification.type.reaction_email_digest')),

            // Étapes personnalisées
            array('custom', array(array($this, 'set_utf8mb4_bin'))),
            array('custom', array(array($this, 'clean_orphan_notifications'))), // Keep this for cleanup
        );
    }

    public function revert_data()
    {
        return array(
            // Suppression des configs
            array('config.remove', array('bastien59960_reactions_enabled')),
            array('config.remove', array('bastien59960_reactions_max_per_post')),
            array('config.remove', array('bastien59960_reactions_max_per_user')),
            array('config.remove', array('reactions_ucp_preferences_installed')),

            array('config.remove', array('bastien59960_reactions_post_emoji_size')),
            array('config.remove', array('bastien59960_reactions_picker_width')),
            array('config.remove', array('bastien59960_reactions_picker_height')),
            array('config.remove', array('bastien59960_reactions_picker_show_categories')),
            array('config.remove', array('bastien59960_reactions_picker_show_search')),
            array('config.remove', array('bastien59960_reactions_picker_use_json')),
            array('config.remove', array('bastien59960_reactions_picker_emoji_size')),
            array('config.remove', array('bastien59960_reactions_sync_interval')),

            // Suppression du module UCP
            array('module.remove', array(
                'ucp',
                'UCP_PREFS',
                array('module_basename' => '\bastien59960\reactions\ucp\reactions_module'),
            )),

            // Suppression des types de notifications
            array('notification.type.remove', array('notification.type.reaction')),
            array('notification.type.remove', array('notification.type.reaction_email_digest')),
        );
    }

    public function set_utf8mb4_bin()
    {
        $table = $this->table_prefix . 'post_reactions';
        $sql = "ALTER TABLE {$table}
                MODIFY `reaction_emoji` VARCHAR(191)
                CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";
        $this->db->sql_query($sql);

        return [];
    }

    public function clean_orphan_notifications()
    {
        $notif = $this->table_prefix . 'notifications';
        $types = $this->table_prefix . 'notification_types';
        $sql = "
            DELETE FROM {$notif}
            WHERE notification_type_id NOT IN (
                SELECT notification_type_id FROM {$types}
            )";
        $this->db->sql_query($sql);

        return [];
    }
}
