<?php
/**
 * ============================================================================
 * MIGRATION release_1_0_4
 * ============================================================================
 * 
 * Ajoute les préférences utilisateur pour les réactions :
 *  - user_reactions_notify      → active/désactive les notifications internes (cloche)
 *  - user_reactions_cron_email  → active/désactive le cron d’envoi d’e-mails
 * 
 * Cette migration effectue également un nettoyage automatique des éventuelles
 * notifications orphelines (type_id inexistant) afin d’éviter les erreurs
 * du type "phpbb\notification\exception: NOTIFICATION_TYPE_NOT_EXIST".
 * 
 * Notes :
 * - Idempotente : aucun effet si les colonnes existent déjà.
 * - Inclut une étape de maintenance légère sur la table des notifications.
 * - Le rollback supprime proprement les deux colonnes et la config.
 * 
 * ⚠️ À REVOIR lors de la fusion des fichiers de migration pour la version stable :
 *    - Ce patch de nettoyage pourra être déplacé dans une migration dédiée
 *      ou intégré dans une étape d’installation initiale.
 * 
 * @package bastien59960.reactions
 * @license GNU General Public License, version 2 (GPL-2.0)
 * ============================================================================
 */

namespace bastien59960\reactions\migrations;

class release_1_0_4 extends \phpbb\db\migration\migration
{
	/**
	 * Vérifie si la migration est déjà appliquée :
	 * On teste simplement la présence de la colonne user_reactions_notify.
	 */
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_reactions_notify');
	}

	/**
	 * Dépendances : la version 1_0_3 doit être appliquée avant celle-ci.
	 */
	public static function depends_on()
	{
		return array('bastien59960\reactions\migrations\release_1_0_3');
	}

	/**
	 * Modification du schéma : ajout des deux colonnes de préférences.
	 */
	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'users' => array(
					'user_reactions_notify' => array('BOOL', 1),
					'user_reactions_cron_email' => array('BOOL', 1),
				),
			),
		);
	}

	/**
	 * Données supplémentaires : 
	 * - ajoute une entrée de config
	 * - nettoie les notifications orphelines (type_id inexistant)
	 */
	public function update_data()
	{
		return array(
			// Indique que la configuration UCP est installée
			array('config.add', array('reactions_ucp_preferences_installed', 1)),

			// Nettoyage automatique des notifications orphelines
			array(array($this, 'clean_orphan_notifications')),
		);
	}

	/**
	 * Nettoyage des notifications orphelines.
	 * 
	 * Supprime les lignes de phpbb_notifications dont le type_id ne correspond
	 * à aucun enregistrement de phpbb_notification_types.
	 * 
	 * Protégé contre les tables manquantes ou les erreurs SQL.
	 */
	public function clean_orphan_notifications()
	{
		$notifications_table = $this->table_prefix . 'notifications';
		$types_table = $this->table_prefix . 'notification_types';

		try
		{
			$sql = "
				DELETE FROM {$notifications_table}
				WHERE notification_type_id NOT IN (
					SELECT notification_type_id FROM {$types_table}
				)
			";
			$this->db->sql_query($sql);
		}
		catch (\Throwable $e)
		{
			// En mode debug, on logge une notice PHPBB
			if (defined('DEBUG'))
			{
				trigger_error('[Reactions] ⚠️ Échec du nettoyage des notifications orphelines : ' . $e->getMessage(), E_USER_NOTICE);
			}
		}
	}

	/**
	 * Rollback : suppression des colonnes utilisateur.
	 */
	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'users' => array(
					'user_reactions_notify',
					'user_reactions_cron_email',
				),
			),
		);
	}

	/**
	 * Rollback des données (config).
	 */
	public function revert_data()
	{
		return array(
			array('config.remove', array('reactions_ucp_preferences_installed')),
		);
	}
}
