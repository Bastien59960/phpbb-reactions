<?php
/**
 * Fichier : common.php
 * Chemin : bastien59960/reactions/language/en/acp/common.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions/blob/main/language/en/acp/common.php
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue anglaises pour la partie administrative
 * (ACP) de l'extension. Il est utilisé pour afficher les titres, labels et
 * explications dans le panneau de configuration de l'extension.
 *
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
    'ACP_REACTIONS_TITLE'                   => 'Reactions settings',
    'ACP_REACTIONS_SETTINGS'                => 'Reactions configuration',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Here you can manage the settings for the Post Reactions extension.',
    'REACTIONS_SPAM_TIME'                   => 'Notification anti-spam window',
    'REACTIONS_SPAM_TIME_EXPLAIN'           => 'The time in minutes to wait before sending a grouped notification to the post author. Set to 0 to disable notifications.',
    'REACTIONS_MAX_PER_POST'                => 'Maximum reaction types per post',
    'REACTIONS_MAX_PER_POST_EXPLAIN'        => 'The maximum number of unique reaction types a single post can receive.',
    'REACTIONS_MAX_PER_USER'                => 'Maximum reactions per user per post',
    'REACTIONS_MAX_PER_USER_EXPLAIN'        => 'The maximum number of reactions a single user can add to a single post.',
    'MINUTES'                               => 'Minutes',
));
