<?php
/**
 * Fichier : reaction.php
 * Chemin : bastien59960/reactions/language/en/notification/reaction.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/en/notification/reaction.php
 *
 * Rôle :
 * This file defines the English language strings for the notifications of the
 * Reactions extension. It is used to display messages in the notification
 * bell and in the user preferences (UCP).
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
	// Notification group in UCP
	'NOTIFICATION_GROUP_REACTIONS'       => 'Reactions',

	// Notification Type: Instant reaction (bell)
	'NOTIFICATION_TYPE_REACTION'         => '<strong>%1$s</strong> reacted to your post with %2$s',
	'NOTIFICATION_TYPE_REACTION_TITLE'   => 'Someone reacted to one of your posts',
	'NOTIFICATION_TYPE_REACTION_DESC'    => 'Receive a notification when a user reacts to one of your posts.',

	// Notification Type: Email digest (cron)
	'NOTIFICATION_TYPE_REACTION_EMAIL_DIGEST_TITLE' => 'Reactions e-mail summary',
	'NOTIFICATION_TYPE_REACTION_EMAIL_DIGEST_DESC'  => 'Periodically receive an e-mail summary of reactions to your posts.',
));
