<?php
/**
 * Fichier : notification.type.reaction.php
 * Chemin : bastien59960/reactions/language/en/notification/notification.type.reaction.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Définit les chaînes de langue anglaises pour le type de notification "réaction".
 * Ces textes sont utilisés pour les notifications instantanées (cloche) et dans
 * les préférences utilisateur (UCP).
 *
 * Ce fichier a été fusionné avec d'autres pour consolider les traductions.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // --- Notification group (UCP) ---
    'NOTIFICATION_GROUP_REACTIONS' => 'Reactions',
    'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => 'Reactions to my posts',

    // --- Instant notification (bell & UCP) ---
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> reacted to your post with %2$s',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Someone reacted to one of your posts',
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Receive a notification when a user reacts to one of your posts.',
]);
