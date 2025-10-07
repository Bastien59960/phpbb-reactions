<?php
/**
 * Notification type : Reaction email digest
 * (classe minimale - n'utilise que la méthode email)
 */

namespace bastien59960\reactions\notification\type;

if (!defined('IN_PHPBB')) {
    exit;
}

use phpbb\notification\type\base;

class reaction_email_digest extends base
{
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
}
