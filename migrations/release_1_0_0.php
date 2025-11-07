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
                $this->table_prefix . 'notifications' => array(
                ),
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
                $this->table_prefix . 'notifications' => array(
                ),
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

            // Options de configuration de l'interface (fusionné depuis release_1_0_4)
			['config.add', ['bastien59960_reactions_post_emoji_size', 24]],
			['config.add', ['bastien59960_reactions_picker_width', 320]],
			['config.add', ['bastien59960_reactions_picker_height', 280]],
			['config.add', ['bastien59960_reactions_picker_show_categories', 1]],
			['config.add', ['bastien59960_reactions_picker_show_search', 1]],
			['config.add', ['bastien59960_reactions_picker_use_json', 1]],
			['config.add', ['bastien59960_reactions_picker_emoji_size', 24]],
			['config.add', ['bastien59960_reactions_sync_interval', 5000]],

            // Ajout du module UCP
            array('module.add', array(
                'ucp',
                'UCP_PREFS',
                array(
                    'module_basename'   => '\bastien59960\reactions\ucp\reactions_module',
                    'modes'             => array('settings'),
                ),
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
            // Suppression des configs
            array('config.remove', array('bastien59960_reactions_max_per_post')),
            array('config.remove', array('bastien59960_reactions_max_per_user')),
            array('config.remove', array('bastien59960_reactions_enabled')),
            array('config.remove', array('reactions_ucp_preferences_installed')),

            // Suppression des configurations de l'interface (fusionné depuis release_1_0_4)
			['config.remove', ['bastien59960_reactions_post_emoji_size']],
			['config.remove', ['bastien59960_reactions_picker_width']],
			['config.remove', ['bastien59960_reactions_picker_height']],
			['config.remove', ['bastien59960_reactions_picker_show_categories']],
			['config.remove', ['bastien59960_reactions_picker_show_search']],
			['config.remove', ['bastien59960_reactions_picker_use_json']],
			['config.remove', ['bastien59960_reactions_picker_emoji_size']],
			['config.remove', ['bastien59960_reactions_sync_interval']],

            // Suppression du module UCP
            array('module.remove', array(
                'ucp',
                'UCP_PREFS',
                array('module_basename' => '\bastien59960\reactions\ucp\reactions_module'),
            )),

            array('custom', array(array($this, 'remove_notification_type'))),
        );
    }

    public function set_utf8mb4_bin()
    {
        $table = $this->table_prefix . 'post_reactions';
        $sql = "ALTER TABLE {$table}
                MODIFY `reaction_emoji` VARCHAR(191)
                CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";
        $this->db->sql_query($sql);
    }

    public function clean_orphan_notifications()
    {
        $notifications_table = $this->table_prefix . 'notifications';
        $types = $this->table_prefix . 'notification_types';
        try {
            $sql = "
                DELETE FROM {$notifications_table}
                WHERE notification_type_id NOT IN (
                    SELECT notification_type_id FROM {$types}
                )
            ";
            $this->db->sql_query($sql);
        } catch (\Throwable $e) {
            if (defined('DEBUG')) {
                trigger_error('[Reactions] Échec du nettoyage des notifications orphelines : ' . $e->getMessage(), E_USER_NOTICE);
            }
        }
    }

public function create_notification_type()
{
    $types_table = $this->table_prefix . 'notification_types';

    // Nettoyage préventif d'une ancienne entrée erronée
    $malformed_name = 'bastien59960.reactions.notification.type.reaction';
    $sql_cleanup = 'DELETE FROM ' . $types_table . "
        WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($malformed_name)) . "'";
    $this->db->sql_query($sql_cleanup);


    // === TYPE 1 : notification.type.reaction (instantané, cloche) ===
    // Ce type gère les notifications immédiates dans la "cloche" du forum.
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
    // Ce type est utilisé par la tâche CRON pour envoyer des résumés périodiques par e-mail.
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
    trigger_error('[DEBUG] create_notification_type() exécutée');
}
public function remove_notification_type()
{
    $types_table = $this->table_prefix . 'notification_types';
    $names = array(
        'notification.type.reaction',
        'notification.type.reaction_email_digest',
    );

    foreach ($names as $canonical_name) {
        $sql = 'DELETE FROM ' . $types_table . "
            WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'";
        $this->db->sql_query($sql);
    }
}
}
