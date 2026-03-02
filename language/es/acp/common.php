<?php
/**
 * File: language/en/acp/common.php
 * Extension: bastien59960/reactions
 *
 * Description:
 * English language strings for the Administration Control Panel (ACP)
 * of the Reactions extension.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // Module Titles
    'ACP_REACTIONS_TITLE'                => 'Reactions Extension',
    'ACP_REACTIONS_SETTINGS'             => 'Settings',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'     => 'Configure the behavior, limits, and appearance of reactions.',

    // Setting Descriptions
    'REACTIONS_SPAM_TIME'                => 'Delay between email digests',
    'REACTIONS_SPAM_TIME_EXPLAIN'        => 'Minimum delay (in minutes) between sending two email digests to the same user.',
    'REACTIONS_MAX_PER_POST'             => 'Maximum types per post',
    'REACTIONS_MAX_PER_POST_EXPLAIN'     => 'Maximum number of different reaction types allowed on a single post.',
    'REACTIONS_MAX_PER_USER'             => 'Maximum reactions per user',
    'REACTIONS_MAX_PER_USER_EXPLAIN'     => 'Maximum number of different reactions a user can add to a single post.',

    'REACTIONS_DISPLAY_SETTINGS'         => 'Display and Picker Settings',
    'REACTIONS_POST_EMOJI_SIZE'          => 'Emoji size under posts',
    'REACTIONS_POST_EMOJI_SIZE_EXPLAIN'  => 'Defines the size (in pixels) of reactions displayed under each post.',
    'REACTIONS_PICKER_EMOJI_SIZE'        => 'Picker icon size',
    'REACTIONS_PICKER_EMOJI_SIZE_EXPLAIN'=> 'Size (in pixels) of the emojis in the picker and the category tab icons.',
    'REACTIONS_PICKER_WIDTH'             => 'Picker width',
    'REACTIONS_PICKER_WIDTH_EXPLAIN'     => 'Width (in pixels) of the emoji picker.',
    'REACTIONS_PICKER_HEIGHT'            => 'Picker height',
    'REACTIONS_PICKER_HEIGHT_EXPLAIN'    => 'Height (in pixels) of the emoji picker.',
    'REACTIONS_PICKER_SHOW_CATEGORIES'   => 'Show categories',
    'REACTIONS_PICKER_SHOW_CATEGORIES_EXPLAIN' => 'Uncheck to hide the category tabs and only show the quick emojis.',
    'REACTIONS_PICKER_SHOW_SEARCH'       => 'Show search',
    'REACTIONS_PICKER_SHOW_SEARCH_EXPLAIN' => 'Uncheck to remove the search field from the picker.',
    'REACTIONS_PICKER_USE_JSON'          => 'Load full emoji set',
    'REACTIONS_PICKER_USE_JSON_EXPLAIN'  => 'Uncheck to not load the external JSON file and only show the 10 common emojis.',
    'REACTIONS_SYNC_INTERVAL'            => 'Refresh interval (in seconds)',
    'REACTIONS_SYNC_INTERVAL_EXPLAIN'    => 'Time (in seconds) between automatic reaction updates.',

    // --- Admin Log Messages ---
    'LOG_REACTIONS_IMPORT_START'         => '<strong>Attempting to import reactions</strong><br>• Searching for data from an old reactions extension.',
    'LOG_REACTIONS_IMPORT_EMPTY'         => '<strong>Reaction import skipped</strong><br>• Old tables were found but were empty.',
    'LOG_REACTIONS_IMPORT_SUCCESS'       => '<strong>Reaction import complete</strong><br>• %1$d reactions imported (%2$d skipped).<br>• %3$d users and %4$d posts affected.',
]);