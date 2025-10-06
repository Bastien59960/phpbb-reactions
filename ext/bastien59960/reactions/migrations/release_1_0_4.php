<?php
/**
 * Migration release_1_0_4 : Ajout des colonnes de préférences utilisateur pour les réactions
 * 
 * Cette migration ajoute deux colonnes dans la table `phpbb_users` :
 * - user_reactions_notify       (bool) : active/désactive les notifications internes (cloche)
 * - user_reactions_cron_email   (bool) : active/désactive le cron d'envoi d'e-mails de réactions
 * 
 * Objectif :
 * Permettre à chaque utilisateur de gérer depuis le UCP (Panneau de Contrôle Utilisateur)
 * s’il souhaite recevoir ou non :
 *  - des notifications internes (dans le forum)
 *  - des e-mails de réactions envoyés par le cron
 * 
 * Les deux options sont activées par défaut (valeur = 1).
 * 
 * Notes :
 * - Le test `effectively_installed()` vérifie directement la présence des colonnes.
 * - L’opération est idempotente : si les colonnes existent déjà, rien n’est refait.
 * - Le rollback supprime proprement les deux colonnes.
 * - Dépend de release_1_0_3 (type de notification déjà enregistré).
 * 
 * @package bastien59960.reactions
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bastien59960\reactions\migrations;

class release_1_0_4 extends \phpbb\db\migration\migration
{
	/**
	 * Vérifie si la migration est déjà appliquée :
	 * On teste si la colonne user_reactions_notify existe.
	 */
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_reactions_notify');
	}

	/**
	 * Dépendances : release_1_0_3 doit être appliquée avant celle-ci.
	 */
	public static function depends_on()
	{
		return array('bastien59960\reactions\migrations\release_1_0_3');
	}

	/**
	 * Modification du schéma : ajout des deux colonnes.
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
	 * Données supplémentaires : ajoute une entrée de config pour indiquer que c’est installé.
	 */
	public function update_data()
	{
		return array(
			array('config.add', array('reactions_ucp_preferences_installed', 1)),
		);
	}

	/**
	 * Rollback : suppression des colonnes.
	 * ⚠️ Attention : cela supprimera les préférences utilisateur pour cette fonctionnalité.
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
