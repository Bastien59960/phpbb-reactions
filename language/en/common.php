<?php
/**
 * File: language/en/common.php — bastien59960/reactions
 * @author Bastien (bastien59960)
 * @github https://github.com/bastien59960/reactions
 *
 * Role:
 * This file contains the general English language strings for the user
 * interface (UI), error messages, tooltips, and extension options.
 * It is loaded on most pages where the extension is active.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang ?? [], [
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
    'REACTIONS_BUTTON_TEXT'     => 'React',
    'REACTIONS_COUNT_TITLE'     => '%d reaction',
    'REACTIONS_COUNT_TITLE_PLURAL' => '%d reactions',
    
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
    'REACTIONS_LIMIT_USER'      => 'Maximum %d reaction types per user per post',
    'REACTION_LIMIT_POST'       => 'Reaction type limit for this post reached',
    'REACTION_LIMIT_USER'       => 'Reaction limit per user reached',
    'REACTIONS_LIMIT_REACHED'   => 'Reaction limit reached',

    'NO_SUBJECT'                    => '(No subject)',

    // =============================================================================
    // CRON TASKS (ACP & CLI)
    // =============================================================================
    // Keys for the command line (CLI) - CRUCIAL for `cron:list`
    // The name is `TASK_` + the return of get_name() with dots replaced by underscores.
    'TASK_BASTIEN59960_REACTIONS_NOTIFICATION'   => 'Reactions: Send email digests',
    'TASK_BASTIEN59960_REACTIONS_TEST'           => 'Reactions: Log test task',

    // Keys for the ACP display
    'BASTIEN59960_REACTIONS_TEST'              => 'Reactions: System test',
    'BASTIEN59960_REACTIONS_TEST_EXPLAIN'  => 'Periodic test to verify that the Reactions extension\'s cron system is working correctly.',
    'BASTIEN59960_REACTIONS_NOTIFICATION'          => 'Reactions: Send email digests',
    'BASTIEN59960_REACTIONS_NOTIFICATION_EXPLAIN' => 'Groups new reactions and sends periodic email summaries to users.',
    'LOG_REACTIONS_CRON_TEST_RUN'                   => '<strong>Reactions test cron run</strong><br>» The test task for the Reactions extension has run successfully.',

    // =============================================================================
    // NOTIFICATION KEYS (from reactions.php)
    // =============================================================================
    'NOTIFICATION_GROUP_REACTIONS' => 'Reactions',
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> reacted to your post with %2$s.',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Instant reaction to a post',
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Receive an instant notification in the forum bell when a user reacts to one of your posts.',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Periodic reaction email summary',
    'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Receive a periodic email summary of new reactions on your posts.',
]);