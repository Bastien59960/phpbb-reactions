<?php
/**
 * Fichier : language/fr/notification/reaction.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue françaises spécifiquement utilisées
 * par le système de notification de phpBB pour le type 'reaction'.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

$lang = array_merge($lang ?? [], [
	'NOTIFICATION_TYPE_REACTION'	=> 'Quelqu\'un a réagi à votre message',
	'NOTIFICATION_GROUP_REACTIONS'	=> 'Notifications de réactions',
]);