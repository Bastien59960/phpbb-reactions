<?php
/**
 * Reactions Extension for phpBB 3.3
 * Fichier de langue français pour les notifications de réactions
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
// Notifications de réactions
// ============================================================================

$lang = array_merge($lang, array(
	// Texte affiché dans la cloche ou dans la page de notifications
	'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => '%s a réagi à votre message avec %s',

	// Titre du groupe dans le panneau de configuration utilisateur (UCP)
	'NOTIFICATION_GROUP_REACTIONS' => 'Notifications de réactions',

	// Titre et description du type de notification dans l’UCP
	'NOTIFICATION_NOTIFICATION.TYPE.REACTION_TITLE' => 'Réactions à vos messages',
	'NOTIFICATION_NOTIFICATION.TYPE.REACTION_DESC'  => 'Recevoir une notification lorsqu’un utilisateur réagit à vos messages.',
));
