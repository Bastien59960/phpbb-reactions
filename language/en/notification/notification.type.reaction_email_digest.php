<?php
/**
 * Fichier : notification.type.reaction_email_digest.php
 * Chemin : bastien59960/reactions/language/en/notification/notification.type.reaction_email_digest.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Définit les chaînes de langue anglaises pour le type de notification "résumé par e-mail".
 * Ces textes sont utilisés dans les préférences utilisateur (UCP) pour permettre
 * d'activer ou de désactiver les e-mails groupés.
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
    'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION_EMAIL_DIGEST' => 'Reaction e-mail summaries',
    // --- E-mail summary (UCP) ---
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Reactions e-mail summary',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Periodically receive an e-mail summary of reactions to your posts.',
]);
