<?php
/**
 * Fichier : notification.type.reaction.php
 * Chemin : bastien59960/reactions/language/fr/notification/notification.type.reaction.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Définit les chaînes de langue françaises pour le type de notification "réaction".
 * Ces textes sont utilisés pour les notifications instantanées (cloche) et dans
 * les préférences utilisateur (UCP).
 *
 * Ce fichier a été fusionné avec d'autres pour consolider les traductions.
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
    // --- Groupe de notifications (UCP) ---
    'NOTIFICATION_GROUP_REACTIONS' => 'Réactions',
    // --- Notification instantanée (cloche & UCP) ---
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> a réagi à votre message avec %2$s.',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Quelqu\'un a réagi à l\'un de vos messages', // Titre dans l'UCP
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Recevoir une notification lorsqu\'un utilisateur réagit à l\'un de vos messages.',
));
