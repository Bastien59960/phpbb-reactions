<?php
/**
 * ============================================================================
 * Fichier : language/fr/notification/notification.type.reaction_email_digest.php
 * Extension : bastien59960/reactions
 * ============================================================================
 *
 * ðŸ“˜ Description :
 * Ce fichier dÃ©finit toutes les chaÃ®nes de langue franÃ§aises utilisÃ©es pour le
 * type de notification Â« rÃ©sumÃ© e-mail des rÃ©actions Â» dans lâ€™extension Reactions
 * pour phpBB.
 *
 * ðŸ” RÃ´le :
 *   - Fournir les textes traduits pour les notifications pÃ©riodiques par e-mail
 *     regroupant plusieurs rÃ©actions reÃ§ues par lâ€™utilisateur.
 *   - ÃŠtre utilisÃ© dans :
 *       â†’ Les prÃ©fÃ©rences de notification du Panneau de ContrÃ´le Utilisateur (UCP)
 *       â†’ Les notifications par e-mail groupÃ©es envoyÃ©es automatiquement
 *
 * âš™ï¸ Notes techniques :
 *   - Ce fichier est chargÃ© via la mÃ©thode get_language_file() de la classe :
 *       â†’ /ext/bastien59960/reactions/notification/type/reaction_email_digest.php
 *   - Il complÃ¨te le fichier :
 *       â†’ notification.type.reaction.php
 *   - Ce fichier doit Ãªtre synchronisÃ© avec la version anglaise
 *     pour garantir la cohÃ©rence des textes et des clÃ©s.
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

// ============================================================================
// ðŸ“¬ ChaÃ®nes de langue pour les notifications par e-mail de type "rÃ©sumÃ©"
// ============================================================================
//
// Ces chaÃ®nes sont utilisÃ©es pour :
//   - Le titre du type de notification dans lâ€™UCP
//   - La description affichÃ©e sous les prÃ©fÃ©rences utilisateur
//   - Les notifications groupÃ©es envoyÃ©es par e-mail (digest)
// ============================================================================

$lang = array_merge($lang, array(
	// --- Groupe de notifications (UCP) ---
	'NOTIFICATION_GROUP_REACTIONS' => 'RÃ©actions',
	'NOTIFICATION_TYPE_NOTIFICATION.TYPE.REACTION_EMAIL_DIGEST' => 'RÃ©sumÃ©s e-mail des rÃ©actions',
	// --- RÃ©sumÃ© par e-mail (UCP) ---
	'NOTIFICATION_REACTION_EMAIL_DIGEST_TITLE' => 'RÃ©sumÃ© e-mail des rÃ©actions',
	'NOTIFICATION_REACTION_EMAIL_DIGEST_DESC'  => 'Recevoir pÃ©riodiquement un rÃ©sumÃ© par e-mail des rÃ©actions reÃ§ues sur vos messages.',
));
