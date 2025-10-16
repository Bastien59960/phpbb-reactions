<?php
/**
 * Fichier : ext.php
 * Chemin : bastien59960/reactions/ext.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 *
 * Rôle :
 * Ce fichier est la classe principale et le point d'entrée de l'extension pour phpBB.
 * Il gère le cycle de vie de l'extension : activation, désactivation, et purge des
 * données. Il est responsable de l'enregistrement des types de notifications
 * personnalisés auprès du système phpBB.
 *
 * @copyright (c) 2025 Bastien59960
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions;

/**
 * Classe principale de l'extension
 * 
 * Gère l'activation, désactivation et la configuration des notifications
 * de l'extension Reactions.
 */
class ext extends \phpbb\extension\base
{
	/**
	 * Vérifie si l'extension peut être activée
	 * 
	 * Cette méthode est appelée par phpBB AVANT d'activer l'extension.
	 * Elle permet de vérifier que l'environnement est compatible.
	 * 
	 * @return bool True si phpBB >= 3.3.0, False sinon
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		return phpbb_version_compare($config['version'], '3.3.0', '>=');
	}

	/**
	 * Retourne la version actuelle de l'extension
	 * 
	 * Cette version DOIT correspondre aux migrations présentes dans le dossier migrations/
	 * Si la version change, phpBB exécutera les nouvelles migrations automatiquement.
	 * 
	 * @return string Version de l'extension (doit correspondre aux migrations)
	 */
	public function get_version()
	{
		return '1.0.1';
	}

	/**
	 * Étape d'activation de l'extension
	 * 
	 * Cette méthode est appelée par phpBB lors de l'activation de l'extension.
	 * Elle enregistre les types de notifications auprès du système de notifications phpBB.
	 * 
	 * CORRECTION CRITIQUE :
	 * On doit utiliser les NOMS DE TYPES (get_type()) et NON les noms de services.
	 * 
	 * L'extension Reactions possède DEUX types de notifications :
	 * 
	 * 1️⃣ notification.type.reaction (notification cloche instantanée)
	 *    - Défini dans : notification/type/reaction.php
	 *    - Méthode get_type() retourne : 'notification.type.reaction'
	 *    - Utilisé pour : Notifier immédiatement l'auteur d'un post qu'on a réagi
	 * 
	 * 2️⃣ notification.type.reaction_email_digest (notification email groupée)
	 *    - Défini dans : notification/type/reaction_email_digest.php
	 *    - Méthode get_type() retourne : 'notification.type.reaction_email_digest'
	 *    - Utilisé pour : Envoyer un résumé périodique par email (cron)
	 * 
	 * Ces noms DOIVENT correspondre EXACTEMENT à ce qui est :
	 * - Retourné par la méthode get_type() de chaque classe
	 * - Stocké dans phpbb_notification_types (colonne notification_type_name)
	 * - Créé par la migration (migrations/release_1_0_0.php)
	 * 
	 * @param mixed $old_state État précédent de l'extension (false = première activation)
	 * @return string|mixed 'notification' si première activation, sinon résultat parent
	 */
	public function enable_step($old_state)
	{
		// TEMPORAIREMENT DÉSACTIVÉ POUR LE TEST DU CRON
		// if ($old_state === false)
		// {
		//     // Récupérer le gestionnaire de notifications phpBB
		//     $notification_manager = $this->container->get('notification_manager');
		//
		//     // ✅ Utiliser uniquement les NOMS DE TYPES (get_type())
		//     // Activation du type "cloche" (instantané)
		//     try {
		//         $notification_manager->enable_notifications('notification.type.reaction');
		//     } catch (\phpbb\notification\exception $e) {
		//         if (defined('DEBUG')) {
		//             trigger_error('[Reactions] enable_notifications(reaction) failed: ' . $e->getMessage(), E_USER_NOTICE);
		//         }
		//     }
		//
		//     // Activation du type "email digest" (cron)
		//     try {
		//         $notification_manager->enable_notifications('notification.type.reaction_email_digest');
		//     } catch (\phpbb\notification\exception $e) {
		//         if (defined('DEBUG')) {
		//             trigger_error('[Reactions] enable_notifications(reaction_email_digest) failed: ' . $e->getMessage(), E_USER_NOTICE);
		//         }
		//     }
		//
		//     return 'notification';
		// }

		return parent::enable_step($old_state);
	}


