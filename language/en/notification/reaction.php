<?php
/**
 * File: language/en/notification/reaction.php
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

$lang = array_merge($lang ?? [], [
	'NOTIFICATION_TYPE_REACTION'		=> '%1$s reacted to your post with %2$s',
	'NOTIFICATION_TYPE_REACTION_TITLE'	=> 'Reactions to your posts',
	'NOTIFICATION_TYPE_REACTION_DESC'	=> 'Receive a notification when someone reacts to one of your posts.',
]);