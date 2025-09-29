<?php
/**
*
* acp_common [English]
*
* @copyright (c) 2025 Bastien59960
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// however where there are multiple placeholders in one string, please use the numbered format.
//

$lang = array_merge($lang, array(
    // ACP Main Title
    'ACP_REACTIONS_TITLE'                   => 'Post Reactions',

    // ACP Settings page
    'ACP_REACTIONS_SETTINGS'                => 'Reactions Settings',
    'ACP_REACTIONS_SETTINGS_EXPLAIN'        => 'Here you can manage the settings for the Post Reactions extension.',

    // Settings fields
    'REACTIONS_SPAM_TIME'                   => 'Notification anti-spam window',
    'REACTIONS_SPAM_TIME_EXPLAIN'           => 'The time in minutes to wait before sending a grouped notification to the post author. Set to 0 to disable notifications.',
    'REACTIONS_MAX_PER_POST'                => 'Maximum reaction types per post',
    'REACTIONS_MAX_PER_POST_EXPLAIN'        => 'The maximum number of unique reaction types a single post can receive.',
    'REACTIONS_MAX_PER_USER'                => 'Maximum reactions per user per post',
    'REACTIONS_MAX_PER_USER_EXPLAIN'        => 'The maximum number of reactions a single user can add to a single post.',

    // General terms
    'MINUTES'                               => 'Minutes',
));
