<?php
/**
 * File: language/en/acp/common.php — bastien59960/reactions/language/en/acp/common.php
 *
 * English language file for the ACP (Admin Control Panel) part of the Reactions extension.
 *
 * This file contains all the admin panel strings used by the extension in English.
 *
 * Key points:
 *   - Provides all ACP-related translatable strings for the extension
 *   - Used by phpBB to display admin configuration pages for Reactions
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
    // ACP Main Title
    'ACP_REACTIONS_TITLE'                   => 'Reactions settings',
    'ACP_REACTIONS_SETTINGS'                => 'Reactions configuration',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Here you can manage the settings for the Post Reactions extension.',
    'ACP_REACTIONS_IMPORT'                  => 'Import old reactions',
    'ACP_REACTIONS_IMPORT_EXPLAIN'          => 'Here you can import old reactions from the old reactions table.',

    // ACP Settings page
    'ACP_REACTIONS_SETTINGS'                => 'Reactions configuration',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Here you can manage the settings for the Post Reactions extension.',

    // Settings fields
    'REACTIONS_SPAM_TIME'                   => 'Notification anti-spam window',
    'REACTIONS_SPAM_TIME_EXPLAIN'           => 'The time in minutes to wait before sending a grouped notification to the post author. Set to 0 to disable notifications.',
    'REACTIONS_MAX_PER_POST'                => 'Maximum reaction types per post',
    'REACTIONS_MAX_PER_POST_EXPLAIN'        => 'The maximum number of unique reaction types a single post can receive.',
    'REACTIONS_MAX_PER_USER'                => 'Maximum reactions per user per post',
    'REACTIONS_MAX_PER_USER_EXPLAIN'        => 'The maximum number of reactions a single user can add to a single post.',

    // General terms
    'MINUTES'                               => 'Minutes',
));
