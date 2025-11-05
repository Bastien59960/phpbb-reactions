<?php
/**
 * Fichier : language/en/email.php — bastien59960/reactions
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
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
	'REACTIONS_DIGEST_SUBJECT'      => 'New reactions to your posts',
	'REACTIONS_DIGEST_GREETING'     => 'Hello %s,',
	'REACTIONS_DIGEST_INTRO'        => 'Here is a summary of the new reactions your posts have recently received.',
	'REACTIONS_DIGEST_SIGNATURE'    => 'Thank you for your participation on %s.',
	'REACTIONS_DIGEST_POST_SUBJECT' => 'Subject',
	'REACTIONS_DIGEST_POST_LINK'    => 'Link',
	'REACTIONS_DIGEST_REACTIONS'    => 'Reactions received:',
	'REACTIONS_DIGEST_BY'           => 'by',
	'REACTIONS_DIGEST_ON'           => 'on',
	'REACTIONS_DIGEST_PROFILE'      => 'Profile',
	'REACTIONS_PREFERENCES_HINT'    => 'Tip: You can change or disable these notifications from your User Control Panel > Reactions Preferences.',
));