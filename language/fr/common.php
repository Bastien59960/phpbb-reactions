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
    'REACTION_ADD'        => 'Ajouter une réaction',
    'REACTION_REMOVE'     => 'Retirer votre réaction', 
    'REACTION_MORE'       => '+',
    'POST_REACTIONS'      => 'Réactions aux posts',
));
