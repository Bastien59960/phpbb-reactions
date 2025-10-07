<?php
/**
 * Fichier : migrations/release_1_0_0.php — bastien59960/reactions/migrations/release_1_0_0.php
 *
 * Migration unique pour l'installation initiale de l'extension Reactions.
 *
 * Ce fichier crée toutes les tables, colonnes, options de configuration et types de notifications nécessaires au bon fonctionnement de l'extension.
 *
 * Points clés de la logique métier :
 *   - Création de la table principale des réactions
 *   - Ajout des colonnes nécessaires dans les tables notifications et users
 *   - Ajout des options de configuration globales
 *   - Création du type de notification canonique pour les réactions
 *   - Nettoyage automatique des notifications orphelines
 *   - Gestion de la réversion complète lors de la désinstallation
 *
 * Ce fichier doit être utilisé pour toute nouvelle installation de l'extension (avant la mise en production).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
    // =============================================================================
    // MÉTHODES REQUISES PAR LE SYSTÈME DE MIGRATIONS
    // =============================================================================

    /**
     * Vérifier si la migration est déjà installée
     *
     * On vérifie la présence de la table des réactions et des colonnes users.
     *
     * @return bool
     */
    public function effectively_installed()
    {
        return (
            $this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions') &&
            $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_reactions_notify')
        );
    }

    /**
     * Dépendances (phpBB 3.3.10 minimum)
     */
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v33x\v3310');
    }

    /**
     * Définir le schéma de la base de données
     *
     * Crée la table des réactions, ajoute les colonnes nécessaires à notifications et users.
     *
     * @return array
     */
    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'post_reactions' => array(
                    'COLUMNS' => array(
                        'reaction_id'      => array('UINT', null, 'auto_increment'),
                        'post_id'          => array('UINT', 0),
                        'topic_id'         => array('UINT', 0),
                        'user_id'          => array('UINT', 0),
                        'reaction_emoji'   => array('VCHAR:191', ''),
                        'reaction_time'    => array('UINT:11', 0),
                        'reaction_notified'=> array('BOOL', 0),
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
                    'notification_type_name' => array('VCHAR:255', ''),
                    'reaction_emoji'         => array('VCHAR_UNI:10', ''),
                ),
                $this->table_prefix . 'users' => array(
                    'user_reactions_notify'     => array('BOOL', 1),
                    'user_reactions_cron_email' => array('BOOL', 1),
                ),
            ),
        );
    }

    /**
     * Schéma de réversion
     */
    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'post_reactions',
            ),
            'drop_columns' => array(
                $this->table_prefix . 'notifications' => array(
                    'notification_type_name',
                    'reaction_emoji',
                ),
                $this->table_prefix . 'users' => array(
                    'user_reactions_notify',
                    'user_reactions_cron_email',
                ),
            ),
        );
    }

    /**
     * Données à mettre à jour lors de l'installation
     */
    public function update_data()
    {
        return array(
            // Options de configuration
            array('config.add', array('bastien59960_reactions_max_per_post', 20)),
            array('config.add', array('bastien59960_reactions_max_per_user', 10)),
            array('config.add', array('bastien59960_reactions_enabled', 1)),
            array('config.add', array('reactions_ucp_preferences_installed', 1)),
            array('custom', array(array($this, 'set_utf8mb4_bin'))),
            array('custom', array(array($this, 'create_notification_type'))),
            array('custom', array(array($this, 'clean_orphan_notifications'))),
        );
    }

    /**
     * Réversion des données
     */
    public function revert_data()
    {
        return array(
            array('config.remove', array('bastien59960_reactions_max_per_post')),
            array('config.remove', array('bastien59960_reactions_max_per_user')),
            array('config.remove', array('bastien59960_reactions_enabled')),
            array('config.remove', array('reactions_ucp_preferences_installed')),
            array('custom', array(array($this, 'remove_notification_type'))),
        );
    }

    /**
     * Configurer le champ emoji pour supporter utf8mb4_bin
     */
    public function set_utf8mb4_bin()
    {
        $table_name = $this->table_prefix . 'post_reactions';
        $sql = "ALTER TABLE {$table_name}
        MODIFY `reaction_emoji` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";
        $this->db->sql_query($sql);
    }

    /**
     * Crée le type de notification canonique si absent
     */
    public function create_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $canonical_name = 'notification.type.reaction';
        $sql = 'SELECT notification_type_id FROM ' . $types_table . " WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "' LIMIT 1";
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
    }

    /**
     * Nettoyage des notifications orphelines
     */
    public function clean_orphan_notifications()
    {
        $notifications_table = $this->table_prefix . 'notifications';
        $types_table = $this->table_prefix . 'notification_types';
        try {
            $sql = "
                DELETE FROM {$notifications_table}
                WHERE notification_type_id NOT IN (
                    SELECT notification_type_id FROM {$types_table}
                )
            ";
            $this->db->sql_query($sql);
        } catch (\Throwable $e) {
            if (defined('DEBUG')) {
                trigger_error('[Reactions] Échec du nettoyage des notifications orphelines : ' . $e->getMessage(), E_USER_NOTICE);
            }
        }
    }

    /**
     * Suppression du type de notification canonique (revert)
     */
    public function remove_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $canonical_name = 'notification.type.reaction';
        $sql = 'DELETE FROM ' . $types_table . " WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'";
        $this->db->sql_query($sql);
    }
}
