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
                WHERE notification_type_name = 'bastien59960.reactions.notification.type.reaction'";
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

            // Étapes personnalisées
            array('custom', array(array($this, 'set_utf8mb4_bin'))),
            array('custom', array(array($this, 'create_notification_type'))),
            array('custom', array(array($this, 'enable_notification_types'))),
            array('custom', array(array($this, 'clean_orphan_notifications'))),
            array('custom', array(array($this, 'import_old_reactions'))),
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

            // Suppression des notifications
            array('custom', array(array($this, 'purge_notification_types'))),
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

    public function create_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';

        $obsolete = [
            // Anciens noms incorrects à nettoyer
            'reaction',
            'reaction_email_digest',
            'bastien59960.reactions.reaction',
            'bastien59960.reactions.reaction_email_digest',
            'notification.type.reaction',
            'notification.type.reaction_email_digest',
        ];
        $sql = 'DELETE FROM ' . $types_table . '
                WHERE ' . $this->db->sql_in_set('notification_type_name', $obsolete);
        $this->db->sql_query($sql);

        $types = [
            // Noms de service complets, corrects pour la BDD
            'bastien59960.reactions.notification.type.reaction',
            'bastien59960.reactions.notification.type.reaction_email_digest',
        ];

        foreach ($types as $type) {
            $sql = 'SELECT notification_type_id FROM ' . $types_table . "
                    WHERE notification_type_name = '" . $this->db->sql_escape($type) . "'";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$row) {
                $this->db->sql_query('INSERT INTO ' . $types_table . ' ' .
                    $this->db->sql_build_array('INSERT', [
                        'notification_type_name'    => $type,
                        'notification_type_enabled' => 1,
                    ])
                );
            }
        }
    }

    public function enable_notification_types()
    {
        $manager = $this->container->get('notification_manager');
        $types = [
            // Noms courts, corrects pour l'activation
            'bastien59960.reactions.reaction',
            'bastien59960.reactions.reaction_email_digest',
        ];

        foreach ($types as $type) {
            try {
                $manager->enable_notifications($type);
            } catch (\Throwable $e) {
                if (defined('DEBUG')) {
                    trigger_error('[Reactions] enable_notifications(' . $type . ') échoué : ' . $e->getMessage(), E_USER_NOTICE);
                }
            }
        }
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
    }

    public function purge_notification_types()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $names = [
            // Noms de service complets à purger
            'bastien59960.reactions.notification.type.reaction',
            'bastien59960.reactions.notification.type.reaction_email_digest',
        ];

        $manager = $this->container->get('notification_manager');

        foreach ($names as $name) {
            try {
                $manager->purge_notifications($name);
            } catch (\Throwable $e) {
                // Ignorer les erreurs
            }
            $sql = 'DELETE FROM ' . $types_table . "
                    WHERE notification_type_name = '" . $this->db->sql_escape($name) . "'";
            $this->db->sql_query($sql);
        }
    }

    public function import_old_reactions()
    {
        // Récupérer les services de log et d'utilisateur
        $log = $this->container->get('log');
        $user = $this->container->get('user');
        // Charger la langue pour les messages de log
        $user->add_lang_ext('bastien59960/reactions', 'acp/common');

        // Récupérer le helper d'affichage console s'il existe (contexte CLI)
        $io = null;
        if ($this->container->has('console.io')) {
            $io = $this->container->get('console.io');
        }

        $old_reactions_table = $this->table_prefix . 'reactions';
        $old_types_table = $this->table_prefix . 'reaction_types';
        $new_reactions_table = $this->table_prefix . 'post_reactions';

        // Affiche un message dans la console si on est en mode CLI.
        if ($io) $io->writeln('  -&gt; Recherche des anciennes tables de réactions...');

        // Étape 1 : Vérifier si les anciennes tables existent.
        if (!$this->db_tools->sql_table_exists($old_reactions_table) || !$this->db_tools->sql_table_exists($old_types_table))
        {
            // Si les tables n'existent pas, on arrête le processus. C'est un cas normal.
            if ($io) $io->writeln('     <comment>Anciennes tables non trouvées. Importation ignorée.</comment>');
            return;
        }

        // Étape 2 : Définir la correspondance entre les anciens noms de fichier PNG et les emojis Unicode.
        // C'est le cœur de la conversion.
        $emoji_map = [
            '1f44d.png' => '👍',  // Like
            '1f44e.png' => '👎',  // Dislike
            '1f642.png' => '🙂',  // Happy
            '1f60d.png' => '😍',  // Love
            '1f602.png' => '😂',  // Funny
            '1f611.png' => '😑',  // Neutral
            '1f641.png' => '🙁',  // Unhappy
            '1f62f.png' => '😯',  // Surprised
            '1f62d.png' => '😭',  // Cry
            '1f621.png' => '😡',  // Mad
            'OMG.png'   => '😮',  // OMG
        ];

        // Étape 3 : Lire toutes les anciennes réactions en une seule requête pour la performance.
        $sql = 'SELECT reaction_user_id, post_id, topic_id, reaction_file_name, reaction_time 
                FROM ' . $old_reactions_table . '
                ORDER BY reaction_time ASC';
        $result = $this->db->sql_query($sql);
        $old_reactions = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);

        // Écrit une entrée dans le journal d'administration pour tracer le début de l'opération.
        $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_START');

        $total_old = count($old_reactions);
        if ($io) $io->writeln("     <info>{$total_old} anciennes réactions trouvées.</info>");

        // Si aucune réaction n'est trouvée, on loggue et on s'arrête.
        if (empty($old_reactions))
        {
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_EMPTY');
            return; // Aucune réaction à importer.
        }

        // Étape 4 : Préparer les données pour une insertion en masse (`sql_multi_insert`).
        $reactions_to_insert = [];
        
        // Pour éviter les doublons dans le lot d'insertion, on utilise un tableau de clés.
        $existing_keys = [];

        if ($io) {
            $io->writeln('  -&gt; Conversion des anciennes réactions...');
            $io->progress_start($total_old);
        }

        foreach ($old_reactions as $row)
        {
            if ($io) $io->progress_advance();

            $png_name = $row['reaction_file_name'];

            if (!isset($emoji_map[$png_name]))
            {
                continue;
            }

            $emoji = $emoji_map[$png_name];
            $post_id = (int) $row['post_id'];
            $user_id = (int) $row['reaction_user_id'];
            $key = $post_id . '-' . $user_id . '-' . $emoji;

            if (isset($existing_keys[$key]))
            {
                continue;
            }

            $reactions_to_insert[] = [
                'post_id'           => $post_id,
                'topic_id'          => (int) $row['topic_id'],
                'user_id'           => $user_id,
                'reaction_emoji'    => $emoji,
                'reaction_time'     => (int) $row['reaction_time'],
                'reaction_notified' => 1, // On les marque comme déjà traitées.
            ];

            $existing_keys[$key] = true;
        }

        if ($io) $io->progress_finish();

        // Étape 5 : Insérer toutes les nouvelles réactions en une seule fois.
        if (!empty($reactions_to_insert))
        {
            $count_to_insert = count($reactions_to_insert);
            $skipped_count = $total_old - $count_to_insert;

            if ($io) {
                $io->writeln("     <info>Préparation de l'insertion de {$count_to_insert} réactions. ({$skipped_count} ignorées/doublons)</info>");
            }

            $this->db->sql_multi_insert($new_reactions_table, $reactions_to_insert);

            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array($count_to_insert, $skipped_count));

            if ($io) $io->writeln('     <info>Importation terminée avec succès.</info>');
        }
        else
        {
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array(0, $total_old));
            if ($io) $io->writeln('     <comment>Aucune nouvelle réaction à importer (toutes ignorées/doublons).</comment>');
        }
    }
}
