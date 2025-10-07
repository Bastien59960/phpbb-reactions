<?php
/**
 * English language file for the Reactions extension
 * 
 * This file contains all English language strings used by the Reactions extension.
 * It includes:
 * - User interface messages
 * - Error and success messages
 * - Notification texts
 * - Administration messages
 * - Tooltips and contextual help
 * 
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

// Security check
if (!defined('IN_PHPBB'))
{
    exit;
}

// Initialize language array
if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

// =============================================================================
// LANGUAGE STRINGS DEFINITION
// =============================================================================

// Merge the existing language array
$lang = array_merge($lang, array(
    // =============================================================================
    // ADMINISTRATION MESSAGES (ACP)
    // =============================================================================
    'ACP_REACTIONS_TITLE'       => 'Reactions settings',
    'ACP_REACTIONS_SETTINGS'    => 'Reactions configuration',
    'ACP_REACTIONS_ENABLED'     => 'Enable reactions',
    'ACP_REACTIONS_MAX_PER_POST' => 'Maximum number of reaction types per post',
    'ACP_REACTIONS_MAX_PER_USER' => 'Maximum number of reactions per user per post',
    'ACP_REACTIONS_EXPLAIN'     => 'Configure the settings for post reactions.',
    
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
    // NOTIFICATIONS
    // =============================================================================
    'REACTIONS_NOTIFICATION_TITLE'      => '%1$s reacted to your message',
    'REACTIONS_NOTIFICATION_TITLE_PLURAL' => '%1$s and %2$d others reacted to your message',
    'REACTIONS_NOTIFICATION_AND_OTHERS' => '%1$s and %2$d other(s)',
    'REACTIONS_NOTIFICATION_EMAIL_SUBJECT' => 'New reactions to your message "%2$s"',
    'REACTIONS_NOTIFICATION_TYPE' => 'Post reactions',
    'REACTIONS_NOTIFICATION_GROUP' => 'Reaction notifications',

    'NOTIFICATION_TYPE_REACTION' => '<strong>%1$s</strong> a réagi %2$s à votre message',
        'NOTIFICATION_TYPE_BASTIEN59960_REACTIONS_CRON_EMAIL' => 'Résumé par e-mail des réactions',
'REACTION_DIGEST_EMAIL_NOTIFICATION' => 'Recevoir un résumé périodique des nouvelles réactions par e-mail.',
    'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => 'Reactions to your posts',

));
