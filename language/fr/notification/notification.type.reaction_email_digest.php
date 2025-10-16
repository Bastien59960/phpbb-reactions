<?php
/**
 * Fichier : notification.type.reaction_email_digest.php
 * Chemin : bastien59960/reactions/language/fr/notification/notification.type.reaction_email_digest.php
 * Auteur : Bastien (bastien5s9960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Définit les chaînes de langue françaises pour le type de notification "résumé par e-mail".
 * Ces textes sont utilisés dans les préférences utilisateur (UCP) pour permettre
 * d'activer ou de désactiver les e-mails groupés.
 *
 * Ce fichier a été fusionné avec d'autres pour consolider les traductions.
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

// ============================================================================
// 📬 Chaînes de langue pour les notifications par e-mail de type "résumé"
// ============================================================================
//
// Ces chaînes sont utilisées pour :
//   - Le titre du type de notification dans l'UCP
//   - La description affichée sous les préférences utilisateur
//   - Les notifications groupées envoyées par e-mail (digest)
// ============================================================================

$lang = array_merge($lang, array(
	'NOTIFICATION_TYPE_BASTIEN59960/REACTIONS/NOTIFICATION/TYPE/REACTION_EMAIL_DIGEST' => 'Résumés e-mail des réactions',
	// --- Résumé par e-mail (UCP) ---
	'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Résumé e-mail des réactions',
	'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Recevoir périodiquement un résumé par e-mail des réactions reçues sur vos messages.',
));
