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

    // --- Backwards compatibility aliases ---
    'ACP_REACTIONS_MAX_PER_POST_EXPLAIN' => 'Maximum number of different reaction types allowed on a single post.',
    'ACP_REACTIONS_MAX_PER_USER_EXPLAIN' => 'Maximum number of reactions a user can add to a single post.',
    'ACP_REACTIONS_SPAM_TIME_EXPLAIN'    => 'Minimum delay (in minutes) between sending two e-mail summary digests to the same user.',
]);
