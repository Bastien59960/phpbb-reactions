<?php
/**
 * File: language/en/common.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * Role:
 * This file contains the general English language strings for the user
 * interface (UI), error messages, tooltips, and extension options.
 * It is loaded on most pages where the extension is active.
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
	$lang = [];
}

$lang = array_merge($lang, [
	// =============================================================================
	// USER INTERFACE MESSAGES
	// =============================================================================
	'REACTION_ADD'              => 'Add reaction',
	'REACTION_REMOVE'           => 'Remove your reaction',
	'REACTION_MORE'             => 'More reactions',
	'REACTION_LOADING'          => 'Loading...',
	'REACTION_ERROR'            => 'Error while reacting',
	'REACTION_SUCCESS_ADD'      => 'Reaction added successfully',
	'REACTION_SUCCESS_REMOVE'   => 'Reaction removed successfully',

	// =============================================================================
	// ERROR AND VALIDATION MESSAGES
	// =============================================================================
	'REACTION_NOT_AUTHORIZED'   => 'You are not authorized to react',
	'REACTION_INVALID_POST'     => 'Invalid post',
	'REACTION_INVALID_EMOJI'    => 'Invalid emoji',
	'REACTION_ALREADY_ADDED'    => 'You have already reacted with this emoji',
	'REACTION_ALREADY_EXISTS'   => 'You have already reacted with this emoji', // Compatibility
	'REACTION_NOT_FOUND'        => 'Reaction not found',

	// =============================================================================
	// COUNTERS AND DISPLAY
	// =============================================================================
	'REACTION_COUNT_SINGULAR'   => '%d reaction',
	'REACTION_COUNT_PLURAL'     => '%d reactions',
	'REACTIONS_TITLE'           => 'Reactions',
	'NO_REACTIONS'              => 'No reactions yet',
	'REACTIONS_BY_USERS'        => 'Reactions from users',
	'REACTION_BY_USER'          => 'Reaction from %s',
	'REACTIONS_SEPARATOR'       => ', ',
	'REACTION_AND'              => ' and ',

	// =============================================================================
	// EMOJIS AND INTERFACE
	// =============================================================================
	'REACTIONS_COMMON_EMOJIS'   => 'Common Emojis',
	'REACTIONS_LOGIN_REQUIRED'  => 'You must be logged in to react to posts',
	'REACTIONS_JSON_ERROR'      => 'Error loading emojis',
	'REACTIONS_FALLBACK_INFO'   => 'JSON file not accessible. Only common emojis are available.',

	// =============================================================================
	// TOOLTIPS AND CONTEXTUAL HELP
	// =============================================================================
	'REACTIONS_ADD_TOOLTIP'     => 'Add a reaction',
	'REACTIONS_MORE_TOOLTIP'    => 'More emojis',
	'REACTIONS_COUNT_TOOLTIP'   => '%d reaction(s)',

	// =============================================================================
	// TECHNICAL AND DEBUG MESSAGES
	// =============================================================================
	'REACTIONS_DEBUG_ENABLED'   => 'Reactions debug mode enabled',
	'REACTIONS_CSRF_ERROR'      => 'Invalid CSRF token',
	'REACTIONS_SERVER_ERROR'    => 'Server error while reacting',

	// =============================================================================
	// LIMITS AND RESTRICTIONS
	// =============================================================================
	'REACTIONS_LIMIT_POST'      => 'Maximum %d reaction types per post',
	'REACTIONS_LIMIT_USER'      => 'Maximum %d reactions per user per post',
	'REACTION_LIMIT_POST'       => 'Reaction type limit for this post reached',
	'REACTION_LIMIT_USER'       => 'Reaction limit per user reached',
	'REACTIONS_LIMIT_REACHED'   => 'Reaction limit reached',
]);