<?php
/**
 * Reactions Extension for phpBB 3.3
 * English language file for notifications
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

// Merge the existing language array
$lang = array_merge($lang, array(
    'NOTIFICATION_TYPE_REACTION' => 'Someone reacted to your message',
    'NOTIFICATION_GROUP_REACTIONS' => 'Reaction notifications',
));
