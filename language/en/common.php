<?php
/**
 * Fichier : language/en/common.php â€” bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 *
 * RÃ´le :
 * Ce fichier contient les chaÃ®nes de langue anglaises gÃ©nÃ©rales pour l'interface
 * utilisateur (UI), les messages d'erreur, les tooltips, et les options de
 * l'extension. Il est chargÃ© sur la plupart des pages oÃ¹ l'extension est active.
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
    // =============================================================================
    // USER INTERFACE MESSAGES
    // =============================================================================
    'REACTION_ADD'              => 'Add a reaction',
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
    'REACTIONS_BY_USERS'        => 'User reactions',
    'REACTION_BY_USER'          => 'Reaction by %s',
    'REACTIONS_SEPARATOR'       => ', ',
    'REACTION_AND'              => ' and ',
    
    // =============================================================================
    // EMOJIS AND INTERFACE
    // =============================================================================
    'REACTIONS_COMMON_EMOJIS'   => 'Common emojis',
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
    'REACTIONS_SERVER_ERROR'    => 'Server error during reaction',
    
    // =============================================================================
    // LIMITS AND RESTRICTIONS
    // =============================================================================
    'REACTIONS_LIMIT_POST'      => 'Maximum %d reaction types per post',
    'REACTIONS_LIMIT_USER'      => 'Maximum %d reactions per user per post',
    'REACTIONS_LIMIT_REACHED'   => 'Reaction limit reached',
    'REACTION_LIMIT_POST'       => 'Post reaction type limit reached',
    'REACTION_LIMIT_USER'       => 'User reaction limit reached',
));
