<?php
/**
 * Fichier : reaction.php
 * Chemin : bastien59960/reactions/language/fr/notification/reaction.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier définit les chaînes de langue françaises pour les notifications
 * de l'extension Reactions. Il est utilisé pour afficher les messages dans
 * la cloche de notification et dans les préférences utilisateur (UCP).
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

$lang = array_merge($lang ?? [], array(
	// Groupe de notification dans l'UCP
	'NOTIFICATION_GROUP_REACTIONS'       => 'Réactions',

	// Type de notification : Réaction instantanée (cloche)
	'NOTIFICATION_TYPE_REACTION'         => '<strong>%1$s</strong> a réagi à votre message avec %2$s',
	'NOTIFICATION_TYPE_REACTION_TITLE'   => 'Quelqu\'un a réagi à l\'un de vos messages',
	'NOTIFICATION_TYPE_REACTION_DESC'    => 'Recevoir une notification lorsqu\'un utilisateur réagit à l\'un de vos messages.',
	
	// Type de notification : Résumé par e-mail (cron)
	'NOTIFICATION_TYPE_REACTION_EMAIL_DIGEST_TITLE' => 'Résumé des réactions par e-mail',
	'NOTIFICATION_TYPE_REACTION_EMAIL_DIGEST_DESC'  => 'Recevoir périodiquement un résumé par e-mail des réactions à vos messages.',
));