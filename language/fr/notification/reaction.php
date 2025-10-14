<?php
/**
 * Fichier : reaction.php
 * Chemin : bastien59960/reactions/language/fr/notification/reaction.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/fr/notification/reaction.php
 *
 * RÃ´le :
 * Ce fichier dÃ©finit les chaÃ®nes de langue franÃ§aises pour les notifications
 * de l'extension Reactions. Il est utilisÃ© pour afficher les messages dans la
 * cloche de notification et dans les prÃ©fÃ©rences de l'utilisateur (UCP).
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
	'NOTIFICATION_GROUP_REACTIONS'       => 'Notifications de rÃ©actions',
	// Type de notification : RÃ©action instantanÃ©e (cloche)
	'NOTIFICATION_TYPE_REACTION'         => '<strong>%1$s</strong> a rÃ©agi Ã  votre message avec %2$s',
));
