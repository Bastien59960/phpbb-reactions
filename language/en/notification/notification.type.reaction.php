<?php
/**
 * ============================================================================
 * File : language/en/notification/notification.type.reaction.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description:
 * This file defines all English language strings used for the
 * “reaction” notification type in the Reactions extension for phpBB.
 *
 * 🔍 Purpose:
 *   - Provides translatable strings for reaction notifications
 *     displayed in the bell, emails, and user preferences.
 *   - Used by phpBB to display messages and notification preferences.
 *   - Referenced by:
 *       → /ext/bastien59960/reactions/notification/type/reaction.php
 *       → /ext/bastien59960/reactions/notification/type/reaction_email_digest.php
 *
 * ⚙️ Technical notes:
 *   - The file name must match the one returned by get_language_file()
 *     in the corresponding notification class ("reactions").
 *   - Must remain synchronized with the French version for consistency.
 *
 * 📅 Last updated: October 2025
 * 👨‍💻 Author: Bastien59960
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
// 🧩 Reaction notification strings
// ============================================================================
//
// These strings are used by phpBB to display:
//   - Notification messages (bell, email, digest, etc.)
//   - Titles and descriptions in the User Control Panel (UCP)
// ============================================================================

$lang = array_merge($lang, array(

	// ----------------------------------------------------------------------------
	// 🔔 Main notification message (displayed in bell and email)
	// ----------------------------------------------------------------------------
	// Example: "Alice reacted to your post with 👍"
	'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => '%s reacted to your post with %s',

	// ----------------------------------------------------------------------------
	// 🧭 Notification group title (in the UCP)
	// ----------------------------------------------------------------------------
	'NOTIFICATION_GROUP_REACTIONS' => 'Reaction notifications',

	// ----------------------------------------------------------------------------
	// ⚙️ Title and description in the User Control Panel (UCP)
	// ----------------------------------------------------------------------------
	'NOTIFICATION_NOTIFICATION.TYPE.REACTION_TITLE' => 'Reactions to your posts',
	'NOTIFICATION_NOTIFICATION.TYPE.REACTION_DESC'  => 'Receive a notification when someone reacts to your posts.',

));
