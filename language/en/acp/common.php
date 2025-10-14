<?php
/**
 * ============================================================================
 * File: language/en/acp/common.php
 * Extension: bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description:
 * This file contains the English language strings for the Administration
 * Control Panel (ACP) of the Reactions extension.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
    // --- Module titles ---
    'ACP_REACTIONS_TITLE'    => 'Post Reactions',
    'ACP_REACTIONS_SETTINGS' => 'Settings',
    'ACP_REACTIONS_IMPORT'   => 'Import reactions',
    'ACP_REACTIONS_SETTINGS_EXPLAIN' => 'Configure the behaviour and limits applied to post reactions.',

    // --- Settings descriptions ---
    'REACTIONS_SPAM_TIME'             => 'E-mail digest cooldown',
    'REACTIONS_SPAM_TIME_EXPLAIN'     => 'Minimum delay (in minutes) between sending two e-mail summary digests to the same user.',
    'REACTIONS_MAX_PER_POST'          => 'Maximum reaction types per post',
    'REACTIONS_MAX_PER_POST_EXPLAIN'  => 'Maximum number of different reaction types allowed on a single post.',
    'REACTIONS_MAX_PER_USER'          => 'Maximum reactions per user',
    'REACTIONS_MAX_PER_USER_EXPLAIN'  => 'Maximum number of reactions a single user can add to a post.',

    'REACTIONS_DISPLAY_SETTINGS'    => 'Display & picker settings',
    'REACTIONS_POST_EMOJI_SIZE'      => 'Post emoji size',
    'REACTIONS_POST_EMOJI_SIZE_EXPLAIN' => 'Defines the size (in pixels) of reactions shown under each post.',
    'REACTIONS_PICKER_EMOJI_SIZE'    => 'Picker emoji size',
    'REACTIONS_PICKER_EMOJI_SIZE_EXPLAIN' => 'Default size (in pixels) of each emoji inside the picker grid.',
    'REACTIONS_PICKER_WIDTH'         => 'Picker width',
    'REACTIONS_PICKER_WIDTH_EXPLAIN' => 'Width (in pixels) of the emoji picker panel.',
    'REACTIONS_PICKER_HEIGHT'        => 'Picker height',
    'REACTIONS_PICKER_HEIGHT_EXPLAIN'=> 'Height (in pixels) of the emoji picker panel.',
    'REACTIONS_PICKER_SHOW_CATEGORIES' => 'Show emoji categories',
    'REACTIONS_PICKER_SHOW_CATEGORIES_EXPLAIN' => 'Uncheck to hide the category tabs and only display the quick reactions grid.',
    'REACTIONS_PICKER_SHOW_SEARCH'   => 'Show search bar',
    'REACTIONS_PICKER_SHOW_SEARCH_EXPLAIN' => 'Uncheck to remove the search field from the picker.',
    'REACTIONS_PICKER_USE_JSON'      => 'Load extended emoji set',
    'REACTIONS_PICKER_USE_JSON_EXPLAIN' => 'Uncheck to disable loading of the external JSON file and only expose the 10 quick reactions.',
    'REACTIONS_SYNC_INTERVAL'        => 'Live refresh interval',
    'REACTIONS_SYNC_INTERVAL_EXPLAIN'=> 'Time between two automatic refreshes (in milliseconds) of the reactions list.',

    // --- Backwards compatibility aliases ---
    'ACP_REACTIONS_MAX_PER_POST_EXPLAIN' => 'Maximum number of different reaction types allowed on a single post.',
    'ACP_REACTIONS_MAX_PER_USER_EXPLAIN' => 'Maximum number of reactions a user can add to a single post.',
    'ACP_REACTIONS_SPAM_TIME_EXPLAIN'    => 'Minimum delay (in minutes) between sending two e-mail summary digests to the same user.',
]);
