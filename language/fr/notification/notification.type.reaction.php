<?php
/**
 * ============================================================================
 * Fichier : language/fr/notification/notification.type.reaction.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * 📘 Description :
 * Ce fichier définit toutes les chaînes de langue françaises utilisées pour le
 * type de notification « réaction » dans l'extension Reactions pour phpBB.
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

$lang = array_merge($lang, array(
    // --- Groupe de notifications (UCP) ---
    'NOTIFICATION_GROUP_REACTIONS' => 'Réactions',
    // --- Notification instantanée (cloche & UCP) ---
    'NOTIFICATION_TYPE_REACTION'       => '<strong>%1$s</strong> a réagi à votre message avec %2$s.',
    'NOTIFICATION_TYPE_REACTION_TITLE' => 'Quelqu\'un a réagi à l\'un de vos messages', // Titre dans l'UCP
    'NOTIFICATION_TYPE_REACTION_DESC'  => 'Recevoir une notification lorsqu\'un utilisateur réagit à l\'un de vos messages.',
));
