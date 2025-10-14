<?php
/**
 * ============================================================================
 * Fichier : language/fr/notification/notification.type.reaction_email_digest.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description :
 * Ce fichier définit toutes les chaînes de langue françaises utilisées pour le
 * type de notification « résumé e-mail des réactions » dans l’extension Reactions
 * pour phpBB.
 *
 * 🔍 Rôle :
 *   - Fournir les textes traduits pour les notifications périodiques par e-mail
 *     regroupant plusieurs réactions reçues par l’utilisateur.
 *   - Être utilisé dans :
 *       → Les préférences de notification du Panneau de Contrôle Utilisateur (UCP)
 *       → Les notifications par e-mail groupées envoyées automatiquement
 *
 * ⚙️ Notes techniques :
 *   - Ce fichier est chargé via la méthode get_language_file() de la classe :
 *       → /ext/bastien59960/reactions/notification/type/reaction_email_digest.php
 *   - Il complète le fichier :
 *       → notification.type.reaction.php
 *   - Ce fichier doit être synchronisé avec la version anglaise
 *     pour garantir la cohérence des textes et des clés.
 *
 * 📅 Dernière mise à jour : octobre 2025
 * 👨‍💻 Auteur : Bastien59960
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
//   - Le titre du type de notification dans l’UCP
//   - La description affichée sous les préférences utilisateur
//   - Les notifications groupées envoyées par e-mail (digest)
// ============================================================================

$lang = array_merge($lang, array(

	// ----------------------------------------------------------------------------
	// ✉️ Texte général du type de notification
	// ----------------------------------------------------------------------------
	'NOTIFICATION_TYPE_REACTION_EMAIL_DIGEST' => 'Résumé périodique par e-mail des réactions',

	// ----------------------------------------------------------------------------
	// 🧭 Titre affiché dans les préférences de notification (UCP)
	// ----------------------------------------------------------------------------
	'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'Résumé e-mail des réactions',
));
