<?php
/**
 * ============================================================================
 * File : language/en/notification/notification.type.reaction_email_digest.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description:
 * This file defines all English language strings used for the
 * “reaction email digest” notification type in the Reactions extension for phpBB.
 *
 * 🔍 Purpose:
 *   - Provides translatable strings for periodic email digests
 *     summarizing the reactions received by the user.
 *   - Used in:
 *       → The User Control Panel (UCP) notification preferences
 *       → The grouped email notifications automatically sent by phpBB
 *
 * ⚙️ Technical notes:
 *   - This file is loaded by the method get_language_file() in:
 *       → /ext/bastien59960/reactions/notification/type/reaction_email_digest.php
 *   - Complements the standard reaction notification language file:
 *       → notification.type.reaction.php
 *   - Must remain synchronized with the French version for translation consistency.
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
// 📬 Language strings for “reaction email digest” notifications
// ============================================================================
//
// These strings are used by phpBB to display:
//   - The title of the notification type in the UCP
//   - The description under the notification preference
//   - The grouped (digest) email notifications
// ============================================================================

$lang = array_merge($lang, array(

	// ----------------------------------------------------------------------------
	// ✉️ General notification type label
	// ----------------------------------------------------------------------------
	// Example: "Reaction email digest" (shown in UCP preferences)
	'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION_EMAIL_DIGEST' => 'Reaction email digest',

	// ----------------------------------------------------------------------------
	// 🧭 Title shown in User Control Panel (UCP)
	// ----------------------------------------------------------------------------
	'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Reaction email digest',

	// ----------------------------------------------------------------------------
	// ⚙️ Description shown below the title in the UCP
	// ----------------------------------------------------------------------------
	'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Receive periodic email summaries of reactions to your posts.',

));
