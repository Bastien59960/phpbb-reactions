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
                    // HISTORIQUE : La clé primaire est sur `reaction_id` pour une identification unique de chaque réaction.
                    // Les autres clés et index sont cruciaux pour les performances des requêtes.
                    // La clé UNIQUE `post_user_emoji` empêche un utilisateur de mettre deux fois le même emoji sur un post.
                    // L'index `user_post_idx` accélère la vérification des limites de réactions par utilisateur.
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

            // --- DÉBUT : PLACE NETTE (ROBUSTIFICATION) ---
            // Philosophie : On s'assure que la place est nette AVANT de construire.
