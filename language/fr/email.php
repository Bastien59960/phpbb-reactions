<?php
/**
 * Fichier : email.php
 * Chemin : language/fr/email.php
 *
 * Rôle :
 * Ce fichier contient les chaînes de langue françaises spécifiquement
 * utilisées dans les templates d'e-mail.
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
	'REACTIONS_DIGEST_GREETING'      => 'Bonjour %s,',
	'REACTIONS_DIGEST_POST_SUBJECT'  => 'Sujet de votre message',
	'REACTIONS_DIGEST_POST_LINK'     => 'Lien vers votre message',
	'REACTIONS_DIGEST_BY'            => 'par',
	'REACTIONS_DIGEST_ON'            => 'le',
	'REACTIONS_DIGEST_PROFILE'       => 'Voir le profil',
));