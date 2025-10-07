<?php
/**
 * Reactions Extension for phpBB 3.3
 * English language file for reaction notifications
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

// ============================================================================
// Reaction Notifications
// ============================================================================

$lang = array_merge($lang, array(
	// Text shown in the bell menu or notifications page
	'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => '%s reacted to your post with %s',

	// Group title in the User Control Panel (UCP)
	'NOTIFICATION_GROUP_REACTIONS' => 'Reaction notifications',

	// Title and description of the notification type in the UCP
	'NOTIFICATION_NOTIFICATION.TYPE.REACTION_TITLE' => 'Reactions to your posts',
	'NOTIFICATION_NOTIFICATION.TYPE.REACTION_DESC'  => 'Receive a notification whenever another user reacts to one of your posts.',
));
