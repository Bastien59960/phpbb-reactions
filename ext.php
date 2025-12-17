<?php
/**
 * Fichier : ext.php
 * Chemin : bastien59960/reactions/ext.php
 * Auteur : Bastien (bastien59960)
 * GitHub : https://github.com/bastien59960/reactions
 * @version 1.0.4
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
	 * Elle effectue aussi un nettoyage préventif pour éviter les conflits
	 * avec des installations précédentes échouées ou l'ancienne extension.
	 *
	 * @return bool True si phpBB >= 3.3.0, False sinon
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');

		// Nettoyage préventif avant activation
		$this->cleanup_before_enable();

		return phpbb_version_compare($config['version'], '3.3.0', '>=');
	}

	/**
	 * Nettoie les données orphelines avant activation
	 *
	 * Cette méthode nettoie :
	 * - Les entrées corrompues dans phpbb3_ext (installations échouées)
	 * - Les migrations partielles dans phpbb3_migrations
	 * - Les configs partielles
	 * - Les modules ACP de l'ancienne extension steve/postreactions
	 */
	private function cleanup_before_enable()
	{
		// Utiliser un flag statique pour ne nettoyer qu'une seule fois par requête
		static $cleanup_done = false;
		if ($cleanup_done) {
			return;
		}
		$cleanup_done = true;

		$db = $this->container->get('dbal.conn');
		$table_prefix = $this->container->getParameter('core.table_prefix');

		try {
			// 1. Nettoyer les modules ACP de l'ancienne extension steve/postreactions
			$sql = "DELETE FROM {$table_prefix}modules
					WHERE module_basename LIKE '%steve%postreactions%'
					AND module_class = 'acp'";
			$db->sql_query($sql);

			// 2. Ne PAS supprimer ACP_REACTIONS_TITLE car notre extension l'utilise
			// Le nettoyage de l'ancienne catégorie n'est fait que dans la migration
			// après avoir vérifié qu'il n'y a pas de conflits

			// NOTE: On ne touche PAS à phpbb3_ext ni aux migrations ici
			// car cela interfère avec le processus d'activation de phpBB

		} catch (\Exception $e) {
			// Ignorer les erreurs - le nettoyage est préventif
		}
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
		return '1.0.2';
	}

	/**
	 * Étape d'activation de l'extension
	 * 
	 * RÔLE SIMPLIFIÉ : Cette méthode se contente d'enregistrer les types de notifications
	 * auprès du notification_manager. La création des préférences utilisateur est gérée
	 * par la migration release_1_0_4.php, ce qui est plus propre et plus maintenable.
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
	 * @param mixed $old_state État précédent de l'extension
	 * @return mixed État de l'étape suivante
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
		
		// Laisser les migrations gérer la création des préférences utilisateur
		return parent::enable_step($old_state);
	}

	/**
	 * Étape de désactivation de l'extension
	 *
	 * Cette méthode est appelée par phpBB lors de la désactivation de l'extension.
	 * Elle désactive les types de notifications pour éviter les erreurs.
	 *
	 * NOTE : On ne supprime PAS les préférences utilisateur lors de la désactivation,
	 * seulement lors de la purge. Ainsi, si l'utilisateur réactive l'extension,
	 * ses préférences sont conservées.
	 *
	 * @param mixed $old_state État précédent de l'extension
	 * @return mixed Résultat de l'étape de désactivation parente
	 */
	public function disable_step($old_state)
	{
		// Récupérer le gestionnaire de notifications phpBB
		$notification_manager = $this->container->get('notification_manager');
		
		try {
			// Désactiver la notification cloche
			$notification_manager->disable_notifications('bastien59960.reactions.notification.type.reaction');
		
			// Désactiver la notification email digest
			$notification_manager->disable_notifications('bastien59960.reactions.notification.type.reaction_email_digest');
		} catch (\Exception $e) {
			// Ignorer les erreurs (par exemple si déjà désactivé)
		}
		
		return parent::disable_step($old_state);
	}

	/**
	 * Étape de purge de l'extension
	 *
	 * Cette méthode est appelée par phpBB lors de la purge de l'extension.
	 * Elle est responsable de la suppression de toutes les données de l'extension.
	 *
	 * La logique de purge (suppression des tables, configs, modules, préférences, etc.)
	 * est gérée par les fichiers de migration dans le dossier `migrations/`.
	 * La méthode `revert_data()` et `revert_schema()` de chaque fichier de migration
	 * est appelée dans l'ordre inverse des dépendances.
	 *
	 * @param mixed $old_state État précédent de l'extension
	 * @return mixed Résultat de l'étape de purge parente
	 */
	public function purge_step($old_state)
	{
		switch ($old_state)
		{
			case '': // Première étape : nettoyage des préférences utilisateur
				$db = $this->container->get('dbal.conn');
				$tables = $this->container->getParameter('tables');
				
				try {
					// Supprimer toutes les préférences de notification liées à l'extension
					$sql = 'DELETE FROM ' . $tables['user_notifications'] . "
						WHERE item_type LIKE 'bastien59960.reactions.notification.type.%'";
					$db->sql_query($sql);
				} catch (\Exception $e) {
					// Ignorer les erreurs (table peut ne pas exister)
				}
				
				// Passer à l'étape suivante (migrations)
				return 'migrations';
			
			case 'migrations': // Deuxième étape : exécution des revert des migrations
				// Les migrations vont supprimer les tables, colonnes, configs, modules, etc.
				return parent::purge_step($old_state);
		}
		
		// Finalisation
		return parent::purge_step($old_state);
	}
}