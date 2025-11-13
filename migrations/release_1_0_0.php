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
        $schema_updates = [];

        return [
            'add_tables' => [
                $this->table_prefix . 'post_reactions' => [
                    'COLUMNS'       => [
                        'reaction_id'       => ['UINT', null, 'auto_increment'],
                        'post_id'           => ['UINT', 0],
                        'topic_id'          => ['UINT', 0],
                        'user_id'           => ['UINT', 0],
                        'reaction_emoji'    => ['VCHAR:191', ''],
                        'reaction_time'     => ['UINT:11', 0],
                        'reaction_notified' => ['BOOL', 0],
                    ],
                    'PRIMARY_KEY'   => 'reaction_id',
                    'KEYS'          => [
                        'post_id'           => ['INDEX', 'post_id'],
                        'topic_id'          => ['INDEX', 'topic_id'],
                        'user_id'           => ['INDEX', 'user_id'],
                        'post_user_emoji'   => ['UNIQUE', ['post_id', 'user_id', 'reaction_emoji']],
                        'user_post_idx'     => ['INDEX', ['user_id', 'post_id']], // Pour vérifier rapidement les limites par utilisateur
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

            // Ajout du module ACP
            array('module.add', array(
                'acp', // parent
                'ACP_CAT_DOT_MODS', // après (catégorie "Extensions")
                'ACP_REACTIONS_SETTINGS' // nom du module
            )),
            array('module.add', array(
                'acp',
                'ACP_REACTIONS_SETTINGS', // parent
                array(
                    'module_basename'   => '\bastien59960\reactions\acp\main_module',
                    'modes'             => array('settings'),
                )
            )),
            // Ajout du module UCP - CORRECTION
            array('module.add', array(
                'ucp', // parent
                'UCP_PREFS', // après
                // CORRECTION : Pour créer une catégorie, il faut un tableau détaillé.
                array(
                    'module_basename'   => null, // Pas de classe pour une catégorie
                    'module_langname'   => 'UCP_REACTIONS_TITLE', // Clé de langue pour le titre
                    'module_mode'       => 'settings', // Mode pour le lien
                    'module_auth'       => 'acl_u_reactions_prefs', // Permission pour voir le module
                )
            )),
            array('module.add', array(
                'ucp',
                'UCP_REACTIONS_TITLE', // Le parent est maintenant la clé de langue de la catégorie
                array(
                    'module_basename'   => '\bastien59960\reactions\ucp\reactions_module',
                    'modes'             => array('settings'),
                )
            )),

            // CORRECTION : Ajout de la tâche cron principale pour les notifications par e-mail.
            // C'est l'oubli de cette ligne qui causait l'échec final.
            array('cron.task.add', array('bastien59960.reactions.notification', '\bastien59960\reactions\cron\notification_task', 300, false)),

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
            
            // Suppression du module ACP.
            // La suppression de la catégorie parente supprime aussi les enfants.
            array('module.remove', array(
                'acp',
                'ACP_REACTIONS_SETTINGS',
            )),

            // Suppression du module UCP.
            // CORRECTION : On supprime la CATÉGORIE en utilisant sa clé de langue.
            // phpBB se chargera de supprimer le module enfant automatiquement.
            array('module.remove', array(
                'ucp',
                'UCP_REACTIONS_TITLE',
            )),

            // CORRECTION : Ajout de la tâche cron qui avait été oubliée.
            array('cron.task.add', array('bastien59960.reactions.notification', '\bastien59960\reactions\cron\notification_task', 300, false)),

            // Suppression des types de notifications
            array('custom', array(array($this, 'remove_notification_type'))),
        );
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
            // Ignorer silencieusement si la table n'existe pas encore ou si la colonne est déjà correcte
        }
    }

    public function clean_orphan_notifications()
    {
        $notifications_table = $this->table_prefix . 'notifications';
        $types = $this->table_prefix . 'notification_types';
        try {
            // Utiliser LEFT JOIN au lieu de NOT IN pour compatibilité MySQL
            $sql = "DELETE n FROM {$notifications_table} n
                    LEFT JOIN {$types} t ON n.notification_type_id = t.notification_type_id
                    WHERE t.notification_type_id IS NULL";
            $this->db->sql_query($sql);
        } catch (\Throwable $e) {
            // Ignorer silencieusement les erreurs pour ne pas bloquer la migration
            // Les notifications orphelines seront nettoyées plus tard si nécessaire
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
        } catch (\Throwable $e) {
            // Ignorer silencieusement les erreurs pour ne pas bloquer la migration
        }
    }

    public function remove_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $names = array(
            'notification.type.reaction',
            'notification.type.reaction_email_digest',
        );

        try {
            foreach ($names as $canonical_name) {
                $sql = 'DELETE FROM ' . $types_table . "
                    WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'";
                $this->db->sql_query($sql);
            }
        } catch (\Throwable $e) {
            // Ignorer silencieusement les erreurs pour ne pas bloquer la migration
        }
    }
}
