<?php
/**
 * Fichier : reaction.php
 * Chemin : bastien59960/reactions/language/fr/notification/reaction.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/fr/notification/reaction.php
 *
 * Rôle :
 * Ce fichier définit les chaînes de langue françaises pour les notifications
 * de l'extension Reactions. Il est utilisé pour afficher les messages dans la
 * cloche de notification et dans les préférences de l'utilisateur (UCP).
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
	// Groupe de notifications dans l'UCP
	'NOTIFICATION_GROUP_REACTIONS'       => 'Notifications de réactions',
	// Type de notification : Réaction instantanée (cloche)
	'NOTIFICATION_TYPE_REACTION'         => '<strong>%1$s</strong> a réagi à votre message avec %2$s',
));
