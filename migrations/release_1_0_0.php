<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /migrations/release_1_0_0.php
 * Rôle :
 * Ce fichier de migration est le script d'installation et de mise à jour de
 * l'extension pour la version 1.0.0. Il est exécuté automatiquement par phpBB
 * lors de l'activation de l'extension.
 *
 * Il est responsable de :
 * - Créer la table `phpbb_post_reactions` pour stocker les réactions.
 * - Ajouter les colonnes nécessaires aux tables `phpbb_users` et `phpbb_notifications`.
 * - Insérer les configurations par défaut dans la table `phpbb_config`.
 * - Créer et activer les types de notifications personnalisés.
 * - Importer les données d'une ancienne version de l'extension si elle existe.
 */

namespace bastien59960\reactions\migrations;

/**
 * Migration pour la version 1.0.0 de l'extension Reactions.
 *
 * Cette classe contient toutes les instructions pour modifier la base de données
 * afin d'installer, mettre à jour ou désinstaller l'extension.
 */
class release_1_0_0 extends \phpbb\db\migration\migration
{
    /**
     * Vérifie si l'extension est déjà "effectivement installée".
     *
     * Cette méthode est appelée par phpBB pour déterminer si la migration doit
     * être exécutée. Si elle retourne `true`, phpBB considère que l'installation
     * est déjà faite et passe à la suite.
     *
     * @return bool True si les structures principales de la BDD existent déjà.
     */
    public function effectively_installed()
    {
        // On vérifie la présence d'un des types de notification que nous créons.
        $types_table = $this->table_prefix . 'notification_types';
        $sql = 'SELECT notification_type_id
                FROM ' . $types_table . "
                WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction'
                   OR notification_type_name = 'reaction'"; // Inclut l'ancien nom pour la compatibilité
        $result = $this->db->sql_query($sql);
        $type_exists = (bool) $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        // L'extension est considérée comme installée si :
        // 1. La table des réactions existe.
        // 2. La colonne de préférence utilisateur existe.
        // 3. Au moins un de nos types de notification existe.
        return (
            $this->db_tools->sql_table_exists($this->table_prefix . 'post_reactions') &&
            $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_reactions_notify') &&
            $type_exists
        );
    }

