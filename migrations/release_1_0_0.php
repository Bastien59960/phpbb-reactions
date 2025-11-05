<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /migrations/release_1_0_0.php
 * Rôle :
 * Ce fichier est la migration d'installation initiale de l'extension. Il est
 * exécuté une seule fois lors de la première activation pour mettre en place la
 * structure de la base de données : création de la table des réactions, ajout des
 * colonnes pour les préférences utilisateur, insertion des configurations par
 * défaut et enregistrement des nouveaux types de notification. Il gère aussi
 * l'importation des données d'une ancienne extension si détectée.
 */

namespace bastien59960\reactions\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
    /**
     * Vérifie si la migration a déjà été appliquée.
     *
     * Cette méthode est une sécurité pour empêcher phpBB de ré-exécuter une migration
     * qui a déjà été installée. Elle vérifie la présence de la table principale
     * et d'un type de notification pour déterminer si l'installation est complète.
     *
     * @return bool True si l'extension semble déjà installée, False sinon.
     */
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


    /**
     * Définit les dépendances de cette migration.
     *
     * Cette migration ne s'exécutera que si la migration `v3310` de phpBB (version 3.3.10)
     * a déjà été appliquée, garantissant la compatibilité.
     */
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v33x\v3310');
    }

    /**
     * Définit les modifications à apporter au schéma de la base de données.
     *
     * Cette méthode est déclarative. Elle retourne un tableau décrivant les tables
     * et les colonnes à créer, que phpBB exécutera.
     */
    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'post_reactions' => array(
                    'COLUMNS' => array(
                        'reaction_id'      => array('UINT', null, 'auto_increment'), // Clé primaire
                        'post_id'          => array('UINT', 0),                      // ID du message réagi
                        'topic_id'         => array('UINT', 0),                      // ID du sujet (pour performance)
                        'user_id'          => array('UINT', 0),                      // ID de l'utilisateur qui a réagi
                        'reaction_emoji'   => array('VCHAR:191', ''),                // L'emoji (supporte les emojis composés)
                        'reaction_time'    => array('UINT:11', 0),                   // Timestamp de la réaction
                        'reaction_notified'=> array('BOOL', 0),                      // Flag pour le cron (0 = non notifié, 1 = notifié)
                    ),
                    'PRIMARY_KEY' => 'reaction_id',
                    'KEYS' => array(
                        'post_id'           => array('INDEX', 'post_id'), // Index pour retrouver les réactions d'un message
                        'topic_id'          => array('INDEX', 'topic_id'),// Index pour les nettoyages par sujet
                        'user_id'           => array('INDEX', 'user_id'), // Index pour retrouver les réactions d'un utilisateur
                        'post_notified_idx' => array('INDEX', array('post_id', 'reaction_notified')), // Index pour le cron
                    ),
                ),
            ),
            'add_columns' => array(
                $this->table_prefix . 'notifications' => array(
                ),
                $this->table_prefix . 'users' => array(
                    'user_reactions_notify'     => array('BOOL', 1), // Préférence UCP : Activer/désactiver les notifs cloche
                    'user_reactions_cron_email' => array('BOOL', 1), // Préférence UCP : Activer/désactiver les résumés e-mail
                ),
            ),
        );
    }

    /**
     * Définit comment annuler les modifications du schéma.
     *
     * Cette méthode est appelée lorsque l'extension est désinstallée (purgée).
     * Elle supprime les tables et colonnes créées par `update_schema`.
     */
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

    /**
     * Définit les données à insérer ou les fonctions personnalisées à exécuter.
     *
     * Cette méthode est appelée après `update_schema`. Elle insère les configurations
     * par défaut et exécute des fonctions PHP pour des logiques plus complexes.
     */
    public function update_data()
    {
        return array(
            // Options de configuration générales avec leurs valeurs par défaut.
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

            // Fonctions personnalisées à exécuter après la mise à jour du schéma.
            array('custom', array(array($this, 'set_utf8mb4_bin'))),
            array('custom', array(array($this, 'create_notification_type'))),
            array('custom', array(array($this, 'enable_notification_types'))),
            array('custom', array(array($this, 'clean_orphan_notifications'))),
            array('custom', array(array($this, 'import_old_reactions'))),
        );
    }

    /**
     * Annule les modifications de données.
     *
     * Appelée lors de la purge de l'extension, cette méthode supprime les configurations
     * et exécute les fonctions de nettoyage.
     */
    public function revert_data()
    {
        return array(
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

            array('custom', array(array($this, 'purge_notification_types'))),
        );
    }

    /**
     * [CUSTOM] Force le jeu de caractères `utf8mb4` pour la colonne des emojis.
     *
     * C'est une étape CRUCIALE pour garantir que les emojis modernes (qui peuvent
     * utiliser 4 octets) sont stockés et comparés correctement dans la base de données.
     */
    public function set_utf8mb4_bin()
    {
        $table_name = $this->table_prefix . 'post_reactions';
        $sql = "ALTER TABLE {$table_name}
        MODIFY `reaction_emoji` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''";
        $this->db->sql_query($sql);
    }

    /**
     * [CUSTOM] Enregistre les types de notification dans la base de données.
     *
     * Cette fonction insère les deux types de notification de l'extension dans la
     * table `phpbb_notification_types`, ce qui les rend disponibles dans l'UCP
     * et pour le système de notification.
     */
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

    /**
     * [CUSTOM] Active les types de notification via le manager.
     * C'est la méthode recommandée pour s'assurer que les notifications
     * sont correctement enregistrées et activées pour les utilisateurs.
     */
    public function enable_notification_types()
    {
        $notification_manager = $this->container->get('notification_manager');
        $notification_types = [
            'notification.type.reaction',
            'notification.type.reaction_email_digest',
        ];

        foreach ($notification_types as $type)
        {
            try {
                $notification_manager->enable_notifications($type);
            } catch (\phpbb\notification\exception $e) {
                if (defined('DEBUG')) {
                    trigger_error('[Reactions Migration] enable_notifications(' . $type . ') failed: ' . $e->getMessage(), E_USER_NOTICE);
                }
            }
        }
    }

    /**
     * [CUSTOM] Nettoie les notifications orphelines.
     *
     * C'est une mesure de sécurité qui supprime les notifications de la table
     * `phpbb_notifications` si leur type de notification n'existe plus dans la
     * table `phpbb_notification_types`, évitant ainsi des erreurs.
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
     * [CUSTOM] Supprime les types de notification lors de la purge.
     *
     * C'est l'opération inverse de `create_notification_type`, appelée lors de la
     * désinstallation complète de l'extension.
     */
