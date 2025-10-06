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

// ============================================================================
// Reaction notifications
// ============================================================================

$lang = array_merge($lang, array(
	// Displayed in the notification dropdown or notifications page
	// Example: "Alice reacted to your post with ❤️"
	'NOTIFICATION_TYPE_REACTION'   => '%s reacted to your post with %s',

	// Group title in the UCP notification settings
	'NOTIFICATION_GROUP_REACTIONS' => 'Reaction notifications',

	// Title and description for the notification type in the UCP
	'NOTIFICATION_REACTION_TITLE'  => 'Reactions to your posts',
	'NOTIFICATION_REACTION_DESC'   => 'Receive a notification when someone reacts to one of your posts.',
));
