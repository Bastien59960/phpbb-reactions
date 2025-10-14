<?php
/**
 * Fichier : common.php
 * Chemin : bastien59960/reactions/language/en/common.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/en/common.php
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue anglaises générales pour l'interface
 * utilisateur (UI), les messages d'erreur, les tooltips, et les options de
 * configuration dans le panneau d'administration (ACP).
 *
 * Il est chargé sur la plupart des pages où l'extension est active.
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
    // ADMINISTRATION MESSAGES (ACP)
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

    // =============================================================================
    // USER PREFERENCES (UCP)
    // =============================================================================
    'UCP_REACTIONS_TITLE'               => 'Reactions preferences',
    'UCP_REACTIONS_SAVED'               => 'Your reaction notification preferences have been saved.',
    'UCP_REACTIONS_NOTIFY'              => 'Notify me about new reactions (notification)',
    'UCP_REACTIONS_NOTIFY_EXPLAIN'      => 'Receive an instant notification in the forum bell.',
    'UCP_REACTIONS_EMAIL'               => 'Notify me about new reactions (e-mail)',
    'UCP_REACTIONS_EMAIL_EXPLAIN'       => 'Receive a periodic email summary of new reactions.',
));
