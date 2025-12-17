<?php
/**
 * Migration initiale pour l'extension Reactions
 *
 * Cette migration crée toute la structure nécessaire :
 * - Table post_reactions
 * - Colonnes utilisateur (préférences notifications)
 * - Configurations de l'extension
 * - Modules ACP et UCP
 * - Types de notifications
 *
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

class release_1_0_0 extends \phpbb\db\migration\container_aware_migration
{
    /**
     * Vérifie si l'extension est déjà installée
     */
    public function effectively_installed()
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions');
    }

    /**
     * Dépendances de cette migration
     */
    public static function depends_on()
    {
        return [
            '\phpbb\db\migration\data\v33x\v3310',
            '\phpbb\db\migration\data\v310\notifications',
        ];
    }

    /**
     * Création du schéma de base de données
     */
    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'post_reactions' => [
                    'COLUMNS' => [
                        'reaction_id'       => ['UINT', null, 'auto_increment'],
                        'post_id'           => ['UINT', 0],
                        'topic_id'          => ['UINT', 0],
                        'user_id'           => ['UINT', 0],
                        'reaction_emoji'    => ['VCHAR:191', ''],
                        'reaction_time'     => ['UINT:11', 0],
                        'reaction_notified' => ['BOOL', 0],
                    ],
                    'PRIMARY_KEY' => 'reaction_id',
                    'KEYS' => [
                        'post_id'         => ['INDEX', 'post_id'],
                        'topic_id'        => ['INDEX', 'topic_id'],
                        'user_id'         => ['INDEX', 'user_id'],
                        'post_user_emoji' => ['UNIQUE', ['post_id', 'user_id', 'reaction_emoji']],
                        'user_post_idx'   => ['INDEX', ['user_id', 'post_id']],
                    ],
                ],
            ],
            'add_columns' => [
                $this->table_prefix . 'users' => [
                    'user_reactions_notify'     => ['BOOL', 1],
                    'user_reactions_cron_email' => ['BOOL', 1],
                ],
            ],
        ];
    }

    /**
     * Suppression du schéma (désinstallation)
     */
    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'post_reactions',
            ],
            'drop_columns' => [
                $this->table_prefix . 'users' => [
                    'user_reactions_notify',
                    'user_reactions_cron_email',
                ],
            ],
        ];
    }

    /**
     * Mise à jour des données
     */
    public function update_data()
    {
        return [
            // Configurations de l'extension
            ['config.add', ['bastien59960_reactions_enabled', 1]],
            ['config.add', ['bastien59960_reactions_max_per_post', 20]],
            ['config.add', ['bastien59960_reactions_max_per_user', 10]],
            ['config.add', ['bastien59960_reactions_spam_time', 15]],
            ['config.add', ['bastien59960_reactions_cron_last_run', 0]],
            ['config.add', ['bastien59960_reactions_picker_width', 320]],
            ['config.add', ['bastien59960_reactions_picker_height', 500]],
            ['config.add', ['bastien59960_reactions_picker_show_categories', 1]],
            ['config.add', ['bastien59960_reactions_picker_show_search', 1]],
            ['config.add', ['bastien59960_reactions_picker_use_json', 1]],
            ['config.add', ['bastien59960_reactions_picker_emoji_size', 24]],
            ['config.add', ['bastien59960_reactions_post_emoji_size', 24]],
            ['config.add', ['bastien59960_reactions_sync_interval', 60]],
            ['config.add', ['bastien59960_reactions_version', '1.0.0']],

            // Module ACP
            ['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_REACTIONS_TITLE']],
            ['module.add', [
                'acp',
                'ACP_REACTIONS_TITLE',
                [
                    'module_basename' => '\bastien59960\reactions\acp\main_module',
                    'module_langname' => 'ACP_REACTIONS_SETTINGS',
                    'module_mode'     => 'settings',
                    'module_auth'     => 'ext_bastien59960/reactions',
                ],
            ]],

            // Module UCP
            ['module.add', [
                'ucp',
                'UCP_PREFS',
                [
                    'module_basename' => '\bastien59960\reactions\ucp\main_module',
                    'module_langname' => 'UCP_REACTIONS_SETTINGS',
                    'module_mode'     => 'settings',
                    'module_auth'     => 'ext_bastien59960/reactions',
                ],
            ]],

            // Fonctions personnalisées
            ['custom', [[$this, 'set_utf8mb4_columns']]],
            ['custom', [[$this, 'create_notification_types']]],
            ['custom', [[$this, 'create_user_notification_preferences']]],
        ];
    }

    /**
     * Suppression des données (désinstallation)
     */
    public function revert_data()
    {
        return [
            // Supprimer les modules
            ['custom', [[$this, 'remove_modules']]],

            // Supprimer les types de notifications
            ['custom', [[$this, 'remove_notification_types']]],

            // Supprimer les configurations
            ['config.remove', ['bastien59960_reactions_enabled']],
            ['config.remove', ['bastien59960_reactions_max_per_post']],
            ['config.remove', ['bastien59960_reactions_max_per_user']],
            ['config.remove', ['bastien59960_reactions_spam_time']],
            ['config.remove', ['bastien59960_reactions_cron_last_run']],
            ['config.remove', ['bastien59960_reactions_picker_width']],
            ['config.remove', ['bastien59960_reactions_picker_height']],
            ['config.remove', ['bastien59960_reactions_picker_show_categories']],
            ['config.remove', ['bastien59960_reactions_picker_show_search']],
            ['config.remove', ['bastien59960_reactions_picker_use_json']],
            ['config.remove', ['bastien59960_reactions_picker_emoji_size']],
            ['config.remove', ['bastien59960_reactions_post_emoji_size']],
            ['config.remove', ['bastien59960_reactions_sync_interval']],
            ['config.remove', ['bastien59960_reactions_version']],
        ];
    }

    /**
     * Configure les colonnes en UTF8MB4 pour supporter les emojis
     */
    public function set_utf8mb4_columns()
    {
        try {
            // Colonne reaction_emoji
            $sql = "ALTER TABLE {$this->table_prefix}post_reactions
                    MODIFY `reaction_emoji` VARCHAR(191)
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";
            $this->db->sql_query($sql);

            // Colonne notification_data (pour stocker les emojis dans les notifications)
            $sql = "ALTER TABLE {$this->table_prefix}notifications
                    MODIFY notification_data MEDIUMTEXT
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_bin";
            $this->db->sql_query($sql);
        } catch (\Exception $e) {
            // Ignorer les erreurs (la colonne peut déjà être en utf8mb4)
        }
        return true;
    }

    /**
     * Crée les types de notifications
     */
    public function create_notification_types()
    {
        $types_table = $this->table_prefix . 'notification_types';

        $notification_types = [
            'bastien59960.reactions.notification.type.reaction',
            'bastien59960.reactions.notification.type.reaction_email_digest',
        ];

        foreach ($notification_types as $type_name) {
            // Vérifier si le type existe déjà
            $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                    WHERE notification_type_name = '" . $this->db->sql_escape($type_name) . "'";
            $result = $this->db->sql_query($sql);
            $exists = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$exists) {
                $sql = 'INSERT INTO ' . $types_table . ' ' . $this->db->sql_build_array('INSERT', [
                    'notification_type_name'    => $type_name,
                    'notification_type_enabled' => 1,
                ]);
                $this->db->sql_query($sql);
            }
        }

        return true;
    }

    /**
     * Crée les préférences de notification pour tous les utilisateurs
     */
    public function create_user_notification_preferences()
    {
        $notification_types = [
            'bastien59960.reactions.notification.type.reaction' => 'notification.method.board',
            'bastien59960.reactions.notification.type.reaction_email_digest' => 'notification.method.email',
        ];

        // Récupérer tous les utilisateurs actifs
        $sql = 'SELECT user_id FROM ' . $this->table_prefix . 'users
                WHERE user_type <> ' . USER_IGNORE . ' AND user_id <> ' . ANONYMOUS;
        $result = $this->db->sql_query($sql);

        $user_ids = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $user_ids[] = (int) $row['user_id'];
        }
        $this->db->sql_freeresult($result);

        foreach ($user_ids as $user_id) {
            foreach ($notification_types as $item_type => $method) {
                // Vérifier si la préférence existe
                $sql = 'SELECT notify FROM ' . $this->table_prefix . 'user_notifications
                        WHERE user_id = ' . $user_id . '
                          AND item_type = \'' . $this->db->sql_escape($item_type) . '\'
                          AND method = \'' . $this->db->sql_escape($method) . '\'
                          AND item_id = 0';
                $result = $this->db->sql_query($sql);
                $exists = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);

                if (!$exists) {
                    $sql = 'INSERT INTO ' . $this->table_prefix . 'user_notifications
                            (item_type, item_id, user_id, method, notify)
                            VALUES (\'' . $this->db->sql_escape($item_type) . '\', 0, ' . $user_id . ',
                                    \'' . $this->db->sql_escape($method) . '\', 1)';
                    $this->db->sql_query($sql);
                }
            }
        }

        return true;
    }

    /**
     * Supprime les modules ACP et UCP
     */
    public function remove_modules()
    {
        try {
            $sql = 'DELETE FROM ' . $this->table_prefix . "modules
                    WHERE module_basename LIKE '%bastien59960%reactions%'";
            $this->db->sql_query($sql);

            // Supprimer aussi la catégorie ACP
            $sql = 'DELETE FROM ' . $this->table_prefix . "modules
                    WHERE module_langname = 'ACP_REACTIONS_TITLE' AND module_class = 'acp'";
            $this->db->sql_query($sql);
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }
        return true;
    }

    /**
     * Supprime les types de notifications
     */
    public function remove_notification_types()
    {
        try {
            // Récupérer les IDs des types
            $sql = 'SELECT notification_type_id FROM ' . $this->table_prefix . "notification_types
                    WHERE notification_type_name LIKE 'bastien59960.reactions.%'";
            $result = $this->db->sql_query($sql);

            $type_ids = [];
            while ($row = $this->db->sql_fetchrow($result)) {
                $type_ids[] = (int) $row['notification_type_id'];
            }
            $this->db->sql_freeresult($result);

            if (!empty($type_ids)) {
                // Supprimer les notifications
                $sql = 'DELETE FROM ' . $this->table_prefix . 'notifications
                        WHERE ' . $this->db->sql_in_set('notification_type_id', $type_ids);
                $this->db->sql_query($sql);

                // Supprimer les préférences utilisateurs
                $sql = 'DELETE FROM ' . $this->table_prefix . "user_notifications
                        WHERE item_type LIKE 'bastien59960.reactions.%'";
                $this->db->sql_query($sql);

                // Supprimer les types
                $sql = 'DELETE FROM ' . $this->table_prefix . 'notification_types
                        WHERE ' . $this->db->sql_in_set('notification_type_id', $type_ids);
                $this->db->sql_query($sql);
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }
        return true;
    }
}
