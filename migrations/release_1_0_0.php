<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960) - https://github.com/bastien59960
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /migrations/release_1_0_0.php
 * Rôle :
 * Ce fichier de migration gère l'installation et la désinstallation initiales
 * de l'extension "Reactions". Il est responsable de la création (et de la
 * suppression) de toutes les structures de base de données : tables, colonnes,
 * configurations, modules ACP/UCP, et types de notifications.
 * C'est le plan de construction de l'extension.
 */

namespace bastien59960\reactions\migrations;

/**
 * Migration 1.0.0
 *
 * CORRECTION CRITIQUE :
 * La classe hérite de `\phpbb\db\migration\container_aware_migration`
 * pour que le conteneur de services ($this->container) soit injecté.
 */
class release_1_0_0 extends \phpbb\db\migration\container_aware_migration
{
    public function effectively_installed()
    {
        // Vérifie si le type de notification 'cloche' est déjà enregistré.
        $types_table = $this->table_prefix . 'notification_types';
        $sql = 'SELECT notification_type_id
                FROM ' . $types_table . " 
                WHERE notification_type_name = 'notification.type.reaction'";
        $result = $this->db->sql_query($sql);
        $type_exists = (bool) $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        // La migration est considérée comme installée si la table des réactions, la colonne utilisateur et le type de notification existent.
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
            // Options de configuration générales
            array('config.add', array('bastien59960_reactions_max_per_post', 20)),
            array('config.add', array('bastien59960_reactions_max_per_user', 10)),
            array('config.add', array('bastien59960_reactions_enabled', 1)),
            array('config.add', array('reactions_ucp_preferences_installed', 1)),
            array('config.add', array('bastien59960_reactions_spam_time', 15)),
            array('config.add', array('bastien59960_reactions_cron_last_run', 0)),

            // Options de configuration de l'interface (fusionné depuis release_1_0_4)
            array('config.add', array('bastien59960_reactions_picker_width', 320)),
            array('config.add', array('bastien59960_reactions_picker_height', 280)),
            array('config.add', array('bastien59960_reactions_picker_show_categories', 0)),
            array('config.add', array('bastien59960_reactions_picker_show_search', 0)),
            array('config.add', array('bastien59960_reactions_picker_use_json', 0)),
            array('config.add', array('bastien59960_reactions_picker_emoji_size', 24)),
            array('config.add', array('bastien59960_reactions_sync_interval', 5000)),

            // --- DÉBUT : PLACE NETTE (ROBUSTIFICATION) ---
            array('custom', array(array($this, 'remove_existing_modules'))),
            array('cache.purge', array()),

            // Ajout du module ACP
            array('module.add', array(
                'acp', 'ACP_CAT_DOT_MODS', 'ACP_REACTIONS_SETTINGS'
            )),
            array('module.add', array(
                'acp', 'ACP_REACTIONS_SETTINGS', array(
                    'module_basename'   => '\bastien59960\reactions\acp\main_module',
                    'modes'             => array('settings'),
                ),
            )),

            // Ajout du module UCP
            array('module.add', array(
                'ucp', 'UCP_PREFS', 'UCP_REACTIONS_TITLE'
            )),
            array('module.add', array(
                'ucp', 'UCP_REACTIONS_TITLE',
                array(
                    'module_basename'   => '\bastien59960\reactions\ucp\reactions_module',
                    'modes'             => array('settings'),
                )
            )),

            // Fonctions personnalisées à exécuter
            array('custom', array(array($this, 'set_utf8mb4_bin'))),
            array('custom', array(array($this, 'create_notification_type'))),
            array('custom', array(array($this, 'clean_orphan_notifications'))),
        );
    }

    public function revert_data()
    {
        return array(
            // Suppression des configurations générales
            array('config.remove', array('bastien59960_reactions_max_per_post')),
            array('config.remove', array('bastien59960_reactions_max_per_user')),
            array('config.remove', array('bastien59960_reactions_enabled')),
            array('config.remove', array('reactions_ucp_preferences_installed')),
            array('config.remove', array('bastien59960_reactions_spam_time')),
            array('config.remove', array('bastien59960_reactions_cron_last_run')),

            // Suppression des configurations de l'interface
            array('config.remove', array('bastien59960_reactions_picker_width')),
            array('config.remove', array('bastien59960_reactions_picker_height')),
            array('config.remove', array('bastien59960_reactions_picker_show_categories')),
            array('config.remove', array('bastien59960_reactions_picker_show_search')),
            array('config.remove', array('bastien59960_reactions_picker_use_json')),
            array('config.remove', array('bastien59960_reactions_picker_emoji_size')),
            array('config.remove', array('bastien59960_reactions_sync_interval')),
            
            // CORRECTION CRITIQUE : Supprimer les modules AVANT les types de notifications
            // pour éviter les dépendances circulaires
            array('module.remove', array('acp', 'ACP_REACTIONS_SETTINGS')),
            array('module.remove', array('ucp', 'UCP_REACTIONS_TITLE')),
            
            // Purge du cache après suppression des modules
            array('cache.purge', array()),

            // Suppression des types de notifications EN DERNIER
            array('custom', array(array($this, 'remove_notification_type'))),
        );
    }

    /**
     * Fonction de nettoyage préventif pour garantir une installation propre.
     * Cette méthode est appelée au début de update_data().
     */
    public function remove_existing_modules()
    {
        /** @var \phpbb\db\migration\tool\module $module_tool */
        $module_tool = $this->container->get('phpbb.db.migration.tool.module');
        $module_tool->remove('acp', 'ACP_REACTIONS_SETTINGS');
        $module_tool->remove('ucp', 'UCP_REACTIONS_TITLE');
    }

    public function set_utf8mb4_bin()
    {
        $table = $this->table_prefix . 'post_reactions';
        try {
            $sql = "ALTER TABLE {$table}
                    MODIFY `reaction_emoji` VARCHAR(191)
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";
            $this->db->sql_query($sql);
        } catch (\Throwable $e) {
            // Ignorer silencieusement si la table n'existe pas encore
        }
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
        } catch (\Throwable $e) {
            // Ignorer silencieusement
        }
    }

    public function create_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';

        try {
            // Nettoyage préventif d'une ancienne entrée erronée
            $malformed_name = 'bastien59960.reactions.notification.type.reaction';
            $sql_cleanup = 'DELETE FROM ' . $types_table . "
                WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($malformed_name)) . "'";
            $this->db->sql_query($sql_cleanup);

            // === TYPE 1 : notification.type.reaction (instantané, cloche) ===
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

            // === TYPE 2 : notification.type.reaction_email_digest (résumé e-mail) ===
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
        } catch (\Throwable $e) {
            // Ignorer silencieusement
        }
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
            // CORRECTION : Supprimer d'abord les notifications associées
            foreach ($names as $canonical_name) {
                // 1. Récupérer l'ID du type
                $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                    WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'";
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);
                
                if ($row) {
                    // 2. Supprimer les notifications liées
                    $type_id = (int) $row['notification_type_id'];
                    $sql = 'DELETE FROM ' . $notifications_table . '
                        WHERE notification_type_id = ' . $type_id;
                    $this->db->sql_query($sql);
                    
                    // 3. Supprimer le type lui-même
                    $sql = 'DELETE FROM ' . $types_table . "
                        WHERE notification_type_id = " . $type_id;
                    $this->db->sql_query($sql);
                }
            }
        } catch (\Throwable $e) {
            // Ignorer silencieusement
        }
    }
}