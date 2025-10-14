<?php
/**
 * ============================================================================
 * Fichier : language/fr/notification/notification.type.reaction.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * ðŸ“˜ Description :
 * Ce fichier dÃ©finit toutes les chaÃ®nes de langue franÃ§aises utilisÃ©es pour le
 * type de notification Â« rÃ©action Â» dans lâ€™extension Reactions pour phpBB.
 *
 * ðŸ” RÃ´le :
 *   - Afficher les messages de notification (cloche, email, rÃ©sumÃ©, etc.)
 *   - DÃ©finir les intitulÃ©s et descriptions dans le Panneau de ContrÃ´le Utilisateur (UCP)
 *   - ÃŠtre rÃ©fÃ©rencÃ© par les classes de notification dans :
 *       â†’ /ext/bastien59960/reactions/notification/type/reaction.php
 *       â†’ /ext/bastien59960/reactions/notification/type/reaction_email_digest.php
 *
 * âš™ï¸ Notes techniques :
 *   - Le nom du fichier doit correspondre Ã  celui retournÃ© par get_language_file()
 *     dans la classe de notification (ici : Â« reactions Â»).
 *   - Ce fichier doit Ãªtre synchronisÃ© avec la version anglaise pour maintenir
 *     la cohÃ©rence entre les langues.
 *
 * ðŸ“… DerniÃ¨re mise Ã  jour : octobre 2025
 * ðŸ‘¨â€ðŸ’» Auteur : Bastien59960
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
	'NOTIFICATION_GROUP_REACTIONS' => 'RÃ©actions',
	'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION' => 'RÃ©actions Ã  mes messages',
	// --- Notification instantanÃ©e (cloche & UCP) ---
	'NOTIFICATION_TYPE_REACTION' => '<strong>%1$s</strong> a rÃ©agi Ã  votre message avec %2$s',
	'NOTIFICATION_TYPE_REACTION_TITLE'	=> 'Quelquâ€™un a rÃ©agi Ã  lâ€™un de vos messages',
	'NOTIFICATION_TYPE_REACTION_DESC'	=> 'Recevoir une notification lorsquâ€™un utilisateur rÃ©agit Ã  lâ€™un de vos messages.',
));
