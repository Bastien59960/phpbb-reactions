<?php
if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

$lang = array_merge($lang, array(
    'REACTION_ADD'        => 'Add a reaction',
    'REACTION_REMOVE'     => 'Remove your reaction',
    'REACTION_MORE'       => '+',
    'POST_REACTIONS'      => 'Post Reactions',
));
