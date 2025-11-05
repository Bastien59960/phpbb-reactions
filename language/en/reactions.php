<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /language/en/reactions.php
 * Rôle : Contient les chaînes de langue anglaises pour les notifications
 * (instantanées et par e-mail) de l'extension Reactions.
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // --- Notification Group (UCP) ---
    'NOTIFICATION_GROUP_REACTIONS' => 'Reactions',

    // --- Instant Notification (bell & UCP) ---
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> reacted to your post with %2$s.',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Someone reacted to one of your posts',
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Receive a notification when a user reacts to one of your posts.',

    // --- Email Digest (UCP) ---
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Reactions e-mail summary',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Receive a periodic e-mail summary of new reactions on your posts.',
]);