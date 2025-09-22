<?php
/**
 * Reactions Extension for phpBB 3.3
 * English language file
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
    'REACTION_ADD'              => 'Add a reaction',
    'REACTION_REMOVE'           => 'Remove your reaction',
    'REACTION_MORE'             => 'More reactions',
    'REACTION_LOADING'          => 'Loading...',
    'REACTION_ERROR'            => 'Error while reacting',
    'REACTION_SUCCESS_ADD'      => 'Reaction added successfully',
    'REACTION_SUCCESS_REMOVE'   => 'Reaction removed successfully',
    'REACTION_NOT_AUTHORIZED'   => 'You are not authorized to react',
    'REACTION_INVALID_POST'     => 'Invalid post',
    'REACTION_INVALID_EMOJI'    => 'Invalid emoji',
    'REACTION_ALREADY_EXISTS'   => 'You have already reacted with this emoji',
    'REACTION_NOT_FOUND'        => 'Reaction not found',
    'REACTION_COUNT_SINGULAR'   => '%d reaction',
    'REACTION_COUNT_PLURAL'     => '%d reactions',
    'REACTIONS_TITLE'           => 'Reactions',
    'NO_REACTIONS'              => 'No reactions yet',
    'REACTIONS_BY_USERS'        => 'User reactions',
    'REACTION_BY_USER'          => 'Reaction by %s',
    'REACTIONS_SEPARATOR'       => ', ',
    'REACTION_AND'              => ' and ',
));
