<?php
/**
 * Reactions Extension for phpBB 3.3
 * Fichier de langue français pour les notifications
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
	// Exemple : "Alice a réagi à votre message avec ❤️"
	'NOTIFICATION_TYPE_REACTION'   => '%s a réagi à votre message avec %s',

	// Titre du groupe dans le panneau de configuration utilisateur (UCP)
	'NOTIFICATION_GROUP_REACTIONS' => 'Notifications de réactions',

	// Titre et description du type de notification dans l’UCP
	'NOTIFICATION_REACTION_TITLE'  => 'Réactions à vos messages',
	'NOTIFICATION_REACTION_DESC'   => 'Recevoir une notification lorsqu’un utilisateur réagit à vos messages.',
));
