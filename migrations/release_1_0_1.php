<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /migrations/release_1_0_1.php
 * --------------------------------------------------------------
 * Rôle :
 * Migration corrective et évolutive.
 * Cette migration utilise le conteneur de services pour interagir avec
 * le gestionnaire de notifications et la configuration, ce qui nécessite
 * d'hériter de `container_aware_migration`.
 * --------------------------------------------------------------
 */

namespace bastien59960\reactions\migrations;

/**
 * Migration 1.0.1
 *
 * CORRECTION CRITIQUE :
 * La classe hérite de `\phpbb\db\migration\container_aware_migration`
 * pour que le conteneur de services ($this->container) soit injecté
 * automatiquement. Cela résout l'erreur "Undefined property: $container".
 */
class release_1_0_1 extends \phpbb\db\migration\container_aware_migration
{
    /**
     * Dépendances de cette migration.
     * Elle ne s'exécutera qu'après l'installation de la version 1.0.0.
     */
    static public function depends_on()
    {
        return array('\bastien59960\reactions\migrations\release_1_0_0');
    }

    /**
     * Applique les modifications de données.
     *
     * @return array
     */
    public function update_data()
    {
        return array(
            // Étape 1 : Appeler la fonction personnalisée pour l'importation
            array('custom', array(array($this, 'import_from_old_extension'))),
            // Étape 2 : Ajouter une configuration pour marquer l'importation comme terminée
            array('config.add', array('bastien59960_reactions_imported_from_old', 1)),
        );
    }

    /**
     * Annule les modifications de données.
     *
     * @return array
     */
    public function revert_data()
    {
        // Annule les actions de `update_data()`.
        // Note : On ne supprime PAS les données importées lors d'une désinstallation.
        // On supprime juste la clé de configuration qui indique que l'import a eu lieu.
        return array(
            array('config.remove', array('bastien59960_reactions_imported_from_old')),
        );
    }

    /**
     * Fonction personnalisée pour importer les données depuis une ancienne extension.
     *
     * Hypothèses sur l'ancienne structure :
     * - Table : phpbb_reactions
     * - Colonnes : post_id, user_id, reaction (emoji)
     *
     * Cette fonction est idempotente : elle ne fera rien si l'ancienne table n'existe pas.
     */
    public function import_from_old_extension()
    {
        // Récupérer les services nécessaires depuis le conteneur
        /** @var \phpbb\log\log_interface $log */
        $log = $this->container->get('log');
        /** @var \phpbb\user $user */
        $user = $this->container->get('user');
        
        // Charger la langue pour les messages de log
        $user->add_lang_ext('bastien59960/reactions', 'acp/common');

        $old_reactions_table = $this->table_prefix . 'reactions';
        $old_types_table = $this->table_prefix . 'reaction_types';
        $new_reactions_table = $this->table_prefix . 'post_reactions';

        // Étape 1 : Vérifier si les anciennes tables existent.
        if (!$this->db_tools->sql_table_exists($old_reactions_table) || !$this->db_tools->sql_table_exists($old_types_table))
        {
            // L'ancienne table n'existe pas, on ne fait rien.
            return true; // CRITIQUE : Retour explicite requis
        }

        try
        {
            // Étape 2 : Définir la correspondance emoji.
            $emoji_map = array(
                '1f44d.png' => '👍', '1f44e.png' => '👎', '1f642.png' => '🙂',
                '1f60d.png' => '😍', '1f602.png' => '😂', '1f611.png' => '😑',
                '1f641.png' => '🙁', '1f62f.png' => '😯', '1f62d.png' => '😭',
                '1f621.png' => '😡', 'OMG.png'   => '😮',
            );

            // Étape 3 : Lire toutes les anciennes réactions.
            $sql = 'SELECT reaction_user_id, post_id, topic_id, reaction_file_name, reaction_time 
                    FROM ' . $old_reactions_table . '
                    ORDER BY reaction_time ASC';
            $result = $this->db->sql_query($sql);
            $old_reactions = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);

            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_START');

            $total_old = count($old_reactions);
            if (empty($old_reactions))
            {
                $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_EMPTY');
                return true; // CRITIQUE : Retour explicite requis
            }

            // Étape 4 : Préparer les données pour une insertion en masse.
            $reactions_to_insert = array();
            $existing_keys = array();

            foreach ($old_reactions as $row)
            {
                $png_name = $row['reaction_file_name'];
                if (!isset($emoji_map[$png_name]))
                {
                    continue;
                }

                $emoji = $emoji_map[$png_name];
                $post_id = (int) $row['post_id'];
                $user_id = (int) $row['reaction_user_id'];

                // La clé unique doit inclure l'emoji pour permettre plusieurs réactions différentes par utilisateur.
                $key = $post_id . '-' . $user_id . '-' . $emoji;

                if (isset($existing_keys[$key]))
                {
                    continue;
                }

                $reactions_to_insert[] = array(
                    'post_id'           => $post_id,
                    'topic_id'          => (int) $row['topic_id'],
                    'user_id'           => $user_id,
                    'reaction_emoji'    => $emoji,
                    'reaction_time'     => (int) $row['reaction_time'],
                    'reaction_notified' => 0,
                );

                $existing_keys[$key] = true;
            }

            // Étape 5 : Insérer toutes les nouvelles réactions en une seule fois.
            if (!empty($reactions_to_insert))
            {
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

                // La table `phpbb_post_reactions` a une clé UNIQUE `post_user_emoji`
                // sur (post_id, user_id, reaction_emoji).
                // On peut donc utiliser `sql_multi_insert` directement, qui gérera les doublons
                // en ignorant les lignes déjà présentes (comportement par défaut de cette méthode).
                $this->db->sql_multi_insert($new_reactions_table, $reactions_to_insert);

                $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array($count_to_insert, $skipped_count, $count_users, $count_posts));
            }
            else
            {
                $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_SUCCESS', false, array(0, $total_old, 0, 0));
            }
        }
        catch (\Throwable $e) {
            // En cas d'erreur, on logue le problème mais on ne bloque pas la migration.
            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_REACTIONS_IMPORT_FAILED', false, array($e->getMessage()));
        }
        
        // CRITIQUE : Retour explicite requis
        return true;
    }
}