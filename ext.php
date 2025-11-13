<?php
/**
 * @package    bastien59960/reactions
 * @author     Bastien (bastien59960) - https://github.com/bastien59960
 * @copyright  (c) 2025 Bastien59960
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Fichier : /ext.php
 * Rôle :
 * Ce fichier est la classe principale et le point d'entrée de l'extension.
 * Il gère le cycle de vie de l'extension (activation, désactivation, purge)
 * et est responsable de la vérification de la version de phpBB et de
 * l'enregistrement des types de notifications.
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
		return '1.0.1'; // La version doit correspondre à la nouvelle migration
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
		if ($old_state === false)
		{
			// Récupérer le gestionnaire de notifications phpBB
			$notification_manager = $this->container->get('notification_manager');

			// Enregistrer la notification cloche
			$notification_manager->enable_notifications('notification.type.reaction');

			// Enregistrer la notification email digest
			$notification_manager->enable_notifications('notification.type.reaction_email_digest');

			return 'notification'; // Indique à phpBB que des types de notifs ont été gérés
		}
		return parent::enable_step($old_state);
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
		$notification_manager->disable_notifications('notification.type.reaction');
	
		// Désactiver la notification email digest
		$notification_manager->disable_notifications('notification.type.reaction_email_digest');
		
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