	/**
	 * Étape de désactivation de l'extension
	 * 
	 * Cette méthode est appelée par phpBB lors de la désactivation de l'extension.
	 * Elle désactive les types de notifications (mais ne les supprime PAS de la base).
	 * 
	 * Les notifications existantes restent en base mais ne sont plus actives.
	 * L'utilisateur peut réactiver l'extension sans perdre les notifications passées.
	 * 
	 * @param mixed $old_state État précédent de l'extension (false = première désactivation)
	 * @return string|mixed 'notification' si première désactivation, sinon résultat parent
	 */
	public function disable_step($old_state)
	{
		// TEMPORAIREMENT DÉSACTIVÉ POUR LE TEST DU CRON
		// if ($old_state === false)
		// {
		// 	// Récupérer le gestionnaire de notifications phpBB
		// 	$notification_manager = $this->container->get('notification_manager');
		// 	
		// 	// ✅ CORRECTION : Utiliser les NOMS DE TYPES (get_type())
		// 	
		// 	// Désactiver la notification cloche
		// 	$notification_manager->disable_notifications('notification.type.reaction');
		//
		// 	// Désactiver la notification email digest
		// 	$notification_manager->disable_notifications('notification.type.reaction_email_digest');
		// 	
		// 	return 'notification';
		// }
		
		return parent::disable_step($old_state);
	}

	/**
	 * Étape de purge de l'extension
	 * 
	 * Cette méthode est appelée par phpBB lors de la SUPPRESSION DÉFINITIVE de l'extension.
	 * Elle supprime TOUTES les notifications de l'extension de la base de données.
	 * 
	 * ⚠️  ATTENTION : Cette action est IRRÉVERSIBLE
	 * Toutes les notifications existantes seront définitivement supprimées.
	 * 
	 * La purge est différente de la désactivation :
	 * - Désactivation : Les données restent, mais l'extension est inactive
	 * - Purge : Les données sont supprimées définitivement
	 * 
	 * Lors de la purge, phpBB va également :
	 * 1. Exécuter les méthodes revert_data() et revert_schema() des migrations
	 * 2. Supprimer les tables créées par l'extension
	 * 3. Supprimer les colonnes ajoutées par l'extension
	 * 4. Supprimer les configurations de l'extension
	 * 
	 * @param mixed $old_state État précédent de l'extension (false = première purge)
	 * @return string|mixed 'notification' si première purge, sinon résultat parent
	 */
	public function purge_step($old_state)
	{
		// TEMPORAIREMENT DÉSACTIVÉ POUR LE TEST DU CRON
		// if ($old_state === false)
		// {
		//     $notification_manager = $this->container->get('notification_manager');
		//
		//     try {
		//         $notification_manager->purge_notifications('notification.type.reaction');
		//     } catch (\phpbb\notification\exception $e) {
		//         if (defined('DEBUG')) {
		//             trigger_error('[Reactions] purge_notifications(reaction) failed: ' . $e->getMessage(), E_USER_NOTICE);
		//         }
		//     }
		//
		//     try {
		//         $notification_manager->purge_notifications('notification.type.reaction_email_digest');
		//     } catch (\phpbb\notification\exception $e) {
		//         if (defined('DEBUG')) {
		//             trigger_error('[Reactions] purge_notifications(reaction_email_digest) failed: ' . $e->getMessage(), E_USER_NOTICE);
		//         }
		//     }
		//
		//     return 'notification';
		// }

		return parent::purge_step($old_state);
	}

}
