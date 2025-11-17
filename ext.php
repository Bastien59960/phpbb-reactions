<?php
/**
 * Fichier : ext.php
 * Chemin : bastien59960/reactions/ext.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 * @version 1.0.3
 *
 * Rôle :
 * Ce fichier est la classe principale et le point d'entrée de l'extension pour
 * le système de phpBB. Il agit comme un "chef d'orchestre" en gérant le cycle
 * de vie complet de l'extension : vérification de la compatibilité avant
 * installation, actions à l'activation (ex: enregistrement des notifications),
 * à la désactivation et à la purge complète des données.
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
		return '1.0.3';
	}

	/**
	 * Étape d'activation de l'extension
	 * 
	 * Cette méthode est appelée par phpBB lors de l'activation de l'extension.
	 * Elle enregistre les types de notifications auprès du système de notifications phpBB.
	 * 
	 * L'extension Reactions possède DEUX types de notifications :
	 * 
	 * 1️⃣ reaction (notification cloche instantanée)
	 *    - Défini dans : notification/type/reaction.php
	 *    - Utilisé pour : Notifier immédiatement l'auteur d'un post qu'on a réagi
	 * 
	 * 2️⃣ reaction_email_digest (notification email groupée)
	 *    - Défini dans : notification/type/reaction_email_digest.php
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
		$notification_manager = $this->container->get('notification_manager');
		
		try {
			// On enregistre les types de notifications. Si une exception est levée
			// (parce qu'ils existent déjà), on l'ignore.
			$notification_manager->enable_notifications('bastien59960.reactions.notification.type.reaction');
			$notification_manager->enable_notifications('bastien59960.reactions.notification.type.reaction_email_digest');
		} catch (\Exception $e) {
			// Ignorer l'exception si les types sont déjà activés.
		}
		
		// CORRECTION : Toujours retourner 'notification' pour forcer phpBB à
		// peupler les préférences pour tous les utilisateurs, même lors d'une réactivation.
		// C'est cette instruction qui déclenche la création des lignes dans phpbb_user_notifications.
		return 'notification';
	}

	/**
	 * Étape de désactivation de l'extension
	 *
	 * Cette méthode est appelée par phpBB lors de la désactivation de l'extension.
	 * Elle désactive les types de notifications pour éviter les erreurs.
	 *
	 * @param mixed $old_state État précédent de l'extension
	 * @return mixed Résultat de l'étape de désactivation parente
	 */
	public function disable_step($old_state)
	{
		// Récupérer le gestionnaire de notifications phpBB
		$notification_manager = $this->container->get('notification_manager');
		
		// Désactiver la notification cloche
		$notification_manager->disable_notifications('bastien59960.reactions.notification.type.reaction');
	
		// Désactiver la notification email digest
		$notification_manager->disable_notifications('bastien59960.reactions.notification.type.reaction_email_digest');
		
		return parent::disable_step($old_state);
	}

	/**
	 * Étape de purge de l'extension
	 *
	 * Cette méthode est appelée par phpBB lors de la purge de l'extension.
	 * Elle est responsable de la suppression de toutes les données de l'extension.
	 *
	 * @param mixed $old_state État précédent de l'extension
	 * @return mixed Résultat de l'étape de purge parente
	 */
	public function purge_step($old_state)
	{
		// La logique de purge (suppression des tables, configs, modules, etc.)
		// est gérée par les fichiers de migration dans le dossier `migrations/`.
		// La méthode `revert()` de chaque fichier de migration est appelée.
		// Il est crucial que chaque méthode `revert()` retourne un tableau,
		// même s'il est vide, pour éviter une erreur fatale.
		return parent::purge_step($old_state);
	}

}
