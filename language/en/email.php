<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /language/en/email.php
 * Rôle : Contient les chaînes de langue anglaises pour les e-mails envoyés par l'extension.
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

//
// Email language keys
//
$lang = array_merge($lang, array(
	// Subject for the digest email
	'REACTIONS_DIGEST_SUBJECT'	=> 'New reactions to your posts',

	// Email body
	'REACTIONS_DIGEST_HELLO'	=> 'Hello %1$s,',
	'REACTIONS_DIGEST_INTRO'	=> 'Here is a summary of new reactions to your posts on "%1$s":',

	'REACTIONS_DIGEST_POST_TITLE'	=> 'Reactions on your post “%1$s”',

	// Signature and footer
	'REACTIONS_DIGEST_SIGNATURE'	=> "Thank you,\nThe %s Team", // The %s is replaced by the board name

	'REACTIONS_DIGEST_FOOTER'		=> 'You are receiving this email because you have chosen to receive reaction summaries.',
	'REACTIONS_DIGEST_UNSUBSCRIBE'	=> 'To manage your notification preferences, please visit your User Control Panel.',
));

?>