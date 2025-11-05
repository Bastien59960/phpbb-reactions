<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960)
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /language/fr/email.php
 * Rôle : Contient les chaînes de langue françaises pour les e-mails envoyés par l'extension.
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
// Clés de langue pour les e-mails
//
$lang = array_merge($lang, array(
	// Sujet de l'e-mail de résumé
	'REACTIONS_DIGEST_SUBJECT'	=> 'Nouvelles réactions à vos messages',

	// Corps de l'e-mail
	'REACTIONS_DIGEST_HELLO'	=> 'Bonjour %1$s,',
	'REACTIONS_DIGEST_INTRO'	=> 'Voici un résumé des nouvelles réactions à vos messages sur « %1$s » :',

	'REACTIONS_DIGEST_POST_TITLE'	=> 'Réactions sur votre message « %1$s »',

	// Signature et pied de page
	'REACTIONS_DIGEST_SIGNATURE'	=> "Cordialement,\nL'équipe de %s", // %s est remplacé par le nom du forum

	'REACTIONS_DIGEST_FOOTER'		=> 'Vous recevez cet e-mail car vous avez choisi de recevoir les résumés de réactions.',
	'REACTIONS_DIGEST_UNSUBSCRIBE'	=> 'Pour gérer vos préférences de notification, veuillez visiter votre Panneau de Contrôle de l’Utilisateur.',
));