<?php
/**
 * Reactions Extension for phpBB 3.3
 * Notification type: Reaction email digest (summary sent by cron)
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\notification\type;

use phpbb\notification\type\base;

class reaction_email_digest extends base
{
    /**
     * Get notification type name (used internally)
     */
    public function get_type()
    {
        return 'notification.type.reaction_email_digest';
    }

    /**
     * Define notification methods (only email, no cloche)
     */
    public function get_notification_methods()
    {
        return array('email');
    }

    /**
     * Return the title shown in UCP
     */
    public static function get_item_type_name()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE';
    }

    /**
     * Description in UCP
     */
    public static function get_item_type_description()
    {
        return 'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC';
    }

    /**
     * This type does not store individual entries in the notifications table.
     * The cron will trigger it manually.
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

    public function get_reference()
    {
        return '';
    }

    public function get_email_template()
    {
        return '@bastien59960_reactions/email/reaction_digest.txt';
    }

    public function get_email_template_variables()
    {
        return array();
    }
}
