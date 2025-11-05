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
		return parent::enable_step($old_state);
	}


	/**
	public function disable_step($old_state)
	{
		
		if ($old_state === false)
		{
			// Récupérer le gestionnaire de notifications phpBB
			$notification_manager = $this->container->get('notification_manager');
			
			// Désactiver la notification cloche
			$notification_manager->disable_notifications('notification.type.reaction');
		
			// Désactiver la notification email digest
			$notification_manager->disable_notifications('notification.type.reaction_email_digest');
		}
		
		return parent::disable_step($old_state);
	}

}
