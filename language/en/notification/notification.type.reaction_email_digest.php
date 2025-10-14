<?php
/**
 * ============================================================================
 * File: language/en/notification/notification.type.reaction_email_digest.php
 * Extension: bastien59960/reactions
 * ============================================================================
 *
 * ðŸ“˜ Description:
 * This file defines all the English language strings used for the "reaction
 * email digest" notification type in the Reactions extension for phpBB.
 *
 * ðŸ” Role:
 *   - Provide translated texts for periodic email notifications that group
 *     several reactions received by the user.
 *   - Be used in:
 *       â†’ The notification preferences of the User Control Panel (UCP)
 *       â†’ The grouped email notifications sent automatically
 *
 * âš™ï¸ Technical Notes:
 *   - This file is loaded via the get_language_file() method of the class:
 *       â†’ /ext/bastien59960/reactions/notification/type/reaction_email_digest.php
 *   - It complements the file:
 *       â†’ notification.type.reaction.php
 *   - This file must be synchronized with the French version to ensure
 *     consistency of texts and keys.
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
    'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION_EMAIL_DIGEST' => 'Reaction e-mail summaries',
    // --- E-mail summary (UCP) ---
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Reactions e-mail summary',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Periodically receive an e-mail summary of reactions to your posts.',
]);
