<?php
/**
 * Fichier : language/fr/notification/reaction.php
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
	'NOTIFICATION_TYPE_REACTION'		=> '%1$s a réagi à votre message avec %2$s',
	'NOTIFICATION_TYPE_REACTION_TITLE'	=> 'Réactions à vos messages',
	'NOTIFICATION_TYPE_REACTION_DESC'	=> 'Recevoir une notification lorsqu\'un utilisateur réagit à l\'un de vos messages.',
]);