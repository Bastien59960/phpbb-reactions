<?php
/**
 * Fichier : release_1_0_0.php
 * Chemin : bastien59960/reactions/migrations/release_1_0_0.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 * @version 1.0.3
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
                WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction'";
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
            array('config.add', array('bastien59960_reactions_sync_interval', 20)), // Valeur en secondes

            array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_REACTIONS_SETTINGS')),
            array('module.add', array(
                'acp',
                'ACP_REACTIONS_SETTINGS',
                array(
                    'module_basename'   => '\bastien59960\reactions\acp\main_module',
                    'module_langname'   => 'ACP_REACTIONS_SETTINGS',
                    'module_mode'       => 'settings',
                    'module_auth'       => 'ext_bastien59960/reactions',
                )
            )),

            // CORRECTION : Utilisation de la syntaxe de module unique avec tous les champs requis.
            array('module.add', array(
                'ucp',
                'UCP_PREFS',
                array(
                    'module_basename'   => '\bastien59960\reactions\ucp\main_module',
                    'module_langname'   => 'UCP_REACTIONS_SETTINGS',
                    'module_mode'       => 'settings',
                    'module_auth'       => 'ext_bastien59960/reactions',
                )
            )),

            array('custom', array(array($this, 'set_utf8mb4_bin'))),
            array('custom', array(array($this, 'create_notification_type'))),
            array('custom', array(array($this, 'clean_orphan_notifications'))),
        );
    }

    public function revert_data()
    {
        return array(
            // Étape 0 : Nettoyage préventif des anciens modules (sécurité)
            array('custom', array(array($this, 'remove_existing_modules'))),

            // Étape 1 : Supprimer les types de notifications via méthode custom
            array('custom', array(array($this, 'remove_notification_type'))),

            // Étape 2 : Supprimer les modules ACP et UCP (d'abord les enfants, puis la catégorie)
            // La méthode custom 'remove_existing_modules' s'occupe déjà de ça.

            // Étape 3 : Supprimer les clés de configuration
            array('config.remove', array('bastien59960_reactions_max_per_post')),
            array('config.remove', array('bastien59960_reactions_max_per_user')),
            array('config.remove', array('bastien59960_reactions_enabled')),
            array('config.remove', array('reactions_ucp_preferences_installed')),
            array('config.remove', array('bastien59960_reactions_spam_time')),
            array('config.remove', array('bastien59960_reactions_cron_last_run')),
            array('config.remove', array('bastien59960_reactions_picker_width')),
            array('config.remove', array('bastien59960_reactions_picker_height')),
            array('config.remove', array('bastien59960_reactions_picker_show_categories')),
            array('config.remove', array('bastien59960_reactions_picker_show_search')),
            array('config.remove', array('bastien59960_reactions_picker_use_json')),
            array('config.remove', array('bastien59960_reactions_picker_emoji_size')),
            array('config.remove', array('bastien59960_reactions_sync_interval')),
            array('config.remove', array('bastien59960_reactions_version')),
            array('config.remove', array('bastien59960_reactions_last_test_run')),
        );
    }

    public function remove_existing_modules()
    {
        try {
            $module_tool = $this->container->get('phpbb.db.migration.tool.module');
            // Supprime le module enfant et sa catégorie parente si elle devient vide.
            $module_tool->remove('acp', 'ACP_CAT_DOT_MODS', 'ACP_REACTIONS_SETTINGS');
            // Supprime le module UCP de la catégorie des préférences.
            $module_tool->remove('ucp', 'UCP_PREFS', 'UCP_REACTIONS_SETTINGS');
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
            $malformed_names = ['reaction', 'reaction_email_digest'];
            $sql = 'DELETE FROM ' . $types_table . "
                WHERE " . $this->db->sql_in_set('notification_type_name', $malformed_names);
            $this->db->sql_query($sql);
    
            // Type 1: reaction (nom court déduit par phpBB)
            $canonical_name = 'bastien59960.reactions.notification.type.reaction';
            $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'
                LIMIT 1";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$row) { // phpcs:ignore
                $insert_data = array(
                    'notification_type_name'    => $canonical_name,
                    'notification_type_enabled' => 1,
                );
                $this->db->sql_query('INSERT INTO ' . $types_table . ' ' . $this->db->sql_build_array('INSERT', $insert_data));
            }

            // Type 2: reaction_email_digest
            $digest_name = 'bastien59960.reactions.notification.type.reaction_email_digest';
            $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($digest_name)) . "'
                LIMIT 1";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$row) { // phpcs:ignore
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

        try {
            // Étape 1 : Récupérer les IDs de tous les types de notification liés à l'extension (noms longs et courts)
            $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                    WHERE notification_type_name LIKE 'bastien59960.reactions.notification.type.%'
                       OR notification_type_name IN ('reaction', 'reaction_email_digest')";
            $result = $this->db->sql_query($sql);
            
            $type_ids = [];
            while ($row = $this->db->sql_fetchrow($result)) {
                $type_ids[] = (int) $row['notification_type_id'];
            }
            $this->db->sql_freeresult($result);

            if (empty($type_ids)) {
                return true; // Rien à faire
            }

            // Étape 2 : Supprimer toutes les notifications qui utilisent ces types
            $sql_delete_notifs = 'DELETE FROM ' . $notifications_table . ' WHERE ' . $this->db->sql_in_set('notification_type_id', $type_ids);
            $this->db->sql_query($sql_delete_notifs);

            // Étape 3 : Supprimer les types de notification eux-mêmes
            $sql_delete_types = 'DELETE FROM ' . $types_table . ' WHERE ' . $this->db->sql_in_set('notification_type_id', $type_ids);
            $this->db->sql_query($sql_delete_types);
        } catch (\Exception $e) {
            // Ignore errors
        }
        return true;
    }
}
