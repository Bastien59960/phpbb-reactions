<?php
/**
 * Fichier : language/fr/notification/notification.type.reaction.php — bastien59960/reactions/language/fr/notification/notification.type.reaction.php
 *
 * Fichier de langue française pour le type de notification "réaction" de l'extension Reactions.
 *
 * Ce fichier contient toutes les chaînes liées aux notifications de type "réaction" en français.
 *
 * Points clés :
 *   - Fournit toutes les chaînes traduisibles pour les notifications de réactions (UCP, cloche, etc.)
 *   - Utilisé par phpBB pour afficher les messages et préférences de notification
 *
 * Ce fichier doit être synchronisé avec la version anglaise pour garantir la cohérence.
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