public function purge_notification_types()
{
    $types_table = $this->table_prefix . 'notification_types';
    $names = array(
        'notification.type.reaction',
        'notification.type.reaction_email_digest',
    );

    $notification_manager = $this->container->get('notification_manager');

    foreach ($names as $canonical_name) {
        // 1. Purger toutes les notifications existantes de ce type
        try {
            $notification_manager->purge_notifications($canonical_name);
        } catch (\phpbb\notification\exception $e) {
            // Ignorer l'erreur si le type n'existe pas
        }

        // 2. Supprimer le type de notification lui-même
        $sql = 'DELETE FROM ' . $types_table . "
            WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'";
        $this->db->sql_query($sql);
    }
}

    /**
     * [CUSTOM] Importe les réactions d'une ancienne extension concurrente.
     *
     * Cette fonction est appelée à l'installation. Elle détecte la présence des
     * tables `phpbb_reactions` et `phpbb_reaction_types`. Si elles existent,
     * elle convertit les anciennes données (basées sur des images PNG) en nouvelles
     * données (basées sur des emojis Unicode) et les insère dans la nouvelle table.
     * Elle fournit une sortie détaillée en ligne de commande et un résumé dans le journal d'administration.
     */
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
        if ($io) $io->writeln('  -> Recherche des anciennes tables de réactions...');

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
        
        // Pour éviter les doublons dans le lot d'insertion (ex: si le fichier source a des doublons),
        // on utilise un tableau de clés pour suivre ce qui a déjà été traité.
        $existing_keys = [];

        if ($io) {
            $io->writeln('  -> Conversion des anciennes réactions...');
            $io->progress_start($total_old);
        }

        // On parcourt chaque ancienne réaction pour la convertir.
        foreach ($old_reactions as $row)
        {
            if ($io) $io->progress_advance();

            $png_name = $row['reaction_file_name'];

            // Ignorer si le nom de fichier n'est pas dans notre table de correspondance.
            if (!isset($emoji_map[$png_name]))
            {
                continue;
            }

            $emoji = $emoji_map[$png_name];
            $post_id = (int) $row['post_id'];
            $user_id = (int) $row['reaction_user_id'];

            // Créer une clé unique pour cette réaction (post-utilisateur-emoji) pour la déduplication.
            $key = $post_id . '-' . $user_id;

            // Si cette clé a déjà été ajoutée dans ce lot, on l'ignore pour éviter les doublons.
            if (isset($existing_keys[$key]) && in_array($emoji, $existing_keys[$key]))
            {
                continue;
            }

            // Initialiser le tableau pour cet utilisateur/post si ce n'est pas déjà fait
            if (!isset($existing_keys[$key]))
            {
                $existing_keys[$key] = [];
            }

            // Ajouter la réaction convertie au tableau pour l'insertion.
            $reactions_to_insert[] = [
                'post_id'           => $post_id,
                'topic_id'          => (int) $row['topic_id'],
                'user_id'           => $user_id,
                'reaction_emoji'    => $emoji,
                'reaction_time'     => (int) $row['reaction_time'],
                'reaction_notified' => 0, // Comme demandé, mis à 0.
            ];

            // Marquer cette clé comme traitée.
            $existing_keys[$key][] = $emoji;
        }

        if ($io) $io->progress_finish();

        // Étape 5 : Insérer toutes les nouvelles réactions en une seule fois.
        if (!empty($reactions_to_insert))
        {
            // Calculer des statistiques supplémentaires pour un résumé plus riche.
            $affected_users = [];
            $affected_posts = [];
            foreach ($reactions_to_insert as $reaction) {
                $affected_users[$reaction['user_id']] = true;
                $affected_posts[$reaction['post_id']] = true;
            }
            $count_users = count($affected_users);
            $count_posts = count($affected_posts);

            $count_to_insert = count($reactions_to_insert);
            $skipped_count = $total_old - $count_to_insert;

            // Afficher le résumé dans la console.
            if ($io) {
                $io->writeln("     <info>Préparation de l'insertion de {$count_to_insert} réactions pour {$count_users} utilisateurs sur {$count_posts} messages.</info> ({$skipped_count} ignorées/doublons)");
            }

            // Utiliser `sql_multi_insert` est beaucoup plus performant que des milliers de requêtes `INSERT` individuelles.
            $this->db->sql_multi_insert($new_reactions_table, $reactions_to_insert);

            // Écrire le résumé final dans le journal d'administration.
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array($count_to_insert, $skipped_count, $count_users, $count_posts));

            if ($io) $io->writeln('     <info>Importation terminée avec succès.</info>');
        }
        else
        {
            // Cas où il y avait des données mais aucune n'a été jugée valide pour l'importation.
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array(0, $total_old, 0, 0));
            if ($io) $io->writeln('     <comment>Aucune nouvelle réaction à importer (toutes ignorées/doublons).</comment>');
        }
    }

}
