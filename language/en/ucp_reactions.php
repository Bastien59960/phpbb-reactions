<?php
/**
 * File: ucp_reactions.php
 * Path: bastien59960/reactions/language/en/ucp_reactions.php
 * Author: Bastien (bastien59960)
 * GitHub: https://github.com/bastien59960/reactions/blob/main/language/en/ucp_reactions.php
 *
 * Role:
 * This file contains the English language strings for the reaction preferences
 * page in the User Control Panel (UCP).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    'UCP_REACTIONS_TITLE'           => 'Reaction Preferences',
    'UCP_REACTIONS_EXPLAIN'         => 'Choose how to be notified when members react to your posts.',
    'UCP_REACTIONS_NOTIFY'          => 'Notify me of new reactions (notification)',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'  => 'Receive a notification when a user reacts to one of your posts.',
    'UCP_REACTIONS_CRON_EMAIL'           => 'Notify me of new reactions (email)',
    'UCP_REACTIONS_CRON_EMAIL_EXPLAIN'   => 'Receive a periodic email summary of new reactions on your posts.',
    'UCP_REACTIONS_SAVED'           => 'Your reaction preferences have been saved.',
));