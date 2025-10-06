<?php
/**
 * Migration release_1_0_3 : Insertion et normalisation du type de notification 'notification.type.reaction'
 *
 * Cette migration :
 *  - s'assure que la ligne canonique 'notification.type.reaction' existe dans la table <prefix>notification_types
 *  - si des anciennes entrées 'reaction' (noms différents) existent, migre les notifications correspondantes
 *    dans <prefix>phpbb_notifications vers l'ID canonique, puis supprime les doublons obsolètes
 *
 * Cette approche permet de corriger l'erreur NOTIFICATION_TYPE_NOT_EXIST dans l'UCP sans perdre
 * les notifications existantes.
 *
 * ATTENTION :
 *  - Faire un dump de la base avant d'exécuter.
 *  - Le revert supprime la ligne canonique si elle existe (danger si notifications déjà migrées).
 *
 * @package bastien59960.reactions
 */

namespace bastien59960\reactions\migrations;

class release_1_0_3 extends \phpbb\db\migration\migration
{
    /**
     * Vérifie si la migration est déjà appliquée.
     * On vérifie la présence du type canonique (case-insensitive).
     *
     * @return bool
     */
    public function effectively_installed()
    {
        $types_table = $this->table_prefix . 'notification_types';

        $sql = 'SELECT notification_type_id
                FROM ' . $types_table . "
                WHERE LOWER(notification_type_name) = 'notification.type.reaction'
                LIMIT 1";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return ($row !== false);
    }

    /**
     * Dépendances — s'assurer que la migration précédente (ex: ajout colonnes notifications) est appliquée.
     *
     * @return array
     */
    public static function depends_on()
    {
        return array('bastien59960\reactions\migrations\release_1_0_2');
    }

    /**
     * update_data : exécution principale (méthode custom).
     * On encapsule la logique dans une méthode custom insert_and_migrate().
     *
     * @return array
     */
    public function update_data()
    {
        return array(
            array('custom', array(array($this, 'insert_and_migrate_notification_type'))),
        );
    }

    /**
     * Méthode principale : insère le type canonique si absent, migre les anciennes références et supprime doublons.
     *
     * Étapes :
     * 1) s'assurer que la table notification_types existe
     * 2) créer la ligne 'notification.type.reaction' si absente
     * 3) récupérer l'ID canonique
     * 4) récupérer les IDs obsolètes (noms contenant 'reaction' mais != canonical)
     * 5) si obsolètes : UPDATE phpbb_notifications SET notification_type_id = canonical_id WHERE notification_type_id IN (old_ids)
     * 6) supprimer les lignes obsolètes de notification_types
     */
    public function insert_and_migrate_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $notifications_table = $this->table_prefix . 'notifications';

        // 1) vérifier existence logique de la table (SHOW TABLES LIKE)
        try {
            $check_sql = 'SHOW TABLES LIKE \'' . $this->db->sql_escape($types_table) . '\'';
            $res = $this->db->sql_query($check_sql);
            $exists = (bool) $this->db->sql_fetchrow($res);
            $this->db->sql_freeresult($res);
        } catch (\Exception $e) {
            // Si la table manque, abort (ne pas tenter d'insérer)
            if (defined('DEBUG') && DEBUG) {
                trigger_error('Reactions migration: table ' . $types_table . ' inaccessible: ' . $e->getMessage(), E_USER_WARNING);
            }
            return;
        }

        if (!$exists) {
            // Table introuvable — sortie sécurisée
            if (defined('DEBUG') && DEBUG) {
                trigger_error('Reactions migration: table ' . $types_table . ' introuvable, skip.', E_USER_WARNING);
            }
            return;
        }

        // 2) Insérer la ligne canonique si absente (tolérant casse)
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

        // 3) récupérer l'id canonique
        $sql = 'SELECT notification_type_id FROM ' . $types_table . " WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "' LIMIT 1";
        $result = $this->db->sql_query($sql);
        $canon_row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$canon_row || !isset($canon_row['notification_type_id'])) {
            // Pas d'id canonique -> abort
            if (defined('DEBUG') && DEBUG) {
                trigger_error('Reactions migration: impossible de récupérer notification_type_id canonique', E_USER_WARNING);
            }
            return;
        }

        $canonical_id = (int) $canon_row['notification_type_id'];

        // 4) rechercher anciens types contenant 'reaction' (tolérant casse) mais excluant le canonique
        $sql = 'SELECT notification_type_id, notification_type_name FROM ' . $types_table . " WHERE LOWER(notification_type_name) LIKE '%reaction%' AND LOWER(notification_type_name) != '" . $this->db->sql_escape(strtolower($canonical_name)) . "'";
        $result = $this->db->sql_query($sql);

        $old_ids = array();
        while ($r = $this->db->sql_fetchrow($result)) {
            $old_ids[] = (int) $r['notification_type_id'];
        }
        $this->db->sql_freeresult($result);

        // 5) Si on a des anciens ids -> migrer les notifications référencées
        if (!empty($old_ids)) {
            // Mettre à jour phpbb_notifications pour pointer vers l'id canonique
            $where_in = $this->db->sql_in_set('notification_type_id', $old_ids);
            $update_sql = 'UPDATE ' . $notifications_table . ' SET notification_type_id = ' . $canonical_id . ' WHERE ' . $where_in;
            $this->db->sql_query($update_sql);

            // 6) supprimer les anciennes lignes de phpbb_notification_types (après verification)
            $delete_sql = 'DELETE FROM ' . $types_table . ' WHERE ' . $where_in;
            $this->db->sql_query($delete_sql);

            if (defined('DEBUG') && DEBUG) {
                trigger_error('Reactions migration: anciens types migrés et supprimés (' . implode(',', $old_ids) . ')', E_USER_NOTICE);
            }
        }
    }

    /**
     * Revert : suppression du type canonique.
     * ATTENTION : ceci supprimera la ligne 'notification.type.reaction' si elle existe.
     * Ne pas exécuter le revert si des notifications ont été migrées (risque d'incohérence).
     *
     * @return array
     */
    public function revert_data()
    {
        return array(
            array('custom', array(array($this, 'remove_notification_type'))),
        );
    }

    /**
     * Suppression effective du type canonique (utilisé lors d'un revert).
     */
    public function remove_notification_type()
    {
        $types_table = $this->table_prefix . 'notification_types';
        $canonical_name = 'notification.type.reaction';

        $sql = 'SELECT notification_type_id FROM ' . $types_table . " WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "' LIMIT 1";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($row) {
            // Suppression
            $this->db->sql_query('DELETE FROM ' . $types_table . " WHERE LOWER(notification_type_name) = '" . $this->db->sql_escape(strtolower($canonical_name)) . "'");
        }
    }
}
