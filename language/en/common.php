<?php
/**
 * Reactions Extension for phpBB 3.3
 * English language file - Corrected version
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

// Merge the existing language array
$lang = array_merge($lang, array(
    // Basic messages
    'REACTION_ADD'              => 'Add a reaction',
    'REACTION_REMOVE'           => 'Remove your reaction',
    'REACTION_MORE'             => 'More reactions',
    'REACTION_LOADING'          => 'Loading...',
    'REACTION_ERROR'            => 'Error while reacting',
    'REACTION_SUCCESS_ADD'      => 'Reaction added successfully',
    'REACTION_SUCCESS_REMOVE'   => 'Reaction removed successfully',
    
    // Error messages - CORRECTIONS according to code changes
    'REACTION_NOT_AUTHORIZED'   => 'You are not authorized to react',
    'REACTION_INVALID_POST'     => 'Invalid post',
    'REACTION_INVALID_EMOJI'    => 'Invalid emoji',
    'REACTION_ALREADY_ADDED'    => 'You have already reacted with this emoji', // CORRECTION: consistent with ajax.php
    'REACTION_ALREADY_EXISTS'   => 'You have already reacted with this emoji', // Keep both for compatibility
    'REACTION_NOT_FOUND'        => 'Reaction not found',
    
    // Counters and display
    'REACTION_COUNT_SINGULAR'   => '%d reaction',
    'REACTION_COUNT_PLURAL'     => '%d reactions',
    'REACTIONS_TITLE'           => 'Reactions',
    'NO_REACTIONS'              => 'No reactions yet',
    'REACTIONS_BY_USERS'        => 'User reactions',
    'REACTION_BY_USER'          => 'Reaction by %s',
    'REACTIONS_SEPARATOR'       => ', ',
    'REACTION_AND'              => ' and ',
    
    // NEW according to specifications and corrections
    'REACTIONS_COMMON_EMOJIS'   => 'Common emojis', // Replaces "popular"
    'REACTIONS_LOGIN_REQUIRED'  => 'You must be logged in to react to posts',
    'REACTIONS_JSON_ERROR'      => 'Error loading emojis',
    'REACTIONS_FALLBACK_INFO'   => 'JSON file not accessible. Only common emojis are available.',
    
    // Tooltips and help
    'REACTIONS_ADD_TOOLTIP'     => 'Add a reaction',
    'REACTIONS_MORE_TOOLTIP'    => 'More emojis',
    'REACTIONS_COUNT_TOOLTIP'   => '%d reaction(s)',
    
    // Technical messages for debugging (optional)
    'REACTIONS_DEBUG_ENABLED'   => 'Reactions debug mode enabled',
    'REACTIONS_CSRF_ERROR'      => 'Invalid CSRF token',
    'REACTIONS_SERVER_ERROR'    => 'Server error during reaction',
    
    // Limits according to specifications
    'REACTIONS_LIMIT_POST'      => 'Maximum %d reaction types per post',
    'REACTIONS_LIMIT_USER'      => 'Maximum %d reactions per user per post',
    'REACTIONS_LIMIT_REACHED'   => 'Reaction limit reached',
    'REACTION_LIMIT_POST'       => 'Post reaction type limit reached',
    'REACTION_LIMIT_USER'       => 'User reaction limit reached',

    'REACTIONS_NOTIFICATION_TITLE'      => '%1$s a réagi à votre message.',
'REACTIONS_NOTIFICATION_TITLE_PLURAL' => '%1$s et %2$d autres personnes ont réagi à votre message.',
'REACTIONS_NOTIFICATION_AND_OTHERS' => '%1$s et %2$d autre(s)',
'REACTIONS_NOTIFICATION_EMAIL_SUBJECT' => 'Nouvelles réactions à votre message "%2$s"',
));
