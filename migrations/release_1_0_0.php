<?php
/**
 * Fichier : release_1_0_0.php
 * Chemin : bastien59960/reactions/migrations/release_1_0_0.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Fichier de migration initial pour l'extension. Il est responsable de la
 * création de la structure de la base de données (tables, colonnes), de
 * l'ajout des configurations par défaut, de l'enregistrement des modules
 * (ACP, UCP) et des types de notifications.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

class release_1_0_0 extends \phpbb\db\migration\container_aware_migration
{
    public function effectively_installed()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $sql = 'SELECT notification_type_id
                FROM ' . $types_table . " 
                WHERE notification_type_name = 'notification.type.reaction'";
        $result = $this->db->sql_query($sql);
        $type_exists = (bool) $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return (
            $this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions') &&
            $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_reactions_notify') &&
            $type_exists
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
                    'COLUMNS'       => array(
                        'reaction_id'       => array('UINT', null, 'auto_increment'),
                        'post_id'           => array('UINT', 0),
                        'topic_id'          => array('UINT', 0),
                        'user_id'           => array('UINT', 0),
                        'reaction_emoji'    => array('VCHAR:191', ''),
                        'reaction_time'     => array('UINT:11', 0),
                        'reaction_notified' => array('BOOL', 0),
                    ),
                    'PRIMARY_KEY'   => 'reaction_id',
                    'KEYS'          => array(
                        'post_id'           => array('INDEX', 'post_id'),
                        'topic_id'          => array('INDEX', 'topic_id'),
                        'user_id'           => array('INDEX', 'user_id'),
                        'post_user_emoji'   => array('UNIQUE', array('post_id', 'user_id', 'reaction_emoji')),
                        'user_post_idx'     => array('INDEX', array('user_id', 'post_id')),
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
            array('config.add', array('bastien59960_reactions_max_per_post', 20)),
            array('config.add', array('bastien59960_reactions_max_per_user', 10)),
            array('config.add', array('bastien59960_reactions_enabled', 1)),
            array('config.add', array('reactions_ucp_preferences_installed', 1)),
            array('config.add', array('bastien59960_reactions_spam_time', 15)),
            array('config.add', array('bastien59960_reactions_cron_last_run', 0)),
            array('config.add', array('bastien59960_reactions_picker_width', 320)),
            array('config.add', array('bastien59960_reactions_picker_height', 500)),
            array('config.add', array('bastien59960_reactions_picker_show_categories', 1)),
            array('config.add', array('bastien59960_reactions_picker_show_search', 1)),
            array('config.add', array('bastien59960_reactions_picker_use_json', 1)),
            array('config.add', array('bastien59960_reactions_picker_emoji_size', 24)),
            array('config.add', array('bastien59960_reactions_sync_interval', 5000)),

            array('custom', array(array($this, 'remove_existing_modules'))),
            
            array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_REACTIONS_SETTINGS')),
            array('module.add', array('acp', 'ACP_REACTIONS_SETTINGS', array(
                'module_basename'   => '\bastien59960\reactions\acp\main_module',
                'modes'             => array('settings'),
            ))),

            array('module.add', array('ucp', 'UCP_PREFS', array(
                'module_basename'   => '\bastien59960\reactions\ucp\main_module',
                'modes'             => array('settings')
            ))),

            array('custom', array(array($this, 'set_utf8mb4_bin'))),
            array('custom', array(array($this, 'create_notification_type'))),
            array('custom', array(array($this, 'clean_orphan_notifications'))),
        );
    }

    public function revert_data()
    {
        return array(
            array('custom', array(array($this, 'remove_notification_type'))),
            
            // CORRECTION : Utiliser une fonction personnalisée pour un nettoyage complet.
            array('custom', array(array($this, 'revert_all_extension_data'))),
        );
    }

    public function remove_existing_modules()
    {
        try {
            $module_tool = $this->container->get('phpbb.db.migration.tool.module');
            $module_tool->remove('acp', 'ACP_REACTIONS_SETTINGS');
            $module_tool->remove('ucp', 'UCP_REACTIONS_TITLE');
        } catch (\Exception $e) {
            // Ignore errors
        }
        return true;
    }

    public function set_utf8mb4_bin()
    {
        $table = $this->table_prefix . 'post_reactions';
        try {
            $sql = "ALTER TABLE {$table}
                    MODIFY `reaction_emoji` VARCHAR(191)
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";
            $this->db->sql_query($sql);
        } catch (\Exception $e) {
            // Ignore errors
        }
        return true;
    }

    public function clean_orphan_notifications()
    {
        $notifications_table = $this->table_prefix . 'notifications';
        $types = $this->table_prefix . 'notification_types';
        try {
            $sql = "DELETE n FROM {$notifications_table} n
                    LEFT JOIN {$types} t ON n.notification_type_id = t.notification_type_id
                    WHERE t.notification_type_id IS NULL";
            $this->db->sql_query($sql);
        } catch (\Exception $e) {
            // Ignore errors
        }
        return true;
    }

    public function create_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';

        try {
            // Cleanup old malformed entries
            $malformed_name = 'bastien59960.reactions.notification.type.reaction';
            $sql = 'DELETE FROM ' . $types_table . "
                WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($malformed_name)) . "'";
            $this->db->sql_query($sql);

            // Type 1: notification.type.reaction
            $canonical_name = 'notification.type.reaction';
            $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'
                LIMIT 1";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$row) {
                $insert_data = array(
                    'notification_type_name'    => $canonical_name,
                    'notification_type_enabled' => 1,
                );
                $this->db->sql_query('INSERT INTO ' . $types_table . ' ' . $this->db->sql_build_array('INSERT', $insert_data));
            }

            // Type 2: notification.type.reaction_email_digest
            $digest_name = 'notification.type.reaction_email_digest';
            $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($digest_name)) . "'
                LIMIT 1";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$row) {
                $insert_data = array(
                    'notification_type_name'    => $digest_name,
                    'notification_type_enabled' => 1,
                );
                $this->db->sql_query('INSERT INTO ' . $types_table . ' ' . $this->db->sql_build_array('INSERT', $insert_data));
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
        return true;
    }

    public function remove_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $notifications_table = $this->table_prefix . 'notifications';
        
        $names = array(
            'notification.type.reaction',
            'notification.type.reaction_email_digest',
        );

        try {
            foreach ($names as $canonical_name) {
                $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                    WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'";
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);
                
                if ($row) {
                    $type_id = (int) $row['notification_type_id'];
                    
                    // Delete notifications first
                    $sql = 'DELETE FROM ' . $notifications_table . '
                        WHERE notification_type_id = ' . $type_id;
                    $this->db->sql_query($sql);
                    
                    // Delete type
                    $sql = 'DELETE FROM ' . $types_table . '
                        WHERE notification_type_id = ' . $type_id;
                    $this->db->sql_query($sql);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
        return true;
    }

    /**
     * Méthode de nettoyage complète pour la désinstallation.
     * Supprime les modules et toutes les clés de configuration.
     * Doit retourner true pour indiquer le succès.
     *
     * @return bool
     */
    public function revert_all_extension_data()
    {
        // Supprimer les modules
        $this->remove_existing_modules();

        // Supprimer toutes les clés de configuration de l'extension
        $sql = 'DELETE FROM ' . $this->table_prefix . "config 
                WHERE config_name LIKE 'bastien59960_reactions_%' 
                   OR config_name LIKE 'reactions_ucp_%'";
        $this->db->sql_query($sql);

        // Indiquer que l'opération a réussi
        return true;
    }
}
