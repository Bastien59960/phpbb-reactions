<?php
/**
 * Fichier : notification/type/reaction_email_digest.php — bastien59960/reactions/notification/type/reaction_email_digest.php
 *
 * Type de notification "Résumé e-mail des réactions" pour l'extension Reactions.
 *
 * Ce fichier définit la classe de notification utilisée pour envoyer un résumé périodique par e-mail des réactions reçues sur les messages d'un utilisateur (notification par cron).
 *
 * Points clés de la logique métier :
 *   - Ce type n'est pas stocké individuellement dans la table des notifications (pas de cloche)
 *   - Utilisé uniquement pour l'affichage dans l'UCP et la gestion des préférences
 *   - Génère les clés de langue pour le titre et la description dans l'UCP
 *   - Fournit le nom du template d'e-mail utilisé par le cron
 *
 * Ce type de notification permet à l'utilisateur d'activer ou non le résumé e-mail dans ses préférences UCP.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB')) {
    exit;
}

use phpbb\notification\type\base;

class reaction_email_digest extends base
{
    public function __construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $notifications_table)
    {
        parent::__construct($db, $language, $user, $auth, $phpbb_root_path, $php_ext, $notifications_table);
        // Insertion automatique du type de notification si absent
        $type_name = $this->get_type();
        $types_table = 'phpbb_notification_types';
        // Vérifier si la colonne notification_type_name existe
        $col_check_sql = 'SHOW COLUMNS FROM ' . $types_table . " LIKE 'notification_type_name'";
        $col_result = $db->sql_query($col_check_sql);
        $col_exists = $db->sql_fetchrow($col_result);
        $db->sql_freeresult($col_result);
        if ($col_exists) {
            // Vérifier si le type existe déjà
            $sql = 'SELECT notification_type_id FROM ' . $types_table . ' WHERE notification_type_name = \'" . $db->sql_escape($type_name) . "\' LIMIT 1';
            $result = $db->sql_query($sql);
            $exists = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);
            if (!$exists) {
                $proto_data = array(
                    'notification_type_name'    => $type_name,
                    'notification_type_enabled' => 1,
                );
                $db->sql_query('INSERT INTO ' . $types_table . ' ' . $db->sql_build_array('INSERT', $proto_data));
                if (defined('DEBUG') && DEBUG) {
                    error_log('[Reactions Notification] Type inséré: ' . $type_name);
                }
            }
        }
    }

    public function get_type()
    {
        return 'notification.type.reaction_email_digest';
    }

    /**
     * Indique qu'on propose uniquement la méthode email pour ce type.
     */
    public function get_notification_methods()
    {
        return array('email');
    }

    /**
     * Clé de langue affichée dans l'UCP (titre)
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE';
    }

    /**
     * Description dans l'UCP
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC';
    }

    /**
     * Par défaut on ne crée pas d'entrées individuelles dans phpbb_notifications
     * pour ce type (le cron fait l'agrégation et l'envoi).
     * On fournit des méthodes vides / compatibles.
     */
    public function find_users_for_notification($data, $options = array())
    {
        return array();
    }

    public function create_insert_array($data, $pre_create_data = array())
    {
        return array();
    }

    public function get_title()
    {
        return '';
    }

    public function get_language_file()
    {
        return 'notification/notification.type.reaction_email_digest';
    }

    public function get_email_template()
    {
        // Le cron utilise les templates language/*/email/reaction.txt
        return 'reaction';
    }

    public function get_email_template_variables()
    {
        return array();
    }

    /**
     * Méthodes requises par l'interface type_interface
     */
    public static function get_item_id($data)
    {
        return 0;
    }

    public static function get_item_parent_id($data)
    {
        return 0;
    }

    public function users_to_query()
    {
        return array();
    }

    public function get_url()
    {
        return '';
    }
}
