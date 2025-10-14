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
	'NOTIFICATION_GROUP_REACTIONS' => 'Reaction notifications',

	// Notification Type: Instant reaction (bell)
	'NOTIFICATION_TYPE_REACTION'         => '<strong>%1$s</strong> reacted to your post with %2$s',
	'NOTIFICATION_TYPE_REACTION_EXPLAIN' => 'Receive a notification when a user reacts to one of your posts.',

	// Notification Type: Email Digest (cron)
	'NOTIFICATION_TYPE_REACTION_EMAIL_DIGEST'         => 'Reactions email summary',
	'NOTIFICATION_TYPE_REACTION_EMAIL_DIGEST_EXPLAIN' => 'Receive a periodic email summary of new reactions on your posts.',

));