    /**
     * Définit les dépendances de cette migration.
     *
     * phpBB s'assurera que la migration `v3310` de phpBB 3.3.10 est exécutée
     * avant celle-ci. C'est une bonne pratique pour garantir la compatibilité.
     *
     * @return array Liste des migrations requises.
     */
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v33x\v3310');
    }

    /**
     * Définit les modifications du schéma de la base de données à appliquer.
     * C'est ici qu'on crée les tables et les colonnes.
     */
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
                        'reaction_emoji'    => array('VCHAR:191', ''), // 191 pour supporter les emojis 4-bytes en utf8mb4
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
                // La colonne `reaction_emoji` dans la table des notifications est gérée
                // dynamiquement par la classe de notification elle-même (via get_insert_sql).
                // Il n'est donc pas nécessaire de l'ajouter ici.
                $this->table_prefix . 'notifications' => array(), 
                $this->table_prefix . 'users' => array(
                    'user_reactions_notify'     => array('BOOL', 1), // Préférence pour les notifs "cloche"
                    'user_reactions_cron_email' => array('BOOL', 1), // Préférence pour les notifs par e-mail (digest)
                ),
            ),
        );
    }

    /**
     * Inverse les modifications du schéma. Appelé lors de la désinstallation.
     */
    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'post_reactions',
            ),
            'drop_columns' => array(
                $this->table_prefix . 'notifications' => array(),
                $this->table_prefix . 'users' => array(
                    'user_reactions_notify',
                    'user_reactions_cron_email',
                ),
            ),
        );
    }

    /**
     * Définit les modifications des données à appliquer.
     * C'est ici qu'on ajoute les configurations et qu'on appelle des fonctions personnalisées.
     */
    public function update_data()
    {
        return array(
            // Configs générales
            array('config.add', array('bastien59960_reactions_max_per_post', 20)),
            array('config.add', array('bastien59960_reactions_max_per_user', 10)),
            array('config.add', array('bastien59960_reactions_enabled', 1)),
            array('config.add', array('reactions_ucp_preferences_installed', 1)),

            // Configs interface
            array('config.add', array('bastien59960_reactions_post_emoji_size', 24)),
            array('config.add', array('bastien59960_reactions_picker_width', 320)),
            array('config.add', array('bastien59960_reactions_picker_height', 280)),
            array('config.add', array('bastien59960_reactions_picker_show_categories', 1)),
            array('config.add', array('bastien59960_reactions_picker_show_search', 1)),
            array('config.add', array('bastien59960_reactions_picker_use_json', 1)),
            array('config.add', array('bastien59960_reactions_picker_emoji_size', 24)),
            array('config.add', array('bastien59960_reactions_sync_interval', 5000)),

            // Fonctions custom
            array('custom', array(array($this, 'set_utf8mb4_bin'))),
            array('custom', array(array($this, 'create_notification_type'))),
            array('custom', array(array($this, 'enable_notification_types'))),
            array('custom', array(array($this, 'clean_orphan_notifications'))),
            array('custom', array(array($this, 'import_old_reactions'))),
        );
    }

    /**
     * Inverse les modifications des données. Appelé lors de la purge des données de l'extension.
     */
    public function revert_data()
    {
        return array(
            array('config.remove', array('bastien59960_reactions_max_per_post')),
            array('config.remove', array('bastien59960_reactions_max_per_user')),
            array('config.remove', array('bastien59960_reactions_enabled')),
            array('config.remove', array('reactions_ucp_preferences_installed')),

            array('config.remove', array('bastien59960_reactions_post_emoji_size')),
            array('config.remove', array('bastien59960_reactions_picker_width')),
            array('config.remove', array('bastien59960_reactions_picker_height')),
            array('config.remove', array('bastien59960_reactions_picker_show_categories')),
            array('config.remove', array('bastien59960_reactions_picker_show_search')),
            array('config.remove', array('bastien59960_reactions_picker_use_json')),
            array('config.remove', array('bastien59960_reactions_picker_emoji_size')),
            array('config.remove', array('bastien59960_reactions_sync_interval')),

            array('custom', array(array($this, 'purge_notification_types'))),
        );
    }

    /**
     * Fonction personnalisée pour forcer l'encodage de la colonne emoji.
     *
     * C'est une étape CRUCIALE pour assurer le support complet des emojis (y compris
     * les plus récents qui utilisent 4 octets en UTF-8). `utf8mb4_bin` est le
     * standard recommandé pour stocker des emojis de manière fiable.
     */
    public function set_utf8mb4_bin()
    {
        $table_name = $this->table_prefix . 'post_reactions';
        $sql = "ALTER TABLE {$table_name}
        MODIFY `reaction_emoji` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";
        $this->db->sql_query($sql);
    }

    /**
     * Crée les types de notifications personnalisés dans la base de données.
     *
     * phpBB a besoin de connaître ces types pour pouvoir les gérer.
     * On crée deux types : un pour les notifications instantanées ("cloche")
     * et un pour le résumé par e-mail (utilisé par le CRON).
     */
    public function create_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';

        // Nettoyage préventif d'une ancienne entrée potentiellement malformée (sans le préfixe complet).
        $malformed_name = 'bastien59960.reactions.notification.type.reaction';
        $sql_cleanup = 'DELETE FROM ' . $types_table . "
            WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($malformed_name)) . "'";
        $this->db->sql_query($sql_cleanup);

        $canonical_name = 'bastien59960.reactions.notification.type.reaction'; // Nom complet du service
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

        $digest_name = 'bastien59960.reactions.notification.type.reaction_email_digest'; // Nom complet du service
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

    /**
     * Active les types de notifications que nous venons de créer.
     *
     * La simple création dans la base de données ne suffit pas, il faut dire
     * explicitement à phpBB de les activer pour qu'ils soient utilisables.
     */
    public function enable_notification_types()
    {
        $notification_manager = $this->container->get('notification_manager');
        $notification_types = array(
            'bastien59960.reactions.notification.type.reaction', // Nom complet du service
            'bastien59960.reactions.notification.type.reaction_email_digest', // Nom complet du service
        );

        foreach ($notification_types as $type) {
            try {
                // On utilise le manager de notifications pour activer chaque type.
                $notification_manager->enable_notifications($type);
            } catch (\phpbb\notification\exception $e) {
                if (defined('DEBUG')) {
                    trigger_error('[Reactions Migration] enable_notifications(' . $type . ') failed: ' . $e->getMessage(), E_USER_NOTICE);
                }
            }
        }
    }

    /**
     * Nettoie les notifications orphelines.
     *
     * C'est une mesure de propreté : si des notifications existent pour un type
     * qui a été supprimé, cette fonction les efface pour éviter les erreurs.
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
     * Purge complètement les types de notifications de l'extension.
     *
     * Cette méthode est appelée lors de la suppression des données de l'extension.
     * Elle supprime toutes les notifications associées et les types eux-mêmes.
     */
    public function purge_notification_types()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $names = array(
            'bastien59960.reactions.notification.type.reaction',
            'bastien59960.reactions.notification.type.reaction_email_digest',
        );

        $notification_manager = $this->container->get('notification_manager');

        foreach ($names as $canonical_name) {
            try {
                // Demande à phpBB de purger toutes les notifications de ce type.
                $notification_manager->purge_notifications($canonical_name);
            } catch (\phpbb\notification\exception $e) {
                // Ignorer l'erreur
            }

            $sql = 'DELETE FROM ' . $types_table . "
                WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'";
            $this->db->sql_query($sql);
        }
    }

    /**
     * Importe les réactions d'une ancienne version de l'extension.
     *
     * Cette fonction est un exemple de migration de données. Elle vérifie l'existence
     * d'anciennes tables (`phpbb_reactions`), lit leur contenu, convertit les anciennes
     * réactions (basées sur des images PNG) en emojis Unicode, et les insère dans la
     * nouvelle table `phpbb_post_reactions`.
     */
    public function import_old_reactions()
    {
        $log = $this->container->get('log');
        $user = $this->container->get('user');
        $user->add_lang_ext('bastien59960/reactions', 'acp/common');

        $io = null;
        if ($this->container->has('console.io')) {
            $io = $this->container->get('console.io');
        }

        $old_reactions_table = $this->table_prefix . 'reactions';
        $old_types_table = $this->table_prefix . 'reaction_types';
        $new_reactions_table = $this->table_prefix . 'post_reactions';

        if ($io) $io->writeln('  -> Recherche des anciennes tables de réactions...');

        if (!$this->db_tools->sql_table_exists($old_reactions_table) || !$this->db_tools->sql_table_exists($old_types_table)) {
            if ($io) $io->writeln('     <comment>Anciennes tables non trouvées. Importation ignorée.</comment>');
            return;
        }

        // Table de correspondance entre les anciens noms de fichiers image et les emojis Unicode.
        $emoji_map = array(
            '1f44d.png' => '👍',
            '1f44e.png' => '👎',
            '1f642.png' => '🙂',
            '1f60d.png' => '😍',
            '1f602.png' => '😂',
            '1f611.png' => '😑',
            '1f641.png' => '🙁',
            '1f62f.png' => '😯',
            '1f62d.png' => '😭',
            '1f621.png' => '😡',
            'OMG.png'   => '😮',
        );

        $sql = 'SELECT reaction_user_id, post_id, topic_id, reaction_file_name, reaction_time 
                FROM ' . $old_reactions_table . '
                ORDER BY reaction_time ASC';
        $result = $this->db->sql_query($sql);
        $old_reactions = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);

        $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_START');

        $total_old = count($old_reactions);
        if ($io) $io->writeln("     <info>{$total_old} anciennes réactions trouvées.</info>");

        if (empty($old_reactions)) {
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_EMPTY');
            return;
        }

        $reactions_to_insert = array();
        $existing_keys = array();

        if ($io) {
            $io->writeln('  -> Conversion des anciennes réactions...');
            $io->progress_start($total_old);
        }

        foreach ($old_reactions as $row) {
            if ($io) $io->progress_advance();

            $png_name = $row['reaction_file_name'];

            if (!isset($emoji_map[$png_name])) {
                continue;
            }

            $emoji = $emoji_map[$png_name];
            $post_id = (int) $row['post_id'];
            $user_id = (int) $row['reaction_user_id'];
            $key = $post_id . '-' . $user_id;

            if (isset($existing_keys[$key]) && in_array($emoji, $existing_keys[$key])) {
                continue;
            }

            if (!isset($existing_keys[$key])) {
                $existing_keys[$key] = array();
            }

            $reactions_to_insert[] = array(
                'post_id'           => $post_id,
                'topic_id'          => (int) $row['topic_id'],
                'user_id'           => $user_id,
                'reaction_emoji'    => $emoji,
                'reaction_time'     => (int) $row['reaction_time'],
                'reaction_notified' => 0,
            );

            $existing_keys[$key][] = $emoji;
        }

        if ($io) $io->progress_finish();

        if (!empty($reactions_to_insert)) {
            $affected_users = array();
            $affected_posts = array();
            foreach ($reactions_to_insert as $reaction) {
                $affected_users[$reaction['user_id']] = true;
                $affected_posts[$reaction['post_id']] = true;
            }
            $count_users = count($affected_users);
            $count_posts = count($affected_posts);

            $count_to_insert = count($reactions_to_insert);
            $skipped_count = $total_old - $count_to_insert;

            if ($io) {
                $io->writeln("     <info>Préparation de l'insertion de {$count_to_insert} réactions pour {$count_users} utilisateurs sur {$count_posts} messages.</info> ({$skipped_count} ignorées/doublons)");
            }

            $this->db->sql_multi_insert($new_reactions_table, $reactions_to_insert);

            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array($count_to_insert, $skipped_count, $count_users, $count_posts));

            if ($io) $io->writeln('     <info>Importation terminée avec succès.</info>');
        } else {
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array(0, $total_old, 0, 0));
            if ($io) $io->writeln('     <comment>Aucune nouvelle réaction à importer (toutes ignorées/doublons).</comment>');
        }
    }
}
