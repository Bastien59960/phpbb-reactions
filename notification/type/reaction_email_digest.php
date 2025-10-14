<?php
/**
 * Fichier : notification/type/reaction_email_digest.php — bastien59960/reactions/notification/type/reaction_email_digest.php
 *
 * Type de notification "Résumé e-mail des réactions" pour l'extension Reactions.
 *
 * Ce type est utilisé exclusivement par la tâche cron afin d'envoyer un digest périodique
 * des réactions reçues. Aucune entrée n'est créée dans la cloche phpBB.
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB')) {
    exit;
}

use phpbb\notification\type\base;

class reaction_email_digest extends base
{
    /**
     * Identifiant unique du type de notification.
     */
    public function get_type()
    {
        return 'notification.type.reaction_email_digest';
    }

    /**
     * Ce type n'expose que la méthode email.
     */
    public function get_notification_methods()
    {
        return ['email'];
    }

    /**
     * Clé de langue affichée dans l'UCP (titre).
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE';
    }

    /**
     * Clé de langue affichée dans l'UCP (description).
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC';
    }

    /**
     * Aucune notification individuelle n'est créée : tableau vide.
     */
    public function find_users_for_notification($data, $options = [])
    {
        return [];
    }

    public function create_insert_array($data, $pre_create_data = [])
    {
        return [];
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
        return 'reaction_digest';
    }

    public function get_email_template_variables()
    {
        return [];
    }

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
        return [];
    }

    public function get_url()
    {
        return '';
    }
}
