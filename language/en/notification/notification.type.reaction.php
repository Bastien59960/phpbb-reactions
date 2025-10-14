<?php
/**
 * ============================================================================
 * File: language/en/notification/notification.type.reaction.php
 * Extension: bastien59960/reactions
 * ============================================================================
 *
 * ðŸ“˜ Description:
 * This file defines all the English language strings used for the "reaction"
 * notification type in the Reactions extension for phpBB.
 *
 * ðŸ” Role:
 *   - Display notification messages (bell, email, digest, etc.)
 *   - Define labels and descriptions in the User Control Panel (UCP)
 *   - Be referenced by the notification classes in:
 *       â†’ /ext/bastien59960/reactions/notification/type/reaction.php
 *
 * âš™ï¸ Technical Notes:
 *   - The filename must correspond to the one returned by get_language_file()
 *     in the notification class (here: "reactions").
 *   - This file must be synchronized with the French version to maintain
 *     consistency between languages.
 *
 * ðŸ“… Last updated: October 2025
 * ðŸ‘¨â€ðŸ’» Author: Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
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
