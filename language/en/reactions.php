<?php
/**
 * ============================================================================
 * File: language/en/reactions.php
 * Extension: bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description:
 * This file centralizes all English language strings for the notifications
 * of the Reactions extension.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // --- Notification group in UCP ---
    'NOTIFICATION_GROUP_REACTIONS' => 'Reactions',

    // --- Instant notification (bell) ---
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> reacted to your post with %2$s',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Someone reacted to one of your posts',
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Receive a notification when a user reacts to one of your posts.',

    // --- E-mail notification (digest) ---
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Reactions e-mail summary',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Periodically receive an e-mail summary of reactions to your posts.',

    // --- UCP option labels for notification centre ---
    'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => 'Reactions to my posts',
    'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION_EMAIL_DIGEST' => 'Reaction e-mail summaries',

    // --- Digest helpers ---
    'REACTIONS_DIGEST_VIEW_POST' => 'View this post',
    'REACTIONS_DIGEST_SIGNATURE' => 'Thanks, the %s team',
]);
