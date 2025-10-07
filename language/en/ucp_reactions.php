<?php
/**
 * File: language/en/ucp_reactions.php — bastien59960/reactions/language/en/ucp_reactions.php
 *
 * English language file for the UCP (User Control Panel) preferences of the Reactions extension.
 *
 * This file contains all the strings used in the user preferences panel for reactions (notifications, email digest, etc.) in English.
 *
 * Key points:
 *   - Provides all translatable strings for user preferences related to reactions
 *   - Used by phpBB to display the UCP preferences page for the extension
 *
 * This file must be kept in sync with the French version for consistency.
 *
 * @copyright (c) 2025 Bastien59960
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
    'UCP_REACTIONS_TITLE' => 'Reactions preferences',
    'UCP_REACTIONS_NOTIFY' => 'Enable in-forum notifications (bell)',
    'UCP_REACTIONS_CRON_EMAIL' => 'Enable periodic email digest',
    'UCP_REACTIONS_SAVED' => 'Your reactions preferences have been saved.',
));
