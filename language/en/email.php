<?php
/**
 * Fichier : email.php
 * Chemin : language/en/email.php
 *
 * Rôle :
 * English language strings for email templates.
 *
 * @author  Bastien (bastien59960)
 * @github  https://github.com/bastien59960/reactions
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

$lang = array_merge($lang, array(
	'REACTIONS_DIGEST_GREETING'      => 'Hello %s,',
	'REACTIONS_DIGEST_POST_SUBJECT'  => 'Subject of your post',
	'REACTIONS_DIGEST_POST_LINK'     => 'Link to your post',
	'REACTIONS_DIGEST_BY'            => 'by',
	'REACTIONS_DIGEST_ON'            => 'on',
	'REACTIONS_DIGEST_PROFILE'       => 'View profile',
	'REACTIONS_DIGEST_SUBJECT'       => 'Summary of reactions to your posts',
	'REACTIONS_DIGEST_INTRO'         => 'Here is a summary of the new reactions your posts have received recently.',
	'REACTIONS_DIGEST_SIGNATURE'     => 'Thank you for your participation on %s.', // %s will be replaced by the site name
	'REACTIONS_PREFERENCES_HINT'     => 'You can manage your notification preferences in your User Control Panel.',
));