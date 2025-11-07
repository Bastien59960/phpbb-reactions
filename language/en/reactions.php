<?php
/**
 * ============================================================================
 * File: language/en/reactions.php
 * Extension: bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description:
 * This file centralizes all English language strings for the
 * Reactions extension notifications.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // --- Notification Group (UCP) ---
    'NOTIFICATION_GROUP_REACTIONS' => 'Reactions',

    // --- Instant Notification (bell & UCP) ---
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> reacted to your post with %2$s.',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Someone reacted to your post',
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Receive an instant notification in the forum bell when a user reacts to one of your posts.',

    // --- Email Summary (UCP) ---
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Reaction Email Summary',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Receive a periodic email summary of new reactions on your posts.',
]);