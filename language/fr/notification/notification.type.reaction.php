<?php
/**
 * ============================================================================
 * Fichier : language/fr/notification/notification.type.reaction.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description :
 * Ce fichier définit toutes les chaînes de langue françaises utilisées pour le
 * type de notification « réaction » dans l’extension Reactions pour phpBB.
 *
 * 🔍 Rôle :
 *   - Afficher les messages de notification (cloche, email, résumé, etc.)
 *   - Définir les intitulés et descriptions dans le Panneau de Contrôle Utilisateur (UCP)
 *   - Être référencé par les classes de notification dans :
 *       → /ext/bastien59960/reactions/notification/type/reaction.php
 *       → /ext/bastien59960/reactions/notification/type/reaction_email_digest.php
 *
 * ⚙️ Notes techniques :
 *   - Le nom du fichier doit correspondre à celui retourné par get_language_file()
 *     dans la classe de notification (ici : « reactions »).
 *   - Ce fichier doit être synchronisé avec la version anglaise pour maintenir
 *     la cohérence entre les langues.
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
// 🧩 Chaînes de langue pour les notifications de réactions
// ============================================================================
//
// Ces chaînes sont utilisées par phpBB pour afficher :
//   - Les messages dans la cloche des notifications
//   - Les notifications par email (si activées)
//   - Les options dans le panneau de configuration utilisateur (UCP)
// ============================================================================

$lang = array_merge($lang, array(

	// ----------------------------------------------------------------------------
	// 🔔 Texte principal de la notification (affiché dans la cloche et les emails)
	// ----------------------------------------------------------------------------
	// Exemple d’affichage : "Alice a réagi à votre message avec 👍"
	'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => '%s a réagi à votre message avec %s',

	// ----------------------------------------------------------------------------
	// 🧭 Groupe dans les préférences de notification (UCP)
	// ----------------------------------------------------------------------------
	'NOTIFICATION_GROUP_REACTIONS' => 'Notifications de réactions',

	// ----------------------------------------------------------------------------
	// ⚙️ Titre et description du type de notification dans l’UCP
	// ----------------------------------------------------------------------------
	'NOTIFICATION_NOTIFICATION.TYPE.REACTION_TITLE' => 'Réactions à vos messages',
	'NOTIFICATION_NOTIFICATION.TYPE.REACTION_DESC'  => 'Recevoir une notification lorsqu’un utilisateur réagit à vos messages.',

));
